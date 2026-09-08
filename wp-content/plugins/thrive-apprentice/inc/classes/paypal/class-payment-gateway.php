<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

use TVA\PayPal\Credentials;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_PayPal_Payment_Gateway
 *
 * Webhook gate: HMAC verification → idempotency guard → event dispatch.
 */
class TVA_PayPal_Payment_Gateway {

	/**
	 * HTTP header name sent by the Product API on each webhook delivery.
	 * Value format: raw lowercase hex HMAC-SHA256 of the raw body (no "sha256=" prefix).
	 * Algorithm confirmed via X-Thrive-Webhook-Algorithm: HMAC-SHA256 header.
	 */
	const SIGNATURE_HEADER = 'X-Thrive-Webhook-Signature';

	/** @var string Raw request body */
	private $raw_body;

	/** @var string Incoming signature from the request header */
	private $signature;

	/** @var array Decoded event payload */
	private $event;

	/** @var string 'live' or 'test' */
	private $mode;

	/**
	 * @param string $raw_body  Raw JSON request body (from $request->get_body()).
	 * @param string $signature Signature header value (from $request->get_header()).
	 * @param string $mode      'live' or 'test' — determined by the merchant's connected mode.
	 */
	public function __construct( string $raw_body, string $signature, string $mode = 'live' ) {
		$this->raw_body  = $raw_body;
		$this->signature = $signature;
		$this->mode      = $mode;
		$this->event     = json_decode( $raw_body, true ) ?: [];
	}

	/**
	 * Run the full pipeline: verify → idempotency (INSERT-first) → dispatch.
	 *
	 * Idempotency uses INSERT-first to close the TOCTOU race: mark_seen() attempts
	 * the INSERT and returns false if the UNIQUE KEY fires (duplicate). Only on a
	 * successful INSERT do we dispatch — no separate is_duplicate() check needed.
	 *
	 * @param string $raw_body
	 * @param string $signature
	 * @param string $mode
	 * @return bool
	 */
	public static function handle( string $raw_body, string $signature, string $mode = 'live' ): bool {
		$gateway = new static( $raw_body, $signature, $mode );

		// Onboarding finalize nudge: during the provisioning lag there is no webhook
		// secret yet, so verify_signature() would fail closed and this event would be
		// lost. Recognize it as a TRIGGER ONLY — read nothing from the body except
		// merchant_id (matched against our own pending record), grant/store nothing
		// from the event, and complete via the same finalize path the poll/retry use.
		if ( $gateway->maybe_handle_onboarding_nudge() ) {
			return true; // 200 — handled as a trigger, nothing granted from the body.
		}

		if ( ! $gateway->verify_signature() ) {
			TVA_Logger::set_type( 'PayPal Webhook' );
			TVA_Logger::log( 'signature_failed', [ 'mode' => $mode ], true );
			return false;
		}

		$event_id = $gateway->get_event_id();

		if ( empty( $event_id ) ) {
			return false;
		}

		// INSERT-first idempotency — relies on UNIQUE KEY `event_id`.
		// Returns false when the row already exists (duplicate delivery).
		if ( ! $gateway->mark_seen( $event_id ) ) {
			return true; // Already processed — return 200 to prevent PayPal retry.
		}

		$gateway->dispatch();

		return true;
	}

	/**
	 * Verify HMAC-SHA256 signature.
	 *
	 * Fails CLOSED — rejects the request when no webhook_secret is stored.
	 * This prevents unauthenticated webhook processing on a public endpoint.
	 *
	 * @return bool
	 */
	protected function verify_signature(): bool {
		$secret = Credentials::get_webhook_secret( $this->mode );

		if ( empty( $secret ) ) {
			TVA_Logger::set_type( 'PayPal Webhook' );
			TVA_Logger::log( 'no_webhook_secret', [ 'mode' => $this->mode ], true );
			return false;
		}

		// Normalize: Product API sends lowercase hex, but proxies may uppercase or add whitespace.
		$incoming = strtolower( trim( $this->signature ) );
		$expected = hash_hmac( 'sha256', $this->raw_body, $secret );

		return hash_equals( $expected, $incoming );
	}

	/**
	 * Whether an incoming UNSIGNED event should be treated as an onboarding-finalize
	 * trigger (and nothing more). True only during the provisioning lag: no webhook
	 * secret yet, the event is MERCHANT.ONBOARDING.COMPLETED, and its merchant_id
	 * matches the merchant we recorded as pending. Pure — no I/O, fully unit-checkable.
	 *
	 * @param bool   $has_webhook_secret   Whether a webhook secret is already stored for the mode.
	 * @param string $event_type           event_type from the body.
	 * @param string $body_merchant_id     resource.merchant_id from the body.
	 * @param string $pending_merchant_id  merchant_id from our pending-onboarding record.
	 * @return bool
	 */
	protected static function should_handle_unsigned_nudge( bool $has_webhook_secret, string $event_type, string $body_merchant_id, string $pending_merchant_id ): bool {
		if ( $has_webhook_secret ) {
			return false; // creds exist — normal HMAC path handles everything
		}
		if ( 'MERCHANT.ONBOARDING.COMPLETED' !== $event_type ) {
			return false;
		}
		if ( '' === $pending_merchant_id || '' === $body_merchant_id ) {
			return false;
		}
		return hash_equals( $pending_merchant_id, $body_merchant_id );
	}

	/**
	 * Pre-verification onboarding nudge. Reads ONLY event_type and
	 * resource.merchant_id from the body — never grants or stores anything from the
	 * event. When the gate passes, completion is delegated to the controller's
	 * finalize_onboarding(), the same path used by the poll/retry flow.
	 *
	 * @return bool True when the event was consumed as an onboarding trigger (handle()
	 *              should return 200 immediately); false to fall through to the HMAC path.
	 */
	private function maybe_handle_onboarding_nudge(): bool {
		// This runs BEFORE signature verification on a public endpoint, so the body is
		// fully attacker-controlled. A non-array event (scalar JSON) or non-array
		// `resource` would make the offset reads below throw a TypeError — `?? ''` does
		// not suppress that. Guard both and fall through to the fail-closed HMAC path.
		if ( ! is_array( $this->event ) ) {
			return false;
		}
		$resource         = ( isset( $this->event['resource'] ) && is_array( $this->event['resource'] ) ) ? $this->event['resource'] : [];
		$body_merchant_id = sanitize_text_field( $resource['merchant_id'] ?? '' );

		$pending = Credentials::get_onboarding_pending( $this->mode );

		// Bound the unsigned-nudge window: only honor it while the pending onboarding is
		// fresh. Past the TTL the onboarding session is dead, so fall through to the
		// fail-closed HMAC path instead of triggering finalize on a stale record.
		if ( time() - (int) ( $pending['created_at'] ?? 0 ) > \TVA_PayPal_Controller::PENDING_ONBOARDING_TTL ) {
			return false;
		}

		if ( ! static::should_handle_unsigned_nudge(
			'' !== Credentials::get_webhook_secret( $this->mode ),
			sanitize_text_field( $this->event['event_type'] ?? '' ),
			$body_merchant_id,
			(string) ( $pending['merchant_id'] ?? '' )
		) ) {
			return false;
		}

		TVA_Logger::set_type( 'PayPal Webhook' );
		TVA_Logger::log( 'onboarding_nudge', [ 'mode' => $this->mode ], true );

		( new TVA_PayPal_Controller() )->finalize_onboarding( $this->mode );

		return true;
	}

	/**
	 * Extract the event ID from the payload.
	 *
	 * @return string
	 */
	protected function get_event_id(): string {
		return sanitize_text_field( $this->event['id'] ?? '' );
	}

	/**
	 * Attempt to record event_id as processed. Returns true only when the row
	 * was newly inserted — false means it was already seen (duplicate delivery).
	 *
	 * Relies on UNIQUE KEY `event_id` in tva_paypal_seen_webhooks to close the
	 * TOCTOU race. No separate is_duplicate() check needed.
	 *
	 * suppress_errors() prevents duplicate-key violations from polluting PHP error logs —
	 * they are expected and handled gracefully here. On unexpected DB errors (missing table,
	 * read-only replica) the method fails closed and returns false so the event is not
	 * dispatched. PayPal will not retry because the listener always returns 200; the error
	 * is logged for investigation.
	 *
	 * @param string $event_id
	 * @return bool True = new event, proceed to dispatch. False = duplicate, skip.
	 */
	protected function mark_seen( string $event_id ): bool {
		global $wpdb;
		$table = $wpdb->prefix . TVA_Const::DB_PREFIX . 'paypal_seen_webhooks';

		$suppress = $wpdb->suppress_errors( true );
		$rows     = $wpdb->insert(
			$table,
			[
				'event_id'   => $event_id,
				'created_at' => current_time( 'mysql', true ),
			],
			[ '%s', '%s' ]
		);
		$wpdb->suppress_errors( $suppress );

		if ( $rows === 1 ) {
			return true;
		}

		// Duplicate key = already processed. Anything else = unexpected DB error.
		if ( strpos( $wpdb->last_error, '1062' ) === false && ! empty( $wpdb->last_error ) ) {
			TVA_Logger::set_type( 'PayPal Webhook' );
			TVA_Logger::log( 'mark_seen_db_error', [ 'event_id' => $event_id, 'error' => $wpdb->last_error ], true );
			return false;
		}

		return false;
	}

	/**
	 * Resolve and dispatch to the matching event handler class.
	 */
	protected function dispatch(): void {
		$event_type = sanitize_text_field( $this->event['event_type'] ?? '' );
		$class_name = TVA\PayPal\Events\Generic::get_class_name( $event_type );

		if ( ! class_exists( $class_name ) ) {
			TVA_Logger::set_type( 'PayPal Webhook' );
			TVA_Logger::log( 'no_handler', [ 'event_type' => $event_type ], true );
			return;
		}

		/** @var TVA\PayPal\Events\Generic $handler */
		$handler = new $class_name( $this->event, $this->mode );
		$handler->do_action();
	}
}

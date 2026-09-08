<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Buy_Now;

use TVA\PayPal\Grace_Period;
use TVA\PayPal\Request;
use TVA\PayPal\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Paypal
 *
 * Phase 1 — creates a PayPal order via the Orders API and returns the approve URL.
 */
class Paypal extends Generic {

	/**
	 * @var string
	 */
	private $product_id;

	/**
	 * @var string 'live' or 'test'
	 */
	private $mode;

	/**
	 * @var string Local URL to return the buyer to after activation.
	 */
	private $return_to;

	/**
	 * @param array $data
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
		$this->parse_data();
	}

	/**
	 * Extract fields from $this->data.
	 */
	protected function parse_data() {
		$this->product_id  = isset( $this->data['product_id'] ) ? (string) absint( $this->data['product_id'] ) : '';
		$raw_mode       = isset( $this->data['mode'] ) ? sanitize_key( $this->data['mode'] ) : '';
		$this->mode     = in_array( $raw_mode, [ 'live', 'test' ], true ) ? $raw_mode : 'live';

		// Buyer-facing URL to return to after PayPal approval + activation. Passed
		// from the checkout REST endpoint (the current course/checkout page). Validated
		// to a local URL; falls back to home on anything off-site or empty.
		$raw_return      = isset( $this->data['return_to'] ) ? (string) $this->data['return_to'] : '';
		$this->return_to = wp_validate_redirect( $raw_return, home_url( '/' ) );
	}

	/**
	 * Valid when merchant_id and site_secret are set for the configured mode and a product_id is set.
	 *
	 * @return bool
	 */
	public function is_valid() {
		if ( empty( $this->product_id ) ) {
			return false;
		}

		if ( ! \TVA\PayPal\Credentials::is_mode_enabled( $this->mode ) ) {
			return false;
		}

		// Both flags are only meaningful in live mode; sandbox accounts commonly return false.
		if ( 'live' === $this->mode && ! \TVA\PayPal\Credentials::is_email_confirmed() ) {
			return false;
		}

		if ( 'live' === $this->mode && ! \TVA\PayPal\Credentials::is_payments_receivable() ) {
			return false;
		}

		if ( \TVA\PayPal\Credentials::is_vetting_blocking( $this->mode ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Return the PayPal buyer-redirect URL for this product.
	 *
	 * Routes to get_subscription_url() when the product rule has is_subscription set;
	 * otherwise uses get_one_time_url().
	 * Returns '' on any failure (invalid credentials, missing rule, API error,
	 * unauthenticated buyer for subscriptions).
	 *
	 * @return string
	 */
	public function get_url() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		$rule = Settings::get_product_rule( (int) $this->product_id );
		if ( empty( $rule ) || empty( $rule['amount'] ) || empty( $rule['currency'] ) ) {
			return '';
		}

		if ( ! empty( $rule['is_subscription'] ) ) {
			return $this->get_subscription_url( $rule );
		}

		return $this->get_one_time_url( $rule );
	}

	/**
	 * One-time order — unchanged from Phase 1.
	 *
	 * @param array $rule
	 * @return string
	 */
	private function get_one_time_url( array $rule ): string {
		$amount    = number_format( (float) $rule['amount'], 2, '.', '' );
		$currency  = (string) $rule['currency'];
		$item_name = \TVA\PayPal\Order_Payload::truncate_for_paypal(
			sanitize_text_field( ( new \TVA\Product( (int) $this->product_id ) )->get_name() ) ?: 'PayPal order'
		);

		$order_data = [
			'data' => [
				'intent'         => 'CAPTURE',
				'purchase_units' => [
					[
						'reference_id' => (string) $this->product_id,
						'description'  => $item_name,
						'amount'       => [
							'currency_code' => $currency,
							'value'         => $amount,
							'breakdown'     => [
								'item_total' => [
									'currency_code' => $currency,
									'value'         => $amount,
								],
							],
						],
						'items' => [
							[
								'name'        => $item_name,
								'description' => __( 'One-time purchase', 'thrive-apprentice' ),
								'quantity'    => '1',
								'unit_amount' => [
									'currency_code' => $currency,
									'value'         => $amount,
								],
								'sku'         => (string) $this->product_id,
							],
						],
					],
				],
			],
		];

		$response = Request::create_order( $order_data, $this->mode );

		if ( is_wp_error( $response ) ) {
			return '';
		}

		return $this->extract_url_by_rel( $response, 'approve' );
	}

	/**
	 * Rate-limit window for the abandoned-pending-order cleanup sweep.
	 */
	const PENDING_CLEANUP_INTERVAL = 30 * MINUTE_IN_SECONDS;

	/**
	 * How long an in-progress subscription checkout (a PENDING order + its stored
	 * payer-action URL) may be reused before it is treated as stale and a fresh one
	 * is created. Keeps repeated checkout calls from spawning duplicate live subscriptions
	 * without handing back an expired PayPal checkout URL.
	 */
	const SUBSCRIPTION_CHECKOUT_REUSE_TTL = 3 * HOUR_IN_SECONDS;

	/**
	 * Vault subscription order.
	 *
	 * Creates a pending TVA_Order WITH a product item before calling the Product API.
	 * The item is required so that renewal and revoke handlers can read product_id
	 * via get_order_items() without receiving an empty array.
	 *
	 * Requires an authenticated buyer — returns '' if user is not logged in.
	 *
	 * @param array $rule
	 * @return string
	 */
	private function get_subscription_url( array $rule ): string {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return '';
		}

		// Effective trial length for this product (0 = no trial). Computed up front so the
		// pending-checkout reuse check can compare it against the trial state stored on any
		// in-progress order, and reused below when building the create payload.
		$trial_days = isset( $rule['trial_days'] ) ? Settings::clamp_trial_days( $rule['trial_days'] ) : 0;

		// Reuse an in-progress checkout: if this buyer already has a recent PENDING PayPal
		// subscription order for this product, hand back its existing payer-action URL rather
		// than creating a second live PayPal subscription (repeated clicks / reloads / back
		// button). Stale in-progress orders — or ones whose trial terms no longer match the
		// current product config — are abandoned inside the helper.
		$reused = $this->reuse_pending_checkout_url( $user_id, $trial_days );
		if ( '' !== $reused ) {
			return $reused;
		}

		// Clean up stale abandoned pending orders at most once every 30 minutes.
		// Rate-limited so it doesn't run on every single checkout click.
		if ( false === get_transient( 'tva_paypal_pending_cleanup_ran' ) ) {
			set_transient( 'tva_paypal_pending_cleanup_ran', 1, self::PENDING_CLEANUP_INTERVAL );
			Grace_Period::cleanup_abandoned_pending_orders();
		}

		// Create a pending order with a product item so renewals can look up product_id.
		$pending = new \TVA_Order();
		$pending->set_user_id( $user_id );
		$pending->set_status( \TVA_Const::STATUS_PENDING );
		$pending->set_gateway( \TVA_Const::PAYPAL_GATEWAY );
		$pending->set_payment_method( \TVA_Const::PAYPAL_GATEWAY );
		$pending->set_type( \TVA_Order::PAID );
		$pending->set_price( (float) $rule['amount'] );
		$pending->set_currency( $rule['currency'] );

		$item = new \TVA_Order_Item();
		$item->set_product_id( (int) $this->product_id );
		// product_id is a Thrive Apprentice product term id, not a post id, so use the
		// term name (get_the_title() would look up an unrelated post or return empty).
		$product_title = ( new \TVA\Product( (int) $this->product_id ) )->get_name();
		$item->set_product_name( $product_title ?: 'PayPal subscription' );
		$item->set_quantity( 1 );
		$item->set_unit_price( (float) $rule['amount'] );
		$item->set_product_price( (float) $rule['amount'] );
		$item->set_total_price( (float) $rule['amount'] );
		$item->set_currency( $rule['currency'] );
		$pending->set_order_item( $item );

		$pending->save();
		$thrive_order_id = (int) $pending->get_id();

		if ( $thrive_order_id <= 0 ) {
			return '';
		}

		$recurring_times = \TVA\PayPal\Order_Payload::normalize_recurring_times( (string) ( $rule['recurring_times'] ?? '1 month' ) );
		if ( '' === $recurring_times ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save();
			return '';
		}

		// PayPal requires monetary values as a fixed 2-decimal string ("9.90", not "9.9"
		// or "10"); normalize once and reuse so amount.value, breakdown.item_total and
		// items[].unit_amount stay byte-identical and pass DECIMAL_PRECISION validation.
		$amount = number_format( (float) $rule['amount'], 2, '.', '' );

		// Pack the TVA order id (always, for webhook correlation) plus the optional buyer
		// reference into custom_id as JSON, mirroring the one-time checkout flow so PayPal's
		// transaction view shows the same details. Subscriptions are login-only, so
		// resolve_client_reference() (no email) uses the logged-in branch.
		$custom = [ 'o' => $thrive_order_id ];
		$reference = \TVA\PayPal\Order_Payload::resolve_client_reference( (int) $this->product_id );
		if ( '' !== $reference ) {
			$custom['u'] = $reference;
		}
		$custom_id = \TVA\PayPal\Order_Payload::pack_custom_id( $custom );
		$item_name = \TVA\PayPal\Order_Payload::truncate_for_paypal( sanitize_text_field( $product_title ?: 'PayPal subscription' ) );

		// Human-readable order-type line shown in PayPal's transaction details (items[].description),
		// so the merchant can tell a trial from a plain subscription at a glance. Renewals are billed
		// by PayPal from this subscription and inherit it — the plugin only builds this initial payload.
		$currency_safe = sanitize_text_field( $rule['currency'] );
		$price_phrase  = sprintf( '%s %s every %s', $currency_safe, $amount, $recurring_times );
		if ( $trial_days > 0 ) {
			// translators: 1: number of trial days, 2: recurring price phrase e.g. "USD 2.00 every 1 month".
			$item_description = sprintf( __( 'Subscription — %1$d-day free trial, then %2$s', 'thrive-apprentice' ), $trial_days, $price_phrase );
		} else {
			// translators: %s: recurring price phrase e.g. "USD 2.00 every 1 month".
			$item_description = sprintf( __( 'Subscription — %s', 'thrive-apprentice' ), $price_phrase );
		}
		$item_description = \TVA\PayPal\Order_Payload::truncate_for_paypal( $item_description );

		// recurring_times is a strtotime string — never cast to int.
		$data = [
			'intent'          => 'CAPTURE',
			'source'          => sanitize_key( $rule['source'] ?? 'paypal' ),
			'thrive_order_id' => $thrive_order_id,
			'purchase_units'  => [
				[
					'amount'       => [
						'currency_code' => $rule['currency'],
						'value'         => $amount,
						// item_total must equal the sum of items[].unit_amount * quantity.
						// For our single qty-1 line that equals the order amount; PayPal
						// rejects the order if items are present without a matching breakdown.
						'breakdown'     => [
							'item_total' => [
								'currency_code' => $rule['currency'],
								'value'         => $amount,
							],
						],
					],
					'reference_id' => (string) $this->product_id,
					'custom_id'    => $custom_id,
					'description'  => sanitize_text_field( $rule['description'] ?? '' ),
					// Line item so the product name/SKU show in PayPal's order details and
					// the buyer's receipt. Single qty-1 item; unit_amount matches the total.
					'items'        => [
						[
							'name'        => $item_name,
							'description' => $item_description,
							'quantity'    => '1',
							'unit_amount' => [
								'currency_code' => $rule['currency'],
								'value'         => $amount,
							],
							'sku'         => (string) $this->product_id,
						],
					],
				],
			],
			'recurring_times' => $recurring_times,
			'total_cycles'    => (int) ( $rule['total_cycles'] ?? 0 ),
			// PayPal's hosted page shows no recurring messaging for vault orders, so the
			// buyer must come back to OUR site to activate. We carry our pending-order id
			// in the return URL so the return handler can match without parsing custom_id.
			'application_context' => [
				'return_url' => add_query_arg( 'thrive_paypal_vault_return', $thrive_order_id, $this->return_to ),
				'cancel_url' => add_query_arg( 'thrive_paypal_vault_cancelled', $thrive_order_id, $this->return_to ),
			],
		];

		// Inject trial_days when a free trial is configured. Only sent when > 0 so
		// non-trial subscription payloads remain unchanged (Product API contract:
		// absence of the key = no trial; key present with value > 0 = trial active).
		// $trial_days was computed up front (see top of method).
		if ( $trial_days > 0 ) {
			$data['trial_days'] = $trial_days;
		}

		// Vault_Client::create_subscription() wraps body in { "data": ... }.
		$response = Request::create_subscription( $data, $this->mode );

		if ( is_wp_error( $response ) ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save(); // Best-effort cleanup; failure is non-fatal.
			return '';
		}

		if ( ! is_array( $response ) ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save(); // Best-effort cleanup; failure is non-fatal.
			return '';
		}

		$subscription_id = sanitize_text_field( $response['id'] ?? '' );

		if ( empty( $subscription_id ) ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save(); // Best-effort cleanup; failure is non-fatal.
			return '';
		}

		// Trials return a setup token (rel: approve); normal subs return rel: payer-action.
		$url = $this->extract_url_by_rel( $response, 'payer-action' );
		if ( '' === $url ) {
			$url = $this->extract_url_by_rel( $response, 'approve' );
		}

		if ( '' === $url ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save(); // Best-effort cleanup; failure is non-fatal.
			return '';
		}

		update_option( 'tva_paypal_vault_' . $thrive_order_id, [
			'subscription_id'  => $subscription_id,
			'thrive_order_id'  => $thrive_order_id,
			'mode'             => $this->mode,
			// Stored so a repeated checkout for this buyer+product can reuse the same
			// in-progress PayPal subscription instead of creating a duplicate (see
			// reuse_pending_checkout_url()).
			'payer_action_url' => $url,
			'created'          => time(),
			// Trial state stored so the activation return handler and cancel path can
			// detect whether this checkout was a free-trial setup token.
			'trialing'         => $trial_days > 0,
			'trial_days'       => $trial_days,
		], false );

		return $url;
	}

	/**
	 * Return the payer-action URL of a still-fresh in-progress subscription checkout for
	 * the current buyer + product, or '' when a fresh checkout should be created.
	 *
	 * Stops repeated /subscriptions/checkout calls from spawning multiple live PayPal
	 * subscriptions: a recent PENDING PayPal order whose stored payer-action URL is within
	 * SUBSCRIPTION_CHECKOUT_REUSE_TTL is handed back as-is. Older PENDING orders (or ones
	 * without a usable URL) are marked FAILED so they don't accumulate.
	 *
	 * @param int $user_id
	 * @return string Reusable payer-action URL, or '' when none is reusable.
	 */
	private function reuse_pending_checkout_url( int $user_id, int $current_trial_days ): string {
		$orders = \TVA_Order::get_orders_by_product( (int) $this->product_id, $user_id );
		if ( ! is_array( $orders ) ) {
			return '';
		}

		foreach ( $orders as $row ) {
			// get_orders_by_product() can't filter on STATUS_PENDING (0 is falsy in its
			// guard), so filter here; only this gateway's in-progress orders are relevant.
			if ( (int) ( $row['status'] ?? -1 ) !== \TVA_Const::STATUS_PENDING ) {
				continue;
			}
			if ( ( $row['gateway'] ?? '' ) !== \TVA_Const::PAYPAL_GATEWAY ) {
				continue;
			}

			$prev_id = (int) ( $row['ID'] ?? 0 );
			if ( $prev_id <= 0 ) {
				continue;
			}

			$vault    = get_option( 'tva_paypal_vault_' . $prev_id, [] );
			$prev_url = is_array( $vault ) && isset( $vault['payer_action_url'] ) ? (string) $vault['payer_action_url'] : '';
			$created  = is_array( $vault ) && isset( $vault['created'] ) ? (int) $vault['created'] : 0;

			// Only reuse when the stored trial terms still match the current product config.
			// Otherwise a merchant toggling the trial (or changing trial_days) would leave a
			// buyer with a recent pending order checking out on stale terms — a mismatch falls
			// through to the abandon block below so a fresh subscription is created.
			$prev_trial_days = is_array( $vault ) && isset( $vault['trial_days'] ) ? (int) $vault['trial_days'] : 0;

			if ( '' !== $prev_url && $created > 0
				&& ( time() - $created ) < self::SUBSCRIPTION_CHECKOUT_REUSE_TTL
				&& $prev_trial_days === $current_trial_days
				&& $this->is_paypal_url( $prev_url ) ) {
				return $prev_url;
			}

			// Stale / unusable in-progress order — abandon it so duplicates don't pile up.
			$prev = new \TVA_Order( $prev_id );
			if ( \TVA_Const::STATUS_PENDING === $prev->get_status() ) {
				$prev->set_status( \TVA_Const::STATUS_FAILED );
				$prev->save();
			}
		}

		return '';
	}

	/**
	 * Extract a URL from PayPal HATEOAS links by rel.
	 *
	 * @param array  $response
	 * @param string $rel  'approve' (one-time) or 'payer-action' (subscription)
	 * @return string
	 */
	private function extract_url_by_rel( array $response, string $rel ): string {
		if ( empty( $response['links'] ) || ! is_array( $response['links'] ) ) {
			return '';
		}

		foreach ( $response['links'] as $link ) {
			if ( isset( $link['rel'] ) && $link['rel'] === $rel && ! empty( $link['href'] ) ) {
				$href = (string) $link['href'];
				if ( filter_var( $href, FILTER_VALIDATE_URL ) !== false && $this->is_paypal_url( $href ) ) {
					return $href;
				}
			}
		}

		return '';
	}

	/**
	 * Whether a redirect URL is an https PayPal host.
	 *
	 * The buyer approve / payer-action URL always points at PayPal
	 * (www.paypal.com or www.sandbox.paypal.com). Reject anything else so a
	 * compromised/buggy Product API response can't redirect buyers off-site.
	 *
	 * @param string $url
	 * @return bool
	 */
	private function is_paypal_url( string $url ): bool {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || 'https' !== $parts['scheme'] || empty( $parts['host'] ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] );

		return 'paypal.com' === $host || str_ends_with( $host, '.paypal.com' );
	}
}

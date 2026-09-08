<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Hooks
 *
 * Central WordPress action/filter registration for the PayPal gateway.
 * Called once from inc/functions.php alongside Stripe_Hooks::init() and Square_Hooks::init().
 *
 * Registers:
 * - tva_admin_localize       — injects PayPal connection status and settings into admin JS.
 * - tva_buy_now_integrations — adds 'paypal' to the buy-now integration picker.
 *
 * No WP-cron is registered. Grace-period expiry is the Product API's responsibility
 * (THRIVE.ACCESS.REVOKE webhook). Abandoned-pending cleanup runs lazily at buy-now time
 * and is also available via `wp tva-paypal cleanup-pending`.
 */
class Hooks {

	/**
	 * Register all PayPal actions and filters.
	 *
	 * Called once from inc/functions.php alongside Stripe_Hooks::init()
	 * and Square_Hooks::init().
	 */
	public static function init() {
		static::add_actions();
		static::add_filters();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'tva-paypal', CLI::class );
		}
	}

	/**
	 * Register WordPress actions.
	 *
	 * Registers:
	 * - init → handle_vault_subscription_return() — catches PayPal's redirect-back
	 *   from a vault subscription approval and grants access.
	 *
	 * No WP-cron is registered. PayPal uses no WP-cron — grace-period expiry is the
	 * Product API's responsibility (THRIVE.ACCESS.REVOKE webhook), and abandoned-
	 * pending cleanup runs lazily at buy-now time / via `wp tva-paypal cleanup-pending`.
	 *
	 * Legacy note: an earlier (never-released) build scheduled a recurring
	 * 'tva_paypal_grace_period_check' cron event. No production install ever ran that build,
	 * so there is deliberately no auto-unschedule here. The only sites that still carry the
	 * orphaned event are our own internal QA/staging environments that ran the integration
	 * branch — clear it there manually with
	 * `wp cron event delete tva_paypal_grace_period_check`. A dangling event whose callback no
	 * longer exists is harmless: WP fires it and nothing handles it.
	 */
	public static function add_actions() {
		// Catch PayPal's redirect-back from the vault subscription approval. The
		// return/cancel URLs are set in Buy_Now\Paypal::get_subscription_url().
		// add_actions() runs from tva_init() on `init` priority 9, so this default-
		// priority (10) callback still fires later in the same `init` cycle.
		add_action( 'init', [ __CLASS__, 'handle_vault_subscription_return' ] );

		// Record a tva_transactions row whenever a PayPal capture completes (one-time,
		// vault activation, or renewal). Fired by Payment_Capture_Completed with args
		// ( $order, $is_renewal ).
		add_action( 'tva_paypal_payment_capture_completed', [ __CLASS__, 'record_capture_transaction' ], 10, 2 );
	}

	/**
	 * Record a tva_transactions row when a PayPal capture completes.
	 *
	 * Hooked on tva_paypal_payment_capture_completed — the single completion action fired
	 * by Payment_Capture_Completed for one-time, vault, and renewal captures (sync + webhook).
	 * The row is keyed on the capture id (the order's payment_id) so the synchronous and
	 * webhook paths converge on one row. debug_id is read from the order's vault option when
	 * a synchronous call stamped it (vault activation — see the controller); it stays empty
	 * for webhook-only completions, which carry no PayPal-Debug-Id.
	 *
	 * @param \TVA_Order $order      The completed order.
	 * @param bool       $is_renewal Whether this completion is a renewal cycle.
	 *
	 * @return void
	 */
	public static function record_capture_transaction( $order, $is_renewal = false ) {
		if ( ! $order instanceof \TVA_Order ) {
			return;
		}

		$capture_id = (string) $order->get_payment_id();
		if ( '' === $capture_id ) {
			return; // Trial activations complete with no capture — nothing to record.
		}

		$vault    = get_option( 'tva_paypal_vault_' . $order->get_id(), [] );
		$debug_id = is_array( $vault ) ? (string) ( $vault['debug_id'] ?? '' ) : '';

		\TVA_Transaction::record_paypal( array(
			'order_id'         => (int) $order->get_id(),
			'transaction_id'   => $capture_id,
			'currency'         => (string) $order->get_currency(),
			'price'            => (string) $order->get_price(),
			'price_gross'      => (string) $order->get_price_gross(),
			'gateway_fee'      => (string) $order->get_gateway_fee(),
			'transaction_type' => \TVA_Const::STATUS_COMPLETED,
			'debug_id'         => $debug_id,
		) );
	}

	/**
	 * Handle PayPal's redirect-back after a vault subscription approval.
	 *
	 * Success (payer-action flow):     ?thrive_paypal_vault_return=<order_id>&token=<subscription_id>&PayerID=...
	 * Success (setup-token flow):      ?thrive_paypal_vault_return=<order_id>&approval_token_id=...&approval_session_id=...
	 * Cancel:  ?thrive_paypal_vault_cancelled=<order_id>
	 *
	 * The two success flows differ only in the PayPal-appended params: a setup-token approval
	 * (HATEOAS rel "approve") carries NO `token`, whereas the payer-action flow (rel
	 * "payer-action") returns token=<subscription_id>. Which flow a subscription uses is a
	 * property of how PayPal vaulted it, NOT of whether it has a trial — both free-trial and
	 * non-trial vault-first subscriptions return through the setup-token shape. Both flows carry
	 * our own thrive_paypal_vault_return marker, so identification keys off that + the stored
	 * vault record, and the token check below is gated on token PRESENCE, not the trial flag.
	 *
	 * Vault subscriptions, unlike the one-time embedded checkout, have NO buyer-facing
	 * confirmation page and (per the Product API flow) fire NO PAYMENT.CAPTURE.COMPLETED
	 * webhook for the INITIAL activation — only renewals do. So the first grant must happen
	 * synchronously here: we activate the subscription (which captures the first payment)
	 * and then fulfil from that capture response via the exact same code path the one-time
	 * embedded `capture_order` endpoint uses (Payment_Capture_Completed::fulfill_from_capture_response()).
	 *
	 * Idempotent: the pending-status guard below short-circuits a reload, and
	 * fulfill_from_capture_response() itself no-ops on a second run (capture-id dedup +
	 * PENDING-status guard inside do_action()). PayPal's redirect can't carry our nonce, so
	 * the guards are: ownership (login is required, so the returning buyer owns the order),
	 * the order still being PENDING, and the Product API's own APPROVED-setup-token / capture
	 * enforcement. The payer-action flow additionally matches PayPal's returned token against the
	 * stored subscription_id; the setup-token flow has no such token, so it relies on the guards
	 * above.
	 */
	public static function handle_vault_subscription_return() {
		// Cancel branch.
		if ( isset( $_GET['thrive_paypal_vault_cancelled'] ) ) {
			$order_id = absint( $_GET['thrive_paypal_vault_cancelled'] );
			if ( $order_id > 0 ) {
				$order = new \TVA_Order( $order_id );
				if ( $order->get_id() && (int) $order->get_user_id() === get_current_user_id() && \TVA_Const::STATUS_PENDING === $order->get_status() ) {
					$order->set_status( \TVA_Const::STATUS_FAILED );
					$order->save();
				}
			}
			// Clean the cancel param from the URL and return the buyer to the page.
			self::redirect_after_vault_return();
		}

		// Fast bail on every unrelated request (this runs on EVERY `init`). Our own
		// thrive_paypal_vault_return marker is present on BOTH redirect flows; only the
		// PayPal-supplied params differ between them:
		//   - Payer-action flow: ...&token=<subscription_id>&PayerID=...
		//   - Setup-token "approve" flow: PayPal appends approval-session params
		//     (e.g. approval_token_id / approval_session_id), NOT token.
		// So we key off our own marker and resolve the subscription_id from the vault record
		// stored at create time, rather than from a PayPal-supplied param (which is absent on
		// the setup-token return). See Buy_Now\Paypal::get_subscription_url().
		if ( ! isset( $_GET['thrive_paypal_vault_return'] ) ) {
			return;
		}

		$order_id = absint( $_GET['thrive_paypal_vault_return'] );
		if ( $order_id <= 0 ) {
			return;
		}

		$order = new \TVA_Order( $order_id );
		// Ownership: login is required for a vault subscription, so the returning buyer owns
		// the pending order. get_user_id() is empty for an order that didn't load from the DB.
		if ( (int) $order->get_user_id() !== get_current_user_id() || get_current_user_id() <= 0 ) {
			return;
		}

		// subscription_id (and mode, below) come from the vault record we stored at create
		// time, keyed by order id — not from the redirect URL.
		$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );
		if ( empty( $vault['subscription_id'] ) ) {
			return;
		}
		$subscription_id = (string) $vault['subscription_id'];

		// Discriminate on the RETURN SHAPE, not on whether this was a trial: every vault-first
		// subscription (trial or not) comes back through this same handler, and only the
		// payer-action flow carries a `token`. Keying off `token` presence — rather than the
		// stored `trialing` flag — keeps non-trial vault-first subs from silently stalling in
		// the require-token branch below (their setup-token return has no token, exactly like a
		// trial's). The trial-vs-capture fulfilment split happens later, off the activate
		// response's `status`, so it stays correct for both regardless of this gate.
		if ( isset( $_GET['token'] ) ) {
			// Payer-action flow: PayPal returns token=<subscription_id>. Verify it matches what
			// we stored, preserving the original guard.
			$returned_token = sanitize_text_field( wp_unslash( $_GET['token'] ) );
			if ( '' === $returned_token || ! hash_equals( $subscription_id, $returned_token ) ) {
				return;
			}
		}
		// Setup-token "approve" flow (free trial OR non-trial vault-first): PayPal does NOT return
		// token, so there is no PayPal-supplied value to match. Identification rests on our
		// order-id marker + ownership + the stored vault record, and — critically — the Product
		// API only activates when the setup token is genuinely APPROVED at PayPal, so a
		// forged/replayed return cannot grant access or move money.

		// Idempotency: only act while still pending. A reload (or a renewal webhook that has
		// already completed the order) leaves this a no-op.
		if ( \TVA_Const::STATUS_PENDING !== $order->get_status() ) {
			// Already activated (reload, or a renewal webhook already completed it): clean the
			// PayPal params from the URL and land the buyer on the now-accessible page.
			self::redirect_after_vault_return();
		}

		$mode = isset( $vault['mode'] ) ? (string) $vault['mode'] : 'live';

		// Atomic activation lock. The PENDING check above is check-then-act, so two concurrent
		// returns (buyer reload / duplicate redirect) could both pass it. This mirrors
		// TVA_PayPal_Controller::acquire_activation_lock() EXACTLY — same key format and same TTL
		// — so the REST path and this return handler share the key and are mutually exclusive:
		// only one of N concurrent requests across BOTH paths can activate. We replicate the full
		// fast-path + stale-lock reclaim inline (rather than calling the protected controller
		// method across classes) because this redirect/return flow has no sibling path that
		// reclaims a stale lock; without the reclaim, an activation that dies mid-flight (e.g. a
		// PHP timeout after the row is inserted but before delete_option) would leave the lock
		// forever, so every buyer reload would silently bail and the order would stay PENDING.
		$lock_key = 'tva_paypal_activating_' . $subscription_id;
		// TTL mirrors TVA_PayPal_Controller::ACTIVATION_LOCK_TTL (60s) — referenced directly so the
		// two activation paths can never drift out of sync.
		$lock_ttl = \TVA_PayPal_Controller::ACTIVATION_LOCK_TTL;

		// Fast path: add_option() is a DB INSERT that fails atomically when the row already exists
		// (option_name is uniquely indexed), so concurrent double-clicks / duplicate returns can
		// never both acquire a fresh lock. 'no' autoload keeps these short-lived locks out of the
		// alloptions cache.
		$lock_acquired = add_option( $lock_key, (string) time(), '', 'no' );
		if ( ! $lock_acquired ) {
			$acquired_at = (int) get_option( $lock_key, 0 );
			if ( $acquired_at <= 0 || ( time() - $acquired_at ) < $lock_ttl ) {
				// A fresh lock is held — another request (this handler or the REST endpoint) is
				// already activating. Return silently: a reload will see the order resolved, or the
				// lock will self-expire.
				return;
			}

			// Stale lock — the previous owner died mid-activation. Reclaim it with an atomic
			// compare-and-swap: the UPDATE only matches while option_value still equals the
			// timestamp we just read, so of two concurrent reclaimers exactly one query changes a
			// row and wins. (A plain get_option/update_option pair would let both proceed.)
			global $wpdb;
			$reclaimed = $wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
				(string) time(),
				$lock_key,
				(string) $acquired_at
			) );

			if ( 1 !== $reclaimed ) {
				// Lost the reclaim race (another reload reclaimed first). Return silently.
				return;
			}

			// The raw UPDATE bypassed the object cache — drop the stale cached value.
			wp_cache_delete( $lock_key, 'options' );
		}

		$result = \TVA\PayPal\Request::activate_subscription( $subscription_id, $mode );
		if ( is_wp_error( $result ) || ! is_array( $result ) ) {
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'vault_activate_failed', [
				'subscription_id' => $subscription_id,
				'order_id'        => $order_id,
				'error'           => is_wp_error( $result ) ? $result->get_error_message() : 'Unexpected activate response shape.',
			], true );
			// Leave the order PENDING and the lock in place (it self-expires after the TTL, so
			// an immediate retry can't double-activate if the call actually succeeded server-
			// side despite the error response). Redirect to a clean URL with an error flag so
			// the buyer is notified (frontend turns it into a toast) instead of being stranded
			// on the token-laden URL with no feedback; they can re-initiate from the Buy button.
			self::redirect_after_vault_return( [ 'tva_paypal_sub_error' => 1 ] );
		}

		// Stamp the activation call's PayPal-Debug-Id onto the order's vault option BEFORE the
		// fulfill below: the capture row is written by record_capture_transaction (hooked on the
		// completion action fulfill fires), which reads debug_id from this option, not from the
		// Vault_Client. This redirect-return handler is the activation path actually used by the
		// subscription flow (the REST activate_subscription endpoint stamps the same value for the
		// JS path). The first vault charge carries no PayPal-Debug-Id of its own, so the activation
		// call's id is the order's most-recent transaction-affecting debug id.
		$debug_id = \TVA\PayPal\Http\Vault_Client::get_instance( $mode )->get_last_debug_id();
		if ( '' !== $debug_id ) {
			$vault['debug_id'] = $debug_id;
			update_option( 'tva_paypal_vault_' . $order_id, $vault, false );
		}

		// Unwrap a possible { "data": ... } envelope, matching capture_order().
		$capture_body = ( isset( $result['data'] ) && is_array( $result['data'] ) ) ? $result['data'] : $result;

		// Trial activation: no capture. Grant access via the trial path and finish.
		// redirect_after_vault_return() is declared `never` (it exits), so the capture
		// normalization + fulfill path below is unreachable for a trialing response.
		if ( isset( $capture_body['status'] ) && 'trialing' === $capture_body['status'] ) {
			\TVA\PayPal\Events\Payment_Capture_Completed::fulfill_trial_activation( $order_id, $capture_body, $mode );
			delete_option( $lock_key );
			self::redirect_after_vault_return();
		}

		// TODO: Remove this root-captures[0] -> nested normalization once the Product API
		// activate-response shape is confirmed (don't let it ossify as cargo-cult defensiveness).
		// fulfill_from_capture_response() reads the Orders-v2 capture shape
		// (purchase_units[0].payments.captures[0]). The /subscriptions/{id}/activate response
		// may instead expose the capture at the root as captures[0] (see docs/product-api-doc.md).
		// Normalise that form into the nested shape so the shared grant path finds the capture.
		if ( empty( $capture_body['purchase_units'][0]['payments']['captures'][0] ) && ! empty( $capture_body['captures'][0] ) ) {
			$capture_body['purchase_units'][0]['payments']['captures'] = $capture_body['captures'];
		}

		// Grant access synchronously via the exact same idempotent path the one-time embedded
		// capture_order endpoint uses. Completes the pending order, grants product access, and
		// fires the purchase / product-received-access triggers.
		\TVA\PayPal\Events\Payment_Capture_Completed::fulfill_from_capture_response( $order_id, $capture_body, $mode );

		// Activation + fulfilment succeeded — release the lock (matches the REST path, which
		// delete_option()s only after a successful activate).
		delete_option( $lock_key );

		// Redirect to a clean URL: strips the order id + subscription token from the address
		// bar and browser history (avoids leaking them via referrers/logs) and prevents a
		// reload from re-triggering this handler. The buyer lands on the now-accessible page.
		self::redirect_after_vault_return();
	}

	/**
	 * Redirect the buyer to the current page with the PayPal vault-return params stripped
	 * (token + PayerID for the normal-sub flow, approval-session params for the trial flow,
	 * and our own order-id markers). Optional $args are appended (e.g. an error flag the
	 * frontend turns into a toast). Runs at `init`, before output. Always exits.
	 *
	 * @param array $args Extra query args to add to the cleaned URL.
	 * @return never
	 */
	private static function redirect_after_vault_return( array $args = [] ): never {
		$url = remove_query_arg( [ 'thrive_paypal_vault_return', 'thrive_paypal_vault_cancelled', 'token', 'PayerID', 'approval_token_id', 'approval_session_id', 'ba_token' ] );
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Register WordPress filters.
	 *
	 * Hooks registered:
	 * - tva_admin_localize → admin_localize()
	 * - tva_buy_now_integrations → register_buy_now_integration()
	 */
	public static function add_filters() {
		add_filter( 'tva_admin_localize', [ __CLASS__, 'admin_localize' ] );
		add_filter( 'tva_buy_now_integrations', [ __CLASS__, 'register_buy_now_integration' ] );
	}

	/**
	 * Add PayPal to the Buy Now integrations list.
	 *
	 * @param array $integrations
	 *
	 * @return array
	 */
	public static function register_buy_now_integration( $integrations ) {
		// Insert PayPal directly after Stripe in the provider dropdown (rather than
		// appending it last).
		$reordered = [];
		foreach ( $integrations as $key => $label ) {
			$reordered[ $key ] = $label;
			if ( 'stripe' === $key ) {
				$reordered['paypal'] = __( 'PayPal', 'thrive-apprentice' );
			}
		}

		// Fallback when 'stripe' isn't present for some reason.
		if ( ! isset( $reordered['paypal'] ) ) {
			$reordered['paypal'] = __( 'PayPal', 'thrive-apprentice' );
		}

		return $reordered;
	}

	/**
	 * Inject PayPal connection status into the admin JS payload.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function admin_localize( $data ) {
		$pending_mode = \TVA_PayPal_Controller::pending_onboarding_mode();

		$data['paypal'] = [
			'live_enabled'            => Credentials::is_mode_enabled( 'live' ),
			'test_enabled'            => Credentials::is_mode_enabled( 'test' ),
			'merchant_id'             => Credentials::get_account_id_live(),
			'merchant_id_test'        => Credentials::get_account_id(),
			'primary_email_confirmed' => Credentials::is_email_confirmed(),
			'payments_receivable'     => Credentials::is_payments_receivable(),
			'capabilities_summary'    => Credentials::get_capabilities_summary(),
			// Per-mode advanced-payments vetting status; lets statusChanged() reload the page
			// (re-rendering the vetting notice) when it changes on PayPal's side.
			'vetting_status'          => [
				'live' => Credentials::get_vetting_status( 'live' ),
				'test' => Credentials::get_vetting_status( 'test' ),
			],
			'checkout_methods'        => Settings::get_checkout_methods(),
			'webhook_url'             => rest_url( \TVA_Const::REST_NAMESPACE . '/paypal/webhook' ),
			'pending'                 => '' !== $pending_mode ? [ 'mode' => $pending_mode ] : false,

			'i18n'                    => [
				'connect_error'    => __( 'Could not start the PayPal connection. Please try again.', 'thrive-apprentice' ),
				'disconnect_error' => __( 'Could not disconnect PayPal. Please try again.', 'thrive-apprentice' ),
				'setting_error'    => __( 'Could not save the setting. Please try again.', 'thrive-apprentice' ),
				'method_error'     => __( 'Could not update the payment method. Please try again.', 'thrive-apprentice' ),
			],

			'settings'                => [
				Settings::SHOW_PAY_LATER_MESSAGING => Settings::is_enabled( Settings::SHOW_PAY_LATER_MESSAGING ),
				Settings::AUTO_DISPLAY_BUY_BUTTON  => Settings::is_enabled( Settings::AUTO_DISPLAY_BUY_BUTTON ),
				Settings::FRAUDNET_ENABLED         => Settings::is_enabled( Settings::FRAUDNET_ENABLED ),
				Settings::SCA_ALWAYS               => Settings::is_enabled( Settings::SCA_ALWAYS ),
			],
		];

		return $data;
	}

	/**
	 * Clear the bearer token transients for all HTTP clients and modes.
	 *
	 * Useful for manually forcing a token refresh after credential rotation.
	 * Bearer tokens are otherwise refreshed lazily when their transient expires.
	 */
	public static function refresh_token() {
		$clients = [ 'onboarding', 'order', 'vault' ];
		$modes   = [ 'live', 'test' ];

		foreach ( $clients as $client ) {
			foreach ( $modes as $mode ) {
				delete_transient( \TVA\PayPal\Http\Base_Client::make_transient_key( $client, $mode ) );
			}
		}
	}
}

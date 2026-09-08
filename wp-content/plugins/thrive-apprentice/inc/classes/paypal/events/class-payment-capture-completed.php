<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Events;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Payment_Capture_Completed
 *
 * Fires when a PayPal payment is successfully captured.
 * Creates a TVA_Order and grants product access to the buyer.
 */
class Payment_Capture_Completed extends Generic {

	/**
	 * Process the event: find/create user, create order, grant access.
	 */
	public function do_action(): void {
		$resource = $this->get_resource();

		$capture_id = sanitize_text_field( $resource['id'] ?? '' );
		$product_id = (int) ( $resource['purchase_units'][0]['reference_id'] ?? 0 );
		$email      = sanitize_email( $resource['payer']['email_address'] ?? '' );
		$first_name = sanitize_text_field( $resource['payer']['name']['given_name'] ?? '' );
		$last_name  = sanitize_text_field( $resource['payer']['name']['surname'] ?? '' );
		$amount     = sanitize_text_field( $resource['amount']['value'] ?? '0' );
		$currency   = sanitize_text_field( $resource['amount']['currency_code'] ?? 'USD' );

		// Guard: empty capture_id applies to all paths including vault.
		if ( empty( $capture_id ) ) {
			return;
		}

		// Idempotency: skip if already recorded under this capture.
		if ( $this->find_order_by_capture_id( $capture_id ) !== null ) {
			return;
		}

		$thrive_order_id = (int) ( $this->event['x_thrive_order_id'] ?? 0 );

		// Fallback correlation: PayPal copies the order's purchase_unit custom_id onto
		// the capture resource. create_order now stamps custom_id as JSON ({"o":<order
		// id>,"u":<buyer ref>}); older orders used a plain integer. Parse both. Covers the
		// case where the forwarded webhook lacks the top-level x_thrive_order_id field (D3).
		if ( $thrive_order_id <= 0 ) {
			$thrive_order_id = static::order_id_from_custom_id( $resource['custom_id'] ?? '' );
		}

		if ( $thrive_order_id > 0 ) {
			$existing = new \TVA_Order( $thrive_order_id );

			// Guard: order must exist in DB.
			if ( ! $existing->get_id() ) {
				\TVA_Logger::set_type( 'PayPal Webhook' );
				\TVA_Logger::log( 'capture_order_not_found', [ 'thrive_order_id' => $thrive_order_id, 'capture_id' => $capture_id ], true );
				return;
			}

			if ( $existing->get_status() === \TVA_Const::STATUS_PENDING ) {
				// Complete the pending order on any successful capture. Vault info is stored
				// when present but is not required to grant access.
				$vault_info = $this->extract_vault_info( $resource );
				if ( $vault_info === null ) {
					\TVA_Logger::set_type( 'PayPal Webhook' );
					\TVA_Logger::log( 'vault_info_missing', [ 'capture_id' => $capture_id, 'thrive_order_id' => $thrive_order_id ], true );
				}
				$this->complete_pending_order( $existing, $capture_id, $email, $first_name, $last_name, $amount, $currency, $vault_info, $product_id );
			} else {
				// Renewal — create a new order for this cycle.
				$this->handle_renewal( $thrive_order_id, $capture_id, $amount, $currency );
			}
			return;
		}

		// Non-vault one-time initial capture.
		if ( empty( $email ) || $product_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'capture_completed_missing_fields', [ 'capture_id' => $capture_id, 'product_id' => $product_id ], true );
			return;
		}

		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			$user = tva_ensure_new_user( $email, 'paypal', $first_name, $last_name );
		}
		if ( ! $user || is_wp_error( $user ) ) {
			return;
		}

		$order = new \TVA_Order();
		$order->set_status( \TVA_Const::STATUS_COMPLETED );
		$this->populate_order( $order, $user->ID, $capture_id, $email, trim( $first_name . ' ' . $last_name ), $amount, $currency, $product_id );

		// Capture is_first BEFORE save() so query doesn't find the order just inserted.
		$is_first = empty( \TVA_Order::get_orders_by_product( $product_id, $user->ID, \TVA_Const::STATUS_COMPLETED ) );

		$order->save();

		$this->grant_and_trigger( $order, $user->ID, $product_id, $is_first, false );
		do_action( 'tva_paypal_payment_capture_completed', $order, false );
	}

	/**
	 * Synchronous fulfilment for the embedded checkout's capture REST endpoint.
	 *
	 * Builds a webhook-shaped event from the Orders v2 capture response and runs the
	 * same do_action() path, so the grant is byte-for-byte identical to — and
	 * idempotent with — the PAYMENT.CAPTURE.COMPLETED webhook. This removes the
	 * buyer's dependence on webhook delivery for getting access right after paying:
	 * whichever path runs first completes the pending order, the other no-ops (the
	 * capture-id idempotency check and the PENDING-status guard in do_action()).
	 *
	 * @param int    $thrive_order_id Pending TVA order id created by create_order.
	 * @param array  $capture_body    Orders v2 capture response (purchase_units[].payments.captures[]).
	 * @param string $mode            'live' or 'test'.
	 */
	public static function fulfill_from_capture_response( int $thrive_order_id, array $capture_body, string $mode ): void {
		if ( $thrive_order_id <= 0 ) {
			return;
		}

		$capture = $capture_body['purchase_units'][0]['payments']['captures'][0] ?? [];
		if ( empty( $capture['id'] ) ) {
			return;
		}

		// The webhook resource IS the capture object. payer and payment_source sit at
		// the order root on the capture response, so lift them onto the resource for
		// the payer/extract_vault_info() reads in do_action().
		$resource = $capture;
		if ( ! empty( $capture_body['payer'] ) ) {
			$resource['payer'] = $capture_body['payer'];
		}
		if ( ! empty( $capture_body['payment_source'] ) ) {
			$resource['payment_source'] = $capture_body['payment_source'];
		}
		// create_order packs the order id into custom_id (as JSON); keep a correlation
		// fallback when x_thrive_order_id is the primary key. A plain id is fine here —
		// order_id_from_custom_id() reads both the JSON and plain-int forms.
		if ( empty( $resource['custom_id'] ) ) {
			$resource['custom_id'] = (string) $thrive_order_id;
		}

		( new self( [ 'resource' => $resource, 'x_thrive_order_id' => $thrive_order_id ], $mode ) )->do_action();
	}

	/**
	 * Grant access for a TRIAL activation (no capture).
	 *
	 * The Product API's /subscriptions/{id}/activate returns { status: "trialing",
	 * vaulted: true, next_charge_at } with no capture object. Access is granted now; the
	 * first real charge fires at next_charge_at as a normal first-renewal webhook. The order
	 * is marked COMPLETED (so the existing access query + UI treat it as active) but carries
	 * NO payment_id until that first charge, and a trialing marker on its vault option.
	 *
	 * grant_and_trigger() is promoted private → protected so this static entry point can
	 * invoke it on a minimal self() instance (no capture data is needed for the trial path).
	 *
	 * @param int    $thrive_order_id Pending vault order id.
	 * @param array  $activate        Decoded activate response.
	 * @param string $mode            'live' | 'test'.
	 */
	public static function fulfill_trial_activation( int $thrive_order_id, array $activate, string $mode ): void {
		if ( $thrive_order_id <= 0 ) {
			return;
		}

		$order = new \TVA_Order( $thrive_order_id );
		if ( ! $order->get_id() || \TVA_Const::STATUS_PENDING !== $order->get_status() ) {
			return; // Idempotent: already activated, or unknown order.
		}

		$items      = $order->get_order_items();
		$product_id = ! empty( $items ) ? (int) $items[0]->get_product_id() : 0;
		$user_id    = (int) $order->get_user_id();

		if ( $product_id <= 0 || $user_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'trial_activation_missing_user_or_product', [ 'order_id' => $thrive_order_id ], true );
			return;
		}

		// Complete the order with NO payment_id (nothing captured during the trial).
		$order->set_status( \TVA_Const::STATUS_COMPLETED );

		// Capture is_first BEFORE save() so the STATUS_COMPLETED query in
		// grant_and_trigger() does not find the order just inserted.
		$is_first = empty( \TVA_Order::get_orders_by_product( $product_id, $user_id, \TVA_Const::STATUS_COMPLETED ) );

		$order->save();

		// Stamp the trial marker + first-charge time onto the order's vault option.
		$vault                   = get_option( 'tva_paypal_vault_' . $thrive_order_id, [] );
		$vault['trialing']       = true;
		$vault['next_charge_at'] = isset( $activate['next_charge_at'] ) ? (int) $activate['next_charge_at'] : 0;
		update_option( 'tva_paypal_vault_' . $thrive_order_id, $vault, false );

		// Grant access + fire purchase / product-received-access triggers (no capture).
		// Instantiate a minimal self() so we can call the protected grant_and_trigger().
		// The event array is empty — no resource data is needed for the grant path.
		( new self( [], $mode ) )->grant_and_trigger( $order, $user_id, $product_id, $is_first, true );

		do_action( 'tva_paypal_trial_activation_completed', $order );
	}

	/**
	 * Extract the TVA order id from a capture's custom_id.
	 *
	 * create_order packs custom_id as JSON ({"o":<order id>,"u":<buyer ref>}); orders
	 * created before that used a plain integer. Handles both forms.
	 *
	 * @param string $custom_id Raw custom_id from the capture resource.
	 *
	 * @return int TVA order id, or 0 when absent/unparseable.
	 */
	protected static function order_id_from_custom_id( $custom_id ) {
		if ( is_numeric( $custom_id ) ) {
			return (int) $custom_id;
		}

		$decoded = json_decode( (string) $custom_id, true );
		if ( is_array( $decoded ) && isset( $decoded['o'] ) ) {
			return (int) $decoded['o'];
		}

		return 0;
	}

	/**
	 * Complete a pending vault order created by the buy-now flow.
	 *
	 * The pending order already has gateway, type, price, currency, and the
	 * product item correctly set from buy-now. We only update the fields that
	 * arrive at capture time: payment_id, buyer info, status.
	 *
	 * Do NOT call populate_order() here — it would create a duplicate item row
	 * because new TVA_Order($id) loads existing items with their DB IDs, and
	 * set_order_item() would add a second item with no ID that save() would INSERT.
	 *
	 * Captures $is_first BEFORE save() so the STATUS_COMPLETED query in
	 * grant_and_trigger() does not find the order just inserted.
	 */
	private function complete_pending_order(
		\TVA_Order $order,
		string $capture_id,
		string $email,
		string $first_name,
		string $last_name,
		string $amount,
		string $currency,
		?array $vault_info,
		int $webhook_product_id = 0
	): void {
		$items      = $order->get_order_items();
		$product_id = ! empty( $items ) ? (int) $items[0]->get_product_id() : 0;

		if ( $product_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'complete_pending_order_missing_product_id', [ 'order_id' => $order->get_id(), 'capture_id' => $capture_id ], true );
			return;
		}

		// Defense-in-depth: the captured amount/currency/product must match what the
		// buyer agreed to at buy-now (stored authoritatively on the pending order). The
		// plugin sets these when it creates the order, so the capture must round-trip them
		// unchanged. A mismatch means a Product API desync, an order-id mix-up, or a forged
		// event — never a legitimate price change. Do not complete or grant access.
		// The product check is only enforced when the webhook actually carries a reference_id;
		// when absent, the order's own (trusted) product is used as before.
		if ( ( $webhook_product_id > 0 && $webhook_product_id !== $product_id )
			|| ! $this->capture_matches_order( $order, $amount, $currency ) ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'capture_amount_mismatch', [
				'order_id'          => $order->get_id(),
				'capture_id'        => $capture_id,
				'expected_product'  => $product_id,
				'webhook_product'   => $webhook_product_id,
				'expected_amount'   => $order->get_price(),
				'webhook_amount'    => $amount,
				'expected_currency' => $order->get_currency(),
				'webhook_currency'  => $currency,
			], true );
			// 4th arg is the authoritative (order-derived) product id, consistent with the
			// renewal path's do_action below. The webhook-reported product is in the log above.
			do_action( 'tva_paypal_capture_mismatch', $order, $amount, $currency, $product_id );
			return;
		}

		// Effective buyer email: prefer the one stored on the order at checkout time
		// (reliable for every payment method, incl. card), fall back to the webhook
		// payer email.
		$buyer_email = $order->get_buyer_email();
		if ( empty( $buyer_email ) ) {
			$buyer_email = $email;
		}

		// Resolve the buyer. A logged-in purchase already has user_id set — use it as the
		// source of truth and never reassign it by email (the buyer's PayPal email may differ
		// from their WordPress email). A guest purchase (user_id 0) creates or matches the WP
		// user from the buyer email — matching the Stripe/Square guest-checkout behaviour.
		$user_id = (int) $order->get_user_id();
		$user    = $user_id > 0 ? get_userdata( $user_id ) : null;

		if ( ! $user && ! empty( $buyer_email ) ) {
			$user = get_user_by( 'email', $buyer_email );
			if ( ! $user ) {
				$user = tva_ensure_new_user( $buyer_email, 'paypal', $first_name, $last_name );
			}
			if ( $user && ! is_wp_error( $user ) ) {
				$order->set_user_id( (int) $user->ID );
			}
		}

		if ( ! $user || is_wp_error( $user ) ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'complete_pending_order_user_not_found', [ 'order_id' => $order->get_id(), 'capture_id' => $capture_id, 'email' => $buyer_email ], true );
			return;
		}

		// Update only capture-time fields. Do NOT overwrite price/currency — the buy-now
		// values are authoritative and the refund full/partial check later depends on them
		// (they have just been validated to match the capture above).
		$order->set_payment_id( $capture_id );
		$order->set_buyer_email( $buyer_email );
		$order->set_buyer_name( trim( $first_name . ' ' . $last_name ) );
		$order->set_status( \TVA_Const::STATUS_COMPLETED );

		// Capture is_first BEFORE save() so the query doesn't find the order just inserted.
		$is_first = empty( \TVA_Order::get_orders_by_product( $product_id, $user->ID, \TVA_Const::STATUS_COMPLETED ) );

		// Persist vault_type alongside the already-stored subscription_id when available.
		if ( $vault_info !== null ) {
			$vault_data               = get_option( 'tva_paypal_vault_' . $order->get_id(), [] );
			$vault_data['vault_type'] = $vault_info['vault_type'];
			$vault_data['vault_id']   = $vault_info['vault_id'];
			update_option( 'tva_paypal_vault_' . $order->get_id(), $vault_data, false );
		}

		$order->save();

		$this->grant_and_trigger( $order, $user->ID, $product_id, $is_first, true );
		do_action( 'tva_paypal_payment_capture_completed', $order, true );
	}

	/**
	 * Verify a capture's amount and currency match the authoritative values stored on
	 * the order at buy-now time.
	 *
	 * The plugin sets the amount/currency when it creates the order via the Product API,
	 * so a legitimate capture always reports the same values back. Amount uses a 0.01
	 * tolerance for float/string representation; currency is compared case-insensitively.
	 *
	 * @param \TVA_Order $order
	 * @param string     $amount   Captured amount from the webhook.
	 * @param string     $currency Captured currency code from the webhook.
	 * @return bool True when amount and currency both match.
	 */
	private function capture_matches_order( \TVA_Order $order, string $amount, string $currency ): bool {
		$amount_ok   = abs( (float) $amount - (float) $order->get_price() ) <= 0.01;
		$currency_ok = strtoupper( $currency ) === strtoupper( (string) $order->get_currency() );

		return $amount_ok && $currency_ok;
	}

	/**
	 * Handle a renewal capture from the Product API cron.
	 *
	 * x_thrive_order_id is the TVA_Order DB ID — direct lookup, no table scan.
	 */
	private function handle_renewal(
		int $thrive_order_id,
		string $capture_id,
		string $amount,
		string $currency
	): void {
		$original   = new \TVA_Order( $thrive_order_id );
		$user_id    = (int) $original->get_user_id();
		$user       = get_userdata( $user_id );
		$items      = $original->get_order_items();
		$first_item = ! empty( $items ) ? $items[0] : null;
		$product_id = $first_item ? (int) $first_item->get_product_id() : 0;

		if ( ! $user || $product_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'renewal_missing_user_or_product', [ 'thrive_order_id' => $thrive_order_id ], true );
			return;
		}

		// Only renew orders that are in an active billing state.
		// STATUS_REFUND and STATUS_FAILED are terminal — a renewal on either would re-bill
		// and re-grant access to a customer whose subscription has already been ended.
		$allowed_statuses = [ \TVA_Const::STATUS_COMPLETED, \TVA_Const::STATUS_GRACE_PERIOD ];
		if ( ! in_array( $original->get_status(), $allowed_statuses, true ) ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'renewal_skipped_terminal_order', [
				'thrive_order_id' => $thrive_order_id,
				'status'          => $original->get_status(),
			], true );
			return;
		}

		// A renewal must capture the same amount/currency as the original cycle. A mismatch
		// indicates a Product API error or a forged event — do not bill/grant on it.
		if ( ! $this->capture_matches_order( $original, $amount, $currency ) ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'renewal_amount_mismatch', [
				'thrive_order_id'   => $thrive_order_id,
				'capture_id'        => $capture_id,
				'expected_amount'   => $original->get_price(),
				'webhook_amount'    => $amount,
				'expected_currency' => $original->get_currency(),
				'webhook_currency'  => $currency,
			], true );
			do_action( 'tva_paypal_capture_mismatch', $original, $amount, $currency, $product_id );
			return;
		}

		// Every paid charge — including the FIRST charge after a free trial — is recorded as its
		// own order below, never merged into the trial anchor. The trial anchor stays as an
		// uncaptured placeholder (its own "Trial start" charge row), and each real charge is a
		// separate order carrying original_order_id back to it. The Edit Member screen groups all
		// of them under one subscription row (keyed by subscription_id), so distinct orders no
		// longer mean duplicate top-level entries — the trial and its first payment show as two
		// nested charge rows, which is what the merchant wants to see. A per-cycle order is also
		// required for per-cycle refunds (Payment_Capture_Refunded resolves the anchor via
		// original_order_id).

		// Clear grace period on the previous cycle if applicable.
		if ( $original->get_status() === \TVA_Const::STATUS_GRACE_PERIOD ) {
			$original->set_status( \TVA_Const::STATUS_COMPLETED );
			$original->save();
		}

		$renewal = new \TVA_Order();
		$renewal->set_status( \TVA_Const::STATUS_COMPLETED );
		$this->populate_order( $renewal, $user_id, $capture_id, $user->user_email, $user->display_name, $amount, $currency, $product_id );
		$renewal->save();

		// Bail if the renewal did not persist: a failed insert leaves get_id() empty, which
		// would write the vault record to the keyless option 'tva_paypal_vault_' (clobbered
		// across renewals and unfindable at refund time) and grant access against an order with
		// no DB trail. Log and stop so the Product API can retry instead.
		$renewal_id = (int) $renewal->get_id();
		if ( $renewal_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'renewal_save_failed', [ 'thrive_order_id' => $thrive_order_id, 'capture_id' => $capture_id ], true );
			return;
		}

		// Copy the subscription linkage onto the renewal order. Renewal orders are NOT the
		// subscription anchor — the vault record (subscription_id) lives on the original
		// purchase order — so a refund of this renewal would otherwise have no way to cancel
		// the subscription or terminate the anchor. Store the subscription_id/mode plus an
		// 'original_order_id' back-reference for Payment_Capture_Refunded to resolve.
		$original_vault = get_option( 'tva_paypal_vault_' . $thrive_order_id, [] );
		if ( ! empty( $original_vault['subscription_id'] ) ) {
			update_option( 'tva_paypal_vault_' . $renewal_id, [
				'subscription_id'   => $original_vault['subscription_id'],
				'mode'              => $original_vault['mode'] ?? $this->mode,
				'original_order_id' => $thrive_order_id,
			], false );
		}

		// Defensive: the first post-trial charge is normally stamped onto the anchor in place
		// (see the trial-placeholder branch above, which clears trialing and returns). Reaching
		// here with the flag still set would mean an inconsistent anchor (trialing set yet a
		// payment_id already present); clear the stale flag so it can't mislabel future cycles.
		if ( ! empty( $original_vault['trialing'] ) ) {
			unset( $original_vault['trialing'] );
			update_option( 'tva_paypal_vault_' . $thrive_order_id, $original_vault, false );
		}

		$result = \Thrive_Apprentice_API::grant_access( $user_id, $product_id, 'paypal_renewal' );
		if ( is_wp_error( $result ) && $result->get_error_code() !== 'access_exists' ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'renewal_grant_access_failed', [ 'error' => $result->get_error_message(), 'user_id' => $user_id ], true );
		}

		do_action( 'tva_paypal_payment_capture_completed', $renewal, true );
	}

	/**
	 * Populate a TVA_Order with capture data and a product item.
	 */
	private function populate_order(
		\TVA_Order $order,
		int $user_id,
		string $capture_id,
		string $email,
		string $buyer_name,
		string $amount,
		string $currency,
		int $product_id
	): void {
		$order->set_user_id( $user_id );
		$order->set_gateway( \TVA_Const::PAYPAL_GATEWAY );
		$order->set_payment_method( \TVA_Const::PAYPAL_GATEWAY );
		$order->set_type( \TVA_Order::PAID );
		$order->set_payment_id( $capture_id );
		$order->set_buyer_email( $email );
		$order->set_buyer_name( $buyer_name );
		$order->set_price( (float) $amount );
		$order->set_currency( $currency );

		$item = new \TVA_Order_Item();
		$item->set_product_id( $product_id );
		// $product_id is a TVA\Product term id, NOT a WP post id — get_the_title() would look
		// up an unrelated post (or a revision) and mislabel the line item. Resolve the term
		// name the same way the checkout path does (see Buy_Now\Paypal::get_subscription_url()).
		$product_title = ( new \TVA\Product( $product_id ) )->get_name();
		$item->set_product_name( $product_title ?: 'PayPal purchase' );
		$item->set_quantity( 1 );
		$item->set_unit_price( (float) $amount );
		$item->set_product_price( (float) $amount );
		$item->set_total_price( (float) $amount );
		$item->set_currency( $currency );
		$order->set_order_item( $item );
	}

	/**
	 * Grant access and fire purchase triggers.
	 *
	 * Promoted from private to protected so fulfill_trial_activation() can invoke it
	 * on a minimal self() instance (no capture data needed for the trial path).
	 *
	 * @param \TVA_Order $order
	 * @param int        $user_id
	 * @param int        $product_id
	 * @param bool       $is_first    Must be computed BEFORE order->save().
	 * @param bool       $is_vault
	 */
	protected function grant_and_trigger( \TVA_Order $order, int $user_id, int $product_id, bool $is_first, bool $is_vault ): void {
		$customer = new \TVA_Customer( $user_id );

		if ( $is_first ) {
			$customer->trigger_purchase( $order );
		}

		$customer->trigger_course_purchase( $order, 'PayPal' );

		$result = \Thrive_Apprentice_API::grant_access( $user_id, $product_id, 'paypal' );
		if ( is_wp_error( $result ) && $result->get_error_code() !== 'access_exists' ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'grant_access_failed', [ 'error' => $result->get_error_message(), 'user_id' => $user_id ], true );
		}

		// Fire the product-received-access event so the course welcome email and the
		// Automator `product_received_access` trigger run for PayPal buyers — Square
		// does this explicitly in its redirect endpoint. The grant_access() ->
		// enrol_user_to_product() path skips it here because the pending order already
		// exists by capture time (has_bought() is true), so it would never fire
		// otherwise. The per-course welcome dedup keeps this safe even when the buyer
		// already had access (e.g. via a WordPress role).
		$customer->trigger_product_received_access( [ $product_id ] );
	}

	/**
	 * Extract vault_id and vault_type from the payment_source block.
	 * Covers card, paypal, apple_pay, and venmo.
	 * Returns null when the capture was not a vault payment.
	 *
	 * @param array $resource
	 * @return array|null { vault_id: string, vault_type: string }
	 */
	private function extract_vault_info( array $resource ): ?array {
		$sources = [
			'card'      => [ 'payment_source', 'card', 'vault_id' ],
			'paypal'    => [ 'payment_source', 'paypal', 'vault_id' ],
			'apple_pay' => [ 'payment_source', 'apple_pay', 'card', 'vault_id' ],
			'venmo'     => [ 'payment_source', 'venmo', 'vault_id' ],
		];

		foreach ( $sources as $type => $path ) {
			$val = $resource;
			foreach ( $path as $key ) {
				$val = $val[ $key ] ?? null;
				if ( $val === null ) {
					break;
				}
			}
			if ( ! empty( $val ) ) {
				return [
					'vault_id'   => sanitize_text_field( (string) $val ),
					'vault_type' => $type,
				];
			}
		}

		return null;
	}
}

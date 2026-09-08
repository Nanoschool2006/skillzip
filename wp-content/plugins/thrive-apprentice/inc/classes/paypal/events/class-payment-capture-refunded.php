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
 * Class Payment_Capture_Refunded
 *
 * Fires when a PayPal payment is refunded.
 * Updates the TVA_Order to STATUS_REFUND, revokes access, and cancels the vault
 * subscription so no further renewals are attempted.
 *
 * Partial-refund guard: any single refund >= order_total (within 0.01 tolerance)
 * is treated as a full refund. Multiple partial refunds that cumulatively equal
 * the order total are NOT detected — each event is evaluated in isolation.
 */
class Payment_Capture_Refunded extends Generic {

	public function do_action(): void {
		$resource = $this->get_resource();

		// Capture ID is in resource.links[rel="up"] — parse the last path segment of the href.
		$capture_id = '';
		foreach ( $resource['links'] ?? [] as $link ) {
			if ( ( $link['rel'] ?? '' ) === 'up' && ! empty( $link['href'] ) ) {
				$path       = wp_parse_url( $link['href'], PHP_URL_PATH );
				$capture_id = sanitize_text_field( basename( rtrim( (string) $path, '/' ) ) );
				break;
			}
		}

		if ( empty( $capture_id ) ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'refund_no_capture_id', [ 'event_id' => $this->get_event_id() ], true );
			return;
		}

		$order = $this->find_order_by_capture_id( $capture_id );

		if ( $order === null ) {
			return;
		}

		// Guard: PAYMENT.CAPTURE.REFUNDED fires for partial refunds too.
		// Only revoke access and mark STATUS_REFUND on a full refund.
		$refund_amount = (float) ( $resource['amount']['value'] ?? 0 );
		$order_total   = (float) $order->get_price();

		if ( $order_total > 0 && $refund_amount < $order_total - 0.01 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'partial_refund_skipped', [
				'event_id'      => $this->get_event_id(),
				'refund_amount' => $refund_amount,
				'order_total'   => $order_total,
			], true );
			do_action( 'tva_paypal_partial_refund', $order, $refund_amount );
			return;
		}

		self::apply_full_refund( $order, $this->mode );
	}

	/**
	 * Apply the side-effects of a full refund to a TVA_Order.
	 *
	 * Shared by the PAYMENT.CAPTURE.REFUNDED webhook (above) and the merchant-initiated
	 * refund REST endpoint (TVA_PayPal_Controller::refund). Marks the order — and its
	 * subscription anchor — STATUS_REFUND, revokes access, cancels the vault subscription,
	 * and fires the reporting hooks.
	 *
	 * Idempotent: a merchant refund processes this synchronously, then PayPal sends the
	 * webhook which arrives at the same order. The status guard makes the second caller a
	 * no-op so access revocation and the cancellation event do not fire twice.
	 *
	 * @param \TVA_Order $order         The refunded order (may be a renewal — the anchor is resolved here).
	 * @param string     $mode_fallback 'live' or 'test', used when the vault record has no stored mode.
	 *
	 * @return void
	 */
	public static function apply_full_refund( \TVA_Order $order, string $mode_fallback = 'live' ): void {
		// Already refunded — the side-effects ran on the first trigger (endpoint or webhook).
		if ( (int) $order->get_status() === \TVA_Const::STATUS_REFUND ) {
			return;
		}

		$user_id = (int) $order->get_user_id();

		// Mark the refunded order itself REFUND.
		$order->set_status( \TVA_Const::STATUS_REFUND );
		$order->save();

		// Resolve the subscription anchor. The vault record (subscription_id) lives on the
		// original purchase order; a renewal order carries a copy plus an 'original_order_id'
		// back-reference (written in Payment_Capture_Completed::handle_renewal). For an
		// initial-purchase refund the refunded order IS the anchor.
		$vault_data   = get_option( 'tva_paypal_vault_' . $order->get_id(), [] );
		$anchor_order = $order;
		$anchor_vault = $vault_data;

		if ( ! empty( $vault_data['original_order_id'] ) ) {
			$candidate = new \TVA_Order( (int) $vault_data['original_order_id'] );
			if ( $candidate->get_id() ) {
				$anchor_order = $candidate;
				$anchor_vault = get_option( 'tva_paypal_vault_' . $candidate->get_id(), $vault_data );

				// The refunded renewal's vault copy is dead weight once the anchor is resolved.
				delete_option( 'tva_paypal_vault_' . $order->get_id() );
			}
		}

		// Terminate the anchor order too. Access is granted by the anchor's
		// COMPLETED/GRACE_PERIOD status (see TVA_PayPal_Integration::is_rule_applied), not by
		// the renewal's, and the Product API renewal cycle is keyed on the anchor via
		// x_thrive_order_id — so leaving the anchor COMPLETED would keep access live AND let
		// the next cycle re-bill. Skip when the refunded order already is the anchor.
		if ( $anchor_order->get_id() !== $order->get_id()
			&& $anchor_order->get_status() !== \TVA_Const::STATUS_REFUND ) {
			$anchor_order->set_status( \TVA_Const::STATUS_REFUND );
			$anchor_order->save();
		}

		// Revoke access for each product on the anchor order.
		foreach ( $anchor_order->get_order_items() as $item ) {
			$product_id = (int) $item->get_product_id();
			if ( $product_id <= 0 ) {
				continue;
			}

			\Thrive_Apprentice_API::revoke_access( $user_id, $product_id, 'paypal_refund' );

			// revoke_access() removes the enrolment, but the admin member screen and the
			// published-courses count are driven by the access-history table
			// (tva_access_history, SUM(status) > 0). Without an offsetting STATUS_ACCESS_REVOKED
			// row the refunded course keeps showing there even though live access rules already
			// deny it. Record the revocation the same way the expiry and Square-refund flows do.
			$product = new \TVA\Product( $product_id );
			if ( ! $product->get_id() ) {
				continue;
			}

			\TVA\Access\Main::remove_order_access( $product, $user_id, \TVA_Const::ACCESS_HISTORY_REASON_REFUND );
		}

		// Cancel the vault subscription so no further renewals are attempted.
		$subscription_id = $anchor_vault['subscription_id'] ?? '';
		$mode            = $anchor_vault['mode'] ?? $mode_fallback;

		if ( ! empty( $subscription_id ) ) {
			$cancel_result = \TVA\PayPal\Request::cancel_subscription( $subscription_id, $mode );
			if ( is_wp_error( $cancel_result ) ) {
				// The remote subscription may still be active — do NOT emit the cancellation
				// reporting event. Local access is still revoked above and the order is REFUND.
				\TVA_Logger::set_type( 'PayPal Refund' );
				\TVA_Logger::log( 'cancel_subscription_failed_on_refund', [
					'order_id'        => $anchor_order->get_id(),
					'subscription_id' => $subscription_id,
					'error'           => $cancel_result->get_error_message(),
				], true );
			} else {
				delete_option( 'tva_paypal_vault_' . $anchor_order->get_id() );

				// Reporting: a confirmed cancellation. Fired only after a successful cancel so
				// downstream reporting never records a subscription that is still billing.
				$user         = get_userdata( $user_id );
				$anchor_items = $anchor_order->get_order_items();
				$first_item   = ! empty( $anchor_items ) ? $anchor_items[0] : null;
				if ( $user && $first_item !== null ) {
					do_action( 'tva_subscription_cancelled', $user, $first_item, $anchor_order );
				}
			}
		}

		do_action( 'tva_paypal_payment_capture_refunded', $order );
	}
}

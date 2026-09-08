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
 * Class Payment_Capture_Denied
 *
 * Fires when a PayPal capture is declined.
 * Vault renewals: grace period. Initial purchases: fail immediately.
 */
class Payment_Capture_Denied extends Generic {

	public function do_action(): void {
		$resource   = $this->get_resource();
		$capture_id = sanitize_text_field( $resource['id'] ?? '' );

		if ( empty( $capture_id ) ) {
			return;
		}

		$found_via_thrive_id = false;

		// Primary lookup: works for initial capture denials.
		$order = $this->find_order_by_capture_id( $capture_id );

		// Fallback: renewal denials have x_thrive_order_id at the event root.
		// The renewal capture_id was never stored in payment_id, so find_order_by_capture_id
		// returns null. Use the thrive_order_id for direct lookup.
		if ( $order === null ) {
			$thrive_order_id = (int) ( $this->event['x_thrive_order_id'] ?? 0 );
			if ( $thrive_order_id > 0 ) {
				$candidate = new \TVA_Order( $thrive_order_id );
				if ( $candidate->get_id() ) {
					$order               = $candidate;
					$found_via_thrive_id = true;
				}
			}
		}

		// Renewal denials (found via x_thrive_order_id) do not include payment_source
		// in the webhook payload, so vault detection from headers is unreliable.
		// When we found the order via thrive_order_id it is definitively a renewal —
		// always enter grace period so the Product API retry schedule can run.
		$is_vault = $found_via_thrive_id
			|| ! empty( $resource['payment_source']['card']['vault_id'] )
			|| ! empty( $resource['payment_source']['paypal']['vault_id'] )
			|| ! empty( $resource['payment_source']['venmo']['vault_id'] )
			|| ! empty( $resource['payment_source']['apple_pay']['card']['vault_id'] );

		$new_status = $is_vault
			? \TVA_Const::STATUS_GRACE_PERIOD
			: \TVA_Const::STATUS_FAILED;

		if ( $order !== null ) {
			// Never move an already-terminal order back into GRACE_PERIOD (e.g. stale/duplicate denial after revoke).
			$skip = $new_status === \TVA_Const::STATUS_GRACE_PERIOD
					&& ! in_array( $order->get_status(), [ \TVA_Const::STATUS_COMPLETED, \TVA_Const::STATUS_PENDING ], true );

			if ( ! $skip ) {
				$order->set_status( $new_status );

				// Stamp updated_at for accurate grace-period tracking in Product API logs and future tooling.
				// TVA_Order::save() does not auto-update this column.
				if ( $new_status === \TVA_Const::STATUS_GRACE_PERIOD ) {
					$order->set_updated_at( current_datetime()->format( 'Y-m-d H:i:s' ) );
				}

				$order->save();
			}
		}

		do_action( 'tva_paypal_payment_capture_denied', $capture_id, $new_status, $is_vault );
	}
}

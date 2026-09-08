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
 * Class Checkout_Payment_Approval_Reversed
 *
 * Fires when a buyer reverses their payment approval before capture.
 * Marks any pending order as failed.
 *
 * resource.id holds the PayPal Order ID. Phase 2 does not persist the PayPal Order ID
 * to the DB (gateway_order_id is an int column), so find_order_by_paypal_order_id()
 * will return null for pre-capture reversals — this is expected and handled gracefully.
 */
class Checkout_Payment_Approval_Reversed extends Generic {

	public function do_action(): void {
		$resource = $this->get_resource();
		$order_id = sanitize_text_field( $resource['id'] ?? '' );

		if ( empty( $order_id ) ) {
			return;
		}

		$order = $this->find_order_by_paypal_order_id( $order_id );

		if ( $order !== null ) {
			$order->set_status( \TVA_Const::STATUS_FAILED );
			$order->save();
		}

		do_action( 'tva_paypal_checkout_payment_approval_reversed', $order_id, $order );
	}
}

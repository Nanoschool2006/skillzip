<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe\Events;

use Stripe\Event;
use TVA_Const;
use TVA_Order;
use TVA_Order_Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Refund_Created extends Generic {
	protected $type = Event::REFUND_CREATED;

	protected $order_status = TVA_Const::STATUS_REFUND;

	/**
	 * Handle the refund created event
	 */
	public function build_data() {
		parent::build_data();

		// Get the payment ID from the refund
		$payment_id = $this->data->charge;

		// Retrieve the charge to get the payment_intent
		$checkout_session_id = null;
		try {
			$charge = $this->stripe_connection->charges->retrieve( $payment_id );
			$payment_intent_id = $charge->payment_intent;

			// Retrieve the checkout session using the payment_intent
			$checkout_sessions = $this->stripe_connection->checkout->sessions->all( [
				'payment_intent' => $payment_intent_id,
				'limit' => 1,
			] );

			if ( ! empty( $checkout_sessions->data ) ) {
				$checkout_session_id = $checkout_sessions->data[0]->id;
			}
		} catch ( \Exception $e ) {
			error_log( 'Stripe API error: ' . $e->getMessage() );
		}

		// Find the order associated with this charge
		$orders = TVA_Order::get_orders_by_payment_id( $checkout_session_id, TVA_Const::STATUS_COMPLETED );
		
		// Process refund for each order: update status, save, and trigger refund action for order items.
		if ( ! empty( $orders ) ) {
			foreach ( $orders as $order_data ) {
				$order = new TVA_Order();
				$order->set_data( $order_data );
				$order->set_id( $order_data['ID'] );
				$order->set_status( TVA_Const::STATUS_REFUND );
				$order->save();

				// Trigger the refund action for each order item
				foreach ( $order->get_order_items() as $order_item ) {
                    // Update order item status to refunded.
                    $order_item->set_status( TVA_Const::STATUS_REFUND );
                    $order_item->save();
                    
                    // Update the reports data.
					do_action( 'tva_stripe_order_refunded', $order_item );
				}
			}
		}
	}
} 
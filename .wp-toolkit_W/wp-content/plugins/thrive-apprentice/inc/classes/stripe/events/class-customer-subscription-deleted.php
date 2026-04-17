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
use TVA\Access\History_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Customer_Subscription_Deleted extends Customer_Subscription_Created {

	protected $type = Event::CUSTOMER_SUBSCRIPTION_DELETED;

	protected $order_status = TVA_Const::STATUS_FAILED;

	public function build_data() {
		global $wpdb;
		$customer_id = $this->original_event->data->object->customer;
		$user_id = get_users( [ 'meta_key' => 'tva_stripe_customer_id', 'meta_value' => $customer_id ] )[0]->ID;

		$price_id = $this->original_event->data->object->plan->id;
		$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM {$wpdb->termmeta} WHERE meta_key = 'tva_rules' AND meta_value LIKE '%{$price_id}%'" ) );
		
		// Update Course Access
		History_Table::get_instance()->update( $product_id, $user_id );

		// Set failed order status and find first valid order item
		$orders = TVA_Order::get_orders_by_product( $price_id, $user_id );
		$first_order_item = null;
		$first_order = null;

		foreach ( $orders as $order ) {
			$o = new TVA_Order( $order['ID'] );
			$o->set_status( TVA_Const::STATUS_FAILED );
			$o->save();
			
			// Store the first valid order item we find
			if ( $first_order_item === null ) {
				$order_items = $o->get_order_items();
				if ( ! empty( $order_items ) ) {
					$first_order_item = $order_items[0];
					$first_order = $o;
				}
			}
		}

		// Trigger the action only once with the first valid order item
		if ( $first_order_item !== null ) {
			$user = get_userdata( $user_id );
			do_action( 'tva_subscription_cancelled', $user, $first_order_item, $first_order );
		}
	}
}

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe\Orders;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

use Stripe\Checkout\Session;
use TVA\Stripe\Helper;
use TVA\Stripe\Hooks;
use TVA\Stripe\Request;

class Checkout extends Generic {

	/**
	 * @var Session
	 */
	protected $data;

	/**
	 * Add current order data to the order object
	 *
	 * @return void
	 */
	public function process_data() {
		$this->order->set_payment_id( $this->data->id );
		$this->order->set_currency( $this->data->currency );
		$this->order->set_buyer_email( $this->data->customer_details->email );
		$this->order->set_created_at( date( 'Y-m-d H:i:s', $this->data->created ) );
		$this->order->set_price( $this->data->amount_total / 100 );
		$this->order->set_price_gross( $this->data->amount_subtotal / 100 );

		list( $first_name, $last_name ) = Helper::get_names( $this->data->customer_details->name );
		$user = $this->get_user( $this->data->customer_details->email, $first_name, $last_name );

		if ( $user ) {
			$this->order->set_user_id( $user->ID );
			$customer = $this->data->customer;
			if ( ! $customer ) {
				$customer = Request::get_customer_id( $this->data->customer_details->email );
			}
			update_user_meta( $user->ID, Hooks::CUSTOMER_META_ID, $customer );
		}

		if ( ! empty( $this->data->line_items ) ) {
			$this->process_line_items( $this->data->line_items );
		}
	}

}

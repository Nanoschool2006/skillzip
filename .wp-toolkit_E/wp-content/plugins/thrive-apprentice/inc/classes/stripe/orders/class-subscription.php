<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe\Orders;

use Exception;
use TVA\Stripe\Helper;
use TVA\Stripe\Hooks;
use TVA\Stripe\Request;
use TVA_Const;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Subscription extends Generic {

	protected $invoice = null;

	public function process_data() {
		$this->order->set_id( $this->data->id );
		$this->order->set_currency( $this->data->currency );
		$this->order->set_created_at( date( 'Y-m-d H:i:s', $this->data->created ) );
		$user = false;
		try {
			$this->invoice = Request::get_invoice( $this->data->latest_invoice );
			$this->order->set_buyer_email( $this->invoice->customer_email );
			$this->order->set_price( $this->invoice->total / 100 );
			$this->order->set_price_gross( $this->invoice->total / 100 );

			list( $first_name, $last_name ) = Helper::get_names( $this->invoice->customer_name );
			$user = $this->get_user( $this->invoice->customer_email, $first_name, $last_name );

			if ( $user ) {
				$this->order->set_user_id( $user->ID );
				$customer = $this->invoice->customer;
				if ( ! $customer ) {
					$customer = Request::get_customer_id( $this->invoice->customer_email );
				}
				update_user_meta( $user->ID, Hooks::CUSTOMER_META_ID, $customer );
			}
		} catch ( Exception $e ) {
		}

		if ( ! empty( $this->data->subscription_items ) ) {
			if ( $this->status === TVA_Const::STATUS_COMPLETED ) {
				$this->process_line_items( $this->data->subscription_items );
			} else if ( in_array( $this->status, [ TVA_Const::STATUS_FAILED, Generic::RESUMED_STATUS ] ) ) {
				$this->update_order_status( $this->data->subscription_items, $user ? $user->ID : 0 );
			}
		}
	}

	/**
	 * Only save the order if the status is completed
	 *
	 * @return void
	 */
	public function save() {
		if ( $this->status === TVA_Const::STATUS_COMPLETED ) {
			parent::save();
		}
	}
}

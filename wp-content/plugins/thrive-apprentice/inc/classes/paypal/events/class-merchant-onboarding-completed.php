<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Events;

use TVA\PayPal\Credentials;
use TVA\PayPal\Http\Order_Client;
use TVA\PayPal\Connection;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Merchant_Onboarding_Completed
 *
 * Fires when PayPal notifies us that merchant onboarding is complete.
 * Refreshes the merchant capabilities cache.
 */
class Merchant_Onboarding_Completed extends Generic {

	public function do_action(): void {
		// Re-fetch merchant status to update capabilities cache.
		$response = Order_Client::get_instance( $this->mode )->get( Connection::ENDPOINT_MERCHANT );

		if ( ! is_wp_error( $response ) ) {
			if ( ! empty( $response['capabilities'] ) ) {
				Credentials::save_capabilities( $response['capabilities'], $this->mode );
			}

			// The admin grid keys off capabilities_summary — persist it independently
			// of the legacy capabilities array.
			if ( ! empty( $response['capabilities_summary'] ) && is_array( $response['capabilities_summary'] ) ) {
				Credentials::save_capabilities_summary( $response['capabilities_summary'], $this->mode );
			}
		}

		do_action( 'tva_paypal_merchant_onboarding_completed', $this->mode );
	}
}

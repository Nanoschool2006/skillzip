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
 * Class Payment_Authorization_Voided
 *
 * Fires when a PayPal authorization is voided before capture.
 */
class Payment_Authorization_Voided extends Generic {

	public function do_action(): void {
		$resource = $this->get_resource();
		$auth_id  = sanitize_text_field( $resource['id'] ?? '' );

		\TVA_Logger::set_type( 'PayPal Webhook' );
		\TVA_Logger::log( 'authorization_voided', [ 'auth_id' => $auth_id, 'mode' => $this->mode ], true );

		do_action( 'tva_paypal_payment_authorization_voided', $auth_id, $this->mode );
	}
}

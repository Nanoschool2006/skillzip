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
 * Class Payment_Authorization_Created
 *
 * Fires when a PayPal payment is authorized but not yet captured.
 * This integration uses CAPTURE intent, so authorizations are unexpected.
 * Logs + fires WP action for extension points.
 */
class Payment_Authorization_Created extends Generic {

	public function do_action(): void {
		$resource = $this->get_resource();
		$auth_id  = sanitize_text_field( $resource['id'] ?? '' );

		\TVA_Logger::set_type( 'PayPal Webhook' );
		\TVA_Logger::log( 'authorization_created', [ 'auth_id' => $auth_id, 'mode' => $this->mode ], true );

		do_action( 'tva_paypal_payment_authorization_created', $auth_id, $this->mode );
	}
}

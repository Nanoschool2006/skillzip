<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Events;

use TVA\PayPal\Credentials;
use TVA\PayPal\Connection;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Merchant_Partner_Consent_Revoked
 *
 * Fires when a merchant revokes partner consent in PayPal.
 * Disconnects the plugin (clears all stored credentials + bearer tokens).
 */
class Merchant_Partner_Consent_Revoked extends Generic {

	public function do_action(): void {
		Credentials::disconnect_mode( $this->mode );
		Credentials::delete_capabilities( $this->mode );
		Credentials::delete_capabilities_summary( $this->mode );
		Connection::reset();

		do_action( 'tva_paypal_merchant_consent_revoked', $this->mode );
	}
}

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
 * Class Vault_Payment_Token_Deleted
 *
 * Removes the stored vault token from WP user meta when it is deleted in PayPal.
 */
class Vault_Payment_Token_Deleted extends Generic {

	public function do_action(): void {
		$resource = $this->get_resource();
		$vault_id = sanitize_text_field( $resource['id'] ?? '' );
		$email    = sanitize_email(
			$resource['payment_source']['paypal']['email_address'] ??
			$resource['customer']['email_address'] ??
			$resource['payer']['email_address'] ??
			''
		);

		if ( empty( $vault_id ) || empty( $email ) ) {
			return;
		}

		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return;
		}

		$meta_key     = 'tva_paypal_vault_token_' . $this->mode;
		$stored_vault = get_user_meta( $user->ID, $meta_key, true );

		if ( $stored_vault === $vault_id ) {
			delete_user_meta( $user->ID, $meta_key );
		}

		do_action( 'tva_paypal_vault_token_deleted', $user->ID, $vault_id, $this->mode );
	}
}

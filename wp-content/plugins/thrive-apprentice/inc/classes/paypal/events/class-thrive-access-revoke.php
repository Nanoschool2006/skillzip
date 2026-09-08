<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Events;

use TVA\PayPal\Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Thrive_Access_Revoke
 *
 * Handles the synthetic THRIVE.ACCESS.REVOKE webhook fired by the Product API
 * when D5 dunning is exhausted (Day 0 → Day 3 retry → Day 7 retry → all failed).
 *
 * This is the authoritative access revocation path for grace-period expiry.
 *
 * Payload root: x_thrive_order_id (integer TVA_Order DB ID)
 */
class Thrive_Access_Revoke extends Generic {

	public function do_action(): void {
		$thrive_order_id = (int) ( $this->event['x_thrive_order_id'] ?? 0 );

		if ( $thrive_order_id <= 0 ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'thrive_access_revoke_missing_order_id', [ 'event_id' => $this->get_event_id() ], true );
			return;
		}

		$order = new \TVA_Order( $thrive_order_id );

		if ( ! $order->get_id() ) {
			\TVA_Logger::set_type( 'PayPal Webhook' );
			\TVA_Logger::log( 'thrive_access_revoke_order_not_found', [ 'thrive_order_id' => $thrive_order_id ], true );
			return;
		}

		$user_id     = (int) $order->get_user_id();
		$user        = get_userdata( $user_id );
		$order_items = $order->get_order_items();
		$first_item  = ! empty( $order_items ) ? $order_items[0] : null;

		if ( ! $user ) {
			return;
		}

		// Cancel Product API subscription so no further renewals are attempted.
		$vault_data      = get_option( 'tva_paypal_vault_' . $thrive_order_id, [] );
		$subscription_id = $vault_data['subscription_id'] ?? '';
		$mode            = $vault_data['mode'] ?? $this->mode;

		if ( ! empty( $subscription_id ) ) {
			$cancel_result = Request::cancel_subscription( $subscription_id, $mode );
			if ( is_wp_error( $cancel_result ) ) {
				\TVA_Logger::set_type( 'PayPal Webhook' );
				\TVA_Logger::log( 'cancel_subscription_failed', [
					'subscription_id' => $subscription_id,
					'error'           => $cancel_result->get_error_message(),
				], true );
			}
		}

		$order->set_status( \TVA_Const::STATUS_FAILED );
		$order->save();

		if ( $first_item !== null ) {
			do_action( 'tva_subscription_cancelled', $user, $first_item, $order );

			$product_id = (int) $first_item->get_product_id();
			if ( $product_id > 0 ) {
				\Thrive_Apprentice_API::revoke_access( $user_id, $product_id, 'paypal_dunning_exhausted' );
			}
		}

		do_action( 'tva_paypal_access_revoked', $order, $this->event['resource'] ?? [] );
	}
}

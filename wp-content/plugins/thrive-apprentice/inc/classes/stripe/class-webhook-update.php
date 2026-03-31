<?php

namespace TVA\Stripe;

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Webhook_Update {

	const OPTION_NAME = 'tva_stripe_webhook_events_updated';

	/**
	 * Check if the webhook update is needed
	 *
	 * @return bool
	 */
	public static function should_update() {
		return Connection_V2::get_instance()->get_client() && empty( get_option( static::OPTION_NAME ) );
	}

	/**
	 * Run the webhook update
	 *
	 * @param bool $live_mode Whether to update live or test mode webhook
	 * @return bool True if the update was successful, false otherwise
	 */
	public static function update( $live_mode = true ) {
		$connection = Connection_V2::get_instance();
		$success = $connection->update_webhook_events( $live_mode );

		if ( $success ) {
			update_option( static::OPTION_NAME, 1, 'no' );
		}

		return $success;
	}

	/**
	 * Revert the webhook update
	 */
	public static function revert() {
		delete_option( static::OPTION_NAME );
	}
} 
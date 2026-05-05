<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe;

use TVA\Product;
use TVA_Course_V2;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Settings
 *
 * This class provides methods for handling Stripe settings.
 */
class Settings {

	const ALLOW_CHECKOUT_COUPONS_OPTIONS = 'tva_stripe_allow_checkout_coupons';

	const AUTO_DISPLAY_BUY_BUTTON_OPTIONS = 'tva_stripe_auto_display_buy_button';

	/**
	 * Update a setting.
	 *
	 * This method is responsible for updating a setting.
	 * It uses the update_option function to update the setting.
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $value       The new value of the option.
	 */
	public static function update_setting( $option_name, $value ) {
		update_option( $option_name, $value );
	}

	/**
	 * Get a setting.
	 *
	 * This method is responsible for retrieving a setting.
	 * It uses the get_option function to retrieve the setting.
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $default     The default value to return if the option does not exist.
	 *
	 * @return mixed The value of the option, or the default value if the option does not exist.
	 */
	public static function get_setting( $option_name, $default = null ) {
		return get_option( $option_name, $default );
	}
}

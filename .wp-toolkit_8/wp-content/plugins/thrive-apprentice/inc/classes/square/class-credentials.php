<?php

namespace TVA\Square;

use Random\RandomException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Credentials
 *
 * This class provides methods for handling Square credentials.
 */
class Credentials {

	/**
	 * Constants for option keys.
	 */

	const CREDENTIALS_ENDPOINT_OPTION 		= 'tva_square_credentials_endpoint';
	const CREDENTIALS_ENDPOINT_OPTION_LIVE 	= 'tva_square_credentials_endpoint_live';
	const STATE_OPTION                		= 'tva_square_connection_state';
	const STATE_OPTION_LIVE           		= 'tva_square_connection_state_live';
	const ACCESS_TOKEN_OPTION         		= 'tva_square_access_token';
	const ACCESS_TOKEN_OPTION_LIVE    		= 'tva_square_access_token_live';
	const REFRESH_TOKEN_OPTION        		= 'tva_square_refresh_token';
	const REFRESH_TOKEN_OPTION_LIVE   		= 'tva_square_refresh_token_live';
	const EXPIRES_AT_OPTION           		= 'tva_square_expires_at';
	const EXPIRES_AT_OPTION_LIVE      		= 'tva_square_expires_at_live';
	const MERCHANT_ID_OPTION          		= 'tva_square_merchant_id';
	const MERCHANT_ID_OPTION_LIVE     		= 'tva_square_merchant_id_live';
	const AUTO_DISPLAY_BUY_BUTTON_OPTIONS 	= 'tva_square_auto_display_buy_button';


	/**
	 * Get the TEST access token.
	 *
	 * @return string The TEST access token.
	 */
	public static function get_token() {
		return get_option( static::ACCESS_TOKEN_OPTION, '' );
	}

	/**
	 * Get the LIVE access token.
	 *
	 * @return string The LIVE access token.
	 */
	public static function get_token_live() {
		return get_option( static::ACCESS_TOKEN_OPTION_LIVE, '' );
	}

	/**
	 * Get the TEST refresh token.
	 *
	 * @return string The TEST refresh token.
	 */
	public static function get_refresh_token() {
		return get_option( static::REFRESH_TOKEN_OPTION, '' );
	}

	/**
	 * Get the LIVE refresh token.
	 *
	 * @return string The LIVE refresh token.
	 */
	public static function get_refresh_token_live() {
		return get_option( static::REFRESH_TOKEN_OPTION_LIVE, '' );
	}

	/**
	 * Get the TEST account ID.
	 *
	 * @return string The account ID.
	 */
	public static function get_account_id(): mixed {
		return get_option( static::MERCHANT_ID_OPTION, '' );
	}

	/**
	 * Get the LIVE account ID.
	 *
	 * @return string The account ID.
	 */
	public static function get_account_id_live(): mixed {
		return get_option( static::MERCHANT_ID_OPTION_LIVE, '' );
	}

	/**
	 * Save the TEST account ID.
	 *
	 * @param string $id The account ID to save.
	 */
	public static function save_account_id( $id ) {
		update_option( static::MERCHANT_ID_OPTION, $id, false );
	}

	/**
	 * Save the LIVE account ID.
	 *
	 * @param string $id The account ID to save.
	 */
	public static function save_account_id_live( $id ) {
		update_option( static::MERCHANT_ID_OPTION_LIVE, $id, false );
	}

	/**
	 * Get the credentials endpoint.
	 *
	 * @param string $mode The mode of the credentials endpoint.
	 * 
	 * @return string The credentials endpoint.
	 */
	public static function get_credentials_endpoint( $mode = 'live' ) {
		if ( $mode === 'live' ) {
			static::clear_object_cache( static::CREDENTIALS_ENDPOINT_OPTION_LIVE );
			$endpoint = get_option( static::CREDENTIALS_ENDPOINT_OPTION_LIVE, false );

			if ( ! $endpoint ) {
				$endpoint = uniqid( 'tva-webhook-square-live-' );
				update_option( static::CREDENTIALS_ENDPOINT_OPTION_LIVE, $endpoint, true );
			}
		} else {
			static::clear_object_cache( static::CREDENTIALS_ENDPOINT_OPTION );
			$endpoint = get_option( static::CREDENTIALS_ENDPOINT_OPTION, false );

			if ( ! $endpoint ) {
				$endpoint = uniqid( 'tva-webhook-square-' );
				update_option( static::CREDENTIALS_ENDPOINT_OPTION, $endpoint, true );
			}
		}

		return $endpoint;
	}

	/**
	 * Generate a state.
	 *
	 * @return string The generated state.
	 * @throws RandomException
	 */
	public static function generate_state() {
		$pad = substr( uniqid( 'square-', true ), 0, 20 );

		return str_pad( bin2hex( random_bytes( 16 ) ), 80, $pad, STR_PAD_BOTH );
	}

	/**
	 * Get the TEST state.
	 *
	 * @return string The state.
	 * @throws RandomException
	 */
	public static function get_state() {
		static::clear_object_cache( static::STATE_OPTION );
		$state = get_option( self::STATE_OPTION );
		if ( ! $state ) {
			$state = static::generate_state();
			update_option( static::STATE_OPTION, $state, false );
		}

		return $state;
	}

	/**
	 * Get the LIVE state.
	 *
	 * @return string The state.
	 * @throws RandomException
	 */
	public static function get_state_live() {
		static::clear_object_cache( static::STATE_OPTION_LIVE );
		$state = get_option( self::STATE_OPTION_LIVE );
		if ( ! $state ) {
			$state = static::generate_state();
			update_option( static::STATE_OPTION_LIVE, $state, false );
		}

		return $state;
	}

	/**
	 * Delete the TEST state.
	 */
	public static function delete_state() {
		delete_option( static::STATE_OPTION );
	}

	/**
	 * Delete the LIVE state.
	 */
	public static function delete_state_live() {
		delete_option( static::STATE_OPTION_LIVE );
	}

	/**
	 * Save the credentials.
	 *
	 * @param string $access_token    The access token.
	 * @param string $refresh_token   The refresh token.
	 * @param string $expires_at      The expiration date.
	 * @param string $mode            The mode.
	 * @param string $merchant_id      The merchant ID.
	 */
	public static function save_credentials( $access_token, $refresh_token, $expires_at, $mode, $merchant_id ) {
		if ( $mode === 'live' ) {
			update_option( static::ACCESS_TOKEN_OPTION_LIVE, $access_token, false );
			update_option( static::REFRESH_TOKEN_OPTION_LIVE, $refresh_token, false );
			update_option( static::EXPIRES_AT_OPTION_LIVE, $expires_at, false );
			static::save_account_id_live( $merchant_id );
			static::delete_state_live();
		} else {
			update_option( static::ACCESS_TOKEN_OPTION, $access_token, false );
			update_option( static::REFRESH_TOKEN_OPTION, $refresh_token, false );
			update_option( static::EXPIRES_AT_OPTION, $expires_at, false );
			static::save_account_id( $merchant_id );
			static::delete_state();
		}

		// set default options
		static::update_setting( static::AUTO_DISPLAY_BUY_BUTTON_OPTIONS, true );

		return true;
	}

	/**
	 * Disconnect the Square account.
	 *
	 * This method deletes all the saved credentials and the state.
	 */
	public static function disconnect() {
		static::delete_state();
		static::delete_state_live();
		delete_option( static::ACCESS_TOKEN_OPTION );
		delete_option( static::ACCESS_TOKEN_OPTION_LIVE );
		delete_option( static::REFRESH_TOKEN_OPTION );
		delete_option( static::REFRESH_TOKEN_OPTION_LIVE );
		delete_option( static::EXPIRES_AT_OPTION );
		delete_option( static::EXPIRES_AT_OPTION_LIVE );
		delete_option( static::MERCHANT_ID_OPTION );
		delete_option( static::MERCHANT_ID_OPTION_LIVE );
		delete_option( static::CREDENTIALS_ENDPOINT_OPTION );
		delete_option( static::CREDENTIALS_ENDPOINT_OPTION_LIVE );
	}

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

	/**
	 * Clear the WP Object Cache for a given key.
	 *
	 * This method is responsible for clearing the object cache for a given key.
	 * It uses the delete method of the WP_Object_Cache class to clear the cache.
	 *
	 * @param string $key The key to clear the cache for.
	 */
	public static function clear_object_cache( $key ) {
		$GLOBALS['wp_object_cache']->delete( $key, 'options' );
	}
}
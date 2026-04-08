<?php
/**
 * Access code helper
 *
 * @package FrmPdfs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmPdfsAccessCodeHelper
 */
class FrmPdfsAccessCodeHelper {

	/**
	 * The option name of secret key.
	 *
	 * @var string
	 */
	private static $option_name = 'frm_pdfs_secret_key';

	/**
	 * Return a valid access code from the given time.
	 *
	 * @since 2.0.7 Added the $key param.
	 *
	 * @param mixed  $current True to use current time, otherwise a timestamp string.
	 * @param string $key     Use this to make sure that each access code is unique.
	 *
	 * @return string
	 */
	public static function get_access_code( $current = true, $key = '' ) {
		// If $current was not passed, or it is true, we use the current timestamp.
		// If $current was passed in as a string, we'll use that passed in timestamp.
		if ( $current !== true ) {
			$time = $current;
		} else {
			$time = time();
		}

		// Format the timestamp to be less exact, as we want to deal in days.
		// June 19th, 2020 would get formatted as: 1906202017125.
		// Day of the month, month number, year, day number of the year, week number of the year.
		$token_date = gmdate( 'dmYzW', $time );

		// Combine our token date and our token salt, and md5 it.
		return md5( $token_date . $key . self::get_secret_key() );
	}

	/**
	 * Verifies the access code.
	 *
	 * @since 2.0.7 Added $key parameter.
	 *
	 * @param string $code Access code.
	 * @param string $key  Use this to make sure that each access code is unique.
	 *
	 * @return bool
	 */
	public static function verify( $code, $key = '' ) {
		return in_array( $code, self::get_valid_access_codes( $key ), true );
	}

	/**
	 * Gets the secret key. If it doesn't exist in DB, generate a new one and save it.
	 *
	 * @return string
	 */
	private static function get_secret_key() {
		$secret_key = get_option( self::$option_name );

		// If we already have the secret, send it back.
		if ( false !== $secret_key ) {
			return base64_decode( $secret_key ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		// We don't have a secret, so let's generate one.
		$secret_key = is_callable( 'sodium_crypto_secretbox_keygen' ) ? sodium_crypto_secretbox_keygen() : wp_generate_password( 32, true, true );
		add_option( self::$option_name, base64_encode( $secret_key ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		return $secret_key;
	}

	/**
	 * Gets valid access codes.
	 *
	 * @since 2.0.7 Added the $key param.
	 *
	 * @param string $key A unique key used to make sure that each access code is unique.
	 *
	 * @return array
	 */
	private static function get_valid_access_codes( $key ) {
		$current_date = time();

		/**
		 * Filters the maximum days for an access code to be expired.
		 *
		 * @param int $number_of_days Number of days.
		 */
		$number_of_days = apply_filters( 'frm_pdfs_access_code_max_days', 4 );

		$times_after_today = 45 * MINUTE_IN_SECONDS;

		$access_codes = array();

		for ( $i = 0; $i < $number_of_days; $i++ ) {
			$access_codes[] = self::get_access_code( $current_date - $i * DAY_IN_SECONDS, $key );
		}

		$access_codes[] = self::get_access_code( $current_date + $times_after_today, $key );
		$access_codes   = array_merge( $access_codes, self::get_valid_legacy_codes( $number_of_days ) );

		return array_unique( $access_codes );
	}

	/**
	 * Get old access codes with no additional $key specified.
	 * This way old access codes don't break when the plugin updates.
	 *
	 * @since 2.0.7
	 *
	 * @param int $number_of_days Number of days that PDF access codes are valid for.
	 *
	 * @return array<string>
	 */
	private static function get_valid_legacy_codes( $number_of_days ) {
		$option_name  = 'frm_pdfs_legacy_access_codes';
		$legacy_codes = get_option( $option_name );

		if ( ! is_array( $legacy_codes ) ) {
			$legacy_codes = array();

			$current_date = time();
			for ( $i = 0; $i < $number_of_days; $i++ ) {
				$legacy_codes[] = self::get_access_code( $current_date - $i * DAY_IN_SECONDS, '' );
			}

			update_option( $option_name, $legacy_codes, false );
		}

		return $legacy_codes;
	}
}

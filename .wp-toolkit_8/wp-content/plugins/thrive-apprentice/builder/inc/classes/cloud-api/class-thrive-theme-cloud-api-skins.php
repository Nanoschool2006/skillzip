<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Thrive_Theme_Cloud_Api_Skins
 */
class Thrive_Theme_Cloud_Api_Skins extends Thrive_Theme_Cloud_Api_Base {

	public $theme_element = 'skins';

	/**
	 * This transient name does not need to include the skin ID, just use the default transient name
	 *
	 * @return string
	 */
	public function get_transient_name() {
		return 'ttb_cloud_' . $this->theme_element;
	}

	// On skin download check first the TPM connection data and refresh the TTW auth token before going further
	public function before_zip() {

		// Check / refresh TTW access token before download skin
		$tpm_connection = method_exists( 'TPM_Connection', 'get_instance' ) && class_exists( 'TPM_Request' ) ? TPM_Connection::get_instance() : false;
		if ( $tpm_connection && true === $tpm_connection->is_expired() ) {
			$tpm_connection->refresh_token();
		}

		// User does't have his TTW account connected in TPM
		// Stop here, no need to start other requests
		if ( ! $tpm_connection ) {
			$update_check_url = sprintf( '<a href="%s" class="ttb-tpm-err-link">link</a>', admin_url( 'update-core.php?force-check=1' ) );
			throw new Exception( __( "Please make sure you have the latest version of Thrive Product Manager by clicking on this " . $update_check_url, 'thrive-theme' ) );
		}
	}

	/**
	 * Modify the cloud skins data before returning to the user.
	 * Updates preview URLs to use custom format: thrivethemes.com/themedemo/?theme=name
	 *
	 * @param array $items The cloud skins data.
	 *
	 * @return array Modified skins data with custom preview URLs.
	 */
	protected function before_data_list( $items ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}

		foreach ( $items as &$item ) {
			if ( isset( $item['name'] ) && ! empty( $item['name'] ) ) {
				// Sanitize the theme name for URL usage.
				$theme_name = $this->sanitize_theme_name_for_url( $item['name'] );
				
				// Build the custom preview URL.
				$item['preview_url'] = 'https://thrivethemes.com/themedemo/?theme=' . urlencode( $theme_name );
			}
		}

		return $items;
	}

	/**
	 * Sanitize theme name to make it URL-friendly.
	 * Removes the word "theme" from the name and converts to URL format.
	 *
	 * @param string $name The original theme name.
	 *
	 * @return string Sanitized theme name for URL usage.
	 */
	private function sanitize_theme_name_for_url( $name ) {
		// If not a string, cast to string.
		if ( ! is_string( $name ) ) {
			$name = '';
		}
		// Remove the word "theme" (case-insensitive) from the name.
		$sanitized = preg_replace( '/\btheme\b/i', '', $name );
		$sanitized = trim( $sanitized );
		// Convert to lowercase.
		$sanitized = strtolower( $sanitized );
		
		// Replace spaces and special characters with hyphens.
		$sanitized = preg_replace( '/[^a-z0-9]+/', '-', $sanitized );
		
		// Remove leading and trailing hyphens.
		$sanitized = trim( $sanitized, '-' );
		
		// Fallback for edge cases where sanitization results in empty string.
		if ( empty( $sanitized ) ) {
			$sanitized = 'shapeshift';
		}
		return $sanitized;
	}
}
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
 * Class Thrive_Utils
 */
class Thrive_Theme_Update {

	const THRIVE_KEY = '@#$()%*%$^&*(#@$%@#$%93827456MASDFJIK3245';

	const API_URL = 'https://service-api.thrivethemes.com/theme/update';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_themes', [ __CLASS__, 'check_for_update' ] );

		add_filter( 'themes_api', [ __CLASS__, 'api_call' ], 10, 3 );
	}

	/**
	 * Prepare request args for api call
	 *
	 * @param $action
	 * @param $args
	 *
	 * @return array
	 */
	public static function prepare_request( $action = '', $args = [] ) {

		global $wp_version;

		$ttw_id = TD_TTW_Connection::get_instance()->is_connected() ? TD_TTW_Connection::get_instance()->ttw_id : 0;

		return [
			'body'       => [
				'type'               => tvd_get_update_channel(),
				'client_site_url'    => home_url(),
				'client_php_version' => PHP_VERSION,
				'ttw_id'             => (string) $ttw_id,
				'wp_version'         => (string) get_bloginfo( 'version' ),
				'action'             => $action,
				'request'            => serialize( $args ),
				'api-key'            => md5( home_url() ),
			],
			'sslverify'  => false,
			'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
		];
	}

	/**
	 * Calc the hash that should be sent on API's requests
	 *
	 * @param $data
	 *
	 * @return string
	 */
	private static function calc_hash( $data ) {
		return md5( static::THRIVE_KEY . serialize( $data ) . static::THRIVE_KEY );
	}

	/**
	 * Do API call to check if the theme has an update
	 *
	 * @param $transient
	 *
	 * @return mixed
	 */
	public static function check_for_update( $transient = null ) {

		if ( tve_dash_is_debug_on() ) {
			/* don't show updates on the development environment */
			return $transient;
		}

		if ( empty( $transient->checked[ THEME_DOMAIN ] ) ) {
			return $transient;
		}

		$args = static::prepare_request( 'theme_update', [
			'slug'    => THEME_DOMAIN,
			'version' => THEME_VERSION,
		] );

		$url = add_query_arg( [
			'p' => static::calc_hash( $args['body'] ),
		], static::API_URL );

		$response = wp_remote_get( $url, $args );

		if ( ! is_wp_error( $response ) ) {
			$data = thrive_safe_unserialize( wp_remote_retrieve_body( $response ) );
			/* if we have a different version available */
			if ( isset( $data['new_version'] ) && version_compare( $transient->checked[ THEME_DOMAIN ], $data['new_version'], '<' ) ) {
				$data          = (array) $data;
				$data['theme'] = THEME_DOMAIN;

				$transient->response[ THEME_DOMAIN ] = $data;
			}
		}

		return $transient;
	}

	/**
	 * Theme API info
	 *
	 * @param $def
	 * @param $action
	 * @param $args
	 *
	 * @return bool|mixed|WP_Error
	 */
	public static function api_call( $def, $action, $args ) {

		if ( empty( $args->slug ) || $args->slug !== THEME_DOMAIN ) {
			return false;
		}

		$args->version = THEME_VERSION;
		$params        = static::prepare_request( $action, $args );

		$url = add_query_arg( [
			'p' => static::calc_hash( $params['body'] ),
		], static::API_URL );

		$request = wp_remote_get( $url, $params );

		if ( is_wp_error( $request ) ) {
			$response = new WP_Error( 'themes_api_failed', __( 'An Unexpected HTTP Error occurred during the API request', 'thrive-theme' ) . '</p> <p><a href="?" onclick="document.location.reload(); return false;"> ' . __( 'Try again', 'thrive-theme' ) . '', $request->get_error_message() );
		} else {
			$response = thrive_safe_unserialize( wp_remote_retrieve_body( $request ) );

			if ( is_array( $response ) ) {
				$response = (object) $response;
			}

			if ( $response === false ) {
				$response = new WP_Error( 'themes_api_failed', __( 'An unknown error occurred', 'thrive' ), $response );
			}
		}

		return $response;
	}
}

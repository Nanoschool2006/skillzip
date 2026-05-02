<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Public_API;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Main bootstrap for the Public API module.
 *
 * Loads all classes in this directory and registers REST routes
 * under the thrivethemes/v1 namespace.
 */
class Main {
	/**
	 * Entry point for the Public API module.
	 *
	 * @return void
	 */
	public static function init() {
		static::includes();

		add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );

		TD_API_Admin::init();

		add_filter( 'authenticate', [ __CLASS__, 'filter_authenticate' ], 100, 3 );
	}

	/**
	 * Require all PHP files next to this one, except this class file itself.
	 *
	 * @return void
	 */
	public static function includes() {
		foreach ( glob( __DIR__ . '/*.php' ) as $file ) {
			if ( strpos( $file, 'class-main.php' ) !== false ) {
				continue;
			}

			require_once $file;
		}
	}

	/**
	 * Register all REST API routes for the Public API module.
	 *
	 * @return void
	 */
	public static function rest_api_init() {
		$rest = new REST_Controller();
		$rest->register_routes();

		$tokens = new TD_API_Tokens_Controller();
		$tokens->register_routes();
	}

	/**
	 * Allow API token authentication through WordPress authenticate filter.
	 *
	 * This enables token-based auth for WP-API/Swagger integrations.
	 * Previously lived in Thrive Apprentice (tva_filter_authenticate).
	 *
	 * @param \WP_User|null $user
	 * @param string        $username
	 * @param string        $password
	 *
	 * @return \WP_User|null
	 */
	public static function filter_authenticate( $user, $username, $password ) {
		if ( TD_API_Token::auth( $username, $password ) ) {
			$user = get_user_by( 'ID', 1 );
		}

		return $user;
	}
}

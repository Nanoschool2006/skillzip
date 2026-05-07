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
 * Admin integration for API Keys tab within API Connections page.
 */
class TD_API_Admin {

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_scripts' ] );
	}

	/**
	 * Enqueue scripts and styles on the API Connections page.
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	public static function enqueue_scripts( $hook ) {
		if ( $hook !== 'admin_page_tve_dash_api_connect' ) {
			return;
		}

		wp_enqueue_script(
			'td-api-keys',
			TVE_DASH_URL . '/inc/public-api/js/api-keys.js',
			[ 'jquery', 'wp-api-fetch' ],
			TVE_DASH_VERSION,
			true
		);

		wp_localize_script( 'td-api-keys', 'TD_API_Keys', [
			'nonce'    => wp_create_nonce( 'wp_rest' ),
			'rest_url' => rest_url( 'td/v1/api-tokens' ),
			'tab'      => isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '',
			'i18n'     => [
				'confirm_delete'    => esc_html__( 'Are you sure you want to delete this API key?', 'thrive-dash' ),
				'name_required'     => esc_html__( 'Please enter a name for the API key.', 'thrive-dash' ),
				'copied'            => esc_html__( 'Copied!', 'thrive-dash' ),
				'copy'              => esc_html__( 'Copy', 'thrive-dash' ),
				'save_key_notice'   => esc_html__( 'Save this key now. It will be partially hidden once you leave this page.', 'thrive-dash' ),
				'active'            => esc_html__( 'Active', 'thrive-dash' ),
				'inactive'          => esc_html__( 'Inactive', 'thrive-dash' ),
				'unnamed_key'       => esc_html__( 'Unnamed Key', 'thrive-dash' ),
				'created'           => esc_html__( 'Created', 'thrive-dash' ),
				'copy_key'          => esc_html__( 'Copy key', 'thrive-dash' ),
				'enable'            => esc_html__( 'Enable', 'thrive-dash' ),
				'disable'           => esc_html__( 'Disable', 'thrive-dash' ),
				'delete'            => esc_html__( 'Delete', 'thrive-dash' ),
				'generate'          => esc_html__( 'Generate & Save', 'thrive-dash' ),
				'api_key_copied'    => esc_html__( 'API Key Copied', 'thrive-dash' ),
				'fail_create'       => esc_html__( 'Failed to create API key.', 'thrive-dash' ),
				'fail_update'       => esc_html__( 'Failed to update API key.', 'thrive-dash' ),
				'fail_delete'       => esc_html__( 'Failed to delete API key.', 'thrive-dash' ),
				'fail_load'         => esc_html__( 'Failed to load API keys.', 'thrive-dash' ),
			],
		] );

		wp_enqueue_style(
			'td-api-keys',
			TVE_DASH_URL . '/inc/public-api/css/api-keys.css',
			[],
			TVE_DASH_VERSION
		);
	}

	/**
	 * Render the API Keys tab content.
	 *
	 * @return void
	 */
	public static function render_tab_content() {
		include __DIR__ . '/views/api-keys.phtml';
	}
}

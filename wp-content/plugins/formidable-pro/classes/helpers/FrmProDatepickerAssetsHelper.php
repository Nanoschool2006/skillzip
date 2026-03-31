<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProDatepickerAssetsHelper {

	/**
	 * Initialize datepicker on the style settings page.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	public static function init_admin_js_and_css() {
		if ( FrmProAppHelper::use_jquery_datepicker() ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			return;
		}
		self::enqueue_flatpickr_assets();
	}

	/**
	 * Load the theme assets for the datepicker.
	 *
	 * @since 6.24
	 *
	 * @param string $theme_css The theme CSS.
	 *
	 * @return void
	 */
	public static function load_theme_assets( $theme_css ) {
		if ( ! FrmProAppHelper::use_jquery_datepicker() ) {
			return;
		}
		wp_enqueue_style( 'jquery-theme', FrmProStylesController::jquery_css_url( $theme_css ), array(), FrmProDb::$plug_version );
	}

	/**
	 * Enqueue the datepicker assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	public static function enqueue_datepicker_assets() {
		if ( FrmProAppHelper::use_jquery_datepicker() ) {
			self::enqueue_jquery_assets();
			return;
		}

		self::enqueue_flatpickr_assets();
	}

	/**
	 * Enqueue the jQuery assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	private static function enqueue_jquery_assets() {
		FrmProStylesController::enqueue_jquery_css();
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	/**
	 * Enqueues the flatpickr assets.
	 *
	 * @since 6.24
	 *
	 * @return void
	 */
	private static function enqueue_flatpickr_assets() {
		wp_enqueue_script(
			'flatpickr',
			FrmProAppHelper::plugin_url() . '/js/utils/flatpickr/flatpickr.min.js',
			array(),
			FrmProDb::$plug_version,
			true
		);

		wp_enqueue_style( 'flatpickr', FrmProAppHelper::plugin_url() . '/css/flatpickr.css', array(), FrmProDb::$plug_version );
	}
}

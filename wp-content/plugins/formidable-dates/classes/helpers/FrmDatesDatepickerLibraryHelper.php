<?php
/**
 * Datepicker library helper
 *
 * @package formidable-dates
 * @since 3.0
 */

/**
 * Class FrmDatesDatepickerLibraryHelper
 */
class FrmDatesDatepickerLibraryHelper {

	/**
	 * Loads the admin style and scripts for the datepicker library.
	 *
	 * @since 3.0
	 */
	public static function load_admin_style_and_scripts() {
		if ( is_callable( array( 'FrmProDatepickerAssetsHelper', 'init_admin_js_and_css' ) ) ) {
			FrmProDatepickerAssetsHelper::init_admin_js_and_css();
			return;
		}

		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	/**
	 * Gets the admin JS dependencies.
	 *
	 * @since 3.0
	 * @return array
	 */
	public static function get_admin_js_dependencies() {
		if ( self::use_jquery_datepicker() ) {
			return array( 'jquery-ui-datepicker', 'jquery-effects-highlight', 'wp-hooks' );
		}

		if ( ! wp_script_is( 'flatpickr', 'registered' ) ) {
			wp_register_script(
				'flatpickr',
				FrmProAppHelper::plugin_url() . '/js/utils/flatpickr/flatpickr.min.js',
				array(),
				FrmProDb::$plug_version
			);
		}

		if ( ! wp_style_is( 'flatpickr', 'registered' ) ) {
			wp_enqueue_style(
				'flatpickr',
				FrmProAppHelper::plugin_url() . '/css/flatpickr.css',
				array(),
				FrmProDb::$plug_version
			);
		}

		return array( 'flatpickr', 'wp-hooks' );
	}

	/**
	 * Loads the localization file for the datepicker library.
	 *
	 * @since 3.0
	 * @param string $locale The locale to load the localization file for.
	 */
	public static function load_localization_file( $locale ) {

		if ( 'en' === $locale ) {
			return;
		}

		if ( self::use_jquery_datepicker() ) {
			wp_enqueue_script( 'jquery-ui-i18n-' . $locale, FrmProAppHelper::plugin_url() . '/js/jquery-ui-i18n/datepicker-' . $locale . '.min.js', array( 'jquery-ui-core', 'jquery-ui-datepicker' ), '1.13.2' );
			return;
		}

		wp_enqueue_script( 'flatpickr-locale-' . $locale, FrmProAppHelper::plugin_url() . '/js/utils/flatpickr/l10n/' . $locale . '.js', array( 'frmdates_flatpickr' ), FrmDatesAppHelper::plugin_version() );
	}

	public static function use_jquery_datepicker() {
		return is_callable( array( 'FrmProAppHelper', 'use_jquery_datepicker' ) ) && FrmProAppHelper::use_jquery_datepicker();
	}
}

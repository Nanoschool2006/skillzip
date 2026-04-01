<?php

class FrmExportViewHooksController {
	/**
	 * The Formidable version this plugin requires.
	 *
	 * @var string $min_formidable_version
	 */
	public static $min_formidable_version = '4.0.3';

	/**
	 * Loads hooks on every page.
	 */
	public static function load_hooks() {
		if ( is_admin() ) {
			FrmExportViewAppController::load_lang();
		}

		if ( ! self::required_pro_version_is_installed() ) {
			add_action( 'admin_notices', array( 'FrmExportViewAppController', 'pro_not_installed_notice' ) );
			return;
		}

		if ( ! self::required_views_version_is_installed() ) {
			add_action( 'admin_notices', array( 'FrmExportViewAppController', 'views_not_installed_notice' ) );
			return;
		}

		// Adds frm-export-view shortcode.
		add_shortcode( 'frm-export-view', 'FrmExportViewShortcode::shortcode' );
		// Add export button below View table.
		add_filter( 'frm_after_display_content', 'FrmExportViewLink::maybe_add_export_view_link', 15, 4 );
		// Export CSV.
		add_action( 'parse_request', 'FrmExportViewCSVController::maybe_export_csv' );
		// Create custom cron schedule.
		add_filter( 'cron_schedules', 'FrmExportViewCron::create_cron_schedule' );
		// Cron job action.
		add_action( 'frm_export_view_cron', 'FrmExportViewCron::frm_export_view_cron' );

		self::load_admin_hooks();
	}

	/**
	 * Loads hook only in the admin.
	 */
	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmExportViewAppController::include_updater', 1 );
		add_action( 'admin_enqueue_scripts', 'FrmExportViewHooksController::add_scripts' );
		add_action( 'after_plugin_row_formidable-view-export/formidable-view-export.php', 'FrmExportViewAppController::min_version_notice' );
		// Create meta box.
		add_action( 'add_meta_boxes', 'FrmExportViewSettingsController::add_meta_box' );
		add_action( 'frm_add_settings_section', 'FrmExportViewGlobalSettingsController::add_settings_section' );
		// Set up cron job.
		add_action( 'frm_export_view_options_saved', 'FrmExportViewCron::set_up_export_view_cron_job' );
	}

	/**
	 * Adds plugin's JavaScript file.
	 */
	public static function add_scripts() {
		global $post_type_object;

		if ( ! self::is_form_global_settings_page() && ( ! $post_type_object || FrmViewsDisplaysController::$post_type != $post_type_object->name ) ) {
			return;
		}

		$version      = FrmExportViewUpdate::get_export_view_version();
		$dependencies = array( 'jquery' );

		wp_enqueue_script( 'formidable-export-view', FrmExportViewAppController::plugin_url() . '/js/frm_export_view.js', $dependencies, $version, true );
	}

	/**
	 * Checks if the current page is Formidable's Global Setting page.
	 *
	 * @return bool
	 * @since 1.0
	 */
	private static function is_form_global_settings_page() {
		$page = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		return ( 'formidable-settings' === $page );
	}

	/**
	 * Checks if Pro is installed and the version is compatible with the Export View add-on.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private static function required_pro_version_is_installed() {
		if ( ! is_callable( 'FrmAppHelper::pro_is_installed' ) || ! FrmAppHelper::pro_is_installed() ) {
			return false;
		}

		$frm_pro_version = class_exists( 'FrmProDb' ) && FrmProDb::$plug_version ? FrmProDb::$plug_version : 0;
		return version_compare( $frm_pro_version, self::$min_formidable_version, '>=' );
	}

	/**
	 * Checks if Views is installed.
	 *
	 * @since 1.08
	 *
	 * @return bool
	 */
	private static function required_views_version_is_installed() {
		return class_exists( 'FrmViewsDisplaysController' );
	}
}

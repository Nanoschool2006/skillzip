<?php

/**
 * FrmGoogleSpreadsheetHooksController class.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetHooksController {

	/**
	 * Adds this class to hook controllers list.
	 *
	 * @since 1.0
	 *
	 * @param array $controllers Hooks controllers.
	 * @return array
	 */
	public static function add_hooks_controller( $controllers ) {
		if ( ! self::is_compatible() ) {
			self::load_incompatible_hooks();
			return $controllers;
		}

		$controllers[] = __CLASS__;
		return $controllers;
	}

	/**
	 * Loads hooks when this plugin isn't safe to run.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private static function load_incompatible_hooks() {
		self::load_translation();

		add_action( 'admin_notices', array( __CLASS__, 'show_incompatible_notice' ) );

		$page = FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' );
		if ( 'formidable' === $page ) {
			add_filter( 'frm_message_list', array( __CLASS__, 'add_incompatible_message' ) );
		}
	}

	/**
	 * Loads translation.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	private static function load_translation() {
		add_action( 'init', array( 'FrmGoogleSpreadsheetAppController', 'init_translation' ) );
	}

	/**
	 * Load hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'frm_trigger_googlespreadsheet_action', 'FrmGoogleSpreadsheetAppController::trigger_googlespreadsheet', 10, 3 );
		add_action( 'frm_registered_form_actions', 'FrmGoogleSpreadsheetAppController::register_actions' );
		add_action( 'wp', 'FrmGoogleSpreadsheetAuth::maybe_echo_post_message_script' );

		self::load_translation();
	}

	/**
	 * Load admin hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_admin_hooks() {
		add_action( 'admin_init', 'FrmGoogleSpreadsheetAppController::include_updater' );
		add_action( 'admin_enqueue_scripts', 'FrmGoogleSpreadsheetAppController::enqueue_admin_js' );

		// Settings controller.
		add_filter( 'frm_add_settings_section', 'FrmGoogleSpreadsheetSettingsController::add_settings_section' );
		add_action( 'frm_update_settings', 'FrmGoogleSpreadsheetSettingsController::update' );
		add_action( 'frm_store_settings', 'FrmGoogleSpreadsheetSettingsController::store' );
		add_filter( 'frm_autoresponder_allowed_actions', 'FrmGoogleSpreadsheetAppController::add_google_spreadsheet_to_automation' );

	}

	/**
	 * Load ajax hooks.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function load_ajax_hooks() {
		// Action form hooks.
		add_action( 'wp_ajax_frm_googlespreadsheet_get_sheets', 'FrmGoogleSpreadsheetManager::get_sheets' );
		add_action( 'wp_ajax_clear_googlespreadsheet_files_cache', 'FrmGoogleSpreadsheetAppController::clear_cache' );
		add_action( 'wp_ajax_frm_googlespreadsheet_match_fields', 'FrmGoogleSpreadsheetAppController::match_fields' );
		add_action( 'wp_ajax_sync_entries_google_spreadsheet', 'FrmGoogleSpreadsheetManager::send_entries_google_spreadsheet' );

		// Settings form authorization hooks.
		add_action( 'wp_ajax_formidable_googlespreadsheet_authorization', 'FrmGoogleSpreadsheetAuth::authorization' );
		add_action( 'wp_ajax_frm_save_google_sheets_code', 'FrmGoogleSpreadsheetAuth::fallback_authorization' );
		add_action( 'wp_ajax_formidable_googlespreadsheet_revoke', 'FrmGoogleSpreadsheetAuth::revoke' );
	}

	/**
	 * Checks if this plugin is safe to run.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private static function is_compatible() {
		$allow = function_exists( 'load_formidable_pro' );

		return $allow;
	}

	/**
	 * Display an error to the user that the plugin could not get activated.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function show_incompatible_notice() {
		echo '<div class="error">' .
			'<p>' . esc_html( self::incompatible_message() ) . '</p>' .
		'</div>';
	}

	/**
	 * Display an error to the user that the plugin could not get activated.
	 *
	 * @since 1.0
	 *
	 * @param array<string> $messages used for formidable.
	 * @return array<string> Array of messages.
	 */
	public static function add_incompatible_message( $messages ) {
		$messages[] = self::incompatible_message();
		return $messages;
	}

	/**
	 * Get the error message to show if not compatible.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	private static function incompatible_message() {
		return __( 'Formidable Google Sheets requires an active version of Formidable Pro.', 'formidable-google-sheets' );
	}
}

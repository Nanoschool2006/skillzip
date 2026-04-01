<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * FrmGoogleSpreadsheetSettingsController Controller.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetSettingsController {

	/**
	 * Add setting section to formidable panel.
	 *
	 * @since 1.0
	 *
	 * @param array<string, array<string, string>|object> $sections formidable section array for setting panel.
	 * @return array<string, array<string, string>|object>
	 */
	public static function add_settings_section( $sections ) {
		$sections['googlespreadsheet'] = array(
			'class'    => __CLASS__,
			'function' => 'settings_form',
			'name'     => __( 'Google Sheets', 'formidable-google-sheets' ),
			'icon'     => 'frm_icon_font frm_googlesheets_icon',
		);
		return $sections;
	}

	/**
	 * Setting form.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function settings_form() {
		$settings         = FrmGoogleSpreadsheetAppHelper::get_settings();
		$client_id        = $settings->frm_googlespreadsheet_client_id;
		$client_secret    = $settings->frm_googlespreadsheet_client_secret;
		$doc_url          = 'https://formidableforms.com/knowledgebase/google-spreadsheet-forms/';
		$auth_settings    = get_option( 'formidable_googlespreadsheet_auth' );
		$has_access_token = ! empty( $auth_settings['access_token'] );

		// Convert fields to asterisks whenever authorize process has been finished.
		if ( $has_access_token ) {
			$client_id     = FrmGoogleSpreadsheetAppHelper::change_string_to_asterisks( $client_id );
			$client_secret = FrmGoogleSpreadsheetAppHelper::change_string_to_asterisks( $client_secret );
		}

		$auth_button = FrmGoogleSpreadsheetAppHelper::get_setting_form_authorization_data( $client_id );

		require_once FrmGoogleSpreadsheetAppHelper::path() . '/views/settings/form.php';
	}

	/**
	 * Update setting field according to the new params.
	 *
	 * @since 1.0
	 *
	 * @param array $params of updated form.
	 * @see action hook frm_update_settings
	 * @return void
	 */
	public static function update( $params ) {
		if ( ! isset( $params['frm_googlespreadsheet_client_id'] ) ) {
			return;
		}
		$settings = FrmGoogleSpreadsheetAppHelper::get_settings();
		$settings->update( $params );
	}

	/**
	 * Save updated field to the DB.
	 *
	 * @since 1.0
	 *
	 * @see action hook frm_store_settings
	 * @return void
	 */
	public static function store() {
		$process_form = FrmAppHelper::get_post_param( 'process_form', '', 'sanitize_text_field' );
		if ( ! wp_verify_nonce( $process_form, 'process_form_nonce' ) ) {
			return;
		}

		$settings = FrmGoogleSpreadsheetAppHelper::get_settings();
		$settings->store();
	}
}

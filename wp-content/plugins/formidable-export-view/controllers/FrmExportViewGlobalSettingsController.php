<?php

class FrmExportViewGlobalSettingsController {
	/**
	 * Adds an export View setting section to Formidable's Global Settings page.
	 *
	 * @param array $sections Array of the sections in Global Settings.
	 *
	 * @return array Array of the sections in Global Settings.
	 */
	public static function add_settings_section( $sections ) {
		$sections['export-view'] = array(
			'class'    => 'FrmExportViewGlobalSettingsController',
			'function' => 'route',
			'name'     => __( 'Export Views', 'formidable-export-view' ),
			'icon'     => 'frm_icon_font frm_export_icon',
		);

		return $sections;
	}

	/**
	 * Retrieves saved settings and displays form to set Export View setting.
	 */
	public static function display_form() {
		$export_settings = new FrmExportViewGlobalSettings();
		self::show_form( $export_settings );
	}

	/**
	 * Displays settings form, populated with the values from the settings.
	 *
	 * @param object $export_settings Global settings.
	 */
	private static function show_form( $export_settings ) {
		$selected_views = array();
		if ( isset( $export_settings->settings ) && isset( $export_settings->settings->export_view_id ) ) {
			$selected_views = (array) $export_settings->settings->export_view_id;
		}
		$views     = FrmExportViewHelper::get_views();
		$has_views = count( $views ) > 0;
		$formats   = self::get_csv_formats();
		require_once FrmExportViewAppController::plugin_path() . '/views/settings/form.php';
	}


	/**
	 * Get the CSV export format options.
	 *
	 * @since 1.09
	 *
	 * @return array
	 */
	private static function get_csv_formats() {
		$formats = FrmCSVExportHelper::csv_format_options();
		array_splice( $formats, 1, 0, 'UTF-8 with BOM' );

		return $formats;
	}

	/**
	 * Saves the new settings in the database and displays the form with the updated values.
	 */
	public static function process_form() {
		$export_settings = new FrmExportViewGlobalSettings();
		// $errors = $export_settings->validate($_POST,array());
		$errors = array();

		$export_settings->update( $_POST ); // WPCS: CSRF ok.

		if ( empty( $errors ) ) {
			$export_settings->store();
			$message = __( 'Settings Saved', 'formidable-export-view' );
		}

		self::show_form( $export_settings );
	}

	/**
	 * Determines whether the form will be processed before being displayed based on the action in the URL.
	 */
	public static function route() {
		$action = FrmAppHelper::get_param( 'action' );
		if ( 'process-form' == $action ) {
			self::process_form();
		} else {
			self::display_form();
		}
	}
}

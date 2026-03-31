<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class Settings Controller.
 *
 * @since 1.0.1
 */
class FrmLogSettingsController {

	/**
	 * Add setting section to formidable panel.
	 *
	 * @since 1.0.1
	 *
	 * @param array<string, array<string, string>|object> $sections formidable section array for setting panel.
	 * @return array<string, array<string, string>|object>
	 */
	public static function add_settings_section( $sections ) {
		$sections['logs'] = array(
			'class'    => __CLASS__,
			'function' => 'logs_settings',
			'name'     => __( 'Logs', 'formidable' ),
			'icon'     => 'frm_icon_font frm_white_label_icon',
		);

		return $sections;
	}

	/**
	 * Display form for settings panel.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public static function logs_settings() {
		$frmlog_settings = FrmLogAppHelper::get_settings();
		$data            = wp_next_scheduled( 'frmlog_auto_clear' ) ? gmdate( 'm/d/Y', wp_next_scheduled( 'frmlog_auto_clear' ) ) : false;
		include FrmLogAppHelper::plugin_path() . '/views/settings/logs.php';
	}

	/**
	 * Update setting field according to the new params.
	 *
	 * @since 1.0.1
	 *
	 * @see action hook frm_update_settings
	 *
	 * @param array<string|int> $params of updated form.
	 * @return void
	 */
	public static function update( $params ) {
		$frmlog_settings = FrmLogAppHelper::get_settings();
		$frmlog_settings->update( $params );
	}

	/**
	 * Save updated field to the DB.
	 *
	 * @since 1.0.1
	 *
	 * @see action hook frm_store_settings
	 * @return void
	 */
	public static function store() {
		$frmlog_settings = FrmLogAppHelper::get_settings();
		$frmlog_settings->store();
	}

}

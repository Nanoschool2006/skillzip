<?php
/**
 * Plugin Name: Formidable Google Sheets
 * Description: Send entries to a Google spreadsheet from a form.
 * Version: 1.0.5
 * Plugin URI: https://formidableforms.com/
 * Author: Strategy11
 * Author URI: https://formidableforms.com/
 * Text Domain: formidable-google-sheets
 * Domain Path: /languages/
 *
 * @package   formidable-google-sheets
 */

// No direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load plugin classes.
 *
 * @param string $class_name class name.
 * @return void
 */
function frm_googlespreadsheet_forms_autoloader( $class_name ) {
	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmGoogleSpreadsheet.+$/', $class_name ) ) {
		return;
	}

	$path = dirname( __FILE__ );

	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$path .= '/helpers/' . $class_name . '.php';
	} elseif ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$path .= '/controllers/' . $class_name . '.php';
	} else {
		$path .= '/models/' . $class_name . '.php';
	}

	if ( file_exists( $path ) ) {
		include $path;
	}
}

spl_autoload_register( 'frm_googlespreadsheet_forms_autoloader' );

add_action( 'frm_load_controllers', array( 'FrmGoogleSpreadsheetHooksController', 'add_hooks_controller' ) );

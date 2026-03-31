<?php
/*
Plugin Name: Formidable API
Description: Create entries in Formidable via a rest API, and send REST API requests when forms are submitted
Version: 1.18
Plugin URI: https://formidableforms.com/
Author URI: https://formidableforms.com/
Author: Strategy11
Text Domain: frmapi
*/

/**
 * @return void
 */
function frm_forms_api_autoloader( $class_name ) {
	// Only load Frm classes here
	if ( ! preg_match( '/^FrmAPI.+$/', $class_name ) ) {
		return;
	}

	$filepath = dirname( __FILE__ );
	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$filepath .= '/helpers/';
	} else if ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$filepath .= '/controllers/';
	} else {
		$filepath .= '/models/';
	}

	$filepath .= $class_name . '.php';

	if ( file_exists( $filepath ) ) {
		include( $filepath );
	}
}

// Add the autoloader
spl_autoload_register( 'frm_forms_api_autoloader' );

FrmAPIAppController::load_hooks();
FrmAPISettingsController::load_hooks();

<?php
/**
 * Formidable Export View
 *
 * Plugin Name: Formidable Export View to CSV
 * Description: Export table Views to CSV files
 * Version: 1.10
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 * Text Domain: formidable-export-view
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package formidable-export-view
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Load all the classes for this plugin.
 *
 * @param string $class_name The name of the class to load.
 */
function frm_export_view_forms_autoloader( $class_name ) {
	$path = dirname( __FILE__ );

	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmExportView.+$/', $class_name ) ) {
		return;
	}

	if ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$path .= '/controllers/' . $class_name . '.php';
	} elseif ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$path .= '/helpers/' . $class_name . '.php';
	} else {
		$path .= '/models/' . $class_name . '.php';
	}

	if ( file_exists( $path ) ) {
		include $path;
	}
}

register_deactivation_hook( __FILE__, 'FrmExportViewCron::delete_export_view_cron_job' );

// Add the autoloader.
spl_autoload_register( 'frm_export_view_forms_autoloader' );

// Load hooks.
add_action( 'plugins_loaded', 'FrmExportViewHooksController::load_hooks' );


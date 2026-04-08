<?php
/**
 * Plugin Name: Formidable AI
 * Description: Bring the power of AI to your forms
 * Version: 2.1
 * Plugin URI: https://formidableforms.com/
 * Author URI: https://formidableforms.com/
 * Author: Strategy11
 * Text Domain: formidable-ai
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads all the classes for this plugin.
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
function frm_ai_autoloader( $class_name ) {
	$path = __DIR__;

	// Only load Frm classes here.
	if ( ! preg_match( '/^FrmAI.+$/', $class_name ) ) {
		return;
	}

	if ( is_callable( 'frm_class_autoloader' ) ) {
		frm_class_autoloader( $class_name, $path );
	}
}
spl_autoload_register( 'frm_ai_autoloader' );

add_filter( 'frm_load_controllers', array( 'FrmAIHooksController', 'add_hooks_controller' ) );

if ( class_exists( 'FrmAIAppController' ) ) {
	register_activation_hook( __FILE__, array( 'FrmAIAppController', 'update_stylesheet_on_activation' ) );
}

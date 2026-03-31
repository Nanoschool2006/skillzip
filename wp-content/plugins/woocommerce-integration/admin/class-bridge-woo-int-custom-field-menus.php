<?php
/**
 * Setup plugin menus in WP admin.
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Woocommerce Integration
 * @subpackage Woocommerce integration/admin
 */

namespace NmBridgeWoocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 * Eb_Admin_Menus Class
 */
class Bridge_Woo_Int_Menu {

	/**
	 * Add settings submenu item
	 *
	 * @since 2.1.4
	 */
	public function custom_field_menu() {
		$custom_field_plugin_path = 'edwiser-custom-fields/edwiser-custom-fields.php';
		if ( ! is_plugin_active( $custom_field_plugin_path ) ) {
			add_submenu_page(
				'edit.php?post_type=eb_course',
				__( 'Custom User Fields', 'woocommerce-integration' ),
				__( 'Custom User Fields', 'woocommerce-integration' ),
				'manage_options',
				'eb-wi-custom-fields',
				array( $this, 'custom_fields_page' )
			);
		}
	}


	/**
	 * Initialize the settings page.
	 *
	 * @since 2.1.4
	 */
	public function custom_fields_page() {

		$custom_field_handler = new Bridge_Wi_Custom_Field_Hanndler();

		$custom_field_handler->wi_output_custom_fields();
	}
}

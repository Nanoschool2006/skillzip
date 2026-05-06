<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-university
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

require_once dirname( __FILE__ ) . '/functions.php';
require_once dirname( __FILE__ ) . '/includes/tva-class-admin.php';

/**
 * like homepage or blog page we display a label for checkout and thank you page
 */
add_filter( 'display_post_states', 'tva_display_post_states', 10, 2 );


/**
 * Add the Thrive Apprentice menu item
 */
// Send success response
class TVA_Automator_Uncanny {

	public static function init() {
		add_action( 'wp_ajax_check_plugin_status', array( 'TVA_Automator_Uncanny', 'check_plugin_status' ) );
		add_action( 'wp_ajax_thrive_apprentice_uncanny_ajax', array( 'TVA_Automator_Uncanny', 'thrive_apprentice_uncanny_ajax' ) );
		add_action( 'wp_ajax_activate_uncanny_if_installed_ajax', array( 'TVA_Automator_Uncanny', 'activate_uncanny_if_installed_ajax' ) );
	}

	public static function check_plugin_status() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'tap_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce.', 403 );
			wp_die();
		}
		if ( is_plugin_active( 'thrive-automator/thrive-automator.php' ) && is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			wp_send_json_success( array( 'status' => 'both_active' ) );
		} elseif ( is_plugin_active( 'thrive-automator/thrive-automator.php' ) && ! file_exists( WP_PLUGIN_DIR . '/uncanny-automator/uncanny-automator.php' ) ) {
			wp_send_json_success( array( 'status' => 'automator_installed' ) );
		} elseif ( file_exists( WP_PLUGIN_DIR . '/uncanny-automator/uncanny-automator.php' ) && ! is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) && ! is_plugin_active( 'thrive-automator/thrive-automator.php' )) {
			wp_send_json_success( array( 'status' => 'uncanny_installed_but_inactive' ) );
		} elseif ( is_plugin_active( 'thrive-automator/thrive-automator.php' ) && ! is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			wp_send_json_success( array( 'status' => 'thrive_autom_active_uncanny_deactivated' ) );			
		} elseif ( is_plugin_active( 'uncanny-automator/uncanny-automator.php' ) ) {
			wp_send_json_success( array( 'status' => 'uncanny_active' ) );
		} else {
			wp_send_json_error( array( 'status' => 'not_installed' ) );
		}
	}

	public static function activate_uncanny_if_installed_ajax() {
		$plugin_slug = 'uncanny-automator/uncanny-automator.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
			$activated = activate_plugin( $plugin_slug );

			if ( is_wp_error( $activated ) ) {
				return new WP_Error( 'activation_failed', $activated->get_error_message() );
			}

			if ( is_plugin_active( $plugin_slug ) ) {
				return true;
			}

			return new WP_Error( 'activation_failed', 'Plugin activation failed.' );
		}

		return new WP_Error( 'plugin_not_found', 'Plugin is not installed.' );
	}
	/**
	 * Function to install the plugin from the WordPress.org repository
	 *
	 * @return bool|WP_Error
	 */
	public static function thrive_apprentice_install_uncanny() {
		$plugin_slug     = 'uncanny-automator/uncanny-automator.php';
		$plugin_dir_slug = 'uncanny-automator';
		if ( ! is_plugin_active( $plugin_slug ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin_dir_slug,
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) ) {
				return new WP_Error( 'plugin_info_failed', $api->get_error_message() );
			}

			$upgrader  = new Plugin_Upgrader();
			$installed = $upgrader->install( $api->download_link );

			if ( ! $installed || is_wp_error( $installed ) ) {
				return new WP_Error( 'install_failed', 'Plugin installation failed.' );
			}

			return true;
		}

		return new WP_Error( 'already_installed', 'Plugin is already installed.' );
	}

	/**
	 * Function to activate the plugin
	 *
	 * @return bool|WP_Error
	 */
	public static function thrive_apprentice_uncanny_activate() {
		$plugin_slug = 'uncanny-automator/uncanny-automator.php';

		if ( is_plugin_active( $plugin_slug ) ) {
			return new WP_Error( 'already_active', 'Plugin is already activated.' );
		}

		$activated = activate_plugin( $plugin_slug );

		if ( is_wp_error( $activated ) ) {
			return new WP_Error( 'activation_failed', $activated->get_error_message() );
		}

		if ( is_plugin_active( $plugin_slug ) ) {
			return true;
		}

		return new WP_Error( 'activation_failed', 'Plugin activation failed.' );
	}

	/**
	 * AJAX handler to install and activate the plugin
	 */
	public static function thrive_apprentice_uncanny_ajax() {
		// Check for required permissions
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied.' ) );
			wp_die();
		}

		// Verify the nonce for security
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'tap_admin_nonce' ) ) {
			// Send a JSON error response if the nonce is invalid.
			wp_send_json_error( 'Invalid nonce.', 403 );
			// Terminate execution.
			wp_die();
		}
		$plugin_slug = 'uncanny-automator/uncanny-automator.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug ) ) {
			$activated = activate_plugin( $plugin_slug );
		}
		else {
			// Install the plugin
			$install_result = self::thrive_apprentice_install_uncanny();

			if ( is_wp_error( $install_result ) ) {
				wp_send_json_error( array( 'message' => $install_result->get_error_message() ) );
				wp_die();
			}

			// Activate the plugin
			$activate_result = self::thrive_apprentice_uncanny_activate();
			if ( is_wp_error( $activate_result ) ) {
				wp_send_json_error( array( 'message' => $activate_result->get_error_message() ) );
			}
		}
	}
}

TVA_Automator_Uncanny::init();

<?php

class FrmRegAppController {

	/**
	 * Load the translations
	 */
	public static function load_lang() {
		load_plugin_textdomain( 'frmreg', false, FrmRegAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Print a notice if Formidable is too old to be compatible with the registration add-on
	 */
	public static function min_version_notice() {
		if ( FrmRegAppHelper::is_formidable_compatible() ) {
			return;
		}

		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		echo '<tr class="plugin-update-tr active"><th colspan="' . absint( $wp_list_table->get_column_count() ) . '" class="check-column plugin-update colspanchange"><div class="update-message">' .
			esc_html__( 'You are running an outdated version of Formidable. This plugin will not work correctly if you do not update Formidable.', 'frmreg' ) .
			'</div></td></tr>';
	}

	/**
	 * Adds the updater
	 * Called by the admin_init hook
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmRegUpdate::load_hooks();
		}
	}

	/**
	 * Migrate settings if needed
	 *
	 * @since 2.0
	 */
	public static function initialize() {
		if ( ! FrmRegAppHelper::is_formidable_compatible() ) {
			return;
		}

		$frm_reg_db = new FrmRegDb();
		if ( $frm_reg_db->need_to_migrate_settings() ) {
			$frm_reg_db->migrate();
		}
	}

	/**
	 * Display admin notices if Formidable is too old or registration settings need to be migrated
	 *
	 * @since 2.0
	 */
	public static function display_admin_notices() {

		// Don't display notices as we're upgrading
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';
		if ( $action == 'upgrade-plugin' && ! isset( $_GET['activate'] ) ) {
			return;
		}

		// Show message if Formidable is not compatible
		if ( ! FrmRegAppHelper::is_formidable_compatible() ) {
			include FrmRegAppHelper::path() . '/views/update_formidable.php';
			return;
		}

		// Add Update button
		$frm_reg_db = new FrmRegDb();
		if ( $frm_reg_db->need_to_migrate_settings() ) {
			if ( is_callable( 'FrmAppHelper::plugin_url' ) ) {
				$url = FrmAppHelper::plugin_url();
			} else if ( defined( 'FRM_URL' ) ) {
				$url = FRM_URL;
			} else {
				return;
			}

			include FrmRegAppHelper::path() . '/views/update_database.php';
		}
	}

	/**
	 * Load the login form CSS
	 *
	 * @since 2.0
	 */
	public static function add_login_form_css() {
		readfile( FrmRegAppHelper::path() . '/css/login_form.css' );
	}

	/**
	 * Add styles for registration action form.
	 *
	 * @since 2.13
	 *
	 * @param string $hook Hook suffix for the current admin page.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( ! FrmRegAppHelper::is_form_settings_page() ) {
			return;
		}
		wp_enqueue_style( 'frm_reg_admin', FrmRegAppHelper::plugin_url() . '/css/frm-reg-admin.css', array(), FrmRegAppHelper::plugin_version() );
	}

	/**
	 * Get class name to use from style used in the reg shortcode.
	 *
	 * @since 3.0.1
	 *
	 * @param array  $atts    The shortcode attributes.
	 * @param string $key     The style option key.
	 * @param string $default The default value.
	 *
	 * @return string
	 */
	public static function get_style_option( $atts, $key, $default ) {
		if ( empty( $atts['class'] ) || false === strpos( $atts['class'], 'frm_style_' ) ) {
			return $default;
		}
		$classes = explode( ' ', $atts['class'] );
		foreach ( $classes as $class ) {
			if ( false === strpos( $class, 'frm_style_' ) ) {
				continue;
			}
			$style_name = str_replace( 'frm_style_', '', $class );
			$style      = get_page_by_path( $style_name, OBJECT, 'frm_styles' );
			if ( $style ) {
				break;
			}
		}
		if ( empty( $style ) ) {
			return $default;
		}
		$style->post_content = FrmAppHelper::maybe_json_decode( $style->post_content );

		$style_object        = new FrmStyle();
		$style->post_content = $style_object->override_defaults( $style->post_content );
		/**
		 * @psalm-suppress InvalidPropertyAssignmentValue
		 */
		$style->post_content = wp_parse_args( $style->post_content, $style_object->get_defaults() );

		if ( empty( $style->post_content[ $key ] ) ) {
			return $default;
		}
		$style_value = $style->post_content[ $key ];
		if ( 'position' !== $key ) {
			return $style_value;
		}

		if ( 'none' === $style_value || 'inside' === $style_value ) {
			$style_value = 'top';
		} elseif ( 'no_label' === $style_value ) {
			$style_value = 'none';
		}
		return $style_value;
	}

	/**
	* Show a tooltip icon with the message passed.
	*
	* @since 3.0.1
	*
	* @param string $message The message to be displayed in the tooltip.
	* @param array  $atts    The attributes to be added to the tooltip.
	*
	* @return void
	*/
	public static function show_svg_tooltip( $message, $atts = array() ) {
		if ( ! is_callable( 'FrmAppHelper::tooltip_icon' ) ) {
			return;
		}
		FrmAppHelper::tooltip_icon( $message, $atts );
	}
}

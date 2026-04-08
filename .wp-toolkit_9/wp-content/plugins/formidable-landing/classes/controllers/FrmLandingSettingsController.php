<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmLandingSettingsController {

	public static function load_hooks() {
		if ( is_admin() ) {
			add_filter( 'frm_add_form_settings_section', 'FrmLandingSettingsController::add_settings_section', 20 ); // use 20 so it happens after pro updates sections.
			add_action( 'admin_enqueue_scripts', 'FrmLandingSettingsController::enqueue_admin_js' );
			add_filter( 'frm_landing_page_preview_option', 'FrmLandingSettingsController::get_settings_views_path', 20 );
		}
		add_filter( 'frm_pro_default_form_settings', 'FrmLandingSettingsController::add_settings' );
	}

	/**
	 * Enqueue the JS for the settings page
	 */
	public static function enqueue_admin_js() {
		if ( class_exists( 'FrmAppHelper' ) && self::is_form_settings_page() ) {
			$version = FrmLandingAppHelper::plugin_version();

			wp_register_script( 'formidable_landing_settings', FrmLandingAppHelper::plugin_url() . '/js/settings.js', array(), $version, true );
			wp_enqueue_script( 'formidable_landing_settings' );

			wp_register_style( 'formidable_landing_settings', FrmLandingAppHelper::plugin_url() . '/css/settings.css', array(), $version );
			wp_enqueue_style( 'formidable_landing_settings' );
		}
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @return bool
	 */
	private static function is_form_settings_page() {
		$is_form_settings_page = false;
		$page                  = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action                = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		if ( 'formidable' === $page && 'settings' === $action ) {
			$is_form_settings_page = true;
		}
		return $is_form_settings_page;
	}

	/**
	 * @return string
	 */
	public static function get_settings_views_path() {
		return FrmLandingAppHelper::path() . '/views/settings/';
	}

	public static function add_settings_section( $sections ) {
		$sections['landing'] = array(
			'function' => array( __CLASS__, 'settings_section' ),
			'name'     => __( 'Form Landing Page', 'formidable' ),
			'icon'     => 'frm_icon_font frm_file_text_icon',
			'anchor'   => 'landing',
		);
		return $sections;
	}

	public static function settings_section( $values ) {
		$layouts = self::layout_options();

		self::set_bg_and_opacity( $values );
		$bg_image_id = $values['landing_bg_image_id'];

		if ( $bg_image_id ) {
			$bg_image          = wp_get_attachment_image( $bg_image_id );
			$bg_image_filepath = get_attached_file( $bg_image_id );
			$bg_image_filename = basename( $bg_image_filepath );
		} else {
			$bg_image          = '<img src="" class="frm_hidden" />';
			$bg_image_filepath = '';
			$bg_image_filename = '';
		}

		require self::get_settings_views_path() . 'settings.php';
	}

	/**
	 * If there's no bg set in the form, check the form style.
	 *
	 * @param array $values
	 */
	private static function set_bg_and_opacity( &$values ) {
		$values['landing_bg_image_id'] = absint( $values['landing_bg_image_id'] );
		$values['landing_opacity']     = absint( $values['landing_opacity'] );

		if ( $values['landing_bg_image_id'] ) {
			return;
		}

		$form           = FrmForm::getOne( $values['id'] );
		$style_settings = FrmLandingAppHelper::get_form_style_settings( $form );
		$values['landing_bg_image_id'] = absint( $style_settings['bg_image_id'] );

		if ( $values['landing_opacity'] ) {
			return;
		}

		$values['landing_opacity'] = absint( $style_settings['bg_image_opacity'] );
	}

	private static function layout_options() {
		return array(
			array(
				'type'  => 'default',
				'label' => __( 'Classic', 'formidable-landing' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 187 184"><rect width="187" height="184" fill="#F6F7FB" rx="4"/><rect width="137" height="14" x="25" y="90" fill="#8F99A6" fill-opacity=".2" rx="1.7"/><rect width="75" height="14" x="56" y="112" fill="#4199FD" rx="1.7"/><rect width="109" height="9" x="40" y="69" fill="#9EA9B8" fill-opacity=".7" rx="4.5"/></svg>',
			),
			array(
				'type'  => 'block',
				'label' => __( 'Card', 'formidable-landing' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 187 184"><rect width="187" height="184" fill="#BCC4CE" rx="4"/><rect width="10" height="10" x="177" y="174" fill="#B9C2CD"/><rect width="10" height="10" y="174" fill="#B9C2CD"/><rect width="157" height="99" x="14" y="45" fill="#F6F7FB" rx="4"/><rect width="137" height="14" x="25" y="87" fill="#8F99A6" fill-opacity=".2" rx="1.7"/><rect width="75" height="14" x="56" y="109" fill="#4199FD" rx="1.7"/><rect width="109" height="9" x="40" y="66" fill="#9EA9B8" fill-opacity=".7" rx="4.5"/></svg>',
			),
			array(
				'type'  => 'left',
				'label' => __( 'Left', 'formidable-landing' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 187 184"><rect width="187" height="184" fill="#F6F7FB" rx="4"/><path fill="#B9C2CD" d="M113 0h71a3 3 0 0 1 3 3v178a3 3 0 0 1-3 3h-71V0Z"/><rect width="79" height="14" x="17" y="90" fill="#8F99A6" fill-opacity=".2" rx="1.7"/><rect width="10" height="10" x="177" y="174" fill="#B9C2CD"/><rect width="50" height="14" x="32" y="112" fill="#4199FD" rx="1.7"/><rect width="63" height="9" x="25" y="69" fill="#9EA9B8" fill-opacity=".7" rx="4.5"/></svg>',
			),
			array(
				'type'  => 'right',
				'label' => __( 'Right', 'formidable-landing' ),
				'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 187 184"><rect width="187" height="184" fill="#F6F7FB" rx="4"/><rect width="74" height="184" fill="#B9C2CD" rx="4"/><rect width="9" height="9" y="175" fill="#B9C2CD"/><rect width="9" height="9" x="65" y="175" fill="#B9C2CD"/><rect width="9" height="9" x="65" fill="#B9C2CD"/><rect width="79" height="14" x="91" y="90" fill="#8F99A6" fill-opacity=".2" rx="1.7"/><rect width="50" height="14" x="106" y="112" fill="#4199FD" rx="1.7"/><rect width="63" height="9" x="99" y="69" fill="#9EA9B8" fill-opacity=".7" rx="4.5"/></svg>',
			),
		);
	}

	/**
	 * Add default settings to the form.
	 *
	 * @param array $settings The existing default form settings.
	 *
	 * @return array
	 */
	public static function add_settings( $settings ) {
		$settings['landing_layout']      = 'default';
		$settings['landing_bg_image_id'] = 0;
		$settings['landing_opacity']     = '';

		return $settings;
	}

	/**
	 * @param int $form_id
	 * @return array
	 */
	public static function get_landing_page_url( $form_id ) {
		$landing_page_post_name = self::get_landing_page_post_name( $form_id );
		if ( false !== $landing_page_post_name ) {
			return array( $landing_page_post_name, true );
		}

		$form_name = FrmDb::get_var( 'frm_forms', array( 'id' => $form_id ), 'name' );
		if ( $form_name ) {
			$key = str_replace( ' ', '-', $form_name );
			$key = sanitize_key( $key );
			$key = strtolower( $key );
			return array( $key, false );
		}

		return array( '', false );
	}

	/**
	 * @param int $form_id
	 * @return string|false
	 */
	public static function get_landing_page_post_name( $form_id ) {
		$landing_page_post_id = FrmLandingAppHelper::get_landing_page_post_id( $form_id );
		if ( $landing_page_post_id ) {
			$post = get_post( $landing_page_post_id );
			if ( $post && 'publish' === $post->post_status ) {
				return $post->post_name;
			}
		}
		return false;
	}

	public static function validate_landing_page_url() {
		FrmAppHelper::permission_check( 'frm_change_settings' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$page_name = FrmAppHelper::get_post_param( 'pageName', '', 'sanitize_key' );
		if ( ! $page_name ) {
			wp_die( '0' );
		}

		$form_id = FrmAppHelper::get_post_param( 'formId', 0, 'absint' );

		$valid = self::page_name_is_allowed( $page_name, $form_id );
		$html  = self::get_landing_page_validation_response( $valid );
		wp_send_json_success( compact( 'html' ) );
	}

	/**
	 * @param string $page_name
	 * @param int    $form_id
	 * @return bool
	 */
	private static function page_name_is_allowed( $page_name, $form_id ) {
		if ( in_array( $page_name, FrmFormsHelper::reserved_words(), true ) ) {
			return false;
		}
		$post_id_with_slug = FrmDb::get_var( 'posts', array( 'post_name' => $page_name ) );
		if ( $post_id_with_slug ) {
			$landing_page_post_id = FrmLandingAppHelper::get_landing_page_post_id( $form_id );
			if ( (int) $landing_page_post_id === (int) $post_id_with_slug ) {
				return true;
			}
			return false;
		}
		return true;
	}

	/**
	 * @param bool $valid
	 * @return string
	 */
	private static function get_landing_page_validation_response( $valid ) {
		if ( ! $valid ) {
			return '<span class="frm_error">' . esc_html__( 'This URL is taken', 'formidable-landing' ) . '</span>';
		}
		return '<span style="color: #1E561F;">' . esc_html__( 'This URL is available', 'formidable-landing' ) . '</span>';
	}

	public static function get_settings_url( $form_id ) {
		return admin_url( 'admin.php?page=formidable&frm_action=settings&id=' . absint( $form_id ) . '&t=landing_settings' );
	}

	/**
	 * @param string $id
	 * @param string $name
	 * @param array  $args
	 * @return string|void
	 */
	private static function toggle( $id, $name, $args ) {
		return FrmProHtmlHelper::admin_toggle( $id, $name, $args );
	}
}

<?php

class FrmRegAppHelper {

	private static $min_formidable_version = 2.0;

	/**
	 * @var string $plug_version
	 */
	public static $plug_version = '3.0.1';

	/**
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	/**
	 * Get the plugin path
	 *
	 * @return string
	 */
	public static function path() {
		return dirname( __DIR__ );
	}

	/**
	 * Get the plugin folder
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::path() );
	}

	/**
	 * Get the plugin URL
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::path() . '/formidable-registration.php' );
	}

	/**
	 * Check if the current version of Formidable is compatible with Registration add-on
	 *
	 * @since 2.0
	 * @return mixed
	 */
	public static function is_formidable_compatible() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;

		return version_compare( $frm_version, self::$min_formidable_version, '>=' );
	}

	/**
	 * Get the current site name
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public static function get_site_name() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Enqueue the admin JS file
	 *
	 * @since 2.0
	 */
	public static function enqueue_admin_js() {
		wp_register_script( 'frmreg_admin', self::plugin_url() . '/js/back_end.js', array(), self::plugin_version() );

		$global_data = array(
			'nonce' => wp_create_nonce( 'frm_ajax' ),
		);

		if ( self::is_repeater_actions_ready() && self::is_form_settings_page() ) {
			$form_id = FrmAppHelper::simple_get( 'id', 'intval' );

			$global_data['repeaters']   = FrmProFieldsHelper::get_repeater_fields( $form_id );
			$global_data['formActions'] = array_values( FrmFormAction::get_action_for_form( $form_id, 'register', array( 'post_status' => 'all' ) ) );
		}

		wp_localize_script( 'frmreg_admin', 'frmRegGlobal', $global_data );

		if ( self::is_form_settings_page() ) {
			wp_enqueue_script( 'frmreg_admin' );
		}
	}

	/**
	 * Checks if Repeater Actions feature is ready in Pro plugin.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 */
	private static function is_repeater_actions_ready() {
		return method_exists( 'FrmProFieldsHelper', 'get_repeater_fields' );
	}

	/**
	 * Enqueue the global JS script
	 *
	 * @since 2.09
	 */
	public static function enqueue_global_js() {
		if ( FrmAppHelper::simple_get( 'page', 'sanitize_title' ) === 'formidable-settings' ) {
			wp_add_inline_script( 'formidable_admin_global', "jQuery( document ).ready( function() { frmDom.autocomplete.initAutocomplete( 'page' ); })" );
		}
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @since 2.0
	 * @since 2.13 Function went from private to public.
	 *
	 * @return bool
	 */
	public static function is_form_settings_page() {
		$is_form_settings_page = false;

		$page    = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action  = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		$form_id = FrmAppHelper::simple_get( 'id', 'intval' );

		if ( $page === 'formidable' && $action === 'settings' && $form_id ) {
			$is_form_settings_page = true;
		}

		return $is_form_settings_page;
	}

	/**
	 * Echo 'checked="checked"' if a given value exists in an array
	 *
	 * @param array $array
	 * @param string $current
	 */
	public static function array_checked( $array, $current ) {
		if ( ! empty( $array ) && in_array( $current, $array ) ) {
			echo " checked='checked'";
		}
	}

	/**
	 * Check if the current user has permission to create new users with the given form action
	 *
	 * @since 2.0
	 * @since 3.0 The `$action` parameter could be null.
	 *
	 * @param WP_Post|null $action
	 * @return bool
	 */
	public static function current_user_can_create_users( $action ) {
		$can_create = false;

		if ( ! $action ) {
			return $can_create;
		}

		$capability = apply_filters( 'frmreg_required_role', '' );

		$is_rest_request = defined( 'REST_REQUEST' ) && REST_REQUEST;
		if ( FrmAppHelper::is_admin() || $is_rest_request || self::always_allow_edit() ) {
			$can_create = true;
		} else if ( $capability !== '' ) {
			$can_create = current_user_can( $capability );
		} elseif ( isset( $action->post_content['reg_create_users'] ) && $action->post_content['reg_create_users'] === 'allow' ) {
			// Check if current user's role(s) is within selected roles
			foreach ( $action->post_content['reg_create_role'] as $selected_role ) {
				if ( current_user_can( $selected_role ) ) {
					$can_create = true;
					break;
				}
			}
		}

		return $can_create;
	}

	/**
	 * Check if the current user can update the profile of the selected user ID
	 *
	 * @since 2.0
	 *
	 * @param int|string    $profile_user_id
	 * @param WP_Post       $register_action
	 * @param object|string $form
	 *
	 * @return bool
	 */
	public static function current_user_can_update_profile( $profile_user_id, $register_action, $form = '' ) {
		$can_update      = false;
		$profile_user_id = (int) $profile_user_id;
		$current_user_id = get_current_user_id();

		if ( current_user_can( 'administrator' ) || self::always_allow_edit() ) {
			$can_update = true;
		} elseif ( $profile_user_id && $current_user_id && $profile_user_id === $current_user_id ) {
			$can_update = true;
		} elseif ( self::current_user_can_create_users( $register_action ) ) {
			$can_update = true;
		} elseif ( $profile_user_id && is_object( $form ) && ! empty( $form->options['open_editable_role'] ) && FrmProFieldsHelper::user_has_permission( $form->options['open_editable_role'] ) ) {
			$can_update = true;
		}

		return $can_update;
	}

	/**
	 * Check for situations where editing should be allowed
	 *
	 * @since 2.02.01
	 *
	 * @return bool
	 */
	private static function always_allow_edit() {
		$force_edit = defined( 'FRM_ALLOW_EDIT' ) && FRM_ALLOW_EDIT;
		if ( is_callable( 'wp_doing_cron' ) ) {
			$is_cron = wp_doing_cron(); // added in WP 4.8
		} else {
			$is_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		}

		$allowed = $force_edit || $is_cron || self::is_import();
		return apply_filters( 'frmreg_allow_edit', $allowed );
	}

	/**
	 * Check if import is occurring
	 *
	 * @since 2.02.01
	 *
	 * @return bool
	 */
	public static function is_import() {
		return defined( 'WP_IMPORTING' ) && WP_IMPORTING;
	}

	/**
	 * @return int|false
	 */
	public static function username_exists( $username ) {
		$username = sanitize_user( $username, true );

		if ( ! function_exists( 'username_exists' ) ) {
			require_once ABSPATH . WPINC . '/registration.php';
		}

		return username_exists( $username );
	}

	/**
	 * @param string $name
	 * @param string $class
	 * @return void
	 */
	public static function add_tooltip( $name, $class = 'closed' ) {
		$tooltips = array(
			'mod_email'     => __( 'Require new users to confirm their e-mail address before they may log in.', 'frmreg' ),
			'mod_admin'     => __( 'Require new users to be approved by an administrator before they may log in.', 'frmreg' ),
			'mod_redirect'  => __( 'Select the page where users will be redirected after clicking the activation link.', 'frmreg' ),
			'create_subsite' => __( 'Create a new subdomain or subdirectory when a user registers with this form.', 'frmreg' ),
		);

		if ( ! isset( $tooltips[ $name ] ) ) {
			return;
		}

		if ( 'open' === $class ) {
			echo ' frm_help"';
		} else {
			echo ' class="frm_help"';
		}

		echo ' title="' . esc_attr( $tooltips[ $name ] );

		if ( 'open' !== $class ) {
			echo '"';
		}
	}

	/**
	 * Gets request method from $_SERVER.
	 *
	 * @since 2.05
	 *
	 * @return string
	 */
	public static function request_method() {
		return FrmAppHelper::get_server_value( 'REQUEST_METHOD' );
	}

	/**
	 * Returns the URL for a page, of its translate if available.
	 *
	 * @since 2.09
	 *
	 * @param int $page_id The page id.
	 *
	 * @return string The page URL.
	 */
	public static function get_page_url( $page_id ) {

		if ( function_exists( 'pll_current_language' ) ) {
			$pll_current_language = pll_current_language();

			if ( $pll_current_language !== pll_get_post_language( $page_id ) ) {
				$page_id = pll_get_post( $page_id, $pll_current_language );
			}
		}

		return get_permalink( $page_id );
	}

	/**
	 * Get the global reset password page ID
	 *
	 * @since 2.01
	 * @since 2.13  Function moved from FrmRegRegistrationPageController to FrmRegAppHelper.
	 * @return string
	 */
	private static function registration_page_id() {
		$global_settings = new FrmRegGlobalSettings();
		return $global_settings->get_global_page( 'register_page' );
	}

	/**
	 * Get the registration page URL
	 *
	 * @since 2.01
	 * @since 2.13 Function went from private to public and moved from FrmRegRegistrationPageController to FrmRegAppHelper.
	 *
	 * @param string $fallback
	 *
	 * @return false|string
	 */
	public static function registration_page_url( $fallback = 'wordpress' ) {
		$page_id = self::registration_page_id();

		if ( $page_id ) {
			$page_url = self::get_page_url( $page_id );
		} elseif ( $fallback === 'wordpress' ) {
			$page_url = site_url( 'wp-login.php?action=register' );
		} else {
			$page_url = '';
		}

		return $page_url;
	}

	/**
	 * Get the form that was used to register a user
	 *
	 * @since 2.0
	 * @since 2.13 Function moved from FrmRegModerationController to FrmRegAppHelper
	 * @param int $user_id
	 *
	 * @return object|false
	 */
	public static function get_form_for_user( $user_id ) {
		if ( ! $user_id ) {
			return false;
		}

		// Get form ID from user meta
		$form_id = get_user_meta( $user_id, 'frmreg_form_id', 1 );
		if ( ! $form_id || ! is_numeric( $form_id ) ) {
			return false;
		}

		$form = FrmForm::getOne( $form_id );
		return is_object( $form ) ? $form : false;
	}

	/**
	 * Get form settings for a specific user
	 *
	 * @since 2.0
	 * @since 2.13 Function went from private to public and moved from FrmRegModerationController to FrmRegAppHelper
	 *
	 * @param string|int $user_id - User's ID number
	 *
	 * @return array $settings - Form's Registration Settings
	 */
	public static function get_registration_settings_for_user( $user_id ) {
		$form = self::get_form_for_user( $user_id );

		if ( $form ) {
			$settings = FrmRegActionHelper::get_registration_settings_for_form( $form );
		} else {
			$settings = array();
		}

		return $settings;
	}

	/**
	 * Check for the Siteground Security Optimizer plugin.
	 * This is required to handle conflicts with the plugin.
	 *
	 * @since 3.0.1
	 *
	 * @return bool
	 */
	public static function siteground_security_is_active() {
		global $sg_security_loader;
		return ! is_null( $sg_security_loader );
	}

	/**
	 * Determine whether we want to allow people to login using alt-login.php.
	 *
	 * @since 3.0.1
	 *
	 * @return bool
	 */
	public static function supports_alternative_login_url() {
		return (bool) apply_filters( 'frmreg_supports_alternative_login_url', false );
	}
}

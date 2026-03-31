<?php
if ( ! defined( 'ABSPATH' )) die( 'You are not allowed to call this page directly.' );

class FrmAPIAppController {
	public static $timeout                 = 15;
	public static $v2_base                 = 'frm/v2';
	private static $min_formidable_version = '2.0';

	/**
	 * @return void
	 */
	public static function load_hooks() {
		self::check_preflight_request();
		self::maybe_allow_form_preview_in_iframe();

		add_action( 'admin_init', array( __CLASS__, 'include_updater' ) );
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_action( 'init', array( __CLASS__, 'load_lang' ), 0 );
		register_activation_hook( FrmAPIAppHelper::folder_name() . '/formidable-api.php', array( __CLASS__, 'install' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'create_initial_rest_routes' ), 0 );
		add_shortcode( 'frm-api', array( __CLASS__, 'show_api_object' ) );

		add_filter( 'get_frm_stylesheet', array( __CLASS__, 'force_css_to_use_action' ) );
		add_filter( 'frm_saved_css', array( __CLASS__, 'use_cors_safe_fonts' ) );
		add_action( 'frm_update_form', array( __CLASS__, 'on_form_update' ), 10 );
		add_action( 'set_transient_frmpro_css', array( __CLASS__, 'on_css_update' ) );
		add_action( 'delete_transient_frmpro_css', array( __CLASS__, 'on_css_delete' ) );

		add_action( 'wp_ajax_frmpro_css', array( __CLASS__, 'maybe_force_css' ) );
		add_action( 'wp_ajax_nopriv_frmpro_css', array( __CLASS__, 'maybe_force_css' ) );
		add_action( 'wp_ajax_frm_api_get_font', array( __CLASS__, 'load_icon_font' ) );
		add_action( 'wp_ajax_nopriv_frm_api_get_font', array( __CLASS__, 'load_icon_font' ) );
		add_action( 'wp_ajax_frm_forms_preview', array( __CLASS__, 'maybe_add_scripts_before_form_preview' ), 5 );
		add_action( 'wp_ajax_nopriv_frm_forms_preview', array( __CLASS__, 'maybe_add_scripts_before_form_preview' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'maybe_add_scripts_before_landing_page' ) );

		if ( wp_doing_ajax() ) {
			add_action( 'wp_loaded', array( __CLASS__, 'hydrate_api_before_ajax_create' ), 4 );
		}

		if ( false !== strpos( $_SERVER['REQUEST_URI'], '/wp-json/frm/forms' ) || false !== strpos( $_SERVER['REQUEST_URI'], '/wp-json/frm/entries' ) ) {
			FrmAPIv1Controller::load_hooks();
			add_filter( 'frm_create_cookies', '__return_false' );
		}
	}

	/**
	 * @since 1.16
	 *
	 * @return void
	 */
	public static function load_lang() {
		load_plugin_textdomain( 'frmapi', false, FrmAPIAppHelper::folder_name() . '/languages/' );
	}

	/**
	 * Check preflight request to allow CORS when getting form HTML via API.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function check_preflight_request() {
		if ( ! self::is_preflight_request() ) {
			return;
		}

		$requesting_html    = self::is_requesting_form_html_via_api();
		$uploading_dropzone = self::is_uploading_dropzone();

		if ( ! $requesting_html && ! $uploading_dropzone ) {
			return;
		}

		self::set_allow_origin_header();
		header( 'Access-Control-Allow-Credentials: true' );

		if ( $requesting_html ) {
			header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept' );
		} elseif ( $uploading_dropzone ) {
			header( 'Access-Control-Allow-Methods: POST, OPTIONS' );
			header( 'Access-Control-Allow-Headers: Cache-Control, X-Requested-With, Frm-Dropzone' );
		}

		exit( 0 );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function set_allow_origin_header() {
		header( 'Access-Control-Allow-Origin: ' . apply_filters( 'frmapi_access_control_allow_origin', '*' ) );
	}

	/**
	 * Prevent WordPress from adding X-Frame-Options: SAMEORIGIN so that a form can preview inside of an iframe.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function maybe_allow_form_preview_in_iframe() {
		global $pagenow;
		if ( 'admin-ajax.php' !== $pagenow ) {
			return;
		}
		if ( empty( $_GET['action'] ) || 'frm_forms_preview' !== $_GET['action'] ) {
			return;
		}
		remove_filter( 'admin_init', 'send_frame_options_header', 10 );
	}

	/**
	 * @since 1.10
	 *
	 * @param array $stylesheet
	 * @return array
	 */
	public static function force_css_to_use_action( $stylesheet ) {
		if ( self::is_requesting_form_html_via_api() ) {
			$stylesheet['formidable'] = admin_url( 'admin-ajax.php?action=frmpro_css&api=1' );
		}
		return $stylesheet;
	}

	/**
	 * Never use the pro CSS transient when the frmpro_css action is called with &api=1 set.
	 * Otherwise use_cors_safe_fonts won't get triggered.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function maybe_force_css() {
		if ( empty( $_GET['api'] ) ) {
			return;
		}

		$css = self::use_minified_scripts() ? get_transient( self::get_css_transient_key() ) : false;

		if ( is_string( $css ) ) {
			$filter = function() use ( $css ) {
				return $css;
			};
		} else {
			$filter = '__return_false';
		}

		add_filter( 'transient_frmpro_css', $filter );
	}

	/**
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function use_minified_scripts() {
		return ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG;
	}

	/**
	 * @since 1.10
	 *
	 * @return string
	 */
	private static function get_css_transient_key() {
		return 'frmapi_css_' . FrmAppHelper::plugin_version();
	}

	/**
	 * @since 1.10
	 *
	 * @param string $css
	 * @return css
	 */
	public static function use_cors_safe_fonts( $css ) {
		$api = FrmAppHelper::simple_get( 'api', 'absint' );

		if ( ! $api ) {
			// Only replace requests that include &api=1 in the URL.
			return $css;
		}

		$get_font_url    = admin_url( 'admin-ajax.php?action=frm_api_get_font' );
		$font_folder_url = FrmAppHelper::plugin_url() . '/fonts/';
		$font_version    = FrmAppHelper::$font_version;
		$css             = str_replace( $font_folder_url . 's11-fp.ttf?v=' . $font_version, $get_font_url . '&type=ttf', $css );
		$css             = str_replace( $font_folder_url . 's11-fp.woff?v=' . $font_version, $get_font_url . '&type=woff', $css );
		$css             = str_replace( $font_folder_url . 's11-fp.svg?v=' . $font_version, $get_font_url . '&type=svg', $css );

		if ( false !== strpos( $css, '/../formidable-signature/' ) ) {
			$signature_folder_url = FrmAppHelper::plugin_url() . '/../formidable-signature/assets/';
			$get_font_url        .= '&sig=1';
			$css                  = str_replace( $signature_folder_url . 'journal.eot', $get_font_url . '&type=eot', $css );
			$css                  = str_replace( $signature_folder_url . 'journal.ttf', $get_font_url . '&type=ttf', $css );
			$css                  = str_replace( $signature_folder_url . 'journal.woff', $get_font_url . '&type=woff', $css );
			$css                  = str_replace( $signature_folder_url . 'journal.svg', $get_font_url . '&type=svg', $css );
		}

		set_transient( self::get_css_transient_key(), $css, MONTH_IN_SECONDS );

		if ( self::use_minified_scripts() ) {
			header( 'Cache-Control: max-age=604800' ); // cache the CSS for a week to reduce calls to this action.
		}

		return $css;
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function load_icon_font() {
		if ( FrmAppHelper::simple_get( 'sig' ) ) {
			self::load_signature_font();
		}

		$type = FrmAppHelper::simple_get( 'type' );
		if ( ! in_array( $type, array( 'ttf', 'woff', 'svg' ), true ) ) {
			die( 0 );
		}

		self::load_font( FrmAppHelper::plugin_path() . '/fonts/', 's11-fp', $type );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function load_signature_font() {
		$type = FrmAppHelper::simple_get( 'type' );
		if ( ! in_array( $type, array( 'eot', 'ttf', 'woff', 'svg' ), true ) ) {
			die( 0 );
		}
		if ( ! is_callable( 'FrmSigAppHelper::plugin_path' ) ) {
			die( 0 );
		}
		self::load_font( FrmSigAppHelper::plugin_path() . '/assets/', 'journal', $type );
	}

	/**
	 * @since 1.10
	 *
	 * @param string $folder
	 * @param string $filename
	 * @param string $type either 'eot', 'ttf', 'woff', 'or 'svg'.
	 * @return void
	 */
	private static function load_font( $folder, $filename, $type ) {
		$file = $folder . $filename . '.' . $type;
		self::set_allow_origin_header();
		@readfile( $file );
		die();
	}

	/**
	 * Check if request is a preflight "OPTIONS" request.
	 *
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function is_preflight_request() {
		return isset( $_SERVER['REQUEST_METHOD'] ) && 'OPTIONS' === $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function is_requesting_form_html_via_api() {
		if ( ! array_key_exists( 'REQUEST_URI', $_SERVER ) ) {
			return false;
		}

		$url = wp_strip_all_tags( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		return false !== strpos( $url, '/wp-json/frm/v2/forms/' ) && false !== strpos( $url, '?return=html' );
	}

	/**
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function is_uploading_dropzone() {
		global $pagenow;
		if ( 'admin-ajax.php' !== $pagenow ) {
			return false;
		}

		if ( ! array_key_exists( 'HTTP_ACCESS_CONTROL_REQUEST_HEADERS', $_SERVER ) ) {
			return false;
		}

		$headers = sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ) );
		if ( ! $headers || ! is_string( $headers ) ) {
			return false;
		}

		$headers = strtolower( $headers );
		$headers = explode( ',', $headers );
		return in_array( 'frm-dropzone', $headers, true );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function hydrate_api_before_ajax_create() {
		if ( ! class_exists( 'FrmProFormState' ) ) {
			return;
		}

		$api = FrmProFormState::get_from_request( 'a', 0 );
		if ( $api ) {
			add_filter( 'frm_form_object', array( 'FrmAPIAppController', 'force_ajax_submit' ) );
		}
	}

	/**
	 * @since 1.10
	 *
	 * @param stdClass $form
	 * @return stdClass
	 */
	public static function force_ajax_submit( $form ) {
		$form->options['ajax_submit'] = '1';
		return $form;
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function maybe_initialize_scripts() {
		global $frm_vars;
		if ( is_callable( 'FrmSigAppController::footer_js' ) && ! empty( $frm_vars['sig_fields'] ) ) {
			FrmSigAppController::footer_js();
		}

		self::maybe_add_datepicker_i18n_scripts();
	}

	/**
	 * Load jQuery UI i18n if a datepicker is detected with a locale setting.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function maybe_add_datepicker_i18n_scripts() {
		global $frm_vars;
		if ( empty( $frm_vars['datepicker_loaded'] ) || ! is_array( $frm_vars['datepicker_loaded'] ) ) {
			return;
		}

		foreach ( $frm_vars['datepicker_loaded'] as $options ) {
			if ( ! empty( $options['locale'] ) ) {
				self::add_datepicker_i18n_scripts();
				break;
			}
		}
	}

	/**
	 * Load jQuery UI i18n for datepicker translations.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function add_datepicker_i18n_scripts() {
		if ( ! is_callable( 'FrmProAppHelper::jquery_ui_base_url' ) || ! is_callable( 'FrmAppHelper::script_version' ) ) {
			return;
		}

		$base_url          = FrmProAppHelper::jquery_ui_base_url();
		$jquery_ui_version = FrmAppHelper::script_version( 'jquery-ui-core', '1.11.4' );

		if ( version_compare( $jquery_ui_version, '1.12.0', '>=' ) ) {
			// versions 1.12.0+ do not include i18n files, so use the previous version's files.
			$base_url = str_replace( $jquery_ui_version, '1.11.4', $base_url );
		}

		wp_enqueue_script( 'jquery-ui-i18n', $base_url . '/i18n/jquery-ui-i18n.min.js', array( 'jquery-ui-core', 'jquery-ui-datepicker' ), $jquery_ui_version );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function maybe_add_scripts_before_form_preview() {
		if ( ! self::lite_is_active() ) {
			return;
		}

		if ( self::request_is_from_iframe() ) {
			self::enqueue_preview_script();
		}
	}

	/**
	 * Returns true if lite is active.
	 *
	 * @return bool
	 */
	private static function lite_is_active() {
		return is_callable( 'FrmAppHelper::plugin_version' );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function maybe_add_scripts_before_landing_page() {
		if ( ! self::lite_is_active() ) {
			return;
		}

		if ( ! self::request_is_from_iframe() ) {
			return;
		}
		$url = $_SERVER['REQUEST_URI'];
		if ( false === strpos( $url, '?iframe=1' ) ) {
			return;
		}
		global $post;
		if ( ! ( $post instanceof WP_Post ) || 'frm_landing_page' !== $post->post_type ) {
			return;
		}
		add_filter( 'show_admin_bar', '__return_false' ); // Never show admin bar inside iframe.
		self::enqueue_preview_script();
	}

	/**
	 * Maybe remove the embed transient on form save (Sync if a reCAPTCHA field is added or deleted, or if a Stripe action is added or deleted).
	 *
	 * @since 1.10
	 *
	 * @param int $id
	 * @return void
	 */
	public static function on_form_update( $id ) {
		$form_key      = FrmForm::get_key_by_id( $id );
		$transient_key = self::get_embed_transient_key( $form_key );

		$js = self::use_minified_scripts() ? get_transient( $transient_key ) : false;
		if ( ! is_string( $js ) ) {
			// No embed saved in transient, nothing to clear.
			return;
		}

		$is_using_iframe   = false === strpos( $js, '?return=html' );
		$should_use_iframe = self::should_use_iframe( $id );

		if ( $is_using_iframe === $should_use_iframe ) {
			return;
		}

		delete_transient( $transient_key );
	}

	/**
	 * Sync API CSS when the style manager is saved.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function on_css_update() {
		self::delete_css_transient();
	}

	/**
	 * Sync API CSS when the frmpro_css transient is deleted.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	public static function on_css_delete() {
		self::delete_css_transient();
	}

	/**
	 * Delete the API CSS transient.
	 *
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function delete_css_transient() {
		delete_transient( self::get_css_transient_key() );
	}

	/**
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function request_is_from_iframe() {
		if ( self::check_for_safari_exception() ) {
			return true;
		}
		return array_key_exists( 'HTTP_SEC_FETCH_DEST', $_SERVER ) && 'iframe' === $_SERVER['HTTP_SEC_FETCH_DEST'];
	}

	/**
	 * Safari does not support HTTP_SEC_FETCH_DEST so just check for the get param in the URL with a user agent check instead.
	 *
	 * @return bool
	 */
	private static function check_for_safari_exception() {
		return 1 === FrmAppHelper::simple_get( 'iframe', 'absint', 0 ) && false !== strpos( FrmAppHelper::get_server_value( 'HTTP_USER_AGENT' ), 'Safari/' );
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function enqueue_preview_script() {
		wp_enqueue_script( 'frm_api_preview', FrmAPIAppHelper::plugin_url() . '/js/api-preview.js', array(), FrmAPIAppHelper::plugin_version(), true );
	}

	/**
	 * Check if the current version of Formidable is compatible
	 * @since 1.07
	 * @return bool
	 */
	public static function is_formidable_compatible() {
		$frm_version = is_callable( 'FrmAppHelper::plugin_version' ) ? FrmAppHelper::plugin_version() : 0;
		return version_compare( $frm_version, self::$min_formidable_version, '>=' );
	}

	/**
	 * @return string
	 */
	public static function path() {
		return FrmAPIAppHelper::path();
	}

	/**
	 * @return string
	 */
	public static function folder_name() {
		return FrmAPIAppHelper::folder_name();
	}

	/**
	 * @return void
	 */
	public static function install() {
		$frmdb = new FrmAPIDb();
		$frmdb->upgrade();
	}

	/**
	 * @return void
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include( FrmAPIAppHelper::path() . '/models/FrmAPIUpdate.php' );
			FrmAPIUpdate::load_hooks();
		}
	}

	/**
	 * @return void
	 */
	public static function init() {
		self::check_for_embed();
		self::prevent_ajax_cors();
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function check_for_embed() {
		$embed = self::get_embed();
		if ( ! $embed ) {
			return;
		}

		$form_key = sanitize_key( $embed );
		$form_id  = FrmForm::get_id_by_key( $form_key );
		if ( ! $form_id ) {
			return;
		}

		$transient_key = self::get_embed_transient_key( $form_key );
		$js            = self::use_minified_scripts() ? get_transient( $transient_key ) : false;

		if ( ! is_string( $js ) ) {
			$js = self::should_use_iframe( $form_id ) ? self::get_iframe_js( $form_id, $form_key ) : self::get_embedded_js_file_content( $form_id );
			$js = preg_replace( '/\[frm_api_embed_src\]/', esc_js( FrmAPIAppHelper::get_embed_url( $form_key ) ), $js, 1 );
			set_transient( $transient_key, $js, MONTH_IN_SECONDS );
		}

		header( 'Content-Type: text/javascript' );
		echo $js;
		die();
	}

	/**
	 * @since 1.10
	 *
	 * @param string $string
	 * @return string
	 */
	private static function replace_base_url( $string ) {
		return preg_replace( '/\[frm_api_base_url\]/', esc_js( home_url() ), $string, 1 );
	}

	/**
	 * @since 1.10
	 *
	 * @param string $string
	 * @param string $form_key
	 * @return string
	 */
	private static function replace_form_key( $string, $form_key ) {
		return preg_replace( '/\[frm_api_form_key\]/', $form_key, $string, 1 );
	}

	/**
	 * @since 1.10
	 *
	 * @param string $form_key
	 * @return string
	 */
	private static function get_embed_transient_key( $form_key ) {
		return 'frmapi_embed_' . $form_key . '_' . FrmAPIAppHelper::plugin_version();
	}

	/**
	 * reCAPTCHA and Stripe require iframes to work so force an iframe.
	 *
	 * @since 1.10
	 *
	 * @param int $form_id
	 * @return bool
	 */
	private static function should_use_iframe( $form_id ) {
		if ( ! get_option( 'permalink_structure' ) ) {
			/**
			 * Plain permalinks always use an iframe.
			 * Plugins do not get loaded when sending a preflight request to the rest API.
			 * Because of this the ?rest_route syntax is always prevented by CORS blocking.
			 */
			return true;
		}

		$captcha_fields = FrmField::get_all_types_in_form( $form_id, 'captcha' );
		if ( $captcha_fields ) {
			/**
			 * reCAPTCHA keys are tied to URLs so we need to force an iframe.
			 * This way the URL of the server loading the form never changes.
			 */
			return true;
		}

		if ( self::has_payment_actions( $form_id ) ) {
			/**
			 * If Stripe is loaded without an iframe the following error gets logged in the JavaScript console:
			 * Stripe.js requires 'allow-same-origin' if sandboxed.
			 */
			return true;
		}

		return false;
	}

	/**
	 * @since 1.10
	 *
	 * @param int $form_id
	 * @return bool
	 */
	private static function has_payment_actions( $form_id ) {
		$payment_actions = FrmFormAction::get_action_for_form( $form_id, 'payment' );
		return ! ! $payment_actions;
	}

	/**
	 * @since 1.10
	 *
	 * @param int    $form_id
	 * @param string $form_key
	 * @return string
	 */
	private static function get_iframe_js( $form_id, $form_key ) {
		$folder = FrmAPIAppHelper::path() . '/js/';

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$use_file = $folder . 'iframe-embed.js';
		}

		if ( ! isset( $use_file ) || ! is_readable( $use_file ) ) {
			$use_file = $folder . 'iframe-embed.min.js';
		}

		$js = file_get_contents( $use_file );

		$url = self::maybe_get_landing_page_url( $form_id );
		if ( false === $url ) {
			$url = '[frm_api_base_url]/wp-admin/admin-ajax.php?action=frm_forms_preview&form=[frm_api_form_key]';
			$url = self::replace_base_url( $url );
			$url = self::replace_form_key( $url, $form_key );
		}

		$js = preg_replace( '/\[frm_api_iframe_embed_url\]/', $url, $js, 1 );

		return $js;
	}

	/**
	 * Use the landing page URL by default for an iframe URL if one is set.
	 *
	 * @since 1.15
	 *
	 * @param int $form_id
	 * @return string|false
	 */
	private static function maybe_get_landing_page_url( $form_id ) {
		if ( ! is_callable( 'FrmLandingSettingsController::get_landing_page_post_name' ) ) {
			return false;
		}

		$use_landing_page_url = true;

		/**
		 * By default iframes will use the landing page URL when one is defined.
		 * You can use __return_false with this filter to use the preview URL instead.
		 *
		 * @since 1.15
		 *
		 * @param bool $use_landing_page_url
		 * @param int  $form_id
		 */
		$use_landing_page_url = (bool) apply_filters( 'frm_api_iframe_use_landing_page_url', $use_landing_page_url, $form_id );
		if ( ! $use_landing_page_url ) {
			return false;
		}

		$landing_page_post_name = FrmLandingSettingsController::get_landing_page_post_name( $form_id );
		if ( ! $landing_page_post_name ) {
			return false;
		}

		return esc_js( home_url() . '/' . $landing_page_post_name ) . '?iframe=1';
	}

	/**
	 * @since 1.10
	 *
	 * @param int $form_id
	 * @return string
	 */
	private static function get_embedded_js_file_content( $form_id ) {
		$folder = FrmAPIAppHelper::path() . '/js/';
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$use_file = $folder . 'embed.js';
		}
		if ( ! isset( $use_file ) || ! is_readable( $use_file ) ) {
			$use_file = $folder . 'embed.min.js';
		}
		$js = file_get_contents( $use_file );
		$js = preg_replace( '/\[frm_api_form_id\]/', absint( $form_id ), $js, 1 );
		$js = self::replace_base_url( $js );
		return $js;
	}

	/**
	 * Check the URL for /frm_embed/ ?frm_embed= pattern.
	 *
	 * @since 1.10
	 *
	 * @return string|false
	 */
	private static function get_embed() {
		if ( ! class_exists( 'FrmAppHelper' ) ) {
			return false;
		}

		$uri      = FrmAppHelper::get_server_value( 'REQUEST_URI' );
		$pattern  = FrmAPIAppHelper::embed_protocol();
		$position = strpos( $uri, $pattern );

		if ( $position === false ) {
			return false;
		}

		return substr( $uri, $position + strlen( $pattern ) );
	}

	/**
	 * @return void
	 */
	public static function prevent_ajax_cors() {
		$doing_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;
		if ( ! $doing_ajax || headers_sent() ) {
			return;
		}

		if ( ! class_exists( 'FrmAppHelper' ) ) {
			return;
		}

		$action = FrmAppHelper::get_post_param( 'action', '', 'sanitize_text_field' );
		if ( strpos( $action, 'frm' ) === 0 ) {
			global $wp_filter;
			if ( 'frm_entries_create' === $action || isset( $wp_filter[ 'wp_ajax_nopriv_' . $action ] ) ) {
				self::set_allow_origin_header();
			}
		}
	}

	/**
	 * @return void
	 */
	public static function create_initial_rest_routes() {
		if ( ! self::is_formidable_compatible() ) {
			return;
		}

		add_filter( 'determine_current_user', 'FrmAPIAppController::set_current_user', 40 );
		add_filter( 'rest_authentication_errors', 'FrmAPIAppController::check_authentication', 50 );
		self::force_reauthentication();

		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			include_once( FrmAPIAppHelper::path() . '/controllers/FrmAPITempController.php' );
		}

		$controller = new FrmAPIFieldsController();
		$controller->register_routes();

		$controller = new FrmAPIFormsController();
		$controller->register_routes();

		$controller = new FrmAPIEntriesController();
		$controller->register_routes();

		$controller = new FrmAPIStatsController();
		$controller->register_routes();

		if ( class_exists( 'WP_REST_Posts_Controller' ) && self::views_is_installed() ) {
			$controller = new FrmAPIViewsController( 'frm_display' );
			$controller->register_routes();
		}
	}

	/**
	 * Determine if views is installed before registering view routes.
	 *
	 * @return bool
	 */
	private static function views_is_installed() {
		return post_type_exists( 'frm_display' );
	}

	/**
	 * Force reauthentication after we've registered our handler
	 *
	 * @return void
	 */
	public static function force_reauthentication() {
		if ( is_user_logged_in() ) {
			// Another handler has already worked successfully, no need to reauthenticate.
			return;
		}

		// Force reauthentication
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			$user_id = apply_filters( 'determine_current_user', false );
			if ( $user_id ) {
				wp_set_current_user( $user_id );
			}
		}
	}

	public static function set_current_user( $user_id ) {
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		global $frm_api_error;

		if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
			/*
			* php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
			* For this workaround to work, add this line to your .htaccess file:
			* RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
			*/

			if ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) && ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
				$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			}

			if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && strlen( $_SERVER['HTTP_AUTHORIZATION'] ) > 0 ) {
				$exploded = explode( ':', base64_decode( substr( $_SERVER['HTTP_AUTHORIZATION'], 6 ) ) );
				if ( 2 === count( $exploded ) ) {
					list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = $exploded;
					if ( strlen( $_SERVER['PHP_AUTH_USER'] ) == 0 || strlen( $_SERVER['PHP_AUTH_PW'] ) == 0 ) {
						unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
					}
				}
			}

			if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				// $frm_api_error = array( 'code' => 'frm_missing_api', 'message' => __('You are missing an API key', 'frmapi') );
				return $user_id;
			}
		}

		// check if using api key
		$api_key = get_option( 'frm_api_key' );
		$check_key = $_SERVER['PHP_AUTH_USER'];

		if ( $api_key != $check_key ) {
			$frm_api_error = array( 'code' => 'frm_incorrect_api', 'message'  => __( 'Your API key is incorrect', 'frmapi' ) );
			return $user_id;
		}

		$admins = new WP_User_Query( array( 'role' => 'Administrator', 'number' => 1, 'fields' => 'ID' ) );
		if ( empty( $admins ) ) {
			$frm_api_error = array( 'code' => 'frm_missing_admin', 'message' => __( 'You do not have an administrator on this site', 'frmapi' ) );
			return $user_id;
		}

		$user_ids = $admins->results;
		$user_id = reset( $user_ids );

		$frm_api_error = 'success';

		return $user_id;
	}

	public static function check_authentication( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		// only return error if this is an frm route
		if ( ! FrmAPIAppHelper::is_frm_route() ) {
			return $result;
		}

		global $frm_api_error;
		if ( $frm_api_error && is_array( $frm_api_error ) ) {
			return new WP_Error( $frm_api_error['code'], $frm_api_error['message'], array( 'status' => 403 ) );
		}

		if ( 'success' == $frm_api_error || is_user_logged_in() ) {
			return true;
		}

		return $result;
	}

	/**
	 * @param array  $atts The shortcode parameters.
	 * @param string $atts[id] The form ID.
	 * @param string $atts[url] The URL of the API source.
	 * @param string $atts[exclude_script] A comma separated list of extra scripts to exclude from the response.
	 * @param string $atts[exclude_style] A comma separated list of styles to exclude from the response.
	 *
	 * @return string
	 */
	public static function show_api_object( $atts ) {
		if ( ! isset( $atts['id'] ) || ! isset( $atts['url'] ) ) {
			return __( 'Please include id=# and url="yoururl.com" in your shortcode', 'frmapi' );
		}
		$atts['id'] = sanitize_title( $atts['id'] );
		$atts['type'] = sanitize_title( isset( $atts['type'] ) ? $atts['type'] : 'form' ) . 's';

		$container_id = 'frmapi-' . $atts['id'] . rand( 1000, 9999 );
		$url = trailingslashit( $atts['url'] ) . 'wp-json/frm/v2/' . $atts['type'] . '/' . $atts['id'];

		self::add_excluded_scripts( $atts );

		$get_params = $atts;
		if ( isset( $get_params['get'] ) ) {
			$pass_params = explode( ',', $get_params['get'] );
			foreach ( $pass_params as $pass_param ) {
				if ( isset( $_GET[ $pass_param ] ) ) {
					$get_params[ $pass_param ] = sanitize_text_field( $_GET[ $pass_param ] );
				}
			}
			unset( $get_params['get'] );
		}
		unset( $get_params['id'], $get_params['type'], $get_params['url'] );

		if ( $atts['type'] == 'forms' ) {
			$get_params['return'] = 'html';
		} else {
			$pass_params = array( 'frm-page-' . $atts['id'], 'frmcal-month', 'frmcal-year' );
			foreach ( $pass_params as $pass_param ) {
				$url_value = filter_input( INPUT_GET, $pass_param );
				if ( ! empty( $url_value ) ) {
					$get_params[ $pass_param ] = sanitize_text_field( $url_value );
				}
			}
		}

		if ( ! empty( $get_params ) ) {
			$url .= '?' . http_build_query( $get_params );
		}

		$form = '<div id="' . esc_attr( $container_id ) . '" class="frmapi-form" data-url="' . esc_url( $url ) . '"></div>';
		add_action( 'wp_footer', array( __CLASS__, 'load_form_scripts' ) );

		self::add_form_to_forms_loaded_global( $atts['id'] );

		return $form;
	}

	/**
	 * @param int $form_id
	 * @return void
	 */
	private static function add_form_to_forms_loaded_global( $form_id ) {
		if ( ! self::lite_is_active() ) {
			return;
		}

		global $frm_vars;
		$form = FrmForm::getOne( $form_id );

		if ( ! $form ) {
			return;
		}

		$small_form                 = new stdClass();
		$small_form->id             = $form->id;
		$small_form->form_key       = $form->form_key;
		$small_form->name           = $form->name;
		$frm_vars['forms_loaded'][] = $small_form;
	}

	/**
	 * Prevent duplicate scripts by letting the API know which scripts
	 * should not be included.
	 *
	 * @param array $atts
	 *
	 * @return void
	 */
	private static function add_excluded_scripts( &$atts ) {
		global $wp_scripts;

		$loaded = array_merge( $wp_scripts->done, $wp_scripts->queue );
		$loaded = implode( ',', $loaded );

		if ( ! isset( $atts['exclude_script'] ) ) {
			$atts['exclude_script'] = '';
		}

		if ( ! empty( $atts['exclude_script'] ) ) {
			$atts['exclude_script'] .= ',';
		}

		$atts['exclude_script'] .= $loaded;
	}

	/**
	 * Include the scripts for forms and views, and prevent duplicates.
	 *
	 * @since 1.08
	 *
	 * @param array $request The shortcode attributes.
	 *
	 * @return void
	 */
	public static function include_scripts( $request ) {
		global $frm_vars;

		$frm_vars['footer_loaded'] = true;

		self::exclude_scripts( $request['exclude_script'] );
		self::exclude_styles( $request['exclude_style'] );
	}

	/**
	 * Prevent scripts from being loaded a second time on the page.
	 *
	 * @since 1.08
	 *
	 * @param string $scripts A comma-separated list of script names.
	 *
	 * @return void
	 */
	private static function exclude_scripts( $scripts ) {
		if ( empty( $scripts ) ) {
			return;
		}

		global $wp_scripts;

		$scripts = explode( ',', $scripts );
		if ( in_array( 'jquery', $scripts ) ) {
			$scripts[] = 'jquery-core';
			$scripts[] = 'jquery-migrate';
		}

		$wp_scripts->done = array_merge( $wp_scripts->done, $scripts );
	}

	/**
	 * Prevent scripts from being loaded a second time on the page.
	 *
	 * @since 1.08
	 *
	 * @param string $styles A comma-separated list of style names.
	 * @return void
	 */
	private static function exclude_styles( $styles ) {
		if ( empty( $styles ) ) {
			return;
		}

		global $wp_styles;

		$styles          = explode( ',', $styles );
		$wp_styles->done = array_merge( $wp_styles->done, $styles );
	}

	/**
	 * @return void
	 */
	public static function load_form_scripts() {
		$script = "var frmApiBackupAjaxUrl;
document.addEventListener('DOMContentLoaded', function(){
var frmapi=document.getElementsByClassName('frmapi-form');
if(frmapi.length) {
	for(var frmi=0,frmlen=frmapi.length;frmi<frmlen;frmi++){
		frmapiGetData(frmapi[frmi]);
	}
	if ('frm_js' in window) {
		frmApiBackupAjaxUrl = window.frm_js.ajax_url;
	}
}

function frmapiGetData(frmcont){
	var xhr = new XMLHttpRequest();
	xhr.open( 'get',frmcont.getAttribute('data-url'),true );
	xhr.onreadystatechange = handleReadyStateChange;
	xhr.send();
	function handleReadyStateChange() {
		var response;

		if (!requestIsSuccessful()) {
			return;
		}

		response = JSON.parse(xhr.responseText);

		frmcont.innerHTML = response.renderedHtml;
		if('__frmHideOrShowFields' in window) {
			frmProForm.hideOrShowFields(__frmHideOrShowFields, 'pageLoad');
		}
		if ('undefined' !== typeof frmApiBackupAjaxUrl && 'frm_js' in window) {
			window.frm_js.ajax_url = frmApiBackupAjaxUrl;
		}
	}
	function requestIsSuccessful() {return xhr.readyState > 3 && xhr.status == 200 && xhr.responseText !== '';}
}
});";
		$script = str_replace( array( "\r\n", "\r", "\n", "\t", '' ), '', $script );
		echo '<script type="text/javascript">' . $script . '</script>';
	}

	/**
	 * @return void
	 */
	public static function send_webhooks( $entry, $hook, $type = 'live' ) {
		if ( ! is_object( $entry ) ) {
			$entry = FrmEntry::getOne( $entry );
		}

		add_filter( 'frm_use_wpautop', '__return_false' );

		$args = self::prepare_args( $entry, $hook, $type );
		$response = self::send_request( $args );
		$processed = self::process_response( $response );

		$log_args = array(
			'url' => $args['url'],
			'body' => $args['body'],
			'headers' => $args['headers'],
			'processed' => $processed,
			'entry' => $entry,
			'hook' => $hook,
			'response' => $response,
		);
		self::log_results( $log_args );
		do_action(
			'frmapi_post_response',
			$response,
			$entry,
			$hook,
			array(
				'processed' => $processed,
				'request' => $args['body'],
				'url' => $args['url'],
			)
		);

		add_filter( 'frm_use_wpautop', '__return_true' );
	}

	/**
	 * @param stdClass[] $atts
	 *
	 * @psalm-param array{hook: stdClass, entry: stdClass} $atts
	 */
	private static function get_body( $atts ) {
		$body = trim( $atts['hook']->post_content['data_format'] );
		$format = $atts['hook']->post_content['format'];

		if ( empty( $body ) && 'raw' != $format ) {
			self::get_body_settings( $atts, $body );
		} elseif ( strpos( $body, '{' ) === 0 ) {
			// allow for non-json formats
			$body = FrmAppHelper::maybe_json_decode( $body );
		}

		return $body;
	}

	/**
	 * @param string $body
	 *
	 * @psalm-param ''|'0' $body
	 *
	 * @return void
	 */
	private static function get_body_settings( $atts, &$body ) {
		$body = $atts['hook']->post_content['data_fields'];
		$has_data = ( count( $body ) > 1 || $body[0]['key'] != '' );
		if ( $has_data ) {
			self::prepare_data( $body );
		} else {
			$body = self::get_entries_array( array( $atts['entry']->id ) );
		}
	}

	/**
	 * @return void
	 */
	private static function prepare_data( &$body ) {
		$values = array();
		foreach ( $body as $value ) {
			if ( strpos( $value['key'], '|' ) ) {
				$keys = explode( '|', $value['key'] );
				self::unflatten_array( $keys, $value['value'], $values );
			} else {
				$values[ $value['key'] ] = $value['value'];
			}
		}
		$body = $values;
	}

	/**
	 * Turn piped key into nested array
	 * fields|name => array( 'fields' => array( 'name' => '' ) )
	 *
	 * @param string[] $keys
	 * @param array    $unflattened
	 *
	 * @return void
	 */
	private static function unflatten_array( $keys, $value, &$unflattened ) {
		$name = $keys;
		$name = reset( $name );

		if ( count( $keys ) == 1 ) {
			$unflattened[ $name ] = $value;
		} else {
			if ( ! isset( $unflattened[ $name ] ) ) {
				$unflattened[ $name ] = array();
			}

			$pos = array_search( $name, $keys );
			unset( $keys[ $pos ] );
			if ( ! empty( $keys ) ) {
				self::unflatten_array( $keys, $value, $unflattened[ $name ] );
			}
		}
	}

	/**
	 * @param string|array $body
	 * @param array        $atts
	 *
	 * @return void
	 */
	private static function encode_data( &$body, $atts ) {
		if ( 'form' === $atts['format'] ) {
			$body = self::filter_shortcodes( $body, $atts );
		}

		if ( $atts['method'] === 'GET' ) {
			// GET requests are handled differently.
			self::encode_get_data( $body, $atts );
			return;
		}

		// Handle other methods that are not GET.
		if ( 'form' === $atts['format'] ) {
			$body = http_build_query( $body );
			return;
		}

		if ( is_array( $body ) ) {
			$body = self::filter_shortcodes( $body, $atts );
			/**
			 * @psalm-suppress ReferenceConstraintViolation
			 */
			$body = wp_json_encode( $body );
		} else {
			self::filter_shortcodes_in_json( $body, $atts['entry'] );
		}
	}

	/**
	 * GET requests have special requirements. $body should always be an array.
	 *
	 * @since 1.17
	 *
	 * @param string|array $body
	 * @param array        $atts
	 * @return void
	 */
	private static function encode_get_data( &$body, $atts ) {
		if ( 'form' === $atts['format'] ) {
			return;
		}

		if ( is_array( $body ) ) {
			$body = self::filter_shortcodes( $body, $atts );
			return;
		}

		/**
		 * GET request body is expected to be array to be parsed with wp_remote_post(). You can refer to WpOrg\Requests\Requests->set_defaults
		 * and WpOrg\Requests\Transport->setup_handle to see the relevant function calls.
		 */
		$body = array();
	}

	/**
	 * @since 1.03
	 *
	 * @param mixed $value
	 * @param array $atts
	 *
	 * @return mixed
	 */
	private static function filter_shortcodes( $value, $atts ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $single_value ) {
				$value[ $key ] = self::filter_shortcodes( $single_value, $atts );
			}
		} else {
			if ( strpos( $value, '[' ) === false ) {
				return $value;
			}

			$value = apply_filters( 'frm_content', $value, $atts['entry']->form_id, $atts['entry'] );
			$value = do_shortcode( $value );
		}

		self::maybe_replace_non_standard_formidable_shortcodes( $value );
		return $value;
	}

	private static function maybe_replace_non_standard_formidable_shortcodes( &$value ) {
		if ( ! is_string( $value ) ) {
			return;
		}

		self::replace_user_id_shortcode( $value );

		self::replace_date_shortcode( $value );
	}

	/**
	 * @since 1.14
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	private static function replace_user_id_shortcode( &$value ) {
		preg_match_all( '/\[(user_id)\b(.*?)(?:(\/))?\]/s', $value, $matches, PREG_PATTERN_ORDER );
		if ( empty( $matches[0] ) ) {
			return;
		}
		$value = FrmProAppHelper::get_user_id();
	}

	/**
	 * @since 1.14
	 *
	 * @param string $value
	 *
	 * @return void
	 */
	private static function replace_date_shortcode( &$value ) {
		preg_match_all( '/\[(date)\b(.*?)(?:(\/))?\]/s', $value, $matches, PREG_PATTERN_ORDER );

		if ( empty( $matches[0] ) ) {
			return;
		}

		if ( ! is_callable( array( 'FrmShortcodeHelper', 'get_shortcode_attribute_array' ) ) ) {
			return;
		}

		$atts   = FrmShortcodeHelper::get_shortcode_attribute_array( stripslashes( $matches[2][0] ) );
		$date   = isset( $atts['offset'] ) ? $atts['offset'] : current_time( 'mysql' );
		$format = isset( $atts['format'] ) ? $atts['format'] : '';

		if ( is_callable( array( 'FrmProFieldsHelper', 'get_single_date' ) ) ) {
			$value  = FrmProFieldsHelper::get_single_date( $date, $format );
		}
	}

	/**
	 * @return void
	 */
	private static function filter_shortcodes_in_json( &$value, $entry ) {
		if ( strpos( $value, '[' ) === false ) {
			return;
		}

		add_filter( 'frmpro_fields_replace_shortcodes', 'FrmAPIAppController::replace_double_quotes', 99, 4 );

		$value = str_replace( '[\/', '[/', $value ); // allow end shortcodes to be processed
		$value = apply_filters( 'frm_content', $value, $entry->form_id, $entry );
		$value = do_shortcode( $value );
		$value = str_replace( '[/', '[\/', $value ); // if the end shortcodes are still present, escape them
		$value = str_replace( ' & ', ' %26 ', $value ); // escape &

		// Remove surrounding quotes from JSON arrays
		$value = str_replace( array( '"[{', '}]"' ), array( '[{', '}]' ), $value );

		remove_filter( 'frmpro_fields_replace_shortcodes', 'FrmAPIAppController::replace_double_quotes' );
	}

	/**
	 * Replace double quotes with single quotes to keep valid JSON
	 *
	 * @since 1.0rc4
	 * @param mixed $value
	 * @param string $tag
	 * @param array $atts
	 * @param object $field
	 * @return mixed
	 */
	public static function replace_double_quotes( $value, $tag, $atts, $field ) {
		if ( is_string( $value ) ) {
			$value = str_replace( '"', '\'', $value );

			// Double encode line breaks in paragraph fields
			$value = str_replace( "\r\n", "\\r\\n", $value );
		}

		return $value;
	}

	/**
	 * Prepare the arguments for an API request
	 *
	 * @since 1.03
	 *
	 * @param stdClass $entry
	 * @param stdClass $hook
	 * @param string $type
	 *
	 * @return array
	 */
	private static function prepare_args( $entry, $hook, $type ) {
		$body = self::get_body( compact( 'hook', 'entry' ) );
		self::encode_data( $body, array( 'format' => $hook->post_content['format'], 'entry' => $entry, 'method' => $hook->post_content['method'] ) );

		// Prepare headers
		$charset = '';
		if ( isset( $hook->post_content['charset'] ) ) {
			$charset = $hook->post_content['charset'];
		}
		$headers = array(
			'Content-type' => FrmAPIAppHelper::content_type_header( $hook->post_content['format'], $charset ),
		);
		if ( $type == 'test' ) {
			$headers['X-Hook-Test'] = 'true';
		}
		if ( ! empty( $hook->post_content['api_key'] ) ) {
			$api_key = self::prepare_basic_auth_key( $hook->post_content['api_key'] );
			$headers['Authorization'] = 'Basic ' . base64_encode( $api_key );
		}

		$url = self::filter_shortcodes( $hook->post_content['url'], compact( 'entry' ) );
		$method = empty( $hook->post_content['method'] ) ? 'POST' : $hook->post_content['method'];

		$args = array(
			'url' => $url,
			'headers' => $headers,
			'body' => $body,
			'method' => $method,
			'timeout'   => self::$timeout,
		);

		// Second argument is for reverse compatibility
		return apply_filters( 'frm_api_request_args', $args, $args );
	}

	/**
	 * @param array $args
	 */
	public static function send_request( $args ) {
		if ( ! isset( $args['url'] ) ) {
			return false;
		}

		$url = esc_url_raw( trim( $args['url'] ) );
		unset( $args['url'] );

		return wp_remote_post( $url, $args );
	}

	/**
	 * @return (mixed|string)[]
	 *
	 * @psalm-return array{message: mixed|string, code: 'FAIL'|mixed}
	 */
	private static function process_response( $response ) {
		$body = wp_remote_retrieve_body( $response );
		$processed = array( 'message' => '', 'code' => 'FAIL' );
		if ( is_wp_error( $response ) ) {
			$processed['message'] = $response->get_error_message();
		} elseif ( $body == 'error' || is_wp_error( $body ) ) {
			$processed['message'] = __( 'You had an HTTP connection error', 'frmapi' );
		} elseif ( isset( $response['response'] ) && isset( $response['response']['code'] ) ) {
			$processed['code'] = $response['response']['code'];
			$processed['message'] = $response['body'];
		}

		return $processed;
	}

	/**
	 * @param array $atts
	 *
	 * @return void
	 */
	public static function log_results( $atts ) {
		if ( ! class_exists( 'FrmLog' ) ) {
			return;
		}

		$content = $atts['processed'];
		$message = isset( $content['message'] ) ? $content['message'] : '';

		$headers = '';
		self::array_to_list( $atts['headers'], $headers );

		$log = new FrmLog();
		$log->add(
			array(
				'title'   => __( 'API:', 'frmapi' ) . ' ' . $atts['hook']->post_title,
				'content' => (array) $atts['response'],
				'fields'  => array(
					'entry'   => $atts['entry']->id,
					'action'  => $atts['hook']->ID,
					'code'    => isset( $content['code'] ) ? $content['code'] : '',
					'message' => $message,
					'url'     => $atts['url'],
					'request' => $atts['body'],
					'headers' => $headers,
				),
			)
		);
	}

	/**
	 * @param string $list
	 *
	 * @return void
	 */
	private static function array_to_list( $array, &$list ) {
		foreach ( $array as $k => $v ) {
			$list .= "\r\n" . $k . ': ' . $v;
		}
	}

	/**
	 * @param array $ids
	 *
	 * @return array[]
	 */
	public static function get_entries_array( $ids ) {
		global $wpdb;

		$entry_array = array();

		// fetch 20 posts at a time rather than loading the entire table into memory
		while ( $next_set = array_splice( $ids, 0, 20 ) ) {
			$where = 'WHERE id IN (' . join( ',', $next_set ) . ')';
			$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}frm_items $where" );
			unset( $where );

			foreach ( $entries as $entry ) {
				$meta = FrmEntriesController::show_entry_shortcode(
					array(
						'format' => 'array',
						'include_blank' => true,
						'id' => $entry->id,
						'user_info' => false, // 'entry' => $entry
					)
				);

				$entry_array[] = array_merge( (array) $entry, $meta );

				unset( $entry );
			}
		}

		return $entry_array;
	}

	/**
	 * @return string
	 */
	public static function prepare_basic_auth_key( $api_key ) {
		$api_key = trim( $api_key );
		if ( ! empty( $api_key ) ) {
			$api_key = ( strpos( $api_key, ':' ) === false ) ? $api_key . ':x' : $api_key;
		}
		return $api_key;
	}

	/**
	 * @since 1.09
	 *
	 * @deprecated 1.10
	 *
	 * @return void
	 */
	public static function load_formidable_script_fix() {
		_deprecated_function( __METHOD__, '1.10', 'No longer required' );
	}
}

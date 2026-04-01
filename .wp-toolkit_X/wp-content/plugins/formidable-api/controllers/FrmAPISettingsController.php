<?php
if ( ! defined( 'ABSPATH' )) die( 'You are not allowed to call this page directly.' );

class FrmAPISettingsController {

	/**
	 * @return void
	 */
	public static function load_hooks() {
		if ( is_admin() ) {
			add_action( 'frm_add_settings_section', 'FrmAPISettingsController::add_settings_section' );

			add_action( 'wp_ajax_frmapi_insert_json', 'FrmAPISettingsController::default_json' );
			add_action( 'wp_ajax_frmapi_test_connection', 'FrmAPISettingsController::test_connection' );
			add_action( 'wp_ajax_frm_api_settings_ajax', 'FrmAPISettingsController::ajax_requests' );

			add_filter( 'frm_before_save_api_action', 'FrmAPISettingsController::clear_raw_before_save' );
		}

		add_action( 'frm_registered_form_actions', 'FrmAPISettingsController::register_actions' );
		add_action( 'frm_trigger_api_action', 'FrmAPISettingsController::trigger_api', 10, 3 );
	}

	public static function register_actions( $actions ) {
		self::enqueue_admin_js();
		$actions['api'] = 'FrmAPIAction';

		include_once FrmAPIAppHelper::path() . '/models/FrmAPIAction.php';

		return $actions;
	}

	/**
	 * Enqueue the JS for the settings page
	 *
	 * @since 1.02
	 *
	 * @return void
	 */
	private static function enqueue_admin_js() {
		if ( FrmAppHelper::is_admin_page( 'formidable-settings' ) || self::is_form_settings_page() ) {
			self::enqueue_js( 'frmapi_admin', 'frmapi-admin', array( 'jquery', 'wp-i18n', 'formidable_dom' ) );
		}

		if ( self::should_include_embed_form_script() ) {
			self::enqueue_embed_form_js();
		}
	}

	/**
	 * @since 1.10
	 *
	 * @return void
	 */
	private static function enqueue_embed_form_js() {
		self::enqueue_js( 'frm_api_embed_form', 'embed-form', array( 'wp-i18n' ) );
		$embed_js = array(
			'protocol' => FrmAPIAppHelper::embed_protocol(),
		);
		wp_localize_script( 'frm_api_embed_form', 'frmApiEmbedJs', $embed_js );
	}

	/**
	 * Enqueue a local JS file in the /js/ folder.
	 *
	 * @since 1.10
	 *
	 * @param string $handle
	 * @param string $file
	 * @param array  $dependencies
	 *
	 * @return void
	 */
	private static function enqueue_js( $handle, $file, $dependencies ) {
		$url     = FrmAPIAppHelper::plugin_url() . '/js/' . $file . '.js';
		$version = FrmAPIAppHelper::plugin_version();
		wp_enqueue_script( $handle, $url, $dependencies, $version );
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @since 1.02
	 *
	 * @return bool
	 */
	private static function is_form_settings_page() {
		$page   = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		return $page === 'formidable' && $action === 'settings';
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @since 1.10
	 *
	 * @return bool
	 */
	private static function should_include_embed_form_script() {
		$include = false;
		if ( 'formidable' === FrmAppHelper::simple_get( 'page', 'sanitize_title' ) ) {
			$action  = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
			$include = ! $action || in_array( $action, array( 'edit', 'duplicate', 'list', 'trash', 'settings' ), true );
		}

		/**
		 * Filter the value of $include so other pages can include the embed form script.
		 *
		 * @since 1.12
		 *
		 * @param bool $include
		 */
		return apply_filters( 'frm_api_include_embed_form_script', $include );
	}

	public static function add_settings_section( $sections ) {
		if ( ! isset( $sections['api'] ) ) {
			$sections['api'] = array(
				'class'    => 'FrmAPISettingsController',
				'function' => 'show_api_key',
				'name'     => 'API',
				'icon'     => 'frm_feed_icon frm_icon_font',
			);
		}
		return $sections;
	}

	/**
	 * @return void
	 */
	public static function show_api_key() {
		$api_key         = self::get_api_key();
		$refresh_message = __( 'Existing uses of the old API key will no longer work and <b style="color:var(--error-500);">this action is irreversible</b>.', 'frmapi' );

		if ( is_callable( 'FrmZapAppController::plugin_url' ) ) {
			$refresh_message .= ' ' . __( 'This will also break existing Zapier integrations', 'frmapi' );
		}
		require_once( FrmAPIAppHelper::path() . '/views/settings/api_key.php' );
	}

	private static function get_api_key() {
		$api_key = get_option( 'frm_api_key' );
		if ( ! $api_key ) {
			$api_key = self::generate_new_api_key();
		}
		return $api_key;
	}

	/**
	 * Generate a new API KEY
	 *
	 * @since 1.17
	 *
	 * @return string
	 */
	private static function generate_new_api_key() {
		$api_key = FrmAPIAppHelper::generate( 4, 4 );
		update_option( 'frm_api_key', $api_key );
		return $api_key;
	}

	/**
	 * @return void
	 */
	public static function default_json() {
		$form_id = absint( $_POST['form_id'] );

		$entry = array(
			'item_id' => '[id]',
			'key'     => '[key]',
			'form_id' => $form_id,
			// 'updated_by' => '[updated-by]',
			'post_id' => '[post_id]',
			'created_at' => '[created_at format=\'Y-m-d H:i:s\']',
			'updated_at' => '[updated_at format=\'Y-m-d H:i:s\']',
			'is_draft' => '[is_draft]',
		);
		$meta = FrmEntriesController::show_entry_shortcode(
			array(
				'format' => 'array',
				'user_info' => false,
				'default_email' => true,
				'form_id' => $form_id,
			)
		);

		$entry_array = $entry + (array) $meta;

		if ( version_compare( phpversion(), '5.4', '>=' ) ) {
			echo json_encode( $entry_array, JSON_PRETTY_PRINT );
		} else {
			echo json_encode( $entry_array, true );
		}
		wp_die();
	}

	/**
	 * @return void
	 */
	public static function test_connection() {
		check_ajax_referer( 'frm_ajax', 'nonce' );
		FrmAppHelper::permission_check( 'frm_edit_forms' );

		$url     = FrmAppHelper::get_param( 'url', '', 'post', 'sanitize_text_field' );
		$api_key = FrmAppHelper::get_param( 'key', '', 'post', 'sanitize_text_field' );
		$format  = FrmAppHelper::get_param( 'format', '', 'post', 'sanitize_text_field' );
		$charset = FrmAppHelper::get_param( 'charset', '', 'post', 'sanitize_text_field' );

		$headers = array(
			'X-Hook-Test' => 'true',
		);

		if ( $format ) {
			$headers['Content-Type'] = FrmAPIAppHelper::content_type_header( $format, $charset );
		}

		if ( $api_key ) {
			$api_key                  = FrmAPIAppController::prepare_basic_auth_key( $api_key );
			$headers['Authorization'] = 'Basic ' . base64_encode( $api_key );
		}

		$body = json_encode( array( 'test' => true ) );

		$arg_array = array(
			'body'    => $body,
			'timeout' => FrmAPIAppController::$timeout,
			'headers' => $headers,
			'url'     => $url,
		);

		// Second argument is for reverse compatibility
		$arg_array = apply_filters( 'frm_api_request_args', $arg_array, $arg_array );

		$resp = FrmAPIAppController::send_request( $arg_array );
		$body = wp_remote_retrieve_body( $resp );

		self::check_for_wp_error( $resp, $body );
		$response_body = json_decode( $body, true );
		self::check_response_body( $response_body );
		self::check_response_headers( $resp, $response_body );

		wp_die();
	}

	/**
	 * @since 1.08
	 *
	 * @param mixed $resp
	 * @param string $body
	 *
	 * @return void
	 */
	private static function check_for_wp_error( $resp, $body ) {
		if ( ! is_wp_error( $resp ) && $body != 'error' ) {
			return;
		}

		$message = __( 'You had an error communicating with that API.', 'frmapi' );
		if ( is_wp_error( $resp ) ) {
			$message .= ' ' . $resp->get_error_message();
		}
		wp_send_json_error( $message );
	}

	/**
	 * @since 1.08
	 *
	 * @param mixed $response_body
	 *
	 * @return void
	 */
	private static function check_response_body( $response_body ) {
		if ( ! $response_body ) {
			return;
		}

		$has_error = is_array( $response_body ) && isset( $response_body['error'] );
		if ( ! $has_error ) {
			return;
		}
		ob_start();
		if ( is_array( $response_body['error'] ) ) {
			foreach ( $response_body['error'] as $e ) {
				print_r( $e );
			}
		} else {
			echo esc_html( $response_body['error'] );
		}
		wp_send_json_error( esc_html( ob_get_clean() . self::test_connection_warning() ) );
	}

	/**
	 * @since 1.08
	 *
	 * @param mixed $response_body
	 *
	 * @return string
	 */
	private static function get_message_from_response_body( $response_body ) {
		if ( ! $response_body ) {
			return '';
		}
		if ( ! is_array( $response_body ) ) {
			return (string) $response_body;
		}

		$message = '';
		foreach ( $response_body as $k => $e ) {
			if ( is_array( $e ) && isset( $e['code'] ) && isset( $e['message'] ) ) {
				$message .= $e['message'];
			} elseif ( is_array( $e ) ) {
				$message .= implode( '- ', $e );
			} elseif ( $k !== 'success' ) {
				$message .= $e . ' ';
			}

			unset( $k, $e );
		}
		return $message;
	}

	/**
	 * @since 1.08
	 *
	 * @param mixed $resp
	 * @param mixed $response_body
	 *
	 * @return void
	 */
	private static function check_response_headers( $resp, $response_body ) {
		$has_code = isset( $resp['response'] ) && isset( $resp['response']['code'] );
		if ( ! $has_code ) {
			self::show_success();
		}
		$message = isset( $resp['response']['message'] ) ? $resp['response']['message'] : self::get_message_from_response_body( $response_body );

		if ( strpos( $resp['response']['code'], '20' ) === 0 ) {
			if ( ! $message ) {
				self::show_success();
			}
			wp_send_json_success( esc_html( $message ) );
		}
		$message = sprintf(
			/* translators: %1$s: Response code. %2$s: Response message. */
			__( 'There was a %1$s error: %2$s.', 'frmapi' ),
			$resp['response']['code'],
			$message
		);
		$message .= ' ' . self::test_connection_warning();
		wp_send_json_error( esc_html( $message ) );
	}

	/**
	 * @since 1.08
	 *
	 * @return void
	 */
	private static function show_success() {
		wp_send_json_success( esc_html__( 'Good to go!', 'frmapi' ) );
	}

	/**
	 * @since 1.08
	 *
	 * @return string
	 */
	private static function test_connection_warning() {
		return __( 'In many cases, the test connection lets you know if the link is reachable. The receiving API may not return a successful response when the data is not included. Please submit the form to test with data.', 'frmapi' );
	}

	/**
	 * @return void
	 */
	public static function trigger_api( $action, $entry, $form ) {
		FrmAPIAppController::send_webhooks( $entry, $action );
	}

	public static function clear_raw_before_save( $settings ) {
		$has_raw_data = isset( $settings['data_format'] ) && ! empty( $settings['data_format'] );
		if ( $has_raw_data && $settings['format'] != 'raw' ) {
			$settings['data_format'] = '';
		}
		return $settings;
	}

	/**
	 * Handle settings AJAX requests.
	 * Action name: frm_api_settings_ajax.
	 *
	 * @since 1.17
	 *
	 * @return void
	 */
	public static function ajax_requests() {
		FrmAppHelper::permission_check( 'frm_change_settings' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$settings_action = FrmAppHelper::get_post_param( 'settings_action', '', 'sanitize_text_field' );

		if ( 'refresh-api-key' === $settings_action ) {
			wp_send_json_success( array( 'api_key' => self::generate_new_api_key() ) );
		}
	}

	/**
	 * Show a tooltip icon with the message passed.
	 *
	 * @since 1.17
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

	/**
	 * @deprecated 1.08 - Use the javascript template instead of ajax.
	 *
	 * @return void
	 */
	public static function data_row() {
		_deprecated_function( __METHOD__, '1.08', 'Javascript only' );
	}
}

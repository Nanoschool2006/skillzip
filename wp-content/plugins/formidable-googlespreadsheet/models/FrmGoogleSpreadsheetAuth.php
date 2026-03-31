<?php
/**
 * FrmGoogleSpreadsheetAuth class.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetAuth {

	/**
	 * @var bool Used to make sure we only echo our <script> tag on the return URL once.
	 */
	private static $added_script = false;

	/**
	 * Get auth code after google authorized by user.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function authorization() {
		FrmAppHelper::permission_check( 'frm_change_settings' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );
		$client_id     = FrmAppHelper::get_post_param( 'client_id', 0 );
		$client_secret = FrmAppHelper::get_post_param( 'client_secret', 0 );

		// Bail if we need to update credentials before any action.
		self::update_credentials( $client_id, $client_secret );

		$task = FrmAppHelper::get_post_param( 'task', false );
		if ( 'get_auth_url' === $task ) {
			self::authorization_data( $client_id );
		} else {
			self::authorization_oauth_fetch_code( $client_id, $client_secret );
		}

	}

	/**
	 * Get access token.
	 *
	 * @since 1.0
	 *
	 * @return mixed access token.
	 */
	public static function get_access_token() {
		$auth_settings   = get_option( 'formidable_googlespreadsheet_auth', true );
		$expiration_time = isset( $auth_settings['expires_in'] ) ? $auth_settings['expires_in'] : false;
		if ( ! $expiration_time ) {
			return new WP_Error( 'http_expiration', __( 'The saved code is missing an expiration. Please deauthorize and then authorize again.', 'formidable-google-sheets' ) );
		}
		// Give the access token a 5 minute buffer (300 seconds).
		$expiration_time = $expiration_time - 300;
		if ( time() < $expiration_time ) {
			return $auth_settings['access_token'];
		}
		// at this point we have an expiration time but it is in the past or will be very soon.
		return self::set_oauth2_token( '', 'refresh_token' );
	}

	/**
	 * Revoke the Google API access.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function revoke() {
		FrmAppHelper::permission_check( 'frm_change_settings' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );

		$authsettings = get_option( 'formidable_googlespreadsheet_auth', true );

		$oauth2revoke_url = FrmGoogleSpreadsheetAppHelper::base_auth_url() . 'revoke';
		if ( ! empty( $authsettings['refresh_token'] ) ) {
			$oauth2revoke_url = $oauth2revoke_url . '?token=' . $authsettings['refresh_token'];
		} elseif ( ! empty( $authsettings['access_token'] ) ) {
			$oauth2revoke_url = $oauth2revoke_url . '?token=' . $authsettings['access_token'];
		}
		// We will remove auth regardless of remote result since it may auth is removed by client before revoking.
		delete_option( 'formidable_googlespreadsheet_auth' );
		$settings = FrmGoogleSpreadsheetAppHelper::get_settings();

		$response = array(
			'response' => array(
				'client_id'               => $settings->frm_googlespreadsheet_client_id,
				'client_secret'           => $settings->frm_googlespreadsheet_client_secret,
				'toggle_button_authorize' => FrmGoogleSpreadsheetAppHelper::get_setting_form_authorization_data( $settings->frm_googlespreadsheet_client_id ),
				'message'                 => esc_html__(
					'Google Sheets has been successfully disconnected.',
					'formidable-google-sheets'
				),
			),
			'error'    => false,
		);

		try {
			$remote_request = new FrmGoogleSpreadsheetRemoteRequest( new FrmGoogleSpreadsheetLogController() );
			$remote_request->request( $oauth2revoke_url );
		} catch ( Exception $exception ) {
			/* translators: %1$s: HTTP Error message(s) */
			$response['response']['message'] = sprintf( __( 'Successfully updated but there was an issue deauthorizing with Google for the following reason: (%1$s)', 'formidable-google-sheets' ), $exception->getMessage() );
			$response['error']               = true;
		}

		wp_send_json( $response );

	}

	/**
	 * Get access or refresh token.
	 *
	 * @param string $grant_code could be null for refresh token.
	 * @param string $grant_type access or refresh.
	 * @return mixed access or refresh token.
	 */
	private static function set_oauth2_token( $grant_code, $grant_type ) {
		$settings        = FrmGoogleSpreadsheetAppHelper::get_settings();
		$authsettings    = get_option( 'formidable_googlespreadsheet_auth', array() );
		$oauth2token_url = 'https://www.googleapis.com/oauth2/v4/token';
		$post_args       = array(
			'client_id'     => $settings->frm_googlespreadsheet_client_id,
			'client_secret' => $settings->frm_googlespreadsheet_client_secret,
		);

		if ( 'auth_code' === $grant_type ) {
			// The "auth_code" grant type is to do the initial code exchange.
			$post_args['code']         = $grant_code;
			$post_args['redirect_uri'] = trailingslashit( home_url() );
			$post_args['grant_type']   = 'authorization_code';
		}

		if ( 'refresh_token' === $grant_type ) {
			// The "refresh token" grant type is to use a refresh token to get a new access token.
			$post_args['refresh_token'] = $authsettings['refresh_token'];
			$post_args['grant_type']    = 'refresh_token';
		}

		try {
			$remote_request = new FrmGoogleSpreadsheetRemoteRequest( new FrmGoogleSpreadsheetLogController() );
			$response       = $remote_request->request(
				$oauth2token_url,
				array(
					'method' => 'POST',
					'body'   => $post_args,
				)
			);
		} catch ( Exception $exception ) {
			/* translators: %1$s: the fetched URL, %2$s the error message that was returned */
			return new WP_Error( 'http_error', sprintf( __( 'Failed to fetch: %1$s (%2$s)', 'formidable-google-sheets' ), $oauth2token_url, $exception->getMessage() ) );
		}

		$auth_obj = json_decode( wp_remote_retrieve_body( (array) $response ), true );

		// Return error if there is some unknown issue happened.
		if ( ! isset( $auth_obj['access_token'] ) && ! isset( $auth_obj['refresh_token'] ) ) {
			return new WP_Error( 'failed_access_token', __( 'There is an issue with your access token. Please try to reauthorize the Google API from the Global settings.', 'formidable-google-sheets' ) );
		}

		// Update refresh token if there is one.
		$authsettings['expires_in']    = isset( $auth_obj['expires_in'] ) ? strtotime( '+' . $auth_obj['expires_in'] . ' seconds' ) : $authsettings['expires_in'];
		$authsettings['access_token']  = isset( $auth_obj['access_token'] ) ? $auth_obj['access_token'] : $authsettings['access_token'];
		$authsettings['refresh_token'] = isset( $auth_obj['refresh_token'] ) ? $auth_obj['refresh_token'] : $authsettings['refresh_token'];

		// Update auth into db.
		update_option( 'formidable_googlespreadsheet_auth', $authsettings, false );

		return $auth_obj['access_token'];
	}

	/**
	 * Get access token.
	 *
	 * @since 1.0
	 *
	 * @param int|string $client_id Client ID.
	 * @param int|string $client_secret Client secret.
	 * @return void
	 */
	private static function update_credentials( $client_id, $client_secret ) {
		$settings = FrmGoogleSpreadsheetAppHelper::get_settings();

		if ( $client_id === $settings->frm_googlespreadsheet_client_id && $client_secret === $settings->frm_googlespreadsheet_client_secret ) {
			return;
		}

		$params = array(
			'frm_googlespreadsheet_client_id'     => $client_id,
			'frm_googlespreadsheet_client_secret' => $client_secret,
		);

		$settings->update( $params );
		$settings->store();
	}

	/**
	 * Get access token.
	 *
	 * @since 1.0
	 *
	 * @param string $client_id Client ID.
	 * @return void
	 */
	private static function authorization_data( $client_id ) {
		wp_send_json(
			array(
				'response' => array(
					'authorization_data' => FrmGoogleSpreadsheetAppHelper::get_setting_form_authorization_data( $client_id ),
				),
				'error'    => false,
			)
		);
	}

	/**
	 * Get access token.
	 *
	 * @since 1.0
	 *
	 * @param string $client_id Client ID.
	 * @param string $client_secret Client secret.
	 * @return void.
	 */
	private static function authorization_oauth_fetch_code( $client_id, $client_secret ) {
		$auth_code = FrmAppHelper::get_post_param( 'auth_code', false );
		// Exit early if auth code is not sent.
		if ( ! $auth_code ) {
			wp_send_json(
				array(
					'response' => array(
						'message' => esc_html__( 'The Google API connection failed. Please check the permissions in the Google API console.', 'formidable-google-sheets' ),
					),
					'error'    => true,
				)
			);
		}

		$token = self::set_oauth2_token( $auth_code, 'auth_code' );
		if ( is_wp_error( $token ) ) {
			/** @var WP_Error $token */
			$response = array(
				'response' => array(
					'message' => $token->get_error_messages(),
				),
				'error'    => true,
			);
		} else {
			$response = array(
				'response' => array(
					'client_id'                 => FrmGoogleSpreadsheetAppHelper::change_string_to_asterisks( $client_id ),
					'client_secret'             => FrmGoogleSpreadsheetAppHelper::change_string_to_asterisks( $client_secret ),
					'toggle_button_deauthorize' => FrmGoogleSpreadsheetAppHelper::get_setting_form_authorization_data( $client_id ),
					'message'                   => esc_html__( 'Google Sheets has been authorized successfully.', 'formidable-google-sheets' ),
				),
				'error'    => false,
			);
		}

		wp_send_json( $response );
	}

	/**
	 * Sometimes window.opener is not available.
	 * This is likely caused by a Cross-Origin-Opener-Policy.
	 * In this case, this fallback endpoint is used.
	 * As long as client ID and client secret are saved, we can send the code from the URL in an AJAX request from the pop up window.
	 *
	 * @since 1.0.3
	 *
	 * @return void
	 */
	public static function fallback_authorization() {
		FrmAppHelper::permission_check( 'frm_change_settings' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );

		if ( ! self::client_id_and_secret_are_set() || ! self::access_token_is_missing() ) {
			wp_send_json_error();
		}

		$code = FrmAppHelper::get_post_param( 'code' );
		if ( ! $code ) {
			wp_send_json_error();
		}

		$token = self::set_oauth2_token( $code, 'auth_code' );
		if ( is_wp_error( $token ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * On the home page, if the URL includes $_GET['code'], and we're expecting an OAuth code,
	 * and the user is allowed to authenticate Google Sheets, add a script that posts a message.
	 * This message is listenered for in admin.js.
	 * On some sites, window.opener is not available. In this case, there is a fallback (see the else condition below).
	 *
	 * @since 1.0.3
	 *
	 * @return void
	 */
	public static function maybe_echo_post_message_script() {
		if ( ! is_home() && ! is_front_page() ) {
			return;
		}

		if ( self::$added_script ) {
			return;
		}

		$code = FrmAppHelper::simple_get( 'code' );
		if ( ! $code ) {
			return;
		}

		if ( ! current_user_can( 'frm_change_settings' ) ) {
			return;
		}

		if ( ! self::access_token_is_missing() ) {
			return;
		}

		self::$added_script = true;
		$home_url           = trailingslashit( home_url() );
		?>
		<script>
			( function() {
				if ( window.opener ) {
					window.opener.postMessage(
						{
							message: "Google Sheets Connected",
							code: "<?php echo esc_js( $code ); ?>"
						},
						"<?php echo esc_js( $home_url ); ?>"
					);
					// Fallback using a timeout.
					// When postMessage works, win.close() is called almost immediately.
					setTimeout( fallback, 100 );
				} else {
					fallback();
				}

				function fallback() {
					// If the window.opener is not available, this can be because of a Cross-Origin-Opener-Policy.
					// This is a fallback solution that saves the Auth code from the pop up window instead.
					const formData = new FormData();
					formData.append( 'security', "<?php echo esc_js( wp_create_nonce( 'frmgooglespreadsheet_ajax' ) ); ?>" );
					formData.append( 'code', "<?php echo esc_js( $code ); ?>" );
					formData.append( 'action', 'frm_save_google_sheets_code' );
					const init = {
						method: 'POST',
						body: formData
					};
					fetch( "<?php echo esc_js( FrmAppHelper::get_ajax_url() ); ?>", init ).then(
						response => {
							if ( ! response.ok ) {
								return;
							}

							response.json().then(
								json => {
									if ( ! json.success ) {
										return;
									}

									alert( "<?php echo esc_js( __( 'Google Sheets has been authorized successfully. You can now close this window and reload your global settings.', 'formidable-google-sheets' ) ); ?>" );
									// If window.opener does not work, neither does window.close();
								}
							);
						}
					);
				}
			}() );
		</script>
		<?php
	}

	/**
	 * @since 1.0.3
	 *
	 * @return bool
	 */
	private static function access_token_is_missing() {
		$authsettings = get_option( 'formidable_googlespreadsheet_auth', array() );
		return ! $authsettings || empty( $authsettings['access_token'] );
	}

	/**
	 * @since 1.0.3
	 *
	 * @return bool
	 */
	private static function client_id_and_secret_are_set() {
		$settings = FrmGoogleSpreadsheetAppHelper::get_settings();
		return ! empty( $settings->frm_googlespreadsheet_client_id ) && ! empty( $settings->frm_googlespreadsheet_client_secret );
	}
}

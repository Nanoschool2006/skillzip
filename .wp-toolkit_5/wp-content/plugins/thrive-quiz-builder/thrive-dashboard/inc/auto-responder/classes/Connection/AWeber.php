<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_AWeber extends Thrive_Dash_List_Connection_Abstract {
	const APP_ID = '10fd90de';

	/**
	 * Default OAuth version for new connections
	 *
	 * New connections use OAuth 2.0 Middleman by default.
	 * Existing OAuth 1.0 users can reconnect with 1.0 or upgrade to 2.0 via the UI.
	 *
	 * @var string
	 */
	const DEFAULT_OAUTH_VERSION = '2.0-middleman';

	/**
	 * OAuth 2.0 Middleman version identifier
	 *
	 * @var string
	 */
	const OAUTH_VERSION_MIDDLEMAN = '2.0-middleman';

	/**
	 * Get AWeber API keys from endpoint with transient caching
	 *
	 * Fetches OAuth 1.0 credentials from Thrive's API endpoint for seamless authentication.
	 * Implements 12-hour caching to reduce API calls and improve performance.
	 *
	 * @since 4.0.0
	 *
	 * @return array API keys with consumer_key and consumer_secret, or empty array on error
	 */
	private function get_aweber_api_keys() {

		// Check transient cache first to minimize API calls
		$cached_keys = get_transient( 'thrive_aweber_api_keys' );
		if ( false !== $cached_keys && is_array( $cached_keys ) ) {
			return $cached_keys;
		}

		$endpoint = 'https://thrivethemesapi.com/api/secrets/v1/api_key_aweber';

		// Generate correlation ID for error tracking
		$correlation_id = wp_generate_uuid4();

		$response = wp_remote_get(
			$endpoint,
			array(
				'timeout'   => 10,
				'sslverify' => true,
				'headers'   => array(
					'X-Correlation-ID' => $correlation_id,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );


		// Validate response structure
		if ( 200 !== $response_code ||
			! is_array( $data ) ||
			empty( $data['success'] ) ||
			empty( $data['data']['value']['consumer_key'] ) ||
			empty( $data['data']['value']['consumer_secret'] )
		) {
			return array();
		}

		$keys = array(
			'consumer_key'    => sanitize_text_field( $data['data']['value']['consumer_key'] ),
			'consumer_secret' => sanitize_text_field( $data['data']['value']['consumer_secret'] ),
		);


		// Cache for 12 hours to reduce API load
		set_transient( 'thrive_aweber_api_keys', $keys, 12 * HOUR_IN_SECONDS );

		return $keys;
	}

	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * Check if tags are supported
	 *
	 * AWeber supports tags for all OAuth versions (1.0, 2.0, and middleman) conceptually.
	 * However, tags are only functional for OAuth 2.0 and middleman connections.
	 * For OAuth 1.0 connections, tag functionality is not available (returns empty tags).
	 *
	 * @since  4.0.0 Added middleman support
	 * @return bool True - tags are always supported (for UI/backward compatibility)
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * Get the consumer key for OAuth 1.0
	 *
	 * For OAuth 1.0 connections, credentials are fetched from Thrive's API endpoint.
	 * This eliminates the need for users to manually enter consumer keys.
	 *
	 * @since 4.0.0
	 *
	 * @return string Consumer key from API endpoint or empty string on error
	 */
	protected function get_consumer_key() {
		$keys = $this->get_aweber_api_keys();

		return ! empty( $keys['consumer_key'] ) ? $keys['consumer_key'] : '';
	}

	/**
	 * Get the consumer secret for OAuth 1.0
	 *
	 * For OAuth 1.0 connections, credentials are fetched from Thrive's API endpoint.
	 * This eliminates the need for users to manually enter consumer secrets.
	 *
	 * @since 4.0.0
	 *
	 * @return string Consumer secret from API endpoint or empty string on error
	 */
	protected function get_consumer_secret() {
		$keys = $this->get_aweber_api_keys();

		return ! empty( $keys['consumer_secret'] ) ? $keys['consumer_secret'] : '';
	}

	/**
	 * Get the OAuth 2.0 client ID from saved credentials
	 *
	 * @return string
	 */
	protected function get_client_id() {
		return $this->param( 'client_id' );
	}

	/**
	 * Get the OAuth 2.0 client secret from saved credentials
	 *
	 * @return string
	 */
	protected function get_client_secret() {
		return $this->param( 'client_secret' );
	}

	/**
	 * Get the authorization URL for the AWeber Application
	 *
	 * Routes to OAuth 2.0, OAuth 1.0, or Middleman flow based on the oauth_version parameter.
	 * All flows use simple form-based redirect - no AJAX or polling.
	 *
	 * @since 4.0.0
	 *
	 * @return string Authorization URL or error message
	 */
	public function getAuthorizeUrl() {

		// Get OAuth version from form request
		// Existing OAuth 1.0 users can explicitly request '1.0' to reconnect with legacy flow
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handler
		$oauth_version = ! empty( $_REQUEST['oauth_version'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oauth_version'] ) ) : '';

		// Use default version for new connections (when no version specified)
		if ( empty( $oauth_version ) ) {
			$oauth_version = self::DEFAULT_OAUTH_VERSION;
		}

		// Check for middleman OAuth first (default for new connections)
		if ( self::OAUTH_VERSION_MIDDLEMAN === $oauth_version ) {
			return $this->get_middleman_authorize_url();
		}

		// Check if OAuth 2.0 is requested (direct OAuth 2.0, not middleman)
		if ( '2.0' === $oauth_version ) {
			return $this->get_oauth2_authorize_url();
		}

		// OAuth 1.0 flow - for existing users reconnecting with legacy auth
		$correlation_id = wp_generate_uuid4();

		// Fetch credentials from API
		$consumer_key    = $this->get_consumer_key();
		$consumer_secret = $this->get_consumer_secret();


		// Validate credentials were successfully fetched
		if ( empty( $consumer_key ) || empty( $consumer_secret ) ) {
			return sprintf(
				__( 'Could not connect to AWeber. Please try again later or contact support. [Error Code: %s]', 'thrive-dash' ),
				substr( $correlation_id, 0, 8 )
			);
		}

		// Store oauth_version for later use
		$this->set_param( 'oauth_version', '1.0' );

		try {
			/** @var Thrive_Dash_Api_AWeber $aweber */
			$aweber       = $this->get_api();
			$callback_url = admin_url( 'admin.php?page=tve_dash_api_connect&api=aweber' );


			list ( $request_token, $request_token_secret ) = $aweber->getRequestToken( $callback_url );

			update_option( 'thrive_aweber_rts', $request_token_secret );

			$authorize_url = $aweber->getAuthorizeUrl();

			return $authorize_url;
		} catch ( Exception $e ) {
			return sprintf(
				__( 'Error connecting to AWeber: %s [Error Code: %s]', 'thrive-dash' ),
				$e->getMessage(),
				substr( $correlation_id, 0, 8 )
			);
		}
	}

	/**
	 * Get middleman OAuth authorization URL (simple redirect, like OAuth 1.0)
	 *
	 * Uses the same simple form-based redirect pattern as OAuth 1.0:
	 * 1. Form POST triggers this method
	 * 2. Returns authorization URL
	 * 3. Browser redirects to middleman API with redirect_uri parameter
	 * 4. Middleman API redirects to AWeber
	 * 5. AWeber redirects back to middleman API
	 * 6. Middleman API redirects back to WordPress callback with aweber_middleman_callback=1
	 * 7. read_credentials() processes the callback
	 *
	 * @since 4.0.0
	 * @return string Authorization URL or error message
	 */
	protected function get_middleman_authorize_url() {
		// Store oauth_version for later use
		$this->set_param( 'oauth_version', self::OAUTH_VERSION_MIDDLEMAN );

		// Get middleman client
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return __( 'Middleman OAuth client not available. Please contact support.', 'thrive-dash' );
		}

		// Pre-flight check: ensure API token exists before making the OAuth API call
		if ( empty( $client->get_api_token() ) ) {
			return __( 'Unable to connect: Authentication token is missing. Please refresh this page and try again. If the problem persists, contact support.', 'thrive-dash' );
		}

		// Build redirect URI - MUST include api=aweber parameter so tve_dash_api_handle_save() will trigger
		// Without this, the callback URL won't trigger read_credentials() and connection won't be saved
		$redirect_uri = admin_url( 'admin.php?page=tve_dash_api_connect&api=aweber' );

		// Initiate OAuth through middleman API with redirect_uri
		$result = $client->initiate_oauth( $redirect_uri );

		if ( empty( $result['success'] ) ) {
			$error = $result['error'] ?? __( 'Failed to initiate OAuth', 'thrive-dash' );
			return sprintf( __( 'Error: %s', 'thrive-dash' ), $error );
		}

		// Validate authorization URL before returning
		$authorize_url = $result['data']['authorization_url'] ?? '';
		if ( empty( $authorize_url ) || ! filter_var( $authorize_url, FILTER_VALIDATE_URL ) ) {
			return __( 'Authorization URL not received from API or is invalid', 'thrive-dash' );
		}

		return $authorize_url;
	}

	/**
	 * Get OAuth 2.0 authorization URL
	 *
	 * @return string
	 */
	protected function get_oauth2_authorize_url() {

		// Get credentials from form
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handler
		$client_id     = ! empty( $_REQUEST['client_id'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['client_id'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handler
		$client_secret = ! empty( $_REQUEST['client_secret'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['client_secret'] ) ) : '';


		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return __( 'Please enter your AWeber Client ID and Client Secret from https://labs.aweber.com/. Create an OAuth 2.0 PUBLIC app and add your credentials.', 'thrive-dash' );
		}

		// Store client credentials for later use in the OAuth flow
		$this->set_param( 'client_id', $client_id );
		$this->set_param( 'client_secret', $client_secret );
		$this->set_param( 'oauth_version', '2.0' );


		// Store credentials in transient for callback (persist across requests)
		set_transient(
			'aweber_oauth2_credentials_' . get_current_user_id(),
			array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
			),
			10 * MINUTE_IN_SECONDS
		);


		$redirect_uri = admin_url( 'admin.php?page=tve_dash_api_connect&api=aweber&oauth_version=2.0' );
		$state        = wp_create_nonce( 'aweber_oauth2_' . get_current_user_id() );


		// Store state for verification
		set_transient( 'aweber_oauth2_state_' . get_current_user_id(), $state, 10 * MINUTE_IN_SECONDS );

		// Build the authorization URL manually with proper encoding
		// We can't use add_query_arg() because it doesn't encode the redirect_uri parameter properly
		$auth_url = 'https://auth.aweber.com/oauth2/authorize?' . http_build_query( array(
			'response_type' => 'code',
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'scope'         => 'account.read list.read list.write subscriber.read subscriber.write',
			'state'         => $state,
		) );

		return $auth_url;
	}

	/**
	 * Check if connection is established
	 *
	 * @since  4.0.0 Added middleman support
	 * @return bool True if connected
	 */
	public function is_connected() {
		$oauth_version = $this->param( 'oauth_version' );

		// Check middleman connection
		if ( self::OAUTH_VERSION_MIDDLEMAN === $oauth_version ) {
			$client = $this->get_middleman_client();

			if ( false === $client ) {
				return false;
			}

			return $client->is_connected();
		}

		// Check OAuth 2.0 connection
		if ( '2.0' === $oauth_version ) {
			return ! empty( $this->param( 'access_token' ) );
		}

		// Check OAuth 1.0 connection
		return $this->param( 'token' ) && $this->param( 'secret' );
	}

	/**
	 * @return string the API connection title
	 */
	public function get_title() {
		return 'AWeber';
	}

	/**
	 * Get API data with supports_tags flag and tags array
	 *
	 * @param array $params
	 * @param bool  $force
	 *
	 * @return array
	 */
	public function get_api_data( $params = array(), $force = false ) {
		$data = parent::get_api_data( $params, $force );

		// Add supports_tags flag based on OAuth version
		$supports_tags         = $this->has_tags();
		$data['supports_tags'] = $supports_tags;

		// Add tags for OAuth 2.0 connections
		if ( $supports_tags ) {
			$tags = $this->_get_tags();
			$data['tags'] = $tags;

			// Also add tags to extra_settings for TCB UI compatibility
			// This ensures tags are available even when parent data is cached
			if ( ! isset( $data['extra_settings'] ) || ! is_array( $data['extra_settings'] ) ) {
				$data['extra_settings'] = array();
			}

			// Convert tags array to object format expected by JavaScript
			$tags_object = array();
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag['id'] ) ) {
					$tags_object[ $tag['id'] ] = $tag['text'];
				}
			}
			$data['extra_settings']['tags'] = $tags_object;
		}

		return $data;
	}

	/**
	 * Prepare JSON data for JavaScript/Backbone models
	 *
	 * Adds upgrade badge for legacy OAuth 1.0 connections
	 *
	 * @return array
	 */
	public function prepare_json() {
		$properties = parent::prepare_json();

		// Check if this is a legacy OAuth 1.0 connection
		$oauth_version   = $this->param( 'oauth_version' );
		$is_legacy_oauth = $this->is_connected() && ( empty( $oauth_version ) || '1.0' === $oauth_version );

		if ( $is_legacy_oauth ) {
			$badge_style = 'display:inline-flex;align-items:center;gap:4px;background-color:#fcf9f6;border:1px solid #ff7100;border-radius:100px;padding:2px 5px;font-family:Roboto,sans-serif;font-size:11px;font-weight:500;line-height:1.5;color:#ff7100;white-space:nowrap;margin-left:4px;';
			$properties['upgrade_badge'] = '<span style="' . esc_attr( $badge_style ) . '">' .
				'<svg width="11" height="11" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">' .
					'<circle cx="6" cy="6" r="5.5" stroke="#ff7100"/>' .
					'<path d="M6 3V6.5" stroke="#ff7100" stroke-linecap="round"/>' .
					'<circle cx="6" cy="8.5" r="0.5" fill="#ff7100"/>' .
				'</svg>' .
				esc_html__( 'Upgrade', 'thrive-dash' ) .
			'</span>';
		}

		return $properties;
	}

	/**
	 * Get tags from AWeber OAuth 2.0 API
	 *
	 * Nested loop is necessary here because:
	 * - Outer loop: Fetches tags from each list (AWeber stores tags per list)
	 * - Inner loop: Processes response data from each API call
	 *
	 * Performance optimization: Results are cached for 5 minutes to reduce API calls
	 *
	 * @since  4.0.0 Added middleman support
	 * @return array Array of tags with 'id' and 'text' keys
	 */
	protected function _get_tags() {
		$tags = array();

		// Only fetch tags for OAuth 2.0 connections
		$oauth_version = $this->param( 'oauth_version' );

		// Use middleman for middleman OAuth
		if ( self::OAUTH_VERSION_MIDDLEMAN === $oauth_version ) {
			return $this->get_tags_middleman();
		}

		if ( '2.0' !== $oauth_version ) {
			return $tags;
		}

		$access_token = $this->param( 'access_token' );
		$account_id   = $this->param( 'account_id' );

		if ( empty( $access_token ) || empty( $account_id ) ) {
			return $tags;
		}

		// Check cache first to avoid unnecessary API calls
		$cache_key = 'aweber_tags_' . md5( $account_id . $access_token );
		$cached_tags = get_transient( $cache_key );

		if ( false !== $cached_tags && is_array( $cached_tags ) ) {
			return $cached_tags;
		}

		try {
			// Fetch all lists first to get tags from each list
			$lists = $this->_get_lists();

			if ( empty( $lists ) || ! is_array( $lists ) ) {
				return $tags;
			}

			$all_tags = array();

			// AWeber stores tags per list, so we need to fetch tags from all lists
			// Note: This nested loop is unavoidable - we must make one API call per list
			foreach ( $lists as $list ) {
				if ( empty( $list['id'] ) ) {
					continue;
				}

				$response = wp_remote_get(
					'https://api.aweber.com/1.0/accounts/' . $account_id . '/lists/' . $list['id'] . '/tags',
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . $access_token,
							'Accept'        => 'application/json',
						),
						'timeout' => 30,
					)
				);

				if ( is_wp_error( $response ) ) {
					continue;
				}

				$response_code = wp_remote_retrieve_response_code( $response );
				$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

				// AWeber returns tags as a simple array of strings: ["tag1", "tag2", ...]
				if ( 200 === $response_code && is_array( $response_data ) && ! empty( $response_data ) ) {
					// Process each tag from the response
					foreach ( $response_data as $tag_name ) {
						if ( ! empty( $tag_name ) && is_string( $tag_name ) ) {
							// Use tag name as key to avoid duplicates across lists
							$all_tags[ $tag_name ] = array(
								'id'   => $tag_name,
								'text' => $tag_name,
							);
						}
					}
				}
			}

			// Convert associative array to indexed array and sort alphabetically
			$tags = array_values( $all_tags );
			usort( $tags, function ( $a, $b ) {
				$a_text = isset( $a['text'] ) ? $a['text'] : '';
				$b_text = isset( $b['text'] ) ? $b['text'] : '';
				return strcasecmp( $a_text, $b_text );
			} );

			// Cache results for 5 minutes to improve performance
			set_transient( $cache_key, $tags, 5 * MINUTE_IN_SECONDS );

		} catch ( Exception $e ) {
			// Silently fail and return empty tags array
		}

		return $tags;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'aweber' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		// Check if this is a middleman OAuth callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state verified in handler
		if ( ! empty( $_REQUEST['aweber_middleman_callback'] ) ) {
			return $this->read_middleman_credentials();
		}

		// Check if this is an OAuth 2.0 callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state verified in handler
		$oauth_version_request = ! empty( $_REQUEST['oauth_version'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oauth_version'] ) ) : '';
		if ( '2.0' === $oauth_version_request ) {
			return $this->read_oauth2_credentials();
		}

		// OAuth 1.0 flow
		/** @var Thrive_Dash_Api_AWeber $aweber */
		$aweber = $this->get_api();

		$aweber->user->tokenSecret  = get_option( 'thrive_aweber_rts' );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback
		$aweber->user->requestToken = ! empty( $_REQUEST['oauth_token'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oauth_token'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback
		$aweber->user->verifier     = ! empty( $_REQUEST['oauth_verifier'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['oauth_verifier'] ) ) : '';

		try {
			list( $accessToken, $accessTokenSecret ) = $aweber->getAccessToken();

			// Preserve consumer_key and consumer_secret along with access token and secret
			$credentials = array(
				'oauth_version' => '1.0',
				'token'         => $accessToken,
				'secret'        => $accessTokenSecret,
			);

			// Ensure the consumer credentials are always present
			$consumer_key    = $this->param( 'consumer_key' );
			$consumer_secret = $this->param( 'consumer_secret' );
			if ( empty( $consumer_key ) ) {
				$consumer_key = $this->get_consumer_key();
			}
			if ( empty( $consumer_secret ) ) {
				$consumer_secret = $this->get_consumer_secret();
			}
			$credentials['consumer_key']    = $consumer_key;
			$credentials['consumer_secret'] = $consumer_secret;

			$this->set_credentials( $credentials );
		} catch ( Exception $e ) {
			$correlation_code = 'AWE-OAUTH-ACC-' . substr( wp_hash( uniqid( '', true ) ), 0, 8 );
			$this->api_log_error( 'auth', array(
				'step'             => 'access_token',
				'api_url'          => 'https://auth.aweber.com/1.0/oauth/access_token',
				'correlation_code' => $correlation_code,
			), sprintf( '%s. Please contact customer support at thrivethemes.com and mention code %s', $e->getMessage(), $correlation_code ) );
			$this->error( $e->getMessage() );

			return false;
		}

		$result = $this->test_connection();
		if ( true !== $result ) {
			$this->error( sprintf( __( 'Could not test AWeber connection: %s', 'thrive-dash' ), $result ) );

			return false;
		}

		$this->save();

		// Clear the cached API data to ensure fresh data
		$this->clear_api_data_cache();

		/**
		 * Fetch all custom fields on connect so that we have them all prepared
		 * - TAr doesn't need to fetch them from API
		 */
		$this->get_api_custom_fields( array(), true, true );

		return true;
	}

	/**
	 * Handle middleman OAuth callback (simple redirect, like OAuth 1.0)
	 *
	 * This is called when the middleman API redirects back to WordPress after OAuth completion.
	 * The callback URL will have parameters: aweber_middleman_callback=1, success=1, state={state}
	 * Or on error: aweber_middleman_callback=1, error={error_message}, state={state}
	 *
	 * Uses the same simple pattern as OAuth 1.0 - no AJAX, no polling.
	 *
	 * @since 4.0.0
	 * @return bool True on success, false on failure
	 */
	protected function read_middleman_credentials() {
		// Validate this is a middleman callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state verified below
		if ( empty( $_REQUEST['aweber_middleman_callback'] ) ) {
			$this->error( __( 'Invalid callback - missing aweber_middleman_callback parameter', 'thrive-dash' ) );
			return false;
		}

		// Get state from callback for verification
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state verified below
		$state = ! empty( $_REQUEST['state'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['state'] ) ) : '';

		if ( empty( $state ) ) {
			$this->error( __( 'Missing state parameter from callback', 'thrive-dash' ) );
			return false;
		}

		// Verify state to prevent CSRF attacks
		$user_id      = get_current_user_id();
		$stored_state = get_transient( 'aweber_middleman_oauth_state_' . $user_id );

		if ( ! $stored_state || $stored_state !== $state ) {
			$this->error( __( 'Invalid state parameter. Security check failed or session expired.', 'thrive-dash' ) );
			return false;
		}

		// Delete state (one-time use)
		delete_transient( 'aweber_middleman_oauth_state_' . $user_id );

		// Check for error from callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state already verified
		if ( ! empty( $_REQUEST['error'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state already verified
			$error_message = sanitize_text_field( wp_unslash( $_REQUEST['error'] ) );
			$this->error( sprintf( __( 'OAuth error: %s', 'thrive-dash' ), $error_message ) );
			return false;
		}

		// Check for success parameter
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state already verified
		$success = ! empty( $_REQUEST['success'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['success'] ) ) : '';

		if ( '1' !== $success ) {
			$this->error( __( 'OAuth did not complete successfully', 'thrive-dash' ) );
			return false;
		}

		// Get middleman client
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			$this->error( __( 'Middleman OAuth client not available', 'thrive-dash' ) );
			return false;
		}

		// Check connection status from API
		$status_result = $client->check_status();

		// $status_result is always an array; no need to check is_wp_error

		if ( empty( $status_result['success'] ) ) {
			$error = $status_result['error'] ?? __( 'Failed to verify connection status', 'thrive-dash' );
			$this->error( $error );
			return false;
		}

		// Verify connection is established
		if ( empty( $status_result['data']['connected'] ) ) {
			$this->error( __( 'Connection not established on API', 'thrive-dash' ) );
			return false;
		}

		// Store credentials in connection format
		$info        = $client->get_connection_info();

		$credentials = array(
			'oauth_version'    => self::OAUTH_VERSION_MIDDLEMAN,
			'connection_state' => 'connected',
			'connected_at'     => $info['connected_at'] ?? time(),
			'account_id'       => $info['account_id'] ?? '',
		);

		$this->set_credentials( $credentials );

		// Test connection
		if ( ! $client->is_connected() ) {
			$this->error( __( 'Connection test failed', 'thrive-dash' ) );
			return false;
		}

		$this->save();

		// Clear the cached API data to ensure fresh data
		$this->clear_api_data_cache();

		/**
		 * Fetch all custom fields on connect so that we have them all prepared
		 * - TAr doesn't need to fetch them from API
		 */
		$this->get_api_custom_fields( array(), true, true );

		return true;
	}

	/**
	 * Handle OAuth 2.0 credentials and token exchange
	 *
	 * @return bool
	 */
	protected function read_oauth2_credentials() {

		// Retrieve credentials from transient (persists across HTTP requests)
		$credentials = get_transient( 'aweber_oauth2_credentials_' . get_current_user_id() );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, state verified below
		$auth_code   = ! empty( $_REQUEST['code'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['code'] ) ) : '';


		if ( empty( $credentials ) || ! is_array( $credentials ) ) {
			$this->error( __( 'OAuth 2.0 session expired. Please try connecting again.', 'thrive-dash' ) );

			return false;
		}

		$client_id     = ! empty( $credentials['client_id'] ) ? $credentials['client_id'] : '';
		$client_secret = ! empty( $credentials['client_secret'] ) ? $credentials['client_secret'] : '';

		// Delete transient after retrieval
		delete_transient( 'aweber_oauth2_credentials_' . get_current_user_id() );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			$this->error( __( 'OAuth 2.0 credentials missing. Please enter your Client ID and Client Secret from https://labs.aweber.com/', 'thrive-dash' ) );

			return false;
		}


		if ( empty( $auth_code ) ) {
			$this->error( __( 'Missing authorization code from AWeber', 'thrive-dash' ) );

			return false;
		}

		// Verify state if it's a callback
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback state verification
		if ( ! empty( $_REQUEST['state'] ) ) {
			$stored_state   = get_transient( 'aweber_oauth2_state_' . get_current_user_id() );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback state verification
			$received_state = sanitize_text_field( wp_unslash( $_REQUEST['state'] ) );

			if ( empty( $stored_state ) || $stored_state !== $received_state ) {
				$this->error( __( 'Invalid state parameter. Security check failed.', 'thrive-dash' ) );

				return false;
			}

			delete_transient( 'aweber_oauth2_state_' . get_current_user_id() );
		}

		try {
			$redirect_uri = admin_url( 'admin.php?page=tve_dash_api_connect&api=aweber&oauth_version=2.0' );


			// Exchange authorization code for access token
			$response = wp_remote_post(
				'https://auth.aweber.com/oauth2/token',
				array(
					'body'    => array(
						'grant_type'    => 'authorization_code',
						'code'          => $auth_code,
						'redirect_uri'  => $redirect_uri,
						'client_id'     => $client_id,
						'client_secret' => $client_secret,
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				$this->error( sprintf( __( 'Error exchanging authorization code: %s', 'thrive-dash' ), $response->get_error_message() ) );

				return false;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$token_data    = json_decode( $response_body, true );


			if ( 200 !== $response_code || empty( $token_data['access_token'] ) ) {
				$error_message = ! empty( $token_data['error_description'] ) ? $token_data['error_description'] : __( 'Failed to obtain access token', 'thrive-dash' );

				$this->error( $error_message );

				return false;
			}


			// Store OAuth 2.0 credentials
			$credentials = array(
				'oauth_version'  => '2.0',
				'client_id'      => $client_id,
				'client_secret'  => $client_secret,
				'access_token'   => $token_data['access_token'],
				'refresh_token'  => ! empty( $token_data['refresh_token'] ) ? $token_data['refresh_token'] : '',
				'expires_in'     => ! empty( $token_data['expires_in'] ) ? $token_data['expires_in'] : 0,
				'token_obtained' => time(),
			);

			$this->set_credentials( $credentials );

			// Fetch and store account_id immediately after getting access token
			$account_response = wp_remote_get(
				'https://api.aweber.com/1.0/accounts',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token_data['access_token'],
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( ! is_wp_error( $account_response ) ) {
				$account_data = json_decode( wp_remote_retrieve_body( $account_response ), true );

				// AWeber API returns a single account per OAuth token in entries array.
				// Each token is tied to one AWeber customer account.
				if ( ! empty( $account_data['entries'] ) && is_array( $account_data['entries'] ) && ! empty( $account_data['entries'][0]['id'] ) ) {
					$account_id = $account_data['entries'][0]['id'];
					$this->set_param( 'account_id', $account_id );
				} else {
				}
			} else {
			}

		} catch ( Exception $e ) {
			$this->error( $e->getMessage() );

			return false;
		}

		$result = $this->test_connection();
		if ( true !== $result ) {
			$this->error( sprintf( __( 'Could not test AWeber connection: %s', 'thrive-dash' ), $result ) );

			return false;
		}

		$this->save();

		// Clear the cached API data to ensure fresh data with supports_tags and tags
		$this->clear_api_data_cache();

		/**
		 * Fetch all custom fields on connect so that we have them all prepared
		 * - TAr doesn't need to fetch them from API
		 */
		$this->get_api_custom_fields( array(), true, true );

		return true;
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {

		// Test OAuth 2.0 Middleman connection
		if ( self::OAUTH_VERSION_MIDDLEMAN === $this->param( 'oauth_version' ) ) {
			return $this->test_middleman_connection();
		}

		// Test OAuth 2.0 connection
		if ( '2.0' === $this->param( 'oauth_version' ) ) {
			return $this->test_oauth2_connection();
		}

		// Test OAuth 1.0 connection
		/** @var Thrive_Dash_Api_AWeber $aweber */
		$aweber = $this->get_api();

		try {
			$aweber->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );

			return true;
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Test OAuth 2.0 Middleman connection
	 *
	 * @since  4.0.0
	 * @return bool|string true for success or error message for failure
	 */
	protected function test_middleman_connection() {
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return __( 'Middleman client not available', 'thrive-dash' );
		}

		$result = $client->check_status();

		if ( ! empty( $result['success'] ) && ! empty( $result['data']['connected'] ) ) {
			return true;
		}

		return $result['error'] ?? __( 'Connection test failed', 'thrive-dash' );
	}

	/**
	 * Test OAuth 2.0 connection
	 *
	 * @return bool|string
	 */
	protected function test_oauth2_connection() {
		$access_token = $this->param( 'access_token' );

		if ( empty( $access_token ) ) {
			return __( 'Missing access token', 'thrive-dash' );
		}


		try {
			$response = wp_remote_get(
				'https://api.aweber.com/1.0/accounts',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			}

			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				$response_body = wp_remote_retrieve_body( $response );
				$error_data    = json_decode( $response_body, true );
				$error_message = ! empty( $error_data['error']['message'] ) ? $error_data['error']['message'] : __( 'Connection test failed', 'thrive-dash' );

				return $error_message;
			}

			return true;
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return mixed
	 */
	protected function get_api_instance() {
		$consumer_key    = $this->get_consumer_key();
		$consumer_secret = $this->get_consumer_secret();

		return new Thrive_Dash_Api_AWeber( $consumer_key, $consumer_secret );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @since  4.0.0 Added middleman support
	 * @return array|bool Array of lists or false on failure
	 */
	protected function _get_lists() {
		$oauth_version = $this->param( 'oauth_version' );

		// Use middleman for middleman OAuth
		if ( self::OAUTH_VERSION_MIDDLEMAN === $oauth_version ) {
			return $this->get_lists_middleman();
		}

		// Use OAuth 2.0 API if connected via OAuth 2.0
		if ( '2.0' === $oauth_version ) {
			return $this->get_lists_oauth2();
		}

		// OAuth 1.0 flow
		/** @var Thrive_Dash_Api_AWeber $aweber */
		$aweber = $this->get_api();

		try {
			$lists   = array();
			$account = $aweber->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );
			foreach ( $account->lists as $item ) {
				/** @var Thrive_Dash_Api_AWeber_Entry $item */
				$lists [] = array(
					'id'   => $item->data['id'],
					'name' => $item->data['name'],
				);
			}

			return $lists;
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * Get lists using OAuth 2.0 API
	 *
	 * @return array|bool
	 */
	protected function get_lists_oauth2() {
		$access_token = $this->param( 'access_token' );

		if ( empty( $access_token ) ) {
			$this->_error = __( 'Missing access token', 'thrive-dash' );

			return false;
		}


		try {
			// First, get the account ID
			$account_response = wp_remote_get(
				'https://api.aweber.com/1.0/accounts',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $account_response ) ) {
				$this->_error = $account_response->get_error_message();

				return false;
			}

			$account_data = json_decode( wp_remote_retrieve_body( $account_response ), true );

			// AWeber API returns a single account per OAuth token in entries array.
			// Each token is tied to one AWeber customer account.
			if ( empty( $account_data['entries'] ) || ! is_array( $account_data['entries'] ) || empty( $account_data['entries'][0]['id'] ) ) {
				$this->_error = __( 'Could not retrieve account information', 'thrive-dash' );

				return false;
			}

			$account_id = $account_data['entries'][0]['id'];

			// Store account ID for later use
			$this->set_param( 'account_id', $account_id );

			// Get lists for this account
			$lists_response = wp_remote_get(
				'https://api.aweber.com/1.0/accounts/' . $account_id . '/lists',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $lists_response ) ) {
				$this->_error = $lists_response->get_error_message();

				return false;
			}

			$lists_data = json_decode( wp_remote_retrieve_body( $lists_response ), true );

			if ( empty( $lists_data['entries'] ) ) {
				return array();
			}

			$lists = array();
			foreach ( $lists_data['entries'] as $item ) {
				if ( ! empty( $item['id'] ) && ! empty( $item['name'] ) ) {
					$lists[] = array(
						'id'   => $item['id'],
						'name' => $item['name'],
					);
				}
			}

			return $lists;
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * add a contact to a list
	 *
	 * @since 4.0.0 Added middleman support
	 *
	 * @param string $list_identifier List ID
	 * @param array  $arguments       Subscriber data
	 *
	 * @return bool|string True on success, error message on failure
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		$oauth_version = $this->param( 'oauth_version' );

		// Use middleman for middleman OAuth
		if ( self::OAUTH_VERSION_MIDDLEMAN === $oauth_version ) {
			return $this->add_subscriber_middleman( $list_identifier, $arguments );
		}

		// Use OAuth 2.0 API if connected via OAuth 2.0
		if ( '2.0' === $oauth_version ) {
			return $this->add_subscriber_oauth2( $list_identifier, $arguments );
		}

		// OAuth 1.0 flow
		try {
			/** @var Thrive_Dash_Api_AWeber $aweber */
			$aweber  = $this->get_api();
			$account = $aweber->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );
			$listURL = "/accounts/{$account->id}/lists/{$list_identifier}";
			$list    = $account->loadFromUrl( $listURL );

			# create a subscriber
			$params = array(
				'email' => $arguments['email'],
			);

			// Only add IP address if it's not from a reserved block (localhost, private networks)
			$ip_address = tve_dash_get_ip();
			if ( ! empty( $ip_address ) && ! $this->is_reserved_ip( $ip_address ) ) {
				$params['ip_address'] = $ip_address;
			}
			if ( ! empty( $arguments['name'] ) ) {
				$params['name'] = $arguments['name'];
			}

			if ( isset( $arguments['url'] ) ) {
				$params['custom_fields']['Web Form URL'] = $arguments['url'];
			}
			// create custom fields
			$custom_fields = $list->custom_fields;

			try {
				$custom_fields->create( array( 'name' => 'Web Form URL' ) );
			} catch ( Exception $e ) {
			}

			if ( ! empty( $arguments['phone'] ) && ( $phone_field_name = $this->phoneCustomFieldExists( $list ) ) ) {
				$params['custom_fields'][ $phone_field_name ] = $arguments['phone'];
			}

			// Handle tags for OAuth 1.0 (backward compatible with original code)
			// Check both field names - form submissions may send 'aweber_aweber_tags' or 'aweber_tags'
			$tags_value = '';
			if ( ! empty( $arguments['aweber_aweber_tags'] ) ) {
				$tags_value = $arguments['aweber_aweber_tags'];
			} elseif ( ! empty( $arguments['aweber_tags'] ) ) {
				$tags_value = $arguments['aweber_tags'];
			}

			if ( ! empty( $tags_value ) ) {
				// Handle both array format (from multi-select) and string format (comma-separated)
				if ( is_array( $tags_value ) ) {
					$params['tags'] = array_map( 'sanitize_text_field', array_map( 'trim', $tags_value ) );
				} else {
					$params['tags'] = explode( ',', trim( $tags_value, ' ,' ) );
					$params['tags'] = array_map( 'sanitize_text_field', array_map( 'trim', $params['tags'] ) );
				}
				$params['tags'] = array_filter( $params['tags'] );
			}

			if ( ( $existing_subscribers = $list->subscribers->find( array( 'email' => $params['email'] ) ) ) && 1 === $existing_subscribers->count() ) {
				$subscriber = $existing_subscribers->current();
				if ( ! empty( $arguments['name'] ) ) {
					$subscriber->name        = $params['name'];
				}
				if ( ! empty( $params['custom_fields'] ) ) {
					$subscriber->custom_fields = $params['custom_fields'];
				}

				// Handle tags for existing subscribers (backward compatible)
				if ( empty( $params['tags'] ) || ! is_array( $params['tags'] ) ) {
					$params['tags'] = array();
				}
				$tags = array_values( array_diff( $params['tags'], $subscriber->tags->getData() ) );

				if ( ! empty( $tags ) ) {
					$subscriber->tags = array(
						'add' => $tags,
					);
				}

				$new_subscriber = 209 === $subscriber->save();
			} else {
				$new_subscriber = $list->subscribers->create( $params );
			}

			if ( ! $new_subscriber ) {
				return sprintf( __( "Could not add contact: %s to list: %s", 'thrive-dash' ), $arguments['email'], $list->name );
			}

			// Update custom fields
			// Make another call to update custom mapped fields in order not to break the subscription call,
			// if custom data doesn't pass API custom fields validation
			$mapping = thrive_safe_unserialize( base64_decode( isset( $arguments['tve_mapping'] ) ? $arguments['tve_mapping'] : '' ) );
			if ( ! empty( $mapping ) || ! empty( $arguments['automator_custom_fields'] ) ) {
				$this->updateCustomFields( $list_identifier, $arguments, $params );
			}

		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Add subscriber using OAuth 2.0 REST API
	 *
	 * @param string $list_identifier
	 * @param array  $arguments
	 *
	 * @return bool|string
	 */
	protected function add_subscriber_oauth2( $list_identifier, $arguments ) {
		// Early validation - check required email
		if ( empty( $arguments['email'] ) ) {
			return __( 'Email address is required', 'thrive-dash' );
		}

		$email = sanitize_email( $arguments['email'] );
		if ( ! is_email( $email ) ) {
			return __( 'Invalid email address', 'thrive-dash' );
		}

		$access_token = $this->param( 'access_token' );
		$account_id   = $this->param( 'account_id' );

		if ( empty( $access_token ) || empty( $account_id ) ) {
			return __( 'Missing OAuth 2.0 credentials', 'thrive-dash' );
		}

		try {
			// Prepare subscriber data
			$subscriber_data = array(
				'email' => $email,
			);

			// Only add IP address if it's not from a reserved block
			$ip_address = tve_dash_get_ip();

			if ( ! empty( $ip_address ) && ! $this->is_reserved_ip( $ip_address ) ) {
				$subscriber_data['ip_address'] = $ip_address;
			}

			if ( ! empty( $arguments['name'] ) ) {
				$subscriber_data['name'] = $arguments['name'];
			}

			// Check both field names for tags - form submissions send 'aweber_aweber_tags'
			$tags_value = '';
			if ( ! empty( $arguments['aweber_aweber_tags'] ) ) {
				$tags_value = $arguments['aweber_aweber_tags'];
			} elseif ( ! empty( $arguments['aweber_tags'] ) ) {
				$tags_value = $arguments['aweber_tags'];
			}

			$tags = array();
			if ( ! empty( $tags_value ) ) {
				// Handle both array format (from multi-select) and string format (comma-separated)
				if ( is_array( $tags_value ) ) {
					$tags = array_map( 'sanitize_text_field', array_map( 'trim', $tags_value ) );
				} else {
					$tags = explode( ',', trim( $tags_value, ' ,' ) );
					$tags = array_map( 'sanitize_text_field', array_map( 'trim', $tags ) );
				}
				$tags = array_filter( $tags );
			}

			if ( ! empty( $tags ) ) {
				$subscriber_data['tags'] = $tags;
			}

			// Add custom fields
			$custom_fields = array();

			// Web Form URL
			if ( isset( $arguments['url'] ) ) {
				$custom_fields['Web Form URL'] = $arguments['url'];
			}

			// Phone number
			if ( ! empty( $arguments['phone'] ) ) {
				$phone_field_name = $this->get_phone_custom_field_oauth2( $list_identifier );
				if ( ! empty( $phone_field_name ) ) {
					$custom_fields[ $phone_field_name ] = $arguments['phone'];
				}
			}

			if ( ! empty( $custom_fields ) ) {
				$subscriber_data['custom_fields'] = $custom_fields;
			}

			// Add subscriber via REST API
			$response = wp_remote_post(
				"https://api.aweber.com/1.0/accounts/{$account_id}/lists/{$list_identifier}/subscribers",
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => json_encode( $subscriber_data ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $response->get_error_message();
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			$response_data = json_decode( $response_body, true );

			// 201 = created, 209 = already subscribed
			if ( 201 === $response_code || 209 === $response_code ) {
				// Update mapped custom fields if needed
				$mapping = thrive_safe_unserialize( base64_decode( isset( $arguments['tve_mapping'] ) ? $arguments['tve_mapping'] : '' ) );
				if ( ! empty( $mapping ) || ! empty( $arguments['automator_custom_fields'] ) ) {
					$this->update_custom_fields_oauth2( $list_identifier, $arguments, $subscriber_data );
				}

				return true;
			}

			// Handle duplicate subscriber error with 400 status code
			// AWeber returns 400 with "already subscribed" message instead of 209 for duplicates
			if ( 400 === $response_code ) {
				$error_message = ( is_array( $response_data ) && isset( $response_data['error']['message'] ) ) ? $response_data['error']['message'] : '';

				// Check if error message indicates duplicate subscriber
				if ( false !== stripos( $error_message, 'already subscribed' ) || false !== stripos( $error_message, 'subscriber already exists' ) ) {
					// Find existing subscriber by email
					$find_response = wp_remote_get(
						"https://api.aweber.com/1.0/accounts/{$account_id}/lists/{$list_identifier}/subscribers?ws.op=find&email=" . rawurlencode( $subscriber_data['email'] ),
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $access_token,
								'Accept'        => 'application/json',
							),
							'timeout' => 30,
						)
					);

					if ( is_wp_error( $find_response ) ) {
						return $find_response->get_error_message();
					}

					$find_response_code = wp_remote_retrieve_response_code( $find_response );
					if ( 200 !== $find_response_code ) {
						return __( 'Failed to find existing subscriber', 'thrive-dash' );
					}

					$find_data = json_decode( wp_remote_retrieve_body( $find_response ), true );

					// Validate JSON decode was successful
					if ( ! is_array( $find_data ) ) {
						return __( 'Invalid response when searching for subscriber', 'thrive-dash' );
					}

					// Find the correct subscriber from entries by matching email
					$found_subscriber = null;
					if ( ! empty( $find_data['entries'] ) && is_array( $find_data['entries'] ) ) {
						foreach ( $find_data['entries'] as $entry ) {
							if ( isset( $entry['email'] ) && strtolower( $entry['email'] ) === strtolower( $subscriber_data['email'] ) ) {
								$found_subscriber = $entry;
								break;
							}
						}
					}

					if ( ! empty( $found_subscriber ) && ! empty( $found_subscriber['id'] ) ) {
						$subscriber_id = $found_subscriber['id'];
						$existing_tags = isset( $found_subscriber['tags'] ) && is_array( $found_subscriber['tags'] ) ? $found_subscriber['tags'] : array();

						// Re-initialize tags and custom_fields from earlier in the function for duplicate handling
						$tags = isset( $subscriber_data['tags'] ) && is_array( $subscriber_data['tags'] ) ? $subscriber_data['tags'] : array();
						$custom_fields = isset( $subscriber_data['custom_fields'] ) && is_array( $subscriber_data['custom_fields'] ) ? $subscriber_data['custom_fields'] : array();

						// Build update data
						$update_data = array();

						if ( ! empty( $arguments['name'] ) ) {
							$update_data['name'] = $arguments['name'];
						}

						if ( ! empty( $custom_fields ) ) {
							$update_data['custom_fields'] = $custom_fields;
						}

						// Handle tags - add new tags without replacing existing ones
						if ( ! empty( $tags ) ) {
							$tags_to_add = array_values( array_diff( $tags, $existing_tags ) );
							if ( ! empty( $tags_to_add ) ) {
								$update_data['tags'] = array(
									'add' => $tags_to_add,
								);
							}
						}

						// Update subscriber if there's data to update
						if ( ! empty( $update_data ) ) {
							$update_response = wp_remote_request(
								"https://api.aweber.com/1.0/accounts/{$account_id}/lists/{$list_identifier}/subscribers/{$subscriber_id}",
								array(
									'method'  => 'PATCH',
									'headers' => array(
										'Authorization' => 'Bearer ' . $access_token,
										'Content-Type'  => 'application/json',
									),
									'body'    => json_encode( $update_data ),
									'timeout' => 30,
								)
							);

							if ( is_wp_error( $update_response ) ) {
								return $update_response->get_error_message();
							}

							$update_code = wp_remote_retrieve_response_code( $update_response );

							if ( 200 !== $update_code && 209 !== $update_code ) {
								$update_body = wp_remote_retrieve_body( $update_response );
								$update_error = json_decode( $update_body, true );
								$error_msg = ( is_array( $update_error ) && isset( $update_error['error']['message'] ) ) ? $update_error['error']['message'] : __( 'Failed to update existing subscriber', 'thrive-dash' );

								return $error_msg;
							}
						}

						// Update mapped custom fields if needed
						$mapping = thrive_safe_unserialize( base64_decode( isset( $arguments['tve_mapping'] ) ? $arguments['tve_mapping'] : '' ) );
						if ( ! empty( $mapping ) || ! empty( $arguments['automator_custom_fields'] ) ) {
							$this->update_custom_fields_oauth2( $list_identifier, $arguments, $subscriber_data );
						}

						return true;
					} else {
						// Subscriber exists but couldn't be found via API - return success since they're already subscribed
						return true;
					}
				}
			}

			// Handle other errors
			$error_message = ( is_array( $response_data ) && isset( $response_data['error']['message'] ) ) ? $response_data['error']['message'] : __( 'Failed to add subscriber', 'thrive-dash' );

			return $error_message;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Get phone custom field name for OAuth 2.0
	 *
	 * @param string $list_identifier
	 *
	 * @return string|bool
	 */
	protected function get_phone_custom_field_oauth2( $list_identifier ) {
		$custom_fields = $this->get_custom_fields_oauth2( $list_identifier );

		foreach ( $custom_fields as $field ) {
			if ( ! empty( $field['name'] ) && false !== stripos( $field['name'], 'phone' ) ) {
				return $field['name'];
			}
		}

		return false;
	}

	/**
	 * Update custom fields for OAuth 2.0
	 *
	 * @param string $list_identifier
	 * @param array  $arguments
	 * @param array  $subscriber_data
	 *
	 * @return bool
	 */
	protected function update_custom_fields_oauth2( $list_identifier, $arguments, $subscriber_data ) {
		$access_token = $this->param( 'access_token' );
		$account_id   = $this->param( 'account_id' );

		if ( empty( $access_token ) || empty( $account_id ) || empty( $subscriber_data['email'] ) ) {
			return false;
		}

		try {
			// Get subscriber ID first
			$response = wp_remote_get(
				"https://api.aweber.com/1.0/accounts/{$account_id}/lists/{$list_identifier}/subscribers?ws.op=find&email=" . urlencode( $subscriber_data['email'] ),
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( empty( $response_data['entries'] ) || empty( $response_data['entries'][0]['id'] ) ) {
				return false;
			}

			$subscriber_id = $response_data['entries'][0]['id'];

			// Build custom fields
			if ( empty( $arguments['automator_custom_fields'] ) ) {
				$custom_fields = $this->buildMappedCustomFields( $list_identifier, $arguments );
			} else {
				$custom_fields = $arguments['automator_custom_fields'];
			}

			if ( empty( $custom_fields ) ) {
				return false;
			}

			// Update subscriber with custom fields
			$update_response = wp_remote_request(
				"https://api.aweber.com/1.0/accounts/{$account_id}/lists/{$list_identifier}/subscribers/{$subscriber_id}",
				array(
					'method'  => 'PATCH',
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Content-Type'  => 'application/json',
					),
					'body'    => json_encode( array(
						'custom_fields' => $custom_fields,
					) ),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $update_response ) ) {
				$this->api_log_error( $list_identifier, $custom_fields, __( 'Could not update custom fields', 'thrive-dash' ) );
				return false;
			}

			$response_code = wp_remote_retrieve_response_code( $update_response );

			return 200 === $response_code || 209 === $response_code;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Check if IP address is from a reserved block
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	protected function is_reserved_ip( $ip ) {
		if ( empty( $ip ) ) {
			return true;
		}

		// Check for IPv4 reserved blocks (localhost, private networks, etc.)
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
		}

		// Check for IPv6 reserved blocks
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return true;
			}
		}

		return false;
	}

	protected function phoneCustomFieldExists( $list ) {
		$customFieldsURL = $list->custom_fields_collection_link;
		$customFields    = $list->loadFromUrl( $customFieldsURL );
		foreach ( $customFields as $custom ) {
			if ( false !== stripos( $custom->name, 'phone' ) ) {
				//return the name of the phone custom field cos users can set its name as: Phone/phone/pHone/etc
				//used in custom_fields for subscribers parameters
				/** @see add_subscriber */
				return $custom->name;
			}
		}

		return false;
	}

	/**
	 * output any (possible) extra editor settings for this API
	 *
	 * @param array $params allow various different calls to this method
	 * @param bool  $force  force refresh from API
	 */
	public function get_extra_settings( $params = array(), $force = false ) {
		$settings = $params;

		// Add tags to extra_settings for TCB UI compatibility
		// TCB JavaScript (apis.js) expects tags in extra_settings.tags as object format
		if ( $this->has_tags() ) {
			$tags = $this->_get_tags();

			// Convert tags array to object format expected by JavaScript
			// Format: { 'tagName1': 'tagName1', 'tagName2': 'tagName2' }
			$tags_object = array();
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag['id'] ) ) {
					$tags_object[ $tag['id'] ] = $tag['text'];
				}
			}

			$settings['tags'] = $tags_object;
		}

		return $settings;
	}

	/**
	 * output any (possible) extra editor settings for this API
	 *
	 * @param array $params allow various different calls to this method
	 */
	public function render_extra_editor_settings( $params = array() ) {
		$this->output_controls_html( 'aweber/tags', $params );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '{!email}';
	}

	/**
	 * @param array $params  which may contain `list_id`
	 * @param bool  $force   make a call to API and invalidate cache
	 * @param bool  $get_all where to get lists with their custom fields
	 *
	 * @return array
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = true ) {

		$lists = $this->get_all_custom_fields( $force );

		// Get custom fields for all list ids [used on localize in TAr]
		if ( true === $get_all ) {
			return $lists;
		}

		$list_id = isset( $params['list_id'] ) ? $params['list_id'] : null;

		if ( '0' === $list_id ) {
			$list_id = current( array_keys( $lists ) );
		}

		return array( $list_id => $lists[ $list_id ] );
	}

	/**
	 * Get all custom fields by list id
	 *
	 * @param $force calls the API and invalidate cache
	 *
	 * @return array|mixed
	 */
	public function get_all_custom_fields( $force ) {

		// Serve from cache if exists and requested
		$cached_data = $this->get_cached_custom_fields();

		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$custom_fields = array();
		$lists         = $this->_get_lists();

		if ( is_array( $lists ) ) {
			foreach ( $lists as $list ) {

				if ( empty( $list['id'] ) ) {
					continue;
				}

				$custom_fields[ $list['id'] ] = $this->getCustomFieldsByListId( $list['id'] );
			}
		}

		$this->_save_custom_fields( $custom_fields );

		return $custom_fields;
	}

	/**
	 * Get custom fields by list id
	 *
	 * @param $list_id
	 *
	 * @return array
	 */
	public function getCustomFieldsByListId( $list_id ) {

		$fields = array();

		if ( empty( $list_id ) ) {
			return $fields;
		}

		// Use middleman for middleman OAuth
		if ( self::OAUTH_VERSION_MIDDLEMAN === $this->param( 'oauth_version' ) ) {
			return $this->get_custom_fields_middleman( $list_id );
		}

		// Use OAuth 2.0 API if connected via OAuth 2.0
		if ( '2.0' === $this->param( 'oauth_version' ) ) {
			return $this->get_custom_fields_oauth2( $list_id );
		}

		// OAuth 1.0 flow
		try {
			$account  = $this->get_api()->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );
			$list_url = "/accounts/{$account->id}/lists/{$list_id}";
			$list_obj = $account->loadFromUrl( $list_url );

			// CF obj
			$custom_fields_url = $list_obj->custom_fields_collection_link;
			$custom_fields     = $list_obj->loadFromUrl( $custom_fields_url );

			foreach ( $custom_fields as $custom_field ) {

				if ( ! empty( $custom_field->data['name'] ) && ! empty( $custom_field->data['id'] ) ) {

					$fields[] = $this->_normalize_custom_field( $custom_field->data );
				}
			}
		} catch ( Thrive_Dash_Api_AWeber_Exception $e ) {
		}

		return $fields;
	}

	/**
	 * Get custom fields using OAuth 2.0 API
	 *
	 * @param string $list_id
	 *
	 * @return array
	 */
	protected function get_custom_fields_oauth2( $list_id ) {
		$fields       = array();
		$access_token = $this->param( 'access_token' );
		$account_id   = $this->param( 'account_id' );

		if ( empty( $access_token ) || empty( $account_id ) ) {
			return $fields;
		}

		try {
			$response = wp_remote_get(
				'https://api.aweber.com/1.0/accounts/' . $account_id . '/lists/' . $list_id . '/custom_fields',
				array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $access_token,
						'Accept'        => 'application/json',
					),
					'timeout' => 30,
				)
			);

			if ( is_wp_error( $response ) ) {
				return $fields;
			}

			$response_data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! empty( $response_data['entries'] ) && is_array( $response_data['entries'] ) ) {
				foreach ( $response_data['entries'] as $custom_field ) {
					if ( ! empty( $custom_field['name'] ) && ! empty( $custom_field['id'] ) ) {
						$fields[] = $this->_normalize_custom_field( $custom_field );
					}
				}
			}
		} catch ( Exception $e ) {
			// Silently fail
		}

		return $fields;
	}

	/**
	 * Normalize custom field data
	 *
	 * @param $field
	 *
	 * @return array
	 */
	protected function _normalize_custom_field( $field ) {

		$field = (array) $field;

		return array(
			'id'    => isset( $field['id'] ) ? $field['id'] : '',
			'name'  => ! empty( $field['name'] ) ? $field['name'] : '',
			'type'  => '', // API does not have type
			'label' => ! empty( $field['name'] ) ? $field['name'] : '',
		);
	}

	/**
	 * Append custom fields to defaults
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function get_custom_fields( $params = array() ) {

		return array_merge( parent::get_custom_fields(), $this->_mapped_custom_fields );
	}

	/**
	 * Call the API in order to update subscriber's custom fields
	 *
	 * @param $list_identifier
	 * @param $arguments
	 * @param $data
	 *
	 * @return bool
	 */
	public function updateCustomFields( $list_identifier, $arguments, $data ) {
		if ( ! $list_identifier || empty( $arguments ) || empty( $data['email'] ) ) {
			return false;
		}
		$saved = false;

		/** @var Thrive_Dash_Api_AWeber $aweber */
		$aweber   = $this->get_api();
		$account  = $aweber->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );
		$list_url = "/accounts/{$account->id}/lists/{$list_identifier}";
		$list     = $account->loadFromUrl( $list_url );

		if ( empty( $arguments['automator_custom_fields'] ) ) {
			$custom_fields = $this->buildMappedCustomFields( $list_identifier, $arguments );
		} else {
			$custom_fields = $arguments['automator_custom_fields'];
		}

		$existing_subscribers = $list->subscribers->find( array( 'email' => $data['email'] ) );
		if ( $existing_subscribers && 1 === $existing_subscribers->count() && ! empty( $custom_fields ) ) {
			$subscriber                = $existing_subscribers->current();
			$subscriber->custom_fields = $custom_fields;
			$saved                     = $subscriber->save();
		}

		if ( ! $saved ) {
			$this->api_log_error( $list_identifier, $custom_fields, __( 'Could not update custom fields', 'thrive-dash' ) );
		}

		return $saved;
	}

	/**
	 * Creates and prepare the mapping data from the subscription form
	 *
	 * @param       $list_identifier
	 * @param       $args
	 * @param array $custom_fields
	 *
	 * @return array
	 */
	public function buildMappedCustomFields( $list_identifier, $args, $custom_fields = array() ) {

		if ( empty( $args['tve_mapping'] ) || ! tve_dash_is_bas64_encoded( $args['tve_mapping'] ) || ! is_serialized( base64_decode( $args['tve_mapping'] ) ) ) {
			return $custom_fields;
		}

		$mapped_form_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );

		if ( is_array( $mapped_form_data ) && $list_identifier ) {
			$api_custom_fields = $this->buildCustomFieldsList();

			// Loop trough allowed custom fields names
			foreach ( $this->get_mapped_field_ids() as $mapped_field_name ) {

				// Extract an array with all custom fields (siblings) names from the form data
				// {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}
				$cf_form_fields = preg_grep( "#^{$mapped_field_name}#i", array_keys( $mapped_form_data ) );

				// Matched "form data" for current allowed name
				if ( ! empty( $cf_form_fields ) && is_array( $cf_form_fields ) ) {

					// Pull form allowed data, sanitize it and build the custom fields array
					foreach ( $cf_form_fields as $cf_form_name ) {

						if ( empty( $mapped_form_data[ $cf_form_name ][ $this->_key ] ) ) {
							continue;
						}

						$args[ $cf_form_name ] = $this->process_field( $args[ $cf_form_name ] );

						$mapped_form_field_id = $mapped_form_data[ $cf_form_name ][ $this->_key ];
						$field_label          = $api_custom_fields[ $list_identifier ][ $mapped_form_field_id ];

						$cf_form_name = str_replace( '[]', '', $cf_form_name );
						if ( ! empty( $args[ $cf_form_name ] ) ) {
							$args[ $cf_form_name ]         = $this->process_field( $args[ $cf_form_name ] );
							$custom_fields[ $field_label ] = sanitize_text_field( $args[ $cf_form_name ] );
						}
					}
				}
			}
		}

		return $custom_fields;
	}


	/**
	 * Build custom fields mapping for automations
	 *
	 * @param $automation_data
	 *
	 * @return array
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();
		if ( $automation_data['mailing_list'] ) {
			$api_custom_fields = $this->buildCustomFieldsList();

			foreach ( $automation_data['api_fields'] as $pair ) {
				$value = sanitize_text_field( $pair['value'] );
				if ( $value ) {
					$field_label                 = $api_custom_fields[ $automation_data['mailing_list'] ][ $pair['key'] ];
					$mapped_data[ $field_label ] = $value;
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Create a simpler structure with [list_id] => [ field_id => field_name]
	 *
	 * @return array
	 */
	public function buildCustomFieldsList() {

		$parsed = array();

		foreach ( $this->get_all_custom_fields( false ) as $list_id => $merge_field ) {
			array_map(
				function ( $var ) use ( &$parsed, $list_id ) {
					$parsed[ $list_id ][ $var['id'] ] = $var['name'];
				},
				$merge_field
			);
		}

		return $parsed;
	}

	/**
	 * @param       $email
	 * @param array $custom_fields
	 * @param array $extra
	 *
	 * @return false|int
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {

		try {
			/** @var Thrive_Dash_Api_AWeber $api */
			$api     = $this->get_api();
			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;
			$args    = array(
				'email' => $email,
			);

			if ( ! empty( $extra['name'] ) ) {
				$args['name'] = $extra['name'];
			}

			$this->add_subscriber( $list_id, $args );

			$account  = $api->getAccount( $this->param( 'token' ), $this->param( 'secret' ) );
			$list_url = "/accounts/{$account->id}/lists/{$list_id}";
			$list     = $account->loadFromUrl( $list_url );

			$existing_subscribers = $list->subscribers->find( array( 'email' => $email ) );

			if ( $existing_subscribers && 1 === $existing_subscribers->count() ) {
				$subscriber      = $existing_subscribers->current();
				$prepared_fields = $this->prepare_custom_fields_for_api( $custom_fields, $list_id );

				$subscriber->custom_fields = array_merge( $subscriber->data['custom_fields'], $prepared_fields );

				$subscriber->save();

				return $subscriber->id;
			}

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Prepare custom fields for api call
	 *
	 * @param array $custom_fields
	 * @param null  $list_identifier
	 *
	 * @return array
	 */
	protected function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {

		if ( empty( $list_identifier ) ) { // list identifier required here
			return array();
		}

		$api_fields = $this->get_api_custom_fields( array( 'list_id' => $list_identifier ), true );

		if ( empty( $api_fields[ $list_identifier ] ) ) {
			return array();
		}

		$prepared_fields = array();

		// Optimization: Create lookup array to avoid nested loop O(n*m) → O(n+m)
		// Convert custom_fields to associative array with integer keys for fast lookup
		$custom_fields_lookup = array();
		foreach ( $custom_fields as $key => $value ) {
			if ( ! empty( $value ) ) {
				$custom_fields_lookup[ (int) $key ] = $value;
			}
		}

		// Single loop: O(n) instead of nested O(n*m)
		foreach ( $api_fields[ $list_identifier ] as $field ) {
			$field_id = (int) $field['id'];

			// Direct lookup is O(1) instead of inner loop O(m)
			if ( isset( $custom_fields_lookup[ $field_id ] ) ) {
				$prepared_fields[ $field['name'] ] = $custom_fields_lookup[ $field_id ];
			}
		}

		return $prepared_fields;
	}

	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list' => array( 'api_fields' ), 'tag_input' => array() ) );
	}

	public function get_automator_tag_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'tag_input' ) );
	}

	public function has_custom_fields() {
		return true;
	}

	/**
	 * ==========================================
	 * OAuth 2.0 Middleman Support (Streamlined)
	 * ==========================================
	 */

	/**
	 * Middleman client instance (lazy loaded)
	 *
	 * @var Thrive_Dash_Api_AWeber_OAuth2_Middleman|null
	 */
	private $middleman_client = null;

	/**
	 * Get middleman client instance (lazy loaded)
	 *
	 * @since  4.0.0
	 * @return Thrive_Dash_Api_AWeber_OAuth2_Middleman|false Middleman client or false on failure
	 */
	private function get_middleman_client() {
		if ( null === $this->middleman_client ) {
			// Include the middleman class file from same directory
			$middleman_file = __DIR__ . '/AWeber_OAuth2_Middleman.php';

			if ( ! file_exists( $middleman_file ) ) {
				return false;
			}

			// Use include_once with error suppression to prevent fatal errors
			// if the file has syntax errors or other issues
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Intentional to prevent fatal errors on corrupted file
			$loaded = @include_once $middleman_file;

			if ( false === $loaded || ! class_exists( 'Thrive_Dash_Api_AWeber_OAuth2_Middleman' ) ) {
				if ( function_exists( 'error_log' ) ) {
					error_log( 'AWeber Middleman: Failed to load OAuth2 Middleman class file' );
				}
				return false;
			}

			$this->middleman_client = new Thrive_Dash_Api_AWeber_OAuth2_Middleman();
		}

		return $this->middleman_client;
	}

	/**
	 * Check if middleman OAuth is supported
	 *
	 * @since  4.0.0
	 * @return bool Always true for AWeber
	 */
	public function supports_middleman_oauth() {
		return true;
	}


	/**
	 * Get middleman connection info
	 *
	 * @since  4.0.0
	 * @return array Connection info or empty array
	 */
	public function get_middleman_connection_info() {
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return array();
		}

		return $client->get_connection_info();
	}

	/**
	 * Get lists via middleman (helper method for _get_lists)
	 *
	 * @since  4.0.0
	 * @return array|bool Array of lists or false on failure
	 */
	private function get_lists_middleman() {
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			$this->_error = __( 'Middleman client not available', 'thrive-dash' );
			return false;
		}

		$account_id = $client->get_account_id();

		if ( empty( $account_id ) ) {
			$this->_error = __( 'Account ID not found', 'thrive-dash' );
			return false;
		}

		// Proxy request to get lists
		$result = $client->proxy_request( 'GET', "accounts/{$account_id}/lists" );

		if ( empty( $result['success'] ) ) {
			$this->_error = $result['error'] ?? __( 'Failed to fetch lists', 'thrive-dash' );
			return false;
		}

		$entries = $result['data']['entries'] ?? array();

		if ( empty( $entries ) ) {
			return array();
		}

		$lists = array();
		foreach ( $entries as $item ) {
			if ( ! empty( $item['id'] ) && ! empty( $item['name'] ) ) {
				$lists[] = array(
					'id'   => $item['id'],
					'name' => $item['name'],
				);
			}
		}

		return $lists;
	}

	/**
	 * Get tags via middleman (helper method for _get_tags)
	 *
	 * @since  4.0.0
	 * @return array Array of tags
	 */
	private function get_tags_middleman() {
		$tags   = array();
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return $tags;
		}

		$account_id = $client->get_account_id();

		if ( empty( $account_id ) ) {
			return $tags;
		}

		// Check cache first
		$cache_key   = 'aweber_tags_middleman_' . md5( $account_id );
		$cached_tags = get_transient( $cache_key );

		if ( false !== $cached_tags && is_array( $cached_tags ) ) {
			return $cached_tags;
		}

		// Fetch all lists first
		$lists = $this->_get_lists();

		if ( empty( $lists ) || ! is_array( $lists ) ) {
			return $tags;
		}

		$all_tags = array();

		// Fetch tags from each list
		foreach ( $lists as $list ) {
			if ( empty( $list['id'] ) ) {
				continue;
			}

			$result = $client->proxy_request( 'GET', "accounts/{$account_id}/lists/{$list['id']}/tags" );

			if ( empty( $result['success'] ) ) {
				continue;
			}

			$tag_entries = $result['data'] ?? array();

			if ( ! is_array( $tag_entries ) ) {
				continue;
			}

			// AWeber returns tags as simple array of strings
			foreach ( $tag_entries as $tag_name ) {
				if ( ! empty( $tag_name ) && is_string( $tag_name ) ) {
					// Use tag name as key to avoid duplicates
					$all_tags[ $tag_name ] = array(
						'id'   => $tag_name,
						'text' => $tag_name,
					);
				}
			}
		}

		// Convert to indexed array and sort
		$tags = array_values( $all_tags );
		usort( $tags, function ( $a, $b ) {
			return strcasecmp( $a['text'] ?? '', $b['text'] ?? '' );
		} );

		// Cache for 5 minutes
		set_transient( $cache_key, $tags, 5 * MINUTE_IN_SECONDS );

		return $tags;
	}

	/**
	 * Add subscriber via middleman (helper method for add_subscriber)
	 *
	 * @since 4.0.0
	 *
	 * @param string $list_identifier List ID
	 * @param array  $arguments       Subscriber data
	 *
	 * @return bool|string True on success, error message on failure
	 */
	private function add_subscriber_middleman( $list_identifier, $arguments ) {
		// Early validation - check required email
		if ( empty( $arguments['email'] ) ) {
			return __( 'Email address is required', 'thrive-dash' );
		}

		$email = sanitize_email( $arguments['email'] );
		if ( ! is_email( $email ) ) {
			return __( 'Invalid email address', 'thrive-dash' );
		}

		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return __( 'Middleman client not available', 'thrive-dash' );
		}

		$account_id = $client->get_account_id();

		if ( empty( $account_id ) ) {
			return __( 'Account ID not found', 'thrive-dash' );
		}

		// Prepare subscriber data
		$subscriber_data = array(
			'email' => $email,
		);

		// Add IP address if not reserved
		$ip_address = tve_dash_get_ip();
		if ( ! empty( $ip_address ) && ! $this->is_reserved_ip( $ip_address ) ) {
			$subscriber_data['ip_address'] = $ip_address;
		}

		// Add name
		if ( ! empty( $arguments['name'] ) ) {
			$subscriber_data['name'] = $arguments['name'];
		}

		// Add tags
		$tags = array();
		$tags_value = '';
		if ( ! empty( $arguments['aweber_aweber_tags'] ) ) {
			$tags_value = $arguments['aweber_aweber_tags'];
		} elseif ( ! empty( $arguments['aweber_tags'] ) ) {
			$tags_value = $arguments['aweber_tags'];
		}

		if ( ! empty( $tags_value ) ) {
			// Handle both array format (from multi-select) and string format (comma-separated)
			if ( is_array( $tags_value ) ) {
				$tags = array_map( 'sanitize_text_field', array_map( 'trim', $tags_value ) );
			} else {
				$tags = explode( ',', trim( $tags_value, ' ,' ) );
				$tags = array_map( 'sanitize_text_field', array_map( 'trim', $tags ) );
			}
			$tags                    = array_filter( $tags );
			$subscriber_data['tags'] = $tags;
		}

		// Add custom fields
		$custom_fields = array();

		if ( isset( $arguments['url'] ) ) {
			$custom_fields['Web Form URL'] = $arguments['url'];
		}

		if ( ! empty( $arguments['phone'] ) ) {
			$phone_field_name = $this->get_phone_custom_field_middleman( $list_identifier, $account_id );
			if ( ! empty( $phone_field_name ) ) {
				$custom_fields[ $phone_field_name ] = $arguments['phone'];
			}
		}

		// Process mapped custom fields from form builder (tve_mapping parameter)
		$mapping = thrive_safe_unserialize( base64_decode( isset( $arguments['tve_mapping'] ) ? $arguments['tve_mapping'] : '' ) );
		if ( ! empty( $mapping ) ) {
			$mapped_fields = $this->buildMappedCustomFields( $list_identifier, $arguments, $custom_fields );
			if ( ! empty( $mapped_fields ) && is_array( $mapped_fields ) ) {
				$custom_fields = array_merge( $custom_fields, $mapped_fields );
			}
		}

		// Process automator custom fields (from Thrive Automator)
		if ( ! empty( $arguments['automator_custom_fields'] ) && is_array( $arguments['automator_custom_fields'] ) ) {
			$custom_fields = array_merge( $custom_fields, $arguments['automator_custom_fields'] );
		}

		if ( ! empty( $custom_fields ) ) {
			$subscriber_data['custom_fields'] = $custom_fields;
		}

		// Add subscriber via proxy
		$result = $client->proxy_request( 'POST', "accounts/{$account_id}/lists/{$list_identifier}/subscribers", $subscriber_data );

		if ( empty( $result['success'] ) ) {
			$error_message = $result['error'] ?? __( 'Failed to add subscriber', 'thrive-dash' );

			// Check if error indicates duplicate subscriber
			if ( false !== stripos( $error_message, 'already subscribed' ) || false !== stripos( $error_message, 'subscriber already exists' ) ) {
				// Find existing subscriber by email
				$find_result = $client->proxy_request( 'GET', "accounts/{$account_id}/lists/{$list_identifier}/subscribers?ws.op=find&email=" . rawurlencode( $subscriber_data['email'] ) );

				// Find the correct subscriber from entries (proxy may return all subscribers)
				$found_subscriber = null;
				if ( ! empty( $find_result['success'] ) && is_array( $find_result['data'] ) && ! empty( $find_result['data']['entries'] ) && is_array( $find_result['data']['entries'] ) ) {
					foreach ( $find_result['data']['entries'] as $entry ) {
						if ( isset( $entry['email'] ) && strtolower( $entry['email'] ) === strtolower( $subscriber_data['email'] ) ) {
							$found_subscriber = $entry;
							break;
						}
					}
				}

				if ( ! empty( $found_subscriber ) && ! empty( $found_subscriber['id'] ) ) {
					$subscriber_id = $found_subscriber['id'];
					$existing_tags = isset( $found_subscriber['tags'] ) && is_array( $found_subscriber['tags'] ) ? $found_subscriber['tags'] : array();

					// Build update data
					$update_data = array();

					if ( ! empty( $arguments['name'] ) ) {
						$update_data['name'] = $arguments['name'];
					}

					if ( ! empty( $custom_fields ) ) {
						$update_data['custom_fields'] = $custom_fields;
					}

					// Handle tags - add new tags without replacing existing ones
					if ( ! empty( $tags ) ) {
						$tags_to_add = array_values( array_diff( $tags, $existing_tags ) );
						if ( ! empty( $tags_to_add ) ) {
							$update_data['tags'] = array(
								'add' => $tags_to_add,
							);
						}
					}

					// Update subscriber if there's data to update
					if ( ! empty( $update_data ) ) {
						$update_result = $client->proxy_request( 'PATCH', "accounts/{$account_id}/lists/{$list_identifier}/subscribers/{$subscriber_id}", $update_data );

						if ( empty( $update_result['success'] ) ) {
							$update_error = $update_result['error'] ?? __( 'Failed to update existing subscriber', 'thrive-dash' );
							return $update_error;
						}
					}

					return true;
				} else {
					// Subscriber exists but couldn't be found via API - return success since they're already subscribed
					return true;
				}
			}

			return $error_message;
		}

		return true;
	}

	/**
	 * Get custom fields via middleman
	 *
	 * @since 4.0.0
	 *
	 * @param string $list_id List ID
	 *
	 * @return array Array of custom fields
	 */
	private function get_custom_fields_middleman( $list_id ) {
		$fields = array();
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return $fields;
		}

		$account_id = $client->get_account_id();

		if ( empty( $account_id ) ) {
			return $fields;
		}

		// Fetch custom fields via proxy
		$result = $client->proxy_request( 'GET', "accounts/{$account_id}/lists/{$list_id}/custom_fields" );

		if ( empty( $result['success'] ) ) {
			return $fields;
		}

		$entries = $result['data']['entries'] ?? array();

		if ( ! is_array( $entries ) ) {
			return $fields;
		}

		foreach ( $entries as $custom_field ) {
			if ( ! empty( $custom_field['name'] ) && ! empty( $custom_field['id'] ) ) {
				$fields[] = $this->_normalize_custom_field( $custom_field );
			}
		}

		return $fields;
	}

	/**
	 * Get phone custom field via middleman
	 *
	 * @since 4.0.0
	 *
	 * @param string $list_identifier List ID
	 * @param string $account_id      Account ID
	 *
	 * @return string|false Phone field name or false
	 */
	private function get_phone_custom_field_middleman( $list_identifier, $account_id ) {
		$client = $this->get_middleman_client();

		if ( false === $client ) {
			return false;
		}

		// Fetch custom fields
		$result = $client->proxy_request( 'GET', "accounts/{$account_id}/lists/{$list_identifier}/custom_fields" );

		if ( empty( $result['success'] ) ) {
			return false;
		}

		$entries = $result['data']['entries'] ?? array();

		foreach ( $entries as $field ) {
			if ( ! empty( $field['name'] ) && false !== stripos( $field['name'], 'phone' ) ) {
				return $field['name'];
			}
		}

		return false;
	}
}

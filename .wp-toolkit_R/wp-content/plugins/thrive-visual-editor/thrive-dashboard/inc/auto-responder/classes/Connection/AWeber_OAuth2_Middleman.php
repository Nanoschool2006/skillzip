<?php
/**
 * Thrive Themes - AWeber OAuth 2.0 Middleman API Client
 *
 * Handles OAuth 2.0 authentication via Thrive's middleman API at thrivethemesapi.com
 * This client manages the complete OAuth flow and proxies all AWeber API requests
 * through the middleman API which handles token storage and refresh.
 *
 * @package Thrive Dashboard
 * @since 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_Api_AWeber_OAuth2_Middleman {

	/**
	 * Middleman API base URL
	 *
	 * @var string
	 */
	private $api_url = 'https://thrivethemesapi.com/api/integrations/v1';

	/**
	 * Base URL for authentication endpoints
	 *
	 * @var string
	 */
	private $auth_url = 'https://thrivethemesapi.com';

	/**
	 * API token for authentication
	 *
	 * @var string
	 */
	private $api_token;

	/**
	 * Site identifier used across all requests
	 *
	 * @var string
	 */
	private $site_id;

	/**
	 * Integration name
	 *
	 * @var string
	 */
	private $integration = 'aweber';

	/**
	 * Flag to prevent repeated account ID fetch attempts
	 *
	 * @var bool
	 */
	private $account_id_fetch_attempted = false;

	/**
	 * Constructor
	 *
	 * @param string $api_token Optional API token. If not provided, will try to get from options.
	 */
	public function __construct( $api_token = null ) {
		$this->api_token = ! empty( $api_token ) ? $api_token : $this->get_api_token();
		$this->site_id   = $this->get_site_id();

		// Validate critical properties to prevent fatal errors
		if ( empty( $this->site_id ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AWeber Middleman: get_site_url() returned empty value, WordPress configuration may be invalid' );
		}
	}

	/**
	 * Get or generate the site identifier
	 *
	 * Returns the site URL as the identifier to match tpa-plugin-v2 behavior.
	 * The Laravel API expects the raw site URL, not a hash.
	 *
	 * @since 4.0.0
	 * @return string Site identifier (site URL)
	 */
	public function get_site_id() {
		// Use site URL directly (not hashed) to match tpa-plugin-v2 implementation
		// The Laravel API expects the raw URL as the site identifier
		return get_site_url();
	}

	/**
	 * Validate prerequisites for OAuth connection
	 *
	 * Checks all requirements needed for successful OAuth flow and API communication.
	 * Returns detailed error messages for any issues found.
	 *
	 * @since 4.0.0
	 * @return array Array with 'valid' => true/false and 'errors' => array of error messages
	 */
	public function validate_prerequisites() {
		$errors = array();

		// Check if API token exists and is not empty
		if ( empty( $this->api_token ) ) {
			$errors[] = 'Thrive API token is missing. Please ensure your Thrive license is activated.';
		}

		// Check if site_id (site URL) is valid
		if ( empty( $this->site_id ) ) {
			$errors[] = 'Site URL is not configured. Please check your WordPress site URL settings.';
		} else {
			// Check if URL structure is valid
			$parsed_url = wp_parse_url( $this->site_id );

			if ( ! isset( $parsed_url['scheme'] ) || ! isset( $parsed_url['host'] ) ) {
				$errors[] = 'Site URL format is invalid. Please ensure your WordPress site URL is properly configured.';
			} else {
				// Check for localhost or local IPs in production context
				$host = strtolower( $parsed_url['host'] );
				if ( $host === 'localhost' || strpos( $host, '127.0.0.1' ) !== false || strpos( $host, '::1' ) !== false ) {
					$errors[] = 'Cannot connect from localhost. AWeber OAuth requires a publicly accessible site URL.';
				}

				// Check for common local development domains
				$local_tlds = array( '.local', '.test', '.dev', '.localhost' );
				foreach ( $local_tlds as $tld ) {
					if ( substr( $host, - strlen( $tld ) ) === $tld ) {
						$errors[] = sprintf( 'Cannot connect from local development domain (%s). AWeber OAuth requires a publicly accessible site URL.', esc_html( $host ) );
						break;
					}
				}
			}
		}

		// Check if outbound HTTPS connections are possible (basic connectivity test)
		$test_response = wp_remote_get( 'https://thrivethemesapi.com', array( 'timeout' => 5 ) );
		if ( is_wp_error( $test_response ) ) {
			$error_message = $test_response->get_error_message();

			if ( strpos( $error_message, 'SSL' ) !== false || strpos( $error_message, 'certificate' ) !== false ) {
				$errors[] = 'SSL certificate verification failed. Your server may have outdated SSL certificates. Please contact your hosting provider.';
			} elseif ( strpos( $error_message, 'timed out' ) !== false ) {
				$errors[] = 'Connection to Thrive API server timed out. Please check your internet connection and firewall settings.';
			} else {
				$errors[] = 'Cannot connect to Thrive API server. Please ensure your server can make outbound HTTPS requests.';
			}
		}

		return array(
			'valid'  => empty( $errors ),
			'errors' => $errors,
		);
	}

	/**
	 * Validate API token against the API
	 *
	 * Makes a lightweight API call to check if the token is still valid.
	 * Returns the HTTP status code to detect expired tokens (401).
	 *
	 * @since 10.9.beta
	 * @return array Response with 'valid' boolean and optional 'http_status' for debugging
	 */
	public function validate_api_token() {
		// Early validation - token must exist
		if ( empty( $this->api_token ) ) {
			return array(
				'valid'       => false,
				'error'       => 'API token is missing',
				'http_status' => null,
			);
		}

		// Use /api/user endpoint - lightweight Sanctum token validation
		$url = $this->auth_url . '/api/user';

		$args = array(
			'method'  => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			),
			'timeout' => 10,
		);

		$response = wp_remote_request( $url, $args );

		// Handle WordPress errors (network issues)
		if ( is_wp_error( $response ) ) {
			return array(
				'valid'       => false,
				'error'       => $response->get_error_message(),
				'http_status' => null,
			);
		}

		$http_status = wp_remote_retrieve_response_code( $response );

		// 200-299 = valid token
		if ( $http_status >= 200 && $http_status < 300 ) {
			return array(
				'valid'       => true,
				'http_status' => $http_status,
			);
		}

		// 401 = expired/invalid token
		if ( 401 === $http_status ) {
			return array(
				'valid'       => false,
				'error'       => 'Token is expired or invalid',
				'http_status' => 401,
			);
		}

		// Other error statuses
		return array(
			'valid'       => false,
			'error'       => 'API validation failed',
			'http_status' => $http_status,
		);
	}

	/**
	 * Get the API token
	 *
	 * Retrieves the Thrive Product API token from WordPress options.
	 * If not found, attempts to authenticate and get a new token.
	 *
	 * @since 4.0.0
	 * @return string Empty string if token is missing (logs warning)
	 */
	public function get_api_token() {
		// First, check if token is defined as a constant (for local override)
		if ( defined( 'THRIVE_MIDDLEMAN_API_TOKEN' ) ) {
			return THRIVE_MIDDLEMAN_API_TOKEN;
		}

		// Primary: Get from WordPress options
		$api_token = get_option( 'thrive_api_token' );

		if ( ! empty( $api_token ) ) {
			return $this->maybe_decrypt_token( $api_token );
		}

		// Try to authenticate and get a new token
		$api_token = $this->authenticate();

		if ( ! empty( $api_token ) ) {
			return $api_token;
		}

		// Fallback: Try thrive_middleman_api_token option for backward compatibility
		$api_token = get_option( 'thrive_middleman_api_token' );

		if ( ! empty( $api_token ) ) {
			return $this->maybe_decrypt_token( $api_token );
		}

		// Log warning for debugging
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AWeber Middleman: All token retrieval methods failed - no API token available' );
		}

		return '';
	}

	/**
	 * Decrypt token if encryption helper is available
	 *
	 * Handles both encrypted and plain text tokens for backward compatibility.
	 *
	 * @since 4.0.0
	 * @param string $token Token that may be encrypted
	 * @return string Decrypted token or original if decryption unavailable/fails
	 */
	private function maybe_decrypt_token( $token ) {
		if ( empty( $token ) ) {
			return $token;
		}

		// Check if encryption helper is available
		if ( ! class_exists( 'Thrive_Dash_Encryption_Helper' ) || ! method_exists( 'Thrive_Dash_Encryption_Helper', 'decrypt' ) ) {
			return $token;
		}

		$decrypted = Thrive_Dash_Encryption_Helper::decrypt( $token );

		// If decryption returns empty/false, token might be stored unencrypted (legacy)
		// In that case, return the original token
		if ( empty( $decrypted ) ) {
			return $token;
		}

		return $decrypted;
	}

	/**
	 * Fetch API key from Laravel API endpoint
	 *
	 * @since 4.0.0
	 * @return string|false API key on success, false on failure
	 */
	public function fetch_api_key() {
		$url = $this->auth_url . '/api/secrets/v1/api_key_thrive_token';

		$request_args = array(
			'timeout' => 10,
			'headers' => array(
				'Accept' => 'application/json',
			),
		);

		$response = wp_remote_get( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Failed to fetch API key - ' . $response->get_error_message() );
			}
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: API key fetch returned status ' . $response_code );
			}
			return false;
		}

		$data = json_decode( $response_body, true );

		// Validate JSON decode was successful
		if ( null === $data || JSON_ERROR_NONE !== json_last_error() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: API key fetch JSON decode failed' );
			}
			return false;
		}

		if ( isset( $data['success'] ) && true === $data['success'] && isset( $data['data']['value'] ) ) {
			return $data['data']['value'];
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AWeber Middleman: API key fetch returned unexpected response structure' );
		}
		return false;
	}

	/**
	 * Authenticate with middleman API and get token
	 *
	 * Uses the API key to authenticate and retrieve an API token.
	 * Stores the token in WordPress options for future use.
	 *
	 * @since 4.0.0
	 * @return string|false Token on success, false on failure
	 */
	public function authenticate() {
		// Check if we already have a cached API key
		$api_key = get_option( 'thrive_middleman_api_key', '' );

		// If no cached key, fetch from API
		if ( empty( $api_key ) ) {
			$api_key = $this->fetch_api_key();

			if ( ! $api_key ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'AWeber Middleman: Authentication failed - could not fetch API key' );
				}
				return false;
			}

			// Cache the API key for future use
			update_option( 'thrive_middleman_api_key', $api_key, false );
		}

		$url = $this->auth_url . '/api/auth/tokens';

		// Safely get host from site URL for fallback email
		$site_url    = get_site_url();
		$parsed_host = wp_parse_url( $site_url, PHP_URL_HOST );
		// Use a safe fallback email if host is empty, 'localhost', or not a valid domain
		if ( empty( $parsed_host ) || strtolower( $parsed_host ) === 'localhost' ) {
			$fallback_email = 'admin@example.com';
		} else {
			$fallback_email = 'admin@' . $parsed_host;
		}

		$body = array(
			'api_key'    => $api_key,
			'domain'     => $site_url,
			'email'      => get_option( 'admin_email', $fallback_email ),
			'token_name' => $site_url . ' - ' . get_bloginfo( 'name' ),
		);

		$request_args = array(
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
			'headers' => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
			),
		);

		$response = wp_remote_post( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Authentication failed - ' . $response->get_error_message() );
			}
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$data = json_decode( $response_body, true );

		// Validate JSON decode was successful
		if ( null === $data || JSON_ERROR_NONE !== json_last_error() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Authentication JSON decode failed' );
			}
			return false;
		}

		if ( isset( $data['data']['token'] ) ) {
			$token = $data['data']['token'];
			$this->set_api_token( $token );
			return $token;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'AWeber Middleman: Authentication failed - no token in response (HTTP ' . $response_code . ')' );
		}
		return false;
	}

	/**
	 * Set the API token
	 *
	 * Stores the API token in WordPress options.
	 * Stores in both thrive_api_token (primary) and thrive_middleman_api_token (backward compatibility).
	 *
	 * @since 4.0.0
	 * @param string $token API token
	 * @return bool True on success, false on failure
	 */
	public function set_api_token( $token ) {
		$this->api_token = $token;

		// Encrypt token before storing in both locations
		$encrypted_token = $token;
		if ( class_exists( 'Thrive_Dash_Encryption_Helper' ) && method_exists( 'Thrive_Dash_Encryption_Helper', 'encrypt' ) ) {
			$encrypted_token = Thrive_Dash_Encryption_Helper::encrypt( $token );
		}
		// Store encrypted token in primary location
		$result = update_option( 'thrive_api_token', $encrypted_token, false );
		// Also store in legacy location for backward compatibility
		update_option( 'thrive_middleman_api_token', $encrypted_token, false );

		return $result;
	}

	/**
	 * Initiate OAuth flow
	 *
	 * Starts the OAuth 2.0 authorization flow by requesting an authorization URL
	 * from the middleman API.
	 *
	 * The middleman API accepts redirect_uri parameter and redirects back after OAuth completion:
	 * 1. WordPress sends redirect_uri to middleman API
	 * 2. Middleman API redirects user to AWeber OAuth
	 * 3. AWeber redirects back to middleman API
	 * 4. Middleman API redirects back to WordPress redirect_uri with callback parameters
	 * 5. WordPress processes the callback in read_middleman_credentials()
	 *
	 * @param string $redirect_uri Optional redirect URI. If not provided, uses default callback URL
	 * @return array Response containing 'success', 'data' (with 'authorization_url', 'state'), and 'error'
	 */
	public function initiate_oauth( $redirect_uri = '' ) {
		// Check if API token exists before making request
		if ( empty( $this->api_token ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: OAuth initiation failed - API token is missing' );
			}
			return array(
				'success' => false,
				'error'   => 'Thrive API token is missing. Please ensure your Thrive license is activated and try again.',
				'code'    => 'missing_api_token',
			);
		}

		// Validate site_id is not empty
		if ( empty( $this->site_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: OAuth initiation failed - site_id is empty' );
			}
			return array(
				'success' => false,
				'error'   => 'Site URL configuration is invalid. Please check your WordPress site URL settings.',
				'code'    => 'invalid_site_id',
			);
		}

		// Check if site is localhost in production context
		$parsed_host = wp_parse_url( $this->site_id, PHP_URL_HOST );
		if ( ! empty( $parsed_host ) && ( strtolower( $parsed_host ) === 'localhost' || strpos( $parsed_host, '127.0.0.1' ) !== false ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: OAuth initiation attempted from localhost environment' );
			}
		}

		// Use provided redirect_uri or default to WordPress API connect page
		if ( empty( $redirect_uri ) ) {
			$redirect_uri = admin_url( 'admin.php?page=tve_dash_api_connect' );
		}

		// Build URL manually to ensure proper encoding (matching tpa-plugin-v2 pattern)
		$url      = $this->api_url . '/' . rawurlencode( $this->integration ) . '/oauth/initiate';
		$url      .= '?site_id=' . rawurlencode( $this->site_id );
		$url      .= '&redirect_uri=' . rawurlencode( $redirect_uri );

		$response = $this->make_request( 'GET', $url );

		if ( ! $response['success'] ) {
			return $this->build_oauth_error_response( $response );
		}

		// Store state for reference (server-side validation happens on API)
		$state   = $response['data']['state'] ?? '';
		$user_id = get_current_user_id();

		// Validate user is logged in - OAuth requires authentication
		if ( empty( $user_id ) ) {
			return array(
				'success' => false,
				'error'   => 'You must be logged in to connect AWeber.',
				'code'    => 'authentication_required',
			);
		}

		set_transient( 'aweber_middleman_oauth_state_' . $user_id, $state, 10 * MINUTE_IN_SECONDS );

		// Mark connection as pending for status polling
		set_transient( 'aweber_middleman_connection_pending_' . $user_id, true, 10 * MINUTE_IN_SECONDS );

		return $response;
	}

	/**
	 * Build a user-friendly error response for OAuth initiation failures.
	 *
	 * @since 10.9.beta
	 * @param array $response The failed API response.
	 * @return array Error response with user-friendly message.
	 */
	private function build_oauth_error_response( $response ) {
		$error_code = $response['code'] ?? 'unknown_error';
		$error_msg  = $response['error'] ?? 'Unknown error occurred';

		$error_map = array(
			'connection_timeout'      => 'Connection timed out while contacting Thrive API server. Please check your internet connection and try again.',
			'ssl_verification_failed' => 'SSL certificate verification failed. Your server may have outdated SSL certificates. Please contact your hosting provider.',
			'network_error'           => 'Could not connect to Thrive API server. Please check your internet connection and ensure your server can make outbound HTTPS requests.',
			'invalid_site_id'         => 'Your site URL is not valid. Please check your WordPress site URL settings.',
		);

		$user_error = $error_map[ $error_code ] ?? sprintf( 'API returned error: %s. Please try again or contact support if the problem persists.', esc_html( $error_msg ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'AWeber Middleman: OAuth initiation failed - Code: %s, Error: %s', $error_code, $error_msg ) );
		}

		return array(
			'success' => false,
			'error'   => $user_error,
			'code'    => $error_code,
		);
	}

	/**
	 * Handle OAuth callback
	 *
	 * Processes the OAuth callback with the authorization code and completes
	 * the token exchange via the middleman API.
	 *
	 * @param string $code  Authorization code from provider
	 * @param string $state State parameter for CSRF protection
	 * @return array Response containing 'success', 'data', and 'error'
	 */
	public function handle_callback( $code, $state ) {
		// Validate API token exists
		if ( empty( $this->api_token ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: OAuth callback failed - API token is missing' );
			}
			return array(
				'success' => false,
				'error'   => 'Thrive API token is missing. Please ensure your Thrive license is activated.',
				'code'    => 'missing_api_token',
			);
		}

		// Verify state to prevent CSRF attacks
		$user_id = get_current_user_id();

		// Validate user is logged in - OAuth callback requires authentication
		if ( empty( $user_id ) ) {
			return array(
				'success' => false,
				'error'   => 'You must be logged in to complete the AWeber connection.',
				'code'    => 'authentication_required',
			);
		}

		$stored_state = get_transient( 'aweber_middleman_oauth_state_' . $user_id );

		if ( ! $stored_state || $stored_state !== $state ) {
			return array(
				'success' => false,
				'error'   => 'Invalid state parameter. Possible CSRF attack.',
				'code'    => 'invalid_state',
			);
		}

		// Delete state (one-time use)
		delete_transient( 'aweber_middleman_oauth_state_' . $user_id );

		// Build URL manually to ensure proper encoding (matching tpa-plugin-v2 pattern)
		$url = $this->api_url . '/' . rawurlencode( $this->integration ) . '/oauth/callback';
		$url .= '?code=' . rawurlencode( $code );
		$url .= '&state=' . rawurlencode( $state );
		$url .= '&site_id=' . rawurlencode( $this->site_id );

		$response = $this->make_request( 'GET', $url );

		if ( $response['success'] ) {
			// Delete pending transient
			delete_transient( 'aweber_middleman_connection_pending_' . $user_id );

			// Store connection status
			$this->update_connection_status( 'connected' );

			// Try to fetch and store account ID
			$this->fetch_and_store_account_id();
		}

		return $response;
	}

	/**
	 * Make a proxy request to AWeber API
	 *
	 * Proxies API requests through the middleman API which handles
	 * authentication and token refresh automatically.
	 *
	 * @param string $method   HTTP method (GET, POST, PUT, PATCH, DELETE)
	 * @param string $endpoint AWeber API endpoint path (e.g., 'accounts', 'lists/123/subscribers')
	 * @param array  $data     Optional request body data for POST/PUT/PATCH requests
	 * @return array Response containing 'success', 'data', and 'error'
	 */
	public function proxy_request( $method, $endpoint, $data = array() ) {
		// Validate API token exists
		if ( empty( $this->api_token ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Proxy request failed - API token is missing' );
			}
			return array(
				'success' => false,
				'error'   => 'Thrive API token is missing. Please ensure your Thrive license is activated.',
				'code'    => 'missing_api_token',
			);
		}

		// Verify AWeber is connected before making API requests
		if ( ! $this->is_connected() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Proxy request failed - AWeber is not connected' );
			}
			return array(
				'success' => false,
				'error'   => 'AWeber is not connected. Please connect your account first.',
				'code'    => 'integration_not_connected',
			);
		}

		// Build proxy URL - manually encode site_id to match tpa-plugin-v2 implementation
		// Using manual concatenation instead of add_query_arg() to ensure proper encoding
		$base_url = $this->api_url . '/' . $this->integration . '/proxy/' . ltrim( $endpoint, '/' );
		// Use & if endpoint already has query params (e.g., "subscribers?ws.op=find&email=..."), otherwise use ?
		// This ensures we don't break existing query parameters in the endpoint
		$separator = ( strpos( $endpoint, '?' ) !== false ) ? '&' : '?';
		$url       = $base_url . $separator . 'site_id=' . rawurlencode( $this->site_id );

		$response = $this->make_request( $method, $url, $data );

		// Check for token expiration errors
		if ( ! $response['success'] ) {
			$error_code = $response['code'] ?? '';

			// If token expired or refresh failed, mark as disconnected
			if ( in_array( $error_code, array( 'token_expired', 'token_refresh_failed', 'integration_not_connected' ), true ) ) {
				$this->update_connection_status( 'disconnected' );
				$response['action'] = 'reconnect';
			}
		}

		return $response;
	}

	/**
	 * Disconnect integration
	 *
	 * Disconnects the AWeber integration by revoking tokens on the middleman API.
	 *
	 * @return array Response containing 'success', 'message', and 'error'
	 */
	public function disconnect() {
		// Validate API token exists
		if ( empty( $this->api_token ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Disconnect failed - API token is missing' );
			}
			return array(
				'success' => false,
				'error'   => 'Thrive API token is missing. Please ensure your Thrive license is activated.',
				'code'    => 'missing_api_token',
			);
		}

		// Build URL manually to ensure proper encoding (matching tpa-plugin-v2 pattern)
		$url = $this->api_url . '/' . $this->integration . '/oauth/disconnect';
		$url .= '?site_id=' . rawurlencode( $this->site_id );

		$response = $this->make_request( 'DELETE', $url );

		if ( $response['success'] ) {
			$this->update_connection_status( 'disconnected' );

			// Clear cached data
			$this->clear_cached_data();
		}

		return $response;
	}

	/**
	 * Check OAuth connection status via API
	 *
	 * Queries the middleman API to check if OAuth tokens are stored and valid.
	 * Updates local connection status based on API response.
	 *
	 * @since 4.0.0
	 * @return array Response containing 'success', 'data' (with 'connected' boolean), and 'error'
	 */
	public function check_status() {
		// Validate API token exists
		if ( empty( $this->api_token ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'AWeber Middleman: Status check failed - API token is missing' );
			}
			return array(
				'success' => false,
				'error'   => 'Thrive API token is missing. Please ensure your Thrive license is activated.',
				'code'    => 'missing_api_token',
			);
		}

		// Build URL manually to ensure proper encoding (matching tpa-plugin-v2 pattern)
		$url = $this->api_url . '/' . $this->integration . '/oauth/status';
		$url .= '?site_id=' . rawurlencode( $this->site_id );

		$response = $this->make_request( 'GET', $url );

		// Update local connection status based on API response
		if ( $response['success'] && isset( $response['data']['connected'] ) ) {
			$is_connected = (bool) $response['data']['connected'];
			$this->update_connection_status( $is_connected ? 'connected' : 'disconnected' );

			// If newly connected, try to fetch account ID
			if ( $is_connected ) {
				$this->fetch_and_store_account_id();
			}
		}

		return $response;
	}

	/**
	 * Check if integration is connected
	 *
	 * This checks the local stored status. For real-time status from the API,
	 * use check_status() instead.
	 *
	 * @return bool True if connected, false otherwise
	 */
	public function is_connected() {
		$connection_status = $this->get_connection_status();
		return $connection_status === 'connected';
	}

	/**
	 * Get connection status
	 *
	 * Returns the locally stored connection status. Does not query the API.
	 * For real-time status from the API, use check_status() instead.
	 *
	 * @return string Connection status: 'connected', 'disconnected', or 'pending'
	 */
	public function get_connection_status() {
		$credentials = $this->get_stored_credentials();
		return $credentials['connection_state'] ?? 'disconnected';
	}

	/**
	 * Update connection status
	 *
	 * @param string $status Status: 'connected', 'disconnected', or 'pending'
	 * @return bool True on success
	 */
	private function update_connection_status( $status ) {
		$credentials                   = $this->get_stored_credentials();
		$credentials['connection_state'] = $status;

		if ( $status === 'connected' ) {
			$credentials['connected_at'] = time();
		}

		return $this->store_credentials( $credentials );
	}

	/**
	 * Fetch and store AWeber account ID
	 *
	 * Gets the account ID from AWeber API and stores it in credentials.
	 *
	 * @return bool True on success, false on failure
	 */
	private function fetch_and_store_account_id() {
		$response = $this->proxy_request( 'GET', 'accounts' );

		if ( $response['success'] && isset( $response['data']['entries'][0]['id'] ) ) {
			$account_id  = $response['data']['entries'][0]['id'];
			$credentials = $this->get_stored_credentials();
			$credentials['account_id'] = $account_id;
			return $this->store_credentials( $credentials );
		}

		return false;
	}

	/**
	 * Get stored AWeber account ID
	 *
	 * @return string|null Account ID or null if not found
	 */
	public function get_account_id() {
		$credentials = $this->get_stored_credentials();
		$account_id  = $credentials['account_id'] ?? null;

		// If not stored, try to fetch it (only once per instance to prevent potential recursion)
		if ( ! $account_id && $this->is_connected() && ! $this->account_id_fetch_attempted ) {
			$this->account_id_fetch_attempted = true;
			$this->fetch_and_store_account_id();
			$credentials = $this->get_stored_credentials();
			$account_id  = $credentials['account_id'] ?? null;
		}

		return $account_id;
	}

	/**
	 * Get stored credentials
	 *
	 * @return array Stored credentials
	 */
	private function get_stored_credentials() {
		$all_credentials = get_option( 'thrive_mail_list_api', array() );
		return $all_credentials['aweber'] ?? array();
	}

	/**
	 * Store credentials
	 *
	 * @param array $credentials Credentials to store
	 * @return bool True on success
	 */
	private function store_credentials( $credentials ) {
		$all_credentials           = get_option( 'thrive_mail_list_api', array() );
		$all_credentials['aweber'] = $credentials;
		return update_option( 'thrive_mail_list_api', $all_credentials );
	}

	/**
	 * Clear cached AWeber data
	 *
	 * Removes all cached lists, tags, custom fields, etc.
	 */
	private function clear_cached_data() {
		// Clear transient caches
		delete_transient( 'tve_api_data_aweber' );
		delete_transient( 'api_custom_fields_aweber' );

		// Clear tags cache (pattern-based) using proper escaping
		global $wpdb;
		$transient_pattern = $wpdb->esc_like( '_transient_aweber_tags_' ) . '%';
		$timeout_pattern   = $wpdb->esc_like( '_transient_timeout_aweber_tags_' ) . '%';

		// Find and delete all matching transients using delete_transient() to ensure object cache consistency
		$option_names = $wpdb->get_col( $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$transient_pattern,
			$timeout_pattern
		) );
		foreach ( $option_names as $option_name ) {
			// Skip timeout entries; deleting the transient will also remove its timeout
			if ( strpos( $option_name, '_transient_timeout_' ) === 0 ) {
				continue;
			}
			if ( strpos( $option_name, '_transient_' ) === 0 ) {
				$transient_key = substr( $option_name, strlen( '_transient_' ) );
				delete_transient( $transient_key );
			}
		}

		// Clear accounts cache - sanitize site_id for transient key
		$safe_site_id = sanitize_key( $this->site_id );
		delete_transient( 'aweber_middleman_accounts_' . $safe_site_id );
	}

	/**
	 * Make HTTP request to middleman API
	 *
	 * Handles all HTTP communication with the Thrive API, including comprehensive
	 * error detection for network issues, timeouts, and SSL problems.
	 *
	 * @since 4.0.0
	 * @param string      $method HTTP method (GET, POST, PUT, PATCH, DELETE)
	 * @param string      $url    Full URL to request
	 * @param array|null  $data   Optional request body data
	 * @return array Response with 'success', 'data', 'error', and 'code' keys
	 */
	private function make_request( $method, $url, $data = null, $is_retry = false ) {
		$args = array(
			'method'  => strtoupper( $method ),
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		// Add body for POST/PUT/PATCH requests
		if ( in_array( $args['method'], array( 'POST', 'PUT', 'PATCH' ), true ) && ! empty( $data ) ) {
			$json_body = wp_json_encode( $data );
			$args['body'] = $json_body;
		}

		$response = wp_remote_request( $url, $args );

		// Handle WordPress errors with specific categorization
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$error_code    = $response->get_error_code();

			// Categorize error types for better user feedback
			if ( strpos( $error_message, 'timed out' ) !== false || strpos( $error_message, 'timeout' ) !== false ) {
				$categorized_code = 'connection_timeout';
			} elseif ( strpos( $error_message, 'SSL' ) !== false || strpos( $error_message, 'certificate' ) !== false ) {
				$categorized_code = 'ssl_verification_failed';
			} elseif ( strpos( $error_message, 'Could not resolve host' ) !== false || strpos( $error_message, 'name resolution' ) !== false ) {
				$categorized_code = 'dns_resolution_failed';
			} elseif ( strpos( $error_message, 'Connection refused' ) !== false ) {
				$categorized_code = 'connection_refused';
			} else {
				$categorized_code = 'network_error';
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AWeber Middleman: HTTP request failed - WP_Error [%s]: %s', $error_code, $error_message ) );
			}

			return array(
				'success' => false,
				'error'   => $error_message,
				'code'    => $categorized_code,
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		$parsed_body = json_decode( $body, true );

		// Handle non-JSON responses
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$json_error = json_last_error_msg();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'AWeber Middleman: Invalid JSON response from API - %s. Response body: %s', $json_error, substr( $body, 0, 200 ) ) );
			}

			return array(
				'success' => false,
				'error'   => 'Invalid JSON response from API: ' . $json_error,
				'code'    => 'invalid_json',
			);
		}

		// Return parsed response
		if ( $status_code >= 200 && $status_code < 300 ) {
			return array(
				'success' => true,
				'data'    => $parsed_body['data'] ?? $parsed_body,
				'message' => $parsed_body['message'] ?? '',
			);
		}

		// Not a 401 or already retried - return error
		if ( 401 !== $status_code || $is_retry ) {
			return $this->build_api_error_response( $parsed_body, $status_code );
		}

		// 401 and not a retry - attempt re-authentication
		delete_option( 'thrive_api_token' );
		delete_option( 'thrive_middleman_api_token' );

		$new_token = $this->authenticate();

		if ( ! $new_token ) {
			return $this->build_api_error_response( $parsed_body, $status_code );
		}

		// Re-authentication succeeded, retry the request
		$this->api_token = $new_token;

		return $this->make_request( $method, $url, $data, true );
	}

	/**
	 * Build a standardized error response from an API failure.
	 *
	 * @since 10.9.beta
	 * @param array $parsed_body  Parsed JSON response body.
	 * @param int   $status_code  HTTP status code.
	 * @return array Error response with 'success', 'error', and 'code' keys.
	 */
	private function build_api_error_response( $parsed_body, $status_code ) {
		$error_message = $parsed_body['error'] ?? $parsed_body['message'] ?? 'Unknown error occurred';
		$error_code    = $parsed_body['code'] ?? 'unknown_error';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'AWeber Middleman: API returned error - Status: %d, Code: %s, Message: %s', $status_code, $error_code, $error_message ) );
		}

		return array(
			'success' => false,
			'error'   => $error_message,
			'code'    => $error_code,
		);
	}

	/**
	 * Get connection info for display
	 *
	 * @return array Connection info with status, connected_date, account_id
	 */
	public function get_connection_info() {
		$credentials = $this->get_stored_credentials();

		return array(
			'status'         => $this->get_connection_status(),
			'connected_at'   => $credentials['connected_at'] ?? null,
			'connected_date' => $credentials['connected_at'] ? date_i18n( get_option( 'date_format' ), $credentials['connected_at'] ) : null,
			'account_id'     => $credentials['account_id'] ?? null,
			'site_id'        => $this->site_id,
		);
	}
}

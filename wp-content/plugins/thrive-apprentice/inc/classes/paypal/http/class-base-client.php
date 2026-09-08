<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Http;

use TVA\PayPal\Connection;
use TVA\PayPal\Credentials;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Base_Client
 *
 * Abstract HTTP client for Product API communication.
 * Provides bearer-token transient caching, singleton swap seam, and
 * get()/post()/request() HTTP primitives shared by all per-domain clients.
 *
 * Concrete subclasses must:
 *   1. Redeclare `protected static $instances = [];` — required for PHP static
 *      property isolation so each class maintains its own singleton registry.
 *   2. Implement client_slug() returning the domain key used in the transient
 *      name (e.g. 'order', 'onboarding', 'vault').
 */
abstract class Base_Client {

	/**
	 * Singleton registry keyed by mode.
	 *
	 * Must be redeclared in each concrete subclass — PHP does not isolate
	 * static properties per subclass unless explicitly redeclared.
	 *
	 * @var static[]
	 */
	protected static $instances = [];

	/**
	 * API mode: 'live' or 'test'.
	 *
	 * @var string
	 */
	protected $mode = 'live';

	/**
	 * PayPal-Debug-Id from the most recent response on this client instance.
	 *
	 * The Product API forwards PayPal's PayPal-Debug-Id header on every successful
	 * response and includes it in the error body on failure. It is the support pivot
	 * for locating a call in PayPal's logs. Read immediately after the call that set it.
	 *
	 * @var string
	 */
	protected $last_debug_id = '';

	/**
	 * @param string $mode 'live' or 'test'
	 */
	public function __construct( $mode = 'live' ) {
		$this->mode = $mode;
	}

	// -------------------------------------------------------------------------
	// Abstract interface
	// -------------------------------------------------------------------------

	/**
	 * Return the domain slug used in the bearer token transient key.
	 *
	 * Examples: 'onboarding', 'order', 'vault'
	 *
	 * @return string
	 */
	abstract protected function client_slug(): string;

	// -------------------------------------------------------------------------
	// Singleton seam
	// -------------------------------------------------------------------------

	/**
	 * Return (or create) the singleton for the given mode.
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return static
	 */
	public static function get_instance( $mode = 'live' ) {
		if ( ! isset( static::$instances[ $mode ] ) ) {
			static::$instances[ $mode ] = new static( $mode );
		}

		return static::$instances[ $mode ];
	}

	/**
	 * Replace the singleton with an arbitrary instance (for test injection).
	 *
	 * @param static $inst
	 * @param string $mode 'live' or 'test'
	 */
	public static function set_instance( $inst, $mode = 'live' ) {
		static::$instances[ $mode ] = $inst;
	}

	/**
	 * Clear the singleton registry (for test teardown).
	 */
	public static function reset_instance() {
		static::$instances = [];
	}

	// -------------------------------------------------------------------------
	// Bearer token management
	// -------------------------------------------------------------------------

	/**
	 * Build the transient key for a given client slug and mode.
	 *
	 * Centralises the key format so all callers — Base_Client subclasses,
	 * the REST controller, Credentials::disconnect(), and Hooks::refresh_token()
	 * — always produce the same string. Avoids silent drift if the format changes.
	 *
	 * @param string $slug Client domain slug: 'onboarding', 'order', or 'vault'.
	 * @param string $mode 'live' or 'test'.
	 *
	 * @return string e.g. 'tva_paypal_bearer_order_live'
	 */
	public static function make_transient_key( string $slug, string $mode ): string {
		return 'tva_paypal_bearer_' . $slug . '_' . $mode;
	}

	/**
	 * Return the WP transient key for this client and mode.
	 *
	 * @return string
	 */
	protected function bearer_transient_key() {
		return static::make_transient_key( $this->client_slug(), $this->mode );
	}

	/**
	 * Fetch a valid bearer token, using the transient cache when available.
	 *
	 * On cache miss, POSTs to Connection::ENDPOINT_OAUTH_ACCESS_TOKEN with
	 * Basic Auth (merchant_id:site_secret). The site_secret must be exactly
	 * Credentials::SITE_SECRET_LENGTH chars.
	 *
	 * Returns '' on any failure — callers must check for empty string.
	 *
	 * @return string
	 */
	public function get_bearer_token() {
		$cached = get_transient( $this->bearer_transient_key() );

		if ( $cached !== false ) {
			return (string) $cached;
		}

		$merchant_id = Credentials::get_merchant_id( $this->mode );
		$secret      = Credentials::get_site_secret( $this->mode );

		if ( empty( $merchant_id ) || strlen( $secret ) !== Credentials::SITE_SECRET_LENGTH ) {
			return '';
		}

		$response = wp_remote_post(
			Connection::get_product_api_url() . Connection::ENDPOINT_OAUTH_ACCESS_TOKEN,
			[
				'headers' => [
					'Authorization' => 'Basic ' . base64_encode( $merchant_id . ':' . $secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		if ( ! empty( $body['error'] ) || empty( $body['access_token'] ) ) {
			return '';
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 604800;
		set_transient( $this->bearer_transient_key(), $body['access_token'], max( 1, $expires_in - 60 ) );

		return $body['access_token'];
	}

	// -------------------------------------------------------------------------
	// HTTP primitives
	// -------------------------------------------------------------------------

	/**
	 * Perform a GET request against the Product API.
	 *
	 * @param string $path    API path starting with '/'.
	 * @param array  $headers Additional headers to merge.
	 *
	 * @return array|WP_Error
	 */
	public function get( $path, array $headers = [] ) {
		return $this->request( 'GET', $path, [], $headers );
	}

	/**
	 * Perform a POST request against the Product API.
	 *
	 * @param string $path    API path starting with '/'.
	 * @param array  $body    Request body data (will be JSON-encoded).
	 * @param array  $headers Additional headers to merge.
	 *
	 * @return array|WP_Error
	 */
	public function post( $path, array $body, array $headers = [] ) {
		return $this->request( 'POST', $path, $body, $headers );
	}

	/**
	 * Dispatch a single HTTP call — one GET or POST — and return the raw WP HTTP response.
	 *
	 * DRY helper for request(). Both the initial attempt and the 401-retry call through
	 * here so timeouts, encoding, and header merging live in one place.
	 *
	 * @param string $method  'GET' or 'POST'.
	 * @param string $url     Full URL including Product API base.
	 * @param array  $headers Merged request headers (already includes Authorization).
	 * @param array  $body    Request body (only sent for POST).
	 *
	 * @return array|WP_Error Raw WordPress HTTP response.
	 */
	private function execute_http_request( string $method, string $url, array $headers, array $body ) {
		if ( $method === 'GET' ) {
			return wp_remote_get( $url, [ 'headers' => $headers, 'timeout' => 15 ] );
		}

		return wp_remote_post( $url, [
			'method'  => $method,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 15,
		] );
	}

	/**
	 * Record the PayPal-Debug-Id from a response: header first, then a debug_id field
	 * in the (error) body. Stores it on the instance for get_last_debug_id().
	 *
	 * @param array|\WP_Error $response WP HTTP response.
	 * @param array           $parsed   Decoded JSON body.
	 *
	 * @return string The debug id (also stored on the instance).
	 */
	private function capture_debug_id( $response, array $parsed ): string {
		$debug_id = (string) wp_remote_retrieve_header( $response, 'paypal-debug-id' );

		if ( '' === $debug_id ) {
			// Error bodies carry it as top-level debug_id or nested under error.debug_id.
			// Guard the nested read: `error` is sometimes a plain string (see request()'s
			// $message handling), and indexing a string with 'debug_id' would warn/throw.
			$debug_id = (string) ( $parsed['debug_id'] ?? '' );
			if ( '' === $debug_id && isset( $parsed['error'] ) && is_array( $parsed['error'] ) ) {
				$debug_id = (string) ( $parsed['error']['debug_id'] ?? '' );
			}
		}

		$this->last_debug_id = $debug_id;

		return $debug_id;
	}

	/**
	 * Core HTTP dispatch — all get() and post() calls funnel through here.
	 *
	 * Returns WP_Error immediately when no bearer token is available (empty
	 * string from get_bearer_token), avoiding a guaranteed 401 round-trip.
	 *
	 * @param string $method  'GET' or 'POST'.
	 * @param string $path    API path starting with '/'.
	 * @param array  $body    Request body (only used for POST).
	 * @param array  $headers Additional headers.
	 *
	 * @return array|WP_Error
	 */
	public function request( $method, $path, array $body = [], array $headers = [] ) {
		// Reset per call: the client is a per-mode singleton, so without this a call that
		// bails early (no token, or a transport WP_Error) would leave a previous call's
		// debug id in place and get_last_debug_id() would misattribute it to this one.
		$this->last_debug_id = '';

		$token = $this->get_bearer_token();

		if ( $token === '' ) {
			return new WP_Error( 'paypal_no_token', 'PayPal bearer token unavailable — check merchant_id and site_secret configuration.' );
		}

		$url = Connection::get_product_api_url() . $path;

		$merged_headers = array_merge(
			[
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			],
			$headers
		);

		$response = $this->execute_http_request( $method, $url, $merged_headers, $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$parsed = json_decode( wp_remote_retrieve_body( $response ), true );
		$parsed = is_array( $parsed ) ? $parsed : [];

		$this->capture_debug_id( $response, $parsed );

		if ( $status === 401 ) {
			delete_transient( $this->bearer_transient_key() );
			$token = $this->get_bearer_token();

			if ( $token !== '' ) {
				$merged_headers['Authorization'] = 'Bearer ' . $token;
				$response                        = $this->execute_http_request( $method, $url, $merged_headers, $body );

				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$status = wp_remote_retrieve_response_code( $response );
				$parsed = json_decode( wp_remote_retrieve_body( $response ), true );
				$parsed = is_array( $parsed ) ? $parsed : [];

				$this->capture_debug_id( $response, $parsed );
			}
		}

		if ( $status >= 400 ) {
			$message = isset( $parsed['error'] ) ? $parsed['error'] : wp_remote_retrieve_response_message( $response );

			return new WP_Error( 'paypal_api_error', $message, [
				'status'   => $status,
				'body'     => $parsed,
				'debug_id' => $this->last_debug_id,
			] );
		}

		return is_array( $parsed ) ? $parsed : [];
	}

	/**
	 * Return the PayPal-Debug-Id captured from the most recent response.
	 *
	 * @return string Empty string when none was returned.
	 */
	public function get_last_debug_id(): string {
		return $this->last_debug_id;
	}
}

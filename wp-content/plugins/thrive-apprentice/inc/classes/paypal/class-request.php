<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal;

use TVA\PayPal\Connection;
use TVA\PayPal\Http\Order_Client;
use TVA\PayPal\Http\Vault_Client;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Request
 *
 * Static facade that routes PayPal API calls to the correct per-domain HTTP client.
 * All calls are forwarded via `Connection::get_product_api_url()` — the plugin
 * never calls PayPal directly.
 *
 * Order methods delegate to Http\Order_Client.
 * Subscription methods delegate to Http\Vault_Client.
 */
class Request {

	/**
	 * Create a PayPal order via the Product API.
	 *
	 * Pass a 'PayPal-Request-Id' header for idempotency to prevent duplicate orders
	 * on network timeout + retry:
	 *   Request::create_order( $data, 'live', [ 'PayPal-Request-Id' => uniqid( 'tva_order_', true ) ] )
	 *
	 * @param array  $data    Order request body (intent, purchase_units, etc.).
	 * @param string $mode    'live' or 'test'.
	 * @param array  $headers Optional additional headers (e.g. PayPal-Request-Id).
	 *
	 * @return array|WP_Error Decoded response array, or WP_Error on failure.
	 */
	public static function create_order( array $data, $mode = 'live', array $headers = [] ) {
		return Order_Client::get_instance( $mode )->post( Connection::ENDPOINT_ORDERS_CREATE, $data, $headers );
	}

	/**
	 * Capture (charge) a previously created PayPal order via the Product API.
	 *
	 * @param string $order_id PayPal order ID (returned by create_order).
	 * @param string $mode     'live' or 'test'.
	 * @param array  $headers  Optional additional headers.
	 *
	 * @return array|WP_Error Decoded response array, or WP_Error on failure.
	 */
	public static function capture_order( $order_id, $mode = 'live', array $headers = [] ) {
		$path = sprintf( '/api/paypal/v1/orders/%s/capture', $order_id );
		return Order_Client::get_instance( $mode )->post( $path, [], $headers );
	}

	/**
	 * Read a PayPal order via the Product API (GET /orders/{id}).
	 *
	 * Used by client-confirmed flows (e.g. Google Pay), where the order is captured
	 * client-side via the SDK and the server must read — not re-capture — the order
	 * to finalize the local purchase. A second capture on an already-captured order errors.
	 *
	 * @param string $order_id PayPal order ID.
	 * @param string $mode     'live' or 'test'.
	 * @param array  $headers  Optional additional headers.
	 *
	 * @return array|WP_Error Decoded response array, or WP_Error on failure.
	 */
	public static function get_order( $order_id, $mode = 'live', array $headers = [] ) {
		$path = sprintf( '/api/paypal/v1/orders/%s', $order_id );
		return Order_Client::get_instance( $mode )->get( $path, $headers );
	}

	/**
	 * Register the merchant's checkout domain for Apple Pay.
	 *
	 * POST /merchant/domains. PayPal returns 201 on success. A 422
	 * DOMAIN_ALREADY_REGISTERED comes back as a WP_Error and must be treated as
	 * idempotent success by the caller (see Apple_Pay::maybe_register_domain()).
	 *
	 * @param string $domain Bare host, e.g. "shop.example.com".
	 * @param string $mode   'live' | 'test'.
	 * @return array|WP_Error
	 */
	public static function register_domain( $domain, $mode = 'live' ) {
		return Order_Client::get_instance( $mode )->post( Connection::ENDPOINT_MERCHANT_DOMAINS, [ 'domain' => $domain ] );
	}

	/**
	 * Deregister the merchant's checkout domain for Apple Pay.
	 *
	 * DELETE /merchant/domains/<domain>. The Product API maps this to PayPal's
	 * unregister-wallet-domain call. Returns the raw client result: a success array, or a
	 * WP_Error for any HTTP >= 400 — including a "not registered" 404. Callers should treat
	 * that not-registered error as benign (the post-condition — PayPal no longer has this
	 * domain — already holds); reverify() calls this best-effort and ignores the result.
	 *
	 * @param string $domain Bare host, e.g. "shop.example.com".
	 * @param string $mode   'live' | 'test'.
	 * @return array|WP_Error
	 */
	public static function deregister_domain( $domain, $mode = 'live' ) {
		$path = Connection::ENDPOINT_MERCHANT_DOMAINS . '/' . rawurlencode( $domain );
		return Order_Client::get_instance( $mode )->request( 'DELETE', $path );
	}

	/**
	 * Refund a captured PayPal order via the Product API.
	 *
	 * @param string $capture_id PayPal capture ID.
	 * @param string $mode       'live' or 'test'.
	 * @param array  $params     Optional additional refund parameters.
	 * @param array  $headers    Optional additional headers.
	 *
	 * @return array|WP_Error Decoded response array, or WP_Error on failure.
	 */
	public static function refund_order( $capture_id, $mode = 'live', array $params = [], array $headers = [] ) {
		$path = sprintf( '/api/paypal/v1/captures/%s/refund', $capture_id );
		return Order_Client::get_instance( $mode )->post( $path, $params, $headers );
	}

	/**
	 * Create a vault-based subscription via the Product API.
	 *
	 * Wraps $data in { "data": ... } per Product API contract.
	 *
	 * @param array  $data { intent, source, purchase_units, recurring_times, total_cycles, thrive_order_id }
	 * @param string $mode
	 * @return array|\WP_Error
	 */
	public static function create_subscription( array $data, string $mode = 'live' ) {
		return Vault_Client::get_instance( $mode )->create_subscription( $data );
	}

	/**
	 * Activate (capture) a subscription after buyer approval.
	 *
	 * @param string $subscription_id
	 * @param string $mode
	 * @return array|\WP_Error
	 */
	public static function activate_subscription( string $subscription_id, string $mode = 'live' ) {
		return Vault_Client::get_instance( $mode )->activate_subscription( $subscription_id );
	}

	/**
	 * Cancel a vault-based subscription via the Product API.
	 *
	 * @param string $subscription_id
	 * @param string $mode
	 * @return array|\WP_Error
	 */
	public static function cancel_subscription( string $subscription_id, string $mode = 'live' ) {
		return Vault_Client::get_instance( $mode )->cancel_subscription( $subscription_id );
	}

	/**
	 * Return a fresh PayPal client token for the embedded checkout SDK.
	 *
	 * This is the `client_token` from GET /merchant/credentials — a base64 blob
	 * that decodes to `{ braintree, paypal }`, which the PayPal JS SDK's
	 * `data-client-token` attribute expects (the SDK base64-decodes it to enable
	 * the Advanced Credit/Debit Card fields). NOTE: the API also returns
	 * `sdk_client_token`, an ES256 JWT — that is NOT base64 and must NOT go into
	 * data-client-token (the SDK's atob() on it throws InvalidCharacterError). The
	 * token is merchant-scoped, so it is cached per mode (not per buyer), under its
	 * TTL. Refreshed via GET /merchant/credentials (Bearer auth).
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return string The client_token, or '' on failure.
	 */
	public static function get_client_token( $mode = 'live' ) {
		$transient_key = 'tva_paypal_ct_' . $mode;

		$cached = get_transient( $transient_key );
		if ( $cached !== false ) {
			return (string) $cached;
		}

		$response = Order_Client::get_instance( $mode )->get( Connection::ENDPOINT_MERCHANT_CREDENTIALS );

		if ( is_wp_error( $response ) || empty( $response['client_token'] ) ) {
			return '';
		}

		$token   = (string) $response['client_token'];
		$expires = isset( $response['client_token_expires_in'] ) ? (int) $response['client_token_expires_in'] : 3600;

		// Keep the cache comfortably under the token TTL (60s safety margin).
		set_transient( $transient_key, $token, max( 60, $expires - 60 ) );

		return $token;
	}
}

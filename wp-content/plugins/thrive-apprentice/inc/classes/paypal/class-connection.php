<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Connection
 *
 * Holds Product API URL constants and endpoint path constants used by all
 * HTTP clients. Also provides reset() for callers that need to invalidate
 * any per-request caches after a disconnect or credential change.
 *
 * The Product API base URL can be overridden for staging via wp-config.php:
 *   define( 'TVA_PAYPAL_PRODUCT_API_URL', 'https://tpa.stagingthrivethemes.com' );
 *
 * Bearer token management is NOT handled here — see Http\Base_Client.
 */
class Connection {

	// -------------------------------------------------------------------------
	// Product API
	// -------------------------------------------------------------------------

	public const PRODUCT_API_URL_LIVE = 'https://thrivethemesapi.com';

	public const PRODUCT_API_URL_STAGING = 'https://tpa.stagingthrivethemes.com';

	public const ENDPOINT_OAUTH_ACCESS_TOKEN    = '/api/paypal/v1/auth/token';
	public const ENDPOINT_ORDERS_CREATE         = '/api/paypal/v1/orders';
	public const ENDPOINT_ONBOARDING_START      = '/api/paypal/v1/onboarding/start';
	public const ENDPOINT_ONBOARDING_COMPLETE   = '/api/paypal/v1/onboarding/complete';
	public const ENDPOINT_MERCHANT              = '/api/paypal/v1/merchant';
	public const ENDPOINT_MERCHANT_CREDENTIALS  = '/api/paypal/v1/merchant/credentials';
	public const ENDPOINT_MERCHANT_DOMAINS      = '/api/paypal/v1/merchant/domains';
	public const ENDPOINT_AUTH_CLIENT_TOKEN     = '/api/paypal/v1/auth/client-token';
	public const ENDPOINT_AUTH_SDK_TOKEN        = '/api/paypal/v1/auth/sdk-token';
	public const ENDPOINT_SUBSCRIPTIONS_CREATE   = '/api/paypal/v1/subscriptions';
	public const ENDPOINT_SUBSCRIPTIONS_ACTIVATE = '/api/paypal/v1/subscriptions/%s/activate';
	public const ENDPOINT_SUBSCRIPTIONS_CANCEL   = '/api/paypal/v1/subscriptions/%s/cancel';

	/**
	 * Return the Product API base URL.
	 *
	 * Override for staging via wp-config.php:
	 *   define( 'TVA_PAYPAL_PRODUCT_API_URL', 'https://tpa.stagingthrivethemes.com' );
	 *
	 * @return string
	 */
	public static function get_product_api_url() {
		if ( defined( 'TVA_PAYPAL_PRODUCT_API_URL' ) ) {
			if ( static::is_allowed_api_url( (string) TVA_PAYPAL_PRODUCT_API_URL ) ) {
				return TVA_PAYPAL_PRODUCT_API_URL;
			}

			// Override is defined but failed the https-Thrive-host allowlist. Fall back to the
			// safe live default rather than sending credentials anywhere else — but log it, so a
			// developer who typo'd the host (or pointed at http://localhost) can discover why
			// their requests are silently hitting live instead of the intended target.
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'product_api_url_override_rejected', [
				'override' => (string) TVA_PAYPAL_PRODUCT_API_URL,
			], true );
		}

		// No override (or a rejected one): use the safe live default.
		return static::PRODUCT_API_URL_LIVE;
	}

	/**
	 * Whether a Product API base URL is trusted: must be https and on a Thrive domain.
	 *
	 * Allowlist (exact host or any subdomain):
	 *   - thrivethemesapi.com      (production)
	 *   - stagingthrivethemes.com  (staging, e.g. tpa.stagingthrivethemes.com)
	 *
	 * @param string $url
	 * @return bool
	 */
	private static function is_allowed_api_url( string $url ): bool {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || 'https' !== $parts['scheme'] || empty( $parts['host'] ) ) {
			return false;
		}

		$host            = strtolower( $parts['host'] );
		$allowed_domains = [ 'thrivethemesapi.com', 'stagingthrivethemes.com' ];

		foreach ( $allowed_domains as $domain ) {
			if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * No-op kept for backward compatibility with event handlers and the REST
	 * controller that call it after disconnect(). The per-request token cache
	 * (previously stored in $token_live / $token_test) was removed in #3769
	 * because Connection::get_token() was dead code reading deprecated options.
	 * Bearer tokens are now managed exclusively by Base_Client transients.
	 */
	public static function reset() {
	}
}

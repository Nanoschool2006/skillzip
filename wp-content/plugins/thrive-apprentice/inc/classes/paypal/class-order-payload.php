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
 * Shared builders for PayPal order/subscription purchase_unit fields
 * (custom_id packing, buyer reference, line-item helpers). Used by both the
 * one-time checkout controller and the vault-subscription buy flow so the two
 * stay byte-identical.
 */
class Order_Payload {

	/**
	 * custom_id length cap (bytes). PayPal documents custom_id at Maximum Length 255
	 * (Orders v2 OpenAPI spec + PHP Server SDK PurchaseUnitRequest) — distinct from
	 * invoice_id's 127. pack_custom_id() truncates byte-accurately against this.
	 */
	const CUSTOM_ID_MAX_LENGTH = 255;

	/**
	 * Recurring intervals the Product API accepts for a vault subscription
	 * (per docs/product-api-doc.md). Anything outside this list is a 422 upstream.
	 */
	const ALLOWED_RECURRING_INTERVALS = [
		'daily', '1 day', 'weekly', '1 week', 'monthly', '1 month',
		'quarterly', '3 months', 'semi-yearly', '6 months', 'yearly', '1 year',
	];

	/**
	 * Map a rule's recurring interval to a Product-API-accepted value.
	 *
	 * Returns '' for anything not in ALLOWED_RECURRING_INTERVALS so the caller can fail
	 * fast instead of getting a 422 from the subscription create call.
	 *
	 * @param string $raw
	 * @return string Accepted value (lower-cased/trimmed), or '' if not recognised.
	 */
	public static function normalize_recurring_times( string $raw ): string {
		$value = strtolower( trim( $raw ) );

		return in_array( $value, self::ALLOWED_RECURRING_INTERVALS, true ) ? $value : '';
	}

	/**
	 * Read the saved client-reference type for a product, defaulting to 'user_id'.
	 *
	 * @param int $product_id Thrive Apprentice product term id.
	 *
	 * @return string 'user_id' or 'user_name'.
	 */
	public static function get_client_reference_type( $product_id ) {
		return self::sanitize_client_reference_type( get_term_meta( $product_id, 'tva_paypal_product_client_reference_type', true ) );
	}

	/**
	 * Constrain a client-reference type to the supported values.
	 *
	 * @param mixed $type Raw stored/posted value.
	 *
	 * @return string 'user_id' or 'user_name'.
	 */
	public static function sanitize_client_reference_type( $type ) {
		return 'user_name' === $type ? 'user_name' : 'user_id';
	}

	/**
	 * Resolve the buyer reference packed into the order's custom_id ('u' key).
	 *
	 * For logged-in buyers this returns the chosen identifier (User ID or User name),
	 * mirroring Stripe's get_client_reference(). Guests have no WP user or name at
	 * order-creation time — the account is created/matched post-payment by the capture
	 * webhook — so the entered email (the key that account is matched on) is used
	 * instead. The value is sanitised; pack_custom_id() handles fitting it within
	 * PayPal's custom_id length limit.
	 *
	 * @param int    $product_id Thrive Apprentice product term id.
	 * @param string $email      Buyer email entered at checkout (used for guests).
	 *
	 * @return string The reference, or '' when disabled / empty.
	 */
	public static function resolve_client_reference( $product_id, $email = '' ) {
		if ( ! get_term_meta( $product_id, 'tva_paypal_product_client_reference_enabled', true ) ) {
			return '';
		}

		if ( is_user_logged_in() ) {
			$reference = 'user_name' === self::get_client_reference_type( $product_id )
				? wp_get_current_user()->display_name
				: (string) get_current_user_id();
		} else {
			$reference = $email;
		}

		return sanitize_text_field( $reference );
	}

	/**
	 * Truncate a value to PayPal's field length, multibyte-safe.
	 *
	 * Uses mb_substr() when available so a non-ASCII value (e.g. a display name or
	 * product title) is not cut mid-character, which would produce invalid UTF-8 and
	 * could break JSON encoding / the PayPal API call.
	 *
	 * @param string $value  Already-sanitised value.
	 * @param int    $length Max length in characters (default 127, PayPal's item-name cap).
	 *
	 * @return string
	 */
	public static function truncate_for_paypal( $value, $length = 127 ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
	}

	/**
	 * Pack order id + optional buyer reference into a JSON custom_id.
	 *
	 * Encodes the data as JSON within the custom_id limit (CUSTOM_ID_MAX_LENGTH). The
	 * limit is in bytes, so if the encoded value overflows, the buyer reference ('u') is
	 * shortened a character at a time (multibyte-safe) until the JSON fits — rather than
	 * dropped outright — so long/non-ASCII display names still carry a (truncated)
	 * reference. The order id ('o', needed for webhook correlation) always survives.
	 *
	 * @param array $data e.g. [ 'o' => 123, 'u' => '48746' ].
	 *
	 * @return string JSON string within CUSTOM_ID_MAX_LENGTH bytes.
	 */
	public static function pack_custom_id( array $data ) {
		$encoded = wp_json_encode( $data );
		if ( strlen( $encoded ) <= self::CUSTOM_ID_MAX_LENGTH || ! isset( $data['u'] ) ) {
			return $encoded;
		}

		// Overflow: shrink the reference (by characters, never splitting a UTF-8 sequence)
		// until the byte length of the encoded JSON fits.
		$reference = (string) $data['u'];
		$length    = function_exists( 'mb_strlen' ) ? mb_strlen( $reference ) : strlen( $reference );
		$length    = min( $length, self::CUSTOM_ID_MAX_LENGTH );
		while ( $length > 0 ) {
			$length--;
			$data['u'] = self::truncate_for_paypal( $reference, $length );
			$encoded   = wp_json_encode( $data );
			if ( strlen( $encoded ) <= self::CUSTOM_ID_MAX_LENGTH ) {
				return $encoded;
			}
		}

		// Even an empty reference doesn't fit (cannot happen with just the order id) — drop it.
		unset( $data['u'] );
		return wp_json_encode( $data );
	}
}

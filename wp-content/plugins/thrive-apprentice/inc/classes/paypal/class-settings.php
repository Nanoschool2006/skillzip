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
 * Class Settings
 *
 * Merchant-facing PayPal preferences stored in wp_options (autoload = false).
 * Groups:
 *   - integration toggles (enable PayPal, Buy Now) and per-product rules
 *   - simple boolean "default options" toggles (Pay Later messaging, etc.)
 *   - which approved funding methods are shown at checkout (display preference)
 *
 * Mirrors TVA\Stripe\Settings. The checkout-method preferences are a display
 * choice only — they can hide/show methods the merchant is already eligible
 * for; they never grant a capability (see Credentials::get_capabilities_summary()).
 */
class Settings {

	public const ENABLE_PAYPAL_OPTION     = 'tva_paypal_enabled';
	public const BUY_NOW_ENABLED_OPTION   = 'tva_paypal_buy_now_enabled';

	/**
	 * Term meta holding the product's PayPal price catalog — every price the
	 * merchant has added via the "Add Price" modal. PayPal has no external price
	 * catalog (unlike Stripe/Square), so these prices live locally and back the
	 * admin pricing dropdown. The *selected* price is stored separately on the
	 * `integration=paypal` entry in `tva_rules` (see save_product_rule()).
	 */
	public const PRODUCT_PRICES_META = 'tva_paypal_prices';

	/** Currency codes PayPal supports for payments (ISO 4217). Drives the price modal's currency list. */
	public const SUPPORTED_CURRENCIES = [
		'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN',
		'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'THB', 'USD',
	];

	public const SHOW_PAY_LATER_MESSAGING = 'tva_paypal_show_pay_later_messaging';
	public const AUTO_DISPLAY_BUY_BUTTON  = 'tva_paypal_auto_display_buy_button';
	public const FRAUDNET_ENABLED         = 'tva_paypal_fraudnet_enabled';
	public const SCA_ALWAYS               = 'tva_paypal_sca_always';
	public const CHECKOUT_METHODS_OPTION  = 'tva_paypal_checkout_methods';

	/** Default-options toggles that the /settings route accepts. */
	public const TOGGLE_OPTIONS = [
		self::SHOW_PAY_LATER_MESSAGING => true,
		self::AUTO_DISPLAY_BUY_BUTTON  => false,
		self::FRAUDNET_ENABLED         => true,
		// off = SCA_WHEN_REQUIRED (PayPal default), on = SCA_ALWAYS (see TVA\Buy_Now\Paypal)
		self::SCA_ALWAYS               => false,
	];

	/**
	 * Always-on buyer-facing method that cannot be disabled. The full set of
	 * toggleable methods is data-driven: the renderable item keys of the stored
	 * capabilities_summary (see Credentials::get_renderable_keys()).
	 */
	public const LOCKED_ON_METHOD = 'paypal_wallet';

	/**
	 * Funding methods that are NOT exposed as "shown at checkout" toggles: Pay in 4 /
	 * installments and PayPal Credit (a separate funding option, not Pay Later messaging).
	 * They render only on PayPal's hosted checkout (governed by the buyer's PayPal account)
	 * and cannot be hidden from there by the merchant — `disable-funding` only affects our
	 * on-site SDK buttons, not PayPal's hosted pages (confirmed with the gateway dev). A
	 * toggle would have no effect, so we don't render or persist one. Pay Later *messaging*
	 * is still controlled by the SHOW_PAY_LATER_MESSAGING default option.
	 */
	public const NON_TOGGLEABLE_METHODS = [ 'paypal_credit', 'installments' ];

	/**
	 * Methods PayPal grants but the embedded checkout does not offer yet, so we hide their
	 * "shown at checkout" toggle (a toggle that changes nothing is misleading). `apms` (local
	 * payment methods) and `fastlane` are not implemented this phase. Apple Pay keeps its toggle
	 * (tracked + implemented in its own issue). Re-home a key from here once it ships at checkout.
	 */
	public const UNSUPPORTED_METHODS = [ 'apms', 'fastlane' ];

	/** Maximum allowed free-trial length in days. */
	public const TRIAL_DAYS_MAX = 365;

	/**
	 * Clamp a raw trial-days value to a non-negative integer within [0, TRIAL_DAYS_MAX].
	 *
	 * @param mixed $value Raw input.
	 * @return int
	 */
	public static function clamp_trial_days( $value ): int {
		$days = is_numeric( $value ) ? (int) $value : 0;
		if ( $days < 0 ) {
			$days = 0;
		}
		if ( $days > self::TRIAL_DAYS_MAX ) {
			$days = self::TRIAL_DAYS_MAX;
		}
		return $days;
	}

	/**
	 * Get a single option value, falling back to the given default.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		return get_option( $key, $default );
	}

	/**
	 * Get a single toggle value, falling back to its documented default.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public static function is_enabled( $option_name ) {
		$default = static::TOGGLE_OPTIONS[ $option_name ] ?? false;

		return (bool) get_option( $option_name, $default );
	}

	/**
	 * Update an option value.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	public static function update_setting( $key, $value ) {
		return update_option( $key, $value, false );
	}

	/**
	 * Whether a given option is an accepted toggle.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public static function is_toggle( $option_name ) {
		return array_key_exists( $option_name, static::TOGGLE_OPTIONS );
	}

	/**
	 * Stored "shown at checkout" preference map (summary item key => bool) for
	 * the buyer-facing (renderable) methods. Unset keys default to enabled.
	 * paypal_wallet is always enabled.
	 *
	 * @param string|null $mode 'live', 'test' or null to auto-detect (matches the caller's mode).
	 *
	 * @return array<string,bool>
	 */
	public static function get_checkout_methods( $mode = null ) {
		$stored = get_option( static::CHECKOUT_METHODS_OPTION, [] );
		if ( ! is_array( $stored ) ) {
			$stored = [];
		}

		$map = [ static::LOCKED_ON_METHOD => true ];
		foreach ( Credentials::get_renderable_keys( $mode ) as $key ) {
			// Skip the locked-on method (already set), the methods we never expose as toggles
			// (NON_TOGGLEABLE_METHODS) and the ones not offered at checkout yet (UNSUPPORTED_METHODS),
			// so the reader stays consistent with the write path/template.
			if ( $key === static::LOCKED_ON_METHOD
				|| in_array( $key, static::NON_TOGGLEABLE_METHODS, true )
				|| in_array( $key, static::UNSUPPORTED_METHODS, true ) ) {
				continue;
			}
			$map[ $key ] = array_key_exists( $key, $stored ) ? (bool) $stored[ $key ] : true;
		}

		return $map;
	}

	/**
	 * Toggle a single checkout method's display preference.
	 *
	 * @param string $key     Summary item key (must be renderable and not the locked-on one).
	 * @param bool   $enabled
	 *
	 * @return bool false when the key is invalid or locked
	 */
	public static function set_checkout_method( $key, $enabled ) {
		if ( $key === static::LOCKED_ON_METHOD
			|| in_array( $key, static::NON_TOGGLEABLE_METHODS, true )
			|| in_array( $key, static::UNSUPPORTED_METHODS, true )
			|| ! in_array( $key, Credentials::get_renderable_keys(), true ) ) {
			return false;
		}

		$stored         = get_option( static::CHECKOUT_METHODS_OPTION, [] );
		$stored         = is_array( $stored ) ? $stored : [];
		$stored[ $key ] = (bool) $enabled;
		update_option( static::CHECKOUT_METHODS_OPTION, $stored, false );

		return true;
	}

	/**
	 * Get the per-product PayPal pricing rule.
	 *
	 * Reads the `integration=paypal` entry from the product term's `tva_rules`
	 * meta (the same store Stripe/Square use) and flattens its single item into
	 * the shape the buyer checkout expects.
	 *
	 * @param int $product_id Product term id.
	 *
	 * @return array {
	 *     Empty array when no paypal rule/item exists, otherwise:
	 *
	 *     @type string $amount          Price amount.
	 *     @type string $currency        Currency code.
	 *     @type bool   $is_subscription Whether this is a recurring price.
	 *     @type string $recurring_times strtotime interval ("1 month"/"1 year") for subscriptions, else "".
	 *     @type int    $total_cycles    Number of billing cycles (0 = unlimited).
	 *     @type string $description     Rule description.
	 * }
	 */
	public static function get_product_rule( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return [];
		}

		$rules = get_term_meta( $product_id, 'tva_rules', true );
		if ( ! is_array( $rules ) ) {
			return [];
		}

		$paypal = null;
		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) && isset( $rule['integration'] ) && 'paypal' === $rule['integration'] ) {
				$paypal = $rule;
				break;
			}
		}

		if ( null === $paypal || empty( $paypal['items'][0] ) ) {
			return [];
		}

		$item            = $paypal['items'][0];
		$is_subscription = isset( $item['type'] ) && 'recurring' === $item['type'];

		$trial_enabled = $is_subscription && (bool) get_term_meta( $product_id, 'tva_paypal_product_trial_enabled', true );
		$trial_days    = $trial_enabled ? self::clamp_trial_days( get_term_meta( $product_id, 'tva_paypal_product_trial_days', true ) ) : 0;

		return [
			'amount'          => isset( $item['amount'] ) ? (string) $item['amount'] : '',
			'currency'        => isset( $item['currency'] ) ? (string) $item['currency'] : '',
			'is_subscription' => $is_subscription,
			'recurring_times' => $is_subscription ? ( 'year' === ( $item['interval'] ?? '' ) ? '1 year' : '1 month' ) : '',
			'total_cycles'    => 0,
			'description'     => isset( $paypal['description'] ) ? (string) $paypal['description'] : '',
			'success_url'     => isset( $paypal['success_url'] ) ? (string) $paypal['success_url'] : '',
			'trial_enabled'   => $trial_enabled,
			'trial_days'      => $trial_days,
		];
	}

	/**
	 * Save the per-product PayPal pricing rule.
	 *
	 * Upserts the `integration=paypal` entry into the product term's `tva_rules`
	 * meta from the flat admin input. All other integrations' entries (Stripe,
	 * Square, etc.) are preserved.
	 *
	 * Used by the `paypal/selected_price` REST route (the admin "Add/select price"
	 * flow) to persist this product's price on its own — without re-saving the whole
	 * product, which would re-render and collapse the rules panel — and by code/tests
	 * that need to set a price directly.
	 *
	 * @param int   $product_id Product term id.
	 * @param array $rule       Flat input: { amount, currency, type, interval }.
	 *
	 * @return bool
	 */
	public static function save_product_rule( $product_id, array $rule ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		$rules = get_term_meta( $product_id, 'tva_rules', true );
		if ( ! is_array( $rules ) ) {
			$rules = [];
		}

		$amount   = isset( $rule['amount'] ) ? (string) $rule['amount'] : '';
		$type     = ( isset( $rule['type'] ) && 'recurring' === $rule['type'] ) ? 'recurring' : 'one_time';
		$interval = isset( $rule['interval'] ) ? (string) $rule['interval'] : '';

		$entry = [
			'integration' => 'paypal',
			'price_id'    => (string) $product_id,
			'items'       => [
				[
					// Match the deterministic id the admin modal builds (price.js): the
					// id is never read by get_product_rule(), but keeping all write
					// paths (and the price catalog) consistent avoids confusion.
					'id'         => static::build_price_id( $amount, $type, $interval ),
					'product_id' => (string) $product_id,
					'amount'     => $amount,
					'currency'   => isset( $rule['currency'] ) ? (string) $rule['currency'] : '',
					'type'       => $type,
					'interval'   => $interval,
				],
			],
		];

		$replaced = false;
		foreach ( $rules as $index => $existing ) {
			if ( is_array( $existing ) && isset( $existing['integration'] ) && 'paypal' === $existing['integration'] ) {
				// Merge over the existing entry rather than replacing it: this lightweight
				// price write only owns the price-related keys (integration, price_id,
				// items). Sibling fields the full product-save path manages — success_url,
				// cancel_url, description — must survive, otherwise selecting a price would
				// silently wipe the saved checkout URLs from tva_rules (see #3805).
				$rules[ $index ] = array_merge( $existing, $entry );
				$replaced        = true;
				break;
			}
		}

		if ( ! $replaced ) {
			$rules[] = $entry;
		}

		return (bool) update_term_meta( $product_id, 'tva_rules', $rules );
	}

	/**
	 * Clear the per-product PayPal price.
	 *
	 * Empties the `integration=paypal` entry's items in the product term's
	 * `tva_rules` meta (other integrations preserved), so get_product_rule()
	 * returns [] for this product. No-op when there is no paypal entry yet.
	 *
	 * @param int $product_id Product term id.
	 *
	 * @return bool
	 */
	public static function clear_product_rule( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		$rules = get_term_meta( $product_id, 'tva_rules', true );
		if ( ! is_array( $rules ) ) {
			return false;
		}

		$changed = false;
		foreach ( $rules as $index => $existing ) {
			if ( is_array( $existing ) && isset( $existing['integration'] ) && 'paypal' === $existing['integration'] ) {
				$rules[ $index ]['items'] = [];
				$changed                  = true;
				break;
			}
		}

		if ( ! $changed ) {
			return false;
		}

		return (bool) update_term_meta( $product_id, 'tva_rules', $rules );
	}

	/**
	 * Build the deterministic price id used everywhere a PayPal price is stored.
	 *
	 * Matches the id the admin modal builds (price.js):
	 * `paypal_<interval|one_time>_<amount-in-cents>`. Used by save_product_rule()
	 * (the selected price) and add_price() (the catalog) so the same price always
	 * has the same id across every store.
	 *
	 * @param string $amount   Price amount.
	 * @param string $type     'recurring' or 'one_time'.
	 * @param string $interval 'month'/'year' for recurring, else ''.
	 *
	 * @return string
	 */
	protected static function build_price_id( $amount, $type, $interval ) {
		$slug = ( 'recurring' === $type ) ? $interval : 'one_time';

		return 'paypal_' . $slug . '_' . (int) round( (float) $amount * 100 );
	}

	/**
	 * Get the product's PayPal price catalog — every price the merchant has added.
	 *
	 * Backs the admin pricing dropdown. Stored on its own term meta
	 * (PRODUCT_PRICES_META) rather than in `tva_rules`, so it is never touched by
	 * the full product-save path and survives re-renders/reloads.
	 *
	 * @param int $product_id Product term id.
	 *
	 * @return array<int,array> List of { id, amount, currency, type, interval }.
	 */
	public static function get_prices( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return [];
		}

		$prices = get_term_meta( $product_id, static::PRODUCT_PRICES_META, true );

		return is_array( $prices ) ? array_values( $prices ) : [];
	}

	/**
	 * Add a price to the product's PayPal price catalog.
	 *
	 * De-dupes by price id (so re-selecting an existing price is a no-op); all
	 * other prices are preserved. The id is taken from the input when present,
	 * otherwise derived from amount/type/interval so it stays consistent with the
	 * selected-price id written to `tva_rules`.
	 *
	 * @param int   $product_id Product term id.
	 * @param array $price      { id?, amount, currency, type, interval }.
	 *
	 * @return bool
	 */
	public static function add_price( $product_id, array $price ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return false;
		}

		$amount   = isset( $price['amount'] ) ? (string) $price['amount'] : '';
		$type     = ( isset( $price['type'] ) && 'recurring' === $price['type'] ) ? 'recurring' : 'one_time';
		$interval = isset( $price['interval'] ) ? (string) $price['interval'] : '';
		$id       = ! empty( $price['id'] ) ? (string) $price['id'] : static::build_price_id( $amount, $type, $interval );

		if ( '' === $amount ) {
			return false;
		}

		$prices = static::get_prices( $product_id );
		foreach ( $prices as $existing ) {
			if ( isset( $existing['id'] ) && $id === (string) $existing['id'] ) {
				return true; // Already in the catalog.
			}
		}

		$prices[] = [
			'id'       => $id,
			'amount'   => $amount,
			'currency' => isset( $price['currency'] ) ? (string) $price['currency'] : '',
			'type'     => $type,
			'interval' => $interval,
		];

		return (bool) update_term_meta( $product_id, static::PRODUCT_PRICES_META, $prices );
	}

	/**
	 * Get the product's currently-selected PayPal price as a raw catalog-shaped
	 * item ({ id, amount, currency, type, interval }), or [] when none is set.
	 *
	 * Reads the `integration=paypal` entry's first item from `tva_rules`. Used to
	 * guarantee the selected price always appears in the pricing dropdown, even
	 * for products saved before the catalog meta existed.
	 *
	 * @param int $product_id Product term id.
	 *
	 * @return array
	 */
	public static function get_selected_price( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return [];
		}

		$rules = get_term_meta( $product_id, 'tva_rules', true );
		if ( ! is_array( $rules ) ) {
			return [];
		}

		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) && isset( $rule['integration'] ) && 'paypal' === $rule['integration'] && ! empty( $rule['items'][0] ) ) {
				$item = $rule['items'][0];

				return [
					'id'       => isset( $item['id'] ) ? (string) $item['id'] : '',
					'amount'   => isset( $item['amount'] ) ? (string) $item['amount'] : '',
					'currency' => isset( $item['currency'] ) ? (string) $item['currency'] : '',
					'type'     => isset( $item['type'] ) ? (string) $item['type'] : 'one_time',
					'interval' => isset( $item['interval'] ) ? (string) $item['interval'] : '',
				];
			}
		}

		return [];
	}

	/**
	 * Get the product's stored PayPal checkout success/cancel URLs.
	 *
	 * Reads them from the `integration=paypal` entry in `tva_rules` (where the full
	 * product-save path stores them). Returned regardless of whether a price/item is
	 * set, so the admin pricing view can refresh the URL fields from the server on a
	 * stale/cached page before a save overwrites them (see #3805).
	 *
	 * @param int $product_id Product term id.
	 *
	 * @return array { @type string $success_url, @type string $cancel_url }
	 */
	public static function get_checkout_urls( $product_id ) {
		$product_id = (int) $product_id;
		$urls       = [ 'success_url' => '', 'cancel_url' => '' ];
		if ( $product_id <= 0 ) {
			return $urls;
		}

		$rules = get_term_meta( $product_id, 'tva_rules', true );
		if ( ! is_array( $rules ) ) {
			return $urls;
		}

		foreach ( $rules as $rule ) {
			if ( is_array( $rule ) && isset( $rule['integration'] ) && 'paypal' === $rule['integration'] ) {
				$urls['success_url'] = isset( $rule['success_url'] ) ? esc_url_raw( (string) $rule['success_url'] ) : '';
				$urls['cancel_url']  = isset( $rule['cancel_url'] ) ? esc_url_raw( (string) $rule['cancel_url'] ) : '';
				break;
			}
		}

		return $urls;
	}

	/**
	 * Reset the merchant preference settings on disconnect: the default-options
	 * toggles (Pay Later messaging, auto display buy button, FraudNet, SCA always)
	 * and the checkout-method display preferences.
	 *
	 * Note: the integration toggles (ENABLE_PAYPAL_OPTION, BUY_NOW_ENABLED_OPTION)
	 * and per-product pricing (the `integration=paypal` entry in each product's
	 * `tva_rules` term meta) are intentionally preserved so they survive a reconnect.
	 */
	public static function reset() {
		delete_option( static::SHOW_PAY_LATER_MESSAGING );
		delete_option( static::AUTO_DISPLAY_BUY_BUTTON );
		delete_option( static::FRAUDNET_ENABLED );
		delete_option( static::SCA_ALWAYS );
		delete_option( static::CHECKOUT_METHODS_OPTION );
	}
}

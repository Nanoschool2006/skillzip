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
 * Class Checkout
 *
 * Builds the config the embedded PayPal checkout needs (PayPal JS SDK: Wallet
 * buttons + Advanced Credit/Debit Card fields). One-time products only —
 * subscriptions use the vault redirect flow.
 *
 * The checkout itself is a modal opened by the buy button on the current page
 * (js/frontend.js, mirroring Square): the frontend fetches get_config() via the
 * /paypal/product_config REST endpoint, loads the SDK with these values and
 * renders the buttons + card fields in place. There is no self-rendered page.
 *
 * IWT-required SDK attributes (commit, data-page-type, data-partner-attribution-id
 * (BN code), data-client-token) are set by the frontend on the dynamically
 * injected SDK <script>, from the values returned here.
 */
class Checkout {

	/**
	 * The PayPal capability that the rendered Pay Later message represents. PayPal's Messages
	 * component surfaces the "Pay in 4 / Pay Monthly" (installments) offer, so the message is
	 * gated on this capability being renderable for the merchant. The separate PayPal Credit
	 * capability is a distinct funding option and does NOT drive this message.
	 */
	const PAY_LATER_MESSAGE_CAPABILITY = 'installments';

	/**
	 * Build the config payload the embedded-checkout modal needs to boot the
	 * PayPal JS SDK and run the create_order -> capture_order flow.
	 *
	 * Returns null when the product cannot be checked out via the embedded flow
	 * (not connected, no one-time price, a subscription, or no partner client id),
	 * so the REST endpoint can respond 400. Mirrors Square's product_config.
	 *
	 * @param int $product_id
	 *
	 * @return array|null
	 */
	public static function get_config( $product_id ) {
		$context = static::get_context( (int) $product_id );
		if ( $context === null ) {
			return null;
		}

		$mode      = $context['mode'];
		$rule      = $context['rule'];
		$client_id = Credentials::get_client_id( $mode );

		// Without a partner client id the SDK cannot initialise.
		if ( $client_id === '' ) {
			return null;
		}

		// Whether the embedded modal should render the Pay Later promo message (toggle on +
		// Pay Later capability). The frontend renders it via the standalone, namespaced messages
		// SDK (window.tvaPayPalMessages) — NOT a `messages` component on this checkout SDK, whose
		// bundled Messages build paints empty in the modal context.
		$pay_later = static::pay_later_available( $mode );

		// Honor the merchant's "shown at checkout" capability toggles. get_checkout_methods()
		// returns renderable methods (key => bool), defaulting unset keys to enabled; a key absent
		// entirely means PayPal didn't grant it. Capability keys come from the Product API with
		// underscores (google_pay, credit_debit); the matching PayPal SDK *component* names have
		// none (googlepay). The wallet (paypal_wallet) is locked-on, so 'buttons' is always present.
		$methods    = Settings::get_checkout_methods( $mode );
		$google_pay = ! empty( $methods['google_pay'] );
		$apple_pay  = ! empty( $methods['apple_pay'] );
		$card_on    = ! empty( $methods['credit_debit'] );
		$venmo_on   = ! empty( $methods['venmo'] );

		$components_list = [ 'buttons' ];
		if ( $card_on ) {
			$components_list[] = 'card-fields';
		}
		if ( $google_pay ) {
			$components_list[] = 'googlepay';
		}
		if ( $apple_pay ) {
			// Capability key is apple_pay (underscore); the SDK component is applepay (none).
			$components_list[] = 'applepay';
		}
		$components = implode( ',', $components_list );

		// Pay Later (installments) is non-toggleable, so it stays enabled. Venmo IS gated on its
		// Capabilities toggle ($methods['venmo']): get_checkout_methods only includes it when PayPal
		// granted the venmo capability, defaulting to on unless the merchant turned it off. PayPal
		// still gates the actual button on buyer eligibility (US, supported browser).
		$enable_funding_list = [ 'paylater' ];
		if ( $venmo_on ) {
			$enable_funding_list[] = 'venmo';
		}
		$enable_funding = implode( ',', $enable_funding_list );

		// Suppress a Smart Button when its method is off. Card: disable-funding=card ONLY when card
		// is off — when card is on the card-fields component already suppresses that button, and
		// disabling it would also kill ACDC CardFields eligibility and hide the inline card form.
		// Venmo: disable-funding=venmo when its toggle is off, so the SDK can't re-surface it.
		$disable_funding_list = [];
		if ( ! $card_on ) {
			$disable_funding_list[] = 'card';
		}
		if ( ! $venmo_on ) {
			$disable_funding_list[] = 'venmo';
		}
		$disable_funding = implode( ',', $disable_funding_list );

		return [
			// --- SDK boot (used to build the SDK <script> src + IWT data-* attrs) ---
			'clientId'        => $client_id,
			'merchantId'      => Credentials::get_merchant_id( $mode ),
			'bnCode'          => Credentials::get_bn_code( $mode ),
			'clientToken'     => Request::get_client_token( $mode ),
			'components'      => $components,
			'disableFunding'  => $disable_funding,
			'payLater'        => $pay_later,
			// enable-funding renders the Pay Later / Venmo Smart Buttons when eligible. Pay Later is
			// non-toggleable (always on); Venmo is gated on its Capabilities toggle (see $enable_funding
			// above). The disable-funding we pass only affects the SDK buttons on our site, never
			// PayPal's hosted checkout page (confirmed with the gateway dev), so it can't hide
			// Pay in 4 / PayPal Credit there — those are governed by the buyer's PayPal account.
			'enableFunding'   => $enable_funding,
			// Whether the ACDC card path is offered (Capabilities toggle). The frontend gates the
			// "Debit or credit card" row + fields on this explicitly, rather than inferring it from
			// the SDK exposing window.paypal.CardFields (which it does even without the component).
			'card'            => $card_on,
			// Google Pay (component-based, one-time only). countryCode feeds the Google Pay
			// sheet's transactionInfo; merchant country falls back to US when unknown.
			'googlePay'       => $google_pay,
			'applePay'        => $apple_pay,
			'countryCode'     => Credentials::get_merchant_country( $mode ) ?: 'US',
			'currency'        => (string) $rule['currency'],
			'mode'            => $mode,
			// --- Checkout flow ---
			'createOrderUrl'  => rest_url( \TVA_Const::REST_NAMESPACE . '/paypal/create_order' ),
			'captureOrderUrl' => rest_url( \TVA_Const::REST_NAMESPACE . '/paypal/capture_order' ),
			'productId'       => (int) $context['product_id'],
			'isSubscription'  => false,
			'payerEmail'      => static::prefill_email( $product_id ),
			// Vaulting opt-in is shown only to logged-in buyers — the customer.id is stored
			// against the WP user, so guests can't vault (see #3886 design).
			'canVault'        => is_user_logged_in(),
			'redirectUrl'     => static::get_redirect_url( $product_id, $rule ),
			'nonce'           => wp_create_nonce( 'wp_rest' ),
			'amount'          => (string) $rule['amount'],
			'productTitle'    => ( new \TVA\Product( (int) $product_id ) )->get_name(),
			// Merchant display name for the checkout modal header/avatar (the site name).
			'merchantName'    => (string) get_bloginfo( 'name' ),
			// --- IWT FraudNet collector (null when disabled) ---
			'fraudnet'        => static::fraudnet_config( $mode ),
		];
	}

	/**
	 * Config for the Pay Later messaging banner shown on product pages.
	 *
	 * Returns the minimal values PayPal's "messages" SDK component needs to render the
	 * "as low as N payments of X" banner above a one-time product's buy button: the
	 * partner client id, the price amount and its currency. Pay Later applies to one-time
	 * purchases only, so this reuses get_context() — which already rejects subscriptions,
	 * unconnected merchants and unpriced products.
	 *
	 * Returns null when the merchant's "Show Pay Later messaging" toggle is off or the
	 * product is not eligible, so the frontend renders no banner.
	 *
	 * @param int $product_id
	 *
	 * @return array|null { clientId, currency, amount }
	 */
	public static function pay_later_config( $product_id ) {
		// Cheapest gate first: this runs via AJAX per product on every eligible buy-button
		// load, so short-circuit on the toggle before get_context() does its DB reads (term
		// meta + capabilities summary) when Pay Later messaging is disabled.
		if ( ! Settings::is_enabled( Settings::SHOW_PAY_LATER_MESSAGING ) ) {
			return null;
		}

		$context = static::get_context( (int) $product_id );
		if ( $context === null ) {
			return null;
		}

		if ( ! static::pay_later_available( $context['mode'] ) ) {
			return null;
		}

		$client_id = Credentials::get_client_id( $context['mode'] );
		if ( $client_id === '' ) {
			return null;
		}

		return [
			'clientId' => $client_id,
			'currency' => (string) $context['rule']['currency'],
			'amount'   => (string) $context['rule']['amount'],
		];
	}

	/**
	 * Whether Pay Later messaging should show for a mode: the merchant's "Show Pay Later
	 * messaging" toggle is on AND the account is renderable for the Pay Later capability
	 * (Pay in 4 / installments). The one-time-price requirement is enforced separately by
	 * get_context(). Shared by the product-page banner (pay_later_config) and the in-modal
	 * message (get_config).
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return bool
	 */
	protected static function pay_later_available( $mode ) {
		return Settings::is_enabled( Settings::SHOW_PAY_LATER_MESSAGING )
			&& static::is_pay_later_capability_available( $mode );
	}

	/**
	 * Whether the connected account is offered the Pay Later (Pay in 4 / installments)
	 * capability for a mode — PayPal lists it as ACTIVE and renderable. Independent of the
	 * "Show Pay Later messaging" toggle, so the settings UI can hide that toggle when the
	 * merchant is not offered Pay Later at all.
	 *
	 * @param string|null $mode 'live', 'test', or null for the active mode (live if connected, else test).
	 *
	 * @return bool
	 */
	public static function is_pay_later_capability_available( $mode = null ) {
		return in_array( static::PAY_LATER_MESSAGE_CAPABILITY, Credentials::get_renderable_keys( $mode ), true );
	}

	/**
	 * Resolve the renderable checkout context for a product, or null when the
	 * embedded checkout should not run (not connected, no one-time price, etc.).
	 *
	 * @param int $product_id
	 *
	 * @return array{mode:string,product_id:int,rule:array}|null
	 */
	protected static function get_context( $product_id ) {
		$product_id = (int) $product_id;
		if ( $product_id <= 0 ) {
			return null;
		}

		$mode = static::mode();
		if ( ! Credentials::is_mode_enabled( $mode ) ) {
			return null;
		}

		// Both flags are only meaningful in live mode; sandbox accounts commonly return false.
		if ( 'live' === $mode && ! Credentials::is_email_confirmed() ) {
			return null;
		}

		if ( 'live' === $mode && ! Credentials::is_payments_receivable() ) {
			return null;
		}

		if ( Credentials::is_vetting_blocking( $mode ) ) {
			return null;
		}

		$rule = Settings::get_product_rule( $product_id );

		// One-time products only; a valid amount + currency is required.
		if ( empty( $rule ) || empty( $rule['amount'] ) || empty( $rule['currency'] ) || ! empty( $rule['is_subscription'] ) ) {
			return null;
		}

		return [
			'mode'       => $mode,
			'product_id' => $product_id,
			'rule'       => $rule,
		];
	}

	/**
	 * Buyer email to prefill at checkout.
	 *
	 * Only returned when the buyer is logged in AND the product's "pre-populate
	 * email at checkout" setting is enabled (per-product term meta, mirroring
	 * Square's tva_square_product_prepopulate_email toggle). Empty otherwise, so
	 * guests — and logged-in buyers on products with the setting off — start with
	 * a blank email field.
	 *
	 * @param int $product_id
	 *
	 * @return string
	 */
	protected static function prefill_email( $product_id ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		if ( ! get_term_meta( (int) $product_id, 'tva_paypal_product_prepopulate_email', true ) ) {
			return '';
		}

		return (string) wp_get_current_user()->user_email;
	}

	/**
	 * Where to send the buyer after a successful purchase. Matches the Stripe and
	 * Square gateways: the success URL configured on the product's PayPal settings,
	 * falling back to the site home when none is set.
	 *
	 * @param int   $product_id
	 * @param array $rule       PayPal product rule (may include success_url).
	 *
	 * @return string
	 */
	protected static function get_redirect_url( $product_id, array $rule = [] ) {
		$success_url = isset( $rule['success_url'] ) ? trim( (string) $rule['success_url'] ) : '';

		// Open-redirect guard: only honour a configured success URL when it is a valid,
		// same-site URL (FILTER_VALIDATE_URL alone would pass http://evil.com). Otherwise
		// fall back to the site home.
		$url = home_url( '/' );
		if ( $success_url !== '' && filter_var( $success_url, FILTER_VALIDATE_URL ) !== false ) {
			$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
			$dest_host = wp_parse_url( $success_url, PHP_URL_HOST );
			if ( $dest_host && $home_host && strcasecmp( $dest_host, $home_host ) === 0 ) {
				$url = esc_url_raw( $success_url );
			}
		}

		// Append the product id for order context on the destination (parity with
		// the Stripe checkout). The TVA order id is appended client-side in
		// onApprove once create_order returns it.
		return add_query_arg( [ 'product_id' => $product_id ], $url );
	}

	/**
	 * Current mode ('live' or 'test') for the connected merchant.
	 *
	 * @return string
	 */
	protected static function mode() {
		return Credentials::is_mode_enabled( 'live' ) ? 'live' : 'test';
	}

	/**
	 * FraudNet collector config for the modal (issue 3693).
	 *
	 * FraudNet must load on any checkout that can charge a saved card (IWT
	 * requirement). Gated on the merchant's FraudNet toggle
	 * (Settings::FRAUDNET_ENABLED, default on); returns null when off. The
	 * frontend injects the application/json snippet + collector iframe.
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return array|null { sessionId, bnCode, fncls, iframeUrl }
	 */
	protected static function fraudnet_config( $mode ) {
		if ( ! Settings::is_enabled( Settings::FRAUDNET_ENABLED ) ) {
			return null;
		}

		$session_id = str_replace( '-', '', wp_generate_uuid4() );
		$bn_code    = Credentials::get_bn_code( $mode );

		return [
			'sessionId' => $session_id,
			'bnCode'    => $bn_code,
			'fncls'     => 'fnparams-dede7cc5-15fd-4c75-a9f4-36c430ee3a99',
			'iframeUrl' => add_query_arg( [ 'f' => $session_id, 's' => $bn_code ], 'https://c.paypal.com/da/r/fb.js' ),
		];
	}
}

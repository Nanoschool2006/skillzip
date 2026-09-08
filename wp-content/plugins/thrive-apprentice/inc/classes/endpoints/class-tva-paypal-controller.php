<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

use TVA\PayPal\Connection;
use TVA\PayPal\Credentials;
use TVA\PayPal\Order_Payload;
use TVA\PayPal\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_PayPal_Controller
 *
 * REST controller for the PayPal payment gateway.
 * Namespace: tva/v1 — registered via tva_create_initial_rest_routes() in inc/functions.php.
 *
 * Routes (all under /wp-json/tva/v1/paypal/):
 *   POST   connect_account              — start onboarding via Product API (/onboarding/start)
 *   DELETE disconnect                   — remove all stored credentials + notify Product API
 *   GET    status                       — check whether live/test tokens exist and are valid
 *   GET    {credentials_endpoint}       — PayPal return redirect (test); validates secret, calls /onboarding/complete
 *   GET    {credentials_endpoint_live}  — PayPal return redirect (live); validates secret, calls /onboarding/complete
 *   POST   {credentials_endpoint}       — retired service API callback (returns 410 Gone)
 *   POST   {credentials_endpoint_live}  — retired service API callback (returns 410 Gone)
 *   POST   webhook                      — receive PayPal webhook events (Phase 0: log only)
 */
class TVA_PayPal_Controller extends TVA_REST_Controller {

	public $base = 'paypal';

	/**
	 * Max raw webhook body accepted before HMAC/JSON work. Real Product API
	 * webhooks are a few KB; anything larger is rejected cheaply to avoid
	 * CPU/memory abuse on this public endpoint.
	 */
	const MAX_WEBHOOK_BODY_BYTES = 262144; // 256 KB

	/** Activation lock time-to-live in seconds — a lock row older than this is reclaimable. */
	const ACTIVATION_LOCK_TTL = 60;

	/** Option-name prefix for the per-mode atomic lock guarding finalize_onboarding(). */
	const FINALIZE_LOCK_PREFIX = 'tva_paypal_finalizing_';

	/**
	 * How long (seconds) a pending-onboarding record stays actionable. PayPal's
	 * provisioning lag resolves in seconds-to-minutes; past this the onboarding
	 * session is dead, so we stop finalizing/retrying and also stop honoring the
	 * unsigned webhook nudge (bounds the nudge window) — the merchant re-onboards.
	 */
	const PENDING_ONBOARDING_TTL = 600;

	/** PayPal Resolution Center — surfaced alongside any refund error in the admin UI. */
	const RESOLUTION_CENTER_URL = 'https://www.paypal.com/resolutioncenter';

	/**
	 * Register all REST routes for the PayPal gateway.
	 */
	public function register_routes() {
		$credentials_endpoint      = Credentials::get_credentials_endpoint( 'test' );
		$credentials_endpoint_live = Credentials::get_credentials_endpoint( 'live' );

		// Admin routes gate on [ TVA_Product, has_access ] — the same capability
		// (current_user_can( <apprentice cap> ), filterable via thrive_has_access_*)
		// the Stripe and Square gateway controllers use for connect/disconnect.
		// This keeps PayPal consistent with the sibling gateways rather than a
		// stricter, gateway-specific manage_options gate.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/connect_account', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_connect_account_link' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'mode' => [
						'required' => false,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/disconnect', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'disconnect' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/status', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/refresh_status', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'refresh_status' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/settings', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'setting' => [ 'required' => true, 'type' => 'string' ],
					'value'   => [ 'required' => true ],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/checkout_method', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_checkout_method' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'method'  => [ 'required' => true, 'type' => 'string' ],
					'enabled' => [ 'required' => true ],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/apple_pay_reverify', [
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'reverify_apple_pay_domain' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/' . $credentials_endpoint, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_paypal_return' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_credentials' ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/' . $credentials_endpoint_live, [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle_paypal_return' ],
				'permission_callback' => '__return_true',
			],
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_credentials' ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/webhook', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'webhook_listener' ],
				'permission_callback' => '__return_true',
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/subscriptions/(?P<id>[A-Za-z0-9_-]+)/activate', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'activate_subscription' ],
				// Login + nonce (was login-only). No JS caller today — the redirect return
				// handler activates server-side — but align the gate now.
				'permission_callback' => [ $this, 'check_logged_in_rest_nonce' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get_pricing', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_pricing' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/selected_price', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_selected_price' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'price'      => [
						'required' => false,
						'type'     => 'object',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/prepopulate_email', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'prepopulate_email' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'state'      => [
						'required' => false,
						'type'     => 'boolean',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/client_reference', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'client_reference' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'state'      => [
						'required' => false,
						'type'     => 'boolean',
					],
					'type'       => [
						'required' => false,
						'type'     => 'string',
						'enum'     => [ 'user_id', 'user_name' ],
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/trial_setting', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'trial_setting' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'state'      => [
						'required' => false,
						'type'     => 'boolean',
					],
					'trial_days' => [
						'required'          => false,
						'type'              => 'integer',
						'validate_callback' => static function ( $value ) {
							// Strict integer >= 0: FILTER_VALIDATE_INT rejects floats and numeric
							// strings like "1.0" that loose comparison would let through (and
							// clamp_trial_days() would then silently truncate).
							return false !== filter_var( $value, FILTER_VALIDATE_INT ) && (int) $value >= 0;
						},
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/create_page', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_page' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'title' => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		// Buyer-facing embedded-checkout endpoints. Guest checkout is allowed (same
		// as Stripe/Square): no login required, but check_rest_nonce() enforces the
		// wp_rest nonce the SDK JS sends as X-WP-Nonce so these state-changing routes
		// cannot be called cross-site. The buyer is created/matched from their email
		// after payment.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/product_config', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'product_config' ],
				'permission_callback' => [ $this, 'check_rest_nonce' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/create_order', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_order' ],
				'permission_callback' => [ $this, 'check_rest_nonce' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'email'      => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					],
					'source'     => [
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					],
					'vault'      => [
						'required'          => false,
						'type'              => 'boolean',
						'default'           => false,
						'sanitize_callback' => 'rest_sanitize_boolean',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/capture_order', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'capture_order' ],
				'permission_callback' => [ $this, 'check_rest_nonce' ],
				'args'                => [
					'order_id'     => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
					'tva_order_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'confirmed'    => [
						'required' => false,
						'type'     => 'boolean',
						'default'  => false,
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/subscriptions/(?P<id>[\d]+)/cancel', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'cancel_subscription_request' ],
				'permission_callback' => [ $this, 'check_logged_in_rest_nonce' ],
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );

		// Merchant-initiated full refund from the TA order/customer admin. Admin-gated
		// like the other PayPal management routes. See refund() for the 7-code matcher.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/refund', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'refund' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'order_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'user_id'  => [
						'required'          => false,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/subscriptions/checkout', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_subscription_checkout' ],
				// Login + nonce enforced in the permission layer (not just the callback body)
				// so the gate can't be silently dropped by a refactor: a subscription's vault
				// must be tied to a real WP user, and the return handler verifies ownership.
				'permission_callback' => [ $this, 'check_logged_in_rest_nonce' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
					'return_to'  => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					],
				],
			],
		] );

		// Buyer-facing subscription pricing for the RBM consent modal. The admin
		// get_pricing endpoint is GET + TVA_Product::has_access (admin-gated), so
		// buyers can't use it; this is a POST, nonce-protected, subscription-only
		// read that exposes only the display fields the modal renders.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/subscriptions/pricing', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_subscription_pricing' ],
				'permission_callback' => [ $this, 'check_rest_nonce' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			],
		] );

		// Buyer-facing Pay Later messaging config for the product-page banner. POST,
		// nonce-protected, one-time-only; returns just the clientId/amount/currency the
		// PayPal "messages" SDK needs (or success:false when the toggle is off or the
		// product is ineligible). Mirrors subscriptions/pricing.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/pay_later_config', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'pay_later_config' ],
				'permission_callback' => [ $this, 'check_rest_nonce' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'integer',
					],
				],
			],
		] );

		// Admin poll endpoint: drive a pending onboarding to completion.
		// The settings page polls this after the PayPal return redirect lands with
		// a 202 (merchant not yet provisioned) until it gets 'done' or 'failed'.
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/onboarding/finalize', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'finalize_onboarding_endpoint' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );
	}

	/**
	 * permission_callback for the buyer-facing embedded-checkout endpoints.
	 *
	 * These routes allow guest checkout (no login required) but are state-changing,
	 * so they must not be callable cross-site. The SDK JS sends the wp_rest nonce as
	 * the X-WP-Nonce header; verifying it here provides CSRF protection that
	 * __return_true would not (WordPress does not enforce the nonce on its own for a
	 * fully public route).
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function check_rest_nonce( WP_REST_Request $request ): bool {
		return false !== wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' );
	}

	/**
	 * permission_callback for buyer-facing endpoints that require a logged-in user.
	 *
	 * Enforces login in the permission layer (not only the callback body) so the gate
	 * cannot be silently dropped by a future refactor, plus the wp_rest nonce for CSRF.
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function check_logged_in_rest_nonce( WP_REST_Request $request ): bool {
		return is_user_logged_in() && $this->check_rest_nonce( $request );
	}

	// -------------------------------------------------------------------------
	// Callbacks
	// -------------------------------------------------------------------------

	/**
	 * Return the config the embedded-checkout modal needs to boot the PayPal JS
	 * SDK and run the create_order -> capture_order flow.
	 *
	 * Mirrors Square's product_config: the buy button (js/frontend.js) fetches
	 * this on click, opens the modal, loads the SDK with these values and renders
	 * the buttons + card fields. Guest checkout allowed; Checkout::get_config()
	 * validates the product (connected mode, one-time price) and returns null —
	 * answered here as 400 — when it cannot be checked out via the embedded flow.
	 *
	 * @param WP_REST_Request $request product_id.
	 *
	 * @return WP_REST_Response
	 */
	public function product_config( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );

		$config = \TVA\PayPal\Checkout::get_config( $product_id );
		if ( $config === null ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'PayPal checkout is not available for this product.', 'thrive-apprentice' ) ], 400 );
		}

		$config['success'] = true;

		return new WP_REST_Response( $config );
	}

	/**
	 * Return the product's PayPal price catalog and the id of the selected price.
	 *
	 * Mirrors the Square gateway's get_pricing endpoint: the admin pricing
	 * dropdown re-fetches this on every render so all prices the merchant has
	 * added persist across re-renders and reloads (PayPal has no external price
	 * catalog, so the list is stored locally — see Settings::get_prices()).
	 *
	 * The currently-selected price is folded in when missing from the catalog, so
	 * products saved before the catalog existed still show their one price.
	 *
	 * @param WP_REST_Request $request product_id.
	 *
	 * @return WP_REST_Response
	 */
	public function get_pricing( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'pricing' => [], 'selected_id' => '' ], 400 );
		}

		$pricing     = Settings::get_prices( $product_id );
		$selected    = Settings::get_selected_price( $product_id );
		$selected_id = $selected['id'] ?? '';
		$urls        = Settings::get_checkout_urls( $product_id );

		if ( '' !== $selected_id ) {
			$present = false;
			foreach ( $pricing as $price ) {
				if ( isset( $price['id'] ) && $selected_id === (string) $price['id'] ) {
					$present = true;
					break;
				}
			}
			if ( ! $present ) {
				$pricing[] = $selected;
			}
		}

		return new WP_REST_Response( [
			'success'                  => true,
			'pricing'                  => array_values( $pricing ),
			'selected_id'              => $selected_id,
			'success_url'              => $urls['success_url'],
			'cancel_url'               => $urls['cancel_url'],
			'prepopulate_email'        => (bool) get_term_meta( $product_id, 'tva_paypal_product_prepopulate_email', true ),
			'client_reference_enabled' => (bool) get_term_meta( $product_id, 'tva_paypal_product_client_reference_enabled', true ),
			'client_reference_type'    => $this->get_client_reference_type( $product_id ),
			'trial_enabled'            => (bool) get_term_meta( $product_id, 'tva_paypal_product_trial_enabled', true ),
			'trial_days'               => Settings::clamp_trial_days( get_term_meta( $product_id, 'tva_paypal_product_trial_days', true ) ),
		] );
	}

	/**
	 * Persist the product's "pre-populate email at checkout" setting.
	 *
	 * Mirrors the Square gateway's prepopulate_email endpoint. When enabled, the
	 * embedded checkout prefills a logged-in buyer's email (see
	 * TVA\PayPal\Checkout::prefill_email()); when off, the field starts blank.
	 *
	 * @param WP_REST_Request $request product_id + boolean state.
	 *
	 * @return WP_REST_Response
	 */
	public function prepopulate_email( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product', 'thrive-apprentice' ) ], 400 );
		}

		update_term_meta( $product_id, 'tva_paypal_product_prepopulate_email', $request->get_param( 'state' ) ? 1 : 0 );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Persist the product's "pass a client reference to checkout" setting.
	 *
	 * Mirrors the Stripe gateway's shipped feature: when enabled, the chosen buyer
	 * identifier (User ID or User name) is packed into the order's custom_id as JSON at
	 * create time (see resolve_client_reference() / pack_custom_id()), letting the
	 * merchant match the payment back to the user in PayPal reporting.
	 * Stored as two per-product term-meta values (not per-mode — the choice does not
	 * vary between sandbox and live).
	 *
	 * @param WP_REST_Request $request product_id + boolean state + reference type.
	 *
	 * @return WP_REST_Response
	 */
	public function client_reference( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		// Verify product_id is a real Thrive Apprentice product term (mirrors create_order)
		// so term-meta can't be written onto an arbitrary term (a tag, another plugin's term).
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product', 'thrive-apprentice' ) ], 400 );
		}

		// state is optional: only touch the enabled flag when it is actually sent, so a
		// type-only request can't implicitly disable the setting.
		$state = $request->get_param( 'state' );
		if ( null !== $state ) {
			update_term_meta( $product_id, 'tva_paypal_product_client_reference_enabled', $state ? 1 : 0 );
		}

		$type = $request->get_param( 'type' );
		if ( null !== $type ) {
			update_term_meta( $product_id, 'tva_paypal_product_client_reference_type', $this->sanitize_client_reference_type( $type ) );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Persist the per-product free-trial setting (toggle + days).
	 *
	 * Mirrors client_reference(): stores term meta directly. A persisted trial_days is
	 * floored to [1, Settings::TRIAL_DAYS_MAX] (the configured trial length is at least
	 * 1 day); negatives / non-integers are rejected by the route's validate_callback before
	 * we get here. Note clamp_trial_days() itself keeps a 0 floor — 0 is the "no trial"
	 * sentinel used elsewhere (get_product_rule()/checkout) — so the >= 1 minimum is applied
	 * here at persist time, not in the clamp helper.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function trial_setting( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product', 'thrive-apprentice' ) ], 400 );
		}

		$state = $request->get_param( 'state' );
		if ( null !== $state ) {
			update_term_meta( $product_id, 'tva_paypal_product_trial_enabled', $state ? 1 : 0 );
		}

		$days = $request->get_param( 'trial_days' );
		if ( null !== $days ) {
			// Minimum trial length is 1 day. clamp_trial_days() keeps a 0 floor (its 0 is the
			// "no trial" sentinel consumed by get_product_rule()/checkout), so enforce the
			// 1-day minimum here, when persisting a configured trial length.
			update_term_meta( $product_id, 'tva_paypal_product_trial_days', max( 1, Settings::clamp_trial_days( $days ) ) );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Read the saved client-reference type for a product, defaulting to 'user_id'.
	 *
	 * @param int $product_id Thrive Apprentice product term id.
	 *
	 * @return string 'user_id' or 'user_name'.
	 */
	protected function get_client_reference_type( $product_id ) {
		return Order_Payload::get_client_reference_type( $product_id );
	}

	/**
	 * Constrain a client-reference type to the supported values.
	 *
	 * @param mixed $type Raw stored/posted value.
	 *
	 * @return string 'user_id' or 'user_name'.
	 */
	protected function sanitize_client_reference_type( $type ) {
		return Order_Payload::sanitize_client_reference_type( $type );
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
	protected function resolve_client_reference( $product_id, $email = '' ) {
		return Order_Payload::resolve_client_reference( $product_id, $email );
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
	protected function truncate_for_paypal( $value, $length = 127 ) {
		return Order_Payload::truncate_for_paypal( $value, $length );
	}

	/**
	 * Pack order id + optional buyer reference into a JSON custom_id.
	 *
	 * Delegates to Order_Payload so the one-time and subscription flows stay byte-identical.
	 *
	 * @param array $data e.g. [ 'o' => 123, 'u' => '48746' ].
	 *
	 * @return string JSON string within Order_Payload::CUSTOM_ID_MAX_LENGTH bytes.
	 */
	protected function pack_custom_id( array $data ) {
		return Order_Payload::pack_custom_id( $data );
	}

	/**
	 * Persist the product's selected PayPal price to the integration=paypal entry
	 * in tva_rules — the lightweight write the access-rules UI uses when the
	 * merchant adds/selects (or clears) a price.
	 *
	 * Mirrors the Square gateway's selected_price endpoint: it writes only this
	 * product's paypal rule instead of re-saving the whole product (which would
	 * re-render the rules panel and collapse it). An empty/absent price clears the
	 * selection but leaves the price catalog intact. When a price is provided it is
	 * also added to the catalog (Settings::add_price de-dupes) so it stays in the
	 * dropdown after re-render.
	 *
	 * @param WP_REST_Request $request product_id + optional price { id, amount, currency, type, interval }.
	 *
	 * @return WP_REST_Response
	 */
	public function save_selected_price( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product', 'thrive-apprentice' ) ], 400 );
		}

		$price  = $request->get_param( 'price' );
		$price  = is_array( $price ) ? $price : [];
		$amount = isset( $price['amount'] ) ? (string) $price['amount'] : '';

		if ( '' === $amount ) {
			Settings::clear_product_rule( $product_id );
		} else {
			// Reject non-numeric / non-positive amounts: stored as-is, they are later cast
			// to float in checkout/order creation and would produce 0/NaN pricing or
			// invalid PayPal API calls.
			if ( ! is_numeric( $amount ) || (float) $amount <= 0 ) {
				return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid price', 'thrive-apprentice' ) ], 400 );
			}

			// Currency must be one PayPal supports, or the order/SDK call fails downstream.
			$currency = isset( $price['currency'] ) ? strtoupper( sanitize_text_field( $price['currency'] ) ) : '';
			if ( ! in_array( $currency, Settings::SUPPORTED_CURRENCIES, true ) ) {
				return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Unsupported currency', 'thrive-apprentice' ) ], 400 );
			}

			$normalized = [
				'id'       => isset( $price['id'] ) ? sanitize_text_field( $price['id'] ) : '',
				'amount'   => $amount,
				'currency' => $currency,
				'type'     => ( isset( $price['type'] ) && 'recurring' === $price['type'] ) ? 'recurring' : 'one_time',
				'interval' => isset( $price['interval'] ) ? sanitize_text_field( $price['interval'] ) : '',
			];

			Settings::save_product_rule( $product_id, $normalized );
			Settings::add_price( $product_id, $normalized );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Create a published WordPress page to use as a checkout success/cancel URL.
	 *
	 * Mirrors the Square gateway's create_page endpoint: takes a title, inserts a
	 * page, and returns its permalink so the admin UI can fill the URL field.
	 *
	 * @param WP_REST_Request $request The request containing the page title.
	 *
	 * @return WP_REST_Response
	 */
	public function create_page( WP_REST_Request $request ) {
		$title   = sanitize_text_field( $request->get_param( 'title' ) );
		$page_id = wp_insert_post( [
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => 'page',
		] );

		if ( $page_id ) {
			return new WP_REST_Response( [ 'success' => true, 'url' => get_permalink( $page_id ) ] );
		}

		return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Error creating page', 'thrive-apprentice' ) ], 400 );
	}

	/**
	 * Initiate the PayPal onboarding flow via the Product API (/onboarding/start).
	 *
	 * Generates a CSPRNG site secret, stores it, and asks the Product API for the
	 * PayPal signup URL. The referralToken is extracted from the returned URL and
	 * persisted so /onboarding/complete can use it later.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_connect_account_link( WP_REST_Request $request ) {
		$mode = sanitize_key( $request->get_param( 'mode' ) ?: 'live' );
		if ( ! in_array( $mode, [ 'live', 'test' ], true ) ) {
			$mode = 'live';
		}

		try {
			$secret = bin2hex( random_bytes( 16 ) ); // 32-char hex, CSPRNG
		} catch ( \Random\RandomException $e ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => __( 'Could not generate secure secret.', 'thrive-apprentice' ) ],
				500
			);
		}
		Credentials::save_site_secret( $secret, $mode );

		$site_url = $this->get_credentials_endpoint_url( $mode );

		$response = wp_remote_post(
			Connection::get_product_api_url() . Connection::ENDPOINT_ONBOARDING_START,
			[
				'headers'   => [ 'Content-Type' => 'application/json' ],
				'body'      => wp_json_encode( [ 'secret' => $secret, 'site_url' => $site_url ] ),
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => $response->get_error_message() ],
				500
			);
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$body      = is_array( $body ) ? $body : [];

		if ( $http_code !== 200 || empty( $body['url'] ) ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => __( 'Could not start PayPal onboarding.', 'thrive-apprentice' ) ],
				502
			);
		}

		// Extract referralToken from the PayPal redirect URL query string.
		$parsed = wp_parse_url( $body['url'] );
		wp_parse_str( is_array( $parsed ) ? ( $parsed['query'] ?? '' ) : '', $qs );
		$referral_token = sanitize_text_field( $qs['referralToken'] ?? '' );

		if ( ! empty( $referral_token ) ) {
			Credentials::save_referral_token( $referral_token, $mode );
		}

		return new WP_REST_Response( [ 'success' => true, 'url' => $body['url'] ] );
	}

	/**
	 * Handle the PayPal browser redirect after merchant approval.
	 *
	 * PayPal redirects to this endpoint with:
	 *   - merchantIdInPayPal: the real PayPal merchant ID to store
	 *   - merchantId: echo of the secret (used for CSRF check only)
	 *   - permissionsGranted: must be 'true'
	 *   - consentStatus: must be 'true'
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void Always exits via wp_safe_redirect() (browser GET endpoint).
	 */
	public function handle_paypal_return( WP_REST_Request $request ) {
		$merchant_id_in_paypal = sanitize_text_field( $request->get_param( 'merchantIdInPayPal' ) ?? '' );
		$merchant_id_echo      = sanitize_text_field( $request->get_param( 'merchantId' ) ?? '' );
		$permissions_granted   = sanitize_key( $request->get_param( 'permissionsGranted' ) ?? '' );
		$consent_status        = sanitize_key( $request->get_param( 'consentStatus' ) ?? '' );

		// The "slot" the onboarding ran under (which return endpoint PayPal hit).
		// This only tells us where the secret/referral token are stored — the real
		// environment (live vs sandbox) is reported by /onboarding/complete below,
		// since neither the start call nor this redirect carries it.
		$pending_mode = $this->resolve_mode_from_request( $request );

		// CSRF: Product API passes the secret to PayPal as tracking_id; PayPal echoes it back here as merchantId.
		// Validate format (32-char hex) before the timing-safe compare.
		$stored_secret = Credentials::get_site_secret( $pending_mode );
		if ( empty( $stored_secret )
			|| strlen( $merchant_id_echo ) !== Credentials::SITE_SECRET_LENGTH
			|| ! ctype_xdigit( $merchant_id_echo )
			|| ! hash_equals( $stored_secret, $merchant_id_echo ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
			exit;
		}

		if ( $permissions_granted !== 'true' || $consent_status !== 'true' ) {
			// Rollback: clear secret so onboarding can be retried.
			Credentials::save_site_secret( '', $pending_mode );
			wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
			exit;
		}

		// Call /onboarding/complete — use raw wp_remote_post (not Onboarding_Client; bearer gate blocks this).
		$referral_token = Credentials::get_referral_token( $pending_mode );
		$secret         = Credentials::get_site_secret( $pending_mode );

		$response = wp_remote_post(
			Connection::get_product_api_url() . Connection::ENDPOINT_ONBOARDING_COMPLETE,
			[
				'headers'   => [ 'Content-Type' => 'application/json' ],
				'body'      => wp_json_encode( [
					'secret'         => $secret,
					'merchant_id'    => $merchant_id_in_paypal,
					'referral_token' => $referral_token,
					'site_url'       => get_site_url(),
					'webhooks_url'   => rest_url( static::$namespace . static::$version . '/' . $this->base . '/webhook' ),
				] ),
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) ) {
			Credentials::save_site_secret( '', $pending_mode );
			wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
			exit;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		$body      = json_decode( wp_remote_retrieve_body( $response ), true );
		$body      = is_array( $body ) ? $body : [];

		if ( 202 === $http_code ) {
			// PayPal hasn't provisioned the merchant yet. Keep the secret, record the
			// pending state, grant nothing, and land on settings in the "finalizing"
			// state. Completion happens later via the webhook nudge or the JS poll.
			Credentials::save_onboarding_pending( $pending_mode, [
				'merchant_id'    => $merchant_id_in_paypal,
				'referral_token' => $referral_token,
				'webhooks_url'   => rest_url( static::$namespace . static::$version . '/' . $this->base . '/webhook' ),
				'site_url'       => get_site_url(),
				'created_at'     => time(),
				'attempts'       => 0,
			] );
			wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
			exit;
		}

		if ( 200 !== $http_code ) {
			// 422 mismatch or hard failure — clear secret so onboarding can be retried.
			Credentials::save_site_secret( '', $pending_mode );
			wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
			exit;
		}

		$this->store_onboarding_credentials( $body, $pending_mode, $secret, $referral_token, $merchant_id_in_paypal );

		// Clear any pending record from a prior 202 on this slot: a reload of the return
		// URL can reach this 200 path after the connection already finalized. Leaving the
		// record would keep the settings UI polling and could let a later finalize clear
		// the secret on a non-200, disconnecting an already-connected merchant. No-op when
		// none exists.
		Credentials::clear_onboarding_pending( $pending_mode );

		wp_safe_redirect( admin_url( 'admin.php?page=thrive_apprentice#settings/payments/paypal' ) );
		exit;
	}

	/**
	 * Persist credentials from a 200 /onboarding/complete response and warm caches.
	 * Single writer for connect — called by handle_paypal_return() (immediate 200)
	 * and finalize_onboarding() (nudge / poll / retry).
	 *
	 * @param array  $body                  Decoded 200 response body.
	 * @param string $pending_mode          Slot onboarding ran under.
	 * @param string $secret                Site secret used for this onboarding.
	 * @param string $referral_token        Referral token used for this onboarding.
	 * @param string $merchant_id_in_paypal The merchant's PayPal ID from the redirect.
	 * @return void
	 */
	protected function store_onboarding_credentials( array $body, string $pending_mode, string $secret, string $referral_token, string $merchant_id_in_paypal ): void {
		// The Product API reports the real PayPal environment in the response (it
		// is sandbox on staging, live on production). Store the connection under
		// the matching mode so the UI shows it under the correct Live/Sandbox card.
		// 'live' => live, anything else (incl. 'sandbox') => test. Fall back to the
		// onboarding slot if the field is missing, for backward compatibility.
		$env  = isset( $body['env'] ) ? sanitize_key( $body['env'] ) : '';
		$mode = 'live' === $env ? 'live' : ( 'sandbox' === $env ? 'test' : $pending_mode );

		// If the detected environment differs from the slot onboarding ran under,
		// move the secret + referral token to the correct mode and clear the old
		// slot so it isn't left looking half-connected.
		if ( $mode !== $pending_mode ) {
			Credentials::save_site_secret( $secret, $mode );
			Credentials::save_referral_token( $referral_token, $mode );
			Credentials::save_site_secret( '', $pending_mode );
			Credentials::save_referral_token( '', $pending_mode );
		}

		// Persist credentials from /onboarding/complete response.
		// Use the merchant's own PayPal ID (from the redirect) for auth — partner_merchant_id is Thrive's ID.
		Credentials::save_merchant_id( $merchant_id_in_paypal, $mode );
		if ( ! empty( $body['client_id'] ) ) {
			Credentials::save_client_id( sanitize_text_field( $body['client_id'] ), $mode );
		}
		if ( ! empty( $body['sdk_client_token'] ) ) {
			Credentials::save_sdk_token( sanitize_text_field( $body['sdk_client_token'] ), $mode );
		}
		if ( ! empty( $body['webhook_secret'] ) ) {
			Credentials::save_webhook_secret( sanitize_text_field( $body['webhook_secret'] ), $mode );
		}
		// Merchant country (ISO 3166-1 alpha-2) — used to gate the subscription RBM
		// usage_pattern to US sellers. Persist at connect to avoid a /merchant round-trip.
		if ( ! empty( $body['country'] ) ) {
			Credentials::save_merchant_country( sanitize_text_field( $body['country'] ), $mode );
		}

		// Pre-warm Bearer token transient via /auth/token (Basic auth: merchant_id:secret).
		$this->refresh_bearer_token( $mode );

		// Verify merchant is ready to receive payments via GET /merchant.
		$this->verify_merchant( $mode );

		Connection::reset();
	}

	/**
	 * Re-POST /onboarding/complete for a pending merchant and act on the result.
	 * The single completion path shared by the 202-redirect retry, the unsigned
	 * webhook nudge, the admin JS poll, and the page-load retry.
	 *
	 * @param string $mode 'live' | 'test'
	 * @return string 'done' (200, connected) | 'pending' (202) | 'failed' (422/other) | 'none' (nothing pending)
	 */
	public function finalize_onboarding( string $mode ): string {
		$pending = Credentials::get_onboarding_pending( $mode );
		if ( empty( $pending['merchant_id'] ) ) {
			return 'none';
		}

		// Bounded lifetime: once the record is older than PENDING_ONBOARDING_TTL the
		// onboarding session is dead. Stop retrying, clear the half-state so the
		// merchant can re-onboard cleanly, and report a terminal failure.
		if ( time() - (int) ( $pending['created_at'] ?? 0 ) > self::PENDING_ONBOARDING_TTL ) {
			Credentials::save_site_secret( '', $mode );
			Credentials::clear_onboarding_pending( $mode );
			return 'failed';
		}

		// Atomic lock so the four completion triggers (202 retry, webhook nudge, JS
		// poll, page-load retry) can never double-complete. Reuses the same
		// fast-path + stale-CAS-reclaim helper the REST activation path uses.
		$lock_key = self::FINALIZE_LOCK_PREFIX . $mode;
		if ( ! $this->acquire_activation_lock( $lock_key ) ) {
			return 'pending'; // another trigger holds the lock — still finalizing
		}

		// The lock is now owned. Release it in finally so a thrown exception in any owner
		// call (e.g. store_onboarding_credentials()) can't leak the lock until its TTL.
		try {
			$secret   = Credentials::get_site_secret( $mode );
			$response = wp_remote_post(
				Connection::get_product_api_url() . Connection::ENDPOINT_ONBOARDING_COMPLETE,
				[
					'headers'   => [ 'Content-Type' => 'application/json' ],
					'body'      => wp_json_encode( [
						'secret'         => $secret,
						'merchant_id'    => $pending['merchant_id'],
						'referral_token' => $pending['referral_token'] ?? '',
						'site_url'       => $pending['site_url'] ?? get_site_url(),
						'webhooks_url'   => $pending['webhooks_url'] ?? rest_url( static::$namespace . static::$version . '/' . $this->base . '/webhook' ),
					] ),
					'timeout'   => 15,
					'sslverify' => true,
				]
			);

			if ( is_wp_error( $response ) ) {
				return 'pending'; // transient network error — let a later trigger retry
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$body = is_array( $body ) ? $body : [];

			if ( 200 === $code ) {
				// A concurrent disconnect (another tab) can clear the pending record while
				// this finalize is mid-flight on the API call. Re-check before storing so we
				// don't re-connect a merchant who just disconnected.
				if ( empty( Credentials::get_onboarding_pending( $mode )['merchant_id'] ) ) {
					return 'none';
				}
				$this->store_onboarding_credentials( $body, $mode, $secret, $pending['referral_token'] ?? '', $pending['merchant_id'] );
				Credentials::clear_onboarding_pending( $mode );
				return 'done';
			}

			if ( 202 === $code ) {
				$pending['attempts'] = (int) ( $pending['attempts'] ?? 0 ) + 1;
				Credentials::save_onboarding_pending( $mode, $pending );
				return 'pending';
			}

			// 422 / any other code: mismatch or hard failure — clear secret + pending so
			// the merchant can cleanly re-onboard.
			Credentials::save_site_secret( '', $mode );
			Credentials::clear_onboarding_pending( $mode );
			return 'failed';
		} finally {
			delete_option( $lock_key );
		}
	}

	/**
	 * Admin poll endpoint: drive onboarding to completion. Returns the finalize
	 * status so the settings page can flip to connected / show an error / keep polling.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function finalize_onboarding_endpoint( WP_REST_Request $request ): WP_REST_Response {
		$mode = self::pending_onboarding_mode();
		if ( '' === $mode ) {
			return new WP_REST_Response( [ 'status' => 'none' ] );
		}
		return new WP_REST_Response( [ 'status' => $this->finalize_onboarding( $mode ) ] );
	}

	/**
	 * Retired endpoint — the old server-to-server service API callback.
	 *
	 * Returns 410 Gone so stale calls fail clearly.
	 *
	 * @return WP_REST_Response
	 */
	public function save_credentials( WP_REST_Request $request ) {
		return new WP_REST_Response(
			[ 'success' => false, 'message' => __( 'This endpoint is no longer active.', 'thrive-apprentice' ) ],
			410
		);
	}

	/**
	 * Remove all stored PayPal credentials and notify the Product API.
	 *
	 * @return WP_REST_Response
	 */
	public function disconnect() {
		// D17 wind-down: cancel every active vault subscription at the end of its current
		// billing period (no further renewals). MUST run before Credentials::disconnect()
		// clears the credentials — the cancel calls need a live bearer token. Subscriber
		// notification email is deferred (not part of this flow yet).
		// Capture the active count first so the response can report any subscriptions whose
		// remote cancel failed — those keep billing at PayPal and the merchant must know.
		$active_before   = \TVA\PayPal\Subscription_Winddown::count_active();
		$cancelled       = \TVA\PayPal\Subscription_Winddown::run();
		$winddown_failed = max( 0, $active_before - $cancelled );

		// Best-effort: tell Product API to clean up server-side merchant state.
		foreach ( [ 'live', 'test' ] as $mode ) {
			$bearer = $this->get_cached_bearer( $mode );
			if ( ! empty( $bearer ) ) {
				wp_remote_request(
					Connection::get_product_api_url() . Connection::ENDPOINT_MERCHANT,
					[
						'method'    => 'DELETE',
						'headers'   => [ 'Authorization' => 'Bearer ' . $bearer ],
						'timeout'   => 10,
						'sslverify' => true,
					]
				);
			}
		}

		Credentials::disconnect();

		foreach ( [ 'live', 'test' ] as $mode ) {
			Credentials::clear_onboarding_pending( $mode );
			// Drop any finalize lock so an in-flight finalize that already passed lock
			// acquisition can't outlive the disconnect (its post-API pending re-check
			// then bails). Cleared record + dropped lock together close the window.
			delete_option( self::FINALIZE_LOCK_PREFIX . $mode );
		}

		Connection::reset();

		return new WP_REST_Response( [
			'success'         => true,
			'winddown_failed' => $winddown_failed,
		] );
	}

	/**
	 * The mode with a pending onboarding, or '' if none. Used to render the
	 * "finalizing" settings state and to drive the admin poll.
	 *
	 * @return string 'live' | 'test' | ''
	 */
	public static function pending_onboarding_mode(): string {
		// Only one onboarding runs at a time, so the first of [ live, test ] with a
		// pending record wins.
		foreach ( [ 'live', 'test' ] as $mode ) {
			if ( ! empty( Credentials::get_onboarding_pending( $mode )['merchant_id'] ) ) {
				return $mode;
			}
		}
		return '';
	}

	/**
	 * Return connection status for both live and test modes.
	 *
	 * @return WP_REST_Response
	 */
	public function get_status() {
		$live_enabled = Credentials::is_mode_enabled( 'live' );
		$test_enabled = Credentials::is_mode_enabled( 'test' );
		$pending_mode = self::pending_onboarding_mode();

		return new WP_REST_Response( [
			'success'      => $live_enabled || $test_enabled,
			'live_enabled' => $live_enabled,
			'test_enabled' => $test_enabled,
			'pending'      => '' !== $pending_mode ? [ 'mode' => $pending_mode ] : false,
		] );
	}

	/**
	 * Re-fetch merchant readiness from the Product API for connected modes and
	 * return the fresh snapshot. Lets the settings page reflect changes made on
	 * PayPal's side since the last load (e.g. the merchant confirming their
	 * primary email, or a capability becoming active) without a reconnect.
	 *
	 * @return WP_REST_Response
	 */
	public function refresh_status() {
		foreach ( [ 'live', 'test' ] as $mode ) {
			if ( ! Credentials::is_mode_enabled( $mode ) ) {
				continue;
			}

			// The bearer may have expired since the last call — re-warm it first.
			if ( empty( $this->get_cached_bearer( $mode ) ) ) {
				$this->refresh_bearer_token( $mode );
			}

			$this->verify_merchant( $mode );
		}

		return new WP_REST_Response( [
			'live_enabled'            => Credentials::is_mode_enabled( 'live' ),
			'test_enabled'            => Credentials::is_mode_enabled( 'test' ),
			'primary_email_confirmed' => Credentials::is_email_confirmed(),
			'payments_receivable'     => Credentials::is_payments_receivable(),
			'capabilities_summary'    => Credentials::get_capabilities_summary(),
			'vetting_status'          => [
				'live' => Credentials::get_vetting_status( 'live' ),
				'test' => Credentials::get_vetting_status( 'test' ),
			],
		] );
	}

	/**
	 * Persist a default-options toggle (Pay Later messaging, auto-display buy
	 * button, FraudNet). Validated against the Settings toggle allowlist.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$setting = sanitize_key( $request->get_param( 'setting' ) );
		// rest_sanitize_boolean so the string 'false' (untyped REST param) is not cast truthy.
		$value   = rest_sanitize_boolean( $request->get_param( 'value' ) );

		if ( ! Settings::is_toggle( $setting ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid setting', 'thrive-apprentice' ) ], 400 );
		}

		// Store as int 0/1, never bool false: update_option( $key, false ) is a no-op when the
		// option doesn't exist yet (WP short-circuits on `false === $old_value`), so turning a
		// default-on toggle (Pay Later messaging, FraudNet) off the first time would never
		// persist and it would revert to its default on reload. 0 always writes.
		Settings::update_setting( $setting, $value ? 1 : 0 );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Persist whether an approved funding method is shown at checkout.
	 * Rejects unknown keys and the always-on PayPal Wallet method.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function save_checkout_method( WP_REST_Request $request ) {
		$method  = sanitize_text_field( $request->get_param( 'method' ) );
		// rest_sanitize_boolean so the string 'false' (untyped REST param) is not cast truthy.
		$enabled = rest_sanitize_boolean( $request->get_param( 'enabled' ) );

		if ( ! Settings::set_checkout_method( $method, $enabled ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid payment method', 'thrive-apprentice' ) ], 400 );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Admin action: force a fresh Apple Pay domain re-verification (deregister →
	 * uninstall → install). Returns success, or the failure message for the UI.
	 *
	 * @param WP_REST_Request $request Unused.
	 * @return WP_REST_Response
	 */
	public function reverify_apple_pay_domain( WP_REST_Request $request ) {
		try {
			\TVA\PayPal\Apple_Pay::reverify();
		} catch ( \Throwable $e ) {
			\TVA\PayPal\Apple_Pay::record_error( $e->getMessage() );
			return new WP_REST_Response( [ 'success' => false, 'message' => $e->getMessage() ], 422 );
		}
		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Receive incoming PayPal webhook events.
	 *
	 * Delegates signature verification, idempotency checking, and event dispatch
	 * to TVA_PayPal_Payment_Gateway. Always returns 200 so PayPal does not retry.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function webhook_listener( WP_REST_Request $request ) {
		$raw_body  = $request->get_body();
		$signature = (string) $request->get_header( 'x-thrive-webhook-signature' );

		// Cheap rejects on a public endpoint, BEFORE any HMAC compute or JSON decode:
		// a missing signature can never verify, and a legitimate webhook is only a few KB.
		// Return 200 (and do NOT log) so this guard can't be turned into a log-flood vector.
		if ( '' === $signature || strlen( $raw_body ) > self::MAX_WEBHOOK_BODY_BYTES ) {
			return new WP_REST_Response( [ 'success' => true ] );
		}

		TVA_PayPal_Payment_Gateway::handle( $raw_body, $signature, $this->detect_mode() );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Activate a subscription order after buyer approval.
	 *
	 * Requires authenticated buyer (permission_callback: is_user_logged_in).
	 * Called by JS onApprove after PayPal redirects back.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function activate_subscription( WP_REST_Request $request ): WP_REST_Response {
		$subscription_id = (string) $request->get_param( 'id' );
		$mode            = $this->get_subscription_stored_mode( $subscription_id ) ?: $this->detect_mode();

		// Verify the subscription belongs to a pending order owned by the current user.
		if ( ! $this->current_user_owns_subscription( $subscription_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Subscription not found.' ], 403 );
		}

		// Atomic idempotency lock. Unlike a get/set_transient pair (check-then-set, which two
		// concurrent requests can both pass before either writes), acquire_activation_lock()
		// uses add_option() — a DB INSERT that fails when the row already exists — so only one
		// of N concurrent requests can win. This genuinely blocks concurrent double-activation
		// (double-click, browser retry, duplicate onApprove callbacks), not just sequential.
		$lock_key = 'tva_paypal_activating_' . $subscription_id;
		if ( ! $this->acquire_activation_lock( $lock_key ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Activation already in progress.' ], 409 );
		}

		$result = \TVA\PayPal\Request::activate_subscription( $subscription_id, $mode );

		if ( is_wp_error( $result ) ) {
			// Leave the lock in place on error so an immediate client retry can't double-activate
			// if the call actually succeeded server-side despite returning an error. The lock is
			// self-expiring: acquire_activation_lock() reclaims a row older than the TTL.
			return new WP_REST_Response(
				[ 'success' => false, 'message' => $result->get_error_message() ],
				502
			);
		}

		// Persist the activation call's debug id on the order's vault option so the capture
		// listener (which runs when the first-charge webhook completes the order) can write it
		// onto the transaction row. The vault first charge completes via webhook, which carries
		// no PayPal-Debug-Id of its own — this is the order's most-recent transaction-affecting call.
		$debug_id = \TVA\PayPal\Http\Vault_Client::get_instance( $mode )->get_last_debug_id();
		$sub_order_id = $this->get_subscription_order_id( $subscription_id );
		if ( '' !== $debug_id && $sub_order_id > 0 ) {
			$vault             = get_option( 'tva_paypal_vault_' . $sub_order_id, [] );
			$vault             = is_array( $vault ) ? $vault : [];
			$vault['debug_id'] = $debug_id;
			update_option( 'tva_paypal_vault_' . $sub_order_id, $vault, false );
		}

		delete_option( $lock_key );

		return new WP_REST_Response( [ 'success' => true, 'data' => $result ] );
	}

	/**
	 * Buyer-facing pricing for the RBM consent modal.
	 *
	 * The admin get_pricing endpoint is GET + admin-gated; this is the buyer-safe
	 * equivalent: nonce-protected, subscription-products only, and it returns only
	 * the public display fields (id, amount, currency, type, interval) the modal
	 * renders — never the admin-only prepopulate_email / client_reference fields.
	 * Shape matches get_pricing ({ success, pricing[], selected_id }) so the JS
	 * parses both identically.
	 *
	 * @param WP_REST_Request $request product_id.
	 * @return WP_REST_Response
	 */
	public function get_subscription_pricing( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_REST_Response( [ 'success' => false, 'pricing' => [], 'selected_id' => '' ], 400 );
		}

		$rule = Settings::get_product_rule( $product_id );
		if ( empty( $rule ) || empty( $rule['is_subscription'] ) ) {
			return new WP_REST_Response( [ 'success' => false, 'pricing' => [], 'selected_id' => '' ], 400 );
		}

		$pricing     = Settings::get_prices( $product_id );
		$selected    = Settings::get_selected_price( $product_id );
		$selected_id = $selected['id'] ?? '';

		// Fold the selected price into the list if the catalog doesn't carry it
		// (products saved before the price catalog existed) — same as get_pricing.
		if ( '' !== $selected_id ) {
			$present = false;
			foreach ( $pricing as $price ) {
				if ( isset( $price['id'] ) && $selected_id === (string) $price['id'] ) {
					$present = true;
					break;
				}
			}
			if ( ! $present ) {
				$pricing[] = $selected;
			}
		}

		// Expose only the display fields the consent modal needs.
		$public = array_map( static function ( $p ) {
			return [
				'id'       => $p['id'] ?? '',
				'amount'   => $p['amount'] ?? '',
				'currency' => $p['currency'] ?? '',
				'type'     => $p['type'] ?? '',
				'interval' => $p['interval'] ?? '',
			];
		}, array_values( $pricing ) );

		return new WP_REST_Response( [
			'success'     => true,
			'pricing'     => $public,
			'selected_id' => $selected_id,
			'trial_days'  => (int) ( $rule['trial_days'] ?? 0 ),
		] );
	}

	/**
	 * Buyer-facing Pay Later messaging config for the product-page banner.
	 *
	 * Returns the clientId/amount/currency the PayPal "messages" SDK needs to render the
	 * banner above a one-time product's buy button, or success:false when the merchant's
	 * "Show Pay Later messaging" toggle is off or the product is ineligible (not connected,
	 * no one-time price). The frontend renders nothing on success:false.
	 *
	 * @param WP_REST_Request $request product_id.
	 * @return WP_REST_Response
	 */
	public function pay_later_config( WP_REST_Request $request ): WP_REST_Response {
		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_REST_Response( [ 'success' => false ], 400 );
		}

		$config = \TVA\PayPal\Checkout::pay_later_config( $product_id );
		if ( null === $config ) {
			return new WP_REST_Response( [ 'success' => false ] );
		}

		return new WP_REST_Response( [ 'success' => true, 'config' => $config ] );
	}

	/**
	 * Buyer-facing endpoint that creates a PayPal vault subscription after the
	 * RBM consent step and returns the payer-action URL for the JS to redirect to.
	 *
	 * Login required (the vault is tied to a WP user; the return handler verifies
	 * ownership). Reuses Buy_Now\Paypal::get_url() -> get_subscription_url(), which
	 * creates the pending TVA_Order, calls POST /subscriptions with
	 * application_context.return_url, and persists the subscription_id <-> order mapping.
	 *
	 * @param WP_REST_Request $request product_id, return_to.
	 * @return WP_REST_Response
	 */
	public function create_subscription_checkout( WP_REST_Request $request ): WP_REST_Response {
		if ( ! is_user_logged_in() ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Please log in to subscribe.', 'thrive-apprentice' ) ], 401 );
		}

		$product_id = (int) $request->get_param( 'product_id' );
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product.', 'thrive-apprentice' ) ], 400 );
		}

		$rule = Settings::get_product_rule( $product_id );
		if ( empty( $rule ) || empty( $rule['is_subscription'] ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'This product has no PayPal subscription price.', 'thrive-apprentice' ) ], 400 );
		}

		// Reject new subscription checkouts once PayPal is fully disconnected (D17): with no
		// merchant credentials the subscription cannot be billed, so fail explicitly here
		// rather than letting get_url() return an empty string and surface a generic error.
		// Guard on BOTH modes — detect_mode() falls back to 'test', so checking a single mode
		// would wrongly block (or allow) on dual-mode setups where only one side is connected.
		if ( ! Credentials::is_mode_enabled( 'live' ) && ! Credentials::is_mode_enabled( 'test' ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Subscriptions are no longer available for this product.', 'thrive-apprentice' ) ], 409 );
		}

		$mode = $this->detect_mode();

		$return_to = (string) $request->get_param( 'return_to' );

		$paypal = new \TVA\Buy_Now\Paypal( [
			'product_id' => $product_id,
			'mode'       => $mode,
			'return_to'  => $return_to,
		] );

		$url = $paypal->get_url(); // routes to get_subscription_url() for a subscription rule

		if ( empty( $url ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Could not start the subscription. Please try again.', 'thrive-apprentice' ) ], 502 );
		}

		return new WP_REST_Response( [ 'success' => true, 'payer_action_url' => $url ] );
	}

	/**
	 * Create a pending TVA_Order + matching PayPal order for the embedded checkout.
	 *
	 * One-time products only (subscriptions use the vault redirect flow). Mirrors
	 * Buy_Now\Paypal::get_subscription_url(): a PENDING TVA_Order with a product
	 * item is created first, then its DB id is stamped into the PayPal order as
	 * thrive_order_id so the PAYMENT.CAPTURE.COMPLETED webhook can match and
	 * complete it. The PayPal order id is stored locally for the capture-time
	 * price-swap guard. Access is NEVER granted here — it is granted at capture
	 * time (capture_order) and by the webhook, idempotently.
	 *
	 * @param WP_REST_Request $request product_id.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_order( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );
		$user_id    = get_current_user_id();
		$mode       = $this->detect_mode();

		// Verify product_id is a real Thrive Apprentice product term (not just > 0).
		if ( $product_id <= 0 || ! ( get_term( $product_id, \TVA\Product::TAXONOMY_NAME ) instanceof WP_Term ) ) {
			return new WP_Error( 'tva_paypal_invalid_product', 'Invalid product.', [ 'status' => 400 ] );
		}

		// Resolve the buyer email for guest checkout (matches Stripe/Square): use the
		// logged-in user's email when available, else the email entered on the page.
		// Stored on the pending order so the capture webhook can create/match the WP
		// user from it regardless of payment method.
		$email = sanitize_email( (string) $request->get_param( 'email' ) );
		if ( $user_id > 0 && empty( $email ) ) {
			$email = wp_get_current_user()->user_email;
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'tva_paypal_email_required', 'A valid email address is required.', [ 'status' => 400 ] );
		}

		$rule = Settings::get_product_rule( $product_id );
		if ( empty( $rule ) || empty( $rule['amount'] ) || empty( $rule['currency'] ) ) {
			return new WP_Error( 'tva_paypal_no_price', 'No PayPal price configured for this product.', [ 'status' => 400 ] );
		}

		// Subscriptions use the vault redirect flow, not the embedded checkout.
		if ( ! empty( $rule['is_subscription'] ) ) {
			return new WP_Error( 'tva_paypal_subscription_unsupported', 'Subscriptions are not supported by the embedded checkout.', [ 'status' => 400 ] );
		}

		// Pending order with a product item (item required so the webhook can read product_id).
		// user_id is 0 for guests — the webhook creates/matches the user from buyer_email.
		$pending = new \TVA_Order();
		$pending->set_user_id( $user_id );
		$pending->set_buyer_email( $email );
		$pending->set_status( \TVA_Const::STATUS_PENDING );
		$pending->set_gateway( \TVA_Const::PAYPAL_GATEWAY );
		$pending->set_payment_method( \TVA_Const::PAYPAL_GATEWAY );
		$pending->set_type( \TVA_Order::PAID );
		$pending->set_price( (float) $rule['amount'] );
		$pending->set_currency( $rule['currency'] );

		$item = new \TVA_Order_Item();
		$item->set_product_id( $product_id );
		// $product_id is a Thrive Apprentice product term id, not a post id, so use the
		// term name (get_the_title() would look up an unrelated post or return empty).
		$product_title = ( new \TVA\Product( $product_id ) )->get_name();
		$item->set_product_name( $product_title ?: 'PayPal order' );
		$item->set_quantity( 1 );
		$item->set_unit_price( (float) $rule['amount'] );
		$item->set_product_price( (float) $rule['amount'] );
		$item->set_total_price( (float) $rule['amount'] );
		$item->set_currency( $rule['currency'] );
		$pending->set_order_item( $item );

		$pending->save();
		$thrive_order_id = (int) $pending->get_id();

		if ( $thrive_order_id <= 0 ) {
			return new WP_Error( 'tva_paypal_order_create_failed', 'Could not create the order.', [ 'status' => 500 ] );
		}

		// Pack the TVA order id (always, for webhook correlation) plus the optional buyer
		// reference into custom_id as JSON, instead of using invoice_id. custom_id has no
		// PayPal duplicate-blocking (unlike invoice_id) and the order id keeps it unique,
		// so repeat purchases never collide. The reference ('u') is truncated to fit the
		// custom_id limit (dropped only as a last resort).
		$custom           = [ 'o' => $thrive_order_id ];
		$client_reference = $this->resolve_client_reference( $product_id, $email );
		if ( '' !== $client_reference ) {
			$custom['u'] = $client_reference;
		}
		$custom_id = $this->pack_custom_id( $custom );

		// PayPal requires monetary values as a fixed 2-decimal string ("9.90", not "9.9"
		// or "10"); normalize once and reuse so amount.value, breakdown.item_total and
		// items[].unit_amount stay byte-identical and pass DECIMAL_PRECISION validation.
		$amount = number_format( (float) $rule['amount'], 2, '.', '' );

		// Product API contract (https://thrive-ppcp.pages.dev/v1/): the order body is
		// wrapped in { "data": ... } with intent + purchase_units. thrive_order_id is sent
		// top-level as the primary correlation key; custom_id is the fallback.
		$order_data = [
			'data' => [
				'intent'          => 'CAPTURE',
				'thrive_order_id' => $thrive_order_id,
				'payer'           => [
					'email_address' => $email,
				],
				'purchase_units'  => [
					[
						'amount'       => [
							'currency_code' => $rule['currency'],
							'value'         => $amount,
							// item_total must equal the sum of items[].unit_amount * quantity.
							// For our single qty-1 line that equals the order amount; PayPal
							// rejects the order if items are present without a matching breakdown.
							'breakdown'     => [
								'item_total' => [
									'currency_code' => $rule['currency'],
									'value'         => $amount,
								],
							],
						],
						'reference_id' => (string) $product_id,
						'custom_id'    => $custom_id,
						'description'  => sanitize_text_field( $rule['description'] ?? '' ),
						// Line item so the product name/SKU show in PayPal's order details and
						// the buyer's receipt. Single qty-1 item; unit_amount matches the total.
						'items'        => [
							[
								'name'        => $this->truncate_for_paypal( sanitize_text_field( $product_title ?: 'PayPal order' ) ),
								// Order-type line shown in PayPal's transaction details, mirroring the
								// subscription payload's items[].description (see Buy_Now\Paypal).
								'description' => __( 'One-time purchase', 'thrive-apprentice' ),
								'quantity'    => '1',
								'unit_amount' => [
									'currency_code' => $rule['currency'],
									'value'         => $amount,
								],
								'sku'         => (string) $product_id,
							],
						],
					],
				],
			],
		];

		// Build payment_source by funding source. createOrder() is shared by the wallet /
		// Pay Later / Venmo buttons, the card fields, and Google Pay; the frontend now sends
		// `source` (the funding source the buyer clicked) so we attach the right attributes.
		$source     = sanitize_key( (string) $request->get_param( 'source' ) );
		// Vault-with-purchase opt-in: only for a logged-in buyer (we store the resulting
		// customer.id against the WP user). Gated again here even though the frontend hides
		// the opt-in for guests.
		$want_vault = rest_sanitize_boolean( $request->get_param( 'vault' ) ) && $user_id > 0;

		if ( 'card' === $source ) {
			// Card path always carries the 3D Secure (SCA) verification contingency: SCA_ALWAYS
			// when the merchant's "Require 3D Secure on every card payment" toggle is on, else
			// SCA_WHEN_REQUIRED (PayPal's default). Depends on the gateway forwarding payment_source.
			// Card vaulting (#3890): when the buyer opted in, the vault attributes (incl. customer.id)
			// are merged into the same card.attributes block.
			$verification = [
				'verification' => [
					'method' => Settings::is_enabled( Settings::SCA_ALWAYS ) ? 'SCA_ALWAYS' : 'SCA_WHEN_REQUIRED',
				],
			];
			$card_attributes = $want_vault
				? array_merge( $verification, $this->build_vault_attributes( $user_id, $mode ) )
				: $verification;

			$order_data['data']['payment_source'] = [ 'card' => [ 'attributes' => $card_attributes ] ];
		} elseif ( in_array( $source, [ 'paypal', 'venmo' ], true ) && $want_vault ) {
			// Vault-with-purchase for the PayPal wallet (#3886) or Venmo (#3889): same attributes
			// shape under the funding source's own payment_source key. (Venmo capture can't be
			// completed in PayPal's sandbox, but the vault attributes + customer.id are sent.)
			$order_data['data']['payment_source'] = [
				$source => [ 'attributes' => $this->build_vault_attributes( $user_id, $mode ) ],
			];
		}

		// PayPal-Request-Id makes order creation idempotent: a timeout+retry or a duplicate
		// createOrder for the same TVA order returns the same PayPal order instead of a new one.
		$response = \TVA\PayPal\Request::create_order( $order_data, $mode, [ 'PayPal-Request-Id' => 'tva_order_' . $thrive_order_id ] );

		// The order id may be top-level or nested under a `data` envelope.
		$paypal_order_id = '';
		if ( is_array( $response ) ) {
			$paypal_order_id = (string) ( $response['id'] ?? ( $response['data']['id'] ?? '' ) );
		}

		if ( is_wp_error( $response ) || $paypal_order_id === '' ) {
			$pending->set_status( \TVA_Const::STATUS_FAILED );
			$pending->save();

			// Log the upstream detail server-side; never return it to the (unauthenticated)
			// buyer — the raw Product-API response can expose internal middleware structure.
			// Use a 4xx status (not 5xx): a 5xx origin response is swapped for the CDN error
			// page, masking the JSON and breaking the PayPal SDK's JSON.parse.
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'create_order_failed', [
				'order_id'   => $thrive_order_id,
				'product_id' => $product_id,
				'error'      => is_wp_error( $response ) ? $response->get_error_message() : 'no order id returned',
				'error_data' => is_wp_error( $response ) ? $response->get_error_data() : $response,
			], true );

			return new WP_Error( 'tva_paypal_create_order_failed', __( 'We could not start the PayPal checkout. Please try again.', 'thrive-apprentice' ), [ 'status' => 400 ] );
		}

		$paypal_order_id = sanitize_text_field( $paypal_order_id );

		// Stored for the capture-time price-swap / id-match guard.
		update_option( 'tva_paypal_order_' . $thrive_order_id, [
			'paypal_order_id' => $paypal_order_id,
			'product_id'      => $product_id,
			'amount'          => (string) $rule['amount'],
			'currency'        => (string) $rule['currency'],
			'mode'            => $mode,
		], false );

		return new WP_REST_Response( [ 'id' => $paypal_order_id, 'tva_order_id' => $thrive_order_id ] );
	}

	/**
	 * Capture a previously created PayPal order for the embedded checkout.
	 *
	 * Validates ownership and that the submitted PayPal order id matches the one
	 * stored at create time (price-swap guard), then calls the Product API capture.
	 * On a COMPLETED capture it completes the pending order and grants access
	 * synchronously via the shared capture-completed code path — idempotent with the
	 * PAYMENT.CAPTURE.COMPLETED webhook (keyed on x_thrive_order_id / capture id), so
	 * the buyer isn't left waiting on webhook delivery. On unrecoverable failure the
	 * order is marked STATUS_FAILED.
	 *
	 * @param WP_REST_Request $request order_id (PayPal), tva_order_id.
	 * @return WP_REST_Response|WP_Error
	 */
	public function capture_order( WP_REST_Request $request ) {
		$paypal_order_id = (string) $request->get_param( 'order_id' );
		$tva_order_id    = (int) $request->get_param( 'tva_order_id' );

		if ( $tva_order_id <= 0 || $paypal_order_id === '' ) {
			return new WP_Error( 'tva_paypal_invalid_capture', 'Invalid capture request.', [ 'status' => 400 ] );
		}

		$order = new \TVA_Order( $tva_order_id );
		if ( ! $order->get_id() ) {
			return new WP_Error( 'tva_paypal_order_not_found', 'Order not found.', [ 'status' => 404 ] );
		}

		// Guest checkout: no ownership check (the buyer may not be logged in). The
		// price-swap guard below is the integrity check — the PayPal order id is a
		// server-generated value returned only to the buyer who created this order.

		// Price-swap / id-match guard: the submitted PayPal order id must equal the
		// one stored when this TVA order was created.
		$stored                 = get_option( 'tva_paypal_order_' . $tva_order_id, [] );
		$stored_paypal_order_id = is_array( $stored ) ? (string) ( $stored['paypal_order_id'] ?? '' ) : '';

		// Missing guard (e.g. evicted) is recoverable — do NOT fail the order (the webhook can
		// still complete it); just decline to grant synchronously. A mismatch is the tamper
		// case (a PayPal order id that isn't the one we created for this TVA order) → fail.
		if ( $stored_paypal_order_id === '' ) {
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'capture_guard_missing', [ 'order_id' => $tva_order_id, 'paypal_order_id' => $paypal_order_id ], true );
			return new WP_Error( 'tva_paypal_order_unverified', __( 'We could not verify this order. If you were charged, your access will be granted shortly.', 'thrive-apprentice' ), [ 'status' => 409 ] );
		}

		if ( ! hash_equals( $stored_paypal_order_id, $paypal_order_id ) ) {
			$order->set_status( \TVA_Const::STATUS_FAILED );
			$order->save();
			delete_option( 'tva_paypal_order_' . $tva_order_id );
			return new WP_Error( 'tva_paypal_order_mismatch', 'Order verification failed.', [ 'status' => 409 ] );
		}

		// Atomic idempotency lock (same mechanism as activate_subscription): add_option()'s
		// INSERT fails when the row already exists, so only one of N concurrent captures
		// proceeds. Without it a double-click / duplicate onApprove can double-grant, or the
		// 2nd capture fails on PayPal's already-captured order and flips a COMPLETED order to
		// FAILED (buyer paid, locked out). Self-expiring via the TTL reclaim in the helper.
		$lock_key = 'tva_paypal_capturing_' . $tva_order_id;
		if ( ! $this->acquire_activation_lock( $lock_key ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Capture already in progress.' ], 409 );
		}

		// Idempotency: only act on a still-pending order. The capture may already be
		// done — either the PAYMENT.CAPTURE.COMPLETED webhook completed it first, or a
		// concurrent capture_order call did. Access is granted by whichever runs first
		// (synchronously here and in the webhook), so this just no-ops.
		if ( $order->get_status() !== \TVA_Const::STATUS_PENDING ) {
			delete_option( $lock_key );
			delete_option( 'tva_paypal_order_' . $tva_order_id );

			// Only report success when the order is actually COMPLETED. A FAILED (or other
			// non-completable) order must not return success:true — that would show the buyer
			// "Payment successful" and redirect without granting access.
			if ( \TVA_Const::STATUS_COMPLETED === $order->get_status() ) {
				return new WP_REST_Response( [ 'success' => true, 'already_processed' => true ] );
			}

			return new WP_Error( 'tva_paypal_order_not_completable', __( 'This order can no longer be completed.', 'thrive-apprentice' ), [ 'status' => 409 ] );
		}

		$mode = is_array( $stored ) && ! empty( $stored['mode'] ) ? (string) $stored['mode'] : $this->detect_mode();

		// Client-confirmed flows (Google Pay) capture client-side via
		// paypal.Googlepay().confirmOrder(); re-capturing an already-captured order errors.
		// Read the order instead, and only capture here if it was confirmed but not yet captured.
		if ( $request->get_param( 'confirmed' ) ) {
			// get_order() resets the client's last_debug_id; if the APPROVED branch then calls
			// capture_order() it resets + sets it again, so the capture's debug id is the one that
			// wins below (intended — that's the call that actually took the payment).
			$response = \TVA\PayPal\Request::get_order( $paypal_order_id, $mode );
			$peek     = ( is_array( $response ) && isset( $response['data'] ) && is_array( $response['data'] ) ) ? $response['data'] : ( is_array( $response ) ? $response : [] );
			if ( ! is_wp_error( $response ) && 'APPROVED' === (string) ( $peek['status'] ?? '' ) ) {
				$response = \TVA\PayPal\Request::capture_order( $paypal_order_id, $mode );
			}
		} else {
			$response = \TVA\PayPal\Request::capture_order( $paypal_order_id, $mode );
		}

		// Status/capture data may be top-level or nested under a `data` envelope.
		$capture_body = ( is_array( $response ) && isset( $response['data'] ) && is_array( $response['data'] ) ) ? $response['data'] : ( is_array( $response ) ? $response : [] );
		// Prefer the capture-level status: the order-level `status` can read COMPLETED while
		// the capture itself is PENDING (e.g. PENDING_REVIEW). Fall back to the order status.
		$capture        = $capture_body['purchase_units'][0]['payments']['captures'][0] ?? [];
		$capture_status = (string) ( ( is_array( $capture ) ? ( $capture['status'] ?? '' ) : '' ) ?: ( $capture_body['status'] ?? '' ) );

		// Non-COMPLETED outcomes. The guiding rule: never mark a paid order COMPLETED here,
		// and never brick a buyer who paid or can retry. Only a definitive FAILED status is
		// terminal; everything else leaves the order PENDING for the webhook to resolve.
		// All error statuses are 4xx — a 5xx origin response is swapped for the CDN error
		// page, masking the JSON body and breaking the PayPal SDK's JSON.parse.
		if ( is_wp_error( $response ) || $capture_status !== 'COMPLETED' ) {

			// Pending settlement / under review (e.g. PENDING_REVIEW, eCheck): the
			// PAYMENT.CAPTURE.COMPLETED / .DENIED webhook resolves it and grants access.
			// Leave the order PENDING and the guard option intact.
			if ( ! is_wp_error( $response ) && in_array( $capture_status, [ 'PENDING', 'IN_PROGRESS' ], true ) ) {
				delete_option( $lock_key );
				return new WP_REST_Response( [ 'success' => true, 'pending' => true ] );
			}

			// Retriable decline (INSTRUMENT_DECLINED / DECLINED): the buyer can retry with a
			// different instrument. Leave the order PENDING + guard so a retry reuses it, and
			// release the lock so the retry isn't blocked.
			if ( $this->is_retriable_decline( $response, $capture_status ) ) {
				delete_option( $lock_key );
				\TVA_Logger::set_type( 'PayPal' );
				\TVA_Logger::log( 'capture_declined', [ 'order_id' => $tva_order_id, 'paypal_order_id' => $paypal_order_id ], true );
				$this->record_failed_paypal_transaction( $tva_order_id, $paypal_order_id, $response, $capture_body, $mode );
				return new WP_Error( 'tva_paypal_instrument_declined', __( 'Your payment was declined. Please try a different payment method.', 'thrive-apprentice' ), [ 'status' => 402 ] );
			}

			// Definitive FAILED status (no payment taken): terminal.
			if ( ! is_wp_error( $response ) && 'FAILED' === $capture_status ) {
				$this->record_failed_paypal_transaction( $tva_order_id, $paypal_order_id, $response, $capture_body, $mode );
				$order->set_status( \TVA_Const::STATUS_FAILED );
				$order->save();
				delete_option( $lock_key );
				delete_option( 'tva_paypal_order_' . $tva_order_id );
				return new WP_Error( 'tva_paypal_capture_failed', __( 'PayPal could not process this payment.', 'thrive-apprentice' ), [ 'status' => 402 ] );
			}

			// Indeterminate (network / 5xx / token / unknown status): the capture may actually
			// have succeeded, so do NOT fail the order — leave it PENDING for the webhook to
			// complete. Log the detail server-side; return a generic message (never the raw
			// upstream response — that would leak internal structure to the buyer).
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'capture_indeterminate', [
				'order_id'        => $tva_order_id,
				'paypal_order_id' => $paypal_order_id,
				'status'          => $capture_status,
				'error'           => is_wp_error( $response ) ? $response->get_error_message() : 'non-completed capture status',
				'error_data'      => is_wp_error( $response ) ? $response->get_error_data() : $capture_body,
			], true );
			delete_option( $lock_key );
			return new WP_Error( 'tva_paypal_capture_failed', __( 'We could not confirm your payment. If you were charged, your access will be granted shortly.', 'thrive-apprentice' ), [ 'status' => 400 ] );
		}

		$capture_id = sanitize_text_field(
			$capture_body['purchase_units'][0]['payments']['captures'][0]['id'] ?? ( $capture_body['id'] ?? '' )
		);

		if ( Settings::is_enabled( Settings::SCA_ALWAYS ) ) {
			$liability_shift = (string) ( $capture_body['payment_source']['card']['authentication_result']['liability_shift'] ?? '' );
			if ( 'NO' === $liability_shift ) {
				$order->set_status( \TVA_Const::STATUS_FAILED );
				$order->save();
				delete_option( $lock_key );
				delete_option( 'tva_paypal_order_' . $tva_order_id );
				\TVA_Logger::set_type( 'PayPal' );
				\TVA_Logger::log( 'capture_liability_shift_no', [
					'order_id'        => $tva_order_id,
					'paypal_order_id' => $paypal_order_id,
					'capture_id'      => $capture_id,
				], true );
				return new WP_Error(
					'tva_paypal_sca_required',
					__( 'This payment could not be completed because 3D Secure authentication was not confirmed. Please try again.', 'thrive-apprentice' ),
					[ 'status' => 402 ]
				);
			}
		}

		// A COMPLETED order should always carry a capture id. If it doesn't (e.g. a GET /orders
		// body on the confirmed/Google Pay path that omits the captures array), the debug-id
		// stamp below silently no-ops — log it so the gap is visible rather than invisible.
		if ( '' === $capture_id ) {
			\TVA_Logger::set_type( 'PayPal' );
			\TVA_Logger::log( 'capture_id_missing', [ 'order_id' => $tva_order_id, 'paypal_order_id' => $paypal_order_id ], true );
		}

		// Grant access synchronously instead of waiting on the PAYMENT.CAPTURE.COMPLETED
		// webhook, so the buyer has access the moment the redirect lands and isn't left
		// on locked content if the webhook is delayed or never delivered. This reuses
		// the exact webhook code path and is idempotent with it: whichever runs first
		// completes this pending order, the other no-ops.
		\TVA\PayPal\Events\Payment_Capture_Completed::fulfill_from_capture_response( $tva_order_id, $capture_body, $mode );

		// The capture row was just created by the completion listener; stamp the debug id
		// from the synchronous capture call onto it (header captured in Base_Client).
		$debug_id = \TVA\PayPal\Http\Order_Client::get_instance( $mode )->get_last_debug_id();
		if ( '' !== $debug_id && '' !== $capture_id ) {
			\TVA_Transaction::set_paypal_debug_id( $capture_id, $debug_id );
		}

		// One-shot guard + lock fulfilled — drop them so the options table doesn't accumulate rows.
		delete_option( $lock_key );
		delete_option( 'tva_paypal_order_' . $tva_order_id );

		// Vault-with-purchase: when the capture vaulted a method, the Product API returns a
		// clean top-level paypal_customer_id. Store it against the logged-in buyer so future
		// vaults attach to the same PayPal customer (see Credentials::save_customer_id).
		$paypal_customer_id = isset( $capture_body['paypal_customer_id'] )
			? sanitize_text_field( (string) $capture_body['paypal_customer_id'] )
			: '';
		$buyer_user_id = (int) $order->get_user_id();
		if ( $paypal_customer_id !== '' && $buyer_user_id > 0 ) {
			Credentials::save_customer_id( $buyer_user_id, $paypal_customer_id, $mode );
		}

		return new WP_REST_Response( [
			'success'        => true,
			'capture_id'     => $capture_id,
			// Buyer-facing payment source (venmo / paypal / card / apple_pay / google_pay) so the
			// embedded success state can name how the buyer paid (e.g. "Paid with Venmo").
			'payment_source' => $this->detect_payment_source( $capture_body ),
		] );
	}

	/**
	 * Build the payment_source vault attributes for a vault-with-purchase order.
	 *
	 * Shared by the PayPal-wallet (#3886), Venmo (#3889), and card (#3890) paths. Saves the method on a
	 * successful capture; attaches the buyer's existing PayPal customer.id (when stored) so a
	 * new method joins the same customer instead of minting a duplicate (#3885). usage_type
	 * MERCHANT — confirm PLATFORM vs MERCHANT with the Product API dev before go-live.
	 *
	 * @param int    $user_id Logged-in buyer's WP user id.
	 * @param string $mode    'live' or 'test'.
	 * @return array attributes payload (vault [+ customer]).
	 */
	protected function build_vault_attributes( int $user_id, string $mode ): array {
		$attributes = [
			'vault' => [
				'store_in_vault'                 => 'ON_SUCCESS',
				'usage_type'                     => 'MERCHANT',
				'permit_multiple_payment_tokens' => false,
			],
		];

		$existing_customer_id = Credentials::get_customer_id( $user_id, $mode );
		if ( $existing_customer_id !== '' ) {
			$attributes['customer'] = [ 'id' => $existing_customer_id ];
		}

		return $attributes;
	}

	/**
	 * Identify the funding source from a PayPal capture (or order) response.
	 *
	 * PayPal returns exactly one key under `payment_source` naming how the buyer paid
	 * (venmo, paypal, card, apple_pay, google_pay). Returns that key, or '' when absent
	 * (e.g. a GET /orders body that omits payment_source). The frontend maps it to a label.
	 *
	 * @param array $capture_body Decoded PayPal capture/order response.
	 *
	 * @return string One of venmo|paypal|card|apple_pay|google_pay, or '' when unknown.
	 */
	private function detect_payment_source( array $capture_body ): string {
		$sources = $capture_body['payment_source'] ?? [];
		if ( ! is_array( $sources ) ) {
			return '';
		}

		// Check wallets/card before paypal: a Venmo/Apple Pay/Google Pay capture is its own
		// top-level key, and paypal is the catch-all fallback for a plain PayPal-balance payment.
		// Use key presence (not truthiness): PayPal can return an empty object for the active key
		// (e.g. payment_source: { paypal: {} }), which empty() would wrongly treat as absent.
		foreach ( [ 'venmo', 'apple_pay', 'google_pay', 'card', 'paypal' ] as $key ) {
			if ( array_key_exists( $key, $sources ) ) {
				return $key;
			}
		}

		return '';
	}

	/**
	 * Whether a failed capture is a buyer-retriable decline (use another instrument)
	 * rather than a terminal failure.
	 *
	 * A DECLINED capture status, or a PayPal 422 whose error body carries an
	 * INSTRUMENT_DECLINED issue, means the buyer can simply retry with a different card —
	 * so the pending order must be kept, not failed.
	 *
	 * @param array|\WP_Error $response       Raw capture response.
	 * @param string          $capture_status Resolved capture-level status.
	 * @return bool
	 */
	protected function is_retriable_decline( $response, string $capture_status ): bool {
		if ( 'DECLINED' === $capture_status ) {
			return true;
		}

		if ( ! is_wp_error( $response ) ) {
			return false;
		}

		$data = $response->get_error_data();
		$body = ( is_array( $data ) && isset( $data['body'] ) && is_array( $data['body'] ) ) ? $data['body'] : [];

		foreach ( (array) ( $body['details'] ?? [] ) as $detail ) {
			if ( is_array( $detail ) && isset( $detail['issue'] ) && 'INSTRUMENT_DECLINED' === $detail['issue'] ) {
				return true;
			}
		}

		return isset( $body['name'] ) && 'INSTRUMENT_DECLINED' === $body['name'];
	}

	/**
	 * Record a FAILED PayPal transaction row for a declined/failed capture, carrying the
	 * debug id so the IWT "declined payment" case has a queryable audit trail.
	 *
	 * Uses the capture id when the response carries one, otherwise the PayPal order id, as
	 * transaction_id. The upsert (keyed on transaction_id) refreshes a single row across
	 * retries rather than multiplying rows.
	 *
	 * @param int             $tva_order_id    Local order id.
	 * @param string          $paypal_order_id PayPal order id (fallback transaction id).
	 * @param array|\WP_Error $response        Raw capture response.
	 * @param array           $capture_body    Decoded capture body (empty for WP_Error).
	 * @param string          $mode            'live' or 'test'.
	 *
	 * @return void
	 */
	protected function record_failed_paypal_transaction( $tva_order_id, $paypal_order_id, $response, array $capture_body, $mode ) {
		$capture_id = sanitize_text_field(
			$capture_body['purchase_units'][0]['payments']['captures'][0]['id'] ?? ( $capture_body['id'] ?? '' )
		);
		$transaction_id = '' !== $capture_id ? $capture_id : $paypal_order_id;

		if ( is_wp_error( $response ) ) {
			$data     = $response->get_error_data();
			$debug_id = is_array( $data ) ? (string) ( $data['debug_id'] ?? '' ) : '';
		} else {
			$debug_id = \TVA\PayPal\Http\Order_Client::get_instance( $mode )->get_last_debug_id();
		}

		$order = new \TVA_Order( $tva_order_id );
		// Guard: without a loaded order, get_currency()/get_price() return ''/0 and we'd record a
		// misleading blank FAILED row. capture_order validates the order before reaching here, so
		// this only fires defensively.
		if ( ! $order->get_id() ) {
			return;
		}

		\TVA_Transaction::record_paypal( array(
			'order_id'         => $tva_order_id,
			'transaction_id'   => $transaction_id,
			'currency'         => (string) $order->get_currency(),
			'price'            => (string) $order->get_price(),
			'transaction_type' => \TVA_Const::STATUS_FAILED,
			'debug_id'         => $debug_id,
		) );
	}

	/**
	 * Handle a buyer-initiated subscription cancellation request.
	 *
	 * Requires authenticated buyer (permission_callback: is_user_logged_in).
	 * Verifies the order belongs to the current user, cancels the Product API
	 * subscription, marks the order failed, and revokes access.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function cancel_subscription_request( WP_REST_Request $request ): WP_REST_Response {
		$order_id = (int) $request->get_param( 'id' );
		if ( $order_id <= 0 ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Invalid order ID.' ], 400 );
		}

		if ( ! $this->current_user_owns_active_order( $order_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Order not found.' ], 403 );
		}

		$order = new TVA_Order( $order_id );
		if ( ! $order->get_id() ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Order not found.' ], 404 );
		}

		$user_id     = (int) $order->get_user_id();
		$user        = get_userdata( $user_id );
		$order_items = $order->get_order_items();
		$first_item  = ! empty( $order_items ) ? $order_items[0] : null;

		if ( ! $user ) {
			TVA_Logger::set_type( 'PayPal Buyer Cancel' );
			TVA_Logger::log( 'buyer_cancel_user_not_found', [ 'order_id' => $order_id ], true );
			return new WP_REST_Response( [ 'success' => false, 'message' => 'User not found.' ], 500 );
		}

		$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );

		if ( empty( $vault ) ) {
			TVA_Logger::set_type( 'PayPal Buyer Cancel' );
			TVA_Logger::log( 'buyer_cancel_no_vault_record', [ 'order_id' => $order_id ], true );
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Subscription data not found.' ], 400 );
		}

		$subscription_id = $vault['subscription_id'] ?? '';
		$mode            = ( $vault['mode'] ?? '' ) ?: $this->detect_mode();

		if ( ! empty( $subscription_id ) ) {
			$cancel_result = \TVA\PayPal\Request::cancel_subscription( $subscription_id, $mode );
			if ( is_wp_error( $cancel_result ) ) {
				TVA_Logger::set_type( 'PayPal Buyer Cancel' );
				TVA_Logger::log( 'buyer_cancel_subscription_api_failed', [
					'order_id'        => $order_id,
					'subscription_id' => $subscription_id,
					'error'           => $cancel_result->get_error_message(),
				], true );
				// Non-fatal: continue to revoke local access even if API call failed.
			}
		}

		$order->set_status( \TVA_Const::STATUS_FAILED );
		$save_result = $order->save();

		// Revoke access regardless of save result — the remote subscription is already cancelled
		// at this point, so leaving access granted while save failed would be the worst inconsistency.
		if ( $first_item !== null ) {
			do_action( 'tva_subscription_cancelled', $user, $first_item, $order );

			$product_id = (int) $first_item->get_product_id();
			if ( $product_id > 0 ) {
				\Thrive_Apprentice_API::revoke_access( $user_id, $product_id, 'paypal_buyer_cancel' );
			}
		}

		do_action( 'tva_paypal_access_revoked', $order, [] );

		if ( false === $save_result ) {
			TVA_Logger::set_type( 'PayPal Buyer Cancel' );
			TVA_Logger::log( 'buyer_cancel_order_save_failed', [ 'order_id' => $order_id ], true );
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Failed to update order status.' ], 500 );
		}

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Merchant-initiated full refund of a PayPal order from the TA admin.
	 *
	 * Looks up the capture ID stored on the order (payment_id), calls the Product API
	 * refund, and on success applies the same side-effects as the refund webhook —
	 * STATUS_REFUND, access revocation, and vault-subscription cancellation — via
	 * Payment_Capture_Refunded::apply_full_refund() (idempotent with the webhook).
	 *
	 * On failure the PayPal error is translated to a human-readable message and paired
	 * with the Resolution Center link so the admin knows where to follow up.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function refund( WP_REST_Request $request ) {
		$order_id = (int) $request->get_param( 'order_id' );

		if ( $order_id <= 0 ) {
			return new WP_Error( 'invalid_order', __( 'Invalid order.', 'thrive-apprentice' ), [ 'status' => 400 ] );
		}

		$order = new TVA_Order( $order_id );

		if ( ! $order->get_id() ) {
			return new WP_Error( 'order_not_found', __( 'Order not found.', 'thrive-apprentice' ), [ 'status' => 404 ] );
		}

		// Defence-in-depth: this endpoint is admin-gated, but when the UI supplies the member
		// being edited we reject any order_id that does not belong to them — so a mistyped /
		// stale request can never refund a different customer's order.
		$member_id = (int) $request->get_param( 'user_id' );
		if ( $member_id > 0 && (int) $order->get_user_id() !== $member_id ) {
			return new WP_Error( 'order_user_mismatch', __( 'Order does not belong to this member.', 'thrive-apprentice' ), [ 'status' => 403 ] );
		}

		if ( $order->get_gateway() !== TVA_Const::PAYPAL_GATEWAY ) {
			return new WP_Error( 'not_paypal_order', __( 'This order was not paid through PayPal and cannot be refunded here.', 'thrive-apprentice' ), [ 'status' => 400 ] );
		}

		if ( (int) $order->get_status() === TVA_Const::STATUS_REFUND ) {
			return new WP_Error( 'already_refunded', __( 'This order has already been refunded.', 'thrive-apprentice' ), [ 'status' => 409 ] );
		}

		$capture_id = (string) $order->get_payment_id();

		if ( $capture_id === '' ) {
			return new WP_Error(
				'no_capture',
				__( 'No PayPal payment was captured for this order, so there is nothing to refund.', 'thrive-apprentice' ),
				[ 'status' => 400 ]
			);
		}

		// Vault (subscription) orders store their mode; one-time orders fall back to the
		// currently-enabled mode.
		$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );

		if ( is_array( $vault ) && ! empty( $vault['mode'] ) ) {
			$mode = $vault['mode'];
		} else {
			// One-time order — no vault record. Fall back to the currently-active mode. This is
			// wrong if the site toggled live/test since the original transaction was captured, so
			// log it: a refund_api_failed entry that follows points straight at the cause.
			$mode = $this->detect_mode();
			TVA_Logger::set_type( 'PayPal Refund' );
			TVA_Logger::log( 'refund_mode_fallback', [ 'order_id' => $order_id, 'mode' => $mode ], true );
		}

		$result = \TVA\PayPal\Request::refund_order( $capture_id, $mode );

		if ( is_wp_error( $result ) ) {
			$matched = $this->match_refund_error( $result );

			TVA_Logger::set_type( 'PayPal Refund' );
			TVA_Logger::log( 'refund_api_failed', [
				'order_id'   => $order_id,
				'capture_id' => $capture_id,
				'code'       => $matched['code'],
				'error'      => $result->get_error_message(),
			], true );

			return new WP_Error(
				'refund_failed',
				$matched['message'],
				[
					// 422 (not 5xx): this is a domain error whose JSON body the admin UI must
					// read. Cloudflare / nginx fastcgi_intercept_errors replace 5xx response
					// bodies with a generic error page, which would strip the specific message
					// and Resolution Center link before they reach the browser.
					'status'                => 422,
					'paypal_code'           => $matched['code'],
					'resolution_center_url' => self::RESOLUTION_CENTER_URL,
				]
			);
		}

		\TVA\PayPal\Events\Payment_Capture_Refunded::apply_full_refund( $order, $mode );

		return new WP_REST_Response( [ 'success' => true, 'order_id' => $order_id ] );
	}

	/**
	 * Translate a PayPal refund-capture error into a human-readable message.
	 *
	 * PayPal reports the failure reason as an `issue` code inside `details[]` (and
	 * sometimes as the top-level `name`). The Product API client surfaces the decoded
	 * body under the WP_Error 'body' data key. We scan that body for the seven known
	 * refund issue codes and fall back to a generic message for anything else.
	 *
	 * @param WP_Error $error
	 * @return array{code:string,message:string}
	 */
	protected function match_refund_error( WP_Error $error ): array {
		$messages = [
			'CANNOT_BE_REFUNDED'             => __( 'This transaction can no longer be refunded — it may be too old or already settled in a way that blocks refunds.', 'thrive-apprentice' ),
			'INSUFFICIENT_FUNDS'             => __( 'The refund could not be completed because there are insufficient funds in the PayPal account.', 'thrive-apprentice' ),
			'PAYMENT_REFUND_NOT_ALLOWED'     => __( 'Refunds are not allowed for this payment.', 'thrive-apprentice' ),
			'MERCHANT_REFUND_LIMIT_EXCEEDED' => __( 'The refund limit for this PayPal account has been exceeded.', 'thrive-apprentice' ),
			'TRANSACTION_ALREADY_REFUNDED'   => __( 'This transaction has already been refunded.', 'thrive-apprentice' ),
			'REFUND_NOT_PERMITTED'           => __( 'Refunds are not permitted for this account or transaction.', 'thrive-apprentice' ),
			'PAYEE_ACCOUNT_RESTRICTED'       => __( 'The PayPal account is currently restricted and cannot process refunds.', 'thrive-apprentice' ),
		];

		$data = $error->get_error_data();
		$body = ( is_array( $data ) && isset( $data['body'] ) && is_array( $data['body'] ) ) ? $data['body'] : [];

		// Collect every candidate code: explicit details[].issue, the top-level name,
		// then a flattened-string fallback so a wrapped/unexpected shape still matches.
		$candidates = [];

		if ( ! empty( $body['details'] ) && is_array( $body['details'] ) ) {
			foreach ( $body['details'] as $detail ) {
				if ( ! empty( $detail['issue'] ) ) {
					$candidates[] = (string) $detail['issue'];
				}
			}
		}

		if ( ! empty( $body['name'] ) ) {
			$candidates[] = (string) $body['name'];
		}

		foreach ( $candidates as $candidate ) {
			if ( isset( $messages[ $candidate ] ) ) {
				return [ 'code' => $candidate, 'message' => $messages[ $candidate ] ];
			}
		}

		// Fallback: substring scan of the raw body for any known code.
		$haystack = wp_json_encode( $body );
		if ( is_string( $haystack ) ) {
			foreach ( $messages as $code => $message ) {
				if ( strpos( $haystack, $code ) !== false ) {
					return [ 'code' => $code, 'message' => $message ];
				}
			}
		}

		return [
			'code'    => 'UNKNOWN',
			'message' => __( 'The refund could not be processed by PayPal. Please try again or review the transaction in the PayPal Resolution Center.', 'thrive-apprentice' ),
		];
	}

	/**
	 * Atomically acquire the per-subscription activation lock.
	 *
	 * Backed by add_option(): the INSERT fails (returns false) when the row already exists,
	 * because option_name is uniquely indexed. Two concurrent requests therefore cannot both
	 * acquire it — closing the check-then-set race a get/set_transient pair would leave open.
	 *
	 * The stored value is the acquire timestamp. add_option() has no TTL, so a request that
	 * dies mid-activation would otherwise leave the lock forever; instead a row older than
	 * ACTIVATION_LOCK_TTL is treated as stale and reclaimed. The reclaim path is the only
	 * place a race can reappear, but it is reachable only after the previous activation has
	 * already finished or died — the millisecond-scale double-click window is fully closed.
	 *
	 * @param string $lock_key
	 * @return bool True when this request acquired the lock.
	 */
	protected function acquire_activation_lock( string $lock_key ): bool {
		// Fast path. add_option() autoloads 'yes' by default; pass 'no' so these short-lived
		// locks never bloat the alloptions cache. The INSERT fails atomically when the row
		// already exists, so concurrent double-clicks / duplicate onApprove callbacks can never
		// both acquire a fresh lock.
		if ( add_option( $lock_key, (string) time(), '', 'no' ) ) {
			return true;
		}

		$acquired_at = (int) get_option( $lock_key, 0 );
		if ( $acquired_at <= 0 || ( time() - $acquired_at ) < static::ACTIVATION_LOCK_TTL ) {
			return false;
		}

		// Stale lock — the previous owner died mid-activation. Reclaim it with an atomic
		// compare-and-swap: the UPDATE only matches while option_value still equals the
		// timestamp we just read, so of two concurrent reclaimers exactly one query changes a
		// row and wins. (A plain get_option/update_option pair would let both proceed.)
		global $wpdb;
		$reclaimed = $wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s",
			(string) time(),
			$lock_key,
			(string) $acquired_at
		) );

		if ( 1 === $reclaimed ) {
			// The raw UPDATE bypassed the object cache — drop the stale cached value.
			wp_cache_delete( $lock_key, 'options' );
			return true;
		}

		return false;
	}

	/**
	 * Return the currently-enabled mode ('live' or 'test').
	 *
	 * Only one mode can be active at a time — enforced by the admin UI.
	 * is_mode_enabled() checks merchant_id + site_secret presence.
	 *
	 * @return string 'live' or 'test'
	 */
	protected function detect_mode(): string {
		return Credentials::is_mode_enabled( 'live' ) ? 'live' : 'test';
	}

	/**
	 * Check that a subscription ID is tied to a pending order owned by the current user.
	 *
	 * Prevents a logged-in user from activating subscriptions belonging to others.
	 *
	 * @param string $subscription_id
	 * @return bool
	 */
	protected function current_user_owns_subscription( string $subscription_id ): bool {
		if ( empty( $subscription_id ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		global $wpdb;
		$table       = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;
		$pending_ids = $wpdb->get_col( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE user_id = %d AND gateway = %s AND status = %d',
			$user_id,
			\TVA_Const::PAYPAL_GATEWAY,
			\TVA_Const::STATUS_PENDING
		) );

		foreach ( $pending_ids as $order_id ) {
			$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );
			if ( ( $vault['subscription_id'] ?? '' ) === $subscription_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check that an active PayPal order belongs to the currently logged-in user.
	 *
	 * Prevents a logged-in user from cancelling orders belonging to others.
	 *
	 * @param int $order_id
	 * @return bool
	 */
	protected function current_user_owns_active_order( int $order_id ): bool {
		if ( $order_id <= 0 ) {
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;
		$row   = $wpdb->get_row( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE ID = %d AND user_id = %d AND gateway = %s AND status IN (%d, %d)',
			$order_id,
			get_current_user_id(),
			\TVA_Const::PAYPAL_GATEWAY,
			\TVA_Const::STATUS_COMPLETED,
			\TVA_Const::STATUS_GRACE_PERIOD
		) );

		return null !== $row;
	}

	/**
	 * Return the stored mode ('live' or 'test') for a subscription owned by the current user.
	 *
	 * Reads the vault option written at buy-now time. Falls back to detect_mode()
	 * at the call site if the option is missing or mode is empty.
	 *
	 * @param string $subscription_id
	 * @return string 'live', 'test', or '' when not found.
	 */
	private function get_subscription_stored_mode( string $subscription_id ): string {
		if ( empty( $subscription_id ) ) {
			return '';
		}

		$user_id = get_current_user_id();

		global $wpdb;
		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;
		$ids   = $wpdb->get_col( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE user_id = %d AND gateway = %s AND status = %d',
			$user_id,
			\TVA_Const::PAYPAL_GATEWAY,
			\TVA_Const::STATUS_PENDING
		) );

		foreach ( $ids as $order_id ) {
			$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );
			if ( ( $vault['subscription_id'] ?? '' ) === $subscription_id ) {
				return $vault['mode'] ?? '';
			}
		}

		return '';
	}

	/**
	 * Return the local order id for a subscription owned by the CURRENT (logged-in) user, or 0.
	 *
	 * Reads the vault option written at buy-now time, same scan as get_subscription_stored_mode().
	 *
	 * @internal Buyer-facing REST context only. It scopes the lookup on get_current_user_id();
	 *           called from a webhook, WP-CLI, or admin flow (where that returns 0) the query
	 *           matches nothing and returns 0 silently. Resolve the order id another way there.
	 *
	 * @param string $subscription_id
	 * @return int
	 */
	protected function get_subscription_order_id( string $subscription_id ): int {
		if ( empty( $subscription_id ) ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;
		$ids   = $wpdb->get_col( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE user_id = %d AND gateway = %s AND status = %d',
			get_current_user_id(),
			\TVA_Const::PAYPAL_GATEWAY,
			\TVA_Const::STATUS_PENDING
		) );

		foreach ( $ids as $order_id ) {
			$vault = get_option( 'tva_paypal_vault_' . $order_id, [] );
			if ( ( $vault['subscription_id'] ?? '' ) === $subscription_id ) {
				return (int) $order_id;
			}
		}

		return 0;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the full REST URL for the OAuth credentials callback endpoint.
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return string
	 */
	public function get_credentials_endpoint_url( $mode = 'live' ) {
		$endpoint = Credentials::get_credentials_endpoint( $mode );

		return rest_url( static::$namespace . static::$version . '/' . $this->base . '/' . $endpoint );
	}

	/**
	 * Determine mode ('live' or 'test') by matching the request route against stored endpoint slugs.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return string 'live' or 'test'
	 */
	protected function resolve_mode_from_request( WP_REST_Request $request ) {
		$route     = $request->get_route();
		$live_slug = Credentials::get_credentials_endpoint( 'live' );

		return ( strpos( $route, $live_slug ) !== false ) ? 'live' : 'test';
	}

	/**
	 * Pre-warm the bearer token transient for the given mode via /auth/token.
	 *
	 * The Product API issues one bearer token valid for all endpoints (order, vault,
	 * onboarding), so the same token is written to all three client-slug transient
	 * buckets. Base_Client::get_bearer_token() will then find it on the first cached
	 * read instead of making a fresh /auth/token request.
	 *
	 * @param string $mode 'live' or 'test'.
	 */
	protected function refresh_bearer_token( $mode ) {
		$merchant_id = Credentials::get_merchant_id( $mode );
		$secret      = Credentials::get_site_secret( $mode );

		if ( empty( $merchant_id ) || empty( $secret ) ) {
			return;
		}

		$response = wp_remote_post(
			Connection::get_product_api_url() . Connection::ENDPOINT_OAUTH_ACCESS_TOKEN,
			[
				'headers'   => [
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
					'Authorization' => 'Basic ' . base64_encode( $merchant_id . ':' . $secret ),
				],
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		if ( ! empty( $body['access_token'] ) ) {
			$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 604800;
			foreach ( [ 'onboarding', 'order', 'vault' ] as $client ) {
				set_transient( \TVA\PayPal\Http\Base_Client::make_transient_key( $client, $mode ), $body['access_token'], max( 1, $expires_in - 60 ) );
			}
		}
	}

	/**
	 * Call GET /merchant and persist the seller-readiness snapshot the
	 * connection UI reads: the capabilities summary, primary-email
	 * confirmation, and payments-receivable.
	 *
	 * @param string $mode 'live' or 'test'
	 */
	protected function verify_merchant( $mode ) {
		$bearer = $this->get_cached_bearer( $mode );
		if ( empty( $bearer ) ) {
			return;
		}

		$response = wp_remote_get(
			Connection::get_product_api_url() . Connection::ENDPOINT_MERCHANT,
			[
				'headers'   => [ 'Authorization' => 'Bearer ' . $bearer ],
				'timeout'   => 15,
				'sslverify' => true,
			]
		);

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$body = is_array( $body ) ? $body : [];

		// The Product API normalizes PayPal's raw capabilities into
		// capabilities_summary (groups + rollup) — sanitize and persist it for
		// the admin grid (see save_capabilities_summary). A response without it
		// (older API / partial outage) keeps the previously stored summary
		// instead of wiping it, as does a summary with an unrecognized
		// schema_version.
		if ( ! empty( $body['capabilities_summary'] ) && is_array( $body['capabilities_summary'] ) ) {
			Credentials::save_capabilities_summary( $body['capabilities_summary'], $mode );
		}

		// Refresh the merchant country (gates the subscription RBM usage_pattern). Only
		// overwrite when present, so a response that omits it keeps the stored value.
		if ( ! empty( $body['country'] ) ) {
			Credentials::save_merchant_country( sanitize_text_field( $body['country'] ), $mode );
		}

		Credentials::save_merchant_status(
			! empty( $body['primary_email_confirmed'] ),
			! empty( $body['payments_receivable'] )
		);

		// Capture the merchant's PayPal account email (forwarded verbatim from
		// PayPal's merchant-integrations status). Sanitize first and only persist a
		// non-empty address, so a partial or invalid response never wipes a
		// previously stored value (same defensive pattern as country / capabilities_summary).
		$primary_email = isset( $body['primary_email'] ) ? sanitize_email( (string) $body['primary_email'] ) : '';
		if ( '' !== $primary_email ) {
			Credentials::save_merchant_email( $primary_email, $mode );
		}

		// Only persist vetting_status when the PPCP product entry is present in the response.
		// A response that omits products[] entirely (partial outage, API change) keeps the
		// previously stored value intact rather than silently clearing a DENIED status.
		if ( isset( $body['products'] ) && is_array( $body['products'] ) ) {
			foreach ( $body['products'] as $product ) {
				if ( isset( $product['name'] ) && 'PPCP' === strtoupper( (string) $product['name'] ) ) {
					Credentials::save_vetting_status( (string) ( $product['vetting_status'] ?? '' ), $mode );
					break;
				}
			}
		}
	}

	/**
	 * Get a cached Bearer token for the given mode (any client bucket).
	 *
	 * @param string $mode 'live' or 'test'
	 *
	 * @return string Empty string if not cached.
	 */
	protected function get_cached_bearer( $mode ) {
		foreach ( [ 'onboarding', 'order', 'vault' ] as $client ) {
			$token = get_transient( \TVA\PayPal\Http\Base_Client::make_transient_key( $client, $mode ) );
			if ( ! empty( $token ) ) {
				return $token;
			}
		}

		return '';
	}
}

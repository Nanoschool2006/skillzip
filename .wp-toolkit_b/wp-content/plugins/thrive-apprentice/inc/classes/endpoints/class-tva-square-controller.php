<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

use Random\RandomException;
use TVA\Product;
use TVA\Square\Credentials;
use Square\SquareClient;
use Square\Catalog\Object\Requests\UpsertCatalogObjectRequest;
use Square\Types\CatalogObject;
use Square\Types\CatalogObjectItem;
use Square\Types\CatalogItem;
use Square\Types\CatalogObjectItemVariation;
use Square\Types\CatalogItemVariation;
use Square\Types\Money;
use Square\Types\Currency;
use Square\Types\CatalogPricingType;
use Square\Types\CatalogObjectSubscriptionPlan;
use Square\Types\CatalogSubscriptionPlan;
use Square\Catalog\Object\Requests\GetObjectRequest;
use Square\Checkout\PaymentLinks\Requests\CreatePaymentLinkRequest;
use Square\Types\Order;
use Square\Types\OrderLineItem;
use Square\Types\CheckoutOptions;
use Square\Types\PrePopulatedData;
use Square\Types\CatalogObjectDiscount;
use Square\Types\CatalogDiscount;
use Square\Types\CatalogDiscountType;
use Square\Types\Customer;
use Square\Types\Tender;
use Square\Orders\Requests\GetOrdersRequest;
use Square\Customers\Requests\GetCustomersRequest;
use Square\Payments\Requests\GetPaymentsRequest;
use Square\Subscriptions\Requests\GetSubscriptionsRequest;
use TVA\Access\History_Table;
use Square\Exceptions\SquareApiException;
use Square\Exceptions\SquareException;
use Square\Webhooks\Subscriptions\Requests\CreateWebhookSubscriptionRequest;
use Square\Types\WebhookSubscription;
use Square\Invoices\Requests\GetInvoicesRequest;
use TVE\Reporting\Event;
use TVA\Reporting\Events\Product_Purchase;
use Square\Customers\Requests\SearchCustomersRequest;
use Square\Types\CustomerQuery;
use Square\Types\CustomerFilter;
use Square\Types\CustomerTextFilter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TVA_Square_Controller extends TVA_REST_Controller {

	public $base = 'square';

	// UPDATE THIS PER ENVIRONMENT!
	//const API_URL = 'https://service-api.stagingthrivethemes.com';
	const API_URL = 'https://service-api.thrivethemes.com';
	const SQUARE_URL = 'https://connect.squareupsandbox.com';
	const SQUARE_URL_LIVE = 'https://connect.squareup.com';

	public function register_routes() {
		$credentials_endpoint = Credentials::get_credentials_endpoint( 'test' );
		$credentials_endpoint_live = Credentials::get_credentials_endpoint( 'live' );

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
				]
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/disconnect', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'disconnect' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		// Square Web Payments routes
		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/product_config', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_product_config' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'mode' => [
						'required' => false,
						'type'     => 'string',
					],
				]
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/item_info', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_item_info' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'item_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'mode' => [
						'required' => true,
						'type'     => 'string',
					],
				]
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/payment', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'process_payment' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'sourceId' => [
						'required' => true,
						'type'     => 'string',
					],
					'customerData' => [
						'required' => true,
						'type'     => 'object',
					],
					'productData' => [
						'required' => false,
						'type'     => 'object',
					],
					'idempotencyKey' => [
						'required' => true,
						'type'     => 'string',
					],
					'mode' => [
						'required' => true,
						'type'     => 'string',
					],
				]
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/' . $credentials_endpoint, [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_credentials' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'state'                => [
						'required' => true,
						'type'     => 'string',
					],
					'access_token'           => [
						'required' => true,
						'type'     => 'string',
					],
					'refresh_token'      => [
						'required' => true,
						'type'     => 'string',
					],
					'expires_at'      => [
						'required' => true,
						'type'     => 'string',
					],
					'mode' => [
						'required' => true,
						'type'     => 'string',
					],
					'merchant_id'       => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/' . $credentials_endpoint_live, [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_credentials' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'state'                => [
						'required' => true,
						'type'     => 'string',
					],
					'access_token'           => [
						'required' => true,
						'type'     => 'string',
					],
					'refresh_token'      => [
						'required' => true,
						'type'     => 'string',
					],
					'expires_at'      => [
						'required' => true,
						'type'     => 'string',
					],
					'mode' => [
						'required' => true,
						'type'     => 'string',
					],
					'merchant_id'       => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/status', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/refresh_token', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'refresh_token' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'mode' => [
						'required' => true,
						'type'     => 'string',
					]
				]
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/settings', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'setting' => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ Credentials::AUTO_DISPLAY_BUY_BUTTON_OPTIONS ],
					],
					'value'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/create_product', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_product' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'name' => [
						'required' => true,
						'type'     => 'string',
					],
					'description'   => [
						'required' => false,
						'type'     => 'string',
					],
					'price'   => [
						'required' => true,
						'type'     => 'number',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/product_mode', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'product_mode' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'mode'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/product_trial', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'product_trial' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'state'   => [
						'required' => true,
						'type'     => 'boolean',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/trial_days', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'trial_days' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'days'   => [
						'required' => true,
						'type'     => 'string',
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
						'type'     => 'string',
					],
					'state'   => [
						'required' => true,
						'type'     => 'boolean',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/success_url', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'success_url' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'url'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/cancel_url', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'cancel_url' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'url'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/selected_price', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'selected_price' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'price_id'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get_locations', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_locations' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get_pricing', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_pricing' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/create_price', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_price' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'price_type' => [
						'required' => true,
						'type'     => 'string'
					],
					'amount'     => [
						'required' => true,
						'type'     => 'number',
					],
					'billing_period'   => [
						'required' => false,
						'type'     => 'string',
					],
					'free_trial'   => [
						'required' => false,
						'type'     => 'boolean',
					],
					'free_trial_days'   => [
						'required' => false,
						'type'     => 'number',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/product_data', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_product_data' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'mode'   => [
						'required' => true,
						'type'     => 'string',
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

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get-redirect', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_order_redirect' ],
				'permission_callback' => '__return_true',
				'args'                => [
					'order_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'onetime' => [
						'required' => true,
						'type'     => 'boolean',
					],
					'mode' => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get_settings', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/webhook', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'webhook_lister' ],
				'permission_callback' => '__return_true',
			],
		] );
	}

	/**
	 * Save the Square credentials.
	 *
	 * This method is responsible for saving the Square credentials.
	 * It checks the state and if it's valid, it saves the credentials.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 * @throws RandomException If the state is invalid.
	 */
	public function save_credentials( WP_REST_Request $request ) {
		$state      = $request->get_param( 'state' );
		$access_token = $request->get_param( 'access_token' );
		$refresh_token = $request->get_param( 'refresh_token' );
		$expires_at = $request->get_param( 'expires_at' );
		$mode = $request->get_param( 'mode' );
		$merchant_id = $request->get_param( 'merchant_id' );

		$site_state = $mode === 'test' ? Credentials::get_state() : Credentials::get_state_live();
		if ( ! $state || ! $site_state || $state !== $site_state ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid state ' . $state . '  #   ' . $site_state, 'thrive-apprentice' ) ], 400 );
		}

		// Save credentials
		Credentials::save_credentials( $access_token, $refresh_token, $expires_at, $mode, $merchant_id );

        return new WP_REST_Response( [ 'success' => true ] );
	}

	public function disconnect() {
		Credentials::disconnect();

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Get the URL to connect a Square account.
	 *
	 * This method is responsible for generating the URL to connect a Square account.
	 * It adds the necessary parameters to the API URL.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 * @throws RandomException If the state is invalid.
	 */
	public function get_connect_account_link( WP_REST_Request $request ) {
		$url = $request->get_param( 'url' );
		$mode = $request->get_param( 'mode' ) ?? 'live';
		if ( empty( $url ) ) {
			$url = admin_url( 'admin.php?page=thrive_apprentice#settings/payments/square' );
		}

		$data = [
			'customer_site_url'        => $url,
			'endpoint_url'             => $this->get_credentials_endpoint_url( $mode ),
			'state'                    => $mode === 'test' ? Credentials::get_state() : Credentials::get_state_live(),
			'mode'                     => $mode,
		];

		$response = wp_remote_post( static::API_URL . '/square/connect_ouath', [
			'body' => $data,
			'sslverify' => false,
		] );

		$response = wp_remote_retrieve_body( $response );
		return new WP_REST_Response( [ 'success' => true, 'url' => $response ] );
	}

	/**
	 * Get the credentials endpoint URL.
	 *
	 * This method is responsible for generating the credentials endpoint URL.
	 *
	 * @param string $mode The mode of the credentials endpoint.
	 * 
	 * @return string The credentials endpoint URL.
	 */
	public function get_credentials_endpoint_url( $mode = 'live' ) {
		$endpoint = Credentials::get_credentials_endpoint( $mode );

		return get_rest_url() . TVA_Square_Controller::$namespace . TVA_Square_Controller::$version . '/' . $this->base . '/' . $endpoint;
	}

	/**
	 * Retrieve the status of the Square OAuth2 token.
	 *
	 * This method makes a POST request to the Square API to check the status
	 * of the OAuth2 token. It includes the necessary headers for authorization.
	 *
	 * @return array|WP_REST_Response The response object containing the status of the token.
	 */
	public function get_status() {
		$client_live = $this->get_square_client('live');
		try {
			$response_live = $this->is_valid_square_client( $client_live ) && ! empty( $client_live->oAuth ) ? $client_live->oAuth->retrieveTokenStatus() : '';
			$response_live = json_decode( $response_live );
		} catch ( Exception $e ) {
			$response_live = [];
		}
		
		$client_test = $this->get_square_client('test');
		try {
			$response_test = $this->is_valid_square_client( $client_test ) && ! empty( $client_test->oAuth ) ? $client_test->oAuth->retrieveTokenStatus() : '';
			$response_test = json_decode( $response_test );
		} catch ( Exception $e ) {
			$response_test = [];
		}

		$live_enabled = false;
		if ( $response_live && $response_live->expires_at && strtotime( $response_live->expires_at ) > time() ) {
			$live_enabled = true;
		}

		$test_enabled = false;
		if ( $response_test && $response_test->expires_at && strtotime( $response_test->expires_at ) > time() ) {
			$test_enabled = true;
		}

		// check if TEST token is about to expire in less than 7 days
		$test_token_expiring = false;
		if ( !empty( $response_test ) && $response_test->expires_at && strtotime( $response_test->expires_at ) < ( time() + ( 7 * 24 * 3600 ) ) ) {
			$test_token_expiring = true;
		}

		// check if LIVE token is about to expire in less than 7 days
		$live_token_expiring = false;
		if ( !empty( $response_live ) && $response_live->expires_at && strtotime( $response_live->expires_at ) < ( time() + ( 7 * 24 * 3600 ) ) ) {
			$live_token_expiring = true;
		}

		if ( $live_enabled || $test_enabled ) {
			return new WP_REST_Response( [
				'success' => true,
				'live_enabled' => $live_enabled,
				'test_enabled' => $test_enabled,
				'response_test' => $response_test,
				'response_live' => $response_live,
				'test_token_expiring_soon' => $test_token_expiring,
				'live_token_expiring_soon' => $live_token_expiring,
			] );
		}
		return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Not connected to live or test account', 'thrive-apprentice' ) ], 400 );
	}

	/**
	 * Check if the Square client is valid (not a WP_REST_Response error object).
	 *
	 * @param mixed $client The client object to check.
	 *
	 * @return bool True if client is a valid SquareClient object.
	 */
	private function is_valid_square_client( $client ) {
		return $client && ! ( $client instanceof WP_REST_Response );
	}

	/**
	 * Retrieve the Square client.
	 *
	 * This method retrieves the Square client from the token stored in the credentials.
	 * 
	 * @param string $mode The mode of the Square client.
	 *
	 * @return SquareClient|WP_REST_Response The Square client or a response object.
	 */
	public function get_square_client( string $mode ) {
		if ( empty( $mode ) ) {
			$mode = 'live'; // update this to live
		}

		$token = $mode === 'live' ? Credentials::get_token_live() : Credentials::get_token();

		if ( empty( $token ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'No token found', 'thrive-apprentice' ) ], 400 );
		}

		try {
			$client = new SquareClient(
				$token,
				null,
				[
					'baseUrl' => $mode === 'live' ? static::SQUARE_URL_LIVE : static::SQUARE_URL,
				]
			);
			return $client;
		} catch ( Exception $e ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid token', 'thrive-apprentice' ) ], 400 );
		}
	}

	public function get_merchant_id( $mode ) {
		if ( empty( $mode ) ) {
			$mode = 'live';
		}
		return $mode === 'test' ? Credentials::get_account_id() : Credentials::get_account_id_live();
	}

	/**
	 * Refreshes the Square token.
	 *
	 * This method makes a POST request to the Square API to refresh the OAuth2 token.
	 * It includes the necessary headers for authorization.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object containing the status of the token.
	 */
	public function refresh_token( WP_REST_Request $request ) {
		$mode = $request->get_param( 'mode' ) ?? 'live';

		$refresh_token = $mode === 'test' ? Credentials::get_refresh_token() : Credentials::get_refresh_token_live();
		if ( empty( $refresh_token ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'No refresh token found', 'thrive-apprentice' ) ], 400 );
		}

		$url = admin_url( 'admin.php?page=thrive_apprentice#settings/payments/square' );

		$data = [
			'customer_site_url'        => $url,
			'endpoint_url'             => $this->get_credentials_endpoint_url( $mode ),
			'state'                    => $mode === 'test' ? Credentials::get_state() : Credentials::get_state_live(),
			'mode'                     => $mode,
			'refresh_token'            => $refresh_token,
		];

		$response = wp_remote_post( static::API_URL . '/square/refresh_token', [
			'body' => $data,
			'sslverify' => false
		] );
		$response = wp_remote_retrieve_body( $response );
		return new WP_REST_Response( [ 'success' => true, 'message' => $response ] );
	}

	/**
	 * This method is responsible for saving Square general settings, those settings are used as default for each product setup.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$setting = $request->get_param( 'setting' );
		$value   = $request->get_param( 'value' );

		if ( ! in_array( $setting, [ Credentials::AUTO_DISPLAY_BUY_BUTTON_OPTIONS ] ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid setting', 'thrive-apprentice' ) ], 400 );
		}

		Credentials::update_setting( $setting, $value );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * This method is responsible for getting the current Square general settings.
	 * The setting retrieved is the auto_display_buy_button setting.
	 *
	 * @return WP_REST_Response The response object containing the Square settings.
	 */
	public function get_settings() {
		Credentials::clear_object_cache('tva_stripe_auto_display_buy_button');
		Credentials::clear_object_cache('tva_square_auto_display_buy_button');
		return new WP_REST_Response( [
			'auto_display_buy_button' => Credentials::get_setting( Credentials::AUTO_DISPLAY_BUY_BUTTON_OPTIONS ) ? true : false,
			'stripe_auto_display_buy_button' => get_option( 'tva_stripe_auto_display_buy_button' ) && get_option( 'tva_stripe_auto_display_buy_button' ) === '1' ? true : false,
			'square_auto_display_buy_button' => get_option( 'tva_square_auto_display_buy_button' ) && get_option( 'tva_square_auto_display_buy_button' ) === '1' ? true : false,
		] );
	}

	/**
	 * Create a Square product.
	 *
	 * @param WP_REST_Request $request The request object containing the product data.
	 *
	 * @return WP_REST_Response The response object containing the result of the operation.
	 */
	public function create_product( WP_REST_Request $request ) {
		$mode = $request->get_param( 'mode' ) ?? 'live';
		$product_title = $request->get_param( 'name' );
		$product_description = $request->get_param( 'description' ) ?? '';
		//product_id = titile to lower case and remove spaces
		$product_id = strtolower( str_replace( ' ', '', $product_title ) ); 
		$product_price = $request->get_param( 'price' );
		$client = $this->get_square_client( $mode );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Square client not available', 'thrive-apprentice' ) ], 400 );
		}

		$response = $client->catalog->object->upsert(
			new UpsertCatalogObjectRequest([
				'idempotencyKey' => uniqid(),
				'object' => CatalogObject::item(new CatalogObjectItem([
					'id' => $product_id,
					'itemData' => new CatalogItem([
						'abbreviation' => substr( $product_title, 0, 5 ),
						'description' => $product_description,
						'name' => $product_title,
						'variations' => [
							CatalogObject::itemVariation(new CatalogObjectItemVariation([
								'id' => $product_id . '-variation-01',
								'itemVariationData' => new CatalogItemVariation([
									'name' => $product_title,
									'priceMoney' => new Money([
										'amount' => $product_price,
										'currency' => Currency::Usd->value,
									]),
									'pricingType' => CatalogPricingType::FixedPricing->value,
									'itemId' => $product_id,
								]),
							])),
						],
					]),
				])),
			]),
		);

		if ( $response->getErrors() ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => $response->getErrors() ], 400 );
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $response ] );
	}

	/**
	 * Update the mode of a Square product.
	 *
	 * This function retrieves the mode and product ID from the request
	 * and updates the product's mode in the term meta. If the product
	 * ID or mode is missing, it returns an error response.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response indicating success or failure.
	 */
	public function product_mode( WP_REST_Request $request ) {
		$mode = $request->get_param( 'mode' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) || empty( $mode ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id or mode', 'thrive-apprentice' ) ], 400 );
		}

		update_term_meta( $product_id, 'tva_square_mode', $mode );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Get the Square location data.
	 *
	 * This function retrieves the Square location data for the given mode.
	 * If the mode is 'live', it retrieves the live account ID. Otherwise, it
	 * retrieves the sandbox account ID.
	 *
	 * @param string $mode The mode of the Square client.
	 *
	 * @return WP_REST_Response The REST response with the location data.
	 */
	public function get_locations( $mode ) {
		$client = $this->get_square_client($mode);

		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'No location data', 'thrive-apprentice' ) ], 400 );
		}

		try {
			// Make an API request to validate the credentials
			$locations = $client->locations->list([
				'limit' => 1
			]);
		} catch (SquareApiException $e) {
			// Handle API errors
			if ($e->getStatusCode() === 401) {
				// Unauthorized error, validate credentials
				error_log( 'Square Unauthorized error: ' . $e->getMessage() );
				return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid credentials', 'thrive-apprentice' ) ], 400 );
				
			} else {
				// Other API error
				error_log( 'Square API error: ' . $e->getMessage() );
				return new WP_REST_Response( [ 'success' => false, 'message' => __( 'API error', 'thrive-apprentice' ) ], 400 );
			}
		}

		$data = [
			'currency' => $locations->getLocations()[0]->getCurrency(),
			'country' => $locations->getLocations()[0]->getCountry(),
			'merchant_id' => $mode === 'live' ? Credentials::get_account_id_live() : Credentials::get_account_id(),
			'name' => $locations->getLocations()[0]->getName(),
			'locations' => $locations->getLocations(),
		];

		return new WP_REST_Response( [ 'success' => true, 'data' => $data ] );
	}

	/**
	 * Create a Square price for a product.
	 *
	 * This endpoint receives the product ID, price, price type, and billing period
	 * and creates a Square price for the product. If the price type is recurring,
	 * it creates a subscription plan variation. Otherwise, it creates an item variation.
	 * The response contains the Square data for the created price.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return WP_REST_Response The REST response with the Square data for the created price.
	 */
	public function create_price( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$product_price = $request->get_param( 'amount' );
		$product_price = number_format($product_price, 2, '.', '');
		$product_price = (float)$product_price * 100;
		$price_type = $request->get_param( 'price_type' );
		$billing_period = $request->get_param( 'billing_period' ) ?? 'MONTHLY';
		$free_trial = $request->get_param( 'free_trial' ) ?? false;
		$free_trial_days = $request->get_param( 'free_trial_days' ) ?? 0;
		$cadence = '';

		$product_title = get_term( $product_id )->name;

		$square_mode = get_term_meta( $product_id, 'tva_square_mode', true );
		$mode = $square_mode !== false && $square_mode !== '' ? $square_mode : 'live';

		$locations = $this->get_locations( $mode );
		
		// Check if the response was successful and has the expected data structure
		if ( ! $locations || 
			 ! isset( $locations->data['success'] ) || 
			 ! $locations->data['success'] || 
			 ! isset( $locations->data['data']['currency'] ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Unable to retrieve Square location data', 'thrive-apprentice' ) ], 400 );
		}
		
		$currency = $locations->data['data']['currency'];
		
		$client = $this->get_square_client( $mode );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Square client not available', 'thrive-apprentice' ) ], 400 );
		}
		
		if ( $price_type === 'recurring' ) {
			// create Item to add to Subscription Plan
			$response = $this->create_subscription_plan_item( 
				$client, 
				$product_title, 
				$product_price, 
				$currency, 
				$billing_period 
			);

			$item_id = $response->getCatalogObject()->getValue()->getId();

			// create Subscription Plan
			$response_plan = $this->create_subscription_plan(
				$client,
				$product_title,
				$product_price,
				$currency,
				$item_id,
				$product_id
			);

			$plan_id = $response_plan->getCatalogObject()->getValue()->getId();
			$square_url = $mode === 'live' ? static::SQUARE_URL_LIVE : static::SQUARE_URL;
			$token = $mode === 'live' ? Credentials::get_token_live() : Credentials::get_token();

			// create interval for subscription plan
			$response_interval = $this->create_subscription_plan_interval(
				$product_id,
				$product_price,
				$currency,
				$product_title,
				$plan_id,
				$billing_period,
				$square_url,
				$token,
				$free_trial,
				$free_trial_days
			);

			$final_response = json_decode( $response_interval, true );

			$square_id = $plan_id;

			if ( isset( $final_response['errors'] ) ) {
				return new WP_REST_Response( [ 'success' => false, 'data' => $final_response ] );
			}

			$cadence = $billing_period;

		} else {
			$final_response = $client->catalog->object->upsert(
				new UpsertCatalogObjectRequest([
					'idempotencyKey' => uniqid(),
					'object' => CatalogObject::item(new CatalogObjectItem([
						'id' => '#' . strtotime('now'),
						'itemData' => new CatalogItem([
							'abbreviation' => substr( $product_title, 0, 5 ),
							'description' => $product_title,
							'name' => $product_title . ' - ' . $product_price,
							'variations' => [
								CatalogObject::itemVariation(new CatalogObjectItemVariation([
									'id' => '#' . strtotime('now') . '1',
									'itemVariationData' => new CatalogItemVariation([
										'name' => $product_title . ' - ' . $product_price,
										'priceMoney' => new Money([
											'amount' => $product_price,
											'currency' => $currency,
										]),
										'pricingType' => CatalogPricingType::FixedPricing->value,
									]),
								])),
							],
						]),
					])),
				]),
			);

			$square_id = $final_response->getCatalogObject()->getValue()->getId();

			if ( $final_response->getErrors() ) {
				return new WP_REST_Response( [ 'success' => false, 'data' => $final_response->getErrors() ] );
			}
		}

		$term_data = [
			'mode' => $mode,
			'merchant_id' => $this->get_merchant_id( $mode ),
			'type' => $price_type,
			'cadence' => $cadence,
			'square_id' => $square_id,
			'price' => $product_price / 100,
			'currency' => $currency,
		];

		$existing_term_data = get_term_meta( $product_id, 'tva_square_product_pricing', true );
		if ( empty( $existing_term_data ) ) {
			$data = [
				0 => $term_data,
			];
			add_term_meta( $product_id, 'tva_square_product_pricing', $data );
		} else {
			$data = $existing_term_data;
			$data[] = $term_data;
			update_term_meta( $product_id, 'tva_square_product_pricing', $data );
		}

		return new WP_REST_Response( [ 'success' => true, 'data' => $final_response, 'term_data' => $term_data ] );
	}

	/**
	 * Create a subscription plan item.
	 *
	 * @param $client The Square client to use for the request.
	 * @param string $product_title The title of the product.
	 * @param int $product_price The price of the product.
	 * @param string $currency The currency of the product.
	 * @param string $billing_period The billing period of the product.
	 *
	 * @return mixed The response from the API.
	 */
	public function create_subscription_plan_item( $client, $product_title, $product_price, $currency, $billing_period ) {
		return $client->catalog->object->upsert(
			new UpsertCatalogObjectRequest([
				'idempotencyKey' => uniqid(),
				'object' => CatalogObject::item(new CatalogObjectItem([
					'id' => '#' . strtotime('now'),
					'itemData' => new CatalogItem([
						'abbreviation' => substr( $product_title, 0, 5 ),
						'name' => $product_title . ' - ' . (string) $product_price,
						'variations' => [
							CatalogObject::itemVariation(new CatalogObjectItemVariation([
								'id' => '#' . strtotime('now') . '1',
								'itemVariationData' => new CatalogItemVariation([
									'name' => $product_title . ' - ' . (string) $product_price,
									'priceMoney' => new Money([
										'amount' => $product_price,
										'currency' => $currency,
									]),
									'pricingType' => CatalogPricingType::FixedPricing->value,
								]),
							])),
						],
					]),
				])),
			]),
		);
	}

	/**
	 * Creates a subscription plan in Square.
	 *
	 * @param SquareClient $client The Square client instance to use for the request.
	 * @param string $product_title The title of the product for the subscription plan.
	 * @param int $product_price The price of the product in smallest currency unit.
	 * @param string $currency The currency code (e.g., USD) for the product price.
	 * @param string $item_id The ID of the item to associate with the subscription plan.
	 * @param string $product_id The ID of the product for creating a unique subscription plan ID.
	 *
	 * @return mixed The response from the Square API after attempting to create the subscription plan.
	 */
	public function create_subscription_plan( $client, $product_title, $product_price, $currency, $item_id, $product_id ) {
		return $client->catalog->object->upsert(
			new UpsertCatalogObjectRequest([
				'object' => CatalogObject::subscriptionPlan(new CatalogObjectSubscriptionPlan([
					'id' => '#' . $product_id . '-' . (string) $product_price . '-' . $currency,
					'custom' => [
						'product_id' => $product_id,
					],
					'subscriptionPlanData' => new CatalogSubscriptionPlan([
						'name' => $product_title . ' - ' . (string) $product_price,
						'eligibleItemIds' => [
							$item_id,
						],
						'allItems' => false,
					]),

				])),
				'idempotencyKey' => uniqid(),
			]),
		);
	}

	/**
	 * Creates a subscription plan variation in Square.
	 *
	 * @param int $product_id The ID of the product for creating a unique subscription plan ID.
	 * @param int $product_price The price of the product in smallest currency unit.
	 * @param string $currency The currency code (e.g., USD) for the product price.
	 * @param string $product_title The title of the product for the subscription plan.
	 * @param string $plan_id The ID of the subscription plan to associate with the subscription plan variation.
	 * @param string $billing_period The billing period for the subscription plan variation.
	 * @param string $square_url The URL of the Square API.
	 * @param string $token The access token for the Square API.
	 *
	 * @return string The response from the Square API after attempting to create the subscription plan variation.
	 */
	public function create_subscription_plan_interval( $product_id, $product_price, $currency, $product_title, $plan_id, $billing_period, $square_url, $token, $trial_period, $trial_days ) {

		$cadence_name = str_replace('_', ' ', strtolower( $billing_period ));

		$trial_data = '';
		$ordinal = 0;
		if ( $trial_period === true ) {		
			$ordinal = 1;
			$trial_data = '{
							"cadence": "DAILY",
							"ordinal": 0,
							"periods": ' . (int) $trial_days . ',
							"pricing": {
								"type": "STATIC",
								"price_money": {
									"amount": 0,
									"currency": "' . $currency . '"
								}
							}
						},';
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $square_url . '/v2/catalog/object',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
				"idempotency_key": "' . uniqid() . '",
				"object": {
				"id": "' . '#' . $product_id . '-' . (string) $product_price . '-' . $currency . '-' . $cadence_name . '",
				"type": "SUBSCRIPTION_PLAN_VARIATION",
				"subscription_plan_variation_data": {
					"name": "' . $product_title . ' - ' . (string) $product_price . '",
					"phases": [
					' . $trial_data . '
					{
						"cadence": "' . $billing_period . '",
						"ordinal": ' . $ordinal . ',
						"pricing": {
							"type": "STATIC",
							"price_money": {
								"amount": ' . $product_price . ',
								"currency": "' . $currency . '"
							}
						}
					} 
					],
					"subscription_plan_id": "' . (string) $plan_id . '"
				}
				}
			}',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json'
			),
		));

		$final_response = curl_exec($curl);

		curl_close($curl);
		return $final_response;
	}

	/**
	 * Creates a 100% discount in Square.
	 *
	 * @param SquareClient $client The Square client instance to use for the request.
	 *
	 * @return mixed The response from the Square API after attempting to create the discount.
	 */
	public function create_subscription_plan_discount( $client ) {
		return $client->catalog->object->upsert(
			new UpsertCatalogObjectRequest([
				'idempotencyKey' => uniqid(),
				'object' => CatalogObject::discount(new CatalogObjectDiscount([
					'discountData' => new CatalogDiscount([
						'discountType' => CatalogDiscountType::FixedPercentage->value,
						'percentage' => '100',
						'name' => '100%',
					]),
					'id' => '#' . time(),
				])),
			]),
		);
	}

	/**
	 * Retrieve product data for a given product ID.
	 *
	 * This method fetches the Square mode and pricing information for a product.
	 * It determines if the product has pricing set in live or test mode and returns
	 * the relevant data including the selected mode, price text, and CSS class.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID.
	 *
	 * @return WP_REST_Response The response object containing success status and product data.
	 */
	public function get_product_data( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$selected_mode = $request->get_param( 'mode' ) ?? 'live';
		$price_set = get_term_meta( (int) $product_id, 'tva_square_product_pricing', true );

		$livesExists = false;
		$testExists = false;
		foreach ($price_set as $price) {
			if ($price["mode"] == "live") {
				$livesExists = true;
			}
			if ($price["mode"] == "test") {
				$testExists = true;
			}
			if ($livesExists && $testExists) {
				break;
			}
		}

		if ( $selected_mode === 'live' ) {
			$price_text =  $livesExists ? 'Product Price Set' : 'Price Not Set';
			$price_class = $livesExists ? '' : 'tva-square-not-selected';
		} else {
			$price_text = $testExists ? 'Product Price Set' : 'Price Not Set';
			$price_class = $testExists ? '' : 'tva-square-not-selected';
		}

		$data = [
			'selected_mode' => $selected_mode,
			'price_text' => $price_text,
			'price_class' => $price_class
		];

		return new WP_REST_Response( [ 'success' => true, 'data' => $data ] );
	}

	/**
	 * Update the free trial flag for a Square product.
	 *
	 * This endpoint updates the free trial flag for a Square product.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and state.
	 *
	 * @return WP_REST_Response The response object containing success status.
	 */
	public function product_trial( WP_REST_Request $request ) {
		$state = $request->get_param( 'state' );
		$product_id = $request->get_param( 'product_id' ) ?? null;
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_free_trial', $state );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Update the number of days for a free trial period.
	 *
	 * This endpoint updates the number of days for a free trial period for a Square product.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and the number of days.
	 *
	 * @return WP_REST_Response The response object containing success status.
	 */
	public function trial_days( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_trial_days', $days );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Update the pre-populate email flag for a Square product.
	 *
	 * This endpoint updates the pre-populate email flag for a Square product based on the provided state.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and state.
	 *
	 * @return WP_REST_Response The response object containing success status.
	 */

	public function prepopulate_email( WP_REST_Request $request ) {
		$state = $request->get_param( 'state' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_prepopulate_email', $state );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Update the success URL for a Square product.
	 *
	 * This endpoint updates the success URL for a Square product.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and URL.
	 *
	 * @return WP_REST_Response The response object containing success status.
	 */
	public function success_url( WP_REST_Request $request ) {
		$url = $request->get_param( 'url' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_success_url', $url );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Update the cancel URL for a Square product.
	 *
	 * This endpoint updates the cancel URL for a Square product.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and URL.
	 *
	 * @return WP_REST_Response The response object containing success status.
	 */
	public function cancel_url( WP_REST_Request $request ) {
		$url = $request->get_param( 'url' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_cancel_url', $url );
		
		return new WP_REST_Response( [ 'success' => true ] );	
	}

	/**
	 * Create a new page and returns the URL of the page.
	 *
	 * This endpoint creates a new page with the given title and returns the URL of the page.
	 *
	 * @param WP_REST_Request $request The REST request object containing the title of the page.
	 *
	 * @return WP_REST_Response The response object containing success status and the URL of the page.
	 */
	public function create_page( WP_REST_Request $request ) {
		$title   = $request->get_param( 'title' );
		$page_id = wp_insert_post( [
			'post_title'  => $title,
			'post_status' => 'publish',
			'post_type'   => 'page',
		] );
		
		if ( $page_id ) {
			return new WP_REST_Response( [ 'success' => true, 'url' => get_permalink( $page_id ) ] );
		} else {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Error creating page', 'thrive-apprentice' ) ], 400 );
		}
	}

	/**
	 * Update the selected price for a Square product.
	 *
	 * This endpoint updates the selected price for a Square product
	 * by storing the price ID in the term meta. If the product ID is
	 * missing, it returns an error response.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID and price ID.
	 *
	 * @return WP_REST_Response The response object containing success status or failure message.
	 */
	public function selected_price( WP_REST_Request $request ) {
		$price_id = $request->get_param( 'price_id' );
		$product_id = $request->get_param( 'product_id' );
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		update_term_meta( $product_id, 'tva_square_product_selected_price', $price_id );
		
		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Get the pricing for a Square product.
	 *
	 * This endpoint retrieves the pricing data for a Square product
	 * by fetching the term meta for the product ID. If the product ID
	 * is missing, it returns an error response.
	 *
	 * @param WP_REST_Request $request The REST request object containing the product ID.
	 *
	 * @return WP_REST_Response The response object containing success status or failure message and the pricing data.
	 */
	public function get_pricing( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$mode = $request->get_param( 'mode' ) ?? 'live';
		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid product id', 'thrive-apprentice' ) ], 400 );
		}
		
		$pricing = get_term_meta( $product_id, 'tva_square_product_pricing', true ) ?? [];
		$settings = get_term_meta( $product_id, 'tva_access_restriction', true ) ?? '';
		$buy_now_notice = true;
		if (
			empty( $pricing )
			|| ( isset($settings)
			&& is_array($settings)
			&& is_array($settings['action_button_display'])
			&& isset($settings['action_button_display']['buy_action'])
			&& is_array($settings['action_button_display']['buy_action'])
			&& $settings['action_button_display']['buy_action']['provider'] === 'square' )
		) {
			$buy_now_notice = false;
		}
		
		return new WP_REST_Response( 
			[ 
				'success' => true, 
				'pricing' => $pricing, 
				'buy_now_notice' => $buy_now_notice, 
				'merchant_id' => $this->get_merchant_id( $mode ),
			] 
		);
	}

	/**
	 * Fetches a catalog object from Square by ID.
	 *
	 * @param string $object_id The ID of the object to fetch.
	 * @param SquareClient $client The Square client to use.
	 *
	 * @return array An array containing the type and object.
	 */
	public function get_item_object( string $object_id, SquareClient $client ) {
	
		try {
			$get_object = $client->catalog->object->get(
				new GetObjectRequest([
					'objectId' => $object_id,
				]),
			);
		} catch ( SquareException $e ) {
			error_log( 'Square error fetching catalog object: ' . $e->getMessage() );
			return [];
		}

		$item_object = $get_object->getObject();
		if ( empty( $item_object ) ) {
			return [];
		}

		$item_type = $item_object->getType();
		if ( empty( $item_type) ) {
			return [];
		}

		return [
			'type' => $item_type,
			'object' => $item_object,
		];
	}

	public function get_order_redirect( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$gateway_order_id = $request->get_param( 'order_id' );
		$onetime = $request->get_param( 'onetime' );
		$mode = $request->get_param( 'mode' );

		if ( empty( $product_id ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid Apprentice product id', 'thrive-apprentice' ) ], 400 );
		}

		$sucess_url = get_term_meta( $product_id, 'tva_square_product_success_url', true ) ?? home_url( '/' );
		$cancel_url = get_term_meta( $product_id, 'tva_square_product_cancel_url', true ) ?? home_url( '/' );
		
		$client = $this->get_square_client( $mode );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [ 
				'success' => false, 
				'url' => $cancel_url,
				'message' => __( 'Invalid Square client', 'thrive-apprentice' ) ], 400 );
		}
	
		// get ORDER data
		$user_first_name = '';
		$user_last_name = '';
		$stored_customer_data = null;
		$transient_key = null;
		
		if ( $onetime ) {
			// ONETIME
			$order = $client->orders->get(
				new GetOrdersRequest([
					'orderId' => $gateway_order_id,
				]),
			);
	
			if ( empty( $order ) ) {
				error_log( 'Payment failed. Square order not found' );
				wp_redirect( $cancel_url );
				exit();
			}
	
			// get PAYMENT data
			$payment_id = $order->getOrder()->getTenders()[0]->getPaymentId() ?? 0;
			$payment = $client->payments->get(
				new GetPaymentsRequest([
					'paymentId' => $payment_id,
				])
			);

			$user_email = $payment->getPayment()->getBuyerEmailAddress();
			
			// Get customer data from transient or Square API
			$customer_data = $this->get_customer_data_from_square( $gateway_order_id, $payment->getPayment(), $client, $user_email );
			$user_first_name = $customer_data['firstName'];
			$user_last_name = $customer_data['lastName'];
			$transient_key = $customer_data['transient_key'];
			$stored_customer_data = $customer_data['stored_customer_data'];
			
			$order_price = $order->getOrder()->getTotalMoney()->getAmount();
			$order_price = (int)$order_price / 100;
			$order_currency = $order->getOrder()->getTotalMoney()->getCurrency();
		} else {
			// get ORDER data - SUBSCRIPTION
			$order = $client->subscriptions->get(
				new GetSubscriptionsRequest([
					'subscriptionId' => $gateway_order_id,
				]),
			);
	
			if ( empty( $order ) ) {
				error_log( 'Payment failed. Square subscription not found' );
				wp_redirect( $cancel_url );
				exit();
			}
	
			$customer_id = $order->getSubscription()->getCustomerId() ?? 0;
			$square_customer = $client->customers->get(
				new GetCustomersRequest([
					'customerId' => $customer_id,
				])
			);

			$user_email = $square_customer->getCustomer()->getEmailAddress();
			$user_first_name = $square_customer->getCustomer()->getGivenName() ?? '';
			$user_last_name = $square_customer->getCustomer()->getFamilyName() ?? '';
			$order_pricing = $order->getSubscription()->getPriceOverrideMoney();

			if ( empty( $order_pricing ) ) {
				$variation_id = $order->getSubscription()->getPlanVariationId();
				$item = $client->catalog->object->get(
					new GetObjectRequest([
						'objectId' => $variation_id,
					]),
				);

				if ( empty( $item ) ) {
					$order_price = 0;
					$order_currency = 'USD';
				} else {
					$order_price = $item->getObject()->getValue()['subscription_plan_variation_data']['phases'][0]['pricing']['price']['amount'];
					$order_price = (int)$order_price / 100;
					$order_currency = $item->getObject()->getValue()['subscription_plan_variation_data']['phases'][0]['pricing']['price']['currency'];
				}	
			} else {
				$order_price = $order->getSubscription()->getPriceOverrideMoney()->getAmount();
				$order_price = (int)$order_price / 100;
				$order_currency = $order->getSubscription()->getPriceOverrideMoney()->getCurrency();
			}	
		}

		// Calculate display name from customer data
		$display_name = $this->calculate_user_display_name( $user_first_name, $user_last_name, $user_email, $stored_customer_data, $transient_key );

		// create USER if does not exist
		$new_user = false;
		$user = get_user_by( 'email', $user_email );
		$user_id = $user->ID ?? 0;
		if ( ! $user ) {
			$new_user = true;
			$user_data = [
				'user_email' => $user_email,
				'user_login' => $user_email,
				'user_pass' => wp_generate_password( 12, false ),
			];
			
			if ( ! empty( $display_name ) ) {
				$user_data['display_name'] = $display_name;
			}
			if ( ! empty( $user_first_name ) ) {
				$user_data['first_name'] = $user_first_name;
			}
			if ( ! empty( $user_last_name ) ) {
				$user_data['last_name'] = $user_last_name;
			}
			
			$user_id = wp_insert_user( $user_data );

			$user = get_user_by( 'id', $user_id );
		}
		
		// Update user first name, last name, and display name if available
		if ( $user && ( ! empty( $user_first_name ) || ! empty( $user_last_name ) || ! empty( $display_name ) ) ) {
			$update_data = array_merge(
				[ 'ID' => $user_id ],
				array_filter( [
					'first_name' => $user_first_name ?: null,
					'last_name' => $user_last_name ?: null,
					'display_name' => $display_name ?: null,
				] )
			);
			wp_update_user( $update_data );
		}

		$customer = new TVA_Customer( $user->ID );

		// send email to user after purchase
		$this->send_email( $user, $new_user );

		// save ORDER data to DB
		$product_name = get_term( $product_id )->name;

		$order = new TVA_Order();
		$order->set_user_id( $user_id );
		$order->set_status( 1 );
		$order->set_payment_id( $gateway_order_id );
		$order->set_gateway( 'Square' );
		$order->set_type( 'paid' );
		$order->set_payment_method( 'Square' );
		$order->set_buyer_email( $user_email );
		$order->set_price( $order_price );
		$order->set_price_gross( $order_price );
		$order->set_currency( $order_currency );
		$order->save();
		
		$order_items = new TVA_Order_Item();
		$order_items->set_order_id( $order->get_id() );
		$order_items->set_status( 1 );
		$order_items->set_product_price( $order_price );
		$order_items->set_quantity( 1 );
		$order_items->set_unit_price( $order_price );
		$order_items->set_currency( $order_currency );
		$order_items->set_product_name( $product_name );
		$order_items->set_product_id( $product_id );
		$order_items->save();

		$order->set_order_item( $order_items );
		$order->save();

		// save MEMBERSHIP data to DB
		$product = new Product( $product_id );
		$courses = $product->get_courses() ?? [];
		$access_data = [];

		if ( $courses === [] ) {
			$access_data = [
				'course_id' => null,
				'product_id' => $product_id,
				'user_id' => $user_id,
				'status' => 1,
				'source' => 'square',
				'created' => date( 'Y-m-d H:i:s' ),
			];
		} else {
			foreach ( $courses as $course ) {
				$access_data[] = [
					'course_id' => $course->get_id(),
					'product_id' => $product_id,
					'user_id' => $user_id,
					'status' => 1,
					'source' => 'square',
					'created' => date( 'Y-m-d H:i:s' ),
				];
			}
		}

		// add access to access history table
		if ( ! empty( $access_data ) ) {
			History_Table::get_instance()->insert_or_update_multiple( $access_data, false );
		}

		//trigger user enrollment
		$enrolled_courses = array();
		$enrolled_courses[] = $product_id;

		if ( ! empty( $enrolled_courses ) ) {
			$customer->trigger_course_purchase($order, 'Square');
			$customer->trigger_product_received_access($enrolled_courses);
			$customer->trigger_purchase($order);
		}

		// redirect to success page
		return new WP_REST_Response( [ 
			'success' => true, 
			'url' => $sucess_url
		], 200 );
	}

	public function send_email( $user, $new_user ) {
		if ( ! is_wp_error( $user ) && $new_user ) {
			// Get the email template for new account creation
			$email_template = tva_email_templates()->check_templates_for_trigger( 'square' );
			
			if ( $email_template ) {
				// Prepare the email template with user data
				$email_template = array_merge( $email_template, [
					'user' => $user,
				] );
				
				// Trigger the email template preparation
				do_action( 'tva_prepare_square_email_template', $email_template );
				
				// Send the email
				$to = $user->user_email;
				$subject = $email_template['subject'];
				$body = do_shortcode( nl2br( $email_template['body'] ) );
				$headers = array( 'Content-Type: text/html' );
				
				$result = wp_mail( $to, $subject, $body, $headers );
				if ( is_wp_error( $result ) ) {
					// Handle the error
					error_log( 'Error sending email: ' . $result->get_error_message() );
				}
			}
		}
	}

	public function webhook_lister( WP_REST_Request $request ) {
		
		$request_data = $request->get_params();
		$request_data = json_decode( json_encode( $request_data ), true );
		$request_type = $request_data['type'];

		if ( $request_type !== 'invoice.refunded' && $request_type !== 'subscription.updated' ) {
			return;
		}
		
		Product::flush_global_cache( [ 'get_protected_products_by_integration', 'wordpress' ] );
		$already_cancelled = false;

		if ( $request_data['type'] === 'invoice.refunded' ) {
			$gateway_order_id = $request_data['data']['object']['invoice']['subscription_id'];
			if ( empty( $gateway_order_id ) ) {
				$gateway_order_id = $request_data['data']['object']['invoice']['order_id'];
			}

			if ( empty( $gateway_order_id ) ) {
				$gateway_order_id = '';
			}

			// find TVA Order, order item, product and user
			$order = TVA_Order::get_orders_by_payment_id( $gateway_order_id, 1 );

			$user_id = !empty( $order ) ? $order[0]['user_id'] : 0;
			$user = get_user_by( 'ID', $user_id ?? 0 );
			$customer = new TVA_Customer( $user_id );
			$order_object = new TVA_Order( !empty( $order ) ? $order[0]['ID'] : 0 );
			$order_items = $order_object->get_order_items(); 
			$product = new Product(  !empty( $order_items ) ? $order_items[0]->get_product_id() : 0 );			

			// remove user from product immediately
			$customer->remove_user_from_product( $user, $product );

			// Add refund record to access history for Course Enrollments report
			$this->add_cancel_event( $product, $user_id, $order_items[0]->get_product_id(), $order_items[0]->get_product_price(), true );

			$already_cancelled = true;
		}

		if ( $request_data['type'] === 'subscription.updated' ) {
			$gateway_order_id = $request_data['data']['id'] ?? '';
			$canceled_date = $request_data['data']['object']['subscription']['canceled_date'] ?? date( 'Y-m-d H:i:s' );

			// find TVA Order
			$order = TVA_Order::get_orders_by_payment_id( $gateway_order_id, 1 );

			$user_id = !empty( $order ) ? $order[0]['user_id'] : 0;
			$user = get_user_by( 'ID', $user_id ?? 0 );
			$customer = new TVA_Customer( $user_id );
			$order_object = new TVA_Order( !empty( $order ) ? $order[0]['ID'] : 0 );
			$order_items = $order_object->get_order_items(); 
			$product_id = !empty( $order_items ) ? $order_items[0]->get_product_id() : 0;
			$product = new Product(  $product_id );
			$product_price = !empty( $order_items ) ? $order_items[0]->get_product_price() : 0;

			$client = $this->get_square_client('live');
			if ( ! $this->is_valid_square_client( $client ) ) {
				error_log( 'Square client not available for webhook processing' );
				return;
			}
			
			$subscription = $client->subscriptions->get(
				new GetSubscriptionsRequest([
					'subscriptionId' => $gateway_order_id,
				]),
			);

			$invoices = json_decode( $subscription, true );
			$invoice_ids = $invoices['subscription']['invoice_ids'] ?? [];

			if ( ! empty( $invoice_ids ) ) {
				$inovice = $client->invoices->get(
					new GetInvoicesRequest([
						'invoiceId' => end( $invoice_ids ),
					]),
				);

				$inovice = json_decode( $inovice, true );

				if ( isset( $inovice['invoice']['status'] ) && $inovice['invoice']['status'] === 'UNPAID' && $already_cancelled === false ) {
					$customer->remove_user_from_product( $user, $product );
					$this->add_cancel_event( $product, $user_id, $product_id, $product_price, false );
					$already_cancelled = true;
				}
			}

			//create cron job to remove user from product at cancel date
			if ( ! wp_next_scheduled( 'tva_remove_user_from_product' ) && $already_cancelled === false ) {
				$timestamp = strtotime( $canceled_date );

				if ( $timestamp <= time()  ) {
					$customer->remove_user_from_product( $user, $product );
					$this->add_cancel_event( $product, $user_id, $product_id, $product_price, false );
				} else {
					wp_schedule_single_event( $timestamp, 'tva_remove_user_from_product', array( (int) $user_id, (int) $product_id, (int) $product_price ) );
				}
			}
		}
	}

	public function add_cancel_event( $product, $user_id, $item_id, $item_price, $refund = false ) {
		if ( empty( $product ) || is_wp_error( $product ) ) {
			$product = new Product(  $item_id );
		}

		$courses = $product->get_courses() ?? [];
		$refund_data = [];

		if ( $courses === [] ) {
			$refund_data = [
				'course_id' => null,
				'product_id' => $item_id,
				'user_id' => $user_id ?? 0,
				'status' => -1,
				'source' => 'square',
				'created' => date( 'Y-m-d H:i:s' ),
			];
		} else {
			foreach ( $courses as $course ) {
				$refund_data[] = [
					'course_id' => $course->get_id(),
					'product_id' => $item_id,
					'user_id' => $user_id ?? 0,
					'status' => -1,
					'source' => 'square',
					'created' => date( 'Y-m-d H:i:s' ),
				];
			}
		}

		if ( ! empty( $refund_data ) ) {
			History_Table::get_instance()->insert_or_update_multiple( $refund_data, true );
		}

		// refund/cancel data
		$cancel_data = array(
			'item_id'       => $item_id ?? 0,
			'user_id'       => $user_id ?? 0,
			'order_gateway' => 'Square',
			'order_type'    => 'paid',
		);

		if ( $refund ) {
			// add refund action to reports
			$event_refund = new TVA\Reporting\Events\Order_Refunded( array_merge( $cancel_data, [ 'order_item_price' => $item_price	 ?? 0 ] ) );
			$event_refund->log();
		}

		// add cancel action to reports
		$event_cancel = new TVA\Reporting\Events\Subscription_Cancelled( $cancel_data );
		$event_cancel->log(); 
	}

	/**
	 * Get Square product configuration for web payments
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_product_config( WP_REST_Request $request ) {
		// Step 1: Validate request and get parameters
		$config_params = $this->extract_config_parameters( $request );
		if ( ! $config_params['success'] ) {
			return new WP_REST_Response( $config_params, 400 );
		}

		// Step 2: Get email prepopulation settings
		$email_config = $this->get_email_prepopulation_config( $config_params['product_id'] );

		try {
			// Step 3: Get Square client and validate configuration
			$square_setup = $this->get_square_setup( $config_params['product_id'], $config_params['mode'] );
			if ( ! $square_setup['success'] ) {
				return new WP_REST_Response( $square_setup, $square_setup['status'] ?? 500 );
			}

			// Step 4: Get location data
			$location_data = $this->get_location_data( $square_setup['mode'] );
			if ( ! $location_data['success'] ) {
				return new WP_REST_Response( $location_data, 400 );
			}

			// Step 5: Build base configuration
			$config = $this->build_base_config( $config_params, $square_setup, $location_data, $email_config );

			// Step 6: Add item-specific configuration
			$this->add_item_specific_config( $config, $square_setup['item_object_data'], $square_setup['client'] );

			return new WP_REST_Response( $config, 200 );

		} catch ( Exception $e ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => 'Failed to get product configuration: ' . $e->getMessage()
			], 500 );
		}
	}

	/**
	 * Extract and validate config parameters
	 */
	private function extract_config_parameters( WP_REST_Request $request ) {
		$product_id = $request->get_param( 'product_id' );
		$mode = $request->get_param( 'mode' ) ?? 'test';
		
		if ( empty( $product_id ) ) {
			return [
				'success' => false,
				'message' => 'Product ID is required'
			];
		}

		return [
			'success' => true,
			'product_id' => $product_id,
			'mode' => $mode
		];
	}

	/**
	 * Get email prepopulation configuration
	 */
	private function get_email_prepopulation_config( $product_id ) {
		$prepulate_email = get_term_meta( $product_id, 'tva_square_product_prepopulate_email', true ) ?? false;

		$user_email = null;
		$prepopulate = 0;
		
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$user_email = $current_user->user_email;
		}

		if ( $prepulate_email == '1' && $user_email ) {
			$prepopulate = 1;
		}

		return [
			'prepopulate' => $prepopulate,
			'prepopulateEmail' => $user_email
		];
	}

	/**
	 * Get Square setup (client, mode, item data)
	 */
	private function get_square_setup( $product_id, $mode ) {
		// Get Square mode for this product
		$square_mode = get_term_meta( $product_id, 'tva_square_mode', true );
		$effective_mode = ! empty( $square_mode ) ? $square_mode : $mode;

		// Get Square client
		$client = $this->get_square_client( $effective_mode );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return [
				'success' => false,
				'message' => 'Square client not available',
				'status' => 500
			];
		}

		// Get selected price/item ID for this product
		$object_id = get_term_meta( $product_id, 'tva_square_product_selected_price', true );
		if ( empty( $object_id ) ) {
			return [
				'success' => false,
				'message' => 'No Square item configured for this product',
				'status' => 400
			];
		}

		// Get item object data
		$item_object_data = $this->get_item_object( $object_id, $client );
		if ( empty( $item_object_data ) ) {
			return [
				'success' => false,
				'message' => 'Invalid Square item configuration',
				'status' => 400
			];
		}

		return [
			'success' => true,
			'client' => $client,
			'mode' => $effective_mode,
			'object_id' => $object_id,
			'item_object_data' => $item_object_data
		];
	}

	/**
	 * Get location data from Square
	 */
	private function get_location_data( $mode ) {
		$locations = $this->get_locations( $mode );
		
		// Check if the response was successful and has the expected data structure
		if ( ! $locations || 
			 ! isset( $locations->data['success'] ) || 
			 ! $locations->data['success'] || 
			 ! isset( $locations->data['data']['locations'] ) || 
			 empty( $locations->data['data']['locations'] ) ) {
			return [
				'success' => false,
				'message' => 'No Square locations available'
			];
		}

		return [
			'success' => true,
			'location' => $locations->data['data']['locations'][0]
		];
	}

	/**
	 * Get Square API keys from the endpoint
	 */
	private function get_square_api_keys() {
		// Check transient first
		if ( false !== $keys = get_transient( 'thrive_square_api_keys' ) ) {
			return $keys;
		}

		$endpoint = 'https://thrivethemesapi.com/api/secrets/v1/api_key_square';
		
		$response = wp_remote_get( $endpoint, array( 
			'timeout' => 10,
			'sslverify' => true 
		) );
		
		if ( is_wp_error( $response ) ) {
			$correlation_code = 'SQ-KEYS-NET-' . substr( wp_hash( uniqid( '', true ) ), 0, 8 );
			error_log( sprintf( 'Square API key fetch failed: %s. Please contact customer support at thrivethemes.com and mention code %s', $response->get_error_message(), $correlation_code ) );
			return array();
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( ! empty( $status_code ) && (int) $status_code !== 200 ) {
			$correlation_code = 'SQ-KEYS-HTTP-' . substr( wp_hash( uniqid( '', true ) ), 0, 8 );
			$error_message   = sprintf( 'Square API key fetch failed: HTTP %d. Please contact customer support at thrivethemes.com and mention code %s', (int) $status_code, $correlation_code );
			error_log( $error_message );
			return array();
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( ! is_array( $data ) || 
			 ! isset( $data['success'] ) || 
			 ! $data['success'] || 
			 ! isset( $data['data']['value']['live']['application_id'] ) ||
			 ! isset( $data['data']['value']['sandbox']['application_id'] ) ) {
			$correlation_code = 'SQ-KEYS-PAY-' . substr( wp_hash( uniqid( '', true ) ), 0, 8 );
			error_log( sprintf( 'Square API key fetch returned unexpected payload. Please contact customer support at thrivethemes.com and mention code %s', $correlation_code ) );
			return array();
		}
		
		$keys = array(
			'live'    => array(
				'application_id' => sanitize_text_field( $data['data']['value']['live']['application_id'] ),
				'environment'    => sanitize_text_field( $data['data']['value']['live']['environment'] )
			),
			'sandbox' => array(
				'application_id' => sanitize_text_field( $data['data']['value']['sandbox']['application_id'] ),
				'environment'    => sanitize_text_field( $data['data']['value']['sandbox']['environment'] )
			)
		);
		
		// Cache for 24 hours
		set_transient( 'thrive_square_api_keys', $keys, 24 * HOUR_IN_SECONDS );
		
		return $keys;
	}

	/**
	 * Build base configuration array
	 */
	private function build_base_config( $config_params, $square_setup, $location_data, $email_config ) {
		$item_type = $square_setup['item_object_data']['type'];
		$location = $location_data['location'];

		// Get Square API keys
		$square_keys = $this->get_square_api_keys();
		
		if ( empty( $square_keys ) ) {
			return [
				'success' => false,
				'message' => 'Square API keys are not available. Please contact support.'
			];
		}
		
		$mode = $square_setup['mode'];
		// Map 'test' mode to 'sandbox' to match the keys array structure
		$key_mode = $mode === 'test' ? 'sandbox' : $mode;
		$app_id = isset( $square_keys[ $key_mode ]['application_id'] ) ? $square_keys[ $key_mode ]['application_id'] : '';
		
		if ( empty( $app_id ) ) {
			return [
				'success' => false,
				'message' => 'Square API key for ' . $mode . ' mode is not available. Please contact support.'
			];
		}

		return [
			'success'              => true,
			'squareItemId'         => $square_setup['object_id'],
			'squareItemType'       => $item_type,
			'squareItemMode'       => $square_setup['mode'],
			'squareItemLocationId' => $location->getId(),
			'squareItemAppId'      => $app_id,
			'productId'            => $config_params['product_id'],
			'countryCode'          => $location->getCountry(),
			'currencyCode'         => $location->getCurrency(),
			'isSubscription'       => $item_type === 'SUBSCRIPTION_PLAN',
			'prepopulate'          => $email_config['prepopulate'],
			'prepopulateEmail'     => $email_config['prepopulateEmail'],
			'merchantName'         => method_exists( $location, 'getName' ) ? ( $location->getName() ?? '' ) : '',
			'merchantId'           => method_exists( $location, 'getMerchantId' ) ? ( $location->getMerchantId() ?? '' ) : '',
		];
	}

	/**
	 * Add item-specific configuration based on item type
	 */
	private function add_item_specific_config( &$config, $item_object_data, $client ) {
		$item_type = $item_object_data['type'];
		$item_object = $item_object_data['object'];

		if ( $item_type === 'ITEM' ) {
			$config['itemVariationId'] = $item_object->asItem()->getItemData()->getVariations()[0]->getValue()->getId();
		} elseif ( $item_type === 'SUBSCRIPTION_PLAN' ) {
			$config['planVariationId'] = $item_object->asSubscriptionPlan()->getSubscriptionPlanData()->getSubscriptionPlanVariations()[0]->getValue()['id'];
			
			// Get associated item for invoice creation
			$eligible_items = $item_object->asSubscriptionPlan()->getSubscriptionPlanData()->getEligibleItemIds();
			if ( ! empty( $eligible_items ) ) {
				$item_data = $this->get_item_object( $eligible_items[0], $client );
				if ( ! empty( $item_data ) ) {
					$config['itemId'] = $eligible_items[0];
					$config['itemVariationId'] = $item_data['object']->asItem()->getItemData()->getVariations()[0]->getValue()->getId();
				}
			}
		}
	}

	/**
	 * Get Square catalog item information
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_item_info( WP_REST_Request $request ) {
		// Step 1: Validate request parameters
		$params = $this->validate_item_info_request( $request );
		if ( ! $params['success'] ) {
			return new WP_REST_Response( $params, 400 );
		}

		// Step 2: Get Square client
		$client = $this->get_square_client( $params['mode'] );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => 'Square client not available'
			], 500 );
		}

		try {
			// Step 3: Fetch and process catalog object
			$item_data = $this->fetch_and_process_item_info( $client, $params['item_id'] );
			if ( ! $item_data['success'] ) {
				return new WP_REST_Response( $item_data, 400 );
			}

			return new WP_REST_Response( $item_data, 200 );

		} catch ( Exception $e ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => 'Failed to get item information'
			], 500 );
		}
	}

	/**
	 * Validate item info request parameters
	 */
	private function validate_item_info_request( WP_REST_Request $request ) {
		$item_id = $request->get_param( 'item_id' );
		$mode = $request->get_param( 'mode' ) ?? 'test';
		
		if ( empty( $item_id ) ) {
			return [
				'success' => false,
				'message' => 'Item ID is required'
			];
		}

		return [
			'success' => true,
			'item_id' => $item_id,
			'mode' => $mode
		];
	}

	/**
	 * Fetch catalog object and process for item info
	 */
	private function fetch_and_process_item_info( $client, $item_id ) {
		// Fetch catalog object
		$catalog_object = $this->fetch_catalog_object( $client, $item_id );
		if ( ! $catalog_object['success'] ) {
			return $catalog_object;
		}

		// Process based on object type
		return $this->process_catalog_object_for_info( $catalog_object['object'], $item_id );
	}

	/**
	 * Fetch catalog object from Square
	 */
	private function fetch_catalog_object( $client, $item_id ) {
		$get_request = new GetObjectRequest( [
			'objectId' => $item_id,
			'includeRelatedObjects' => true
		] );

		$response = $client->catalog->object->get( $get_request );

		if ( ! empty( $response->getErrors() ) || $response->getObject() === null ) {
			return [
				'success' => false,
				'message' => 'Failed to fetch item from Square'
			];
		}

		return [
			'success' => true,
			'object' => $response->getObject()
		];
	}

	/**
	 * Process catalog object and extract item information
	 */
	private function process_catalog_object_for_info( $catalog_object, $item_id ) {
		$object_type = $catalog_object->getType();
		
		switch ( $object_type ) {
			case 'ITEM':
				return $this->process_item_for_info( $catalog_object, $item_id );
			
			case 'SUBSCRIPTION_PLAN':
				return $this->process_subscription_plan_for_info( $catalog_object, $item_id );
			
			case 'ITEM_VARIATION':
				return $this->process_item_variation_for_info( $catalog_object, $item_id );
			
			default:
				return [
					'success' => false,
					'message' => 'Unsupported catalog object type: ' . $object_type
				];
		}
	}

	/**
	 * Process ITEM type for item info
	 */
	private function process_item_for_info( $catalog_object, $item_id ) {
		$item_object = $catalog_object->getValue();
		$item_data = $item_object->getItemData();
		$variations = $item_data->getVariations();

		if ( empty( $variations ) ) {
			return [
				'success' => false,
				'message' => 'Item has no pricing variations'
			];
		}

		$variation = $variations[0];
		$variation_object = $variation->getValue();
		$variation_data = $variation_object->getItemVariationData();
		$price = $variation_data->getPriceMoney();

		return [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $item_data->getName(),
				'description' => $item_data->getDescription(),
				'type' => 'ITEM',
				'price' => [
					'amount' => $price->getAmount(),
					'currency' => $price->getCurrency(),
					'formatted' => '$' . number_format( $price->getAmount() / 100, 2 )
				]
			]
		];
	}

	/**
	 * Process SUBSCRIPTION_PLAN type for item info
	 */
	private function process_subscription_plan_for_info( $catalog_object, $item_id ) {
		$plan_object = $catalog_object->getValue();
		$plan_data = $plan_object->getSubscriptionPlanData();
		$plan_variations = $plan_data->getSubscriptionPlanVariations();

		if ( empty( $plan_variations ) ) {
			return [
				'success' => false,
				'message' => 'Subscription plan has no variations'
			];
		}

		$plan_variation = $plan_variations[0];
		$plan_variation_data = $plan_variation->getValue();
		$phases = $plan_variation_data['subscription_plan_variation_data']['phases'] ?? [];
		
		if ( empty( $phases ) ) {
			return [
				'success' => false,
				'message' => 'Subscription plan has no pricing phases'
			];
		}

		// Check for free trial (first phase with DAILY cadence and price 0)
		$has_free_trial = false;
		$trial_days = 0;
		$billing_phase = $phases[0];
		
		if ( count( $phases ) > 1 ) {
			$trial_phase = $phases[0];
			$billing_phase = $phases[1];
			
			// Check if first phase is a free trial
			if ( strtoupper( $trial_phase['cadence'] ?? '' ) === 'DAILY' && 
				 ( $trial_phase['pricing']['price_money']['amount'] ?? 0 ) == 0 ) {
				$has_free_trial = true;
				$trial_days = $trial_phase['periods'] ?? 0;
			}
		}
		
		$price_money = $billing_phase['pricing']['price_money'] ?? null;

		$item_data = [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $plan_data->getName(),
				'description' => 'Subscription Plan',
				'type' => 'SUBSCRIPTION_PLAN',
				'cadence' => $billing_phase['cadence'] ?? 'MONTHLY',
				'price' => [
					'amount' => $price_money['amount'] ?? 0,
					'currency' => $price_money['currency'] ?? 'USD',
					'formatted' => '$' . number_format( ($price_money['amount'] ?? 0) / 100, 2 ) . '/' . strtolower( $billing_phase['cadence'] ?? 'monthly' )
				]
			]
		];

		// Add free trial information if present
		if ( $has_free_trial ) {
			$item_data['item']['freeTrial'] = [
				'enabled' => true,
				'days' => $trial_days,
				'description' => $trial_days . '-day free trial'
			];
		}

		return $item_data;
	}

	/**
	 * Process ITEM_VARIATION type for item info
	 */
	private function process_item_variation_for_info( $catalog_object, $item_id ) {
		$variation_object = $catalog_object->getValue();
		$variation_data = $variation_object->getItemVariationData();
		$price = $variation_data->getPriceMoney();

		return [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $variation_data->getName(),
				'description' => 'Product Variation',
				'type' => 'ITEM_VARIATION',
				'price' => [
					'amount' => $price->getAmount(),
					'currency' => $price->getCurrency(),
					'formatted' => '$' . number_format( $price->getAmount() / 100, 2 )
				]
			]
		];
	}

	/**
	 * Process Square payment
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function process_payment( WP_REST_Request $request ) {
		// Step 1: Extract and validate request parameters
		$payment_data = $this->extract_payment_parameters( $request );
		if ( ! $payment_data['success'] ) {
			return new WP_REST_Response( $payment_data, 400 );
		}

		// Step 2: Get Square client
		$client = $this->get_square_client( $payment_data['mode'] );
		if ( ! $this->is_valid_square_client( $client ) ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => 'Square client not available'
			], 500 );
		}

		try {
			// Step 3: Process payment
			$payment_result = $this->process_square_payment( $client, $payment_data );

			if ( ! $payment_result['success'] ) {
				return new WP_REST_Response( [
					'success' => false,
					'message' => $payment_result['error'] ?? 'Payment failed'
				], 400 );
			}

			// Step 4: Grant course access
			$this->grant_course_access_if_needed( $payment_data, $payment_result );

			// Step 5: Build final response
			return new WP_REST_Response( [
				'success' => true,
				'data' => [
					'payment' => $payment_result['payment'],
					'invoice' => $payment_result['invoice'] ?? null,
					'customer' => $payment_result['customer'] ?? null,
					'subscription' => $payment_result['subscription'] ?? null,
					'courseUrl' => $this->get_course_url( $payment_data['productData'] ),
					'message' => 'Payment successful! Course access granted.'
				]
			], 200 );

		} catch ( Exception $e ) {
			return new WP_REST_Response( [
				'success' => false,
				'message' => 'Payment processing failed: ' . $e->getMessage()
			], 500 );
		}
	}

	/**
	 * Extract and validate payment parameters from request
	 */
	private function extract_payment_parameters( WP_REST_Request $request ) {
		$source_id = $request->get_param( 'sourceId' );
		$customer_data = $request->get_param( 'customerData' );
		$product_data = $request->get_param( 'productData' );
		$idempotency_key = $request->get_param( 'idempotencyKey' );
		$mode = $request->get_param( 'mode' ) ?? 'test';

		if ( empty( $source_id ) || empty( $customer_data ) || empty( $idempotency_key ) || empty( $mode ) ) {
			return [
				'success' => false,
				'message' => 'Missing required payment data'
			];
		}

		return [
			'success' => true,
			'sourceId' => $source_id,
			'customerData' => $customer_data,
			'productData' => $product_data,
			'idempotencyKey' => $idempotency_key,
			'mode' => $mode
		];
	}

	/**
	 * Grant course access if needed
	 */
	private function grant_course_access_if_needed( $payment_data, $payment_result ) {
		if ( ! empty( $payment_data['productData']['courseId'] ) ) {
			$this->grant_course_access( $payment_data['customerData'], $payment_data['productData'], $payment_result );
		}
	}

	/**
	 * Process Square payment using Square API
	 * 
	 * @param SquareClient $client
	 * @param array $data
	 * @return array
	 */
	private function process_square_payment( $client, $data ) {
		try {
			// Step 1: Create customer if needed
			$customer = $this->create_customer_if_needed( $client, $data );

		if ( $this->is_subscription_payment( $data ) ) {
			$customer = $this->ensure_subscription_customer( $client, $data, $customer );
			$data['productData'] = $customer['productData'] ?? $data['productData'];

			if ( empty( $customer['customer'] ) ) {
				return [
					'success' => false,
					'error' => $customer['error'] ?? 'Customer creation required for subscription payments'
				];
			}
		}

			// Step 2: Get location ID
			$locationId = $this->get_location_id( $data );

			// Step 3: Route to appropriate payment type
			$isSubscription = $this->is_subscription_payment( $data );
			
			if ( $isSubscription ) {
				return $this->process_subscription_payment( $client, $data, $customer['customer'], $locationId );
			}

			return $this->process_one_time_payment( $client, $data, $customer, $locationId );

		} catch ( \Square\Exceptions\SquareException $e ) {
			return [
				'success' => false,
				'error' => 'Square API Error: ' . $e->getMessage()
			];
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'error' => 'Payment processing failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Create customer if customer data provided
	 */
	private function create_customer_if_needed( $client, $data ) {
		if ( empty( $data['customerData'] ) ) {
			return [ 'customer' => null, 'error' => null, 'productData' => $data['productData'] ?? [] ];
		}

		$customerData = $data['customerData'];
		
		try {
			$customerRequest = new \Square\Customers\Requests\CreateCustomerRequest( [
				'givenName' => $customerData['firstName'],
				'familyName' => $customerData['lastName'],
				'emailAddress' => $customerData['email'],
				'phoneNumber' => $customerData['phone']
			] );

			$customerResponse = $client->customers->create( $customerRequest );
			
			if ( empty( $customerResponse->getErrors() ) && $customerResponse->getCustomer() !== null ) {
				$data['productData']['squareCustomerId'] = $customerResponse->getCustomer()->getId();
				return [ 'customer' => $customerResponse->getCustomer(), 'error' => null, 'productData' => $data['productData'] ];
			}

			return [ 
				'customer' => null, 
				'error' => 'Customer creation failed: ' . json_encode( $customerResponse->getErrors() ),
				'productData' => $data['productData'],
			];

		} catch ( Exception $e ) {
			return [ 
				'customer' => null, 
				'error' => 'Customer creation error: ' . $e->getMessage(),
				'productData' => $data['productData'],
			];
		}
	}

	/**
	 * Get location ID from data or fetch default
	 */
	private function get_location_id( $data ) {
		if ( ! empty( $data['productData']['squareItemLocationId'] ) ) {
			return $data['productData']['squareItemLocationId'];
		}

		if ( ! empty( $data['productData']['squareLocationId'] ) ) {
			return $data['productData']['squareLocationId'];
		}

		$locations = $this->get_locations( $data['mode'] );
		
		// Check if the response was successful and has the expected data structure
		if ( ! $locations || 
			 ! isset( $locations->data['success'] ) || 
			 ! $locations->data['success'] || 
			 ! isset( $locations->data['data']['locations'] ) || 
			 empty( $locations->data['data']['locations'] ) ) {
			throw new Exception( 'Unable to retrieve Square location data' );
		}
		
		return $locations->data['data']['locations'][0]->getId();
	}

	/**
	 * Check if this is a subscription payment
	 */
	private function is_subscription_payment( $data ) {
		return ! empty( $data['productData']['isSubscription'] ) && 
			( $data['productData']['isSubscription'] === true || $data['productData']['isSubscription'] === 'true' );
	}

	/**
	 * Process one-time payment
	 */
	private function process_one_time_payment( $client, $data, $customer, $locationId ) {
		// Step 1: Get payment amount
		$amountData = $this->get_payment_amount( $client, $data );
		if ( ! $amountData['success'] ) {
			return $amountData;
		}

		// Step 2: Build payment request
		$paymentValues = $this->build_payment_request( $data, $customer['customer'], $locationId, $amountData );

		// Step 3: Create payment
		$paymentRequest = new \Square\Payments\Requests\CreatePaymentRequest( $paymentValues );
		$response = $client->payments->create( $paymentRequest );

		$errors = $response->getErrors();
		if ( ! empty( $errors ) || $response->getPayment() === null ) {
			return [
				'success' => false,
				'error' => 'Payment failed',
				'errors' => array_map( function( $error ) {
					return [
						'category' => $error->getCategory(),
						'code' => $error->getCode(),
						'detail' => $error->getDetail()
					];
				}, $errors ?? [] )
			];
		}

		$payment = $response->getPayment();

		// Step 4: Store customer data temporarily for later retrieval during redirect
		// This ensures we have first/last name even if Square doesn't link the customer properly
		if ( ! empty( $data['customerData'] ) && ! empty( $payment->getOrderId() ) ) {
			$this->store_customer_data_in_transient( $data['customerData'], $payment->getOrderId() );
		}

		// Step 5: Create invoice if customer exists
		$invoice = null;
		$invoiceError = $customer['error'];

		if ( $customer['customer'] ) {
			$invoiceResult = $this->create_one_time_invoice( $client, $customer['customer'], $payment, $locationId, $data );
			if ( $invoiceResult['success'] ) {
				$invoice = $invoiceResult['invoice'];
			} else {
				$invoiceError = $invoiceResult['error'];
			}
		}

		// Step 6: Build response
		return $this->build_one_time_payment_response( $payment, $customer['customer'], $invoice, $invoiceError );
	}

	/**
	 * Get payment amount for one-time payments
	 */
	private function get_payment_amount( $client, $data ) {
		// Try product data first
		if ( ! empty( $data['productData']['amount'] ) ) {
			return [
				'success' => true,
				'amount' => $data['productData']['amount'],
				'currency' => $data['productData']['currency'] ?? 'USD'
			];
		}

		// Fetch from catalog
		if ( empty( $data['productData']['squareItemId'] ) ) {
			return [
				'success' => false,
				'error' => 'Amount is required for one-time payments'
			];
		}

		try {
			$itemInfo = $this->get_item_info_for_payment( $client, $data['productData']['squareItemId'] );
			if ( $itemInfo['success'] && ! empty( $itemInfo['item']['price']['amount'] ) ) {
				return [
					'success' => true,
					'amount' => $itemInfo['item']['price']['amount'],
					'currency' => $itemInfo['item']['price']['currency']
				];
			}
		} catch ( Exception $e ) {
			// Silently handle errors
		}

		return [
			'success' => false,
			'error' => 'Amount is required for one-time payments'
		];
	}

	/**
	 * Build payment request values
	 */
	private function build_payment_request( $data, $customer, $locationId, $amountData ) {
		$paymentValues = [
			'sourceId' => $data['sourceId'],
			'idempotencyKey' => $data['idempotencyKey'],
			'locationId' => $locationId,
			'amountMoney' => new \Square\Types\Money( [
				'amount' => $amountData['amount'],
				'currency' => $amountData['currency']
			] )
		];

		// Add customer data
		if ( ! empty( $data['customerData'] ) ) {
			$customerData = $data['customerData'];
			$paymentValues['buyerEmailAddress'] = $customerData['email'];
			$paymentValues['buyerPhoneNumber'] = $customerData['phone'];
		}

		// Link to customer if created
		if ( $customer ) {
			$paymentValues['customerId'] = $customer->getId();
		}

		// Add verification token if provided
		if ( ! empty( $data['verificationToken'] ) ) {
			$paymentValues['verificationToken'] = $data['verificationToken'];
		}

		return $paymentValues;
	}

	/**
	 * Build one-time payment response
	 */
	private function build_one_time_payment_response( $payment, $customer, $invoice, $invoiceError ) {
		$responseData = [
			'success' => true,
			'payment' => [
				'id' => $payment->getId(),
				'status' => $payment->getStatus(),
				'receiptUrl' => $payment->getReceiptUrl(),
				'orderId' => $payment->getOrderId()
			]
		];

		// Add customer data
		if ( $customer ) {
			$responseData['customer'] = [
				'id' => $customer->getId(),
				'name' => $customer->getGivenName() . ' ' . $customer->getFamilyName(),
				'email' => $customer->getEmailAddress()
			];
		}

		// Add invoice data
		if ( $invoice ) {
			$responseData['invoice'] = [
				'id' => $invoice->getId(),
				'status' => $invoice->getStatus(),
				'publicUrl' => $invoice->getPublicUrl()
			];
		} elseif ( $invoiceError ) {
			$responseData['invoiceError'] = $invoiceError;
		}

		return $responseData;
	}

	/**
	 * Process subscription payment using Square Subscriptions API
	 * 
	 * @param SquareClient $client
	 * @param array $data
	 * @param Customer|null $customer
	 * @param string $locationId
	 * @return array
	 */
	private function process_subscription_payment( $client, $data, $customer, $locationId ) {
		if ( ! $customer ) {
			return [
				'success' => false,
				'error' => 'Customer creation required for subscription payments'
			];
		}

		try {
			// Step 1: Store card for future payments
			$card = $this->store_card_for_subscription( $client, $data, $customer );
			if ( ! $card['success'] ) {
				return $card;
			}

			// Step 2: Get plan variation ID
			$planVariationId = $this->get_plan_variation_id( $client, $data );
			if ( ! $planVariationId ) {
				return [
					'success' => false,
					'error' => 'Plan variation ID is required for subscription creation'
				];
			}

			// Step 3: Create subscription
			$subscription = $this->create_subscription( $client, $data, $customer, $card['card'], $locationId, $planVariationId );
			if ( ! $subscription['success'] ) {
				return $subscription;
			}

			// Step 4: Build response (Square handles invoicing for subscriptions automatically)
			return $this->build_subscription_response( $subscription['subscription'], $customer, null, $data );

		} catch ( \Square\Exceptions\SquareException $e ) {
			return [
				'success' => false,
				'error' => 'Square Subscription API Error: ' . $e->getMessage()
			];
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'error' => 'Subscription processing failed: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Store card for subscription payments
	 */
	private function store_card_for_subscription( $client, $data, $customer ) {
		$cardRequest = new \Square\Cards\Requests\CreateCardRequest( [
			'sourceId' => $data['sourceId'],
			'idempotencyKey' => $data['idempotencyKey'] . '-card',
			'card' => new \Square\Types\Card( [
				'customerId' => $customer->getId()
			] )
		] );

		$cardResponse = $client->cards->create( $cardRequest );
		
		if ( ! empty( $cardResponse->getErrors() ) || $cardResponse->getCard() === null ) {
			return [
				'success' => false,
				'error' => 'Failed to store card for subscription',
				'errors' => array_map( function( $error ) {
					return [
						'category' => $error->getCategory(),
						'code' => $error->getCode(),
						'detail' => $error->getDetail()
					];
				}, $cardResponse->getErrors() ?? [] )
			];
		}

		return [
			'success' => true,
			'card' => $cardResponse->getCard()
		];
	}

	/**
	 * Get plan variation ID for subscription
	 */
	private function get_plan_variation_id( $client, $data ) {
		// Return if already provided
		if ( ! empty( $data['productData']['planVariationId'] ) ) {
			return $data['productData']['planVariationId'];
		}

		// Get from catalog if item ID provided
		if ( empty( $data['productData']['squareItemId'] ) ) {
			return null;
		}

		try {
			$get_request = new \Square\Catalog\Object\Requests\GetObjectRequest( [
				'objectId' => $data['productData']['squareItemId'],
				'includeRelatedObjects' => true
			] );

			$response = $client->catalog->object->get( $get_request );

			if ( ! empty( $response->getErrors() ) || $response->getObject() === null ) {
				return null;
			}

			$catalog_object = $response->getObject();
			$object_type = $catalog_object->getType();
			
			if ( $object_type === 'SUBSCRIPTION_PLAN' ) {
				return $this->extract_plan_variation_from_plan( $catalog_object );
			}
			
			if ( $object_type === 'ITEM_VARIATION' ) {
				return $this->find_plan_variation_by_item( $client, $catalog_object );
			}

		} catch ( Exception $e ) {
			// Silently handle errors
		}

		return null;
	}

	/**
	 * Extract plan variation ID from subscription plan object
	 */
	private function extract_plan_variation_from_plan( $catalog_object ) {
		$plan_object = $catalog_object->getValue();
		$plan_data = $plan_object->getSubscriptionPlanData();
		$plan_variations = $plan_data->getSubscriptionPlanVariations();
		
		if ( empty( $plan_variations ) ) {
			return null;
		}

		$plan_variation = $plan_variations[0];
		return $plan_variation->getValue()['id'] ?? null;
	}

	/**
	 * Find plan variation ID by searching for plans that include the item
	 */
	private function find_plan_variation_by_item( $client, $catalog_object ) {
		$variation_object = $catalog_object->getValue();
		$variation_data = $variation_object->getItemVariationData();
		$item_id = $variation_data->getItemId();
		
		try {
			$search_request = new \Square\Catalog\Requests\SearchCatalogObjectsRequest( [
				'objectTypes' => [ 'SUBSCRIPTION_PLAN' ],
				'includeRelatedObjects' => true
			] );

			$search_response = $client->catalog->search( $search_request );

			if ( ! empty( $search_response->getErrors() ) || empty( $search_response->getObjects() ) ) {
				return null;
			}

			foreach ( $search_response->getObjects() as $plan_object ) {
				$plan_data = $plan_object->getValue()->getSubscriptionPlanData();
				$eligible_item_ids = $plan_data->getEligibleItemIds() ?? [];
				
				if ( ! in_array( $item_id, $eligible_item_ids ) ) {
					continue;
				}

				$plan_variations = $plan_data->getSubscriptionPlanVariations();
				
				if ( ! empty( $plan_variations ) ) {
					$plan_variation = $plan_variations[0];
					return $plan_variation->getValue()['id'] ?? null;
				}
			}

		} catch ( Exception $e ) {
			// Silently handle search errors
		}

		return null;
	}

	/**
	 * Create subscription with Square API
	 */
	private function create_subscription( $client, $data, $customer, $card, $locationId, $planVariationId ) {
		$subscriptionSource = new \Square\Types\SubscriptionSource( [
			'name' => $data['productData']['planName'] ?? 'Subscription Plan'
		] );

		$subscriptionData = [
			'locationId' => $locationId,
			'customerId' => $customer->getId(),
			'startDate' => date( 'Y-m-d' ),
			'cardId' => $card->getId(),
			'source' => $subscriptionSource,
			'planVariationId' => $planVariationId,
			'idempotencyKey' => $data['idempotencyKey'] . '-subscription'
		];

		$subscriptionRequest = new \Square\Subscriptions\Requests\CreateSubscriptionRequest( $subscriptionData );
		$subscriptionResponse = $client->subscriptions->create( $subscriptionRequest );

		if ( ! empty( $subscriptionResponse->getErrors() ) || $subscriptionResponse->getSubscription() === null ) {
			return [
				'success' => false,
				'error' => 'Failed to create subscription',
				'errors' => array_map( function( $error ) {
					return [
						'category' => $error->getCategory(),
						'code' => $error->getCode(),
						'detail' => $error->getDetail()
					];
				}, $subscriptionResponse->getErrors() ?? [] )
			];
		}

		return [
			'success' => true,
			'subscription' => $subscriptionResponse->getSubscription()
		];
	}

	/**
	 * Build subscription payment response
	 */
	private function build_subscription_response( $subscription, $customer, $invoice, $data ) {
		$responseData = [
			'success' => true,
			'payment' => [
				'subscription' => [
					'id' => $subscription->getId(),
					'status' => $subscription->getStatus(),
					'planName' => $data['productData']['planName'] ?? 'Subscription Plan'
				]
			],
			'customer' => [
				'id' => $customer->getId(),
				'name' => $customer->getGivenName() . ' ' . $customer->getFamilyName(),
				'email' => $customer->getEmailAddress()
			]
		];

		// Add invoice data
		if ( $invoice !== null && $invoice['success'] && ! empty( $invoice['invoice'] ) ) {
			$responseData['invoice'] = [
				'id' => $invoice['invoice']->getId(),
				'status' => $invoice['invoice']->getStatus(),
				'publicUrl' => $invoice['invoice']->getPublicUrl()
			];
		} elseif ( $invoice !== null && ! empty( $invoice['error'] ) ) {
			$responseData['invoiceError'] = $invoice['error'];
		}

		return $responseData;
	}

	/**
	 * Create invoice for subscription
	 * 
	 * @param SquareClient $client
	 * @param Customer $customer
	 * @param Subscription $subscription
	 * @param string $locationId
	 * @param array $data
	 * @return array
	 */
//	private function create_subscription_invoice( $client, $customer, $subscription, $locationId, $data ) {
//		try {
//			// Step 1: Create order for invoice if item data available
//			$order = $this->create_invoice_order_if_needed( $client, $locationId, $data );
//
//			// Step 2: Build invoice components
//			$invoice_components = $this->build_invoice_components( $customer, $locationId, $order, $data );
//
//			// Step 3: Create invoice
//			$invoice_result = $this->create_and_publish_invoice( $client, $invoice_components, $data );
//
//			return $invoice_result;
//
//		} catch ( Exception $e ) {
//			return [
//				'success' => false,
//				'error' => 'Invoice creation error: ' . $e->getMessage()
//			];
//		}
//	}

	/**
	 * Create order for invoice if item data is provided
	 */
	private function create_invoice_order_if_needed( $client, $locationId, $data ) {
		if ( empty( $data['productData']['itemId'] ) || empty( $data['productData']['itemVariationId'] ) ) {
			return null;
		}

		$invoiceOrderLineItem = new \Square\Types\OrderLineItem( [
			'name' => $data['productData']['itemName'] ?? 'Subscription Item',
			'quantity' => '1',
			'catalogObjectId' => $data['productData']['itemVariationId']
		] );

		$invoiceOrder = new \Square\Types\Order( [
			'locationId' => $locationId,
			'lineItems' => [ $invoiceOrderLineItem ]
		] );

		$createOrderRequest = new \Square\Types\CreateOrderRequest( [
			'order' => $invoiceOrder,
			'idempotencyKey' => $data['idempotencyKey'] . '-invoice-order'
		] );

		$orderResponse = $client->orders->create( $createOrderRequest );
		
		if ( empty( $orderResponse->getErrors() ) && $orderResponse->getOrder() !== null ) {
			return $orderResponse->getOrder();
		}

		return null;
	}

	/**
	 * Build invoice components (payment request, accepted methods, recipient)
	 */
	private function build_invoice_components( $customer, $locationId, $order, $data ) {
		$invoicePaymentRequest = new \Square\Types\InvoicePaymentRequest( [
			'requestMethod' => 'EMAIL',
			'requestType' => 'BALANCE',
			'dueDate' => date( 'Y-m-d', strtotime( '+30 days' ) )
		] );

		$acceptedPaymentMethods = new \Square\Types\InvoiceAcceptedPaymentMethods( [
			'card' => true,
			'squareGiftCard' => false,
			'bankAccount' => false,
			'buyNowPayLater' => false,
			'cashAppPay' => false
		] );

		$invoiceRecipient = new \Square\Types\InvoiceRecipient( [
			'customerId' => $customer->getId()
		] );

		$invoiceData = [
			'locationId' => $locationId,
			'primaryRecipient' => $invoiceRecipient,
			'paymentRequests' => [ $invoicePaymentRequest ],
			'description' => 'Invoice for ' . ( $data['productData']['planName'] ?? 'Subscription' ),
			'acceptedPaymentMethods' => $acceptedPaymentMethods
		];

		// Add order to invoice if created
		if ( $order ) {
			$invoiceData['orderId'] = $order->getId();
		}

		return new \Square\Types\Invoice( $invoiceData );
	}

	/**
	 * Create and publish invoice
	 */
	private function create_and_publish_invoice( $client, $invoiceData, $data ) {
		$createInvoiceRequest = new \Square\Invoices\Requests\CreateInvoiceRequest( [
			'invoice' => $invoiceData
		] );

		$invoiceResponse = $client->invoices->create( $createInvoiceRequest );
		
		if ( ! empty( $invoiceResponse->getErrors() ) || $invoiceResponse->getInvoice() === null ) {
			return [
				'success' => false,
				'error' => 'Invoice creation failed: ' . json_encode( $invoiceResponse->getErrors() )
			];
		}

		$createdInvoice = $invoiceResponse->getInvoice();
		
		// Try to publish the invoice
		return $this->publish_invoice( $client, $createdInvoice, $data );
	}

	/**
	 * Publish invoice to send via email
	 */
	private function publish_invoice( $client, $invoice, $data ) {
		try {
			$publishInvoiceRequest = new \Square\Invoices\Requests\PublishInvoiceRequest( [
				'invoiceId' => $invoice->getId(),
				'version' => $invoice->getVersion(),
				'idempotencyKey' => $data['idempotencyKey'] . '-publish'
			] );
			
			$publishResponse = $client->invoices->publish( $publishInvoiceRequest );
			
			if ( empty( $publishResponse->getErrors() ) && $publishResponse->getInvoice() !== null ) {
				return [
					'success' => true,
					'invoice' => $publishResponse->getInvoice()
				];
			}

			return [
				'success' => true,
				'invoice' => $invoice,
				'error' => 'Invoice created but failed to send: ' . json_encode( $publishResponse->getErrors() )
			];

		} catch ( Exception $e ) {
			return [
				'success' => true,
				'invoice' => $invoice,
				'error' => 'Invoice created but failed to send: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Create invoice for one-time payment
	 * 
	 * @param SquareClient $client
	 * @param Customer $customer
	 * @param Payment $payment
	 * @param string $locationId
	 * @param array $data
	 * @return array
	 */
	private function create_one_time_invoice( $client, $customer, $payment, $locationId, $data ) {
		try {
			// Step 1: Create order for invoice if item data available
			$order = $this->create_one_time_invoice_order( $client, $locationId, $data );

			// Step 2: Build invoice components for one-time payment
			$invoice_components = $this->build_one_time_invoice_components( $customer, $locationId, $order, $data );

			// Step 3: Create and publish invoice
			return $this->create_and_publish_invoice( $client, $invoice_components, $data );

		} catch ( Exception $e ) {
			return [
				'success' => false,
				'error' => 'Invoice creation error: ' . $e->getMessage()
			];
		}
	}

	/**
	 * Create order for one-time payment invoice
	 */
	private function create_one_time_invoice_order( $client, $locationId, $data ) {
		if ( empty( $data['productData']['squareItemId'] ) ) {
			return null;
		}

		// Get item info for the invoice
		$itemInfo = $this->get_item_info_for_payment( $client, $data['productData']['squareItemId'] );
		
		if ( ! $itemInfo['success'] ) {
			return null;
		}

		// Build order line item
		$line_item_data = [
			'name' => $itemInfo['item']['name'],
			'quantity' => '1',
			'basePriceMoney' => new \Square\Types\Money( [
				'amount' => $itemInfo['item']['price']['amount'],
				'currency' => $itemInfo['item']['price']['currency']
			] )
		];

		// Add catalog object ID if available
		if ( ! empty( $data['productData']['itemVariationId'] ) ) {
			$line_item_data['catalogObjectId'] = $data['productData']['itemVariationId'];
		}

		$invoiceOrderLineItem = new \Square\Types\OrderLineItem( $line_item_data );

		$invoiceOrder = new \Square\Types\Order( [
			'locationId' => $locationId,
			'lineItems' => [ $invoiceOrderLineItem ]
		] );

		$createOrderRequest = new \Square\Types\CreateOrderRequest( [
			'order' => $invoiceOrder,
			'idempotencyKey' => $data['idempotencyKey'] . '-invoice-order'
		] );

		$orderResponse = $client->orders->create( $createOrderRequest );
		
		if ( empty( $orderResponse->getErrors() ) && $orderResponse->getOrder() !== null ) {
			return $orderResponse->getOrder();
		}

		return null;
	}

	/**
	 * Build invoice components for one-time payment
	 */
	private function build_one_time_invoice_components( $customer, $locationId, $order, $data ) {
		$invoicePaymentRequest = new \Square\Types\InvoicePaymentRequest( [
			'requestMethod' => 'EMAIL',
			'requestType' => 'BALANCE',
			'dueDate' => date( 'Y-m-d', strtotime( '+30 days' ) )
		] );

		$acceptedPaymentMethods = new \Square\Types\InvoiceAcceptedPaymentMethods( [
			'card' => true,
			'squareGiftCard' => false,
			'bankAccount' => false,
			'buyNowPayLater' => false,
			'cashAppPay' => false
		] );

		$invoiceRecipient = new \Square\Types\InvoiceRecipient( [
			'customerId' => $customer->getId()
		] );

		$invoiceData = [
			'locationId' => $locationId,
			'primaryRecipient' => $invoiceRecipient,
			'paymentRequests' => [ $invoicePaymentRequest ],
			'description' => 'Invoice for ' . ( $data['productData']['itemName'] ?? 'Purchase' ),
			'acceptedPaymentMethods' => $acceptedPaymentMethods
		];

		// Add order to invoice if created
		if ( $order ) {
			$invoiceData['orderId'] = $order->getId();
		}

		return new \Square\Types\Invoice( $invoiceData );
	}

	/**
	 * Get item info for payment processing (internal helper)
	 * 
	 * @param SquareClient $client
	 * @param string $item_id
	 * @return array
	 */
	private function get_item_info_for_payment( $client, $item_id ) {
		try {
			// Step 1: Fetch catalog object
			$catalog_object = $this->fetch_catalog_object( $client, $item_id );
			if ( ! $catalog_object['success'] ) {
				return $catalog_object;
			}

			// Step 2: Process based on object type
			return $this->process_catalog_object_for_payment( $catalog_object['object'], $item_id );

		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => 'Failed to get item information'
			];
		}
	}

	/**
	 * Process catalog object for payment processing
	 */
	private function process_catalog_object_for_payment( $catalog_object, $item_id ) {
		$object_type = $catalog_object->getType();
		
		switch ( $object_type ) {
			case 'ITEM':
				return $this->process_item_for_payment( $catalog_object, $item_id );
			
			case 'ITEM_VARIATION':
				return $this->process_item_variation_for_payment( $catalog_object, $item_id );
			
			case 'SUBSCRIPTION_PLAN':
				return $this->process_subscription_plan_for_payment( $catalog_object, $item_id );
			
			default:
				return [
					'success' => false,
					'message' => 'Unsupported catalog object type for payment: ' . $object_type
				];
		}
	}

	/**
	 * Process ITEM type for payment
	 */
	private function process_item_for_payment( $catalog_object, $item_id ) {
		$item_object = $catalog_object->getValue();
		$item_data = $item_object->getItemData();
		$variations = $item_data->getVariations();

		if ( empty( $variations ) ) {
			return [
				'success' => false,
				'message' => 'Item has no pricing variations'
			];
		}

		$variation = $variations[0];
		$variation_object = $variation->getValue();
		$variation_data = $variation_object->getItemVariationData();
		$price = $variation_data->getPriceMoney();

		return [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $item_data->getName(),
				'price' => [
					'amount' => $price->getAmount(),
					'currency' => $price->getCurrency()
				]
			]
		];
	}

	/**
	 * Process ITEM_VARIATION type for payment
	 */
	private function process_item_variation_for_payment( $catalog_object, $item_id ) {
		$variation_object = $catalog_object->getValue();
		$variation_data = $variation_object->getItemVariationData();
		$price = $variation_data->getPriceMoney();

		return [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $variation_data->getName(),
				'type' => 'ITEM_VARIATION',
				'price' => [
					'amount' => $price->getAmount(),
					'currency' => $price->getCurrency()
				]
			]
		];
	}

	/**
	 * Process SUBSCRIPTION_PLAN type for payment
	 */
	private function process_subscription_plan_for_payment( $catalog_object, $item_id ) {
		$plan_object = $catalog_object->getValue();
		$plan_data = $plan_object->getSubscriptionPlanData();
		$plan_variations = $plan_data->getSubscriptionPlanVariations();

		if ( empty( $plan_variations ) ) {
			return [
				'success' => false,
				'message' => 'Subscription plan has no variations'
			];
		}

		$plan_variation = $plan_variations[0];
		$plan_variation_object = $plan_variation->getValue();
		$phases = $plan_variation_object->getSubscriptionPlanVariationData()->getPhases();
		
		if ( empty( $phases ) ) {
			return [
				'success' => false,
				'message' => 'Subscription plan has no pricing phases'
			];
		}

		$first_phase = $phases[0];
		$pricing = $first_phase->getPricing();
		$price_money = $pricing->getPriceMoney();

		return [
			'success' => true,
			'item' => [
				'id' => $item_id,
				'name' => $plan_data->getName(),
				'type' => 'SUBSCRIPTION_PLAN',
				'planVariationId' => $plan_variation->getValue()['id'] ?? null,
				'price' => [
					'amount' => $price_money->getAmount(),
					'currency' => $price_money->getCurrency()
				]
			]
		];
	}

	/**
	 * Grant course access after successful payment
	 * 
	 * @param array $customer_data
	 * @param array $product_data
	 * @param array $payment_result
	 * @return bool
	 */
	private function grant_course_access( $customer_data, $product_data, $payment_result ) {
		// Create or get WordPress user
		$user = $this->get_or_create_user( $customer_data );
		
		if ( ! $user ) {
			return false;
		}
		
		// Grant access to the course/product
		if ( ! empty( $product_data['courseId'] ) ) {
			// Use Thrive Apprentice's access management system
			do_action( 'tva_grant_course_access', $user->ID, $product_data['courseId'], $payment_result );
		}
		
		return true;
	}

	/**
	 * Get or create WordPress user from customer data
	 * 
	 * @param array $customer_data
	 * @return WP_User|false
	 */
	private function get_or_create_user( $customer_data ) {
		$email = $customer_data['email'];
		
		// Check if user exists
		$user = get_user_by( 'email', $email );
		
		if ( $user ) {
			return $user;
		}
		
		// Create new user
		$username = sanitize_user( $customer_data['firstName'] . $customer_data['lastName'] . rand( 100, 999 ) );
		$password = wp_generate_password();
		
		$user_id = wp_create_user( $username, $password, $email );
		
		if ( is_wp_error( $user_id ) ) {
			error_log( 'TVA Square User Creation Error: ' . $user_id->get_error_message() );
			return false;
		}
		
		// Update user meta
		wp_update_user( [
			'ID' => $user_id,
			'first_name' => $customer_data['firstName'],
			'last_name' => $customer_data['lastName']
		] );
		
		// Send welcome email
		wp_new_user_notification( $user_id, null, 'user' );
		
		return get_user_by( 'ID', $user_id );
	}

	/**
	 * Get course URL after purchase
	 * 
	 * @param array $product_data
	 * @return string
	 */
	private function get_course_url( $product_data ) {
		if ( empty( $product_data['courseId'] ) ) {
			return home_url();
		}
		
		// Get course permalink
		return get_permalink( $product_data['courseId'] );
	}

	private function ensure_subscription_customer( $client, $data, $customer_result ) {
		if ( $customer_result['customer'] ) {
			return [
				'customer' => $customer_result['customer'],
				'error'    => $customer_result['error'],
				'productData' => $data['productData'],
			];
		}

		$customer_data = $data['customerData'] ?? [];
		$email = $customer_data['email'] ?? '';
		$first_name = $customer_data['firstName'] ?? '';
		$last_name = $customer_data['lastName'] ?? '';
		$phone = $customer_data['phone'] ?? '';

		if ( ! empty( $data['productData']['squareCustomerId'] ) ) {
			$existing_customer_id = $data['productData']['squareCustomerId'];
			try {
				$existing_response = $client->customers->retrieve( $existing_customer_id );
				if ( empty( $existing_response->getErrors() ) && $existing_response->getCustomer() ) {
					return [
						'customer' => $existing_response->getCustomer(),
						'error'    => null,
						'productData' => $data['productData'],
					];
				}
			} catch ( Exception $ignored ) {}
		}

		if ( $email ) {
			try {
				$search_request = new SearchCustomersRequest( [
					'query' => new CustomerQuery( [
						'filter' => new CustomerFilter( [
							'emailAddress' => new CustomerTextFilter( [
								'exact' => $email,
							] ),
						] ),
					] ),
				] );

				$search_response = $client->customers->search( $search_request );
				if ( empty( $search_response->getErrors() ) && ! empty( $search_response->getCustomers() ) ) {
					$found_customer = $search_response->getCustomers()[0];
					$data['productData']['squareCustomerId'] = $found_customer->getId();
					return [
						'customer' => $found_customer,
						'error'    => null,
						'productData' => $data['productData'],
					];
				}
			} catch ( Exception $ignored ) {}
		}

		if ( ! $first_name && ! $last_name && ! $email && ! $phone ) {
			return [
				'customer' => null,
				'error'    => 'Customer data missing for subscription payment',
				'productData' => $data['productData'],
			];
		}

		try {
			$create_request = new \Square\Customers\Requests\CreateCustomerRequest( [
				'givenName'    => $first_name,
				'familyName'   => $last_name,
				'emailAddress' => $email,
				'phoneNumber'  => $phone,
			] );

			$create_response = $client->customers->create( $create_request );
			if ( empty( $create_response->getErrors() ) && $create_response->getCustomer() ) {
				$created = $create_response->getCustomer();
				$data['productData']['squareCustomerId'] = $created->getId();
				return [
					'customer' => $created,
					'error'    => null,
					'productData' => $data['productData'],
				];
			}

			return [
				'customer' => null,
				'error'    => 'Customer creation failed: ' . json_encode( $create_response->getErrors() ),
				'productData' => $data['productData'],
			];

		} catch ( Exception $e ) {
			return [
				'customer' => null,
				'error'    => 'Customer creation error: ' . $e->getMessage(),
				'productData' => $data['productData'],
			];
		}
	}

	/**
	 * Search for customer by email address
	 *
	 * @param SquareClient $client Square API client
	 * @param string $email Customer email address
	 * @return object|null Customer object or null if not found
	 */
	private function search_customer_by_email( $client, $email ) {
		if ( empty( $email ) ) {
			return null;
		}

		try {
			$search_request = new SearchCustomersRequest( [
				'query' => new CustomerQuery( [
					'filter' => new CustomerFilter( [
						'emailAddress' => new CustomerTextFilter( [
							'exact' => $email,
						] ),
					] ),
				] ),
			] );

			$search_response = $client->customers->search( $search_request );

			if ( empty( $search_response->getErrors() ) && ! empty( $search_response->getCustomers() ) ) {
				return $search_response->getCustomers()[0];
			}
		} catch ( Exception $e ) {
			// If customer lookup fails, return null
		}

		return null;
	}

	/**
	 * Extract customer names from Square customer response
	 *
	 * @param mixed $square_customer Square customer response object
	 * @return array Array with 'firstName' and 'lastName' keys
	 */
	private function extract_customer_names( $square_customer ) {
		if ( empty( $square_customer ) ) {
			return ['firstName' => '', 'lastName' => ''];
		}

		$customer_obj = null;

		if ( method_exists( $square_customer, 'getCustomer' ) && $square_customer->getCustomer() ) {
			$customer_obj = $square_customer->getCustomer();
		} elseif ( method_exists( $square_customer, 'getCustomers' ) && ! empty( $square_customer->getCustomers() ) ) {
			$customer_obj = $square_customer->getCustomers()[0];
		}

		if ( empty( $customer_obj ) ) {
			return ['firstName' => '', 'lastName' => ''];
		}

		return [
			'firstName' => $customer_obj->getGivenName() ?? '',
			'lastName' => $customer_obj->getFamilyName() ?? '',
		];
	}

	/**
	 * Get customer data from Square (transient or API)
	 *
	 * @param string $gateway_order_id Square order ID
	 * @param object $payment Square payment object
	 * @param SquareClient $client Square API client
	 * @param string $user_email Customer email address
	 * @return array Array with 'firstName', 'lastName', 'transient_key', and 'stored_customer_data'
	 */
	private function get_customer_data_from_square( $gateway_order_id, $payment, $client, $user_email ) {
		$transient_key = 'tva_square_customer_data_' . md5( $gateway_order_id );
		$stored_customer_data = get_transient( $transient_key );
		$user_first_name = '';
		$user_last_name = '';

		// Use stored data if available
		if ( $stored_customer_data ) {
			$user_first_name = $stored_customer_data['firstName'] ?? '';
			$user_last_name = $stored_customer_data['lastName'] ?? '';
			return [
				'firstName' => $user_first_name,
				'lastName' => $user_last_name,
				'transient_key' => $transient_key,
				'stored_customer_data' => $stored_customer_data,
			];
		}

		// Try to get customer info from Square API
		$square_customer_id = $payment->getCustomerId();
		$square_customer = null;

		// If no customer ID, try searching by email
		if ( empty( $square_customer_id ) ) {
			$found_customer = $this->search_customer_by_email( $client, $user_email );
			if ( $found_customer ) {
				$square_customer_id = $found_customer->getId();
				$user_first_name = $found_customer->getGivenName() ?? '';
				$user_last_name = $found_customer->getFamilyName() ?? '';
				// Return early with found customer data
				return [
					'firstName' => $user_first_name,
					'lastName' => $user_last_name,
					'transient_key' => $transient_key,
					'stored_customer_data' => null,
				];
			}
		}

		// Fetch customer by ID if we have one
		if ( ! empty( $square_customer_id ) ) {
			try {
				$square_customer = $client->customers->get(
					new GetCustomersRequest([
						'customerId' => $square_customer_id,
					])
				);
			} catch ( Exception $e ) {
				// If customer lookup fails, continue without name data
			}
		}

		// Extract customer data from Square response
		$names = $this->extract_customer_names( $square_customer );
		$user_first_name = $names['firstName'];
		$user_last_name = $names['lastName'];

		return [
			'firstName' => $user_first_name,
			'lastName' => $user_last_name,
			'transient_key' => $transient_key,
			'stored_customer_data' => null,
		];
	}

	/**
	 * Calculate display name from customer data
	 *
	 * @param string $user_first_name First name
	 * @param string $user_last_name Last name
	 * @param string $user_email Email address
	 * @param array|null $stored_customer_data Stored customer data from transient
	 * @param string|null $transient_key Transient key for cleanup
	 * @return string Display name
	 */
	private function calculate_user_display_name( $user_first_name, $user_last_name, $user_email, $stored_customer_data = null, $transient_key = null ) {
		// Use stored display name if available
		if ( is_array( $stored_customer_data ) && ! empty( $stored_customer_data['displayName'] ) ) {
			if ( $transient_key ) {
				delete_transient( $transient_key );
			}
			return $stored_customer_data['displayName'];
		}

		// Clean up transient if it exists but didn't have displayName
		if ( is_array( $stored_customer_data ) && ! empty( $stored_customer_data ) && $transient_key ) {
			delete_transient( $transient_key );
		}

		// Calculate from first and last name
		$display_name = trim( $user_first_name . ' ' . $user_last_name );
		if ( ! empty( $display_name ) ) {
			return $display_name;
		}

		// Fallback to email prefix
		$email_prefix_pos = strpos( $user_email, '@' );
		if ( $email_prefix_pos !== false ) {
			return substr( $user_email, 0, $email_prefix_pos );
		}

		return $user_email;
	}

	/**
	 * Store customer data in transient for later retrieval
	 *
	 * @param array $customer_data Customer data array with firstName, lastName, email
	 * @param string $order_id Square order ID
	 * @return string Transient key used for storage
	 */
	private function store_customer_data_in_transient( $customer_data, $order_id ) {
		if ( empty( $customer_data ) || empty( $order_id ) ) {
			return '';
		}

		$customer_first_name = $customer_data['firstName'] ?? '';
		$customer_last_name = $customer_data['lastName'] ?? '';
		$customer_email = $customer_data['email'] ?? '';

		// Calculate display name (no stored data or transient key needed here since we're storing, not retrieving)
		$customer_display_name = $this->calculate_user_display_name( $customer_first_name, $customer_last_name, $customer_email, null, null );

		$customer_data_to_store = [
			'firstName' => $customer_first_name,
			'lastName' => $customer_last_name,
			'email' => $customer_email,
			'displayName' => $customer_display_name,
		];

		$transient_key = 'tva_square_customer_data_' . md5( $order_id );
		set_transient( $transient_key, $customer_data_to_store, 3600 );

		return $transient_key;
	}
}

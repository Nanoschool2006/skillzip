<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

use Random\RandomException;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use TVA\Product;
use TVA\Stripe\Connection_V2;
use TVA\Stripe\Credentials;
use TVA\Stripe\Events\Generic;
use TVA\Stripe\Helper;
use TVA\Stripe\Hooks;
use TVA\Stripe\Request;
use TVA\Stripe\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TVA_Stripe_Controller extends TVA_REST_Controller {

	public $base = 'stripe';

	const THRIVE_KEY = '@#$()%*%$^&*(#@$%@#$%93827456MASDFJIK3245';

	const API_URL = 'https://service-api.thrivethemes.com/stripe/connect_ouath';

	// const API_URL = 'https://services-api.thrivethemes.com.test/stripe/connect_ouath';

	public function register_routes() {
		$stripe_connection    = Connection_V2::get_instance();
		$webhook_endpoint     = $stripe_connection->get_webhook_endpoint();
		$credentials_endpoint = Credentials::get_credentials_endpoint();

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/connect_account', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_connect_account_link' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/disconnect', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'disconnect' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
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

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/' . $webhook_endpoint, [
			[
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => [ $this, 'generic_listen' ],
				'permission_callback' => '__return_true',
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
					'secret_key'           => [
						'required' => true,
						'type'     => 'string',
					],
					'test_secret_key'      => [
						'required' => true,
						'type'     => 'string',
					],
					'publishable_key'      => [
						'required' => true,
						'type'     => 'string',
					],
					'test_publishable_key' => [
						'required' => true,
						'type'     => 'string',
					],
					'stripe_user_id'       => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/products', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_products' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'force'     => [
						'required' => false,
						'type'     => 'boolean',
						'default'  => false,
					],
					'test_mode' => [
						'required' => true,
						'type'     => 'boolean',
						'default'  => false,
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/prices', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_prices' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
				'args'                => [
					'product_id' => [
						'required' => true,
						'type'     => 'string',
					],
					'test_mode'  => [
						'required' => true,
						'type'     => 'boolean',
						'default'  => false,
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/prices', [
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
						'type'     => 'string'
					],
				],
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
						'enum'     => [ Settings::ALLOW_CHECKOUT_COUPONS_OPTIONS, Settings::AUTO_DISPLAY_BUY_BUTTON_OPTIONS ],
					],
					'value'   => [
						'required' => true,
						'type'     => 'string',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/manage_product', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'set_manage_product' ],
				'permission_callback' => [ 'TVA_Product', 'has_access' ],
			],
		] );

        register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/customer_portal', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'set_customer_portal' ],
                'permission_callback' => [ 'TVA_Product', 'has_access' ],
            ],
        ] );
	}

	/**
	 * Save the Stripe credentials.
	 *
	 * This method is responsible for saving the Stripe credentials.
	 * It checks the state and if it's valid, it saves the credentials.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 * @throws RandomException If the state is invalid.
	 */
	public function save_credentials( WP_REST_Request $request ) {
		$state      = $request->get_param( 'state' );
		$site_state = Credentials::get_state();
		if ( ! $state || ! $site_state || $state !== $site_state ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid state ' . $state . '  #   ' . $site_state, 'thrive-apprentice' ) ], 400 );
		}
		$secret_key           = $request->get_param( 'secret_key' );
		$test_secret_key      = $request->get_param( 'test_secret_key' );
		$publishable_key      = $request->get_param( 'publishable_key' );
		$test_publishable_key = $request->get_param( 'test_publishable_key' );
		$stripe_user_id       = $request->get_param( 'stripe_user_id' );

		Credentials::save_credentials( $secret_key, $publishable_key, $stripe_user_id, $test_secret_key, $test_publishable_key );

		/**
		 * Enable the customer portal.
		 */
        Request::enable_customer_portal();

        return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * This method is responsible for saving Stripe general settings, those settings are used as default for each product setup .
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		$setting = $request->get_param( 'setting' );
		$value   = $request->get_param( 'value' );

		if ( ! in_array( $setting, [ Settings::ALLOW_CHECKOUT_COUPONS_OPTIONS, Settings::AUTO_DISPLAY_BUY_BUTTON_OPTIONS ] ) ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => __( 'Invalid setting', 'thrive-apprentice' ) ], 400 );
		}

		Settings::update_setting( $setting, $value );

		return new WP_REST_Response( [ 'success' => true ] );
	}

	public function disconnect() {
		Credentials::disconnect();

		return new WP_REST_Response( [ 'success' => true ] );
	}

	/**
	 * Get the URL to connect a Stripe account.
	 *
	 * This method is responsible for generating the URL to connect a Stripe account.
	 * It adds the necessary parameters to the API URL.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 * @throws RandomException If the state is invalid.
	 */
	public function get_connect_account_link( WP_REST_Request $request ) {
		$url = $request->get_param( 'url' );
		if ( empty( $url ) ) {
			$url = admin_url( 'admin.php?page=thrive_apprentice#settings/payments/stripe' );
		}

		$url = remove_query_arg( 'tve_stripe_connect_error', $url );

		$data = [
			'customer_site_url'        => $url,
			'endpoint_url'             => $this->get_credentials_endpoint_url(),
			'state'                    => Credentials::get_state(),
			'tve_gateway_connect_init' => 'stripe_connect',
		];

		$response = wp_remote_post( static::API_URL, [
			'body' => $data,
			'sslverify' => false
		] );
		$response = wp_remote_retrieve_body( $response );

		return new WP_REST_Response( [ 'success' => filter_var( $response, FILTER_VALIDATE_URL ) !== false, 'url' => $response ] );
	}

	/**
	 * Generic endpoint to listen for Stripe webhooks.
	 *
	 * This method is responsible for handling Stripe webhooks.
	 * It verifies the signature and processes the event.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response object.
	 */
	public function generic_listen( WP_REST_Request $request ) {
		$stripe_connection   = Connection_V2::get_instance();
		$webhook_secret      = $stripe_connection->get_webhook_secret();
		$stripe_signature    = $request->get_header( 'stripe-signature' );
		$test_webhook_secret = $stripe_connection->get_test_webhook_secret();
		$success             = false;
		$message             = __( 'Invalid signature', 'thrive-apprentice' );

		if ( empty( $webhook_secret ) ) {
			$webhook_secret = $test_webhook_secret;
		}

		if ( $stripe_signature && $webhook_secret ) {
			try {
				$this->handle_stripe_event( $request, $stripe_signature, $webhook_secret );
				$success = true;
				$message = __( 'Event processed', 'thrive-apprentice' );

			} catch ( Exception $e ) {
				// If the webhook secret doesn't work try with the test secret too
				if ( ! $stripe_connection->get_test_mode() ) {
					try {
						$stripe_connection->set_test_mode( true );
						$this->handle_stripe_event( $request, $stripe_signature, $test_webhook_secret );
						$success = true;
						$message = __( 'Event processed', 'thrive-apprentice' );
					} catch ( Exception $e ) {
						$message = $e->getMessage();
					}
				} else {
					$message = $e->getMessage();
				}
			}
		}

		return new WP_REST_Response( [ 'success' => $success, 'message' => $message ] );
	}

	/**
	 * Handle the Stripe event.
	 *
	 * This method is responsible for handling the Stripe event.
	 * It constructs the event and triggers the necessary actions.
	 *
	 * @param $request          - The request object.
	 * @param $stripe_signature - The Stripe signature.
	 * @param $webhook_secret   - The webhook secret.
	 *
	 * @throws SignatureVerificationException If the signature is invalid.
	 */
	protected function handle_stripe_event( $request, $stripe_signature, $webhook_secret ) {
		$stripe_event = Webhook::constructEvent(
			$request->get_body(),
			$stripe_signature,
			$webhook_secret
		);

		/**
		 * Action triggered when a valid Stripe webhook is received
		 *
		 * @param Event $stripe_event
		 */
		do_action( 'tva_stripe_webhook', $stripe_event );
		do_action( 'tva_stripe_webhook_' . $stripe_event->type, $stripe_event );

		$class_name = Generic::get_class_name( $stripe_event->type );

		if ( class_exists( $class_name ) ) {
			/** @var Generic $event */
			$event = new $class_name( $stripe_event );
			$event->do_action();
		}
	}


	/**
	 * Handle the request to get all products
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_products( WP_REST_Request $request ) {
		$force          = $request->get_param( 'force' );
		$test_mode 		= $request->get_param( 'test_mode' );
		$message   		= '';
		$products  		= [];
		$success   		= true;

		try {
			$product_id = $request->get_param( 'product_id' );
			if ( empty( $product_id ) ) {
				$products = Request::get_all_products( $force, $test_mode );
			} else {
				$products[] = Request::get_product( $product_id, $test_mode );
			}
			
			
		} catch ( Exception $e ) {
			$message = $e->getMessage();
			$success = false;
		}

		return new WP_REST_Response( [ 'products' => $products, 'success' => $success, 'message' => $message ] );
	}

	/**
	 * Handle the request to get the prices for a product
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_prices( WP_REST_Request $request ) {
		$test_mode 		 = $request->get_param( 'test_mode' );
		$product_id      = $request->get_param( 'product_id' );

		$prices = Request::get_product_prices( $product_id, $test_mode );

		if ( $prices instanceof WP_Error ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $prices->get_error_message(), ], 400 );
		}

		return new WP_REST_Response( [ 'success' => true, 'prices' => $prices, ] );
	}

	/**
	 * Get the credentials endpoint URL.
	 *
	 * This method is responsible for generating the credentials endpoint URL.
	 *
	 * @return string The credentials endpoint URL.
	 */
	public function get_credentials_endpoint_url() {
		$endpoint = Credentials::get_credentials_endpoint();

		return get_rest_url() . TVA_Stripe_Controller::$namespace . TVA_Stripe_Controller::$version . '/' . $this->base . '/' . $endpoint;
	}

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
	 * Create a new price for a product.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The REST response.
	 */
	public function create_price( WP_REST_Request $request ) {
		$product_id = (int) $request->get_param( 'product_id' );

		// Retrieve the test product and its ID.
		$test_product = Helper::get_stripe_product( $product_id, true );
		if ( $test_product instanceof WP_Error ) {
			return new WP_REST_Response( [ 'success' => false, 'error' => $test_product->get_error_message(), ], 400 );
		}

		// Generate price data based on request parameters and test stripe product ID.
		$price_data = Helper::generate_price_data( $request->get_params(), $test_product->id );

		// Create a new price for the test product.
		$test_price = Request::create_product_price( $price_data, true );

		// Retrieve the live product and its ID.
		$live_product = Helper::get_stripe_product( $product_id );
		if ( $live_product instanceof WP_Error ) {
			// If an error occurs, return a response indicating failure.
			return new WP_REST_Response( [ 'success' => false, 'error' => $live_product->get_error_message(), ], 400 );
		}

		// Set the product ID for the live price data.
		$price_data['product'] = $live_product->id;

		// Create a new price for the live product.
		$live_price = Request::create_product_price( $price_data );
		// Return a response indicating success along with relevant data.

		if ( ! Helper::is_product_manageable( $product_id ) ) {
			update_term_meta( $product_id, 'tva_stripe_manageable', 1 );
		}

		return new WP_REST_Response( [ 
			'success'      => true, 
			'test_product' => $test_product, 
			'live_product' => $live_product,
			'test_price'   => $test_price, 
			'live_price'   => $live_price
		] );
	}


	/**
	 * Set the Stripe product as manageable from thrive-appertice.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * 
	 * @return WP_REST_Response The REST response.
	 */
	public function set_manage_product( WP_REST_Request $request ) {		
		// Get the product ID from the request.
		$product_id = $request->get_param( 'product_id' );
		
		try {
			// Create a new product object based on the provided product ID.
			$product = new Product( (int) $product_id );
			
			// Retrieve rules for Stripe integration for the product.
			$rules = $product->get_rules_by_integration( 'stripe' );

			// Get the last rule.
			$rule = array_pop( $rules );

			// Define the modes.
			$modes = [ 'test', 'live' ];

			// Get metadata for the product data.
			$metadata = Helper::get_stripe_meta_data( $product_id );

			// Prepare product data.
			$product_data = [
				'name'     => $product->get_name(),
				'metadata' => $metadata
			];

			// Check if there are rules and items exist.
			if ( $rule && count( $rule['items'] ) ) {
				foreach ( $rule['items'] as $item ) {

					// Loop through modes.
					foreach ( $modes as $mode ) {
						$test_mode = $mode === 'test';

						// Get the Stripe product ID for the mode.
						$stripe_product_id = $item[ $mode . '_product_id' ] ?? '';

						// If the Stripe product ID is not empty, update the product.
						if ( ! empty( $stripe_product_id ) ) {
							Request::update_product( $stripe_product_id, $product_data, $test_mode );
						} else {
							// Otherwise, create a new product.
							$stripe_product = Request::create_product( $product_data, $test_mode );
							$stripe_product_id = $stripe_product->id;
						}

						// Get prices for the Stripe product.
						$prices = Request::get_product_prices( $stripe_product_id, $test_mode );

						if ( ! $prices instanceof WP_Error ) {
							// Prepare price data.
							$price_data = [
								'metadata' => $metadata
							];

							if ( count( $prices ) ) {
								// Update product prices.
								foreach ( $prices as $price ) {
									Request::update_product_price( $price->id, $price_data );
								}
							}
						}
					}
				}
			}

			// Mark the product as manageable.
			update_term_meta( $product_id, 'tva_stripe_manageable', 1 );
		} catch ( \Exception $e ) {			
			// Return a response indicating failure with the error message.
			return new WP_REST_Response( [ 'success' => false, 'error' => $e->getMessage() ], 400 );
		}

		// Return a response indicating success.
		return new WP_REST_Response( [ 'success' => true ] );
	}

    public function set_customer_portal() {
		/**
		 * Enable the customer portal if it is not already enabled.
		 */
        $response = Request::enable_customer_portal();

        $message = true === $response
            ? __( 'Customer portal enabled', 'thrive-apprentice' )
            : $response;

        return new WP_REST_Response( [
            'success' => true === $response,
            'message' => esc_html( $message )
        ] );
    }
}

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe;

use Exception;
use Stripe\Event;
use Stripe\StripeClient;
use TVA_Stripe_Controller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Connection_V2 {

	const STRIPE_VERSION = '2023-10-16';

	const WEBHOOK_SECRET_OPTION = 'tva_stripe_webhook_secret';

	const WEBHOOK_TEST_SECRET_OPTION = 'tva_stripe_webhook_secret_test';

	const WEBHOOK_ENDPOINT_OPTION = 'tva_stripe_webhook_endpoint';

	protected static $_instance;

	protected $client;

	protected $client_test;

	protected $api_key;

	protected $is_test_mode = false;

	protected $test_key;

	protected $webhook_secret;

	protected $webhook_endpoint;

	public function __construct() {
		$this->read_credentials();
		$this->ensure_client();
	}

	/**
	 * @return Connection | Connection_V2
	 */
	public static function get_instance() {
		if ( empty( static::$_instance ) ) {
			$version = Hooks::get_stripe_version();
			if ( $version === 'v1' ) {
				static::$_instance = Connection::get_instance();
			} else {
				static::$_instance = new self();
			}
		}

		return static::$_instance;
	}

	public function get_webhook_secret() {
		if ( ! $this->webhook_secret ) {
			$this->webhook_secret = get_option( $this->is_test_mode ? static::WEBHOOK_TEST_SECRET_OPTION : static::WEBHOOK_SECRET_OPTION, '' );
		}

		return $this->webhook_secret;
	}

	public function get_test_webhook_secret() {
		return get_option( static::WEBHOOK_TEST_SECRET_OPTION, '' );
	}

	/**
	 * @return StripeClient
	 */
	public function get_client() {
		if ( ! $this->client ) {
			$this->ensure_client();
		}
		if ( ! $this->client_test ) {
			$this->ensure_test_client();
		}

		return $this->is_test_mode || ! $this->client ? $this->client_test : $this->client;
	}

	public function set_test_mode( $mode ) {
		$this->is_test_mode = $mode;
	}

	public function get_test_mode() {
		return $this->is_test_mode;
	}

	private function read_credentials() {
		$this->test_key = Credentials::get_secret_key( false ) ?: '';
		$this->api_key  = Credentials::get_secret_key() ?: '';

		if ( ! empty( $this->test_key ) && empty( $this->api_key ) ) {
			$this->is_test_mode = true;
		}
	}

	public function get_api_key() {
		if ( ! $this->api_key ) {
			$this->read_credentials();
		}

		return $this->api_key;
	}

	public function get_test_key() {
		if ( ! $this->test_key ) {
			$this->read_credentials();
		}

		return $this->test_key;
	}

	public function get_webhook_endpoint() {
		if ( ! $this->webhook_endpoint ) {
			$this->webhook_endpoint = get_option( static::WEBHOOK_ENDPOINT_OPTION, '' );
		}

		if ( ! $this->webhook_endpoint ) {
			$endpoint = uniqid( 'tva-webhook-' );
			update_option( static::WEBHOOK_ENDPOINT_OPTION, $endpoint, false );
			$this->webhook_endpoint = $endpoint;
		}

		return $this->webhook_endpoint;
	}

	/**
	 * @return StripeClient
	 */
	protected function ensure_client() {
		if ( ! $this->client ) {
			$api_key = $this->get_api_key();

			if ( $api_key ) {
				$this->client = new StripeClient( [
					'api_key'        => $api_key,
					'stripe_version' => static::STRIPE_VERSION,
				] );
			}
		}

		return $this->client;
	}

	/**
	 * @return StripeClient
	 */
	protected function ensure_test_client() {
		if ( ! $this->client_test ) {
			$test_key = $this->get_test_key();
			if ( $test_key ) {
				$this->client_test = new StripeClient( [
					'api_key'        => $test_key,
					'stripe_version' => static::STRIPE_VERSION,
				] );
			}
		}

		return $this->client_test;
	}

	public function save_webhook_secret( $secret, $live_mode ) {
		$this->webhook_secret = $secret;
		$option               = $live_mode ? static::WEBHOOK_SECRET_OPTION : static::WEBHOOK_TEST_SECRET_OPTION;
		update_option( $option, $secret, false );
	}

	public function get_endpoint_url() {
		$endpoint = $this->get_webhook_endpoint();

		return get_rest_url() . TVA_Stripe_Controller::$namespace . TVA_Stripe_Controller::$version . '/stripe/' . $endpoint;
	}

	public function ensure_endpoint( $live_mode = true ) {
		$valid = true;
		$url   = $this->get_endpoint_url();

		if ( $live_mode ) {
			$api_key = $this->get_api_key();
		} else {
			$api_key = $this->get_test_key();
		}

		$client = new StripeClient( $api_key );

		try {
			$webhooks = $client->webhookEndpoints->all();
			$found    = false;
			
			foreach ( $webhooks as $webhook ) {
				if ( $webhook->url === $url ) {
					$found = true;
					break;
				}
			}

			if ( ! $found ) {
				$webhook = $client->webhookEndpoints->create( [
					'api_version'    => static::STRIPE_VERSION,
					'url'            => $url,
					'enabled_events' => [
						Event::CHARGE_DISPUTE_CLOSED,
						Event::CHARGE_DISPUTE_CREATED,
						Event::CHARGE_SUCCEEDED,
						Event::CHECKOUT_SESSION_COMPLETED,
						Event::CHECKOUT_SESSION_ASYNC_PAYMENT_FAILED,
						Event::CHECKOUT_SESSION_ASYNC_PAYMENT_SUCCEEDED,
						Event::CUSTOMER_SUBSCRIPTION_CREATED,
						Event::CUSTOMER_SUBSCRIPTION_DELETED,
						Event::CUSTOMER_SUBSCRIPTION_UPDATED,
						Event::CUSTOMER_SUBSCRIPTION_PAUSED,
						Event::CUSTOMER_SUBSCRIPTION_RESUMED,
						Event::CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_EXPIRED,
						Event::CUSTOMER_SUBSCRIPTION_PENDING_UPDATE_APPLIED,
						Event::CUSTOMER_SUBSCRIPTION_TRIAL_WILL_END,
						Event::INVOICE_CREATED,
						Event::INVOICE_DELETED,
						Event::INVOICE_FINALIZATION_FAILED,
						Event::INVOICE_FINALIZED,
						Event::INVOICE_MARKED_UNCOLLECTIBLE,
						Event::INVOICE_PAID,
						Event::INVOICE_PAYMENT_ACTION_REQUIRED,
						Event::INVOICE_PAYMENT_FAILED,
						Event::INVOICE_PAYMENT_SUCCEEDED,
						Event::INVOICE_SENT,
						Event::INVOICE_UPCOMING,
						Event::INVOICE_UPDATED,
						Event::INVOICE_VOIDED,
						Event::PAYMENT_INTENT_CANCELED,
						Event::PAYMENT_INTENT_PAYMENT_FAILED,
						Event::PAYMENT_INTENT_PROCESSING,
						Event::PAYMENT_INTENT_SUCCEEDED,
						Event::REFUND_CREATED,
					]
				],
					[
						'stripe_account' => Credentials::get_account_id(),
					]
				);
				if ( $webhook->secret ) {
					$this->save_webhook_secret( $webhook->secret, $live_mode );
				}
				Request::clear_cache();
			} else {
				$this->update_webhook_events( $live_mode );
			}

		} catch ( Exception $e ) {
			$valid = false;
		}

		return $valid;
	}

	/**
	 * Update the webhook endpoint with additional events
	 * 
	 * @param bool $live_mode Whether to update live or test mode webhook
	 * @return bool True if the update was successful, false otherwise
	 */
	public function update_webhook_events( $live_mode = true ) {
		$valid = false;
		$url   = $this->get_endpoint_url();

		if ( $live_mode ) {
			$api_key = $this->get_api_key();
		} else {
			$api_key = $this->get_test_key();
		}

		if ( empty( $api_key ) ) {
			return false;
		}

		$client = new StripeClient( $api_key );

		try {
			$webhooks = $client->webhookEndpoints->all();
			$existing_webhook = null;
			
			foreach ( $webhooks as $webhook ) {
				if ( $webhook->url === $url ) {
					$existing_webhook = $webhook;
					break;
				}
			}

			if ( $existing_webhook ) {
				// Check if refund.created is already in enabled_events
				$enabled_events = $existing_webhook->enabled_events;

				// Remove Event::REFUND_CREATED from enabled events
				$refund_created = 'refund.created';
				$enabled_events = array_diff( $enabled_events, [ $refund_created ] );

				if ( ! in_array( $refund_created, $enabled_events ) ) {
					// Add refund.created to enabled_events
					$enabled_events[] = $refund_created;
					
					// Update the webhook with the new events
					$updated_webhook = $client->webhookEndpoints->update(
						$existing_webhook->id,
						[
							'enabled_events' => $enabled_events,
						]
					);

					if ( $updated_webhook ) {
						$valid = true;
						Request::clear_cache();
					}
				}
			}

		} catch ( Exception $e ) {
			$valid = false;
		}

		return $valid;
	}

/**
 * Ensures that the customer portal configuration is set up correctly.
 * This method checks if the necessary settings for the customer portal are present,
 * and if not, it sets them to their default values.
 *
 * @param bool $live_mode Whether to check the live or test mode configuration
 * @return mixed True if the configuration is valid, error message otherwise.
 */
	public function ensure_customer_portal_configuration( $live_mode = true ) {

		if ( $live_mode ) {
			$api_key = $this->get_api_key();
		} else {
			$api_key = $this->get_test_key();
		}

		$client = new StripeClient( $api_key );

		$valid = true;

		/**
		 * Filter the features available in the Stripe customer portal.
		 *
		 * This filter allows you to modify the features available in the Stripe customer portal.
		 *
		 * @param array $features An array of features available in the customer portal.
		 * @return array The modified array of features.
		 */
		$features = apply_filters( 'tva_stripe_customer_portal_features', [
			'customer_update' => [
				'enabled'        => true,
				'allowed_updates' => [
					'address',
					'email',
					'name',
					'phone',
				],
			],
			'invoice_history' => [
				'enabled' => true,
			],
			'payment_method_update' => [
				'enabled' => true,
			],
            'subscription_cancel' => [
                'enabled' => true,
            ],
		] );

		if ( $client ) {
			$configurations = $client->billingPortal->configurations->all( [
				 'active'     => true, 
				 'is_default' => false,
				 'limit'      => 1
			] );

			if ( $configurations && count( $configurations->data ) ) {
				$configuration = $configurations->data[0];
				$current_features      = $configuration->features;
		
				if ( $this->features_are_equal( $current_features, $features ) ) {
					$valid = true;
				} else {
					try {
						$configuration = $client->billingPortal->configurations->update( $configuration->id, [
							'features' => $features
						] );

						if ( $configuration ) {
							$valid = true;
						}
					} catch ( Exception $e ) {
						$valid = $e->getMessage();
					}
				}
			} else {
				try {
					$configuration = $client->billingPortal->configurations->create( [
                        'business_profile' => [
                            'privacy_policy_url' => home_url(),
                            'terms_of_service_url' => home_url()
                        ],
						'features' => $features
					] );

					if ( $configuration ) {
						$valid = true;
					}
				} catch ( Exception $e ) {
					$valid = $e->getMessage();
				}
			}
		}

		return $valid;
	}



	/**
	 * Check if the current features are equal to the default features.
	 *
	 * @param stdClass $current_features Current features object.
	 * @param array    $default_features Default features array.
	 * 
	 * @return bool true if features are equal, false otherwise.
	 */
	private function features_are_equal( $current_features, $default_features ) {
		foreach ( $default_features as $feature => $data ) {
			$feature_object = $current_features->{ $feature };

			if ( ! $feature_object ) {
				return false;
			}

			if ( 'customer_update' === $feature ) {
				foreach ( $data[ 'allowed_updates' ] as $update ) {
					if ( ! in_array( $update, $feature_object->allowed_updates ) ) {
						return false;
					}
				}
			} else {
				if ( $data[ 'enabled' ] != $feature_object->enabled ) {
					return false;
				}
			}
		}

		return true;
	}
}

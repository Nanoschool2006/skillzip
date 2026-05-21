<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Buy_Now;

use Exception;
use TVA\Stripe\Connection_V2;
use TVA\Stripe\Credentials;
use TVA\Stripe\Hooks;
use TVA\Stripe\Settings;
use function get_home_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Stripe extends Generic {

	public static $CACHE = [];


	/**
	 * @var mixed|string
	 */
	private $product_id;
	private $price_id;
	private $success_url;
	private $cancel_url;
	private $test_mode;
	private $price_type;
	private $stripe_connection;

	private $populate_email;

	private $trial     = 0;
	private $reference = '';
	private $allow_coupons;

	/**
	 * Stripe constructor.
	 *
	 * @param array $data The data associated with the Stripe payment.
	 */
	public function __construct( $data ) {
		parent::__construct( $data );
		$this->parse_data();
	}


	/**
	 * Parse the data associated with the Stripe payment.
	 *
	 * This method is responsible for parsing the data associated with the Stripe payment.
	 * It extracts the necessary information from the data and stores it in the class properties.
	 */
	protected function parse_data() {
		$this->price_id = isset( $this->data['price_id'] ) ? $this->data['price_id'] : '';
		if ( ! empty( $this->price_id ) ) {
			$this->product_id = isset( $this->data['product_id'] ) ? $this->data['product_id'] : '';
			$this->test_mode  = isset( $this->data['test_mode'] ) ? $this->data['test_mode'] : false;

			$price_type_key   = $this->test_mode ? 'test_price_type' : 'live_price_type';
			$this->price_type = isset( $this->data[ $price_type_key ] ) ? $this->data[ $price_type_key ] : '';

			$this->success_url = isset( $this->data['success_url'] ) ? $this->data['success_url'] : get_home_url();
			$this->cancel_url  = isset( $this->data['cancel_url'] ) ? $this->data['cancel_url'] : '';

			if ( ! empty( $this->success_url ) ) {
				$this->success_url = $this->append_url( $this->success_url );
			}
			if ( ! empty( $this->cancel_url ) ) {
				$this->cancel_url = $this->append_url( $this->cancel_url );
			}

			$this->populate_email = isset( $this->data['populate_email'] ) ? $this->data['populate_email'] : false;
			$this->allow_coupons  = isset( $this->data['allow_coupons'] ) ? $this->data['allow_coupons'] : Settings::get_setting( Settings::ALLOW_CHECKOUT_COUPONS_OPTIONS, false );
			$trial_key            = $this->test_mode ? 'test_free_trial' : 'live_free_trial';
			if ( $this->price_type === 'recurring' && isset( $this->data[ $trial_key ] ) && $this->data[ $trial_key ] ) {
				$this->trial = $this->data[ $this->test_mode ? 'test_free_trial_days' : 'live_free_trial_days' ];
			}

			$reference_key = $this->test_mode ? 'test_reference' : 'live_reference';
			if ( isset( $this->data[ $reference_key ] ) && $this->data[ $reference_key ] ) {
				$this->reference = $this->get_client_reference( $this->data[ $this->test_mode ? 'test_reference_type' : 'live_reference_type' ] );
			}

			$stripe = Connection_V2::get_instance();
			$stripe->set_test_mode( $this->test_mode );
			$this->stripe_connection = $stripe->get_client();
		}
	}

	/**
	 * Check if the Stripe payment is valid.
	 *
	 * This method checks if the price ID associated with the Stripe payment is not empty.
	 *
	 * @return bool True if the price ID is not empty, false otherwise.
	 */
	public function is_valid() {
		return ! empty( $this->price_id );
	}

	/**
	 * Get the URL for the checkout session
	 *
	 * This method is responsible for generating the URL for the checkout session.
	 * It creates a checkout session with the Stripe API and returns the URL for the session.
	 *
	 * @return string The URL for the checkout session.
	 */
	public function get_url() {

		/**
		 * If Stripe is not connected with Thrive Apprentice
		 */
		if ( ! Hooks::is_connected() ) {
			return '';
		}

		if ( $this->price_id && ! isset( static::$CACHE[ $this->price_id ] ) ) {
			$checkout_data = [
				'mode'       => $this->price_type === 'recurring' ? 'subscription' : 'payment',
				'line_items' => [
					[
						'price'    => $this->price_id,
						'quantity' => 1,
					],
				],
			];

			if ( ! empty( $this->success_url ) ) {
				$checkout_data['success_url'] = $this->success_url;
			}

			if ( ! empty( $this->cancel_url ) ) {
				$checkout_data['cancel_url'] = $this->cancel_url;
			}

			if ( is_user_logged_in() ) {
				$user_email = wp_get_current_user()->user_email;

				// Search for customer in Stripe via email
				$customer = $this->stripe_connection->customers->all([
					'email' => $user_email,
					'limit' => 1,
				]);

				if ( ! empty( $customer->data ) ) {
					$checkout_data['customer'] = $customer->data[0]->id;
				}

				// If the customer does not exist in Stripe, and populate email is enabled, add the email to the checkout data
				// we can not add the email to the checkout data if we sending customer data due to stripe api limitations
				if ( ! empty( $this->populate_email ) && $this->populate_email === true && empty( $checkout_data['customer'] ) ) {
					$checkout_data['customer_email'] = $user_email;
				}
			}

			if ( $this->allow_coupons ) {
				$checkout_data['allow_promotion_codes'] = true;
			}

			if ( ! empty( $this->reference ) ) {
				$checkout_data['client_reference_id'] = $this->reference;
			}

			if ( ! empty( $this->trial ) ) {
				$checkout_data['subscription_data'] = [
					'trial_period_days' => $this->trial,
					'trial_settings'    => [
						'end_behavior' => [
							'missing_payment_method' => 'cancel',
						],
					],
				];
			}

			/**
			 * Filter the Checkout Data.
			 *
			 * This allows developers to add additional metadata
			 * to the checkout data for the Buy Now button for Stripe.
			 *
			 * Example:
			 *
			 *    add_filter(
			 *        'tva_buy_now_url_stripe_checkout_data',
			 *        function( array $checkout_data, array $buynow_data ) : array {
			 *            return array_merge(
			 *                $checkout_data,
			 *
			 *                // Additional data to pass along with the checkout data...
			 *                array(
			 *
			 *                    // Add Metadata...
			 *                    'payment_intent_data' => array(
			 *                      'metadata' => array_merge(
			 *
			 *                          // Make sure and include any previously added metadata...
			 *                          $checkout_data['metadata'] ?? array(),
			 *
			 *                          // Add our metadata...
			 *                          array(
			 *                              'product_id'      => $buynow_data['product_id'] ?? ''
			 *                              'my_metadata_key' => 'my_metadata_value',
			 *                          )
			 *                      ),
			 *                    ),
			 *                )
			 *            );
			 *        },
			 *        10, 2
			 *    );
			 *
			 * @param array $checkout_data Checkout data used to generate the Buy Now button that leads to Stripe checkout.
			 * @param array $buynow_data   Additional data about the Buy Now button.
			 *
			 * @var array
			 */
			$checkout_data = apply_filters( 'tva_buy_now_url_stripe_checkout_data', $checkout_data, $this->data );

			try {
				static::$CACHE[ $this->price_id ] = $this->stripe_connection->checkout->sessions->create( $checkout_data, [
					'stripe_account' => Credentials::get_account_id(),
				] )->url;
			} catch ( Exception $e ) {
				error_log( 'Stripe Checkout Error: ' . $e->getMessage() );

				// do error mapping
				$mapped = $this->map_stripe_error( $e->getMessage() );

				//send base url + error message as parameter
				static::$CACHE[ $this->price_id ] = add_query_arg( 'error-stripe', __( $mapped, 'thrive-apprentice' ) );
			}
		}

		return isset( static::$CACHE[ $this->price_id ] ) ? static::$CACHE[ $this->price_id ] : '';
	}

	/**
	 * Get the client reference
	 *
	 * @param $type
	 *
	 * @return mixed|string
	 */
	public function get_client_reference( $type ) {
		if ( is_user_logged_in() ) {
			switch ( $type ) {
				case 'user_id':
					return get_user_meta( get_current_user_id(), Hooks::CUSTOMER_META_ID, true );
				case 'user_name':
					return wp_get_current_user()->display_name;
				default:
					return '';
			}
		}

		return '';
	}

	/**
	 * Append the price_id and product_id to the URL
	 *
	 * This method is responsible for appending the price ID and product ID to the URL.
	 * It uses the add_query_arg function to append the parameters to the URL.
	 *
	 * @param $url - The URL to which the parameters should be appended.
	 *
	 * @return string The URL with the parameters appended.
	 */
	protected function append_url( $url ) {
		return add_query_arg( [
			'price_id'   => $this->price_id,
			'product_id' => $this->product_id,
		], $url );
	}

	/**
	 * Map Stripe error messages to user-friendly text.
	 *
	 * @param string $message The Stripe error message.
	 *
	 * @return string The user-friendly error message.
	 *
	 * This function takes a Stripe error message and maps it to a user-friendly error message.
	 * It checks for specific error messages and returns a custom error message for each one.
	 * If the message does not match any of the special cases, it returns the original message.
	 */
	public function map_stripe_error( $message ) {
		// If the message is empty, return a default error message
		if ( empty( $message ) ) {
			return "Something went wrong with your payment link generation. Please try again.";
		}

		// Because of special cases, this is the best approach. A classic array mapping solution would require multiple nested if statements in a foreach loop.
		if ( strpos( $message, 'You cannot combine currencies' ) !== false ) {
			return "We're unable to process this payment due to a currency mismatch with your account. Please use a payment method in your account's currency or contact support for assistance.";
		}

		// Special case variant 1
		if ( strpos( $message, 'Currency' ) !== false && strpos( $message, 'not supported' ) !== false ) {
			return "We don't currently accept payments in this currency. Please try a different payment method or contact us for alternative payment options.";
		}

		if ( strpos( $message, 'Invalid currency' ) !== false ) {
			return "The currency code you've entered isn't recognized. Please check your payment details and try again.";
		}

		// Special case variant 2
		if ( strpos( $message, 'No such customer' ) !== false 
			|| strpos( $message, 'Customer' ) !== false && strpos( $message, 'not exist' ) !== false ) {
			return "We couldn't find your account information. Please check your details or create a new account to continue.";
		}

		if ( strpos( $message, 'This customer has no payment method' ) !== false ) {
			return "You'll need to add a payment method to complete this purchase. Please add a credit card or other payment option to your account.";
		}

		if ( strpos( $message, 'This customer has no default payment method' ) !== false ) {
			return "Please select a payment method or add a new one to complete your purchase.";
		}

		if ( strpos( $message, 'No such price' ) !== false 
			|| ( strpos( $message, 'Price' ) !== false && strpos( $message, 'not exist' ) !== false ) ) {
			return "This pricing option is no longer available. Please choose from our current plans or contact support for assistance.";
		}

		if ( strpos( $message, 'This price is not active' ) !== false ) {
			return "This plan is currently unavailable. Please select from our active subscription options or contact us for help.";
		}

		if ( strpos( $message, 'Subscription' ) !== false && strpos( $message, 'not exist' ) !== false ) {
			return "We couldn't find your subscription. Please check your account or contact support if you believe this is an error.";
		}

		if ( strpos( $message, 'This subscription is already canceled' ) !== false ) {
			return "This subscription has already been canceled. If you'd like to reactivate it or start a new subscription, please contact support.";
		}

		if ( strpos( $message, 'No such product' ) !== false 
			|| ( strpos( $message, 'Product' ) !== false && strpos( $message, 'not exist' ) !== false ) ) {
			return "This item is no longer available. Please check our current offerings or contact support for similar alternatives.";
		}

		if ( strpos( $message, 'This product is not active' ) !== false ) {
			return "This item is currently unavailable for purchase. Please browse our available products or contact us for assistance.";
		}

		if ( strpos( $message, 'Invalid line item' ) !== false ) {
			return "There's an issue with one of the items in your order. Please review your cart and try again, or contact support for help.";
		}

		// If nothing matches, return the original message
		return $message;
	}
}

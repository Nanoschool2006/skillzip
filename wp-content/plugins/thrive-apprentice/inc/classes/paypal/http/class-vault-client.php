<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Http;

use TVA\PayPal\Connection;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Vault_Client
 *
 * HTTP client scoped to the Product API subscription endpoints.
 * Handles create, activate, and cancel operations.
 * All shared HTTP/auth logic lives in Base_Client.
 */
class Vault_Client extends Base_Client {

	/**
	 * Must be redeclared so this class has its own singleton registry,
	 * isolated from Onboarding_Client and Order_Client.
	 *
	 * @var static[]
	 */
	protected static $instances = [];

	/**
	 * @return string
	 */
	protected function client_slug(): string {
		return 'vault';
	}

	/**
	 * Create a vault-based subscription order.
	 *
	 * Wraps $data in { "data": ... } per Product API contract.
	 *
	 * @param array $data { intent, source, purchase_units, recurring_times, total_cycles, thrive_order_id, application_context }
	 * @return array|\WP_Error
	 */
	public function create_subscription( array $data ) {
		return $this->post( Connection::ENDPOINT_SUBSCRIPTIONS_CREATE, [ 'data' => $data ] );
	}

	/**
	 * Activate (capture) a subscription after buyer approval.
	 *
	 * Maps to SubscriptionsProcessorController::capture() on the Product API.
	 *
	 * @param string $subscription_id PayPal order ID from create_subscription().
	 * @return array|\WP_Error
	 */
	public function activate_subscription( string $subscription_id ) {
		if ( '' === $subscription_id ) {
			return new \WP_Error( 'paypal_invalid_subscription_id', 'Subscription ID must not be empty.' );
		}
		$path = sprintf( Connection::ENDPOINT_SUBSCRIPTIONS_ACTIVATE, $subscription_id );
		return $this->post( $path, [] );
	}

	/**
	 * Cancel a subscription and delete the vault token.
	 *
	 * @param string $subscription_id PayPal order ID.
	 * @return array|\WP_Error
	 */
	public function cancel_subscription( string $subscription_id ) {
		if ( '' === $subscription_id ) {
			return new \WP_Error( 'paypal_invalid_subscription_id', 'Subscription ID must not be empty.' );
		}
		$path = sprintf( Connection::ENDPOINT_SUBSCRIPTIONS_CANCEL, $subscription_id );
		return $this->post( $path, [] );
	}
}

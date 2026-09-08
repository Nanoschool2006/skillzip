<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Http;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Order_Client
 *
 * HTTP client scoped to the Product API order endpoints
 * (create, capture, refund). All shared logic lives in Base_Client.
 */
class Order_Client extends Base_Client {

	/**
	 * Must be redeclared so this class has its own singleton registry,
	 * isolated from Onboarding_Client and Vault_Client.
	 *
	 * @var static[]
	 */
	protected static $instances = [];

	/**
	 * @return string
	 */
	protected function client_slug(): string {
		return 'order';
	}
}

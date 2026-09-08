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
 * Class Onboarding_Client
 *
 * HTTP client scoped to PayPal onboarding and credential-exchange endpoints.
 * All shared logic lives in Base_Client.
 */
class Onboarding_Client extends Base_Client {

	/**
	 * Must be redeclared so this class has its own singleton registry,
	 * isolated from Order_Client and Vault_Client.
	 *
	 * @var static[]
	 */
	protected static $instances = [];

	/**
	 * @return string
	 */
	protected function client_slug(): string {
		return 'onboarding';
	}
}

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Square;

use TVA\Product;
use WpOrg\Requests\Exception\Http\Status304;
use function add_action;
use function add_filter;
use TVA_Square_Controller;
use TVA_Customer;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Hooks
 *
 * This class provides methods for handling Square hooks.
 */
class Hooks {

	/**
	 * Constant for the Square customer ID meta key
	 */
	const CUSTOMER_META_ID = 'tva_square_customer_id';

	/**
	 * Constant for the Square version meta key
	 */
	const SQUARE_VERSION_META = 'tva_square_version';

	/**
	 * Constant for the Square customer panel link enabled or not
	 */
	const CUSTOMER_PORTAL = 'tva_square_customer_portal';

	/**
	 * Initialize the hooks.
	 *
	 * This method is responsible for initializing the hooks.
	 * It adds the necessary actions and filters.
	 */
	public static function init() {
		static::add_actions();
		static::add_filters();
	}

	/**
	 * Add actions.
	 *
	 * This method is responsible for adding the necessary actions.
	 * It uses the add_action function to add the actions.
	 */
	public static function add_actions() {
		add_action( 'admin_init', [ __CLASS__, 'refresh_token' ] );
		add_action( 'tva_remove_user_from_product', [ __CLASS__, 'remove_user_from_product' ], 10, 3 );
	}

	/**
	 * Add filters.
	 *
	 * This method is responsible for adding the necessary filters.
	 * It uses the add_filter function to add the filters.
	 */
	public static function add_filters() {
		add_filter( 'tva_admin_localize', [ __CLASS__, 'admin_localize' ] );
	}

	public static function get_customer_portal() {
		return get_option( static::CUSTOMER_PORTAL, 0 );
	}

	public static function is_customer_portal_enable() {
		return static::get_customer_portal();
	}

	public static function ensure_customer_portal() {
		update_option( static::CUSTOMER_PORTAL, 1 );
	}

	/**
	 * Get the number of protected products that are using the Square integration
	 *
	 * @param $live
	 *
	 * @return false|mixed|null
	 */
	public static function get_protected_products_count( $live = true ) {
		$products = Product::get_items();
		
		$live_count = 0;
		$test_count = 0;

		$products = is_array( $products ) || is_object( $products ) ? $products : [];

		foreach ( $products as $product ) {
			$price_set = get_term_meta( $product->ID, 'tva_square_product_pricing', true );
			$price_set = is_array( $price_set ) || is_object( $price_set ) ? $price_set : [];
			foreach ( $price_set as $price ) {
				if ( $price['mode'] == 'live' ) {
					$live_count++;
					break;
				}
			}

			foreach ( $price_set as $price ) {
				if ( $price['mode'] == 'test' ) {
					$test_count++;
					break;
				}
			}
		}

		return $live ? $live_count : $test_count;
	}

	/**
	 * Square details for the admin localize script
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	public static function admin_localize( $data = [] ) {
		$status_response = self::get_status_response();
		$settings_response = self::get_settings_response();

		$data['square'] = [
			'status'                             => $status_response['success'] ?? false,
			'status_data'                        => $status_response,
			'account_id'                         => '',
			'tva_square_auto_display_buy_button' => $settings_response['auto_display_buy_button'] ? '1' : '0',
			'connected_products_count'           => self::get_protected_products_count(),
			'connected_products_count_test'      => self::get_protected_products_count( false ),
		];

		return $data;
	}

	public static function currencies() {
		$currencies = [
			[
				'label' => 'Afghan Afghani',
				'code'  => 'AFN',
			],
			[
				'label' => 'Albanian Lek',
				'code'  => 'ALL',
			],
			[
				'label' => 'Algerian Dinar',
				'code'  => 'DZD',
			],
			[
				'label' => 'Angolan Kwanza',
				'code'  => 'AOA',
			],
			[
				'label' => 'Argentine Peso',
				'code'  => 'ARS',
			],
			[
				'label' => 'Armenian Dram',
				'code'  => 'AMD',
			],
			[
				'label' => 'Aruban Florin',
				'code'  => 'AWG',
			],
			[
				'label' => 'Australian Dollar',
				'code'  => 'AUD',
			],
			[
				'label' => 'Azerbaijani Manat',
				'code'  => 'AZN',
			],
			[
				'label' => 'Bahamian Dollar',
				'code'  => 'BSD',
			],
			[
				'label' => 'Bangladeshi Taka',
				'code'  => 'BDT',
			],
			[
				'label' => 'Barbadian Dollar',
				'code'  => 'BBD',
			],
			[
				'label' => 'Belize Dollar',
				'code'  => 'BZD',
			],
			[
				'label' => 'Bermudian Dollar',
				'code'  => 'BMD',
			],
			[
				'label' => 'Bolivian Boliviano',
				'code'  => 'BOB',
			],
			[
				'label' => 'Bosnia & Herzegovina Convertible Mark',
				'code'  => 'BAM',
			],
			[
				'label' => 'Botswana Pula',
				'code'  => 'BWP',
			],
			[
				'label' => 'Brazilian Real',
				'code'  => 'BRL',
			],
			[
				'label' => 'British Pound',
				'code'  => 'GBP',
			],
			[
				'label' => 'Brunei Dollar',
				'code'  => 'BND',
			],
			[
				'label' => 'Bulgarian Lev',
				'code'  => 'BGN',
			],
			[
				'label' => 'Burundian Franc',
				'code'  => 'BIF',
			],
			[
				'label' => 'Cambodian Riel',
				'code'  => 'KHR',
			],
			[
				'label' => 'Canadian Dollar',
				'code'  => 'CAD',
			],
			[
				'label' => 'Cape Verdean Escudo',
				'code'  => 'CVE',
			],
			[
				'label' => 'Cayman Islands Dollar',
				'code'  => 'KYD',
			],
			[
				'label' => 'Central African Cfa Franc',
				'code'  => 'XAF',
			],
			[
				'label' => 'Cfp Franc',
				'code'  => 'XPF',
			],
			[
				'label' => 'Chilean Peso',
				'code'  => 'CLP',
			],
			[
				'label' => 'Chinese Renminbi Yuan',
				'code'  => 'CNY',
			],
			[
				'label' => 'Colombian Peso',
				'code'  => 'COP',
			],
			[
				'label' => 'Comorian Franc',
				'code'  => 'KMF',
			],
			[
				'label' => 'Congolese Franc',
				'code'  => 'CDF',
			],
			[
				'label' => 'Costa Rican Colón',
				'code'  => 'CRC',
			],
			[
				'label' => 'Croatian Kuna',
				'code'  => 'HRK',
			],
			[
				'label' => 'Czech Koruna',
				'code'  => 'CZK',
			],
			[
				'label' => 'Danish Krone',
				'code'  => 'DKK',
			],
			[
				'label' => 'Djiboutian Franc',
				'code'  => 'DJF',
			],
			[
				'label' => 'Dominican Peso',
				'code'  => 'DOP',
			],
			[
				'label' => 'East Caribbean Dollar',
				'code'  => 'XCD',
			],
			[
				'label' => 'Egyptian Pound',
				'code'  => 'EGP',
			],
			[
				'label' => 'Ethiopian Birr',
				'code'  => 'ETB',
			],
			[
				'label' => 'Euro',
				'code'  => 'EUR',
			],
			[
				'label' => 'Falkland Islands Pound',
				'code'  => 'FKP',
			],
			[
				'label' => 'Fijian Dollar',
				'code'  => 'FJD',
			],
			[
				'label' => 'Gambian Dalasi',
				'code'  => 'GMD',
			],
			[
				'label' => 'Georgian Lari',
				'code'  => 'GEL',
			],
			[
				'label' => 'Gibraltar Pound',
				'code'  => 'GIP',
			],
			[
				'label' => 'Guatemalan Quetzal',
				'code'  => 'GTQ',
			],
			[
				'label' => 'Guinean Franc',
				'code'  => 'GNF',
			],
			[
				'label' => 'Guyanese Dollar',
				'code'  => 'GYD',
			],
			[
				'label' => 'Haitian Gourde',
				'code'  => 'HTG',
			],
			[
				'label' => 'Honduran Lempira',
				'code'  => 'HNL',
			],
			[
				'label' => 'Hong Kong Dollar',
				'code'  => 'HKD',
			],
			[
				'label' => 'Hungarian Forint',
				'code'  => 'HUF',
			],
			[
				'label' => 'Icelandic Króna',
				'code'  => 'ISK',
			],
			[
				'label' => 'Indian Rupee',
				'code'  => 'INR',
			],
			[
				'label' => 'Indonesian Rupiah',
				'code'  => 'IDR',
			],
			[
				'label' => 'Israeli New Sheqel',
				'code'  => 'ILS',
			],
			[
				'label' => 'Jamaican Dollar',
				'code'  => 'JMD',
			],
			[
				'label' => 'Japanese Yen',
				'code'  => 'JPY',
			],
			[
				'label' => 'Kazakhstani Tenge',
				'code'  => 'KZT',
			],
			[
				'label' => 'Kenyan Shilling',
				'code'  => 'KES',
			],
			[
				'label' => 'Kyrgyzstani Som',
				'code'  => 'KGS',
			],
			[
				'label' => 'Lao Kip',
				'code'  => 'LAK',
			],
			[
				'label' => 'Lebanese Pound',
				'code'  => 'LBP',
			],
			[
				'label' => 'Lesotho Loti',
				'code'  => 'LSL',
			],
			[
				'label' => 'Liberian Dollar',
				'code'  => 'LRD',
			],
			[
				'label' => 'Macanese Pataca',
				'code'  => 'MOP',
			],
			[
				'label' => 'Macedonian Denar',
				'code'  => 'MKD',
			],
			[
				'label' => 'Malagasy Ariary',
				'code'  => 'MGA',
			],
			[
				'label' => 'Malawian Kwacha',
				'code'  => 'MWK',
			],
			[
				'label' => 'Malaysian Ringgit',
				'code'  => 'MYR',
			],
			[
				'label' => 'Maldivian Rufiyaa',
				'code'  => 'MVR',
			],
			[
				'label' => 'Mauritanian Ouguiya',
				'code'  => 'MRO',
			],
			[
				'label' => 'Mauritian Rupee',
				'code'  => 'MUR',
			],
			[
				'label' => 'Mexican Peso',
				'code'  => 'MXN',
			],
			[
				'label' => 'Moldovan Leu',
				'code'  => 'MDL',
			],
			[
				'label' => 'Mongolian Tögrög',
				'code'  => 'MNT',
			],
			[
				'label' => 'Moroccan Dirham',
				'code'  => 'MAD',
			],
			[
				'label' => 'Mozambican Metical',
				'code'  => 'MZN',
			],
			[
				'label' => 'Myanmar Kyat',
				'code'  => 'MMK',
			],
			[
				'label' => 'Namibian Dollar',
				'code'  => 'NAD',
			],
			[
				'label' => 'Nepalese Rupee',
				'code'  => 'NPR',
			],
			[
				'label' => 'Netherlands Antillean Gulden',
				'code'  => 'ANG',
			],
			[
				'label' => 'New Taiwan Dollar',
				'code'  => 'TWD',
			],
			[
				'label' => 'New Zealand Dollar',
				'code'  => 'NZD',
			],
			[
				'label' => 'Nicaraguan Córdoba',
				'code'  => 'NIO',
			],
			[
				'label' => 'Nigerian Naira',
				'code'  => 'NGN',
			],
			[
				'label' => 'Norwegian Krone',
				'code'  => 'NOK',
			],
			[
				'label' => 'Pakistani Rupee',
				'code'  => 'PKR',
			],
			[
				'label' => 'Panamanian Balboa',
				'code'  => 'PAB',
			],
			[
				'label' => 'Papua New Guinean Kina',
				'code'  => 'PGK',
			],
			[
				'label' => 'Paraguayan Guaraní',
				'code'  => 'PYG',
			],
			[
				'label' => 'Peruvian Nuevo Sol',
				'code'  => 'PEN',
			],
			[
				'label' => 'Philippine Peso',
				'code'  => 'PHP',
			],
			[
				'label' => 'Polish Złoty',
				'code'  => 'PLN',
			],
			[
				'label' => 'Qatari Riyal',
				'code'  => 'QAR',
			],
			[
				'label' => 'Romanian Leu',
				'code'  => 'RON',
			],
			[
				'label' => 'Russian Ruble',
				'code'  => 'RUB',
			],
			[
				'label' => 'Rwandan Franc',
				'code'  => 'RWF',
			],
			[
				'label' => 'São Tomé and Príncipe Dobra',
				'code'  => 'STD',
			],
			[
				'label' => 'Saint Helenian Pound',
				'code'  => 'SHP',
			],
			[
				'label' => 'Salvadoran Colón',
				'code'  => 'SVC',
			],
			[
				'label' => 'Samoan Tala',
				'code'  => 'WST',
			],
			[
				'label' => 'Saudi Riyal',
				'code'  => 'SAR',
			],
			[
				'label' => 'Serbian Dinar',
				'code'  => 'RSD',
			],
			[
				'label' => 'Seychellois Rupee',
				'code'  => 'SCR',
			],
			[
				'label' => 'Sierra Leonean Leone',
				'code'  => 'SLL',
			],
			[
				'label' => 'Singapore Dollar',
				'code'  => 'SGD',
			],
			[
				'label' => 'Solomon Islands Dollar',
				'code'  => 'SBD',
			],
			[
				'label' => 'Somali Shilling',
				'code'  => 'SOS',
			],
			[
				'label' => 'South African Rand',
				'code'  => 'ZAR',
			],
			[
				'label' => 'South Korean Won',
				'code'  => 'KRW',
			],
			[
				'label' => 'Sri Lankan Rupee',
				'code'  => 'LKR',
			],
			[
				'label' => 'Surinamese Dollar',
				'code'  => 'SRD',
			],
			[
				'label' => 'Swazi Lilangeni',
				'code'  => 'SZL',
			],
			[
				'label' => 'Swedish Krona',
				'code'  => 'SEK',
			],
			[
				'label' => 'Swiss Franc',
				'code'  => 'CHF',
			],
			[
				'label' => 'Tajikistani Somoni',
				'code'  => 'TJS',
			],
			[
				'label' => 'Tanzanian Shilling',
				'code'  => 'TZS',
			],
			[
				'label' => 'Thai Baht',
				'code'  => 'THB',
			],
			[
				'label' => 'Tongan Paʻanga',
				'code'  => 'TOP',
			],
			[
				'label' => 'Trinidad and Tobago Dollar',
				'code'  => 'TTD',
			],
			[
				'label' => 'Turkish Lira',
				'code'  => 'TRY',
			],
			[
				'label' => 'Ugandan Shilling',
				'code'  => 'UGX',
			],
			[
				'label' => 'Ukrainian Hryvnia',
				'code'  => 'UAH',
			],
			[
				'label' => 'United Arab Emirates Dirham',
				'code'  => 'AED',
			],
			[
				'label' => 'United States Dollar',
				'code'  => 'USD',
			],
			[
				'label' => 'Uruguayan Peso',
				'code'  => 'UYU',
			],
			[
				'label' => 'Uzbekistani Som',
				'code'  => 'UZS',
			],
			[
				'label' => 'Vanuatu Vatu',
				'code'  => 'VUV',
			],
			[
				'label' => 'Vietnamese Đồng',
				'code'  => 'VND',
			],
			[
				'label' => 'West African Cfa Franc',
				'code'  => 'XOF',
			],
			[
				'label' => 'Yemeni Rial',
				'code'  => 'YER',
			],
			[
				'label' => 'Zambian Kwacha',
				'code'  => 'ZMW',
			],
		];

		return apply_filters( 'tva_square_currencies', $currencies );
	}

	/**
	 * Checks the Square token expiration and refreshes it if needed.
	 *
	 * This method will check the Square token expiration for both live and test modes.
	 * If the token will expire in less than 7 days, it will automatically refresh the
	 * token using the refresh_token_request method.
	 *
	 * This method is intended to be called via the WordPress cron job.
	 */
	public static function refresh_token() {
		$live_token_expiration = get_transient( 'square_live_token_expiration' );
		if ( false === $live_token_expiration ) {
			// Check the token expiration and store it in the transient
			$status_response = self::get_status_response();
			$live_token_expiration = ( $status_response['live_token_expiring_soon'] ?? false ) ? '1' : '0';
			set_transient( 'square_live_token_expiration', $live_token_expiration, 60 * 60 * 24 ); // Store for 24 hours
		}

		if ( $live_token_expiration === '1' ) {
			// Token will expire in less than 7 days
			self::refresh_token_request( 'live' );
		}

		$test_token_expiration = get_transient( 'square_test_token_expiration' );
		if ( false === $test_token_expiration ) {
			// Check the token expiration and store it in the transient
			$status_response = self::get_status_response();
			$test_token_expiration = ( $status_response['test_token_expiring_soon'] ?? false ) ? '1' : '0';
			set_transient( 'square_test_token_expiration', $test_token_expiration, 60 * 60 * 24 ); // Store for 24 hours
		}

		if ( $test_token_expiration === '1' ) {
			// Token will expire in less than 7 days
			self::refresh_token_request( 'test' );
		}
	}

	public static function remove_user_from_product() {
		$data = func_get_args();
		$user_id = $data[0];
		$product_id = $data[1];
		$item_price = $data[2];
		$customer = new TVA_Customer( $user_id );
		$product = new Product( $product_id );
		$user = get_user_by( 'ID', $user_id );
		$customer->remove_user_from_product( $user, $product );

		// Add cancel event
		$square_controller = new TVA_Square_Controller();
		$square_controller->add_cancel_event( $product, $user_id, $product_id, $item_price, false );
	}

	/**
	 * Retrieves the status response from the Square controller.
	 *
	 * This method initializes a Square controller instance and fetches the status response
	 * using the controller's get_status method. If the status response is not available,
	 * it defaults to an empty array.
	 *
	 * @return array The status response data.
	 */
	public static function get_status_response() {
		$square_controller = new TVA_Square_Controller();
		$status_response = null;
		if ( $square_controller && $square_controller->get_status() ) {
			$status_response = $square_controller->get_status()->get_data();
		}

		if ( empty( $status_response ) ) {
			$status_response = [];
		}

		return $status_response;
	}

	/**
	 * Retrieves the settings response from the Square controller.
	 *
	 * This method initializes a Square controller instance and fetches the settings response
	 * using the controller's get_settings method. If the settings response is not available,
	 * it defaults to an empty array.
	 *
	 * @return array The settings response data.
	 */
	public static function get_settings_response() {
		$square_controller = new TVA_Square_Controller();
		$settings_response = null;
		if ( $square_controller->get_settings() ) {
			$settings_response = $square_controller->get_settings()->get_data();
		}
		
		if ( empty( $settings_response ) ) {
			$settings_response = [];
		}

		return $settings_response;
	}

	/**
	 * Refreshes the Square token.
	 *
	 * This method sends a POST request to the Square controller's refresh_token endpoint
	 * to refresh the token. It includes the necessary body parameter for authorization.
	 *
	 * @param string $mode The mode of the Square client.
	 *
	 * @return array The response object containing the status of the token.
	 */
	public static function refresh_token_request( $mode ) {
		// send post request to refresh token
		$response = wp_remote_post( get_rest_url() . TVA_Square_Controller::$namespace . TVA_Square_Controller::$version . '/square/refresh_token', [
			 	'body' => [
				 	'mode' => $mode,
				],
			]);

		if ( is_wp_error( $response ) ) {
			error_log( 'Error refreshing token: ' . $response->get_error_message() );
		}

		return $response;
	}

	public static function get_locations( $mode ) {
		$square_controller = new TVA_Square_Controller();
		$locations = [];
		if ( $square_controller->get_locations( $mode ) ) {
			$locations = $square_controller->get_locations( $mode )->get_data();
		}
		return $locations;
	}

	public static function get_product_data ( int $product_id ) {
		$square_mode = get_term_meta( $product_id, 'tva_square_mode', true ) ?? '';
		$selected_mode = $square_mode && $square_mode !== '' ? $square_mode : 'live';
		$price_set = get_term_meta( $product_id, 'tva_square_product_pricing', true ) ?? [];
		$free_trial = get_term_meta( $product_id, 'tva_square_product_free_trial', true ) ?? false;
		$trial_days = get_term_meta( $product_id, 'tva_square_product_trial_days', true ) ?? 0;
		$prepopulate_email = get_term_meta( $product_id, 'tva_square_product_prepopulate_email', true ) ?? false;
		$success_url = get_term_meta( $product_id, 'tva_square_product_success_url', true ) ?? '';
		$cancel_url = get_term_meta( $product_id, 'tva_square_product_cancel_url', true ) ?? '';
		$selected_price_id = get_term_meta( $product_id, 'tva_square_product_selected_price', true ) ?? '';
		$settings = get_term_meta( $product_id, 'tva_access_restriction', true ) ?? '';

		$livesExists = false;
		$testExists = false;
		$price_set = is_array( $price_set ) || is_object( $price_set ) ? $price_set : [];
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

		if ( $square_mode === 'live' ) {
			$price_text =  $livesExists ? 'Product Price Set' : 'Price Not Set';
			$price_class = $livesExists ? '' : 'tva-square-not-selected';
			$pricing_set = $livesExists ? true : false;
			$merchant_id = Credentials::get_account_id_live();
		} else {
			$price_text = $testExists ? 'Product Price Set' : 'Price Not Set';
			$price_class = $testExists ? '' : 'tva-square-not-selected';
			$pricing_set = $testExists ? true : false;
			$merchant_id = Credentials::get_account_id();
		}

		$data = [
			'merchant_id' => $merchant_id,
			'selected_mode' => $selected_mode,
			'price_text' => $price_text,
			'price_class' => $price_class,
			'free_trial' => $free_trial,
			'trial_days' => $trial_days,
			'prepopulate_email' => $prepopulate_email,
			'success_url' => $success_url,
			'cancel_url' => $cancel_url,
			'pricing' => $price_set,
			'pricing_set' => $pricing_set,
			'selected_price_id' => $selected_price_id,
			'settings' => $settings,
		];

		return $data;
	}

}

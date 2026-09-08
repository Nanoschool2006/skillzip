<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Stripe;

use Exception;
use TVA\Product;
use TVA_Const;
use TVA_Order;
use function add_action;
use function add_filter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Hooks
 *
 * This class provides methods for handling Stripe hooks.
 */
class Hooks {

	/**
	 * Constant for the Stripe customer ID meta key
	 */
	const CUSTOMER_META_ID = 'tva_stripe_customer_id';

	/**
	 * Constant for the Stripe version meta key
	 */
	const STRIPE_VERSION_META = 'tva_stripe_version';

	/**
	 * Constant for the Stripe live count meta key of the number of protected products
	 */
	const STRIPE_LIVE_COUNT = 'tva_stripe_live_count';

	/**
	 * Constant for the Stripe test count meta key of the number of protected products
	 */
	const STRIPE_TEST_COUNT = 'tva_stripe_test_count';

    /**
     * Constant for the Stripe customer panel link enabled or not
     */
    const CUSTOMER_PORTAL = 'tva_stripe_customer_portal';

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
		add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ], 1 );

		add_action( 'tva_product_updated', [ __CLASS__, 'update_stipe_product' ], 10 );
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

	/**
	 * Get the Stripe version.
	 * v1 - for the old enrollment method
	 * v2 - current valid version
	 *
	 *
	 * @return false|mixed|null
	 */
	public static function get_stripe_version() {
		return get_option( static::STRIPE_VERSION_META, '' );
	}

    public static function get_customer_portal() {
        return get_option( static::CUSTOMER_PORTAL, 0 );
    }

	public static function v2_update() {
		static::ensure_customer_ids();
		static::check_v1();
		static::count_protected_products();
	}

	/**
	 * Update the Stripe version to v1 if the API key or test key exist
	 *
	 * @return void
	 */
	public static function check_v1() {
		$v1_connection = Connection::get_instance();
		if ( $v1_connection->get_api_key() || $v1_connection->get_test_key() ) {
			update_option( static::STRIPE_VERSION_META, 'v1' );
		}
	}

	/**
	 * Check if the Stripe version is v1 (legacy)
	 *
	 * @return bool
	 */
	public static function is_legacy() {
		return static::get_stripe_version() === 'v1';
	}

    /**
     * Check if the Stripe is connected or not
     *
     * @return bool
     */
    public static function is_connected() {
        $connected_id = Credentials::get_account_id();

        return ! empty( $connected_id );
    }

    public static function is_customer_portal_enable() {
        if ( static::is_legacy() ) {
            return false;
        }

        return static::get_customer_portal();
    }

    public static function ensure_customer_portal() {
        update_option( static::CUSTOMER_PORTAL, 1 );
    }

	/**
	 * Fetch the customer IDs for all orders and update the user meta
	 * Customer IDs are used to identify the customer in the Stripe dashboard
	 *
	 */
	public static function ensure_customer_ids() {
		$orders = TVA_Order::get_orders_by_gateway( TVA_Const::STRIPE_GATEWAY );
		foreach ( $orders as $order ) {
			$user_id = $order['user_id'];
			if ( empty( get_user_meta( $user_id, static::CUSTOMER_META_ID, true ) ) ) {
				$user = get_user_by( 'id', $user_id );
				if ( $user ) {
					try {
						$customer_id = Request::get_customer_id( $user->user_email );
						if ( $customer_id ) {
							update_user_meta( $user_id, static::CUSTOMER_META_ID, $customer_id );
						}
					} catch ( Exception $e ) {
						//do nothing
					}
				}
			}
		}
	}

	/**
	 * Get the number of protected products that are using the Stripe integration
	 *
	 * @param $live
	 *
	 * @return false|mixed|null
	 */
	public static function get_protected_products_count( $live = true ) {
		return $live ? get_option( static::STRIPE_LIVE_COUNT, 0 ) : get_option( static::STRIPE_TEST_COUNT, 0 );
	}

	/**
	 * Count the number of protected products that are using the Stripe integration
	 *
	 * @return void
	 */
	public static function count_protected_products() {
		/**
		 * @var Product[] $products
		 */
		$products   = Product::get_protected_products_by_integration( 'stripe' );
		$live_count = 0;
		$test_count = 0;
		foreach ( $products as $product ) {
			$rules = $product->get_rules();
			foreach ( $rules as &$rule ) {
				if ( $rule['integration'] === 'stripe' && isset( $rule['items'][0]['test_mode'] ) ) {
					if ( $rule['items'][0]['test_mode'] ) {
						$test_count ++;
					} else {
						$live_count ++;
					}
				}
			}
		}
		update_option( static::STRIPE_LIVE_COUNT, $live_count );
		update_option( static::STRIPE_TEST_COUNT, $test_count );
	}

	/**
	 * Stripe details for the admin localize script
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	public static function admin_localize( $data = [] ) {
		$connection = Connection_V2::get_instance();

		$data['stripe'] = [
			'api_key'                                 => $connection->get_api_key(),
			'test_api_key'                            => $connection->get_test_key(),
			'account_id'                              => Credentials::get_account_id(),
			'is_legacy'                               => static::is_legacy(),
			'has_customer_portal'                     => static::is_customer_portal_enable(),
			Settings::ALLOW_CHECKOUT_COUPONS_OPTIONS  => Settings::get_setting( Settings::ALLOW_CHECKOUT_COUPONS_OPTIONS, false ),
			Settings::AUTO_DISPLAY_BUY_BUTTON_OPTIONS => Settings::get_setting( Settings::AUTO_DISPLAY_BUY_BUTTON_OPTIONS, false ),
		];

		return $data;
	}

	public static function admin_notices() {
		if ( static::is_legacy() ) {
			include TVA_Const::plugin_path( 'admin/includes/templates/stripe/v1-admin-notice.php' );
		}
	}

	public static function currencies() {
		$currencies = [
			[ 'label' => 'Afghan Afghani', 'code' => 'AFN' ],
			[ 'label' => 'Albanian Lek', 'code' => 'ALL' ],
			[ 'label' => 'Algerian Dinar', 'code' => 'DZD' ],
			[ 'label' => 'Angolan Kwanza', 'code' => 'AOA' ],
			[ 'label' => 'Argentine Peso', 'code' => 'ARS' ],
			[ 'label' => 'Armenian Dram', 'code' => 'AMD' ],
			[ 'label' => 'Aruban Florin', 'code' => 'AWG' ],
			[ 'label' => 'Australian Dollar', 'code' => 'AUD' ],
			[ 'label' => 'Azerbaijani Manat', 'code' => 'AZN' ],
			[ 'label' => 'Bahamian Dollar', 'code' => 'BSD' ],
			[ 'label' => 'Bangladeshi Taka', 'code' => 'BDT' ],
			[ 'label' => 'Barbadian Dollar', 'code' => 'BBD' ],
			[ 'label' => 'Belize Dollar', 'code' => 'BZD' ],
			[ 'label' => 'Bermudian Dollar', 'code' => 'BMD' ],
			[ 'label' => 'Bolivian Boliviano', 'code' => 'BOB' ],
			[ 'label' => 'Bosnia & Herzegovina Convertible Mark', 'code' => 'BAM' ],
			[ 'label' => 'Botswana Pula', 'code' => 'BWP' ],
			[ 'label' => 'Brazilian Real', 'code' => 'BRL' ],
			[ 'label' => 'British Pound', 'code' => 'GBP' ],
			[ 'label' => 'Brunei Dollar', 'code' => 'BND' ],
			[ 'label' => 'Bulgarian Lev', 'code' => 'BGN' ],
			[ 'label' => 'Burundian Franc', 'code' => 'BIF' ],
			[ 'label' => 'Cambodian Riel', 'code' => 'KHR' ],
			[ 'label' => 'Canadian Dollar', 'code' => 'CAD' ],
			[ 'label' => 'Cape Verdean Escudo', 'code' => 'CVE' ],
			[ 'label' => 'Cayman Islands Dollar', 'code' => 'KYD' ],
			[ 'label' => 'Central African Cfa Franc', 'code' => 'XAF' ],
			[ 'label' => 'Cfp Franc', 'code' => 'XPF' ],
			[ 'label' => 'Chilean Peso', 'code' => 'CLP' ],
			[ 'label' => 'Chinese Renminbi Yuan', 'code' => 'CNY' ],
			[ 'label' => 'Colombian Peso', 'code' => 'COP' ],
			[ 'label' => 'Comorian Franc', 'code' => 'KMF' ],
			[ 'label' => 'Congolese Franc', 'code' => 'CDF' ],
			[ 'label' => 'Costa Rican Colón', 'code' => 'CRC' ],
			[ 'label' => 'Croatian Kuna', 'code' => 'HRK' ],
			[ 'label' => 'Czech Koruna', 'code' => 'CZK' ],
			[ 'label' => 'Danish Krone', 'code' => 'DKK' ],
			[ 'label' => 'Djiboutian Franc', 'code' => 'DJF' ],
			[ 'label' => 'Dominican Peso', 'code' => 'DOP' ],
			[ 'label' => 'East Caribbean Dollar', 'code' => 'XCD' ],
			[ 'label' => 'Egyptian Pound', 'code' => 'EGP' ],
			[ 'label' => 'Ethiopian Birr', 'code' => 'ETB' ],
			[ 'label' => 'Euro', 'code' => 'EUR' ],
			[ 'label' => 'Falkland Islands Pound', 'code' => 'FKP' ],
			[ 'label' => 'Fijian Dollar', 'code' => 'FJD' ],
			[ 'label' => 'Gambian Dalasi', 'code' => 'GMD' ],
			[ 'label' => 'Georgian Lari', 'code' => 'GEL' ],
			[ 'label' => 'Gibraltar Pound', 'code' => 'GIP' ],
			[ 'label' => 'Guatemalan Quetzal', 'code' => 'GTQ' ],
			[ 'label' => 'Guinean Franc', 'code' => 'GNF' ],
			[ 'label' => 'Guyanese Dollar', 'code' => 'GYD' ],
			[ 'label' => 'Haitian Gourde', 'code' => 'HTG' ],
			[ 'label' => 'Honduran Lempira', 'code' => 'HNL' ],
			[ 'label' => 'Hong Kong Dollar', 'code' => 'HKD' ],
			[ 'label' => 'Hungarian Forint', 'code' => 'HUF' ],
			[ 'label' => 'Icelandic Króna', 'code' => 'ISK' ],
			[ 'label' => 'Indian Rupee', 'code' => 'INR' ],
			[ 'label' => 'Indonesian Rupiah', 'code' => 'IDR' ],
			[ 'label' => 'Israeli New Sheqel', 'code' => 'ILS' ],
			[ 'label' => 'Jamaican Dollar', 'code' => 'JMD' ],
			[ 'label' => 'Japanese Yen', 'code' => 'JPY' ],
			[ 'label' => 'Kazakhstani Tenge', 'code' => 'KZT' ],
			[ 'label' => 'Kenyan Shilling', 'code' => 'KES' ],
			[ 'label' => 'Kyrgyzstani Som', 'code' => 'KGS' ],
			[ 'label' => 'Lao Kip', 'code' => 'LAK' ],
			[ 'label' => 'Lebanese Pound', 'code' => 'LBP' ],
			[ 'label' => 'Lesotho Loti', 'code' => 'LSL' ],
			[ 'label' => 'Liberian Dollar', 'code' => 'LRD' ],
			[ 'label' => 'Macanese Pataca', 'code' => 'MOP' ],
			[ 'label' => 'Macedonian Denar', 'code' => 'MKD' ],
			[ 'label' => 'Malagasy Ariary', 'code' => 'MGA' ],
			[ 'label' => 'Malawian Kwacha', 'code' => 'MWK' ],
			[ 'label' => 'Malaysian Ringgit', 'code' => 'MYR' ],
			[ 'label' => 'Maldivian Rufiyaa', 'code' => 'MVR' ],
			[ 'label' => 'Mauritanian Ouguiya', 'code' => 'MRO' ],
			[ 'label' => 'Mauritian Rupee', 'code' => 'MUR' ],
			[ 'label' => 'Mexican Peso', 'code' => 'MXN' ],
			[ 'label' => 'Moldovan Leu', 'code' => 'MDL' ],
			[ 'label' => 'Mongolian Tögrög', 'code' => 'MNT' ],
			[ 'label' => 'Moroccan Dirham', 'code' => 'MAD' ],
			[ 'label' => 'Mozambican Metical', 'code' => 'MZN' ],
			[ 'label' => 'Myanmar Kyat', 'code' => 'MMK' ],
			[ 'label' => 'Namibian Dollar', 'code' => 'NAD' ],
			[ 'label' => 'Nepalese Rupee', 'code' => 'NPR' ],
			[ 'label' => 'Netherlands Antillean Gulden', 'code' => 'ANG' ],
			[ 'label' => 'New Taiwan Dollar', 'code' => 'TWD' ],
			[ 'label' => 'New Zealand Dollar', 'code' => 'NZD' ],
			[ 'label' => 'Nicaraguan Córdoba', 'code' => 'NIO' ],
			[ 'label' => 'Nigerian Naira', 'code' => 'NGN' ],
			[ 'label' => 'Norwegian Krone', 'code' => 'NOK' ],
			[ 'label' => 'Pakistani Rupee', 'code' => 'PKR' ],
			[ 'label' => 'Panamanian Balboa', 'code' => 'PAB' ],
			[ 'label' => 'Papua New Guinean Kina', 'code' => 'PGK' ],
			[ 'label' => 'Paraguayan Guaraní', 'code' => 'PYG' ],
			[ 'label' => 'Peruvian Nuevo Sol', 'code' => 'PEN' ],
			[ 'label' => 'Philippine Peso', 'code' => 'PHP' ],
			[ 'label' => 'Polish Złoty', 'code' => 'PLN' ],
			[ 'label' => 'Qatari Riyal', 'code' => 'QAR' ],
			[ 'label' => 'Romanian Leu', 'code' => 'RON' ],
			[ 'label' => 'Russian Ruble', 'code' => 'RUB' ],
			[ 'label' => 'Rwandan Franc', 'code' => 'RWF' ],
			[ 'label' => 'São Tomé and Príncipe Dobra', 'code' => 'STD' ],
			[ 'label' => 'Saint Helenian Pound', 'code' => 'SHP' ],
			[ 'label' => 'Salvadoran Colón', 'code' => 'SVC' ],
			[ 'label' => 'Samoan Tala', 'code' => 'WST' ],
			[ 'label' => 'Saudi Riyal', 'code' => 'SAR' ],
			[ 'label' => 'Serbian Dinar', 'code' => 'RSD' ],
			[ 'label' => 'Seychellois Rupee', 'code' => 'SCR' ],
			[ 'label' => 'Sierra Leonean Leone', 'code' => 'SLL' ],
			[ 'label' => 'Singapore Dollar', 'code' => 'SGD' ],
			[ 'label' => 'Solomon Islands Dollar', 'code' => 'SBD' ],
			[ 'label' => 'Somali Shilling', 'code' => 'SOS' ],
			[ 'label' => 'South African Rand', 'code' => 'ZAR' ],
			[ 'label' => 'South Korean Won', 'code' => 'KRW' ],
			[ 'label' => 'Sri Lankan Rupee', 'code' => 'LKR' ],
			[ 'label' => 'Surinamese Dollar', 'code' => 'SRD' ],
			[ 'label' => 'Swazi Lilangeni', 'code' => 'SZL' ],
			[ 'label' => 'Swedish Krona', 'code' => 'SEK' ],
			[ 'label' => 'Swiss Franc', 'code' => 'CHF' ],
			[ 'label' => 'Tajikistani Somoni', 'code' => 'TJS' ],
			[ 'label' => 'Tanzanian Shilling', 'code' => 'TZS' ],
			[ 'label' => 'Thai Baht', 'code' => 'THB' ],
			[ 'label' => 'Tongan Paʻanga', 'code' => 'TOP' ],
			[ 'label' => 'Trinidad and Tobago Dollar', 'code' => 'TTD' ],
			[ 'label' => 'Turkish Lira', 'code' => 'TRY' ],
			[ 'label' => 'Ugandan Shilling', 'code' => 'UGX' ],
			[ 'label' => 'Ukrainian Hryvnia', 'code' => 'UAH' ],
			[ 'label' => 'United Arab Emirates Dirham', 'code' => 'AED' ],
			[ 'label' => 'United States Dollar', 'code' => 'USD' ],
			[ 'label' => 'Uruguayan Peso', 'code' => 'UYU' ],
			[ 'label' => 'Uzbekistani Som', 'code' => 'UZS' ],
			[ 'label' => 'Vanuatu Vatu', 'code' => 'VUV' ],
			[ 'label' => 'Vietnamese Đồng', 'code' => 'VND' ],
			[ 'label' => 'West African Cfa Franc', 'code' => 'XOF' ],
			[ 'label' => 'Yemeni Rial', 'code' => 'YER' ],
			[ 'label' => 'Zambian Kwacha', 'code' => 'ZMW' ]
		];

		return apply_filters( 'tva_stripe_currencies', $currencies );
	}

	public static function update_stipe_product( $product ) {
		$rules = $product->get_rules_by_integration( 'stripe' );
        $metadata = Helper::get_stripe_meta_data( $product->get_id() );
        $product_data = [
            'name' => $product->get_name(),
            'metadata' => $metadata
        ];
        // Prepare price data.
        $price_data = [
            'metadata' => $metadata
        ];

        foreach ( $rules as $rule ) {
            foreach ( $rule[ 'items' ]as $item ) {
                foreach ( [ 'test', 'live' ] as $mode ) {
                    $test_mode = $mode === 'test';
					$product_key = $mode . '_product_id';
                    $stripe_product_id = isset( $item[ $product_key ] ) ? $item[ $product_key ] : '';
                    if ( ! empty( $stripe_product_id ) ) {
                        $stripe_product = Request::get_product( $stripe_product_id, $test_mode );
                        if ( $stripe_product instanceof \WP_Error ) {
                            continue;
                        }
                        Request::update_product( $stripe_product_id, $product_data, $test_mode );

                        // Get prices for the Stripe product.
                        $prices = Request::get_product_prices( $stripe_product_id, $test_mode );

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
	}
}

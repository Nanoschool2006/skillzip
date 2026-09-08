<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

 namespace TVA\Stripe;

use TVA\Product;

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

 class Helper {

    /**
	 * Generate price data based on given parameters and Stripe product ID.
	 *
	 * @param array $params            The parameters for creating the price.
	 * @param int   $stripe_product_id The ID of the Stripe product.
	 *
	 * @return array The generated price data.
	 */
	public static function generate_price_data( $params, $stripe_product_id ) {
		// Extract parameters from the given array.
		$product_id = (int) $params['product_id'];
		$price_type = sanitize_text_field( $params['price_type'] );
		$billing_period = sanitize_text_field( $params['billing_period'] );
		$currency = sanitize_text_field( $params['currency'] );
		$interval_count = (int) $params['interval_count'];
		$interval = sanitize_text_field( $params['interval'] );
		$amount = $params['amount'];

		// Prepare metadata for the price data.
		$metadata = static::get_stripe_meta_data( $product_id );

		// Initialize price data with metadata and product ID.
		$price_data = [
			'metadata' => $metadata,
			'product' => $stripe_product_id
		];

		// Set unit amount and currency for the price data.
		$price_data['unit_amount'] = $amount * 100;
		$price_data['currency'] = $currency;

		// If the price type is recurring, add recurring interval to the price data.
		if ( $price_type === 'recurring' ) {
			$price_data['recurring'] = [
				'interval' => $billing_period
			];
			// If the billing period is custom, set custom interval count and interval.
			if ( $billing_period === 'custom' ) {
				$price_data['recurring']['interval'] = $interval;
				$price_data['recurring']['interval_count'] = $interval_count;
			}
		}

		// Return the generated price data.
		return $price_data;
	}


	/**
	 * Get the Stripe product based on the given product ID.
	 *
	 * @param int  $product_id The ID of the product.
	 * @param bool $test_mode  Whether to use test mode.
	 *
	 * @return object The Stripe product object.
	 */
	public static function get_stripe_product( $product_id, $test_mode = false ) {

		// Retrieve all products from the request in test mode.
		$products = Request::search_products( $product_id, $test_mode );

		// If products exist, return the first product as an object, otherwise create a new product.
		if ( count( $products ) ) {
			return (object) $products[0];
		} else {
			// Create a new product object with the provided product ID.
			$product = new Product( (int) $product_id );

			// Get metadata for the product data.
			$metadata = static::get_stripe_meta_data( $product_id );

			// Prepare product data with name and metadata.
			$product_data = [
				'name'     => $product->name,
				'metadata' => $metadata
			];
			
			return Request::create_product( $product_data, $test_mode );
		}
	}

	/**
	 * Get metadata for the Stripe data.
	 *
	 * @param int $product_id The ID of the product.
	 *
	 * @return array The metadata array.
	 */
	public static function get_stripe_meta_data( $product_id ) {
		// Get the current user.
		$current_user = wp_get_current_user();

		// Prepare metadata for the price data.
		return apply_filters( 'tva_stripe_meta_data', [
			'tapp_product_id'  => $product_id,
			'tapp_created_by' => esc_html( $current_user->user_login )
		] );
	}

	/**
	 * Checks if a product is manageable.
	 *
	 * @param int $product_id The ID of the product to check.
	 * 
	 * @return bool Whether the product is manageable or not.
	 */
	public static function is_product_manageable( $product_id ) {
		// If the product ID is empty, it's not manageable.
		if ( empty( $product_id ) ) {
			return false;
		}

		return (bool) get_term_meta( $product_id, 'tva_stripe_manageable', true );
	}


	/**
	 * Splits a full name into first name and last name.
	 *
	 * @param string $name The full name to split.
	 * @return array An array containing the first name and last name.
	 */
	public static function get_names( $name ) {
		$first_name = $name;
		$last_name = '';
		$name_parts = explode( ' ', $name );

		if ( count( $name_parts ) > 1 ) {
			$last_name = $name_parts[ count( $name_parts ) - 1 ];
			array_pop( $name_parts );
			$first_name = implode( ' ', $name_parts );   
		}

		return [ $first_name, $last_name ];
	}

	/**
	 * Search for specific key in a nested array.
	 *
	 * @param array $data The data to check.
	 * @param string $search The keyword to search for.
	 * @return bool Whether the key is present or not.
	 */
	public static function check_array_key_recursive( $data, $search ) {
		foreach ( (array) $data as $key => $item ) {
			if ( $key == $search ) {
				return true;
			} elseif ( 
				( is_array( $item ) || is_object( $item ) ) && 
				static::check_array_key_recursive( $item, $search ) 
			) {
				return true;
			}
		}

		return false;
	}
 }
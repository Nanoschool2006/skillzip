<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

namespace TCB\Integrations\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Rest_Api
 *
 * @package Thrive\Theme\Integrations\WooCommerce
 */
class Rest_Api {
	public static $namespace = 'tcb/v1';
	public static $route = '/woo';

	public static function register_routes() {
		register_rest_route( static::$namespace, static::$route . '/render_shop', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ __CLASS__, 'render_shop' ],
				/* Publicly accessible – renders only product listing HTML */
				'permission_callback' => '__return_true',
			),
		) );

		register_rest_route( static::$namespace, static::$route . '/render_product_categories', array(
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ __CLASS__, 'render_product_categories' ],
				/* Publicly accessible – renders only product categories HTML */
				'permission_callback' => '__return_true',
			),
		) );

		register_rest_route( static::$namespace, static::$route . '/variations', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ __CLASS__, 'get_product_variations' ],
				'permission_callback' => [ __CLASS__, 'route_permission' ],
				'args'                => [
					'product_id'   => [
						'type'              => 'int',
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return ! empty ( $param );
						},
					],
					'variation_id' => [
						'type'              => 'int',
						'required'          => false,
						'validate_callback' => static function ( $param ) {
							return ! empty ( $param );
						},
					],

				],
			],
		] );
	}

	/**
	 * Render the WooCommerce shop element
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
    public static function render_shop( $request ) {
        /* Sanitize and whitelist incoming args */
        $raw_args = $request->get_param( 'args' );
        $allowed  = array(
            'columns'               => 'absint',
            'limit'                 => 'absint',
            'per_page'              => 'absint',
            'orderby'               => 'sanitize_text_field',
            'order'                 => 'sanitize_text_field',
            'ids'                   => 'sanitize_text_field',
            'category'              => 'sanitize_text_field',
            'cat_operator'          => 'sanitize_text_field',
            'taxonomy'              => 'sanitize_text_field',
            'terms'                 => 'sanitize_text_field',
            'terms_operator'        => 'sanitize_text_field',
            'tag'                   => 'sanitize_text_field',
            'tag_operator'          => 'sanitize_text_field',
            'align-items'           => 'sanitize_text_field',
            'paginate'              => 'rest_sanitize_boolean',
            'page'                  => 'absint',
            'hide-result-count'     => 'absint',
            'hide-catalog-ordering' => 'absint',
            'hide-sale-flash'       => 'absint',
            'hide-title'            => 'absint',
            'hide-price'            => 'absint',
            'hide-rating'           => 'absint',
            'hide-cart'             => 'absint',
            'hide-pagination'       => 'absint',
        );

		$args = array();

		if ( ! is_array( $raw_args ) || empty( $raw_args ) ) {
			$raw_args = array();
		}

		foreach ( $raw_args as $key => $val ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$callback = $allowed[ $key ];

			if ( $callback === 'rest_sanitize_boolean' ) {
				$args[ $key ] = rest_sanitize_boolean( $val );

				continue;
			}

			if ( $callback === 'absint' ) {
				$args[ $key ] = absint( $val );

				continue;
			}

			$args[ $key ] = call_user_func( $callback, $val );
		}

		/* Ensure paginate is enabled when rendering via REST */
		if ( empty( $args['paginate'] ) ) {
			$args['paginate'] = true;
		}

		/* Support forced page when rendering over REST so pagination works in ajax contexts (e.g., TQB) */
		if ( isset( $args['page'] ) && (int) $args['page'] > 0 ) {
			/* WC_Shortcode_Products reads current page from $_GET['product-page'] when paginate=true */
			$_GET['product-page'] = (int) $args['page'];
			/* Some shortcodes also accept an explicit page attribute */
			$args['page']         = (int) $args['page'];
		}

		/* Normalize per-page for certain shortcode code paths */
		if ( isset( $args['limit'] ) && ! isset( $args['per_page'] ) ) {
			$args['per_page'] = (int) $args['limit'];
		}

		Main::init_frontend_woo_functionality();

		$content = Shortcodes\Shop\Main::render( $args );

		return new \WP_REST_Response( [ 'content' => $content ], 200 );
	}

	/**
	 * Render the WooCommerce product categories element
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public static function render_product_categories( $request ) {
		$args = $request->get_param( 'args' );

		$content = Shortcodes\Product_Categories\Main::render( $args );

		return new \WP_REST_Response( [ 'content' => $content ], 200 );
	}

	/**
	 * Check if a given request has access to this route
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_Error|bool
	 */
	public static function route_permission( $request ) {
		return \TCB_Product::has_external_access();
	}

	/**
	 * Get the variations of a product
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 *
	 * @return \WP_REST_Response
	 */
	public static function get_product_variations( $request ) {
		$variation_id = $request->get_param( 'variation_id' );
		$product_id   = $request->get_param( 'product_id' );
		$product      = wc_get_product( $product_id );

		/* Early exit if product invalid or not a variable type */
		$not_variable = ! $product || ! ( $product->is_type( 'variable' ) || $product->is_type( 'variable-subscription' ) );
		if ( $not_variable ) {
			return new \WP_REST_Response( [ 'variation' => [] ], 200 );
		}

		$available_variations = $product->get_available_variations();

		if ( ! $variation_id ) {
			return new \WP_REST_Response( [ 'variation' => $available_variations ], 200 );
		}

		$selected_variation = [];

		foreach ( $available_variations as $variation ) {
			if ( (int) $variation['variation_id'] === (int) $variation_id ) {
				$selected_variation = $variation;
				break;
			}
		}

		return new \WP_REST_Response( [ 'variation' => $selected_variation ], 200 );
	}
}

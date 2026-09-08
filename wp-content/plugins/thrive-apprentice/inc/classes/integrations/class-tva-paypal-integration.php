<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

use TVA\Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_PayPal_Integration
 *
 * Integration entry for the PayPal payment gateway.
 * Mirrors TVA_Square_Integration — access rule evaluation checks whether the
 * current user has an order whose payment_id matches a PayPal price_id stored
 * on the product rule.
 *
 * Access is resolved via direct order lookup in is_rule_applied() rather than
 * via TVA_Integration membership items, so init_items(), _get_item_from_membership(),
 * and get_customer_access_items() are intentional no-ops in this integration.
 */
class TVA_PayPal_Integration extends TVA_Integration {

	/**
	 * Per-request product cache keyed by identifier string.
	 *
	 * @var array
	 */
	public static $CACHE = [];

	/**
	 * PayPal integration is always available (no 3rd-party plugin dependency).
	 *
	 * @return bool
	 */
	public function allow() {
		return true;
	}

	/**
	 * Return all TA Products that have a PayPal rule with a price_id configured.
	 *
	 * @param string $identifier Cache key (typically 'paypal').
	 * @param bool   $as_objects When true, return Product instances; otherwise return IDs.
	 *
	 * @return array
	 */
	public static function get_all_products_for_identifier( $identifier, $as_objects = true ) {
		if ( ! isset( static::$CACHE[ $identifier ] ) ) {
			$args = [
				'taxonomy'   => Product::TAXONOMY_NAME,
				'hide_empty' => false,
				'meta_query' => [
					'relation'         => 'AND',
					'tva_order_clause' => [
						'key' => 'tva_order',
					],
				],
				'fields'  => 'ids',
				'orderby' => 'meta_value_num',
				'order'   => 'DESC',
			];

			$product_ids = get_terms( $args );
			$products    = [];

			if ( is_wp_error( $product_ids ) || ! is_array( $product_ids ) ) {
				static::$CACHE[ $identifier ] = [];
				return [];
			}

			foreach ( $product_ids as $product_id ) {
				$rules       = get_term_meta( $product_id, 'tva_rules', true );
				$integration = array_values( array_filter( is_array( $rules ) ? $rules : [], static function ( $rule ) {
					return isset( $rule['integration'] ) && $rule['integration'] === 'paypal';
				} ) );

				if ( count( $integration ) && isset( $integration[0]['price_id'] ) ) {
					$products[] = $product_id;
				}
			}

			if ( $as_objects ) {
				$products = array_map( static function ( $product_id ) {
					return new Product( $product_id );
				}, $products );
			}

			static::$CACHE[ $identifier ] = $products;
		}

		return static::$CACHE[ $identifier ];
	}

	/**
	 * Check whether the access rule is satisfied for the current user.
	 *
	 * @param array $rule The access rule array stored in tva_rules term meta.
	 *
	 * @return bool
	 */
	public function is_rule_applied( $rule ) {
		$tva_user = tva_access_manager()->get_tva_user();

		if ( ! $tva_user instanceof TVA_User ) {
			return false;
		}

		if ( empty( $rule['items'] ) || ! is_array( $rule['items'] ) ) {
			return false;
		}

		// Build the access-granting order set once, outside the rule-item loop. Orders are
		// already cached on $tva_user, but hoisting still avoids rebuilding the merged array
		// for every item in the rule.
		//
		// Only honour orders that came through PayPal — prevents a Stripe or Square order for
		// the same product from satisfying a PayPal access rule, and ensures PayPal-specific
		// revocations (STATUS_FAILED/REFUND) are not bypassed by a completed order on another
		// gateway.
		//
		// Loop all access-granting orders rather than using has_bought(): has_bought() returns
		// only the FIRST completed order regardless of gateway, so a user holding both a Stripe
		// and a PayPal order for the same product could be incorrectly denied if the Stripe
		// order happened to sort first.
		//
		// STATUS_GRACE_PERIOD is access-granting too: when a vault renewal capture is denied the
		// order enters grace period while the Product API runs its dunning retries. Access must
		// persist until dunning is exhausted and THRIVE.ACCESS.REVOKE moves the order to
		// STATUS_FAILED — it must not be cut off the instant a single renewal capture fails.
		$access_orders = array_merge(
			$tva_user->get_orders_by_status( TVA_Const::STATUS_COMPLETED ),
			$tva_user->get_orders_by_status( TVA_Const::STATUS_GRACE_PERIOD )
		);

		foreach ( $rule['items'] as $item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}

			foreach ( $access_orders as $order ) {
				if ( ! $order instanceof TVA_Order || $order->get_gateway() !== \TVA_Const::PAYPAL_GATEWAY ) {
					continue;
				}

				$order_item = $order->get_order_item_by_product_id_and_status( $item['id'], 1 );

				if ( ! $order_item instanceof TVA_Order_Item ) {
					continue;
				}

				$this->set_order( $order );
				$this->set_order_item( $order_item );

				return true;
			}
		}

		return false;
	}

	/**
	 * Not used — PayPal resolves access via direct order lookup in is_rule_applied(),
	 * not via TVA_Integration membership items.
	 */
	protected function init_items() {
	}

	/**
	 * Not used — PayPal resolves access via direct order lookup in is_rule_applied(),
	 * not via TVA_Integration membership items.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return null
	 */
	protected function _get_item_from_membership( $key, $value ) {
		return null;
	}

	/**
	 * Not used — PayPal resolves access via direct order lookup in is_rule_applied(),
	 * not via TVA_Integration membership items.
	 *
	 * @param TVA_Customer $customer
	 *
	 * @return array
	 */
	public function get_customer_access_items( $customer ) {
		return [];
	}
}

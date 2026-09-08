<?php

namespace TVA\Access\Providers;

use TVA\Access\Expiry\After_Purchase;
use TVA\Access\History_Table;
use TVA\Product;
use TVA_Const;
use TVA_Order;
use TVA_Order_Item;
use TVA_Sendowl_Manager;
use TVA_Stripe_Integration;
use TVA_Woocommerce_Order;
use function apply_filters;

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Order extends Base {
	/**
	 * @var string
	 */
	const KEY = 'order';

	/**
	 * Holds cache / request of order IDs
	 *
	 * @var array
	 */
	protected static $ORDER_SAVE_CACHE = [];

	/**
	 * Hooks into apprentice purchase events and adds entries into access log
	 *
	 * This class should NOT call parent::construct()
	 */
	public function __construct() {
		add_action( 'tva_after_order_saved', [ $this, 'log_order_save' ], 10, 1 );
		add_action( 'tva_after_sendowl_order_item_db', [ $this, 'log_order_item_change' ], 10, 3 );
		add_action( 'tva_order_item_deleted', [ $this, 'log_order_item_canceled' ], 10, 1 );
		add_action( 'woocommerce_order_refunded', [ $this, 'woocommerce_refund' ], 12, 1 );
		add_action( 'tva_sendowl_order_refunded', [ $this, 'sendowl_refund' ], 10, 1 );
		add_action( 'tva_course_published', [ $this, 'course_published' ] );
		add_action( 'tva_stripe_order_refunded', [ $this, 'log_order_item_canceled' ], 10, 1 );
	}

	/**
	 * When the course content is modified we need to adjust the access history according to the modifications
	 *
	 * @param Product $product
	 * @param array   $course_ids
	 * @param int     $status
	 *
	 * @return void
	 */
	public function product_course_content_modified( $product, $course_ids, $status ) {
		$user_ids   = $this->get_order_users( $product );
		$course_ids = tva_filter_published_courses_ids( $course_ids );

		if ( ! empty( $user_ids ) && is_array( $user_ids ) ) {
			$data = [];

			foreach ( $user_ids as $user_id ) {

				foreach ( $course_ids as $course_id ) {
					$data[] = [
						'user_id'    => (int) $user_id,
						'product_id' => (int) $product->get_id(),
						'course_id'  => (int) $course_id,
						'status'     => (int) $status,
						'source'     => static::KEY,
					];
				}
			}

			$this->commit_data( $data );
		}
	}

	/**
	 * When a draft course is published we need to adjust the access history
	 *
	 * @return void
	 */
	public function course_published( $course ) {
		$products = $course->get_product( true );
		$data     = [];

		if ( empty( $products ) ) {
			return;
		}

		foreach ( $products as $product ) {
			$user_ids = $this->get_order_users( $product );
			$this->course_data( $data, $course->get_id(), $product, $user_ids, static::KEY );
		}

		$this->commit_data( $data );
	}

	/**
	 * Filter out users with sendowl access
	 *
	 * @return array
	 */
	public function get_order_users( $product, $key = 'sendowl_product' ) {
		$user_ids = $product->get_customers();

		$access_levels = $product->get_ids_of_integration( $key );

		if ( ! empty( $access_levels ) ) {
			$user_ids = array_filter(
				$user_ids,
				static function ( $user ) use ( $access_levels ) {
					return ! in_array( $user['product_id'], $access_levels );
				}, ARRAY_FILTER_USE_BOTH
			);
		}

		return array_unique( array_column( $user_ids, 'ID' ) );
	}

	/**
	 * @param TVA_Order $order
	 *
	 * @return void
	 */
	public function log_order_save( $order ) {
		if ( $order->get_status() !== TVA_Const::STATUS_COMPLETED ) {
			return;
		}

		if ( ! empty( static::$ORDER_SAVE_CACHE[ $order->get_id() ] ) ) {
			/**
			 * This hook can be called multiple times per request.
			 * We need to be sure we log this info only once / request
			 */
			return;
		}

		static::$ORDER_SAVE_CACHE[ $order->get_id() ] = 1;

		foreach ( $order->get_order_items() as $order_item ) {
			if ( ! in_array( $order_item->get_status(), [ 0, 1 ] ) ) {
				continue;
			}

			if ( $order->is_sendowl() ) {
				$products = TVA_Sendowl_Manager::get_products_that_have_protection( (int) $order_item->get_product_id() );

				foreach ( $products as $product ) {
					$this->commit_product_data( $order, $order_item, $product, 'sendowl_product' );
				}
			} else if ( $order->is_stripe() ) {
				$products = TVA_Stripe_Integration::get_all_products_for_identifier( $order_item->get_product_id() );
				foreach ( $products as $product ) {
					$existing_orders = TVA_Order::get_orders_by_product( $order_item->get_product_id(), $order->get_user_id(), TVA_Const::STATUS_COMPLETED );
					if ( apply_filters( 'tva_stripe_commit_product_data', empty( $existing_orders ) || count( $existing_orders ) === 1 ) ) {
						$this->commit_product_data( $order, $order_item, $product );
					}
				}
			} else {
				$product = new Product( $order_item->get_product_id() );
				$this->commit_product_data( $order, $order_item, $product );
			}
		}
	}

	/**
	 * Commit course related changes
	 *
	 * @param $order
	 * @param $order_item
	 * @param $product
	 * @param $key
	 *
	 * @return void
	 */
	protected function commit_product_data( $order, $order_item, $product, $key = '' ) {
		$course_ids = $product->get_published_courses( true );
		$status     = $order_item->get_status() === 1 ? static::STATUS_ACCESS_ADDED : static::STATUS_ACCESS_REVOKED;

		/**
		 * PayPal-only: make a GRANT symmetric with the revoke path so a
		 * grant -> revoke -> re-grant sequence does not leave net-zero [-1, +1] history
		 * rows for the same (user, product, course). Those net to SUM(status) = 0, which
		 * hides the member from the Members list / reporting even though access is active.
		 *
		 * Deliberately scoped to PayPal orders: every other gateway (Stripe, Square,
		 * SendOwl, WooCommerce, manual) keeps the original plain-INSERT behavior untouched,
		 * so this carries no cross-gateway regression risk. The same net-zero fix for the
		 * other gateways is tracked separately.
		 */
		if ( static::STATUS_ACCESS_ADDED === $status && TVA_Const::PAYPAL_GATEWAY === $order->get_gateway() ) {
			$this->commit_paypal_granted_data( $order, $product, $course_ids );
			$this->toggle_access_expiry( $product, $order_item, $status );

			return;
		}

		$data = [];
		$this->build_course_data( $product, $order->get_user_id(), $status, $course_ids, $data, '', $key );

		$this->commit_data( $data );

		$this->toggle_access_expiry( $product, $order_item, $status );
	}

	/**
	 * Grant PayPal product access, consolidating to one definitive history row per
	 * (user, product, course).
	 *
	 * Removes any existing `order`-source rows for these courses (prior grants/revokes,
	 * including legacy duplicates) and inserts a single fresh +1. Consolidating — rather
	 * than flipping existing rows — guarantees one row per tuple, keeps the history table
	 * clean, and self-heals duplicates left by earlier behavior (so a grant -> revoke ->
	 * re-grant never accumulates net-zero [-1, +1] rows that hide the member from reports).
	 *
	 * Deliberately addresses review feedback: the DELETE is scoped by `source = 'order'`,
	 * so it can never touch another integration's rows for the same tuple, and it uses a
	 * prepared `$wpdb->query()` rather than the shared `dbDelta()` path for the removal.
	 *
	 * @param TVA_Order $order
	 * @param Product   $product
	 * @param int[]     $course_ids
	 *
	 * @return void
	 */
	protected function commit_paypal_granted_data( $order, $product, $course_ids ) {
		global $wpdb;

		$user_id       = (int) $order->get_user_id();
		$product_id    = (int) $product->get_id();
		$history_table = $wpdb->prefix . 'tva_' . History_Table::get_table_name();
		$source        = static::KEY; // 'order' — what build_course_data() writes for these rows.

		if ( count( $course_ids ) === 0 ) {
			// No published courses -> a single course_id IS NULL row (a tuple match cannot use NULL).
			$wpdb->query( $wpdb->prepare(
				'DELETE FROM ' . $history_table . ' WHERE user_id = %d AND product_id = %d AND source = %s AND course_id IS NULL',
				[ $user_id, $product_id, $source ]
			) );
		} else {
			$course_ids   = array_map( 'intval', $course_ids );
			$placeholders = implode( ', ', array_fill( 0, count( $course_ids ), '%d' ) );
			$wpdb->query( $wpdb->prepare(
				'DELETE FROM ' . $history_table . ' WHERE user_id = %d AND product_id = %d AND source = %s AND course_id IN (' . $placeholders . ')',
				array_merge( [ $user_id, $product_id, $source ], $course_ids )
			) );
		}

		// Insert a single +1 per course (or one NULL-course row when the product has none).
		$data = [];
		$this->build_course_data( $product, $user_id, static::STATUS_ACCESS_ADDED, $course_ids, $data, '', '' );
		$this->commit_data( $data, false );
	}

	/**
	 * @param         $order
	 * @param         $order_item
	 * @param Product $product
	 * @param         $key
	 *
	 * @return void
	 */
	protected function commit_canceled_product_data( $order, $order_item, $product, $key = '' ) {
		global $wpdb;

		$course_ids    = $product->get_published_courses( true );
		$history_table = $wpdb->prefix . 'tva_' . History_Table::get_table_name();

		if ( count( $course_ids ) > 0 ) {
			/**
			 * This query selects all latest history rows that have matching user_id, product_id. course IDs and source = order and status = 1
			 *
			 * We need only the course IDs from this query to avoid inserting multiple -1 for status in the database
			 * The rule should be -> Insert status -1 only if the last status inserted for the query is 1
			 */
			$sql = 'SELECT * FROM ' . $history_table . ' main WHERE user_id = %d AND product_id = %d AND source = %s AND course_id IN (' . implode( ',', $course_ids ) . ') AND main.created = (SELECT MAX(created) FROM ' . $history_table . ' as tmp WHERE tmp.course_id = main.course_id AND tmp.user_id = %d ) GROUP BY course_id HAVING status = 1 ORDER BY `created` DESC';
			$sql = $wpdb->prepare( $sql, [ $order->get_user_id(), $product->get_id(), 'order', $order->get_user_id() ] );

		} else {
			$sql = 'SELECT * FROM ' . $history_table . ' main WHERE user_id = %d AND product_id = %d AND source = %s AND course_id IS NULL ORDER by created DESC LIMIT 1';
			$sql = $wpdb->prepare( $sql, [ $order->get_user_id(), $product->get_id(), 'order' ] );
		}

		$sql_results = $wpdb->get_results( $sql, ARRAY_A );

		if ( is_array( $sql_results ) && count( $sql_results ) > 0 ) {
			$course_ids = array_filter( array_column( $sql_results, 'course_id' ) );

			$data = [];
			$this->build_course_data( $product, $order->get_user_id(), static::STATUS_ACCESS_REVOKED, $course_ids, $data, '', $key );

			$this->commit_data( $data, true );

			$this->toggle_access_expiry( $product, $order_item, static::STATUS_ACCESS_REVOKED );
		}
	}

	/**
	 * @param TVA_Order_Item $order_item
	 *
	 * @return void
	 */
	public function log_order_item_canceled( $order_item ) {
		$order = new TVA_Order( $order_item->get_order_id() );

		if ( $order->is_sendowl() ) {
			foreach ( $order->get_order_items() as $order_item ) {
				$products = TVA_Sendowl_Manager::get_products_that_have_protection( (int) $order_item->get_product_id() );

				foreach ( $products as $product ) {
					$this->commit_canceled_product_data( $order, $order_item, $product, 'sendowl_product' );
				}

			}
		} else if ( $order->is_stripe() ) {
			$products = TVA_Stripe_Integration::get_all_products_for_identifier( $order_item->get_product_id() );
			foreach ( $products as $product ) {
				$this->commit_canceled_product_data( $order, $order_item, $product );
			}
		} else {
			$product = new Product( $order_item->get_product_id() );
			$this->commit_canceled_product_data( $order, $order_item, $product );
		}

		// Trigger timeline event for refunds
		$user = get_userdata( $order->get_user_id() );
		if ( $user && ! $order->is_sendowl() && ! $order->is_woocommerce() ) {
			do_action( 'tva_order_refunded', $user, $order_item, $order );
		}
	}

	/**
	 * @param                 $data
	 * @param                 $types
	 * @param TVA_Order_Item  $order_item
	 *
	 * @return void
	 */
	public function log_order_item_change( $data, $types, $order_item ) {
		// The tva_after_sendowl_order_item_db action is fired by both TVA_Order_Item::save()
		// and TVA_Transaction::save() (which reuses the same action name). Only order items
		// carry a cancellable status, so guard the type — otherwise a transaction save (e.g. a
		// PayPal capture row) would call the undefined TVA_Transaction::get_status() and fatal.
		if ( $order_item instanceof TVA_Order_Item && $order_item->get_status() === 0 ) {
			$this->log_order_item_canceled( $order_item );
		}
	}

	/**
	 * @param $product_id
	 */
	public function woocommerce_refund( $product_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			$wc_order = wc_get_order( $product_id );
			$wc_order = new TVA_Woocommerce_Order( $wc_order );

			$tva_order = $wc_order->get_tva_order();

			foreach ( $tva_order->get_order_items() as $order_item ) {
				static::log_order_item_canceled( $order_item );
				
				// Trigger refund event
				$user = get_userdata( $tva_order->get_user_id() );
				if ( $user ) {
					do_action( 'tva_order_refunded', $user, $order_item, $tva_order );
				}
			}
		}
	}

	/**
	 * @param TVA_Order $order
	 */
	public function sendowl_refund( $order ) {
		foreach ( $order->get_order_items() as $order_item ) {
			static::log_order_item_canceled( $order_item );
			
			// Trigger refund event
			$user = get_userdata( $order->get_user_id() );
			if ( $user ) {
				do_action( 'tva_order_refunded', $user, $order_item, $order );
			}
		}
	}

	/**
	 * @param Product  $product_id
	 * @param int      $user_id
	 * @param int      $status
	 * @param string   $created
	 * @param string   $key
	 * @param int|null $reason
	 *
	 * @return array
	 */
	public function build_course_data( $product, $user_id, $status, $course_ids, &$data, $created = '', $key = '', $reason = null ) {
		if ( empty( $course_ids ) ) {
			$data[] = [
				'user_id'    => (int) $user_id,
				'product_id' => (int) $product->get_id(),
				'course_id'  => 'null',
				'status'     => (int) $status,
				'source'     => empty( $key ) ? static::KEY : $key,
				'created'    => $created,
				'reason'     => $reason,
			];
		} else {
			foreach ( $course_ids as $id_course ) {
				$data[] = [
					'user_id'    => (int) $user_id,
					'product_id' => (int) $product->get_id(),
					'course_id'  => (int) $id_course,
					'status'     => (int) $status,
					'source'     => empty( $key ) ? static::KEY : $key,
					'created'    => $created,
					'reason'     => $reason,
				];
			}
		}
	}

	/**
	 * Toggle course purchase or course access revoke access expiry
	 *
	 * @param Product        $product
	 * @param TVA_Order_Item $order_item
	 * @param int            $status
	 *
	 * @return void
	 */
	public function toggle_access_expiry( $product, $order_item, $status ) {

		if ( ! $product->has_access_expiry_condition( After_Purchase::CONDITION ) ) {
			return;
		}

		/**
		 * @var After_Purchase $after_purchase
		 */
		$after_purchase = \TVA\Access\Expiry\Base::factory( $product, null );

		switch ( $status ) {
			case static::STATUS_ACCESS_ADDED:
				$after_purchase->on_purchase_add( $order_item );
				break;
			case static::STATUS_ACCESS_REVOKED:
				$after_purchase->on_purchase_revert( $order_item );
				break;
			default:
				break;
		}
	}

	/**
	 * @return true
	 */
	public static function is_active() {
		return true;
	}
}

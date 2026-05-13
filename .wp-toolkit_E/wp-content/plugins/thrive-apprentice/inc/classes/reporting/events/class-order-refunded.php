<?php

namespace TVA\Reporting\Events;

use TVA\Reporting\EventFields\Order_Gateway;
use TVA\Reporting\EventFields\Order_Item_Price;
use TVA\Reporting\EventFields\Order_Type;
use TVE\Reporting\Event;
use TVE\Reporting\Traits\Report;
use TVA\Product;

class Order_Refunded extends Event {
	use Report;

	public static function key(): string {
		return 'tva_order_refunded';
	}

	public static function label(): string {
		return __( 'Order refunded', 'thrive-apprentice' );
	}

	public static function get_extra_varchar_field_1(): string {
		return Order_Gateway::class;
	}

	public static function get_extra_varchar_field_2(): string {
		return Order_Type::class;
	}

	public static function get_extra_float_field() {
		return Order_Item_Price::class;
	}

	public static function register_action() {
		add_action( 'tva_order_refunded', static function ( $user, $order_item, $order ) {
			/**
			 * @var \WP_User        $user
			 * @var \TVA_Order_Item $order_item
			 * @var \TVA_Order      $order
			 */

			if ( $order_item->get_status() !== \TVA_Const::STATUS_REFUND ) {
				return;
			}

			$event = new static( [
				'item_id'          => $order_item->get_product_id(),
				'user_id'          => $user->ID,
				'order_gateway'    => $order->get_gateway(),
				'order_type'       => $order->get_type(),
				'order_item_price' => $order_item->get_product_price(),
			] );

			$event->log();
		}, 10, 3 );
	}

	/**
	 * Event description - used for user timeline
	 *
	 * @return string
	 */
	public function get_event_description(): string {
		$gateway = sanitize_text_field( $this->get_field_value( 'varchar_field_1' ) );

		return esc_html( sprintf( __( ' refunded a product via %s', 'thrive-apprentice' ), $gateway ) );
	}
} 
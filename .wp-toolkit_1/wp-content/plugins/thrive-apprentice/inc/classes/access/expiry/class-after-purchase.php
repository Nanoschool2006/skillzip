<?php

namespace TVA\Access\Expiry;

use TVA\Drip\Schedule\Utils;
use TCB_Utils;
use TVA_Order;
use TVA_Order_Item;

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class After_Purchase extends Base {
	use Utils;

	/**
	 * Condition type
	 */
	const CONDITION = 'after_purchase';

	/**
	 * Event Name
	 */
	const EVENT = 'tva_product_expiry_after_purchase';

	/**
	 * Reminder event name
	 */
	const EVENT_REMINDER = 'tva_product_expiry_after_purchase_reminder';

	/**
	 * @param string $condition
	 *
	 * @return void
	 */
	public function add( $condition ) {
		$product_id = $this->product->get_id();
		update_term_meta( $product_id, static::PRODUCT_EXPIRY_META_KEY, $condition );

		foreach ( $this->product->get_users_with_access() as $user_id ) {
			$order = TCB_Utils::get_tva_order_by_product_and_user( $product_id, $user_id );
			if ( $order ) {
				$this->on_purchase_add( $order );
			}
		}
	}

	public function remove( $condition ) {
		//On general remove, we remove all the expiry hooks
		$this->on_purchase_revert( false );
	}

	/**
	 * @param TVA_Order_Item | TVA_Order $order_item
	 *
	 * @return void
	 */
	public function on_purchase_add( $order_item ) {
		if ( $order_item instanceof TVA_Order_Item ) {
			$order = new TVA_Order( $order_item->get_order_id() );
		} else {
			$order = $order_item; 
		}
		$condition = $this->product->get_access_expiry()['expiry']['cond_purchase'];

		$unit   = strtoupper( $condition['unit'] );
		$number = (int) $condition['number'];

		$order_datetime      = static::get_datetime( (string) $order_item->get_created_at() );
		$expiration_datetime = $order_datetime->add( new \DateInterval( "P$number$unit" ) );

		update_user_meta( (int) $order->get_user_id(), $this->get_product_user_meta_key(), $expiration_datetime->format( 'Y-m-d H:i:s' ) );

		$this->schedule_event( $expiration_datetime, [ 'user_id' => (int) $order->get_user_id() ] );

		if ( $this->product->has_access_expiry_reminder() ) {
			$reminder = $this->product->get_access_expiry()['reminder'];

			$this->add_reminder( $order_datetime, $reminder, [ 'user_id' => (int) $order->get_user_id() ] );
		}
	}

	/**
	 * Removed the purchase hooks
	 * If $order_item is false it removes all hooks from all users
	 *
	 * @param TVA_Order_Item|false $order_item
	 *
	 * @return void
	 */
	public function on_purchase_revert( $order_item = false ) {
		$reminder = ! empty( $this->product->get_access_expiry()['reminder'] ) ? $this->product->get_access_expiry()['reminder'] : false;

		if ( $order_item instanceof TVA_Order_Item ) {
			$order = new TVA_Order( $order_item->get_order_id() );

			$scheduled_date    = get_user_meta( $order->get_user_id(), $this->get_product_user_meta_key(), true );
			$schedule_datetime = static::get_datetime( (string) $scheduled_date );

			$this->unschedule_event( $schedule_datetime, [ 'user_id' => (int) $order->get_user_id() ] );

			if ( is_array( $reminder ) ) {
				$this->remove_reminder( $schedule_datetime, $reminder, [ 'user_id' => (int) $order->get_user_id() ] );
			}
		} else {
			$meta_key = $this->get_product_user_meta_key();
			$users    = get_users( [
				'meta_key' => $meta_key,
			] );

			foreach ( $users as $user ) {
				$scheduled_date    = get_user_meta( $user->ID, $meta_key, true );
				$schedule_datetime = static::get_datetime( (string) $scheduled_date );

				$this->unschedule_event( $schedule_datetime, [ 'user_id' => $user->ID ] );

				if ( is_array( $reminder ) ) {
					$this->remove_reminder( $schedule_datetime, $reminder, [ 'user_id' => $user->ID ] );
				}
			}
			$this->clear_user_meta();
		}
	}

	/**
	 * Triggered only when reminder has been modified
	 *
	 * @param $expiry
	 * @param $old_reminder
	 * @param $new_reminder
	 *
	 * @return void
	 */
	public function reminder_modified( $expiry, $old_reminder, $new_reminder ) {
		$meta_key = $this->get_product_user_meta_key();
		$users    = get_users( [
			'meta_key' => $meta_key,
		] );
		
		foreach ( $users as $user ) {
			$scheduled_date = get_user_meta( $user->ID, $meta_key, true );

			$this->remove_reminder( (string) $scheduled_date, $old_reminder, [ 'user_id' => $user->ID ] );
			$this->add_reminder( (string) $scheduled_date, $new_reminder, [ 'user_id' => $user->ID ] );
		}
	}
}

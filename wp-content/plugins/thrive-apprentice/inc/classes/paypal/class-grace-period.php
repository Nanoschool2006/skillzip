<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Grace_Period
 *
 * Utility for cleaning up abandoned PayPal subscription orders.
 *
 * An "abandoned" order is STATUS_PENDING with no payment_id and older than
 * PENDING_CLEANUP_HOURS hours. These orders were created at buy-now time but
 * the buyer never approved the payment in PayPal. The associated subscription
 * is in APPROVAL_PENDING state in the Product API — it has never been
 * activated and cannot bill the buyer. No cancellation call is needed.
 *
 * Triggered lazily in Buy_Now\Paypal::get_subscription_url() before each new
 * buy attempt. Can also be called directly via WP-CLI:
 *   wp tva-paypal cleanup-pending
 *
 * Grace-period lifecycle (STATUS_GRACE_PERIOD → STATUS_FAILED after 10 days)
 * is the Product API's responsibility, delivered via the THRIVE.ACCESS.REVOKE
 * webhook. No WP-cron fallback exists in the plugin.
 */
class Grace_Period {

	/** Hours after creation before an abandoned pending order is cleaned up. */
	const PENDING_CLEANUP_HOURS = 24;

	const VAULT_OPTION_PREFIX = 'tva_paypal_vault_';

	/**
	 * Mark abandoned STATUS_PENDING PayPal *subscription* buy-now orders as failed
	 * after PENDING_CLEANUP_HOURS — buyer started but never approved.
	 *
	 * Scoped to subscription orders only (identified by the vault option's
	 * subscription_id). One-time orders are deliberately left alone: they are
	 * fulfilled synchronously at capture time and by the capture webhook, so a
	 * still-pending one-time order may be a captured payment whose grant simply
	 * hasn't landed yet — force-failing it would discard a paid purchase. With no
	 * order-status API available here we cannot tell "abandoned" from "paid but not
	 * yet completed", so we never fail one-time orders in this sweep.
	 */
	public static function cleanup_abandoned_pending_orders(): void {
		global $wpdb;

		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;

		// Compute cutoff in WP-local time to match how created_at is stored.
		// TVA_Order::save() writes created_at via current_datetime() (WP-local),
		// so the comparison value must also be WP-local, not UTC.
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( static::PENDING_CLEANUP_HOURS * HOUR_IN_SECONDS ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM `' . $table . '`
				 WHERE `gateway`    = %s
				   AND `status`     = %d
				   AND `created_at` <= %s
				   AND ( `payment_id` IS NULL OR `payment_id` = \'\' OR `payment_id` = \'0\' )
				 LIMIT 100',
				\TVA_Const::PAYPAL_GATEWAY,
				\TVA_Const::STATUS_PENDING,
				$cutoff
			),
			ARRAY_A
		);

		foreach ( $rows as $row ) {
			$order_id = (int) ( $row['ID'] ?? 0 );
			if ( $order_id <= 0 ) {
				continue;
			}

			$vault_data      = get_option( static::VAULT_OPTION_PREFIX . $order_id, [] );
			$subscription_id = $vault_data['subscription_id'] ?? '';
			$mode            = $vault_data['mode'] ?? 'live';

			// Only abandoned subscription buy-now orders are swept. A pending order
			// with no subscription_id is a one-time purchase — never force-fail it
			// (see method docblock).
			if ( empty( $subscription_id ) ) {
				continue;
			}

			$cancel_result = Request::cancel_subscription( $subscription_id, $mode );
			if ( is_wp_error( $cancel_result ) ) {
				\TVA_Logger::set_type( 'PayPal Grace Period' );
				\TVA_Logger::log( 'cancel_subscription_failed', [
					'order_id'        => $order_id,
					'subscription_id' => $subscription_id,
					'error'           => $cancel_result->get_error_message(),
				], true );
				// Leave the order PENDING and the vault linkage intact so the next sweep
				// retries; deleting + failing now would orphan a subscription that may
				// still be cancellable.
				continue;
			}

			delete_option( static::VAULT_OPTION_PREFIX . $order_id );

			$order = new \TVA_Order();
			$order->set_data( $row );
			$order->set_status( \TVA_Const::STATUS_FAILED );
			$order->save();
		}
	}
}

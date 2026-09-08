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
 * Class Subscription_Winddown
 *
 * Implements the D17 merchant-disconnect wind-down for PayPal vault subscriptions:
 * when a merchant disconnects, every active subscription is cancelled at the end of
 * its current billing period (no further renewals).
 *
 * MUST run BEFORE Credentials::disconnect() clears the merchant credentials — the
 * Product API cancel calls need a live bearer token. After credentials are gone the
 * cancel calls would fail and any renewal webhooks would be silently dropped (the
 * webhook secret is cleared on disconnect), leaving subscribers billed with no order
 * or access update recorded.
 *
 * Scope: the admin-initiated disconnect endpoint. The MERCHANT.PARTNER.CONSENT.REVOKED
 * webhook path (Credentials::disconnect_mode()) deliberately does NOT wind down here —
 * PayPal has already revoked partner consent at that point, so cancel calls cannot
 * succeed.
 *
 * "Active" means a PayPal subscription order in STATUS_COMPLETED (billing normally) or
 * STATUS_GRACE_PERIOD (in dunning). One-time PayPal orders are excluded: they carry no
 * vault option with a subscription_id.
 *
 * Access is intentionally left untouched — the subscriber keeps the period they have
 * already paid for; only future renewals stop. This differs from the buyer-initiated
 * cancel path, which revokes access immediately.
 */
class Subscription_Winddown {

	const VAULT_OPTION_PREFIX = 'tva_paypal_vault_';

	/**
	 * Cancel every active PayPal subscription at period end.
	 *
	 * Idempotent: a subscription whose vault option already carries the
	 * `winddown_cancelled` marker is skipped, so a repeated disconnect (or a retried
	 * request) is not double-cancelled. Per-subscription failures are logged and skipped
	 * so one bad row never blocks the rest of the wind-down.
	 *
	 * @return int Number of subscriptions cancelled in this run.
	 */
	public static function run(): int {
		$cancelled = 0;

		foreach ( static::get_active_subscriptions() as $sub ) {
			$order_id        = $sub['order_id'];
			$subscription_id = $sub['subscription_id'];
			$mode            = $sub['mode'];

			$cancel_result = Request::cancel_subscription( $subscription_id, $mode );
			if ( is_wp_error( $cancel_result ) ) {
				\TVA_Logger::set_type( 'PayPal Disconnect Wind-Down' );
				\TVA_Logger::log( 'cancel_subscription_failed', [
					'order_id'        => $order_id,
					'subscription_id' => $subscription_id,
					'error'           => $cancel_result->get_error_message(),
				], true );
				// Leave the vault linkage intact and unmarked so nothing is recorded as
				// wound down when the remote cancel never landed.
				continue;
			}

			static::mark_wound_down( $order_id, $sub['vault'] );
			$cancelled++;
		}

		return $cancelled;
	}

	/**
	 * Count active PayPal subscriptions, for the pre-disconnect admin warning.
	 *
	 * @return int
	 */
	public static function count_active(): int {
		return count( static::get_active_subscriptions() );
	}

	/**
	 * Active PayPal subscriptions that have not already been wound down — one entry per
	 * subscription, never per order row.
	 *
	 * Reads candidate orders from the orders table (gateway + status filter, indexed),
	 * then keeps only those whose vault option carries a subscription_id and no
	 * `winddown_cancelled` marker. Renewal orders are skipped: each renewal copies the
	 * anchor's subscription_id onto its own vault option with an `original_order_id`
	 * back-reference (see Payment_Capture_Completed::handle_renewal()), so counting or
	 * cancelling them would over-count and cancel the same subscription multiple times.
	 * Results are also de-duplicated by subscription_id as a belt-and-suspenders guard.
	 *
	 * Statically cached for the request: both admin templates (status page + disconnect
	 * modal) call count_active(), and disconnect() calls it once more before run(), so the
	 * query + per-row option reads run only once per request.
	 *
	 * @return array<int,array{order_id:int,subscription_id:string,mode:string,vault:array}>
	 */
	public static function get_active_subscriptions(): array {
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		global $wpdb;

		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is built from constants.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT `ID` FROM `' . $table . '`
				 WHERE `gateway` = %s
				   AND `status`  IN ( %d, %d )',
				\TVA_Const::PAYPAL_GATEWAY,
				\TVA_Const::STATUS_COMPLETED,
				\TVA_Const::STATUS_GRACE_PERIOD
			),
			ARRAY_A
		);

		$order_ids    = [];
		$option_names = [];
		foreach ( (array) $rows as $row ) {
			$order_id = (int) ( $row['ID'] ?? 0 );
			if ( $order_id <= 0 ) {
				continue;
			}
			$order_ids[]    = $order_id;
			$option_names[] = static::VAULT_OPTION_PREFIX . $order_id;
		}

		if ( empty( $order_ids ) ) {
			return $cache = [];
		}

		// Read every candidate's vault option in ONE query rather than a get_option() per
		// order row. These options are autoload=false, so on a cold object cache each
		// get_option() is its own DB hit — O(orders) queries on every admin page load and
		// disconnect for high-renewal merchants.
		$placeholders = implode( ', ', array_fill( 0, count( $option_names ), '%s' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders are a counted list of %s; option_names are bound.
		$vault_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT `option_name`, `option_value` FROM `{$wpdb->options}` WHERE `option_name` IN ( {$placeholders} )",
				$option_names
			),
			ARRAY_A
		);

		$vault_map = [];
		foreach ( (array) $vault_rows as $vrow ) {
			$vault_map[ $vrow['option_name'] ] = maybe_unserialize( $vrow['option_value'] );
		}

		$subscriptions = [];
		$seen          = [];

		foreach ( $order_ids as $order_id ) {
			$vault = $vault_map[ static::VAULT_OPTION_PREFIX . $order_id ] ?? [];
			if ( ! is_array( $vault ) ) {
				continue;
			}

			$subscription_id = (string) ( $vault['subscription_id'] ?? '' );

			// No subscription_id => one-time purchase, not a vault subscription.
			if ( '' === $subscription_id ) {
				continue;
			}

			// Renewal order — the subscription anchor (and its winddown marker) live on the
			// original purchase order, so skip renewals to avoid cancelling the same
			// subscription once per billing cycle.
			if ( ! empty( $vault['original_order_id'] ) ) {
				continue;
			}

			// Already cancelled by a previous wind-down — don't count or re-process.
			if ( ! empty( $vault['winddown_cancelled'] ) ) {
				continue;
			}

			// One entry per subscription, even if two anchor rows somehow share an id.
			if ( isset( $seen[ $subscription_id ] ) ) {
				continue;
			}
			$seen[ $subscription_id ] = true;

			// Validate the stored mode rather than defaulting to 'live': a test-only merchant
			// with a missing/blank mode would otherwise get every cancel sent to live (no bearer
			// there), failing silently. Fall back to whichever mode is actually connected.
			$mode = $vault['mode'] ?? '';
			if ( ! in_array( $mode, [ 'live', 'test' ], true ) ) {
				$mode = Credentials::is_mode_enabled( 'live' ) ? 'live' : 'test';
			}

			$subscriptions[] = [
				'order_id'        => $order_id,
				'subscription_id' => $subscription_id,
				'mode'            => $mode,
				// Carried so mark_wound_down() can reuse it instead of re-reading the option.
				'vault'           => $vault,
			];
		}

		return $cache = $subscriptions;
	}

	/**
	 * Stamp the wind-down marker on a subscription's vault option (idempotency guard).
	 *
	 * @param int   $order_id
	 * @param array $vault    The vault option already fetched by get_active_subscriptions();
	 *                        re-read only as a fallback when not supplied.
	 */
	private static function mark_wound_down( int $order_id, array $vault = [] ): void {
		if ( empty( $vault ) ) {
			$vault = get_option( static::VAULT_OPTION_PREFIX . $order_id, [] );
			$vault = is_array( $vault ) ? $vault : [];
		}

		$vault['winddown_cancelled'] = true;
		$vault['winddown_at']        = time();

		update_option( static::VAULT_OPTION_PREFIX . $order_id, $vault, false );
	}
}

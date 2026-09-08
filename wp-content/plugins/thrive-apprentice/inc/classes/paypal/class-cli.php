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
 * Class CLI
 *
 * WP-CLI commands for the PayPal PPCP integration.
 *
 * Registered as: wp tva-paypal <command>
 *
 * Usage in system cron (alternative to WP-cron):
 *   * * * * * /usr/bin/wp --path=/path/to/wp tva-paypal cleanup-pending --quiet
 */
class CLI {

	/**
	 * Clean up abandoned PayPal subscription orders older than 24 hours.
	 *
	 * Marks STATUS_PENDING orders with no payment_id as FAILED and deletes their
	 * vault options. Safe to run frequently — the SQL WHERE clause limits scope.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tva-paypal cleanup-pending
	 *
	 * @when after_wp_load
	 * @subcommand cleanup-pending
	 */
	public function cleanup_pending(): void {
		Grace_Period::cleanup_abandoned_pending_orders();
		\WP_CLI::success( 'Abandoned pending PayPal orders cleaned up.' );
	}
}

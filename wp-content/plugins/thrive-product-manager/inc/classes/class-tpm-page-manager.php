<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-product-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TPM_Page_Manager {

	protected static $_instance;

	private function __construct() {
	}

	public function render() {

		$connection = TPM_Connection::get_instance();

		/*
		 * Account-side deactivation enforcement: a connected site must always sit on a VALID active
		 * license. If the account owner deactivated the license this site was using (Manage Sites),
		 * drop the connection back to the Connect screen - don't sit on a dead license or silently
		 * hop to an unrelated one. Reconcile against the CACHED license pool here - no forced refetch,
		 * which would add a blocking proxy round-trip (up to the 30s request timeout) to every open of
		 * this page; a just-deactivated site is caught within the cache cycle, or instantly via the
		 * License Manager's "Refresh" action. Tracked via $deactivated because disconnect() does not
		 * reset the in-memory status, so is_connected() would still report true within this same request.
		 */
		$deactivated = false;

		if ( $connection->is_connected() && 'disconnect' === TPM_License_Manager::get_instance()->reconcile_active_license() ) {
			$connection->disconnect();
			$deactivated = true;
		}

		if ( $deactivated || $connection->is_connected() === false ) {
			$connection->render();
		} else {
			TPM_Product_List::get_instance()->render();
		}
	}

	public static function get_instance() {

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

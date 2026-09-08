<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-product-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Hidden, direct-URL-only License Manager page for support/diagnostics.
 *
 * Reachable only at admin.php?page=tpm_license_manager (no menu item), gated by manage_options.
 * Lets support inspect everything TTW/EDD knows about the connected account, switch/toggle which
 * licenses the site uses, clear the license cache and force a fresh re-fetch.
 */
class TPM_License_Page {

	const NAME = 'tpm_license_manager';

	/**
	 * TD transient + option holding the rich get_licenses_details response.
	 */
	const TD_DETAILS_TRANSIENT = 'td_ttw_licenses_details';

	/**
	 * Keys whose values must never be printed raw in the diagnostic dump.
	 */
	const SECRET_KEYS = array( 'ttw_salt', 'ttw_auth', 'auth_token', 'tpm_token', 'salt', 'signature' );

	/**
	 * @var TPM_License_Page
	 */
	protected static $_instance;

	private function __construct() {

		add_action( 'admin_menu', array( $this, 'register_section' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), PHP_INT_MAX );
		add_action( 'admin_init', array( $this, 'maybe_handle_actions' ) );
	}

	public static function get_instance() {

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Register the hidden page (empty parent slug => no menu item; NOT null, for PHP 8.1).
	 */
	public function register_section() {

		if ( empty( $_REQUEST['page'] ) || self::NAME !== $_REQUEST['page'] ) {
			return;
		}

		add_submenu_page(
			'',
			__( 'License Manager', Thrive_Product_Manager::T ),
			'',
			'manage_options',
			self::NAME,
			array( $this, 'render' )
		);
	}

	/**
	 * This class' own page check - do NOT reuse Thrive_Product_Manager::is_known_page(), which
	 * matches against the main plugin's registered menu pages and never sees this hidden page.
	 *
	 * @return bool
	 */
	public function is_known_page() {

		return isset( $_REQUEST['page'] ) && self::NAME === $_REQUEST['page'];
	}

	public function get_admin_url() {

		return admin_url( 'admin.php?page=' . self::NAME );
	}

	/**
	 * Reuse the already-built TPM admin stylesheet, scoped to this page only.
	 */
	public function enqueue_scripts() {

		if ( ! $this->is_known_page() ) {
			return;
		}

		wp_enqueue_style( 'tpm-license-page', thrive_product_manager()->url( 'css/tpm-admin.css' ), array(), Thrive_Product_Manager::V );
	}

	/**
	 * Menu callback - server-render the template the TPM way.
	 */
	public function render() {

		/* Defense in depth: WP core already gates this page via the manage_options cap on
		   add_submenu_page(), but never render the support dump without an explicit capability check. */
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', Thrive_Product_Manager::T ), '', array( 'response' => 403 ) );
		}

		/* Keep the site on a VALID active license so the table pre-selects it (and disconnect if the
		   active license was deactivated with none left). Refresh the pool first for a true state. */
		$deactivated = false;

		if ( class_exists( 'TPM_Connection' ) && class_exists( 'TPM_License_Manager' ) && TPM_Connection::get_instance()->is_connected() ) {
			TPM_License_Manager::get_instance()->clear_cache();
			if ( 'disconnect' === TPM_License_Manager::get_instance()->reconcile_active_license() ) {
				TPM_Connection::get_instance()->disconnect();
				$deactivated = true;
			}
		}

		/*
		 * When the account is not connected - or was just disconnected on THIS request because the
		 * active license is deactivated - render the Connect screen instead of the license table,
		 * matching the Product Manager page. disconnect() does not reset the in-memory connection
		 * status, so without this same-request short-circuit the table would still render now and
		 * only flip to "not connected" on the next page load.
		 */
		if ( class_exists( 'TPM_Connection' ) && ( $deactivated || ! TPM_Connection::get_instance()->is_connected() ) ) {
			TPM_Connection::get_instance()->render();
			return;
		}

		ob_start();
		include thrive_product_manager()->path( 'inc/templates/header.phtml' );
		include thrive_product_manager()->path( 'inc/templates/license/page.phtml' );
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes its own dynamic output.
	}

	/* ---------------------------------------------------------------------
	 * Action handling
	 * ------------------------------------------------------------------- */

	/**
	 * Dispatch the page's POST/link actions. Every action is capability- + nonce-checked and
	 * ends in a PRG redirect back to the page (so no action re-runs on refresh).
	 */
	public function maybe_handle_actions() {

		if ( ! $this->is_known_page() || empty( $_REQUEST['tpm_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_REQUEST['tpm_action'] ) );

		switch ( $action ) {
			case 'clear':
				$this->handle_clear();
				break;
			case 'refresh':
				$this->handle_refresh();
				break;
			case 'set_active':
				$this->handle_set_active();
				break;
			case 'clear_legacy':
				$this->handle_clear_legacy();
				break;
		}
	}

	/**
	 * Force-clear the license caches (TD details + connection error + TPM caches).
	 */
	protected function handle_clear() {

		check_admin_referer( 'tpm_license_clear' );

		$this->clear_license_caches();

		$this->redirect( 'cleared' );
	}

	/**
	 * Delete the legacy `thrive_license` option — the additive, never-shrunk dashboard over-grant.
	 *
	 * A stale ['all'] (left over from a long-expired all-access membership) makes every product
	 * report licensed via TPM_Product::is_licensed()'s backwards-compat check, regardless of the
	 * account's real entitlement and regardless of the License Manager selection. Neither "Clear
	 * license cache" nor connect/disconnect touch this option, so this is the explicit support lever
	 * to purge it. After purging, is_licensed() falls through to the License Manager selection.
	 */
	protected function handle_clear_legacy() {

		check_admin_referer( 'tpm_license_clear_legacy' );

		$had = get_option( 'thrive_license', array() );

		delete_option( 'thrive_license' );

		// Re-evaluate the product list immediately on the next render.
		$this->clear_license_caches();

		$this->redirect( empty( $had ) ? 'legacy_empty' : 'legacy_cleared' );
	}

	/**
	 * Clear the cached license data, then force a single immediate re-fetch from TTW and report the
	 * real outcome (connection error, or no data returned) rather than always claiming success.
	 */
	protected function handle_refresh() {

		check_admin_referer( 'tpm_license_refresh' );

		if ( class_exists( 'TPM_Connection' ) && ! TPM_Connection::get_instance()->is_connected() ) {
			$this->redirect( 'not_connected' );
		}

		$this->clear_license_caches();

		$details = array();
		if ( class_exists( 'TD_TTW_User_Licenses' ) ) {
			/* cache was just cleared, so this performs a fresh authenticated fetch */
			$details = TD_TTW_User_Licenses::get_instance()->get_licenses_details();
		}

		/*
		 * Report the real outcome. A non-200 fetch sets the connection-error transient -> 'refresh_error'.
		 * A 200 with a malformed/empty body sets neither an error nor any data, so an empty result with no
		 * error means the refresh brought nothing back - don't show a green "refreshed" on that silent
		 * failure (this also honestly covers a genuinely license-less account).
		 */
		$error = function_exists( 'thrive_get_transient' ) && thrive_get_transient( 'td_ttw_connection_error' );

		if ( $error ) {
			$notice = 'refresh_error';
		} elseif ( empty( $details ) ) {
			$notice = 'refresh_empty';
		} else {
			$notice = 'refreshed';
		}

		$this->redirect( $notice );
	}

	/**
	 * Apply the checked set of licenses to this site. Multi-select: the checkboxes are the source of
	 * truth - every selected license is enabled locally and registered on the account ('up'), and any
	 * license that was active but is now unselected is disabled locally and released ('down').
	 */
	protected function handle_set_active() {

		check_admin_referer( 'tpm_license_set_active' );

		if ( ! class_exists( 'TPM_License_Manager' ) ) {
			$this->redirect( 'error' );
		}

		$posted  = isset( $_POST['license_id'] ) ? (array) wp_unslash( $_POST['license_id'] ) : array();
		$checked = array_values( array_unique( array_filter( array_map( 'absint', $posted ) ) ) );

		/* At least one license must be selected. */
		if ( empty( $checked ) ) {
			$this->redirect( 'select_one' );
		}

		/* Account-area deactivation wins: a license deactivated for this site can't be enabled here. */
		$checked = array_values( array_filter( $checked, function ( $id ) {
			return ! $this->is_license_deactivated( (int) $id );
		} ) );

		if ( empty( $checked ) ) {
			$this->redirect( 'blocked_deactivated' );
		}

		$manager = TPM_License_Manager::get_instance();

		$saved      = array_map( 'intval', array_keys( TPM_License::get_saved_licenses() ) );
		$to_disable = array_values( array_diff( $saved, $checked ) );

		/*
		 * LOCAL first - enable every checked license, then drop the ones no longer selected (enabling
		 * first means the local enabled set is never momentarily empty). Caches are cleared once at the
		 * end, not inside enable/disable, so the next render re-evaluates.
		 */
		foreach ( $checked as $id ) {
			$manager->enable_license( $id );
		}
		foreach ( $to_disable as $id ) {
			$manager->disable_license( $id );
		}

		/*
		 * Mirror to the account so its Active Sites list matches the checkboxes: register the site on
		 * each selected license ('up') and release it from the ones just removed ('down'). release_others
		 * is FALSE - with multiple licenses allowed we drive each explicitly and must NOT have the server
		 * release the site from the other still-selected licenses.
		 */
		$up_ok   = $this->license_uses_request( $checked, 'up', false );
		$down_ok = empty( $to_disable ) || $this->license_uses_request( $to_disable, 'down', false );

		/* Clear caches so the next Product Manager render reflects the new set immediately. */
		$this->clear_license_caches();

		$this->redirect( ( $up_ok && $down_ok ) ? 'active_set' : 'active_set_nosync' );
	}

	/**
	 * POST /license_uses for this site, same request shape as TPM_License_Manager::activate_licenses().
	 *
	 * @param array  $license_ids
	 * @param string $direction 'up' (register this site on the licenses) or 'down' (release them)
	 * @param bool   $release_others when true, ask the server to release this site from the user's
	 *                               OTHER seated licenses (single-license-per-site; switch only)
	 *
	 * @return bool true when the server confirmed every requested license id
	 */
	protected function license_uses_request( $license_ids, $direction, $release_others = false ) {

		$connection = TPM_Connection::get_instance();

		$request = new TPM_Request( '/api/v1/public/license_uses', array(
			'user_id'        => $connection->ttw_id,
			'user_site_url'  => get_site_url(),
			'data'           => $license_ids,
			'direction'      => $direction,
			'release_others' => $release_others ? 1 : 0,
		) );
		$request->set_header( 'Authorization', $connection->ttw_salt );

		$proxy_request = new TPM_Proxy_Request( $request );
		$response      = $proxy_request->execute( '/tpm/proxy' );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $result ) || ! isset( $result['data'] ) || ! is_array( $result['data'] ) ) {
			return false;
		}

		/*
		 * Direction-aware success. A 'down' releases the site by deleting its activation row
		 * server-side, unconditionally; the per-id value it returns is the success of the
		 * now-frozen/vestigial legacy uses counter (EDD's COUNT(*) is the real source post-migration),
		 * so it is false even on a clean release. Treat the id being PRESENT in the response as "the
		 * release was processed" (a not-owned/unprocessed id is absent). 'up' keeps a reliable per-id
		 * active flag (true when the seat is registered, incl. already-active), so its strict check
		 * still surfaces a genuine activation failure (e.g. over the seat limit).
		 */
		foreach ( $license_ids as $license_id ) {
			if ( 'down' === $direction ) {
				if ( ! array_key_exists( (int) $license_id, $result['data'] ) ) {
					return false;
				}
			} elseif ( empty( $result['data'][ $license_id ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete every license-related cache so the next read re-fetches fresh data.
	 */
	protected function clear_license_caches() {

		if ( function_exists( 'thrive_delete_transient' ) ) {
			thrive_delete_transient( self::TD_DETAILS_TRANSIENT );
			thrive_delete_transient( 'td_ttw_connection_error' );
		} else {
			delete_option( '_thrive_tr_' . self::TD_DETAILS_TRANSIENT );
			delete_option( '_thrive_tr_td_ttw_connection_error' );
		}

		if ( class_exists( 'TPM_License_Manager' ) ) {
			TPM_Product_List::get_instance()->clear_cache();
			TPM_License_Manager::get_instance()->clear_cache();
		}
	}

	/**
	 * PRG redirect back to the page with a notice code, then halt.
	 *
	 * @param string $notice
	 */
	protected function redirect( $notice, $args = array() ) {

		$args['tpm_notice'] = $notice;
		wp_safe_redirect( add_query_arg( $args, $this->get_admin_url() ) );
		die;
	}

	/* ---------------------------------------------------------------------
	 * View data (consumed by the template)
	 * ------------------------------------------------------------------- */

	/**
	 * Build a nonce'd action URL for the link-style actions.
	 *
	 * @param string $action
	 * @param array  $args
	 * @param string $nonce_action
	 *
	 * @return string
	 */
	public function action_url( $action, $args, $nonce_action ) {

		$args['tpm_action'] = $action;

		return wp_nonce_url( add_query_arg( $args, $this->get_admin_url() ), $nonce_action );
	}

	/**
	 * The human-readable notice for the current ?tpm_notice= code, or null.
	 *
	 * @return array|null { type: 'success'|'error', message: string }
	 */
	public function get_notice() {

		if ( empty( $_GET['tpm_notice'] ) ) {
			return null;
		}

		$code = sanitize_key( wp_unslash( $_GET['tpm_notice'] ) );

		/*
		 * License label (name + id) carried by the per-license notices so they name the license.
		 * Degrades gracefully: "Name (#id)" -> "#id" -> a generic phrase if nothing came through.
		 */
		$lic_id      = ! empty( $_GET['tpm_license'] ) ? absint( wp_unslash( $_GET['tpm_license'] ) ) : 0;
		$lic_name    = ! empty( $_GET['tpm_license_name'] ) ? sanitize_text_field( wp_unslash( $_GET['tpm_license_name'] ) ) : '';
		$label       = $lic_id ? ( $lic_name ? sprintf( '%s (#%d)', $lic_name, $lic_id ) : sprintf( '#%d', $lic_id ) ) : '';
		$label_block = $label ? $label : __( 'This license', Thrive_Product_Manager::T );

		$map = array(
			'cleared'             => array( 'success', __( 'License cache cleared locally (no call to Thrive Themes). It will be re-fetched the next time it is needed.', Thrive_Product_Manager::T ) ),
			'legacy_cleared'      => array( 'success', __( 'Legacy license override (thrive_license) cleared. Product access now follows this account\'s real entitlement and the License Manager selection above. If a product still shows as licensed, "Refresh from Thrive Themes" to re-evaluate.', Thrive_Product_Manager::T ) ),
			'legacy_empty'        => array( 'success', __( 'No legacy license override was set — nothing to clear.', Thrive_Product_Manager::T ) ),
			'refreshed'           => array( 'success', __( 'License data refreshed from Thrive Themes.', Thrive_Product_Manager::T ) ),
			'refresh_error'       => array( 'error', __( 'Refresh failed - see the last connection error below. Stale data was not re-cached.', Thrive_Product_Manager::T ) ),
			'refresh_empty'       => array( 'error', __( 'Refresh completed, but Thrive Themes returned no license data. If you expected licenses here, check the connection details in the diagnostic dump below.', Thrive_Product_Manager::T ) ),
			'active_set'          => array( 'success', __( 'Saved the selected licenses for this site and registered them in your Thrive Themes account. The Product Manager shows the combined products of every enabled license.', Thrive_Product_Manager::T ) ),
			'active_set_nosync'   => array( 'error', __( 'Saved the selected licenses for this site, but syncing them to your Thrive Themes account did not fully complete - the account\'s Active Sites list may be out of date. Check the connection below and try again.', Thrive_Product_Manager::T ) ),
			'select_one'          => array( 'error', __( 'Select at least one license to use on this site.', Thrive_Product_Manager::T ) ),
			'not_connected'       => array( 'error', __( 'Not connected to Thrive Themes - nothing to refresh.', Thrive_Product_Manager::T ) ),
			'blocked_deactivated' => array( 'error', sprintf( __( '%s was deactivated for this website in the Thrive Themes account area and cannot be enabled from here. Reactivate the site in your account first.', Thrive_Product_Manager::T ), $label_block ) ),
			'error'               => array( 'error', __( 'Action failed.', Thrive_Product_Manager::T ) ),
		);

		if ( ! isset( $map[ $code ] ) ) {
			return null;
		}

		return array(
			'type'    => $map[ $code ][0],
			'message' => $map[ $code ][1],
		);
	}

	/**
	 * Rows for the "switch & toggle" table: every owned (purchased) license, enriched with the
	 * rich TD details (name/status/expiration) and marked enabled when present locally.
	 *
	 * @return array
	 */
	public function get_license_rows() {

		$rows = array();

		if ( ! class_exists( 'TPM_License_Manager' ) ) {
			return $rows;
		}

		$owned       = TPM_License_Manager::get_instance()->get_ttw_license_instances(); // id => TPM_License (tags + seats)
		$enabled     = TPM_License::get_saved_licenses();                                // id => TPM_License
		$details     = $this->index_details_by_id();                                     // id => TD_TTW_License
		$raw_details = $this->get_raw_details_by_id();                                    // id => raw details item
		$pool        = $this->get_raw_license_pool();                                     // id => raw /get_licenses item

		foreach ( $owned as $id => $license ) {
			$id        = (int) $id;
			$detail    = isset( $details[ $id ] ) ? $details[ $id ] : null;
			$raw       = isset( $pool[ $id ] ) && is_array( $pool[ $id ] ) ? $pool[ $id ] : array();
			$raw_detail = isset( $raw_details[ $id ] ) && is_array( $raw_details[ $id ] ) ? $raw_details[ $id ] : array();

			$rows[ $id ] = array(
				'id'          => $id,
				/* translators: %d: license id */
				'name'        => $detail ? $detail->get_name() : sprintf( __( 'License #%d', Thrive_Product_Manager::T ), $id ),
				'tags'        => $license->get_tags(),
				'used'        => $license->get_used(),
				'max'         => $license->get_max(),
				/* Real seat cap from the server (usage.limit); null when an older cache lacks it. 0 == unlimited. */
				'limit'       => isset( $raw['usage']['limit'] ) ? (int) $raw['usage']['limit'] : null,
				'status'      => $detail ? (int) $detail->status : null,
				'state'       => $detail ? $detail->get_state() : '',
				/* Raw account-area status string (active/inactive/expired/...). Read from the raw
				   details item, NOT the TD_TTW_License getter, so a whitelisted __get can't null it. */
				'account_status' => isset( $raw_detail['account_status'] ) ? (string) $raw_detail['account_status'] : '',
				'expiration'  => $detail ? $detail->get_expiration() : '',
				'can_update'  => $detail ? (bool) $detail->can_update() : null,
				'is_all'      => in_array( 'all', $license->get_tags(), true ),
				'enabled'     => isset( $enabled[ $id ] ),
				/* Deactivated for THIS site in the account-area Manage Sites - cannot be enabled here. */
				'deactivated' => ! empty( $raw['site_deactivated'] ),
			);
		}

		return $rows;
	}

	/**
	 * The raw cached /get_licenses pool (id => { tags, usage, site_deactivated, ... }).
	 *
	 * `site_deactivated` is set server-side for the caller's own site URL, but
	 * get_ttw_license_instances() keeps only tags/seats, so read it straight from the transient.
	 *
	 * @return array
	 */
	protected function get_raw_license_pool() {

		if ( ! function_exists( 'tpm_get_transient' ) || ! class_exists( 'TPM_License_Manager' ) ) {
			return array();
		}

		$pool = tpm_get_transient( TPM_License_Manager::NAME );

		return is_array( $pool ) ? $pool : array();
	}

	/**
	 * Whether a license was admin-deactivated for this site (so it must not be enabled from here).
	 *
	 * @param int $license_id
	 *
	 * @return bool
	 */
	protected function is_license_deactivated( $license_id ) {

		$pool = $this->get_raw_license_pool();
		$id   = (int) $license_id;

		return isset( $pool[ $id ] ) && is_array( $pool[ $id ] ) && ! empty( $pool[ $id ]['site_deactivated'] );
	}

	/**
	 * Index the cached get_licenses_details response by license id as TD_TTW_License instances.
	 * Reads the cached transient directly (no side-effecting live fetch).
	 *
	 * @return array id => TD_TTW_License
	 */
	protected function index_details_by_id() {

		$indexed = array();

		if ( ! function_exists( 'thrive_get_transient' ) || ! class_exists( 'TD_TTW_License' ) ) {
			return $indexed;
		}

		$details = thrive_get_transient( self::TD_DETAILS_TRANSIENT );

		if ( ! is_array( $details ) ) {
			return $indexed;
		}

		foreach ( $details as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				$indexed[ (int) $item['id'] ] = new TD_TTW_License( $item );
			}
		}

		return $indexed;
	}

	/**
	 * The cached get_licenses_details items keyed by id, as RAW arrays (no TD_TTW_License wrapper).
	 * Used for fields the wrapper may not surface through its magic getter (e.g. account_status).
	 *
	 * @return array id => raw detail array
	 */
	protected function get_raw_details_by_id() {

		$indexed = array();

		if ( ! function_exists( 'thrive_get_transient' ) ) {
			return $indexed;
		}

		$details = thrive_get_transient( self::TD_DETAILS_TRANSIENT );

		if ( ! is_array( $details ) ) {
			return $indexed;
		}

		foreach ( $details as $item ) {
			if ( is_array( $item ) && isset( $item['id'] ) ) {
				$indexed[ (int) $item['id'] ] = $item;
			}
		}

		return $indexed;
	}

	/**
	 * Map an integer license status to a human label.
	 *
	 * @param int|null $status
	 *
	 * @return string
	 */
	public function status_label( $status ) {

		switch ( (int) $status ) {
			case 1:
				return __( 'Active', Thrive_Product_Manager::T );
			case 3:
				return __( 'Refunded', Thrive_Product_Manager::T );
			case 9:
				return __( 'Pending cancellation', Thrive_Product_Manager::T );
			default:
				return null === $status ? __( 'Unknown', Thrive_Product_Manager::T ) : (string) $status;
		}
	}

	/**
	 * Everything we know about the connected account, with secrets masked. For the support dump.
	 *
	 * @return array
	 */
	public function get_diagnostic_dump() {

		$connection = class_exists( 'TPM_Connection' ) ? TPM_Connection::get_instance() : null;

		$dump = array(
			'connected'                                   => $connection ? $connection->is_connected() : false,
			'connection_expired'                          => $connection && $connection->is_connected() ? $connection->is_expired() : null,
			'connection (tpm_connection)'                 => $connection ? $connection->get_data() : array(),
			'license_details_cache (td_ttw_licenses_details)' => function_exists( 'thrive_get_transient' ) ? thrive_get_transient( self::TD_DETAILS_TRANSIENT ) : null,
			'license_usage_cache (tpm_ttw_licenses)'      => function_exists( 'tpm_get_transient' ) ? tpm_get_transient( 'tpm_ttw_licenses' ) : null,
			'activated_licenses (tpm_licenses option)'    => get_option( 'tpm_licenses', array() ),
			'legacy_thrive_license (thrive_license option)' => get_option( 'thrive_license', array() ),
			'last_connection_error'                       => function_exists( 'thrive_get_transient' ) ? thrive_get_transient( 'td_ttw_connection_error' ) : null,
			'connection_backup (tpm_bk_connection)'       => get_option( 'tpm_bk_connection', array() ),
			'license_lock (tpm_license_lock)'             => get_option( 'tpm_license_lock', '' ),
			'environment'                                 => $this->_diag_environment(),
			'connectivity'                                => $this->_diag_connectivity(),
			'license_summary'                             => $this->_diag_license_summary(),
			'scheduling'                                  => $this->_diag_scheduling(),
		);

		return $this->mask_secrets( $dump );
	}

	/**
	 * Site + platform context support asks for first - notably the exact URL used for activation
	 * (site_deactivated is per-URL) and any duplicate bundled Thrive Dashboard (the dual-Dashboard
	 * fatal source).
	 *
	 * @return array
	 */
	protected function _diag_environment() {

		if ( ! function_exists( 'get_plugins' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed = function_exists( 'get_plugins' ) ? get_plugins() : array();
		$thrive    = array();
		foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
			$name = isset( $installed[ $plugin ]['Name'] ) ? $installed[ $plugin ]['Name'] : '';
			if ( false !== stripos( $plugin, 'thrive' ) || false !== stripos( $name, 'thrive' ) ) {
				$thrive[ $plugin ] = isset( $installed[ $plugin ]['Version'] ) ? $installed[ $plugin ]['Version'] : '?';
			}
		}

		$dashboard_copies = array();
		$patterns         = array( get_theme_root() . '/*/thrive-dashboard/version.php' );
		if ( defined( 'WP_PLUGIN_DIR' ) ) {
			array_unshift( $patterns, WP_PLUGIN_DIR . '/*/thrive-dashboard/version.php' );
		}
		foreach ( $patterns as $pattern ) {
			foreach ( (array) glob( $pattern ) as $version_file ) {
				$rel                      = defined( 'WP_CONTENT_DIR' ) ? str_replace( WP_CONTENT_DIR, '', $version_file ) : $version_file;
				$dashboard_copies[ $rel ] = trim( (string) ( @include $version_file ) ); // phpcs:ignore
			}
		}

		return array(
			'site_url'                => get_site_url(),
			'home_url'                => get_home_url(),
			'wp_version'              => get_bloginfo( 'version' ),
			'php_version'             => PHP_VERSION,
			'is_multisite'            => is_multisite(),
			'is_ssl'                  => is_ssl(),
			'tpm_version'             => class_exists( 'Thrive_Product_Manager' ) ? Thrive_Product_Manager::V : null,
			'thrive_dashboard_loaded' => defined( 'TVE_DASH_VERSION' ) ? TVE_DASH_VERSION : null,
			'thrive_dashboard_copies' => $dashboard_copies,
			'multiple_dashboards'     => count( $dashboard_copies ) > 1,
			'active_thrive_plugins'   => $thrive,
		);
	}

	/**
	 * Whether the site can actually reach the proxy/TTW, plus a flag for a pre_http_request
	 * short-circuit (mu-plugin / host firewall) - most "can't connect" tickets are one of these.
	 *
	 * @return array
	 */
	protected function _diag_connectivity() {

		$proxy_base   = class_exists( 'TPM_Proxy_Request' ) ? TPM_Proxy_Request::URL : '';
		$reachability = array();

		if ( $proxy_base ) {
			$start = microtime( true );
			$resp  = wp_remote_get( $proxy_base, array( 'timeout' => 5 ) );
			$ms    = (int) round( ( microtime( true ) - $start ) * 1000 );

			$reachability = is_wp_error( $resp )
				? array( 'ok' => false, 'error' => $resp->get_error_message(), 'ms' => $ms )
				: array( 'ok' => true, 'http_code' => wp_remote_retrieve_response_code( $resp ), 'ms' => $ms );
		}

		return array(
			'ttw_url'                   => class_exists( 'Thrive_Product_Manager' ) ? Thrive_Product_Manager::get_ttw_url() : '',
			'proxy_endpoint'            => $proxy_base ? rtrim( $proxy_base, '/' ) . '/tpm/proxy' : '',
			'proxy_reachability'        => $reachability,
			'pre_http_request_filtered' => has_filter( 'pre_http_request' ) ? true : false,
		);
	}

	/**
	 * Flattened, human-readable per-license view (products, status, seats, deactivation) plus the
	 * state TPM concluded (usable ids + disconnect cause) - easier to scan than the raw caches.
	 *
	 * @return array
	 */
	protected function _diag_license_summary() {

		$manager = class_exists( 'TPM_License_Manager' ) ? TPM_License_Manager::get_instance() : null;
		if ( ! $manager ) {
			return array();
		}

		$pool = function_exists( 'tpm_get_transient' ) ? tpm_get_transient( 'tpm_ttw_licenses' ) : array();
		$pool = is_array( $pool ) ? $pool : array();

		$tag_names = array();
		try {
			if ( class_exists( 'TPM_Product_List' ) ) {
				foreach ( (array) TPM_Product_List::get_instance()->get_products_array() as $tag => $product ) {
					if ( is_array( $product ) && ! empty( $product['name'] ) ) {
						$tag_names[ $tag ] = $product['name'];
					}
				}
			}
		} catch ( Throwable $e ) {
			$tag_names = array();
		}

		$details       = function_exists( 'thrive_get_transient' ) ? thrive_get_transient( self::TD_DETAILS_TRANSIENT ) : array();
		$details_by_id = array();
		if ( is_array( $details ) ) {
			foreach ( $details as $detail ) {
				if ( isset( $detail['id'] ) ) {
					$details_by_id[ (int) $detail['id'] ] = $detail;
				}
			}
		}

		$rows = array();
		foreach ( $pool as $id => $entry ) {
			$names = array();
			foreach ( ( isset( $entry['tags'] ) && is_array( $entry['tags'] ) ? $entry['tags'] : array() ) as $tag ) {
				$names[] = ( 'all' === $tag ) ? 'All products' : ( isset( $tag_names[ $tag ] ) ? $tag_names[ $tag ] : $tag );
			}

			$detail = isset( $details_by_id[ (int) $id ] ) ? $details_by_id[ (int) $id ] : array();
			$used   = isset( $entry['usage']['used'] ) ? $entry['usage']['used'] : '?';
			$limit  = ! empty( $entry['usage']['limit'] ) ? $entry['usage']['limit'] : '∞';

			$rows[] = array(
				'id'               => (int) $id,
				'products'         => implode( ', ', $names ),
				'account_status'   => isset( $detail['account_status'] ) ? $detail['account_status'] : ( isset( $detail['status'] ) ? $detail['status'] : null ),
				'expiration'       => isset( $detail['expiration'] ) ? $detail['expiration'] : null,
				'seats_used_limit' => $used . ' / ' . $limit,
				'site_deactivated' => ! empty( $entry['site_deactivated'] ),
			);
		}

		$usable_ids = array();
		try {
			$usable_ids = array_keys( $manager->get_usable_licenses() );
		} catch ( Throwable $e ) {
			$usable_ids = array();
		}

		return array(
			'licenses'           => $rows,
			'usable_license_ids' => $usable_ids,
			'disconnect_cause'   => $manager->get_disconnect_notice(),
		);
	}

	/**
	 * Cron / cache-freshness context (token refresh schedule, WP-Cron status, server time and the
	 * usage-cache expiry) so support can reason about staleness vs the expiration dates above.
	 *
	 * @return array
	 */
	protected function _diag_scheduling() {

		$next_refresh = class_exists( 'TPM_Cron' ) ? wp_next_scheduled( TPM_Cron::CRON_HOOK_NAME ) : false;

		$pool_raw = get_option( '_thrive_tr_tpm_ttw_licenses' );
		$pool_exp = is_array( $pool_raw ) && isset( $pool_raw['exp'] ) ? date_i18n( 'Y-m-d H:i:s', (int) $pool_raw['exp'] ) : 'unknown';

		return array(
			'next_token_refresh'  => $next_refresh ? date_i18n( 'Y-m-d H:i:s', $next_refresh ) : 'not scheduled',
			'wp_cron_disabled'    => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
			'server_time'         => date_i18n( 'Y-m-d H:i:s' ),
			'server_timezone'     => function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : date_default_timezone_get(),
			'usage_cache_expires' => $pool_exp,
		);
	}

	/**
	 * Recursively mask any value whose key is a known secret.
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	protected function mask_secrets( $data ) {

		if ( ! is_array( $data ) ) {
			return $data;
		}

		$masked = array();

		foreach ( $data as $key => $value ) {
			if ( is_string( $key ) && in_array( strtolower( $key ), self::SECRET_KEYS, true ) ) {
				$masked[ $key ] = $this->mask_value( $value );
			} else {
				$masked[ $key ] = is_array( $value ) ? $this->mask_secrets( $value ) : $value;
			}
		}

		return $masked;
	}

	/**
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function mask_value( $value ) {

		$length = is_scalar( $value ) ? strlen( (string) $value ) : 0;

		return $length ? sprintf( '••• masked (%d chars) •••', $length ) : '••• masked •••';
	}

	/**
	 * Pretty-print a value for the diagnostic <pre>, escaped for output.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public function pretty( $value ) {

		return esc_html( wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}
}

TPM_License_Page::get_instance();

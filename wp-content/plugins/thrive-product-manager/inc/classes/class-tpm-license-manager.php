<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-product-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class TPM_License_Manager {

	const NAME = 'tpm_ttw_licenses';

	const CACHE_LIFE_TIME = 28800; //8 hours

	const DISCONNECT_NOTICE = 'tpm_disconnect_notice';

	/**
	 * @var TPM_License_Manager
	 */
	protected static $_instance;

	/**
	 * Array of tags set from old licensing system
	 * Used for backwards compatibility
	 *
	 * @var array
	 */
	protected $_thrive_license;

	/**
	 * List of all licenses the user has on ttw website
	 *
	 * @var array
	 */
	protected $_ttw_licenses = array();

	protected $ttw_license_instances = array();

	private function __construct() {

		$thrive_license            = get_option( 'thrive_license', array() );
		$this->_thrive_license = is_array( $thrive_license ) ? $thrive_license : array();
	}

	public static function get_instance() {

		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Checks if there is a license saved/used for current site and has $product tag
	 *
	 * @param $product TPM_Product
	 *
	 * @return bool
	 */
	public function is_licensed( TPM_Product $product ) {

		/** @var TPM_License $license */
		foreach ( $this->get_usable_licenses() as $license ) {
			if ( $license->has_tag( $product->get_tag() ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks in TTW licenses if there is one which has $product tag
	 *
	 * @param TPM_Product $product
	 *
	 * @return bool
	 */
	public function is_purchased( TPM_Product $product ) {

		/*
		 * Offer a product only when a license the site can ACTUALLY use covers it. "Usable" excludes
		 * licenses this url is deactivated on and is scoped to the active license when there is one -
		 * so with every owned suite deactivated, only the remaining valid license's products show,
		 * and the catalog can never offer something no non-deactivated license could license.
		 */
		foreach ( $this->get_usable_licenses() as $license ) {
			if ( $license->has_tag( $product->get_tag() ) ) {
				return true;
			}
		}

		//check old licenses (legacy pre-TPM local grant, independent of the EDD pool)
		$thrive_license = get_option( 'thrive_license', array() );
		$thrive_license = is_array( $thrive_license ) ? $thrive_license : array();

		return in_array( 'all', $thrive_license, false ) || in_array( $product->get_tag(), $thrive_license, false );
	}

	public function get_ttw_license_instances() {

		if ( ! empty( $this->ttw_license_instances ) ) {
			return $this->ttw_license_instances;
		}

		foreach ( $this->_get_ttw_licenses() as $license_id => $data ) {
			$instance                                   = new TPM_License( $license_id, $data['tags'], $data['usage']['used'], $data['usage']['max'] );
			$this->ttw_license_instances[ $license_id ] = $instance;
		}

		return $this->ttw_license_instances;
	}

	/**
	 * The licenses this site may actually use - highest-tier first, EXCLUDING any the account owner
	 * deactivated for this url (Manage Sites). Scoped to the site's enabled/active license set when
	 * that set still holds a usable license; otherwise (nothing active yet, or the active license was
	 * deactivated) it returns every usable owned license so the next valid highest-tier one takes
	 * over. Single source of truth for BOTH the catalog (is_purchased) and the install binding
	 * (get_product_license): a product is offered iff a non-deactivated license can license it.
	 *
	 * @return TPM_License[] license_id => TPM_License
	 */
	public function get_usable_licenses() {

		$pool   = $this->_get_ttw_licenses();
		$usable = array();

		/** @var TPM_License $license */
		foreach ( $this->get_ttw_license_instances() as $id => $license ) {
			if ( ! isset( $pool[ $id ] ) || empty( $pool[ $id ]['site_deactivated'] ) ) {
				$usable[ $id ] = $license;
			}
		}

		$enabled = TPM_License::get_saved_licenses();
		if ( $enabled ) {
			$scoped = array_intersect_key( $usable, $enabled );
			if ( $scoped ) {
				return $scoped;
			}
		}

		return $usable;
	}

	/**
	 * Based on current connection a request is made to TTW for assigned licenses
	 *
	 * @param TPM_Connection $connection
	 *
	 * @return array
	 */
	protected function _get_connection_licenses( TPM_Connection $connection ) {

		if ( ! $connection->is_connected() ) {
			return array();
		}

		$licenses = tpm_get_transient( self::NAME );

		if ( Thrive_Product_Manager::CACHE_ENABLED && $licenses !== false && is_array( $licenses ) ) {
			return $licenses;
		}

		$params = array(
			'user_id'       => $connection->ttw_id,
			/* Lets the server tag each license with `site_deactivated` for THIS site (Manage Sites). */
			'user_site_url' => get_site_url(),
		);

		$route   = '/api/v1/public/get_licenses';
		$request = new TPM_Request( $route, $params );
		$request->set_header( 'Authorization', $connection->ttw_salt );

		$proxy_request = new TPM_Proxy_Request( $request );
		$response      = $proxy_request->execute( '/tpm/proxy' );

		$body = wp_remote_retrieve_body( $response );
		$body = json_decode( $body, true );

		if ( ! is_array( $body ) || empty( $body['success'] ) ) {

			tpm_set_transient( self::NAME, array(), self::CACHE_LIFE_TIME );

			return array();
		}

		$licenses = $body['data'];
		$licenses = is_array( $licenses ) ? $licenses : array();

		//sort licenses so that the ones with 'all' tags will be 1st in list
		//so they have priority on usage
		uasort( $licenses, static function ( $license_a, $license_b ) {

			$a_tags = is_array( $license_a ) && ! empty( $license_a['tags'] ) && is_array( $license_a['tags'] ) ? $license_a['tags'] : array();
			$b_tags = is_array( $license_b ) && ! empty( $license_b['tags'] ) && is_array( $license_b['tags'] ) ? $license_b['tags'] : array();


			if ( in_array( 'all', $a_tags, true ) && in_array( 'all', $b_tags, true ) ) {
				return 0;
			}

			if ( false === in_array( 'all', $a_tags, true ) && in_array( 'all', $b_tags, true ) ) {
				return 1;
			}

			return - 1;
		} );

		tpm_set_transient( self::NAME, $licenses, self::CACHE_LIFE_TIME );

		return $licenses;
	}

	/**
	 * Searches in all licenses user has bought on TTW site
	 *
	 * @param TPM_Product $product
	 *
	 * @return int|null
	 */
	public function get_product_license( TPM_Product $product ) {

		/*
		 * Keep installs within the site's active license: once a license is registered, only it may
		 * be consumed, so installing a product can't silently pull a broader license from the owned
		 * pool and widen what the site uses. With nothing registered yet, use the full pool.
		 */
		/*
		 * Bind the highest-tier license that can actually license this product on this site.
		 * get_usable_licenses() already excludes any license this url is deactivated on (and scopes
		 * to the active license, falling back to the rest when that one is deactivated), so a
		 * deactivated license is never bound.
		 *
		 * @var TPM_License $license
		 */
		foreach ( $this->get_usable_licenses() as $license_id => $license ) {
			if ( $license->has_tag( $product->get_tag() ) && $license->get_used() < $license->get_max() ) {
				return $license_id;
			}
		}

		return null;
	}

	/**
	 * If $products have a license id assigned then
	 * - a request to TTW  is made to increase the usage of the license/licenses
	 *
	 * @param array $products tag
	 *
	 * @return array|bool
	 */
	public function activate_licenses( $products = array() ) {

		if ( empty( $products ) ) {
			return false;
		}

		$licenses_ids = array();
		$product_tags = array();

		/** @var TPM_Product $product */
		foreach ( $products as $product ) {
			$product_tags[ $product->get_tag() ] = false;

			$id = $product->get_license();

			if ( ! empty( $id ) ) {
				$licenses_ids[] = $id;
			}
		}

		$licenses_ids = array_filter( $licenses_ids );
		$licenses_ids = array_unique( $licenses_ids );

		if ( empty( $licenses_ids ) ) {
			return false;
		}

		$params  = array(
			'user_id'       => TPM_Connection::get_instance()->ttw_id,
			'user_site_url' => get_site_url(),
			'data'          => $licenses_ids,
		);
		$request = new TPM_Request( '/api/v1/public/license_uses', $params );
		$request->set_header( 'Authorization', TPM_Connection::get_instance()->ttw_salt );

		$proxy_request = new TPM_Proxy_Request( $request );
		$response      = $proxy_request->execute( '/tpm/proxy' );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( ! is_array( $result ) || ! isset( $result['success'] ) || ! isset( $result['data'] ) || ! is_array( $result['data'] ) ) {
			return false;
		}

		$ttw_licenses = $this->_get_ttw_licenses();

		foreach ( $result['data'] as $license_id => $activated ) {

			if ( ! array_key_exists( $license_id, $ttw_licenses ) ) {
				continue;
			}

			$license          = $ttw_licenses[ $license_id ];
			$license_instance = new TPM_License( $license_id, $license['tags'] );

			if ( $activated === true ) {
				$license_instance->save();
				//prepare response
				foreach ( $product_tags as $tag => $value ) {
					if ( $license_instance->has_tag( $tag ) ) {
						$product_tags[ $tag ] = true;
					}
				}
			}
		}

		return $product_tags;
	}

	/**
	 * @return array
	 */
	protected function _get_ttw_licenses() {

		if ( empty( $this->_ttw_licenses ) ) {
			$this->_ttw_licenses = $this->_get_connection_licenses( TPM_Connection::get_instance() );
		}

		return $this->_ttw_licenses;
	}

	/**
	 * @param $license_id
	 *
	 * @return TPM_License|null;
	 */
	public function get_license_instance( $license_id ) {

		$license_id = (int) $license_id;

		if ( empty( $license_id ) ) {
			return null;
		}

		$license = null;
		$list    = TPM_License::get_saved_licenses();

		/** @var TPM_License $item */
		foreach ( $list as $item_id => $item ) {
			if ( $item->get_id() === $license_id ) {
				$license = $item;
				break;
			}
		}

		return $license;
	}

	public function license_deactivate( WP_REST_Request $request ) {

		$authorization = $request->get_param( 'Authorization' );
		$connection    = TPM_Connection::get_instance();
		$tpm_token     = $connection->decrypt( get_option( 'tpm_token', null ) );

		if ( $authorization !== $tpm_token ) {
			return array(
				'success' => false,
				'message' => 'No permission',
			);
		}

		$deactivated = true;
		$message     = 'License deactivated with success';
		$response    = array(
			'success' => $deactivated,
			'message' => $message,
		);

		$license_id = (int) $request->get_param( 'id' );

		if ( empty( $license_id ) ) {
			$response['success'] = false;
			$response['message'] = 'Invalid param license id ' . $request->get_param( 'id' );

			return $response;
		}

		$license = $this->get_license_instance( $license_id );

		if ( ! ( $license instanceof TPM_License ) ) {
			$response['success'] = true;
			$response['message'] = "Couldn't find any license with ID " . $request->get_param( 'id' );

			return $response;
		}

		if ( $license->delete() !== true ) {
			$response['success'] = false;
			$response['message'] = "Couldn't not deactivate license " . $request->get_param( 'id' );
		}

		TPM_Product_List::get_instance()->clear_cache();
		self::get_instance()->clear_cache();

		return $response;
	}

	public function clear_cache() {

		return tpm_delete_transient( self::NAME );
	}

	/**
	 * Keep a connected site on a VALID active license, and report whether it should be disconnected.
	 * Called on every TPM page load (after the pool is refreshed):
	 *  - active license still valid                      -> 'noop'.
	 *  - active license set but every one is deactivated -> 'disconnect' (the account owner revoked
	 *      the license this site was using; do NOT silently hop to an unrelated one).
	 *  - no active license yet (e.g. just re-connected)  -> ASSIGN the highest-tier license the site
	 *      can use so the License Manager shows it pre-selected ('assigned'); 'disconnect' only if
	 *      there is no usable license at all.
	 *
	 * @return string 'noop' | 'assigned' | 'disconnect'
	 */
	public function reconcile_active_license() {

		$connection = TPM_Connection::get_instance();

		if ( ! $connection->is_connected() ) {
			return 'noop';
		}

		$pool = $this->_get_connection_licenses( $connection );
		if ( empty( $pool ) ) {
			return 'noop'; // no data - fail open, never disconnect on a blank
		}

		$enabled = TPM_License::get_saved_licenses();

		if ( $enabled ) {
			/* Keep the active license while at least one enabled license is still valid; if the
			   account owner deactivated the active license, disconnect (don't hop). */
			foreach ( array_keys( $enabled ) as $enabled_id ) {
				if ( isset( $pool[ $enabled_id ] ) && empty( $pool[ $enabled_id ]['site_deactivated'] ) ) {
					$this->clear_disconnect_notice();
					return 'noop';
				}
			}

			$this->_set_disconnect_notice( $pool );
			return 'disconnect';
		}

		/* No active license yet: assign the highest-tier usable (non-deactivated) license so the
		   License Manager pre-selects it. Disconnect only if the site has no usable license at all. */
		$usable = $this->get_usable_licenses();
		if ( empty( $usable ) ) {
			$this->_set_disconnect_notice( $pool );
			return 'disconnect';
		}

		$license_id = (int) array_key_first( $usable );
		update_option( TPM_License::NAME, array( $license_id => $usable[ $license_id ]->get_tags() ) );
		$this->clear_disconnect_notice();

		return 'assigned';
	}

	/**
	 * Reason the Connect screen should explain after a deactivation-driven disconnect, so it shows
	 * contextual guidance instead of the generic connect prompt. Empty string when there is nothing
	 * to explain (fresh install / normal disconnect).
	 *
	 * @return string '' | 'reactivate' | 'seat_full' | 'no_license'
	 */
	public function get_disconnect_notice() {

		$cause = get_option( self::DISCONNECT_NOTICE, '' );

		return is_string( $cause ) ? $cause : '';
	}

	public function clear_disconnect_notice() {

		delete_option( self::DISCONNECT_NOTICE );
	}

	/**
	 * Classify why this site has no usable license, from the pool we already fetched:
	 *  - 'no_license' : the account owns no license for this site;
	 *  - 'reactivate' : a deactivated license still has a free seat (just reactivate this site);
	 *  - 'seat_full'  : every deactivated license is at its site limit (free a seat first).
	 *
	 * @param array $pool raw connection-license pool (id => entry with 'site_deactivated' + 'usage').
	 */
	protected function _set_disconnect_notice( $pool ) {

		$deactivated = array();
		foreach ( (array) $pool as $entry ) {
			if ( ! empty( $entry['site_deactivated'] ) ) {
				$deactivated[] = $entry;
			}
		}

		if ( empty( $deactivated ) ) {
			$cause = 'no_license';
		} else {
			$has_free_seat = false;
			foreach ( $deactivated as $entry ) {
				$limit = isset( $entry['usage']['limit'] ) ? (int) $entry['usage']['limit'] : 0;
				$used  = isset( $entry['usage']['used'] ) ? (int) $entry['usage']['used'] : 0;
				if ( $limit <= 0 || $used < $limit ) {
					$has_free_seat = true;
					break;
				}
			}
			$cause = $has_free_seat ? 'reactivate' : 'seat_full';
		}

		update_option( self::DISCONNECT_NOTICE, $cause );
	}

	/**
	 * Enable a single license on this site by id.
	 *
	 * Records this license as the active local selection so is_licensed() picks it up.
	 * Does NOT change TTW seat usage - switching the active license is a read-only selection here;
	 * seat counting is EDD's job on the site. The license must exist in the purchased TTW pool
	 * (get_ttw_license_instances()).
	 *
	 * @param int $license_id
	 *
	 * @return bool true when the license was enabled locally
	 */
	public function enable_license( $license_id ) {

		$license_id = (int) $license_id;
		$ttw        = $this->get_ttw_license_instances();

		if ( empty( $license_id ) || ! isset( $ttw[ $license_id ] ) ) {
			return false;
		}

		/*
		 * Hard block: never re-enable a license the account owner deactivated for THIS site in the
		 * Manage Sites area. The server advertises that per-license via `site_deactivated`
		 * (request-v2.php refuses the reactivation server-side anyway). Read it straight off the raw
		 * cached pool, since get_ttw_license_instances() keeps only tags/seats.
		 */
		$pool = $this->_get_ttw_licenses();
		if ( isset( $pool[ $license_id ] ) && is_array( $pool[ $license_id ] ) && ! empty( $pool[ $license_id ]['site_deactivated'] ) ) {
			return false;
		}

		/* Local only: record this license as the active selection. TPM does NOT change seat usage on
		   the account - switching is a read-only selection here; seat counting is EDD's job on the site. */
		/** @var TPM_License $license */
		$license = $ttw[ $license_id ];
		$license->save();

		return true;
	}

	/**
	 * Disable a single license on this site by id.
	 *
	 * Local only: removes the license from the local active selection. Does NOT touch TTW seat
	 * usage - switching the active license must never deactivate the site on the old license
	 * (seat counting is EDD's job on the site, not TPM's).
	 *
	 * @param int $license_id
	 *
	 * @return bool true when the license was removed locally
	 */
	public function disable_license( $license_id ) {

		$license_id = (int) $license_id;
		$license    = $this->get_license_instance( $license_id );

		if ( empty( $license_id ) || ! ( $license instanceof TPM_License ) ) {
			return false;
		}

		$license->delete();

		return true;
	}

	/**
	 * Deletes the local saved licenses
	 * - increments the usages for licenses by doing a request to TTW
	 */
	public function deactivate_all_licenses() {

		$licenses = TPM_License::get_saved_licenses();

		if ( empty( $licenses ) ) {
			return;
		}

		$connection = TPM_Connection::get_instance();

		//if user has disconnected TPM then try to use the backup connection saved at disconnecting
		if ( false === $connection->is_connected() ) {
			$connection->set_data( get_option( 'tpm_bk_connection', array() ) );
		}

		$params  = array(
			'user_id'       => $connection->ttw_id,
			'user_site_url' => get_site_url(),
			'direction'     => 'down',
			'data'          => array_keys( $licenses ),
		);
		$request = new TPM_Request( '/api/v1/public/license_uses', $params );
		$request->set_header( 'Authorization', $connection->ttw_salt );

		$proxy_request = new TPM_Proxy_Request( $request );
		$response      = $proxy_request->execute( '/tpm/proxy' );
		TPM_Log_Manager::get_instance()->set_message( var_export( $response, true ) )->log();
		delete_option( TPM_License::NAME );
	}
}

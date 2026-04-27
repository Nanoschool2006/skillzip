<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Public_API;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * API Token model for dashboard-level token authentication.
 *
 * Manages tokens stored in the wp_tva_tokens table.
 * This class was moved from Thrive Apprentice (TVA_Token) to Dashboard
 * so that API authentication works independently of any product plugin.
 *
 * Note: The table name and salt option names retain their 'tva_' prefix
 * for backward compatibility with existing tokens.
 */
class TD_API_Token {

	/**
	 * @var int
	 */
	private $_id;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	private $_key;

	/**
	 * Flag for enabled/disabled
	 *
	 * @var int 0 or 1
	 */
	private $_status;

	/**
	 * @var string|null
	 */
	private $_created_at;

	/**
	 * Code salt for key encoding.
	 * Kept identical to the original TVA_Token value for backward compatibility.
	 *
	 * @var string
	 */
	private $_code_salt = '123[ThriveApprentice]321';

	/**
	 * @var array
	 */
	private $_defaults = array(
		'id'         => null,
		'name'       => null,
		'key'        => null,
		'status'     => 1,
		'created_at' => null,
	);

	/**
	 * @param int|string|array $data
	 */
	public function __construct( $data ) {

		if ( ! is_array( $data ) ) {
			$this->_init_from_db( $data );

			return;
		}

		$args = array_merge( $this->_defaults, $data );

		$this->_id         = $args['id'] !== null ? (int) $args['id'] : null;
		$this->name        = $args['name'];
		$this->_key        = empty( $data['key'] ) ? tve_dash_generate_api_key() : $this->_decode_key( $data['key'] );
		$this->_status     = $args['status'];
		$this->_created_at = $args['created_at'];
	}

	/**
	 * Save current token data into DB
	 *
	 * @return int|true|WP_Error int id for new token inserted with success; true for success update; WP_Error for error insert or update
	 */
	public function save() {

		return is_int( $this->_id ) ? $this->_update() : $this->_insert();
	}

	/**
	 * Save a new token into db
	 *
	 * @return int|WP_Error
	 */
	protected function _insert() {

		global $wpdb;

		$table_name = static::_get_table_name();
		$created_at = current_time( 'mysql' );
		$data       = array(
			'key'        => $this->_encode_key( $this->_key ),
			'name'       => $this->_prepare_name_for_db( $this->name ),
			'status'     => $this->_status,
			'created_at' => $created_at,
		);

		$data = array_merge( $this->_defaults, $data );

		$id = (int) $wpdb->insert( $table_name, $data );

		if ( $id > 0 ) {
			$this->_id         = $wpdb->insert_id;
			$this->_created_at = $created_at;

			return $this->_id;
		}

		return new WP_Error( 'token_not_added', __( 'Token could not be added into DB', 'thrive-dash' ) );
	}

	/**
	 * @return true|WP_Error
	 */
	protected function _update() {

		global $wpdb;

		$table_name = static::_get_table_name();
		$data       = array(
			'name'   => $this->_prepare_name_for_db( $this->name ),
			'key'    => $this->_encode_key( $this->_key ),
			'status' => (int) $this->_status,
		);
		$where      = array( 'id' => $this->_id );

		$updated = $wpdb->update( $table_name, $data, $where );

		return false === $updated ? new WP_Error( 'token_not_saved', __( 'Token could not be saved', 'thrive-dash' ) ) : true;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function _encode_key( $key ) {

		$db_salt = $this->_get_db_salt();

		return $db_salt . $key . $this->_code_salt;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	protected function _decode_key( $key ) {

		$key = str_replace( $this->_code_salt, '', $key );
		$key = str_replace( $this->_get_db_salt(), '', $key );

		return $key;
	}

	/**
	 * @param string $name
	 *
	 * @return string
	 */
	protected function _prepare_name_for_db( $name ) {
		return sanitize_text_field( $name );
	}

	/**
	 * Read from DB a specific Token based on id or key
	 *
	 * @param int|string $search_key
	 */
	protected function _init_from_db( $search_key ) {

		global $wpdb;

		$table_name = static::_get_table_name();
		$sql        = "SELECT * FROM $table_name WHERE id = %d OR `key` = %s";

		$id  = is_int( $search_key ) ? $search_key : 0;
		$row = $wpdb->get_row( $wpdb->prepare( $sql, $id, $this->_encode_key( $search_key ) ) );

		if ( ! empty( $row ) ) {
			$this->_id         = (int) $row->id;
			$this->name        = $row->name;
			$this->_key        = $this->_decode_key( $row->key );
			$this->_status     = (int) $row->status;
			$this->_created_at = $row->created_at ?? null;
		}
	}

	/**
	 * Get the full table name with WordPress prefix.
	 *
	 * Retains the 'tva_tokens' name for backward compatibility
	 * with existing Apprentice installations.
	 *
	 * @return string
	 */
	protected static function _get_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'tva_tokens';
	}

	/**
	 * Reads salt from db.
	 * If not exists, generates one and saves it.
	 *
	 * Note: Option name retains 'tva_' prefix for backward compatibility.
	 *
	 * @return string
	 */
	protected function _get_db_salt() {

		$option_name = 'tva_db_token_salt';
		$salt        = get_option( $option_name, null );

		if ( empty( $salt ) ) {
			$salt = md5( time() );
			update_option( $option_name, $salt );
		}

		return $salt;
	}

	/**
	 * Expose instance properties
	 *
	 * @return array
	 */
	public function get_data() {

		return array(
			'id'         => $this->_id ? (int) $this->_id : $this->_id,
			'name'       => $this->name,
			'key'        => $this->_key,
			'status'     => (int) $this->_status,
			'created_at' => $this->_created_at,
		);
	}

	/**
	 * Check if current token is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {

		return (int) $this->_status !== 0;
	}

	/**
	 * @return int|true|WP_Error
	 */
	public function enable() {

		$this->_status = 1;

		return $this->save();
	}

	/**
	 * @return int|true|WP_Error
	 */
	public function disable() {

		$this->_status = 0;

		return $this->save();
	}

	/**
	 * @return int
	 */
	public function get_id() {

		return $this->_id;
	}

	/**
	 * @return string
	 */
	public function get_key() {

		return $this->_key;
	}

	/**
	 * @return true|WP_Error
	 */
	public function delete() {

		global $wpdb;

		$table_name = static::_get_table_name();
		$where      = array(
			'id' => $this->_id,
		);

		$deleted = (int) $wpdb->delete( $table_name, $where );

		return $deleted > 0 ? true : new WP_Error( 'token_not_deleted', __( 'Token could not be deleted', 'thrive-dash' ) );
	}

	/**
	 * @param string $type of return ARRAY_A|OBJECT
	 *
	 * @return array|TD_API_Token[]
	 */
	public static function get_items( $type = ARRAY_A ) {
		$tokens = array();

		global $wpdb;
		$table_name = static::_get_table_name();

		$results = $wpdb->get_results(
			"SELECT * FROM $table_name ORDER BY id"
		);

		if ( ! empty( $results ) ) {
			foreach ( $results as $item ) {
				$temp     = new static( (array) $item );
				$tokens[] = ARRAY_A === $type ? $temp->get_data() : $temp;
			}
		}

		return $tokens;
	}

	/**
	 * Validate an API token by password.
	 *
	 * @param string $username Not used, kept for interface compatibility.
	 * @param string $password The token key to validate.
	 *
	 * @return bool
	 */
	public static function auth( $username, $password ) {

		$token = new static( $password );

		$id = $token->get_id();

		return ! empty( $id ) && $token->is_enabled();
	}
}

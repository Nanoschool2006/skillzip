<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class TVA_Logger
 */
class TVA_Logger {
	/**
	 * @var WP_Query|wpdb
	 */
	private static $wpdb;

	/**
	 * @var string
	 */
	private static $debug_table_name;

	/**
	 * @var string
	 */
	private static $type = 'Error';

	/**
	 * @var null
	 */
	private static $instance = null;

	/**
	 * @var string
	 */
	private static $product = 'Thrive Apprentice';

	/**
	 * TTW_Debugger constructor.
	 */
	public function __construct() {
		global $wpdb;

		self::$wpdb             = $wpdb;
		self::$debug_table_name = self::$wpdb->prefix . 'thrive_debug';

		$this->create_debug_table();
	}

	/**
	 * Create an instance
	 *
	 * @return null|TVA_Logger
	 */
	public static function instance() {

		// Check if instance is already exists
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Log a row in the DB
	 *
	 * The $identifier should be the name for the row we're setting up
	 *
	 * @param       $identifier
	 * @param array $data
	 * @param bool  $to_db
	 * @param null  $date
	 * @param null  $type
	 */
	public static function log( $identifier, $data = array(), $to_db = false, $date = null, $type = null ) {

		if ( defined( 'TVE_UNIT_TESTS_RUNNING' ) ) {
			return;
		}
		self::instance();

		/**
		 * Set the date if we don't have one
		 */
		if ( ! $date ) {
			$date = date( 'Y-m-d H:i:s' );
		}

		/**
		 * Set the type if we have one set
		 */
		if ( ! $type ) {
			$type = self::get_type();
		}

		foreach ( $data as $key => $value ) {
			if ( is_object( $value ) ) {
				$data[ $key ] = (array) $value;
			}
		}
		$data = tve_sanitize_data_recursive( $data );

		$_data = array(
			'type'       => $type,
			'identifier' => $identifier,
			'product'    => self::$product,
			'data'       => $data,
			'date'       => $date,
		);

		if ( $to_db ) {
			$_data['data'] = maybe_serialize( $data );

			self::$wpdb->insert(
				self::$debug_table_name,
				$_data,
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				)
			);

			return;
		}
	}

	/**
	 * @param array $filters
	 *
	 * @return array|null|object
	 * @deprecated
	 */
	public static function get_logs( $filters = array() ) {
		self::instance();

		/*
		 * $filters is the raw `settings` parameter of POST /tva/v1/logs/get_logs/, which declares no
		 * args, so nothing validates it before it arrives here. Every clause below is therefore built
		 * from placeholders and the values are bound - previously they were interpolated into
		 * hand-written quotes, inside the string that is then handed to prepare() as its FORMAT
		 * argument, so prepare() sanitized none of it.
		 *
		 * $placeholders is filled in the same order the clauses appear in the final query. That
		 * ordering matters now in a way it did not before: the only placeholder used to be the LIMIT,
		 * so its position at the head of the array happened to line up.
		 *
		 * Mirrors fetch_logs() below, which is the non-deprecated equivalent and was already correct.
		 */
		if ( ! is_array( $filters ) ) {
			$filters = array();
		}

		$where        = '';
		$placeholders = array();

		if ( ! empty( $filters['types'] ) && is_array( $filters['types'] ) ) {
			$where = ' WHERE type IN ( ' . implode( ', ', array_fill( 0, count( $filters['types'] ), '%s' ) ) . ' )';

			foreach ( $filters['types'] as $type ) {
				$placeholders[] = $type;
			}
		}

		if ( ! empty( $filters['s'] ) && is_scalar( $filters['s'] ) ) {
			/* esc_like() first, so a % or _ in the search term is matched literally rather than as a wildcard. */
			$like = '%' . self::$wpdb->esc_like( (string) $filters['s'] ) . '%';

			/* type is only searched when it is not already constrained above - same as fetch_logs(). */
			$columns = empty( $where ) ? array( 'type', 'product', 'date' ) : array( 'product', 'date' );
			$clause  = array();

			foreach ( $columns as $column ) {
				$clause[]       = "`$column` LIKE %s";
				$placeholders[] = $like;
			}

			$where .= ( '' === $where ? ' WHERE ' : ' AND ' ) . '( ' . implode( ' OR ', $clause ) . ' )';
		}

		if ( ! isset( $filters['limit'] ) ) {
			$limit          = ' LIMIT %d';
			$placeholders[] = 20;
		} else {
			$limit          = ' LIMIT %d, %d';
			$placeholders[] = (int) $filters['limit'];
			$placeholders[] = (int) $filters['limit'] + 20;
		}

		$query = 'SELECT * FROM ' . self::$debug_table_name . $where . ' ORDER BY date DESC' . $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is assembled from literals and placeholders only; every value is bound through $placeholders.
		$logs = self::$wpdb->get_results( self::$wpdb->prepare( $query, $placeholders ) );

		if ( is_array( $logs ) ) {
			foreach ( $logs as $log ) {
				$log->data = maybe_unserialize( $log->data );
				$log->data = tve_sanitize_data_recursive( $log->data );
			}
		}

		return $logs;
	}

	/**
	 * Used for fetching the logs
	 *
	 * @param array   $filters
	 * @param boolean $count
	 *
	 * @return array|int
	 */
	public static function fetch_logs( $filters = array(), $count = false ) {
		self::instance();

		$where        = ' WHERE 1=1';
		$placeholders = array();


		$filters = array_merge(
			array(
				'offset' => 0,
				'limit'  => 10,
				's'      => '',
				'types'  => array(),
			),
			$filters
		);

		if ( ! empty( $filters['types'] ) && is_array( $filters['types'] ) ) {
			$where .= ' AND type IN ( ';
			foreach ( $filters['types'] as $type ) {
				$where .= '%s,';

				$placeholders[] = sanitize_text_field( $type );
			}
			//We remove the last comma
			$where = rtrim( $where, ',' ) . ')';
		}

		if ( ! empty( $filters['s'] ) ) {
			$type_clause = '';
			if ( empty( $filters['types'] ) ) {
				$type_clause    = " type LIKE '%%%s%%' OR";
				$placeholders[] = $filters['s'];
			}
			$where .= ' AND (' . $type_clause . " product LIKE '%%%s%%' OR date LIKE '%%%s%%' )";

			$placeholders[] = $filters['s'];
			$placeholders[] = $filters['s'];
		}

		$limit = '';
		if ( false === $count ) {
			$limit          = ' LIMIT %d, %d';
			$placeholders[] = $filters['offset'];
			$placeholders[] = $filters['limit'];
		}
		$query = 'SELECT ' . ( $count ? 'COUNT(id)' : '*' ) . ' FROM ' . self::$debug_table_name . $where . ' ORDER BY date DESC ' . $limit . ';';

		$prepared = empty( $placeholders ) ? $query : self::$wpdb->prepare( $query, $placeholders );

		if ( $count ) {
			$logs = (int) self::$wpdb->get_var( $prepared );
		} else {
			$logs = self::$wpdb->get_results( $prepared );

			if ( is_array( $logs ) ) {
				foreach ( $logs as $log ) {
					/**
					 * On PHP 8.1+ unserialize function returns errors such as the one bellow when unserialize objects
					 * PHP Fatal error:  Uncaught TypeError: Cannot assign null to property mysqli_result::$current_field of type int in
					 */
//					$log->data = tve_sanitize_data_recursive( maybe_unserialize( $log->data ) );
					$log->data = '';
				}
			}
		}

		return $logs;
	}

	/**
	 * Return the types for the logs
	 *
	 * @return array|null|object
	 */
	public static function get_log_types() {
		self::instance();
		$types = array();
		$data  = self::$wpdb->get_results(
			self::$wpdb->prepare(
				'SELECT DISTINCT type FROM ' . self::$debug_table_name . ' WHERE 1 = %s', array( 1 )
			)
		);

		if ( ! empty( $data ) ) {
			foreach ( $data as $key => $type ) {
				$types[] = array(
					'id'   => $key,
					'type' => $type->type,
				);
			}
		}

		return $types;
	}

	/**
	 * Set the product
	 *
	 * @param $product
	 */
	public static function set_product( $product ) {
		/**
		 * make sure we already have the instance
		 */
		self::instance();

		self::$product = $product;
	}

	/**
	 * Return the product
	 *
	 * @return string
	 */
	public static function get_product() {
		/**
		 * make sure we already have the instance
		 */
		self::instance();

		return self::$product;
	}

	/**
	 * Set the type
	 *
	 * @param $type
	 */
	public static function set_type( $type ) {
		/**
		 * make sure we already have the instance
		 */
		self::instance();

		self::$type = $type;
	}

	/**
	 * Return the type
	 *
	 * @return string
	 */
	public static function get_type() {
		/**
		 * make sure we already have the instance
		 */
		self::instance();

		return self::$type;
	}

	/**
	 * Create the the debugging table if it doesn't exist
	 */
	private function create_debug_table() {
		if ( self::$wpdb->get_var( "SHOW TABLES LIKE '" . self::$debug_table_name . "'" ) !== self::$debug_table_name ) {
			$charset_collate = self::$wpdb->get_charset_collate();

			$sql = 'CREATE TABLE IF NOT EXISTS ' . self::$debug_table_name . ' (
			  id mediumint(9) NOT NULL AUTO_INCREMENT,
			  `type` varchar(60) NOT NULL,
			  `identifier` TEXT NOT NULL,
			  product TEXT NOT NULL,
			  `data` TEXT NOT NULL,
			  `date` datetime DEFAULT "0000-00-00 00:00:00" NOT NULL,
			  PRIMARY KEY  (id)
			)' . $charset_collate . ';';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}
}

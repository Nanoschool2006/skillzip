<?php

namespace TVA\Access;

// TODO Separate general functionality from specific one
class History_Table {

	/**
	 * Maybe rename this
	 */
	use \TD_Singleton;

	/**
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * Table name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Column formats
	 *
	 * @var string[]
	 */
	private $format = [
		'user_id'    => '%d',
		'product_id' => '%d',
		'course_id'  => '%d',
		'source'     => '%s',
		'status'     => '%d',
		'created'    => '%s',
	];

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->name = $wpdb->prefix . 'tva_' . static::get_table_name();
	}

	/**
	 * Used for initial migration and in prepare table function
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'access_history';
	}

	/**
	 * @param array $data
	 * @param bool  $update Whether to update existing records instead of inserting new ones
	 *
	 * @return array|false Strings containing the results of the various update queries.
	 */
	public function insert_or_update_multiple( $data = [], $update = false ) {
		if ( empty( $data ) ) {
			return false;
		}

		$created = gmdate( 'Y-m-d H:i:s' );
		$values  = [];
		$queries = [];

		if ( $update ) {
			$header = "UPDATE $this->name SET status = CASE ";
			$where  = " WHERE (user_id, product_id, course_id) IN (";
		} else {
			$header = "INSERT INTO $this->name (user_id, product_id, course_id, source, status, created, reason) VALUES ";
		}

		foreach ( $data as $info ) {
			$created_info = empty( $info['created'] ) ? $created : $info['created'];
			$reason       = empty( $info['reason'] ) ? 'NULL' : (int) $info['reason'];

			if ( strpos( $created_info, 'SELECT' ) !== false ) {
				/**
				 * Ability to add created info from a select - dynamically on migration
				 */
				$created_info = "($created_info)";
			} else {
				$created_info = "'" . $created_info . "'";
			}

			if ( $update ) {
				$values[] = "WHEN (user_id = " . $info['user_id'] . " AND product_id = " . $info['product_id'] . " AND course_id = " . $info['course_id'] . ") THEN " . $info['status'];
				$where_values[] = "(" . $info['user_id'] . ", " . $info['product_id'] . ", " . $info['course_id'] . ")";
			} else {
				$values[] = '(' . $info['user_id'] . ', ' . $info['product_id'] . ', ' . $info['course_id'] . ",'" . $info['source'] . "', " . $info['status'] . ", $created_info, '" . $reason . "')";
			}
		}

		if ( $update ) {
			$queries[] = $header . implode( ' ', $values ) . " END" . $where . implode( ', ', $where_values ) . ")";
		} else {
			$values_chunk = array_chunk( $values, 7000 );
			foreach ( $values_chunk as $chunk ) {
				$queries[] = $header . implode( ' , ', $chunk );
			}
		}

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		return dbDelta( implode( ';', $queries ) );
	}

	/**
	 * @param array $data
	 *
	 * @return int|false The number of rows inserted, or false on error.
	 */
	public function insert( $data = [] ) {

		if ( empty( $data['user_id'] ) || empty( $data['course_id'] ) || empty( $data['source'] ) ) {
			/**
			 * user_id - is a mandatory field for the history table
			 * course_id - For now we only log data for course access change
			 */
			return false;
		}

		$data['created'] = gmdate( 'Y-m-d H:i:s' );

		$format = [];
		foreach ( $data as $key => $value ) {
			$format[] = isset( $this->format[ $key ] ) ? $this->format[ $key ] : '%s';
		}

		return $this->wpdb->insert( $this->name, $data, $format );
	}

	public function update( $product_id = 0, $user_id = 0 ) {
		$wpdb = $this->wpdb;
		$sql = "UPDATE $this->name SET status = -1 WHERE product_id = %d AND user_id = %d AND status = 1";
		return $wpdb->query( $wpdb->prepare( $sql, $product_id, $user_id ) );
	}

	/**
	 * @param array $where
	 *
	 * @return int|false The number of rows deleted, or false on error.
	 */
	public function delete( $where = [] ) {
		if ( empty( $where ) ) {
			return false;
		}

		return $this->wpdb->delete( $this->name, $where );
	}

	public function get_course_enrollments( $filters = [] ) {
		$sub_query = $this->build_report_query( [
			'select'   => [
				'status',
				'created',
				'user_id',
				'course_id',
			],
			'where'    => $filters,
			'having'   => 'status > 0'
		] );

		$query = "SELECT SUM(status) as status, DATE(created) as created, user_id, course_id FROM ($sub_query) as tmp GROUP BY DATE(created), course_id";

		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	public function get_number_of_entries( $filters = [] ) {
		$query = $this->build_report_query( [
			'select'   => [
				'user_id',
				'SUM(status) as status',
			],
			'where'    => $filters,
			'group_by' => [ 'user_id' ],
		] );

		$results = $this->wpdb->get_results( $query, ARRAY_A );

		$user_ids = [];
		if ( ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as $result ) {
				$user_ids[ $result['user_id'] ] = $result['status'];
			}
		}

		return $user_ids;
	}

	public function get_course_enrollments_table( $query = [], $new_member_query = false ) {
		if ( empty( $query['select'] ) ) {
			$query['select'] = [ 'status', 'source', 'created', 'user_id', 'course_id', 'product_id' ];
		}
		// Remove the status filter to show all enrollments
		// $query['having'] = 'status > 0';

		$query = $this->build_report_query( $query, $new_member_query );

		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	public function get_course_enrollment_dates( $filters = [] ) {
		$query = $this->build_report_query( [
			'select'   => [
				'course_id',
				'DATE(created) as created',
				'COUNT(DISTINCT(user_id)) as count',
			],
			'where'    => $filters,
			'group_by' => [ 'course_id' ],
			'having'   => 'SUM(status) > 0',
		] );

		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get the total count of students based on filters
	 *
	 * When min_courses_count is set (e.g., via tva_hide_members_no_courses option),
	 * only counts members who have at least that many active courses.
	 * Members with only expired courses (status <= 0) are excluded by the SUM(status) > 0 filter.
	 *
	 * @param array $filters
	 * @param bool  $new_member_query
	 *
	 * @return int
	 */
	public function get_total_students( $filters = [], $new_member_query = false ) {
		// Use a unified approach for both cases to ensure consistency

		// First, get all users with any active enrollment (consistent baseline)
		$base_user_query = $this->build_report_query( [
			'select'   => [
				'user_id',
			],
			'where'    => $filters,
			'group_by' => [ 'user_id' ],
			'having'   => 'SUM(status) > 0',
		], $new_member_query );

		// Join with wp_users to filter out non-existent users at the SQL level (more efficient)
		// Using esc_sql() for WordPress core table name as extra safety measure
		$users_table = esc_sql( $this->wpdb->users );
		$query_count = "SELECT COUNT(*) as number FROM ($base_user_query) as test INNER JOIN {$users_table} ON test.user_id = {$users_table}.ID WHERE test.user_id > 0";
		
		$count = $this->wpdb->get_row( $query_count, ARRAY_A );

		return (int) $count['number'];
	}

	public function get_students( $filters = [], $new_member_query = false ) {
		$column = 'created';
		$select = [
			'user_id',
			'DATE(created) as created',
		];
		
		if ( $new_member_query ) {
			$column = 'user_registered';
			unset( $select[1] );
		}

		$query = $this->build_report_query( [
			'select'   => $select,
			'where'    => $filters,
			'group_by' => [ 'user_id' ],
			'having'   => 'SUM(status) > 0',
		], $new_member_query );

		$query_count = "SELECT COUNT($column) as number, $column FROM ($query) as tmp GROUP BY ($column)";

		return $this->wpdb->get_results( $query_count, ARRAY_A );
	}

	public function get_top_students( $filters = [] ) {
		$query = $this->build_report_query( [
			'select'   => [
				'user_id',
				'SUM(status) as sum',
			],
			'where'    => $filters,
			'group_by' => [
				'user_id',
				'course_id',
			],
			'having'   => 'sum > 0',
		] );

		$results = $this->wpdb->get_results( "SELECT user_id, COUNT(user_id) as number FROM ($query) as tmp GROUP BY user_id ORDER BY number DESC", ARRAY_A );

		$user_ids = [];
		if ( ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as $result ) {
				$user_ids[ $result['user_id'] ] = $result['number'];
			}
		}

		return $user_ids;
	}

	public function get_average_products( $filters = [] ) {
		$users_with_products = $this->build_report_query( [
			'select'   => [ 'user_id', 'product_id', 'created' ],
			'where'    => $filters,
			'group_by' => [ 'product_id', 'user_id' ],
			'having'   => 'SUM(status) > 0',
		] );
		$nb_of_products      = "SELECT users.user_id, COUNT(users.product_id) as products FROM ($users_with_products) as users GROUP BY users.user_id";

		return $this->wpdb->get_row( "SELECT AVG(product_numbers.products) as average FROM ($nb_of_products) as product_numbers", ARRAY_A );
	}

	/**
	 * Get all the customers that have access
	 *
	 * @param int $user_id
	 *
	 * @return array|int|object|\stdClass[]|null
	 */
	public function get_student( $user_id ) {
		if ( is_numeric( $user_id ) ) {
			$result = $this->get_all_students( [
				'filters' => array(
					'user_id' => array( $user_id ),
				),
			] );

			if ( ! empty( $result[0] ) ) {
				return $result[0];
			}
		}

		return null;
	}

	/**
	 * Get all the customers that have access
	 *
	 * The inner query filters by SUM(status) > 0, which means only active enrollments are counted.
	 * Members with only expired courses (status <= 0) will have no rows in the inner query results,
	 * resulting in courses_count = 0 in the outer query.
	 *
	 * When min_courses_count is set (e.g., via tva_hide_members_no_courses option),
	 * the HAVING clause filters out members with courses_count < min_courses_count.
	 * This effectively hides members with 0 active courses, including those with only expired courses.
	 *
	 * @param null|array $args
	 *
	 * @return array|int|object|\stdClass[]
	 */
	public function get_all_students( $args = [] ) {
		/* the inner query returns multiple results per user based on how many products / courses he has access to
		so in the outer query we must get the oldest enrolled date which represents the user's joined date */

		// Join with wp_users to get user_registered date
		// Using esc_sql() for WordPress core table name as extra safety measure
		$users_table = esc_sql( $this->wpdb->users );
		
		$outer_query = "SELECT access.ID, COUNT(DISTINCT(access.course_id)) as courses_count, min(DATE_FORMAT(access.enrolled, '%d.%m.%Y')) as enrolled, u.user_registered FROM (";

		$query = $this->build_report_query( [
			'select'   => [
				'user_id AS ID',
				'course_id',
				'MAX(created) AS enrolled',
				'MIN(created) AS min_created',
			],
			'where'    => empty( $args['filters'] ) ? '' : $args['filters'],
			'group_by' => [ 'user_id', 'product_id', 'course_id' ],
			'having'   => 'SUM(status) > 0',
		] );

		$outer_query .= $query;

		// Join with wp_users to filter out non-existent users at the SQL level
		// This is more efficient than post-filtering as it avoids N additional get_userdata() queries
		// The JOIN uses wp_users.ID PRIMARY KEY index for fast lookups
		$outer_query .= ") as access INNER JOIN {$users_table} u ON access.ID = u.ID WHERE access.ID > 0 GROUP BY access.ID";

		if ( ! empty ( $args['order_by'] ) ) {
			$outer_query .= $this->build_order_by_clause( $args['order_by'] );
		}

		if ( ! empty ( $args['limit'] ) ) {
			// Sanitize limit values to prevent SQL injection
			$limit_offset = absint( $args['limit']['offset'] ?? 0 );
			$limit_count = absint( $args['limit']['limit'] ?? 10 );
			$outer_query .= $this->wpdb->prepare( ' LIMIT %d, %d', $limit_offset, $limit_count );
		}
		
		$results = $this->wpdb->get_results( $outer_query, ARRAY_A );
		
		return $results;
	}

	/**
	 * Get the products the user has access to based on some type of role
	 *
	 * @param $user_id
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_role_accesses( $user_id ) {
		$query = $this->build_report_query( [
			'select'   => [
				'user_id as ID',
				'product_id',
				'source',
				'MAX(created) as enrolled',
			],
			'where'    => [
				'user_id' => [ $user_id ],
				'source'  => [ 'sendowl_product', 'wishlist', 'memberpress', 'wordpress', 'membermouse', 'membermouse_bundle' ],
			],
			'group_by' => [ 'product_id' ],
			'having'   => 'SUM(status) > 0',
		] );

		return $this->wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Builds the WHERE clause for the history table
	 *
	 * @param $filters
	 * @param $new_member_query
	 *
	 * @return array[]
	 */
	private function build_where_clause( $filters = [], $new_member_query = false ) {
		$where  = [];
		$params = [];

		$allowed_where = [ 'IS NOT NULL', 'IS NULL' ];

		foreach ( [ 'user_id', 'product_id', 'course_id', 'status' ] as $filter_key ) {

			if ( ! empty( $filters[ $filter_key ] ) ) {
				if ( is_array( $filters[ $filter_key ] ) ) {
					$ids = [];
					foreach ( $filters[ $filter_key ] as $id ) {
						$ids[]    = '%d';
						$params[] = $id;
					}
					$where[] = "$filter_key IN(" . implode( ',', $ids ) . ')';
				} else if ( is_numeric( $filters[ $filter_key ] ) ) {
					$where[]  = "$filter_key='%d'";
					$params[] = $filters[ $filter_key ];
				} else if ( is_string( $filters[ $filter_key ] ) && in_array( $filters[ $filter_key ], $allowed_where ) ) {
					$where[] = "$filter_key {$filters[ $filter_key ]}";
				}
			}
		}

		if ( ! empty( $filters['date'] ) && count( $filters['date'] ) > 1 ) {

			list( $from, $to ) = array_values( $filters['date'] );

			if ( ! empty( $from ) && ! empty( $to ) ) {

				$params[] = gmdate( 'Y-m-d', strtotime( $from ) );
				$params[] = gmdate( 'Y-m-d', strtotime( $to ) );

				if ( $new_member_query ) {
					$date = 'user_registered';
				} else {
					$date = 'created';
				}

				$where[] = 'DATE(' . $date . ') BETWEEN %s AND %s';
			}
		}

		/**
		 * Search by user email
		 */
		if ( ! empty( $filters['s'] ) && is_string( $filters['s'] ) ) {
			global $wpdb;
			$where[]  = "user_id IN(SELECT ID FROM $wpdb->users WHERE user_email LIKE '%%%s%%' OR display_name LIKE '%%%s%%')";
			$params[] = trim( $filters['s'] );
			$params[] = trim( $filters['s'] );
		}

		/**
		 * filter by source
		 */
		if ( ! empty( $filters['source'] ) && is_array( $filters['source'] ) ) {
			$sources         = [];
			$source_operator = empty( $filters['source_operator'] ) || ! in_array( $filters['source_operator'], [ 'IN', 'NOT IN' ] ) ? 'IN' : $filters['source_operator'];

			foreach ( array_filter( $filters['source'] ) as $source ) { //array_filter to remove empty values
				$sources[] = '%s';
				$params[]  = $source;
			}
			if ( ! empty( $sources ) ) {
				$where[] = "source $source_operator(" . implode( ',', $sources ) . ')';
			}
		}

		return [
			$where,
			$params,
		];
	}

	/**
	 * Builds the ORDER BY clause for the history table
	 *
	 * @param $filters
	 *
	 * @return string
	 */
	private function build_order_by_clause( $filters = [] ) {
		$order_by = '';

		if ( ! empty( $filters ) && is_array( $filters ) ) {
			// Whitelist of allowed column names to prevent SQL injection
			$allowed_columns = [ 'enrolled', 'min_created', 'courses_count', 'ID', 'user_registered' ];

			$order = [];
			foreach ( $filters as $order_key => $order_dir ) {
				// Validate column name against whitelist
				if ( ! in_array( $order_key, $allowed_columns, true ) ) {
					continue;
				}

				$dir = strtoupper( trim( $order_dir ) );

				if ( in_array( $dir, [ '', 'ASC', 'DESC' ], true ) ) {
					$order[] = "$order_key $dir";
				}
			}

			if ( ! empty( $order ) ) {
				$order_by .= ' ORDER BY ' . implode( ',', $order );
			}
		}

		return $order_by;
	}

	/**
	 * @param array $filters
	 * @param bool  $new_member_query
	 *
	 * @return string|null
	 */
	private function build_report_query( $filters, $new_member_query = false ) {
		$filters = array_merge( [
			'select'   => [],
			'where'    => [],
			'group_by' => [],
			'having'   => [],
			'order_by' => [],
			'limit'    => [],
		], $filters );

		list( $select, $where, $group_by, $having, $order_by, $limit ) = [
			$filters['select'],
			$filters['where'],
			$filters['group_by'],
			$filters['having'],
			$filters['order_by'],
			$filters['limit'],
		];

		// For new members query, we need to get the user_registered date from the users table.
		if ( $new_member_query ) {
			$select = array_map( function ( $field ) {
				$parts = preg_split('/[\(\)]/', $field);

				if ( count( $parts ) > 1 ) {
					$field = $parts[0] . '(tah.' . $parts[1] . ') ' . $parts[2];
				} else {
					$field = 'tah.' . $field;
				}

				return $field;
			}, $select );

			$query = 'SELECT ' . implode( ',', $select ) . ', DATE(u.user_registered) as user_registered FROM ' . $this->name . ' tah';
			$query .= ' JOIN ' . $this->wpdb->users . ' u' . ' ON tah.user_id = u.ID';
		} else {
			$query = 'SELECT ' . implode( ',', $select ) . " FROM $this->name";
		}

		list( $where, $params ) = $this->build_where_clause( $where, $new_member_query );

		if ( ! empty( $where ) ) {
			$query .= ' WHERE ' . implode( ' AND ', $where );
		}

		if ( ! empty( $group_by ) ) {
			$query .= ' GROUP BY ' . implode( ',', $group_by );
		}

		if ( ! empty( $having ) ) {
			$query .= ' HAVING ' . $having;
		}

		if ( ! empty( $order_by ) && is_array( $order_by ) ) {
			$query .= $this->build_order_by_clause( $order_by );
		}

		if ( ! empty( $limit ) ) {
			$query .= ' LIMIT ' . implode( ',', $limit );
		}

		if ( empty( $params ) ) {
			return $query;
		} else {
			return $this->wpdb->prepare( $query, $params );
		}
	}
}

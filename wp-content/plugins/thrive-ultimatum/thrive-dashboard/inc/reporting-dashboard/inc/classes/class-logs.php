<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Reporting;

use TVE\Reporting\EventFields\Item_Id;
use TVE\Reporting\EventFields\Post_Id;
use TVE\Reporting\EventFields\User_Id;

class Logs {

	use \TD_Singleton;

	const TABLE_NAME = 'thrive_reporting_logs';

	/**
	 * Physical columns of the reporting log table.
	 *
	 * Every identifier this class emits - filter keys, ORDER BY, GROUP BY, COUNT() and the
	 * DISTINCT column in get_fields() - has to be one of these. None of them can be bound as a
	 * placeholder, and the strings they are concatenated into end up either inside
	 * $wpdb->prepare()'s format argument or, in the case of the SELECT list, outside prepare()
	 * altogether. So an unvalidated identifier is raw SQL.
	 *
	 * These are also exactly the keys of Event::get_registered_fields(), which maps a report's
	 * logical field names onto them.
	 *
	 * @see inc/db-manager/migrations/reporting-logs-install-1.0.3.php
	 */
	const TABLE_COLUMNS = [
		'id',
		'event_type',
		'created',
		'item_id',
		'user_id',
		'post_id',
		'int_field_1',
		'int_field_2',
		'float_field',
		'varchar_field_1',
		'varchar_field_2',
		'text_field_1',
	];

	/** @var \Tve_Wpdb */
	protected $db;

	/**
	 * @var string
	 */
	private $select;
	/**
	 * @var string
	 */
	private $where = '';
	/**
	 * @var string
	 */
	private $group_by = '';

	/**
	 * @var string
	 */
	private $order_by = '';

	/**
	 * @var string
	 */
	private $limit = '';
	/**
	 * @var string[]
	 */
	private $args = [];

	/**
	 * @var string
	 */
	private $table;

	public function __construct() {
		$this->db = \Tve_Wpdb::instance();

		$this->table = $GLOBALS['wpdb']->prefix . static::TABLE_NAME;
	}

	/**
	 * Identifiers this query is allowed to emit, on top of TABLE_COLUMNS.
	 *
	 * Reports may legitimately group or sort by a SELECT alias rather than a physical column -
	 * Created::get_query_select_field() emits `DATE_FORMAT(...) AS date`, and reports then group by
	 * `date`. Report::parse_query() supplies the alias set for the event type in play; it is built
	 * from the registered field classes, so it is entirely plugin-defined and never request-derived.
	 *
	 * @var string[]
	 */
	private $allowed_aliases = [];

	/**
	 * Reduce an identifier to a known column or alias, or to $fallback when it is neither.
	 *
	 * Report queries take their column names from the request, and no amount of escaping makes an
	 * arbitrary string safe in an identifier position - esc_sql() and _escape() both leave
	 * backticks, spaces, commas and parentheses untouched. Matching against a fixed set is the only
	 * thing that works here.
	 *
	 * @param mixed  $column   Candidate identifier, usually request-derived.
	 * @param string $fallback Returned when $column is not recognised.
	 *
	 * @return string
	 */
	protected function valid_column( $column, $fallback = '' ) {
		if ( ! is_string( $column ) && ! is_int( $column ) ) {
			return $fallback;
		}

		if ( in_array( $column, static::TABLE_COLUMNS, true ) ) {
			return (string) $column;
		}

		return in_array( $column, $this->allowed_aliases, true ) ? (string) $column : $fallback;
	}

	/**
	 * Build a bound placeholder list for an IN ( ... ) clause, pushing the values onto $this->args.
	 *
	 * @param array $values Values to bind.
	 *
	 * @return string Comma-separated placeholders, or '' when there is nothing to bind.
	 */
	protected function bind_in_list( $values ) {
		$values = array_values( (array) $values );

		if ( empty( $values ) ) {
			return '';
		}

		$placeholders = [];
		foreach ( $values as $value ) {
			$placeholders[] = is_int( $value ) || ctype_digit( (string) $value ) ? '%d' : '%s';
			$this->args[]   = $value;
		}

		return implode( ', ', $placeholders );
	}

	/**
	 * @param Event|mixed $event
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function insert( $event ) {
		$log_data = $event->get_log_data();

		return $this->db->insert( $this->table, $log_data );
	}

	public function update( $event, $id, $fields_to_update ) {
		$log_data = $event->get_log_data( $fields_to_update );

		return $this->db->update( $this->table, $log_data, [ 'id' => $id ] );
	}

	public function get_row() {
		return $this->db->get_row( $this->prepare_query() );
	}

	/**
	 * @param $event_type
	 * @param $field
	 * @param $values
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function get_fields( $event_type, $field, $values = [] ) {
		$this->args = [];

		/*
		 * Logs is a singleton and $allowed_aliases is only ever assigned by set_query(), so without
		 * this reset a report query earlier in the same request would leave its SELECT aliases behind
		 * and valid_column() would keep accepting them here. Nothing exploits that today - every
		 * caller passes a physical column - but the guard is only meaningful if it cannot be widened
		 * by request ordering.
		 */
		$this->allowed_aliases = [];

		/*
		 * $field is an identifier and $values arrive from the REST `ids` parameter. The identifier is
		 * validated against the real columns and the values are bound, rather than imploded straight
		 * into the clause as before. An unknown column yields no rows instead of injected SQL.
		 */
		$field = $this->valid_column( $field );

		if ( '' === $field ) {
			return [];
		}

		$this->where  = "event_type='%s'";
		$this->args[] = $event_type;

		if ( ! empty( $values ) ) {
			$placeholders = $this->bind_in_list( $values );

			if ( '' !== $placeholders ) {
				$this->where .= sprintf( ' AND `%s` IN ( %s )', $field, $placeholders );
			}
		}

		//exp:   'SELECT DISTINCT item_id FROM wp_thrive_reporting_logs WHERE event_type = "tqb_quiz_completed"';
		$query = sprintf(
			"SELECT DISTINCT `%s` as 'value' FROM %s WHERE %s",
			$field,
			$this->table,
			// phpcs:ignore
			$this->db->prepare( $this->where, $this->args )
		);

		/* @codingStandardsIgnoreLine */
		return $this->db->get_results( $query, ARRAY_A );
	}

	/**
	 * Remove logs
	 *
	 * @param $field_key
	 * @param $field_value
	 * @param $format
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 */
	public function remove_by( $field_key, $field_value, $format = '%d' ) {
		return $this->db->delete( $this->table, [ $field_key => $field_value ], [ $format ] );
	}

	/**
	 * @param array $where_args
	 *
	 * @return \Tve_Wpdb
	 */
	public function delete( array $where_args ): \Tve_Wpdb {
		$conditions = [];
		$values     = [];

		// See get_fields(): stale aliases from an earlier set_query() must not widen a DELETE.
		$this->allowed_aliases = [];

		if ( isset( $where_args['event_type'] ) ) {
			$conditions[] = 'event_type IN (' . implode( ',', array_fill( 0, count( $where_args['event_type'] ), "'%s'" ) ) . ')';
			$values       = array_values( $where_args['event_type'] );
			unset( $where_args['event_type'] );
		}

		foreach ( $where_args as $field => $value ) {
			/*
			 * Both halves used to be concatenated raw - `$field` as an identifier and $value straight
			 * into the string that becomes prepare()'s format argument, so the value was not bound
			 * either. Callers all pass hardcoded keys today, but the values include request-derived
			 * ids, so the column is validated and the value is bound.
			 */
			$column = $this->valid_column( $field );

			if ( '' === $column ) {
				/*
				 * Bail rather than skip. Dropping the condition and carrying on would WIDEN a DELETE:
				 * an unrecognised column used to produce invalid SQL and delete nothing, so skipping
				 * it would start deleting every row matching the remaining conditions instead.
				 */
				return $this->db;
			}

			if ( is_array( $value ) ) {
				$placeholders = [];
				foreach ( array_values( $value ) as $item ) {
					$placeholders[] = is_int( $item ) || ctype_digit( (string) $item ) ? '%d' : '%s';
					$values[]       = $item;
				}

				if ( empty( $placeholders ) ) {
					continue;
				}

				$conditions[] = "`$column` IN ( " . implode( ', ', $placeholders ) . ' )';
			} else {
				$conditions[] = "`$column` = " . ( is_int( $value ) || ctype_digit( (string) $value ) ? '%d' : '%s' );
				$values[]     = $value;
			}
		}

		/*
		 * Never emit an unconstrained DELETE. Today an empty $conditions would produce invalid SQL
		 * and fail, but that is an accident rather than a guard - a refactor that tidied the WHERE
		 * away would turn this into a table wipe.
		 */
		if ( empty( $conditions ) ) {
			return $this->db;
		}

		$conditions = implode( ' AND ', $conditions );

		return $this->db->do_query( $this->db->prepare( "DELETE FROM `$this->table` WHERE $conditions", $values ) );
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_query( array $args = [] ): Logs {

		/* reset query */
		$this->select   = '';
		$this->where    = '';
		$this->group_by = '';
		$this->order_by = '';

		/*
		 * Aliases the caller vouches for. Report::parse_query() derives these from the registered
		 * field classes of the event type being reported on, so callers that build a query by hand
		 * (User_Events, Privacy) simply get the physical columns and nothing else.
		 */
		$this->allowed_aliases = empty( $args['allowed_columns'] ) || ! is_array( $args['allowed_columns'] )
			? []
			: array_filter( $args['allowed_columns'], 'is_string' );

		if ( empty( $args['fields'] ) ) {
			$this->select = '*';
		} elseif ( is_string( $args['fields'] ) ) {
			$this->select = $args['fields'];
		} elseif ( is_array( $args['fields'] ) ) {
			$this->select = implode( ', ', $args['fields'] );
		}

		$this->args = [];

		if ( isset( $args['event_type'] ) ) {
			if ( is_array( $args['event_type'] ) ) {
				/* fill an array with %s for each event type */
				$this->where .= 'event_type IN (' . join( ',', array_fill( 0, count( $args['event_type'] ), "'%s'" ) ) . ')';

				$this->args = array_merge( $this->args, array_values( $args['event_type'] ) );
			} else {
				$this->where  = "event_type='%s'";
				$this->args[] = $args['event_type'];
			}
		} else {
			$this->where = '1';
		}

		if ( ! empty( $args['filters'] ) && is_array( $args['filters'] ) ) {
			foreach ( $args['filters'] as $key => $values ) {
				if ( empty( $values ) ) {
					continue;
				}

				$this->set_filter( $key, $values );
			}
		}

		if ( ! empty( $args['group_by'] ) ) {
			/*
			 * group_by and count are request-derived identifiers, and $this->group_by ends up inside
			 * $wpdb->prepare()'s format argument while $this->select is concatenated outside prepare()
			 * entirely. Both are reduced to known columns; unrecognised ones are dropped rather than
			 * emitted.
			 */
			$group_by = is_array( $args['group_by'] ) ? $args['group_by'] : [ $args['group_by'] ];
			$group_by = array_filter( array_map( [ $this, 'valid_column' ], $group_by ) );

			if ( ! empty( $group_by ) ) {
				$this->group_by = ' GROUP BY `' . implode( '`, `', $group_by ) . '`';

				/*
				 * The COUNT() target is deliberately NOT validated as a column: report classes set
				 * it to expressions on purpose (Course_Finish uses a DISTINCT CONCAT to stop
				 * double-counting repeat completions, #2544). It is safe because it is server-owned -
				 * Report_App::register_rest_routes() strips `count` out of the request at the route
				 * boundary, before any report class runs, and the callers that pass it directly all
				 * hardcode it. Note it is NOT stripped in Report::parse_query(): by that point a
				 * report's own expression is indistinguishable from a request value.
				 */
				$count = isset( $args['count'] ) && is_string( $args['count'] ) && '' !== $args['count']
					? $args['count']
					: 'id';

				$this->select .= ', COUNT(' . $count . ') AS count';
			}
		}

		if ( empty( $args['page'] ) || empty( $args['items_per_page'] ) ) {
			$this->limit = '';
		} else {
			$items_per_page = (int) $args['items_per_page'];
			$this->limit    = sprintf( ' LIMIT %d, %d', ( (int) $args['page'] - 1 ) * $items_per_page, $items_per_page );
		}

		if ( ! empty( $args['order_by'] ) && ! empty( $args['order_by_direction'] ) ) {
			/*
			 * Both halves are request-derived. order_by is only ever run through
			 * Event::get_field_table_col(), which returns its input unchanged when nothing matches -
			 * a mapper, not an allowlist - and order_by_direction is not filtered anywhere upstream.
			 */
			$order_by = $this->valid_column( $args['order_by'] );

			if ( '' !== $order_by ) {
				$direction = 'ASC' === strtoupper( (string) $args['order_by_direction'] ) ? 'ASC' : 'DESC';

				$this->order_by = ' ORDER BY `' . $order_by . '` ' . $direction;
			}
		}

		return $this;
	}

	/**
	 * @param $key
	 * @param $values
	 *
	 * @return void
	 */
	public function set_filter( $key, $values ) {
		switch ( $key ) {
			case 'created':
				if ( ! empty( $values['from'] ) ) {
					/* extracts the date from the full date-time - if we also need the time at some point, modify this */
					$this->where .= " AND DATE(created) >= '%s'";

					$this->args[] = $values['from'];
				}

				if ( ! empty( $values['to'] ) ) {
					$this->where .= " AND DATE(created) <= '%s'";

					$this->args[] = $values['to'];
				}
				break;
			case User_Id::key():
			case Post_Id::key():
			case Item_Id::key():
			default:
				/*
				 * This is the sink behind #4463, and it is the same shape as #4404: $key is an array
				 * KEY taken from the request, concatenated into $this->where, which prepare_query()
				 * hands to $wpdb->prepare() as its FORMAT argument - so prepare() sanitizes the bound
				 * values and never the injected identifier. The array branch was worse still, imploding
				 * the values in unbound as well.
				 *
				 * The /user-events route reaches here without passing through
				 * Report::parse_query(), so its allowlist is not in play on that path; the key has to
				 * be validated here.
				 */
				$column = $this->valid_column( $key );

				if ( '' === $column ) {
					/*
					 * Fail closed. Simply dropping the clause would return a SUPERSET - on
					 * /user-events an unmapped filter key would hand back every user's events
					 * instead of the requested user's, where before it errored and returned none.
					 */
					$this->where .= ' AND 1=0';
					break;
				}

				if ( is_array( $values ) ) {
					$placeholders = $this->bind_in_list( $values );

					if ( '' !== $placeholders ) {
						$this->where .= sprintf( ' AND `%s` IN ( %s )', $column, $placeholders );
					}
				} else {
					$this->where .= " AND `$column`='%s'";

					$this->args[] = $values;
				}
				break;
		}
	}

	/**
	 * @return array|object|\stdClass[]|null
	 */
	public function get_results() {
		return $this->db->get_results( $this->prepare_query(), ARRAY_A );
	}

	public function query() {
		$this->db->do_query( $this->prepare_query() );
	}

	public function get_one_row() {
		return $this->db->get_one_row();
	}

	public function count_results() {
		$count = $this->db->do_query( $this->prepare_query( true ) )->num_rows();

		return empty( $count ) ? 0 : $count;
	}

	/**
	 * Get date format depending on the range we use
	 *
	 * @return string
	 */
	public function get_date_format( $min_date = 0, $max_date = 0 ) {
		if ( empty( $this->min_date ) || empty( $this->max_date ) ) {
			$min_max_date = $this->db->do_query( "SELECT MAX(created) AS max_date, MIN(created) AS min_date FROM $this->table" )->get_one_row();

			$this->min_date = empty( $min_max_date->min_date ) ? time() : strtotime( $min_max_date->min_date );
			$this->max_date = empty( $min_max_date->max_date ) ? time() : strtotime( $min_max_date->max_date );
		}

		$from = empty( $min_date ) ? $this->min_date : max( $this->min_date, strtotime( $min_date ) );
		$to   = empty( $max_date ) ? $this->max_date : min( $this->max_date, strtotime( $max_date ) );

		$days = ( $to - $from ) / DAY_IN_SECONDS;

		if ( $days > 30 * 12 * 10 ) {
			/* display years if we have at least 10 */
			$format = 'year';
		} elseif ( $days > 30 * 10 ) {
			/* display months if we have at least 10 */
			$format = 'month';
		} elseif ( $days > 7 * 10 ) {
			/* display weeks if we have at least 10 */
			$format = 'week';
		} else {
			$format = 'day';
		}

		return $format;
	}

	/**
	 * sum all counts from the query
	 *
	 * @return mixed|null
	 */
	public function sum_results_count() {
		$query = $this->prepare_query();

		$query = "SELECT SUM(`items`.count) AS total from ($query) as `items`";

		$row = $this->db->get_row( $query, ARRAY_A );

		return empty( $row ) ? null : $row['total'];
	}

	protected function prepare_query( $count_results = false ) {
		if ( $count_results ) {
			$this->order_by = '';
			$this->limit    = '';
		}

		/* @codingStandardsIgnoreLine */
		$where = "WHERE $this->where $this->group_by $this->order_by $this->limit";

		// phpcs:ignore
		return "SELECT $this->select FROM $this->table " . $this->db->prepare( $where, $this->args );
	}
}

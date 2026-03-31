<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class FrmLogList.
 *
 * @since 1.0
 */
class FrmLogList {

	/**
	 * This gets stored so it can be re-used for each row but generated once.
	 * It only includes the valid current entry ids.
	 * If an entry id is missing, it is flagged as deleted.
	 *
	 * @since 1.0.2
	 *
	 * @var array|null
	 */
	private static $entry_ids_on_page;

	/**
	 * This variable stores the form objects on the current page.
	 *
	 * @since 1.0.3
	 *
	 * @var array|null
	 */
	private static $forms_on_page;

	/**
	 * Holds meta values with frm_entry or frm_custom_fields key, for the logs on the current page.
	 *
	 * @since 1.0.3
	 *
	 * @var array|null
	 */
	private static $meta_values;

	/**
	 * Manage columns.
	 *
	 * @param array<string> $columns table columns.
	 *
	 * @return array<string>
	 */
	public static function manage_columns( $columns ) {
		$columns['entry']  = __( 'Entry', 'formidable' );
		$columns['form']   = __( 'Form', 'formidable' );
		$columns['action'] = __( 'Action', 'formidable-logs' );
		$columns['code']   = __( 'Status', 'formidable' );

		// Replace the date column with a custom created_at column.
		// This way we can get rid of the word "Published" and localize the time.
		$columns = array_slice( $columns, 0, 2, true )
			+ array( 'created_at' => __( 'Date', 'formidable-logs' ) )
			+ array_slice( $columns, 2, null, true );

		unset( $columns['date'] );

		return $columns;
	}

	public static function sortable_columns( $columns ) {
		$columns['created_at'] = 'post_date_gmt';
		return $columns;
	}

	/**
	 * Display extra table which is our buttons in top of the frmlogs table.
	 *
	 * @since 1.0.1
	 *
	 * @param string $which determines which part of the page is.
	 * @return void
	 */
	public static function extra_tablenav( $which ) {
		$screen = get_current_screen();

		$validate = (
			isset( $screen->base ) &&
			'edit-frm_logs' === $screen->id
		);
		// Simple checks to demonstrate button in proper location.
		if ( ! $validate ) {
			return;
		}

		if ( 'top' !== $which ) {
			return;
		}

		// Check for right access to clear data.
		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		// No cache, just check is there any logs to display buttons or not.
		$frmlog_posts = get_posts(
			array(
				'post_type'   => 'frm_logs',
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);

		if ( ! $frmlog_posts ) {
			return;
		}

		// Invoke buttons from helper.
		FrmLogAppHelper::show_list_entry_buttons();

	}

	/**
	 * Populates the list of entry ids related to logs on the current page.
	 *
	 * @since 1.0.3
	 *
	 * @param array $meta_values
	 *
	 * @return void
	 */
	private static function populate_entry_ids_on_page( $meta_values ) {
		$entry_ids = array();
		foreach ( $meta_values as $meta_value ) {
			$entry_id = 0;
			if ( is_numeric( $meta_value ) ) {
				$entry_id = (int) $meta_value;
			} else {
				FrmAppHelper::unserialize_or_decode( $meta_value );
				if ( isset( $meta_value['entry'] ) ) {
					$entry_id = (int) $meta_value['entry'];
				}
			}

			if ( ! empty( $entry_id ) ) {
				$entry_ids[] = $entry_id;
			}
		}

		if ( empty( $entry_ids ) ) {
			self::$entry_ids_on_page = array();
			return;
		}
		$entry_ids = FrmDb::get_col( 'frm_items', array( 'id' => $entry_ids ), 'id' );

		self::$entry_ids_on_page = array_map( 'intval', $entry_ids );
	}


	/**
	 * Shows extra controls used for filtering the logs.
	 *
	 * @since 1.0.3
	 *
	 * @return void
	 */
	public static function show_custom_filters() {
		if ( ! self::is_valid_post_type() ) {
			return;
		}
		$form_id = FrmAppHelper::simple_get( 'form_id', 'absint' );
		echo '<div class="alignleft actions">';

		echo '<label for="form_id" class="screen-reader-text">' . esc_html__( 'Filter by form', 'formidable-logs' ) . '</label>';

		self::forms_dropdown( $form_id );
		echo '</div>';
	}

	/**
	 * Shows a dropdown with form names.
	 *
	 * @since 1.0.3
	 *
	 * @param int $form_id
	 *
	 * @return void
	 */
	private static function forms_dropdown( $form_id ) {
		$forms = FrmDb::get_results(
			'frm_forms',
			array(
				'is_template'    => 0,
				'parent_form_id' => array( null, 0 ),
			),
			'id,name',
			array( 'order_by' => 'name' )
		);
		?>
		<select name="form_id">
			<option value=""><?php echo '- ' . esc_attr__( 'Select form', 'formidable-logs' ) . ' -'; ?></option>
			<?php foreach ( $forms as $form ) { ?>
				<option value="<?php echo esc_attr( $form->id ); ?>" <?php selected( $form_id, $form->id ); ?>>
					<?php echo esc_html( FrmFormsHelper::edit_form_link_label( $form ) ); ?>
				</option>
			<?php } ?>
		</select>
		<?php
	}

	/**
	 * Returns true if on the current page is Logs page.
	 *
	 * @since 1.0.3
	 *
	 * @return bool
	 */
	private static function is_valid_post_type() {
		global $pagenow;

		return 'edit.php' === $pagenow && 'frm_logs' === FrmAppHelper::get_param( 'post_type', '', 'get', 'sanitize_text_field' );
	}

	/**
	 * Updates the current query object with custom meta filter values.
	 *
	 * @since 1.0.3
	 *
	 * @param WP_Query $query
	 *
	 * @return void
	 */
	public static function filter_posts_by_custom_filters( $query ) {
		$form_id = FrmAppHelper::get_param( 'form_id', '', 'get', 'absint' );

		if ( ! $form_id || ! self::is_valid_post_type() ) {
			return;
		}
		$entries      = FrmDb::get_col( 'frm_items', array( 'form_id' => $form_id ) );
		$meta_query   = array( 'relation' => 'OR' );
		$meta_query[] = array(
			'key'     => 'frm_custom_fields',
			'value'   => 's:4:"form";i:' . $form_id,
			'compare' => 'LIKE',
		);
		$meta_query[] = array(
			'key'     => 'frm_entry',
			'value'   => $entries,
			'compare' => 'IN',
		);

		$query->set( 'meta_query', $meta_query );
	}

	/**
	 * Returns meta values with frm_entry or frm_custom_fields key.
	 *
	 * @since 1.0.3
	 *
	 * @return array
	 */
	private static function get_meta_values() {
		if ( self::$meta_values !== null ) {
			return self::$meta_values;
		}
		global $wpdb, $wp_query;
		$log_ids = wp_list_pluck( $wp_query->posts, 'ID' );

		$custom_fields = FrmDb::get_results(
			$wpdb->postmeta,
			array(
				'post_id'  => $log_ids,
				'meta_key' => array( 'frm_entry', 'frm_custom_fields' ),
			)
		);

		self::$meta_values = wp_list_pluck( $custom_fields, 'meta_value', 'post_id' );
		return self::$meta_values;
	}

	/**
	 * @since 1.0.3
	 *
	 * @param array  $meta_values Log metas with either frm_entry or frm_custom_fields key.
	 * @param int    $id          Log id.
	 * @param string $col         The string used to store the log meta.
	 *
	 * @return int
	 */
	private static function get_col_value_from_post_metas( $meta_values, $id, $col ) {
		if ( ! isset( $meta_values[ $id ] ) ) {
			return 0;
		}
		$meta = $meta_values[ $id ];

		if ( 'entry' === $col && is_numeric( $meta ) ) {
			return (int) $meta;
		}

		if ( ! is_numeric( $meta ) ) {
			FrmAppHelper::unserialize_or_decode( $meta );
			if ( isset( $meta[ $col ] ) ) {
				return 'entry' === $col ? (int) $meta[ $col ] : $meta[ $col ];
			}
		}
		return 0;
	}

	/**
	 * Add custom columns to the table.
	 *
	 * @param string $column_name column name.
	 * @param int    $id post id.
	 * @return void
	 */
	public static function manage_custom_columns( $column_name, $id ) {
		$meta_values = self::get_meta_values();

		switch ( $column_name ) {
			case 'name':
			case 'content':
				$post = get_post( $id );
				$val  = FrmAppHelper::truncate( strip_tags( $post->{"post_$column_name"} ), 100 );
				break;
			case 'entry':
				$entry_id = self::get_col_value_from_post_metas( $meta_values, $id, $column_name );

				if ( 0 === $entry_id ) {
					$val = '';
					break;
				}
				if ( null === self::$entry_ids_on_page ) {
					self::populate_entry_ids_on_page( $meta_values );
				}
				if ( ! in_array( $entry_id, self::$entry_ids_on_page, true ) ) { // @phpstan-ignore-line
					$val = esc_html( sprintf( __( '%d (Deleted)', 'formidable-logs' ), $entry_id ) );
					break;
				}
				$permission = 'frm_edit_entries';
				$page       = 'formidable-entries';
				$val        = self::get_content_for_column( $permission, $entry_id, $column_name, $page );
				break;
			case 'form':
				if ( null === self::$forms_on_page ) {
					self::populate_forms_on_page( $meta_values );
				}

				$permission = 'frm_edit_forms';
				$page       = 'formidable';
				$form_id    = self::get_col_value_from_post_metas( $meta_values, $id, $column_name );

				if ( empty( $form_id ) ) {
					$entry_id = self::get_col_value_from_post_metas( $meta_values, $id, 'entry' );
					if ( ! empty( $entry_id ) ) {
						$form_id = FrmDb::get_var( 'frm_items', array( 'id' => $entry_id ), 'form_id' );
					}
				}

				$val = self::get_content_for_column( $permission, $form_id, $column_name, $page );
				break;
			case 'action':
			case 'code':
				$val = self::get_col_value_from_post_metas( $meta_values, $id, $column_name );

				if ( ! $val ) {
					$val = absint( get_post_meta( $id, 'frm_' . $column_name, true ) );
				}
				break;
			case 'created_at':
				$post = get_post( $id );
				$val  = FrmAppHelper::get_formatted_time( (string) $post->post_date_gmt );
				break;
			default:
				$val = esc_html( $column_name );
				break;
		}

		echo $val; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Gets either an anchor HTML or id for an entry or form objects.
	 *
	 * @param string $permission
	 * @param mixed  $object_id
	 * @param string $column_name
	 * @param string $page
	 *
	 * @return string
	 */
	private static function get_content_for_column( $permission, $object_id, $column_name, $page ) {
		if ( ! $object_id ) {
			return '';
		}
		if ( ! current_user_can( $permission ) || ! is_numeric( $object_id ) ) {
			return $object_id;
		}
		$object_id = absint( $object_id );
		if ( 'form' === $column_name ) {
			if ( empty( self::$forms_on_page[ $object_id ] ) ) {
				return __( 'Form', 'formidable' ) . ' ' . $object_id . ' (' . __( 'Deleted', 'formidable-logs' ) . ')';
			}

			$form = self::$forms_on_page[ $object_id ];
			$link_name = FrmFormsHelper::edit_form_link_label( $form );
			if ( $form->status === 'trash' ) {
				$link_name .= ' (' . __( 'Trash', 'formidable' ) . ')';
			}
		} else {
			$link_name = (string) $object_id;
		}
		$edit_link = "admin.php?page={$page}&frm_action=edit&id=" . esc_attr( (string) $object_id );
		return '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $link_name ) . '</a>';
	}

	/**
	 * Populates forms on the current page.
	 *
	 * @since 1.0.3
	 *
	 * @param array $meta_values
	 *
	 * @return void
	 */
	private static function populate_forms_on_page( $meta_values ) {
		if ( null !== self::$forms_on_page ) {
			return;
		}

		$form_ids = array();
		foreach ( $meta_values as $meta_value ) {
			FrmAppHelper::unserialize_or_decode( $meta_value );
			if ( ! empty( $meta_value['form'] ) && is_numeric( $meta_value['form'] ) ) {
				$form_ids[] = (int) $meta_value['form'];
			}
		}

		if ( null === self::$entry_ids_on_page ) {
			self::$entry_ids_on_page = array(); // This is just to make PHPStan aware that it is no longer null.
			self::populate_entry_ids_on_page( $meta_values );
		}

		global $wpdb;
		$query = "SELECT DISTINCT fr.id,fr.name,fr.status from {$wpdb->prefix}frm_forms AS fr LEFT JOIN {$wpdb->prefix}frm_items AS i ON fr.id=i.form_id WHERE ";

		if ( self::$entry_ids_on_page ) {
			$query .= $wpdb->prepare( 'i.id IN (%s)', implode( ',', self::$entry_ids_on_page ) );
		}

		if ( $form_ids ) {
			if ( self::$entry_ids_on_page ) {
				$query .= ' OR';
			}
			$query .= ' fr.id IN (' . implode( ',', array_unique( $form_ids ) ) . ')';
		}

		$query_is_valid = substr( $query, -6 ) !== 'WHERE ';
		if ( ! $query_is_valid ) {
			// If no forms or entries ids are found, avoid querying for nothing.
			self::$forms_on_page = array();
			return;
		}

		$forms = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		self::$forms_on_page = array_combine( array_column( $forms, 'id' ), $forms ); //@phpstan-ignore-line
	}
}

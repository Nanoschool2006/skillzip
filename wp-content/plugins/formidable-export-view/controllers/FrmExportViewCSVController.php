<?php

class FrmExportViewCSVController {

	/**
	 * Result of calling fopen.
	 * The CSV data is written to this resource using fputcsv.
	 *
	 * @var resource
	 */
	private static $file;

	/**
	 * If link values are valid, exports a CSV, either as a download or a saved file.
	 */
	public static function maybe_export_csv() {
		if ( ! isset( $_GET['frmdata'] ) ) {
			// If the info is missing from the url, cut out early.
			return;
		}

		FrmExportViewLink::decrypt_secure_data();
		$view         = FrmAppHelper::simple_get( 'view' );
		$export_nonce = FrmAppHelper::simple_get( 'export_nonce' );

		if ( self::export_csv_permitted( $view, $export_nonce ) ) {
			self::export_csv( $view );
			die();
		}
	}

	/**
	 * Checks if the view id exists and the nonce is valid.
	 *
	 * @param string|int $view         Id of View to be exported.
	 * @param string     $export_nonce WordPress nonce for logged-in users and date for logged-out users.
	 *
	 * @return bool Whether the View is allowed to be exported.
	 */
	private static function export_csv_permitted( $view, $export_nonce ) {
		if ( ! is_user_logged_in() ) {
			return ! empty( $view ) && self::date_has_not_expired( $export_nonce );
		}

		return ! empty( $export_nonce ) && wp_verify_nonce( $export_nonce, 'exporting_view' ) && ! empty( $view );
	}

	/**
	 * Takes date string (date in seconds) and tests if it's within the specified number of hours of the current date.
	 *
	 * @param string $date_string Expects a string showing the number.
	 *
	 * @return bool Whether or not the date is close enough to the current date.
	 */
	private static function date_has_not_expired( $date_string ) {
		$date = (int) $date_string;
		$now  = gmdate( 'U' );
		if ( $date > $now ) {
			return false;
		}
		$hours_to_expiration = apply_filters( 'frm_export_view_link_expiration', 24 );

		// Deprecated hook.
		$hours_to_expiration   = apply_filters( 'frm-export-view-link-expiration-hours', $hours_to_expiration );
		$seconds_to_expiration = $hours_to_expiration * HOUR_IN_SECONDS;

		$valid = $date + $seconds_to_expiration >= gmdate( 'U' );
		return $valid;
	}

	/**
	 *  Prepares as needed and then creates the CSV export.
	 *
	 * @param int|string $view_id  Id of View to be exported.
	 * @param bool       $download Whether or not the CSV should be downloaded.  If false, file will be saved online.
	 * @param bool       $is_cron  Whether or not this process is happening during a cron job.
	 */
	public static function export_csv( $view_id, $download = true, $is_cron = false ) {
		$atts = self::get_atts_for_export( $view_id, $download, $is_cron );
		if ( ! is_array( $atts ) || count( $atts ) == 0 ) {
			return;
		}
		self::prepare_for_export();
		self::create_csv_export( $atts, $download );
	}

	/**
	 * Prepares $atts needed for the export process.
	 *
	 * @param int|string $view_id  Id of View to be exported.
	 * @param bool       $download Whether or not the CSV should be downloaded.  If false, file will be saved online.
	 * @param bool       $is_cron  Whether or not this process is happening during a cron job.
	 *
	 * @return array Array of data and selections needed to process the export.
	 */
	private static function get_atts_for_export( $view_id, $download, $is_cron ) {
		$frm_options = get_post_meta( $view_id, 'frm_options', true );
		if ( ! self::is_export_possible( $frm_options ) ) {
			self::show_error_message( 'no-export', $view_id, $is_cron );

			return array();
		};
		$view        = FrmViewsDisplay::getOne( $view_id, false, true );
		$view_values = self::get_entry_ids_and_where( $view );
		$entry_ids   = $view_values['entry_ids'];
		$where       = $view_values['where'];

		if ( ! self::view_has_content( $entry_ids ) ) {
			self::show_error_message( 'no-entries', $view_id, $is_cron );

			return array();
		}
		$filename = self::get_filename( $frm_options, $view );
		$location = self::get_location( $view );

		$atts = array(
			'entry_ids'   => $entry_ids,
			'view'        => $view,
			'frm_options' => $frm_options,
			'filename'    => $filename,
			'where'       => $where,
			'view_id'     => $view_id,
			'location'    => $location,
		);

		return $atts;
	}

	/**
	 * Determines if a CSV export is possible with this View.
	 *
	 *  When View was last saved, the View markup and type were evaluated to determine if this View can be exported to a CSV.
	 *
	 * @param array $frm_options Array of View options.
	 *
	 * @return bool Whether or not the View can be exported.
	 */
	private static function is_export_possible( $frm_options ) {
		return ! empty( $frm_options['view_export_possible'] );
	}

	/**
	 * Displays appropriate error message where appropriate (on screen or in the error log).
	 *
	 * @param string $message_type Type of message to display.
	 * @param string $view_id      Id of View, for error messages in the error log.
	 * @param bool   $is_cron      Whether this process is happening duringa cron job.
	 */
	private static function show_error_message( $message_type, $view_id = '', $is_cron = false ) {
		if ( $is_cron ) {
			$message_type = $message_type . '-cron';
		}
		$messages = self::get_error_message( $message_type, $view_id, $is_cron );

		if ( ! is_array( $messages ) ) {
			return;
		}
		foreach ( $messages as $message ) {
			if ( $is_cron ) {
				error_log( esc_html( $message ) );
			} else {
				echo( esc_html( $message ) );
			}
		}
	}

	/**
	 * Retrieves appropriate error message based on type and context.
	 *
	 * @param string $message_type Type of error message, e.g. 'no-export'.
	 * @param string $view_id      Id of View, for error messages in error log.
	 * @param bool   $is_cron      Whether or not this process is happening during a cron job.
	 *
	 * @return mixed|string Error message string.
	 */
	private static function get_error_message( $message_type, $view_id, $is_cron = false ) {
		$messages = array(
			'no-export'       => array(
				__( 'This View cannot be exported.  It may not be a table View, or it may not have been saved since the FrmExportView add-on was installed.', 'formidable-export-view' ),
				__( 'You can return to the previous page using your browser\'s back button.', 'formidable-export-view' ),
			),
			'no-export-cron'  => array(
				// translators: %d - View id.
				sprintf( __( 'View %d is not a table View or it has not been saved since the Formidable Export View add-on was activated.  A CSV file was not created.', 'formidable-export-view' ), $view_id ),
			),
			'no-entries'      => array(
				__( 'This View has no entries to download.', 'formidable-export-view' ),
				__( 'You can return to the previous page using your browser\'s back button.', 'formidable-export-view' ),

			),
			'no-entries-cron' => array(
				// translators: %d - View id.
				sprintf( __( 'View %d has no entries.  A CSV file was not created.', 'formidable-export-view' ), $view_id ),
			),
		);

		return ( ! empty( $messages[ $message_type ] ) ? $messages[ $message_type ] : '' );
	}

	/**
	 * Retrieves entry ids for the View and the where value, which is needed by some View functions.
	 *
	 * @param object $view The info about the current view.
	 *
	 * @return array An array of the entry_ids and where value.
	 */
	public static function get_entry_ids_and_where( $view ) {
		$view->frm_page_size = '';

		return FrmViewsDisplaysController::get_view_entry_ids( $view, '' );
	}

	/**
	 * Determines if the View has any entries.
	 *
	 * @param array|string $entry_ids Entry ids.
	 *
	 * @return bool Whether the View has any entries.
	 */
	private static function view_has_content( $entry_ids ) {
		return is_array( $entry_ids ) && count( $entry_ids ) > 0;
	}

	/**
	 * Returns string that will be used as part of the filename, along with the date.
	 *
	 * @param array  $frm_options Options of the View.
	 * @param object $view        View object.
	 *
	 * @return string Filename part.
	 */
	private static function get_filename( $frm_options, $view ) {
		$filename = ! empty( $frm_options['filename'] ) ? $frm_options['filename'] : '';
		if ( ! $filename ) {
			$filename = ! empty( $view->post_title ) ? $view->post_title : 'view-export';
		}

		$filename = sanitize_title_with_dashes( $filename );

		$filename = apply_filters( 'frm_export_view_csv_filename', gmdate( 'Y-m-d-H-i-s', time() ) . '-' . $filename, $view );

		return $filename . '.csv';
	}

	/**
	 * Returns location set by filter, if valid.
	 *
	 * @param object $view View object.
	 *
	 * @return string Location, if set and valid, or empty string.
	 */
	private static function get_location( $view ) {
		$location = apply_filters( 'frm_export_view_file_location', '', $view );

		return FrmExportViewCSVHelper::validate_directory( $location );
	}

	/**
	 * Sets up environment and helper class for export.
	 */
	private static function prepare_for_export() {
		self::set_time_and_memory_limits();
		FrmExportViewCSVHelper::set_class_parameters();
	}

	/**
	 * Removes time limit and raises memory limit to at least 256.
	 */
	private static function set_time_and_memory_limits() {
		if ( function_exists( 'set_time_limit' ) ) {
			// Remove time limit to execute this function.
			set_time_limit( 0 );
		}

		$mem_limit = str_replace( 'M', '', ini_get( 'memory_limit' ) );

		if ( (int) $mem_limit < 256 ) {
			// Set memory limit to 256 or the original php.ini memory limit, whichever is higher.
			if ( is_callable( 'wp_raise_memory_limit' ) ) {
				wp_raise_memory_limit( 'frm_export_view' );
			}
		}
	}

	/**
	 * Generates the export.
	 *
	 * @param array $atts     Data and selections used in export process.
	 * @param bool  $download Whether or not the CSV should be downloaded.  Otherwise, it will be saved online.
	 */
	private static function create_csv_export( $atts, $download ) {
		try {
			if ( $download && ! $atts['location'] ) {
				self::create_file_for_download( $atts );
			} else {
				self::save_file_online( $atts );
			}
		} catch ( Exception $exception ) {
			error_log( $exception->getMessage() );
		}
	}

	/**
	 * Adds BOM to the csv file being exported if the charset setting is set to 'UTF-8 with BOM'.
	 *
	 * @since 1.09
	 *
	 * @return void
	 */
	private static function maybe_add_bom_to_file() {
		$settings = new FrmExportViewGlobalSettings();
		$options = $settings->get_options();
		if ( 'UTF-8 with BOM' === $options->csv_format ) {
			fwrite( self::$file, "\xEF\xBB\xBF" );
		}
	}

	/**
	 * Creates file for download.
	 *
	 * @param array $atts Data and selections used in the export process.
	 */
	private static function create_file_for_download( $atts ) {
		add_filter( 'frm_csv_format', 'FrmExportViewCSVController::set_encoding' );
		FrmExportViewCSVHelper::print_file_headers( $atts['filename'] );

		self::$file = fopen( 'php://output', 'w' );

		self::maybe_add_bom_to_file();

		self::add_content_to_file( $atts['view'], $atts['entry_ids'] );

		fclose( self::$file );
	}

	/**
	 * Let the Formidable encoding function know what to use.
	 *
	 * @since 1.02
	 *
	 * @param string $format - The default encoding format.
	 */
	public static function set_encoding( $format ) {
		return FrmExportViewCSVHelper::to_encoding( $format );
	}

	/**
	 * Adds header and row content to file.
	 *
	 * @param object $view      View object.
	 * @param array  $entry_ids Entry ids for the View.
	 */
	private static function add_content_to_file( $view, $entry_ids ) {
		self::disable_caching();

		$separator = FrmExportViewCSVHelper::get_column_separator();
		self::add_header_to_file( $view, $entry_ids, $separator );
		self::add_inner_content_to_file( $view, $entry_ids, $separator );
	}

	/**
	 * Turn off caching as memory limits always happen when setting the cache.
	 *
	 * @since 1.07
	 *
	 * @return void
	 */
	private static function disable_caching() {
		global $frm_vars;
		$frm_vars['prevent_caching'] = true;
	}

	/**
	 * Retrieves header content and adds to file.
	 *
	 * @param object $view      View object.
	 * @param array  $entry_ids Ids of entries for the View.
	 * @param string $separator Single character that separates cells in CSV.
	 */
	private static function add_header_to_file( $view, $entry_ids, $separator ) {
		$total_entries = count( $entry_ids );
		$args          = array(
			'entry_ids'    => $entry_ids,
			'total_count'  => $total_entries,
			'record_count' => $total_entries,
		);

		$before_content = FrmViewsDisplaysController::get_before_content_for_listing_page( $view, $args );
		$before_content = apply_filters( 'frm_export_csv_table_heading', $before_content, $view );
		$before_content = do_shortcode( $before_content );
		self::add_row_content_to_file( $before_content, 'th', $separator );
	}

	/**
	 * Adds content of any table rows with cells in given markup to file.
	 *
	 * @param string $content   Markup with content for export.
	 * @param string $cell_tag  Tag of type of cell to be matched, either td or th.
	 * @param string $separator Single character that separates cells in CSV.
	 * @return void
	 */
	private static function add_row_content_to_file( $content, $cell_tag = 'td', $separator = ',' ) {
		$content     = FrmCSVExportHelper::encode_value( $content );
		$row_matches = array();
		preg_match_all( '/<tr(>| [^>]*>)(.*?)<\/tr( |>)/is', $content, $row_matches );

		unset( $content );

		$rows = $row_matches[2];
		unset( $row_matches );

		if ( count( $rows ) === 0 ) {
			return;
		}

		$pattern = '/<' . $cell_tag . '(>| [^>]*>)(.*?)<\/' . $cell_tag . '( |>)/is';

		foreach ( $rows as $row ) {
			$cell_matches = array();
			preg_match_all( $pattern, $row, $cell_matches );
			$cells = FrmExportViewCSVHelper::adjust_cell_content( $cell_matches[2] );
			unset( $cell_matches );
			if ( $cells ) {
				fputcsv( self::$file, $cells, $separator );
			}
			unset( $cells );
		}

		unset( $pattern );
	}

	/**
	 * Adds table row content (i.e. not the header) to the specified file.
	 *
	 * @param object $view            View object.
	 * @param array  $entry_ids       Entry ids of the View.
	 * @param string $separator       Single character that separates the cells in the CSV.
	 * @param int    $number_in_batch Number of rows of the table to process in each batch.
	 */
	private static function add_inner_content_to_file( $view, $entry_ids, $separator = ',', $number_in_batch = 30 ) {
		$total_entries = count( $entry_ids );

		for ( $offset = 0; $offset < $total_entries; $offset = $offset + $number_in_batch ) {
			$entries = array_slice( $entry_ids, $offset, $number_in_batch );

			$args = array(
				'entry_ids'    => $entries,
				'record_count' => $total_entries,
				'total_count'  => count( $entries ),
				'offset'       => $offset,
			);

			$inner_content = FrmViewsDisplaysController::get_inner_content_for_listing_page( $view, $args );
			$inner_content = do_shortcode( $inner_content );
			self::add_row_content_to_file( $inner_content, 'td', $separator );

			gc_collect_cycles();
		}
	}

	/**
	 * Saves file online.
	 *
	 * @param array $atts Data and selections used in the process of saving the CSV online.
	 */
	private static function save_file_online( $atts ) {
		$upload_dir = FrmExportViewCSVHelper::get_file_location( $atts['location'] );

		if ( ! $upload_dir || ! FrmExportViewCSVHelper::add_htaccess_if_needed( $upload_dir ) ) {
			$message = self::get_failure_message();
			echo( esc_html( $message ) );

			return;
		}

		self::$file = fopen( untrailingslashit( $upload_dir ) . '/' . $atts['filename'], 'w' );

		self::maybe_add_bom_to_file();

		self::add_content_to_file( $atts['view'], $atts['entry_ids'] );
		fclose( self::$file );
		$message = self::get_success_message();
		echo( esc_html( $message ) );
	}

	/**
	 * Returns message shown when file has been successfully saved online.
	 *
	 * @return string|void Success message.
	 */
	private static function get_success_message() {
		return esc_html__( 'The CSV has been saved.  You can use your browser\'s back button to return to the previous page.', 'formidable-export-view' );
	}

	/**
	 * Returns message shown when file has not been successfully saved online.
	 *
	 * @return string|void Success message.
	 */
	private static function get_failure_message() {
		return esc_html__( 'The CSV could not be saved because a valid location wasn\'t available or an .htaccess file is blocking access. Please enter a valid location in Global Settings->ExportView or with the frm_export_view_file_location hook or adjust the .htaccess file to allow access. You can use your browser\'s back button to return to the previous page.', 'formidable-export-view' );
	}
}

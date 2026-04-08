<?php
/**
 * Manager class for google communications.
 *
 * @package formidable-google-sheets
 */

/**
 * Class FrmGoogleSpreadsheetManager.
 */
class FrmGoogleSpreadsheetManager {

	/**
	 * Remote request instance.
	 *
	 * @since 1.0
	 *
	 * @var FrmGoogleSpreadsheetRemoteRequest
	 */
	private $remote_request;

	/**
	 * Logs add on controller instance.
	 *
	 * @since 1.0
	 *
	 * @var FrmGoogleSpreadsheetLogController
	 */
	private $google_sheet_log;

	/**
	 * FrmGoogleSpreadsheetManager constructor.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->google_sheet_log = new FrmGoogleSpreadsheetLogController();
		$this->remote_request   = new FrmGoogleSpreadsheetRemoteRequest( $this->google_sheet_log );
	}

	/**
	 * Get sheets of a google drive file via AJAX request.
	 *
	 * @return void
	 */
	public static function get_sheets() {
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );
		$spreadsheet_id = FrmAppHelper::get_post_param( 'spreadsheet_id' );
		$sheets         = ( new self() )->get_googlespreadsheet_sheets( $spreadsheet_id );
		wp_send_json( $sheets );
	}

	/**
	 * Get List of GoogleSpreadsheets
	 *
	 * @since  1.0
	 *
	 * @param object $action form action.
	 *
	 * @return mixed
	 */
	public function get_googlespreadsheet_files( $action ) {
		$cached_files = get_transient( 'frm_googlespreadsheet_files' );

		if ( ! empty( $cached_files ) ) {
			return $cached_files;
		}
		$files = array();

		$this->remote_request->set_logs_data(
			array(
				'action' => $action,
			)
		);

		$page_token = null;
		do {
			$response = $this->request(
				'drive',
				'GET',
				'/drive/v3/files',
				array(
					'q'                         => rawurlencode( 'mimeType="application/vnd.google-apps.spreadsheet" and trashed=false' ),
					'supportsAllDrives'         => 'true',
					'includeItemsFromAllDrives' => 'true',
					'pageToken'                 => $page_token,
				)
			);

			if ( is_wp_error( $response ) || ! is_object( $response ) ) {
				return $response;
			}

			/* @var object $response */
			$expected = ! empty( $response->kind ) && ! empty( $response->files );
			if ( $expected && 'drive#fileList' == $response->kind ) {
				foreach ( $response->files as $file ) {
					$files[] = array(
						'label' => $file->name,
						'id'    => $file->id,
					);
				}
			}
			$page_token = isset( $response->nextPageToken ) ? $response->nextPageToken : null; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		} while ( $page_token );

		set_transient( 'frm_googlespreadsheet_files', $files, 60 * 60 * 60 );

		return $files;
	}

	/**
	 * Get List of GoogleSpreadsheet Sheets.
	 *
	 * @since  1.0
	 *
	 * @param int|string     $spreadsheet_id file id.
	 * @param object|boolean $action form action.
	 * @return mixed
	 */
	public function get_googlespreadsheet_sheets( $spreadsheet_id, $action = false ) {
		if ( empty( $spreadsheet_id ) ) {
			return array();
		}
		$cached_sheets = get_transient( 'frm_googlespreadsheet_sheets' );

		if ( empty( $cached_sheets[ $spreadsheet_id ] ) ) {
			$sheets = array();

			$this->remote_request->set_logs_data(
				array(
					'action' => $action,
				)
			);
			$response = $this->request( 'sheets', 'GET', '/' . $spreadsheet_id, array( 'includeGridData' => 'false' ) );

			if ( is_object( $response ) ) {
				if ( isset( $response->sheets ) ) {
					foreach ( $response->sheets as $sheet ) {
						$sheets[] = array(
							'label' => $sheet->properties->title,
							'id'    => $sheet->properties->title,
						);
					}

					if ( ! is_array( $cached_sheets ) ) {
						$cached_sheets = array();
					}

					$cached_sheets[ $spreadsheet_id ] = $sheets;
					set_transient( 'frm_googlespreadsheet_sheets', $cached_sheets, 60 * 60 * 60 );
				}
			}
		} else {
			$sheets = $cached_sheets[ $spreadsheet_id ];
		}
		return json_encode( $sheets );
	}

	/**
	 * Get List of GoogleSpreadsheet Headers.
	 *
	 * @since  1.0
	 *
	 * @param int|string $spreadsheet_id file id.
	 * @param int|string $sheet_id sheet id.
	 * @return mixed
	 */
	public function get_googlespreadsheet_headers( $spreadsheet_id, $sheet_id ) {
		if ( empty( $spreadsheet_id ) || empty( $sheet_id ) ) {
			return '';
		}

		$sheet_id       = (string) $sheet_id;
		$cached_headers = get_transient( 'frm_googlespreadsheet_headers' );

		if ( ! empty( $cached_headers[ $spreadsheet_id ][ $sheet_id ] ) ) {
			return $cached_headers[ $spreadsheet_id ][ $sheet_id ];
		}

		$headers  = array();
		$response = $this->request( 'sheets', 'GET', '/' . $spreadsheet_id . '/values/' . urlencode( $sheet_id ) );

		if ( is_object( $response ) && isset( $response->values[0] ) ) {
			foreach ( $response->values[0] as $key => $name ) {
				$headers[] = array(
					'label' => $name,
					'id'    => $key,
				);
			}

			if ( ! is_array( $cached_headers ) ) {
				$cached_headers = array();
			}

			$cached_headers[ $spreadsheet_id ][ $sheet_id ] = $headers;
			set_transient( 'frm_googlespreadsheet_headers', $cached_headers, 60 * 60 * 60 );
		}

		return $headers;

	}

	/**
	 * Add new row to GoogleSpreadsheet.
	 *
	 * @since  1.0
	 *
	 * @param int|string   $spreadsheet_id file id.
	 * @param string       $sheet_id sheet id.
	 * @param array<mixed> $row array of entry.
	 * @param stdClass     $action form action.
	 * @param stdClass     $entry form entry.
	 * @return void
	 */
	public function add_new_row( $spreadsheet_id, $sheet_id, $row, $action, $entry ) {
		$this->remote_request->set_logs_data(
			array(
				'action' => $action,
				'entry'  => $entry,
			)
		);
		$this->request( 'sheets', 'POST', self::get_add_to_sheet_uri( $spreadsheet_id, $sheet_id ), $row );
	}

	/**
	 * Get the relative URI to use in self::add_new_row and self::send_entries.
	 *
	 * @since 1.0.3
	 *
	 * @param int|string $spreadsheet_id file id.
	 * @param string     $sheet_id sheet id.
	 * @return string
	 */
	private static function get_add_to_sheet_uri( $spreadsheet_id, $sheet_id ) {
		/**
		 * By default a GET request is made before inserting a new row. This is to avoid issues with data getting inserted
		 * in the wrong columns. This is to work around an issue with the Google Sheets API when cells are empty.
		 * This hook exists so users have the option to disable this. Opting out should help with performance.
		 *
		 * @since 1.0.4
		 *
		 * @param bool $should_check_for_first_empty_row
		 */
		$should_check_for_first_empty_row = apply_filters( 'frm_googlespreadsheet_should_check_for_first_empty_row', true );

		$target_range = '';
		if ( $should_check_for_first_empty_row ) {
			$first_empty_row_number = self::get_first_empty_row_number( $spreadsheet_id, $sheet_id );

			if ( $first_empty_row_number >= 2 ) {
				// Only set a target range when the $first_empty_row_number value is valid.
				$target_range = '!A' . $first_empty_row_number;
			}
		}

		return '/' . $spreadsheet_id . '/values/' . rawurlencode( $sheet_id ) . $target_range . ':append?valueInputOption=USER_ENTERED';
	}

	/**
	 * When appending data to Google Sheets, it will skip empty columns by default when no range is specified.
	 * So first, before inserting, we need to query for the number of rows.
	 * This function determines what the first empty row number is, where the new data will be inserted.
	 *
	 * @since 1.0.4
	 *
	 * @param int|string $spreadsheet_id file id.
	 * @param string     $sheet_id sheet id.
	 * @return int This should always 2 or higher as we never want to update the header row. If the request fails, -1 is returned.
	 */
	private static function get_first_empty_row_number( $spreadsheet_id, $sheet_id ) {
		$response = ( new self() )->request( 'sheets', 'GET', '/' . $spreadsheet_id . '/values/' . rawurlencode( $sheet_id ) . '!A:Z?majorDimension=ROWS' );

		if ( ! is_object( $response ) || is_wp_error( $response ) || ! isset( $response->values ) || ! is_array( $response->values ) ) {
			return -1;
		}

		return count( $response->values ) + 1;
	}

	/**
	 * Send entries too Google Sheet.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function send_entries_google_spreadsheet() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );

		$form_id   = FrmAppHelper::get_post_param( 'formid', 0, 'absint' );
		$action_id = FrmAppHelper::get_post_param( 'actionid', 0, 'absint' );

		if ( ! $form_id || ! $action_id ) {
			return;
		}

		$step = FrmAppHelper::get_post_param( 'step', 1, 'absint' );

		/**
		 * Entries sync per page filter default is 10 item.
		 *
		 * @since 1.0
		 */
		$per_page = apply_filters( 'frm_googlespreadsheet_sync_per_page_limit', 10 );

		$offset = ( $step - 1 ) * $per_page;
		$limit  = FrmDb::esc_limit( $offset . ',' . $per_page );

		$entries = FrmEntry::getAll( array( 'it.form_id' => $form_id ), '', $limit, true );

		// When there are no items to sync.
		if ( empty( $entries ) ) {
			wp_send_json(
				array(
					'error_detail' => esc_html__( 'There are no entries to send.', 'formidable-google-sheets' ),
					'response'     => 'error',
				)
			);
		}

		$mapped_fields = array();
		try {
			$fields        = isset( $_POST['mapped_fields'] ) ? (array) map_deep( stripslashes_deep( $_POST['mapped_fields'] ), 'sanitize_text_field' ) : false;
			$mapped_fields = self::prepare_mapped_fields( $fields );
		} catch ( Exception $exception ) {
			wp_send_json(
				array(
					'error_detail' => $exception->getMessage(),
					'response'     => 'error',
				)
			);
		}

		// Get action setting.
		$spreadsheet_id = FrmAppHelper::get_post_param( 'spreadsheet_id' );
		$sheet_id       = FrmAppHelper::get_post_param( 'sheet_id' );

		try {
			self::send_entries( $entries, $mapped_fields, $spreadsheet_id, $sheet_id );
		} catch ( Exception $exception ) {
			wp_send_json(
				array(
					'error_detail' => $exception->getMessage(),
					'response'     => 'error',
				)
			);
		}

		$step++;
		// Check if this is the last set of result.
		if ( count( $entries ) < $per_page ) {
			$step = 'complete';
		}
		// Total entries processed so far.
		$processed_entries_count = $offset + count( $entries );
		wp_send_json(
			array(
				// translators: %d is the number of entries processed so far.
				'processed' => sprintf( esc_html__( '%d entries processed.', 'formidable-google-sheets' ), $processed_entries_count ),
				'step'      => $step,
				'response'  => 'success',
			)
		);

	}

	/**
	 * Request to google apis.
	 *
	 * @since 1.0
	 *
	 * @param string     $scope Google API endpoint.
	 * @param string     $method Call method.
	 * @param string     $uri Exact endpoint of the uri.
	 * @param mixed|null $params params.
	 *
	 * @return WP_Error|mixed responses.
	 */
	public function request( $scope, $method = 'GET', $uri = '', $params = null ) {

		if ( 'drive' === $scope ) {
			$url = 'https://www.googleapis.com' . $uri;
		} else {
			$url = 'https://sheets.googleapis.com/v4/spreadsheets' . $uri;
		}

		$access_token = FrmGoogleSpreadsheetAuth::get_access_token();
		if ( is_wp_error( $access_token ) ) {
			return $access_token;
		}

		// make the request.
		$req_args = array(
			'method'  => $method,
			'headers' => array(
				'content-type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
		);

		switch ( $method ) {
			case 'GET':
				if ( ! empty( $params ) ) {
					$url = $url . '?' . build_query( (array) $params );
				}
				break;
			case 'POST':
				if ( ! empty( $params ) ) {
					$req_args['body'] = json_encode( array( 'values' => $params ) );
				}
				break;
		}

		try {
			$response = $this->remote_request->request( $url, $req_args );
		} catch ( Exception $exception ) {
			/* translators: %1$s: the fetched URL, %2$s the error message that was returned */
			return new WP_Error( 'http_error', sprintf( __( 'Failed to fetch: %1$s (%2$s)', 'formidable-google-sheets' ), $url, $exception->getMessage() ) );
		}

		return json_decode( wp_remote_retrieve_body( (array) $response ) );

	}

	/**
	 * Get mapped fields for selected spread sheet.
	 *
	 * @since 1.0
	 *
	 * @param false|array $fields Raw field numbers.
	 *
	 * @return array<mixed> Mapped result.
	 * @throws Exception Throwable exception.
	 */
	private static function prepare_mapped_fields( $fields ) {
		// If it's first action initiation we need to update the form.
		if ( empty( $fields ) || empty( array_filter( $fields ) ) ) {
			throw new Exception( esc_html__( 'Please map form fields to sheet headers.', 'formidable-google-sheets' ) );
		}

		$headers = array();
		foreach ( $fields as $headerid => $field_id ) {
			$headers[ $headerid ] = $field_id ? $field_id : '';
		}

		return $headers;
	}

	/**
	 * Send entries to Google Sheets.
	 *
	 * @since 1.0
	 *
	 * @param array<stdClass> $entries Call method.
	 * @param array           $mapped_fields   Mapped header.
	 * @param string          $spreadsheet_id    Spread sheet file id.
	 * @param string          $sheet_id    sheet id.
	 *
	 * @return void.
	 * @throws Exception Throwable exception.
	 */
	private static function send_entries( $entries, $mapped_fields, $spreadsheet_id, $sheet_id ) {
		// Match the fields and post to the google sheet.
		$final_rows = array();
		foreach ( $entries as $entry ) {
			$final_rows[] = FrmGoogleSpreadsheetAppController::prepare_mapped_values( $mapped_fields, $entry );
		}

		$response = ( new self() )->request( 'sheets', 'POST', self::get_add_to_sheet_uri( $spreadsheet_id, $sheet_id ), $final_rows );

		// Check for the error earlier.
		if ( is_object( $response ) && is_wp_error( $response ) ) {
			throw new Exception( ! empty( $response->errors ) ? implode( ', ', $response->errors ) : esc_html__( 'Please check your Google API authorization.', 'formidable-google-sheets' ) );
		}

		// Set default error in case of WP Error has not set for whatever reason.
		if ( ! is_object( $response ) || ! isset( $response->updates ) ) {
			throw new Exception( esc_html__( 'Please check your Google API authorization.', 'formidable-google-sheets' ) );
		}

	}
}

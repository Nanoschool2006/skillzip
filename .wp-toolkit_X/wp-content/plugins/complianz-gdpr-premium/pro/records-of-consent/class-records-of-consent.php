<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! class_exists( "cmplz_records_of_consent" ) ) {
	class cmplz_records_of_consent {
		private static $_this;
		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}
			self::$_this = $this;
			add_filter( 'cmplz_do_action', array( $this, 'get_records_of_consent_data' ), 10, 3 );
		}

		static function this() {
			return self::$_this;
		}



		/**
		 * Get a list of processors
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return []
		 */

		public function get_records_of_consent_data($data, $action, $request){
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}
			if ( $action==='get_records_of_consent' ){
				$data = $request->get_params();
				$per_page = $data['per_page'] ?? 10;
				$page = $data['page'] ?? 1;
				$search = $data['search'] ?? false;
				$order = $data['order'] ?? 'ASC';
				$orderby = $data['orderBy'] ?? 'id';
				$offset  = $per_page * ( $page - 1 );
				$regions = cmplz_get_regions(false, 'full');
				//convert key value array to array of objects with id and label
				$regions = array_map(function($id, $label){
					return (object) array('value'=>$id, 'label'=>$label);
				}, array_keys($regions), $regions);
				$regions[]=['value'=>'', 'label'=>__('Select a region', 'complianz-gdpr')];
				$args = [
					'start_date' => strtotime('-1 week'),
					'order' => $order,
					'orderby' => $orderby,
					'offset' => $offset,
					'search' => $search,
				];
				$records = $this->get_consent_records($args);
				$total = $this->get_consent_records($args, true);
				if (!is_array($records)) $records = [];
				//convert unix timestamp to date
				//convert the array key to an 'id'
				foreach ($records as $key => $record) {
					$id = $record['ID'];
					unset($record['ID']);
					$record['id'] = $id;
					$unix = $record['time'];
					$record['time'] = date_i18n( get_option( 'date_format' ), $unix );
					if (empty($record['poc_url'])) {
						$file_data = $this->get_poc_for_record($unix, $record['region']);
						if ($file_data) {
							$record['poc_url'] = $file_data['url'];
						}
					}
					//make path relative
					$record['poc_url'] = str_replace(trailingslashit( cmplz_upload_url('snapshots') ), '', $record['poc_url']);

					$records[$key]=$record;
				}
				//strip key from the array
				$records = empty($records) ? []: array_values($records);
				$data = [
						'records' => $records,
						'totalRecords' => $total,
						'regions' => $regions,
						'download_url' => cmplz_upload_url('snapshots'),
				];
			} else if ( $action==='delete_records_of_consent' ) {
				$records = $request->get_param('records');
				foreach ($records as $record) {
					$this->delete_record($record['id']);
				}
			} else if ($action==='export_records_of_consent') {
				$data = $request->get_params();
				$dateStart = $data['startDate'] ?? false;
				$dateEnd = $data['endDate'] ?? false;
				$statusOnly = $data['statusOnly'] ?? false;
				$data = $this->run_export_roc_to_csv($dateStart, $dateEnd, $statusOnly);
			}
			return $data;
		}

		/**
		 * Export all records in the current selection to a csv file
		 */

		public function run_export_roc_to_csv($dateStart, $dateEnd, $statusOnly = false ){
			$page_batch = 100;
			if ( ! cmplz_user_can_manage() ) {
				return [];
			}

			$offset = get_option( 'cmplz_current_poc_export_offset' ) ?: 0;
			if ( $statusOnly ) {
				$progress = get_option( 'cmplz_current_poc_export_progress' ) ?: 100;
				$total=1;
			} else {
				if ($offset===0) {
					//cleanup old file
					$file = $this->filepath();
					if ( file_exists($file) ){
						unlink($file);
					}
				}

				$args = array(
					'number' => $page_batch,
					'offset' => $offset * $page_batch,
					'start_date' => strtotime($dateStart),
					'end_date' => strtotime($dateEnd),
				);
				$offset++;

				$pages_completed = ( $offset ) * $page_batch;
				update_option('cmplz_roc_export_args', $args, false );
				update_option('cmplz_current_poc_export_offset', $offset , false );
				$total = $this->get_consent_records( $args, true );
				if ($total>0) {
					$data = $this->get_consent_records($args);
					$add_header = $offset==1;
					$this->create_csv_file( $data, $add_header);
					$progress = 100 * ($pages_completed/$total);
					$progress = $progress>100 ? 100 : $progress;
				} else {
					$progress = 100;
				}
				update_option('cmplz_current_poc_export_progress', $progress, false );
			}

			if ( $progress === 100 ) {
				delete_option('cmplz_current_poc_export_offset' );
				delete_option('cmplz_roc_export_args');
			}
			return array(
				'progress' => round($progress, 0),
				'exportLink' => $this->fileurl(),
				'noData' => $total == 0,
			);
		}

		/**
		 * create csv file from array
		 *
		 * @param array $data
		 * @param bool $add_header
		 * @throws Exception
		 */

		private function create_csv_file($data, $add_header = true ){
			$delimiter=",";
			$enclosure = '"';
			$escape = '\\';
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			$upload_dir = cmplz_upload_dir();

			//generate random filename for storage
			if ( !get_option('cmplz_roc_file_name') ) {
				$token = bin2hex(random_bytes(18));
				update_option('cmplz_roc_file_name', $token, false );
			}
			$filename = get_option('cmplz_roc_file_name');

			//set the path
			$file = $upload_dir .$filename.".csv";
			//'a' creates file if not existing, otherwise appends.
			$csv_handle = fopen ($file,'a');

			//create a line with headers
			if ( $add_header ) {
				$headers = $this->parse_headers_from_array( $data );
				fputcsv( $csv_handle, $headers, $delimiter, $enclosure, $escape );
			}

			if ( is_array($data) ) {
				$data = array_map(array($this, 'localize_date') , $data);

				foreach ( $data as $line ) {
					$line = array_map( 'sanitize_text_field', $line );
					fputcsv( $csv_handle, $line, $delimiter, $enclosure, $escape );
				}
			}
			fclose ($csv_handle);
		}

		/**
		 * Get headers from an array
		 * @param array $array
		 *
		 * @return array|bool
		 */

		private function parse_headers_from_array($array){
			if (!isset($array[0])) return array();
			$array = $array[0];
			$array[__("Date", "complianz-gdpr")] = 1;
			return array_keys($array);
		}

		/**
		 * Get a localized date for this row
		 * @param $row
		 *
		 * @return mixed
		 */
		public function localize_date($row){
			if (isset($row['time'])) {
				$row['nice_time'] = sprintf("%s at %s", date( str_replace( 'F', 'M', get_option('date_format')), $row['time']  ), date( get_option('time_format'), $row['time'] ) );
			}
			return $row;
		}

		/**
		 * Get a filepath
		 * @return string
		 */

		private function filepath(){
			return untrailingslashit(cmplz_upload_dir().get_option('cmplz_roc_file_name').".csv");
		}

		/**
		 * Get a file URL
		 * @return string
		 */

		private function fileurl() {
			if ( file_exists($this->filepath() ) ) {
				return untrailingslashit(cmplz_upload_url().get_option('cmplz_roc_file_name').".csv" );
			}
			return '';
		}

		/**
		 * The last pdf in time before this record is the one belonging to this record.
		 * The next pdf in time after the poc belongs to the next record
		 * If there are no other records between these two pdf's in time, we can delete the pdf.
		 *
		 * @param int $id
		 */

		public function delete_record( $id ) {
			global $wpdb;
			$delete_file = true;
			$record_id = (int) $id;
			$record = $wpdb->get_row("select * from {$wpdb->prefix}cmplz_statistics where ID = $record_id" );
			if ( $record ) {
				$poc_url = $record->poc_url;
				if ( !empty($poc_url) ) {
					//get count of other records with this url
					$other_records_count = $wpdb->get_var($wpdb->prepare("select count(*) from {$wpdb->prefix}cmplz_statistics where ID != %s AND poc_url = %s", $record_id, $poc_url ) );
					if ( $other_records_count>0 ) {
						$delete_file = false;
					}

					if ( $delete_file ) {
						$url = cmplz_upload_url('snapshots');
						$file_name = str_replace($url, '', $poc_url);
						COMPLIANZ::$proof_of_consent->delete_snapshot( $file_name );
					}
				}

				$wpdb->delete(
						$wpdb->prefix.'cmplz_statistics',
						array('ID' => $record_id)
				);
			}
		}

		/**
		 * Get poc pdf file belonging to this record
		 * @param int $record_time_stamp
		 * @param string $region
		 * @return false|array
		 */

		public function get_poc_for_record( $record_time_stamp, $region ){
			if (empty($region)) $region = COMPLIANZ::$company->get_default_region();
			$args = array(
				'number'  => 1,
				'start_date'    => 0,
				'end_date'      => $record_time_stamp,
				'region'      => $region,
			);

			$files      = COMPLIANZ::$proof_of_consent->get_cookie_snapshot_list( $args );
			if ( empty($files) ) {
				//try again, with a larger range
				$args['end_date'] = $record_time_stamp + 24 * HOUR_IN_SECONDS;
				$files      = COMPLIANZ::$proof_of_consent->get_cookie_snapshot_list( $args );
				if (empty($files)) {
					return false;
				}
			}

			$file = reset($files);
			if ($file) {
				$upload_dir = cmplz_upload_dir();
				$upload_url = cmplz_upload_url();
				$file['url']= str_replace( $upload_dir, $upload_url, $file['path'] );
				return $file;
			}
			return false;
		}

		/**
		 * @param int $record_time_stamp
		 * @param string $region
		 *
		 * @return false|array
		 */

		public function get_next_poc( $record_time_stamp, $region ){
			if (empty($region)) $region = COMPLIANZ::$company->get_default_region();
			$args = array(
				'number' => 1,
				'order'  => 'ASC',
				'start_date'    => $record_time_stamp,
				'region'    => $region,
			);

			$files      = COMPLIANZ::$proof_of_consent->get_cookie_snapshot_list( $args );
			if (isset($files[0])) {
				$file = $files[0];
				$upload_dir = cmplz_upload_dir();
				$upload_url = cmplz_upload_url();
				$file['url']= str_replace( $upload_dir, $upload_url, $file['path'] );
				return $file;
			}

			return false;
		}

		/**
		 * Add the latest snapshot file to all users who haven't been updated since the last cookie policy snapshot generation was scheduled.
		 * @param int|bool $generation_scheduled_time //time at which moment the new pdf was scheduled to be generated in the next 24 hours
		 */

		public function update_users_without_snapshot( $generation_scheduled_time ){
			//if it's forced, don't update
			if ( !$generation_scheduled_time ) return;
			$generation_scheduled_time = (int) $generation_scheduled_time;
			$regions = cmplz_get_regions();
			foreach ($regions as $region) {
				//get last poc pdf file, counting back from now.
				$file = COMPLIANZ::$records_of_consent->get_poc_for_record( time(), $region );
				//file has path, url, file, time
				if ( $file ) {
					//for all users without a file since generation update time, set this file
					global $wpdb;
					$sql = $wpdb->prepare( "UPDATE {$wpdb->prefix}cmplz_statistics SET poc_url = %s where poc_url='' AND region = %s AND time>=%s", $file['url'], $region, $generation_scheduled_time ) ;
					$wpdb->query($sql);
				}
			}
		}

		/**
		 * @param array $args
		 * @param bool $count
		 *
		 * @return array|false
		 */

		public function get_consent_records( $args = array(), $count = false ) {
			$defaults = array(
					'number'     => false,
					'offset'     => 0,
					'order'      => 'DESC',
					'orderby'    => 'time',
					'start_date' => 0,
					'end_date'   => false,
					'search'	=> false,
			);

			$args       = wp_parse_args( array_filter($args), $defaults );
			global $wpdb;
			//add 24 hours to end_date, because it's the start of that day.
			if ($args['end_date']) $args['end_date'] = $args['end_date']+24*HOUR_IN_SECONDS;
			$where     = " where time> '0' ";
			$where     .= $args['end_date'] ? $wpdb->prepare( " AND time> %s AND time < %s", $args['start_date'], $args['end_date'] ) : "";
			$where     .= $args['search'] ? $wpdb->prepare( " AND (ID=%s OR region=%s OR consenttype=%s)", $args['search'] ) : "";
			$total = 0;
			if ($count) $total = $wpdb->get_var( "SELECT count(*) from {$wpdb->prefix}cmplz_statistics $where" );
			if ( !$count ) {
				$limit   = (int) $args['number'];
				$orderby = sanitize_title( $args['orderby'] );
				$order   = sanitize_title( $args['order'] );
				$limit   = $limit > 0 ? $limit : 10;
				$offset  = (int) $args['offset'];
				$limit_sql = "limit $limit offset $offset";
				$sql = "SELECT * from {$wpdb->prefix}cmplz_statistics $where ORDER BY $orderby $order " . $limit_sql;
				$records = $wpdb->get_results( $sql );
				$records = json_decode(json_encode($records), true);
				if ( empty( $records ) ) {
					return false;
				}

				return $records;
			}

			return $total;
		}

	}
} //class closure

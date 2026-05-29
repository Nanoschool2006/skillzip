<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-quiz-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

/**
 * Class TQB_Results_Export_Manager
 * Handles asynchronous export of quiz results to CSV files.
 */
class TQB_Results_Export_Manager {

	/**
	 * Cron hook for processing export jobs.
	 */
	const CRON_HOOK = 'tqb_process_export_job';

	/**
	 * Cron hook for cleanup of old export files.
	 */
	const CLEANUP_HOOK = 'tqb_cleanup_export_files';

	/**
	 * Number of results threshold for async export.
	 */
	const ASYNC_THRESHOLD = 5000;

	/**
	 * Initialize the export manager.
	 */
	public static function init() {
		// Register cron action hooks.
		add_action( self::CRON_HOOK, array( __CLASS__, 'process_export_job' ) );
		add_action( self::CLEANUP_HOOK, array( __CLASS__, 'cleanup_old_exports' ) );

		// Schedule daily cleanup if not already scheduled.
		add_action( 'plugins_loaded', array( __CLASS__, 'ensure_cleanup_scheduled' ) );
	}

	/**
	 * Ensure cleanup cron is scheduled.
	 */
	public static function ensure_cleanup_scheduled() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			self::schedule_cleanup_cron();
		}
	}

	/**
	 * Schedule the cleanup cron job to run daily.
	 */
	public static function schedule_cleanup_cron() {
		if ( wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			return;
		}

		// Schedule for next midnight (00:00) server time.
		$now           = current_time( 'timestamp' );
		$next_midnight = strtotime( 'tomorrow 00:00', $now );
		wp_schedule_event( $next_midnight, 'daily', self::CLEANUP_HOOK );
	}

	/**
	 * Get the async threshold with filter support.
	 *
	 * @return int Threshold for async export.
	 */
	public static function get_threshold() {
		return (int) apply_filters( 'tqb_export_async_threshold', self::ASYNC_THRESHOLD );
	}

	/**
	 * Create a new export job.
	 *
	 * @param int $quiz_id Quiz ID to export.
	 *
	 * @return string|false Job ID on success, false on failure.
	 */
	public static function create_job( $quiz_id ) {
		if ( ! $quiz_id || ! is_numeric( $quiz_id ) ) {
			return false;
		}

		$user_id = get_current_user_id();

		// Return existing active job if one exists.
		$existing_job_id = self::get_active_job( $quiz_id, $user_id );
		if ( $existing_job_id ) {
			return $existing_job_id;
		}

		$job_id   = 'tqb_exp_' . uniqid();
		$job_data = array(
			'quiz_id'    => $quiz_id,
			'user_id'    => $user_id,
			'status'     => 'pending',
			'created_at' => time(),
			'file_path'  => '',
			'file_name'  => '',
			'error'      => '',
			'row_count'  => 0,
		);

		set_transient( 'tqb_export_job_' . $job_id, $job_data, DAY_IN_SECONDS );
		set_transient( 'tqb_export_active_' . $quiz_id . '_' . $user_id, $job_id, DAY_IN_SECONDS );

		$scheduled = wp_schedule_single_event( time(), self::CRON_HOOK, array( $job_id ) );

		if ( false === $scheduled ) {
			self::mark_job_failed( $job_id, $job_data, __( 'Export could not be scheduled for processing.', 'thrive-quiz-builder' ) );

			return $job_id;
		}

		spawn_cron();

		return $job_id;
	}

	/**
	 * Get active job for a quiz and user.
	 *
	 * @param int $quiz_id Quiz ID.
	 * @param int $user_id User ID.
	 *
	 * @return string|false Job ID if active job exists, false otherwise.
	 */
	public static function get_active_job( $quiz_id, $user_id ) {
		if ( ! $quiz_id || ! is_numeric( $quiz_id ) || ! $user_id || ! is_numeric( $user_id ) ) {
			return false;
		}

		$job_id = get_transient( 'tqb_export_active_' . $quiz_id . '_' . $user_id );

		if ( ! $job_id ) {
			return false;
		}

		$job_data = get_transient( 'tqb_export_job_' . $job_id );

		if ( ! $job_data || ! is_array( $job_data ) ) {
			delete_transient( 'tqb_export_active_' . $quiz_id . '_' . $user_id );

			return false;
		}

		if ( in_array( $job_data['status'], array( 'pending', 'processing' ), true ) ) {
			return $job_id;
		}

		return false;
	}

	/**
	 * Get job status.
	 *
	 * @param string $job_id Job ID.
	 *
	 * @return array|false Job data array or false on failure.
	 */
	public static function get_job_status( $job_id ) {
		if ( empty( $job_id ) ) {
			return false;
		}

		$job_data = get_transient( 'tqb_export_job_' . $job_id );

		if ( ! $job_data || ! is_array( $job_data ) ) {
			return false;
		}

		if ( isset( $job_data['user_id'] ) && $job_data['user_id'] !== get_current_user_id() ) {
			return false;
		}

		if ( 'pending' === $job_data['status'] ) {
			$job_data = self::handle_stuck_job( $job_id, $job_data );
		}

		return $job_data;
	}

	/**
	 * Handle a job stuck in pending status for more than 5 minutes.
	 *
	 * @param string $job_id   Job ID.
	 * @param array  $job_data Job data.
	 *
	 * @return array Updated job data.
	 */
	private static function handle_stuck_job( $job_id, $job_data ) {
		$created_at = isset( $job_data['created_at'] ) ? $job_data['created_at'] : 0;
		$time_diff  = time() - $created_at;

		if ( $time_diff <= 300 ) {
			return $job_data;
		}

		if ( wp_next_scheduled( self::CRON_HOOK, array( $job_id ) ) ) {
			return $job_data;
		}

		$scheduled = wp_schedule_single_event( time(), self::CRON_HOOK, array( $job_id ) );

		if ( false === $scheduled ) {
			self::mark_job_failed( $job_id, $job_data, __( 'Export could not be scheduled for processing.', 'thrive-quiz-builder' ) );
			$job_data['status'] = 'failed';

			return $job_data;
		}

		spawn_cron();

		return $job_data;
	}

	/**
	 * Process export job (WP Cron callback).
	 *
	 * @param string $job_id Job ID.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function process_export_job( $job_id ) {
		if ( empty( $job_id ) ) {
			return false;
		}

		$job_data = get_transient( 'tqb_export_job_' . $job_id );

		if ( ! $job_data || ! is_array( $job_data ) ) {
			return false;
		}

		$job_data['status'] = 'processing';
		set_transient( 'tqb_export_job_' . $job_id, $job_data, DAY_IN_SECONDS );

		$export_dir = self::get_export_directory();

		if ( ! $export_dir ) {
			self::mark_job_failed( $job_id, $job_data, __( 'Could not create export directory.', 'thrive-quiz-builder' ) );

			return false;
		}

		$quiz_id  = isset( $job_data['quiz_id'] ) ? $job_data['quiz_id'] : 0;
		$filename = 'quiz-' . $quiz_id . '-results-' . gmdate( 'Y-m-d-His' ) . '-' . wp_generate_password( 8, false ) . '.csv';
		$filepath = $export_dir . $filename;

		$fh = null;
		try {
			$fh = fopen( $filepath, 'w' );

			if ( ! $fh ) {
				throw new Exception( __( 'Could not open file for writing.', 'thrive-quiz-builder' ) );
			}

			$reporting_manager = new TQB_Reporting_Manager( $quiz_id, 'results' );
			$row_count         = $reporting_manager->export_user_results_csv( $fh );

			fclose( $fh );
			$fh = null;

			$job_data['status']       = 'completed';
			$job_data['file_path']    = $filepath;
			$job_data['file_name']    = $filename;
			$job_data['row_count']    = $row_count;
			$job_data['completed_at'] = time();

			set_transient( 'tqb_export_job_' . $job_id, $job_data, DAY_IN_SECONDS );

			do_action( 'tqb_results_exported', array(
				'quiz_id'   => $quiz_id,
				'quiz_name' => get_the_title( $quiz_id ),
				'user_id'   => isset( $job_data['user_id'] ) ? $job_data['user_id'] : 0,
				'row_count' => $row_count,
				'mode'      => 'async',
				'job_id'    => $job_id,
			) );

			self::cleanup_active_job_index( $job_data, $quiz_id );

			return true;

		} catch ( Exception $e ) {
			self::mark_job_failed( $job_id, $job_data, $e->getMessage() );

			do_action( 'tqb_export_failed', $quiz_id, $job_id, $e->getMessage() );

			self::cleanup_active_job_index( $job_data, $quiz_id );

			if ( null !== $fh && is_resource( $fh ) ) {
				fclose( $fh );
			}

			if ( file_exists( $filepath ) ) {
				wp_delete_file( $filepath );
			}

			return false;
		}
	}

	/**
	 * Download export file.
	 *
	 * @param string $job_id Job ID.
	 * @param string $nonce  Nonce for verification.
	 */
	public static function download_export_file( $job_id, $nonce ) {
		if ( ! wp_verify_nonce( $nonce, 'tqb_download_export_' . $job_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'thrive-quiz-builder' ), '', array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'thrive-quiz-builder' ), '', array( 'response' => 403 ) );
		}

		$job_data = get_transient( 'tqb_export_job_' . $job_id );

		if ( ! $job_data || ! is_array( $job_data ) ) {
			wp_die( esc_html__( 'Export job not found.', 'thrive-quiz-builder' ), '', array( 'response' => 404 ) );
		}

		if ( isset( $job_data['user_id'] ) && $job_data['user_id'] !== get_current_user_id() ) {
			wp_die( esc_html__( 'You do not have permission to download this file.', 'thrive-quiz-builder' ), '', array( 'response' => 403 ) );
		}

		if ( 'completed' !== $job_data['status'] ) {
			wp_die( esc_html__( 'Export is not yet complete.', 'thrive-quiz-builder' ), '', array( 'response' => 400 ) );
		}

		if ( empty( $job_data['file_path'] ) || ! file_exists( $job_data['file_path'] ) ) {
			wp_die( esc_html__( 'Export file not found.', 'thrive-quiz-builder' ), '', array( 'response' => 404 ) );
		}

		$real_path = self::validate_export_file_path( $job_data['file_path'] );

		if ( ! $real_path ) {
			wp_die( esc_html__( 'Invalid file path.', 'thrive-quiz-builder' ), '', array( 'response' => 400 ) );
		}

		$filename      = isset( $job_data['file_name'] ) ? $job_data['file_name'] : basename( $real_path );
		$safe_filename = str_replace( array( "\r", "\n", '"' ), '', $filename );

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $safe_filename . '"' );
		header( 'Content-Length: ' . filesize( $real_path ) );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		readfile( $real_path );

		wp_delete_file( $real_path );
		delete_transient( 'tqb_export_job_' . $job_id );

		exit;
	}

	/**
	 * Cleanup old export files (Daily cron callback).
	 *
	 * @return int Number of files deleted.
	 */
	public static function cleanup_old_exports() {
		$upload_dir = wp_upload_dir();

		if ( ! isset( $upload_dir['basedir'] ) ) {
			return 0;
		}

		$export_dir = $upload_dir['basedir'] . '/tqb-exports/';

		if ( ! file_exists( $export_dir ) ) {
			return 0;
		}

		$files = glob( $export_dir . '*.csv' );

		if ( empty( $files ) || ! is_array( $files ) ) {
			return 0;
		}

		$deleted_count = 0;
		$cutoff_time   = time() - DAY_IN_SECONDS;

		foreach ( $files as $file ) {
			$file_time = filemtime( $file );

			if ( ! $file_time || $file_time >= $cutoff_time ) {
				continue;
			}

			wp_delete_file( $file );

			if ( ! file_exists( $file ) ) {
				$deleted_count ++;
			}
		}

		return $deleted_count;
	}

	/**
	 * Get or create the export directory.
	 *
	 * @return string|false Export directory path on success, false on failure.
	 */
	private static function get_export_directory() {
		$upload_dir = wp_upload_dir();

		if ( ! isset( $upload_dir['basedir'] ) ) {
			return false;
		}

		$export_dir = $upload_dir['basedir'] . '/tqb-exports/';

		if ( file_exists( $export_dir ) ) {
			return $export_dir;
		}

		if ( ! wp_mkdir_p( $export_dir ) ) {
			return false;
		}

		// Add .htaccess to deny direct access.
		$htaccess_file = $export_dir . '.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			file_put_contents( $htaccess_file, "Deny from all\n" );
		}

		// Add index.php for security.
		$index_file = $export_dir . 'index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" );
		}

		return $export_dir;
	}

	/**
	 * Validate that a file path is within the export directory.
	 *
	 * @param string $file_path File path to validate.
	 *
	 * @return string|false Real path if valid, false otherwise.
	 */
	private static function validate_export_file_path( $file_path ) {
		$upload_dir      = wp_upload_dir();
		$export_dir      = $upload_dir['basedir'] . '/tqb-exports/';
		$real_path       = realpath( $file_path );
		$real_export_dir = realpath( $export_dir );

		if ( ! $real_path || ! $real_export_dir || strpos( $real_path, $real_export_dir ) !== 0 ) {
			return false;
		}

		return $real_path;
	}

	/**
	 * Mark a job as failed.
	 *
	 * @param string $job_id   Job ID.
	 * @param array  $job_data Job data.
	 * @param string $error    Error message.
	 */
	private static function mark_job_failed( $job_id, $job_data, $error ) {
		$job_data['status'] = 'failed';
		$job_data['error']  = $error;
		set_transient( 'tqb_export_job_' . $job_id, $job_data, DAY_IN_SECONDS );
	}

	/**
	 * Clean up active job index transient.
	 *
	 * @param array $job_data Job data.
	 * @param int   $quiz_id  Quiz ID.
	 */
	private static function cleanup_active_job_index( $job_data, $quiz_id ) {
		if ( ! isset( $job_data['user_id'] ) ) {
			return;
		}

		delete_transient( 'tqb_export_active_' . $quiz_id . '_' . $job_data['user_id'] );
	}
}

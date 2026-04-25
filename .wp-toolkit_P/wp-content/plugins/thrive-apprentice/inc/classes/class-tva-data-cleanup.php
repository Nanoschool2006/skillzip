<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_Data_Cleanup
 *
 * Handles the deletion of old, non-crucial data from various Thrive Apprentice tables.
 * This includes IPN logs, debug logs, and reporting event data.
 *
 * Note: Access history records are NOT deleted as they are required for accurate access calculation.
 * Access is determined by SUM(status) which requires both access added (+1) and revoked (-1) records.
 */
class TVA_Data_Cleanup {

	/**
	 * Minimum number of days for cleanup (to prevent accidental deletion of recent data).
	 */
	const MIN_DAYS = 30;

	/**
	 * Event types in thrive_reporting_logs that are safe to delete.
	 * These are non-crucial analytics events - financial, completion, and assessment events are preserved.
	 */
	const DELETABLE_EVENT_TYPES = [
		'tva_lesson_start',
		'tva_module_start',
		'tva_course_start',
		'tva_video_start',
		'tva_video_watch_data',
		'tva_video_completed',
		'tva_protected_file_download',
		'tva_certificate_downloaded',
		'tva_certificate_verify',
		'tva_drip_unlocked_for_user',
		'tva_lesson_unlocked_by_quiz',
		'tva_free_lesson_completed',
		'tva_all_free_lessons_completed',
	];

	/**
	 * WordPress database object.
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Number of days threshold for cleanup.
	 *
	 * @var int
	 */
	private $days;

	/**
	 * Results of the cleanup operations.
	 *
	 * @var array
	 */
	private $results = [];

	/**
	 * TVA_Data_Cleanup constructor.
	 *
	 * @param int $days Number of days - data older than this will be deleted.
	 */
	public function __construct( $days ) {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->days = max( (int) $days, self::MIN_DAYS );
	}

	/**
	 * Run the complete cleanup process.
	 *
	 * @return array Results of all cleanup operations.
	 */
	public function run() {
		$this->results = [
			'ipn_logs'         => $this->cleanup_ipn_logs(),
			// Note: access_history cleanup is disabled - records are needed for access calculation
			'access_history'   => $this->cleanup_access_history(),
			'debug_logs'       => $this->cleanup_debug_logs(),
			'reporting_events' => $this->cleanup_reporting_events(),
		];

		$this->results['total_deleted'] = array_sum( array_column( $this->results, 'deleted' ) );

		return $this->results;
	}

	/**
	 * Delete old IPN logs.
	 * Table: {prefix}tva_ipn_log
	 *
	 * @return array
	 */
	private function cleanup_ipn_logs() {
		$table = esc_sql( $this->wpdb->prefix . 'tva_ipn_log' );

		// Check if table exists.
		if ( ! $this->table_exists( $table ) ) {
			return [
				'deleted' => 0,
				'message' => 'Table does not exist',
			];
		}

		$date_threshold = $this->get_date_threshold();

		// Delete old records.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$date_threshold
			)
		);

		return [
			'deleted' => false !== $deleted ? $deleted : 0,
			'message' => false !== $deleted ? 'Success' : 'Error: ' . $this->wpdb->last_error,
		];
	}

	/**
	 * Delete old inactive access history records.
	 * Table: {prefix}tva_access_history
	 *
	 * IMPORTANT: We do NOT delete access history records because they are critical for access calculation.
	 * Access is determined by SUM(status) grouped by user/product/course, where:
	 * - status = 1 means access added
	 * - status = -1 means access revoked
	 * Deleting revoked records (-1) would incorrectly grant access when only active records (+1) remain.
	 *
	 * This method is kept for API compatibility but performs no deletion.
	 *
	 * @return array
	 */
	private function cleanup_access_history() {
		// Do not delete access history records - they are needed for accurate access calculation
		// Access is calculated using SUM(status) which requires both +1 (added) and -1 (revoked) records
		return [
			'deleted' => 0,
			'message' => 'Access history records are preserved for access calculation integrity',
		];
	}

	/**
	 * Delete old Thrive Apprentice debug logs.
	 * Table: {prefix}thrive_debug
	 * Only deletes records where product = 'Thrive Apprentice'.
	 *
	 * @return array
	 */
	private function cleanup_debug_logs() {
		$table = esc_sql( $this->wpdb->prefix . 'thrive_debug' );

		// Check if table exists.
		if ( ! $this->table_exists( $table ) ) {
			return [
				'deleted' => 0,
				'message' => 'Table does not exist',
			];
		}

		$date_threshold = $this->get_date_threshold();

		// Delete old Thrive Apprentice logs only - preserve other products' logs.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $this->wpdb->query(
			$this->wpdb->prepare(
				"DELETE FROM {$table} WHERE product = %s AND date < %s",
				'Thrive Apprentice',
				$date_threshold
			)
		);

		return [
			'deleted' => false !== $deleted ? $deleted : 0,
			'message' => false !== $deleted ? 'Success' : 'Error: ' . $this->wpdb->last_error,
		];
	}

	/**
	 * Delete old reporting event data (selective).
	 * Table: {prefix}thrive_reporting_logs
	 * Only deletes specific event types - preserves financial, completion, and assessment events.
	 *
	 * @return array
	 */
	private function cleanup_reporting_events() {
		$table = esc_sql( $this->wpdb->prefix . 'thrive_reporting_logs' );

		// Check if table exists.
		if ( ! $this->table_exists( $table ) ) {
			return [
				'deleted' => 0,
				'message' => 'Table does not exist',
			];
		}

		$date_threshold = $this->get_date_threshold();
		$event_types    = self::DELETABLE_EVENT_TYPES;

		// Build placeholders for IN clause.
		$placeholders = implode( ', ', array_fill( 0, count( $event_types ), '%s' ) );

		// Build query args.
		$query_args = array_merge( [ $date_threshold ], $event_types );

		// Delete old records for specific event types only.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$deleted = $this->wpdb->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->wpdb->prepare(
				"DELETE FROM {$table} WHERE created < %s AND event_type IN ({$placeholders})",
				$query_args
			)
		);

		return [
			'deleted' => false !== $deleted ? $deleted : 0,
			'message' => false !== $deleted ? 'Success' : 'Error: ' . $this->wpdb->last_error,
		];
	}

	/**
	 * Check if a table exists in the database.
	 *
	 * @param string $table_name Full table name with prefix.
	 *
	 * @return bool
	 */
	private function table_exists( $table_name ) {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		);

		return $result === $table_name;
	}

	/**
	 * Get the date threshold for cleanup.
	 *
	 * @return string MySQL datetime format.
	 */
	private function get_date_threshold() {
		return gmdate( 'Y-m-d H:i:s', strtotime( "-{$this->days} days" ) );
	}

	/**
	 * Get a preview of what would be deleted (dry run).
	 *
	 * @return array Counts of records that would be deleted.
	 */
	public function preview() {
		$date_threshold = $this->get_date_threshold();
		$preview        = [];

		// IPN Logs.
		$table = esc_sql( $this->wpdb->prefix . 'tva_ipn_log' );
		if ( $this->table_exists( $table ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$preview['ipn_logs'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE created_at < %s",
					$date_threshold
				)
			);
		} else {
			$preview['ipn_logs'] = 0;
		}

		// Access History - not cleaned up (preserved for access calculation integrity)
		$preview['access_history'] = 0;

		// Debug Logs (only Thrive Apprentice).
		$table = esc_sql( $this->wpdb->prefix . 'thrive_debug' );
		if ( $this->table_exists( $table ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$preview['debug_logs'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE product = %s AND date < %s",
					'Thrive Apprentice',
					$date_threshold
				)
			);
		} else {
			$preview['debug_logs'] = 0;
		}

		// Reporting Events (selective).
		$table       = esc_sql( $this->wpdb->prefix . 'thrive_reporting_logs' );
		$event_types = self::DELETABLE_EVENT_TYPES;
		if ( $this->table_exists( $table ) ) {
			$placeholders = implode( ', ', array_fill( 0, count( $event_types ), '%s' ) );
			$query_args   = array_merge( [ $date_threshold ], $event_types );

			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$preview['reporting_events'] = (int) $this->wpdb->get_var(
				$this->wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE created < %s AND event_type IN ({$placeholders})",
					$query_args
				)
			);
		} else {
			$preview['reporting_events'] = 0;
		}

		$preview['total'] = array_sum( $preview );

		return $preview;
	}
}

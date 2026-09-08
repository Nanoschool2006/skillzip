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
 * Class TQB_Stats_Cleanup_Manager
 * Handles automatic cleanup of old quiz statistics.
 */
class TQB_Stats_Cleanup_Manager {

	/**
	 * Cron hook name.
	 */
	const CRON_HOOK = 'tqb_auto_clear_old_stats';

	/**
	 * Initialize the cleanup manager.
	 */
	public static function init() {
		// Schedule cron job on settings save.
		add_action( 'update_option_' . Thrive_Quiz_Builder::PLUGIN_SETTINGS, array( __CLASS__, 'maybe_schedule_cleanup' ), 10, 2 );

		// Register the cron action.
		add_action( self::CRON_HOOK, array( __CLASS__, 'cleanup_old_stats' ) );

		// Schedule on plugin activation if setting is already enabled.
		add_action( 'plugins_loaded', array( __CLASS__, 'ensure_cron_scheduled' ) );
	}

	/**
	 * Schedule or unschedule the cron job based on settings.
	 *
	 * @param mixed $old_value Old settings value.
	 * @param mixed $new_value New settings value.
	 */
	public static function maybe_schedule_cleanup( $old_value, $new_value ) {
		$new_settings       = maybe_unserialize( $new_value );
		$auto_clear_enabled = ! empty( $new_settings['tqb_auto_clear_stats'] ) && 1 === (int) $new_settings['tqb_auto_clear_stats'];

		if ( $auto_clear_enabled ) {
			self::schedule_cron();
		} else {
			self::unschedule_cron();
		}
	}

	/**
	 * Ensure cron is scheduled if setting is enabled.
	 */
	public static function ensure_cron_scheduled() {
		$settings           = tqb_get_option( Thrive_Quiz_Builder::PLUGIN_SETTINGS, tqb_get_default_values( Thrive_Quiz_Builder::PLUGIN_SETTINGS ) );
		$auto_clear_enabled = ! empty( $settings['tqb_auto_clear_stats'] ) && 1 === (int) $settings['tqb_auto_clear_stats'];

		if ( $auto_clear_enabled && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			self::schedule_cron();
		}
	}

	/**
	 * Schedule the cron job to run daily.
	 */
	public static function schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			// Schedule for next midnight (00:00) server time
			$now = current_time( 'timestamp' );
			$next_midnight = strtotime( 'tomorrow 00:00', $now );
			wp_schedule_event( $next_midnight, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the cron job.
	 */
	public static function unschedule_cron() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Main cleanup function - deletes old quiz statistics.
	 *
	 * @return array|null Deleted counts or null if cleanup didn't run.
	 */
	public static function cleanup_old_stats() {
		global $wpdb;

		// Get settings.
		$settings = tqb_get_option( Thrive_Quiz_Builder::PLUGIN_SETTINGS, tqb_get_default_values( Thrive_Quiz_Builder::PLUGIN_SETTINGS ) );

		// Check if auto-clear is still enabled.
		if ( empty( $settings['tqb_auto_clear_stats'] ) || 1 !== (int) $settings['tqb_auto_clear_stats'] ) {
			return null;
		}

		$duration = isset( $settings['tqb_stats_duration'] ) ? intval( $settings['tqb_stats_duration'] ) : 90;
		$unit     = isset( $settings['tqb_stats_duration_unit'] ) ? $settings['tqb_stats_duration_unit'] : 'days';

		// Validate duration against minimums.
		$minimums = array(
			'days'   => 30,
			'weeks'  => 4,
			'months' => 1,
		);
		$minimum  = isset( $minimums[ $unit ] ) ? $minimums[ $unit ] : 1;

		if ( $duration < $minimum ) {
			return null;
		}

		// Calculate cutoff date.
		$cutoff_date = self::calculate_cutoff_date( $duration, $unit );

		if ( ! $cutoff_date ) {
			return null;
		}

		// Delete old data.
		$deleted_counts = array();

		/*
		 * 1. Delete old users (excluding ignored users - they're tracked separately).
		 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		 */
		$deleted_counts['users'] = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . tqb_table_name( 'users' ) . ' WHERE date_started < %s AND (ignore_user IS NULL OR ignore_user != 1)', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$cutoff_date
			)
		);

		// 2. Delete orphaned user answers (answers whose user no longer exists).
		$deleted_counts['answers'] = $wpdb->query(
			'DELETE ua FROM ' . tqb_table_name( 'user_answers' ) . ' ua LEFT JOIN ' . tqb_table_name( 'users' ) . ' u ON ua.user_id = u.id WHERE u.id IS NULL' // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);

		/*
		 * Note: tqb_results table stores result definitions (quiz config), not user data.
		 * It should not be cleaned up as it contains quiz structure, not user stats.
		 */

		// 3. Delete old event logs.
		$deleted_counts['event_logs'] = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . tqb_table_name( 'event_log' ) . ' WHERE date < %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$cutoff_date
			)
		);

		// 4. Clean up ignored users (if they're old enough).
		$deleted_counts['ignored_users'] = $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . tqb_table_name( 'users' ) . ' WHERE ignore_user = 1 AND date_started < %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$cutoff_date
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Store last cleanup timestamp.
		update_option( 'tqb_last_auto_cleanup', time() );

		// Fire action hook for extensibility.
		do_action( 'tqb_after_auto_cleanup', $deleted_counts, $cutoff_date );

		return $deleted_counts;
	}

	/**
	 * Calculate the cutoff date based on duration and unit.
	 *
	 * @param int    $duration Number of time units.
	 * @param string $unit     Time unit (days, weeks, months).
	 *
	 * @return string|false Date in MySQL format or false on error.
	 */
	private static function calculate_cutoff_date( $duration, $unit ) {
		$valid_units = array( 'days', 'weeks', 'months' );

		if ( ! in_array( $unit, $valid_units, true ) ) {
			return false;
		}

		// Use DateTime arithmetic for accurate calculation.
		try {
			$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
			// Use singular for 1 unit, plural otherwise.
			$unit_str = rtrim( $unit, 's' );
			$modifier = "-{$duration} {$unit_str}";
			$date->modify( $modifier );
			return $date->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get last cleanup info for admin display.
	 *
	 * @return array
	 */
	public static function get_cleanup_info() {
		$last_cleanup   = get_option( 'tqb_last_auto_cleanup', 0 );
		$next_scheduled = wp_next_scheduled( self::CRON_HOOK );

		return array(
			'last_cleanup'   => $last_cleanup,
			'next_scheduled' => $next_scheduled,
			'is_scheduled'   => (bool) $next_scheduled,
		);
	}

	/**
	 * Manually trigger cleanup (for testing or admin action).
	 *
	 * @return array|null Deleted counts.
	 */
	public static function manual_cleanup() {
		return self::cleanup_old_stats();
	}
}

// Initialize the cleanup manager.
TQB_Stats_Cleanup_Manager::init();

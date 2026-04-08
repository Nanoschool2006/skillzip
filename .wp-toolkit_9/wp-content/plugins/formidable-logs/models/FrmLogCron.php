<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class FrmLogCron.
 *
 * @since 1.0.1
 */
class FrmLogCron extends FrmLog {

	/**
	 * FrmLogCron instance.
	 *
	 * @since 1.0.1
	 *
	 * @var FrmLogCron|null $instance
	 */
	private static $instance;

	/**
	 * Get the instance of class.
	 *
	 * @since 1.0.1
	 *
	 * @return FrmLogCron
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 1.0.1
	 */
	public function __construct() {
		$this->hooks();

		$auto_clear_status = $this->is_enabled();
		// Eliminate next cron from triggering.
		if ( ! $auto_clear_status && wp_next_scheduled( 'frmlog_auto_clear' ) ) {
			wp_clear_scheduled_hook( 'frmlog_auto_clear' );
		}

		if ( $auto_clear_status && ! wp_next_scheduled( 'frmlog_auto_clear' ) ) {
			wp_schedule_event( $this->get_next_cron_date_gmt(), 'frmlog_auto_clear_monthly', 'frmlog_auto_clear' );
		}
	}

	/**
	 * Purge log hooks.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function hooks() {
		if ( $this->is_enabled() ) {
			add_filter( 'cron_schedules', array( $this, 'add_monthly_cron_schedule' ) );
			add_action( 'frmlog_auto_clear', array( $this, 'cron' ) );
		}
	}

	/**
	 * Add Frm logs cron schedule.
	 *
	 * @since 1.0.1
	 *
	 * @param array<mixed> $schedules WP cron schedules.
	 * @return array<mixed>
	 */
	public function add_monthly_cron_schedule( $schedules ) {
		$schedules['frmlog_auto_clear_monthly'] = array(
			'interval' => MONTH_IN_SECONDS,
			'display'  => __( 'Purge the logs in a monthly period', 'formidable-logs' ),
		);

		return $schedules;
	}

	/**
	 * Frm logs cron callback.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public function cron() {
		$this->delete_frmlogs_posts_metadata();
	}

	/**
	 * Check if auto purge is enabled in settings.
	 *
	 * @since 1.0.1
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$frmlog_settings = FrmLogAppHelper::get_settings();
		return (bool) $frmlog_settings->auto_clear_log;
	}

	/**
	 * Get next cron occurrence date.
	 *
	 * @since 1.0.1
	 *
	 * @return int
	 */
	private function get_next_cron_date_gmt() {
		if ( is_callable( 'FrmAppHelper::filter_gmt_offset' ) ) {
			FrmAppHelper::filter_gmt_offset();
		}

		$date = absint( strtotime( '+1 month' ) - ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) );
		return $date ? $date : absint( current_time( 'timestamp' ) );
	}

}

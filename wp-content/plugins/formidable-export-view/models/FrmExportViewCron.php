<?php


class FrmExportViewCron {

	/**
	 * Clears existing cron job and sets up new cron job.
	 *
	 * @param object $settings Settings object.
	 */
	public static function set_up_export_view_cron_job( $settings ) {
		// Clear existing cron job.
		self::delete_export_view_cron_job();
		if ( ! empty( $settings->export_view_id ) ) {
			wp_schedule_event( time(), 'frm_export_view_schedule', 'frm_export_view_cron' );
		}
	}

	/**
	 * Creates CSV exports based on the settings.
	 */
	public static function frm_export_view_cron() {
		$frm_settings = new FrmExportViewGlobalSettings();
		$view_ids     = (array) $frm_settings->settings->export_view_id;
		if ( ! $view_ids || count( $view_ids ) === 0 ) {
			error_log( esc_html__( 'No Formidable Views selected to be exported automatically' ) );

			return;
		}
		try {
			foreach ( $view_ids as $view_id ) {
				FrmExportViewCSVController::export_csv( $view_id, false, true );
			}
		} catch ( Exception $exception ) {
			error_log( $exception->getMessage() );
		}
	}

	/**
	 * Clears existing cron jobs hooked to frm_export_view_cron.
	 */
	public static function delete_export_view_cron_job() {
		wp_clear_scheduled_hook( 'frm_export_view_cron' );
	}

	/**
	 * Maybe updates the cron schedules array to include a schedule for Export Views.
	 *
	 * @param array $schedules The array of schedule options for cron jobs, like 'hourly' or 'daily'.
	 *
	 * @return array Array of schedules which may include a custom schedule for Export Views
	 */
	public static function create_cron_schedule( $schedules ) {
		$frm_settings = new FrmExportViewGlobalSettings();
		$settings     = $frm_settings->settings;

		if ( ! empty( $settings->export_view_id ) && ! empty( $settings->frequency ) && ! empty( $settings->frequency_period ) ) {
			$schedules['frm_export_view_schedule'] = array(
				'interval' => self::get_interval( $settings->frequency, $settings->frequency_period ),
				// translators: %1$s - frequency, %2$s - recurring period.
				'display'  => sprintf( esc_html__( 'Every %1$s %2$s', 'formidable-export-view' ), $settings->frequency, $settings->frequency_period ),
			);
		}

		return $schedules;
	}

	/**
	 * Converts settings to an interval in seconds.
	 *
	 * @param int    $frequency The number of times the interval should occur before the next cron job.
	 * @param string $period    The interval: days or months.
	 *
	 * @return float|int The number of seconds in the new interval.
	 */
	public static function get_interval( $frequency, $period ) {
		$interval = ( 24 * 60 * 60 ) * $frequency;
		if ( 'months' === $period ) {
			$interval = $interval * 30;
		}

		return $interval;
	}
}

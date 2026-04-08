<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class FrmLogAppHelper.
 *
 * @since 1.0.1
 */
class FrmLogAppHelper {

	/**
	 * Settings holder.
	 *
	 * @since 1.0.0
	 *
	 * @var FrmLogSettings|null $settings
	 */
	private static $settings;

	/**
	 * Plugin path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return dirname( dirname( __FILE__ ) );
	}

	/**
	 * Get the log settings
	 *
	 * @since 1.0.1
	 *
	 * @return FrmLogSettings
	 */
	public static function get_settings() {
		if ( ! isset( self::$settings ) ) {
			self::$settings = new FrmLogSettings();
		}
		return self::$settings;
	}

	/**
	 * Get buttons on top of frmlogs table.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public static function show_list_entry_buttons() {
		echo '<div class="actions alignleft frm-button-group">';
		self::download_csv_button();
		self::delete_all_button();
		echo '</div>';
	}

	/**
	 * Get CSV download button.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	private static function download_csv_button() {
		$page_params = array(
			'frm_action' => 0,
			'action'     => 'frm_log_generate_csv',
		);

		include self::plugin_path() . '/views/csv-button.php';
	}

	/**
	 * Get delete all frm log button .
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	private static function delete_all_button() {
		FrmAppHelper::include_svg();
		include self::plugin_path() . '/views/delete-button.php';
	}

	/**
	 * CSV export handler.
	 *
	 * @since 1.0.1
	 *
	 * @return void
	 */
	public static function csv() {
		if ( ! current_user_can( 'frm_view_entries' ) || ! wp_verify_nonce( FrmAppHelper::simple_get( '_wpnonce', '', 'sanitize_text_field' ), '-1' ) ) {
			$frm_settings = FrmAppHelper::get_settings();
			wp_die( esc_html( $frm_settings->admin_permission ) );
		}

		$csv_export = new FrmLogCSVExportHelper();

		// Add header row to file.
		$csv_export->print_csv_row(
			array(
				'ID',
				'Title',
				'Date',
				'Content',
				'Entry',
				'Action',
				'Code',
				'Message',
				'URL',
				'Request',
				'Headers',
			)
		);

		$page = 1;
		while ( true ) {
			$frmlogs = FrmLog::get_all_frmlogs( $page );

			if ( ! $frmlogs ) {
				break;
			}

			foreach ( $frmlogs as $post ) {
				$post_export = array(
					'id'           => $post['id'],
					'title'        => $post['title'],
					'date_created' => ! empty( $post['date_created'] ) && is_string( $post['date_created'] ) ? FrmAppHelper::get_formatted_time( $post['date_created'], 'Y-m-d H:i:s', ' ' ) : '',
					'content'      => $post['content'],
					'entry'        => isset( $post['fields']['frm_entry'][0] ) ? $post['fields']['frm_entry'][0] : '',
					'action'       => isset( $post['fields']['frm_action'][0] ) ? $post['fields']['frm_action'][0] : '',
					'code'         => isset( $post['fields']['frm_code'][0] ) ? $post['fields']['frm_code'][0] : '',
					'message'      => isset( $post['fields']['frm_message'][0] ) ? $post['fields']['frm_message'][0] : '',
					'url'          => isset( $post['fields']['frm_url'][0] ) ? $post['fields']['frm_url'][0] : '',
					'request'      => isset( $post['fields']['frm_request'][0] ) ? $post['fields']['frm_request'][0] : '',
					'headers'      => isset( $post['fields']['frm_headers'][0] ) ? $post['fields']['frm_headers'][0] : '',
				);
				unset( $post );

				$csv_export->print_csv_row( $post_export );
			}

			$page++;
		}

		// Close output file stream.
		$csv_export->close_stream();

		die;

	}
}

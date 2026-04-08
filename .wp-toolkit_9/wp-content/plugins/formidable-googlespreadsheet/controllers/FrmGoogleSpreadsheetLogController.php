<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Communication between logs addon and Google Sheets.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetLogController {

	/**
	 * Stores the list of logs.
	 *
	 * @since 1.0
	 * @var array<array<string>> logs.
	 */
	private $logs = array();

	/**
	 * Stores the most recently added data for each logs code.
	 *
	 * @since 1.0
	 * @var array<array<string>> Logs data.
	 */
	private $logs_data = array();

	/**
	 * Stores action param of Google sheet.
	 *
	 * @since 1.0
	 * @var null|object Action of form.
	 */
	private $action;

	/**
	 * Stores entry detail.
	 *
	 * @since 1.0
	 * @var null|object Entry.
	 */
	private $entry;

	/**
	 * Initializes the logs class.
	 *
	 * @since 1.0
	 *
	 * @param null|object $action    GoogleSheet action.
	 * @param null|object $entry     Entry.
	 */
	public function __construct( $action = null, $entry = null ) {
		if ( ! $this->is_log_installed() ) {
			return;
		}
		// Store the action and the entry.
		$this->action = $action;
		$this->entry  = $entry;
	}

	/**
	 * Set stores entry detail.
	 *
	 * @param  null|object $entry  Stores entry detail.
	 *
	 * @return  void
	 */
	public function set_entry( $entry ) {
		$this->entry = $entry;
	}

	/**
	 * Set action param of Google sheet.
	 *
	 * @param  null|object $action  Form action.
	 *
	 * @return  void
	 */
	public function set_action( $action ) {
		$this->action = $action;
	}

	/**
	 * Check if logs addon exist.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function is_log_installed() {
		return class_exists( 'FrmLog' );
	}

	/**
	 * Adds an log or appends an additional message to an existing log.
	 *
	 * @since 1.0
	 *
	 * @param string|int $code    Log code.
	 * @param string     $message Log message.
	 * @param array      $data    Optional. Log data.
	 * @return void
	 */
	public function add( $code, $message, $data = array() ) {
		if ( ! isset( $this->logs[ $code ] ) ) {
			$this->logs[ $code ] = array();
		}
		$this->logs[ $code ][] = $message;

		if ( ! empty( $data ) ) {
			$this->logs_data[ $code ] = $data;
		}
	}

	/**
	 * Add logs to FrmLog post type.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function send_logs() {
		if ( ! $this->is_log_installed() || ! $this->has_logs() ) {
			return;
		}

		$log = new FrmLog();

		foreach ( $this->get_logs_codes() as $key => $code ) {
			$data = $this->get_all_logs_data( $code );

			$log->add(
				array(
					'title'   => isset( $this->action->post_title ) ? sanitize_text_field( $this->action->post_title ) : esc_attr__( 'Google Sheets I/O', 'formidable-google-sheets' ),
					'content' => self::prepare_logs_data( $data ),
					'fields'  => array(
						'entry'   => isset( $this->entry->id ) ? $this->entry->id : '',
						'action'  => isset( $this->action->ID ) ? $this->action->ID : '',
						'code'    => sanitize_key( $code ),
						'message' => self::prepare_logs_data( $this->get_logs_messages( $code ) ),
						'url'     => isset( $data['url'] ) ? esc_url_raw( $data['url'] ) : '',
						'request' => ! empty( $data['request'] ) ? self::prepare_logs_data( $data['request'] ) : '',
						'headers' => ! empty( $data['headers'] ) ? self::prepare_logs_data( $data['headers'] ) : '',
					),
				)
			);
		}

		$this->reset_data();
	}

	/**
	 * Prepare data for logs addon.
	 *
	 * @since 1.0
	 *
	 * @param mixed|object $data  Additional data.
	 * @return mixed
	 */
	private static function prepare_logs_data( $data = array() ) {
		return map_deep( $data, 'wp_strip_all_tags' );
	}

	/**
	 * Verifies if the instance contains logs.
	 *
	 * @since 1.0
	 *
	 * @return bool If the instance contains logs.
	 */
	private function has_logs() {
		return ! empty( $this->logs );
	}

	/**
	 * Retrieves all logs codes.
	 *
	 * @since 1.0
	 *
	 * @return array List of logs codes, if available.
	 */
	private function get_logs_codes() {
		if ( ! $this->has_logs() ) {
			return array();
		}

		return array_keys( $this->logs );
	}

	/**
	 * Retrieves all log messages.
	 *
	 * @since 1.0
	 *
	 * @param string|int $code Retrieve messages matching code, if exists.
	 * @return string[] Log strings on success, or empty array if there are none.
	 */
	private function get_logs_messages( $code ) {
		return isset( $this->logs[ $code ] ) ? $this->logs[ $code ] : array();
	}

	/**
	 * Retrieves all logs data for an logs code in the order in which the data was added.
	 *
	 * @since 1.0
	 *
	 * @param string|int $code logs code.
	 * @return array Array of logs data, if it exists.
	 */
	private function get_all_logs_data( $code ) {
		return isset( $this->logs_data[ $code ] ) ? $this->logs_data[ $code ] : array();
	}

	/**
	 * Reset all the settings and logs to default.
	 *
	 * @since 1.0
	 *
	 * @return void.
	 */
	private function reset_data() {
		$this->logs      = array();
		$this->logs_data = array();
	}
}

<?php
/**
 * Remote request transport using the WordPress WP_Http abstraction layer.
 *
 * @package formidable-google-sheets
 */

use \WP_Http as WP_Http;
use \WP_Error as WP_Error;
use \Traversable as Traversable;

/**
 * Class FrmGoogleSpreadsheetRemoteRequest.
 *
 * @since 1.0
 */
final class FrmGoogleSpreadsheetRemoteRequest implements FrmGoogleSpreadsheetRequest {

	/**
	 * Default timeout value to use in seconds.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	const DEFAULT_TIMEOUT = 5;

	/**
	 * Default number of retry attempts to do.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	const DEFAULT_RETRIES = 2;

	/**
	 * List of HTTP status codes that are worth retrying for.
	 *
	 * @since 1.0
	 *
	 * @var int[]
	 */
	const RETRYABLE_STATUS_CODES = array(
		WP_Http::REQUEST_TIMEOUT,
		WP_Http::LOCKED,
		WP_Http::TOO_MANY_REQUESTS,
		WP_Http::INTERNAL_SERVER_ERROR,
		WP_Http::SERVICE_UNAVAILABLE,
		WP_Http::GATEWAY_TIMEOUT,
	);

	/**
	 * Whether to verify SSL certificates or not.
	 *
	 * @since 1.0
	 *
	 * @var boolean
	 */
	private $ssl_verify;

	/**
	 * Timeout value to use in seconds.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * Number of retry attempts to do for an error that is worth retrying.
	 *
	 * @since 1.0
	 *
	 * @var int
	 */
	private $retries;

	/**
	 * Logs add on controller instance.
	 *
	 * @since 1.0
	 *
	 * @var FrmGoogleSpreadsheetLogController
	 */
	private $google_sheet_log;

	/**
	 * Logs data.
	 *
	 * @since 1.0
	 *
	 * @var array<mixed>
	 */
	private $log_data = array(
		'action' => 0,
		'entry'  => 0,
	);

	/**
	 * Instantiate a FrmGoogleSpreadsheetRemoteRequest object.
	 *
	 * @since 1.0
	 *
	 * @param FrmGoogleSpreadsheetLogController $log_controller    Number of retry attempts to do if a status code was thrown that is worth.
	 * @param bool                              $ssl_verify Whether to verify SSL certificates. Defaults to true.
	 * @param int                               $timeout    Timeout value to use in seconds. Defaults to 10.
	 * @param int                               $retries    Number of retry attempts to do if a status code was thrown that is worth.
	 *
	 * @return void
	 */
	public function __construct( FrmGoogleSpreadsheetLogController $log_controller, $ssl_verify = true, $timeout = self::DEFAULT_TIMEOUT, $retries = self::DEFAULT_RETRIES ) {
		if ( ! is_int( $timeout ) || $timeout < 0 ) {
			$timeout = self::DEFAULT_TIMEOUT;
		}

		if ( ! is_int( $retries ) || $retries < 0 ) {
			$retries = self::DEFAULT_RETRIES;
		}

		$this->ssl_verify       = $ssl_verify;
		$this->timeout          = $timeout;
		$this->retries          = $retries;
		$this->google_sheet_log = $log_controller;
	}

	/**
	 * Send logs to log add-on on destroying the class.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __destruct() {
		$this->google_sheet_log->send_logs();
	}

	/**
	 * Set log data.
	 *
	 * @param  array<mixed> $args  Logs arguments.
	 *
	 * @return  void
	 */
	public function set_logs_data( $args ) {
		$this->log_data = wp_parse_args( $args, $this->log_data );
		$this->google_sheet_log->set_action( $this->log_data['action'] );
		$this->google_sheet_log->set_entry( $this->log_data['entry'] );
	}

	/**
	 * Do a remote request to retrieve the contents of a remote URL.
	 *
	 * @since 1.0
	 *
	 * @param string $url     URL.
	 * @param array  $args Optional.
	 * @return void|object|array Response for the executed request.
	 * @throws Exception Throwable exception.
	 */
	public function request( $url, $args = array() ) {
		$retries_left = $this->retries;
		do {
			$defaults = array(
				'method'    => 'GET',
				'timeout'   => $this->timeout,
				'sslverify' => $this->ssl_verify,
			);

			$args = wp_parse_args( $args, $defaults );

			$response = wp_remote_request( $url, $args );

			$status = wp_remote_retrieve_response_code( $response );

			if ( $response instanceof WP_Error ) {
				$this->prepare_logs(
					array(
						'response' => $response,
						'request'  => $args,
						'headers'  => '',
					),
					$url,
					$status,
					$response->get_error_message()
				);
				throw new Exception( $response->get_error_message() );
			}

			if ( empty( $response['response']['code'] ) ) {
				$message = ! empty( $response['response']['message'] ) ? $response['response']['message'] : esc_html__( 'Unknown error', 'formidable-google-sheets' );
				$this->prepare_logs(
					array(
						'response' => $response,
						'request'  => $args,
						'headers'  => '',
					),
					$url,
					$status,
					$message
				);
				/* translators: %1$s: response message */
				throw new Exception( sprintf( esc_html__( 'Failed to fetch the contents: %1$s as it returned HTTP status 500.', 'formidable-google-sheets' ), $message ) );
			}

			if ( $status < 200 || $status >= 300 ) {
				if ( ! $retries_left || in_array( $status, self::RETRYABLE_STATUS_CODES, true ) === false ) {
					/* translators: %1$s: url, %2$s: status code */
					$message = sprintf( esc_html__( 'Failed to fetch the contents from the URL %1$s as it returned HTTP status %2$s.', 'formidable-google-sheets' ), $url, $status );

					$this->prepare_logs(
						array(
							'response' => $response,
							'request'  => $args,
							'headers'  => '',
						),
						$url,
						$status,
						$message
					);
					throw new Exception( $message );
				}

				continue;
			}

			$headers = $response['headers'];
			if ( $headers instanceof Traversable ) {
				$headers = iterator_to_array( $headers );
			}

			if ( ! is_array( $headers ) ) {
				$headers = array();
			}

			$this->prepare_logs(
				array(
					'response' => $response,
					'request'  => $args,
					'headers'  => $headers,
				),
				$url,
				$status,
				esc_html__( 'Successful request.', 'formidable-google-sheets' )
			);

			return $response;
		} while ( $retries_left-- );
	}

	/**
	 * Prepare data to add in logs addon.
	 *
	 * @since 1.0
	 *
	 * @param array<mixed> $data Remote Response.
	 * @param string       $url form Request URL.
	 * @param int|string   $status Response code.
	 * @param string       $message Message.
	 *
	 * @return void.
	 */
	private function prepare_logs( $data, $url, $status, $message ) {
		// Add a log to logs addon on failure.
		if ( $this->google_sheet_log->is_log_installed() ) {
			$this->google_sheet_log->add(
				$status,
				$message,
				array(
					'url'      => $url,
					'response' => $data['response'],
					'request'  => $data['request'],
					'headers'  => $data['headers'],
				)
			);
		}
	}

}

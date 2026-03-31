<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class FrmAIApi {

	/**
	 * The API route to call.
	 *
	 * Should exclude the domain, thus starting with "/wp-json/".
	 *
	 * @var string
	 */
	const API_ROUTE = '';

	/**
	 * License key.
	 *
	 * @var string
	 */
	protected static $license = '';

	/**
	 * Send the API request off and return the response.
	 *
	 * @param array $data The unsanitized data sent from the field js.
	 * @return array
	 */
	public static function get_response( $data ) {
		self::set_license();
		$data = static::prepare_request_data( $data );

		if ( empty( self::$license ) || ! $data ) {
			return array(
				'error' => self::get_error_message( 'frm_ai_invalid_request' ),
			);
		}

		$api_route = '';
		if ( static::class === FrmAIChatGPT::class ) {
			if ( 0 === strpos( $data['gpt_version'], 'claude-' ) ) {
				$api_route = '/wp-json/s11connect/v1/claude/';
			} elseif ( 0 === strpos( $data['gpt_version'], 'gemini-' ) ) {
				$api_route = '/wp-json/s11connect/v1/gemini/';
			}
		}

		$response = self::send_request(
			$data,
			array(
				'timeout' => 450, // phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
			),
			$api_route
		);

		// Check if the request was successful.
		if ( ! is_array( $response ) && is_wp_error( $response ) ) {
			// Handle error.
			return array(
				'error' => self::get_error_message( 'frm_ai_remote_post_error' ),
			);
		}

		// Retrieve the answer from the response.
		$answer = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! is_object( $answer ) ) {
			return array(
				'error' => self::get_error_message( 'frm_ai_invalid_answer' ),
			);
		}

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return array(
				'error' => self::get_error_message( $answer->code ?? 'frm_ai_remote_post_invalid_http_code', $answer ),
			);
		}

		return self::interpret_answer( $answer );
	}

	/**
	 * Get the license key from Formidable.
	 *
	 * @return void
	 */
	protected static function set_license() {
		if ( FrmAppHelper::pro_is_installed() && empty( self::$license ) ) {
			$updater       = FrmProAppHelper::get_updater();
			self::$license = $updater->license;
		}
	}

	/**
	 * Prepare the data to be sent in the request body.
	 *
	 * @param array $params The unsanitized values sent from the field js.
	 * @return array
	 */
	protected static function prepare_request_data( $params ) {
		// Retrieve the value of the fields to watch.
		if ( ! $params || empty( $params['question'] ) ) {
			return array();
		}

		$question = sanitize_text_field( $params['question'] );
		if ( empty( $question ) ) {
			return array();
		}

		return array(
			'question'    => $question,
			'gpt_version' => isset( $params['gpt_version'] ) ? $params['gpt_version'] : 'gpt-4o-mini',
		);
	}

	/**
	 * Send the API request off and return the response.
	 *
	 * @param array  $data The data to send to the API.
	 * @param array  $args Additional arguments for the request.
	 * @param string $api_route The API route to send the request to.
	 * @return array|WP_Error
	 */
	protected static function send_request( $data, $args = array(), $api_route = '' ) {
		$api_url   = 'https://api.strategy11.com';
		$api_route = $api_route ? $api_route : static::API_ROUTE;
		$endpoint  = $api_url . $api_route . '?l=' . urlencode( base64_encode( self::$license ) );

		$args = array_merge(
			array(
				'headers'    => array(
					'Content-Type' => 'application/json',
				),
				'body'       => wp_json_encode( $data ),
				'user-agent' => self::agent() . '; ' . get_bloginfo( 'url' ),
			),
			$args
		);

		// Send the request to the API endpoint.
		return wp_remote_post( $endpoint, $args );
	}

	/**
	 * Get the answer into a readable format.
	 *
	 * @param object $answer The answer from the API.
	 * @return array
	 */
	protected static function interpret_answer( $answer ) {
		// TODO: remove this once we deploy the new API, since we're making this correction there.
		if ( empty( $answer->message ) && ! empty( $answer->data ) ) {
			// @phpstan-ignore-next-line Ignore error `Access to an undefined property object::$message.`
			$answer->message = $answer->data;
		}

		if ( empty( $answer->message ) ) {
			return array(
				'error' => self::get_error_message( 'frm_ai_no_answer_found' ),
			);
		}

		return array(
			'success' => static::sanitize_answer( $answer->message ),
		);
	}

	/**
	 * Sanitize the response from the API.
	 *
	 * @param mixed $answer The unsanitized answer data.
	 * @return mixed
	 */
	abstract protected static function sanitize_answer( $answer );

	/**
	 * Get the user agent for the request.
	 *
	 * @return string
	 */
	protected static function agent() {
		$agent = 'formidable/' . FrmAppHelper::plugin_version();
		if ( class_exists( 'FrmProDb' ) ) {
			$agent = 'formidable-pro/' . FrmProDb::$plug_version;
		}
		return $agent;
	}

	/**
	 * Get a user-friendly error message.
	 *
	 * @param string $error_code The error code.
	 * @param object $response_body The body of API's response.
	 * @return string
	 */
	protected static function get_error_message( $error_code, $response_body = null ) {
		if ( ! current_user_can( 'frm_change_settings' ) ) {
			return __( 'We apologize, but this AI feature is not available at this time.', 'formidable-ai' );
		}

		if ( 'api_license_status_invalid' === $error_code && ! empty( $response_body->data->license_status ) ) {
			$specific_invalid_status_messages = array(
				'expired'  => sprintf(
					// translators: %1$s start link, %2$s end link.
					esc_html__( 'The license is expired. Please check out our %1$stroubleshooting guide%2$s for details on resolving this issue.', 'formidable-ai' ),
					'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array(), 'knowledgebase/manage-licenses-and-sites/renewing-an-expired-license/' ) ) . '">',
					'</a>'
				),
				'disabled' => sprintf(
					// translators: %1$s start link, %2$s end link.
					esc_html__( 'The license is disabled. Please check out our %1$stroubleshooting guide%2$s for details on resolving this issue.', 'formidable-ai' ),
					'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array(), 'knowledgebase/why-cant-i-activate-formidable-pro/' ) ) . '">',
					'</a>'
				),
			);

			if ( isset( $specific_invalid_status_messages[ $response_body->data->license_status ] ) ) {
				return $specific_invalid_status_messages[ $response_body->data->license_status ];
			}
		}

		$messages = array(
			'api_license_status_invalid' => sprintf(
				// translators: %1$s start link, %2$s end link.
				esc_html__( 'The license is invalid. Please check out our %1$stroubleshooting guide%2$s for details on resolving this issue.', 'formidable-ai' ),
				'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array( 'anchor' => 'kb-that-license-key-is-invalid' ), 'knowledgebase/why-cant-i-activate-formidable-pro/' ) ) . '">',
				'</a>'
			),
			'api_not_enough_credits'     => sprintf(
				// translators: %1$s start link, %2$s end link.
				esc_html__( 'The account doesn\'t have enough credits. Please %1$spurchase more%2$s.', 'formidable-ai' ),
				'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array(), 'buy-ai-credits/' ) ) . '">',
				'</a>'
			),
			'api_site_not_enabled'       => sprintf(
				// translators: %1$s start link, %2$s end link.
				esc_html__( 'This site is not authorized to use AI Credits. Please %1$smodify your account settings%2$s or contact the account owner to enable.', 'formidable-ai' ),
				'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array(), 'account/purchases/' ) ) . '">',
				'</a>'
			),
		);

		return $messages[ $error_code ] ?? sprintf(
			// translators: %1$s start link, %2$s end link.
			esc_html__( 'An error occurred. Please %1$scontact our support%2$s and mention the following code:', 'formidable-ai' ),
			'<a href="' . esc_url( FrmAppHelper::admin_upgrade_link( array(), 'new-topic/' ) ) . '">',
			'</a>'
		) . ' ' . sanitize_text_field( $error_code );
	}
}

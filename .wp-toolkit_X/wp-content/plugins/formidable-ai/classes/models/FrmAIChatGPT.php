<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrmAIChatGPT extends FrmAIApi {

	const API_ROUTE = '/wp-json/s11connect/v1/chatgpt/';

	/**
	 * Format the response as json.
	 *
	 * @param array $data The unsanitized data sent from the field js.
	 * @return void
	 */
	public static function get_json_response( $data ) {
		$response = self::get_response( $data );
		if ( ! empty( $response['error'] ) ) {
			wp_send_json_error( $response['error'] );
		} else {
			wp_send_json_success( $response['success'] );
		}
	}

	/**
	 * Prepare the data to be sent in the request body.
	 *
	 * @param array $params The unsanitized values sent from the field js.
	 * @return array
	 */
	protected static function prepare_request_data( $params ) {
		$data = parent::prepare_request_data( $params );

		if ( empty( $data ) ) {
			return array();
		}

		$data = array_merge(
			$data,
			array(
				'prompt'      => sanitize_text_field( $params['prompt'] ),
				'temperature' => 0.5,
				'gpt_version' => isset( $params['gpt_version'] ) ? $params['gpt_version'] : 'gpt-3.5-turbo',
			)
		);

		/**
		 * Filter the data sent to the API.
		 *
		 * @since 1.0
		 */
		return (array) apply_filters( 'frm_ai_data', $data );
	}

	/**
	 * Sanitize the response from ChatGPT.
	 *
	 * @param string $answer The unsanitized answer data.
	 * @return array<int, string>
	 */
	protected static function sanitize_answer( $answer ) {
		$answer = sanitize_textarea_field( $answer );

		$answer = array_filter( explode( "\n", $answer ) );
		$answer = array_values( $answer ); // Reset array keys.

		return $answer;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrmAIFormGenerator extends FrmAIApi {

	const API_ROUTE = '/wp-json/s11connect/v1/formgenerator/';

	/**
	 * Sanitize the response from ChatGPT.
	 * This is to make sure there is no harmful data in the response.
	 *
	 * @param stdClass $answer The unsanitized answer data.
	 * @return stdClass
	 */
	protected static function sanitize_answer( $answer ) {
		$sanitized_answer           = new stdClass();
		$sanitized_answer->fields   = array();
		$sanitized_answer->settings = array();

		if ( isset( $answer->fields ) ) {
			$sanitized_answer->fields = self::sanitize_fields( $answer->fields );
		}

		if ( isset( $answer->settings ) ) {
			$sanitized_answer->settings = self::sanitize_settings( $answer->settings );
		}

		return $sanitized_answer;
	}

	/**
	 * Sanitize the fields data from the ChatGPT response.
	 *
	 * @param array $fields An array of data for all fields generated.
	 * @return array
	 */
	private static function sanitize_fields( $fields ) {
		$sanitized_fields = array();
		$expected_keys    = array( 'name', 'type', 'options', 'required' );
		foreach ( $fields as $field ) {
			$sanitized_field = new stdClass();
			foreach ( $expected_keys as $key ) {
				if ( ! isset( $field->$key ) ) {
					continue;
				}

				switch ( $key ) {
					case 'name':
					case 'type':
						$sanitized_field->$key = sanitize_text_field( $field->$key );
						break;

					case 'options':
						// Options is an array.
						$sanitized_field->$key = array_reduce(
							$field->$key,
							function ( $total, $option ) {
								if ( is_array( $total ) && is_string( $option ) ) {
									$total[] = sanitize_text_field( $option );
								}
								return $total;
							},
							array()
						);
						break;

					case 'required':
						$sanitized_field->required = $field->$key ? 1 : 0;
						break;
				}
			}

			$sanitized_fields[] = $sanitized_field;
		}

		return $sanitized_fields;
	}

	/**
	 * Sanitize the form settings data. Currently this is just a single "name" property.
	 *
	 * @param array $settings The unsanitized settings data.
	 * @return array
	 */
	private static function sanitize_settings( $settings ) {
		$sanitized_settings = array();
		if ( is_array( $settings ) && $settings ) {
			$setting = reset( $settings );
			if ( is_object( $setting ) && isset( $setting->name ) ) {
				$sanitized_setting       = new stdClass();
				$sanitized_setting->name = sanitize_text_field( $setting->name );
				$sanitized_settings      = array( $sanitized_setting );
			}
		}
		return $sanitized_settings;
	}
}

<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Sendfox extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * Return api connection title
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Sendfox';
	}

	/**
	 * Output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'sendfox' );
	}

	/**
	 * Read data from post, test connection and save the details.
	 *
	 * Show error message on failure.
	 *
	 * @return mixed|Thrive_Dash_List_Connection_Abstract
	 */
	public function read_credentials() {
		if ( empty( $_POST['connection']['api_key'] ) ) {
			return $this->error( __( 'Api key is required', 'thrive-dash' ) );
		}

		$this->set_credentials( $this->post( 'connection' ) );

		$result = $this->test_connection();

		if ( true !== $result ) {
			return $this->error( __( 'Could not connect to Sendfox using provided api key.', 'thrive-dash' ) );
		}

		/**
		 * Finally, save the connection details.
		 */
		$this->save();

		return $this->success( __( 'Sendfox connected successfully', 'thrive-dash' ) );
	}

	/**
	 * Test connection to SendFox API.
	 *
	 * @return bool|string
	 */
	public function test_connection() {
		return is_array( $this->_get_lists() );
	}

	/**
	 * Add subscriber to SendFox list.
	 *
	 * @param string $list_identifier The list identifier.
	 * @param array  $arguments      The subscriber arguments.
	 *
	 * @return bool
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		try {
			$api = $this->get_api();

			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );

			$subscriber_args = array(
				'email'      => $arguments['email'],
				'first_name' => $first_name,
				'last_name'  => $last_name,
			);

			// Add custom fields if they exist and are available.
			$available_custom_fields = $this->get_api_custom_fields( array() );
			if ( ! empty( $available_custom_fields ) ) {
				$custom_fields = $this->_generateCustomFields( $arguments );

				// Add phone field mapping if phone is provided.
				if ( ! empty( $arguments['phone'] ) ) {
					$phone_field = $this->find_phone_custom_field( $available_custom_fields );
					if ( $phone_field ) {
						$custom_fields[] = array(
							'name'  => $phone_field['name'],
							'value' => sanitize_text_field( $arguments['phone'] ),
						);
					}
				}

				if ( ! empty( $custom_fields ) ) {
					$subscriber_args['contact_fields'] = $custom_fields;
				}
			}

			$api->add_subscriber( $list_identifier, $subscriber_args );

			return true;
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SendFox add_subscriber: Exception - ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get SendFox API instance.
	 *
	 * @return mixed|Thrive_Dash_Api_Sendfox
	 * @throws Exception When API key is missing.
	 */
	protected function get_api_instance() {
		$api_key = $this->param( 'api_key' );

		return new Thrive_Dash_Api_Sendfox( $api_key );
	}

	/**
	 * Get lists from SendFox API.
	 *
	 * @return array|bool
	 */
	protected function _get_lists() {
		$result = array();

		try {
			$api   = $this->get_api();
			$lists = $api->getLists();

			if ( isset( $lists['data'] ) && is_array( $lists['data'] ) ) {
				/* First page of lists */
				$result = $lists['data'];

				/* For multiple pages */
				if ( ! empty( $lists['total'] ) ) {
					$lists_total       = (int) $lists['total'];
					$list_per_page     = (int) $lists['per_page'];
					$pagination_needed = (int) ( $lists_total / $list_per_page ) + 1;

					/* Request pages >=2 and merge lists */
					if ( $pagination_needed >= 2 ) {
						for ( $i = 2; $i <= $pagination_needed; $i++ ) {
							$response_pages = $api->getListsOnPage( $i );

							if ( isset( $response_pages['data'] ) && is_array( $response_pages['data'] ) ) {
								$result = array_merge( $result, $response_pages['data'] );
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SendFox DEBUG: Error getting lists: ' . $e->getMessage() );
			}
		}

		return $result;
	}

	/**
	 * Get API custom fields.
	 *
	 * @param array $params  The parameters.
	 * @param bool  $force   Force refresh flag.
	 * @param bool  $get_all Get all fields flag.
	 *
	 * @return array|mixed
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		$cached_data = $this->get_cached_custom_fields();

		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$custom_data = array();

		try {
			$api = $this->get_api();

			$custom_fields = $api->getContactFields();

			if ( empty( $custom_fields['data'] ) ) {
				$this->_save_custom_fields( $custom_data );

				return $custom_data;
			}

			foreach ( $custom_fields['data'] as $field ) {
				$normalized_field = $this->_normalizeCustomFields( $field );
				$custom_data[] = $normalized_field;
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SendFox DEBUG: Error getting custom fields: ' . $e->getMessage() );
			}
		}

		$this->_save_custom_fields( $custom_data );

		return $custom_data;
	}

	/**
	 * Normalize custom field data.
	 *
	 * @param array|object $field The field data.
	 *
	 * @return array
	 */
	protected function _normalizeCustomFields( $field ) {
		$field = (array) $field;

		return array(
			'id'    => isset( $field['id'] ) ? (string) $field['id'] : '',
			'name'  => ! empty( $field['name'] ) ? $field['name'] : '',
			'type'  => 'custom',
			'label' => ! empty( $field['name'] ) ? $field['name'] : '',
		);
	}

	/**
	 * Generate custom fields array.
	 *
	 * @param array $args The arguments array.
	 *
	 * @return array
	 */
	private function _generateCustomFields( $args ) {
		$custom_fields = $this->get_api_custom_fields( array() );
		$ids           = $this->buildMappedCustomFields( $args );
		$result        = array();

		if ( empty( $custom_fields ) || empty( $ids ) ) {
			return $result;
		}

		// Create hash map for O(1) lookups instead of array_filter in loop.
		$custom_fields_map = array();
		foreach ( $custom_fields as $field ) {
			$custom_fields_map[ $field['id'] ] = $field;
		}

		foreach ( $ids as $key => $id ) {
			if ( ! isset( $custom_fields_map[ $id['value'] ] ) ) {
				continue;
			}

			$field = $custom_fields_map[ $id['value'] ];

			// Get the original field ID from the mapped data.
			$original_id = isset( $id['original_id'] ) ? $id['original_id'] : $key;

			// Use original field type for form field name construction to handle converted types.
			$form_field_type = isset( $id['original_type'] ) ? $id['original_type'] : $id['type'];
			$name            = $form_field_type . '_' . $original_id;
			$cf_form_name    = str_replace( '[]', '', $name );

			// Check if the form field exists before processing.
			if ( ! isset( $args[ $cf_form_name ] ) ) {
				continue;
			}

			$processed_value = $this->process_field( $args[ $cf_form_name ] );

			$result[] = array(
				'name'  => $field['name'],
				'value' => $processed_value,
			);
		}

		return $result;
	}

	/**
	 * Build mapped custom fields array based on form params.
	 *
	 * @param array $args The form arguments.
	 *
	 * @return array
	 */
	public function buildMappedCustomFields( $args ) {
		$mapped_data = array();

		// Should be always base_64 encoded of a serialized array.
		if ( empty( $args['tve_mapping'] ) || ! tve_dash_is_bas64_encoded( $args['tve_mapping'] ) || ! is_serialized( base64_decode( $args['tve_mapping'] ) ) ) {
			return $mapped_data;
		}

		$form_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );

		if ( empty( $form_data ) ) {
			return $mapped_data;
		}

		$mapped_fields = $this->get_mapped_field_ids();

		if ( empty( $mapped_fields ) ) {
			return $mapped_data;
		}

		// Create a regex pattern from all mapped fields for single-pass matching.
		$pattern_parts = array();
		foreach ( $mapped_fields as $mapped_field_name ) {
			$pattern_parts[] = preg_quote( $mapped_field_name, '/' );
		}
		$combined_pattern = '/^(' . implode( '|', $pattern_parts ) . ')_(.+)$/';

		// Single loop with regex pattern matching - no nested loops.
		foreach ( $form_data as $cf_form_name => $field_data ) {
			if ( empty( $field_data[ $this->_key ] ) ) {
				continue;
			}

			// Single regex match instead of nested loop.
			if ( preg_match( $combined_pattern, $cf_form_name, $matches ) ) {
				$mapped_field_name = $matches[1]; // The matched prefix.
				$field_id          = $matches[2]; // The extracted ID.

				// Clean the field_id by removing brackets if present (for checkbox fields).
				$clean_field_id = str_replace( '[]', '', $field_id );

				// Construct the actual field name that should exist in args (without brackets).
				$actual_field_name = $mapped_field_name . '_' . $clean_field_id;

				// Check if the actual form field exists in the arguments.
				if ( ! isset( $args[ $actual_field_name ] ) ) {
					continue;
				}

				// Convert country, state and checkbox fields to mapping_text for SendFox.
				$field_type = $mapped_field_name;
				if ( 'country' === $mapped_field_name || 'state' === $mapped_field_name || 'checkbox' === $mapped_field_name ) {
					$field_type = 'mapping_text';
				}

				// Use unique key to prevent overwrites when multiple field types map to same SendFox field.
				$unique_key = $field_type . '_' . $mapped_field_name . '_' . $clean_field_id;

				$mapped_data[ $unique_key ] = array(
					'type'          => $field_type,
					'value'         => $field_data[ $this->_key ],
					'original_type' => $mapped_field_name,
					'original_id'   => $clean_field_id,
				);

			}
		}

		return $mapped_data;
	}

	/**
	 * Add custom fields to a contact.
	 *
	 * @param string $email         The email address.
	 * @param array  $custom_fields The custom fields array.
	 * @param array  $extra         Extra data array.
	 *
	 * @return int|bool
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {
		try {
			$api     = $this->get_api();
			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;

			list( $first_name, $last_name ) = $this->get_name_parts( ! empty( $extra['name'] ) ? $extra['name'] : '' );

			$subscriber_args = array(
				'email'      => $email,
				'first_name' => $first_name,
				'last_name'  => $last_name,
			);

			if ( ! empty( $custom_fields ) ) {
				$subscriber_args['contact_fields'] = $this->prepare_custom_fields_for_api( $custom_fields );
			}

			$api->add_subscriber( $list_id, $subscriber_args );
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'SendFox add_custom_fields: Exception - ' . $e->getMessage() );
			}
			return false;
		}
	}

	/**
	 * Get available custom fields for this api connection.
	 *
	 * @param null $list_id The list ID.
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {

		return $this->get_api_custom_fields( null, true );
	}

	/**
	 * Prepare custom fields for api call.
	 *
	 * @param array $custom_fields   The custom fields array.
	 * @param null  $list_identifier The list identifier.
	 *
	 * @return array
	 */
	public function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {
		$prepared_fields = array();
		$api_fields      = $this->get_api_custom_fields( null, true );

		if ( empty( $custom_fields ) || empty( $api_fields ) ) {
			return $prepared_fields;
		}

		// Create hash map for O(1) lookups instead of nested loops.
		$api_fields_map = array();
		foreach ( $api_fields as $field ) {
			$api_fields_map[ (string) $field['id'] ] = $field;
		}

		foreach ( $custom_fields as $key => $custom_field ) {
			$field_id = (string) $key;
			if ( isset( $api_fields_map[ $field_id ] ) ) {
				$prepared_fields[] = array(
					'name'  => $api_fields_map[ $field_id ]['name'],
					'value' => $custom_field,
				);
			}
		}

		return $prepared_fields;
	}

	/**
	 * Whether the current integration can provide custom fields
	 *
	 * @return bool
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * Append custom fields to defaults.
	 *
	 * @param array $params The parameters.
	 *
	 * @return array
	 */
	public function get_custom_fields( $params = array() ) {
		return array_merge( parent::get_custom_fields(), $this->_mapped_custom_fields );
	}

	/**
	 * Find phone custom field in available fields.
	 *
	 * @param array $available_fields The available custom fields.
	 * @return array|false The phone field data or false if not found.
	 */
	private function find_phone_custom_field( $available_fields ) {
		// Create single regex pattern for all phone field patterns - no nested loops.
		$phone_pattern = '/phone|mobile|telephone|cell/i';

		// Use array_filter with single regex match for O(n) complexity.
		$phone_fields = array_filter(
			$available_fields,
			function ( $field ) use ( $phone_pattern ) {
				return preg_match( $phone_pattern, $field['name'] );
			}
		);

		// Return first matching phone field.
		return ! empty( $phone_fields ) ? reset( $phone_fields ) : false;
	}

	/**
	 * Process field value for SendFox API.
	 *
	 * @param mixed $field The field value to process.
	 *
	 * @return string
	 */
	public function process_field( $field ) {
		// Handle arrays (multi-select, checkboxes).
		if ( is_array( $field ) ) {
			// Filter out empty values and join with comma-space.
			$filtered_values = array_filter(
				$field,
				function ( $value ) {
					return ! empty( trim( $value ) );
				}
			);

			return implode( ', ', $filtered_values );
		}

		return stripslashes( (string) $field );
	}
}

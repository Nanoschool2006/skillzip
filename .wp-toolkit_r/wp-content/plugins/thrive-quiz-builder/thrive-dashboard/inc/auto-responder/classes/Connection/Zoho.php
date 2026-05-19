<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Thrive Dash List Connection for Zoho.
 */
class Thrive_Dash_List_Connection_Zoho extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * API title
	 *
	 * @return string
	 */
	public function get_title() {
		return 'Zoho';
	}

	/**
	 * Enable tags support.
	 *
	 * @return boolean
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function can_create_tags_via_api() {
		return true;
	}

	/**
	 * Output the setup form html.
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'zoho' );
	}

	/**
	 * Read and validate credentials.
	 *
	 * @return mixed|Thrive_Dash_List_Connection_Abstract
	 * @throws Exception When connection fails.
	 */
	public function read_credentials() {
		$this->set_credentials( $this->post( 'connection' ) );

		$result = $this->test_connection();

		if ( true !== $result ) {
			return $this->error( __( 'Could not connect to Zoho using provided credentials.', 'thrive-dash' ) );
		}

		$this->_save();
		$this->saveOauthCredentials();

		return $this->success( __( 'Zoho connected successfully', 'thrive-dash' ) );
	}

	/**
	 * Save OAuth details.
	 *
	 * @throws Exception When API connection fails.
	 */
	public function saveOauthCredentials() {
		$api = $this->get_api();

		$this->_credentials = array_merge( $this->_credentials, $api->getOauth()->getTokens() );

		$this->_save();
	}

	/**
	 * Remove access code value from credentials since it's only valid once.
	 * Save the connection details.
	 */
	private function _save() {
		unset( $this->_credentials['access_code'] );

		$this->save();
	}

	/**
	 * Test the connection to Zoho.
	 *
	 * @return bool|string
	 */
	public function test_connection() {
		return is_array( $this->_get_lists() );
	}

	/**
	 * Add subscriber to a list.
	 *
	 * @param string $list_identifier The list identifier.
	 * @param array  $arguments       The subscriber arguments.
	 *
	 * @return bool|mixed|string
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		try {
			$api = $this->get_api();

			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );

			$contact_info = array(
				'Contact Email' => $arguments['email'],
				'First Name'    => $first_name,
				'Last Name'     => $last_name,
			);

			if ( ! empty( $arguments['phone'] ) ) {
				$contact_info['Phone'] = sanitize_text_field( $arguments['phone'] );
			}

			$custom_fields = $this->_generateCustomFields( $arguments );
			$contact_info  = array_merge( $contact_info, $custom_fields );

			$args = array(
				'listkey'     => $list_identifier,
				'contactinfo' => wp_json_encode( $contact_info ),
			);

			$api_result = $api->add_subscriber( $args );

			// Handle tags if provided - tags need to be associated after contact is created.
			// Since Zoho returns XML that gets parsed as null, we check for tags after successful API call.
			if ( ! empty( $arguments['zoho_tags'] ) ) {
				$this->handle_subscriber_tags( $arguments['email'], $arguments['zoho_tags'], $list_identifier );
			}

			return true;
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Zoho add_subscriber: Exception - ' . $e->getMessage() );
			}
			return $e->getMessage();
		}
	}

	/**
	 * Get API instance.
	 *
	 * @return mixed|Thrive_Dash_Api_Zoho
	 * @throws Exception When API initialization fails.
	 */
	protected function get_api_instance() {
		return new Thrive_Dash_Api_Zoho( $this->get_credentials() );
	}

	/**
	 * Get lists.
	 *
	 * @return array|bool
	 */
	protected function _get_lists() {
		$lists = array();

		try {
			/**
			 * @var Thrive_Dash_Api_Zoho $api
			 */
			$api    = $this->get_api();
			$result = $api->getLists();

			if ( isset( $result['status'] ) && 'error' === $result['status'] ) {
				return false;
			}

			if ( ! empty( $result['list_of_details'] ) && is_array( $result['list_of_details'] ) ) {
				foreach ( $result['list_of_details'] as $list ) {
					$lists[] = array(
						'id'   => $list['listkey'],
						'name' => $list['listname'],
					);
				}
			}

			if ( $api->getOauth()->isAccessTokenNew() ) {
				$this->saveOauthCredentials();
			}
		} catch ( Exception $e ) {
			return false;
		}

		return $lists;
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
			/** @var Thrive_Dash_Api_Zoho $api */
			$api           = $this->get_api();
			$custom_fields = $api->getCustomFields();

			if ( empty( $custom_fields['response']['fieldnames']['fieldname'] ) ) {
				$this->_save_custom_fields( $custom_data );

				return $custom_data;
			}

			foreach ( $custom_fields['response']['fieldnames']['fieldname'] as $field ) {
				if ( 'custom' !== $field['TYPE'] ) {
					continue;
				}

				$custom_data[] = $this->_normalizeCustomFields( $field );
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Zoho: Exception in get_api_custom_fields - ' . $e->getMessage() );
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
			'id'    => isset( $field['FIELD_ID'] ) ? (string) $field['FIELD_ID'] : '',
			'name'  => ! empty( $field['FIELD_DISPLAY_NAME'] ) ? $field['FIELD_DISPLAY_NAME'] : '',
			'type'  => 'custom',
			'label' => ! empty( $field['FIELD_DISPLAY_NAME'] ) ? $field['FIELD_DISPLAY_NAME'] : '',
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

		// Create a lookup map to avoid nested loops - optimization for performance.
		$custom_fields_map = array();
		foreach ( $custom_fields as $custom_field ) {
			$custom_fields_map[ $custom_field['id'] ] = $custom_field;
		}

		foreach ( $ids as $key => $id ) {
			if ( ! isset( $custom_fields_map[ $id['value'] ] ) ) {
				continue;
			}

			$field = $custom_fields_map[ $id['value'] ];

			// Get the original field ID from the mapped data.
			$original_id = isset( $id['original_id'] ) ? $id['original_id'] : $key;

			// Use the original field type for building the form field name, not the converted type.
			$form_field_type = isset( $id['original_type'] ) ? $id['original_type'] : $id['type'];

			// Build the correct field name based on original field type and original ID.
			if ( false !== strpos( $form_field_type, 'mapping_' ) ) {
				// For mapping_ fields, use the format: mapping_type_originalid.
				$name = $form_field_type . '_' . $original_id;
			} else {
				// For other fields (country, state, number, date, etc.), use the format: type_originalid.
				$name = $form_field_type . '_' . $original_id;
			}
			$cf_form_name = str_replace( '[]', '', $name );

			// Check if the form field exists before processing.
			// For checkbox fields, we need to check without the [] suffix.
			$actual_form_field_name = str_replace( '[]', '', $cf_form_name );

			if ( ! isset( $args[ $actual_form_field_name ] ) ) {
				continue;
			}

			$processed_value          = $this->process_field( $args[ $actual_form_field_name ], $id['type'] );
			$result[ $field['name'] ] = $processed_value;
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
		$mapped_fields = $this->get_mapped_field_ids();

		foreach ( $mapped_fields as $mapped_field_name ) {
			// Extract an array with all custom fields (siblings) names from form data.
			// {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}.
			$cf_form_fields = preg_grep( "#^{$mapped_field_name}#i", array_keys( $form_data ) );

			if ( ! empty( $cf_form_fields ) && is_array( $cf_form_fields ) ) {

				foreach ( $cf_form_fields as $cf_form_name ) {
					if ( empty( $form_data[ $cf_form_name ][ $this->_key ] ) ) {
						continue;
					}

					$field_id = str_replace( $mapped_field_name . '_', '', $cf_form_name );

					// Check if the actual form field exists in the arguments
					// For checkbox fields, we need to check without the [] suffix
					$actual_field_name = str_replace( '[]', '', $cf_form_name );

					if ( ! isset( $args[ $actual_field_name ] ) ) {
						continue;
					}

					// Convert country, state, and number fields for proper Zoho mapping.
					$field_type = $mapped_field_name;
					if ( 'country' === $mapped_field_name || 'state' === $mapped_field_name ) {
						$field_type = 'mapping_text';
					} elseif ( 'number' === $mapped_field_name ) {
						$field_type = 'mapping_number';
					} elseif ( 'date' === $mapped_field_name ) {
						$field_type = 'mapping_date';
					}

					// Use a unique key that includes the original field type and ID to prevent overwrites.
					// This ensures country_132 and state_132 don't overwrite each other.
					$unique_key = $field_type . '_' . $mapped_field_name . '_' . $field_id;

					$mapped_data[ $unique_key ] = array(
						'type'          => $field_type,
						'value'         => $form_data[ $cf_form_name ][ $this->_key ],
						'original_id'   => $field_id,
						'original_type' => $mapped_field_name,
					);

				}
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
	 * @return int
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {
		try {
			/** @var Thrive_Dash_Api_Zoho $api */
			$api     = $this->get_api();
			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;

			list( $first_name, $last_name ) = $this->get_name_parts( ! empty( $extra['name'] ) ? $extra['name'] : '' );

			$cf = array(
				'Contact Email' => $email,
				'First Name'    => $first_name,
				'Last Name'     => $last_name,
			);

			$args = array(
				'listkey'     => $list_id,
				'contactinfo' => wp_json_encode( array_merge( $cf, $this->prepare_custom_fields_for_api( $custom_fields ) ) ),
			);

			$api->add_subscriber( $args );
		} catch ( Exception $e ) {
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

		foreach ( $api_fields as $field ) {
			foreach ( $custom_fields as $key => $custom_field ) {
				if ( (string) $field['id'] === (string) $key ) {
					$prepared_fields[ $field['name'] ] = $custom_field;
				}
			}

			if ( empty( $custom_fields ) ) {
				break;
			}
		}

		return $prepared_fields;
	}

	/**
	 * Handle tags for a subscriber.
	 *
	 * @param string $email           The subscriber email.
	 * @param string $tags_string     The tags string.
	 * @param string $list_identifier The list identifier.
	 * @return void
	 */
	private function handle_subscriber_tags( $email, $tags_string, $list_identifier ) {
		if ( empty( $tags_string ) ) {
			return;
		}

		try {
			// Parse tag names from comma-separated string.
			$tag_names = explode( ',', trim( $tags_string, ' ,' ) );
			$tag_names = array_map( 'trim', $tag_names );
			$tag_names = array_filter( $tag_names ); // Remove empty tags.

			if ( empty( $tag_names ) ) {
				return;
			}

			// Get or create tag names.
			$available_tag_names = $this->get_or_create_tag_names( $tag_names );

			if ( ! empty( $available_tag_names ) ) {
				// Associate each tag with the contact.
				$api = $this->get_api();
				foreach ( $available_tag_names as $tag_name ) {
					$associate_args = array(
						'tagName'    => $tag_name,
						'lead_email' => $email,
					);
					$result         = $api->associateTag( $associate_args );
				}
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Zoho: Failed to handle subscriber tags - ' . $e->getMessage() );
			}
		}
	}

	/**
	 * Get or create tag names.
	 *
	 * @param array $tag_names The tag names array.
	 * @return array
	 */
	private function get_or_create_tag_names( $tag_names ) {
		$available_tag_names = array();

		try {
			// Get all existing tags first.
			$api           = $this->get_api();
			$existing_tags = $api->getAllTags();
			$tag_map       = array();

			// Create a map of tag name => tag_name (case-insensitive).
			// Parse the nested tag structure from Zoho API by flattening first to eliminate nested loops.
			if ( ! empty( $existing_tags['tags'] ) && is_array( $existing_tags['tags'] ) ) {
				// Flatten all tag containers into a single array of tag details.
				$all_tag_details = call_user_func_array( 'array_merge', array_filter( $existing_tags['tags'], 'is_array' ) );

				// Process all tag details in a single loop.
				foreach ( $all_tag_details as $tag_details ) {
					if ( isset( $tag_details['tag_name'] ) ) {
						$tag_map[ strtolower( trim( $tag_details['tag_name'] ) ) ] = $tag_details['tag_name'];
					}
				}
			}

			// Process each tag name.
			foreach ( $tag_names as $tag_name ) {
				$tag_key = strtolower( trim( $tag_name ) );

				// Check if tag already exists.
				if ( isset( $tag_map[ $tag_key ] ) ) {
					$available_tag_names[] = $tag_map[ $tag_key ];
				} else {
					// Create new tag.
					$created_tag_name = $this->create_new_tag( $tag_name );
					if ( $created_tag_name ) {
						$available_tag_names[] = $created_tag_name;
					}
				}
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Zoho: Exception in get_or_create_tag_names - ' . $e->getMessage() );
			}
		}

		return $available_tag_names;
	}

	/**
	 * Create a new tag.
	 *
	 * @param string $tag_name The tag name.
	 * @return string|false Tag name on success, false on failure.
	 */
	private function create_new_tag( $tag_name ) {
		try {
			$api      = $this->get_api();
			$tag_data = array(
				'name' => trim( $tag_name ),
			);

			$result = $api->createTag( $tag_data );

			if ( isset( $result['status'] ) && 'success' === $result['status'] ) {
				// Clear the tags cache since we created a new tag
				$this->clearTagsCache();
				return trim( $tag_name );
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Zoho: Exception in create_new_tag for "' . $tag_name . '" - ' . $e->getMessage() );
			}
		}

		return false;
	}

	/**
	 * Process number field data to ensure proper Integer format for Zoho.
	 *
	 * @param mixed $field The number field data.
	 * @return string|int The processed number value.
	 */
	private function process_number_field( $field ) {
		if ( is_numeric( $field ) ) {
			return (int) $field;
		}

		if ( is_string( $field ) ) {
			$cleaned = preg_replace( '/[^0-9.\-]/', '', trim( $field ) );
			if ( is_numeric( $cleaned ) ) {
				return (int) floatval( $cleaned );
			}
		}

		if ( is_array( $field ) ) {
			foreach ( $field as $value ) {
				if ( is_numeric( $value ) ) {
					return (int) $value;
				}
			}
		}

		return 0;
	}

	/**
	 * Process date field data to ensure proper DateTime format for Zoho.
	 *
	 * @param mixed $field The date field data.
	 * @return string The processed date value in YYYY-MM-DD format.
	 */
	private function process_date_field( $field ) {
		if ( empty( $field ) ) {
			return '';
		}

		if ( is_array( $field ) ) {
			$date_string = reset( $field );
			if ( empty( $date_string ) ) {
				return '';
			}
			$date_string = (string) $date_string;
		} else {
			$date_string = (string) $field;
		}

		$date_string = trim( $date_string );

		$formats_to_try = array(
			// Date only formats
			'd/m/Y',
			'm/d/Y',
			'd-m-Y',
			'm-d-Y',
			'Y-m-d',
			'd.m.Y',
			'm.d.Y',
			'j/n/Y',
			'n/j/Y',
			'Y/m/d',
			'd M Y',
			'M d, Y',
			'F j, Y',
			// Date and time formats
			'd/m/Y H:i',
			'm/d/Y H:i',
			'd-m-Y H:i',
			'm-d-Y H:i',
			'Y-m-d H:i',
			'd/m/Y H:i:s',
			'm/d/Y H:i:s',
			'd-m-Y H:i:s',
			'm-d-Y H:i:s',
			'Y-m-d H:i:s',
			// 12-hour formats with AM/PM
			'd/m/Y g:i A',
			'm/d/Y g:i A',
			'd/m/Y h:i A',
			'm/d/Y h:i A',
			'd/m/Y g:i:s A',
			'm/d/Y g:i:s A',
			// ISO format variants
			'Y-m-d\TH:i:s',
			'Y-m-d\TH:i:s\Z',
			'c',
		);

		foreach ( $formats_to_try as $format ) {
			$date_obj = DateTime::createFromFormat( $format, $date_string );
			if ( false !== $date_obj ) {
				// If the format doesn't include time information, set to noon
				if ( false === strpos( $format, 'H' ) && false === strpos( $format, 'g' ) && false === strpos( $format, 'h' ) ) {
					$date_obj->setTime( 12, 0, 0 );
				}
				return $date_obj->format( 'Y-m-d\TH:i:s\Z' );
			}
		}

		$timestamp = strtotime( $date_string );
		if ( false !== $timestamp ) {
			$date_obj = new DateTime();
			$date_obj->setTimestamp( $timestamp );
			// Only set to noon if the original string doesn't contain time information
			if ( ! preg_match( '/\d{1,2}:\d{2}/', $date_string ) ) {
				$date_obj->setTime( 12, 0, 0 );
			}
			return $date_obj->format( 'Y-m-d\TH:i:s\Z' );
		}

		return $date_string;
	}

	/**
	 * Get automator add autoresponder mapping fields.
	 * Adds tag input support for the automator.
	 *
	 * @return array
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields', 'tag_input' ) );
	}

	/**
	 * Override process_field method to handle country fields properly.
	 *
	 * @param mixed  $field      The field value to process.
	 * @param string $field_type The type of the field (optional).
	 * @return string The processed field value.
	 */
	public function process_field( $field, $field_type = '' ) {

		// Handle country fields specifically.
		if ( false !== strpos( $field_type, 'country' ) ) {
			return $this->process_country_field( $field );
		}

		// Handle number fields specifically for Zoho Integer field type.
		if ( false !== strpos( $field_type, 'mapping_number' ) || false !== strpos( $field_type, 'number' ) ) {
			return $this->process_number_field( $field );
		}

		// Handle date fields specifically for Zoho DateTime field type.
		if ( false !== strpos( $field_type, 'mapping_date' ) || false !== strpos( $field_type, 'date' ) ) {
			return $this->process_date_field( $field );
		}

		return parent::process_field( $field );
	}

	/**
	 * Process country field data to extract the appropriate text value for Zoho.
	 *
	 * @param mixed $field The country field data.
	 * @return string The country name or code as text.
	 */
	private function process_country_field( $field ) {
		if ( is_string( $field ) && ! empty( $field ) ) {
			return stripslashes( $field );
		}

		if ( is_array( $field ) ) {
			$priority_keys = array(
				'country_name',
				'name',
				'full_name',
				'display_name',
				'label',
				'text',
				'country_code',
				'code',
				'iso',
				'alpha2',
				'alpha3',
				'value',
			);

			// Use array_intersect_key for better performance instead of nested loops.
			$available_values = array_intersect_key( $field, array_flip( $priority_keys ) );
			if ( ! empty( $available_values ) ) {
				$first_value = reset( $available_values );
				if ( ! empty( $first_value ) ) {
					return stripslashes( (string) $first_value );
				}
			}

			// Fallback to first scalar value.
			foreach ( $field as $value ) {
				if ( ! empty( $value ) && is_scalar( $value ) ) {
					return stripslashes( (string) $value );
				}
			}
		}

		if ( is_object( $field ) ) {
			return $this->process_country_field( (array) $field );
		}

		return stripslashes( (string) $field );
	}

	/**
	 * Gets a list of tags through GET /tag/getalltags API with 15-minute transient caching
	 *
	 * @return array
	 */
	public function getTags( $force = false ) {
		// Create a unique cache key based on API credentials
		$credentials = $this->get_credentials();
		$cache_key = 'zoho_tags_' . md5( serialize( $credentials ) );
		$cached_tags = false;

		// Try to get cached tags first if not force refresh.
		if ( ! $force ) {
			$cached_tags = get_transient( $cache_key );
		}

		if ( false !== $cached_tags && is_array( $cached_tags ) ) {
			// Sort cached tags alphabetically by value
			asort( $cached_tags );
			return $cached_tags;
		}

		$tags = array();

		try {
			/** @var Thrive_Dash_Api_Zoho $api */
			$api = $this->get_api();
			$response = $api->getAllTags();

			// Process the nested Zoho API response structure
			// Based on API docs: tags array contains objects with tag_id as key and tag details as value
			if ( is_array( $response ) && isset( $response['tags'] ) ) {
				// Flatten all tag containers into a single array to avoid nested loops
				$all_tag_details = array();
				foreach ( $response['tags'] as $tag_container ) {
					if ( is_array( $tag_container ) ) {
						$all_tag_details = array_merge( $all_tag_details, $tag_container );
					}
				}

				// Process all tag details in a single loop
				foreach ( $all_tag_details as $tag_id => $tag_details ) {
					if ( isset( $tag_details['tag_name'] ) ) {
						$tags[ $tag_id ] = $tag_details['tag_name'];
					}
				}
			}

			// Cache the tags for 15 minutes (900 seconds)
			if ( is_array( $tags ) && ! empty( $tags ) ) {
				// Sort tags alphabetically by value before caching
				asort( $tags );
				set_transient( $cache_key, $tags, 15 * MINUTE_IN_SECONDS );
			}
		} catch ( Exception $e ) {
			// If API call fails but we have expired cache, use it
			$expired_cache = get_transient( $cache_key . '_backup' );
			if ( false !== $expired_cache && is_array( $expired_cache ) ) {
				asort( $expired_cache );
				return $expired_cache;
			}
		}

		// Sort tags alphabetically by value
		if ( is_array( $tags ) && ! empty( $tags ) ) {
			asort( $tags );
			// Store a backup cache that doesn't expire for fallback
			set_transient( $cache_key . '_backup', $tags, YEAR_IN_SECONDS );
		}

		return $tags;
	}

	/**
	 * Clear the tags cache (useful when tags are created/updated)
	 *
	 * @return void
	 */
	public function clearTagsCache() {
		$credentials = $this->get_credentials();
		$cache_key = 'zoho_tags_' . md5( serialize( $credentials ) );

		delete_transient( $cache_key );
		delete_transient( $cache_key . '_backup' );
	}

	/**
	 * output any (possible) extra editor settings for this API
	 *
	 * @param array $params allow various different calls to this method
	 */
	public function get_extra_settings( $params = array(), $force = false ) {
		$params['tags'] = $this->getTags( $force );

		if ( ! is_array( $params['tags'] ) ) {
			$params['tags'] = array();
		}

		return $params;
	}

	/**
	 * Render the extra editor settings HTML for this API.
	 *
	 * @param array $params Parameters to customize the rendered settings.
	 */
	public function render_extra_editor_settings( $params = array() ) {
		$params['tags'] = $this->getTags();
		if ( ! is_array( $params['tags'] ) ) {
			$params['tags'] = array();
		}
		$this->output_controls_html( 'zoho/tags', $params );
	}

	/**
	 * Create tags if needed (called from editor when page is saved)
	 *
	 * @param array $params
	 * @return array 
	 */
	public function _create_tags_if_needed( $params ) {
		$tag_names = isset( $params['tag_names'] ) ? $params['tag_names'] : array();

		// Handle both array and comma-separated string.
		if ( is_string( $tag_names ) ) {
			$tag_names = explode( ',', $tag_names );
			$tag_names = array_map( 'trim', $tag_names );
		}

		// Filter out empty values
		$tag_names = array_filter( $tag_names );

		if ( empty( $tag_names ) ) {
			return array(
				'success' => true,
				'message' => __( 'No tags to create', 'thrive-dash' ),
				'tags_created' => 0
			);
		}

		try {
			// Get all existing tags to check for duplicates
			$existing_tags = $this->getTags( true ); // Force fresh fetch
			$existing_tag_names = array();

			foreach ( $existing_tags as $tag_id => $tag_name ) {
				$existing_tag_names[] = strtolower( $tag_name );
			}

			// Filter out tags that already exist (case-insensitive comparison)
			$new_tag_names = array();
			foreach ( $tag_names as $tag_name ) {
				$tag_name = trim( $tag_name );
				if ( ! empty( $tag_name ) && ! in_array( strtolower( $tag_name ), $existing_tag_names, true ) ) {
					$new_tag_names[] = $tag_name;
				}
			}

			if ( empty( $new_tag_names ) ) {
				return array(
					'success' => true,
					'message' => __( 'All tags already exist', 'thrive-dash' ),
					'tags_created' => 0
				);
			}

			// Create tags using Zoho API
			$created_tags = array();
			
			foreach ( $new_tag_names as $tag_name ) {
				$created_tag_name = $this->create_new_tag( $tag_name );
				if ( $created_tag_name ) {
					$created_tags[] = $tag_name;
				}
			}

			return array(
				'success' => true,
				'message' => sprintf(
					_n( '%d tag created successfully', '%d tags created successfully', count( $created_tags ), 'thrive-dash' ),
					count( $created_tags )
				),
				'tags_created' => count( $created_tags )
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}
	}
}

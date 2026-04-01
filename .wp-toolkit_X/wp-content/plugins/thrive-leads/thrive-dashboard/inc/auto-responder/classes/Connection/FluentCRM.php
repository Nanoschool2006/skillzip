<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

// FluentCRM CustomContactField class is loaded conditionally when needed.

class Thrive_Dash_List_Connection_FluentCRM extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Key used for mapping custom fields
	 *
	 * @var string
	 */
	protected $_key = '_field';
	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return 'FluentCRM';
	}

	/**
	 * @return bool
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function has_optin() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * check whether or not the FluentCRM plugin is installed
	 */
	public function pluginInstalled() {
		return function_exists( 'FluentCrmApi' );
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'fluentcrm' );
	}

	/**
	 * just save the key in the database
	 *
	 * @return mixed|void
	 */
	public function read_credentials() {
		if ( ! $this->pluginInstalled() ) {
			return $this->error( __( 'FluentCRM plugin must be installed and activated.', 'thrive-dash' ) );
		}

		$this->set_credentials( $this->post( 'connection', array() ) );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( '<strong>' . $result . '</strong>)' );
		}
		/**
		 * finally, save the connection details
		 */
		$this->save();

		return true;
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		if ( ! $this->pluginInstalled() ) {
			return __( 'FluentCRM plugin must be installed and activated.', 'thrive-dash' );
		}

		return true;
	}

	/**
	 * add a contact to a list
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		if ( ! $this->pluginInstalled() || ! function_exists( 'FluentCrmApi' ) ) {
			return __( 'FluentCRM plugin is not installed / activated', 'thrive-dash' );
		}

		$name_array = array();
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
			$name_array = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
			);
		}

		$prepared_args = array();
		if ( ! empty( $arguments['phone'] ) ) {
			$prepared_args['phone'] = sanitize_text_field( $arguments['phone'] );
		}

		// Handle default FluentCRM contact fields from direct arguments
		$default_fields = array( 'city', 'state', 'country', 'address_line_1', 'postal_code', 'date_of_birth' );
		foreach ( $default_fields as $field_name ) {
			if ( ! empty( $arguments[ $field_name ] ) ) {
				$prepared_args[ $field_name ] = sanitize_text_field( $arguments[ $field_name ] );
			}
		}

		if ( ! empty( $arguments['tve_mapping'] ) ) {
			// Use original automator logic when called from automator plugin
			if ( $this->is_automator_context() ) {
				// Original automator approach - simple and reliable
				$prepared_args['custom_values'] = $this->buildMappedCustomFields( $arguments );
			} else {
				// Enhanced TAR approach with field separation
				$mapped_fields = $this->buildMappedCustomFields( $arguments );
				
				// Separate FluentCRM default contact fields from custom fields
				$fluentcrm_default_fields = array( 'first_name', 'last_name', 'phone', 'country', 'state', 'city', 'address_line_1', 'address_line_2', 'postal_code', 'date_of_birth' );
				$custom_values = array();
				
				foreach ( $mapped_fields as $field_key => $field_value ) {
					if ( in_array( $field_key, $fluentcrm_default_fields, true ) ) {
						// Add to main contact data (these are FluentCRM's built-in contact fields)
						$prepared_args[ $field_key ] = $field_value;
					} else {
						// Add to custom values (these are custom fields created in FluentCRM)
						$custom_values[ $field_key ] = $field_value;
					}
				}
				
				if ( ! empty( $custom_values ) ) {
					$prepared_args['custom_values'] = $custom_values;
				}
			}
		}
		$prepared_args['tags'] = array();
		$prepared_args['status'] = 'subscribed';
		$tag_key               = $this->get_tags_key();
		if ( ! empty( $arguments[ $tag_key ] ) ) {
			$prepared_args['tags'] = $this->importTags( $arguments[ $tag_key ] );
		}

		if ( isset( $arguments['fluentcrm_optin'] ) && 'd' === $arguments['fluentcrm_optin'] ) {
			$prepared_args['status'] = 'pending';
		}

		$data = array(
			'email' => $arguments['email'],
			'lists' => array( $list_identifier ),
		);

		$data = array_merge( $data, $name_array, $prepared_args );

		try {
			$fluent  = FluentCrmApi( 'contacts' );
			$contact = $fluent->createOrUpdate( $data );

			if ( $contact->status === 'pending' ) {
				$contact->sendDoubleOptinEmail();
			}
		} catch ( Exception $exception ) {
			return $exception->getMessage();
		}

		return true;
	}

	/**
	 * Import tags
	 *
	 * @return array true for success or error message for failure
	 */
	public function importTags( $tags ) {
		$imported_tags = array();
		$inserted_tags = array();
		if ( ! empty( $tags ) ) {
			$tags = explode( ',', trim( $tags, ' ,' ) );

			foreach ( $tags as $tag ) {
				$inserted_tags[] = array(
					'title' => $tag,
				);
			}

			$inserted_tags = FluentCrmApi( 'tags' )->importBulk( $inserted_tags );//[1,2,3]

			foreach ( $inserted_tags as $new_tag ) {
				$imported_tags[] = $new_tag->id;

			}
		}

		return $imported_tags;
	}

	/**
	 * Build mapped custom fields array based on form params
	 *
	 * @param $args
	 *
	 * @return array
	 */
	public function buildMappedCustomFields( $args ) {

		$mapped_data = array();

		// Should be always base_64 encoded of a serialized array
		if ( empty( $args['tve_mapping'] ) || ! tve_dash_is_bas64_encoded( $args['tve_mapping'] ) || ! is_serialized( base64_decode( $args['tve_mapping'] ) ) ) {
			return $mapped_data;
		}

		$form_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );
		
		if ( is_array( $form_data ) ) {

			foreach ( $this->get_mapped_field_ids() as $mapped_field ) {

				// Extract an array with all custom fields (siblings) names from form data
				// {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}
				$custom_fields = preg_grep( "#^{$mapped_field}#i", array_keys( $form_data ) );

				// Matched "form data" for current allowed name
				if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {

					// Pull form allowed data, sanitize it and build the custom fields array
					foreach ( $custom_fields as $cf_name ) {

						if ( empty( $form_data[ $cf_name ][ $this->_key ] ) ) {
							continue;
						}

						$field_id = $form_data[ $cf_name ][ $this->_key ];
						
						// Check if this is a FluentCRM custom field (has fluentcrm key) or default field
						$is_custom_field = ! empty( $form_data[ $cf_name ]['fluentcrm'] );
						$actual_field_id = $is_custom_field ? $form_data[ $cf_name ]['fluentcrm'] : $field_id;

						$clean_field_name = str_replace( '[]', '', $cf_name );
						
						
						if ( ! empty( $args[ $clean_field_name ] ) ) {
							$args[ $clean_field_name ] = $this->process_field( $args[ $clean_field_name ] );
							
							// Get field type for proper sanitization from the original form_data key
							$field_type = ! empty( $form_data[ $cf_name ]['_field'] ) ? $form_data[ $cf_name ]['_field'] : 'text';
							
							// Convert mapping field types to FluentCRM field types
							$fluentcrm_field_type = $this->convert_mapping_type_to_fluentcrm_type( $field_type );
							
							// Use original cf_name for form_data lookup, but ensure we have a fallback
							$field_config = isset( $form_data[ $cf_name ] ) ? $form_data[ $cf_name ] : array();
							$field_config['type'] = $fluentcrm_field_type;
							
						$sanitized_value = $this->sanitize_custom_field_value( $args[ $clean_field_name ], $field_config );
						
						// Use the actual field ID (custom field slug or default field name)
						$mapped_data[ $actual_field_id ] = $sanitized_value;
						}

					}
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Sanitize custom field value based on field type.
	 *
	 * @param mixed $value The field value to sanitize.
	 * @param array $field_config The field configuration.
	 *
	 * @return mixed Sanitized field value.
	 */
	protected function sanitize_custom_field_value( $value, $field_config = array() ) {
		// Get field type from configuration if available
		$field_type = ! empty( $field_config['type'] ) ? $field_config['type'] : 'text';

		switch ( $field_type ) {
			case 'checkbox':
				// For checkbox fields, value should be an array or comma-separated string
				if ( is_array( $value ) ) {
					// Filter out empty values and sanitize
					$sanitized_values = array_filter( array_map( 'sanitize_text_field', $value ) );
					return array_values( $sanitized_values );
				}
				// If it's a string, split by comma and sanitize each part
				if ( is_string( $value ) ) {
					$values = explode( ',', $value );
					$sanitized_values = array_filter( array_map( 'trim', array_map( 'sanitize_text_field', $values ) ) );
					return array_values( $sanitized_values );
				}
				return array();

			case 'select-one':
			case 'radio':
				// Single selection fields
				return sanitize_text_field( $value );

			case 'number':
				return is_numeric( $value ) ? floatval( $value ) : 0;

			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'date':
				// Use robust timestamp approach for date conversion
				return $this->convert_date_to_fluentcrm_format( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Convert date string to FluentCRM format using timestamp approach.
	 * 
	 * @param string $date_string The date string to convert.
	 * 
	 * @return string Formatted date string in Y-m-d format or empty string on failure.
	 */
	protected function convert_date_to_fluentcrm_format( $date_string ) {
		if ( empty( $date_string ) || ! is_string( $date_string ) ) {
			return '';
		}

		// Clean up the date string.
		$date_string = trim( $date_string );

		// Check if already in correct format and valid.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_string ) ) {
			$date_parts = explode( '-', $date_string );
			if ( checkdate( (int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[0] ) ) {
				return $date_string;
			}
		}

		// Try to convert to timestamp using various date formats.
		$timestamp = $this->convert_date_to_timestamp( $date_string );

		if ( false !== $timestamp ) {
			// Convert timestamp to FluentCRM's expected Y-m-d format.
			return gmdate( 'Y-m-d', $timestamp );
		}

		// Log the failure for debugging purposes.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'FluentCRM: Failed to convert date "%s" to valid format', $date_string ) );
		}

		return '';
	}

	/**
	 * Convert various date formats to Unix timestamp.
	 * 
	 * @param string $date_string The date string to convert.
	 * 
	 * @return int|false Unix timestamp or false on failure.
	 */
	protected function convert_date_to_timestamp( $date_string ) {
		if ( empty( $date_string ) ) {
			return false;
		}

		// Clean up the date string.
		$date_string = trim( $date_string );

		// List of possible date formats to try.
		$formats = array(
			'Y-m-d',     // 2025-09-23 (ISO format)
			'd/m/Y',     // 23/09/2025 (European format)
			'd/m/y',     // 23/09/25
			'm/d/Y',     // 09/23/2025 (US format)
			'm/d/y',     // 09/23/25
			'd-m-Y',     // 23-09-2025
			'm-d-Y',     // 09-23-2025
			'd.m.Y',     // 23.09.2025 (German format)
			'm.d.Y',     // 09.23.2025
			'Y/m/d',     // 2025/09/23
			'j/n/Y',     // 23/9/2025 (no leading zeros)
			'n/j/Y',     // 9/23/2025 (no leading zeros)
			'j-n-Y',     // 23-9-2025 (no leading zeros)
			'n-j-Y',     // 9-23-2025 (no leading zeros)
		);

		// Try each format using DateTime::createFromFormat for precise parsing.
		foreach ( $formats as $format ) {
			try {
				$date = DateTime::createFromFormat( $format, $date_string );
			} catch ( Exception $e ) {
				$date = false;
			}
			if ( false !== $date && $date->format( $format ) === $date_string ) {
				// Validate the date is reasonable (not in the far future or past).
				$timestamp = $date->getTimestamp();
				$current_year = (int) gmdate( 'Y' );
				$date_year = (int) $date->format( 'Y' );
				
				// Allow dates from 1900 to 50 years in the future.
				if ( $date_year >= 1900 && $date_year <= ( $current_year + 50 ) ) {
					return $timestamp;
				}
			}
		}

		// Fallback to strtotime() for other formats, but with validation.
		$timestamp = strtotime( $date_string );
		if ( false !== $timestamp ) {
			// Additional validation for strtotime results.
			$date_year = (int) gmdate( 'Y', $timestamp );
			$current_year = (int) gmdate( 'Y' );
			
			// Ensure the year is reasonable.
			if ( $date_year >= 1900 && $date_year <= ( $current_year + 50 ) ) {
				return $timestamp;
			}
		}

		return false;
	}

	/**
	 * Convert TAR mapping field types to FluentCRM field types.
	 *
	 * @param string $mapping_type The mapping field type from TAR.
	 *
	 * @return string The corresponding FluentCRM field type.
	 */
	protected function convert_mapping_type_to_fluentcrm_type( $mapping_type ) {
		$type_mapping = array(
			'mapping_text'     => 'text',
			'mapping_url'      => 'url',
			'mapping_phone'    => 'text',
			'mapping_hidden'   => 'text',
			'mapping_checkbox' => 'checkbox',
			'mapping_select'   => 'select-one',
			'mapping_radio'    => 'radio',
			'mapping_textarea' => 'textarea',
			'mapping_number'   => 'number',
			'mapping_email'    => 'email',
			'mapping_date'     => 'date',
			'date'             => 'date',  // Support for direct date field type
			'number'           => 'number',  // Support for direct number field type
		);

		return isset( $type_mapping[ $mapping_type ] ) ? $type_mapping[ $mapping_type ] : 'text';
	}

	/**
	 * Detect if we're being called from the automator plugin.
	 *
	 * @return bool True if automator is calling, false otherwise.
	 */
	protected function is_automator_context() {
		// Currently disabled - both TAR and automator use enhanced functionality
		return false;
	}

	/**
	 * Get mapped field IDs for automator compatibility.
	 *
	 * @return array
	 */
	protected function get_mapped_field_ids() {
		// For automator compatibility, use original field list
		if ( $this->is_automator_context() ) {
			return array( 'mapping_text', 'mapping_url', 'mapping_phone', 'mapping_hidden' );
		}
		
		// For TAR and enhanced functionality, use extended field list
		return array( 
			'mapping_text', 'mapping_url', 'mapping_phone', 'mapping_hidden', 'mapping_checkbox', 'mapping_select', 'mapping_date', 'mapping_number',
			'date', 'number',  // Support for direct date and number fields (date_xxx, number_xxx)
			'country', 'state', 'city', 'address_line_1', 'address_line_2', 'postal_code', 'date_of_birth'
		);
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return mixed
	 */
	protected function get_api_instance() {
		// no API instance needed here
		return null;
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array|bool
	 */
	protected function _get_lists() {

		if ( ! $this->pluginInstalled() || ! function_exists( 'FluentCrmApi' ) ) {
			$this->_error = __( 'FluentCRM plugin could not be found.', 'thrive-dash' );

			return false;
		}

		$lists = array();

		$list_api = FluentCrmApi( 'lists' );

		// Get all the lists
		$all_lists = $list_api->all();

		foreach ( $all_lists as $list ) {
			$lists[] = array(
				'id'   => $list->id,
				'name' => $list->title,
			);
		}

		return $lists;
	}

	public function get_tags() {

		if ( ! $this->pluginInstalled() || ! function_exists( 'FluentCrmApi' ) ) {
			$this->_error = __( 'FluentCRM plugin could not be found.', 'thrive-dash' );

			return array();
		}

		$tags = array();

		$tag_api = FluentCrmApi( 'tags' );

		// Get all the tags
		$all_tags = $tag_api->all();

		foreach ( $all_tags as $tag ) {
			$tags[] = array(
				'id'       => $tag->id,
				'text'     => $tag->title,
				'selected' => false,
			);
		}

		return $tags;
	}

	/**
	 * Get available custom fields for TAR (Thrive Architect).
	 * This method is used by TAR to retrieve custom fields for form mapping.
	 *
	 * @param null $list_id List ID (not used by FluentCRM).
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {
		// Force refresh to ensure we get the latest fields from FluentCRM
		return $this->get_all_custom_fields( true );
	}

	/**
	 * Append custom fields to defaults
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function get_custom_fields( $params = array() ) {
		return array_merge( parent::get_custom_fields(), $this->_mapped_custom_fields );
	}

	/**
	 * @param      $params
	 * @param bool $force
	 * @param bool $get_all
	 *
	 * @return array|mixed
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		return $this->get_all_custom_fields( $force );
	}

	/**
	 * @param (bool) $force
	 *
	 * @return array|mixed
	 */
	public function get_all_custom_fields( $force ) {

		$custom_data = array();
		
		// Use original automator logic when called from automator
		if ( $this->is_automator_context() ) {
			// Original automator approach - only custom fields, text type only
			if ( class_exists( 'FluentCrm\App\Models\CustomContactField' ) ) {
				$cached_data = $this->get_cached_custom_fields();
				if ( false === $force && ! empty( $cached_data ) ) {
					return $cached_data;
				}

				try {
					$global_fields_data = ( new \FluentCrm\App\Models\CustomContactField() )->getGlobalFields();
					
					if ( isset( $global_fields_data['fields'] ) && is_array( $global_fields_data['fields'] ) ) {
						$custom_fields = $global_fields_data['fields'];

						if ( is_array( $custom_fields ) ) {
							foreach ( $custom_fields as $field ) {
								if ( ! empty( $field['type'] ) && $field['type'] === 'text' ) {
									$custom_data[] = $this->normalize_custom_field( $field );
								}
							}
						}
					}
				} catch ( Exception $e ) {
					// Ignore errors
				}
			}
		} else {
			// Enhanced TAR approach - include default fields + all custom field types
			$custom_data = array(
				array( 'id' => 'first_name', 'name' => 'First Name', 'type' => 'text', 'label' => 'First Name' ),
				array( 'id' => 'last_name', 'name' => 'Last Name', 'type' => 'text', 'label' => 'Last Name' ),
				array( 'id' => 'phone', 'name' => 'Phone', 'type' => 'text', 'label' => 'Phone' ),
				array( 'id' => 'city', 'name' => 'City', 'type' => 'text', 'label' => 'City' ),
				array( 'id' => 'state', 'name' => 'State', 'type' => 'text', 'label' => 'State' ),
				array( 'id' => 'country', 'name' => 'Country', 'type' => 'text', 'label' => 'Country' ),
				array( 'id' => 'address_line_1', 'name' => 'Address Line 1', 'type' => 'text', 'label' => 'Address Line 1' ),
				array( 'id' => 'address_line_2', 'name' => 'Address Line 2', 'type' => 'text', 'label' => 'Address Line 2' ),
				array( 'id' => 'postal_code', 'name' => 'Postal Code', 'type' => 'text', 'label' => 'Postal Code' ),
				array( 'id' => 'date_of_birth', 'name' => 'Date of Birth', 'type' => 'date', 'label' => 'Date of Birth' ),
			);
			
			if ( class_exists( 'FluentCrm\App\Models\CustomContactField' ) ) {
				$cached_data = $this->get_cached_custom_fields();
				if ( false === $force && ! empty( $cached_data ) ) {
					return $cached_data;
				}

				try {
					$global_fields_data = ( new \FluentCrm\App\Models\CustomContactField() )->getGlobalFields();
					
					if ( is_array( $global_fields_data ) && isset( $global_fields_data['fields'] ) && is_array( $global_fields_data['fields'] ) ) {
						$custom_fields = $global_fields_data['fields'];

						if ( is_array( $custom_fields ) ) {
							foreach ( $custom_fields as $field ) {
								// Ensure we have the required field data
								if ( ! empty( $field['slug'] ) && ! empty( $field['label'] ) && 
									 ! empty( $field['type'] ) && $this->is_supported_field_type( $field['type'] ) ) {
									$custom_data[] = $this->normalize_custom_field( $field );
								}
							}
						}
					}
				} catch ( Exception $e ) {
					// Ignore errors
				}
			}
		}

		$this->_save_custom_fields( $custom_data );

		return $custom_data;
	}

	/**
	 * Check if field type is supported by FluentCRM integration.
	 *
	 * @param string $field_type The field type to check.
	 *
	 * @return bool True if supported, false otherwise.
	 */
	protected function is_supported_field_type( $field_type ) {
		$supported_types = array(
			'text',
			'textarea',
			'select-one',
			'checkbox',
			'radio',
			'number',
			'date',
			'url',
			'email',
		);

		return in_array( $field_type, $supported_types, true );
	}

	/**
	 * Normalize custom field data
	 *
	 * @param $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {

		$field = (array) $field;

		$normalized = array(
			'id'    => ! empty( $field['slug'] ) ? sanitize_text_field( $field['slug'] ) : '',
			'name'  => ! empty( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
			'type'  => ! empty( $field['type'] ) ? sanitize_text_field( $field['type'] ) : 'text',
			'label' => ! empty( $field['label'] ) ? sanitize_text_field( $field['label'] ) : '',
		);

		// Add options for checkbox and select fields
		if ( in_array( $field['type'], array( 'checkbox', 'select-one', 'radio' ), true ) && ! empty( $field['options'] ) ) {
			if ( is_array( $field['options'] ) ) {
				$normalized['options'] = array_map( 'sanitize_text_field', $field['options'] );
			} elseif ( is_string( $field['options'] ) ) {
				// Handle comma-separated options string
				$options = explode( ',', $field['options'] );
				$normalized['options'] = array_map( 'trim', array_map( 'sanitize_text_field', $options ) );
			}
		}

		return $normalized;
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '{{contact.email}}';
	}

	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields', 'optin', 'tag_input' ) );
	}
}
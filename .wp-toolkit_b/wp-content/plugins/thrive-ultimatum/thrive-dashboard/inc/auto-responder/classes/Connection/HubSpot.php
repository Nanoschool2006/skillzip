<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_HubSpot extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Key used for mapping custom fields
	 *
	 * @var string
	 */
	protected $_field_mapping_key = '_field';

	/**
	 * Static cache for custom fields to prevent multiple API calls in same request
	 *
	 * @var array
	 */
	private static $_fields_cache = array();

	/**
	 * API instance cache to prevent multiple instantiations per request
	 *
	 * @var mixed
	 */
	private $_api_instance = null;

	/**
	 * @return string the API connection title
	 */
	public function get_title() {
		return 'HubSpot';
	}

	/**
	 * @return bool
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_list_sub_title() {
		return __( 'Choose from the following contact lists', 'thrive-dash' );
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'hubspot' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		$connection = $this->post( 'connection' );
		
		// Add defensive checks to prevent fatal errors.
		if ( ! is_array( $connection ) ) {
			return $this->error( __( 'Invalid connection data provided', 'thrive-dash' ) );
		}
		
		if ( ! isset( $connection['key'] ) ) {
			return $this->error( __( 'Connection key is missing', 'thrive-dash' ) );
		}
		
		$key = $connection['key'];

		if ( empty( $key ) ) {
			return $this->error( __( 'You must provide a valid HubSpot key', 'thrive-dash' ) );
		}

		$this->set_credentials( $connection );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to HubSpot using the provided key (<strong>%s</strong>)', 'thrive-dash' ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'HubSpot connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		$api = $this->get_api();
		if ( ! $api ) {
			return 'Failed to initialize HubSpot API';
		}

		/**
		 * just try getting the static contact lists as a connection test
		 */
		try {
			$api->getContactLists(); // this will throw the exception if there is a connection problem
		} catch ( Thrive_Dash_Api_HubSpot_Exception $e ) {
			return $e->getMessage();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * instantiate the API code required for this connection
	 * Optimized with caching to prevent multiple instantiations
	 *
	 * @return mixed
	 */
	protected function get_api_instance() {
		// Return cached instance if available.
		if ( null !== $this->_api_instance ) {
			return $this->_api_instance;
		}

		$key     = $this->param( 'key' );
		$version = $this->param( 'version' );

		if ( empty( $key ) ) {
			return null;
		}

		try {
			if ( ! empty( $version ) && $version === '2' ) {
				if ( ! class_exists( 'Thrive_Dash_Api_HubSpotV2' ) ) {
					return null;
				}
				$this->_api_instance = new Thrive_Dash_Api_HubSpotV2( $key );
			} else {
				if ( ! class_exists( 'Thrive_Dash_Api_HubSpot' ) ) {
					return null;
				}
				$this->_api_instance = new Thrive_Dash_Api_HubSpot( $key );
			}
		} catch ( Exception $e ) {
			return null;
		}

		return $this->_api_instance;
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array|bool for error
	 */
	protected function _get_lists() {
		$api = $this->get_api();

		try {
			$lists        = array();
			$contactLists = $api->getContactLists();
			foreach ( $contactLists as $key => $item ) {
				$lists [] = array(
					'id'      => $item['listId'],
					'dynamic' => ! empty( $item['dynamic'] ),
					'name'    => $item['name'],
				);
			}

			return $lists;
		} catch ( Thrive_Dash_Api_HubSpot_Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * add a contact to a static list
	 * OPTIMIZED: Reduced API calls and improved data processing
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		// Early validation to prevent fatal errors.
		if ( empty( $arguments ) || ! is_array( $arguments ) ) {
			return new WP_Error( 'hubspot-error', 'Invalid arguments provided' );
		}

		if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
			return new WP_Error( 'hubspot-error', 'Valid email address required' );
		}

		// Get API instance once and reuse.
		$api = $this->get_api();
		if ( ! $api ) {
			return new WP_Error( 'hubspot-error', 'Cannot establish API connection' );
		}

		try {
			// Extract basic contact info with sanitization.
			$name  = ! empty( $arguments['name'] ) ? sanitize_text_field( $arguments['name'] ) : '';
			$phone = ! empty( $arguments['phone'] ) ? sanitize_text_field( $arguments['phone'] ) : '';
			$email = sanitize_email( $arguments['email'] );

			// Process custom fields and tags.
			$custom_fields = $this->process_custom_fields( $arguments );
			$tags          = $this->process_tags( $arguments );

			// OPTIMIZATION: Use single API call with all data instead of multiple calls.
			if ( method_exists( $api, 'registerToContactListWithFields' ) ) {
				// Validate critical data before API call.
				$validation_errors = $this->validate_api_data( $list_identifier, $email, $custom_fields, $tags );
				if ( ! empty( $validation_errors ) ) {
					return new WP_Error( 'hubspot-validation-error', 'Data validation failed: ' . implode( ', ', $validation_errors ) );
				}

				// Use optimized method that combines contact creation, custom fields, and tags in fewer API calls.
				return $api->registerToContactListWithFields( $list_identifier, $name, $email, $phone, $custom_fields, $tags );
			}

			// Fallback to original multiple API calls for backward compatibility.
			$result = $api->registerToContactList( $list_identifier, $name, $email, $phone );

			// Add custom fields and tags if API supports it.
			if ( ! empty( $custom_fields ) && method_exists( $api, 'update_contact_custom_fields' ) ) {
				$api->update_contact_custom_fields( $email, $custom_fields );
			}

			if ( ! empty( $tags ) && method_exists( $api, 'add_contact_tags' ) ) {
				$api->add_contact_tags( $email, $tags );
			}

			return $result;

		} catch ( Thrive_Dash_Api_HubSpot_Exception $e ) {
			return new WP_Error( 'hubspot-error', $e->getMessage() );
		} catch ( Exception $e ) {
			return new WP_Error( 'hubspot-error', $e->getMessage() );
		}
	}

	/**
	 * Get all custom fields from HubSpot - SIMPLIFIED
	 *
	 * @param bool $force Force fresh data from API.
	 *
	 * @return array
	 */
	public function get_all_custom_fields( $force = false ) {
		$cache_key = 'hubspot_custom_fields';
		
		if ( ! $force && isset( self::$_fields_cache[ $cache_key ] ) ) {
			return self::$_fields_cache[ $cache_key ];
		}

		$custom_data = $this->get_default_custom_fields();

		try {
			$api = $this->get_api();
			if ( $api && method_exists( $api, 'get_custom_fields' ) ) {
				$api_fields = $api->get_custom_fields();
				if ( is_array( $api_fields ) ) {
					foreach ( $api_fields as $field ) {
						$normalized = $this->normalize_field( $field );
						if ( $normalized['id'] && $normalized['label'] ) {
							$custom_data[] = $normalized;
						}
					}
				}
			}
		} catch ( Exception $e ) {
			// Use defaults on error.
		}

		self::$_fields_cache[ $cache_key ] = $custom_data;
		return $custom_data;
	}

	/**
	 * Get API custom fields - SIMPLIFIED
	 *
	 * @param array $params Parameters.
	 * @param bool  $force Force fresh data.
	 * @param bool  $get_all Get all fields.
	 *
	 * @return array
	 */
	public function get_api_custom_fields( $params = array(), $force = false, $get_all = false ) {
		$fields = $this->get_all_custom_fields( $force || ! empty( $params['force_refresh'] ) );
		
		// Convert to API format.
		$formatted = array();
		foreach ( $fields as $field ) {
			$formatted[] = array(
				'id'    => $field['id'],
				'name'  => $field['label'],
				'type'  => $field['type'],
				'label' => $field['label'],
			);
		}
		
		return $formatted;
	}

	/**
	 * Get available custom fields for this api connection (for API compatibility)
	 *
	 * @param null $list_id List ID.
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {
		// Always force refresh to ensure we get the latest fields.
		return $this->get_all_custom_fields( true );
	}

	/**
	 * Get default custom fields - SIMPLIFIED
	 *
	 * @return array
	 */
	protected function get_default_custom_fields() {
		return array(
			array( 'id' => 'company', 'label' => 'Company', 'type' => 'text' ),
			array( 'id' => 'website', 'label' => 'Website', 'type' => 'text' ),
			array( 'id' => 'jobtitle', 'label' => 'Job Title', 'type' => 'text' ),
			array( 'id' => 'phone', 'label' => 'Phone', 'type' => 'text' ),
			array( 'id' => 'city', 'label' => 'City', 'type' => 'text' ),
			array( 'id' => 'state', 'label' => 'State', 'type' => 'text' ),
		);
	}


	/**
	 * Normalize field data - SIMPLIFIED single method
	 *
	 * @param string|array $field Field data.
	 * @param string       $user_label Optional user label.
	 *
	 * @return array
	 */
	protected function normalize_field( $field, $user_label = '' ) {
		if ( is_string( $field ) ) {
			return array(
				'id'    => $field,
				'type'  => 'text',
				'label' => $user_label ?: $field,
			);
		}

		$field = (array) $field;

		// Get ID.
		$id = $field['name'] ?? $field['id'] ?? '';

		// Get label.
		$label = $user_label ?: $field['label'] ?? $field['displayName'] ?? ucwords( str_replace( array( '_', '-' ), ' ', $id ) );

		// Get type.
		$type = $this->map_field_type( $field['type'] ?? $field['fieldType'] ?? 'string' );

		return array(
			'id'    => $id,
			'type'  => $type,
			'label' => $label,
		);
	}

	/**
	 * Map HubSpot field types to our field types
	 * OPTIMIZED: Using static cache for repeated calls
	 *
	 * @param string $hubspot_type HubSpot field type.
	 *
	 * @return string
	 */
	protected function map_field_type( $hubspot_type ) {
		static $type_map = array(
			'string'       => 'text',
			'number'       => 'number',
			'date'         => 'date',
			'datetime'     => 'datetime',
			'enumeration'  => 'select',
			'bool'         => 'checkbox',
			'phone_number' => 'tel',
			'email'        => 'email',
		);

		return isset( $type_map[ $hubspot_type ] ) ? $type_map[ $hubspot_type ] : 'text';
	}

	/**
	 * Map our field types to HubSpot field types
	 * OPTIMIZED: Using static cache for repeated calls
	 *
	 * @param string $field_type Our field type.
	 *
	 * @return string
	 */
	protected function map_to_hubspot_type( $field_type ) {
		static $type_map = array(
			'text'     => 'string',
			'textarea' => 'string',
			'number'   => 'number',
			'date'     => 'date',
			'datetime' => 'datetime',
			'select'   => 'enumeration',
			'checkbox' => 'bool',
			'tel'      => 'phone_number',
			'email'    => 'email',
		);

		return isset( $type_map[ $field_type ] ) ? $type_map[ $field_type ] : 'string';
	}

	/**
	 * Process custom fields from form data - SIMPLIFIED
	 *
	 * @param array $arguments POST sent by optin form.
	 *
	 * @return array
	 */
	protected function process_custom_fields( $arguments ) {
		if ( empty( $arguments ) || ! is_array( $arguments ) ) {
			return array();
		}

		$fields = array();

		// Process simple custom fields.
		foreach ( $arguments as $key => $value ) {
			if ( 0 === strpos( $key, 'custom_fields[' ) && ! empty( $value ) ) {
				preg_match( '/custom_fields\[(.*?)\]/', $key, $matches );
				if ( ! empty( $matches[1] ) && isset( $arguments[ $matches[1] ] ) ) {
					$fields[ sanitize_key( $matches[1] ) ] = sanitize_text_field( $arguments[ $matches[1] ] );
				}
			}
		}

		// Process legacy mapping fields.
		if ( ! empty( $arguments['tve_mapping'] ) ) {
			$mapping = thrive_safe_unserialize( base64_decode( $arguments['tve_mapping'] ) );
			if ( is_array( $mapping ) ) {
				foreach ( $mapping as $form_field => $field_config ) {
					$form_field = str_replace( '[]', '', $form_field );
					$api_field = $field_config['hubspot'] ?? $field_config[ $this->_field_mapping_key ] ?? '';
					
					if ( $api_field && ! empty( $arguments[ $form_field ] ) ) {
						$value = $arguments[ $form_field ];
						
						// COMPREHENSIVE CHECKBOX HANDLING for HubSpot.
						if ( is_array( $value ) ) {
							$fields[ $api_field ] = $this->process_hubspot_checkbox_value( $value, $api_field, $field_config, $form_field );
						} else {
							$fields[ $api_field ] = $this->format_field_value( $value, $api_field, $field_config );
						}
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Build custom fields mapping for automations
	 *
	 * @param array $automation_data Automation data.
	 *
	 * @return array
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();
		if ( ! empty( $automation_data['api_fields'] ) && is_array( $automation_data['api_fields'] ) ) {
			foreach ( $automation_data['api_fields'] as $pair ) {
				if ( ! empty( $pair['key'] ) && ! empty( $pair['value'] ) ) {
					$value = sanitize_text_field( $pair['value'] );
					if ( $value ) {
						$mapped_data[ $pair['key'] ] = $value;
					}
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Add custom fields to a contact
	 *
	 * @param string $email Email address.
	 * @param array  $custom_fields Custom fields array.
	 * @param array  $extra Extra parameters.
	 *
	 * @return bool|mixed
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {
		// Validate input to prevent fatal errors.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return false;
		}

		try {
			$api = $this->get_api();
			if ( ! $api || ! method_exists( $api, 'update_contact_custom_fields' ) ) {
				return false;
			}

			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;
			$args    = array(
				'email' => $email,
			);

			if ( ! empty( $extra['name'] ) ) {
				$args['name'] = $extra['name'];
			}

			// Add subscriber first if list_id provided.
			if ( $list_id ) {
				$this->add_subscriber( $list_id, $args );
			}

			$prepared_fields = $this->prepare_custom_fields_for_api( $custom_fields );
			if ( ! empty( $prepared_fields ) ) {
				return $api->update_contact_custom_fields( $email, $prepared_fields );
			}

			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Prepare custom fields for API call
	 *
	 * @param array $custom_fields Custom fields array.
	 * @param null  $list_identifier List identifier (not used for HubSpot).
	 *
	 * @return array
	 */
	protected function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {
		$prepared_fields = array();

		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return $prepared_fields;
		}

		foreach ( $custom_fields as $field_name => $field_value ) {
			if ( ! empty( $field_name ) && is_string( $field_name ) && ! empty( $field_value ) ) {
				$prepared_fields[ sanitize_text_field( $field_name ) ] = sanitize_text_field( $field_value );
			}
		}

		return $prepared_fields;
	}

	/**
	 * Check if custom field exists (read-only, no creation) - SIMPLIFIED
	 *
	 * @param string $field_name Field name.
	 *
	 * @return bool
	 */
	public function custom_field_exists( $field_name ) {
		if ( empty( $field_name ) ) {
			return false;
		}

		$field_name = sanitize_key( $field_name );

		try {
			// Check if field exists in retrieved fields.
			$existing_fields = $this->get_all_custom_fields( false );
			foreach ( $existing_fields as $field ) {
				if ( $field['id'] === $field_name ) {
					return true;
				}
			}
		} catch ( Exception $e ) {
			// Ignore errors.
		}

		return false;
	}

	/**
	 * Get field information from TAR interface (read-only) - SIMPLIFIED
	 *
	 * @param array $field_data Field configuration array.
	 *
	 * @return array|WP_Error
	 */
	public function get_custom_field_info( $field_data ) {
		if ( empty( $field_data['name'] ) ) {
			return new WP_Error( 'missing_name', 'Field name is required.' );
		}

		$field_name = sanitize_key( $field_data['name'] );

		if ( $this->custom_field_exists( $field_name ) ) {
			// Get the field details from existing fields.
			$existing_fields = $this->get_all_custom_fields( false );
			foreach ( $existing_fields as $field ) {
				if ( $field['id'] === $field_name ) {
					return array(
						'success' => true,
						'field'   => $field,
						'message' => sprintf( 'Custom field "%s" found.', $field['label'] ),
					);
				}
			}
		}

		return new WP_Error( 'field_not_found', sprintf( 'Custom field "%s" does not exist in HubSpot. Please create it manually in HubSpot first.', $field_name ) );
	}

	/**
	 * Clear custom fields cache - SIMPLIFIED
	 *
	 * @return bool
	 */
	public function clear_custom_fields_cache() {
		self::$_fields_cache = array();
		return true;
	}

	/**
	 * Force refresh all HubSpot field data - SIMPLIFIED
	 *
	 * @return array
	 */
	public function force_refresh_fields() {
		return $this->get_all_custom_fields( true );
	}



	/**
	 * Process tags - SIMPLIFIED
	 *
	 * @param array $arguments POST sent by optin form.
	 *
	 * @return array
	 */
	protected function process_tags( $arguments ) {
		if ( empty( $arguments['hubspot_tags'] ) ) {
			return array();
		}

		$tags = explode( ',', $arguments['hubspot_tags'] );
		return array_filter( array_map( 'trim', $tags ) );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '{{contact.email}}';
	}

	/**
	 * Process checkbox values specifically for HubSpot API requirements.
	 *
	 * @param array  $value        The checkbox value array.
	 * @param string $api_field    The HubSpot field name.
	 * @param array  $field_config The field configuration.
	 * @param string $form_field   The form field name.
	 *
	 * @return string The properly formatted checkbox value for HubSpot.
	 */
	private function process_hubspot_checkbox_value( $value, $api_field, $field_config, $form_field ) {
		// Validate inputs to prevent fatal errors.
		if ( ! is_array( $value ) || empty( $value ) ) {
			return '';
		}

		if ( empty( $api_field ) || ! is_string( $api_field ) ) {
			return implode( ', ', $value );
		}

		if ( ! is_array( $field_config ) ) {
			$field_config = array();
		}

		if ( empty( $form_field ) ) {
			$form_field = '';
		}

		// Detect if this is a checkbox field.
		$is_checkbox = $this->is_checkbox_field( $api_field, $field_config, $form_field );
		
		if ( ! $is_checkbox ) {
			// Not a checkbox, use regular array processing.
			return implode( ', ', array_filter( array_map( 'strval', $value ) ) );
		}

		// Handle different HubSpot checkbox scenarios.
		$hubspot_field_type = $this->get_hubspot_field_type( $api_field );
		
		switch ( $hubspot_field_type ) {
			case 'single_checkbox':
			case 'booleancheckbox':
				// Single checkbox: return boolean or 'true'/'false'.
				return ! empty( $value ) ? 'true' : 'false';
				
			case 'select':
			case 'dropdown':
				// Dropdown/Select: use first value only (single selection).
				return ! empty( $value ) && isset( $value[0] ) ? trim( strval( $value[0] ) ) : '';
				
			case 'checkbox':
			case 'enumeration':
			default:
				// Multiple checkbox: normalize values and use semicolon separator.
				$cleaned_values = array();
				foreach ( $value as $val ) {
					if ( null === $val || '' === $val ) {
						continue;
					}
					$normalized = $this->normalize_checkbox_value( $val );
					if ( ! empty( $normalized ) ) {
						$cleaned_values[] = $normalized;
					}
				}
				return implode( ';', $cleaned_values );
		}
	}

	/**
	 * Detect if a field is a checkbox field using multiple methods.
	 *
	 * @param string $api_field    The HubSpot field name.
	 * @param array  $field_config The field configuration.
	 * @param string $form_field   The form field name.
	 *
	 * @return bool
	 */
	private function is_checkbox_field( $api_field, $field_config, $form_field ) {
		// Validate inputs to prevent fatal errors.
		if ( empty( $api_field ) || ! is_string( $api_field ) ) {
			return false;
		}

		if ( ! is_array( $field_config ) ) {
			$field_config = array();
		}

		if ( ! is_string( $form_field ) ) {
			$form_field = '';
		}

		// Method 1: Check field configuration.
		$field_type = $field_config['type'] ?? $field_config['_field_type'] ?? '';
		$field_value = $field_config['_field'] ?? '';
		
		if ( in_array( $field_type, array( 'mapping_checkbox', 'checkbox', 'boolean', 'bool' ), true ) ) {
			return true;
		}
		
		if ( 'mapping_checkbox' === $field_value ) {
			return true;
		}

		// Method 2: Check field names for checkbox indicators.
		$checkbox_indicators = array( 'checkbox', 'check', 'bool', 'boolean', 'tick' );
		foreach ( $checkbox_indicators as $indicator ) {
			if ( false !== stripos( $api_field, $indicator ) || false !== stripos( $form_field, $indicator ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize checkbox values for HubSpot API.
	 * Converts mixed boolean/string values to consistent string format.
	 *
	 * @param mixed $value The checkbox value to normalize.
	 *
	 * @return string The normalized value.
	 */
	private function normalize_checkbox_value( $value ) {
		// Handle boolean values.
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		
		// Handle string boolean representations.
		if ( is_string( $value ) ) {
			$lower_value = strtolower( trim( $value ) );
			
			// Convert common boolean strings to consistent format.
			switch ( $lower_value ) {
				case 'true':
				case '1':
				case 'yes':
				case 'on':
				case 'checked':
					return 'true';
					
				case 'false':
				case '0':
				case 'no':
				case 'off':
				case 'unchecked':
					return 'false';
					
				default:
					// Return the original string value (like 'C', 'D', 'Option A', etc.).
					return trim( $value );
			}
		}
		
		// Handle numeric values.
		if ( is_numeric( $value ) ) {
			return (string) $value;
		}
		
		// Fallback: convert to string.
		return (string) $value;
	}

	/**
	 * Get the HubSpot field type for a specific field.
	 *
	 * @param string $field_name The field name.
	 *
	 * @return string The HubSpot field type.
	 */
	private function get_hubspot_field_type( $field_name ) {
		if ( empty( $field_name ) || ! is_string( $field_name ) ) {
			return 'text';
		}

		try {
			$existing_fields = $this->get_all_custom_fields( false );
			if ( ! is_array( $existing_fields ) ) {
				return 'text';
			}
			
			foreach ( $existing_fields as $field ) {
				if ( is_array( $field ) && isset( $field['id'] ) && $field['id'] === $field_name ) {
					return $field['type'] ?? 'text';
				}
			}
		} catch ( Exception $e ) {
			// Ignore errors.
		}

		// Default to text if we can't determine the type.
		return 'text';
	}

	/**
	 * Validate API data before sending to HubSpot.
	 *
	 * @param mixed $list_identifier The list ID.
	 * @param string $email The email address.
	 * @param array $custom_fields The custom fields.
	 * @param array $tags The tags.
	 *
	 * @return array Array of validation errors (empty if valid).
	 */
	private function validate_api_data( $list_identifier, $email, $custom_fields, $tags ) {
		$errors = array();

		// Validate list identifier.
		if ( empty( $list_identifier ) || ! is_numeric( $list_identifier ) ) {
			$errors[] = 'Invalid list identifier: ' . $list_identifier;
		}

		// Validate email.
		if ( ! is_email( $email ) ) {
			$errors[] = 'Invalid email address: ' . $email;
		}

		// Validate custom fields.
		if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
			foreach ( $custom_fields as $field_name => $field_value ) {
				// Check for problematic field names.
				if ( 'createdate' === $field_name ) {
					$errors[] = 'createdate is a read-only HubSpot field and cannot be set via API';
				}

				// Check for empty field names.
				if ( empty( $field_name ) || ! is_string( $field_name ) ) {
					$errors[] = 'Invalid field name: ' . print_r( $field_name, true );
				}

				// Check if custom field actually exists in HubSpot.
				if ( ! $this->custom_field_exists( $field_name ) ) {
					// Provide specific guidance for checkbox fields.
					if ( false !== strpos( $field_name, 'checkbox' ) ) {
						$errors[] = 'Checkbox field "' . $field_name . '" does not exist in HubSpot. Please create it as a "Multiple checkboxes" or "Single checkbox" property in HubSpot Settings → Properties → Contact Properties.';
					} else {
						$errors[] = 'Custom field "' . $field_name . '" does not exist in HubSpot. Please create it manually in HubSpot first.';
					}
				}
			}
		}

		// Validate tags.
		if ( ! empty( $tags ) && ! is_array( $tags ) ) {
			$errors[] = 'Tags must be an array';
		}

		return $errors;
	}


	/**
	 * Format field value according to HubSpot requirements.
	 *
	 * @param mixed  $value        The field value to format.
	 * @param string $api_field    The HubSpot API field name.
	 * @param array  $field_config The field configuration.
	 *
	 * @return mixed The formatted value.
	 */
	private function format_field_value( $value, $api_field, $field_config ) {
		// Handle date fields - convert to ISO format.
		if ( $this->is_date_field( $api_field, $field_config ) ) {
			return $this->format_date_for_hubspot( $value );
		}

		// Handle boolean fields.
		if ( $this->is_boolean_field( $api_field, $field_config ) ) {
			return $this->format_boolean_for_hubspot( $value );
		}

		// Default: return sanitized string.
		return sanitize_text_field( $value );
	}

	/**
	 * Check if field is a date field.
	 *
	 * @param string $api_field    The HubSpot API field name.
	 * @param array  $field_config The field configuration.
	 *
	 * @return bool
	 */
	private function is_date_field( $api_field, $field_config ) {
		// Check by field name patterns.
		$date_field_patterns = array( 'date', 'created', 'modified', 'updated', 'birth' );
		foreach ( $date_field_patterns as $pattern ) {
			if ( false !== stripos( $api_field, $pattern ) ) {
				return true;
			}
		}

		// Check by field type.
		$field_type = $field_config['type'] ?? $field_config['_field_type'] ?? '';
		return in_array( $field_type, array( 'date', 'datetime' ), true );
	}

	/**
	 * Check if field is a boolean field.
	 *
	 * @param string $api_field    The HubSpot API field name.
	 * @param array  $field_config The field configuration.
	 *
	 * @return bool
	 */
	private function is_boolean_field( $api_field, $field_config ) {
		$field_type = $field_config['type'] ?? $field_config['_field_type'] ?? '';
		return in_array( $field_type, array( 'boolean', 'bool', 'checkbox' ), true );
	}

	/**
	 * Format date value for HubSpot API.
	 *
	 * @param string $date_value The date value to format.
	 *
	 * @return string The formatted date.
	 */
	private function format_date_for_hubspot( $date_value ) {
		if ( empty( $date_value ) ) {
			return '';
		}

		// Try to parse the date.
		$timestamp = strtotime( $date_value );
		if ( false === $timestamp ) {
			return $date_value; // Return original if parsing fails.
		}

		// Format as ISO 8601 date (YYYY-MM-DD).
		return gmdate( 'Y-m-d', $timestamp );
	}

	/**
	 * Format boolean value for HubSpot API.
	 *
	 * @param mixed $value The value to convert to boolean.
	 *
	 * @return bool
	 */
	private function format_boolean_for_hubspot( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$lower_value = strtolower( trim( $value ) );
			return in_array( $lower_value, array( '1', 'true', 'yes', 'on', 'checked' ), true );
		}

		return (bool) $value;
	}

}

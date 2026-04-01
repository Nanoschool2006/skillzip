<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Infusionsoft extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * @var array Supported custom field types
	 */
	protected $_custom_fields = array(
		1  => 'text',           // Text
		2  => 'textarea',       // Textarea
		3  => 'dropdown',       // Dropdown
		4  => 'radio',          // Radio buttons
		5  => 'checkbox',       // Checkbox (standard)
		6  => 'date',           // Date
		7  => 'datetime',       // Date/Time
		8  => 'phone',          // Phone
		9  => 'email',          // Email
		10 => 'currency',       // Currency
		11 => 'number',         // Number
		12 => 'percent',        // Percentage
		13 => 'social_security', // SSN
		14 => 'text',           // Text (additional)
		15 => 'text',           // Text (legacy)
		16 => 'dropdown',       // Dropdown (alternative)
		17 => 'checkbox',       // Checkbox (alternative type)
		18 => 'url',            // Website/URL
		19 => 'text',           // Text (extended)
		20 => 'textarea',       // Textarea (extended)
		21 => 'dropdown',       // Dropdown (extended)
		22 => 'radio',          // Radio (extended)
		23 => 'checkbox',       // Checkbox (list type)
		24 => 'date',           // Date (extended)
		25 => 'datetime',       // DateTime (extended)
	);

	/**
	 * @var array Keap contact fields supported by XML-RPC API
	 */
	protected $_supported_contact_fields = array(
		'FirstName', 'LastName', 'Email', 'Phone1', 'Phone2',
		'City', 'State', 'PostalCode', 'Country',
		'StreetAddress1', 'StreetAddress2',
		'Company', 'JobTitle', 'Website'
	);

	/**
	 * Constructor
	 */
	public function __construct( $key ) {
		parent::__construct( $key );
	}

	/**
	 * Return the connection type
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * Get connection title
	 */
	public function get_title() {
		return 'Keap (Infusionsoft)';
	}

	/**
	 * Get list subtitle
	 */
	public function get_list_sub_title() {
		return __( 'Choose your Tag', 'thrive-dash' );
	}

	/**
	 * Has tags support
	 */
	public function has_tags() {
		return true;
	}

	/**
	 * Has custom fields support
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * Output setup form
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'infusionsoft' );
	}

	/**
	 * Read and save credentials
	 */
	public function read_credentials() {
		$client_id = ! empty( $_POST['connection']['client_id'] ) ? sanitize_text_field( $_POST['connection']['client_id'] ) : '';
		$key       = ! empty( $_POST['connection']['api_key'] ) ? sanitize_text_field( $_POST['connection']['api_key'] ) : '';

		if ( empty( $key ) || empty( $client_id ) ) {
			return $this->error( __( 'Client ID and API key are required', 'thrive-dash' ) );
		}

		$this->set_credentials( array( 'client_id' => $client_id, 'api_key' => $key ) );

		$result = $this->test_connection();
		if ( true !== $result ) {
			return $this->error( sprintf( __( 'Could not connect to Keap: %s', 'thrive-dash' ), $result ) );
		}

		$this->save();
		return $this->success( 'Keap connected successfully' );
	}

	/**
	 * Test connection with enhanced error handling.
	 */
	public function test_connection() {
		try {
			// Check if we can even get an API instance
			$api = $this->get_api();
			if ( false === $api ) {
				return 'Failed to create API instance';
			}
			
			$result = $this->_get_lists();
			return is_array( $result ) ? true : $result;
			
		} catch ( Exception $e ) {
			error_log( 'Keap API Test Connection Exception: ' . $e->getMessage() );
			return 'Connection test failed: ' . $e->getMessage();
		} catch ( Error $e ) {
			error_log( 'Keap API Test Connection Fatal Error: ' . $e->getMessage() );
			return 'Fatal error during connection test';
		}
	}

	/**
	 * Check if connection is safe to use (prevents fatal errors).
	 *
	 * @return bool True if connection is safe to use.
	 */
	public function is_connection_safe() {
		// Check if required credentials exist
		if ( empty( $this->param( 'client_id' ) ) || empty( $this->param( 'api_key' ) ) ) {
			return false;
		}
		
		// Try to get API instance - let autoloader handle class loading
		try {
			$api = $this->get_api();
			return false !== $api && is_object( $api );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}
	}

	/**
	 * Get API instance with fatal error prevention.
	 */
	protected function get_api_instance() {
		// Check if required parameters exist
		$client_id = $this->param( 'client_id' );
		$api_key   = $this->param( 'api_key' );
		
		if ( empty( $client_id ) || empty( $api_key ) ) {
			return false;
		}
		
		try {
			// Try to instantiate - let autoloader handle class loading
			return new Thrive_Dash_Api_Infusionsoft( $client_id, $api_key );
		} catch ( Exception $e ) {
			error_log( 'Keap API Error: Failed to instantiate API class - ' . $e->getMessage() );
			return false;
		} catch ( Error $e ) {
			error_log( 'Keap API Fatal Error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get tags as lists with enhanced error prevention.
	 */
	protected function _get_lists() {
		try {
			$api = $this->get_api();
			
			// Check if API instance is valid
			if ( false === $api || ! is_object( $api ) ) {
				return 'Failed to get API instance';
			}
			
			$response = $api->data( 'query', 'ContactGroup', 1000, 0, array( 'GroupName' => '%' ), array( 'Id', 'GroupName' ) );

			
			if ( empty( $response ) || ! is_array( $response ) ) {
				return array();
			}

			$lists = array();
			foreach ( $response as $item ) {
				// Validate array structure before accessing
				if ( ! is_array( $item ) || ! isset( $item['Id'] ) || ! isset( $item['GroupName'] ) ) {
					continue;
				}
				
				$lists[] = array(
					'id'   => sanitize_text_field( $item['Id'] ),
					'name' => sanitize_text_field( $item['GroupName'] ),
				);
			}

			return $lists;

		} catch ( Exception $e ) {
			error_log( 'Keap API Exception in _get_lists: ' . $e->getMessage() );
			return $e->getMessage();
		} catch ( Error $e ) {
			error_log( 'Keap API Fatal Error in _get_lists: ' . $e->getMessage() );
			return 'Fatal error occurred while fetching lists';
		}
	}

	/**
	 * Add subscriber with tags support and enhanced error prevention.
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		try {
			// Validate required arguments
			if ( empty( $arguments ) || ! is_array( $arguments ) ) {
				return false;
			}
			
			if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
				return false;
			}
			
			$api = $this->get_api();
			
			// Check if API instance is valid
			if ( false === $api || ! is_object( $api ) ) {
				return false;
			}
			
			// API uses __call magic method for contact() calls
			
			// Prepare basic contact data
			$data = array( 'Email' => sanitize_email( $arguments['email'] ) );
			
			// Add name fields
			if ( ! empty( $arguments['name'] ) ) {
				list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
				$data['FirstName'] = sanitize_text_field( $first_name );
				$data['LastName'] = sanitize_text_field( $last_name );
			}
			
			// Add phone
			if ( ! empty( $arguments['phone'] ) ) {
				$data['Phone1'] = sanitize_text_field( $arguments['phone'] );
			}

			// Add basic mapped default fields only (XML-RPC limitations)
			if ( ! empty( $arguments['tve_mapping'] ) ) {
				$default_fields = $this->get_basic_default_fields( $arguments );
				if ( is_array( $default_fields ) ) {
					$data = array_merge( $data, $default_fields );
				}
			}

			// Create/update contact
			$contact_id = $api->contact( 'addWithDupCheck', $data, 'Email' );
			
			if ( $contact_id ) {
				// Opt in email
				$api->APIEmail( 'optIn', $data['Email'], 'thrive opt in' );
				
				// Load contact to check existing groups
				$contact = $api->contact( 'load', $contact_id, array( 'Id', 'Email', 'Groups' ) );
				$existing_groups = empty( $contact['Groups'] ) ? array() : explode( ',', $contact['Groups'] );

				// Add to main tag
				if ( ! in_array( $list_identifier, $existing_groups ) ) {
					$api->contact( 'addToGroup', $contact_id, $list_identifier );
				}
				
				// Handle additional tags
				$tag_key = $this->get_tags_key();
				if ( ! empty( $arguments[ $tag_key ] ) ) {
					$tag_ids = $this->import_tags( $arguments[ $tag_key ] );
					foreach ( $tag_ids as $tag_id ) {
						$api->contact( 'addToGroup', $contact_id, $tag_id );
					}
				}
				
				// Note: XML-RPC API has limited support for default fields beyond basic contact info
				
				// Handle custom fields
				if ( ! empty( $arguments['tve_mapping'] ) || ! empty( $arguments['automator_custom_fields'] ) ) {
					$this->update_custom_fields( $contact_id, $arguments );
				}
			}

			return true;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Create or get tag if it doesn't exist
	 */
	public function create_tag( $tag_name ) {
		try {
			$api = $this->get_api();
			
			// Check if tag exists
			$existing = $api->data( 'query', 'ContactGroup', 1, 0, 
				array( 'GroupName' => $tag_name ), 
				array( 'Id', 'GroupName' ) 
			);
			
			if ( ! empty( $existing ) ) {
				return $existing[0]['Id'];
			}
			
			// Create new tag
			$tag_id = $api->data( 'add', 'ContactGroup', array( 'GroupName' => $tag_name ) );
			
			return $tag_id;
			
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Import tags (create if they don't exist)
	 */
	public function import_tags( $tags ) {
		$imported_tag_ids = array();
		
		if ( empty( $tags ) ) {
			return $imported_tag_ids;
		}

		$tag_names = explode( ',', trim( $tags, ' ,' ) );

		foreach ( $tag_names as $tag_name ) {
			$tag_name = trim( $tag_name );
			if ( ! empty( $tag_name ) ) {
				$tag_id = $this->create_tag( $tag_name );
				if ( $tag_id ) {
					$imported_tag_ids[] = $tag_id;
				}
			}
		}

		return $imported_tag_ids;
	}

	/**
	 * Get custom fields with default fields always available and enhanced safety.
	 */
	public function get_api_custom_fields( $params = array(), $force = false, $get_all = false ) {
		// Serve from cache
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		// Default Keap fields (always available)
		$fields = array(
			array( 'id' => 'FirstName', 'name' => 'First Name', 'type' => 'text', 'label' => 'First Name' ),
			array( 'id' => 'LastName', 'name' => 'Last Name', 'type' => 'text', 'label' => 'Last Name' ),
			array( 'id' => 'Phone1', 'name' => 'Phone', 'type' => 'phone', 'label' => 'Phone' ),
			array( 'id' => 'City', 'name' => 'City', 'type' => 'text', 'label' => 'City' ),
			array( 'id' => 'State', 'name' => 'State', 'type' => 'text', 'label' => 'State' ),
			array( 'id' => 'PostalCode', 'name' => 'Postal Code', 'type' => 'text', 'label' => 'Postal Code' ),
			array( 'id' => 'Country', 'name' => 'Country', 'type' => 'text', 'label' => 'Country' ),
		);

		// Only try to get custom fields if connection is safe
		if ( $this->is_connection_safe() ) {
			// Add custom fields from API
			try {
				foreach ( array_keys( $this->_custom_fields ) as $field_id ) {
					$api_fields = $this->get_custom_fields_by_type( $field_id );
					if ( is_array( $api_fields ) ) {
						$fields = array_merge( $fields, $api_fields );
					}
				}
			} catch ( Exception $e ) {
				error_log( 'Keap API Exception in get_api_custom_fields: ' . $e->getMessage() );
				// Continue with default fields only
			} catch ( Error $e ) {
				error_log( 'Keap API Fatal Error in get_api_custom_fields: ' . $e->getMessage() );
				// Continue with default fields only
			}
		}

		$this->_save_custom_fields( $fields );
		return $fields;
	}

	/**
	 * Get custom fields by DataType ID with enhanced checkbox support.
	 */
	protected function get_custom_fields_by_type( $field_id ) {
		try {
			$api = $this->get_api();
			
			// Check if API instance is valid
			if ( false === $api || ! is_object( $api ) ) {
				return array();
			}
			
			// Check if API has required method (data calls use __call magic method)
			if ( ! method_exists( $api, 'data' ) && ! method_exists( $api, '__call' ) ) {
				return array();
			}
			
			$field_id = (int) $field_id;
			
			// Define checkbox field types (Keap has multiple checkbox types)
			$checkbox_types = array( 5, 17, 23 ); // Standard, Alternative, and List checkbox types
			$is_checkbox = in_array( $field_id, $checkbox_types, true );
			
			$response = array();
			
			// Strategy 1: Try standard query with GroupId filter (works for most fields)
			if ( ! $is_checkbox ) {
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id, 'GroupId' => '~<>~0' ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}
			
			// Strategy 2: For checkbox fields or if standard query failed, try without GroupId filter
			if ( empty( $response ) ) {
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}
			
			// Strategy 3: For checkbox fields, try with different GroupId conditions
			if ( empty( $response ) && $is_checkbox ) {
				// Try with GroupId = 0 (some checkbox fields have GroupId 0)
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id, 'GroupId' => 0 ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}
			
			// Strategy 4: Last resort - query all fields and filter by DataType
			if ( empty( $response ) && $is_checkbox ) {
				try {
					$all_fields = $api->data(
						'query',
						'DataFormField',
						500, // Increase limit for comprehensive search
						0,
						array(), // No filters - get all
						array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
					);
					
					// Filter for our specific field type
					$response = array();
					if ( ! empty( $all_fields ) ) {
						foreach ( $all_fields as $field ) {
							if ( isset( $field['DataType'] ) && (int) $field['DataType'] === $field_id ) {
								$response[] = $field;
							}
						}
					}
				} catch ( Exception $e ) {
					// Continue with empty response
				}
			}

			if ( empty( $response ) ) {
				return array();
			}

			$fields = array();
			foreach ( $response as $field ) {
				$normalized_field = $this->normalize_custom_field( $field );
				if ( ! empty( $normalized_field['id'] ) ) {
					$fields[] = $normalized_field;
				}
			}

			return $fields;

		} catch ( Exception $e ) {
			error_log( 'Keap XML-RPC Error for field type ' . $field_id . ': ' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Normalize custom field data with enhanced validation.
	 */
	protected function normalize_custom_field( $field ) {
		$field = (array) $field;

		// Validate required fields
		if ( empty( $field['Name'] ) && empty( $field['Id'] ) ) {
			return array(); // Skip fields without proper identification
		}

		$field_type = 'text';
		$data_type_id = ! empty( $field['DataType'] ) ? (int) $field['DataType'] : 15;
		
		if ( array_key_exists( $data_type_id, $this->_custom_fields ) ) {
			$field_type = $this->_custom_fields[ $data_type_id ];
		}

		// Use field name or construct from ID if name is missing
		$field_name = '';
		if ( ! empty( $field['Name'] ) ) {
			$field_name = $field['Name'];
		} elseif ( ! empty( $field['Id'] ) ) {
			$field_name = '_' . $field['Id']; // Prefix custom field IDs
		}

		// Use label or construct from name if label is missing
		$field_label = '';
		if ( ! empty( $field['Label'] ) ) {
			$field_label = $field['Label'];
		} elseif ( ! empty( $field['Name'] ) ) {
			$field_label = ucwords( str_replace( array( '_', '-' ), ' ', $field['Name'] ) );
		} elseif ( ! empty( $field['Id'] ) ) {
			$field_label = 'Custom Field ' . $field['Id'];
		}

		return array(
			'id'        => $field_name,
			'name'      => $field_label,
			'type'      => $field_type,
			'label'     => $field_label,
			'data_type' => $data_type_id, // Keep original data type for debugging
			'group_id'  => isset( $field['GroupId'] ) ? $field['GroupId'] : null, // For debugging
		);
	}

	/**
	 * Debug method to get all custom fields with detailed information.
	 * Use this to troubleshoot checkbox field issues.
	 *
	 * @return array Detailed information about all custom fields.
	 */
	public function debug_get_all_custom_fields() {
		if ( ! $this->is_connected() ) {
			return array( 'error' => 'Not connected to Keap API' );
		}

		$debug_info = array(
			'field_types_checked' => array(),
			'total_fields_found'  => 0,
			'checkbox_fields'     => array(),
			'all_fields'          => array(),
		);

		try {
			$api = $this->get_api();
			
			// Check each field type
			foreach ( array_keys( $this->_custom_fields ) as $field_id ) {
				$field_type_name = $this->_custom_fields[ $field_id ];
				$debug_info['field_types_checked'][] = "Type {$field_id} ({$field_type_name})";
				
				$fields = $this->get_custom_fields_by_type( $field_id );
				
				if ( ! empty( $fields ) ) {
					$debug_info['all_fields'][ $field_id ] = array(
						'type_name' => $field_type_name,
						'count'     => count( $fields ),
						'fields'    => $fields,
					);
					
					$debug_info['total_fields_found'] += count( $fields );
					
					// Track checkbox fields specifically
					if ( 'checkbox' === $field_type_name ) {
						$debug_info['checkbox_fields'][ $field_id ] = $fields;
					}
				}
			}
			
			// Additional debug: Try to get ALL fields without filtering
			try {
				$all_raw_fields = $api->data(
					'query',
					'DataFormField',
					500,
					0,
					array(),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
				
				$debug_info['raw_api_response'] = array(
					'total_raw_fields' => count( $all_raw_fields ),
					'checkbox_raw_fields' => array(),
				);
				
				// Find checkbox fields in raw response
				foreach ( $all_raw_fields as $raw_field ) {
					$data_type = isset( $raw_field['DataType'] ) ? (int) $raw_field['DataType'] : 0;
					if ( in_array( $data_type, array( 5, 17, 23 ), true ) ) {
						$debug_info['raw_api_response']['checkbox_raw_fields'][] = $raw_field;
					}
				}
				
			} catch ( Exception $e ) {
				$debug_info['raw_api_error'] = $e->getMessage();
			}
			
		} catch ( Exception $e ) {
			$debug_info['error'] = $e->getMessage();
		}

		return $debug_info;
	}

	/**
	 * Update custom fields for contact
	 */
	protected function update_custom_fields( $contact_id, $arguments ) {
		if ( ! is_int( $contact_id ) || empty( $arguments ) ) {
			return false;
		}

		try {
			$custom_fields = array();
			
			// Handle automator custom fields
			if ( ! empty( $arguments['automator_custom_fields'] ) ) {
				$custom_fields = $this->build_automation_custom_fields( $arguments['automator_custom_fields'] );
			} elseif ( ! empty( $arguments['tve_mapping'] ) ) {
				$custom_fields = $this->build_mapped_custom_fields( $arguments );
			}

			if ( ! empty( $custom_fields ) ) {
				$api = $this->get_api();
				
				// Safety check to prevent fatal errors
				if ( false === $api || ! is_object( $api ) ) {
					error_log( 'Keap API Error: Invalid API instance in update_custom_fields' );
					return false;
				}
				
				$api->contact( 'update', $contact_id, $custom_fields );
				return true;
			}

		} catch ( Exception $e ) {
			error_log( 'Keap API Exception in update_custom_fields: ' . $e->getMessage() );
			// Continue silently for backward compatibility
		} catch ( Error $e ) {
			error_log( 'Keap API Fatal Error in update_custom_fields: ' . $e->getMessage() );
			// Continue silently to prevent breaking automator flows
		}

		return false;
	}

	/**
	 * Build automation custom fields with proper checkbox handling.
	 *
	 * @param array $automation_data The automation custom fields data.
	 *
	 * @return array Formatted custom fields for Keap API.
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();

		if ( ! empty( $automation_data['api_fields'] ) ) {
			foreach ( $automation_data['api_fields'] as $pair ) {
				$value = sanitize_text_field( $pair['value'] );
				
				if ( $value ) {
					$field_type = isset( $pair['type'] ) ? $pair['type'] : '';
					
					// Format date fields properly for Keap API.
					if ( 'date' === strtolower( $field_type ) ) {
						$value = $this->format_date_value( $value );
					}
					
					// Convert field values based on type (dropdown, number, checkbox, etc.)
					$value = $this->convert_special_field_values( $value, $field_type );
					$mapped_data[ '_' . $pair['key'] ] = $value;
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Get basic default fields that work with XML-RPC
	 */
	protected function get_basic_default_fields( $args ) {
		if ( empty( $args['tve_mapping'] ) ) {
			return array();
		}

		$mapped_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );
		if ( ! is_array( $mapped_data ) ) {
			return array();
		}

		$contact_fields = array();
		
		foreach ( $mapped_data as $cf_name => $cf_data ) {
			if ( empty( $cf_data['infusionsoft'] ) ) {
				continue;
			}

			$field_id = $cf_data['infusionsoft'];
			$clean_name = str_replace( '[]', '', $cf_name );
			
		if ( isset( $args[ $clean_name ] ) && in_array( $field_id, $this->_supported_contact_fields, true ) ) {
			$field_value = $args[ $clean_name ];
			
			// Check if field type is available in mapping data.
			$field_type = '';
			if ( isset( $cf_data['type'] ) ) {
				$field_type = $cf_data['type'];
			} elseif ( isset( $cf_data['_field_type'] ) ) {
				$field_type = $cf_data['_field_type'];
			} elseif ( isset( $cf_data['_field'] ) ) {
				$field_type = $cf_data['_field'];
			}

			// Format date fields properly for Keap API.
			if ( 'date' === strtolower( $field_type ) ) {
				$field_value = $this->format_date_value( $field_value );
			}

			// Convert checkbox and special field values.
			$field_value = $this->convert_special_field_values( $field_value, $field_type );
			
			// Handle arrays by joining with comma.
			if ( is_array( $field_value ) ) {
				$field_value = implode( ', ', $field_value );
			}
			
			$contact_fields[ $field_id ] = sanitize_text_field( $field_value );
		}
		}

		return $contact_fields;
	}

	/**
	 * Convert field values for Keap API based on field type.
	 * 
	 * Note: XML-RPC API (current) expects specific formats for different field types.
	 * REST API would expect different formats - see: https://developer.infusionsoft.com/docs/rest/
	 *
	 * @param mixed  $value The field value to check and potentially convert.
	 * @param string $field_type The field type to determine conversion logic.
	 *
	 * @return mixed The converted value formatted for Keap API.
	 */
	private function convert_special_field_values( $value, $field_type = '' ) {
		// Convert 'GDPR ACCEPTED' to 1 (XML-RPC expects integer).
		if ( is_string( $value ) && 'GDPR ACCEPTED' === $value ) {
			return 1;
		}

		// Handle dropdown/select fields - use the selected value, not the array.
		$dropdown_types = array( 'mapping_dropdown', 'dropdown', 'select', 'mapping_select' );
		if ( in_array( $field_type, $dropdown_types, true ) ) {
			// If value is array, get the first (selected) value.
			if ( is_array( $value ) && ! empty( $value ) ) {
				return trim( strval( $value[0] ) );
			}
			// If it's already a string, return it trimmed.
			return is_string( $value ) ? trim( $value ) : strval( $value );
		}

		// Handle number fields - ensure numeric format.
		$number_types = array( 'mapping_number', 'number', 'currency', 'percent' );
		if ( in_array( $field_type, $number_types, true ) ) {
			// Clean and validate numeric value.
			if ( is_array( $value ) && ! empty( $value ) ) {
				$value = $value[0]; // Take first value if array.
			}
			
			// Remove any non-numeric characters except decimal point and minus sign.
			$cleaned_value = preg_replace( '/[^0-9.\-]/', '', strval( $value ) );
			
			// Return as string (Keap XML-RPC expects string format).
			return is_numeric( $cleaned_value ) ? $cleaned_value : '0';
		}

		// Convert checkbox field values for both mapping and API field types.
		$checkbox_types = array( 'mapping_checkbox', 'checkbox' );
		if ( in_array( $field_type, $checkbox_types, true ) && ! empty( $value ) ) {
			// For XML-RPC API: return 1 for checked state
			// For future REST API implementation: would return true
			return 1;
		}

		// Handle checkbox arrays - if any value is present, return 1.
		if ( is_array( $value ) && in_array( $field_type, $checkbox_types, true ) ) {
			$meaningful_values = array_filter(
				$value,
				function ( $item ) {
					return is_string( $item ) && ! empty( trim( $item ) );
				}
			);
			// For XML-RPC API: return 1 if any meaningful values exist
			// For future REST API implementation: would return true
			return ! empty( $meaningful_values ) ? 1 : $value;
		}

		// Handle radio buttons - similar to dropdown, use selected value.
		$radio_types = array( 'mapping_radio', 'radio' );
		if ( in_array( $field_type, $radio_types, true ) ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				return trim( strval( $value[0] ) );
			}
			return is_string( $value ) ? trim( $value ) : strval( $value );
		}

		// Default: return value as-is.
		return $value;
	}

	/**
	 * Format date value to Keap's expected YYYY-MM-DD format.
	 *
	 * @param string $date_string Date string to format.
	 *
	 * @return string Formatted date string.
	 */
	private function format_date_value( $date_string ) {
		$formatted_date = '';

		if ( empty( $date_string ) ) {
			return $formatted_date;
		}

		// Check if $date_string is already in YYYY-MM-DD format.
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_string ) ) {
			// Validate the date.
			$date_parts = explode( '-', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) ) {
				return $date_string;
			}
		}

		// Check if $date_string is in the format of "M, d, Y" (e.g., "Jan, 15, 2024").
		if ( preg_match( '/[a-zA-Z]{3}, [0-9]{1,2}, [0-9]{4}/', $date_string ) ) {
			$date_string = str_replace( ', ', '-', $date_string );
			$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
			return $formatted_date;
		}

		// Check if $date_string is in the format of "d/m/Y" (e.g., "15/01/2024").
		if ( preg_match( '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[0], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( str_replace( '/', '-', $date_string ) ) );
				return $formatted_date;
			}
		}

		// Check if $date_string is in MM/DD/YYYY format.
		if ( preg_match( '/^(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[0], $date_parts[1], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
				return $formatted_date;
			}
		}

		// Try to parse other common date formats and convert to YYYY-MM-DD.
		$timestamp = strtotime( $date_string );
		if ( false !== $timestamp ) {
			$formatted_date = gmdate( 'Y-m-d', $timestamp );
		} else {
			// If all else fails, return the original string.
			$formatted_date = $date_string;
		}

		return $formatted_date;
	}

	/**
	 * Build mapped custom fields from form data (for update call)
	 */
	protected function build_mapped_custom_fields( $args ) {
		if ( empty( $args['tve_mapping'] ) ) {
			return array();
		}

		$mapped_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );
		if ( ! is_array( $mapped_data ) ) {
			return array();
		}

		$custom_fields = array();
		
		foreach ( $mapped_data as $cf_name => $cf_data ) {
			if ( empty( $cf_data['infusionsoft'] ) ) {
				continue;
			}

			$field_id = $cf_data['infusionsoft'];
			$clean_name = str_replace( '[]', '', $cf_name );
			
			// Check if field has a meaningful value.
			$has_meaningful_value = false;
			if ( isset( $args[ $clean_name ] ) ) {
				if ( is_array( $args[ $clean_name ] ) ) {
					// For arrays, check if any element is non-empty.
					$meaningful_values = array_filter(
						$args[ $clean_name ],
						function ( $item ) {
							return is_string( $item ) && ! empty( trim( $item ) );
						}
					);
					$has_meaningful_value = ! empty( $meaningful_values );
				} else {
					// For non-arrays, use regular empty check.
					$has_meaningful_value = ! empty( $args[ $clean_name ] );
				}
			}
			
			if ( $has_meaningful_value ) {
				$field_value = $args[ $clean_name ];
				
				// Check if field type is available in mapping data.
				$field_type = '';
				if ( isset( $cf_data['type'] ) ) {
					$field_type = $cf_data['type'];
				} elseif ( isset( $cf_data['_field_type'] ) ) {
					$field_type = $cf_data['_field_type'];
				} elseif ( isset( $cf_data['_field'] ) ) {
					$field_type = $cf_data['_field'];
				}

				// Format date fields properly for Keap API.
				if ( 'date' === strtolower( $field_type ) ) {
					$field_value = $this->format_date_value( $field_value );
				}

				// Convert checkbox and special field values.
				$field_value = $this->convert_special_field_values( $field_value, $field_type );
				
				// Handle arrays by joining with comma.
				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}
				
				// Sanitize the final value.
				$field_value = sanitize_text_field( $field_value );
				
				// Only process fields that aren't handled during creation.
				if ( ! in_array( $field_id, $this->_supported_contact_fields, true ) ) {
					$custom_fields[ '_' . $field_id ] = $field_value;
				}
			}
		}

		return $custom_fields;
	}

	/**
	 * Get automator mapping fields
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields' ) );
	}

	/**
	 * Get automator tag mapping fields
	 */
	public function get_automator_tag_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'tag_select' ) );
	}

}

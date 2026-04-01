<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_iContact extends Thrive_Dash_List_Connection_Abstract {
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
	 * @return string the API connection title
	 */
	public function get_title() {
		return 'iContact';
	}

	/**
	 * Enable custom fields support
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'iContact' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		$apiId       = ! empty( $_POST['connection']['appId'] ) ? sanitize_text_field( $_POST['connection']['appId'] ) : '';
		$apiUsername = ! empty( $_POST['connection']['apiUsername'] ) ? sanitize_text_field( $_POST['connection']['apiUsername'] ) : '';
		$apiPassword = ! empty( $_POST['connection']['apiPassword'] ) ? sanitize_text_field( $_POST['connection']['apiPassword'] ) : '';

		if ( empty( $apiId ) || empty( $apiUsername ) || empty( $apiPassword ) ) {
			return $this->error( __( 'You must provide a valid iContact AppID/Username/Password', 'thrive-dash' ) );
		}

		$this->set_credentials( $this->post( 'connection' ) );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to iContact: %s', 'thrive-dash' ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'iContact connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		$lists = $this->_get_lists();
		if ( $lists === false ) {
			return $this->_error;
		}

		return true;
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return Thrive_Dash_Api_iContact
	 */
	protected function get_api_instance() {
		return Thrive_Dash_Api_iContact::getInstance()->setConfig( $this->get_credentials() );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array|bool for error
	 */
	protected function _get_lists() {
		$api   = $this->get_api();
		$lists = array();

		try {
			$data = $api->getLists();
			if ( count( $data ) ) {
				foreach ( $data as $item ) {
					$lists[] = array(
						'id'   => $item->listId,
						'name' => $item->name,
					);
				}
			}
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}

		return $lists;
	}

	/**
	 * add a contact to a list
	 *
	 * @param array $list_identifier
	 * @param array $arguments
	 *
	 * @return mixed true -> success; string -> error;
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		$sEmail                         = $arguments['email'];
		$sStatus                        = 'normal';
		$sPrefix                        = null;
		$sPhone                         = null;
		list( $sFirstName, $sLastName ) = $this->get_name_parts( $arguments['name'] );
		$sSuffix                        = null;
		$sStreet                        = null;
		$sStreet2                       = null;
		$sCity                          = null;
		$sState                         = null;
		$sPostalCode                    = null;
		$sPhone                         = empty( $arguments['phone'] ) ? '' : $arguments['phone'];

		// Prepare custom fields data
		try {
			$aCustomFields = $this->prepare_api_custom_fields( $arguments );
		} catch ( Exception $e ) {
			$aCustomFields = array(); // Continue without custom fields if there's an error
		}

		try {

			/** @var Thrive_Dash_Api_iContact $api */
			$api = $this->get_api();

			$contact = $api->addContact( $sEmail, $sStatus, $sPrefix, $sFirstName, $sLastName, $sSuffix, $sStreet, $sStreet2, $sCity, $sState, $sPostalCode, $sPhone, null, null, $aCustomFields );

			if ( empty( $contact ) || ! is_object( $contact ) || empty( $contact->contactId ) ) {
				throw new Thrive_Dash_Api_iContact_Exception( 'Unable to save contact' );
			}

			$api->subscribeContactToList( $contact->contactId, $list_identifier );

			return true;

		} catch ( Thrive_Dash_Api_iContact_Exception $e ) {

			return $e->getMessage();

		} catch ( Exception $e ) {

			return $e->getMessage();
		}
	}

	/**
	 * Get custom fields from iContact API
	 *
	 * @param array $params
	 * @param bool  $force
	 * @param bool  $get_all
	 *
	 * @return array|mixed
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		// Serve from cache if exists and requested
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$custom_data = array();

		try {
			/** @var Thrive_Dash_Api_iContact $api */
			$api = $this->get_api();

			$custom_fields = $api->getCustomFields();

			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {
					$normalized_field = $this->normalize_custom_field( $field );
					$custom_data[]    = $normalized_field;
				}
			}

			$this->_save_custom_fields( $custom_data );

		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();
		}

		return $custom_data;
	}

	/**
	 * Normalize custom field data from iContact API
	 *
	 * @param object $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {

		$field = (object) $field;

		return array(
			'id'    => ! empty( $field->customFieldId ) ? $field->customFieldId : ( ! empty( $field->fieldName ) ? $field->fieldName : '' ),
			'name'  => ! empty( $field->fieldName ) ? $field->fieldName : ( ! empty( $field->name ) ? $field->name : '' ),
			'type'  => ! empty( $field->fieldType ) ? $field->fieldType : 'text',
			'label' => ! empty( $field->publicName ) ? $field->publicName : ( ! empty( $field->fieldName ) ? $field->fieldName : '' ),
		);
	}

	/**
	 * Get available custom fields for this api connection
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $data = array() ) {

		return $this->get_api_custom_fields( null, true );
	}

	/**
	 * Prepare custom fields data for API submission
	 *
	 * @param array $arguments POST sent by optin form
	 *
	 * @return array with iContact custom field name as key and the value of inputs filled by the visitor
	 */
	public function prepare_api_custom_fields( $arguments ) {

		$fields = array();
		if ( empty( $arguments['tve_mapping'] ) ) {
			return $fields;
		}

		try {
			$serialized = base64_decode( $arguments['tve_mapping'] );
			$mapping    = array();
			if ( $serialized ) {
				$mapping = thrive_safe_unserialize( $serialized );
			}

			if ( empty( $mapping ) ) {
				return $fields;
			}

			foreach ( $mapping as $name => $field ) {
				$name = str_replace( '[]', '', $name );

				// Check if field has a mapping and if arguments value is meaningful.
				$has_meaningful_value = false;
				if ( isset( $arguments[ $name ] ) ) {
					if ( is_array( $arguments[ $name ] ) ) {
						// For arrays, check if any element is non-empty.
						$meaningful_values    = array_filter(
							$arguments[ $name ],
							function ( $item ) {
								return is_string( $item ) && ! empty( trim( $item ) );
							}
						);
						$has_meaningful_value = ! empty( $meaningful_values );
					} else {
						// For non-arrays, use regular empty check
						$has_meaningful_value = ! empty( $arguments[ $name ] );
					}
				}

				if ( ! empty( $field[ $this->_key ] ) && $has_meaningful_value ) {
					$custom_field_name  = $field[ $this->_key ];
					$custom_field_value = $arguments[ $name ];

					// Check if field type is available in mapping data
					$field_type = '';
					if ( isset( $field['type'] ) ) {
						$field_type = $field['type'];
					} elseif ( isset( $field['_field_type'] ) ) {
						$field_type = $field['_field_type'];
					} elseif ( isset( $field['_field'] ) ) {
						$field_type = $field['_field'];
					}

					// Format date fields properly for iContact API
					if ( 'date' === strtolower( $field_type ) ) {
						$custom_field_value = $this->format_date_value( $custom_field_value );
					}

					// Convert 'GDPR ACCEPTED' and checkbox values to 1 for iContact API
					$custom_field_value = $this->convert_special_field_values( $custom_field_value, $field_type );

					$fields[ $custom_field_name ] = is_array( $custom_field_value ) ? implode( ', ', $custom_field_value ) : $custom_field_value;
				}
			}
		} catch ( Exception $e ) {
			// Continue silently if there's an error
		}

		return $fields;
	}

	/**
	 * Convert GDPR ACCEPTED value and checkbox field values to 1 for iContact API
	 *
	 * @param mixed  $value The field value to check and potentially convert
	 * @param string $field_type The field type to determine conversion logic
	 *
	 * @return mixed The converted value (1 for GDPR ACCEPTED or checkbox fields, otherwise original value)
	 */
	private function convert_special_field_values( $value, $field_type = '' ) {
		// Convert 'GDPR ACCEPTED' to 1
		if ( is_string( $value ) && 'GDPR ACCEPTED' === $value ) {
			return 1;
		}

		// Convert checkbox field values to 1.
		if ( 'mapping_checkbox' === $field_type && ! empty( $value ) ) {
			return 1;
		}
		return $value;
	}

	/**
	 * Format date value to iContact's expected YYYY-MM-DD format
	 *
	 * @param string $date_string Date string to format
	 *
	 * @return string Formatted date string
	 */
	private function format_date_value( $date_string ) {
		$formatted_date = '';

		if ( empty( $date_string ) ) {
			return $formatted_date;
		}

		// Check if $date_string is already in YYYY-MM-DD format
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_string ) ) {
			// Validate the date
			$date_parts = explode( '-', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) ) {
				return $date_string;
			}
		}

		// Check if $date_string is in the format of "M, d, Y" (e.g., "Jan, 15, 2024")
		if ( preg_match( '/[a-zA-Z]{3}, [0-9]{1,2}, [0-9]{4}/', $date_string ) ) {
			$date_string    = str_replace( ', ', '-', $date_string );
			$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
			return $formatted_date;
		}

		// Check if $date_string is in the format of "d/m/Y" (e.g., "15/01/2024")
		if ( preg_match( '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[0], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( str_replace( '/', '-', $date_string ) ) );
				return $formatted_date;
			}
		}

		// Check if $date_string is in MM/DD/YYYY format
		if ( preg_match( '/^(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[0], $date_parts[1], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
				return $formatted_date;
			}
		}

		// Try to parse other common date formats and convert to YYYY-MM-DD
		$timestamp = strtotime( $date_string );
		if ( $timestamp !== false ) {
			$formatted_date = gmdate( 'Y-m-d', $timestamp );
		} else {
			// If all else fails, return the original string
			$formatted_date = $date_string;
		}

		return $formatted_date;
	}

	/**
	 * Format custom fields for API submission by checking field types
	 *
	 * @param array $custom_fields
	 *
	 * @return array
	 */
	private function format_custom_fields_for_api( $custom_fields ) {

		$formatted_fields = array();

		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return $formatted_fields;
		}

		try {
			// Get available custom fields to check types, but only if we're not already in an error state
			$available_fields = array();
			$field_types      = array();

			// Try to get field types, but don't fail if this causes issues
			try {
				$available_fields = $this->get_api_custom_fields( null, false );

				// Build a map of field names to types
				if ( ! empty( $available_fields ) && is_array( $available_fields ) ) {
					foreach ( $available_fields as $field ) {
						if ( ! empty( $field['name'] ) && ! empty( $field['type'] ) ) {
							$field_types[ $field['name'] ] = $field['type'];
						}
						// Also check by id if name doesn't match
						if ( ! empty( $field['id'] ) && ! empty( $field['type'] ) ) {
							$field_types[ $field['id'] ] = $field['type'];
						}
					}
				}
			} catch ( Exception $e ) {
				// Continue silently if field types can't be retrieved
			}

			foreach ( $custom_fields as $field_name => $field_value ) {
				$field_type = isset( $field_types[ $field_name ] ) ? $field_types[ $field_name ] : '';

				// Format date fields properly for iContact API
				if ( 'date' === strtolower( $field_type ) ) {
					$field_value = $this->format_date_value( $field_value );
				}

				// Convert 'GDPR ACCEPTED' and checkbox values to 1 for iContact API
				$field_value = $this->convert_special_field_values( $field_value, $field_type );

				$formatted_fields[ $field_name ] = $field_value;
			}
		} catch ( Exception $e ) {
			// Return original fields if formatting fails
			return $custom_fields;
		}

		return $formatted_fields;
	}

	/**
	 * Add custom fields to existing contact
	 *
	 * @param string $email
	 * @param array  $custom_fields
	 * @param array  $extra
	 *
	 * @return int|false
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {

		try {
			/** @var Thrive_Dash_Api_iContact $api */
			$api = $this->get_api();

			// First, find the contact by email to get the contact ID
			$api->setLimit( 1 );
			$api->addCustomQueryField( 'email', $email );
			$contacts = $api->getContacts();

			if ( empty( $contacts ) || ! is_array( $contacts ) ) {
				return false;
			}

			$contact   = $contacts[0];
			$contactId = $contact->contactId;

			// Format date fields before updating
			$formatted_custom_fields = $this->format_custom_fields_for_api( $custom_fields );

			// Update the contact with custom fields
			$api->updateContact( $contactId, null, null, null, null, null, null, null, null, null, null, null, null, null, null, $formatted_custom_fields );

			return $contactId;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get custom fields by list
	 *
	 * @param null $list
	 *
	 * @return array
	 */
	public function get_custom_fields_by_list( $list = null ) {
		return $this->get_available_custom_fields();
	}

	/**
	 * Build custom fields mapping for automations
	 *
	 * @param $automation_data
	 *
	 * @return array
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();

		if ( ! empty( $automation_data['api_fields'] ) ) {
			foreach ( $automation_data['api_fields'] as $pair ) {
				$value = sanitize_text_field( $pair['value'] );
				if ( $value ) {
					// Convert 'GDPR ACCEPTED' and checkbox values to 1 for iContact API
					$value                       = $this->convert_special_field_values( $value, isset( $pair['type'] ) ? $pair['type'] : '' );
					$mapped_data[ $pair['key'] ] = $value;
				}
			}
		}

		// Format date fields for automation data
		if ( ! empty( $mapped_data ) ) {
			$mapped_data = $this->format_custom_fields_for_api( $mapped_data );
		}

		return $mapped_data;
	}
}

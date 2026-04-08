<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Thrive_Dash_List_Connection_MailRelay extends Thrive_Dash_List_Connection_Abstract {
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
		return 'MailRelay';
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
	public function has_custom_fields() {
		return true;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$related_api = Thrive_Dash_List_Manager::connection_instance( 'mailrelayemail' );
		if ( $related_api->is_connected() ) {
			$this->set_param( 'new_connection', 1 );
		}

		$this->output_controls_html( 'mailrelay' );
	}

	/**
	 * just save the key in the database
	 *
	 * @return mixed|void
	 */
	public function read_credentials() {
		$connection = $this->post( 'connection' );
		$key        = ! empty( $connection['key'] ) ? $connection['key'] : '';

		if ( empty( $key ) ) {
			return $this->error( __( 'You must provide a valid MailRelay key', 'thrive-dash' ) );
		}

		$connection['url'] = isset( $connection['domain'] ) ? $connection['domain'] : $connection['url'];

		$url = ! empty( $connection['url'] ) ? $connection['url'] : '';

		if ( filter_var( $url, FILTER_VALIDATE_URL ) === false || empty( $url ) ) {
			return $this->error( __( 'You must provide a valid MailRelay URL', 'thrive-dash' ) );
		}

		$this->set_credentials( $connection );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to MailRelay using the provided key (<strong>%s</strong>)', 'thrive-dash' ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();
		/** @var Thrive_Dash_List_Connection_MailRelayEmail $related_api */
		$related_api = Thrive_Dash_List_Manager::connection_instance( 'mailrelayemail' );

		if ( isset( $connection['new_connection'] ) && (int) $connection['new_connection'] === 1 ) {
			/**
			 * Try to connect to the email service too
			 */
			$r_result = true;
			if ( ! $related_api->is_connected() ) {
				$_POST['connection'] = $connection;
				$r_result            = $related_api->read_credentials();
			}

			if ( $r_result !== true ) {
				$this->disconnect();

				return $this->error( $r_result );
			}
		} else {
			/**
			 * let's make sure that the api was not edited and disconnect it
			 */
			$related_api->set_credentials( array() );
			Thrive_Dash_List_Manager::save( $related_api );
		}

		return $this->success( __( 'MailRelay connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {

		/** @var Thrive_Dash_Api_MailRelay $mr */
		$mr = $this->get_api();
		
		// Prevent fatal errors if API is not available
		if ( ! $mr ) {
			return 'MailRelay API classes not available';
		}

		try {
			$mr->get_list();
		} catch ( Thrive_Dash_Api_MailRelay_Exception $e ) {
			return $e->getMessage();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return Thrive_Dash_Api_MailRelay|Thrive_Dash_Api_MailRelayV1
	 */
	protected function get_api_instance() {

		$url     = $this->param( 'url' );
		$api_key = $this->param( 'key' );

		// Validate essential parameters to prevent fatal errors
		if ( empty( $url ) || empty( $api_key ) ) {
			return null;
		}

		// Check if required API classes exist to prevent fatal errors
		if ( false !== strpos( $url, 'ipzmarketing' ) ) {
			if ( ! class_exists( 'Thrive_Dash_Api_MailRelayV1' ) ) {
				return null;
			}
			$instance = new Thrive_Dash_Api_MailRelayV1( $url, $api_key );
		} else {
			if ( ! class_exists( 'Thrive_Dash_Api_MailRelay' ) ) {
				return null;
			}
			$instance = new Thrive_Dash_Api_MailRelay(
				array(
					'host'   => $url,
					'apiKey' => $api_key,
				)
			);
		}

		return $instance;
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	protected function _get_lists() {
		/** @var Thrive_Dash_Api_MailRelay $api */
		$api = $this->get_api();
		
		// Prevent fatal errors if API is not available
		if ( ! $api ) {
			return array();
		}

		$body = $api->get_list();

		$lists = array();
		foreach ( $body as $item ) {
			$lists [] = array(
				'id'   => $item['id'],
				'name' => $item['name'],
			);
		}

		return $lists;
	}

	/**
	 * Add a subscriber
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return bool|string true for success or string error message for failure
	 */
	public function add_subscriber( $list_identifier, $arguments ) {

		// Validate essential parameters to prevent fatal errors
		if ( empty( $arguments ) || ! is_array( $arguments ) ) {
			return 'Invalid arguments provided'; // String for backward compatibility
		}

		if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
			return 'Valid email address required'; // String for backward compatibility
		}

		$api = $this->get_api();
		if ( ! $api ) {
			return 'Cannot establish API connection'; // String for backward compatibility
		}

		// Check if we have a custom list_id from any mapping_hidden field (flexible logic)
		$custom_list_id = null;
		
		// Look for any field that starts with "mapping_hidden" and has numeric value. We use this to populate group under MailRelay, targeting group id. 
		foreach ( $arguments as $field_name => $field_value ) {
			if ( strpos( $field_name, 'mapping_hidden' ) === 0 && is_numeric( $field_value ) ) {
				$custom_list_id = $field_value;
				break; // Use the first numeric mapping_hidden field found
			}
		}
		
		if ( $custom_list_id ) {
			$list_identifier = $custom_list_id;
		}

		// Validate list_identifier
		if ( empty( $list_identifier ) || ! is_numeric( $list_identifier ) ) {
			return 'Valid list identifier required'; // String for backward compatibility
		}

		// Process tags if provided
		$tags = array();
		if ( ! empty( $arguments['mailrelay_tags'] ) && is_string( $arguments['mailrelay_tags'] ) ) {
			$tags = explode( ',', $arguments['mailrelay_tags'] );
			$tags = array_map( 'trim', $tags );
			$tags = array_filter( $tags ); // Remove empty tags
		}

		// Process custom fields if provided  
		$custom_fields = array();
		if ( ! empty( $arguments['tve_mapping'] ) && is_string( $arguments['tve_mapping'] ) ) {
			$custom_fields = $this->prepare_api_custom_fields( $arguments );
			// Validate that prepare_api_custom_fields returned an array
			if ( ! is_array( $custom_fields ) ) {
				$custom_fields = array();
			}
		}

		// Prepare subscriber data
		$args = array(
			'email' => $arguments['email'],
		);

		if ( ! empty( $arguments['name'] ) ) {
			$args['name'] = $arguments['name'];
		}

		// Collect ALL custom fields from different sources
		$all_custom_fields = array();

		// Add phone field if valid
		if ( ! empty( $arguments['phone'] ) && is_string( $arguments['phone'] ) ) {
			$all_custom_fields['f_phone'] = sanitize_text_field( $arguments['phone'] );
		}

		// Add processed custom fields from tve_mapping
		if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
			foreach ( $custom_fields as $field_key => $field_value ) {
				// Validate field key and value
				if ( empty( $field_key ) || ! is_string( $field_key ) || $field_value === '' ) {
					continue;
				}
				
				// Only exclude mapping_hidden if it's numeric (used for group selection)
				if ( $field_key === 'mapping_hidden' && is_numeric( $field_value ) ) {
					// Skip - this is used for group selection
				} else {
					// Process as normal custom field
					$all_custom_fields[ $field_key ] = sanitize_text_field( $field_value );
				}
			}
		}

		// Store all custom fields for later processing
		if ( ! empty( $all_custom_fields ) ) {
			$args['customFields'] = $all_custom_fields;
		}

		try {
			// Add subscriber to list
			$result = $api->add_subscriber( $list_identifier, $args );
			
			// Handle API errors - return string for backward compatibility
			if ( is_array( $result ) && isset( $result['error'] ) ) {
				return $result['error']; // String error message for backward compatibility
			}

			// Process BOTH tags and custom fields together in ONE API call to avoid conflicts
			if ( isset( $result['id'] ) && $api instanceof Thrive_Dash_Api_MailRelayV1 ) {
				$all_fields_to_update = array();
				
				// Handle tags first - validate tags array
				if ( ! empty( $tags ) && is_array( $tags ) ) {
					try {
						$tags_field = $api->get_custom_field( 'mailrelay_tags' );
						if ( empty( $tags_field ) ) {
							$tags_field = $api->create_custom_field( array(
								'label'      => 'Tags',
								'tag_name'   => 'mailrelay_tags',
								'field_type' => 'text',
							) );
						}
						
						if ( is_array( $tags_field ) && ! empty( $tags_field['id'] ) ) {
							$combined_tags = implode( ',', array_map( 'trim', $tags ) );
							if ( ! empty( $combined_tags ) ) {
								$all_fields_to_update[ $tags_field['id'] ] = $combined_tags;
							}
						}
					} catch ( Exception $e ) {
						// Continue processing other fields
					}
				}
				
				// Handle custom fields - validate array first
				if ( ! empty( $all_custom_fields ) && is_array( $all_custom_fields ) ) {
					foreach ( $all_custom_fields as $field_key => $field_value ) {
						// Validate field data before processing
						if ( empty( $field_key ) || ! is_string( $field_key ) || $field_value === '' ) {
							continue;
						}
						
						try {
							// Get/create custom field
							if ( $field_key === 'f_phone' ) {
								$custom_field = $api->get_custom_field( 'thrive_phone' );
								if ( empty( $custom_field ) ) {
									$custom_field = $api->create_custom_field( array(
										'label'      => 'Phone',
										'tag_name'   => 'thrive_phone',
										'field_type' => 'text',
									) );
								}
							} else {
								$custom_field = $api->get_custom_field( $field_key );
								if ( empty( $custom_field ) ) {
									$custom_field = $api->create_custom_field( array(
										'label'      => ucfirst( str_replace( '_', ' ', $field_key ) ),
										'tag_name'   => $field_key,
										'field_type' => 'text',
									) );
								}
							}
							
							// Add to batch if field exists and has valid ID
							if ( is_array( $custom_field ) && ! empty( $custom_field['id'] ) && is_numeric( $custom_field['id'] ) ) {
								$all_fields_to_update[ $custom_field['id'] ] = $field_value;
							}
							
						} catch ( Exception $e ) {
							// Continue processing other fields
						}
					}
				}
				
				// Make ONE single API call with ALL fields (tags + custom fields)
				if ( ! empty( $all_fields_to_update ) && is_array( $all_fields_to_update ) ) {
					try {
						$api->update_subscriber_custom_fields( $arguments['email'], $all_fields_to_update );
					} catch ( Exception $e ) {
						// Continue if custom fields update fails
					}
				}
			}

			// Maintain backward compatibility for Automator plugin
			if ( is_array( $result ) && isset( $result['id'] ) ) {
				return true; // Success - maintain original return type
			}
			
			return $result;

		} catch ( Exception $e ) {
			// Return string error message for backward compatibility
			return $e->getMessage();
		}
	}

	/**
	 * Based on custom inputs set in form and their mapping
	 * - prepares custom fields for MailRelay
	 *
	 * @param array $arguments POST sent by optin form
	 *
	 * @return array with MailRelay custom field name as key and the value of inputs filled by the visitor
	 */
	public function prepare_api_custom_fields( $arguments ) {
		$fields = array();
		if ( empty( $arguments['tve_mapping'] ) ) {
			return $fields;
		}

		$serialized = base64_decode( $arguments['tve_mapping'] );
		$mapping    = array();
		if ( $serialized ) {
			$mapping = thrive_safe_unserialize( $serialized );
		}

		if ( empty( $mapping ) ) {
			return $fields;
		}

		// Use the correct key for field mapping
		$field_key = '_field';

		foreach ( $mapping as $form_field_name => $field ) {
			$form_field_name = str_replace( '[]', '', $form_field_name );
			
			if ( ! empty( $field[ $field_key ] ) && ! empty( $arguments[ $form_field_name ] ) ) {
				$api_field_name               = $field[ $field_key ];
				$custom_field_value           = $arguments[ $form_field_name ];
				$fields[ $api_field_name ]    = is_array( $custom_field_value ) ? implode( ', ', $custom_field_value ) : $custom_field_value;
			}
		}

		return $fields;
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
		foreach ( $automation_data['api_fields'] as $pair ) {
			$value = sanitize_text_field( $pair['value'] );
			if ( $value ) {
				$mapped_data[ $pair['key'] ] = $value;
			}
		}

		return $mapped_data;
	}

	/**
	 * @param       $email
	 * @param array $custom_fields
	 * @param array $extra
	 *
	 * @return false|int|mixed
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {
		try {
			/** @var Thrive_Dash_Api_MailRelay|Thrive_Dash_Api_MailRelayV1 $api */
			$api     = $this->get_api();
			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;
			$args    = array(
				'email' => $email,
			);

			if ( ! empty( $extra['name'] ) ) {
				$args['name'] = $extra['name'];
			}

			$this->add_subscriber( $list_id, $args );

			$prepared_fields = $this->prepare_custom_fields_for_api( $custom_fields );
			if ( ! empty( $prepared_fields ) ) {
				// Update subscriber with custom fields via MailRelay API
				if ( method_exists( $api, 'update_subscriber_custom_fields' ) ) {
					$api->update_subscriber_custom_fields( $email, $prepared_fields );
				}
			}

			return true;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Prepare custom fields for API call
	 *
	 * @param array $custom_fields
	 * @param null  $list_identifier
	 *
	 * @return array
	 */
	protected function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {
		$prepared_fields = array();
		
		foreach ( $custom_fields as $field_name => $field_value ) {
			if ( ! empty( $field_value ) ) {
				$prepared_fields[ $field_name ] = sanitize_text_field( $field_value );
			}
		}

		return $prepared_fields;
	}

	/**
	 * Get all custom fields from MailRelay
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	public function get_all_custom_fields( $force = false ) {
		$custom_data = array();
		
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		/** @var Thrive_Dash_Api_MailRelay|Thrive_Dash_Api_MailRelayV1 $api */
		$api = $this->get_api();
		
		// Prevent fatal errors if API is not available
		if ( ! $api ) {
			return $this->get_default_custom_fields();
		}

		try {
			// Check if it's V1 API with custom fields support
			if ( $api instanceof Thrive_Dash_Api_MailRelayV1 ) {
				// For V1 API, we can get all custom fields directly
				$custom_fields = $api->get_all_custom_fields();
				
				if ( is_array( $custom_fields ) ) {
					foreach ( $custom_fields as $field ) {
						$custom_data[] = $this->normalize_custom_field( $field );
					}
				}
			} else {
				// For regular API, get custom fields or use defaults
				$custom_fields = $api->get_custom_fields();
				
				if ( is_array( $custom_fields ) ) {
					foreach ( $custom_fields as $field ) {
						$custom_data[] = $this->normalize_custom_field( $field );
					}
				}
			}

		} catch ( Exception $e ) {
			// Silently handle exceptions
		}

		// If no custom fields were found, provide some default ones
		if ( empty( $custom_data ) ) {
			$custom_data = $this->get_default_custom_fields();
		}

		$this->_save_custom_fields( $custom_data );

		return $custom_data;
	}

	/**
	 * Get API custom fields (required for editor custom fields detection)
	 * This method is called by Thrive_Dash_List_Manager::getAvailableCustomFields()
	 * 
	 * @param array $params
	 * @param bool $force
	 * @param bool $get_all
	 *
	 * @return array
	 */
	public function get_api_custom_fields( $params = array(), $force = false, $get_all = false ) {
		return $this->get_all_custom_fields( $force );
	}

	/**
	 * Get available custom fields for this api connection (for API compatibility)
	 *
	 * @param null $list_id
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {
		return $this->get_all_custom_fields( true );
	}

	/**
	 * Brings an API field under a known form that TAr can understand
	 *
	 * @param string|array $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {
		if ( is_string( $field ) ) {
			return array(
				'id'    => $field,
				'name'  => $field,
				'type'  => 'text',
				'label' => $field,
			);
		}

		$field = (array) $field;

		return array(
			'id'    => isset( $field['tag_name'] ) ? $field['tag_name'] : ( isset( $field['name'] ) ? $field['name'] : '' ),
			'name'  => isset( $field['label'] ) ? $field['label'] : ( isset( $field['name'] ) ? $field['name'] : '' ),
			'type'  => isset( $field['field_type'] ) ? $field['field_type'] : ( isset( $field['type'] ) ? $field['type'] : 'text' ),
			'label' => isset( $field['label'] ) ? $field['label'] : ( isset( $field['name'] ) ? $field['name'] : '' ),
		);
	}

	/**
	 * Apply tag to subscriber (MailRelay-specific implementation)
	 *
	 * @param string $email
	 * @param string $tag
	 *
	 * @return bool
	 */
	protected function apply_tag( $email, $tag ) {
		try {
			/** @var Thrive_Dash_Api_MailRelay|Thrive_Dash_Api_MailRelayV1 $api */
			$api = $this->get_api();
			
			// For MailRelay, we might handle tags as custom fields or groups
			// This depends on MailRelay's specific API capabilities
			// For now, we'll store it as a custom field called 'tags'
			if ( $api instanceof Thrive_Dash_Api_MailRelayV1 ) {
				// Try to create or get a tags custom field
				$tags_field = $api->get_custom_field( 'mailrelay_tags' );
				if ( empty( $tags_field ) ) {
					$tags_field = $api->create_custom_field( array(
						'label'      => 'Tags',
						'tag_name'   => 'mailrelay_tags',
						'field_type' => 'text',
					) );
				}
			}
			
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Get default custom fields when API is not available
	 *
	 * @return array
	 */
	protected function get_default_custom_fields() {
		$default_fields = array(
			array( 'name' => 'company', 'label' => 'Company', 'type' => 'text' ),
			array( 'name' => 'website', 'label' => 'Website', 'type' => 'text' ),
			array( 'name' => 'custom_field_1', 'label' => 'Custom Field 1', 'type' => 'text' ),
			array( 'name' => 'custom_field_2', 'label' => 'Custom Field 2', 'type' => 'text' ),
		);
		
		$custom_data = array();
		foreach ( $default_fields as $field ) {
			$custom_data[] = $this->normalize_custom_field( $field );
		}
		
		return $custom_data;
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '[email]';
	}

	/**
	 * disconnect (remove) this API connection
	 */
	public function disconnect() {

		$this->set_credentials( array() );
		Thrive_Dash_List_Manager::save( $this );

		/**
		 * disconnect the email service too
		 */
		$related_api = Thrive_Dash_List_Manager::connection_instance( 'mailrelayemail' );
		$related_api->set_credentials( array() );
		Thrive_Dash_List_Manager::save( $related_api );

		return $this;
	}

}

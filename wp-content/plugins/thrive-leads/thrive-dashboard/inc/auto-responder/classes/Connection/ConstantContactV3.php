<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_ConstantContactV3 extends Thrive_Dash_List_Connection_Abstract {

	public static function get_type() {
		return 'autoresponder';
	}

	public function get_title() {
		return 'Constant Contact';
	}

	public function has_tags() {
		return true;
	}

	/**
	 * Builds an authorization URI - the user will be redirected to that URI and asked to give app access
	 *
	 * @return string
	 */
	public function getAuthorizeUrl() {
		$this->save(); // save the client_id and client_secret for later use

		return $this->get_api()->get_authorize_url();
	}

	/**
	 * whether or not this list is connected to the service (has been authenticated)
	 *
	 * @return bool
	 */
	public function is_connected() {
		return $this->param( 'access_token' ) && $this->param( 'refresh_token' );
	}

	public function output_setup_form() {
		$this->output_controls_html( 'constant-contact-v3' );
	}

	/**
	 * Called during the redirect from constant contact oauth flow
	 *
	 * _REQUEST contains a `code` parameter which needs to be sent back to g.api in exchange for an access token
	 *
	 * @return bool|mixed|string|Thrive_Dash_List_Connection_Abstract
	 */
	public function read_credentials() {
		$code = empty( $_REQUEST['code'] ) ? '' : $_REQUEST['code'];

		if ( empty( $code ) ) {
			return $this->error( 'Missing `code` parameter' );
		}

		try {
			/* get access token from constant contact API */
			$response = $this->get_api()->get_access_token( $code );
			if ( empty( $response['access_token'] ) ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Missing token from response data' );
			}
			$this->_credentials = array(
				'client_id'     => $this->param( 'client_id' ),
				'client_secret' => $this->param( 'client_secret' ),
				'access_token'  => $response['access_token'],
				'expires_at'    => time() + $response['expires_in'],
				'refresh_token' => $response['refresh_token'],
			);
			$this->save();
				/**
			 * Fetch all custom fields on connect so that we have them all prepared
			 * - TAr doesn't need to get them from API
			 */
			$this->get_api_custom_fields( array(), true, true );

		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {

			echo 'caught ex: ' . esc_html( $e->getMessage() );
			$this->_credentials = array();
			$this->save();

			$this->error( $e->getMessage() );

			return false;
		}

		return true;
	}

	/**
	 * Test the connection to the service.
	 *
	 * @return void
	 */
	public function test_connection() {

		$result = array(
			'success' => true,
			'message' => __( 'Connection works', 'thrive-dash' ),
		);
		try {
			$this->get_api()->get_account_details(); // this will throw the exception if there is a connection problem
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Instantiate the service and set any available data
	 *
	 * @return Thrive_Dash_Api_ConstantContactV3_Service
	 */
	protected function get_api_instance() {
		$api = new Thrive_Dash_Api_ConstantContactV3_Service(
			$this->param( 'client_id' ),
			$this->param( 'client_secret' ),
			$this->param( 'access_token' )
		);

		/* check for expired token and renew it */
		if ( $this->param( 'refresh_token' ) && $this->param( 'expires_at' ) && time() > (int) $this->param( 'expires_at' ) ) {
			$data                               = $api->refresh_access_token( $this->param( 'refresh_token' ) );
			$this->_credentials['access_token'] = $data['access_token'];
			$this->_credentials['refresh_token'] = $data['refresh_token'];
			$this->_credentials['expires_at']   = time() + $data['expires_in'];
			$this->save();
		}

		return $api;
	}

	protected function _get_lists() {
		return $this->get_api()->getLists();
	}

	/**
	 * Add a subscriber to a list.
	 *
	 * @param string $list_identifier Contact list identifier.
	 * @param array  $arguments Subscriber data.
	 * @return bool
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		// add logic here.
		$params = array(
			'create_source'    => 'Contact',
			'list_memberships' => array( $list_identifier ),
		);

		if ( $arguments['email'] ) {
			$params['email_address'] = array(
				'address'            => $arguments['email'],
				'permission_to_send' => 'implicit',
			);
		}

		if ( $arguments['name'] ) {
			$split_name           = $this->_splitFullName( $arguments['name'] );
			$params['first_name'] = $split_name['first_name'];
			$params['last_name']  = $split_name['last_name'];
		}

		if ( $arguments['first_name'] ) {
			$params['first_name'] = $arguments['first_name'];
		}

		if ( $arguments['last_name'] ) {
			$params['last_name'] = $arguments['last_name'];
		}

		if ( $arguments['phone'] ) {
			$params['phone_number'] = $arguments['phone'];
		}

		// Handle tags - get or create tag IDs BEFORE contact creation.
		if ( ! empty( $arguments['constantcontact_v3_tags'] ) ) {
			$tag_names = explode( ',', trim( $arguments['constantcontact_v3_tags'], ' ,' ) );
			$tag_names = array_map( 'trim', $tag_names );
			$tag_names = array_filter( $tag_names ); // Remove empty tags.

			// Get or create tag IDs.
			$tag_ids = $this->get_or_create_tag_ids( $tag_names );

			if ( ! empty( $tag_ids ) ) {
				$params['taggings'] = $tag_ids; // Pass tag IDs to API wrapper.
			}
		}

		$params = array_merge( $params, $this->_generateMappingFields( $arguments ) );

		return $this->get_api()->addSubscriber( $params );
	}


	/**
	 * Get or create tag IDs.
	 *
	 * @param [type] $tag_names Tag names.
	 * @return array
	 */
	private function get_or_create_tag_ids( $tag_names ) {
		$tag_ids = array();

		try {
			// Get all existing tags first - GET /contact_tags.
			$existing_tags = $this->get_api()->getAllTags();
			$tag_map = array();

			// Create a map of tag name => tag_id (case-insensitive).
			if ( is_array( $existing_tags ) && isset( $existing_tags['tags'] ) ) {
				foreach ( $existing_tags['tags'] as $tag ) {
					if ( isset( $tag['name'] ) && isset( $tag['tag_id'] ) ) {
						$tag_map[ strtolower( trim( $tag['name'] ) ) ] = $tag['tag_id'];
					}
				}
			}

			// Process each tag name.
			foreach ( $tag_names as $tag_name ) {
				$tag_key = strtolower( trim( $tag_name ) );

				// Check if tag already exists.
				if ( isset( $tag_map[ $tag_key ] ) ) {
					$tag_ids[] = $tag_map[ $tag_key ];
				} else {
					// Create new tag and get its ID.
					$new_tag_id = $this->create_new_tag( $tag_name );
					if ( $new_tag_id ) {
						$tag_ids[] = $new_tag_id;
					}
				}
			}
		} catch ( Exception $e ) {
			error_log( 'ConstantContactV3: Failed to get or create tag IDs - ' . $e->getMessage() );
		}

		return $tag_ids;
	}


	/**
	 * Create a new tag using POST /contact_tags.
	 *
	 * @param string $tag_name Tag name.
	 * @return int|null Tag ID.
	 */
	private function create_new_tag( $tag_name ) {
		try {
			$tag_data = array(
				'name'       => trim( $tag_name ),
				'tag_source' => 'API',  // Indicates this tag was created via API.
			);

			$result = $this->get_api()->createTag( $tag_data );

			if ( is_array( $result ) && isset( $result['tag_id'] ) ) {
				return $result['tag_id'];
			}
		} catch ( Exception $e ) {
			error_log( 'ConstantContactV3: Failed to create tag "' . $tag_name . '" - ' . $e->getMessage() );
		}

		return false;
	}


	/**
	 * Get first name and last name from full name.
	 *
	 * @param string $full_name full name.
	 * @return array
	 */
	private function _splitFullName( $full_name = '' ) {
		$full_name = trim( preg_replace( '/\s+/', ' ', $full_name ) );
		$result    = [
			'first_name' => '',
			'last_name'  => '',
		];

		if ( empty( $full_name ) ) {
			return $result;
		}

		$name_parts = explode( ' ', $full_name );

		if ( count( $name_parts ) === 1 ) {
			$result['first_name'] = $name_parts[0];
			return $result;
		}

		$result['first_name'] = array_shift( $name_parts );
		$result['last_name']  = implode( ' ', $name_parts );

		return $result;
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '{$email}';
	}



	/**
	 * Get the custom fields for the API.
	 *
	 * @param array   $params Parameters.
	 * @param boolean $force Force.
	 * @param boolean $get_all Get all.
	 * @return array
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {
		// Serve from cache if exists and requested.
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		$custom_data = $this->get_api()->getAllFields();

		$this->_save_custom_fields( $custom_data );

		return $custom_data;
	}


	/**
	 * Generate mapping fields array
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	private function _generateMappingFields( $args = array() ) {
		$mapping_fields = array();
		$tve_mapping    = unserialize( base64_decode( $args['tve_mapping'] ) );

		if ( ! is_array( $tve_mapping ) || 0 === count( $tve_mapping ) ) {
			return $mapping_fields;
		}

		foreach ( $tve_mapping as $key => $value ) {
			$field_name  = reset( $value );
			$field_type  = $value['_field'];
			$field_value = $args[ $key ];

			if ( 'date' === $field_type ) {
				$field_value = $this->formatDateValue( $field_value );
			}

			if ( ! empty( $args[ $key ] ) ) {
				// check if the field name is in this format 87a47d98-c8ef-11ef-a282-fa163eb4f69a then it is a custom field.
				if ( preg_match( '/[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}/', $field_name ) ) {
					$mapping_fields['custom_fields'][] = array(
						'custom_field_id' => $field_name,
						'value'           => $field_value,
					);
				} else {
					$mapping_fields[ $field_name ] = $field_value;
					if ( 'birthday_day' === $field_name || 'birthday_month' === $field_name ) {
						$mapping_fields[ $field_name ] = (int) $field_value;
					}
				}
			}
		}

		return $mapping_fields;
	}


	/**
	 * Format date value to d/m/Y format.
	 *
	 * @param String $date_string Date string.
	 * @return String
	 */
	private function formatDateValue( $date_string ) {
		$formatted_date = '';

		// check if $date_string is in the format of M, d, Y.
		if ( preg_match( '/[a-zA-Z]{3}, [0-9]{1,2}, [0-9]{4}/', $date_string ) ) {
			$date_string    = str_replace( ', ', '-', $date_string );
			$formatted_date = gmdate( 'm/d/Y', strtotime( $date_string ) );
			return $formatted_date;
		}

		// check if $date_string is in the format of d/m/Y but not m/d/Y.
		if ( preg_match( '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[0], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'm/d/Y', strtotime( str_replace( '/', '-', $date_string ) ) );
				return $formatted_date;
			}
		}

		$formatted_date = gmdate( 'm/d/Y', strtotime( $date_string ) );
		return $formatted_date;
	}


	/**
	 * Get available custom fields for this api connection
	 *
	 * @param null $list_id
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {

		return $this->get_api_custom_fields( null, true );
	}


	/**
	 * Define support for custom fields.
	 *
	 * @return boolean
	 */
	public function has_custom_fields() {
		return true;
	}

	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields', 'tag_input' ) );
	}
}

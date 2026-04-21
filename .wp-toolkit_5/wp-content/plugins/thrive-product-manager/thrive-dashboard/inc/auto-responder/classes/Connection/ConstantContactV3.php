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
	 * @return bool
	 */
	public function can_create_tags_via_api() {
		return true;
	}

	/**
	 * Builds an authorization URI - the user will be redirected to that URI and asked to give app access
	 *
	 * @return string
	 */
	public function getAuthorizeUrl() {
		$this->save(); // save the client_id and client_secret for later use

		$api = $this->get_api();
		if ( ! $api ) {
			return '';
		}

		return $api->get_authorize_url();
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
			$api = $this->get_api();
			if ( ! $api ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Cannot establish API connection' );
			}

			$response = $api->get_access_token( $code );
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
			$api = $this->get_api();

			if ( ! $api ) {
				return array(
					'success' => false,
					'message' => __( 'Cannot establish API connection. Your Constant Contact token may have expired. Please reconnect your account.', 'thrive-dash' ),
				);
			}

			$api->get_account_details(); // this will throw the exception if there is a connection problem
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			$result['success'] = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	/**
	 * Instantiate the service and set any available data
	 *
	 * @return Thrive_Dash_Api_ConstantContactV3_Service|null Returns null if token refresh fails
	 */
	protected function get_api_instance() {
		try {
			$api = new Thrive_Dash_Api_ConstantContactV3_Service(
				$this->param( 'client_id' ),
				$this->param( 'client_secret' ),
				$this->param( 'access_token' )
			);

			/* check for expired token and renew it */
			if ( $this->param( 'refresh_token' ) && $this->param( 'expires_at' ) && time() > (int) $this->param( 'expires_at' ) ) {
				// Attempt to refresh the token
				$data = $api->refresh_access_token( $this->param( 'refresh_token' ) );

				// Update credentials with new tokens
				$this->_credentials['access_token'] = $data['access_token'];
				// Only update refresh_token if a new one is provided (not all OAuth flows return a new refresh token)
				if ( ! empty( $data['refresh_token'] ) ) {
					$this->_credentials['refresh_token'] = $data['refresh_token'];
				}
				$this->_credentials['expires_at'] = time() + $data['expires_in'];
				$this->save();
			}

			return $api;
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			// Log the error for debugging
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: Token refresh failed - ' . $e->getMessage() );
			}

			// Clear only expired/invalid tokens so the user is prompted to reconnect, but preserve other credential data.
			unset( $this->_credentials['access_token'], $this->_credentials['refresh_token'], $this->_credentials['expires_at'] );
			$this->save();

			return null;
		} catch ( Exception $e ) {
			// Log unexpected errors
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: API initialization failed - ' . $e->getMessage() );
			}

			// Clear invalid credentials so the user is prompted to reconnect
			$this->_credentials = array();
			$this->save();

			return null;
		}
	}

	protected function _get_lists() {
		try {
			$api = $this->get_api();

			if ( ! $api ) {
				return array();
			}

			return $api->getLists();
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: Failed to get lists - ' . $e->getMessage() );
			}
			return array();
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: Unexpected error getting lists - ' . $e->getMessage() );
			}
			return array();
		}
	}

	/**
	 * Add a subscriber to a list.
	 *
	 * @param string $list_identifier Contact list identifier.
	 * @param array  $arguments Subscriber data.
	 * @return bool|string true for success, error message string for failure
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		try {
			// add logic here.
			$params = array(
				'create_source'    => 'Contact',
				'list_memberships' => array( $list_identifier ),
			);

			if ( ! empty( $arguments['email'] ) ) {
				$params['email_address'] = array(
					'address'            => $arguments['email'],
					'permission_to_send' => 'implicit',
				);
			}

			if ( ! empty( $arguments['name'] ) ) {
				$split_name           = $this->_splitFullName( wp_unslash( $arguments['name'] ) );
				$params['first_name'] = $split_name['first_name'];
				$params['last_name']  = $split_name['last_name'];
			}

		if ( ! empty( $arguments['first_name'] ) ) {
			$params['first_name'] = wp_unslash( $arguments['first_name'] );
		}

		if ( ! empty( $arguments['last_name'] ) ) {
			$params['last_name'] = wp_unslash( $arguments['last_name'] );
		}

		if ( ! empty( $arguments['phone'] ) ) {
			$params['phone_number'] = wp_unslash( $arguments['phone'] );
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

			$api = $this->get_api();
			if ( ! $api ) {
				return __( 'Cannot establish API connection. Your Constant Contact token may have expired. Please reconnect your account.', 'thrive-dash' );
			}

			return $api->addSubscriber( $params );
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			error_log( 'ConstantContactV3 Exception: ' . $e->getMessage() );
			return __( 'An error occurred while adding the subscriber. Please try again later.', 'thrive-dash' );
		} catch ( Exception $e ) {
			error_log( 'General Exception in ConstantContactV3: ' . $e->getMessage() );
			return __( 'An error occurred while adding the subscriber. Please try again later.', 'thrive-dash' );
		}
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
			$api = $this->get_api();
			if ( ! $api ) {
				return $tag_ids;
			}

			// Get all existing tags first - GET /contact_tags.
			$existing_tags = $api->getAllTags();
			$tag_map = array();

			// Create a map of tag name => tag_id (case-insensitive).
			if ( is_array( $existing_tags ) && isset( $existing_tags['tags'] ) && is_array( $existing_tags['tags'] ) ) {
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
			$api = $this->get_api();
			if ( ! $api ) {
				return false;
			}

			$tag_data = array(
				'name'       => trim( $tag_name ),
				'tag_source' => 'API',  // Indicates this tag was created via API.
			);

			$result = $api->createTag( $tag_data );

			if ( is_array( $result ) && isset( $result['tag_id'] ) ) {
				// Clear the tags cache since we created a new tag
				$this->clearTagsCache();
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

		$custom_data = array();

		try {
			$api = $this->get_api();

			if ( ! $api ) {
				return $custom_data;
			}

			$custom_data = $api->getAllFields();

			if ( is_array( $custom_data ) && ! empty( $custom_data ) ) {
				$this->_save_custom_fields( $custom_data );
			}
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: Failed to get custom fields - ' . $e->getMessage() );
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'ConstantContact V3: Unexpected error getting custom fields - ' . $e->getMessage() );
			}
		}

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

		if ( empty( $args['tve_mapping'] ) || ! is_string( $args['tve_mapping'] ) ) {
			return $mapping_fields;
		}

		$decoded     = base64_decode( $args['tve_mapping'], true );
		$tve_mapping = false !== $decoded ? @unserialize( $decoded ) : false;

		if ( ! is_array( $tve_mapping ) || 0 === count( $tve_mapping ) ) {
			return $mapping_fields;
		}

		foreach ( $tve_mapping as $key => $value ) {
			if ( ! is_array( $value ) || empty( $value ) || ! isset( $value['_field'] ) || ! isset( $args[ $key ] ) ) {
				continue;
			}

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
						'value'           => wp_unslash( $field_value ),
					);
				} else {
					$mapping_fields[ $field_name ] = wp_unslash( $field_value );
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

	/**
	 * Gets a list of tags through GET /contact_tags API with 15-minute transient caching
	 *
	 * @return array
	 */
	public function getTags( $force = false ) {
		// Create a unique cache key based on API credentials
		$credentials = $this->get_credentials();
		$cache_key = 'constantcontact_v3_tags_' . md5( serialize( $credentials ) );
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
			/** @var Thrive_Dash_Api_ConstantContactV3_Service $api */
			$api = $this->get_api();

			if ( ! $api ) {
				return $tags;
			}

			$response = $api->getAllTags();

			// Process the response to create a simple tag_id => tag_name array
			if ( is_array( $response ) && isset( $response['tags'] ) ) {
				foreach ( $response['tags'] as $tag ) {
					if ( isset( $tag['name'] ) && isset( $tag['tag_id'] ) ) {
						$tags[ $tag['tag_id'] ] = $tag['name'];
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
		$cache_key = 'constantcontact_v3_tags_' . md5( serialize( $credentials ) );

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
	 * output any (possible) extra editor settings for this API
	 *
	 * @param array $params allow various different calls to this method
	 */
	public function render_extra_editor_settings( $params = array() ) {
		$params['tags'] = $this->getTags();
		if ( ! is_array( $params['tags'] ) ) {
			$params['tags'] = array();
		}
		$this->output_controls_html( 'constantcontact_v3/tags', $params );
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

			// Create tags using ConstantContact API
			$created_tags = array();

			foreach ( $new_tag_names as $tag_name ) {
				$new_tag_id = $this->create_new_tag( $tag_name );
				if ( $new_tag_id ) {
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

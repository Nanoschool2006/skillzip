<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_ConvertKit extends Thrive_Dash_List_Connection_Abstract {
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
		return 'ConvertKit / Seva';
	}

	/**
	 * @return bool
	 */
	public function has_tags() {
		// Tags are only supported when Secret API Key is configured
		// Secret API Key enables write operations (create/assign tags)
		// Public API Key only supports read operations
		return $this->supports_tags();
	}

	/**
	 * @return bool
	 */
	public function can_create_tags_via_api() {
		return true;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'convertkit' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		// SECURITY FIX: Use $this->post() instead of direct $_POST access
		$connection = $this->post( 'connection' );

		// Validate connection data structure
		if ( ! is_array( $connection ) ) {
			return $this->error( __( 'Invalid connection data', 'thrive-dash' ) );
		}

		$key = isset( $connection['key'] ) ? sanitize_text_field( $connection['key'] ) : '';

		if ( empty( $key ) ) {
			return $this->error( __( 'You must provide a valid ConvertKit API Key', 'thrive-dash' ) );
		}

		// Secret is optional - only needed for tag support (write operations)
		$secret = isset( $connection['secret'] ) ? sanitize_text_field( $connection['secret'] ) : '';

		$credentials = array( 'key' => $key );
		
		// Only save secret if provided
		if ( ! empty( $secret ) ) {
			$credentials['secret'] = $secret;
		}

		$this->set_credentials( $credentials );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to ConvertKit: %s', 'thrive-dash' ), $this->_error ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'ConvertKit connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		return is_array( $this->_get_lists() );
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return Thrive_Dash_List_Connection_ConvertKit
	 */
	protected function get_api_instance() {
		// Return self - we have all API methods in this class now
		return $this;
	}
	
	/**
	 * API Base URL
	 *
	 * @var string
	 */
	protected $api_url = 'https://api.convertkit.com/v3/';
	
	/**
	 * Existing tags cache (for API compatibility)
	 *
	 * @var array
	 */
	public $_existing_tags = array();
	
	/**
	 * Make ConvertKit API call
	 * Based on: https://developers.kit.com/api-reference/v3/tags
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $args     Request arguments
	 * @param string $method   HTTP method
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_ConvertKit_Exception
	 */
	protected function _call( $endpoint, $args = array(), $method = 'GET' ) {
		$url = $this->api_url . $endpoint;
		
		$api_key = $this->param( 'key' );
		$api_secret = $this->param( 'secret' );
		
		// Determine which API authentication to use:
		// If secret is provided, use api_secret (supports write operations including tags)
		// Otherwise, use api_key (read-only operations)
		$has_secret = ! empty( $api_secret );
		$api_param_name = $has_secret ? 'api_secret' : 'api_key';
		$api_value = $has_secret ? $api_secret : $api_key;

		if ( 'GET' === $method ) {
			// Use the appropriate parameter name and value
			$args[ $api_param_name ] = $api_value;
			$url = add_query_arg( $args, $url );

			$response = wp_remote_get( $url, array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			) );
		} else {
			// Add API authentication to body for POST/DELETE requests
			if ( ! isset( $args[ $api_param_name ] ) ) {
				$args[ $api_param_name ] = $api_value;
			}

			$request_args = array(
				'timeout' => 30,
				'method'  => $method,
				'headers' => array(
					'Content-Type' => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( $args ),
			);

			$response = wp_remote_request( $url, $request_args );
		}

		if ( is_wp_error( $response ) ) {
			throw new Thrive_Dash_Api_ConvertKit_Exception( $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code < 200 || $code >= 300 ) {
			throw new Thrive_Dash_Api_ConvertKit_Exception( 'HTTP Error ' . $code . ': ' . $body );
		}

		$result = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Thrive_Dash_Api_ConvertKit_Exception( 'JSON decode error: ' . json_last_error_msg() );
		}

		return $result;
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * ConvertKit has both sequences and forms
	 *
	 * @return array|string for error
	 */
	protected function _get_lists() {
		/**
		 * just try getting the lists as a connection test
		 */
		try {
			$lists = array();

			$data = $this->get_forms();
			if ( ! empty( $data ) ) {
				foreach ( $data as $form ) {
					if ( ! empty( $form['archived'] ) ) {
						continue;
					}
					$lists[] = array(
						'id'   => $form['id'],
						'name' => $form['name'],
					);
				}
			}

			return $lists;

		} catch ( Thrive_Dash_Api_ConvertKit_Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
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
		// Validate arguments
		if ( ! is_array( $arguments ) ) {
			return __( 'Invalid arguments provided', 'thrive-dash' );
		}

		// Validate email format
		if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
			return __( 'Invalid email address', 'thrive-dash' );
		}

		try {
			$arguments['custom_fields_ids'] = $this->buildMappedCustomFields( $arguments );

			// Allow usage of single quote in name.
			if ( isset( $arguments['name'] ) ) {
				$arguments['name'] = str_replace( "\\'", "'", $arguments['name'] );
			}

			// Only set fields if we have actual custom field data
			if ( ! empty( $arguments['custom_fields_ids'] ) ) {
				$arguments['fields'] = $this->_generateCustomFields( $arguments );
			} else if ( ! empty( $arguments['automator_custom_fields'] ) ) {
				$arguments['fields'] = $arguments['automator_custom_fields'];
				unset( $arguments['automator_custom_fields'] );
			}
			// Don't set fields to empty object - omit it entirely if no custom fields
			if ( isset( $arguments['fields'] ) && empty( $arguments['fields'] ) ) {
				unset( $arguments['fields'] );
			}

			// Subscribe to form
			$result = $this->subscribeForm( $list_identifier, $arguments );

			// Apply tags if provided AND if using Secret API Key (has write permissions)
			// Public API Key users won't have tags UI, but skip silently for backward compatibility
			$tags_key = $this->get_tags_key();

			if ( ! empty( $arguments[ $tags_key ] ) && $this->supports_tags() ) {
				$this->add_tags_to_subscriber( $arguments['email'], $arguments[ $tags_key ] );
			}

		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Get custom fields from ConvertKit API
	 * Endpoint: GET /v3/custom_fields
	 *
	 * @return array
	 */
	public function getCustomFields() {
		try {
			$result = $this->_call( 'custom_fields' );

			// Validate response structure
			if ( ! is_array( $result ) ) {
				return array();
			}

			if ( ! isset( $result['custom_fields'] ) || ! is_array( $result['custom_fields'] ) ) {
				return array();
			}

			// Validate each field has required structure
			$fields = array();
			foreach ( $result['custom_fields'] as $field ) {
				// Skip invalid field entries
				if ( ! is_array( $field ) || empty( $field['id'] ) || empty( $field['name'] ) ) {
					continue;
				}

				$fields[] = $field;
			}

			return $fields;
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * @param array $args
	 *
	 * @return object
	 * @throws Thrive_Dash_Api_ConvertKit_Exception
	 */
	protected function _generateCustomFields( $args ) {
		$fields = $this->_getCustomFields( false );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return (object) array();
		}

		$response = array();
		$ids = $this->buildMappedCustomFields( $args );

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			// Handle phone field if present
			if ( ! empty( $args['phone'] ) ) {
				$phone_fields = $this->phoneFields( $args['phone'] );
				if ( is_array( $phone_fields ) && isset( $phone_fields['phone'] ) ) {
					$response['phone'] = $phone_fields['phone'];
				}
			}
			return (object) $response;
		}

		// OPTIMIZATION: Create indexed lookup by field ID to eliminate nested loop
		// Before: O(n*m) nested loop complexity
		// After: O(n+m) single pass complexity
		$fields_by_id = array();
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && isset( $field['name'] ) ) {
				$fields_by_id[ (int) $field['id'] ] = $field;
			}
		}

		// Single loop through IDs with O(1) lookup
		foreach ( $ids as $key => $id ) {
			if ( ! is_array( $id ) || ! isset( $id['value'] ) ) {
				continue;
			}

			$field_id = (int) $id['value'];
			if ( ! isset( $fields_by_id[ $field_id ] ) ) {
				continue;
			}

			$field = $fields_by_id[ $field_id ];

			// Use the 'key' field directly from API response (e.g., "last_name")
			// This is the correct field to use for API submissions per ConvertKit API docs
			// Fallback to parsing 'name' field for backward compatibility
			if ( ! empty( $field['key'] ) ) {
				$_name = $field['key'];
			} else {
				// Legacy fallback: Extract field name: ck_field_{id}_{name} -> {name}
				$_name = $field['name'];
				$_name = str_replace( 'ck_field_', '', $_name );
				$_name = explode( '_', $_name );
				unset( $_name[0] );
				$_name = implode( '_', $_name );
			}

			$name = strpos( $id['type'], 'mapping_' ) !== false ? $id['type'] . '_' . $key : $key;
			$cf_form_name = str_replace( '[]', '', $name );

			if ( ! empty( $args[ $cf_form_name ] ) ) {
				$response[ $_name ] = $this->process_field( $args[ $cf_form_name ] );
			}
		}

		// Handle phone field
		if ( ! empty( $args['phone'] ) ) {
			$phone_fields = $this->phoneFields( $args['phone'] );
			if ( is_array( $phone_fields ) && isset( $phone_fields['phone'] ) ) {
				$response['phone'] = $phone_fields['phone'];
			}
		}

		return (object) $response;
	}

	/**
	 * Build custom fields mapping for automations
	 *
	 * @param $automation_data
	 *
	 * @return object
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();

		// Validate input
		if ( ! is_array( $automation_data ) || empty( $automation_data['api_fields'] ) || ! is_array( $automation_data['api_fields'] ) ) {
			return (object) $mapped_data;
		}

		$fields = $this->_getCustomFields( false );
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return (object) $mapped_data;
		}

		// OPTIMIZATION: Create indexed lookup by field ID to eliminate nested loop
		// Before: O(n*m) nested loop complexity
		// After: O(n+m) single pass complexity
		$fields_by_id = array();
		foreach ( $fields as $field ) {
			if ( isset( $field['id'] ) && isset( $field['name'] ) ) {
				$fields_by_id[ (int) $field['id'] ] = $field;
			}
		}

		// Single loop through API fields with O(1) lookup
		foreach ( $automation_data['api_fields'] as $pair ) {
			if ( ! is_array( $pair ) || ! isset( $pair['value'] ) || ! isset( $pair['key'] ) ) {
				continue;
			}

			$value = sanitize_text_field( $pair['value'] );
			if ( empty( $value ) ) {
				continue;
			}

			$field_id = (int) $pair['key'];
			if ( ! isset( $fields_by_id[ $field_id ] ) ) {
				continue;
			}

			$field = $fields_by_id[ $field_id ];

			// Use the 'key' field directly from API response (e.g., "last_name")
			// This is the correct field to use for API submissions per ConvertKit API docs
			// Fallback to parsing 'name' field for backward compatibility
			if ( ! empty( $field['key'] ) ) {
				$_name = $field['key'];
			} else {
				// Legacy fallback: Extract field name: ck_field_{id}_{name} -> {name}
				$_name = $field['name'];
				$_name = str_replace( 'ck_field_', '', $_name );
				$_name = explode( '_', $_name );
				unset( $_name[0] );
				$_name = implode( '_', $_name );
			}

			$mapped_data[ $_name ] = $value;
		}

		return (object) $mapped_data;
	}


	/**
	 * Override get_api_data to include available tags for the editor.
	 *
	 * @param array $params Parameters.
	 * @param bool  $force Force refresh.
	 * @param bool  $get_all Get all data.
	 *
	 * @return array
	 */
	public function get_api_data( $params = array(), $force = false, $get_all = false ) {
		$data = parent::get_api_data( $params, $force, $get_all );

		// Clear tags cache when force refresh is requested or no_cache parameter is set
		if ( $force || ! empty( $params['no_cache'] ) ) {
			$this->clearTagsCache();
		}

		$data['tags'] = $this->get_tags();
		
		// Only show tags UI if using Secret API Key (has write permissions)
		// Public API Key cannot create/assign tags
		$data['supports_tags'] = $this->supports_tags();

		return $data;
	}

	/**
	 * Get all forms from ConvertKit API
	 * Endpoint: GET /v3/forms
	 * Docs: https://developers.kit.com/
	 *
	 * @return array Array of forms from API
	 */
	public function get_forms() {
		try {
			$result = $this->_call( 'forms' );
			return isset( $result['forms'] ) ? $result['forms'] : array();
		} catch ( Exception $e ) {
			return array();
		}
	}
	
	/**
	 * Get all tags from ConvertKit API (raw).
	 * Endpoint: GET /v3/tags
	 * Docs: https://developers.kit.com/api-reference/v3/tags#list-tags
	 *
	 * @return array Array of tags from API
	 */
	protected function get_tags_from_api() {
		try {
			$result = $this->_call( 'tags' );
			return isset( $result['tags'] ) ? $result['tags'] : array();
		} catch ( Exception $e ) {
			return array();
		}
	}
	
	/**
	 * Set existing tags (fetch and cache)
	 * Called by legacy code
	 *
	 * @return void
	 */
	public function setExistingTags() {
		$this->_existing_tags = $this->get_tags_from_api();
	}
	
	/**
	 * Get all tags from ConvertKit (formatted for editor).
	 *
	 * @return array
	 */
	public function get_tags() {
		$cache_key = 'convertkit_tags_' . $this->get_key();
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		try {
			// Get tags from API
			$tags = $this->get_tags_from_api();
			
			if ( empty( $tags ) || ! is_array( $tags ) ) {
				return array();
			}

			// Format tags for editor.
			$formatted_tags = array();
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag['id'] ) && ! empty( $tag['name'] ) ) {
					$formatted_tags[] = array(
						'id'   => (string) $tag['id'],
						'name' => $tag['name'],
					);
				}
			}

			// Cache for 15 minutes.
			set_transient( $cache_key, $formatted_tags, 15 * MINUTE_IN_SECONDS );

			return $formatted_tags;

		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Clear tags cache.
	 *
	 * @return void
	 */
	public function clearTagsCache() {
		delete_transient( 'convertkit_tags_' . $this->get_key() );
	}
	
	/**
	 * Check if the current API connection supports tags (write operations)
	 * 
	 * Secret API Keys support both read and write operations (including tags)
	 * Public API Keys only support read operations (no tag creation/assignment)
	 *
	 * @return bool
	 */
	public function supports_tags() {
		$api_secret = $this->param( 'secret' );
		
		// Tags are supported only if a secret is provided
		// Secret API Key enables write operations (create/assign tags)
		// Public API Key (without secret) only supports read operations
		return ! empty( $api_secret );
	}
	
	/**
	 * Create tags if they don't exist yet (called from Apply button)
	 *
	 * @param array $params Parameters containing 'tag_names'
	 *
	 * @return array Response with success status and created tags info
	 */
	public function _create_tags_if_needed( $params ) {
		// Check if Secret API Key is configured (required for tag creation)
		if ( ! $this->supports_tags() ) {
			return array(
				'success' => false,
				'message' => __( 'Secret API Key required for tag management', 'thrive-dash' )
			);
		}

		$tag_names = isset( $params['tag_names'] ) ? $params['tag_names'] : array();

		// Handle both array and comma-separated string
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
			$existing_tags = $this->get_tags( true ); // Force fresh fetch
			$existing_tag_names = array();

			foreach ( $existing_tags as $tag ) {
				$existing_tag_names[] = strtolower( $tag['name'] );
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

			// Create new tags using ConvertKit API
			$created_tags = array();
			foreach ( $new_tag_names as $tag_name ) {
				try {
					$result = $this->createTag( $tag_name );

					// Handle response format
					$tag_data = isset( $result['tag'] ) ? $result['tag'] : $result;

					if ( isset( $tag_data['id'] ) && isset( $tag_data['name'] ) ) {
						$created_tags[] = array(
							'id' => (string) $tag_data['id'],
							'name' => $tag_data['name']
						);
					}
				} catch ( Exception $e ) {
					// Continue creating other tags even if one fails
				}
			}

			// Clear cache so new tags appear immediately
			$this->clearTagsCache();

			return array(
				'success' => true,
				'message' => sprintf(
					_n( '%d tag created successfully', '%d tags created successfully', count( $created_tags ), 'thrive-dash' ),
					count( $created_tags )
				),
				'tags_created' => count( $created_tags ),
				'tags' => $created_tags
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}
	}

	/**
	 * Add tags to a subscriber
	 * Tags can be comma-separated tag IDs or tag names
	 *
	 * @param string $email     Subscriber email
	 * @param string $tags_data Comma-separated tag IDs or names
	 *
	 * @return void
	 */
	protected function add_tags_to_subscriber( $email, $tags_data ) {
		if ( empty( $tags_data ) || empty( $email ) ) {
			return;
		}

		// Validate email format
		if ( ! is_email( $email ) ) {
			return;
		}

		// Split tags by comma and clean
		$tags = array_map( 'trim', explode( ',', $tags_data ) );
		$tags = array_filter( $tags ); // Remove empty values

		if ( empty( $tags ) ) {
			return;
		}

		// OPTIMIZATION: Fetch existing tags once at start instead of per-tag lookup
		if ( empty( $this->_existing_tags ) ) {
			$this->setExistingTags();
		}

		// Build lookup map for existing tags
		$existing_tags_map = array();
		if ( is_array( $this->_existing_tags ) ) {
			foreach ( $this->_existing_tags as $tag ) {
				if ( isset( $tag['name'] ) && isset( $tag['id'] ) ) {
					// Index by both name and ID for fast lookup
					$existing_tags_map[ $tag['name'] ] = $tag['id'];
					$existing_tags_map[ $tag['id'] ] = $tag['id'];
				}
			}
		}

		// Separate existing tags from new tags that need creation
		$existing_tag_ids = array();
		$new_tag_names = array();

		foreach ( $tags as $tag ) {
			if ( is_numeric( $tag ) ) {
				// It's a tag ID
				$tag_id = (int) $tag;
				if ( isset( $existing_tags_map[ $tag_id ] ) ) {
					$existing_tag_ids[] = $tag_id;
				}
			} else {
				// It's a tag name
				if ( isset( $existing_tags_map[ $tag ] ) ) {
					$existing_tag_ids[] = $existing_tags_map[ $tag ];
				} else {
					$new_tag_names[] = $tag;
				}
			}
		}

		// Assign existing tags
		foreach ( $existing_tag_ids as $tag_id ) {
			try {
				$this->assignTag( $tag_id, $email );
			} catch ( Exception $e ) {
				// Silent fail - tag assignment errors shouldn't break subscription
			}
		}

		// Create and assign new tags
		foreach ( $new_tag_names as $tag_name ) {
			try {
				$result = $this->createAndAssignTag( $tag_name, $email );
				// The result is intentionally not used here, as errors are handled via exceptions.
			} catch ( Exception $e ) {
				// Silent fail - tag creation errors shouldn't break subscription
			}
		}
	}
	
	/**
	 * Search for a tag by name in cached tags
	 *
	 * @param string $needle Tag name to search for
	 *
	 * @return array|false Tag array if found, false otherwise
	 */
	public function searchTagInList( $needle ) {
		if ( empty( $this->_existing_tags ) ) {
			$this->setExistingTags();
		}

		foreach ( $this->_existing_tags as $tag ) {
			if ( isset( $tag['name'] ) && $tag['name'] === $needle ) {
				return $tag;
			}
		}

		return false;
	}
	
	/**
	 * Create a new tag
	 * Endpoint: POST /v3/tags
	 * Docs: https://developers.kit.com/api-reference/v3/tags#create-a-tag
	 *
	 * @param string $tag_name Tag name
	 * @param string $email    Email address (unused, for compatibility)
	 *
	 * @return array Response with created tag data
	 */
	public function createTag( $tag_name, $email = '' ) {
		$args = array(
			'tag' => array(
				'name' => $tag_name,
			),
		);

		return $this->_call( 'tags', $args, 'POST' );
	}
	
	/**
	 * Assign a tag to a subscriber
	 * Endpoint: POST /v3/tags/{tag_id}/subscribe
	 * Docs: https://developers.kit.com/api-reference/v3/tags#tag-a-subscriber
	 *
	 * @param int    $tag_id Tag ID
	 * @param string $email  Email address
	 *
	 * @return array Response data
	 */
	public function assignTag( $tag_id, $email ) {
		$args = array(
			'email' => $email,
		);

		return $this->_call( 'tags/' . $tag_id . '/subscribe', $args, 'POST' );
	}
	
	/**
	 * Add multiple tags to a contact
	 *
	 * @param string $email Email address
	 * @param array  $tags  Array of tag names
	 *
	 * @return array Array of results from each tag assignment
	 */
	public function addTagsToContact( $email, $tags ) {
		$results = array();

		foreach ( $tags as $tag_name ) {
			// Check if tag exists
			$existing_tag = $this->searchTagInList( $tag_name );

			if ( $existing_tag ) {
				// Tag exists, assign it
				$result = $this->assignTag( $existing_tag['id'], $email );
			} else {
				// Tag doesn't exist, create and assign
				$result = $this->createAndAssignTag( $tag_name, $email );
			}

			$results[] = $result;
		}

		return $results;
	}
	
	/**
	 * Create a new tag and assign it to a subscriber
	 *
	 * @param string $tag_name Tag name
	 * @param string $email    Email address
	 *
	 * @return array Response data
	 */
	public function createAndAssignTag( $tag_name, $email ) {
		try {
			// Create the tag
			$tag_result = $this->createTag( $tag_name );

			// Handle both response formats: wrapped in 'tag' key or direct
			$tag_data = isset( $tag_result['tag'] ) ? $tag_result['tag'] : $tag_result;
			
			if ( isset( $tag_data['id'] ) ) {
				// Add to existing tags cache so it can be found next time
				$this->_existing_tags[] = array(
					'id'   => $tag_data['id'],
					'name' => $tag_data['name'],
				);
				
				// Assign the tag to the subscriber
				return $this->assignTag( $tag_data['id'], $email );
			}

			return $tag_result;
			
		} catch ( Exception $e ) {
			throw $e;
		}
	}
	
	/**
	 * Subscribe to a form
	 * Endpoint: POST /v3/forms/{form_id}/subscribe
	 *
	 * @param int   $form_id   Form ID
	 * @param array $arguments Subscriber data (email, name, fields, etc.)
	 *
	 * @return array Response data
	 */
	public function subscribeForm( $form_id, $arguments ) {
		$args = array(
			'email' => $arguments['email'],
		);

		if ( ! empty( $arguments['name'] ) ) {
			$args['first_name'] = $arguments['name'];
		}

		// Only add fields if they're not empty
		// ConvertKit expects fields to be an object, not an array
		if ( ! empty( $arguments['fields'] ) ) {
			$fields = is_object( $arguments['fields'] ) ? (array) $arguments['fields'] : $arguments['fields'];
			// Only add if fields has actual data
			if ( ! empty( $fields ) && is_array( $fields ) && count( $fields ) > 0 ) {
				$args['fields'] = (object) $fields; // Convert to object for JSON encoding
			}
		}

		return $this->_call( 'forms/' . $form_id . '/subscribe', $args, 'POST' );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '{{subscriber.email_address}}';
	}

	/**
	 * @param $force
	 *
	 * @return array
	 */
	protected function _getCustomFields( $force ) {

		// Serve from cache if exists and requested
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		/** @var $api Thrive_Dash_Api_ConvertKit */
		$api = $this->get_api();

		$fields = $api->getCustomFields();

		foreach ( $fields as $key => $field ) {
			$fields[ $key ] = $this->normalize_custom_field( $field );
		}

		$this->_save_custom_fields( $fields );

		return $fields;
	}

	/**
	 * @param      $params
	 * @param bool $force
	 * @param bool $get_all
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_ConvertKit_Exception
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		$response = $this->_getCustomFields( $force );

		return is_array( $response ) ? $response : array();
	}

	protected function normalize_custom_field( $data ) {
		if ( ! is_array( $data ) ) {
			return array( 'type' => 'text' );
		}

		$data['type'] = 'text';

		return parent::normalize_custom_field( $data );
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

		$mapped_fields = $this->get_mapped_field_ids();

		foreach ( $mapped_fields as $mapped_field_name ) {

			// Extract an array with all custom fields (siblings) names from form data
			// {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}
			$cf_form_fields = preg_grep( "#^{$mapped_field_name}#i", array_keys( $form_data ) );

			// Matched "form data" for current allowed name
			if ( ! empty( $cf_form_fields ) && is_array( $cf_form_fields ) ) {

				// Pull form allowed data, sanitize it and build the custom fields array
				foreach ( $cf_form_fields as $cf_form_name ) {
					// Validate nested array structure before access
					if ( ! isset( $form_data[ $cf_form_name ] ) ||
					     ! is_array( $form_data[ $cf_form_name ] ) ||
					     empty( $form_data[ $cf_form_name ][ $this->_key ] ) ) {
						continue;
					}

					$field_id = str_replace( $mapped_field_name . '_', '', $cf_form_name );

					$mapped_data[ $field_id ] = array(
						'type'  => $mapped_field_name,
						'value' => $form_data[ $cf_form_name ][ $this->_key ],
					);
				}
			}
		}


		return $mapped_data;
	}

	/**
	 * @param       $email
	 * @param array $custom_fields ex array( 'cf_name' => 'some nice cf value' )
	 * @param array $extra
	 *
	 * @return false|int|mixed
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {

		if ( empty( $extra['list_identifier'] ) ) {
			return false;
		}

		try {
			$args = array(
				'fields' => (object) $this->prepare_custom_fields_for_api( $custom_fields ),
				'email'  => $email,
				'name'   => ! empty( $extra['name'] ) ? $extra['name'] : '',
			);

			$subscriber = $this->subscribeForm( $extra['list_identifier'], $args );

			// Validate response structure
			if ( ! is_array( $subscriber ) ||
			     ! isset( $subscriber['subscriber'] ) ||
			     ! is_array( $subscriber['subscriber'] ) ||
			     ! isset( $subscriber['subscriber']['id'] ) ) {
				return false;
			}

			return $subscriber['subscriber']['id'];

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Unsubscribe a user (ConvertKit API method)
	 * Endpoint: PUT /v3/unsubscribe
	 *
	 * @param string $email     Email address
	 * @param array  $arguments Additional arguments
	 *
	 * @return array
	 */
	public function unsubscribeUser( $email, $arguments = array() ) {
		try {
			$args = array( 'email' => $email );
			return $this->_call( 'unsubscribe', $args, 'PUT' );
		} catch ( Exception $e ) {
			return array();
		}
	}
	
	/**
	 * Process phone fields (helper method)
	 *
	 * @param string $phone Phone number
	 *
	 * @return array
	 */
	public function phoneFields( $phone ) {
		// Simple phone number processing
		// ConvertKit expects phone in a specific format
		return array(
			'phone' => preg_replace( '/[^0-9+]/', '', $phone ), // Remove non-numeric chars except +
		);
	}
	
	/**
	 * delete a contact from the list
	 *
	 * @param string $email
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public function delete_subscriber( $email, $arguments = array() ) {
		$result = $this->unsubscribeUser( $email, $arguments );
		return isset( $result['subscriber']['id'] );
	}

	/**
	 * Get available custom fields for this api connection
	 *
	 * @param null $list_id
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {

		return $this->_getCustomFields( true );
	}

	/**
	 * Prepare custom fields for api call
	 *
	 * @param array $custom_fields
	 * @param null  $list_identifier
	 *
	 * @return array
	 */
	public function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {
		if ( ! is_array( $custom_fields ) || empty( $custom_fields ) ) {
			return array();
		}

		$prepared_fields = array();
		$cf_prefix = 'ck_field_';
		$api_fields = $this->get_api_custom_fields( null, true );

		if ( ! is_array( $api_fields ) || empty( $api_fields ) ) {
			return array();
		}

		// OPTIMIZATION: Create indexed lookup by field ID to eliminate nested loop
		// Before: O(n*m) nested loop complexity
		// After: O(n+m) single pass complexity
		$fields_by_id = array();
		foreach ( $api_fields as $field ) {
			if ( isset( $field['id'] ) && isset( $field['name'] ) ) {
				$fields_by_id[ (int) $field['id'] ] = $field;
			}
		}

		// Single pass through custom fields with O(1) lookup
		foreach ( $custom_fields as $key => $custom_field ) {
			if ( empty( $custom_field ) ) {
				continue;
			}

			$field_id = (int) $key;
			if ( ! isset( $fields_by_id[ $field_id ] ) ) {
				continue;
			}

			$field = $fields_by_id[ $field_id ];
			$str_to_replace = $cf_prefix . $field['id'] . '_';
			$cf_key = str_replace( $str_to_replace, '', $field['name'] );

			$prepared_fields[ $cf_key ] = $custom_field;
		}

		return $prepared_fields;
	}

	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list' => array( 'api_fields' ), 'tag_input' => array() ) );
	}

	public function has_custom_fields() {
		return true;
	}
}

/**
 * ConvertKit Exception Class
 * Used for API error handling
 */
if ( ! class_exists( 'Thrive_Dash_Api_ConvertKit_Exception' ) ) {
	class Thrive_Dash_Api_ConvertKit_Exception extends Exception {
	}
}

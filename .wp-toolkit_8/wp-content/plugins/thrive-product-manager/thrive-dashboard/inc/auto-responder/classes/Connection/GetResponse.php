<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_GetResponse extends Thrive_Dash_List_Connection_Abstract {
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
		return 'GetResponse';
	}

	/**
	 * Has tags support
	 *
	 * @return bool
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
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'get-response' );
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

		// Validate connection data structure
		if ( ! is_array( $connection ) ) {
			return $this->error( __( 'Invalid connection data', 'thrive-dash' ) );
		}

		$version = (string) ( isset( $connection['version'] ) ? $connection['version'] : '' );

		if ( empty( $connection['key'] ) ) {
			return $this->error( __( 'You must provide a valid GetResponse key', 'thrive-dash' ) );
		}

		if ( $version === '3' && empty( $connection['url'] ) ) {
			return $this->error( __( 'You must provide a valid GetResponse V3 API URL', 'thrive-dash' ) );
		}

		$this->set_credentials( $connection );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to GetResponse using the provided key (<strong>%s</strong>)', 'thrive-dash' ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'GetResponse connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		$gr = $this->get_api();
		/**
		 * just try getting a list as a connection test
		 */
		$credentials = $this->get_credentials();

		try {
			if ( ! $credentials['version'] || $credentials['version'] == 2 ) {
				/** @var Thrive_Dash_Api_GetResponse $gr */
				$gr->getCampaigns();
			} else {
				/** @var Thrive_Dash_Api_GetResponseV3 $gr */
				$gr->ping();
			}
		} catch ( Thrive_Dash_Api_GetResponse_Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Instantiate the API code required for this connection
	 *
	 * @return Thrive_Dash_Api_GetResponse|Thrive_Dash_Api_GetResponseV3
	 */
	protected function get_api_instance() {
		if ( ! $this->param( 'version' ) || $this->param( 'version' ) == 2 ) {
			return new Thrive_Dash_Api_GetResponse( $this->param( 'key' ) );
		}

		$getresponse = new Thrive_Dash_Api_GetResponseV3( $this->param( 'key' ), $this->param( 'url' ) );

		$enterprise_param = $this->param( 'enterprise' );
		if ( ! empty( $enterprise_param ) ) {
			$getresponse->enterprise_domain = $this->param( 'enterprise' );

		}

		return $getresponse;

	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array|false
	 */
	protected function _get_lists() {

		/** @var Thrive_Dash_Api_GetResponse $gr */
		$gr = $this->get_api();

		try {
			$lists       = array();
			$items       = $gr->getCampaigns();
			$credentials = $this->get_credentials();

			if ( ! $credentials['version'] || $credentials['version'] == 2 ) {
				foreach ( $items as $key => $item ) {
					$lists [] = array(
						'id'   => $key,
						'name' => $item->name,
					);
				}
			} else {
				foreach ( $items as $item ) {
					$lists [] = array(
						'id'   => $item->campaignId,
						'name' => $item->name,
					);
				}
			}

			return $lists;
		} catch ( Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * Override get_api_data to include tags.
	 *
	 * @param array $params
	 * @param bool  $force
	 *
	 * @return array
	 */
	public function get_api_data( $params = array(), $force = false ) {
		if ( empty( $params ) ) {
			$params = array();
		}

		$transient = 'tve_api_data_' . $this->get_key();
		$data      = get_transient( $transient );

		if ( false === $force && tve_dash_is_debug_on() ) {
			$force = true;
		}

		if ( true === $force || false === $data ) {
			$data = array(
				'lists'          => $this->get_lists( false ),
				'extra_settings' => $this->get_extra_settings( $params ),
				'custom_fields'  => $this->get_custom_fields( $params ),
				'tags'           => $this->get_tags( $force ),
			);

			set_transient( $transient, $data, MONTH_IN_SECONDS );
		} else {
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			// Always fetch tags separately since they have their own 15-minute cache
			$data['tags'] = $this->get_tags( $force );
		}

		$data['api_custom_fields'] = $this->get_api_custom_fields( $params, $force );

		return $data;
	}

	/**
	 * Get all available tags formatted for the editor.
	 *
	 * @param bool $force Force refresh from API
	 *
	 * @return array
	 */
	public function get_tags( $force = false ) {
		// Only V3 API supports tags
		$credentials = $this->get_credentials();
		$version     = empty( $credentials['version'] ) ? 2 : (int) $credentials['version'];

		if ( $version !== 3 ) {
			return array(); // V2 doesn't support tags
		}

		// Create a unique cache key for tags
		$cache_key = 'getresponse_tags_' . $this->get_key();
		$cached_tags = false;

		// Try to get cached tags if not force refresh
		if ( ! $force ) {
			$cached_tags = get_transient( $cache_key );
		}

		if ( false !== $cached_tags && is_array( $cached_tags ) ) {
			return $cached_tags;
		}

		$tags = array();

		try {
			/** @var Thrive_Dash_Api_GetResponseV3 $api */
			$api = $this->get_api();

			$api_tags = $api->getTags();

			if ( ! empty( $api_tags ) && is_array( $api_tags ) ) {
				foreach ( $api_tags as $tag ) {
					if ( ! empty( $tag->tagId ) && ! empty( $tag->name ) ) {
						$tags[] = array(
							'id'       => $tag->tagId,
							'text'     => $tag->name,
							'selected' => false,
						);
					}
				}
			}

			// Cache the tags for 15 minutes
			if ( is_array( $tags ) && ! empty( $tags ) ) {
				set_transient( $cache_key, $tags, 15 * MINUTE_IN_SECONDS );
				// Store a backup cache that doesn't expire for fallback
				set_transient( $cache_key . '_backup', $tags, YEAR_IN_SECONDS );
			}
		} catch ( Exception $e ) {
			// If API call fails, try to use backup cache
			$backup_cache = get_transient( $cache_key . '_backup' );
			if ( false !== $backup_cache && is_array( $backup_cache ) ) {
				return $backup_cache;
			}
		}

		return $tags;
	}

	/**
	 * Clear the tags cache.
	 *
	 * @return void
	 */
	public function clearTagsCache() {
		$cache_key = 'getresponse_tags_' . $this->get_key();
		delete_transient( $cache_key );
		delete_transient( $cache_key . '_backup' );
	}

	/**
	 * Create tags if they don't already exist.
	 * Called from the editor when tags are added to avoid duplicates.
	 *
	 * @param array $params Array with 'tag_names' key containing tag names
	 *
	 * @return array Response with success status and created tags count
	 */
	public function _create_tags_if_needed( $params ) {
		// Only V3 API supports tags
		$credentials = $this->get_credentials();
		$version     = empty( $credentials['version'] ) ? 2 : (int) $credentials['version'];

		if ( $version !== 3 ) {
			return array(
				'success' => false,
				'message' => __( 'GetResponse V3 API required for tag support', 'thrive-dash' )
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
			/** @var Thrive_Dash_Api_GetResponseV3 $api */
			$api = $this->get_api();

			// Get all existing tags to check for duplicates
			$existing_tags = $this->get_tags( true ); // Force fresh fetch
			$existing_tag_names = array();

			foreach ( $existing_tags as $tag ) {
				$existing_tag_names[] = strtolower( $tag['text'] );
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

			// Create tags one by one using GetResponse API
			$created_tags = array();
			foreach ( $new_tag_names as $tag_name ) {
				try {
					$created_tag = $api->createTag( array( 'name' => $tag_name ) );
					if ( ! empty( $created_tag->tagId ) ) {
						$created_tags[] = array(
							'id' => $created_tag->tagId,
							'text' => $created_tag->name
						);
					}
				} catch ( Exception $e ) {
					// Continue with next tag if one fails
					continue;
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
	 * delete a contact from the list
	 *
	 * @param string $email
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public function delete_subscriber( $email, $arguments = array() ) {
		$api = $this->get_api();
		if ( ! empty( $email ) ) {

			$contacts = $api->searchContacts(
				array(
					'query' => array(
						'email' => $email,
					),
				)
			);

			if ( ! empty( $contacts ) && is_array( $contacts ) ) {
				foreach ( $contacts as $contact ) {
					$api->deleteContact( $contact->contactId, array() );
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * add a contact to a list
	 *
	 * @param string $list_identifier
	 * @param array  $arguments
	 *
	 * @return mixed
	 */
	public function add_subscriber( $list_identifier, $arguments ) {

		/** @var Thrive_Dash_Api_GetResponseV3 $api */
		$api         = $this->get_api();
		$credentials = $this->get_credentials();

		$return  = true;
		$version = empty( $credentials['version'] ) ? 2 : (int) $credentials['version'];

		try {
			if ( 2 === $version ) {
				if ( empty( $arguments['name'] ) ) {
					$arguments['name'] = ' ';
				}
				/** @var Thrive_Dash_Api_GetResponse $api */
				$api->addContact( $list_identifier, $arguments['name'], $arguments['email'], 'standard', (int) empty( $arguments['get-response_cycleday'] ) ? 0 : $arguments['get-response_cycleday'] );
			} else {

				$params = array(
					'email'      => $arguments['email'],
					'dayOfCycle' => empty( $arguments['get-response_cycleday'] ) ? 0 : $arguments['get-response_cycleday'],
					'campaign'   => array(
						'campaignId' => $list_identifier,
					),
					'ipAddress'  => tve_dash_get_ip(),
				);

				if ( ! empty( $arguments['name'] ) ) {
					$params['name'] = $arguments['name'];
				}
				// forward already inserted custom fields
				if ( ! empty( $arguments['CustomFields'] ) ) {
					$params['customFieldValues'] = $arguments['CustomFields'];
				}
				// Set / Create & set Phone as custom field
				if ( ! empty( $arguments['phone'] ) ) {
					$params = array_merge( $params, $this->setCustomPhone( $arguments, $params ) );
				}
				// Build custom fields data
				$existing_custom_fields = ! empty( $params['customFieldValues'] ) ? $params['customFieldValues'] : array();
				$mapped_custom_fields   = $this->buildMappedCustomFields( $arguments, $existing_custom_fields );

			if ( ! empty( $mapped_custom_fields ) ) {
				$params = array_merge( $params, $mapped_custom_fields );
			}

				// Handle tags - include them in the params directly
				$tag_key = $this->get_tags_key();
				if ( ! empty( $arguments[ $tag_key ] ) ) {
					$tag_ids = $this->process_tags( $arguments[ $tag_key ] );
					// Convert from array of objects to simple array of tag IDs
					$params['tags'] = array_map( function( $tag ) {
						return $tag['tagId'];
					}, $tag_ids );
				}

				try {
					/**
					 * this contact may be in other list but try to add it in the current on
					 */
					$new_contact = $api->addContact( $params );

					return true;
				} catch ( Exception $e ) {
					/**
					 * we're talking about the same email but
					 * it is the same contact in multiple list
					 */
					$contacts = $api->searchContacts(
						array(
							'query' => array(
								'email' => $params['email'],
							),
						)
					);

					if ( ! empty( $contacts ) && is_array( $contacts ) ) {
						foreach ( $contacts as $contact ) {
							/**
							 * Update the subscriber only in current list
							 */
							if ( ! empty( $contact->campaign->campaignId ) &&
							     $contact->campaign->campaignId === $params['campaign']['campaignId'] ) {
								// Tags are already in $params, so updateContact will include them
								$api->updateContact( $contact->contactId, $params );
								// Removed break; to update all matching contacts
							}
						}
					}
				}
			}
		} catch ( Exception $e ) {
			$return = $e->getMessage();
		}

		return $return;
	}

	/**
	 * Build or add to existing custom fields array
	 *
	 * @param array $args
	 * @param array $mapped_data
	 *
	 * @return array
	 */
	public function buildMappedCustomFields( $args, $mapped_data = array() ) {

		// Validate tve_mapping exists and is properly encoded
		if ( empty( $args['tve_mapping'] ) ) {
			return array();
		}

		if ( ! tve_dash_is_bas64_encoded( $args['tve_mapping'] ) ) {
			return array();
		}

		$decoded = base64_decode( $args['tve_mapping'] );
		if ( ! is_serialized( $decoded ) ) {
			return array();
		}

		$form_data = thrive_safe_unserialize( $decoded );

		// Validate unserialized data is an array
		if ( ! is_array( $form_data ) ) {
			return array();
		}

		// Validate mapped_data parameter is an array
		if ( ! is_array( $mapped_data ) ) {
			return array();
		}

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

						$mapped_api_id = $form_data[ $cf_form_name ][ $this->_key ];
						$cf_form_name  = str_replace( '[]', '', $cf_form_name );
						if ( ! empty( $args[ $cf_form_name ] ) ) {
							$args[ $cf_form_name ] = $this->process_field( $args[ $cf_form_name ] );
							$mapped_data[]         = array(
								'customFieldId' => $mapped_api_id,
								'value'         => array( sanitize_text_field( $args[ $cf_form_name ] ) ),
							);
						}

					}
				}
			}

		return ! empty( $mapped_data ) ? array( 'customFieldValues' => $mapped_data ) : array();
	}

	/**
	 * Set / create&set a new phone custom field
	 *
	 * @param       $arguments
	 * @param array $params
	 *
	 * @return array
	 */
	public function setCustomPhone( $arguments, $params = array() ) {

		if ( empty( $arguments ) || ! is_array( $params ) ) {
			return array();
		}

		$custom_fields = $this->get_api()->getCustomFields();

		if ( is_array( $custom_fields ) ) {

			$phone_field = array_values( wp_list_filter( $custom_fields, array( 'name' => 'thrvphone' ) ) );

			/**
			 * We use a custom field to add phone filed for getResponse
			 * This because getResponse has a strict validation for built in phone number and very often added contacts
			 * with this custom field will fail
			 */
			if ( empty( $phone_field ) ) {

				$field_args = array(
					'name'   => 'thrvphone',
					'type'   => 'number',
					'hidden' => false,
					'values' => array(),
				);

				$phone_field = $this->get_api()->setCustomField( $field_args );
			}

			// Validate phone_field structure before accessing
			if ( isset( $phone_field[0] ) &&
			     is_object( $phone_field[0] ) &&
			     ! empty( $phone_field[0]->customFieldId ) ) {
				$phone_value = str_replace( array( '-', '+', ' ' ), '', trim( $arguments['phone'] ) );

				$params['customFieldValues'] = array(
					array(
						'customFieldId' => $phone_field[0]->customFieldId,
						'value'         => array( $phone_value ),
					),
				);
			}
		}

		return $params;
	}

	/**
	 * Render extra html API setup form
	 *
	 * @param array $params
	 * @param bool  $force  force refresh from API
	 *
	 * @return array
	 */
	public function get_extra_settings( $params = array(), $force = false ) {

		return $params;
	}

	/**
	 * Render extra html API setup form
	 *
	 * @param array $params
	 *
	 * @see api-list.php
	 *
	 */
	public function render_extra_editor_settings( $params = array() ) {

		$this->output_controls_html( 'getresponse/cycleday', $params );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {

		return '[[email]]';
	}

	/**
	 * @param      $params
	 * @param bool $force
	 * @param bool $get_all
	 *
	 * @return array|mixed
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		// Serve from cache if exists and requested
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		// Needed custom fields type [every API can have different naming type]
		$allowed_types = array(
			'text',
			'url',
		);

		$custom_data = array();

		try {
			/** @var Thrive_Dash_Api_GetResponseV3 $api */
			$custom_fields = $this->get_api()->getCustomFields();

			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {
					if ( ! empty( $field->type ) && in_array( $field->type, $allowed_types, true ) ) {
						$custom_data[] = $this->normalize_custom_field( $field );
					}
				}
			}

			$this->_save_custom_fields( $custom_data );

		} catch ( Thrive_Dash_Api_GetResponse_Exception $e ) {
		}

		return $custom_data;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {

		$field = (object) $field;

		return array(
			'id'    => ! empty( $field->customFieldId ) ? $field->customFieldId : '',
			'name'  => ! empty( $field->name ) ? $field->name : '',
			'type'  => ! empty( $field->type ) ? $field->type : '',
			'label' => ! empty( $field->name ) ? $field->name : '',
		);
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
	 * @param       $email
	 * @param array $custom_fields
	 * @param array $extra
	 *
	 * @return int
	 */
	public function add_custom_fields( $email, $custom_fields = array(), $extra = array() ) {

		try {
			$api = $this->get_api();

			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;

			$args = array(
				'email' => $email,
			);
			if ( ! empty( $extra['name'] ) ) {
				$args['name'] = $extra['name'];
			}
			$args['CustomFields'] = $this->prepare_custom_fields_for_api( $custom_fields, $list_id );

			$this->add_subscriber( $list_id, $args );

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
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

		$prepared_fields = array();
		$api_fields      = $this->get_api_custom_fields( null, true );
		if ( empty( $custom_fields ) ) {
			return $prepared_fields;
		}

		foreach ( $api_fields as $field ) {
			foreach ( $custom_fields as $key => $custom_field ) {
				if ( $field['id'] == $key ) {

					$prepared_fields[] = array(
						'customFieldId' => $field['id'],
						'value'         => array( $custom_field ),
					);
				}
			}
		}

		return $prepared_fields;
	}

	/**
	 * Process tags - convert comma-separated tag names to tag IDs array.
	 *
	 * PERFORMANCE OPTIMIZATION:
	 * Previous implementation made 2-3 API calls per tag (N+1 problem).
	 * With 10 tags, this resulted in 20-30 API calls.
	 *
	 * New implementation:
	 * 1. Fetch ALL tags once at start (1 API call)
	 * 2. Build in-memory lookup indexes (by ID and name)
	 * 3. Lookup each requested tag in indexes (O(1) operations)
	 * 4. Only make API calls to create NEW tags (unavoidable)
	 *
	 * Result: With 10 existing tags = 1 API call (vs 20-30 before)
	 *         With 10 new tags = 11 API calls (vs 20-30 before)
	 *         With 5 new + 5 existing = 6 API calls (vs 20-30 before)
	 *
	 * @param string $tags_input Comma-separated tag names or IDs
	 *
	 * @return array Array of tag objects with tagId
	 */
	protected function process_tags( $tags_input ) {
		$tag_ids = array();

		if ( empty( $tags_input ) ) {
			return $tag_ids;
		}

		// Split and clean tag items
		$tag_items = explode( ',', trim( $tags_input, ' ,' ) );
		$tag_items = array_filter( array_map( 'trim', $tag_items ) );

		if ( empty( $tag_items ) ) {
			return $tag_ids;
		}

		try {
			/** @var Thrive_Dash_Api_GetResponseV3 $api */
			$api = $this->get_api();

			// OPTIMIZATION: Fetch ALL tags ONCE at the start
			$all_tags = $api->getTags();

			// Build fast lookup indexes
			$tags_by_id   = array();
			$tags_by_name = array();

			if ( ! empty( $all_tags ) && is_array( $all_tags ) ) {
				foreach ( $all_tags as $tag ) {
					if ( ! empty( $tag->tagId ) && ! empty( $tag->name ) ) {
						// Index by ID for numeric lookups
						$tags_by_id[ $tag->tagId ] = $tag;
						// Index by lowercase name for case-insensitive matching
						$tags_by_name[ strtolower( $tag->name ) ] = $tag;
					}
				}
			}

			// Collect tags that need to be created
			$tags_to_create = array();

			// Process each requested tag
			foreach ( $tag_items as $tag_item ) {
				$found = false;

				// Check if it's an existing tag ID (numeric string)
				if ( is_numeric( $tag_item ) && isset( $tags_by_id[ $tag_item ] ) ) {
					$tag_ids[] = array( 'tagId' => $tag_item );
					$found     = true;
				}
				// Check if it's an existing tag name (case-insensitive)
				elseif ( isset( $tags_by_name[ strtolower( $tag_item ) ] ) ) {
					$tag_ids[] = array( 'tagId' => $tags_by_name[ strtolower( $tag_item ) ]->tagId );
					$found     = true;
				}

				// Collect tags that need creation
				if ( ! $found ) {
					$tags_to_create[] = $tag_item;
				}
			}

			// Create new tags (one API call per new tag - unavoidable with GetResponse API)
			foreach ( $tags_to_create as $tag_name ) {
				try {
					$new_tag = $api->createTag( array( 'name' => $tag_name ) );
					if ( ! empty( $new_tag->tagId ) ) {
						$tag_ids[] = array( 'tagId' => $new_tag->tagId );
					}
				} catch ( Exception $e ) {
					// Continue with next tag if creation fails
					continue;
				}
			}

		} catch ( Exception $e ) {
			// Silently handle errors - return whatever tags were successfully processed
		}

		return $tag_ids;
	}
}


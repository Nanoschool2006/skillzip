<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Ontraport extends Thrive_Dash_List_Connection_Abstract {
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
		return 'Ontraport';
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'ontraport' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		$key    = ! empty( $_POST['connection']['key'] ) ? sanitize_text_field( $_POST['connection']['key'] ) : '';
		$app_id = ! empty( $_POST['connection']['app_id'] ) ? sanitize_text_field( $_POST['connection']['app_id'] ) : '';

		if ( empty( $key ) || empty( $app_id ) ) {
			return $this->error( __( 'You must provide a valid Ontraport AppID/APIKey', 'thrive-dash' ) );
		}

		$this->set_credentials( $this->post( 'connection' ) );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to Ontraport: %s', 'thrive-dash' ), $this->_error ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();
		$this->success( __( 'Ontraport connected successfully', 'thrive-dash' ) );

		return true;
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
	 * @return Thrive_Dash_Api_Ontraport
	 */
	protected function get_api_instance() {
		return new Thrive_Dash_Api_Ontraport( $this->param( 'app_id' ), $this->param( 'key' ) );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * Ontraport has both sequences and forms
	 *
	 * @return array|string for error
	 */
	protected function _get_lists() {
		/**
		 * just try getting the lists as a connection test
		 */
		try {

			$lists = array();

			/** @var $op Thrive_Dash_Api_Ontraport */
			$op = $this->get_api();

			$data = $op->get_campaigns();

			if ( ! empty( $data ) ) {
				foreach ( $data as $id => $list ) {
					$lists[] = array(
						'id'   => $id,
						'name' => $list['name'],
					);
				}
			}

			return $lists;

		} catch ( Thrive_Dash_Api_Ontraport_Exception $e ) {
			$this->_error = $e->getMessage();

			return false;
		}
	}

	/**
	 * Get campaigns
	 *
	 * @param array $params
	 * @param bool  $force  force refresh from API
	 *
	 * @return array
	 */
	public function get_extra_settings( $params = array(), $force = false ) {

		$lists = array();

		try {

			$data = $this->get_api()->get_sequences();

			if ( ! empty( $data ) ) {
				foreach ( $data as $id => $list ) {
					$lists['sequences'][] = array(
						'id'   => $id,
						'name' => $list['name'],
					);
				}
			}
		} catch ( Exception $e ) {
			return $lists;
		}


		return $lists;
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

		try {

			list( $firstname, $lastname ) = $this->get_name_parts( $arguments['name'] );

			$data = array(
				'firstname' => $firstname,
				'lastname'  => $lastname,
				'email'     => $arguments['email'],
				'type'      => ! empty( $arguments['ontraport_ontraport_type'] ) ? $arguments['ontraport_ontraport_type'] : '',
			);

			if ( ! empty( $arguments['phone'] ) ) {
				$data['phone'] = $arguments['phone'];
			}

			// Add contact first - this now returns contact data
			$api = $this->get_api();
			$contact_data = $api->add_contact( $list_identifier, $data );
			
			if ( empty( $contact_data ) || ! is_array( $contact_data ) ) {
				return 'Failed to add contact to Ontraport';
			}
			
			// Handle tags if provided
			$tag_ids = array();
			$all_tag_names = array();

			// Check for tags in the arguments
			if ( ! empty( $arguments['ontraport_tags'] ) ) {
				$tag_names = explode( ',', trim( $arguments['ontraport_tags'], ' ,' ) );
				$tag_names = array_map( 'trim', $tag_names );
				$all_tag_names = array_merge( $all_tag_names, array_filter( $tag_names ) );
			}

			// Also check generic 'tags' field
			if ( ! empty( $arguments['tags'] ) ) {
				$tag_names = explode( ',', trim( $arguments['tags'], ' ,' ) );
				$tag_names = array_map( 'trim', $tag_names );
				$all_tag_names = array_merge( $all_tag_names, array_filter( $tag_names ) );
			}

			// Remove duplicates and apply tags
			if ( ! empty( $all_tag_names ) ) {
				$all_tag_names = array_unique( $all_tag_names );
				$tag_ids = $this->get_or_create_tag_ids( $all_tag_names );
				
				// Apply tags to the contact
				if ( ! empty( $tag_ids ) && ! empty( $contact_data['id'] ) ) {
					$api->add_tag_to_contact( $contact_data['id'], $tag_ids );
				}
			}

		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return true;
	}

	/**
	 * Get all tags from Ontraport (formatted for editor).
	 *
	 * @param bool $force Force refresh from API
	 *
	 * @return array
	 */
	public function get_tags( $force = false ) {
		// Create a unique cache key for tags
		$cache_key = 'ontraport_tags_' . $this->get_key();
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
			/** @var Thrive_Dash_Api_Ontraport $api */
			$api = $this->get_api();
			$api_tags = $api->get_tags();

			if ( ! empty( $api_tags ) && is_array( $api_tags ) ) {
				foreach ( $api_tags as $tag ) {
					if ( ! empty( $tag['tag_id'] ) && ! empty( $tag['tag_name'] ) ) {
						$tags[] = array(
							'id'       => (string) $tag['tag_id'],
							'text'     => $tag['tag_name'],
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
		$cache_key = 'ontraport_tags_' . $this->get_key();
		delete_transient( $cache_key );
		delete_transient( $cache_key . '_backup' );
	}

	/**
	 * Get or create tag IDs from tag names.
	 *
	 * @param array $tag_names Array of tag names
	 *
	 * @return array Array of tag IDs
	 */
	private function get_or_create_tag_ids( $tag_names ) {
		if ( empty( $tag_names ) || ! is_array( $tag_names ) ) {
			return array();
		}

		$tag_ids = array();

		try {
			// Get existing tags
			$existing_tags = $this->get_tags( false );

			/** @var Thrive_Dash_Api_Ontraport $api */
			$api = $this->get_api();

			foreach ( $tag_names as $tag_name ) {
				$tag_name = trim( $tag_name );
				if ( empty( $tag_name ) ) {
					continue;
				}

				// Check if tag already exists
				$found = false;
				foreach ( $existing_tags as $existing_tag ) {
					if ( strcasecmp( $existing_tag['text'], $tag_name ) === 0 ) {
						$tag_ids[] = $existing_tag['id'];
						$found = true;
						break;
					}
				}

				// If tag doesn't exist, create it
				if ( ! $found ) {
					$new_tag = $api->create_tag( $tag_name );

					if ( ! empty( $new_tag['tag_id'] ) ) {
						$tag_ids[] = $new_tag['tag_id'];
						
						// Clear cache to include the new tag
						$this->clearTagsCache();
					}
				}
			}
		} catch ( Exception $e ) {
			// Silent fail - tags will be created on next attempt
		}

		return $tag_ids;
	}

	/**
	 * Whether the current integration can create tags via API.
	 *
	 * @return bool
	 */
	public function can_create_tags_via_api() {
		return true;
	}

	/**
	 * Create tags if they don't exist (called from TCB editor on Apply)
	 *
	 * @param array $params
	 * @return array
	 */
	public function _create_tags_if_needed( $params ) {
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
				'success'       => true,
				'message'       => __( 'No tags to create', 'thrive-dash' ),
				'tags_created'  => 0
			);
		}

		try {
			$tags_created = 0;
			$existing_tags = $this->get_tags( false );

			/** @var Thrive_Dash_Api_Ontraport $api */
			$api = $this->get_api();

			foreach ( $tag_names as $tag_name ) {
				$tag_name = trim( $tag_name );
				if ( empty( $tag_name ) ) {
					continue;
				}

				// Check if tag already exists
				$found = false;
				foreach ( $existing_tags as $existing_tag ) {
					if ( strcasecmp( $existing_tag['text'], $tag_name ) === 0 ) {
						$found = true;
						break;
					}
				}

				// If tag doesn't exist, create it
				if ( ! $found ) {
					$new_tag = $api->create_tag( $tag_name );

					if ( ! empty( $new_tag['tag_id'] ) ) {
						$tags_created++;
					}
				}
			}

			// Clear cache to include newly created tags
			if ( $tags_created > 0 ) {
				$this->clearTagsCache();
			}

			return array(
				'success'       => true,
				'message'       => sprintf( __( 'Created %d new tag(s)', 'thrive-dash' ), $tags_created ),
				'tags_created'  => $tags_created
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
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

	public static function get_email_merge_tag() {
		return '[Email]';
	}


}

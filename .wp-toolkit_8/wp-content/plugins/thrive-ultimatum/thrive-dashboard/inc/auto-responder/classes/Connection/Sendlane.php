<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Sendlane extends Thrive_Dash_List_Connection_Abstract {
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
		return 'SendLane';
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
	public function can_create_tags_via_api() {
		return true;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'sendlane' );
	}

	/**
	 * @return mixed|Thrive_Dash_List_Connection_Abstract
	 */
	public function read_credentials() {

		$connection = $this->post( 'connection', array() );

		if ( empty( $connection['api_url'] ) || empty( $connection['api_key'] ) || empty( $connection['hash_key'] ) ) {
			return $this->error( __( 'All fields are required!', 'thrive-dash' ) );
		}

		$this->set_credentials( $connection );
		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( __( 'Could not connect to SendLane using the provided details', 'thrive-dash' ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'SendLane connected successfully', 'thrive-dash' ) );
	}

	/**
	 * @return bool
	 */
	public function test_connection() {

		return is_array( $this->_get_lists() );
	}

	/**
	 * @return mixed|Thrive_Dash_Api_Sendlane
	 * @throws Thrive_Dash_Api_Sendlane_Exception
	 */
	protected function get_api_instance() {
		$api_url  = $this->param( 'api_url' );
		$api_key  = $this->param( 'api_key' );
		$hash_key = $this->param( 'hash_key' );

		return new Thrive_Dash_Api_Sendlane( $api_key, $hash_key, $api_url );
	}

	/**
	 * @return array|bool
	 */
	protected function _get_lists() {
		/** @var Thrive_Dash_Api_Sendlane $api */
		$api    = $this->get_api();
		$result = $api->call( 'lists' );

		$api->setConnectionStatus( $result['status'] );

		/**
		 * Invalid connection
		 */
		if ( ! isset( $result['data'] ) || ! is_array( $result['data'] ) ) {
			return false;
		}

		/**
		 * Valid connection but no lists found
		 */
		if ( isset( $result['data']['info'] ) ) {
			return array();
		}

		/**
		 * Add id and name fields for each list
		 */
		foreach ( $result['data'] as $key => $list ) {
			$result['data'][ $key ]['id']   = $list['list_id'];
			$result['data'][ $key ]['name'] = $list['list_name'];
		}

		return $result['data'];
	}

	/**
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return mixed|string
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		$name_array = array();
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
			$name_array = array(
				'first_name' => $first_name,
				'last_name'  => $last_name,
			);
		}

		/** @var Thrive_Dash_Api_Sendlane $api */
		$api  = $this->get_api();
		$args = array(
			'list_id' => $list_identifier,
			'email'   => $arguments['email'],
		);

		$args = array_merge( $args, $name_array );

		if ( isset( $arguments['sendlane_tags'] ) ) {
			$args['tag_names'] = trim( $arguments['sendlane_tags'] );
		}

		if ( isset( $arguments['phone'] ) ) {
			$args['phone'] = $arguments['phone'];
		}

		return $api->call( 'list-subscriber-add', $args );
	}

	/**
	 * Get all tags from SendLane with caching
	 *
	 * @param bool $force Force refresh from API
	 * @return array
	 */
	public function getTags( $force = false ) {
		// Create a unique cache key based on API credentials
		$credentials = $this->get_credentials();
		$cache_key   = 'sendlane_tags_' . md5( serialize( $credentials ) );
		$cached_tags = false;

		// Try to get cached tags if not force refresh
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
			/** @var Thrive_Dash_Api_Sendlane $api */
			$api    = $this->get_api();
			$result = $api->call( 'tags', [ 'limit' => PHP_INT_MAX ] );

			// Process the API response
			if ( isset( $result['data'] ) && is_array( $result['data'] ) ) {
				foreach ( $result['data'] as $tag ) {
					if ( isset( $tag['tag_id'] ) && isset( $tag['tag_name'] ) ) {
						$tags[ $tag['tag_id'] ] = $tag['tag_name'];
					}
				}
			}

			// Cache the tags for 15 minutes
			if ( ! empty( $tags ) ) {
				asort( $tags );
				set_transient( $cache_key, $tags, 15 * MINUTE_IN_SECONDS );
			}
		} catch ( Exception $e ) {
			// If API call fails, try to use backup cache
			$expired_cache = get_transient( $cache_key . '_backup' );
			if ( false !== $expired_cache && is_array( $expired_cache ) ) {
				asort( $expired_cache );
				return $expired_cache;
			}
		}

		// Store a backup cache that doesn't expire for fallback
		if ( ! empty( $tags ) ) {
			asort( $tags );
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
		$cache_key   = 'sendlane_tags_' . md5( serialize( $credentials ) );

		delete_transient( $cache_key );
		delete_transient( $cache_key . '_backup' );
	}

	/**
	 * Create a single tag via Sendlane API
	 * 
	 * Sendlane API Response Format:
	 * Success: ['status' => 200, 'data' => ['success' => 'Tag added successfully']]
	 * Note: Sendlane does NOT return the tag_id in the creation response
	 *
	 * @param string $tag_name Tag name to create
	 * @return array|false API response or false on failure
	 */
	protected function create_tag( $tag_name ) {
		try {
			/** @var Thrive_Dash_Api_Sendlane $api */
			$api = $this->get_api();
			
			// Call Sendlane API to create tag
			// Endpoint: 'tag-create', Parameter: 'name'
			$result = $api->call( 'tag-create', array(
				'name' => $tag_name
			) );
			
			return $result;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Create tags if they don't exist (called from TCB editor on Apply)
	 * Creates tags directly via Sendlane API
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

			// Create tags via Sendlane API
			$created_tags = array();
			$failed_tags = array();
			
			foreach ( $new_tag_names as $tag_name ) {
				$result = $this->create_tag( $tag_name );
				
				// Check if tag was created successfully
				// Sendlane API returns status 200 with success message, but doesn't return tag_id in creation response
				if ( $result && isset( $result['status'] ) && $result['status'] === 200 ) {
					// Tag created successfully
					$created_tags[] = array(
						'id' => null, // Sendlane doesn't return tag_id in creation response
						'text' => $tag_name
					);
				} else {
					$failed_tags[] = $tag_name;
				}
			}

			// Clear cache so new tags appear immediately
			$this->clearTagsCache();

			$message = sprintf(
				_n( '%d tag created successfully', '%d tags created successfully', count( $created_tags ), 'thrive-dash' ),
				count( $created_tags )
			);
			
			if ( ! empty( $failed_tags ) ) {
				$message .= ' ' . sprintf(
					_n( '(%d tag failed)', '(%d tags failed)', count( $failed_tags ), 'thrive-dash' ),
					count( $failed_tags )
				);
			}

			return array(
				'success' => true,
				'message' => $message,
				'tags_created' => count( $created_tags ),
				'tags' => $created_tags,
				'failed_tags' => $failed_tags
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage()
			);
		}
	}

	/**
	 * Get extra settings including tags
	 *
	 * @param array $params
	 * @param bool  $force
	 *
	 * @return array
	 */
	public function get_extra_settings( $params = array(), $force = false ) {
		$params['tags'] = $this->getTags( $force );
		
		if ( ! is_array( $params['tags'] ) ) {
			$params['tags'] = array();
		}

		return $params;
	}

	/**
	 * Render extra editor settings for SendLane
	 *
	 * @param array $params
	 */
	public function render_extra_editor_settings( $params = array() ) {
		$params['tags'] = $this->getTags();
		
		if ( ! is_array( $params['tags'] ) ) {
			$params['tags'] = array();
		}
		
		$this->output_controls_html( 'sendlane/tags', $params );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return 'VAR_EMAIL';
	}

	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields', 'tag_input' ) );
	}
}

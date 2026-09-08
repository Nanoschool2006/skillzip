<?php /** @noinspection ALL */

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_ActiveCampaign extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * Cache for field types to avoid repeated API calls per instance
	 *
	 * @var array|null
	 */
	protected $field_types_cache = null;

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
		return 'ActiveCampaign';
	}

	/**
	 * @return bool
	 */
	public function has_tags() {

		return true;
	}

	public function has_custom_fields() {
		return true;
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'activecampaign' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		$api_url = ! empty( $_POST['connection']['api_url'] ) ? sanitize_text_field( $_POST['connection']['api_url'] ) : '';
		$api_key = ! empty( $_POST['connection']['api_key'] ) ? sanitize_text_field( $_POST['connection']['api_key'] ) : '';

		if ( empty( $api_key ) || empty( $api_url ) || empty( $_POST['connection'] ) ) {
			return $this->error( __( 'Both API URL and API Key fields are required', 'thrive-dash' ) );
		}

		$this->set_credentials( compact( 'api_url', 'api_key' ) );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( sprintf( __( 'Could not connect to ActiveCampaign using the provided details. Response was: <strong>%s</strong>', 'thrive-dash' ), $result ) );
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		/**
		 * Fetch all custom fields on connect so that we have them all prepared
		 * - TAr doesn't need to get them from API
		 */
		$this->get_api_custom_fields( array(), true, true );

		return $this->success( __( 'ActiveCampaign connected successfully', 'thrive-dash' ) );
	}

	/**
	 * test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		/** @var Thrive_Dash_Api_ActiveCampaign $api */
		$api = $this->get_api();

		try {
			$api->call( 'account_view', array() );

			return true;
		} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {
			return $e->getMessage();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * instantiate the API code required for this connection
	 *
	 * @return mixed
	 */
	protected function get_api_instance() {
		$api_url = $this->param( 'api_url' );
		$api_key = $this->param( 'api_key' );

		return new Thrive_Dash_Api_ActiveCampaign( $api_url, $api_key );
	}

	/**
	 * get all Subscriber Lists from this API service
	 *
	 * @return array|bool for error
	 */
	protected function _get_lists() {
		try {
			$raw   = $this->get_api()->getLists();
			$lists = array();

			foreach ( $raw as $list ) {
				$lists [] = array(
					'id'   => $list['id'],
					'name' => $list['name'],
				);
			}

			return $lists;

		} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {

			$this->_error = $e->getMessage();

			return false;

		} catch ( Exception $e ) {

			$this->_error = $e->getMessage();

			return false;
		}

	}

	/**
	 * get all Subscriber Forms from this API service
	 *
	 * @return array|bool for error
	 */
	protected function _get_forms() {
		try {
			$raw   = $this->get_api()->get_forms();
			$forms = array();

			$lists = $this->get_lists();
			foreach ( $lists as $list ) {
				$forms[ $list['id'] ][0] = array(
					'id'   => 0,
					'name' => __( 'none', 'thrive-dash' ),
				);
			}

			foreach ( $raw as $form ) {
				foreach ( $form['lists'] as $list_id ) {
					if ( empty( $forms[ $list_id ] ) ) {
						$forms[ $list_id ] = array();
					}
					/**
					 * for some reason, I've seen an instance where forms were duplicated (2 or more of the same form were displayed in the list)
					 */
					$forms[ $list_id ][ $form['id'] ] = array(
						'id'   => $form['id'],
						'name' => $form['name'],
					);
				}
			}

			return $forms;

		} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {

			$this->_error = $e->getMessage();

			return false;

		} catch ( Exception $e ) {

			$this->_error = $e->getMessage();

			return false;
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
		$api     = $this->get_api();
		$contact = $api->call( 'contact_view_email', array( 'email' => $email ) );

		if ( isset( $contact['result_code'] ) && $contact['result_code'] == 1 ) {

			$body   = array( 'id' => $contact['id'] );
			$result = $api->call( 'contact_delete', $body, array() );

			return isset( $result['result_code'] ) && $result['result_code'] == 1;
		}

		return true;

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

		/** @var Thrive_Dash_Api_ActiveCampaign $api */
		$api        = $this->get_api();
		$name_array = array();
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
			$name_array = array(
				'firstname' => $first_name,
				'lastName'  => $last_name,
			);
		}


		// Get contact
		try {
			$contact = $api->call( 'contact_view_email', array( 'email' => $arguments['email'] ) );
		} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {
			return $e->getMessage();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		$update = false;
		if ( isset( $contact['result_code'] ) && $contact['result_code'] == 1 ) {
			foreach ( $contact['lists'] as $list ) {
				if ( $list['listid'] == $list_identifier ) {
					$update = true;
				}
			}
		}

		// Prepared args for passing to subscribe/update methods
		$prepared_args = array(
			'email'            => ! empty( $arguments['email'] ) ? sanitize_email( $arguments['email'] ) : '',
			'phone'            => empty( $arguments['phone'] ) ? '' : sanitize_text_field( $arguments['phone'] ),
			'form_id'          => empty( $arguments['activecampaign_form'] ) ? 0 : sanitize_text_field( $arguments['activecampaign_form'] ),
			'organizationName' => '',
			'tags'             => ! empty( $arguments['activecampaign_tags'] ) ? trim( $arguments['activecampaign_tags'], ',' ) : '',
			'ip'               => null,
		);
		$prepared_args = array_merge( $prepared_args, $name_array );
		// Add or update subscriber
		try {

			/**
			 * Try to add/update contact on a single api call so linkted automation will be properly triggered
			 */
			if ( ! empty( $arguments['tve_mapping'] ) ) {
				$prepared_args['custom_fields'] = $this->buildMappedCustomFields( $arguments );
			} else if ( ! empty( $arguments['automator_custom_fields'] ) ) {
				$prepared_args['custom_fields'] = $arguments['automator_custom_fields'];
			}

			if ( isset( $contact['result_code'] ) && ( empty( $contact['result_code'] ) || false === $update ) ) {
				$api->add_subscriber( $list_identifier, $prepared_args );
			} else {
				// Sanitize contact data to remove invalid datetime field values before updating
				$prepared_args['contact'] = $this->sanitize_contact_data( $contact );
				$api->updateSubscriber( $list_identifier, $prepared_args );
			}

			$return = true;
		} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {
			$return = $e->getMessage();
		} catch ( Exception $e ) {
			$return = $e->getMessage();
		}

		/**
		 * Add/update action failed so we try again by doing two separate requests
		 * one for add/update contact and another one for updating custom fields
		 */
		if ( true !== $return ) {
			try {

				if ( isset( $contact['result_code'] ) && ( empty( $contact['result_code'] ) || false === $update ) ) {
					$api->add_subscriber( $list_identifier, $prepared_args );
				} else {
					// Sanitize contact data to remove invalid datetime field values before updating
					$prepared_args['contact'] = $this->sanitize_contact_data( $contact );
					$api->updateSubscriber( $list_identifier, $prepared_args );
				}

				$return = true;
			} catch ( Thrive_Dash_Api_ActiveCampaign_Exception $e ) {
				$return = $e->getMessage();
			} catch ( Exception $e ) {
				$return = $e->getMessage();
			}

			// Update custom fields
			// Make another call to update custom mapped fields in order not to break the subscription call,
			// if custom data doesn't pass API custom fields validation
			if ( true === $return && ! empty( $arguments['tve_mapping'] ) ) {
				unset( $prepared_args['tags'] );
				$this->updateCustomFields( $list_identifier, $arguments, $prepared_args );
			}
		}

		return $return;
	}

	/**
	 * Update custom fields
	 *
	 * @param string|int $list_identifier
	 * @param array      $arguments     form data
	 * @param array      $prepared_args prepared array for subscription
	 *
	 * @return bool|string
	 */
	public function updateCustomFields( $list_identifier, $arguments, $prepared_args ) {

		if ( ! $list_identifier || empty( $arguments ) || empty( $prepared_args ) ) {
			return false;
		}

		/** @var Thrive_Dash_Api_ActiveCampaign $api */
		$api = $this->get_api();
		try {

			// Refresh the contact data for mapping custom fields
			$contact_data = $api->call( 'contact_view_email', array( 'email' => sanitize_email( $arguments['email'] ) ) );

			// Sanitize contact data to remove invalid datetime field values before updating
			$prepared_args['contact'] = $this->sanitize_contact_data( $contact_data );

			// Build mapped fields array
			$prepared_args['custom_fields'] = $this->buildMappedCustomFields( $arguments );

			$api->updateSubscriber( $list_identifier, $prepared_args );

			$return = true;
		} catch ( Exception $e ) {
			// Log api errors
			$this->api_log_error( $list_identifier, $prepared_args, __METHOD__ . ': ' . $e->getMessage() );
			$return = $e->getMessage();
		}

		return $return;
	}

	/**
	 * Get all tags from ActiveCampaign
	 *
	 * @param bool $force Force refresh from API
	 * @return array
	 */
	public function getTags( $force = false ) {
		// Create cache key
		$credentials = $this->get_credentials();
		$cache_key   = 'activecampaign_tags_' . md5( serialize( $credentials ) );
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
			/** @var Thrive_Dash_Api_ActiveCampaign $api */
			$api = $this->get_api();

			// ActiveCampaign API v3 endpoint for tags
			$response = $api->call( 'tags_list' );

			if ( empty( $response ) || ! is_array( $response ) ) {
				return $tags; // Return empty array if response is empty or not an array.
			}

			foreach ( $response as $tag ) {
				if ( ! empty( $tag['name'] ) ) {
					$tags[ $tag['id'] ] = $tag['name'];
				}
			}

			// Cache the tags for 15 minutes
			if ( ! empty( $tags ) ) {
				set_transient( $cache_key, $tags, 15 * MINUTE_IN_SECONDS );
			}
		} catch ( Exception $e ) {
			// If API call fails, return empty array
		}

		return $tags;
	}

	/**
	 * output any (possible) extra editor settings for this API
	 *
	 * @param array $params allow various different calls to this method
	 * @param bool  $force  force refresh from API
	 *
	 * @return array
	 */
	public function get_extra_settings( $params = array(), $force = false ) {
		$params['forms'] = $this->_get_forms();
		if ( ! is_array( $params['forms'] ) ) {
			$params['forms'] = array();
		}

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
		$params['forms'] = $this->_get_forms();
		if ( ! is_array( $params['forms'] ) ) {
			$params['forms'] = array();
		}
		$this->output_controls_html( 'activecampaign/forms-list', $params );
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '%EMAIL%';
	}

	/**
	 * @param      $params
	 * @param bool $force
	 * @param bool $get_all
	 *
	 * @return array|mixed
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {

		return $this->get_all_custom_fields( $force );
	}

	/**
	 * @param (bool) $force
	 *
	 * @return array|mixed
	 */
	public function get_all_custom_fields( $force ) {

		$custom_data = array();

		// Serve from cache if exists and requested
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		// Needed custom fields type
		$allowed_types = array(
			'text',
			'url',
			'number',
			'hidden',
		);

		// Build custom fields for every list
		$custom_fields = $this->get_api()->getCustomFields();

		if ( is_array( $custom_fields ) ) {
			foreach ( $custom_fields as $field ) {
				if ( ! empty( $field['type'] ) && in_array( $field['type'], $allowed_types, true ) && 1 === (int) $field['visible'] ) {
					$custom_data[] = $this->normalize_custom_field( $field );
				}
			}
		}

		$this->_save_custom_fields( $custom_data );

		return $custom_data;
	}

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {

		$field = (array) $field;

		return array(
			'id'    => ! empty( $field['id'] ) ? $field['id'] : '',
			'name'  => ! empty( $field['perstag'] ) ? $field['perstag'] : '',
			'type'  => $field['type'],
			'label' => ! empty( $field['title'] ) ? $field['title'] : '',
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

		if ( is_array( $form_data ) ) {

			$mapped_fields = $this->get_mapped_field_ids();

			foreach ( $mapped_fields as $mapped_field_name ) {

				// Extract an array with all custom fields (siblings) names from form data
				// {ex: [mapping_url_0, .. mapping_url_n] / [mapping_text_0, .. mapping_text_n]}
				$cf_form_fields = preg_grep( "#^{$mapped_field_name}#i", array_keys( $form_data ) );

				// Matched "form data" for current allowed name

				if ( ! empty( $cf_form_fields ) && is_array( $cf_form_fields ) ) {

					// Pull form allowed data, sanitize it and build the custom fields array
					foreach ( $cf_form_fields as $cf_form_name ) {

						if ( empty( $form_data[ $cf_form_name ][ $this->_key ] ) ) {
							continue;
						}

						$mapped_api_id = $form_data[ $cf_form_name ][ $this->_key ];

						$cf_form_name = str_replace( '[]', '', $cf_form_name );
						if ( ! empty( $args[ $cf_form_name ] ) ) {
							$args[ $cf_form_name ]                     = $this->process_field( $args[ $cf_form_name ] );
							$mapped_data["field[{$mapped_api_id}, 0]"] = sanitize_text_field( $args[ $cf_form_name ] );
						}
					}
				}
			}
		}

		return $mapped_data;
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
				$mapped_data["field[{$pair['key']}, 0]"] = $value;
			}
		}

		return $mapped_data;
	}

	/**
	 * get relevant data from webhook trigger
	 *
	 * @param $request WP_REST_Request
	 *
	 * @return array
	 */
	public function get_webhook_data( $request ) {
		$contact = $request->get_param( 'contact' );

		return array( 'email' => empty( $contact['email'] ) ? '' : $contact['email'] );
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
			/** @var Thrive_Dash_Api_ActiveCampaign $api */
			$api     = $this->get_api();
			$list_id = ! empty( $extra['list_identifier'] ) ? $extra['list_identifier'] : null;
			$args    = array(
				'email' => $email,
			);

			if ( ! empty( $extra['name'] ) ) {
				$args['name'] = $extra['name'];
			}

			$this->add_subscriber( $list_id, $args );

			$args['contact']       = $api->call( 'contact_view_email', array( 'email' => $email ) );
			$args['custom_fields'] = $this->prepare_custom_fields_for_api( $custom_fields );

			$api->updateSubscriber( $list_id, $args );

			return $args['contact']['id'];

		} catch ( Exception $e ) {
			return false;
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
	protected function prepare_custom_fields_for_api( $custom_fields = array(), $list_identifier = null ) {

		$prepared_fields = array();

		foreach ( $custom_fields as $key => $custom_field ) {
			if ( $custom_field ) {
				$prepared_fields["field[{$key}], 0"] = sanitize_text_field( $custom_field );
			}
		}

		return $prepared_fields;
	}


	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list' => array( 'form_list' ), 'api_fields' => array(), 'tag_input' => array() ) );
	}

	public function get_custom_fields_by_list( $list = null ) {
		return $this->get_available_custom_fields();
	}

	public function has_forms() {
		return true;
	}

	/**
	 * Sanitize contact data to remove invalid datetime field values
	 * This prevents API errors when updating subscribers with existing invalid datetime values
	 *
	 * @param array $contact The contact data from ActiveCampaign API
	 *
	 * @return array Sanitized contact data
	 */
	protected function sanitize_contact_data( $contact ) {
		if ( ! is_array( $contact ) || empty( $contact['fields'] ) || ! is_array( $contact['fields'] ) ) {
			return $contact;
		}

		// Get all field types to identify datetime fields
		$field_types = $this->get_all_field_types();

		// Sanitize fields array
		foreach ( $contact['fields'] as $field_id => $field_data ) {
			if ( ! is_array( $field_data ) ) {
				continue;
			}

			$field_id = absint( $field_id );

			if ( ! $this->is_datetime_field( $field_id, $field_data, $field_types ) ) {
				continue;
			}

			$this->sanitize_datetime_field( $contact, $field_id, $field_data );
		}

		return $contact;
	}

	/**
	 * Check if a field is a datetime field type
	 *
	 * @param int   $field_id    The field ID
	 * @param array $field_data  The field data array
	 * @param array $field_types Array of field types by ID
	 *
	 * @return bool True if datetime field, false otherwise
	 */
	protected function is_datetime_field( $field_id, $field_data, $field_types ) {
		$field_type = isset( $field_types[ $field_id ] ) ? sanitize_text_field( $field_types[ $field_id ] ) : '';

		if ( empty( $field_type ) && ! empty( $field_data['type'] ) ) {
			$field_type = sanitize_text_field( $field_data['type'] );
		}

		return 'datetime' === $field_type;
	}

	/**
	 * Sanitize a datetime field value in contact data
	 *
	 * @param array $contact    The contact data array (passed by reference)
	 * @param int   $field_id    The field ID
	 * @param array $field_data  The field data array
	 *
	 * @return void
	 */
	protected function sanitize_datetime_field( &$contact, $field_id, $field_data ) {
		$field_value = isset( $field_data['val'] ) ? $field_data['val'] : '';

		// If the value is empty, null, or invalid, remove the field entirely
		// ActiveCampaign API doesn't accept empty strings or null for datetime fields
		if ( empty( $field_value ) ) {
			unset( $contact['fields'][ $field_id ] );

			return;
		}

		// Try to format the existing value to ISO 8601 if it's not already
		$formatted_value = $this->format_datetime_for_api( $field_value );

		if ( ! empty( $formatted_value ) ) {
			$contact['fields'][ $field_id ]['val'] = $formatted_value;
		} else {
			// If we can't format it, remove the field to avoid API errors
			unset( $contact['fields'][ $field_id ] );
		}
	}

	/**
	 * Get all custom fields including datetime types for type checking
	 * This is used internally to check field types, not for display
	 *
	 * @return array Array of field ID => field type
	 */
	protected function get_all_field_types() {
		if ( null !== $this->field_types_cache ) {
			return $this->field_types_cache;
		}

		$this->field_types_cache = array();

		try {
			// Get all custom fields including datetime types
			$custom_fields = $this->get_api()->getCustomFields();

			if ( ! is_array( $custom_fields ) || empty( $custom_fields ) ) {
				return $this->field_types_cache;
			}

			foreach ( $custom_fields as $field ) {
				if ( empty( $field['id'] ) || empty( $field['type'] ) ) {
					continue;
				}

				$field_id   = absint( $field['id'] );
				$field_type = sanitize_text_field( $field['type'] );

				$this->field_types_cache[ $field_id ] = $field_type;
			}
		} catch ( Exception $e ) {
			// If we can't get field types, return empty array
			// This will cause datetime formatting to be skipped, which is safer than failing
		}

		return $this->field_types_cache;
	}

	/**
	 * Format datetime value to ISO 8601 format for ActiveCampaign API
	 *
	 * @param string|mixed $datetime_value The datetime value to format
	 *
	 * @return string ISO 8601 formatted datetime string or empty string if invalid
	 */
	protected function format_datetime_for_api( $datetime_value ) {
		if ( empty( $datetime_value ) ) {
			return '';
		}

		// Sanitize input
		$datetime_value = sanitize_text_field( $datetime_value );

		if ( empty( $datetime_value ) ) {
			return '';
		}

		// If already in ISO 8601 format, return as is
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})?$/', $datetime_value ) ) {
			return $datetime_value;
		}

		// Try to parse the datetime value
		try {
			$datetime = $this->parse_datetime_value( $datetime_value );

			if ( false !== $datetime ) {
				// Format to RFC 3339 (ISO 8601 compatible) with timezone
				return $datetime->format( 'c' );
			}
		} catch ( Exception $e ) {
			// If parsing fails, return empty string to skip this field
			return '';
		}

		// If we can't parse it, return empty string to skip this field
		return '';
	}

	/**
	 * Parse datetime value from various formats
	 *
	 * @param string $datetime_value The datetime value to parse
	 *
	 * @return DateTime|false DateTime object on success, false on failure
	 */
	protected function parse_datetime_value( $datetime_value ) {
		// Try common date formats
		$formats = array(
			'Y-m-d H:i:s',
			'Y-m-d H:i',
			'Y-m-d',
			'm/d/Y H:i:s',
			'm/d/Y H:i',
			'm/d/Y',
			'd/m/Y H:i:s',
			'd/m/Y H:i',
			'd/m/Y',
			'YmdHis',
			'Ymd',
		);

		foreach ( $formats as $format ) {
			$datetime = DateTime::createFromFormat( $format, $datetime_value );

			if ( false !== $datetime ) {
				return $datetime;
			}
		}

		// Fallback to strtotime if format parsing fails
		$timestamp = strtotime( $datetime_value );

		if ( false !== $timestamp ) {
			$datetime = new DateTime();
			$datetime->setTimestamp( $timestamp );

			return $datetime;
		}

		return false;
	}
}

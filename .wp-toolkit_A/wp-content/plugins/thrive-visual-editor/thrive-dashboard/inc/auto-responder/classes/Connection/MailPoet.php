<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * MailPoet autoresponder integration for Thrive Architect Lead Generation
 */
class Thrive_Dash_List_Connection_MailPoet extends Thrive_Dash_List_Connection_Abstract {
	/**
	 * Key used for mapping custom fields
	 *
	 * @var string
	 */
	protected $_key = '_field';

	/**
	 * Constructor - initialize custom fields mapping
	 *
	 * @param string $key API connection key
	 */
	public function __construct( $key ) {
		parent::__construct( $key );
		$this->set_custom_fields_mapping();
	}

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
		return 'MailPoet';
	}

	/**
	 * @return bool
	 */
	public function has_tags() {
		return false; // MailPoet uses segments/lists instead of tags
	}

	/**
	 * @return bool
	 */
	public function has_optin() {
		return true;
	}

	/**
	 * @return bool
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * Check whether or not the MailPoet plugin is installed
	 *
	 * @return bool
	 */
	public function pluginInstalled() {
		return class_exists( '\MailPoet\API\API' );
	}

	/**
	 * Output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'mailpoet' );
	}

	/**
	 * Just save the key in the database
	 *
	 * @return mixed|void
	 */
	public function read_credentials() {
		if ( ! $this->pluginInstalled() ) {
			return $this->error( __( 'MailPoet plugin must be installed and activated.', 'thrive-dash' ) );
		}

		$connection_data = $this->post( 'connection', array() );
		$connection_data['connected'] = true;
		$this->set_credentials( $connection_data );

		$result = $this->test_connection();

		if ( $result !== true ) {
			return $this->error( '<strong>' . $result . '</strong>' );
		}

		/**
		 * Finally, save the connection details.
		 */
		$this->save();

		return true;
	}

	/**
	 * Test if a connection can be made to the service using the stored credentials
	 *
	 * @return bool|string true for success or error message for failure
	 */
	public function test_connection() {
		if ( ! $this->pluginInstalled() ) {
			return __( 'MailPoet plugin must be installed and activated.', 'thrive-dash' );
		}

		try {
			$api = \MailPoet\API\API::MP( 'v1' );
			// Test by getting subscriber fields - this will fail if API is not working
			$api->getSubscriberFields();
		} catch ( Exception $exception ) {
			return $exception->getMessage();
		}

		return true;
	}

	/**
	 * Add a contact to a list
	 *
	 * @param mixed $list_identifier
	 * @param array $arguments
	 *
	 * @return bool|string true for success, error message string for failure
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		// Early validation - check plugin installation
		if ( ! $this->pluginInstalled() ) {
			return __( 'MailPoet plugin is not installed / activated', 'thrive-dash' );
		}

		// Early validation - check required email
		if ( empty( $arguments['email'] ) ) {
			return __( 'Email address is required', 'thrive-dash' );
		}

		$email = sanitize_email( $arguments['email'] );

		// Validate sanitized email to avoid passing empty/invalid values to MailPoet
		if ( empty( $email ) || ! is_email( $email ) ) {
			return __( 'Please enter a valid email address', 'thrive-dash' );
		}
		$prepared_args = array();

		// Handle name
		if ( ! empty( $arguments['name'] ) ) {
			list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
			$prepared_args['first_name'] = sanitize_text_field( $first_name );
			$prepared_args['last_name'] = sanitize_text_field( $last_name );
		}

		// Handle mapped fields from TAR
		// Decode tve_mapping if it's base64 encoded (follows established pattern from other integrations)
		if ( ! empty( $arguments['tve_mapping'] ) && ! is_array( $arguments['tve_mapping'] ) ) {
			if ( tve_dash_is_bas64_encoded( $arguments['tve_mapping'] ) && is_serialized( base64_decode( $arguments['tve_mapping'] ) ) ) {
				$decoded = thrive_safe_unserialize( base64_decode( $arguments['tve_mapping'] ) );
				$arguments['tve_mapping'] = is_array( $decoded ) ? $decoded : array();
			} else {
				$arguments['tve_mapping'] = array();
			}
		}

		if ( ! empty( $arguments['tve_mapping'] ) && is_array( $arguments['tve_mapping'] ) ) {
			$mapped_defaults = $this->buildMappedDefaultFields( $arguments );
			$custom_fields = $this->buildMappedCustomFields( $arguments );

			if ( ! empty( $mapped_defaults ) ) {
				$prepared_args = array_merge( $prepared_args, $mapped_defaults );
			}

			if ( ! empty( $custom_fields ) ) {
				$prepared_args = array_merge( $prepared_args, $custom_fields );
			}
		}

		// Determine desired status based on optin choice
		$desired_status = 'subscribed'; // Default to single optin
		$use_double_optin = false;
		if ( isset( $arguments['mailpoet_optin'] ) && 'd' === $arguments['mailpoet_optin'] ) {
			$desired_status = 'unconfirmed';
			$use_double_optin = true;
		}

		try {
			$api = \MailPoet\API\API::MP( 'v1' );

			// Check if subscriber already exists
			$existing_subscriber = null;
			try {
				$existing_subscriber = $api->getSubscriber( $email );
			} catch ( Exception $e ) {
				// Subscriber doesn't exist, which is fine - we'll create them
				$existing_subscriber = null;
			}

			if ( ! empty( $existing_subscriber ) && isset( $existing_subscriber['id'] ) ) {
				// Subscriber exists - update their data and subscribe to list

				// Prepare update data - exclude 'email' and 'status' to preserve existing status
				// This prevents downgrading confirmed subscribers to unconfirmed
				$update_data = $prepared_args;

				// Update subscriber data (custom fields and name fields only)
				// Note: MailPoet ignores first_name/last_name/email updates for WP-linked subscribers
				if ( ! empty( $update_data ) ) {
					$api->updateSubscriber( $existing_subscriber['id'], $update_data );
				}

				// Subscribe to the list with confirmation email option
				// send_confirmation_email: only sends if subscriber is unconfirmed and signup confirmation is enabled in MailPoet
				$subscribe_options = array(
					'send_confirmation_email'    => $use_double_optin,
					'schedule_welcome_email'     => true,
					'skip_subscriber_notification' => false,
				);
				$api->subscribeToLists( $existing_subscriber['id'], array( $list_identifier ), $subscribe_options );
			} else {
				// Subscriber doesn't exist - create new subscriber
				$data = array_merge(
					array(
						'email'  => $email,
						'status' => $desired_status,
					),
					$prepared_args
				);

				// Options for new subscriber - confirmation email handled automatically based on status
				$add_options = array(
					'send_confirmation_email'    => $use_double_optin,
					'schedule_welcome_email'     => true,
					'skip_subscriber_notification' => false,
				);

				// Add new subscriber and subscribe to list
				$api->addSubscriber( $data, array( $list_identifier ), $add_options );
			}
		} catch ( Exception $exception ) {
			// Return actual error message for debugging
			return $exception->getMessage();
		}

		return true;
	}

	/**
	 * Build mapped custom fields array (only actual custom fields)
	 *
	 * @param array $args Form arguments
	 *
	 * @return array Mapped custom fields data
	 */
	public function buildMappedCustomFields( $args ) {
		if ( empty( $args['tve_mapping'] ) || ! is_array( $args['tve_mapping'] ) ) {
			return array();
		}

		$custom_fields = array();
		$available_fields = $this->get_all_custom_fields( false );

		if ( empty( $available_fields ) || ! is_array( $available_fields ) ) {
			return array();
		}

		// Create lookup array for available custom fields
		$custom_field_ids = array();
		$default_field_ids = $this->get_default_field_ids();
		foreach ( $available_fields as $field ) {
			if ( ! empty( $field['id'] ) && ! in_array( $field['id'], $default_field_ids, true ) ) {
				$custom_field_ids[] = $field['id'];
			}
		}

		foreach ( $args['tve_mapping'] as $form_field => $mapping_data ) {
			// Extract the actual field name from mapping data
			// Mapping structure is ['mailpoet' => 'field_name', '_field' => 'mapping_text']
			$api_field = is_array( $mapping_data ) && isset( $mapping_data['mailpoet'] ) ? $mapping_data['mailpoet'] : $mapping_data;

			// Validate api_field is a non-empty string and form field has a value
			if ( ! is_string( $api_field ) || empty( $api_field ) || empty( $args[ $form_field ] ) ) {
				continue;
			}

			// Only process actual custom fields (not default subscriber fields)
			if ( in_array( $api_field, $custom_field_ids, true ) ) {
				$custom_fields[ $api_field ] = sanitize_text_field( $args[ $form_field ] );
			}
		}

		return $custom_fields;
	}

	/**
	 * Build mapped default fields array (first_name, last_name, etc.)
	 *
	 * @param array $args Form arguments
	 *
	 * @return array Mapped default fields data
	 */
	public function buildMappedDefaultFields( $args ) {
		if ( empty( $args['tve_mapping'] ) || ! is_array( $args['tve_mapping'] ) ) {
			return array();
		}

		$default_fields = array();
		$allowed_defaults = $this->get_default_field_ids();

		foreach ( $args['tve_mapping'] as $form_field => $mapping_data ) {
			// Extract the actual field name from mapping data
			// Mapping structure is ['mailpoet' => 'field_name', '_field' => 'mapping_text']
			$api_field = is_array( $mapping_data ) && isset( $mapping_data['mailpoet'] ) ? $mapping_data['mailpoet'] : $mapping_data;

			// Validate api_field is a non-empty string and form field has a value
			if ( ! is_string( $api_field ) || empty( $api_field ) || empty( $args[ $form_field ] ) ) {
				continue;
			}

			// Only process default subscriber fields
			if ( in_array( $api_field, $allowed_defaults, true ) ) {
				$default_fields[ $api_field ] = sanitize_text_field( $args[ $form_field ] );
			}
		}

		return $default_fields;
	}

	/**
	 * Get default MailPoet subscriber field IDs
	 *
	 * @return array
	 */
	protected function get_default_field_ids() {
		return array( 'first_name', 'last_name' );
	}

	/**
	 * Get all available fields (default + custom)
	 *
	 * @param bool $force Force refresh from API
	 *
	 * @return array
	 */
	public function get_all_custom_fields( $force ) {
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		if ( ! $this->pluginInstalled() ) {
			return array();
		}

		// Default MailPoet subscriber fields
		$fields = array(
			array( 'id' => 'first_name', 'name' => 'First Name', 'type' => 'text', 'label' => 'First Name' ),
			array( 'id' => 'last_name', 'name' => 'Last Name', 'type' => 'text', 'label' => 'Last Name' ),
		);

		try {
			$api = \MailPoet\API\API::MP( 'v1' );
			$subscriber_fields = $api->getSubscriberFields();

			if ( is_array( $subscriber_fields ) ) {
				foreach ( $subscriber_fields as $field ) {
					// Skip default fields (email, first_name, last_name)
					if ( ! empty( $field['id'] ) && ! in_array( $field['id'], array( 'email', 'first_name', 'last_name' ), true ) ) {
						$fields[] = array(
							'id'    => sanitize_text_field( $field['id'] ),
							'name'  => ! empty( $field['name'] ) ? sanitize_text_field( $field['name'] ) : $field['id'],
							'type'  => ! empty( $field['type'] ) ? sanitize_text_field( $field['type'] ) : 'text',
							'label' => ! empty( $field['name'] ) ? sanitize_text_field( $field['name'] ) : $field['id'],
						);
					}
				}
			}
		} catch ( Exception $e ) {
			// Log error but continue with default fields
			error_log( 'MailPoet API error: ' . $e->getMessage() );
		}

		$this->_save_custom_fields( $fields );
		return $fields;
	}

	/**
	 * Get MailPoet lists/segments for TAR dropdown
	 *
	 * @param bool $use_cache Whether to use cached results
	 *
	 * @return array
	 */
	public function get_lists( $use_cache = true ) {
		if ( ! $this->pluginInstalled() ) {
			return array();
		}

		// Check cache first if requested
		if ( $use_cache ) {
			$cached_lists = get_transient( 'tve_mailpoet_lists_' . md5( $this->_key ) );
			if ( false !== $cached_lists ) {
				return $cached_lists;
			}
		}

		try {
			$api = \MailPoet\API\API::MP( 'v1' );
			$segments = $api->getLists();

			$lists = array();
			if ( is_array( $segments ) ) {
				foreach ( $segments as $segment ) {
					if ( ! empty( $segment['id'] ) && ! empty( $segment['name'] ) ) {
						$lists[] = array(
							'id'   => sanitize_text_field( $segment['id'] ),
							'name' => sanitize_text_field( $segment['name'] ),
						);
					}
				}
			}

			// Cache the results for 30 minutes
			if ( ! empty( $lists ) ) {
				set_transient( 'tve_mailpoet_lists_' . md5( $this->_key ), $lists, 30 * MINUTE_IN_SECONDS );
			}

			return $lists;
		} catch ( Exception $exception ) {
			error_log( 'MailPoet get lists error: ' . $exception->getMessage() );
			return array();
		}
	}

	/**
	 * Normalize custom field data
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	protected function normalize_custom_field( $field ) {
		$field = (array) $field;

		return array(
			'id'    => ! empty( $field['id'] ) ? sanitize_text_field( $field['id'] ) : '',
			'name'  => ! empty( $field['name'] ) ? sanitize_text_field( $field['name'] ) : '',
			'type'  => ! empty( $field['type'] ) ? sanitize_text_field( $field['type'] ) : 'text',
			'label' => ! empty( $field['name'] ) ? sanitize_text_field( $field['name'] ) : '',
		);
	}

	/**
	 * Get available custom fields for this api connection
	 *
	 * @param null $list_id
	 *
	 * @return array
	 */
	public function get_available_custom_fields( $list_id = null ) {
		return $this->get_all_custom_fields( true );
	}

	/**
	 * Get custom fields for TAR form builder (merges default + mapped fields)
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function get_custom_fields( $params = array() ) {
		return array_merge( parent::get_custom_fields(), $this->_mapped_custom_fields );
	}

	/**
	 * Get API custom fields for TAR (returns all available MailPoet fields)
	 *
	 * @param array $params
	 * @param bool  $force
	 * @param bool  $get_all
	 *
	 * @return array
	 */
	public function get_api_custom_fields( $params, $force = false, $get_all = false ) {
		return $this->get_all_custom_fields( $force );
	}

	/**
	 * Get API instance (not needed for MailPoet since we use their API directly)
	 *
	 * @return null
	 */
	protected function get_api_instance() {
		// No API instance needed here - we use MailPoet\API\API directly
		return null;
	}

	/**
	 * Get all subscriber lists from MailPoet API service
	 *
	 * @return array|bool
	 */
	protected function _get_lists() {
		if ( ! $this->pluginInstalled() ) {
			$this->_error = __( 'MailPoet plugin must be installed and activated.', 'thrive-dash' );
			return false;
		}

		try {
			$api = \MailPoet\API\API::MP( 'v1' );
			$segments = $api->getLists();

		$lists = array();
			if ( is_array( $segments ) ) {
				foreach ( $segments as $segment ) {
					if ( ! empty( $segment['id'] ) && ! empty( $segment['name'] ) ) {
						$lists[] = array(
							'id'   => sanitize_text_field( $segment['id'] ),
							'name' => sanitize_text_field( $segment['name'] ),
						);
					}
				}
			}

			return $lists;
		} catch ( Exception $exception ) {
			$this->_error = sprintf( __( 'MailPoet API Error: %s', 'thrive-dash' ), $exception->getMessage() );
			return false;
			}
	}

	/**
	 * Return the connection email merge tag
	 *
	 * @return String
	 */
	public static function get_email_merge_tag() {
		return '[subscriber:email]';
	}
}
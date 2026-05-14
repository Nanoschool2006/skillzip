<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class Thrive_Dash_Api_MailRelayV1
 * Wrapper for another version MailRelay's API
 * - {user}.ipzmarketing.com/api/v1
 */
class Thrive_Dash_Api_MailRelayV1 {

	/**
	 * @var string
	 */
	protected $_base_url;

	/**
	 * @var string
	 */
	protected $_api_key;

	/**
	 * @var string
	 */
	protected $uri = '/api/v1';

	/**
	 * Thrive_Dash_Api_MailRelayV1 constructor.
	 *
	 * @param string $base_url
	 * @param string $api_key
	 */
	public function __construct( $base_url, $api_key ) {

		$this->_base_url = untrailingslashit( $base_url );
		$this->_api_key  = $api_key;
	}

	/**
	 * Makes a request to API
	 *
	 * @param string $route
	 * @param string $method
	 * @param array  $data
	 * @param array  $headers
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	protected function _request( $route, $method = 'get', $data = array(), $headers = array() ) {

		$data = array_filter( $data );

		$method = strtoupper( $method );
		$body   = json_encode( $data );
		$route  = '/' . trim( $route, '/' );
		$url    = untrailingslashit( $this->_base_url . $this->uri ) . untrailingslashit( $route );

		switch ( $method ) {
			case 'GET':
				$fn   = 'tve_dash_api_remote_get';
				$url  = add_query_arg( $data, $url );
				$body = '';
				break;
			default:
				$fn = 'tve_dash_api_remote_post';
				break;
		}

		$args = array(
			'body'      => $body,
			'timeout'   => 15,
			'headers'   => array_merge( array(
				'Content-Type' => 'application/json',
				'X-AUTH-TOKEN' => $this->_api_key,
			), $headers ),
			'method'    => $method,
			'sslverify' => false,
		);

		$response = $fn( $url, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Get lists/groups from MailRelay
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function get_list() {

		$lists = $this->_request( '/groups', 'get',
			array(
				'page'     => 0,
				'per_page' => 1000,
			)
		);

		return $lists;
	}
	/**
	 * Adds a subscriber to a group into MailRelay
	 * - does a check for existence before sending it through API
	 *
	 * @param $list_id
	 * @param $args
	 *
	 * @return array with the new or updated subscriber
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function add_subscriber( $list_id, $args ) {

		// Validate essential parameters to prevent fatal errors
		if ( empty( $list_id ) || ! is_numeric( $list_id ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Invalid list id', 400 );
		}

		if ( empty( $args ) || ! is_array( $args ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Invalid arguments provided', 400 );
		}

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Valid email address required', 400 );
		}

		$list_id = (int) $list_id;

		// Remove custom fields from main subscriber creation - they'll be handled separately like tags
		if ( isset( $args['customFields'] ) ) {
			unset( $args['customFields'] );
		}

		// Validate final args before API call
		if ( empty( $args['email'] ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Email missing from final args', 400 );
		}

		$args = array_merge(
			array(
				'group_ids' => array( $list_id ),
				'status'    => 'active',
			),
			$args
		);

		return $this->_request( '/subscribers/sync', 'post', $args );
	}

	/**
	 * Makes an API requests for all custom_fields and loops through them
	 * if there exists any with a $tag_name then returns it
	 *
	 * @param string $tag_name
	 *
	 * @return null|array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function get_custom_field( $tag_name ) {

		// Validate tag_name to prevent fatal errors
		if ( empty( $tag_name ) || ! is_string( $tag_name ) ) {
			return null;
		}

		$tag_name     = (string) $tag_name;
		$custom_field = null;
		
		try {
			$fields = $this->_request( '/custom_fields' );
			
			// Validate API response
			if ( empty( $fields ) || ! is_array( $fields ) ) {
				return null;
			}

			foreach ( $fields as $field ) {
				// Validate field structure
				if ( ! is_array( $field ) || empty( $field['tag_name'] ) ) {
					continue;
				}
				
				if ( $field['tag_name'] === $tag_name ) {
					$custom_field = $field;
					break;
				}
			}
		} catch ( Exception $e ) {
			return null;
		}

		return $custom_field;
	}

	/**
	 * Makes a POST request to API with a custom_field data
	 *
	 * @param array $field
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function create_custom_field( $field ) {

		// Validate field data to prevent fatal errors
		if ( empty( $field ) || ! is_array( $field ) ) {
			return null;
		}

		if ( empty( $field['tag_name'] ) || ! is_string( $field['tag_name'] ) ) {
			return null;
		}

		$field = array_merge( array(
			'required' => false,
		), $field );

		try {
			$result = $this->_request( '/custom_fields', 'post', $field );
			
			// Validate API response
			if ( empty( $result ) || ! is_array( $result ) ) {
				return null;
			}
			
			return $result;
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Get all custom fields from MailRelay V1 API
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function get_all_custom_fields() {
		return $this->_request( '/custom_fields' );
	}

	/**
	 * Update subscriber custom fields
	 *
	 * @param string $email
	 * @param array $custom_fields
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function update_subscriber_custom_fields( $email, $custom_fields ) {
		// Validate parameters to prevent fatal errors
		if ( empty( $email ) || ! is_email( $email ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Valid email address required', 400 );
		}

		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( 'Valid custom fields array required', 400 );
		}

		$args = array(
			'email' => $email,
			'custom_fields' => $custom_fields,
		);
		
		return $this->_request( '/subscribers/sync', 'post', $args );
	}

	/**
	 * Apply custom field to subscriber (EXACT COPY of apply_tag logic)
	 *
	 * @param string $email
	 * @param string $field_tag_name
	 * @param string $field_label
	 * @param string $field_value
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function apply_custom_field( $email, $field_tag_name, $field_label, $field_value ) {
		// Validate parameters to prevent fatal errors
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		if ( empty( $field_tag_name ) || ! is_string( $field_tag_name ) ) {
			return false;
		}

		if ( $field_value === '' || $field_value === null ) {
			return false;
		}

		try {
			// Create/get custom field (EXACT same logic as tags)
			$custom_field = $this->get_custom_field( $field_tag_name );
			if ( empty( $custom_field ) ) {
				$custom_field = $this->create_custom_field( array(
					'label'      => $field_label,
					'tag_name'   => $field_tag_name,
					'field_type' => 'text',
				) );
			}
			
			if ( is_array( $custom_field ) && ! empty( $custom_field['id'] ) && is_numeric( $custom_field['id'] ) ) {
				$custom_fields = array( $custom_field['id'] => sanitize_text_field( $field_value ) );
				$this->update_subscriber_custom_fields( $email, $custom_fields );
			}
			
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Apply tag to subscriber 
	 *
	 * @param string $email
	 * @param string $tag
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function apply_tag( $email, $tag ) {
		// Validate parameters to prevent fatal errors
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		if ( empty( $tag ) || ! is_string( $tag ) ) {
			return false;
		}

		try {
			// Create/get tags custom field
			$tags_field = $this->get_custom_field( 'mailrelay_tags' );
			if ( empty( $tags_field ) ) {
				$tags_field = $this->create_custom_field( array(
					'label'      => 'Tags',
					'tag_name'   => 'mailrelay_tags',
					'field_type' => 'text',
				) );
			}
			
			if ( is_array( $tags_field ) && ! empty( $tags_field['id'] ) && is_numeric( $tags_field['id'] ) ) {
				$custom_fields = array( $tags_field['id'] => sanitize_text_field( $tag ) );
				$this->update_subscriber_custom_fields( $email, $custom_fields );
			}
			
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Processes the response got from API
	 *
	 * @param WP_Error|array $response
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	protected function handle_response( $response ) {

		if ( $response instanceof WP_Error ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( sprintf( __( 'Failed connecting: %s', 'thrive-dash' ), $response->get_error_message() ) );
		}

		if ( isset( $response['response']['code'] ) ) {
			switch ( $response['response']['code'] ) {
				case 200:
					$result = json_decode( $response['body'], true );

					return $result;
					break;
				case 400:
					throw new Thrive_Dash_Api_MailRelay_Exception( __( 'Missing a required parameter or calling invalid method', 'thrive-dash' ) );
					break;
				case 401:
					throw new Thrive_Dash_Api_MailRelay_Exception( __( 'Invalid API key provided!', 'thrive-dash' ) );
					break;
				case 404:
					throw new Thrive_Dash_Api_MailRelay_Exception( __( "Can't find requested items", 'thrive-dash' ) );
					break;
			}
		}

		return json_decode( $response['body'], true );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function sendEmail( $args ) {

		$senders = $this->get_senders();

		if ( ! is_array( $senders ) || empty( $senders ) ) {
			throw new Thrive_Dash_Api_MailRelay_Exception( __( 'No senders available', 'thrive-dash' ), 400 );
		}

		$email_args = array(
			'from'      => array(
				'name'  => $senders[0]['name'],
				'email' => $senders[0]['email'],
			),
			'to'        => array(
				array(
					'name'  => $args['emails'][0]['name'],
					'email' => $args['emails'][0]['email'],
				),
			),
			'subject'   => $args['subject'],
			'html_part' => $args['html'],
			'smtp_tags' => array( 'string' ),
		);

		return $this->_request( '/send_emails', 'post', $email_args );
	}

	/**
	 * Get a list of senders
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_MailRelay_Exception
	 */
	public function get_senders() {

		return $this->_request( '/senders' );
	}
}

/**
 * Class Thrive_Dash_Api_MailRelay_Exception
 * Exception class for MailRelay API errors (V1)
 */
if ( ! class_exists( 'Thrive_Dash_Api_MailRelay_Exception' ) ) {
	class Thrive_Dash_Api_MailRelay_Exception extends Exception {
		
	}
}

<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}


class Thrive_Dash_Api_ConstantContactV3_Service {

	protected $client_id;

	protected $client_secret;

	protected $access_type = 'offline';

	protected $access_token = '';

	const AUTH_URI  = 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
	const TOKEN_URI = 'https://authz.constantcontact.com/oauth2/default/v1/token';

	const BASE_URI = 'https://api.cc.email/v3/';

	public function __construct( $client_id, $client_secret, $access_token ) {
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->access_token  = $access_token;
	}

	public function get_authorize_url( $scopes = array( 'account_read', 'offline_access', 'contact_data' ) ) {
		return add_query_arg(
			array(
				'scope'                  => $this->prepare_scopes( $scopes ),
				'state'                  => 'connection_constant_contact_v3',
				'access_type'            => $this->access_type,
				'include_granted_scopes' => 'true',
				'response_type'          => 'code',
				'redirect_uri'           => $this->get_redirect_uri(),
				'client_id'              => $this->client_id,
				/* always send `consent` in the prompt parameter in order to always get back a refresh_token */
				'prompt'                 => 'consent',
			),
			static::AUTH_URI
		);
	}

	/**
	 * https://v3.developer.constantcontact.com/api_guide/server_flow.html#step-8-refresh-the-access-token
	 *
	 * @param string $code
	 *
	 * @return mixed
	 */
	public function get_access_token( $code ) {
		return $this->post( static::TOKEN_URI, array(
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'code'          => $code,
			'grant_type'    => 'authorization_code',
			'redirect_uri'  => $this->get_redirect_uri(),
		), array(), false );
	}

	/**
	 * Refresh access token
	 *
	 * https://v3.developer.constantcontact.com/api_guide/server_flow.html#step-8-refresh-the-access-token
	 *
	 * @param string $refresh_token
	 *
	 * @return array
	 */
	public function refresh_access_token( $refresh_token ) {
		$data = $this->post( static::TOKEN_URI, array(
			'client_id'     => $this->client_id,
			'client_secret' => $this->client_secret,
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		), array(), false );
		/* store the new access token in the instance */
		$this->access_token = $data['access_token'];

		return $data;
	}

	protected function prepare_scopes( $scopes ) {
		if ( ! is_array( $scopes ) ) {
			$scopes = array( $scopes );
		}

		return implode( '+', $scopes );
	}

	/**
	 * @param $response
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	protected function parse_response( $response ) {

		$response = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $response ) || ! empty( $response['error'] ) ) {
			$this->throw_error( $response );
		}

		return $response;
	}

	/**
	 * @param $response
	 *
	 * @return string
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	protected function throw_error( $response ) {
		if ( ! isset( $response['error'] ) ) {
			$message = 'Unknownerror. Raw response was: ' . print_r( $response, true );
		} elseif ( is_string( $response['error'] ) ) {
			$description = isset( $response['error_description'] ) ? ' (' . $response['error_description'] . ')' : '';
			$message     = $response['error'] . $description;
		} elseif ( is_array( $response['error'] ) ) {
			$message = isset( $response['error']['message'] ) ? $response['error']['message'] : '';
		} else {
			$message = 'Unknown error. Raw response was: ' . print_r( $response, true );
		}

		throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'ConstantContactV3 API Error: ' . $message );
	}

	public function get_redirect_uri() {
		return admin_url( 'admin.php?page=tve_dash_api_connect' );
	}

	public function get( $uri, $query = array(), $auth = true ) {
		$params = array(
			'body'    => $query,
			'headers' => $auth ? $this->auth_headers() : array(),
		);

		return $this->parse_response( tve_dash_api_remote_get( $uri, $params ) );
	}

	public function post( $uri, $data, $headers = array(), $auth = true, $args = array() ) {
		$args     = wp_parse_args( $args, array(
			'body'    => $data,
			'headers' => $headers + ( $auth ? $this->auth_headers() : array() ),
		) );
		$response = tve_dash_api_remote_post( $uri, $args );

		return $this->parse_response( $response );
	}

	protected function auth_headers() {
		return array(
			'Authorization' => 'Bearer ' . $this->access_token,
		);
	}

	/** API calls */

	/**
	 * get the static contact lists
	 * HubSpot is letting us to work only with static contact lists
	 * "Please note that you cannot manually add (via this API call) contacts to dynamic lists - they can only be updated by the contacts app."
	 *
	 * @return mixed
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function get_account_details($params = array()) {
		$result = $this->get(
			static::BASE_URI . 'account/summary',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		if( isset( $result['contact_email'] ) ) {
			return $result;
		} else {
			// throw error.
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to get account details' );
			return false;
		}
	}


	/**
	 * Retrieve all the contact lists, including all information associated with each.
	 *
	 * @see https://v3.developer.constantcontact.com/api_guide/lists_get_all.html
	 *
	 * @return array
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	public function getLists($params = array()) {
		$result = $this->get(
			static::BASE_URI. 'contact_lists?include_count=true&include_membership_count=all',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		if( isset( $result['lists'] ) ) {
			$lists = array();
			foreach ( $result['lists'] as $list_item ) {
				$lists [] = array(
					'id' => $list_item['list_id'],
					'name' => $list_item['name'],
				);
			}
			return $lists;
		} else {
			// throw error.
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to get account details' );
			return false;
		}
	}

	/**
	 * Add/Update subscriber to the specific mailing list.
	 *
	 * @see https://v3.developer.constantcontact.com/api_guide/contacts_create_or_update.html
	 *
	 * @return array
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	public function addSubscriber( $params = array() ) {
		// convert $params to json
		$params = wp_json_encode( $params );

		$result = $this->post(
			static::BASE_URI. 'contacts',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		if( isset( $result['contact_id'] ) ) {
			return true;
		} else {
			// throw error.
			$error_message = isset( $result['error_message'] ) ? $result['error_message'] : '';
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to add subscriber details.' . $error_message );
			return false;
		}
	}


	/**
	 * Get all fields supported.
	 *
	 * @return void
	 */
	public function getAllFields(){
		$custom_data   = array();
		$allowed_types = array(
			'text',
			'url',
			'number',
			'hidden',
			'date'
		);

		try {
			$custom_fields = array(
				array(
					'id'    => 'first_name',
					'name'  => 'first_name',
					'type'  => 'text',
					'label' => 'First Name',
				),
				array(
					'id'    => 'last_name',
					'name'  => 'last_name',
					'type'  => 'text',
					'label' => 'Last Name',
				),
				array(
					'id'    => 'job_title',
					'name'  => 'job_title',
					'type'  => 'text',
					'label' => 'Job Title',
				),
				array(
					'id'    => 'company_name',
					'name'  => 'company_name',
					'type'  => 'text',
					'label' => 'Company Name',
				),
				array(
					'id'    => 'anniversary',
					'name'  => 'anniversary',
					'type'  => 'text',
					'label' => 'Anniversary',
				),
				array(
					'id'    => 'birthday_month',
					'name'  => 'birthday_month',
					'type'  => 'text',
					'label' => 'Birthday Month (Number: 1-12)',
				),
				array(
					'id'    => 'birthday_day',
					'name'  => 'birthday_day',
					'type'  => 'text',
					'label' => 'Birthday Date (Number: 1-31)',
				),
			);

			// add custom fields here.
			$api_custom_fields = $this->getApiCustomFields();
			$custom_fields     = array_merge( $custom_fields, $api_custom_fields );

			if ( is_array( $custom_fields ) ) {
				foreach ( $custom_fields as $field ) {
					if ( ! empty( $field['type'] ) && in_array( $field['type'], $allowed_types, true ) ) {
						$custom_data[] = $field;
					}
				}
			}
		} catch ( Exception $e ) {
		}

		return $custom_data;
	}


	/**
	 * Get custom fields for the specific list.
	 *
	 * @param string $list_identifier
	 *
	 * @return array
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	public function getApiCustomFields() {
		$params = array();

		$result = $this->get(
			static::BASE_URI. 'contact_custom_fields',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		if( isset( $result['custom_fields'] ) ) {
			$custom_fields = array();
			foreach ( $result['custom_fields'] as $field_item ) {
				$custom_fields [] = array(
					'id' => $field_item['custom_field_id'],
					'name' => $field_item['name'],
					'type' => 'string' === $field_item['type'] ? 'text' : $field_item['type'],
					'label' => $field_item['label'],
				);
			}
			return $custom_fields;
		} else {
			// throw error.
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to get custom fields.' );
			return false;
		}
	}


	// Get all existing tags - GET /contact_tags
	public function getAllTags() {
		$params = array();

		$result = $this->get(
			static::BASE_URI. 'contact_tags',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		return $result;
	}

	// Create new tag - POST /contact_tags
	public function createTag( $tag_data ) {
		// convert $params to json
		$params = wp_json_encode( $tag_data );

		$result = $this->post(
			static::BASE_URI. 'contact_tags',
			$params,
			array(
				'Content-type' => 'application/json',
			)
		);

		return $result;
	}
}

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
	 * Exchange authorization code for access token
	 *
	 * https://v3.developer.constantcontact.com/api_guide/server_flow.html#step-8-refresh-the-access-token
	 *
	 * @param string $code Authorization code from OAuth2 flow
	 *
	 * @return array Contains access_token, refresh_token, expires_in, and token_type
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception When authorization fails
	 */
	public function get_access_token( $code ) {
		if ( empty( $code ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Authorization code is required to get access token.' );
		}

		try {
			$data = $this->post( static::TOKEN_URI, array(
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'code'          => $code,
				'grant_type'    => 'authorization_code',
				'redirect_uri'  => $this->get_redirect_uri(),
			), array(), false );

			// Validate the response contains required fields
			if ( empty( $data['access_token'] ) || empty( $data['refresh_token'] ) ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Invalid response from authorization server. Missing access_token or refresh_token.' );
			}

			return $data;
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			// Provide more context for authorization failures
			if ( strpos( $e->getMessage(), 'invalid_grant' ) !== false ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception(
					'The authorization code is invalid or expired. Authorization codes can only be used once and expire quickly. Please try connecting again.',
					$e->getCode(),
					$e
				);
			}
			throw $e;
		}
	}

	/**
	 * Refresh access token
	 *
	 * https://v3.developer.constantcontact.com/api_guide/server_flow.html#step-8-refresh-the-access-token
	 *
	 * Note: Refresh tokens expire after 180 days according to Constant Contact documentation
	 *
	 * @param string $refresh_token The refresh token to use for getting a new access token
	 *
	 * @return array Contains new access_token, refresh_token, expires_in, and token_type
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception When refresh token is invalid, expired, or revoked
	 */
	public function refresh_access_token( $refresh_token ) {
		if ( empty( $refresh_token ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception(
				'Refresh token is required to refresh access token. Please reconnect your Constant Contact account.'
			);
		}

		try {
			$data = $this->post( static::TOKEN_URI, array(
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret,
				'refresh_token' => $refresh_token,
				'grant_type'    => 'refresh_token',
			), array(), false );

			// Validate the response contains required fields
			if ( empty( $data['access_token'] ) ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception(
					'Invalid response from authorization server. Missing access_token in refresh response.'
				);
			}

			/* store the new access token in the instance */
			$this->access_token = $data['access_token'];

			return $data;
		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			// Check if this is a refresh token expiration error
			if ( strpos( $e->getMessage(), 'invalid_grant' ) !== false ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception(
					'The refresh token is invalid or expired. Refresh tokens expire after 180 days of inactivity. Please reconnect your Constant Contact account.',
					$e->getCode(),
					$e
				);
			}
			// Re-throw the original exception
			throw $e;
		}
	}

	protected function prepare_scopes( $scopes ) {
		if ( ! is_array( $scopes ) ) {
			$scopes = array( $scopes );
		}

		return implode( '+', $scopes );
	}

	/**
	 * Parse API response and handle errors
	 *
	 * @param $response WP HTTP API response object
	 *
	 * @return array Parsed JSON response
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception When request fails or returns error
	 */
	protected function parse_response( $response ) {
		// Check for WordPress HTTP errors
		if ( is_wp_error( $response ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception(
				'HTTP request failed: ' . $response->get_error_message()
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$parsed_response = json_decode( $body, true );

		// Handle HTTP error status codes
		if ( $status_code >= 400 ) {
			// Handle rate limiting (429)
			if ( $status_code === 429 ) {
				$retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
				$message = 'Rate limit exceeded. ';
				if ( $retry_after ) {
					$message .= 'Please retry after ' . $retry_after . ' seconds.';
				} else {
					$message .= 'Please try again later.';
				}
				throw new Thrive_Dash_Api_ConstantContactV3_Exception( $message );
			}

			// Handle authentication errors (401)
			if ( $status_code === 401 ) {
				throw new Thrive_Dash_Api_ConstantContactV3_Exception(
					'Authentication failed. Your access token may be expired or invalid. Please reconnect your Constant Contact account.'
				);
			}

			// Handle conflict errors (409) - duplicate contacts
			if ( $status_code === 409 ) {
				// Include status code in message so addSubscriber can detect it
				$error_message = 'Conflict (409): ';
				if ( ! empty( $parsed_response['error'] ) ) {
					// Extract error message from response body
					if ( is_string( $parsed_response['error'] ) ) {
						$error_message .= $parsed_response['error'];
					} elseif ( is_array( $parsed_response['error'] ) && isset( $parsed_response['error']['message'] ) ) {
						$error_message .= $parsed_response['error']['message'];
					} else {
						$error_message .= 'Contact already exists';
					}
				} else {
					$error_message .= 'Contact already exists';
				}
				throw new Thrive_Dash_Api_ConstantContactV3_Exception( $error_message );
			}

			// For other errors, check if there's an error in the response body
			if ( ! empty( $parsed_response['error'] ) ) {
				$this->throw_error( $parsed_response );
			} else {
				// Include response body in error message for debugging
				$error_details = '';
				if ( ! empty( $body ) ) {
					$error_details = ' Response body: ' . substr( $body, 0, 500 );
				}
				throw new Thrive_Dash_Api_ConstantContactV3_Exception(
					'API request failed with status code ' . $status_code . $error_details
				);
			}
		}

		// Check for errors in successful responses (some APIs return 200 with error)
		if ( ! empty( $parsed_response['error'] ) ) {
			$this->throw_error( $parsed_response );
		}

		// Handle empty or invalid JSON responses
		if ( empty( $parsed_response ) && ! empty( $body ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception(
				'Invalid JSON response from API: ' . substr( $body, 0, 200 )
			);
		}

		return $parsed_response;
	}

	/**
	 * Parse and throw error with user-friendly messages
	 *
	 * @param $response
	 *
	 * @return string
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	protected function throw_error( $response ) {
		if ( ! isset( $response['error'] ) ) {
			$message = 'Unknown error. Raw response was: ' . print_r( $response, true );
		} elseif ( is_string( $response['error'] ) ) {
			$error_type = $response['error'];
			$description = isset( $response['error_description'] ) ? $response['error_description'] : '';

			// Provide user-friendly messages for common OAuth2 errors
			// Reference: https://developer.constantcontact.com/api_guide/auth_overview.html
			switch ( $error_type ) {
				case 'invalid_grant':
					$message = 'The refresh token is invalid or expired.';
					if ( $description ) {
						$message .= ' (' . $description . ')';
					}
					$message .= ' Refresh tokens expire after 180 days of inactivity. Please reconnect your Constant Contact account.';
					break;
				case 'invalid_token':
					$message = 'The access token is invalid or expired. Please reconnect your Constant Contact account.';
					break;
				case 'invalid_client':
					$message = 'The API credentials (client ID or secret) are invalid. Please verify your Constant Contact API settings.';
					break;
				case 'access_denied':
					$message = 'Access was denied. The user may have revoked access to your application.';
					break;
				case 'invalid_scope':
					$message = 'The requested permissions (scopes) are invalid or unsupported.';
					break;
				case 'unauthorized_client':
					$message = 'The client is not authorized to use this authorization method.';
					break;
				case 'server_error':
					$message = 'Constant Contact server error. Please try again later.';
					break;
				case 'temporarily_unavailable':
					$message = 'Constant Contact service is temporarily unavailable. Please try again later.';
					break;
				default:
					$message = $error_type;
					if ( $description ) {
						$message .= ' (' . $description . ')';
					}
					break;
			}
		} elseif ( is_array( $response['error'] ) ) {
			$message = isset( $response['error']['message'] ) ? $response['error']['message'] : 'Unknown error';
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

	public function put( $uri, $data, $headers = array(), $auth = true, $args = array() ) {
		$args     = wp_parse_args( $args, array(
			'body'    => $data,
			'headers' => $headers + ( $auth ? $this->auth_headers() : array() ),
			'method'  => 'PUT',
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
	 * Uses contacts endpoint which supports taggings. Handles duplicates via PUT update.
	 *
	 * @see https://v3.developer.constantcontact.com/api_guide/contacts_create.html
	 *
	 * @param array $params Subscriber data.
	 *
	 * @return bool True on success.
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	public function addSubscriber( $params = array() ) {
		// Validate required fields early
		if ( empty( $params['email_address']['address'] ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Email address is required.' );
		}

		if ( empty( $params['list_memberships'] ) || ! is_array( $params['list_memberships'] ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'At least one list membership is required.' );
		}

		// Ensure email_address has required structure for new contacts
		if ( ! isset( $params['email_address']['permission_to_send'] ) ) {
			$params['email_address']['permission_to_send'] = 'implicit';
		}

		// Ensure create_source and update_source are set
		if ( ! isset( $params['create_source'] ) ) {
			$params['create_source'] = 'Contact';
		}
		if ( ! isset( $params['update_source'] ) ) {
			$params['update_source'] = 'Contact';
		}

		// Store original params before JSON encoding (for update fallback)
		$original_params = $params;

		$json_params = wp_json_encode( $params );

		// Validate JSON encoding
		if ( false === $json_params ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to encode subscriber data as JSON.' );
		}

		try {
			$result = $this->post(
				static::BASE_URI . 'contacts',
				$json_params,
				array(
					'Content-Type' => 'application/json',
				)
			);

			// POST returns contact_id on successful creation
			if ( isset( $result['contact_id'] ) ) {
				return true;
			}

			// If we got here without exception, something unexpected happened
			$error_message = isset( $result['error_message'] ) ? $result['error_message'] : 'Unknown error';
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to add subscriber: ' . $error_message );

		} catch ( Thrive_Dash_Api_ConstantContactV3_Exception $e ) {
			// Check for 409 conflict (duplicate subscriber) - message starts with "Conflict (409):"
			if ( strpos( $e->getMessage(), 'Conflict (409):' ) === 0 ) {
				// Subscriber exists, update them instead
				return $this->updateExistingSubscriber( $original_params );
			}

			// Re-throw any other exceptions
			throw $e;
		}
	}

	/**
	 * Update existing subscriber when a duplicate is detected.
	 *
	 * @param array $params Subscriber data.
	 *
	 * @return bool True on success.
	 *
	 * @throws Thrive_Dash_Api_ConstantContactV3_Exception
	 */
	private function updateExistingSubscriber( $params ) {
		// Validate email early
		if ( empty( $params['email_address']['address'] ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Email address is required to update existing subscriber.' );
		}

		$email = sanitize_email( $params['email_address']['address'] );

		if ( ! is_email( $email ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Invalid email address format.' );
		}

		// Search for the contact by email
		$search_result = $this->get(
			static::BASE_URI . 'contacts',
			array( 'email' => $email )
		);

		// Validate search results structure
		if ( empty( $search_result['contacts'] ) || ! is_array( $search_result['contacts'] ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception(
				'API reported duplicate contact but search returned no results. Please try again.'
			);
		}

		// Get contact_id from first result
		if ( empty( $search_result['contacts'][0]['contact_id'] ) ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception(
				'API returned contact without a valid contact_id.'
			);
		}

		$contact_id = $search_result['contacts'][0]['contact_id'];

		// Remove permission_to_send for updates - CC API returns "Invalid state transition"
		// when trying to set permission_to_send on contacts already in Pending/Confirmed states
		unset( $params['email_address']['permission_to_send'] );

		// Remove create_source (not needed for updates)
		unset( $params['create_source'] );

		// Ensure update_source is set
		if ( ! isset( $params['update_source'] ) ) {
			$params['update_source'] = 'Contact';
		}

		// Update the contact via PUT
		$json_params = wp_json_encode( $params );

		// Validate JSON encoding
		if ( false === $json_params ) {
			throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to encode subscriber data as JSON.' );
		}

		$result = $this->put(
			static::BASE_URI . 'contacts/' . $contact_id,
			$json_params,
			array(
				'Content-Type' => 'application/json',
			)
		);

		// PUT returns updated contact with contact_id on success
		if ( isset( $result['contact_id'] ) ) {
			return true;
		}

		// If we got here without exception, something unexpected happened
		$error_message = isset( $result['error_message'] ) ? $result['error_message'] : 'Unknown error';
		throw new Thrive_Dash_Api_ConstantContactV3_Exception( 'Failed to update existing subscriber: ' . $error_message );
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

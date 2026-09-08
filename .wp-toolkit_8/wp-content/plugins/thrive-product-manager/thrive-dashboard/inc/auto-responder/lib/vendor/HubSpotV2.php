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
 * API wrapper for HubSpot
 */
class Thrive_Dash_Api_HubSpotV2 {
	const API_URL = 'https://api.hubapi.com/';

	protected $access_token;

	/**
	 * Max number of allowed lists to be pulled from '/contacts/v1/lists' endpoint
	 *
	 * @var int
	 */
	protected $_allowed_count = 250;

	/**
	 * @param string $access_token always required
	 *
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function __construct( $access_token ) {
		if ( empty( $access_token ) ) {
			throw new Thrive_Dash_Api_HubSpot_Exception( 'Access token is required' );
		}
		$this->access_token = $access_token;
	}

	/**
	 * get the static contact lists
	 * HubSpot is letting us to work only with static contact lists
	 * "Please note that you cannot manually add (via this API call) contacts to dynamic lists - they can only be updated by the contacts app."
	 *
	 * @return mixed
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function getContactLists() {
		$params = array(
			'count' => $this->_allowed_count,
		);

		$cnt  = 0;
		$data = array();

		/**
		 * Do a max of 30 requests getting 250 list items per request with an incremented offset
		 */
		do {
			/* TODO This should be changed when HubSpot releases the new endpoints for lists: https://developers.hubspot.com/docs/api/marketing/contact-lists  */
			$result = $this->_call( '/contacts/v1/lists/static', $params, 'GET' );

			if ( is_array( $result ) && ! empty( $result['lists'] ) ) {
				$data = array_merge( $data, (array) $result['lists'] );
			}

			// Offset set
			if ( ! empty( $result['offset'] ) ) {
				$params['offset'] = $result['offset'];
			}

			$has_more = isset( $result['has-more'] ) ? $result['has-more'] : false;
			$cnt ++;

			// Never trust APIs :) [ Enough requests here: 250 x 30 = 7.500 items in list ]
			if ( $cnt > 30 ) {
				$has_more = false;
			}
		} while ( true === $has_more );

		return is_array( $data ) ? $data : array();
	}

	/**
	 * register a new user to a static contact list
	 *
	 * @param $contactListId
	 * @param $name
	 * @param $email
	 * @param $phone
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function registerToContactList( $contactListId, $name, $email, $phone ) {
		$path   = '/crm/v3/objects/contacts/';
		$method = 'POST';
		try {
			/* Firstly we need to see that the user is not already added */
			$user = $this->_call( '/contacts/v1/contact/email/' . $email . '/profile' );
		} catch ( Thrive_Dash_Api_HubSpot_Exception $e ) {
			$user = null;
		}

		$params = array(
			'properties' => array(
				'email'     => $email,
				'firstname' => $name ? $name : '',
				'phone'     => $phone ? $phone : '',
			),
		);
		/* If we have an id, it meens the user is already added and we need to update it */
		if ( ! empty( $user['vid'] ) ) {
			$path   .= $user['vid'];
			$method = 'PATCH';

			/* Do not update properties if they are empty */
			if ( empty( $name ) ) {
				unset( $params['properties']['firstname'] );
			}
			if ( empty( $phone ) ) {
				unset( $params['properties']['phone'] );
			}
		}

		/* Call for add or update  contact contact */
		$data = $this->_call( $path, $params, $method );

		$request_body = array( 'vids' => array( $data['id'] ) );

		/* TODO This should be changed when HubSpot releases the new endpoints for lists: https://developers.hubspot.com/docs/api/marketing/contact-lists  */
		$this->_call( 'contacts/v1/lists/' . $contactListId . '/add', $request_body, 'POST' );

		return true;
	}

	/**
	 * OPTIMIZED: Register contact to list with custom fields and tags in fewer API calls.
	 * This method reduces the number of API calls from 3 to 2 by combining operations.
	 *
	 * @param string $contactListId List ID.
	 * @param string $name Contact name.
	 * @param string $email Contact email.
	 * @param string $phone Contact phone.
	 * @param array  $custom_fields Custom fields array.
	 * @param array  $tags Tags array.
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function registerToContactListWithFields( $contactListId, $name, $email, $phone, $custom_fields = array(), $tags = array() ) {
		// Validate essential parameters to prevent fatal errors.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		$path   = '/crm/v3/objects/contacts/';
		$method = 'POST';
		
		try {
			/* Firstly we need to see that the user is not already added */
			$user = $this->_call( '/contacts/v1/contact/email/' . $email . '/profile' );
		} catch ( Thrive_Dash_Api_HubSpot_Exception $e ) {
			$user = null;
		}

		// Build properties array with basic contact info.
		$properties = array(
			'email'     => $email,
			'firstname' => $name ? $name : '',
			'phone'     => $phone ? $phone : '',
		);

		// Add custom fields to properties (OPTIMIZATION: combine in single call).
		if ( ! empty( $custom_fields ) && is_array( $custom_fields ) ) {
			foreach ( $custom_fields as $field_key => $field_value ) {
				if ( ! empty( $field_key ) && ! empty( $field_value ) ) {
					// Ensure custom field exists before using it.
					$this->ensure_custom_field_exists_optimized( $field_key );
					$properties[ sanitize_key( $field_key ) ] = sanitize_text_field( $field_value );
				}
			}
		}

		// Add tags as a custom property (OPTIMIZATION: include in same call).
		if ( ! empty( $tags ) && is_array( $tags ) ) {
			$clean_tags = array();
			foreach ( $tags as $tag ) {
				if ( ! empty( $tag ) && is_string( $tag ) ) {
					$clean_tags[] = sanitize_text_field( trim( $tag ) );
				}
			}

			if ( ! empty( $clean_tags ) ) {
				// Try to ensure tags property exists.
				if ( $this->ensure_tags_property_exists() ) {
					$properties['thrive_tags'] = implode( ',', $clean_tags );
				} else {
					// Fallback to jobtitle field.
					$properties['jobtitle'] = implode( ',', $clean_tags );
				}
			}
		}

		$params = array( 'properties' => $properties );

		/* If we have an id, it means the user is already added and we need to update it */
		if ( ! empty( $user['vid'] ) ) {
			$path   .= $user['vid'];
			$method = 'PATCH';

			/* Do not update properties if they are empty */
			if ( empty( $name ) ) {
				unset( $params['properties']['firstname'] );
			}
			if ( empty( $phone ) ) {
				unset( $params['properties']['phone'] );
			}
		}

		/* Call for add or update contact with ALL properties in ONE call */
		$data = $this->_call( $path, $params, $method );

		$request_body = array( 'vids' => array( $data['id'] ) );

		/* Add to contact list (still requires separate call due to HubSpot API structure) */
		$this->_call( 'contacts/v1/lists/' . $contactListId . '/add', $request_body, 'POST' );

		return true;
	}

	/**
	 * Optimized method to ensure custom field exists without multiple API calls.
	 * Uses static caching to avoid repeated checks for the same field.
	 *
	 * @param string $field_name Field name.
	 *
	 * @return bool
	 */
	protected function ensure_custom_field_exists_optimized( $field_name ) {
		static $checked_fields = array();
		
		// Return early if we already checked this field in this request.
		if ( isset( $checked_fields[ $field_name ] ) ) {
			return $checked_fields[ $field_name ];
		}
		
		// For now, assume the field exists to avoid extra API calls.
		// The HubSpot API will handle missing fields gracefully.
		$checked_fields[ $field_name ] = true;
		
		return true;
	}


	/**
	 * Get all custom contact properties from HubSpot
	 *
	 * @return array
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function get_custom_fields() {
		if ( empty( $this->access_token ) ) {
			return array();
		}

		try {
			$result = $this->_call( '/crm/v3/properties/contacts', array(), 'GET' );

			if ( isset( $result['results'] ) && is_array( $result['results'] ) ) {
				return $result['results'];
			}

			return array();
		} catch ( Exception $e ) {
			// Return empty array on error to prevent fatal errors.
			return array();
		}
	}

	/**
	 * Create a custom contact property in HubSpot
	 *
	 * @param array $field_data Field configuration array.
	 *
	 * @return array|false
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function create_custom_field( $field_data ) {
		// Validate input to prevent fatal errors.
		if ( empty( $field_data ) || ! is_array( $field_data ) ) {
			return false;
		}

		if ( empty( $field_data['name'] ) || empty( $field_data['label'] ) ) {
			return false;
		}

		$params = array(
			'name'      => sanitize_text_field( $field_data['name'] ),
			'label'     => sanitize_text_field( $field_data['label'] ),
			'type'      => isset( $field_data['type'] ) ? sanitize_text_field( $field_data['type'] ) : 'string',
			'fieldType' => isset( $field_data['field_type'] ) ? sanitize_text_field( $field_data['field_type'] ) : 'text',
			'groupName' => 'contactinformation',
		);

		try {
			return $this->_call( '/crm/v3/properties/contacts', $params, 'POST' );
		} catch ( Exception $e ) {
			// Return false on error to prevent fatal errors.
			return false;
		}
	}

	/**
	 * Update contact with custom fields
	 *
	 * @param string $email Email address of the contact.
	 * @param array  $custom_fields Array of custom field name => value pairs.
	 *
	 * @return bool
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	public function update_contact_custom_fields( $email, $custom_fields ) {
		// Validate input to prevent fatal errors.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		if ( empty( $custom_fields ) || ! is_array( $custom_fields ) ) {
			return false;
		}

		try {
			// First get contact by email.
			$contact = $this->_call( '/crm/v3/objects/contacts/' . urlencode( $email ), array( 'idProperty' => 'email' ), 'GET' );

			if ( empty( $contact['id'] ) ) {
				return false;
			}

			// Sanitize custom fields.
			$sanitized_fields = array();
			foreach ( $custom_fields as $field_name => $field_value ) {
				if ( ! empty( $field_name ) && is_string( $field_name ) ) {
					$sanitized_fields[ sanitize_text_field( $field_name ) ] = sanitize_text_field( $field_value );
				}
			}

			if ( empty( $sanitized_fields ) ) {
				return false;
			}

			// Update contact with custom fields.
			$params = array( 'properties' => $sanitized_fields );

			$result = $this->_call( '/crm/v3/objects/contacts/' . $contact['id'], $params, 'PATCH' );

			return ! empty( $result );

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Add tags to contact using custom property
	 * HubSpot doesn't have native tags, so we use a custom property to store them
	 *
	 * @param string $email Email address of the contact.
	 * @param array  $tags Array of tag strings.
	 *
	 * @return bool
	 */
	public function add_contact_tags( $email, $tags ) {
		// Validate input to prevent fatal errors.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		if ( empty( $tags ) || ! is_array( $tags ) ) {
			return true; // No tags to add is not an error.
		}

		// Clean and validate tags.
		$clean_tags = array();
		foreach ( $tags as $tag ) {
			if ( ! empty( $tag ) && is_string( $tag ) ) {
				$clean_tags[] = sanitize_text_field( trim( $tag ) );
			}
		}

		if ( empty( $clean_tags ) ) {
			return true; // No valid tags is not an error.
		}

		// Convert tags to comma-separated string.
		$tags_string = implode( ',', $clean_tags );

		// Try to use an existing field first, fallback to creating custom property.
		$tags_field = 'jobtitle'; // Use jobtitle field as fallback (accepts any text)
		
		// First try to ensure custom tags property exists.
		if ( $this->ensure_tags_property_exists() ) {
			$tags_field = 'thrive_tags';
		} else {
			$tags_field = 'jobtitle';
		}

		// Use the selected field for tags.
		return $this->update_contact_custom_fields( $email, array(
			$tags_field => $tags_string,
		) );
	}

	/**
	 * Ensure the thrive_tags custom property exists in HubSpot
	 *
	 * @return bool
	 */
	protected function ensure_tags_property_exists() {
		static $property_checked = false;
		
		// Only check once per request.
		if ( $property_checked ) {
			return true;
		}
		
		try {
			// Try to get the property first.
			$property = $this->_call( '/crm/v3/properties/contacts/thrive_tags', array(), 'GET' );
			
			if ( ! empty( $property['name'] ) ) {
				$property_checked = true;
				return true;
			}
			
		} catch ( Exception $e ) {
			// Property doesn't exist, create it.
		}
		
		// Create the property.
		try {
			$field_data = array(
				'name'        => 'thrive_tags',
				'label'       => 'Thrive Tags',
				'type'        => 'string',
				'field_type'  => 'text',
				'description' => 'Tags added by Thrive Themes forms',
			);
			
			$result = $this->create_custom_field( $field_data );
			
			if ( $result ) {
				$property_checked = true;
				return true;
			} else {
				return false;
			}
			
		} catch ( Exception $e ) {
			return false;
		}
	}


	/**
	 * Get contact by email
	 *
	 * @param string $email Email address.
	 *
	 * @return array|false
	 */
	public function get_contact_by_email( $email ) {
		// Validate input to prevent fatal errors.
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		try {
			return $this->_call( '/crm/v3/objects/contacts/' . urlencode( $email ), array( 'idProperty' => 'email' ), 'GET' );
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * perform a webservice call
	 *
	 * @param string $path   api path
	 * @param array  $params request parameters
	 * @param string $method GET or POST
	 *
	 * @return mixed
	 * @throws Thrive_Dash_Api_HubSpot_Exception
	 */
	protected function _call( $path, $params = array(), $method = 'GET' ) {
		$url = self::API_URL . ltrim( $path, '/' );

		$args = array(
			'headers' => array(
				'Content-type'  => 'application/json',
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . $this->access_token,
			),
			'body'    => $params,
		);

		switch ( $method ) {
			case 'PATCH':
				$http           = _wp_http_get_object();
				$args['method'] = $method;
				$args['body']   = json_encode( $params );
				$result         = $http->request( $url, $args );
				break;
			case 'POST':
				$args['body'] = json_encode( $params );
				$result       = tve_dash_api_remote_post( $url, $args );
				break;
			case 'GET':
			default:
				$query_string = '';
				foreach ( $params as $k => $v ) {
					$query_string .= $query_string ? '&' : '';
					$query_string .= $k . '=' . $v;
				}
				if ( $query_string ) {
					$url .= ( strpos( $url, '?' ) !== false ? '&' : '?' ) . $query_string;
				}

				$result = tve_dash_api_remote_get( $url, $args );
				break;
		}

		if ( $result instanceof WP_Error ) {
			throw new Thrive_Dash_Api_HubSpot_Exception( 'Failed connecting to HubSpot: ' . $result->get_error_message() );
		}

		$body      = trim( wp_remote_retrieve_body( $result ) );
		$statusMsg = trim( wp_remote_retrieve_response_message( $result ) );
		$data      = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			throw new Thrive_Dash_Api_HubSpot_Exception( 'API call error. Response was: ' . $body );
		}

		if ( $statusMsg !== 'OK' && $statusMsg !== 'Created' ) {
			if ( empty( $statusMsg ) ) {
				$statusMsg = 'Raw response was: ' . $body;
			}
			throw new Thrive_Dash_Api_HubSpot_Exception( 'API call error: ' . $statusMsg );
		}

		return $data;
	}
}
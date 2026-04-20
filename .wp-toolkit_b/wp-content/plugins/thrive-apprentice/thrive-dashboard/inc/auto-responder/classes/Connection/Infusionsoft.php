<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Infusionsoft extends Thrive_Dash_List_Connection_Abstract {

	/**
	 * Maps Keap/Infusionsoft DataType IDs to normalized field types.
	 *
	 * Keap uses numeric IDs to identify field types in their API. This mapping allows us to
	 * translate those IDs into standardized types that our form builder understands.
	 *
	 * Multiple DataType IDs map to the same type because Keap has different internal implementations
	 * for similar field types (e.g., checkbox types 5, 17, and 23 all behave as checkboxes but may
	 * have different GroupId handling or storage mechanisms in Keap's database).
	 *
	 * @var array Maps DataType ID => normalized field type
	 */
	protected $_custom_fields = array(
		1  => 'text',           // Text
		2  => 'textarea',       // Textarea
		3  => 'dropdown',       // Dropdown
		4  => 'radio',          // Radio buttons
		5  => 'checkbox',       // Checkbox (standard)
		6  => 'date',           // Date
		7  => 'datetime',       // Date/Time
		8  => 'phone',          // Phone
		9  => 'email',          // Email
		10 => 'currency',       // Currency
		11 => 'number',         // Number
		12 => 'percent',        // Percentage
		13 => 'social_security', // SSN
		14 => 'text',           // Text (additional)
		15 => 'text',           // Text (legacy)
		16 => 'dropdown',       // Dropdown (alternative)
		17 => 'checkbox',       // Checkbox (alternative type)
		18 => 'url',            // Website/URL
		19 => 'text',           // Text (extended)
		20 => 'textarea',       // Textarea (extended)
		21 => 'dropdown',       // Dropdown (extended)
		22 => 'radio',          // Radio (extended)
		23 => 'checkbox',       // Checkbox (list type)
		24 => 'date',           // Date (extended)
		25 => 'datetime',       // DateTime (extended)
	);

	/**
	 * Standard contact fields that can be set via XML-RPC contact.add/update methods.
	 *
	 * Keap's XML-RPC API has limitations - only these predefined contact table fields can be
	 * updated directly in the contact.add or contact.update call. Custom fields (DataFormField)
	 * must be prefixed with underscore and sent separately.
	 *
	 * This list determines which fields go into the initial contact creation vs. the separate
	 * custom field update call, preventing "Invalid field name" XML-RPC errors.
	 *
	 * @var array Keap contact fields supported by XML-RPC API
	 */
	protected $_supported_contact_fields = array(
		'FirstName', 'LastName', 'Email', 'Phone1', 'Phone2',
		'City', 'State', 'PostalCode', 'Country',
		'StreetAddress1', 'StreetAddress2',
		'Company', 'JobTitle', 'Website'
	);

	/**
	 * Cache for all DataFormField records from Strategy 4 fallback.
	 *
	 * When fetching checkbox custom fields, Strategy 4 retrieves ALL fields from Keap
	 * and filters client-side. To avoid fetching all fields 3 times (once for each
	 * checkbox DataType 5, 17, 23), we cache the result here.
	 *
	 * This reduces API calls from ~56 to ~30 on first custom fields fetch.
	 *
	 * @var array|null Cached all fields result, null if not yet fetched
	 */
	private $_all_fields_cache = null;

	/**
	 * Constructor
	 */
	public function __construct( $key ) {
		parent::__construct( $key );
	}

	/**
	 * Return the connection type
	 */
	public static function get_type() {
		return 'autoresponder';
	}

	/**
	 * Get connection title
	 */
	public function get_title() {
		return 'Keap (Infusionsoft)';
	}

	/**
	 * Get list subtitle
	 */
	public function get_list_sub_title() {
		return __( 'Choose your Tag', 'thrive-dash' );
	}

	/**
	 * Has tags support
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
	 * Has custom fields support
	 */
	public function has_custom_fields() {
		return true;
	}

	/**
	 * Output setup form
	 */
	public function output_setup_form() {
		$this->output_controls_html( 'infusionsoft' );
	}

	/**
	 * Read and save credentials
	 *
	 * @return mixed True on success, error message on failure
	 */
	public function read_credentials() {
		// Verify nonce for CSRF protection
		if ( ! isset( $_POST['nonce'] ) || ! check_ajax_referer( 'tve_dash_api_save', 'nonce', false ) ) {
			return $this->error( __( 'Security check failed. Please reload the page and try again.', 'thrive-dash' ) );
		}

		// Use parent helper method instead of direct $_POST access
		$connection = $this->post( 'connection', array() );

		// Validate connection data structure
		if ( ! is_array( $connection ) ) {
			return $this->error( __( 'Invalid connection data', 'thrive-dash' ) );
		}

		$client_id = isset( $connection['client_id'] ) ? sanitize_text_field( $connection['client_id'] ) : '';
		$key       = isset( $connection['api_key'] ) ? sanitize_text_field( $connection['api_key'] ) : '';

		if ( empty( $key ) || empty( $client_id ) ) {
			return $this->error( __( 'Client ID and API key are required', 'thrive-dash' ) );
		}

		$this->set_credentials( array( 'client_id' => $client_id, 'api_key' => $key ) );

		$result = $this->test_connection();
		if ( true !== $result ) {
			return $this->error( sprintf( __( 'Could not connect to Keap: %s', 'thrive-dash' ), $result ) );
		}

		$this->save();
		return $this->success( 'Keap connected successfully' );
	}

	/**
	 * Test connection with enhanced error handling.
	 */
	public function test_connection() {
		try {
			$api = $this->get_api();
			if ( false === $api ) {
				return 'Failed to create API instance';
			}

			$result = $this->_get_lists();
			return is_array( $result ) ? true : $result;

		} catch ( Exception $e ) {
			return 'Connection test failed: ' . $e->getMessage();
		} catch ( Error $e ) {
			return 'Fatal error during connection test';
		}
	}

	/**
	 * Check if connection is safe to use (prevents fatal errors).
	 *
	 * This safety check prevents fatal errors when API credentials are missing or invalid.
	 * Called before making API requests to ensure the connection is properly initialized.
	 *
	 * We catch both Exception and Error because XML-RPC can throw either depending on
	 * the type of failure (network issues vs. missing classes/methods).
	 *
	 * @return bool True if connection is safe to use.
	 */
	public function is_connection_safe() {
		if ( empty( $this->param( 'client_id' ) ) || empty( $this->param( 'api_key' ) ) ) {
			return false;
		}

		try {
			$api = $this->get_api();
			return false !== $api && is_object( $api );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}
	}

	/**
	 * Get API instance with fatal error prevention.
	 *
	 * Creates a new instance of the Keap API wrapper with stored credentials.
	 * Returns false instead of throwing exceptions to prevent breaking automations
	 * when credentials are invalid or the API library has issues.
	 *
	 * @return Thrive_Dash_Api_Infusionsoft|false API instance or false on failure.
	 */
	protected function get_api_instance() {
		$client_id = $this->param( 'client_id' );
		$api_key   = $this->param( 'api_key' );

		if ( empty( $client_id ) || empty( $api_key ) ) {
			return false;
		}

		try {
			return new Thrive_Dash_Api_Infusionsoft( $client_id, $api_key );
		} catch ( Exception $e ) {
			return false;
		} catch ( Error $e ) {
			return false;
		}
	}

	/**
	 * Fetch all tags (ContactGroups) from Keap API.
	 *
	 * In Keap, tags are implemented as ContactGroups. This method queries the ContactGroup table
	 * to retrieve all available tags that can be applied to contacts.
	 *
	 * The '%' wildcard in GroupName filter returns all groups. We limit to 1000 results which
	 * should be sufficient for most accounts. Returns numeric tag IDs that are used for the
	 * tag assignment API calls.
	 *
	 * @return array|string Array of lists with 'id' and 'name', or error message string.
	 */
	protected function _get_lists() {
		try {
			$api = $this->get_api();

			if ( false === $api || ! is_object( $api ) ) {
				return 'Failed to get API instance';
			}

			// Query ContactGroup table for all tags (groups) in the account
			$response = $api->data( 'query', 'ContactGroup', 1000, 0, array( 'GroupName' => '%' ), array( 'Id', 'GroupName' ) );

			if ( empty( $response ) || ! is_array( $response ) ) {
				return array();
			}

			$lists = array();
			foreach ( $response as $item ) {
				// Validate each item has required fields before adding to results
				if ( ! is_array( $item ) || ! isset( $item['Id'] ) || ! isset( $item['GroupName'] ) ) {
					continue;
				}

				$lists[] = array(
					'id'   => sanitize_text_field( $item['Id'] ),
					'name' => sanitize_text_field( $item['GroupName'] ),
				);
			}

			return $lists;

		} catch ( Exception $e ) {
			return $e->getMessage();
		} catch ( Error $e ) {
			return 'Fatal error occurred while fetching lists';
		}
	}

	/**
	 * Get all API data needed for the form editor (tags, lists, custom fields).
	 *
	 * This method caches the complete API dataset for 1 month to reduce API calls and improve
	 * editor load performance. The cache includes:
	 * - lists: Available tags (ContactGroups) for assignment
	 * - tags: Formatted tag data for the tag selector UI
	 * - custom_fields: Available custom fields for mapping
	 * - tag_mapping: Numeric ID to name mapping for backward compatibility
	 *
	 * Cache is refreshed when $force is true or debug mode is enabled.
	 * We backfill missing 'tags' or 'tag_mapping' keys for caches created by older versions.
	 *
	 * @param array $params Request parameters.
	 * @param bool  $force  Force refresh cache and fetch fresh data from API.
	 *
	 * @return array Complete API data for editor initialization.
	 */
	public function get_api_data( $params = array(), $force = false ) {
		if ( empty( $params ) ) {
			$params = array();
		}

		$transient = 'tve_api_data_' . $this->get_key();
		$data      = get_transient( $transient );

		// Always refresh cache in debug mode for development/testing
		if ( false === $force && tve_dash_is_debug_on() ) {
			$force = true;
		}

		if ( true === $force || false === $data ) {
			// Cache miss or forced refresh - fetch everything from API
			$lists = $this->get_lists( false );

			$data = array(
				'lists'          => $lists,
				'extra_settings' => $this->get_extra_settings( $params ),
				'custom_fields'  => $this->get_custom_fields( $params ),
				'tags'           => $this->get_tags( $force ),
				'tag_mapping'    => $this->build_tag_id_mapping( $lists ),
			);

			set_transient( $transient, $data, MONTH_IN_SECONDS );
		} else {
			// Cache hit - validate data structure
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			// Backfill tags if missing (cache from older version)
			if ( ! isset( $data['tags'] ) || ! is_array( $data['tags'] ) || empty( $data['tags'] ) ) {
				$data['tags'] = $this->get_tags( $force );
				set_transient( $transient, $data, MONTH_IN_SECONDS );
			}

			// Backfill tag mapping for migration support if not present
			if ( ! isset( $data['tag_mapping'] ) ) {
				$lists = isset( $data['lists'] ) ? $data['lists'] : $this->get_lists( false );
				$data['tag_mapping'] = $this->build_tag_id_mapping( $lists );
			}
		}

		// Always fetch fresh custom fields to ensure latest field definitions
		$data['api_custom_fields'] = $this->get_api_custom_fields( $params, $force );

		return $data;
	}
	
	/**
	 * Build mapping from numeric tag IDs to tag names for backward compatibility.
	 *
	 * Older plugin versions stored numeric tag IDs (e.g., "123") in form configurations.
	 * Newer versions store tag names (e.g., "Customer") for better portability and reliability.
	 *
	 * This mapping enables migration logic to convert old numeric IDs to tag names when
	 * encountered, ensuring forms configured with old versions continue working correctly
	 * after the tag storage format was changed to use names instead of IDs.
	 *
	 * @param array $lists The lists array from _get_lists() with 'id' and 'name' keys.
	 *
	 * @return array Mapping array where numeric ID => tag name (e.g., array( '123' => 'Customer' )).
	 */
	protected function build_tag_id_mapping( $lists ) {
		$mapping = array();

		if ( ! empty( $lists ) && is_array( $lists ) ) {
			foreach ( $lists as $list ) {
				if ( isset( $list['id'] ) && isset( $list['name'] ) ) {
					$mapping[ $list['id'] ] = $list['name'];
				}
			}
		}

		return $mapping;
	}

	/**
	 * Return all available Keap tags formatted for the editor's tag selector.
	 *
	 * Implements a two-tier caching strategy for reliability:
	 * 1. Primary cache: 15 minutes - provides fresh data while reducing API calls
	 * 2. Backup cache: 1 month - fallback when API is unreachable or returns errors
	 *
	 * The backup cache ensures the tag selector continues working even when Keap's API
	 * is down or experiencing issues. This prevents forms from breaking due to temporary
	 * API problems.
	 *
	 * Tags are deduplicated by lowercase name to prevent duplicate entries if Keap has
	 * tags with different casing. The tag 'id' is set to tag name (not numeric ID) because
	 * names are more portable and don't change when exporting/importing between accounts.
	 *
	 * @param bool $force Force fresh fetch from API, bypassing cache.
	 *
	 * @return array Array of tag objects with 'id', 'text', and 'selected' keys for Select2.
	 */
	public function get_tags( $force = false ) {
		$cache_key = 'infusionsoft_tags_' . $this->get_key();
		$cached    = get_transient( $cache_key );

		// Check 15-minute cache first for better performance
		if ( false !== $cached && false === $force && is_array( $cached ) ) {
			return $cached;
		}

		$tags      = array();
		$processed = array();

		try {
			$lists = $this->get_lists( false );

			// If API call fails, use backup cache as fallback
			if ( empty( $lists ) || ! is_array( $lists ) ) {
				$backup = get_transient( $cache_key . '_backup' );
				return ( false !== $backup && is_array( $backup ) ) ? $backup : $tags;
			}

			foreach ( $lists as $list ) {
				$tag_name = isset( $list['name'] ) ? sanitize_text_field( $list['name'] ) : '';

				if ( '' === $tag_name ) {
					continue;
				}

				// Deduplicate by lowercase name to handle case variations
				$slug = strtolower( $tag_name );

				if ( isset( $processed[ $slug ] ) ) {
					continue;
				}

				$processed[ $slug ] = true;

				// Use tag name as ID for better portability (not numeric ID)
				$tags[] = array(
					'id'       => $tag_name,
					'text'     => $tag_name,
					'selected' => false,
				);
			}

			// Update both primary and backup caches on successful fetch
			set_transient( $cache_key, $tags, 15 * MINUTE_IN_SECONDS );
			set_transient( $cache_key . '_backup', $tags, MONTH_IN_SECONDS );

		} catch ( Exception $e ) {
			// On exception, fall back to backup cache to keep things working
			$backup = get_transient( $cache_key . '_backup' );
			return ( false !== $backup && is_array( $backup ) ) ? $backup : $tags;
		}

		return $tags;
	}

	/**
	 * Clear both primary and backup tags cache.
	 *
	 * Called after creating new tags via the "Apply Tags" button to immediately
	 * refresh the tag selector without waiting for cache expiration. This ensures
	 * newly created tags appear in the dropdown right away.
	 *
	 * Clears both caches (primary + backup) to prevent stale data from persisting.
	 *
	 * @return void
	 */
	public function clearTagsCache() {
		$cache_key = 'infusionsoft_tags_' . $this->get_key();
		delete_transient( $cache_key );
		delete_transient( $cache_key . '_backup' );
	}

	/**
	 * Create or update contact in Keap and assign tags.
	 *
	 * This is the main integration point called when a form is submitted. The flow:
	 * 1. Validate email and create/update contact via XML-RPC
	 * 2. Opt-in the contact's email for marketing compliance
	 * 3. Assign the primary tag (list_identifier) if provided
	 * 4. Process and assign additional tags from the tag selector
	 * 5. Update custom fields in a separate API call (XML-RPC limitation)
	 *
	 * We use 'addWithDupCheck' to update existing contacts instead of creating duplicates.
	 * Tag assignment checks existing groups to avoid redundant API calls.
	 *
	 * Custom fields are updated separately because Keap's XML-RPC contact.add method
	 * only accepts standard contact table fields. Custom fields must use contact.update.
	 *
	 * @param string|int $list_identifier Primary tag ID to assign (can be numeric ID or name).
	 * @param array      $arguments       Form submission data including email, name, custom fields.
	 *
	 * @return bool|string True on success, error message string on failure.
	 */
	public function add_subscriber( $list_identifier, $arguments ) {
		try {
			// Validate required arguments
			if ( empty( $arguments ) || ! is_array( $arguments ) ) {
				return false;
			}

			if ( empty( $arguments['email'] ) || ! is_email( $arguments['email'] ) ) {
				return false;
			}

			$api = $this->get_api();

			if ( false === $api || ! is_object( $api ) ) {
				return false;
			}

			// Prepare basic contact data with required email field
			$data = array( 'Email' => sanitize_email( $arguments['email'] ) );

			// Add name fields if provided
			if ( ! empty( $arguments['name'] ) ) {
				list( $first_name, $last_name ) = $this->get_name_parts( $arguments['name'] );
				$data['FirstName'] = sanitize_text_field( $first_name );
				$data['LastName']  = sanitize_text_field( $last_name );
			}

			// Add phone if provided
			if ( ! empty( $arguments['phone'] ) ) {
				$data['Phone1'] = sanitize_text_field( $arguments['phone'] );
			}

			// Add only standard contact fields that XML-RPC accepts in contact.add call
			if ( ! empty( $arguments['tve_mapping'] ) ) {
				$default_fields = $this->get_basic_default_fields( $arguments );
				if ( is_array( $default_fields ) ) {
					$data = array_merge( $data, $default_fields );
				}
			}

			// Create or update contact (deduplicates by email)
			$contact_id = $api->contact( 'addWithDupCheck', $data, 'Email' );

			if ( $contact_id ) {
				// Opt-in email for marketing compliance
				$api->APIEmail( 'optIn', $data['Email'], 'thrive opt in' );

				// Load existing contact to check which tags they already have
				$contact         = $api->contact( 'load', $contact_id, array( 'Id', 'Email', 'Groups' ) );
				$existing_groups = empty( $contact['Groups'] ) ? array() : explode( ',', $contact['Groups'] );

				// Assign primary tag (list_identifier) if not already assigned
				if ( ! empty( $list_identifier ) && is_numeric( $list_identifier ) && ! in_array( $list_identifier, $existing_groups ) ) {
					$api->contact( 'addToGroup', $contact_id, $list_identifier );
				}

				// Process additional tags from tag selector
				$tag_key = $this->get_tags_key();
				if ( ! empty( $arguments[ $tag_key ] ) ) {
					// Import tags (creates them in Keap if they don't exist)
					$tag_ids = $this->import_tags( $arguments[ $tag_key ] );

					// Assign each tag to the contact if not already assigned
					foreach ( $tag_ids as $tag_id ) {
						if ( ! in_array( $tag_id, $existing_groups ) ) {
							$api->contact( 'addToGroup', $contact_id, $tag_id );
						}
					}
				}

				// Update custom fields in separate call (XML-RPC limitation)
				if ( ! empty( $arguments['tve_mapping'] ) || ! empty( $arguments['automator_custom_fields'] ) ) {
					$this->update_custom_fields( $contact_id, $arguments );
				}
			}

			return true;

		} catch ( Exception $e ) {
			return $e->getMessage();
		}
	}

	/**
	 * Create tag in Keap or return existing tag ID.
	 *
	 * Checks if tag already exists by name before creating to prevent duplicates.
	 * This is called during form submission when users type custom tag names that
	 * may not exist in Keap yet.
	 *
	 * The API requires exact tag name match - no fuzzy matching or case-insensitive search.
	 * If the tag exists, returns its numeric ID. If not, creates it and returns the new ID.
	 *
	 * @param string $tag_name The tag name to create or retrieve.
	 *
	 * @return int|false Numeric tag ID if successful, false on API failure.
	 */
	public function create_tag( $tag_name ) {
		try {
			$api = $this->get_api();

			// Check if tag already exists to avoid duplicates
			$existing = $api->data( 'query', 'ContactGroup', 1, 0,
				array( 'GroupName' => $tag_name ),
				array( 'Id', 'GroupName' )
			);

			if ( ! empty( $existing ) ) {
				return $existing[0]['Id'];
			}

			// Tag doesn't exist - create it now
			$tag_id = $api->data( 'add', 'ContactGroup', array( 'GroupName' => $tag_name ) );

			return $tag_id;

		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Process comma-separated tag names and return their numeric IDs.
	 *
	 * Called during form submission to convert tag names into tag IDs for API assignment.
	 * Creates tags instantly if they don't exist in Keap yet, enabling "just-in-time" tag creation.
	 *
	 * This allows users to type new tag names in forms and have them automatically created
	 * in Keap without needing to pre-create them, improving the user experience.
	 *
	 * @param string $tags Comma-separated tag names (e.g., "Customer, VIP, Newsletter").
	 *
	 * @return array Array of numeric tag IDs ready for contact.addToGroup API calls.
	 */
	public function import_tags( $tags ) {
		$imported_tag_ids = array();

		if ( empty( $tags ) ) {
			return $imported_tag_ids;
		}

		// Split comma-separated string into individual tag names
		$tag_names = explode( ',', trim( $tags, ' ,' ) );

		foreach ( $tag_names as $tag_name ) {
			$tag_name = trim( $tag_name );

			if ( ! empty( $tag_name ) ) {
				// Create tag if it doesn't exist, or get existing ID
				$tag_id = $this->create_tag( $tag_name );

				if ( $tag_id ) {
					$imported_tag_ids[] = $tag_id;
				}
			}
		}

		return $imported_tag_ids;
	}

	/**
	 * Instantly create tags in Keap when user clicks "Apply Tags" button in editor.
	 *
	 * This provides immediate tag creation feedback in the editor UI. When users type new
	 * tag names in the tag selector and click Apply, this creates them in Keap right away
	 * so they appear in the dropdown without waiting for form submission.
	 *
	 * The flow:
	 * 1. Parse tag names from request (handles both string and array formats)
	 * 2. Fetch existing tags to determine which ones need creation
	 * 3. Create only the new tags that don't already exist (case-insensitive check)
	 * 4. Clear tags cache so the new tags appear immediately in UI
	 * 5. Return user-friendly message with count of created tags
	 *
	 * Duplicate checking prevents API errors and provides better UX feedback.
	 *
	 * @param array $params Parameters containing 'tag_names' (string or array).
	 *
	 * @return array Response with 'success' boolean, 'message' string, and 'tags' array.
	 */
	public function _create_tags_if_needed( $params ) {
		$tag_names = isset( $params['tag_names'] ) ? $params['tag_names'] : array();

		// Handle both string and array input formats
		if ( is_string( $tag_names ) ) {
			$tag_names = explode( ',', $tag_names );
			$tag_names = array_map( 'trim', $tag_names );
		}

		$tag_names = array_filter( $tag_names );

		if ( empty( $tag_names ) ) {
			return array(
				'success'      => true,
				'message'      => __( 'No tags to create', 'thrive-dash' ),
				'tags_created' => 0,
			);
		}

		try {
			// Fetch existing tags to check for duplicates (force fresh from API)
			$existing_tags      = $this->get_tags( true );
			$existing_tag_names = array();

			foreach ( $existing_tags as $tag ) {
				$existing_tag_names[] = strtolower( $tag['text'] );
			}

			// Filter to only tags that don't exist yet (case-insensitive)
			$new_tag_names = array();
			foreach ( $tag_names as $tag_name ) {
				$tag_name = trim( $tag_name );
				if ( ! empty( $tag_name ) && ! in_array( strtolower( $tag_name ), $existing_tag_names, true ) ) {
					$new_tag_names[] = $tag_name;
				}
			}

			if ( empty( $new_tag_names ) ) {
				return array(
					'success'      => true,
					'message'      => __( 'All tags already exist', 'thrive-dash' ),
					'tags_created' => 0,
				);
			}

			// Create each new tag via API
			$created_tags = array();
			foreach ( $new_tag_names as $tag_name ) {
				try {
					$tag_id = $this->create_tag( $tag_name );
					if ( $tag_id ) {
						$created_tags[] = array(
							'id'   => (string) $tag_id,
							'text' => $tag_name,
						);
					}
				} catch ( Exception $e ) {
					// Continue creating other tags even if one fails
					continue;
				}
			}

			// Clear cache so new tags appear in dropdown immediately
			$this->clearTagsCache();

			return array(
				'success'      => true,
				'message'      => sprintf(
					_n( '%d tag created successfully', '%d tags created successfully', count( $created_tags ), 'thrive-dash' ),
					count( $created_tags )
				),
				'tags_created' => count( $created_tags ),
				'tags'         => $created_tags,
			);

		} catch ( Exception $e ) {
			return array(
				'success' => false,
				'message' => $e->getMessage(),
			);
		}
	}

	/**
	 * Get all available custom fields from Keap for the field mapper.
	 *
	 * Returns both standard contact fields (FirstName, City, etc.) and custom fields (DataFormField).
	 * Standard fields are always available as fallback, even if API fails.
	 *
	 * Custom fields are fetched by querying each DataType ID (1-25) separately because Keap's
	 * XML-RPC API doesn't provide a single "get all fields" method. We iterate through all
	 * known field types from $_custom_fields mapping.
	 *
	 * Results are cached to avoid repeated API calls. If API is unreachable, returns standard
	 * fields only so the field mapper doesn't break completely.
	 *
	 * @param array $params  Optional parameters (unused but required by interface).
	 * @param bool  $force   Force fresh fetch from API, bypassing cache.
	 * @param bool  $get_all Unused parameter (kept for backward compatibility).
	 *
	 * @return array Array of field definitions with 'id', 'name', 'type', 'label' keys.
	 */
	public function get_api_custom_fields( $params = array(), $force = false, $get_all = false ) {
		$cached_data = $this->get_cached_custom_fields();
		if ( false === $force && ! empty( $cached_data ) ) {
			return $cached_data;
		}

		// Always include standard contact fields as foundation
		$fields = array(
			array( 'id' => 'FirstName', 'name' => 'First Name', 'type' => 'text', 'label' => 'First Name' ),
			array( 'id' => 'LastName', 'name' => 'Last Name', 'type' => 'text', 'label' => 'Last Name' ),
			array( 'id' => 'Phone1', 'name' => 'Phone', 'type' => 'phone', 'label' => 'Phone' ),
			array( 'id' => 'City', 'name' => 'City', 'type' => 'text', 'label' => 'City' ),
			array( 'id' => 'State', 'name' => 'State', 'type' => 'text', 'label' => 'State' ),
			array( 'id' => 'PostalCode', 'name' => 'Postal Code', 'type' => 'text', 'label' => 'Postal Code' ),
			array( 'id' => 'Country', 'name' => 'Country', 'type' => 'text', 'label' => 'Country' ),
		);

		// Fetch custom fields from API if connection is working
		if ( $this->is_connection_safe() ) {
			try {
				// Query each DataType ID separately to get all custom fields
				foreach ( array_keys( $this->_custom_fields ) as $field_id ) {
					$api_fields = $this->get_custom_fields_by_type( $field_id );
					if ( is_array( $api_fields ) ) {
						$fields = array_merge( $fields, $api_fields );
					}
				}
			} catch ( Exception $e ) {
				// Continue with default fields only if API fails
			} catch ( Error $e ) {
				// Continue with default fields only if fatal error
			}
		}

		$this->_save_custom_fields( $fields );
		return $fields;
	}

	/**
	 * Query Keap API for custom fields of a specific DataType with multi-strategy fallback.
	 *
	 * Keap's XML-RPC API has inconsistent behavior for querying DataFormField records, especially
	 * for checkbox fields. This method uses multiple query strategies to ensure we find all fields:
	 *
	 * Strategy 1: Query with GroupId filter (~<>~0 means "not equal to 0")
	 *   - Works for most field types but misses some checkboxes
	 *
	 * Strategy 2: Query with DataType only (no GroupId filter)
	 *   - Broader search that catches fields missed by Strategy 1
	 *
	 * Strategy 3 (checkboxes only): Query with GroupId = 0 exactly
	 *   - Some checkbox fields have GroupId of 0 and need exact match
	 *
	 * Strategy 4 (checkboxes only): Fetch ALL fields and filter client-side
	 *   - Last resort fallback when API filters don't work reliably
	 *
	 * The multi-strategy approach is necessary because Keap's API doesn't consistently return
	 * all fields with simple queries. Checkbox fields are especially problematic.
	 *
	 * @param int $field_id The DataType ID to query (1-25 from $_custom_fields mapping).
	 *
	 * @return array Array of normalized custom field definitions.
	 */
	protected function get_custom_fields_by_type( $field_id ) {
		try {
			$api = $this->get_api();

			if ( false === $api || ! is_object( $api ) ) {
				return array();
			}

			if ( ! method_exists( $api, 'data' ) && ! method_exists( $api, '__call' ) ) {
				return array();
			}

			$field_id = (int) $field_id;

			// Checkbox types need special handling due to API inconsistencies
			$checkbox_types = array( 5, 17, 23 );
			$is_checkbox    = in_array( $field_id, $checkbox_types, true );

			$response = array();

			// Strategy 1: Try with GroupId filter first (works for most non-checkbox fields)
			if ( ! $is_checkbox ) {
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id, 'GroupId' => '~<>~0' ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}

			// Strategy 2: Try without GroupId filter (broader search)
			if ( empty( $response ) ) {
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}

			// Strategy 3: For checkboxes, try querying with GroupId = 0 exactly
			if ( empty( $response ) && $is_checkbox ) {
				$response = $api->data(
					'query',
					'DataFormField',
					1000,
					0,
					array( 'DataType' => $field_id, 'GroupId' => 0 ),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
			}

			// Strategy 4: Last resort for checkboxes - fetch all fields and filter client-side
			// Use cached result if available to avoid fetching all fields multiple times (for 3 checkbox types)
			if ( empty( $response ) && $is_checkbox ) {
				try {
					// Check if we've already fetched all fields (cached across checkbox types 5, 17, 23)
					if ( null === $this->_all_fields_cache ) {
						$this->_all_fields_cache = $api->data(
							'query',
							'DataFormField',
							500,
							0,
							array(),
							array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
						);
					}

					$response = array();
					if ( ! empty( $this->_all_fields_cache ) && is_array( $this->_all_fields_cache ) ) {
						foreach ( $this->_all_fields_cache as $field ) {
							if ( isset( $field['DataType'] ) && (int) $field['DataType'] === $field_id ) {
								$response[] = $field;
							}
						}
					}
				} catch ( Exception $e ) {
					// Continue with empty response
				}
			}

			if ( empty( $response ) ) {
				return array();
			}

			// Normalize all fields to consistent format
			$fields = array();
			foreach ( $response as $field ) {
				$normalized_field = $this->normalize_custom_field( $field );
				if ( ! empty( $normalized_field['id'] ) ) {
					$fields[] = $normalized_field;
				}
			}

			return $fields;

		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Convert raw API field data into normalized format for the field mapper.
	 *
	 * Keap's API returns fields with varying structures and missing properties. This method
	 * standardizes them into a consistent format with proper fallbacks:
	 *
	 * - Converts numeric DataType ID to human-readable type (using $_custom_fields mapping)
	 * - Generates field name from either 'Name' property or field ID
	 * - Creates user-friendly label from 'Label', 'Name', or field ID
	 * - Handles missing properties gracefully to prevent errors
	 *
	 * The 'id' is set to the field Name because that's what the XML-RPC API expects when
	 * updating contacts. Custom fields must be prefixed with underscore when sent to API.
	 *
	 * @param array $field Raw field data from Keap API with Id, Name, Label, DataType, GroupId.
	 *
	 * @return array Normalized field with 'id', 'name', 'type', 'label', 'data_type', 'group_id'.
	 */
	protected function normalize_custom_field( $field ) {
		$field = (array) $field;

		// Skip fields with no identifier
		if ( empty( $field['Name'] ) && empty( $field['Id'] ) ) {
			return array();
		}

		// Map numeric DataType ID to standardized field type
		$field_type   = 'text';
		$data_type_id = ! empty( $field['DataType'] ) ? (int) $field['DataType'] : 15;

		if ( array_key_exists( $data_type_id, $this->_custom_fields ) ) {
			$field_type = $this->_custom_fields[ $data_type_id ];
		}

		// Determine field name (prefer 'Name' property, fallback to ID)
		$field_name = '';
		if ( ! empty( $field['Name'] ) ) {
			$field_name = $field['Name'];
		} elseif ( ! empty( $field['Id'] ) ) {
			$field_name = '_' . $field['Id'];
		}

		// Generate user-friendly label with multiple fallbacks
		$field_label = '';
		if ( ! empty( $field['Label'] ) ) {
			$field_label = $field['Label'];
		} elseif ( ! empty( $field['Name'] ) ) {
			// Convert field_name to readable format: "first_name" becomes "First Name"
			$field_label = ucwords( str_replace( array( '_', '-' ), ' ', $field['Name'] ) );
		} elseif ( ! empty( $field['Id'] ) ) {
			$field_label = 'Custom Field ' . $field['Id'];
		}

		return array(
			'id'        => $field_name,
			'name'      => $field_label,
			'type'      => $field_type,
			'label'     => $field_label,
			'data_type' => $data_type_id,
			'group_id'  => isset( $field['GroupId'] ) ? $field['GroupId'] : null,
		);
	}

	/**
	 * Debug method to get all custom fields with detailed information.
	 * Use this to troubleshoot checkbox field issues.
	 *
	 * @return array Detailed information about all custom fields.
	 */
	public function debug_get_all_custom_fields() {
		if ( ! $this->is_connected() ) {
			return array( 'error' => 'Not connected to Keap API' );
		}

		$debug_info = array(
			'field_types_checked' => array(),
			'total_fields_found'  => 0,
			'checkbox_fields'     => array(),
			'all_fields'          => array(),
		);

		try {
			$api = $this->get_api();
			
			// Check each field type
			foreach ( array_keys( $this->_custom_fields ) as $field_id ) {
				$field_type_name = $this->_custom_fields[ $field_id ];
				$debug_info['field_types_checked'][] = "Type {$field_id} ({$field_type_name})";
				
				$fields = $this->get_custom_fields_by_type( $field_id );
				
				if ( ! empty( $fields ) ) {
					$debug_info['all_fields'][ $field_id ] = array(
						'type_name' => $field_type_name,
						'count'     => count( $fields ),
						'fields'    => $fields,
					);
					
					$debug_info['total_fields_found'] += count( $fields );
					
					// Track checkbox fields specifically
					if ( 'checkbox' === $field_type_name ) {
						$debug_info['checkbox_fields'][ $field_id ] = $fields;
					}
				}
			}
			
			try {
				$all_raw_fields = $api->data(
					'query',
					'DataFormField',
					500,
					0,
					array(),
					array( 'Id', 'GroupId', 'Name', 'Label', 'DataType' )
				);
				
				$debug_info['raw_api_response'] = array(
					'total_raw_fields' => count( $all_raw_fields ),
					'checkbox_raw_fields' => array(),
				);
				
				// Find checkbox fields in raw response
				foreach ( $all_raw_fields as $raw_field ) {
					$data_type = isset( $raw_field['DataType'] ) ? (int) $raw_field['DataType'] : 0;
					if ( in_array( $data_type, array( 5, 17, 23 ), true ) ) {
						$debug_info['raw_api_response']['checkbox_raw_fields'][] = $raw_field;
					}
				}
				
			} catch ( Exception $e ) {
				$debug_info['raw_api_error'] = $e->getMessage();
			}
			
		} catch ( Exception $e ) {
			$debug_info['error'] = $e->getMessage();
		}

		return $debug_info;
	}

	/**
	 * Update custom fields for an existing contact.
	 *
	 * Called after contact creation to update custom fields in a separate API call.
	 * This is necessary because Keap's XML-RPC contact.add method only accepts standard
	 * contact table fields. Custom DataFormField values require contact.update.
	 *
	 * Handles two data sources:
	 * 1. automator_custom_fields: From Thrive Automator with structured field data
	 * 2. tve_mapping: From form editor with base64-encoded field mapping
	 *
	 * All custom fields are prefixed with underscore (_FieldName) as required by Keap's
	 * contact.update XML-RPC method. Standard contact fields go in the initial add call.
	 *
	 * Fails silently to prevent breaking form submissions when custom field updates fail.
	 * The contact is already created with the email at this point, so it's better to
	 * succeed partially than fail completely.
	 *
	 * @param int   $contact_id Contact ID to update.
	 * @param array $arguments  Arguments containing custom field data.
	 *
	 * @return bool True if successful, false otherwise.
	 */
	protected function update_custom_fields( $contact_id, $arguments ) {
		if ( ! is_int( $contact_id ) || empty( $arguments ) ) {
			return false;
		}

		try {
			$custom_fields = array();

			// Build custom fields from either Automator or form editor data
			if ( ! empty( $arguments['automator_custom_fields'] ) ) {
				$custom_fields = $this->build_automation_custom_fields( $arguments['automator_custom_fields'] );
			} elseif ( ! empty( $arguments['tve_mapping'] ) ) {
				$custom_fields = $this->build_mapped_custom_fields( $arguments );
			}

			if ( ! empty( $custom_fields ) ) {
				$api = $this->get_api();

				if ( false === $api || ! is_object( $api ) ) {
					return false;
				}

				// Update contact with custom fields (all prefixed with underscore)
				$api->contact( 'update', $contact_id, $custom_fields );
				return true;
			}

		} catch ( Exception $e ) {
			// Continue silently - contact is already created, don't fail completely
		} catch ( Error $e ) {
			// Continue silently to prevent breaking automator flows
		}

		return false;
	}

	/**
	 * Build custom field data from Thrive Automator structured format.
	 *
	 * Thrive Automator provides custom fields in a structured array format with key, value,
	 * and type information. This method converts that format into Keap's XML-RPC format.
	 *
	 * All custom field keys are prefixed with underscore as required by Keap's contact.update
	 * method. The method also handles field type conversions:
	 * - Dates: Converts to YYYY-MM-DD format
	 * - Checkboxes: Converts to 1 or 0
	 * - Dropdowns/Numbers: Handles array values and formatting
	 *
	 * @param array $automation_data The automation custom fields with 'api_fields' array.
	 *
	 * @return array Formatted custom fields ready for contact.update (e.g., array( '_FieldName' => 'value' )).
	 */
	public function build_automation_custom_fields( $automation_data ) {
		$mapped_data = array();

		if ( ! empty( $automation_data['api_fields'] ) ) {
			foreach ( $automation_data['api_fields'] as $pair ) {
				$value = sanitize_text_field( $pair['value'] );

				if ( $value ) {
					$field_type = isset( $pair['type'] ) ? $pair['type'] : '';

					// Format date fields to Keap's expected YYYY-MM-DD format
					if ( 'date' === strtolower( $field_type ) ) {
						$value = $this->format_date_value( $value );
					}

					// Convert special field values based on type (checkbox to 1/0, etc.)
					$value = $this->convert_special_field_values( $value, $field_type );

					// Prefix field name with underscore as required by Keap API
					$mapped_data[ '_' . $pair['key'] ] = $value;
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Extract standard contact fields from form mapping for initial contact.add call.
	 *
	 * Keap's XML-RPC contact.add method only accepts predefined contact table fields like
	 * FirstName, City, Phone1, etc. Custom fields cause "Invalid field name" errors.
	 *
	 * This method filters the form field mapping to include ONLY standard contact fields
	 * that can be safely sent in the initial contact creation call. Custom fields are
	 * handled separately via contact.update in update_custom_fields().
	 *
	 * The $_supported_contact_fields array defines which fields are safe for contact.add.
	 * All other fields are treated as custom and must be prefixed with underscore in the
	 * separate update call.
	 *
	 * @param array $args Form submission arguments with 'tve_mapping' base64-encoded data.
	 *
	 * @return array Standard contact fields ready for contact.add (no underscore prefix).
	 */
	protected function get_basic_default_fields( $args ) {
		if ( empty( $args['tve_mapping'] ) ) {
			return array();
		}

		// Decode field mapping from form editor
		$mapped_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );
		if ( ! is_array( $mapped_data ) ) {
			return array();
		}

		$contact_fields = array();

		foreach ( $mapped_data as $cf_name => $cf_data ) {
			if ( empty( $cf_data['infusionsoft'] ) ) {
				continue;
			}

			$field_id   = $cf_data['infusionsoft'];
			$clean_name = str_replace( '[]', '', $cf_name );

			// Only include standard contact fields (not custom fields)
			if ( isset( $args[ $clean_name ] ) && in_array( $field_id, $this->_supported_contact_fields, true ) ) {
				$field_value = $args[ $clean_name ];

				// Determine field type from multiple possible keys
				$field_type = '';
				if ( isset( $cf_data['type'] ) ) {
					$field_type = $cf_data['type'];
				} elseif ( isset( $cf_data['_field_type'] ) ) {
					$field_type = $cf_data['_field_type'];
				} elseif ( isset( $cf_data['_field'] ) ) {
					$field_type = $cf_data['_field'];
				}

				// Format date values to YYYY-MM-DD
				if ( 'date' === strtolower( $field_type ) ) {
					$field_value = $this->format_date_value( $field_value );
				}

				// Convert special field types (checkbox to 1/0, etc.)
				$field_value = $this->convert_special_field_values( $field_value, $field_type );

				// Convert arrays to comma-separated strings
				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}

				$contact_fields[ $field_id ] = sanitize_text_field( $field_value );
			}
		}

		return $contact_fields;
	}

	/**
	 * Convert form field values to Keap API format based on field type.
	 *
	 * Different field types require different value formats for Keap's API:
	 *
	 * Checkboxes: Must be integer 1 (checked) or 0 (unchecked)
	 * - Special case: "GDPR ACCEPTED" string becomes 1
	 * - Empty/false values become 0
	 *
	 * Dropdowns/Radio: Must be string value (not array)
	 * - If form sends array, extract first value
	 *
	 * Number/Currency/Percent: Must be numeric string
	 * - Strip non-numeric characters except decimal and minus
	 * - Default to "0" if result is non-numeric
	 *
	 * This method ensures values match Keap's strict type requirements, preventing
	 * API errors like "Expected integer for checkbox field".
	 *
	 * @param mixed  $value      The field value to convert (string, array, or int).
	 * @param string $field_type The field type identifier (checkbox, dropdown, number, etc.).
	 *
	 * @return mixed The converted value formatted for Keap API requirements.
	 */
	private function convert_special_field_values( $value, $field_type = '' ) {
		// Handle GDPR consent special case
		if ( is_string( $value ) && 'GDPR ACCEPTED' === $value ) {
			return 1;
		}

		// Dropdown/Select fields: Convert array to string, trim whitespace
		$dropdown_types = array( 'mapping_dropdown', 'dropdown', 'select', 'mapping_select' );
		if ( in_array( $field_type, $dropdown_types, true ) ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				return trim( strval( $value[0] ) );
			}
			return is_string( $value ) ? trim( $value ) : strval( $value );
		}

		// Number/Currency/Percent fields: Strip formatting, ensure numeric
		$number_types = array( 'mapping_number', 'number', 'currency', 'percent' );
		if ( in_array( $field_type, $number_types, true ) ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				$value = $value[0];
			}

			// Remove currency symbols, commas, etc. Keep only digits, decimal, minus
			$cleaned_value = preg_replace( '/[^0-9.\-]/', '', strval( $value ) );

			return is_numeric( $cleaned_value ) ? $cleaned_value : '0';
		}

		// Checkbox fields: Convert to 1 or 0
		$checkbox_types = array( 'mapping_checkbox', 'checkbox' );
		if ( in_array( $field_type, $checkbox_types, true ) && ! empty( $value ) ) {
			return 1;
		}

		// Handle checkbox arrays (multi-checkbox groups)
		if ( is_array( $value ) && in_array( $field_type, $checkbox_types, true ) ) {
			$meaningful_values = array_filter(
				$value,
				function ( $item ) {
					return is_string( $item ) && ! empty( trim( $item ) );
				}
			);
			return ! empty( $meaningful_values ) ? 1 : $value;
		}

		// Radio buttons: Convert array to string
		$radio_types = array( 'mapping_radio', 'radio' );
		if ( in_array( $field_type, $radio_types, true ) ) {
			if ( is_array( $value ) && ! empty( $value ) ) {
				return trim( strval( $value[0] ) );
			}
			return is_string( $value ) ? trim( $value ) : strval( $value );
		}

		// Default: return value unchanged
		return $value;
	}

	/**
	 * Convert various date formats to Keap's required YYYY-MM-DD format.
	 *
	 * Keap's date fields require YYYY-MM-DD format. Forms may submit dates in many formats:
	 * - "Jan, 15, 2024" (text date picker)
	 * - "15/01/2024" (DD/MM/YYYY - European)
	 * - "01/15/2024" (MM/DD/YYYY - US)
	 * - "2024-01-15" (already correct)
	 *
	 * This method attempts to parse and convert all common formats. The conversion logic:
	 * 1. Check if already in YYYY-MM-DD and valid - return as-is
	 * 2. Try text format (Jan, 15, 2024)
	 * 3. Try DD/MM/YYYY format
	 * 4. Try MM/DD/YYYY format
	 * 5. Fall back to PHP's strtotime() for other formats
	 *
	 * Uses checkdate() to validate parsed dates before conversion to prevent invalid dates
	 * like "2024-02-30" from being accepted.
	 *
	 * @param string $date_string Date string in various formats.
	 *
	 * @return string Formatted date in YYYY-MM-DD format, or original string if parsing fails.
	 */
	private function format_date_value( $date_string ) {
		$formatted_date = '';

		if ( empty( $date_string ) ) {
			return $formatted_date;
		}

		// Check if already in YYYY-MM-DD format and valid
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_string ) ) {
			$date_parts = explode( '-', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[2], $date_parts[0] ) ) {
				return $date_string;
			}
		}

		// Try text format: "Jan, 15, 2024"
		if ( preg_match( '/[a-zA-Z]{3}, [0-9]{1,2}, [0-9]{4}/', $date_string ) ) {
			$date_string    = str_replace( ', ', '-', $date_string );
			$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
			return $formatted_date;
		}

		// Try DD/MM/YYYY format (European)
		if ( preg_match( '/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[1], $date_parts[0], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( str_replace( '/', '-', $date_string ) ) );
				return $formatted_date;
			}
		}

		// Try MM/DD/YYYY format (US)
		if ( preg_match( '/^(0?[1-9]|1[0-2])\/(0?[1-9]|[12][0-9]|3[01])\/\d{4}$/', $date_string ) ) {
			$date_parts = explode( '/', $date_string );
			if ( checkdate( $date_parts[0], $date_parts[1], $date_parts[2] ) ) {
				$formatted_date = gmdate( 'Y-m-d', strtotime( $date_string ) );
				return $formatted_date;
			}
		}

		// Fall back to strtotime() for other formats
		$timestamp = strtotime( $date_string );
		if ( false !== $timestamp ) {
			$formatted_date = gmdate( 'Y-m-d', $timestamp );
		} else {
			// If all parsing fails, return original string
			$formatted_date = $date_string;
		}

		return $formatted_date;
	}

	/**
	 * Build custom fields array from form editor mapping for contact.update call.
	 *
	 * Extracts ONLY custom fields (not standard contact fields) from the form submission
	 * and formats them for the contact.update API call.
	 *
	 * The key difference from get_basic_default_fields():
	 * - This method EXCLUDES standard contact fields (FirstName, City, etc.)
	 * - All custom field names are prefixed with underscore as required by contact.update
	 * - Only sends fields that have meaningful values (not empty strings or empty arrays)
	 *
	 * Empty value checking is important because sending empty strings to Keap can cause
	 * issues with certain field types. We only update fields that actually have data.
	 *
	 * @param array $args Form submission arguments with 'tve_mapping' base64-encoded data.
	 *
	 * @return array Custom fields formatted for contact.update (e.g., array( '_FieldName' => 'value' )).
	 */
	protected function build_mapped_custom_fields( $args ) {
		if ( empty( $args['tve_mapping'] ) ) {
			return array();
		}

		// Decode field mapping from form editor
		$mapped_data = thrive_safe_unserialize( base64_decode( $args['tve_mapping'] ) );
		if ( ! is_array( $mapped_data ) ) {
			return array();
		}

		$custom_fields = array();

		foreach ( $mapped_data as $cf_name => $cf_data ) {
			// Ensure $cf_data is array and has infusionsoft mapping
			if ( ! is_array( $cf_data ) || empty( $cf_data['infusionsoft'] ) ) {
				continue;
			}

			$field_id   = $cf_data['infusionsoft'];
			$clean_name = str_replace( '[]', '', $cf_name );

			// Check if field has a meaningful value (not empty string/array)
			$has_meaningful_value = false;
			if ( isset( $args[ $clean_name ] ) ) {
				if ( is_array( $args[ $clean_name ] ) ) {
					$meaningful_values = array_filter(
						$args[ $clean_name ],
						function ( $item ) {
							return is_string( $item ) && ! empty( trim( $item ) );
						}
					);
					$has_meaningful_value = ! empty( $meaningful_values );
				} else {
					$has_meaningful_value = ! empty( $args[ $clean_name ] );
				}
			}

			if ( $has_meaningful_value ) {
				$field_value = $args[ $clean_name ];

				// Determine field type from multiple possible keys
				$field_type = '';
				if ( isset( $cf_data['type'] ) ) {
					$field_type = $cf_data['type'];
				} elseif ( isset( $cf_data['_field_type'] ) ) {
					$field_type = $cf_data['_field_type'];
				} elseif ( isset( $cf_data['_field'] ) ) {
					$field_type = $cf_data['_field'];
				}

				// Format dates to YYYY-MM-DD
				if ( 'date' === strtolower( $field_type ) ) {
					$field_value = $this->format_date_value( $field_value );
				}

				// Convert special field types (checkbox to 1/0, etc.)
				$field_value = $this->convert_special_field_values( $field_value, $field_type );

				// Convert arrays to comma-separated strings
				if ( is_array( $field_value ) ) {
					$field_value = implode( ', ', $field_value );
				}

				$field_value = sanitize_text_field( $field_value );

				// Only include custom fields (exclude standard contact table fields)
				if ( ! in_array( $field_id, $this->_supported_contact_fields, true ) ) {
					// Prefix with underscore as required by contact.update API
					$custom_fields[ '_' . $field_id ] = $field_value;
				}
			}
		}

		return $custom_fields;
	}

	/**
	 * Get automator mapping fields
	 */
	public function get_automator_add_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'mailing_list', 'api_fields' ) );
	}

	/**
	 * Get automator tag mapping fields
	 */
	public function get_automator_tag_autoresponder_mapping_fields() {
		return array( 'autoresponder' => array( 'tag_select' ) );
	}

}

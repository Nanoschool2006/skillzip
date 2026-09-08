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
 * Class TD_REST_Hook_Controller
 *
 * Used to implement Zapier Integration but it can be extended
 * For further REST Hooks
 */
class TD_REST_Hook_Controller extends TD_REST_Controller {

	/**
	 * The base of this controller's route.
	 *
	 * @since 4.7.0
	 * @var string
	 */
	protected $rest_base;

	/**
	 * REST Hook Name
	 * - saves the webhook based on this name
	 *
	 * @var string
	 */
	protected $_hook_name;

	/**
	 * Needed to decide webhook's name
	 *
	 * @var string
	 */
	protected $_hook_prefix = 'td_';

	/**
	 * Needed to decide webhook's name
	 *
	 * @var string
	 */
	protected $_hook_suffix = '_webhook';

	/**
	 * TD_REST_Hook_Controller constructor.
	 *
	 * @param string $hook_name The hook name for the REST controller.
	 */
	public function __construct( $hook_name = '' ) {

		parent::__construct();

		$this->_hook_name = (string) $hook_name;
		$this->rest_base  = trailingslashit( $hook_name ) . 'subscription';
	}

	/**
	 * Register routes
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'subscribe' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'args'                => $this->route_args(),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/sample',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'sample' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/specific_form_data',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'specific_form_data' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'unsubscribe' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'args'                => $this->route_args(),
			)
		);

		register_rest_route(
			$this->namespace,
			$this->rest_base . '/all_lg_forms',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'all_lg_forms' ),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * The endpoint where the Integration subscribes the webhook
	 *
	 * @param WP_REST_Request $request The REST request object.
	 *
	 * @return array|WP_Error
	 */
	public function subscribe( $request ) {

		// Param from Lead Generation auth.
		$hook_url = $request->get_param( 'hook_url' );

		// Param from Contact Form auth.
		if ( ! $hook_url ) {
			$hook_url = $request->get_param( 'hookUrl' );
		}

		if ( filter_var( $hook_url, FILTER_VALIDATE_URL ) ) {
			update_option( $this->get_option_name(), $hook_url );

			$result = array(
				'id' => $this->get_option_name(),
			);
		} else {
			$result = new WP_Error( 'td_invalid_hook_url', __( 'Invalid Hook URL', 'thrive-dash' ) );
		}

		return $result;
	}

	/**
	 * The endpoint where the Integration unsubscribes the webhook
	 *
	 * @return true
	 */
	public function unsubscribe() {

		/**
		 * Mind that if option does not exist false is return by delete_option()
		 */
		delete_option( $this->get_option_name() );

		return true;
	}

	/**
	 * Required endpoint for creating the trigger in Zapier
	 * provide a sample of fields and data
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function sample() {

		// For LG Subscription.
		$response_sample = array(
			array(
				'name'              => 'Full name',
				'email'             => 'name@email.com',
				'phone'             => '1231231231',
				'ip_address'        => '192.168.1.1',
				'tags'              => array(
					'tag1',
					'tag2',
					'tag3',
				),
				'message'           => array(
					'message1',
					'message2',
					'message3',
				),
				'number'            => '123.45',
				'date'              => '24/09/2024',
				'website'           => 'https://yourwebsite.com/',
				'source_url'        => 'https://thrivethemes.com',
				'thriveleads_group' => 'Group 1',
				'thriveleads_type'  => 'Lightbox',
				'thriveleads_name'  => 'First Lightbox',
			),
		);

		// For CF Subscription [DEPRECATED should be removed when considering that old CF forms are no longer connected on the users side].
		if ( 'cf-optin' === $this->_hook_name ) {
			$response_sample = array(
				array(
					'first_name' => 'First name',
					'last_name'  => 'Last name',
					'full_name'  => 'Full name',
					'email'      => 'name@email.com',
					'message'    => 'Sample message',
					'phone'      => '1231231231',
					'website'    => 'https://yourwebsite.com/',
					'source_url' => 'https://thrivethemes.com',
					'ip_address' => '192.168.1.1',
					'tags'       => array( 'tag1', 'tag2', 'tag3' ),
				),
			);
		}

		return rest_ensure_response( $response_sample );
	}

	/**
	 * Required endpoint for creating the trigger in Zapier
	 * provide fields and data from specific form subscription
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function specific_form_data() {
		$response_array = array();
		$form_id        = ! empty( $_GET['form_id'] ) ? sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) : '';

		if ( ! empty( $form_id ) ) {
			global $wpdb;
			// First, try to get regular form data from postmeta table.
			$query   = $wpdb->prepare(
				"SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s",
				'_tve_lead_gen_form_' . $form_id
			);
			$results = $wpdb->get_results( $query, ARRAY_A );

			if ( ! empty( $results ) ) {
				// Handle regular forms (existing logic).
				foreach ( $results as $row ) {
					$meta_value = unserialize( $row['meta_value'] ) ?? [];
					$inputs     = $meta_value['inputs'] ?? [];

					// Format/Rename all the fields.
					$messages       = array();
					$checkbox_count = 1;
					$file_url_count = 1;
					foreach ( $inputs as $input ) {
						if ( strpos( $input['id'], 'mapping_textarea_' ) === 0 ) {
							$messages[] = $input['label'];
						} elseif ( strpos( $input['id'], 'mapping_checkbox_' ) === 0 ) {
							$response_array[ 'checkbox_' . $checkbox_count ] = $input['label'];
							++$checkbox_count;
						} elseif ( strpos( $input['id'], 'mapping_file_' ) === 0 ) {
							$response_array[ 'file_url_' . $file_url_count ] = $input['label'];
							++$file_url_count;
						} else {
							$response_array[ $input['id'] ] = $input['label'];
						}
					}

					if ( ! empty( $messages ) ) {
						$response_array['message'] = $messages;
					}
				}
			} else {
				// If not found in regular forms, try shortcode forms.
				$response_array = $this->get_shortcode_form_fields( $form_id );
			}
		}

		// For LG Subscription.
		$response_sepcific_form_data = array(
			$response_array,
		);

		return rest_ensure_response( $response_sepcific_form_data );
	}

	/**
	 * Uses hook's prefix, name, suffix to establish the option name
	 * to save the webhook
	 *
	 * @return string
	 */
	protected function get_option_name() {

		return $this->_hook_prefix . $this->_hook_name . $this->_hook_suffix;
	}


	/**
	 * Get list of all the LG forms in the site
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function all_lg_forms() {

		global $wpdb;
		$response_forms = [];

		// Part 1: Get regular lead generation forms.
		$query = $wpdb->prepare(
			"SELECT wpm.meta_key, wpm.meta_value, wp.post_title
			FROM $wpdb->postmeta as wpm, $wpdb->posts as wp
			WHERE wpm.post_id = wp.ID AND wpm.meta_key LIKE %s
			ORDER BY wp.post_title",
			'_tve_lead_gen_form_%'
		);

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $results ) ) {
			foreach ( $results as $row ) {
				$meta_value = thrive_safe_unserialize( $row['meta_value'] ) ?? [];
				$apis       = $meta_value['apis'] ?? [];
				if ( ! empty( $apis ) && in_array( 'zapier', $apis, true ) ) {
					$response_forms[] = [
						'id'   => $row['meta_key'] ? str_replace( '_tve_lead_gen_form_', '', $row['meta_key'] ) : '',
						'name' => $row['post_title'] ?? '',
					];
				}
			}
		}

		// Part 2: Get pages containing shortcode forms connected to Zapier.
		$shortcode_query = $wpdb->prepare(
			"SELECT p.ID, p.post_title, pm.meta_value
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id
			WHERE p.post_status = 'publish'
			AND pm.meta_key = %s
			AND pm.meta_value LIKE %s",
			'tve_content_before_more',
			'%__CONFIG_leads_shortcode__%'
		);

		$shortcode_results = $wpdb->get_results( $shortcode_query, ARRAY_A );

		if ( ! empty( $shortcode_results ) ) {
			foreach ( $shortcode_results as $page ) {
				// Extract shortcode IDs from meta value using the CONFIG pattern.
				preg_match_all( '/__CONFIG_leads_shortcode__\{"id":"(\d+)"\}__CONFIG_leads_shortcode__/', $page['meta_value'], $matches );

				if ( ! empty( $matches[1] ) ) {
					foreach ( $matches[1] as $shortcode_id ) {
						// Get both form ID and Zapier connection status in single query.
						$shortcode_data = $this->get_shortcode_data( $shortcode_id );

						if ( $shortcode_data['form_id'] && $shortcode_data['has_zapier'] ) {
							$response_forms[] = [
								'id'   => $shortcode_data['form_id'],
								'name' => $page['post_title'] . ' (Shortcode)',
							];
						}
					}
				}
			}
		}

		return rest_ensure_response( $response_forms );
	}

	/**
	 * Get shortcode data including form ID and Zapier connection status in a single query.
	 *
	 * @param int $shortcode_id The shortcode form ID.
	 * @return array Array with 'form_id' and 'has_zapier' keys
	 */
	private function get_shortcode_data( $shortcode_id ) {
		global $wpdb;

		$result = [
			'form_id'    => null,
			'has_zapier' => false,
		];

		// Single query to get all form content from variations table.
		$form_query    = $wpdb->prepare(
			"SELECT content FROM {$wpdb->prefix}tve_leads_form_variations WHERE post_parent = %d AND post_status = 'publish'",
			$shortcode_id
		);
		$form_contents = $wpdb->get_results( $form_query, ARRAY_A );

		if ( ! empty( $form_contents ) ) {
			// Loop through all variations to find form identifier and check Zapier connection.
			foreach ( $form_contents as $row ) {
				$content = $row['content'];

				// Extract form identifier if not already found.
				if ( ! $result['form_id'] && preg_match( '/form_identifier&quot;:&quot;(.*?)&quot;/', $content, $matches ) ) {
					$result['form_id'] = $matches[1];
				}

				// Check for Zapier connection if not already found.
				if ( ! $result['has_zapier'] && strpos( $content, '{&quot;zapier&quot;:null}' ) !== false ) {
					$result['has_zapier'] = true;
				}

				// Break early if we found both pieces of information.
				if ( $result['form_id'] && $result['has_zapier'] ) {
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Get form fields for shortcode forms by extracting from the variations table.
	 *
	 * @param string $form_id The form identifier to search for.
	 * @return array Array of form fields with their labels
	 */
	private function get_shortcode_form_fields( $form_id ) {
		global $wpdb;
		$response_array = array();

		// Query the variations table to find forms with matching form_identifier.
		$form_query    = $wpdb->prepare(
			"SELECT content FROM {$wpdb->prefix}tve_leads_form_variations
			WHERE post_status = 'publish'
			AND content LIKE %s",
			'%form_identifier&quot;:&quot;' . $form_id . '&quot;%'
		);
		$form_contents = $wpdb->get_results( $form_query, ARRAY_A );

		if ( ! empty( $form_contents ) ) {
			foreach ( $form_contents as $row ) {
				$content = $row['content'];

				// Extract form fields from the HTML content.
				$response_array = $this->extract_form_fields_from_html( $content );

				// Break after finding the first matching form as they should have the same fields.
				if ( ! empty( $response_array ) ) {
					break;
				}
			}
		}

		return $response_array;
	}

	/**
	 * Extract form field information from HTML content.
	 *
	 * @param string $html_content The HTML content to parse.
	 * @return array Array of form fields with their labels
	 */
	private function extract_form_fields_from_html( $html_content ) {
		$response_array = array();
		$messages       = array();
		$checkbox_found = false;
		$file_url_count = 1;

		// Decode HTML entities that are encoded in the content.
		$html_content = html_entity_decode( $html_content );

		// Create a DOMDocument to parse the HTML.
		$dom = new DOMDocument();
		// Suppress warnings for invalid HTML.
		libxml_use_internal_errors( true );

		// Load HTML content.
		$dom->loadHTML( $html_content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

		// Clear libxml errors.
		libxml_clear_errors();

		// Find all input elements.
		$inputs    = $dom->getElementsByTagName( 'input' );
		$textareas = $dom->getElementsByTagName( 'textarea' );
		$selects   = $dom->getElementsByTagName( 'select' );

		// Process input elements.
		foreach ( $inputs as $input ) {
			$name       = $input->getAttribute( 'name' );
			$type       = $input->getAttribute( 'type' );
			$data_field = $input->getAttribute( 'data-field' );

			// Skip submit buttons but include hidden fields.
			if ( in_array( $type, array( 'submit', 'button' ), true ) || empty( $name ) ) {
				continue;
			}

			$label = $this->get_field_label( $input );

			// Skip inputs without labels unless they are special fields.
			if ( empty( $label ) && ! in_array( $name, array( 'gdpr', 'user_consent' ), true ) && ! in_array( $data_field, array( 'avatar' ), true ) ) {
				continue;
			}

			// Handle different field types - check specific fields first before generic types.
			if ( 0 === strpos( $name, 'mapping_textarea_' ) ) {
				$messages[] = $label;
			} elseif ( 'gdpr' === $name || 'user_consent' === $name || 'gdpr' === $data_field ) {
				$response_array['gdpr'] = empty( $label ) ? 'GDPR' : $label;
			} elseif ( 'avatar' === $data_field || false !== strpos( $name, 'mapping_avatar' ) ) {
				$response_array['mapping_avatar_picker'] = empty( $label ) ? 'Avatar Picker' : $label;
			} elseif ( 0 === strpos( $name, 'mapping_checkbox_' ) || 'checkbox' === $type ) {
				// Only add one checkbox entry regardless of how many checkbox options exist.
				if ( ! $checkbox_found ) {
					$response_array['checkbox_1'] = empty( $label ) ? 'Checkbox' : $label;
					$checkbox_found               = true;
				}
			} elseif ( 0 === strpos( $name, 'mapping_file_' ) || 'file' === $type ) {
				$response_array[ 'file_url_' . $file_url_count ] = $label;
				++$file_url_count;
			} elseif ( 0 === strpos( $name, 'mapping_hidden_' ) || 'hidden' === $type ) {
				// Include hidden fields with their mapping name.
				if ( $label || 0 === strpos( $name, 'mapping_hidden_' ) ) {
					$response_array[ $name ] = empty( $label ) ? 'Hidden' : $label;
				}
			} else {
				// Map field name to standardized format.
				$field_key                    = $this->normalize_field_name( $name );
				$response_array[ $field_key ] = $label;
			}
		}

		// Process textarea elements.
		foreach ( $textareas as $textarea ) {
			$name = $textarea->getAttribute( 'name' );
			if ( empty( $name ) ) {
				continue;
			}

			$label = $this->get_field_label( $textarea );

			if ( $label ) {
				if ( 0 === strpos( $name, 'mapping_textarea_' ) ) {
					$messages[] = $label;
				} else {
					$field_key                    = $this->normalize_field_name( $name );
					$response_array[ $field_key ] = $label;
				}
			}
		}

		// Process select elements.
		foreach ( $selects as $select ) {
			$name = $select->getAttribute( 'name' );
			if ( empty( $name ) ) {
				continue;
			}

			$label = $this->get_field_label( $select );

			if ( $label ) {
				$field_key                    = $this->normalize_field_name( $name );
				$response_array[ $field_key ] = $label;
			}
		}

		// Add messages if any were found.
		if ( ! empty( $messages ) ) {
			$response_array['message'] = $messages;
		}

		return $response_array;
	}

	/**
	 * Get label for a form field element.
	 *
	 * @param DOMElement $element The form element.
	 * @return string|null The label text or null if not found
	 */
	private function get_field_label( $element ) {
		// Try to get label from data-name attribute first (preferred in Thrive).
		$label = $element->getAttribute( 'data-name' );
		if ( ! empty( $label ) ) {
			return $label;
		}

		// Try to get label from placeholder attribute.
		$label = $element->getAttribute( 'placeholder' );
		if ( ! empty( $label ) ) {
			return $label;
		}

		// Try to get label from aria-label attribute.
		$label = $element->getAttribute( 'aria-label' );
		if ( ! empty( $label ) ) {
			return $label;
		}

		// Try to find associated label element.
		$id = $element->getAttribute( 'id' );
		if ( ! empty( $id ) ) {
			$owner_document = $element->ownerDocument; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$xpath          = new DOMXPath( $owner_document );
			$label_elements = $xpath->query( "//label[@for='" . $id . "']" );
			if ( $label_elements->length > 0 ) {
				return trim( $label_elements->item( 0 )->textContent );
			}
		}

		return null;
	}

	/**
	 * Normalize field names to match expected format.
	 *
	 * @param string $field_name The original field name.
	 * @return string The normalized field name
	 */
	private function normalize_field_name( $field_name ) {
		// Common field name mappings - keep specific mapping patterns intact.
		$field_mappings = array(
			'mapping_email'      => 'email',
			'mapping_name'       => 'name',
			'mapping_phone'      => 'phone',
			'mapping_first_name' => 'first_name',
			'mapping_last_name'  => 'last_name',
			'mapping_website'    => 'website',
		);

		// Check for exact matches first.
		if ( isset( $field_mappings[ $field_name ] ) ) {
			return $field_mappings[ $field_name ];
		}

		// For fields that should keep their original format (like mapping_select_532, number_84, etc.).
		// Return the original name to preserve the numbering and specific mapping patterns.
		return $field_name;
	}
}

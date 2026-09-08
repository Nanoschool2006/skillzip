<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Public_API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * REST controller for the unified Thrive Themes public API.
 *
 * Exposes the cross-product API methods as REST endpoints under
 * the thrivethemes/v1 namespace with token-based authentication.
 *
 * Routes:
 * - POST /apprentice/access/grant     → grant product access
 * - POST /apprentice/access/revoke    → revoke product access
 * - GET  /apprentice/access/check     → check product access
 * - GET  /apprentice/course/completed → check course completion
 * - GET  /tags/check                  → check user tag
 * - POST /tags/add                   → add tag to user
 * - POST /tags/remove                → remove tag from user
 * - GET  /tags/list                  → list user tags
 * - POST /ultimatum/campaign/start    → start evergreen campaign
 */
class REST_Controller {
	const REST_NAMESPACE = 'thrivethemes/v1';

	/**
	 * Register all REST routes for the public API.
	 *
	 * @return void
	 */
	public function register_routes() {
		/* --- Apprentice: Access --- */
		register_rest_route( self::REST_NAMESPACE, '/apprentice/access/grant', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'grant_access' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'    => $this->get_positive_int_arg( true ),
				'product_id' => $this->get_positive_int_arg( true ),
				'source'     => [
					'type'              => 'string',
					'default'           => 'api',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/apprentice/access/revoke', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'revoke_access' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'    => $this->get_positive_int_arg( true ),
				'product_id' => $this->get_positive_int_arg( true ),
				'source'     => [
					'type'              => 'string',
					'default'           => 'api',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/apprentice/access/check', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'check_access' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'    => $this->get_positive_int_arg( true ),
				'product_id' => $this->get_positive_int_arg( true ),
			],
		] );

		/* --- Apprentice: Course --- */
		register_rest_route( self::REST_NAMESPACE, '/apprentice/course/completed', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'check_course_completed' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'   => $this->get_positive_int_arg( true ),
				'course_id' => $this->get_positive_int_arg( true ),
			],
		] );

		/* --- Tags (Thrive-wide user tags) --- */
		register_rest_route( self::REST_NAMESPACE, '/tags/check', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'check_tag' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'  => $this->get_positive_int_arg( true ),
				'tag_name' => $this->get_tag_name_arg(),
				'source'   => $this->get_tag_source_arg( false ),
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/tags/add', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'add_tag' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'  => $this->get_positive_int_arg( true ),
				'tag_name' => $this->get_tag_name_arg(),
				'source'   => $this->get_tag_source_arg( true ),
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/tags/remove', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'remove_tag' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'  => $this->get_positive_int_arg( true ),
				'tag_name' => $this->get_tag_name_arg(),
				'source'   => $this->get_tag_source_arg( false ),
			],
		] );

		register_rest_route( self::REST_NAMESPACE, '/tags/list', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'list_tags' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id' => $this->get_positive_int_arg( true ),
				'source'  => $this->get_tag_source_arg( false ),
			],
		] );

		/* --- Ultimatum: Campaign --- */
		register_rest_route( self::REST_NAMESPACE, '/ultimatum/campaign/start', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'start_campaign' ],
			'permission_callback' => [ $this, 'token_permission_check' ],
			'args'                => [
				'user_id'     => $this->get_positive_int_arg( true ),
				'campaign_id' => $this->get_positive_int_arg( true ),
			],
		] );
	}

	/**
	 * Token-based permission check using Dashboard API tokens.
	 *
	 * Accepts Basic Auth header, PHP_AUTH_PW, or ?auth= query param.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return true|WP_Error
	 */
	public function token_permission_check( $request ) {
		$header   = null;
		$auth_key = $request->get_param( 'auth' );

		if ( function_exists( 'getallheaders' ) ) {
			$all_header = getallheaders();
			$header     = ! empty( $all_header['Authorization'] ) ? $all_header['Authorization'] : null;
		}

		if ( ! $header ) {
			if ( ! empty( $_SERVER['PHP_AUTH_PW'] ) ) {
				$header = 'Basic ' . base64_encode( 'username:' . $_SERVER['PHP_AUTH_PW'] );
			} else {
				$header = $request->get_header( 'Authorization' );
			}
		}

		/* Treat ?auth= the same as PHP_AUTH_PW: wrap the raw token in Basic auth format */
		if ( empty( $header ) && ! empty( $auth_key ) ) {
			$header = 'Basic ' . base64_encode( 'token:' . $auth_key );
		}

		if ( empty( $header ) ) {
			return new WP_Error(
				'authentication_failure',
				__( 'Authentication failed due to invalid authentication credentials.', 'thrive-dash' ),
				[ 'status' => 401 ]
			);
		}

		$auth = base64_decode( str_replace( 'Basic ', '', $header ), true );

		if ( false === $auth || strpos( $auth, ':' ) === false ) {
			return new WP_Error(
				'authentication_failure',
				__( 'Authentication failed due to invalid authentication credentials.', 'thrive-dash' ),
				[ 'status' => 401 ]
			);
		}

		list( $username, $password ) = explode( ':', $auth, 2 );

		$username = trim( $username );
		$password = trim( $password );

		$has_access = TD_API_Token::auth( $username, $password );

		if ( ! $has_access ) {
			return new WP_Error(
				'not_authorized',
				__( 'Authorization failed due to insufficient permissions.', 'thrive-dash' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/* ------------------------------------------------------------------
	 * Apprentice callbacks
	 * ----------------------------------------------------------------*/

	/**
	 * Grant a user access to an Apprentice product.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function grant_access( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_Apprentice_API' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Apprentice is not active.', [ 'status' => 501 ] );
		}

		$result = \Thrive_Apprentice_API::grant_access(
			$request->get_param( 'user_id' ),
			$request->get_param( 'product_id' ),
			$request->get_param( 'source' ) ?? 'api'
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Revoke a user's access to an Apprentice product.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function revoke_access( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_Apprentice_API' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Apprentice is not active.', [ 'status' => 501 ] );
		}

		$result = \Thrive_Apprentice_API::revoke_access(
			$request->get_param( 'user_id' ),
			$request->get_param( 'product_id' ),
			$request->get_param( 'source' ) ?? 'api'
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Check if a user has access to an Apprentice product.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function check_access( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_Apprentice_API' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Apprentice is not active.', [ 'status' => 501 ] );
		}

		$has_access = \Thrive_Apprentice_API::user_has_access(
			$request->get_param( 'user_id' ),
			$request->get_param( 'product_id' )
		);

		return new WP_REST_Response( [ 'result' => $has_access ], 200 );
	}

	/**
	 * Check if a user has completed an Apprentice course.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function check_course_completed( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_Apprentice_API' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Apprentice is not active.', [ 'status' => 501 ] );
		}

		$completed = \Thrive_Apprentice_API::user_completed_course(
			$request->get_param( 'user_id' ),
			$request->get_param( 'course_id' )
		);

		return new WP_REST_Response( [ 'result' => $completed ], 200 );
	}

	/* ------------------------------------------------------------------
	 * Tags callbacks (Thrive-wide user tags via Thrive_User_Tags)
	 * ----------------------------------------------------------------*/

	/**
	 * Check if a user has a specific tag.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function check_tag( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_User_Tags' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Dashboard is not active.', [ 'status' => 501 ] );
		}

		$has_tag = \Thrive_User_Tags::has_tag(
			$request->get_param( 'user_id' ),
			$request->get_param( 'tag_name' ),
			(string) $request->get_param( 'source' )
		);

		return new WP_REST_Response( [ 'result' => $has_tag ], 200 );
	}

	/**
	 * Add a tag to a user.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_tag( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_User_Tags' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Dashboard is not active.', [ 'status' => 501 ] );
		}

		$result = \Thrive_User_Tags::add_tag(
			$request->get_param( 'user_id' ),
			$request->get_param( 'tag_name' ),
			$request->get_param( 'source' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * Remove a tag from a user.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_tag( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_User_Tags' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Dashboard is not active.', [ 'status' => 501 ] );
		}

		$result = \Thrive_User_Tags::remove_tag(
			$request->get_param( 'user_id' ),
			$request->get_param( 'tag_name' ),
			(string) $request->get_param( 'source' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/**
	 * List all tags for a user.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_tags( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_User_Tags' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Dashboard is not active.', [ 'status' => 501 ] );
		}

		$tags = \Thrive_User_Tags::get_tags(
			$request->get_param( 'user_id' ),
			(string) $request->get_param( 'source' )
		);

		return new WP_REST_Response( [ 'result' => $tags ], 200 );
	}

	/* ------------------------------------------------------------------
	 * Ultimatum callbacks
	 * ----------------------------------------------------------------*/

	/**
	 * Start an evergreen Ultimatum campaign for a user.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function start_campaign( WP_REST_Request $request ) {
		if ( ! class_exists( 'Thrive_Ultimatum_API' ) ) {
			return new WP_Error( 'plugin_inactive', 'Thrive Ultimatum is not active.', [ 'status' => 501 ] );
		}

		$result = \Thrive_Ultimatum_API::start_campaign(
			$request->get_param( 'user_id' ),
			$request->get_param( 'campaign_id' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( [ 'success' => true ], 200 );
	}

	/* ------------------------------------------------------------------
	 * Helpers
	 * ----------------------------------------------------------------*/

	/**
	 * Get the arg definition for a required positive integer parameter.
	 *
	 * @param bool $required Whether the parameter is required.
	 *
	 * @return array
	 */
	private function get_positive_int_arg( bool $required = true ): array {
		return [
			'required'          => $required,
			'type'              => 'integer',
			'validate_callback' => static function ( $value ) {
				return is_numeric( $value ) && (int) $value > 0;
			},
			'sanitize_callback' => 'absint',
		];
	}

	/**
	 * Get the arg definition for the tag_name parameter.
	 *
	 * @return array
	 */
	private function get_tag_name_arg(): array {
		return [
			'required'          => true,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => static function ( $value ) {
				return is_string( $value ) && trim( $value ) !== '';
			},
		];
	}

	/**
	 * Get the arg definition for the tag source parameter.
	 *
	 * Accepts any value from Thrive_User_Tags::VALID_SOURCES
	 * (e.g. 'thrive-leads', 'thrive-quiz-builder', 'thrive-apprentice').
	 *
	 * @param bool $required Whether the parameter is required.
	 *
	 * @return array
	 */
	private function get_tag_source_arg( bool $required = true ): array {
		$arg = [
			'required'          => $required,
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => static function ( $value ) use ( $required ) {
				if ( ! $required && ( $value === '' || $value === null ) ) {
					return true;
				}

				if ( ! class_exists( 'Thrive_User_Tags' ) ) {
					return true; // Defer to callback for proper error.
				}

				return in_array( $value, \Thrive_User_Tags::VALID_SOURCES, true );
			},
		];

		if ( ! $required ) {
			$arg['default'] = '';
		}

		return $arg;
	}
}

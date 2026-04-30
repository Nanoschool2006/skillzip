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
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * REST controller for managing API tokens (admin CRUD).
 *
 * Routes:
 * - GET    /td/v1/api-tokens              -> list all tokens
 * - GET    /td/v1/api-tokens/generate     -> generate a new key (no save)
 * - POST   /td/v1/api-tokens              -> create a token
 * - DELETE /td/v1/api-tokens/{id}         -> delete a token
 * - PATCH  /td/v1/api-tokens/{id}         -> update a token (name, status)
 */
class TD_API_Tokens_Controller extends \WP_REST_Controller {

	/**
	 * @var string
	 */
	protected $namespace = 'td/v1';

	/**
	 * Register routes for token CRUD operations.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/api-tokens',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/api-tokens/generate',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'generate_item' ),
				'permission_callback' => array( $this, 'permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/api-tokens/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Fetch all tokens from DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {

		$tokens = TD_API_Token::get_items();

		return rest_ensure_response( $tokens );
	}

	/**
	 * Save a new API token to DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ) {

		$token = new TD_API_Token( array( 'name' => $request->get_param( 'name' ) ) );

		if ( ! is_wp_error( $token->save() ) ) {
			return rest_ensure_response( $token->get_data() );
		}

		return new WP_Error( 'cannot_create_token', esc_html__( 'Token could not be created', 'thrive-dash' ), array( 'status' => 500 ) );
	}

	/**
	 * Delete a token from DB.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {

		$id    = (int) $request->get_param( 'id' );
		$token = new TD_API_Token( $id );

		if ( ! $token->get_id() ) {
			return new WP_Error( 'token_not_found', esc_html__( 'Token not found', 'thrive-dash' ), array( 'status' => 404 ) );
		}

		$result = $token->delete();

		if ( true === $result ) {
			return new WP_REST_Response( true, 200 );
		}

		return new WP_Error( 'token_not_deleted', esc_html__( 'Token item could not be deleted', 'thrive-dash' ), array( 'status' => 500 ) );
	}

	/**
	 * Update a token (name, status).
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ) {

		$id    = (int) $request->get_param( 'id' );
		$token = new TD_API_Token( $id );

		if ( ! $token->get_id() ) {
			return new WP_Error( 'token_not_found', esc_html__( 'Token not found', 'thrive-dash' ), array( 'status' => 404 ) );
		}

		if ( $request->has_param( 'name' ) ) {
			$token->name = $request->get_param( 'name' );
		}

		if ( $request->has_param( 'status' ) ) {
			$result = (int) $request->get_param( 'status' ) ? $token->enable() : $token->disable();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return rest_ensure_response( $token->get_data() );
		}

		if ( true === $token->save() ) {
			return rest_ensure_response( $token->get_data() );
		}

		return new WP_Error(
			'token_not_updated',
			esc_html__( 'Token could not be updated', 'thrive-dash' ),
			array( 'status' => 500 )
		);
	}

	/**
	 * Generate a new API token key without saving to DB.
	 *
	 * @return WP_REST_Response
	 */
	public function generate_item() {

		$token = new TD_API_Token( array() );

		return rest_ensure_response( $token->get_data() );
	}

	/**
	 * Check if user has permission to manage tokens.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function permission_check( $request ) {

		if ( false === current_user_can( 'manage_options' ) ) {

			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the resource.', 'thrive-dash' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}

		return true;
	}

	/**
	 * Return 401 or 403 status code based on login state.
	 *
	 * @return int
	 */
	public function authorization_status_code() {

		return is_user_logged_in() ? 403 : 401;
	}
}

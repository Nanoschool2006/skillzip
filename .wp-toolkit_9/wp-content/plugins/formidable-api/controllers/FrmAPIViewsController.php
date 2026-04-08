<?php

class FrmAPIViewsController extends WP_REST_Posts_Controller {

	public function __construct( $post_type ) {
		parent::__construct( $post_type );
		$this->rest_base = 'views';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$posts_args = $this->get_shortcode_args();

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'            => $posts_args,
				),

				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		$api_atts = array(
			'get_callback' => array( $this, 'prepare_content_response' ),
			'schema' => array(
				'description' => 'The content for the object.',
				'type'        => 'object',
				'properties'  => array(
					'rendered' => array(
						'description' => 'Content for the object, transformed for display.',
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
		);
		if ( function_exists( 'register_rest_field' ) ) {
			register_rest_field( $this->post_type, 'renderedHtml', $api_atts );
		} else {
			register_api_field( $this->post_type, 'renderedHtml', $api_atts );
		}
	}

	protected function get_shortcode_args() {
		$posts_args = array(
			'page'                  => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'page_size'             => array(
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'order'                 => array(
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order_by'              => array(
				'default'           => 'created_at',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'limit'                 => array(
				'default'           => 30,
				'sanitize_callback' => 'absint',
			),
			'filter'                => array(
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
		);
		/* 'user_id' => false, 'drafts' => false,*/
		return $posts_args;
	}

	protected function prepare_shortcode_args() {
		$default_params = $this->get_shortcode_args();
		$shortcode_params = (array) $_GET;
		foreach ( $shortcode_params as $type => $value ) {
			if ( isset( $default_params[ $type ] ) ) {
				if ( ! empty( $default_params[ $type ]['sanitize_callback'] ) ) {
					$shortcode_params[ $type ] = call_user_func( $default_params[ $type ]['sanitize_callback'], $value, $this, $type );
				}
			} else {
				unset( $shortcode_params[ $type ] );
			}
		}

		return $shortcode_params;
	}

	public function prepare_content_response( $object, $field_name, $request ) {
		$shortcode_params = $this->prepare_shortcode_args();
		$shortcode_params['id'] = $object['id'];

		if ( isset( $shortcode_params['page'] ) ) {
			$_GET[ 'frm-page-' . $object['id'] ] = $shortcode_params['page'];
		}

		if ( apply_filters( 'frm_api_include_view_scripts', true, $shortcode_params ) ) {
			FrmAPIAppController::include_scripts( $request );
		}

		if ( is_callable( 'FrmViewsDisplaysController::get_shortcode' ) ) {
			$object = FrmViewsDisplaysController::get_shortcode( $shortcode_params );
		} else {
			$object = FrmProDisplaysController::get_shortcode( $shortcode_params );
		}

		// remove the urls from pagination, calendar, and detail links
		$current_link = sanitize_text_field( $_SERVER['REQUEST_URI'] );
		if ( strpos( $current_link, '?' ) ) {
			list( $current_link, $query ) = explode( '?', sanitize_text_field( $_SERVER['REQUEST_URI'] ) );
		}
		$object = str_replace( $current_link . '?', '?', $object );

		return $object;
	}

	/**
	 * Check if a given post type should be viewed or managed.
	 *
	 * @param object|string $post_type
	 * @return bool Is post type allowed?
	 */
	protected function check_is_post_type_allowed( $post_type ) {
		return true;
	}
}

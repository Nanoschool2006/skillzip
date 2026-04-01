<?php

/**
 * @since 1.15
 */
class FrmAPIStatsController extends WP_REST_Controller {

	protected $rest_base = 'stats';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 1.15
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->rest_base . '/(?P<type>[\w-]+)/(?P<field_id>[\w,-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats_for_field' ),
					'permission_callback' => array( $this, 'get_stats_permissions_check' ),
				),
			)
		);
	}

	/**
	 * @since 1.15
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|true
	 */
	public function get_stats_permissions_check( $request ) {
		if ( ! current_user_can( 'frm_view_entries' ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view stats', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Returns statistics for field(s).
	 *
	 * @since 1.15
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_stats_for_field( $request ) {
		$params = $request->get_params();

		$field_ids = explode( ',', $params['field_id'] );

		$data = array();
		unset( $params['field_id'] );

		foreach ( $field_ids as $field_id ) {
			$params['id'] = $field_id;
			$data[ $field_id ] = FrmProStatisticsController::stats_shortcode( $params );
		}

		$response = rest_ensure_response( $data );

		return $response;
	}

}

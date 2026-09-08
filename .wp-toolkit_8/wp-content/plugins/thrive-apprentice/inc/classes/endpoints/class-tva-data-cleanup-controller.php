<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_Data_Cleanup_Controller
 *
 * REST API controller for data cleanup operations
 */
class TVA_Data_Cleanup_Controller extends TVA_REST_Controller {

	/**
	 * @var string
	 */
	public $base = 'data-cleanup';

	/**
	 * Register Routes
	 */
	public function register_routes() {

		// Run cleanup
		register_rest_route(
			static::$namespace . static::$version,
			'/' . $this->base . '/run',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'run_cleanup' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => [
						'days' => [
							'required'          => true,
							'type'              => 'integer',
							'minimum'           => TVA_Data_Cleanup::MIN_DAYS,
							'description'       => 'Number of days - data older than this will be deleted',
							'sanitize_callback' => 'absint',
							'validate_callback' => [ $this, 'validate_days' ],
						],
					],
				],
			]
		);

		// Preview what would be deleted
		register_rest_route(
			static::$namespace . static::$version,
			'/' . $this->base . '/preview',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'preview_cleanup' ],
					'permission_callback' => [ $this, 'admin_permissions_check' ],
					'args'                => [
						'days' => [
							'required'          => true,
							'type'              => 'integer',
							'minimum'           => TVA_Data_Cleanup::MIN_DAYS,
							'description'       => 'Number of days - data older than this will be counted',
							'sanitize_callback' => 'absint',
							'validate_callback' => [ $this, 'validate_days' ],
						],
					],
				],
			]
		);
	}

	/**
	 * Run the cleanup process
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function run_cleanup( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		try {
			$cleanup = new TVA_Data_Cleanup( $days );
			$results = $cleanup->run();

			return new WP_REST_Response(
				[
					'success' => true,
					'message' => sprintf(
						/* translators: %d: number of deleted records */
						__( 'Cleanup complete. %d records deleted.', 'thrive-apprentice' ),
						$results['total_deleted']
					),
					'data'    => $results,
				],
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'cleanup_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Preview what would be deleted
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function preview_cleanup( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		try {
			$cleanup = new TVA_Data_Cleanup( $days );
			$preview = $cleanup->preview();

			return new WP_REST_Response(
				[
					'success' => true,
					'data'    => $preview,
				],
				200
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'preview_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Validate the days parameter
	 *
	 * @param mixed           $value
	 * @param WP_REST_Request $request
	 * @param string          $param
	 *
	 * @return bool|WP_Error
	 */
	public function validate_days( $value, $request, $param ) {
		$days = absint( $value );

		if ( $days < TVA_Data_Cleanup::MIN_DAYS ) {
			return new WP_Error(
				'invalid_days',
				sprintf(
					/* translators: %d: minimum number of days */
					__( 'Days must be at least %d to prevent accidental deletion of recent data.', 'thrive-apprentice' ),
					TVA_Data_Cleanup::MIN_DAYS
				),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Check if current user has admin permissions
	 *
	 * @return bool
	 */
	public function admin_permissions_check() {
		return current_user_can( 'manage_options' );
	}
}


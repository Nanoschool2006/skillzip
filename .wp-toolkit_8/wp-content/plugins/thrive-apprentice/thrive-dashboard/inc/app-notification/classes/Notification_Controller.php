<?php
/**
 * Notification Controller Class
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'TD_Notification_Controller' ) ) {


	/**
	 * Notification Controller Class.
	 *
	 * @since 1.0.0
	 */
	class TD_Notification_Controller {
		/**
		 * REST namespace for the controller.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		const REST_NAMESPACE = 'tve-dash/v1';

		/**
		 * Registers REST API routes for the controller.
		 *
		 * @since 1.0.0
		 */
		public function register_routes() {
			register_rest_route( static::REST_NAMESPACE, 'app-notification/dismiss', array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dismiss_notification' ),
					'args'                => array(
						'notification_id' => array(
							'type'     => 'integer || string',
							'required' => true,
						)
					),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			) );

			register_rest_route( static::REST_NAMESPACE, 'app-notification/dismiss-all', array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'dismiss_all_notification' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				),
			) );
		}

		/**
		 * Dismisses a notification.
		 *
		 * @param WP_REST_Request $request The request object.
		 *
		 * @return WP_REST_Response Response object indicating success or failure.
		 * @since 1.0.0
		 *
		 */
		public function dismiss_notification( $request ) {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "Notifications.php";
			global $wpdb;
			$notification_id = intval( $request->get_param( 'notification_id' ) );

			$updated = $wpdb->update( $wpdb->prefix . 'td_app_notifications', [ 'dismissed' => 1 ],
				[ 'notification_id' => $notification_id ] );

			if ( ! $updated ) {
				return new WP_REST_Response( array( 'success' => false ), 403 );
			}

			( new TD_Notifications() )->update_transients();

			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		/**
		 * Dismisses all notifications.
		 *
		 * @return WP_REST_Response Response object indicating success or failure.
		 * @since 1.0.0
		 *
		 */
		public function dismiss_all_notification() {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "Notifications.php";
			global $wpdb;
			$wpdb->update( $wpdb->prefix . 'td_app_notifications', [ 'dismissed' => 1 ], [ 'dismissed' => 0 ] );

			( new TD_Notifications() )->update_transients();

			return new WP_REST_Response( array( 'success' => true ), 200 );
		}
	}
}

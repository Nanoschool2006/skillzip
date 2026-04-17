<?php

class TD_Notifications {
	private $_url;
	private $_connection;
	private $_request_params;

	public function __construct() {
		$this->_connection     = TD_TTW_Connection::get_instance();
		$this->_request_params = [
			'user_id'              => $this->_connection->ttw_id,
			'user_email'           => $this->_connection->ttw_email,
			'last_notification_id' => get_option( 'td_last_notification_id' ),
		];

        $this->_url = TD_TTW_Connection::get_ttw_url() . '/wp-content/notifications.json';
	}

	public function handle_notifications() {
		if ( $this->is_transients_exist() ) {
			return;
		}

		$notifications = $this->get( $this->_request_params );

		if ( ! $notifications || ! is_array( $notifications ) || is_wp_error( $notifications ) ) {
			return;
		}

		foreach ( $notifications as $notification ) {
			if ( $this->is_exist( $notification['notification_id'] ) || ( isset( $notification['end'] ) && $this->is_expired( $notification['end'] ) ) ) {
				continue;
			}

			$this->add_notification( $notification );
		}
	}

	public function check_notification() {
		$this->handle_notifications();

		$notifications = $this->get_notification();

		return $notifications;
	}

	private function get_notification( $update_call = false ) {
		global $wpdb;
		$table_name   = $wpdb->prefix . 'td_app_notifications';
		$current_time = current_time( 'Y-m-d H:i:s' );

		if ( $this->is_transients_exist() && ! $update_call ) {
			return get_transient( 'td_app_notifications_transients' );
		}

		$active_notifications_query = $wpdb->prepare(
			"SELECT * FROM $table_name
			 WHERE start <= %s
		   		AND (end IS NULL OR end >= %s)
	        	AND dismissed = 0
			 ORDER BY start DESC",
			$current_time, $current_time
		);

		$active_notifications = $wpdb->get_results( $active_notifications_query, ARRAY_A );

		$dismissed_notifications_query = $wpdb->prepare( " SELECT * 
											    FROM $table_name 
											    WHERE dismissed = 1
											    AND (end IS NULL OR end >= %s)
											    ORDER BY start DESC
											", $current_time );

		$dismissed_notifications = $wpdb->get_results( $dismissed_notifications_query, ARRAY_A );

		if ( ! $active_notifications && ! $dismissed_notifications ) {
			return false;
		}

		$notifications = [
			'active'    => $active_notifications,
			'dismissed' => $dismissed_notifications
		];

		if ( ! $update_call ) {
			$this->store_transients( $notifications );
		}

		return $notifications;
	}

	private function add_notification( $notification ) {
		if ( ! $notification || ! is_array( $notification ) || empty( $notification ) ) {
			return false;
		}

		if ( isset( $notification['have_license'] ) && is_array( $notification['have_license'] ) ) {
			if ( ! $this->verify_access( $notification['have_license'], $notification['dont_have_license'] ) ) {
				return;
			}
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'td_app_notifications';

		if ( isset( $notification['btns'] ) ) {
			foreach ( $notification['btns'] as $key => $value ) {
				if ( $key === 'main' ) {
					$notification['button1_label']  = sanitize_text_field( $value['text'] );
					$notification['button1_action'] = esc_url_raw( $value['url'] );
				}
				if ( $key === 'alt' ) {
					$notification['button2_label']  = sanitize_text_field( $value['text'] );
					$notification['button2_action'] = esc_url_raw( $value['url'] );
				}
			}
		}

		if ( isset( $notification['btns'] ) ) {
			unset( $notification['btns'] );
		}

		unset( $notification['have_license'] );
		unset( $notification['dont_have_license'] );

		$additional_data = [
			'created' => current_time( 'Y-m-d H:i:s' ),
			'updated' => current_time( 'Y-m-d H:i:s' ),
		];

		$notification = array_merge( $notification, $additional_data );

		$last_notification_id = get_option( 'td_last_notification_id', 0 );
		if ( isset( $notification['notification_id'] ) && $last_notification_id < $notification['notification_id'] ) {
			update_option( 'td_last_notification_id', $notification['notification_id'] );
		}

		$wpdb->insert( $table_name, $notification );
	}

	private function verify_access( $have_license, $dont_have_license ) {

		if ( ! is_array( $have_license ) && ! is_array( $dont_have_license ) ) {
			return true;
		}

		$user_products = TD_Ian_Helper::get_user_product_ids();


		if ( ! empty( $have_license ) && ! empty( $dont_have_license ) ) {
			$has_any_license           = ! empty( array_intersect( $user_products, $have_license ) );
			$has_no_restricted_license = empty( array_intersect( $user_products, $dont_have_license ) );

			return $has_any_license || $has_no_restricted_license;
		}

		if ( ! empty( $have_license ) ) {
			return ! empty( array_intersect( $user_products, $have_license ) );
		}

		if ( ! empty( $dont_have_license ) ) {
			return ! empty( array_intersect( $user_products, $dont_have_license ) );
		}

		return true;
	}

	// Check if the notification is already expired or not
	private function is_expired( $end ) {
		if ( ! $end || empty( $end ) ) {
			return false;
		}

		$current_time = strtotime( current_time( 'Y-m-d H:i:s' ) );

		return $current_time > strtotime( $end );
	}

	// Retrieve data from the server using the REST API
	private function get( $args = [] ) {
		// Check for cached fallback response from previous 403 error
		$fallback_cache = get_transient( 'td_notifications_403_fallback' );
		if ( $fallback_cache !== false ) {
			return [];
		}

		// Build URL with query parameters instead of body (standard HTTP practice for GET)
		$url = $this->_url;
		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		$request_params = [
			'headers'   => [
				'User-Agent' => 'WordPress',
			],
			'timeout'   => 30,
			'sslverify' => false,
		];

		$notifications = wp_remote_get( $url, $request_params );

		if ( is_wp_error( $notifications ) ) {
			return [];
		}

		$response_code = wp_remote_retrieve_response_code( $notifications );

		// Handle 403 Forbidden errors with caching fallback
		if ( $response_code === 403 ) {
			// Cache empty response for 6 hours to prevent repeated failures
			set_transient( 'td_notifications_403_fallback', [], 21600 );
			return [];
		}

		if ( $response_code !== 200 ) {
			return [];
		}

		// Clear any cached fallback on successful request
		delete_transient( 'td_notifications_403_fallback' );

		if ( is_wp_error( $notifications ) ) {
			return false;
		}
		$notifications = json_decode( wp_remote_retrieve_body( $notifications ), true );

		if ( ! is_array( $notifications ) ) {
			return [];
		}

		return $notifications;
	}

	// Check if the notification already exists in the database
	private function is_exist( $notification_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'td_app_notifications';

		$notification = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE notification_id = %d",
			$notification_id ) );

		if ( $notification ) {
			return true;
		}

		return false;
	}

	// Check if the transients already exist
	private function is_transients_exist() {
		$transient_name = 'td_app_notifications_transients';
		$transient      = get_transient( $transient_name );

		if ( $transient ) {
			return true;
		}

		return false;
	}

	// Store the transients
	private function store_transients( $notifications ) {
		if ( ! $notifications || ! is_array( $notifications ) || is_wp_error( $notifications ) ) {
			return;
		}

		set_transient( 'td_app_notifications_transients', $notifications, time() + DAY_IN_SECONDS );
	}

	public function update_transients() {
		$this->store_transients( $this->get_notification( true ) );
	}
}

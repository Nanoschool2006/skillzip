<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}
if ( ! class_exists( 'App_Notification' ) ) {
	class App_Notification {
		protected $namespace = 'tve-dash/v1';
		protected $class_path;
		protected $base_path;
		protected $template_path;
		protected $current_page;
		/**
		 * @var App_Notification
		 */
		public static $_instance;

		/**
		 * App_Notification constructor.
		 */
		private function __construct() {
			$this->base_path     = dirname( dirname( __FILE__ ) );
			$this->class_path    = $this->base_path . '/classes';
			$current_page        = isset( $_GET['page'] ) ? $_GET['page'] : '';
			$this->current_page  = isset( $_GET['action'] ) ? $_GET['action'] : $current_page;
			$this->template_path = $this->base_path . '/templates';
			$this->_load();
			$this->_hooks();
		}

		/**
		 * Singleton instance
		 *
		 * @return App_Notification
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Enqueue notification scripts
		 */
		public function tve_dash_enqueue_notification_scripts() {

			$limit         = 10;
			$offset        = 0;
			$js_prefix     = tve_dash_is_debug_on() ? '.js' : '.min.js';
			$notifications = ( new TD_Notifications )->check_notification();
			$product_key   = TD_Ian_Helper::get_product_name_by_page( $this->current_page );
			$notify_class  = TD_Ian_Helper::class_by_product( $product_key );
			$image_url     = TVE_DASH_URL . '/css/images/No-notifications-Thrive.png';


			$params = array(
				'baseUrl'       => tva_get_rest_route_url( $this->namespace, 'app-notification' ),
				't'             => include $this->path( 'i18n.php' ),
				'dash_url'      => admin_url( 'admin.php?page=tve_dash_section' ),
				'image_url'     => $image_url,
				'data'          => $notifications,
				'limit'         => $limit,
				'offset'        => $offset + $limit,
				'current_page'  => $this->current_page,
				'notify_class'  => $notify_class,
				'date_time_now' => current_time( 'Y-m-d H:i:s ' ),
				'dismiss_nonce' => wp_create_nonce( 'wp_rest' ),
			);


			tve_dash_enqueue_style( 'tve_dash_app_notification',
				TVE_DASH_URL . '/inc/app-notification/assets/css/tve-app-notification.css' );

			tve_dash_enqueue_script( 'tve_dash_app_notification',
				TVE_DASH_URL . '/inc/app-notification/assets/dist/tve-app-notification' . $js_prefix, array(
					'jquery',
					'backbone',
				), TVE_DASH_VERSION, true );
			tve_dash_admin_enqueue_scripts( 'tve_dash_app_notification' );
			wp_enqueue_style( 'wp-block-library' );
			wp_enqueue_style( 'wp-block-library-theme' );

			wp_localize_script( 'tve_dash_app_notification', 'TD_Notification', $params );
		}

		/**
		 * Check if the current page is the editor
		 *
		 * @return bool
		 */
		private function isEditor() {
			return 'architect' === $this->current_page;
		}

		private function is_ttb() {
			return 'thrive-theme-dashboard' === $this->current_page;
		}

		/**
		 * Enqueue notification icons
		 */
		public function tve_dash_enqueue_notification_icons() {
			$icon_file_path = TVE_DASH_PATH . '/inc/app-notification/assets/css/notification-icons.svg';
			if ( file_exists( $icon_file_path ) ) {
				include_once $icon_file_path;
			}
		}

		/**
		 * Register REST routes
		 */
		public static function register_rest_routes() {
			require_once dirname( __FILE__ ) . DIRECTORY_SEPARATOR . "Notification_Controller.php";
			$notificationController = new TD_Notification_Controller();
			$notificationController->register_routes();
		}

		/**
		 * Load required files
		 *
		 * @param string $path
		 */
		private function _load( $path = '' ) {

			$path = $path ? $path : $this->class_path;
			$dir  = new DirectoryIterator( $path );

			foreach ( $dir as $file ) {

				if ( $file->isDot() ) {
					continue;
				}

				if ( file_exists( $file->getPathname() ) && $file->isFile() ) {
					require_once( $file->getPathname() );
				}
			}
			// Run the migration if db tables are not created
			TD_DbMigration::migrate();

			if ( ! wp_next_scheduled( 'delete_expired_notice_daily' ) ) {
				wp_schedule_event( time(), 'daily', 'delete_expired_notice_daily' );
			}

			add_action( 'delete_expired_notice_daily', [ $this, 'delete_expired_notices' ] );
		}

		public function delete_expired_notices() {
			global $wpdb;
			$current_time = current_time( 'Y-m-d H:i:s' );

			$expired_notices_query = $wpdb->prepare( "
		        SELECT ID
		        FROM {$wpdb->prefix}td_app_notifications
		        WHERE end < %s
		    ", $current_time );

			$expired_notices = $wpdb->get_results( $expired_notices_query );

			foreach ( $expired_notices as $notice ) {
				$wpdb->delete( $wpdb->prefix . 'td_app_notifications', array( 'ID' => $notice->ID ) );
			}
		}

		/**
		 * Get the full path to a file
		 *
		 * @param string $file
		 *
		 * @return string
		 */
		private function path( $file = '' ) {
			return untrailingslashit( $this->base_path ) . ( ! empty( $file ) ? DIRECTORY_SEPARATOR : '' ) . ltrim( $file,
					'\\/' );
		}

		/**
		 * Hook into WordPress actions and filters
		 */
		public function _hooks() {
			add_filter( 'tve_dash_admin_product_menu', array( $this, 'add_to_dashboard_menu' ) );

			if ( $this->isEditor() ) {
				add_action( 'wp_loaded', array( $this, 'tve_dash_enqueue_notification_scripts' ) );
				add_action( 'tcb_editor_iframe_after', array( $this, 'include_notification_icons_svg' ) );
			} else {
				add_action( 'admin_enqueue_scripts', array( $this, 'tve_dash_enqueue_notification_scripts' ) );
			}

			add_action( 'admin_enqueue_scripts', array( $this, 'tve_dash_enqueue_notification_icons' ) );
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
			add_action( 'tvd_notification_inbox', array( $this, 'notification_button' ) );
		}

		public function include_notification_icons_svg() {
			$file_path = TVE_DASH_PATH . '/inc/app-notification/assets/css/notification-icons.svg';
			if ( file_exists( $file_path ) ) {
				include $file_path;
			}
		}

		public static function get_unread_count() {
			global $wpdb;
			$current_time = current_time( 'Y-m-d H:i:s' );

			$unread_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(id) FROM {$wpdb->prefix}td_app_notifications WHERE dismissed = 0 AND start <= %s",
					$current_time
				)
			);

			if ( $unread_count > 99 ) {
				$unread_count = '99+';
			}

			return $unread_count;
		}

		/**
		 * Add notifications to the dashboard menu
		 *
		 * @param array $menus
		 *
		 * @return array
		 */
		public function add_to_dashboard_menu( $menus = array() ) {
			$count = static::get_unread_count();

			if ( $count > 0 ) {
				$menus['app_notifications'] = array(
					'parent_slug' => 'tve_dash_section',
					'page_title'  => __( 'App Notification', 'thrive-dash' ),
					'menu_title'  => __( 'Notifications',
							'thrive-dash' ) . '<span class="notification-indicator"></span>',
					'capability'  => TVE_DASH_CAPABILITY,
					'menu_slug'   => 'tve_dash_section&notify=1',
					'function'    => 'tve_dash_section',
				);
			} else {
				unset( $menus['app_notifications'] );
			}

			return $menus;
		}

		/**
		 * Render the notification button
		 *
		 * @param bool $return
		 *
		 * @return string|void
		 */
		public function notification_button( $return = false ) {

			$template = $this->template_path . '/app-notification-button.php';

			ob_start();
			if ( file_exists( $template ) ) {
				include $template;
			}
			$html = ob_get_clean();

			if ( $return ) {
				return $html;
			}

			echo $html; // phpcs:ignore
		}
	}
}

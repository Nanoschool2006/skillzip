<?php
/**
 * Translation Actions Handler
 *
 * Handles translation-related actions from the admin interface
 *
 * @package Complianz\Translations
 * @since 7.5.4
 */

defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );

if ( ! class_exists( 'CMPLZ_Translation_Actions' ) ) {

	/**
	 * Class CMPLZ_Translation_Actions
	 */
	class CMPLZ_Translation_Actions {

		/**
		 * Translation fetcher instance
		 *
		 * @var CMPLZ_Translation_Fetcher
		 */
		private $fetcher;

		/**
		 * Constructor
		 */
		public function __construct() {
			// If translations are disabled, register the early cron disable check hook and don't initialize anything else
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				add_action( 'init', array( $this, 'early_cron_disable_check' ), 1 );
				return;
			}

			$this->fetcher = CMPLZ_Translation_Fetcher::get_instance();
			
			// If fetcher is null (translations disabled), don't initialize hooks
			if ( $this->fetcher === null ) {
				return;
			}
			
			$this->init_hooks();
			
			// Initialize cron actions once during construction
			$this->manage_translation_cron_actions();
		}

		/**
		 * Initialize hooks
		 *
		 * @return void
		 */
		private function init_hooks() {
			// Use the cmplz_do_action filter instead of AJAX hooks
			add_filter( 'cmplz_do_action', array( $this, 'handle_translation_actions' ), 10, 3 );

			// Hook into field updates to automatically handle cron interval changes
			add_action( 'cmplz_after_save_field', array( $this, 'handle_field_update' ), 10, 4 );

			// Hook to manage translation cron actions when the setting is updated
			add_action( 'update_option_cmplz_translation_cron_interval', array( $this, 'manage_translation_cron_actions' ) );
		}

		/**
		 * Handle all translation actions via the cmplz_do_action filter
		 *
		 * @param mixed  $data    Response data.
		 * @param string $action  Action name.
		 * @param array  $request Request data.
		 * @return mixed Response data or original data if action not handled.
		 */
		public function handle_translation_actions( $data, $action, $request ) {
			// If translations are disabled, return disabled response for translation actions
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				switch ( $action ) {
					case 'fetch_translations':
					case 'update_cron_interval':
					case 'get_translation_status':
					case 'get_cron_status':
					case 'test_translations':
						return array(
							'success' => false,
							'error'   => 'Translation functionality is disabled via CMPLZ_DISABLE_TRANSLATIONS constant',
						);
					default:
						return $data;
				}
			}

			// If fetcher is null, return disabled response
			if ( $this->fetcher === null ) {
				switch ( $action ) {
					case 'fetch_translations':
					case 'update_cron_interval':
					case 'get_translation_status':
					case 'get_cron_status':
					case 'test_translations':
						return array(
							'success' => false,
							'error'   => 'Translation functionality is not available',
						);
					default:
						return $data;
				}
			}

			switch ( $action ) {
				case 'fetch_translations':
					return $this->handle_fetch_translations_action( $request );

				case 'update_cron_interval':
					return $this->handle_update_cron_interval_action( $request );

				case 'get_translation_status':
					return $this->handle_get_translation_status_action( $request );

				case 'get_cron_status':
					return $this->handle_get_cron_status_action( $request );

				case 'test_translations':
					return $this->handle_test_translations_action( $request );

				default:
					return $data;
			}
		}

		/**
		 * Handle fetch translations action
		 *
		 * @param array $request Request data.
		 * @return array Response data.
		 */
		private function handle_fetch_translations_action( $request ) {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return array(
					'success' => false,
					'error'   => __( 'You do not have permission to perform this action', 'complianz-gdpr' ),
				);
			}

			// Check if fetcher is available
			if ( $this->fetcher === null ) {
				return array(
					'success' => false,
					'error'   => 'Translation functionality is not available',
				);
			}

			// Fetch all translations
			$result = $this->fetcher->fetch_translations( $this->fetcher->get_all_languages() );

			// Convert success to request_success for frontend compatibility
			if ( isset( $result['success'] ) ) {
				$result['request_success'] = $result['success'];
				unset( $result['success'] );
			}

			return $result;
		}

		/**
		 * Handle update cron interval action
		 *
		 * @param array $request Request data.
		 * @return array Response data.
		 */
		private function handle_update_cron_interval_action( $request ) {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return array(
					'success' => false,
					'error'   => __( 'You do not have permission to perform this action', 'complianz-gdpr' ),
				);
			}

			// Check if fetcher is available
			if ( $this->fetcher === null ) {
				return array(
					'success' => false,
					'error'   => 'Translation functionality is not available',
				);
			}

			$interval = $request['interval'] ?? 'cmplz_weekly';

			// Validate input
			$valid_intervals = array( 'disabled', 'cmplz_daily', 'cmplz_weekly', 'cmplz_monthly' );
			if ( ! in_array( $interval, $valid_intervals, true ) ) {
				return array(
					'success' => false,
					'error'   => __( 'Invalid interval value.', 'complianz-gdpr' ),
				);
			}

			// Update cron interval
			$result = $this->fetcher->update_cron_interval( $interval );

			if ( $result ) {
				$response = array(
					'request_success' => true,
					'message'         => $interval === 'disabled'
						? __( 'Automatic translation updates disabled.', 'complianz-gdpr' )
						: sprintf( __( 'Automatic translation updates set to run %s.', 'complianz-gdpr' ), $interval ),
					'cron_status'     => $this->fetcher->get_cron_status(),
				);
				return $response;
			} else {
				return array(
					'success' => false,
					'error'   => __( 'Failed to update cron interval.', 'complianz-gdpr' ),
				);
			}
		}

		/**
		 * Handle get translation status action
		 *
		 * @param array $request Request data.
		 * @return array Response data.
		 */
		private function handle_get_translation_status_action( $request ) {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return array(
					'success' => false,
					'error'   => __( 'You do not have permission to perform this action', 'complianz-gdpr' ),
				);
			}

			// Check if fetcher is available
			if ( $this->fetcher === null ) {
				return array(
					'success' => false,
					'error'   => 'Translation functionality is not available',
				);
			}

			// Get translation status
			$status = $this->fetcher->get_status();

			// Return with request_success for frontend compatibility
			return array(
				'request_success' => true,
				'data'            => $status,
			);
		}

		/**
		 * Handle get cron status action
		 *
		 * @param array $request Request data.
		 * @return array Response data.
		 */
		private function handle_get_cron_status_action( $request ) {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return array(
					'success' => false,
					'error'   => __( 'You do not have permission to perform this action', 'complianz-gdpr' ),
				);
			}

			// Get cron status
			$status = $this->fetcher->get_cron_status();

			// Return with request_success for frontend compatibility
			return array(
				'request_success' => true,
				'data'            => $status,
			);
		}

		/**
		 * Handle test translations action
		 *
		 * @param array $request Request data.
		 * @return array Response data.
		 */
		private function handle_test_translations_action( $request ) {
			// Check permissions
			if ( ! current_user_can( 'manage_options' ) ) {
				return array(
					'success' => false,
					'error'   => __( 'You do not have permission to perform this action', 'complianz-gdpr' ),
				);
			}

			// Test translation functionality
			$test_results = $this->fetcher->test_functionality();

			// Return with request_success for frontend compatibility
			return array(
				'request_success' => true,
				'data'            => $test_results,
			);
		}

		/**
		 * Handle field updates to automatically process translation-related changes
		 *
		 * @param string $field_id   Field ID.
		 * @param mixed  $value      New field value.
		 * @param mixed  $prev_value Previous field value.
		 * @param string $type       Field type.
		 * @return void
		 */
		public function handle_field_update( $field_id, $value, $prev_value, $type ) {
			// If translations are disabled, don't process any field updates
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				return;
			}

			// Check if this is the translation cron interval field
			if ( $field_id === 'translation_cron_interval' ) {
				// Convert the field value to the cron interval setting
				$cron_interval = $this->convert_field_value_to_cron_interval( $value );

				if ( $cron_interval !== false ) {
					// Update the cron interval setting
					$result = $this->fetcher->update_cron_interval( $cron_interval );

					if ( $result ) {
						// Log successful update
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Translation cron interval automatically updated to: ' . $cron_interval );
						}
					} else {
						// Log failed update
						if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
							error_log( 'Failed to automatically update translation cron interval to: ' . $cron_interval );
						}
					}
				}
			}
		}

		/**
		 * Convert field value to cron interval setting
		 *
		 * @param mixed $value Field value.
		 * @return string|false Cron interval setting or false if invalid.
		 */
		private function convert_field_value_to_cron_interval( $value ) {
			// Map field values to cron interval settings
			$interval_map = array(
				'disabled'      => 'disabled',
				'cmplz_daily'   => 'cmplz_daily',
				'cmplz_weekly'  => 'cmplz_weekly',
				'cmplz_monthly' => 'cmplz_monthly',
			);

			return isset( $interval_map[ $value ] ) ? $interval_map[ $value ] : false;
		}

		/**
		 * Fetch translations periodically based on user settings
		 */
		public function cron_fetch_translations() {	
			// If translations are disabled, don't do anything
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				return;
			}

			// Check if translation functionality is available
			if ( class_exists( 'CMPLZ_Translation_Fetcher' ) ) {
				$fetcher = CMPLZ_Translation_Fetcher::get_instance();
				if ( $fetcher !== null ) {
					$fetcher->fetch_translations( $fetcher->get_all_languages() );
				}
			}
		}

		/**
		 * Early check to ensure cron is disabled if translations are disabled
		 * This runs very early in WordPress initialization
		 */
		public function early_cron_disable_check() {
			// If translations are disabled, force cron to disabled immediately
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				// Force the cron option to disabled
				update_option( 'cmplz_translation_cron_interval', 'disabled' );
				
				// Remove any existing translation actions immediately
				remove_action( 'cmplz_every_day_hook', array( $this, 'cron_fetch_translations' ) );
				remove_action( 'cmplz_every_week_hook', array( $this, 'cron_fetch_translations' ) );
				remove_action( 'cmplz_every_month_hook', array( $this, 'cron_fetch_translations' ) );
			}
		}

		/**
		 * Manage translation cron actions based on user settings
		 * This function is safe to call on every page load
		 */
		public function manage_translation_cron_actions() {
			// If translations are disabled, don't set up any cron actions
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				return;
			}

			$translation_interval = get_option( 'cmplz_translation_cron_interval', 'cmplz_weekly' );
			
			// Remove translation actions from all hooks first
			remove_action( 'cmplz_every_day_hook', array( $this, 'cron_fetch_translations' ) );
			remove_action( 'cmplz_every_week_hook', array( $this, 'cron_fetch_translations' ) );
			remove_action( 'cmplz_every_month_hook', array( $this, 'cron_fetch_translations' ) );
			
			// Add translation action only to the selected interval
			switch ( $translation_interval ) {
				case 'cmplz_daily':
					add_action( 'cmplz_every_day_hook', array( $this, 'cron_fetch_translations' ) );
					break;
				case 'cmplz_weekly':
					add_action( 'cmplz_every_week_hook', array( $this, 'cron_fetch_translations' ) );
					break;
				case 'cmplz_monthly':
					add_action( 'cmplz_every_month_hook', array( $this, 'cron_fetch_translations' ) );
					break;
				case 'cmplz_disabled':
				default:
					// No action added - translation fetching is disabled
					break;
			}
		}
	}
}

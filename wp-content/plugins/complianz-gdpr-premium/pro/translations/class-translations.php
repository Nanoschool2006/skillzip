<?php
/**
 * Translation Fetcher Class
 *
 * Handles automatic translation fetches and updates from the Complianz translation server
 *
 * @package Complianz\Translations
 * @since 7.5.4
 */

defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );

if ( ! class_exists( 'CMPLZ_Translation_Fetcher' ) ) {

	/**
	 * Class CMPLZ_Translation_Fetcher
	 */
	class CMPLZ_Translation_Fetcher {

		/**
		 * Singleton instance
		 *
		 * @var CMPLZ_Translation_Fetcher
		 */
		private static $instance = null;

		/**
		 * API endpoint for translations
		 *
		 * @var string
		 */
		private $api_endpoint = 'https://translations.complianz.io/wp-json/cpz-translations/v1/translations';

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		private $plugin_version;

		/**
		 * Languages directory path
		 *
		 * @var string
		 */
		private $languages_dir;

		/**
		 * Transient prefix for translation fetching
		 *
		 * @var string
		 */
		private $transient_prefix = 'cmplz_translation_fetched_';

		/**
		 * Cron hook name
		 *
		 * @var string
		 */
		private $cron_hook = 'cmplz_fetch_all_translations';

		/**
		 * Constructor
		 */
		private function __construct() {
			// If translations are disabled, don't initialize anything.
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				return;
			}

			$this->plugin_version = $this->get_plugin_version();
			$plugin_file          = dirname( dirname( __DIR__ ) ) . '/complianz-gpdr-premium.php';
			$this->languages_dir  = plugin_dir_path( $plugin_file ) . 'languages/';

			// Initialize hooks.
			$this->init_hooks();
		}

		/**
		 * Get singleton instance
		 *
		 * @return CMPLZ_Translation_Fetcher or null if translations are disabled
		 */
		public static function get_instance() {
			// If translations are disabled, return null.
			if ( defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS ) {
				return null;
			}

			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Prevent cloning of the instance
		 *
		 * @throws Exception When attempting to clone the singleton.
		 */
		public function __clone() {
			throw new Exception( 'Cannot clone singleton' );
		}

		/**
		 * Prevent unserializing of the instance
		 *
		 * @throws Exception When attempting to unserialize the singleton.
		 */
		public function __wakeup() {
			throw new Exception( 'Cannot unserialize singleton' );
		}

		/**
		 * Initialize WordPress hooks
		 *
		 * @return void
		 */
		private function init_hooks() {
			// Hook for admin area user language check.
			add_action( 'admin_init', array( $this, 'check_user_language_on_admin_init' ) );

			// Hook for scheduled user language fetch.
			add_action( 'cmplz_fetch_user_language', array( $this, 'scheduled_fetch_user_language' ) );

			// Hook for plugin updates.
			add_action( 'upgrader_process_complete', array( $this, 'on_plugin_update' ), 10, 2 );

			// Hook for when WordPress detects a plugin update.
			add_action( 'init', array( $this, 'check_for_plugin_updates' ) );

			// Hook for adding translations menu.
			add_filter( 'cmplz_menu', array( $this, 'add_translations_menu' ), 80 );
		}


		/**
		 * Get the current plugin version
		 *
		 * @return string
		 */
		private function get_plugin_version(): string {
			return defined( 'CMPLZ_VERSION' ) ? CMPLZ_VERSION : '7.5.4';
		}

		/**
		 * Get user's preferred language
		 *
		 * @param int|null $user_id User ID or null for current user.
		 * @return string User's language code.
		 */
		public function get_user_language( $user_id = null ) {
			// If no user ID provided, get current user.
			if ( null === $user_id ) {
				// During plugin activation, there might not be a current user.
				if ( function_exists( 'wp_get_current_user' ) ) {
					$current_user = wp_get_current_user();
					if ( $current_user && $current_user->exists() && function_exists( 'get_user_locale' ) ) {
						$user_locale = get_user_locale( $current_user->ID );
						if ( ! empty( $user_locale ) ) {
							return $user_locale;
						}
					}
				}

				// Try to get admin user locale as fallback.
				if ( function_exists( 'get_users' ) ) {
					$admin_users = get_users(
						array(
							'role'   => 'administrator',
							'number' => 1,
							'fields' => array( 'ID' ),
						)
					);

					if ( ! empty( $admin_users ) ) {
						$admin_user  = $admin_users[0];
						$user_locale = get_user_meta( $admin_user->ID, 'locale', true );
						if ( ! empty( $user_locale ) ) {
							return $user_locale;
						}
					}
				}

				// Fall back to site locale.
				if ( function_exists( 'get_locale' ) ) {
					return get_locale();
				}
			}

			// Get specific user's locale.
			if ( function_exists( 'get_user_meta' ) ) {
				$user_locale = get_user_meta( $user_id, 'locale', true );
				if ( ! empty( $user_locale ) ) {
					return $user_locale;
				}
			}

			// Final fallback to site locale or default.
			if ( function_exists( 'get_locale' ) ) {
				$locale = get_locale();
				if ( ! empty( $locale ) ) {
					return $locale;
				}
			}

			// Ultimate fallback.
			return 'en_GB';
		}

		/**
		 * Get all available languages for translation fetching
		 *
		 * @return array Array of language codes.
		 */
		public function get_all_languages() {
			$languages = array();

			// Add site locale.
			$site_locale = get_locale();
			if ( ! empty( $site_locale ) ) {
				$languages[] = $site_locale;
			}

			// Get all users with admin capabilities.
			$admin_users = get_users(
				array(
					'capability' => 'read',
					'fields'     => array( 'ID', 'user_login' ),
				)
			);

			foreach ( $admin_users as $user ) {
				$user_language = $this->get_user_language( $user->ID );
				if ( ! in_array( $user_language, $languages, true ) ) {
					$languages[] = $user_language;
				}
			}

			// check for languages from tranlation plugins.
			// WPML.
			if ( defined( 'ICL_SITEPRESS_VERSION' ) && function_exists( 'apply_filters' ) ) {
				$wpml_langs = apply_filters( 'wpml_active_languages', null );
				if ( is_array( $wpml_langs ) ) {
					foreach ( $wpml_langs as $lang ) {
						$languages[] = $lang['default_locale'];
					}
				}
			}

			// Polylang.
			if ( function_exists( 'pll_languages_list' ) ) {
				$pll_langs = pll_languages_list( array( 'fields' => 'locale' ) );
				if ( is_array( $pll_langs ) ) {
					$languages = array_merge( $languages, $pll_langs );
				}
			}

			// TranslatePress.
			if ( function_exists( 'trp_get_available_languages' ) ) {
				$trp_langs = trp_get_available_languages();
				if ( is_array( $trp_langs ) ) {
					$languages = array_merge( $languages, array_keys( $trp_langs ) );
				}
			}

			// qTranslate / qTranslate-X.
			if ( function_exists( 'qtranxf_getSortedLanguages' ) ) {
				global $q_config;
				$qt_langs = qtranxf_getSortedLanguages();
				if ( is_array( $qt_langs ) && isset( $q_config['locale'] ) ) {
					foreach ( $qt_langs as $lang_code ) {
						if ( isset( $q_config['locale'][ $lang_code ] ) ) {
							$languages[] = $q_config['locale'][ $lang_code ];
						}
					}
				}
			}

			// Remove duplicates and ensure we have at least one language.
			$languages = array_unique( $languages );

			if ( empty( $languages ) ) {
				$languages[] = 'en_GB'; // Fallback to English.
			}

			return $languages;
		}

		/**
		 * Make API request to translation server
		 *
		 * @param array  $languages Array of language codes.
		 * @param string $version   Optional version to use (defaults to plugin version).
		 * @return array|WP_Error Response data or WP_Error on failure.
		 */
		public function make_api_request( $languages, $version = null ) {
			$url = $this->api_endpoint;

			// Add each language as a separate lang[] parameter.
			foreach ( $languages as $language ) {
				$url = add_query_arg( 'lang[]', $language, $url );
			}

			// Use provided version or default to plugin version.
			$request_version = null !== $version ? $version : $this->plugin_version;
			$url             = add_query_arg( 'version', $request_version, $url );

			$args = array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent'    => 'Complianz-GDPR-Premium/' . $request_version,
					'Cache-Control' => 'no-cache, no-store, must-revalidate',
					'Pragma'        => 'no-cache',
					'Expires'       => '0',
				),
			);

			// Bypass SSL verification for local development and problematic SSL environments.
			if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ||
				strpos( home_url(), 'localhost' ) !== false ||
				strpos( home_url(), '.test' ) !== false ||
				strpos( home_url(), '.local' ) !== false ||
				strpos( home_url(), '127.0.0.1' ) !== false ) {
				$args['sslverify'] = false;
			}

			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				// Check if it's an SSL error and try to provide a more helpful error message.
				$error_message = $response->get_error_message();
				if ( strpos( $error_message, 'SSL' ) !== false || strpos( $error_message, 'cURL error 60' ) !== false ) {
					return new WP_Error( 'ssl_error', 'SSL connection failed. This may be due to server SSL configuration. Please contact your hosting provider or try again later.' );
				}
				return $response;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_Error( 'json_decode_error', 'Failed to decode JSON response' );
			}

			return $data;
		}

		/**
		 * Download and process a translation file
		 *
		 * @param array $translation Translation data from API.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function download_and_process_translation( $translation ) {
			if ( empty( $translation['language'] ) || empty( $translation['url'] ) ) {
				return new WP_Error( 'invalid_translation_data', 'Invalid translation data provided' );
			}

			$language     = $translation['language'];
			$download_url = $translation['url'];

			// Get filesystem object.
			$filesystem = $this->get_filesystem();
			if ( is_wp_error( $filesystem ) ) {
				error_log( 'Complianz: Filesystem error: ' . $filesystem->get_error_message() );
				return $filesystem;
			}

			// Create temporary directory for this language.
			$temp_dir = $this->languages_dir . 'temp_' . $language . '_' . uniqid();
			if ( ! $filesystem->mkdir( $temp_dir, 0755 ) ) {
				return new WP_Error( 'mkdir_failed', 'Failed to create temporary directory for ' . $language );
			}

			$temp_zip = $temp_dir . '.zip';

			// Download the translation file.
			$download_result = $this->download_file( $download_url, $temp_zip );
			if ( is_wp_error( $download_result ) ) {
				error_log( 'Complianz: Download file error: ' . $download_result->get_error_message() );
				$filesystem->delete( $temp_zip );
				$filesystem->rmdir( $temp_dir, true ); // Clean up temp directory.
				return $download_result;
			}

			// Extract the zip file.
			$extract_result = $this->extract_translation_files( $temp_zip );
			if ( is_wp_error( $extract_result ) ) {
				error_log( 'Complianz: Extract error: ' . $extract_result->get_error_message() );
				$filesystem->delete( $temp_zip );
				$filesystem->rmdir( $temp_dir, true ); // Clean up temp directory.
				return $extract_result;
			}

			// Clean up temp file.
			$filesystem->delete( $temp_zip );

			// Validate extracted files have content.
			$validation_result = $this->validate_extracted_files( $language );
			if ( is_wp_error( $validation_result ) ) {
				error_log( 'Complianz: Validation error: ' . $validation_result->get_error_message() );
				$filesystem->rmdir( $temp_dir, true ); // Clean up temp directory.
				return $validation_result;
			}

			// Clean up old files after successful download, extraction, and validation.
			$this->cleanup_old_files( $language );

			// Clean up temp directory after successful processing.
			$filesystem->rmdir( $temp_dir, true );

			return true;
		}

		/**
		 * Download a file from URL to local path
		 *
		 * @param string $url  URL to download from.
		 * @param string $path Local path to save to.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function download_file( $url, $path ) {
			$args = array(
				'timeout'  => 60,
				'stream'   => true,
				'filename' => $path,
			);

			// Bypass SSL verification for local development.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( strpos( home_url(), 'localhost' ) !== false || strpos( home_url(), '.test' ) !== false || strpos( home_url(), '.local' ) !== false ) ) {
				$args['sslverify'] = false;
			}

			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				return new WP_Error( 'download_failed', 'Failed to download file. HTTP code: ' . $response_code );
			}

			return true;
		}

		/**
		 * Extract translation files from zip to languages directory
		 *
		 * @param string $zip_path Path to zip file.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function extract_translation_files( $zip_path ) {
			$filesystem = $this->get_filesystem();
			if ( is_wp_error( $filesystem ) ) {
				return $filesystem;
			}

			// Ensure languages directory exists.
			$languages_dir = $this->languages_dir;
			if ( ! $filesystem->is_dir( $languages_dir ) ) {
				$filesystem->mkdir( $languages_dir, 0755 );
			}

			// Extract zip file.
			$unzip_result = unzip_file( $zip_path, $languages_dir );
			if ( is_wp_error( $unzip_result ) ) {
				return $unzip_result;
			}

			return true;
		}

		/**
		 * Validate that extracted translation files have content
		 *
		 * @param string $language Language code.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function validate_extracted_files( $language ) {
			$filesystem = $this->get_filesystem();
			if ( is_wp_error( $filesystem ) ) {
				return $filesystem;
			}

			$languages_dir  = $this->languages_dir;
			$expected_files = array(
				'complianz-gdpr-' . $language . '.po',
				'complianz-gdpr-' . $language . '.mo',
			);

			foreach ( $expected_files as $file ) {
				$file_path = $languages_dir . $file;
				if ( ! $filesystem->exists( $file_path ) ) {
					return new WP_Error( 'missing_file', 'Missing translation file: ' . $file );
				}

				$file_size = $filesystem->size( $file_path );
				if ( 0 === $file_size ) {
					return new WP_Error( 'empty_file', 'Translation file is empty: ' . $file );
				}
			}

			return true;
		}

		/**
		 * Clean up old translation files for a language
		 *
		 * @param string $language Language code.
		 * @return void
		 */
		private function cleanup_old_files( $language ) {
			$filesystem = $this->get_filesystem();
			if ( is_wp_error( $filesystem ) ) {
				return;
			}

			$languages_dir = $this->languages_dir;
			$old_files     = array(
				'complianz-gdpr-' . $language . '.po.bak',
				'complianz-gdpr-' . $language . '.mo.bak',
			);

			foreach ( $old_files as $file ) {
				$file_path = $languages_dir . $file;
				if ( $filesystem->exists( $file_path ) ) {
					$filesystem->delete( $file_path );
				}
			}
		}

		/**
		 * Get WordPress filesystem object
		 *
		 * @return WP_Filesystem_Base|WP_Error Filesystem object or WP_Error on failure.
		 */
		private function get_filesystem() {
			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}

			if ( ! $wp_filesystem ) {
				return new WP_Error( 'filesystem_error', 'Could not initialize filesystem' );
			}

			return $wp_filesystem;
		}

		/**
		 * Update POT file if available
		 *
		 * @param array $response_data API response data.
		 * @return bool|WP_Error True on success, WP_Error on failure.
		 */
		private function update_pot_file( $response_data ) {
			if ( empty( $response_data['pot_file']['url'] ) ) {
				return true; // No POT file available, not an error.
			}

			$pot_url  = $response_data['pot_file']['url'];
			$pot_file = $this->languages_dir . 'complianz-gdpr.pot';

			// Check if POT file needs updating.
			if ( ! empty( $response_data['pot_file']['updated_at'] ) ) {
				if ( file_exists( $pot_file ) ) {
					$local_updated_at = filemtime( $pot_file );
					if ( $response_data['pot_file']['updated_at'] <= $local_updated_at ) {
						return true; // No update needed.
					}
				}
			}

			// Use the generic download function for POT file.
			$download_result = $this->download_file( $pot_url, $pot_file );
			return $download_result;
		}

		/**
		 * Get comprehensive status information
		 *
		 * @return array Status information.
		 */
		public function get_status() {
			$filesystem = $this->get_filesystem();
			if ( is_wp_error( $filesystem ) ) {
				return array(
					'error'          => $filesystem->get_error_message(),
					'languages'      => array(),
					'plugin_version' => $this->plugin_version,
					'languages_dir'  => $this->languages_dir,
					'language_files' => array(),
				);
			}

			$languages = $this->get_all_languages();
			$status    = array(
				'languages'          => $languages,
				'plugin_version'     => $this->plugin_version,
				'languages_dir'      => $this->languages_dir,
				'language_files'     => array(),
				'cron_interval'      => get_option( 'cmplz_translation_cron_interval', 'not_set' ),
				'stored_version'     => get_option( 'cmplz-current-version', 'not_set' ),
				'translation_errors' => get_option( 'cmplz_translation_errors', array() ),
			);

			foreach ( $languages as $language ) {
				$status['language_files'][ $language ] = $this->get_language_status( $language );
			}

			return $status;
		}

		/**
		 * Check if a language has been recently fetched
		 *
		 * @param string $language Language code.
		 * @return bool True if language was recently fetched.
		 */
		private function is_language_fetched( $language ) {
			$transient_key = $this->transient_prefix . $language;
			return (bool) get_transient( $transient_key );
		}

		/**
		 * Mark a language as fetched
		 *
		 * @param string $language Language code.
		 * @return void
		 */
		private function mark_language_fetched( $language ) {
			$transient_key = $this->transient_prefix . $language;
			set_transient( $transient_key, true, WEEK_IN_SECONDS );
		}

		/**
		 * Fetch translations for specified languages
		 *
		 * @param array|string $languages Array of language codes or single language code.
		 * @return array Result array with success status and any errors.
		 */
		public function fetch_translations( $languages ) {

			$result = array(
				'success' => false,
				'errors'  => array(),
				'data'    => array(),
			);

			// Ensure languages is an array.
			if ( ! is_array( $languages ) ) {
				$languages = array( $languages );
			}

			// First attempt with current version.
			$api_response = $this->make_api_request( $languages );

			if ( is_wp_error( $api_response ) ) {
				error_log( 'Complianz: API request failed: ' . $api_response->get_error_message() );
				$result['errors'][] = $api_response->get_error_message();
				return $result;
			}

			// Check if API response has the expected structure.
			if ( ! isset( $api_response['success'] ) ) {
				error_log( 'Complianz: API response missing success field' );
				$result['errors'][] = 'Invalid API response format';
				return $result;
			}

			// Check for suggested version in errors.
			$suggested_version = null;
			if ( ! $api_response['success'] && isset( $api_response['errors'] ) && is_array( $api_response['errors'] ) ) {
				foreach ( $api_response['errors'] as $error ) {
					if ( isset( $error['suggested_version'] ) && null !== $error['suggested_version'] ) {
						$suggested_version = $error['suggested_version'];
						break;
					}
				}
			}

			// If we have a suggested version, retry the request.
			if ( null !== $suggested_version ) {

				// Store original version.
				$original_version = $this->plugin_version;

				// Temporarily override version for the retry.
				$api_response = $this->make_api_request( $languages, $suggested_version );

				if ( is_wp_error( $api_response ) ) {
					$result['errors'][] = 'Retry with suggested version failed: ' . $api_response->get_error_message();
					return $result;
				}
			}

			// Handle successful API responses (even if no translations found).
			if ( ! empty( $api_response['success'] ) && true === $api_response['success'] ) {
				// Process any errors from the API response first (for partial success scenarios).
				if ( isset( $api_response['errors'] ) && is_array( $api_response['errors'] ) ) {
					foreach ( $api_response['errors'] as $error ) {
						if ( isset( $error['language'] ) && isset( $error['error'] ) ) {
							// Store language-specific errors.
							$result['errors'][] = array(
								'language' => $error['language'],
								'error'    => $error['error'],
							);
						} elseif ( isset( $error['error'] ) ) {
							// Store general errors.
							$result['errors'][] = $error['error'];
						}
					}
				}

				// Success - translations found.
				if ( isset( $api_response['translations'] ) && is_array( $api_response['translations'] ) ) {
					$result['data'] = $api_response['translations'];

					// Download and process each translation.
					foreach ( $api_response['translations'] as $translation ) {
						$download_result = $this->download_and_process_translation( $translation );
						if ( is_wp_error( $download_result ) ) {
							$result['errors'][] = 'Failed to download translation for ' . $translation['language'] . ': ' . $download_result->get_error_message();
						}
					}
				}

				// Update POT file if available.
				$pot_result = $this->update_pot_file( $api_response );
				if ( is_wp_error( $pot_result ) ) {
					$result['errors'][] = 'Failed to update POT file: ' . $pot_result->get_error_message();
				}

				$result['success'] = true;
			} else {
				// API returned success: false or empty - this is a valid response indicating no translations available.

				// Process any errors from the API response and store them properly.
				if ( isset( $api_response['errors'] ) && is_array( $api_response['errors'] ) ) {
					foreach ( $api_response['errors'] as $error ) {
						if ( isset( $error['language'] ) && isset( $error['error'] ) ) {
							// Store language-specific errors.
							$result['errors'][] = array(
								'language' => $error['language'],
								'error'    => $error['error'],
							);
						} elseif ( isset( $error['error'] ) ) {
							// Store general errors.
							$result['errors'][] = $error['error'];
						}
					}
				}

				// Process any translations that were found (partial success).
				if ( isset( $api_response['translations'] ) && is_array( $api_response['translations'] ) && ! empty( $api_response['translations'] ) ) {
					$result['data'] = $api_response['translations'];

					// Download and process each translation.
					foreach ( $api_response['translations'] as $translation ) {
						$download_result = $this->download_and_process_translation( $translation );
						if ( is_wp_error( $download_result ) ) {
							$result['errors'][] = 'Failed to download translation for ' . $translation['language'] . ': ' . $download_result->get_error_message();
						}
					}
				}

				// Update POT file if available (even for failed requests, POT might still be available).
				$pot_result = $this->update_pot_file( $api_response );
				if ( is_wp_error( $pot_result ) ) {
					$result['errors'][] = 'Failed to update POT file: ' . $pot_result->get_error_message();
				}

				// Consider this a success if we got a valid response, even if no translations were found.
				$result['success'] = true;
			}

			// Store any errors in the options for frontend display.
			if ( ! empty( $result['errors'] ) ) {
				$existing_errors = get_option( 'cmplz_translation_errors', array() );
				$new_errors      = array();

				foreach ( $result['errors'] as $error ) {
					if ( is_array( $error ) && isset( $error['language'] ) ) {
						// Language-specific error.
						$new_errors[] = $error;
					} else {
						// General error.
						$new_errors[] = $error;
					}
				}

				// Merge with existing errors, avoiding duplicates.
				$all_errors = array_merge( $existing_errors, $new_errors );
				update_option( 'cmplz_translation_errors', $all_errors, false );
			}

			return $result;
		}

		/**
		 * Check and fetch current user's language on login
		 *
		 * @return void
		 */
		public function check_user_language_on_admin_init() {
			$current_language = $this->get_user_language();

			// Skip if language was recently fetched.
			if ( $this->is_language_fetched( $current_language ) ) {
				return;
			}

			// Fetch translations immediately for the current user.
			$result = $this->fetch_translations( $current_language );
			// Mark the language as fetched if successful.
			if ( $result['success'] ) {
				$this->mark_language_fetched( $current_language );
				load_plugin_textdomain( 'complianz-gdpr', false, $this->languages_dir );
			}
		}

		/**
		 * Handle plugin activation
		 *
		 * @return void
		 */
		public static function on_activation() {
			$fetcher = self::get_instance();

			// Set default translation cron interval if not already set.
			if ( ! get_option( 'cmplz_translation_cron_interval' ) ) {
				update_option( 'cmplz_translation_cron_interval', 'cmplz_weekly' );
			}

			// Ensure languages directory exists.
			if ( ! is_dir( $fetcher->languages_dir ) ) {
				wp_mkdir_p( $fetcher->languages_dir );
			}

			// Fetch translations for the current user.
			$fetcher->fetch_translations_for_current_user();
		}

		/**
		 * Handle plugin updates
		 *
		 * @param object $upgrader WP_Upgrader instance.
		 * @param array  $hook_extra Extra data for the hook.
		 * @return void
		 */
		public function on_plugin_update( $upgrader, $hook_extra ) {
			$plugin_updated = false;

			// Check if our plugin was updated.
			if ( isset( $hook_extra['plugin'] ) && strpos( $hook_extra['plugin'], 'complianz' ) !== false ) {
				$plugin_updated = true;
			}

			// Check if our plugin was updated via bulk update.
			if ( isset( $hook_extra['bulk'] ) && $hook_extra['bulk'] && isset( $hook_extra['plugins'] ) ) {
				foreach ( $hook_extra['plugins'] as $plugin ) {
					if ( strpos( $plugin, 'complianz' ) !== false ) {
						$plugin_updated = true;
						break;
					}
				}
			}

			// If our plugin was updated, update the version option and fetch translations.
			if ( $plugin_updated ) {
				update_option( 'cmplz-current-version', $this->plugin_version );
				$this->fetch_translations_for_current_user();
			}
		}

		/**
		 * Check for plugin updates and fetch translations if needed
		 *
		 * @return void
		 */
		public function check_for_plugin_updates() {
			// Only run on admin pages.
			if ( ! is_admin() ) {
				return;
			}

			// Only run for logged in users.
			if ( ! get_current_user_id() ) {
				return;
			}

			$transient_key = 'cmplz_plugin_update_check_' . get_current_user_id();

			// Check if current user needs translations but doesn't have them.
			$current_language = $this->get_user_language();

			if ( 'en_US' !== $current_language ) {
				$translation_file = $this->languages_dir . 'complianz-gdpr-' . $current_language . '.mo';

				if ( file_exists( $translation_file ) ) {
					$file_size = filesize( $translation_file );
				}

				if ( ! file_exists( $translation_file ) || filesize( $translation_file ) === 0 ) {
					$this->fetch_translations_for_current_user();
					set_transient( $transient_key, true, HOUR_IN_SECONDS );
					return;
				}
			}

			// Check if we've already run this check in this session.
			$transient_exists = get_transient( $transient_key );

			if ( $transient_exists ) {
				return;
			}

			// Get stored plugin version from existing option.
			$stored_version  = get_option( 'cmplz-current-version', '' );
			$current_version = $this->plugin_version;

			// Check if version changed.
			if ( $stored_version !== $current_version ) {
				$this->fetch_translations_for_current_user();
				set_transient( $transient_key, true, HOUR_IN_SECONDS );
				return;
			}

			// No action needed, set transient to prevent future checks.
			set_transient( $transient_key, true, HOUR_IN_SECONDS );
		}

		/**
		 * Fetch translations for the current user (used by activation and updates)
		 *
		 * @return void
		 */
		private function fetch_translations_for_current_user() {
			// Get current user's language.
			$user_language = $this->get_user_language();

			// Ensure languages directory exists.
			if ( ! is_dir( $this->languages_dir ) ) {
				wp_mkdir_p( $this->languages_dir );
			}

			// Fetch translations for the user's language.
			$result = $this->fetch_translations( $user_language );

			if ( $result['success'] ) {
				load_plugin_textdomain( 'complianz-gdpr', false, $this->languages_dir );
			} else {
				error_log( 'Complianz: Failed to fetch translations for user language: ' . $user_language . ' . ' . implode( ', ', $result['errors'] ) );
				// Store errors for admin review.
				update_option( 'cmplz_translation_errors', $result['errors'], false );
			}
		}

		/**
		 * Scheduled callback for fetching user language (returns void)
		 *
		 * @param string $language Language code to fetch.
		 * @return void
		 */
		public function scheduled_fetch_user_language( $language ) {
			$result = $this->fetch_translations( $language );

			if ( ! $result['success'] ) {
				error_log( 'Complianz: Scheduled fetch failed for language: ' . $language . ' - Errors: ' . implode( ', ', $result['errors'] ) );
			}
		}

		/**
		 * Get the cron interval setting from user options
		 *
		 * @return string Cron schedule name (cmplz_daily, cmplz_weekly, cmplz_monthly, or 'disabled').
		 */
		private function get_cron_interval_setting() {
			// Default to weekly if not set.
			$interval = get_option( 'cmplz_translation_cron_interval', 'cmplz_weekly' );

			// Validate the interval.
			$valid_intervals = array( 'cmplz_daily', 'cmplz_weekly', 'cmplz_monthly', 'disabled' );
			if ( ! in_array( $interval, $valid_intervals, true ) ) {
				$interval = 'cmplz_weekly';
			}

			return $interval;
		}

		/**
		 * Update cron interval setting
		 *
		 * @param string $interval Cron interval (cmplz_daily, cmplz_weekly, cmplz_monthly, or disabled).
		 * @return bool True on success, false on failure.
		 */
		public function update_cron_interval( $interval ) {
			// Validate input.
			$valid_intervals = array( 'cmplz_daily', 'cmplz_weekly', 'cmplz_monthly', 'disabled' );
			if ( ! in_array( $interval, $valid_intervals, true ) ) {
				return false;
			}

			// Update the option.
			$updated = update_option( 'cmplz_translation_cron_interval', $interval );

			return $updated;
		}

		/**
		 * Get current cron status
		 *
		 * @return array Cron status information.
		 */
		public function get_cron_status() {
			$interval_setting = $this->get_cron_interval_setting();

			// Convert interval setting to display name.
			$interval_display = array(
				'cmplz_daily'   => 'Daily',
				'cmplz_weekly'  => 'Weekly',
				'cmplz_monthly' => 'Monthly',
				'disabled'      => 'Disabled',
			);

			// Get next scheduled time based on the interval.
			$next_scheduled = 0;
			if ( 'disabled' !== $interval_setting ) {
				$hook_name = '';
				switch ( $interval_setting ) {
					case 'cmplz_daily':
						$hook_name = 'cmplz_every_day_hook';
						break;
					case 'cmplz_weekly':
						$hook_name = 'cmplz_every_week_hook';
						break;
					case 'cmplz_monthly':
						$hook_name = 'cmplz_every_month_hook';
						break;
				}
				if ( $hook_name ) {
					$next_scheduled = wp_next_scheduled( $hook_name );
				}
			}

			return array(
				'interval_setting'   => $interval_setting,
				'interval_display'   => $interval_display[ $interval_setting ] ?? 'Unknown',
				'is_enabled'         => 'disabled' !== $interval_setting,
				'next_run'           => $next_scheduled ? $next_scheduled : 0,
				'next_run_formatted' => $next_scheduled ? gmdate( 'Y-m-d H:i:s', $next_scheduled ) : 'Disabled',
			);
		}

		/**
		 * Test method to verify translation functionality
		 *
		 * @return array Test results
		 */
		public function test_functionality() {
			$results = array(
				'class_loaded'         => true,
				'plugin_version'       => $this->plugin_version,
				'languages_dir'        => $this->languages_dir,
				'languages_dir_exists' => is_dir( $this->languages_dir ),
				'filesystem_available' => ! is_wp_error( $this->get_filesystem() ),
				'zip_available'        => class_exists( 'ZipArchive' ),
			);

			try {
				$languages                     = $this->get_all_languages();
				$results['languages_detected'] = count( $languages );
			} catch ( Exception $e ) {
				$results['languages_error'] = $e->getMessage();
			}

			return $results;
		}

		/**
		 * Get detailed translation status for a specific language
		 *
		 * @param string $language Language code.
		 * @return array Status information for the language.
		 */
		public function get_language_status( $language ) {
			$status = array(
				'language'      => $language,
				'status'        => 'unknown',
				'file_exists'   => false,
				'last_updated'  => 0,
				'error_message' => '',
			);

			// Check if it's the default language (no translation needed).
			if ( 'en_US' === $language ) {
				$status['status']        = 'no_translation_needed';
				$status['error_message'] = 'Default language - no translation required';
				return $status;
			}

			// Check if translation file exists locally.
			$local_file = $this->languages_dir . 'complianz-gdpr-' . $language . '.mo';
			if ( file_exists( $local_file ) ) {
				$status['file_exists']  = true;
				$status['last_updated'] = filemtime( $local_file );
				$status['status']       = 'available';
			} else {
				$status['status'] = 'not_fetched';
			}

			// Check if we have stored errors for this language.
			$translation_errors = get_option( 'cmplz_translation_errors', array() );
			foreach ( $translation_errors as $error ) {
				// Handle both old string format and new array format.
				if ( is_array( $error ) && isset( $error['language'] ) && $error['language'] === $language ) {
					$status['error_message'] = $error['error'];

					// Determine status based on error message patterns.
					if ( strpos( $error['error'], 'No translation bundle found' ) !== false ) {
						if ( strpos( $error['error'], 'No versions available for this language' ) !== false ) {
							$status['status'] = 'not_supported';
						} else {
							$status['status'] = 'version_mismatch';
						}
					} elseif ( strpos( $error['error'], 'Invalid language format' ) !== false ) {
						$status['status'] = 'invalid_format';
					} elseif ( strpos( $error['error'], 'is not available in the translation system' ) !== false ) {
						$status['status'] = 'not_supported';
					} elseif ( strpos( $error['error'], 'Language' ) !== false && strpos( $error['error'], 'not available' ) !== false ) {
						$status['status'] = 'not_supported';
					} else {
						$status['status'] = 'error';
					}
					break;
				} elseif ( is_string( $error ) && strpos( $error, 'SSL' ) !== false ) {
					// Skip SSL errors as they're not language-specific.
					continue;
				}
			}

			return $status;
		}

		/**
		 * Clear stored errors
		 *
		 * @return bool True on success, false on failure.
		 */
		public function clear_errors() {
			return delete_option( 'cmplz_translation_errors' );
		}

		/**
		 * Add translations menu to the menu array.
		 *
		 * @param array $menu The menu array.
		 * @return array The modified menu array.
		 */
		public function add_translations_menu( $menu ) {
			foreach ( $menu as $key => $value ) {
				if ( 'tools' === $value['id'] ) {
					$menu[ $key ]['menu_items'][] = array(
						'id'       => 'translations',
						'title'    => __( 'Translations', 'complianz-gdpr' ),
						'helpLink' => 'https://complianz.io/translations/',
						'groups'   => array(
							array(
								'id'    => 'translation-management',
								'title' => __( 'Translation Management', 'complianz-gdpr' ),
								'intro' => __( 'Manage translation files for all detected languages. View status, manually fetch updates and configure automatic updates.', 'complianz-gdpr' ),
							),
						),
					);
				}
			}
			return $menu;
		}
	}
}

// Initialize translation actions.
require_once __DIR__ . '/class-translation-actions.php';
new CMPLZ_Translation_Actions();

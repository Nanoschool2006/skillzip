<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Plugin Name: Complianz Privacy Suite (GDPR/CCPA) premium
 * Plugin URI: https://complianz.io/pricing/
 * Description: Plugin to help you make your website GDPR/CCPa compliant
 * Version: 7.5.7.1
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Text Domain: complianz-gdpr
 * Domain Path: /languages
 * Author: Complianz
 * Author URI: https://complianz.io
 */

/*
	Copyright 2018-2025 Complianz.io (email : support@complianz.io)
	This product includes GeoLite2 data created by MaxMind, available from
	http://www.maxmind.com.
*/

defined( 'ABSPATH' ) || die( 'You do not have access to this page!' );


if ( ! function_exists( 'cmplz_set_activation_time_stamp' ) ) {
	/**
	 * Set an activation time stamp.
	 *
	 * @param bool $networkwide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	function cmplz_set_activation_time_stamp( $networkwide ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- We need this parameter for the activation hook
		update_option( 'cmplz_activation_time', time() );
		update_option( 'cmplz_run_activation', true, false );
		set_transient( 'cmplz_redirect_to_settings_page', true, HOUR_IN_SECONDS );
	}

	register_activation_hook( __FILE__, 'cmplz_set_activation_time_stamp' );
}

/**
 * Instantiate plugin
 */
if ( ! class_exists( 'COMPLIANZ' ) ) {

	/**
	 * Class COMPLIANZ
	 */
	class COMPLIANZ { // phpcs:ignore Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace

		/**
		 * Instance.
		 *
		 * @var COMPLIANZ
		 */
		public static $instance;

		/**
		 * Config instance.
		 *
		 * @var cmplz_config
		 */
		public static $config;

		/**
		 * Company instance.
		 *
		 * @var cmplz_company
		 */
		public static $company;

		/**
		 * Review instance.
		 *
		 * @var cmplz_review
		 */
		public static $review;

		/**
		 * Admin instance.
		 *
		 * @var cmplz_admin
		 */
		public static $admin;

		/**
		 * Scan instance.
		 *
		 * @var cmplz_scan
		 */
		public static $scan;

		/**
		 * Sync instance.
		 *
		 * @var cmplz_sync
		 */
		public static $sync;

		/**
		 * Wizard instance.
		 *
		 * @var cmplz_wizard
		 */
		public static $wizard;

		/**
		 * Export settings instance.
		 *
		 * @var cmplz_export_settings
		 */
		public static $export_settings;

		/**
		 * Upgrade to pro instance.
		 *
		 * @var cmplz_upgrade_to_pro
		 */
		public static $rsp_upgrade_to_pro;

		/**
		 * Comments instance.
		 *
		 * @var cmplz_comments
		 */
		public static $comments;

		/**
		 * Processing instance.
		 *
		 * @var cmplz_processing
		 */
		public static $processing;

		/**
		 * Dataleak instance.
		 *
		 * @var cmplz_dataleak
		 */
		public static $dataleak;

		/**
		 * Import settings instance.
		 *
		 * @var cmplz_import_settings
		 */
		public static $import_settings;

		/**
		 * License instance.
		 *
		 * @var cmplz_license
		 */
		public static $license;

		/**
		 * Banner loader instance.
		 *
		 * @var cmplz_banner_loader
		 */
		public static $banner_loader;

		/**
		 * GeoIP instance.
		 *
		 * @var cmplz_geoip
		 */
		public static $geoip;

		/**
		 * Statistics instance.
		 *
		 * @var cmplz_statistics
		 */
		public static $statistics;

		/**
		 * Admin statistics instance.
		 *
		 * @var cmplz_admin_statistics
		 */
		public static $admin_statistics;

		/**
		 * Document instance.
		 *
		 * @var cmplz_document
		 */
		public static $document;

		/**
		 * Cookie blocker instance.
		 *
		 * @var cmplz_cookie_blocker
		 */
		public static $cookie_blocker;

		/**
		 * Progress instance.
		 *
		 * @var cmplz_progress
		 */
		public static $progress;

		/**
		 * DNSMPD instance.
		 *
		 * @var cmplz_DNSMPD
		 */
		public static $DNSMPD; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		/**
		 * Admin DNSMPD instance.
		 *
		 * @var cmplz_admin_DNSMPD
		 */
		public static $admin_DNSMPD; // phpcs:ignore WordPress.NamingConventions.ValidVariableName

		/**
		 * Support instance.
		 *
		 * @var cmplz_support
		 */
		public static $support;

		/**
		 * Proof of consent instance.
		 *
		 * @var cmplz_proof_of_consent
		 */
		public static $proof_of_consent;

		/**
		 * Records of consent instance.
		 *
		 * @var cmplz_records_of_consent
		 */
		public static $records_of_consent;

		/**
		 * Documents admin instance.
		 *
		 * @var cmplz_documents_admin
		 */
		public static $documents_admin;

		/**
		 * Website scan instance.
		 *
		 * @var cmplz_wsc
		 */
		public static $websitescan;

		/**
		 * Website scan onboarding instance.
		 *
		 * @var cmplz_wsc_onboarding
		 */
		public static $wsc_onboarding;

		/**
		 * Website scan API instance.
		 *
		 * @var cmplz_wsc_api
		 */
		public static $wsc_api;

		/**
		 * Website scan scanner instance.
		 *
		 * @var cmplz_wsc_scanner
		 */
		public static $wsc_scanner;

		/**
		 * Translation fetcher.
		 *
		 * @var CMPLZ_Translation_Fetcher
		 */
		public static $translation_fetcher;


		/**
		 * COMPLIANZ constructor.
		 */
		private function __construct() {
			$this->run();
		}


		/**
		 * Instantiate the singleton class.
		 *
		 * @since 1.0.0
		 *
		 * @return COMPLIANZ
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof COMPLIANZ ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}


		/**
		 * Run the plugin.
		 *
		 * @return void
		 */
		public function run(): void {
			$this->define_constants();
			$this->includes();
			$this->instantiate_classes();
			$this->hooks();
		}


		/**
		 * Define plugin constants.
		 *
		 * @return void
		 */
		private function define_constants(): void {
			define( 'CMPLZ_PRODUCT_NAME', 'Complianz GDPR/CCPA Premium' );
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '#' . time() : '';
			define( 'CMPLZ_VERSION', '7.5.7.1' . $debug );
			define( 'CMPLZ_ITEM_ID', 994 );
			define( 'CMPLZ_URL', plugin_dir_url( __FILE__ ) );
			define( 'CMPLZ_PATH', plugin_dir_path( __FILE__ ) );
			define( 'CMPLZ_PLUGIN', plugin_basename( __FILE__ ) );
			define( 'CMPLZ_PLUGIN_FILE', __FILE__ );
			define( 'CMPLZ_MAIN_MENU_POSITION', 40 );

			define( 'CMPLZ_COOKIEDATABASE_URL', 'https://cookiedatabase.org/wp-json/cookiedatabase/' );

			// Define the plugin version.
			if ( ! defined( 'cmplz_premium' ) ) {
				define( 'cmplz_premium', true );
			}

			// Default region code.
			if ( ! defined( 'CMPLZ_DEFAULT_REGION' ) ) {
				define( 'CMPLZ_DEFAULT_REGION', 'us' );
			}

			// Statistics.
			if ( ! defined( 'CMPLZ_AB_TESTING_DURATION' ) ) {
				define( 'CMPLZ_AB_TESTING_DURATION', 30 ); // Days.
			}
		}


		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes(): void {
			require_once CMPLZ_PATH . 'documents/class-document.php';
			require_once CMPLZ_PATH . 'cookie/class-cookie.php';
			require_once CMPLZ_PATH . 'cookie/class-service.php';
			require_once CMPLZ_PATH . 'integrations/integrations.php';
			require_once CMPLZ_PATH . 'cron/cron.php';

			/* Gutenberg block */
			if ( cmplz_uses_gutenberg() ) {
				require_once plugin_dir_path( __FILE__ ) . 'gutenberg/block.php';
			}
			require_once plugin_dir_path( __FILE__ ) . 'rest-api/rest-api.php';
			require_once CMPLZ_PATH . '/pro/includes.php';

			if ( cmplz_admin_logged_in() ) {
				require_once CMPLZ_PATH . 'config/warnings.php';
				require_once CMPLZ_PATH . 'settings/settings.php';
				require_once CMPLZ_PATH . 'class-admin.php';
				require_once CMPLZ_PATH . 'class-review.php';
				require_once CMPLZ_PATH . 'progress/class-progress.php';
				require_once CMPLZ_PATH . 'cookiebanner/admin/cookiebanner.php';
				require_once CMPLZ_PATH . 'class-export.php';
				require_once CMPLZ_PATH . 'documents/admin-class-documents.php';
				require_once CMPLZ_PATH . 'settings/wizard.php';

				if ( isset( $_GET['install_pro'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					require_once CMPLZ_PATH . 'upgrade/upgrade-to-pro.php';
				}

				require_once CMPLZ_PATH . 'upgrade.php';
				require_once CMPLZ_PATH . 'DNSMPD/class-admin-DNSMPD.php';
				require_once CMPLZ_PATH . 'cookie/class-sync.php';

				// Include translation functionality
				if ( file_exists( CMPLZ_PATH . 'pro/translations/class-translations.php' ) ) {
					require_once CMPLZ_PATH . 'pro/translations/class-translations.php';
				} else {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'Translation classes file not found: ' . CMPLZ_PATH . 'pro/translations/class-translations.php' );
					}
				}
			}

			if ( cmplz_admin_logged_in() || cmplz_scan_in_progress() ) {
				require_once CMPLZ_PATH . 'cookie/class-scan.php';
			}

			if ( cmplz_admin_logged_in() ) {
				require_once CMPLZ_PATH . 'pro/class-licensing.php';
				require_once CMPLZ_PATH . 'websitescan/class-wsc.php';
			}

			require_once CMPLZ_PATH . 'pro/records-of-consent/class-records-of-consent.php';
			require_once CMPLZ_PATH . 'proof-of-consent/class-proof-of-consent.php';
			require_once CMPLZ_PATH . 'cookiebanner/class-cookiebanner.php';
			require_once CMPLZ_PATH . 'cookiebanner/class-banner-loader.php';
			require_once CMPLZ_PATH . 'class-company.php';
			require_once CMPLZ_PATH . 'DNSMPD/class-DNSMPD.php';
			require_once CMPLZ_PATH . 'config/class-config.php';
			require_once CMPLZ_PATH . 'class-cookie-blocker.php';
			require_once CMPLZ_PATH . 'websitescan/class-wsc-api.php';
			require_once CMPLZ_PATH . 'websitescan/class-wsc-scanner.php';
			require_once CMPLZ_PATH . 'mailer/class-mail.php';
		}


		/**
		 * Instantiate classes.
		 *
		 * @return void
		 */
		private function instantiate_classes(): void {
			self::$config  = new cmplz_config();
			self::$company = new cmplz_company();
			self::$DNSMPD  = new cmplz_DNSMPD(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			if ( cmplz_admin_logged_in() ) {
				self::$admin_DNSMPD    = new cmplz_admin_DNSMPD(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				self::$review          = new cmplz_review();
				self::$admin           = new cmplz_admin();
				self::$export_settings = new cmplz_export_settings();
				self::$progress        = new cmplz_progress();
				self::$documents_admin = new cmplz_documents_admin();
				self::$wizard          = new cmplz_wizard();
				self::$sync            = new cmplz_sync();

				/* pro instances */
				self::$comments         = new cmplz_comments();
				self::$import_settings  = new cmplz_import_settings();
				self::$support          = new cmplz_support();
				self::$processing       = new cmplz_processing();
				self::$dataleak         = new cmplz_dataleak();
				self::$admin_statistics = new cmplz_admin_statistics();
				self::$websitescan      = new cmplz_wsc();
				self::$wsc_onboarding   = new cmplz_wsc_onboarding();
				self::$translation_fetcher = CMPLZ_Translation_Fetcher::get_instance();
			}
			if ( cmplz_admin_logged_in() || cmplz_scan_in_progress() ) {
				self::$scan = new cmplz_scan();
			}
			if ( cmplz_admin_logged_in() ) {
				self::$license = new cmplz_license();
			}
			self::$records_of_consent = new cmplz_records_of_consent();
			self::$proof_of_consent   = new cmplz_proof_of_consent();
			self::$cookie_blocker     = new cmplz_cookie_blocker();
			self::$banner_loader      = new cmplz_banner_loader();
			self::$geoip              = new cmplz_geoip();
			self::$statistics         = new cmplz_statistics();
			self::$document           = new cmplz_document();
			self::$wsc_api            = new cmplz_wsc_api();
			self::$wsc_scanner        = new cmplz_wsc_scanner();
		}


		/**
		 * Add hooks.
		 *
		 * @return void
		 */
		private function hooks(): void {
			add_action( 'init', array( $this, 'load_textdomain' ), 0 );

			if ( wp_doing_ajax() ) {
				// using init on ajax calls, as wp is not running.
				add_action( 'init', 'cmplz_init_cookie_blocker' );
			} else {
				// has to be wp for all non ajax calls, because of AMP plugin.
				add_action( 'wp', 'cmplz_init_cookie_blocker' );
			}
		}


		/**
		 * Load plugin translations.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load_textdomain(): void {
			load_plugin_textdomain( 'complianz-gdpr', false, dirname( CMPLZ_PLUGIN ) . '/languages/' );
		}
	}

	/**
	 * Load the plugins main class.
	 */
	add_action(
		'plugins_loaded',
		function () {
			COMPLIANZ::get_instance();
		},
		9
	);
}

require_once plugin_dir_path( __FILE__ ) . 'functions.php';


if ( ! function_exists( 'cmplz_activation' ) ) {
	/**
	 * Handle some initializations when plugin is activated
	 */
	function cmplz_activation() {
		// only run once.
		if ( ! get_option( 'cmplz_run_premium_install' ) ) {
			update_option( 'cmplz_run_premium_install', 'start', false );
		}
		// run always.
		set_transient( 'cmplz_redirect_to_settings_page', true, HOUR_IN_SECONDS );
		update_option( 'cmplz_run_activation', true, false );
		update_option( 'cmplz_run_premium_upgrade', true, false );
		
		// Load translation class
		$translation_file = plugin_dir_path( __FILE__ ) . 'pro/translations/class-translations.php';
		if ( file_exists( $translation_file ) ) {
			require_once $translation_file;
			
			// Call the translations activation method 
			if ( class_exists( 'CMPLZ_Translation_Fetcher' ) ) {
				CMPLZ_Translation_Fetcher::on_activation();
			}
		}
		
		do_action( 'cmplz_activation' );
	}
	register_activation_hook( __FILE__, 'cmplz_activation' );
}

if ( ! function_exists( 'cmplz_is_logged_in_rest' ) ) {
	/**
	 * Check if a user is logged in for Complianz REST API requests.
	 *
	 * This function determines whether the current request is related to
	 * the Complianz REST API (`/complianz/v1/`) and, if so, checks if a user
	 * is logged in.
	 *
	 * @return bool True if the request is for the Complianz REST API and the user is logged in, false otherwise.
	 */
	function cmplz_is_logged_in_rest() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$is_settings_page_request = isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/complianz/v1/' ) !== false;
		if ( ! $is_settings_page_request ) {
			return false;
		}

		return is_user_logged_in();
	}
}

if ( ! function_exists( 'cmplz_admin_logged_in' ) ) {
	/**
	 * Check if an admin user is logged in.
	 */
	function cmplz_admin_logged_in() {
		$wpcli = defined( 'WP_CLI' ) && WP_CLI;
		return ( is_admin() && cmplz_user_can_manage() ) || cmplz_is_logged_in_rest() || wp_doing_cron() || $wpcli || defined( 'CMPLZ_DOING_SYSTEM_STATUS' );
	}
}

if ( ! function_exists( 'cmplz_add_manage_privacy_capability' ) ) {
	/**
	 * Assign the 'manage_privacy' capability to administrators.
	 *
	 * This function ensures that the 'administrator' role has the 'manage_privacy' capability.
	 * If the site is part of a multisite network and `$handle_subsites` is true, the capability
	 * is also added to all subsites by recursively applying the function to each subsite.
	 *
	 * @param bool $handle_subsites Whether to apply the capability to all subsites in a multisite network.
	 *                              Default is true.
	 *
	 * @return void
	 */
	function cmplz_add_manage_privacy_capability( $handle_subsites = true ) {
		$capability = 'manage_privacy';
		$role       = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( $capability ) ) {
			$role->add_cap( $capability );
		}

		// we need to add this role across subsites as well.
		if ( $handle_subsites && is_multisite() ) {
			$sites = get_sites();
			if ( count( $sites ) > 0 ) {
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					cmplz_add_manage_privacy_capability( false );
					restore_current_blog();
				}
			}
		}
	}
	register_activation_hook( __FILE__, 'cmplz_add_manage_privacy_capability' );

	/**
	 * Assign Complianz management capabilities to a new subsite in a multisite network.
	 *
	 * @param WP_Site $site The newly created WordPress site object.
	 *
	 * @return void
	 */
	function cmplz_add_role_to_subsite( $site ) {
		switch_to_blog( $site->blog_id );
		cmplz_add_manage_privacy_capability( false );
		restore_current_blog();
	}
	add_action( 'wp_initialize_site', 'cmplz_add_role_to_subsite', 10, 1 );

}

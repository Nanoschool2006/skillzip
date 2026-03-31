<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

	class BridgeWoocommerce
	{

		/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      BridgeWoocommerceLoader    $loader    Maintains and registers all hooks for the plugin.
	 */
		protected $loader;

		/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
		protected $plugin_name;

		/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
		protected $version;

		/**
	*
	*
	* @var Bridge_Woocommerce The single instance of the class
	* @since 1.0.0
	*/
		protected static $_instance = null;

		/**
	* Main Bridge_Woocommerce Instance
	*
	* Ensures only one instance of Bridge_Woocommerce is loaded or can be loaded.
	*
	* @since 1.0.0
	* @static
	* @see bridge_woocommerce()
	* @return Bridge_Woocommerce - Main instance
	*/
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
		public function __construct() {
			global $wooint_plugin_data;

			$this->plugin_name = isset( $wooint_plugin_data['pluginSlug'] ) ? $wooint_plugin_data['pluginSlug'] : 'woocommerce_integration';
			$this->version     = isset( $wooint_plugin_data['pluginVersion'] ) ? $wooint_plugin_data['pluginVersion'] : '2.2.0'; 
			$this->defineConstants();
			$this->loadDependencies();
			$this->setLocale();
			$this->defineAdminHooks();
			$this->definePublicHooks();
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 1.0.0
		 * @return void
		 */
		private function defineConstants() {

			// Plugin version.
			if ( ! defined( 'BRIDGE_WOOCOMMERCE_VERSION' ) ) {
				define( 'BRIDGE_WOOCOMMERCE_VERSION', $this->version );
			}

			// Woocommerce version.
			if ( ! defined( 'WOOCOMMERCE_VERSION' ) ) {
				define( 'WOOCOMMERCE_VERSION', $this->getWoocommerceVersionNumber() );
			}

			// Plugin Folder URL.
			if ( ! defined( 'BRIDGE_WOOCOMMERCE_PLUGIN_URL' ) ) {
				define( 'BRIDGE_WOOCOMMERCE_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
			}

			// Plugin Folder Path.
			if ( ! defined( 'BRIDGE_WOOCOMMERCE_PLUGIN_DIR' ) ) {
				define( 'BRIDGE_WOOCOMMERCE_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
			}

			/**
			 * WooCommerce Subscriptions version - Useful to support compatibility with legacy version and to check WCS is activated or not.
			 *
			 * @since 1.1.3
			 */
			if ( ! defined( 'WOOINT_WCS_VER' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
				if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
					include_once WP_PLUGIN_DIR . '/woocommerce-subscriptions/woocommerce-subscriptions.php';
					define( 'WOOINT_WCS_VER', \WC_Subscriptions::$version );
				}
			}
		}

		/*
		* This function returns installed woocommerce version number
		*/
		public function getWoocommerceVersionNumber() {
			// If get_plugins() isn't available, require it.
			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Create the plugins folder and file variables.
			$plugin_folder = get_plugins( '/woocommerce' );
			$plugin_file   = 'woocommerce.php';

			// If the plugin version number is set, return it.
			if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
				return $plugin_folder[ $plugin_file ]['Version'];
			} else {
				// Otherwise return null.
				return null;
			}
		}

		/**
		 * Load the required dependencies for this plugin.
		 *
		 * Include the following files that make up the plugin:
		 *
		 * - Bridge_Woocommerce_Loader. Orchestrates the hooks of the plugin.
		 * - Bridge_Woocommerce_i18n. Defines internationalization functionality.
		 * - Bridge_Woocommerce_Admin. Defines all hooks for the admin area.
		 * - Bridge_Woocommerce_Public. Defines all hooks for the public side of the site.
		 * - Bridge_Woocommerce_Course. Defines all hooks for create/update product on Course create/update
		 * - Bridge_Woo_Product_Manager. Defines all hooks for Product meta save & shortcode embed on single product page.
		 * - Bridge_Woocommerce_Order_Manager. Defines all hooks for user enrollment as per Order status change
		 *
		 * Create an instance of the loader which will be used to register the hooks
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function loadDependencies() {
			if ( ! is_admin() ) {
				$this->frontendDependencies();
			}

			/**
			 * The core classes that initiates custom fields menu under edwiser bridge.
			 */
			require_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'admin/class-bridge-woo-int-custom-field-menus.php';

			/**
			 * The core classes that initiates custom fields table.
			 */
			require_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/custom-fields/class-bridge-wi-custom-fields-show.php';
			require_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/custom-fields/class-bridge-wi-custom-fields-save.php';
			require_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/custom-fields/class-bridge-wi-cf-frotnend-handler.php';
			require_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/custom-fields/class-bridge-wi-cf-mdl-sync-handler.php';

			/**
			 * File to load all the functions used multiple times in whole wooocommerce-integration plugin.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-functions.php';

			/**
			 * File responsible to perform all membership related functionalities.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-membership-handler.php';

			/**
			 * The class responsible for orchestrating the actions and filters of the
			 * core plugin.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-loader.php';

			/**
			 * The class responsible for defining internationalization functionality
			 * of the plugin.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-i18n.php';

			/**
			 * The class responsible for defining all actions that occur in the admin area.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'admin/class-bridge-woocommerce-admin.php';

			/**
			 * The class responsible for defining all actions that occur in the public-facing
			 * side of the site.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/class-bridge-woocommerce-public.php';

			$this->loader = new BridgeWoocommerceLoader();

			/*
			 *The class responsible for defining all actions that occur in both for
			 * course Product syncrhonization
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-course.php';

			/*
			 *The class responsible for defining all actions that occur for Product meta fields & other operation
			 */

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-product-manager.php';

						/*
			 *The class responsible for adding a moodle course filter in woocommerce dropdown
			 */

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-product-filter.php';

			/*
			 *The class responsible for defining all actions that occur Order completion & other operation
			 */

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-order-manager.php';

			/*
			 *The class responsible for defining all actions that occur for AJAX
			 */

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woocommerce-ajax.php';

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-email-template-manager.php';

			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-my-account-compatibility.php';

			/*
			 * Edwiser associated Products Api.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-product-api.php';

			/*
			 * Woo int update admin notice handler file.
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'admin/class-bridge-woo-int-admin-notice.php';

		}

		/**
		 * Public facing code
		 *
		 * Include the following files that make up the plugin:
		 * - Bridge_Woocommerce_Shortcodes. Defines set of shortcode.
		 * - Bridge_Woo_Shortcode_Associated_Courses. Defines output for associated courses.
		 *
		 * @return void
		 * @since    1.0.0
		 * @access   private
		 */
		private function frontendDependencies() {

			/**
			 * Tha classes responsible for defining shortcodes & templates
			 */
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/class-bridge-woocommerce-shortcodes.php';
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/shortcodes/class-bridge-woocommerce-shortcode-associated-courses.php';
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'public/shortcodes/class-bridge-woocommerce-shortcode-single-cart-checkout.php';
		}

		/**
		 * Define the locale for this plugin for internationalization.
		 *
		 * Uses the BridgeWoocommercei18n class in order to set the domain and to register the hook
		 * with WordPress.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function setLocale() {
			$plugin_i18n = new BridgeWoocommercei18n();
			$plugin_i18n->setDomain( 'woocommerce-integration' );

			$this->loader->addAction( 'plugins_loaded', $plugin_i18n, 'loadPluginTextdomain' );
		}

		/**
		 * Register all of the hooks related to the admin area functionality
		 * of the plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		private function defineAdminHooks() {
			include_once 'class-bridge-woo-get-plugin-data.php';

			global $wooint_plugin_data;

			$get_data_from_db = BridgeWooGetPluginData::getDataFromDb( $wooint_plugin_data );

			if ( 'available' === $get_data_from_db ) {
				$plugin_admin = new BridgeWoocommerceAdmin( $this->getPluginName(), $this->getVersion() );

				$this->loader->addAction( 'admin_enqueue_scripts', $plugin_admin, 'enqueueStyles' );
				$this->loader->addAction( 'admin_enqueue_scripts', $plugin_admin, 'enqueueScripts' );

				$custom_field_menu = new Bridge_Woo_Int_Menu();
				// Add menus.
				$this->loader->addAction( 'admin_menu', $custom_field_menu, 'custom_field_menu', 10 );

				$this->loader->addAction( 'admin_init', $plugin_admin, 'updateSettings' );

				$this->loader->addFilter( 'eb_get_settings_pages', $plugin_admin, 'addWooIntTab', 10, 1 );

				$this->loader->addFilter( 'post_row_actions', $plugin_admin, 'addContainsEnrolment', 10, 2 );

				$email_tmpl_manag = new EbWooIntTemplateManager();
				$this->loader->addFilter( 'eb_email_templates_list', $email_tmpl_manag, 'ebTemplatesList', 111 );
				$this->loader->addFilter( 'eb_email_template_constant', $email_tmpl_manag, 'ebTemplatesConstants', 111 );
				$this->loader->addFilter( 'eb_emailtmpl_content_before', $email_tmpl_manag, 'emailTemplateParser', 111 );
				$this->loader->addFilter( 'eb_reset_email_tmpl_content', $email_tmpl_manag, 'wdmParseEmailTemplate', 10, 2 );

				// Add product moodle course in woocommerce product filter dropdown.

				$prod_filter = new ProductFilter( $this->plugin_name, $this->version );

				// Add Associated Courses Column.
				$this->loader->addFilter( 'manage_product_posts_columns', $prod_filter, 'addCustomColumn' );

				$this->loader->addAction( 'manage_product_posts_custom_column', $prod_filter, 'addCustomColumnValue', 10, 2);

				$this->loader->addAction( 'wp_ajax_unenrol_check_status', $plugin_admin, 'unenrolCheckStatus' );
				$this->loader->addAction( 'wp_ajax_unenrol_update_html', $plugin_admin, 'unenrolUpdateHtml' );
				$this->loader->addAction( 'woocommerce_order_item_add_line_buttons', $plugin_admin, 'refundHtmlContent', 10, 1);
				$this->loader->addAction( 'woocommerce_order_refunded', $plugin_admin, 'orderRefunded', 10, 2);

				//Add Product synchronization setting

				$this->loader->addFilter( 'eb_get_sections_synchronization', $plugin_admin, 'bridgeWooAddProductSynchronizationSection', 10, 1);

				$this->loader->addFilter( 'eb_get_settings_synchronization', $plugin_admin, 'bridgeWooGetProductSynchronizationSetting', 10, 2);

				//Products Meta fields and other operation

				//$product_manager_woo_plugin = new BridgeWooProductManager($this->plugin_name, $this->version);
				$prod_manager_plugin = new BridgeWooProductManager($this->plugin_name, $this->version);
				// $this->loader->addAction( 'add_meta_boxes',$prod_manager_plugin,'register_meta_boxes' );
				$this->loader->addAction( 'save_post', $prod_manager_plugin, 'handlePostOptionsSave', 10);
				$this->loader->addAction( 'before_delete_post', $prod_manager_plugin, 'handlePostOptionsDelete', 10, 1);

				$this->loader->addFilter( 'woocommerce_product_data_tabs', $prod_manager_plugin, 'bridgeWooAddTab', 10, 1);
				$this->loader->addAction( 'woocommerce_product_data_panels', $prod_manager_plugin, 'bridgeWooAddDataPanel' );
				$this->loader->addAction( 'woocommerce_product_after_variable_attributes', $prod_manager_plugin, 'bridgeWooAddProductMetaVariation', 10, 3);

				$this->loader->addAction( 'woocommerce_save_product_variation', $prod_manager_plugin, 'bridgeWooSaveVariationMeta', 10, 2);

				//$this->loader->addAction( 'woocommerce_process_product_meta_variable',$prod_manager_plugin,'bridge_woo_save_variation_meta',10,2);

				//Enroll User on order status change

				//$order_manager_woo_plugin = new BridgeWoocommerceOrderManager($this->plugin_name, $this->version);
				$order_manager_plugin = new BridgeWoocommerceOrderManager($this->plugin_name, $this->version);

				// $this->loader->addAction( 'woocommerce_order_status_completed', $order_manager_plugin, 'handleOrderComplete', 10, 1);
				// $this->loader->addAction( 'woocommerce_order_status_cancelled', $order_manager_plugin, 'handleOrderCancel', 10, 1);
				// $this->loader->addAction( 'woocommerce_order_status_refunded', $order_manager_plugin, 'handleOrderCancel', 10, 1);

				/**
				 * One hook handles all statues
				 * @since 1.1.3
				 */
				$this->loader->addAction(
					'woocommerce_order_status_changed',
					$order_manager_plugin,
					'wcOrderStatusChanged',
					10,
					3
				);

				/*-----------------------------
				 * Membership Hooks
				 * -----------------------------
				 */

				$membershipHandler = new WooIntMembershipHandler($this->plugin_name, $this->version);
				$this->loader->addAction( 'wc_memberships_user_membership_status_changed', $membershipHandler, 'handleMembsershipStatusChange', 10, 3);

				//Below is to add membership_id column in the moodle_enrollment table
				$this->loader->addAction("admin_init", $membershipHandler, "addMembershipColumnInMoodleEnrollment");


				/*Membership Hooks end*/


				$this->loader->addFilter( 'pre_option_woocommerce_enable_guest_checkout', $order_manager_plugin, 'disableGuestCheckout', 10, 1);

				//Create / Link Moodle User

				$this->loader->addAction( 'woocommerce_checkout_order_processed', $order_manager_plugin, 'createMoodleUserForCreatedCustomer', 1, 2);

				$this->loader->addFilter( 'eb_filter_moodle_password', $order_manager_plugin, 'addUserSubmittedPassword', 10, 1);

				// WCS is active.
				if (defined( 'WOOINT_WCS_VER')) {
					if (version_compare(WOOINT_WCS_VER, '2.0', '>=')) {
						$this->loader->addAction( 'woocommerce_subscription_status_updated', $order_manager_plugin, 'wcsStatusUpdated', 111, 3);
					} else {
						/**
						 * Legacy hooks.
						 * @deprecated 1.1.3 Use woocommerce_subscription_status_updated
						 */
						$this->loader->addAction( 'activated_subscription', $order_manager_plugin, 'handleActivatedSubscription', 10, 2);
						$this->loader->addAction( 'cancelled_subscription', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
						$this->loader->addAction( 'subscription_expired', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
						$this->loader->addAction( 'subscription_put_on-hold', $order_manager_plugin, 'handleCancelledSubscription', 10, 2);
					}
				}



				//Admin notice handler

				$admin_notice_handler = new Bridge_Woo_Int_Admin_Notice_Handler();

				$this->loader->addAction(
					'admin_notices',
					$admin_notice_handler,
					'wi_admin_update_notice'
				);

				// add_action( 'admin_init', 'app\wisdmlabs\edwiserBridge\ebAdminUpdateNoticeDismissHandler' );
				$this->loader->addAction(
					'admin_init',
					$admin_notice_handler,
					'wi_admin_update_notice_dismiss_handler'
				);



			}
		}

		/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
		private function definePublicHooks()
		{
			include_once( 'class-bridge-woo-get-plugin-data.php' );

			global $wooint_plugin_data;
			$get_data_from_db = BridgeWooGetPluginData::getDataFromDb($wooint_plugin_data);

			if ( 'available' == $get_data_from_db ) {
				if ( ! is_admin() ) {
					add_action( 'init', array( 'NmBridgeWoocommerce\BridgeWoocommerceShortcodes', 'init' ));
				}

				$plugin_public = new BridgeWoocommercePublic($this->getPluginName(), $this->getVersion());

				$this->loader->addAction( 'wp_enqueue_scripts', $plugin_public, 'enqueueStyles' );
				$this->loader->addAction( 'wp_enqueue_scripts', $plugin_public, 'enqueueScripts' );

				//Display associated courses on single product page as well as in Order email

				$this->loader->addAction( 'woocommerce_single_product_summary', $plugin_public, 'displayProductRelatedCourses', 10);
				$this->loader->addAction(
					'woocommerce_grouped_product_list_before_price',
					$plugin_public,
					'groupedProductDisplayAssociatedCourses',
					10,
					1
				);
				$this->loader->addAction( 'woocommerce_email_after_order_table', $plugin_public, 'sendAssociatedCoursesInEmail', 10, 3);

				// To mark Create an account? checkbox as checked and then hide the option for non-logged user who have edwiser products in cart
				// Added this hook to include all checkout page css.	
				$this->loader->addFilter( 'woocommerce_is_checkout', $plugin_public, 'isSingleCartCheckout', 111, 1 );

				// Hook to rdirect to the single checkout page if cart is updated from the same page.
				// Hook :: woocommerce_get_cart_url
				$this->loader->addFilter( 'woocommerce_get_cart_url', $plugin_public, 'eb_get_one_click_checkout_url', 111, 1 );

				$this->loader->addFilter(
					'woocommerce_thankyou_order_received_text',
					$plugin_public,
					'thankYouOrderReceivedText',
					90,
					2
				);
				$this->loader->addFilter(
					'eb_user_orders',
					$plugin_public,
					'addWoocomerceOrdersToUserAccountPage',
					10,
					1
				);

				$eb_general = get_option( 'eb_woo_int_settings', array());

				$buy_now_enabled = isset($eb_general['wi_enable_buynow']) && $eb_general['wi_enable_buynow'] === 'yes' ? true : false;

				if ($buy_now_enabled) {
					$this->loader->addAction(
						'woocommerce_after_add_to_cart_button',
						$plugin_public,
						'productPageAfterAddToCart'
					);

					$this->loader->addAction(
						'woocommerce_after_shop_loop_item',
						$plugin_public,
						'shopPageAfterAddToCart',
						11
					);

					$this->loader->addFilter(
						'woocommerce_add_to_cart_redirect',
						$plugin_public,
						'buyNowRedirect',
						10,
						1
					);
				}

				$purchase_for_someone_else = isset($eb_general['wi_enable_purchase_for_someone_else']) && $eb_general['wi_enable_purchase_for_someone_else'] === 'yes' ? true : false;

				if ( $purchase_for_someone_else ) {
					$this->loader->addAction(
						'woocommerce_before_order_notes',
						$plugin_public,
						'wi_add_purchase_for_someone_else_input_fields'
					);

					$this->loader->addAction(
						'woocommerce_checkout_process',
						$plugin_public,
						'wi_validate_purchase_for_someone_else_input_fields'
					);
				}

				// Woocommerce My-account page Hooks.
				$woo_my_account_operations = new Bridge_Woo_My_Account_Page_Handler();
				$this->loader->addAction(
					'woocommerce_created_customer',
					$woo_my_account_operations,
					'my_account_page_user_creation',
					10,
					3
				);

				$this->loader->addAction(
					'woocommerce_save_account_details',
					$woo_my_account_operations,
					'wi_my_account_user_profile_update',
					10,
					1
				);

				$this->loader->addAction(
					'woocommerce_save_account_details_errors',
					$woo_my_account_operations,
					'validate_my_account_page_fields',
					10,
					2
				);

				$this->loader->addAction(
					'woocommerce_register_form_start',
					$woo_my_account_operations,
					'wi_add_name_fields_woo_account_registration',
					10
				);


				$this->loader->addAction(
					'woocommerce_registration_errors',
					$woo_my_account_operations,
					'wi_validate_name_fields',
					10,
					3
				);


				$this->loader->addAction(
					'init',
					$woo_my_account_operations,
					'wi_add_my_courses_endpoint',
					10
				);

				$this->loader->addAction(
					'query_vars',
					$woo_my_account_operations,
					'wi_add_my_courses_query_vars',
					10,
					1
				);

				$this->loader->addAction(
					'woocommerce_account_menu_items',
					$woo_my_account_operations,
					'wi_add_my_courses_link_my_account',
					10,
					1
				);

				$this->loader->addAction(
					'woocommerce_account_eb_my_courses_endpoint',
					$woo_my_account_operations,
					'wi_add_my_courses_content',
					10
				);

				$this->loader->addAction(
					'wp_loaded',
					$woo_my_account_operations,
					'wi_flush_rewrite_rules',
					10
				);

				/*
				 * Edwiser Product API related Hooks
				 */
				$api_handler = new Bridge_Woo_Products_API();
				$this->loader->addAction( 'rest_api_init', $api_handler, "wi_get_edwiser_products_list");


				//migration code
                $custom_field_plugin_path = 'edwiser-custom-fields/edwiser-custom-fields.php';
                if( ! is_plugin_active( $custom_field_plugin_path ) ){
					// Custom fields related hooks.
					$custom_field_handler      = new Bridge_Wi_Cf_Frontend_Handler();
					$custom_field_sync_handler = new Bridge_Wi_Cf_Mdl_Sync_Handler();

					// woocommerce checkout page hook.
					$this->loader->addAction( 'woocommerce_after_order_notes', $custom_field_handler, "wi_show_fields_on_checkout_page");

					// Woo my accnt page hook.
					$this->loader->addAction( 'woocommerce_edit_account_form', $custom_field_handler, "wi_show_fields_on_woo_my_accnt_page");

					// Edwiser bridge user accnt hook. 
					$this->loader->addAction( 'eb_edit_user_profile', $custom_field_handler, "wi_show_fields_on_edwiser_user_accnt_page");

					// Moodle profile update Hooks.
					$this->loader->addFilter( 'eb_moodle_user_profile_details', $custom_field_sync_handler, "wi_cf_bridge_user_accnt_update", 10, 2);
					// $this->loader->addAction( 'woocommerce_checkout_process', $custom_field_sync_handler, "wi_cf_bridge_user_accnt_update");

					//woocommerce_checkout_process validate form fields here.
					$this->loader->addAction( 'woocommerce_checkout_process', $custom_field_handler, "wi_validate_fields_on_checkout");
				}

			}
		}

		/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
		public function run()
		{
			$this->loader->run();
		}

		/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
		public function getPluginName()
		{
			return $this->plugin_name;
		}

		/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    BridgeWoocommerceLoader    Orchestrates the hooks of the plugin.
	 */
		public function getLoader()
		{
			return $this->loader;
		}

		/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
		public function getVersion()
		{
			return $this->version;
		}
	}

/**
 * Returns the main instance of Bridge_Woocommerce to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Bridge_Woocommerce
 */
	function bridgeWoocommerce()
	{
		return BridgeWoocommerce::instance();
	}
}

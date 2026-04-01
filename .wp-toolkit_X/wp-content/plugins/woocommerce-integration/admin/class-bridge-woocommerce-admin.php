<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

	/**
	 * The admin-specific functionality of the plugin.
	 */
	class BridgeWoocommerceAdmin {

		/**
		 * The ID of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $plugin_name    The ID of this plugin.
		 */
		private $plugin_name;

		/**
		 * The version of this plugin.
		 *
		 * @since    1.0.0
		 * @access   private
		 * @var      string    $version    The current version of this plugin.
		 */
		private $version;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @since    1.0.0
		 * @var string $order_manager
		 */
		protected $order_manager;

		/**
		 * Initialize the class and set its properties.
		 *
		 * @param string $plugin_name The name of this plugin.
		 * @param string $version     The version of this plugin.
		 */
		public function __construct( $plugin_name, $version ) {
			$this->plugin_name = $plugin_name;
			$this->version     = $version;

			$this->order_manager = new BridgeWoocommerceOrderManager( $this->plugin_name, $this->version );
		}

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueueStyles() {

			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in BridgeWoocommerceLoader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The BridgeWoocommerceLoader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bridge-woocommerce-admin.css', array(), $this->version, 'all' );
		}

		/**
		 * Register the JavaScript for the admin area.
		 *
		 * @since    1.0.0
		 */
		public function enqueueScripts() {
			/**
			 * This function is provided for demonstration purposes only.
			 *
			 * An instance of this class should be passed to the run() function
			 * defined in BridgeWoocommerceLoader as all of the hooks are defined
			 * in that particular class.
			 *
			 * The BridgeWoocommerceLoader will then create the relationship
			 * between the defined hooks and the functions defined in this
			 * class.
			 */
			wp_register_script(
				'admin_product_js',
				BRIDGE_WOOCOMMERCE_PLUGIN_URL . 'admin/js/bridge-woocommerce-product.js',
				array( 'jquery' ),
				$this->version
			);

			wp_localize_script(
				'admin_product_js',
				'adminProduct',
				array(
					'placeholder' => __( 'Select any course', 'woocommerce-integration' ),
				)
			);

			wp_enqueue_script(
				$this->plugin_name,
				plugin_dir_url( __FILE__ ) . 'js/bridge-woocommerce-admin.js',
				array( 'jquery' ),
				$this->version,
				false
			);

			wp_enqueue_script(
				'eb_wi_custom_fields',
				plugin_dir_url( __FILE__ ) . 'js/bridge-woocommerce-custom-fields.js',
				array( 'jquery', 'jquery-ui-dialog', 'jquery-ui-sortable' ),
				$this->version,
				false
			);

			wp_localize_script(
				'eb_wi_custom_fields',
				'wi_custom_fields',
				array(
					'dialog_save_btn'              => __( 'Save Changes', 'woocommerce-integration' ),
					'dialog_cancel_btn'            => __( 'Cancel', 'woocommerce-integration' ),
					'dialog_option_value'          => __( 'Option Value', 'woocommerce-integration' ),
					'dialog_option_text'           => __( 'Option Text', 'woocommerce-integration' ),
					'dialog_field_name_validation' => __( 'Field name should be unique and not empty.', 'woocommerce-integration' ),
				)
			);

			wp_localize_script(
				$this->plugin_name,
				'adminStrings',
				array(
					'singleTrashWarning' => __( 'Some users are enrolled to this course. By trashing they will be unenrolled. Do you still want to continue?', 'woocommerce-integration' ),
					'bulkTrashWarning'   => __( 'Some users are enrolled in the selected courses. By trashing they will be unenrolled. Do you still want to continue?', 'woocommerce-integration' ),
				)
			);

			wp_register_script(
				'admin_refund_js',
				BRIDGE_WOOCOMMERCE_PLUGIN_URL . 'admin/js/bridge-woocommerce-refund.js',
				array( 'jquery' ),
				$this->version
			);
		}

		public function addWooIntTab( $settings ) {
			$settings[] = include BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'admin/settings/class-bridge-woocommerce-settings.php';

			return $settings;
		}

		/*
		 * Add "Products" tab in course synchronization after "Course"
		 *
		 * @param $section  array  List of section in synchronize tab
		 * @return $section array Modified array with "Product" tab
		 * @since 1.0.2
		 */
		public function bridgeWooAddProductSynchronizationSection( $section ) {
			if ( count( $section ) > 1 ) {
				$result = array_merge(
					array_slice( $section, 0, 1 ),
					array( 'product_data' => __( 'Products', 'woocommerce-integration' ) ),
					array_slice( $section, 1, null )
				);
			} else {
				$result = array( 'product_data' => __( 'Products', 'woocommerce-integration' ) );
			}

			return $result;
		}

		/*
		 * Add fields in "Products" tab
		 *
		 * @param $settings array List of settings fields
		 * @param $current_section string Gives current displayed section
		 *
		 * @return $settings array Modified array with settings for Product section
		 * @since 1.0.2
		 */
		public function bridgeWooGetProductSynchronizationSetting( $settings, $current_section) {
			if ( 'product_data' === $current_section ) {
				$settings = apply_filters(
					'bridge_woo_product_synchronization_settings',
					array(
						array(
							'title' => __( 'Synchronize Products', 'woocommerce-integration' ),
							'type'  => 'title',
							'id'    => 'product_synchronization_options',
						),
						array(
							'title'           => __( 'WooCommerce Synchronization Options', 'woocommerce-integration' ),
							'desc'            => __( 'Create courses as products.', 'woocommerce-integration' ),
							'id'              => 'bridge_woo_synchronize_product_create',
							'default'         => 'no',
							'type'            => 'checkbox',
							'checkboxgroup'   => 'start',
							'show_if_checked' => 'yes',
							'autoload'        => false,
						),
						array(
							'desc'            => __( 'Update courses as products.', 'woocommerce-integration' ),
							'id'              => 'bridge_woo_synchronize_product_update',
							'default'         => 'no',
							'type'            => 'checkbox',
							'checkboxgroup'   => '',
							'show_if_checked' => 'yes',
							'autoload'        => false,
						),
						array(
							'desc'            => __( 'Publish synchronized products.', 'woocommerce-integration' ),
							'id'              => 'bridge_woo_synchronize_product_publish',
							'default'         => 'no',
							'type'            => 'checkbox',
							'checkboxgroup'   => '',
							'show_if_checked' => 'yes',
							'autoload'        => false,
						),
						array(
							'desc'            => __( 'Synchronize categories.', 'woocommerce-integration' ),
							'id'              => 'bridge_woo_synchronize_product_categories',
							'default'         => 'no',
							'type'            => 'checkbox',
							'checkboxgroup'   => '',
							'show_if_checked' => 'yes',
							'autoload'        => false,
						),
						array(
							'title'    => '',
							'desc'     => '',
							'id'       => 'bridge_woo_synchronize_product_button',
							'default'  => 'Start Synchronization',
							'type'     => 'button',
							'desc_tip' => false,
							'class'    => 'button secondary',
						),

						array(
							'type' => 'sectionend',
							'id'   => 'product_synchronization_options',
						),
					)
				);

				// Enqueue Script.

				$nonce = wp_create_nonce( 'check_product_sync_action' );

				wp_enqueue_script( 'synchronization_handler', plugin_dir_url( __FILE__ ) . 'js/bridge-woocommerce-synchronize.js', array( 'jquery' ), $this->version, false );

				wp_localize_script(
					'synchronization_handler',
					'bridge_woo_product_obj',
					array(
						'product_sync_nonce'          => $nonce,
						'admin_ajax_path'             => admin_url( 'admin-ajax.php' ),
						'alt_text'                    => __( 'Loading...', 'woocommerce-integration' ),
						'select_least_option_message' => __( 'Please select proper options.', 'woocommerce-integration' ),
					)
				);
			}

			return $settings;
		}

		public function unenrolCheckStatus() {
			check_ajax_referer( 'wi_refund_unenrol', 'security' );
			$checked  = isset( $_POST['unenrol'] ) && 'checked' === $_POST['unenrol'] ? 'checked' : '';
			$order_id = absint( $_POST['order_id'] ); // @codingStandardsIgnoreLine

			update_post_meta( $order_id, 'wi_refund_checked', $checked );

			$response_data['status'] = 'updated';
			wp_send_json_success( $response_data );
		}

		public function unenrolUpdateHtml() {
			check_ajax_referer( 'wi_refund_unenrol', 'security' );

			$order_id = absint( $_POST['order_id'] ); // @codingStandardsIgnoreLine

			$is_processed             = get_post_meta( $order_id, '_is_processed', true );
			$response_data['display'] = empty( $is_processed ) ? 'false' : 'true';

			wp_send_json_success( $response_data );
		}

		public function refundHtmlContent( $order ) {
			$enrolled_courses = array();
			$order_id         = $order->get_id();
			$user_id          = get_post_meta( $order_id, '_customer_user', true );

			$is_for_some_one_else = get_post_meta($order_id, '_order_for_someone_else', true);
			if ( 'yes' === $is_for_some_one_else ) {
				$user_email = get_post_meta($order_id, '_recipient_email', true);
				$user = get_user_by('email', $user_email);
				$user_id = $user->ID;
			}

			$courses = $this->order_manager->_getMoodleCourseIdsForOrder( $order );

			foreach ( (array) $courses as $course_id ) {
				if ( \app\wisdmlabs\edwiserBridge\edwiserBridgeInstance()->enrollmentManager()->userHasCourseAccess( $user_id, $course_id ) ) {
					$enrolled_courses[] = $course_id;
				}
			}

			// The order does not contain courses in which the user is enrolled.
			if ( ! count( $enrolled_courses ) ) {
				return;
			}

			update_post_meta( $order_id, 'wi_refund_checked', '' );
			$checked = get_post_meta( $order_id, 'wi_refund_checked', true );

			ob_start();
			?>
			<div class="wi-refund-wrapper">
				<table class="wc-order-totals">
					<tr title="<?php esc_attr_e( 'You cannot rollback this action!', 'woocommerce-integration' ); ?>">
						<td class="label">
							<label for="wi_unenrol"><?php esc_attr_e( 'Unenroll from purchased courses?', 'woocommerce-integration' ); ?></label>
						</td>
						<td class="total">
							<input type="checkbox" class="text" id="wi_unenrol" name="wi_unenrol" <?php echo $checked; ?> />
							<div class="clear"></div>
						</td>
					</tr>
				</table>
				<input type="hidden" id="wi_order_id" name="wi_order_id" value="<?php echo $order_id; ?>" />
				<?php wp_nonce_field( 'wi_refund_unenrol', 'wi_refund_unenrol' ); ?>
				<div class="clear"></div>
			</div>
			<?php
			$html = ob_get_clean();

			wp_localize_script(
				'admin_refund_js',
				'wiRefund',
				array(
					'order' => $order,
					'html'  => $html,
				)
			);

			wp_enqueue_script( 'admin_refund_js' );
		}

		public function orderRefunded( $order_id, $refund_id ) {
			$order    = new \WC_Order( $order_id );
			$order_id = $order->get_id();
			$courses  = $this->order_manager->_getMoodleCourseIdsForOrder( $order );

			// Does not contain course product.
			if ( ! count( $courses ) ) {
				return;
			}
			$checked = get_post_meta( $order_id, 'wi_refund_checked', true );
			if ( 'checked' === $checked ) {
				$this->order_manager->handleOrderCancel( $order_id );
				wp_localize_script(
					'admin_refund_js',
					'wiRefunded',
					array( 'display' => false )
				);
			}

			do_action( 'wooint_order_refunded', $order_id, $refund_id, $checked, $courses );
		}

		public function addContainsEnrolment( $actions, $post ) {
			if ( 'eb_course' === $post->post_type && isset( $actions['trash'] ) ) {
				global $wpdb;
				$enrols = $wpdb->get_row( "SELECT user_id FROM {$wpdb->prefix}moodle_enrollment WHERE course_id={$post->ID}", ARRAY_A ); // @codingStandardsIgnoreLine

				if ( isset( $enrols ) && is_array( $enrols ) && count( $enrols ) ) {
					$actions['trash'] = str_replace( 'submitdelete', 'submitdelete contains_enrolment', $actions['trash'] );
				}
			}
			return $actions;
		}

		/**
		 * Function to update enable redirect to yes by default on init
		 *
		 * @since    1.0.0
		 * @access   private
		 */
		public function updateSettings() {
			$setting_woo_integration = get_option( 'eb_woo_int_settings', array() );
			$setting_keys            = array_keys( $setting_woo_integration );
			if ( ! in_array( 'wi_enable_redirect', $setting_keys, true ) ) {
				$setting_woo_integration['wi_enable_redirect'] = 'yes';
			}

			if ( ! in_array( 'wi_enable_asso_courses', $setting_keys, true ) ) {
				$setting_woo_integration['wi_enable_asso_courses'] = 'yes';
			}

			if ( ! in_array( 'wi_on_subscription_expiration', $setting_keys, true ) ) {
				$setting_woo_integration['wi_on_subscription_expiration'] = 'do-nothing';
			}

			if ( ! in_array( 'wi_on_membership_expired', $setting_keys, true ) ) {
				$setting_woo_integration['wi_on_membership_expired'] = 'do-nothing';
			}

			if ( ! in_array( 'wi_on_membership_cancelled', $setting_keys, true ) ) {
				$setting_woo_integration['wi_on_membership_cancelled'] = 'do-nothing';
			}

			update_option( 'eb_woo_int_settings', $setting_woo_integration );
		}
	}
}

<?php
/**
 * WooCommerce Integration Settings.
 *
 * @package    WooCommerce Integration
 * @subpackage WooCommerce Integration/admin/settings
 * @author     <>
 */

namespace app\wisdmlabs\edwiserBridge;

/*
 * EDW General Settings
 *
 * @link       https://edwiser.org
 * @since      1.0.0
 *
 * @package    Edwiser Bridge
 * @subpackage Edwiser Bridge/admin
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WooIntSettings' ) ) :

	/**
	 * WooIntSettings.
	 */
	class WooIntSettings extends EBSettingsPage {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->_id   = 'woo_int_settings';
			$this->label = __( 'Woo Integration', 'woocommerce-integration' );

			add_filter( 'eb_settings_tabs_array', array( $this, 'addSettingsPage' ), 20 );
			add_action( 'eb_settings_' . $this->_id, array( $this, 'output' ) );
			add_action( 'eb_settings_save_' . $this->_id, array( $this, 'save' ) );
		}

		/**
		 * Get settings array.
		 *
		 * @since  1.0.0
		 *
		 * @return array
		 */
		public function getSettings() {
			$settings = apply_filters(
				'wooint_settings_fields',
				array(
					array(
						'title' => __( 'WooCommerce Integration Options', 'woocommerce-integration' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wooint_options',
					),
					// Adding Enable Redirection Option On Checkout Page.
					array(
						'title'    => __( 'Enable Redirection', 'woocommerce-integration' ),
						'desc'     => __( 'This enables user to redirect to <strong>My Courses</strong> page after order completion.', 'woocommerce-integration' ),
						'id'       => 'wi_enable_redirect',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Associated Courses Section', 'woocommerce-integration' ),
						'desc'     => __( 'This shows the associated courses section on the single product page.', 'woocommerce-integration' ),
						'id'       => 'wi_enable_asso_courses',
						'default'  => 'yes',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'One Click Checkout', 'woocommerce-integration' ),
						'desc'     => __( 'This enables <strong>Buy Now</strong> button for simple products. Using this, users will be directly redirected to <strong>Single Cart Checkout</strong> page and the product will be added to their cart.', 'woocommerce-integration' ),
						'id'       => 'wi_enable_buynow',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'   => __( 'Buy Now Button Text', 'woocommerce-integration' ),
						'desc'    => '<br />' . __( 'This text will be shown on <strong>Buy Now</strong> button. Default will be <strong>Buy Now</strong>.', 'woocommerce-integration' ),
						'id'      => 'wi_buy_now_text',
						'default' => __( 'Buy Now', 'woocommerce-integration' ),
						'type'    => 'text',
						'css'     => 'min-width:300px;',
					),
					array(
						'title'   => __( 'Single Cart Checkout Page', 'woocommerce-integration' ),
						'desc'    => '<br/>' . __( 'Add shortcode <code>[bridge_woo_single_cart_checkout]</code> in the selected page.', 'woocommerce-integration' ),
						'id'      => 'wi_scc_page_id',
						'type'    => 'single_select_page',
						'default' => '',
						'css'     => 'min-width:300px;',
						'args'    => array(
							'show_option_none'  => __( '- Select a page -', 'woocommerce-integration' ),
							'option_none_value' => '',
						),
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooint_options',
					),
					array(
						'title' => __( 'WooCommerce My Account Page Settings', 'woocommerce-integration' ),
						'type'  => 'title',
						'desc'  => '',
						'id'    => 'wooint_myaccount_page_settings',
					),
					array(
						'title'    => __( 'Enable Account Creation', 'woocommerce-integration' ),
						'desc'     => __( 'This enables <strong>User creation from woocommerce my account page.</strong>', 'woocommerce-integration' ),
						'id'       => 'wi_enable_my_account_user_creation',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Update My Account Fields On Moodle', 'woocommerce-integration' ),
						'desc'     => __( 'This enables updating fields on the Moodle from woocommerce My-account page.', 'woocommerce-integration' ),
						'id'       => 'wi_enable_my_account_field_update',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Show My Courses Page On My Account Page', 'woocommerce-integration' ),
						'desc'     => __( 'This will show My Courses tab in the My-account page. <b>Note : </b> Please update permalinks once again after enabling this setting.', 'woocommerce-integration' ),
						'id'       => 'wi_show_my_courses_on_my_account',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'title'    => __( 'Enable Purchase Product For Someone Else', 'woocommerce-integration' ),
						'desc'     => __( 'This enables purchase course for someone else on checkout page. User will be able to enroll other users.', 'woocommerce-integration' ),
						'id'       => 'wi_enable_purchase_for_someone_else',
						'default'  => 'no',
						'type'     => 'checkbox',
						'autoload' => false,
					),
					array(
						'type' => 'sectionend',
						'id'   => 'wooint_myaccount_page_settings',
					),

				)
			);
			$is_subscription_active = $this->checkWoocommerceSubscriptionIsActive();
			if ( $is_subscription_active ) {
				$settings[] = array(
					'title' => __( 'WooCommerce Subscriptions Settings', 'woocommerce-integration' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wooint_subscriptions',
				);

				$settings[] = array(
					'title'   => __( 'On Subscription Expiration', 'woocommerce-integration' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on expiration of Product Subscription.', 'woocommerce-integration' ),
					'id'      => 'wi_on_subscription_expiration',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'woocommerce-integration' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'type' => 'sectionend',
					'id'   => 'wooint_subscriptions',
				);
			}

			$is_membership_active = \NmBridgeWoocommerce\checkWoocommerceMembershipIsActive();
			if ( $is_membership_active ) {
				$settings[] = array(
					'title' => __( 'WooCommerce Membership Settings', 'woocommerce-integration' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'wooint_membership',
				);

				$settings[] = array(
					'title'   => __( 'On Membership Expiration', 'woocommerce-integration' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on Membership expiration.', 'woocommerce-integration' ),
					'id'      => 'wi_on_membership_expired',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'woocommerce-integration' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'title'   => __( 'On Membership Cancellation', 'woocommerce-integration' ),
					'desc'    => '<br/>' . __( 'Select an action to perform on Membership Cancellation.', 'woocommerce-integration' ),
					'id'      => 'wi_on_membership_cancelled',
					'type'    => 'select',
					'default' => '',
					'css'     => 'min-width:300px;',
					'options' => array(
						'suspend'    => 'Suspend',
						'unenroll'   => 'Unenroll',
						'do-nothing' => 'Do Nothing',
					),
					'args'    => array(
						'show_option_none'  => __( '- Select a page -', 'woocommerce-integration' ),
						'option_none_value' => '',
					),
				);
				$settings[] = array(
					'type' => 'sectionend',
					'id'   => 'wooint_membership',
				);
			}

			return apply_filters( 'eb_get_settings_' . $this->_id, $settings );
		}



		/**
		 * Functionality to check if the subscription is activated.
		 *
		 * @return bool true if activated else false.
		 */
		public function checkWoocommerceSubscriptionIsActive() {
			$array_of_activated_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );

			if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', $array_of_activated_plugins, true ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Save settings.
		 *
		 * @since  1.0.0
		 */
		public function save() {
			$settings = $this->getSettings();

			EbAdminSettings::saveFields( $settings );
		}
	}

endif;

return new WooIntSettings();

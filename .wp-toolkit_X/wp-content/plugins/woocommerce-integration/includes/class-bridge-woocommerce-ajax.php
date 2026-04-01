<?php

namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    if (! defined('ABSPATH')) {
        exit; // Exit if accessed directly
    }
/**
 * The file that defines Product operation
 *
 * A class definition that includes meta fields and operation related to WooCommerce Products
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.2
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */

/**
 *
 * This is used to define Product operation
 *
 *
 * @since      1.0.2
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

    class BridgeWoocommerceAjax
    {

        /**
         * Constructor
         */
        private $edwiser_bridge;
        public function __construct()
        {
            add_action('wp_ajax_nopriv_handle_product_synchronization', array( $this, 'handleProductSynchronizationCallback' ));
            add_action('wp_ajax_handle_product_synchronization', array( $this, 'handleProductSynchronizationCallback' ));
            // require_once EB_PLUGIN_DIR.'includes/class-eb.php';

            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

            $this->edwiser_bridge = new EdwiserBridge();
        }

        /**
         * This is Product synchronization AJAX callback
         * of the plugin.
         *
         * @since    1.0.2
         * @access   public
         */
        public function handleProductSynchronizationCallback()
        {
            $this->edwiser_bridge->logger()->add('product', 'Initiating Product sync process....'); // Add product updated log

            if (! isset($_POST['_wpnonce_field'])) {
                die('Busted!');
            }

            $nonce = isset($_POST['_wpnonce_field'])? esc_attr($_POST['_wpnonce_field']) : '';

            // verifying generated nonce we created earlier
            if (! wp_verify_nonce($nonce, 'check_product_sync_action')) {
                die('Busted !');
            }

            $sync_options = isset($_POST['sync_options'])? esc_attr($_POST['sync_options']): '';
            // get sync options
            $sync_options = json_decode(str_replace("\\", "", html_entity_decode($sync_options)), 1);
            $course_woo_plugin = new BridgeWoocommerceCourse(BridgeWoocommerce()->getPluginName(), BridgeWoocommerce()->getVersion());
            $response = $course_woo_plugin->bridgeWooProductSyncHandler($sync_options);

            echo json_encode($response);

            die();
        }
    }
    new BridgeWoocommerceAjax();
}

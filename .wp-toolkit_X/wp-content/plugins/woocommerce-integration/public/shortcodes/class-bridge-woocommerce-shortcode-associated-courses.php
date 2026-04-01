<?php
/**
 * The file that defines the Associated courses shortcode.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 */

/**
 * The file that defines the Associated Courses shortcodes.
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */


namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EbTemplateLoader;
    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWooShortcodeAssociatedCourses
    {
        /**
         * Get the shortcode content.
         *
         * @since  1.0.0
         *
         * @param array $atts
         *
         * @return string
         */
        public static function get($atts)
        {
            return BridgeWoocommerceShortcodes::shortcodeWrapper(array(__CLASS__, 'output'), $atts);
        }

        /**
         * Output the shortcode.
         *
         * @since  1.0.0
         *
         * @param array $atts
         */
        public static function output($atts)
        {
            // Including required files. 
            include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-functions.php';
            // require_once EB_PLUGIN_DIR.'includes/class-eb.php';

            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

            // require_once EB_PLUGIN_DIR.'public/class-eb-template-loader.php';
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';

            extract(shortcode_atts(array(
                    'product_id' => '',
                ), $atts));
            $edwiser_bridge = new EdwiserBridge();

            $plugin_tpl_loader = new EbTemplateLoader($edwiser_bridge->getPluginName(), $edwiser_bridge->getVersion());

            if (empty($product_id)) {
                $product_id = '';
            }
            $plugin_tpl_loader->wpGetTemplate(
                'associated-courses-product-page.php',
                array(
                    'product_id' => $product_id,
                ),
                '',
                BRIDGE_WOOCOMMERCE_PLUGIN_DIR.'public/templates/'
            );
        }
    }
}

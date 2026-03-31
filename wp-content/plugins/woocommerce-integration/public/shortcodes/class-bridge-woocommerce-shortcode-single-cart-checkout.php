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

    class SingleCartCheckout
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
            // Currently no arguments.
            extract(shortcode_atts(array(), $atts));

            //echo do_shortcode("[woocommerce_cart]") . do_shortcode("[woocommerce_checkout]");

            // require_once EB_PLUGIN_DIR.'includes/class-eb.php';
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';

            $ed_bdg = new EdwiserBridge();

            //require_once EB_PLUGIN_DIR.'public/class-eb-template-loader.php';
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/public/class-eb-template-loader.php';
            $tpl_loader = new EbTemplateLoader($ed_bdg->getPluginName(), $ed_bdg->getVersion());

            $tpl_loader->wpGetTemplate(
                'single-cart-checkout.php',
                array(),
                '',
                BRIDGE_WOOCOMMERCE_PLUGIN_DIR.'public/templates/'
            );
        }
    }
}

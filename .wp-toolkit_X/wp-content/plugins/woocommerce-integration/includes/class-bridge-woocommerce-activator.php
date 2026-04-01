<?php

/**
 * Fired during plugin activation
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    class BridgeWoocommerceActivator
    {

        /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
        public static function activate()
        {

             // create database tables
            self::createMoodleDbTables();

            self::addDefaultEmailTmplOptions();

        }

        /**
        * create required DB tables
        *
        * @since    1.0.0
        * @access public
        */

        public static function createMoodleDbTables()
        {
            global $wpdb;

            $woo_moo_course_tbl = $wpdb->prefix . 'woo_moodle_course';

            $table_present_result = $wpdb->get_var("SHOW TABLES LIKE '{$woo_moo_course_tbl}'");

            if (null === $table_present_result || $table_present_result != $woo_moo_course_tbl) {
                $charset_collate       = $wpdb->get_charset_collate();

                $woo_moo_course_table = "CREATE TABLE IF NOT EXISTS $woo_moo_course_tbl (
                
                    meta_id        bigint(20) AUTO_INCREMENT,
                    product_id bigint(20),
                    moodle_post_id bigint(20),
                    moodle_course_id bigint(20),
                    PRIMARY KEY id (meta_id)
                ) $charset_collate;";
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';

                dbDelta($woo_moo_course_table);

                $query = 'SELECT `post_id`,`meta_value`
							  FROM  `' . $wpdb->prefix . "postmeta` 
							  WHERE  `meta_key` LIKE  'product_options'";

                $result = $wpdb->get_results($query);

                if (! empty($result)) {
                    foreach ($result as $single_result) {
                        $product_options = unserialize($single_result->meta_value);

                        // if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id']) && isset($product_options['moodle_course_id']) && ! empty($product_options['moodle_course_id'])) {
                        if ( check_value_set($product_options, 'moodle_post_course_id') && check_value_set($product_options, 'moodle_course_id') ) {
                            foreach ($product_options['moodle_post_course_id'] as $key => $value) {
                                $moo_course_id_list = explode(',', $product_options['moodle_course_id']);

                                $wpdb->insert($woo_moo_course_tbl, array(
                                                'product_id' => $single_result->post_id,
                                                'moodle_post_id' => $value,
                                                'moodle_course_id' => $moo_course_id_list[ $key ],
                                            ));
                            }
                        }
                    }
                }//if end
            }
        }

        private static function addDefaultEmailTmplOptions()
        {
            $ebTmplManag = new EbWooIntTemplateManager();
            $bpPurchaseContent = $ebTmplManag->getWooIntDefaultNotification("eb_emailtmpl_woocommerce_moodle_course_notifn", true);

            if (get_option('eb_emailtmpl_woocommerce_moodle_course_notifn') == false) {
                update_option('eb_emailtmpl_woocommerce_moodle_course_notifn', $bpPurchaseContent);
                update_option('eb_emailtmpl_woocommerce_moodle_course_notifn_notify_allow', "ON");
            }

        }
    }
}

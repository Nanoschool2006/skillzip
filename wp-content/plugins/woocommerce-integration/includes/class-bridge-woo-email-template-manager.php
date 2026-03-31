<?php

namespace NmBridgeWoocommerce;

/**
 * The file that defines the core plugin class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link  www.wisdmlabs.com
 * @since 1.0.0
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
 *
 * @author     WisdmLabs, India <support@wisdmlabs.com>
 */
if (!class_exists("EbWooIntTemplateManager")) {

    class EbWooIntTemplateManager
    {

        public function wdmParseEmailTemplate($args)
        {
            return $this->handleTemplateRestore($args);
        }

        /**
         * Provides the functionality to handle the tempalte restore event
         * genrated by the Edwiser bridge plugin for the template.
         * Calles for the eb_reset_email_tmpl_content filter
         * @param array $args contains the tmpl_name(Key) and boolean value to restore the template or not
         * @return array of the tmpl_name(Key) and is_restored(boolean on sucessfull restored true, false othrewise.)
         */
        public function handleTemplateRestore($args)
        {
            $tmplKey = $args['tmpl_name'];
            switch ($tmplKey) {
                case "eb_emailtmpl_woocommerce_moodle_course_notifn":
                    $value = $this->getWooIntDefaultNotification('eb_emailtmpl_woocommerce_moodle_course_notifn', true);
                    break;
                default:
                    return $args;
            }
            $status = update_option($tmplKey, $value);
            if ($status) {
                $args['is_restored'] = true;
                return $args;
            } else {
                return $args;
            }
        }

        /**
         * Prepares the course enrollment email notification template content
         * @param string $tmplId template key
         * @param boolean $restore true to restore the templates default contend by default false
         * @return array array of template subject and content
         */
        public function getWooIntDefaultNotification($tmplId, $restore = false)
        {
            $data = get_option($tmplId);
            if ($data && !$restore) {
                return $data;
            }
            $data = array(
                'subject' =>  __('Moodle Course Enrollment.', 'woocommerce-integration'),
                'content' => $this->getWooIntMailDefaultBody()
            );
            return $data;
        }
        /**
         * Prepares the woocommerce moodle product purchase email body.
         * @return html woocommerce moodle product purchase email template
         */
        private function getWooIntMailDefaultBody()
        {
            ob_start();
            ?>
            <div style="background-color: #efefef; width: 100%; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;">
                <table id="template_container" style="padding-bottom: 20px; box-shadow: 0 0 0 3px rgba(0,0,0,0.025) !important; border-radius: 6px !important; background-color: #dfdfdf;" border="0" width="600" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td style="background-color: #1f397d; border-top-left-radius: 6px !important; border-top-right-radius: 6px !important; border-bottom: 0; font-family: Arial; font-weight: bold; line-height: 100%; vertical-align: middle;">
                                <h1 style="color: white; margin: 0; padding: 28px 24px; text-shadow: 0 1px 0 0; display: block; font-family: Arial; font-size: 30px; font-weight: bold; text-align: left; line-height: 150%;">
                                    <?php _e('Course Enrollment.', 'woocommerce-integration');
                                    ?>
                                </h1>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
                                <div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
                                    <?php
                                    printf(
                                        __('Hi %s', 'woocommerce-integration'),
                                        '{FIRST_NAME}'
                                    );
                                    ?>
                                </div>
                                <div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
                                <div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left; margin-top:3%; margin-bottom:3%;">
                                    <?php
                                    _e('Thank you for your order. You have been successfully enrolled in the following courses.', 'woocommerce-integration');
                                    ?>
                                </div>
                                <div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
                                <div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">
                                    <?php
                                    echo '{PRODUCT_LIST}';
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: center; border-top: 0; -webkit-border-radius: 6px;" align="center" valign="top"><span style="font-family: Arial; font-size: 12px;">{SITE_NAME}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php
            $content = ob_get_clean();

            return apply_filters('eb_woo_int_course_enroll_email_content', $content);
        }
        /**
         * Add email templates in the EB email list.
         *
         * @since 1.1.0
         */
        public function ebTemplatesList($list)
        {
            $list["eb_emailtmpl_woocommerce_moodle_course_notifn"] = __('Woocommerce Moodle Course Enrollment', 'woocommerce-integration');
            return $list;
        }

        /**
         * Add email template constants.
         *
         * @since 1.1.0
         */
        public function ebTemplatesConstants($constants)
        {
            $constants["Woocommerce Moodle Course Enrollment"]['{PRODUCT_LIST}'] = __('Products List', 'woocommerce-integration');
            return $constants;
        }

        /**
         * Callback for the eb_emailtmpl_content_before filter
         * @param array $data array of the default arguments provided by the send email action
         * and unparsed content
         * @return array returns the array of the default arguments and parsed content
         */
        public function emailTemplateParser($data)
        {
            $args = $data['args'];
            if (empty($args) || count($args) <= 0) {
                $args = array(
                    "product_id" => "1",
                    "mdl_cohort_id" => "1",
                    "order_id" => 231,
                    "cohort_manager_id" => 1,
                );
            }
            $tmplContent = $data['content'];
            $tmplConst = $this->getTmplConstant($args);
            foreach ($tmplConst as $const => $val) {
                $tmplContent = str_replace($const, $val, $tmplContent);
            }
            return array("args" => $args, "content" => $tmplContent);
        }

        /**
         * Provides the functionality to get the values for the email temaplte constants
         *
         * @param array $args array of the default values for the constants to
         * prepare the email template content
         *
         * @return array returns the array of the email temaplte constants with
         * associated values for the constants
         */
        private function getTmplConstant($args)
        {
            $constants['{PRODUCT_LIST}'] = $this->getProductList($args);
            return $constants;
        }
        /**
         * Provides the functionality to get the product name by using product id
         * @param type $args default arguments for the send email notification
         * @return string returns the product id
         */
        private function getProductList($args)
        {
            ob_start();
            ?>
            <style>
                .wdm-emial-tbl-body{
                    font-family: arial, sans-serif;
                    border-collapse: collapse;
                    width: 100%;
                }
                .wdm-emial-tbl-body thead{
                    background: #1f397d;
                    color: white;
                }
                .wdm-emial-tbl-body th,
                .wdm-emial-tbl-body td{
                    border: 1px solid #000000;
                    text-align: left;
                    padding: 8px;
                }
                .wdm-emial-tbl-body tbody{
                    background: white;
                }
            </style>
            <table border="0" cellspacing="0" class="wdm-emial-tbl-body" style="font-family: arial, sans-serif;border-collapse: collapse;width: 100%;border: 1px solid gray;">
                <thead style="background: #1f397d;color: white;">
                    <tr>
                        <th style="text-align: left;padding: 8px;"><?php _e('Product Name', 'woocommerce-integration'); ?></th>
                        <th style="text-align: left;padding: 8px;"><?php _e('Associated Courses', 'woocommerce-integration'); ?></th>
                    </tr>
                </thead>
                <tbody style="background: white;">
                    <?php
                    if (isset($args['order_id']) && $args["order_id"] != '12235') {
                        $order = new \WC_Order($args['order_id']);
                        $items = $order->get_items();
                        
                        foreach ($items as $itemId => $prop) {

                                $prodId = $prop->get_product_id();
                                $_product = wc_get_product($prodId);
                                
                                if ($_product && $_product->is_type('variable') && isset($prop['variation_id'])) {
                                    $prodId = $prop['variation_id'];
                                }
                                $courses = get_post_meta($prodId, "product_options", true);
                                ?>
                                <tr>
                                    <td style="text-align: left;padding: 8px;vertical-align: top;"><?php echo get_the_title($prodId); ?></td>
                                    <td style="text-align: left;padding: 8px;">
                                        <ul type="disc">
                                        <?php
                                        if (isset($courses['moodle_post_course_id'])) {

                                            foreach ($courses['moodle_post_course_id'] as $courseId) {
                                                ?> <li><?php echo get_the_title($courseId); ?></li>
                                                <?php
                                            }
                                        }
                                        ?>
                                        </ul>
                                    </td>
                                </tr>
                                <?php
                        }
                        ?>
                        <?php
                    } else {
                        ?>
                        <tr>
                            <td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php _e("Test Product", 'woocommerce-integration'); ?></td>
                            <td style="border: 1px solid #000000;text-align: left;padding: 8px;"><?php _e("Test Course", 'woocommerce-integration'); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php
            return ob_get_clean();
        }
    }
}

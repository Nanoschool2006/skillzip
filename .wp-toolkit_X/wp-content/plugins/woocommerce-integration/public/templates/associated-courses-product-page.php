<?php

// include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . 'includes/class-bridge-woo-functions.php';


if (! empty($product_id)) {
    $product_options = get_post_meta($product_id, 'product_options', true);

    if (! empty($product_options)) {
        if (\NmBridgeWoocommerce\check_value_set($product_options, 'moodle_post_course_id')) {
        // if (isset($product_options['moodle_post_course_id']) && is_array($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
            ?>
            <div class="wi-asso-courses-wrapper">
                <h5><?php _e('Associated Courses', 'woocommerce-integration'); ?></h5>

                <?php \NmBridgeWoocommerce\wi_get_associated_courses($product_options['moodle_post_course_id']); ?>
                
            </div>
            <?php
        }
    }
}

<?php

namespace NmBridgeWoocommerce;

/**
 * File used to define the commonly used functions all over the woocommerce Integration plugin.
 */




/**
 * Functionality to check if the Woocommerce Membership plugin is activated.
 * @return
 */
function checkWoocommerceMembershipIsActive()
{
    $activatedPlugins = apply_filters('active_plugins', get_option('active_plugins', array()));

    if (in_array('woocommerce-memberships/woocommerce-memberships.php', $activatedPlugins)) {
        return true;
    }
    return false;
}


/**
 * returns wordpress course ids which are associated to the product Id
 * @param  [type] $productId [description]
 * @return [type]            [description]
 */
function getWpCoursesFromProductId($productId)
{
    $productMeta = get_post_meta($productId, "product_options", 1);
    $associatedCourses = isset( $productMeta["moodle_post_course_id"] ) ? $productMeta["moodle_post_course_id"] : array();
    return $associatedCourses;
}


/**
 * returns wordpress course ids which are associated to the product Id
 * @param  [type] $productId [description]
 * @return [type]            [description]
 */
function getMdlCoursesFromProductId($productId)
{
    $productMeta = get_post_meta($productId, "product_options", 1);
    $associatedCourses = isset( $productMeta["moodle_course_id"] ) ? $productMeta["moodle_course_id"] : '';
    $associatedCourses = explode(",", $associatedCourses);
    return $associatedCourses;
}



/**
* The function will check if the array key exist or not and dose the arrya key associated a non empty value 
* @param array  associative array.
* @param string array key to get the value.
* @returns arrays value associated with the key if exist and not empty. Otherwise retrurns false.
*/
function check_value_set($dataarray, $key)
{
    $value = false;

    if (is_array($dataarray) && array_key_exists($key,$dataarray) && $dataarray[$key]) {

        $value = empty($dataarray[$key]) ? false : $dataarray[$key];
    }
    return $value;
}




function wi_get_associated_courses($course_ids)
{
    // get course titles and short by name
    $course_titles = array();
    foreach ($course_ids as $single_course_id) {
        $course_titles[$single_course_id] = get_the_title($single_course_id);
    }
    asort($course_titles);
    ?>
    <ul class="bridge-woo-available-courses">
        <?php
        foreach ($course_titles as $single_course_id => $single_course_title) {
            if ('publish' === get_post_status($single_course_id)) {
                ?>
                <li>
                    <a href="<?php echo esc_url(get_permalink($single_course_id)); ?>" target="_blank"><?php echo $single_course_title; ?></a>
                    <?php do_action( 'wi_after_associated_course', $single_course_id) ?>
                </li>
                <?php
            }
        }
        ?>
    </ul>
    <?php

}



/*
function get_course_with_link()
{

}*/



function eb_get_product_id_from_product($_product, $singleItem)
{

    if ($_product && $_product->is_type('variable') && isset($singleItem['variation_id'])) {
        //The line item is a variable product, so consider its variation.
        $product_id = $singleItem['variation_id'];
    } else {
        $product_id = $singleItem['product_id'];
    }

    return $product_id;

}




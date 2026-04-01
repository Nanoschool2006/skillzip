<?php
/**
 * The file that defines Product operation
 *
 * A class definition that includes meta fields and operation related to WooCommerce Products
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */
/**
 *
 * This is used to define Product operation
 *
 *
 * @since      1.0.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

namespace NmBridgeWoocommerce

{

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class ProductFilter
    {
        /**
         * The ID of this plugin.
         *
         * @since    1.0.0
         *
         * @var string The ID of this plugin.
         */
        private $plugin_name;

        /**
         * The version of this plugin.
         *
         * @since    1.0.0
         *
         * @var string The current version of this plugin.
         */
        private $version;
        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
        }
        public function addCustomColumn($columns)
        {
            $new_columns = array(
                'associated_courses' => 'Associated Courses'
            );
            return array_merge($columns, $new_columns);
        }
        public function addCustomColumnValue($column, $post_id)
        {
            switch ($column) {
                case 'associated_courses':
                    $courses = $this->getAssociatedCourseTitle($post_id);
                    $iterator = 0;
                    if (!empty($courses)) {
                        $courses_count = count($courses);
                        foreach ($courses as $value) {
                            if (++$iterator === $courses_count) {
                                echo "$value";
                            } else {
                                echo "$value, ";
                            }
                        }
                    } else {
                         echo "&ndash;";
                    }

                    break;
            }
        }
        public function getAssociatedCourseTitle($post_id)
        {
            $product_options = get_post_meta($post_id, 'product_options', true);

            $productObject=wc_get_product($post_id);

            if ($productObject->is_type('variable')) {
                $associatedCourses=$this->getVariationsAssociatedCourses($productObject);
                return $associatedCourses;
            } else {
                if (!empty($product_options['moodle_post_course_id'])) {
                            $coursePostIdS=$product_options['moodle_post_course_id'];
                    foreach ($coursePostIdS as $coursePostId) {
                        $associatedCourses[] = get_the_title($coursePostId);
                    }
                    return $associatedCourses;
                }
            }
            return false;
        }
        /*
        *This function returns associated courses
        *of the variable product.
        *       input: WC_Product object
        *       output:Array of Associated Course Title
        */
        public function getVariationsAssociatedCourses($productObject)
        {
            $variations = $productObject->get_available_variations();
            $associatedCourses=array();
            foreach ($variations as $var) {
                   $product_options=get_post_meta($var['variation_id'], 'product_options', true);
                if ($product_options != false && !empty($product_options['moodle_post_course_id'])) {
                            $coursePostIdS=$product_options['moodle_post_course_id'];
                    foreach ($coursePostIdS as $coursePostId) {
                        $associatedCourses[] = get_the_title($coursePostId);
                    }
                }
            }
            return $associatedCourses;
        }

        
        public function moodleCourseInDropdown($output)
        {
            $output = [];
                    global $wp_query;
            $terms   = get_terms('product_type');
            $output  = '<select name="product_type" id="dropdown_product_type">';
            $output .= '<option value="">' . __('Filter by product type', '') . '</option>';

            foreach ($terms as $term) {
                $output .= '<option value="' . sanitize_title($term->name) . '" ';

                if (isset($wp_query->query['product_type'])) {
                    $output .= selected($term->slug, $wp_query->query['product_type'], false);
                }

                $output .= '>';

                switch ($term->name) {
                    case 'grouped':
                        $output .= __('Grouped product', '');
                        break;
                    case 'external':
                        $output .= __('External/Affiliate product', '');
                        break;
                    case 'variable':
                        $output .= __('Variable product', '');
                        break;
                    case 'simple':
                        $output .= __('Simple product', 'woocommerce');
                        break;
                    default:
                        // Assuming that we have other types in future
                        $output .= ucfirst($term->name);
                        break;
                }

                $output .= '</option>';

                if ('simple' == $term->name) {
                    $output .= '<option value="downloadable" ';

                    if (isset($wp_query->query['product_type'])) {
                        $output .= selected('downloadable', $wp_query->query['product_type'], false);
                    }

                    $output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __('Downloadable', 'woocommerce') . '</option>';

                    $output .= '<option value="virtual" ';

                    if (isset($wp_query->query['product_type'])) {
                        $output .= selected('virtual', $wp_query->query['product_type'], false);
                    }

                    $output .= '> ' . ( is_rtl() ? '&larr;' : '&rarr;' ) . ' ' . __('Virtual', 'woocommerce') . '</option>';
                }
            }
               $output .= '<option value="moodle_course">Moodle Course</option>';
            $output .= '</select>';
            echo $output;
        }

        public function productFiltersQuery($query)
        {
            // echo "<pre>";
            // print_r($query);
            // echo "<pre>";
            // die();
            global $typenow;
            if ('product' == $typenow) {
                if (isset($query->query_vars['product_type'])) {
                    // Subtypes
                    if ('moodle_course' == $query->query_vars['product_type']) {
                        $query->query_vars['product_type']  = '';
                        $query->is_tax = false;
                        $query->query_vars['meta_value']    = 'yes';
                        $query->query_vars['meta_key']      = 'is_product_a_moodle_course';
                    }
                }
            }
        }
    }//class ends here
}

//namespace ends here

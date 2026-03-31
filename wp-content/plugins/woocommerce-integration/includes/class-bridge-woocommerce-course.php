<?php

/**
 * The file that defines Course WooCommerce Integration details
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */

/**
 *
 * This is used to define Classes & WooCommerce Product synchronization
 *
 *
 * @since      1.0.0
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWoocommerceCourse
    {

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
        private $edwiser_bridge;
        public function __construct($plugin_name, $version)
        {
            $this->plugin_name   = $plugin_name;
            $this->version       = $version;
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';
            $this->edwiser_bridge = new EdwiserBridge();
        }

        /**
     * This checks if Product for course on Moodle already created or not
     *
     * @param integer $course_id_on_moodle      This is Moodle course ID
     *
     * @return integer/boolean $product_id  returns product ID if found, otherwise false
     * @since   1.0.0
     * @access  public
     *
     */
        public function isProductPresynced($course_id_on_moodle)
        {
            global $wpdb;

            $this->edwiser_bridge->logger()->add('product', 'Checking if a product is presynced, Moodle ID of course: ' . $course_id_on_moodle); // add product log

            $query = 'SELECT `product_id` FROM ' . $wpdb->prefix . "woo_moodle_course WHERE `moodle_course_id` = '" . $course_id_on_moodle . "'";

            $product_id = $wpdb->get_var($query);

            $this->edwiser_bridge->logger()->add('product', 'Product Found? :' . (($product_id) ? 'Yes, the ID is: ' . $product_id : 'NO')); // add product log

            return $product_id ? $product_id : false;
        }

        /**
     * This function creates products for already synchronized courses.
     *
     *
     * @param array $sync_options synchronization option selected by User
     *
     * @return void
     * @since   1.0.2
     * @access  public
     * @return $response  return response message for create
     */

        public function bridgeWooSyncCreateProduct($sync_options)
        {
            global $wpdb;

            $query = 'SELECT `ID`, `post_title`, `post_content`
			FROM ' . $wpdb->prefix . "posts WHERE `post_type` = 'eb_course'
			AND `ID` NOT IN (
			
			    SELECT DISTINCT `moodle_post_id` FROM `" . $wpdb->prefix . "woo_moodle_course` 
			)
			AND `post_status` IN ('publish','draft')";

            $result = $wpdb->get_results($query);

            $response = array();

            if (! empty($result)) {
                $post_status = (isset($sync_options['bridge_woo_synchronize_product_publish']) && 1 == $sync_options['bridge_woo_synchronize_product_publish']) ? 'publish' : 'private'; // manage product post status

                foreach ($result as $single_result) {
                    $course_name     = $this->wdmIsset($single_result->post_title);
                    $course_content  = $this->wdmIsset($single_result->post_content);
                    $course_id   = $this->wdmIsset($single_result->ID);


                    if (!empty($course_id)) {
                        $this->edwiser_bridge->logger()->add('product', 'Creating Product for course id: ' . $course_id); // Add product created log

                        $course_args = array( 'post_title' => $course_name, 'post_content' => $course_content, 'post_status' => $post_status, 'post_type' => 'product' );

                        $wp_product_id = wp_insert_post($course_args); // create a Product on WooCommerce
                        // Add Product Meta

                        $moodle_course_id = get_post_meta($course_id, 'moodle_course_id', true);

                        $product_args = array( 'moodle_course_id' => $moodle_course_id, 'moodle_post_course_id' => array( $course_id ) );


                        update_post_meta($wp_product_id, 'product_options', $product_args);

                        //Make Product Virtual & Downloadable

                        add_post_meta($wp_product_id, '_downloadable', 'yes');
                        add_post_meta($wp_product_id, '_virtual', 'yes');

                        update_post_meta($wp_product_id, 'is_product_a_moodle_course', 'yes');

                        //Update Course Meta

                        /*
                         * Change course status & Course closed url
                         */

                        $eb_course_options = get_post_meta($course_id, 'eb_course_options', true);

                        $course_status_option = array( 'course_price_type' => 'closed', 'course_closed_url' => get_permalink($wp_product_id) );

                        if (! empty($eb_course_options)) {
                            $eb_course_options = array_merge($eb_course_options, $course_status_option);
                        } else {
                            $eb_course_options = $course_status_option;
                        }

                        update_post_meta($course_id, 'eb_course_options', $eb_course_options);

                        //Make Entry in Product Course Log

                        $woo_moo_course_tbl = $wpdb->prefix . 'woo_moodle_course';

                        $data = array( 'product_id' => $wp_product_id, 'moodle_post_id' => $course_id, 'moodle_course_id' => $moodle_course_id );

                        $wpdb->insert($woo_moo_course_tbl, $data);

                        //Add Product Log
                        $this->edwiser_bridge->logger()->add('product', 'Product created, ID is: ' . $wp_product_id); // Add product created log

                        //Assign Product categories to Products

                        $category_args = array( 'orderby' => 'name', 'order' => 'ASC', 'fields' => 'slugs' );
                        $course_categories = wp_get_object_terms($course_id, 'eb_course_cat', $category_args);


                        $this->setObjTerms($course_categories, $wp_product_id);
                        do_action('bridge_woo_course_product_created', $wp_product_id, $course_id, $sync_options);
                    }
                }

                $response['respone_message'] = '<div class="alert alert-success">' . __('Product(s) created.', 'woocommerce-integration') . '</div>';
            } else {
                $response['respone_message'] = '<div class="alert alert-warning"><span class="dashicons dashicons-warning" style="padding: 2px 6px 2px 0px;font-size: 22px;margin-left: -2px;"></span>' . __('Courses for synchronization not found. All products may be already created.', 'woocommerce-integration') . '</div>';
            }

            return $response;
        }

        /**
     * This function used to check isset(), to reduce NPath complexity.
     *
     *
     * @param var $var
     * @return boolean true/false
     * @since   1.0.2
     */
        public function wdmIsset($var)
        {
            if (isset($var)) {
                return $var;
            } else {
                return '';
            }
        }

        /**
     * This function sets object terms for product.
     *
     *
     * @param array $course_categories   Course categories
     * @param int $wp_product_id         Product ID
     *
     * @return void
     * @since   1.0.2
     */
        public function setObjTerms($course_categories, $wp_product_id)
        {
            if (! empty($course_categories) && ! is_wp_error($course_categories)) {
                $product_term_id = array();

                foreach ($course_categories as $single_course_cat) {
                    //Find corresponding Product category ID

                    $product_details = get_term_by('slug', $single_course_cat, 'product_cat', ARRAY_A);

                    if (isset($product_details['term_id'])) {
                        $product_term_id[] = $product_details['term_id'];
                    }
                }

                wp_set_object_terms($wp_product_id, $product_term_id, 'product_cat', false);
                $this->edwiser_bridge->logger()->add('product', 'Categories assigned ' . implode(',', $product_term_id)); // Add product created log
            }
        }

        /**
     * This function updates all existing WooCommerce product associated with Moodle Course.
     *
     *
     * @param array $sync_options       synchronization option selected by User
     *
     * @return void
     * @since   1.0.2
     * @access  public
     * @return $response  return response message for update
     */
        public function bridgeWooSyncUpdateProduct($sync_options)
        {
            global $wpdb;

            $query = 'SELECT  `product_id` ,  `moodle_post_id` , `post_title` ,  `post_content` 
			 FROM  `' . $wpdb->prefix . 'woo_moodle_course` moodle_course,  `' . $wpdb->prefix . 'posts` posts
			 WHERE moodle_course.`moodle_post_id` = posts.`ID`';

            $result = $wpdb->get_results($query);
            $response = array();

            $_POST['eb_product_sync'] = 1; 

            if (! empty($result)) {
                $this->edwiser_bridge->logger()->add('product', 'Update all associated Products ...'); // Add product updated log
                $covered = array();
                foreach ($result as $single_result) {
                    if (in_array(intval($single_result->product_id), $covered)) {
                        continue;
                    } else {
                        $covered[] = intval($single_result->product_id);
                    }

                    $course_content  = isset($single_result->post_content) ? $single_result->post_content : '';
                    $course_id       = isset($single_result->moodle_post_id) ? $single_result->moodle_post_id : '';
                    $title           = isset($single_result->post_title) ? $single_result->post_title : '';
                    $product_options = get_post_meta($single_result->product_id, 'product_options', true);
                    $moodle_courses  = isset($product_options['moodle_post_course_id']) ? $product_options['moodle_post_course_id'] : array();

                    $product_args = array(
                        'ID' => $single_result->product_id,
                        'post_content' => $course_content
                    );
                    if ( count($moodle_courses) == 1 ) {
                        $product_args['post_title'] = $single_result->post_title;
                    }
                    wp_update_post($product_args);

                    //Add categories to the previously created products

                    $category_args = array( 'orderby' => 'name', 'order' => 'ASC', 'fields' => 'slugs' );

                    $course_categories = wp_get_object_terms($course_id, 'eb_course_cat', $category_args);
                    
                    $this->setObjTerms($course_categories, $single_result->product_id);

                    do_action('bridge_woo_course_product_updated', $single_result->product_id, $course_id, $sync_options);
                }

                $response['respone_message'] = '<div class="alert alert-success">' . __('Product(s) updated.', 'woocommerce-integration') . '</div>';
            } else {
                $response['respone_message'] = '<div class="alert alert-error">' . __('Product for update not found.', 'woocommerce-integration') . '</div>';
            }

            return $response;
        }

        /*
         * This function updates existing Course categories with WooCommerce categories
         *
         *
         * @param array $sync_options       synchronization option selected by User
         *
         * @return void
         * @since   1.0.3
         * @access  public
         * @return $response  return response message for update
         *
         */
        public function bridgeWooSyncProductCategories()
        {

            //global $wpdb;
            $term_args = array(
                      'hide_empty' => false,
                      'orderby' => 'id',
                      'order' => 'ASC',
                      'fields' => 'id=>parent',
            );
            $terms_relation_list = get_terms('eb_course_cat', $term_args);
            if (! empty($terms_relation_list)) {
                asort($terms_relation_list);
                $term_args = array( 'hide_empty' => false );

                $terms = get_terms('eb_course_cat', $term_args);

                $terms = $this->object2array($terms);

                $term_id_list = function_exists('array_column')? array_column($terms, 'term_id') : $this->wdmArrayColumn($terms, 'term_id');

                $product_cat_relation = array();

                foreach ($terms_relation_list as $key => $value) {
                    /*
                     * Check term exists - IF yes update it, otherwise create new term
                     *
                     */

                    $search_key = array_search($key, $term_id_list);

                    $single_term = $terms[ $search_key ];

                    if ($value > 0) {
                        //Term Has parent , check term exist with respect to this

                        if (! isset($product_cat_relation[ $value ])) {
                            continue;
                        }

                        $term_exist_result = term_exists($single_term['name'], 'product_cat', $product_cat_relation[ $value ]);

                        if (0 == $term_exist_result || null == $term_exist_result) {
                            $term_created = wp_insert_term($single_term['name'], 'product_cat', array( 'parent' => $product_cat_relation[ $value ], 'description' => $single_term['description'], 'slug' => $single_term['slug'] ));

                            if (! is_wp_error($term_created)) {
                                $product_cat_relation[ $single_term['term_id'] ] = $term_created['term_id'];
                            }
                        } else {
                            //Update Term

                            $term_updated = wp_update_term($term_exist_result['term_id'], 'product_cat', array( 'description' => $single_term['description'], 'slug' => $single_term['slug'] ));

                            if (! is_wp_error($term_updated)) {
                                $product_cat_relation[ $single_term['term_id'] ] = $term_updated['term_id'];
                            }
                        }
                    } else {
                        //check if term exist

                        $term_exist_result = term_exists($single_term['name'], 'product_cat');

                        if (0 == $term_exist_result || null == $term_exist_result) {
                            //Insert Term

                            $term_created = wp_insert_term($single_term['name'], 'product_cat', array( 'description' => $single_term['description'], 'slug' => $single_term['slug'] ));

                            if (! is_wp_error($term_created)) {
                                $product_cat_relation[ $single_term['term_id'] ] = $term_created['term_id'];
                            }
                        } else {
                            //Update Term

                            $term_updated = wp_update_term($term_exist_result['term_id'], 'product_cat', array( 'description' => $single_term['description'], 'slug' => $single_term['slug'] ));

                            if (! is_wp_error($term_updated)) {
                                $product_cat_relation[ $single_term['term_id'] ] = $term_updated['term_id'];
                            }
                        }
                    }
                }//foreach ends

                $this->edwiser_bridge->logger()->add('product', 'Categories synchronized'); // Add product created log

                $response['respone_message'] = '<div class="alert alert-success">' . __('Categories synchronized.', 'woocommerce-integration') . '</div>';
            } else {
                $response['respone_message'] = '<div class="alert alert-error">' . __('Categories not found, Please synchronize course categories before this.', 'woocommerce-integration') . '</div>';
            }
            return $response;
        }//function ends - bridge_woo_sync_product_categories

        /*
         * This function converts object to array
         *
         * @param $obj
         * @since 1.0.3
         * @return $array Array - converted object
         */
        private function object2array($obj)
        {
            if (is_object($obj) || is_array($obj)) {

                $obj = is_object($obj) ? get_object_vars($obj) : $obj;
                $obj = array_map(array( $this, 'object2array' ), $obj);
            }
            return $obj;
        }

        /**
     * This is AJAX Handler for Product synchronization
     *
     *
     * @param array $sync_options       synchronization option selected by User
     *
     * @return void
     * @since   1.0.2
     * @access  public
     * @return $response    returns array of Response Message
     */

        public function bridgeWooProductSyncHandler($sync_options)
        {
            $response = array();
            $category_respone = array();
            if (isset($sync_options['bridge_woo_synchronize_product_categories']) && 1 == $sync_options['bridge_woo_synchronize_product_categories']) {
                $category_respone = $this->bridgeWooSyncProductCategories();
            }

            //Update Products

            if (isset($sync_options['bridge_woo_synchronize_product_update']) && 1 == $sync_options['bridge_woo_synchronize_product_update']) {
                $response = $this->bridgeWooSyncUpdateProduct($sync_options);
            }

            //Create Products

            if (isset($sync_options['bridge_woo_synchronize_product_create']) && 1 == $sync_options['bridge_woo_synchronize_product_create']) {
                if (empty($response)) {
                    $response = $this->bridgeWooSyncCreateProduct($sync_options);
                } else {
                    $response = array_merge_recursive($response, $this->bridgeWooSyncCreateProduct($sync_options));
                }
            }
            if (! empty($category_respone)) {
                $response = array_merge_recursive($response, $category_respone);
            }
            return $response;
        }

        /*
         * Provides support for PHP - array_column() function
         * @since 1.0.1
         * @param $input  Input array
         * @param $columnKey column key which should be extracted
         */
        public function wdmArrayColumn($input = null, $columnKey = null, $indexKey = null)
        {
            if (empty($input)) {
                $input = null;
            }
            if (empty($columnKey)) {
                $columnKey = null;
            }
            if (empty($indexKey)) {
                $indexKey = null;
            }

            // Using func_get_args() in order to check for proper number of
            // parameters and trigger errors exactly as the built-in array_column()
            // does in PHP 5.5.
            $argc = func_num_args();
            $params = func_get_args();
            $paramsInput = $params[0];
            $paramsColumnKey = (null !== $params[1]) ? (string) $params[1] : null;
            $paramsIndexKey = null;
            if (isset($params[2])) {
                if (is_float($params[2]) || is_int($params[2])) {
                    $paramsIndexKey = (int) $params[2];
                } else {
                    $paramsIndexKey = (string) $params[2];
                }
            }
            $resultArray = array();

            $resultArray = $this->getResultArr($paramsInput, $paramsIndexKey, $paramsColumnKey);
            
            return $resultArray;
        }

        public function getResultArr($paramsInput, $paramsIndexKey, $paramsColumnKey)
        {
            $resultArray = array();
            
            foreach ($paramsInput as $row) {
                $key = $value = null;
                $keySet = $valueSet = false;
                if (null !== $paramsIndexKey && array_key_exists($paramsIndexKey, $row)) {
                    $keySet = true;
                    $key = (string) $row[ $paramsIndexKey ];
                }
                if (null === $paramsColumnKey) {
                    $valueSet = true;
                    $value = $row;
                } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                    $valueSet = true;
                    $value = $row[ $paramsColumnKey ];
                }
                if ($valueSet) {
                    if ($keySet) {
                        $resultArray[ $key ] = $value;
                    } else {
                        $resultArray[] = $value;
                    }
                }
            }
            return $resultArray;
        }
    }
}

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
namespace NmBridgeWoocommerce {

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWooProductManager
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
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            // require_once EB_PLUGIN_DIR . 'includes/class-eb.php';
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';
            
            $this->edwiser_bridge = new EdwiserBridge();
        }

        /**
         * This function adds new tab for woocommerce Product
         * @since 1.0.4
         * @access public
         * @var      array    $product_data_tabs    The current Product tabs settings
         */
        public function bridgeWooAddTab($product_data_tabs)
        {

            $bridge_woo_tab = array(
                'bridge_woo_simple' => array(
                    'label' => __('WooCommerce Integration', 'bridge_woocommerce'),
                    'target' => 'bridge_woo_product_data',
                    'class' => array('show_if_simple', 'hide_if_grouped'),
                ),
            );
            $product_data_tabs = array_merge($product_data_tabs, $bridge_woo_tab);

            return $product_data_tabs;
        }

        public function bridgeWooAddDataPanel()
        {

            global $post;

            //Enqueue script
            wp_enqueue_script('admin_product_js');
            ?>
            <div id="bridge_woo_product_data" class="panel woocommerce_options_panel">
                <div class="options_bridge_woo pricing show_if_simple show_if_external">
                    <?php self::bridgeWooShowMeta(0, $post->ID, 'product');
                    ?>
                </div>
            </div>
            <?php
        }

        /**
         * This function adds new tab for woocommerce Product
         * @since 1.0.4
         * @access public
         * @var      int    $loop    The current variation Index
         * @var      int    $variation_data    variation details
         * @var      int    $variation    variation post details
         */
        public function bridgeWooAddProductMetaVariation($loop, $variation_data, $variation)
        {

            if (empty($variation_data)) {
                $variation_data = '';
            }
            wp_enqueue_script('admin_product_js');

            if (isset($variation->ID) && !empty($variation->ID)) {
                ?>
                <div class="bridge_woo_variation_wrapper">

                    <?php self::bridgeWooShowMeta($loop, $variation->ID, $variation->post_type);
                    ?>

                </div>
                <?php
            }
        }

        private function bridgeWooShowMeta($index, $product_id, $post_type = '')
        {

            //Check for existing Product option
            //$existing_product_option = get_post_meta($product_id, 'product_options', true);
            $product_option = get_post_meta($product_id, 'product_options', true);

            //$moodle_post_course_id = array();
            $moo_post_course_id = array();

            // if (!empty($product_option) && isset($product_option['moodle_post_course_id']) && is_array($product_option['moodle_post_course_id'])) {
            if (check_value_set($product_option, 'moodle_post_course_id') && is_array($product_option['moodle_post_course_id'])) {
                $moo_post_course_id = $product_option['moodle_post_course_id'];
            }

            //Get existing available course options

            $fields = $this->populateMetaboxFields('product');

            if (!empty($fields) && isset($fields['moodle_post_course_id']['options']) && is_array($fields['moodle_post_course_id']['options'])) {
                if ('product_variation' == $post_type) {
                    $name = 'bridge_woo_variation_option[' . $index . '][]';
                } else {
                    $name = 'product_options[moodle_post_course_id][]';
                }
                ?>

                <p class="form-field">
                    <label for="courses_ids">
                        <?php _e('Courses', 'woocommerce-integration'); ?>
                    </label>
                    <select name="<?php echo esc_html($name); ?>" class="moodle_post_course_id" multiple="multiple" class="woo-moodle-post-course-id">
                        <?php foreach ($fields['moodle_post_course_id']['options'] as $key => $value) {
                            ?>
                            <option value="<?php echo esc_html($key); ?>" <?php echo esc_html(in_array($key, $moo_post_course_id) ? 'selected=selected' : ''); ?> >
                                <?php echo esc_html($value); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <img class="help_tip" data-tip='<?php _e('Associate product with courses.', 'woocommerce-integration'); ?>' src="<?php echo esc_url(WC()->plugin_url());?>/assets/images/help.png" height="16" width="16" />
                </p>
                <?php
                do_action('wdm_display_fields', $product_id);
            }
        }

        public function bridgeWooArrayEscAttr(&$item1, $key)
        {
            unset($key);
            $item1 = esc_attr($item1);
        }

        public function bridgeWooSavevariationMeta($variation_id, $key)
        {
            //global $wpdb;
            //$course_id_list = array();
            $moodle_post_ids = array();
            if (isset($_POST['bridge_woo_variation_option'][$key])) {
                if (is_array($_POST['bridge_woo_variation_option'][$key])) {
                    array_walk($_POST['bridge_woo_variation_option'][$key], array($this, 'bridgeWooArrayEscAttr'));
                    $moodle_post_ids['moodle_post_course_id'] = $_POST['bridge_woo_variation_option'][$key];
                } else {
                    $moodle_post_ids['moodle_post_course_id'] = esc_attr($_POST['bridge_woo_variation_option'][$key]);
                }
            } else {
                // No data to save
                $moodle_post_ids['moodle_post_course_id'] = "-1";
            }
            BridgeWooProductManager::bridgeWooSaveMeta($moodle_post_ids, $variation_id, 'product_variation');
        }

        /**
         * Register meta boxes for Product
         *
         * @return void
         * @since         1.0.0
         */
        public function registerMetaBoxes()
        {

            //Register metabox for Product post type

            add_meta_box(
                'bridge_woo_product_options',
                __('Product Options', 'woocommerce-integration'),
                array($this, 'post_options_callback'),
                'product',
                'advanced',
                'default',
                array('post_type' => 'product')
            );

            //Enqueue script
            wp_enqueue_script('admin_product_js');
        }

        /**
         * callback for metabox fields
         *
         * @since         1.0.0
         * @param object  $post current $post object
         * @param array   $args arguments supplied to the callback function
         *
         * @return string renders and returns renedered html output
         */
        public function postOptionsCallback($post, $args)
        {

            if (empty($post)) {
                $post = '';
            }
            // get fields for a specific post type
            $fields = $this->populateMetaboxFields($args['args']['post_type']);

            $plugin_post_types = new EB_Post_Types($this->plugin_name, $this->version);

            echo esc_html("<div id='{$args['args']['post_type']}_options' class='post-options'>");

            // render fields using our render_metabox_fields() function
            foreach ($fields as $key => $values) {
                $field_args = array(
                    'field_id' => $key,
                    'field' => $values,
                    'post_type' => $args['args']['post_type'],
                );
                $plugin_post_types->render_metabox_fields($field_args);
            }
            echo esc_html('</div>');
        }

        /**
         * Method to populate metabox fields for Product post types
         *
         * @since     1.0.0
         * @param string  $post_type returns array of fields for specific post type
         * @return array  $args_array returns complete fields array.
         */
        private function populateMetaboxFields($post_type)
        {

            global $wpdb;

            //if (! $course_list = wp_cache_get($post_id, 'bridge_woo_courses')) {
            //if (! $course_list = wp_cache_get($post_type, 'bridge_woo_courses')) {
            //$course_list = array( '-1' => __('Select any course', 'bridge-woocommerce') );

            $course_list = array();

            $query = "SELECT `ID`,`post_title`, `post_status`
                  FROM  `" . $wpdb->prefix . "posts`
                  WHERE  `post_type` LIKE  'eb_course' AND (`post_status` LIKE 'publish' OR `post_status` LIKE 'draft')";

            $result = $wpdb->get_results($query, OBJECT_K);

            if (!empty($result)) {
                foreach ($result as $post_id => $single_result) {
                    $draft = "";
                    if ($single_result->post_status == "draft") {
                        $draft = "( draft )";
                    }
                    $course_list[$post_id] = $single_result->post_title . $draft;
                }
            }

            //wp_cache_add($post_id, $course_list, 'bridge_woo_courses');
            //wp_cache_add($post_type, $course_list, 'bridge_woo_courses');
            //}

            $args_array = array(
                'product' => array(
                    'moodle_post_course_id' => array(
                        'label' => __('Courses', 'woocommerce-integration'),
                        'description' => __('Associate product with courses', 'woocommerce-integration'),
                        'type' => 'select_multi',
                        'options' => $course_list,
                    //'default'     => array( '-1' ),
                    ),
                ),
            );

            $args_array = apply_filters('ed_woo_post_options', $args_array);

            if (!empty($post_type)) {
                if (isset($args_array[$post_type])) {
                    return $args_array[$post_type];
                } else {
                    return $args_array;
                }
            }
        }
        /*
         * This function handle meta save when Product is saved/updated.
         * This adds Product, Course log record in table and also update corresponding courses closed url
         *
         * @param integer $post_id
         * @access public
         * @since 1.0.0
         */

        public function handlePostOptionsSave($post_id)
        {
            global $pagenow;

            /*if (!self::isValidToSaveMeta()) {
                return false;
            }*/
            
            $post_type = get_post_type($post_id);

            if (self::isValidToSaveMeta() && in_array($post_type, array('product')) && $pagenow !='admin-ajax.php') {

                // if ($pagenow ==='admin-ajax.php') {
                //     return;
                // }

                //Options to update will be stored here
                $update_post_options = array();
                //get current post type

                /*if (!in_array($post_type, array('product')) || $pagenow ==='admin-ajax.php') {
                    return false;
                }*/

                $fields = $this->populateMetaboxFields($post_type);

                //$post_options = isset($_POST[ $post_type.'_options' ]) ? esc_html($_POST[ $post_type.'_options' ]) : array();
                //$post_options = $this->wdmIsset($_POST[ $post_type.'_options' ]);

                // $post_options = $this->wdmIssetKey($_POST, $post_type . '_options');

                //Using other function eo check the isset.
                $post_options = check_value_set($_POST, $post_type . '_options');



                if (!empty($post_options)) {
                    foreach ($fields as $key => $values) {
                        $option_name = $key;
                        //$option_value = isset($post_options[ $key ]) ? wp_unslash($post_options[ $key ]) : null;
                        $option_value = $this->wdmIssetNull($post_options[$key]);

                        //format the values
                        switch (sanitize_title($values['type'])) {
                            case 'checkbox':
                                $option_value = is_null($option_value) ? 'no' : 'yes';
                                break;
                            case 'textarea':
                                $option_value = wp_kses_post(trim($option_value));
                                break;
                            case 'text':
                            case 'text_secret':
                            case 'number':
                            case 'select':
                            case 'password':
                            case 'radio':
                                $option_value = wpClean($option_value);
                                break;
                            case 'select_multi':
                            case 'checkbox_multi':
                                $option_value = array_filter(array_map('wpClean', (array) $option_value));
                                break;
                            default:
                                //$option_value = isset( $post_options[ $key ] ) ? wp_unslash( $post_options[ $key ] ) : null;
                                break;
                        }

                        if (!is_null($option_value)) {
                            $update_post_options[$option_name] = $option_value;
                        }
                    }

                    //apply_filters( 'eb_save_extra_fields',$update_post_options , $_POST );
                    //BridgeWooProductManager::bridgeWooSaveMeta($update_post_options, $post_id, 'product');
                }//if ends - $_POST not empty
                BridgeWooProductManager::bridgeWooSaveMeta($update_post_options, $post_id, 'product');

                return true;
            }
        }
//function ends - handle_post_options_save

        public static function isValidToSaveMeta()
        {
            if (empty($_POST)) {
                return false;
            }

            if (isset($_POST['action']) && $_POST['action'] === 'handle_product_synchronization') {
                // The request is coming from product synchronization functionality
                return false;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return false;
            }

            return true;
        }

        /**
         * This function used to check isset(), to reduce NPath complexity.
         * 
         *
         * @param var $var
         * @return array
         * @since   1.0.2
         */
        /*public function wdmIssetKey($arr, $key, $default_val = '')
        {

            if (isset($arr[$key])) {
                return $arr[$key];
            } else {
                return $default_val;
            }
        }*/

        /**
         * This function used to check isset(), to reduce NPath complexity.
         *
         *
         * @param var $var
         * @return boolean true/false
         * @since   1.0.2
         */
        public function wdmIssetNull($var)
        {

            if (isset($var)) {
                return $var;
            } else {
                return null;
            }
        }

        public function bridgeWooSaveMeta($update_post_options, $product_id, $post_type)
        {
            global $wpdb;

            $course_id_list = array();

            //Retrieve selected Moodle post course details -- check course ID on Moodle & update

            if (is_array($update_post_options)) {
                if (isset($update_post_options['moodle_post_course_id']) && is_array($update_post_options['moodle_post_course_id'])) {
                    foreach ($update_post_options['moodle_post_course_id'] as $key => $moo_post_course_id) {
                        if (intval($moo_post_course_id) == -1) {
                            array_splice($update_post_options['moodle_post_course_id'], $key, 1);
                            continue;
                        }

                        $moodle_course_id = get_post_meta($moo_post_course_id, 'moodle_course_id', true);

                        array_push($course_id_list, $moodle_course_id);

                        //Add Course link entry in table

                        $insert_query = 'INSERT INTO `' . $wpdb->prefix . "woo_moodle_course`(`product_id`, `moodle_post_id`, `moodle_course_id`) SELECT * FROM (SELECT '{$product_id}' as `product_id`, '{$moo_post_course_id}' as `moodle_post_id`, '{$moodle_course_id}' as `moodle_course_id`) AS tmp
WHERE NOT EXISTS ( SELECT `product_id`,`moodle_post_id` FROM `" . $wpdb->prefix . "woo_moodle_course` WHERE `product_id` = '{$product_id}' AND `moodle_post_id` = '{$moo_post_course_id}'
) LIMIT 1;";

                        $wpdb->get_results($insert_query);
                    }//foreach ends - loop through selected courses

                    /*
                     * Get Previous Course list
                     * Find difference between current & Previous selection
                     * If current post permalink is set for any previously selected course,
                     * it needs to be reset
                     */

                    //$previous_moodle_post_course_id_list = get_post_meta($product_id, 'product_options', true);
                    $moo_course_id_list = get_post_meta($product_id, 'product_options', true);

                    // if (isset($moo_course_id_list['moodle_post_course_id']) && is_array($moo_course_id_list['moodle_post_course_id'])) {
                    if (check_value_set($moo_course_id_list, 'moodle_post_course_id') && is_array($moo_course_id_list['moodle_post_course_id'])) {
                        $course_list_diff = array_diff($moo_course_id_list['moodle_post_course_id'], $update_post_options['moodle_post_course_id']);
                        if (is_array($course_list_diff)) {
                            if ('product_variation' === $post_type) {
                                //Delete course link entry from Table

                                $course_tbl_name = $wpdb->prefix . 'woo_moodle_course';
                                $where = array('product_id' => $product_id, 'moodle_post_id' => implode(',', $course_list_diff));

                                $wpdb->delete($course_tbl_name, $where);
                            } else {
                                $product_permalink = get_permalink($product_id);

                                foreach ($course_list_diff as $single_course_diff) {
                                    $course_options = get_post_meta($single_course_diff, 'eb_course_options', true);

                                    if (!empty($course_options) && isset($course_options['course_closed_url'])) {
                                        if (0 === strcmp($course_options['course_closed_url'], $product_permalink)) {
                                            /* Course contain, current Product link
                                             * It needs to be reset
                                             */

                                            $course_options['course_closed_url'] = '';

                                            update_post_meta($single_course_diff, 'eb_course_options', $course_options);
                                        }
                                    }

                                    //Delete course link entry from Table

                                    $course_tbl_name = $wpdb->prefix . 'woo_moodle_course';
                                    $where = array('product_id' => $product_id, 'moodle_post_id' => $single_course_diff);

                                    $wpdb->delete($course_tbl_name, $where);
                                }
                            }
                        }//course_list_diff is array
                    }

                    //Update Post meta details
                    if ($update_post_options['moodle_post_course_id'] != "-1") {
                        $update_post_options['moodle_course_id'] = implode(',', $course_id_list);
                    } else {
                        $update_post_options = '';
                    }

                    update_post_meta($product_id, 'product_options', $update_post_options);
                    update_post_meta($product_id, 'is_product_a_moodle_course', 'yes');
                } else {
                    // if ends - Moodle post courses are selected
                    $product_options = array(
                        'moodle_post_course_id' => array(),
                        'moodle_course_id' => ''
                    );

                    update_post_meta($product_id, 'product_options', $product_options);
                    update_post_meta($product_id, 'is_product_a_moodle_course', 'no');
                }
            } else {
                //if ends - Update post options is array
                //$product_options = get_post_meta($product_id, 'product_options', true);
                $product_options = array(
                    'moodle_post_course_id' => array(),
                    'moodle_course_id' => ''
                );

                update_post_meta($product_id, 'product_options', $product_options);
                update_post_meta($product_id, 'is_product_a_moodle_course', 'no');
            }
        }

        /*
         * This function performs operation, if any Product or Course is deleted
         * for removing linking between course & Product
         * rather than leaving it hanging.
         *
         * @param integer $post_id
         * @access public
         * @since 1.0.0
         */
        public function handlePostOptionsDelete($post_id)
        {
            global $wpdb;

            $post_type = get_post_type($post_id);

            $post_permalink = get_permalink($post_id);

            if ('product' === $post_type) {
                $query = "SELECT * FROM `{$wpdb->prefix}woo_moodle_course` WHERE `product_id` = " . $post_id;
                $linked_course_result = $wpdb->get_results($query);

                if (!empty($linked_course_result)) {
                    $this->edwiser_bridge->logger()->add('product', 'Perform operation on Product delete  ' . $post_id);  // add Product log

                    foreach ($linked_course_result as $single_course_result) {
                        $moodle_post_id = $single_course_result->moodle_post_id;

                        $course_options = get_post_meta($moodle_post_id, 'eb_course_options', true);

                        if (!empty($course_options)) {
                            if (0 === strcmp(rtrim($course_options['course_closed_url'], '/'), rtrim($post_permalink, '/'))) {
                                //Update Course closed url to empty

                                $course_options['course_closed_url'] = '';
                                update_post_meta($moodle_post_id, 'eb_course_options', $course_options);

                                $this->edwiser_bridge->logger()->add('product', 'Course ID ' . $moodle_post_id . ' closed url is reset.');  // add Product log
                            }//if ends - Product url matches
                        }
                    }//foreach ends -- loop through associated courses
                    //Delete associated Products entry

                    $course_tbl_name = $wpdb->prefix . 'woo_moodle_course';
                    $where = array('product_id' => $post_id);
                    $wpdb->delete($course_tbl_name, $where);
                }//if ends - post type is Product
            } elseif ('eb_course' === $post_type) {
                $query = "SELECT * FROM `{$wpdb->prefix}woo_moodle_course` WHERE `moodle_post_id` = " . $post_id;
                $product_result = $wpdb->get_results($query);

                $cur_moodle_course_id = get_post_meta($post_id, 'moodle_course_id', true);

                if (!empty($product_result)) {
                    $this->edwiser_bridge->logger()->add('course', 'Perform operation on Course delete  ' . $post_id);  // add Course log

                    foreach ($product_result as $single_product) {
                        $find_key = false;
                        //$product_id = isset($single_product->product_id)? $single_product->product_id : '';
                        $product_id = $this->wdmIssetNull($single_product->product_id);

                        if (!empty($product_id)) {
                            //Get Product meta
                            $product_options = get_post_meta($product_id, 'product_options', true);

                            if (!empty($product_options) && is_array($product_options)) {
                                //$moodle_post_id_list = isset($product_options['moodle_post_course_id'])? $product_options['moodle_post_course_id'] : '';
                                $moodle_post_id_list = $this->wdmIssetNull($product_options['moodle_post_course_id']);

                                if (!empty($moodle_post_id_list) && is_array($moodle_post_id_list)) {
                                    $find_key = array_search($post_id, $moodle_post_id_list);

                                    if (is_numeric($find_key)) {
                                        unset($moodle_post_id_list[$find_key]);
                                    }

                                    $product_options['moodle_post_course_id'] = $moodle_post_id_list;
                                }

                                $course_id_list = isset($product_options['moodle_course_id']) ? $product_options['moodle_course_id'] : '';
                                $find_key = false;

                                if (!empty($course_id_list)) {
                                    $course_id_list = explode(',', $course_id_list);

                                    $find_key = array_search($cur_moodle_course_id, $course_id_list);

                                    if (is_numeric($find_key)) {
                                        unset($course_id_list[$find_key]);
                                    }

                                    $product_options['moodle_course_id'] = implode(',', $course_id_list);
                                }
                            }

                            update_post_meta($product_id, 'product_options', $product_options);

                            $this->edwiser_bridge->logger()->add('course', 'Product association is removed for Product ID ' . $product_id);  // add Course log

                            if (empty($product_options['moodle_post_course_id'])) {
                                wp_delete_post($product_id);
                                $this->edwiser_bridge->logger()->add('course', 'Product deleted, Product ID ' . $product_id);  // add Course log
                            }
                        }
                    }//foreach ends -- loop through associated products
                    //Delete associated Courses entry

                    $course_tbl_name = $wpdb->prefix . 'woo_moodle_course';
                    $where = array('moodle_post_id' => $post_id);
                    $wpdb->delete($course_tbl_name, $where);
                }
            }//if ends - post type is eb_course
        }
//function ends - handle_post_options_delete
    }
}

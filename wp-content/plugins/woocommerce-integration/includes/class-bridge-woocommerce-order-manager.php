<?php

/**
 * The file that defines woocommerce Order management.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 */

/**
 * This is used to define Order processing & Moodle Course Enrollment.
 *
 *
 * @since      1.0.0
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class BridgeWoocommerceOrderManager
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
        private $edwiser_bridge;
        public function __construct($plugin_name, $version)
        {

            $this->plugin_name = $plugin_name;
            $this->version = $version;
            // require_once EB_PLUGIN_DIR.'includes/class-eb.php';
            require_once WP_PLUGIN_DIR . '/edwiser-bridge/includes/class-eb.php';
            
            $this->edwiser_bridge = new EdwiserBridge();
        }

        /*
         * This function checks, if order contains products associated with courses
         * Enroll customer in corresponding course
         *
         * @param integer $order_id     The order ID
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function handleOrderComplete($order_id)
        {

            if (! empty($order_id)) {
                $is_processed = get_post_meta($order_id, '_is_processed', true);

                if (! empty($is_processed)) {
                    $this->edwiser_bridge->logger()->add('user', 'Order id '.$order_id.' is already processed');
                    return 0;
                }

                $order     = wc_get_order($order_id); //Get Order details
                $user      = $order->get_user();
                $emailArgs = array(
                    "user_email" => $user->user_email,
                    "order_id"   => $order_id,
                    "username"   => $user->user_login,
                    "first_name" => $user->first_name,
                    "last_name"  => $user->last_name
                );
                // WCS is active.
                $subscription =0;
                if (defined('WOOINT_WCS_VER')) {
                    if (version_compare(WOOINT_WCS_VER, '2.0', '>=') && \wcs_order_contains_subscription($order)) {
                        $subscription = 1;
                    } elseif (version_compare(WOOINT_WCS_VER, '2.0', '<') && \WC_Subscriptions_Order::order_contains_subscription($order)) {
                        $subscription = 1;
                    }
                }


                $user_id = get_post_meta($order_id, '_customer_user', true);

                $is_for_someone_else = get_post_meta($order_id, '_order_for_someone_else', true);
                if ( ! empty($is_for_someone_else) && 'yes' === $is_for_someone_else ) {
                    $user_email = get_post_meta($order_id, '_recipient_email', true);
                    $user = get_user_by('email', $user_email);
                    $user_id = $user->ID;
                    $emailArgs['user_email'] = $user_email;
                    $emailArgs['username'] = $user->user_login;
                    $emailArgs['first_name'] = $user->first_name;
                    $emailArgs['last_name'] = $user->last_name;
                }

                // check if this order have purchase for someones else.
                $list_of_course_ids = self::_getMoodleCourseIdsForOrder($order, 1);

                if (! empty($list_of_course_ids)) {
                    $course_enrolled = self::_enrollUserInCourses($user_id, $list_of_course_ids);
                    if (1 === $course_enrolled) {
                        update_post_meta($order_id, '_is_processed', true);
                        //handling membership orders.
                        $membershipHandler = new WooIntMembershipHandler($this->plugin_name, $this->version);
                        $membershipHandler->handleMembsershipOrder($order, $user_id);
                    }

                    //Added email send functionality here because it was send even on bulk purchase orders.
                    include_once('emails/class-eb-woo-int-emailer.php');
                    $pluginEmailer =  new EbWooIntSendEmailer();
                    $pluginEmailer->sendCourseEnrollmentEmail($emailArgs);
                } elseif ($subscription ===1) {
                    update_post_meta($order_id, '_is_processed', true);
                }
            }

        }

        /*
         * This function checks, if order is already processed,
         * It finds associated product courses and
         * suspend customer enrollment in corresponding course
         *
         * @param integer $order_id     The order ID
         * @access public
         * @return void
         * @since 1.0.0
         */

        public function handleOrderCancel($order_id)
        {
            if (! empty($order_id)) {
                $order = wc_get_order($order_id); //Get Order details

                // WCS is active.
                $subscription =0;
                /*if (defined('WOOINT_WCS_VER')) {
                    if (version_compare(WOOINT_WCS_VER, '2.0', '>=') && \wcs_order_contains_subscription($order)) {
                        $subscription =1;
                    } elseif (version_compare(WOOINT_WCS_VER, '2.0', '<') && \WC_Subscriptions_Order::order_contains_subscription($order)) {
                        $subscription =1;
                    }
                }*/

                if (defined('WOOINT_WCS_VER') && ( (version_compare(WOOINT_WCS_VER, '2.0', '>=') && \wcs_order_contains_subscription($order)) || (version_compare(WOOINT_WCS_VER, '2.0', '<') && \WC_Subscriptions_Order::order_contains_subscription($order))) ) {
                    $subscription = 1;
                }

                $is_processed = get_post_meta($order_id, '_is_processed', true);

                $this->edwiser_bridge->logger()->add('user', 'Check if User enrolled for Order ID - '.$order_id);

                if (empty($is_processed)) {
                    $this->edwiser_bridge->logger()->add('user', 'No User enrollment for Order ID - '.$order_id);
                    return 0;
                }

                $user_id = get_post_meta($order_id, '_customer_user', true);
                
                $is_for_some_one_else = get_post_meta($order_id, '_order_for_someone_else', true);
                if ( 'yes' === $is_for_some_one_else ) {
                    $user_email = get_post_meta($order_id, '_recipient_email', true);
                    $user = get_user_by('email', $user_email);
                    $user_id = $user->ID;
                }

                $list_of_course_ids = self::_getMoodleCourseIdsForOrder($order, 0);

                if (! empty($list_of_course_ids)) {
                    $course_enrolled = self::_enrollUserInCourses($user_id, $list_of_course_ids, 0, 1);

                    if (1 === $course_enrolled) {
                        update_post_meta($order_id, '_is_processed', '');
                    }
                } elseif ($subscription ===1) {
                    update_post_meta($order_id, '_is_processed', '');
                }
            }
        }

        /*
         * This function is used to create Moodle user if, new Customer is created on wordpress
         * This event is executed when new Order is created,
         *
         * @param interger $order_id
         * @param array $posted_data
         * @access public
         * @return void
         * @since 1.0.0
         */
        public function createMoodleUserForCreatedCustomer($order_id, $posted_data)
        {
            if (empty($posted_data)) {
                $posted_data = '';
            }
            //global $wpdb;
            $product_exist = false;

            if (! empty($order_id)) {
                $user_id = get_post_meta($order_id, '_customer_user', true);

                $eb_general = get_option('eb_woo_int_settings');
                $purchase_for_someone_else_enabled = isset($eb_general['wi_enable_purchase_for_someone_else']) && $eb_general['wi_enable_purchase_for_someone_else'] === 'yes' ? true : false;
                $is_for_someone_else = isset($_POST['purchase_for_someone_else']) && $_POST['purchase_for_someone_else'] === '1' ? true : false;
                if ( $purchase_for_someone_else_enabled && $is_for_someone_else ) {
                    $order = wc_get_order( $order_id );
                    
                    $first_name = isset( $_POST['recipient_first_name'] ) ? sanitize_text_field( $_POST['recipient_first_name'] ) : '';
                    $last_name = isset( $_POST['recipient_last_name'] ) ? sanitize_text_field( $_POST['recipient_last_name'] ) : '';
                    $email = isset( $_POST['recipient_email'] ) ? sanitize_email( $_POST['recipient_email'] ) : '';

                    // update order meta
                    update_post_meta( $order_id, '_order_for_someone_else', 'yes' );
                    update_post_meta( $order_id, '_recipient_first_name', $first_name );
                    update_post_meta( $order_id, '_recipient_last_name', $last_name );
                    update_post_meta( $order_id, '_recipient_email', $email );

                    // create user
                    $user = get_user_by( 'email', $email );
                    if ( ! $user ) {
                        $user_id = \app\wisdmlabs\edwiserBridge\edwiser_bridge_instance()->user_manager()->create_wordpress_user( $email, $first_name, $last_name );
                    } else {
                        $user_id = $user->ID;
                    }
                }

                $membershipHandler = new WooIntMembershipHandler($this->plugin_name, $this->version);
                $order = wc_get_order($order_id); //Get Order details
                $items = $order->get_items(); //Get Item details
                foreach ($items as $single_item) {
                    $product_id = isset($single_item['product_id']) ? $single_item['product_id'] : '';
                    if (! empty($product_id)) {
                        $product_options = get_post_meta($product_id, 'product_options', true);
                        $product = wc_get_product($product_id);

                        if (! empty($product_options['moodle_course_id'])) {
                            $product_exist = true;
                            break;
                        } elseif ($product->is_type('variable') && isset($single_item['variation_id'])) {
                            $product_options = get_post_meta($single_item['variation_id'], 'product_options', true);
                            if (! empty($product_options['moodle_course_id'])) {
                                $product_exist = true;
                                break;
                            }
                        }

                        /**---------------------------------------------
                         * check if the membership is enabled
                         * then check if the membership is linked to the current product.
                         * then check if the products associated to the membership have courses assciated.
                         *----------------------------------------------*/

                        if (checkWoocommerceMembershipIsActive()) {
                            $associatedMemberships = $membershipHandler->getProductsAssociatedWithMembership($single_item);

                            //check if the product has any membership associated.
                            if (!empty($associatedMemberships)) {
                                // it can happen that the product have more than one membership if so then get all products of all the memberships and then get courses to add in listOfCourseIds
                                foreach ($associatedMemberships as $membership) {
                                    $membershipProducts = $membershipHandler->getProductsFromMembershipId($membership);
                                    // $totalCourses = array();
                                    foreach ($membershipProducts as $productId) {
                                        $newCourses = getWpCoursesFromProductId($productId);

                                        if (!empty($newCourses)) {
                                            $product_exist = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (true === $product_exist) {
                    $this->edwiser_bridge->logger()->add('user', 'Link Moodle User for User ID  '.$user_id);  // add User log

                    $user = get_userdata(intval($user_id));

                    $user->user_login = strtolower($user->user_login);

                    $this->edwiser_bridge->logger()->add('user', 'Log from WooIntegration');

                    $this->edwiser_bridge->logger()->add('user', 'User Object JSON Encoded : '.json_encode($user));

                    $this->edwiser_bridge->userManager()->linkMoodleUser($user);
                }//if ends - Need to process for Moodle User creation

                //if new custom field plugin is not installed then keep the old code
                //migration code
                $custom_field_plugin_path = 'edwiser-custom-fields/edwiser-custom-fields.php';
                if( ! is_plugin_active( $custom_field_plugin_path ) ){
                // Update fields On Moodle for user
                    $custom_field_handler = new Bridge_Wi_Cf_Mdl_Sync_Handler();
                    $custom_field_handler->wi_cf_checkout_update( $user_id );
                }

                //for updating the user profile fields
                do_action('wi_woo_checkout_customer_user_created', $user_id);

            }//if ends - Order id present

        }//function ends - create_moodle_user_for_created_customer

        /*
         * This function used to change generated password with User entered password during checkout
         *
         * @param string $password      This contains wordpress generated password
         * @return string $password
         * @access public
         * @since 1.0.0
         */
        public function addUserSubmittedPassword($password)
        {

            if (isset($_POST['account_password'])) {
                $password = $_POST['account_password'];
            }

            return $password;
        }

        /*
         * This function is used to enroll user into courses, if subscription is activated.
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param string $subscription_key  The key representing the given subscription
         * @access public
         * @return void
         */

        public function handleActivatedSubscription($user_id, $subscription_key)
        {
            self::_changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, 0);
        }

        /*
        * This function is used to suspend enrollment of user for courses, if subscription is cancelled/expired/put on hold.
        *
        * @param integer $user_id     The id of the user whose subscription is to be activated.
        * @param string $subscription_key  The key representing the given subscription
        * @access public
        * @return void
        */

        public function handleCancelledSubscription($user_id, $subscription_key)
        {

            self::_changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, 1);
        }

        /*
         * This function is called internally to enroll user into set of courses.
         * This calls, 'update_user_course_enrollment()' for User enrollment
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param array $course_id_list     List of Moodle post course ids
         * @param integer $suspend      The suspend status for courses
         * @param integer $unenroll  The unenroll status for courses
         *
         * @return integer $course_enrolled    return status of course enrollment 1 - successfull 0 - problem in enrollment status change
         * @access private
         */
        public function _enrollUserInCourses($user_id, $course_id_list, $suspend = 0, $unenroll = 0, $start_date = '0000-00-00 00:00:00', $end_date = '0000-00-00 00:00:00' )
        {
            $args = array(
                'user_id' => $user_id,
                'courses' => $course_id_list,
                'unenroll' => $unenroll,
                'suspend' => $suspend,
                'start_date' => $start_date,
                'end_date' => $end_date,
            );

            $course_enrolled = $this->edwiser_bridge->enrollmentManager()->updateUserCourseEnrollment($args); // enroll user to course

            if (1 === $course_enrolled) {
                if (1 === $suspend) {
                    $this->edwiser_bridge->logger()->add('user', 'User enrollment suspended for courses - '.serialize($course_id_list));
                } else {
                    $this->edwiser_bridge->logger()->add('user', 'User enrolled for courses - '.serialize($course_id_list));
                }
            } else {
                $this->edwiser_bridge->logger()->add('user', 'Enrollment response '.$course_enrolled);
            }

            return $course_enrolled;
        }

        /*
         * This function is used to change enrollment status as per subscription status
         * It internally calls, self::_enroll_user_in_courses() to change enrollment status of course
         *
         * @param integer $user_id     The id of the user whose subscription is to be activated.
         * @param string $subscription_key  The key representing the given subscription
         * @param integer $suspend_status  The status for enrollment
         *
         * @access private
         * @return void
         */
        private function _changeEnrollmentPerSubscriptionStatus($user_id, $subscription_key, $suspend_status)
        {
            $item = \WC_Subscriptions_Order::get_item_by_subscription_key($subscription_key);
            if (! empty($item)) {
                //$order_id = isset($item['order_id'])? $item['order_id'] : '';
                //$product_id = isset($item['product_id']) ? $item['product_id'] : '';
                $product_id = '';
                // if (isset($item['variation_id']) && is_numeric($item['variation_id']) && $item['variation_id'] > 0) {
                if (check_value_set($item, 'variation_id') && is_numeric($item['variation_id']) && $item['variation_id'] > 0) {
                    $product_id = $item['variation_id'];
                // } elseif (isset($item['product_id']) && is_numeric($item['product_id'])) {
                } elseif (check_value_set($item, 'product_id') && is_numeric($item['product_id'])) {
                    $product_id = $item['product_id'];
                }
                if (! empty($product_id)) {
                    $product_options = get_post_meta($product_id, 'product_options', true);
                    // if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                    if (check_value_set($product_options, 'moodle_post_course_id')) {
                        self::_enrollUserInCourses($user_id, $product_options['moodle_post_course_id'], $suspend_status);

                        if (1 === $suspend_status) {
                            $this->edwiser_bridge->logger()->add('user', 'Subscription suspended for User '.$user_id);
                        } else {
                            $this->edwiser_bridge->logger()->add('user', 'Subscription activated for User '.$user_id);
                        }
                    }
                }
            }
        }

        /*
         * This function is used to fetch list of Moodle courses associated with product items of specified order
         *
         * @param object $order     This is $order object
         *
         * @return array $listOfCourseIds    This returns array of Moodle course post ids
         * @access private
         */

        public function _getMoodleCourseIdsForOrder($order, $skipSubscription = 0)
        {
            $orderId = $order->get_id();
            $listOfCourseIds = array();
            $totalAssociatedMemberships = array();

            // $order_id = trim(str_replace('#', '', $order->get_order_number()));
            $order_id = $order->get_id();
            $this->edwiser_bridge->logger()->add('user', 'Check Line Items for Order ID - '.$order_id);

            //create Membership object
            $membershipHandler = new WooIntMembershipHandler($this->plugin_name, $this->version);
            // $membershipHandler->handleMembsershipOrder($order, $user_id);

            $items = $order->get_items(); //Get Item details
            foreach ($items as $singleItem) {
                //$product_id = isset($single_item['product_id']) ? $single_item['product_id'] : '';
                $product_id = '';

                if (isset($singleItem['product_id'])) {
                    $_product = wc_get_product($singleItem['product_id']);

                    if ($skipSubscription === 1 && defined('WOOINT_WCS_VER') && \WC_Subscriptions_Product::is_subscription($_product)) {
                            //if a subscription do not fetch course_ids
                            continue;
                    }

                    $product_id = eb_get_product_id_from_product($_product, $singleItem);

                }

                if (is_numeric($product_id)) {
                    $product_options = get_post_meta($product_id, 'product_options', true);
                    $group_purchase = 'no';
                    // Removing the condition since the mail was not getting sent by on bulk purchase enabled product single qty purchase
                    if ( 'no' === apply_filters( 'is_order_group_purchase',$group_purchase, $singleItem->get_id() ) ) {
                        if (check_value_set($product_options, 'moodle_post_course_id')) {
                            $line_item_course_ids = $product_options['moodle_post_course_id'];

                            if (! empty($listOfCourseIds)) {
                                $listOfCourseIds = array_unique(array_merge($listOfCourseIds, $line_item_course_ids), SORT_REGULAR);
                            } else {
                                $listOfCourseIds = $line_item_course_ids;
                            }
                        }
                    }
                }

                //check if the woocoommerce membership plugin is active.
                $membershipProcessedData = $this->mergeMembershipCourses($membershipHandler, $listOfCourseIds, $singleItem, $totalAssociatedMemberships);
                $listOfCourseIds = $membershipProcessedData["course_list"];
                $totalAssociatedMemberships = $membershipProcessedData["total_memberships"];
            }//foreach ends


            //update order meta for memberships if has any memberships this is used while updating the membership-id column of the moodle_enrollment table.
            if (!empty($totalAssociatedMemberships)) {
                update_post_meta($orderId, "eb_order_associated_memberships", maybe_serialize($totalAssociatedMemberships));
            }

            $this->edwiser_bridge->logger()->add('user', 'Courses IDs from Line Items  '.serialize($listOfCourseIds));  // add User log

            return $listOfCourseIds;
        }


        /**
         * created this new function because of the Cyclomatic Complexity this will merge courses associated to membership with the existing list of coureses
         * @param  [type] $associatedMemberships [description]
         * @param  [type] $membershipHandler     [description]
         * @param  [type] $listOfCourseIds       [description]
         * @return [type]                        [description]
         */
        public function mergeMembershipCourses($membershipHandler, $listOfCourseIds, $singleProductItem, $totalAssociatedMemberships)
        {
            //check if the woocoommerce membership plugin is active.
            if (checkWoocommerceMembershipIsActive()) {
                $associatedMemberships = $membershipHandler->getProductsAssociatedWithMembership($singleProductItem);
                //check if the product has any membership associated.
                if (!empty($associatedMemberships)) {
                    // it can happen that the product have more than one membership if so then get all products of all the memberships and then get courses to add in listOfCourseIds
                    foreach ($associatedMemberships as $membership) {
                        $membershipProducts = $membershipHandler->getProductsFromMembershipId($membership);
                        // $totalCourses = array();
                        foreach ($membershipProducts as $productId) {                            
                            $listOfCourseIds = is_array( $listOfCourseIds ) ? $listOfCourseIds : array(); // Validate listOfCourseIds array
                            $newCourses      = getWpCoursesFromProductId($productId);
                            $newCourses      = is_array( $newCourses ) ? $newCourses : array(); // Validate newcourses array
                            $listOfCourseIds = array_unique(array_merge($listOfCourseIds, $newCourses));
                        }
                    }

                    //update the totalAssociatedMemberships
                    $totalAssociatedMemberships = array_unique(array_merge($totalAssociatedMemberships, $associatedMemberships));
                }
                //update order meta for memberships if has any memberships this is used while updating the membership-id column of the moodle_enrollment table.
            }
            return array("course_list" => $listOfCourseIds, "total_memberships" => $totalAssociatedMemberships);
        }



        /**
         * Function to update course access if subscription status updates.
         * Handles enrollment or unenrollment only for subscription orders.
         * @since 1.1.3
         */
        public function wcsStatusUpdated($subscription, $new_status, $old_status)
        {
            if (get_class($subscription) !== 'WC_Subscription') {
                return;
            }
            //do not unenroll for pending cancel
            if ($new_status == 'pending-cancel') {
                return;
            }

            if ( 'active' === $new_status ) {
                update_post_meta( $subscription->get_id(), '_eb_subscription_processed', 1 );
            }

            $is_processed = get_post_meta( $subscription->get_id(), '_eb_subscription_processed', true );
            if ( ( 'on-hold' == $new_status || 'cancelled' == $new_status ) && ! $is_processed ) {
                return;
            }

            //Suspend or not w.r.t. subscription status.
            $statuses = array(
                // 'pending'        => true,
                // 'pending-cancel' => false, //do not suspend if subscription is pending-cancel.
                'completed'      => true,
                'active'         => false, //do not suspend if subscription is active.
                'failed'         => true,
                'on-hold'        => true, //do not suspend if subscription is on-hold.
                'cancelled'      => true,
                'switched'       => true,
                'expired'        => true,
            );

            //add filter to change the suspend status.
            $statuses = apply_filters('eb_subscription_suspend_status', $statuses);


            $do_not_proceed_arr = array(
                'pending-cancel', //do not suspend if subscription is pending-cancel.
                // 'on-hold', //do not suspend if subscription is on-hold.
            );

            $process_rquest_array = array();

            // Process request if status is presemnt in any of the array_keys in the above array.
            if (array_key_exists($new_status, $statuses) ) {
                $suspend = isset($statuses[$new_status]) && !$statuses[$new_status] ? 0 : 1;
                $user = get_user_by('id', $subscription->get_user_id());

                // Commneted because direct access is not allowed from woocommerce 3.0.0 version.
                // $order_id = $subscription->order->id;


                $order_id = $subscription->get_parent_id();


                // if (!is_a($subscription->order, 'WC_Order')) {
                //     return;
                // }
                //Check admin saved setting on subscription expiration
                $unenroll = 0;
                $subExpireSetting = $this->checkSubscriptionExpirationSettings($new_status);
                //if do-nothing setting is saved
                if ($subExpireSetting === -1) {
                    return;
                }
                extract($subExpireSetting, EXTR_OVERWRITE);

                $items = $subscription->get_items();
                if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
                    foreach ($items as $item) {
                        $product_id=$item->get_product_id();//new
                        $product = $item->get_product($product_id);


                        if ($product->is_type('subscription_variation')) {
                            $product_id=$item->get_variation_id();//new
                        } else {
                            $product_id=$item->get_product_id();//new
                        }

                        $product_options = get_post_meta($product_id, 'product_options', true);
                        if (isset($product_options['moodle_post_course_id'])) {
                            $course_enrolled = self::_enrollUserInCourses(
                                $subscription->get_user_id(), //new
                                $product_options['moodle_post_course_id'],
                                $suspend,
                                $unenroll,
                                $subscription->get_date('start'), // start date
                                $subscription->get_date('end') // end date
                            );

                            $emailArgs = array(
                                "user_email" => $user->user_email,
                                "order_id"   => $order_id,
                                "username"   => $user->user_login,
                                "first_name" => $user->first_name,
                                "last_name"  => $user->last_name
                            );




                            if (1 === $course_enrolled && !$suspend) {
                               //Added email send functionality here because it was send even on bulk purchase orders.
                                include_once('emails/class-eb-woo-int-emailer.php');
                                $pluginEmailer=  new EbWooIntSendEmailer();
                                $pluginEmailer->sendCourseEnrollmentEmail($emailArgs); 
                            }

                        }
                    }
                } else {
                    //loop for older versions
                    foreach ($items as $item) {
                            $product_id = $item['product_id'];
                            $product_variation_id = $item['variation_id'];
                            $product = \wc_get_product($product_id);

                        if ($product->is_type('variable')|| $product->is_type('subscription_variation')) {
                            $product_options = get_post_meta($product_variation_id, 'product_options', true);
                        } else {
                            $product_options = get_post_meta($product_id, 'product_options', true);
                        }

                        if (isset($product_options['moodle_post_course_id'])) {
                            $course_enrolled = self::_enrollUserInCourses(
                                $subscription->order->user_id,
                                $product_options['moodle_post_course_id'],
                                $suspend,
                                $unenroll,
                                $subscription->get_date('start'), // start date
                                $subscription->get_date('end') // end date
                            );

                            $emailArgs = array(
                                "user_email" => $user->user_email,
                                "order_id"   => $order_id,
                                "username"   => $user->user_login,
                                "first_name" => $user->first_name,
                                "last_name"  => $user->last_name
                            );


                            if (1 === $course_enrolled) {
                               //Added email send functionality here because it was send even on bulk purchase orders.
                                include_once('emails/class-eb-woo-int-emailer.php');
                                $pluginEmailer=  new EbWooIntSendEmailer();
                                $pluginEmailer->sendCourseEnrollmentEmail($emailArgs);
                            }
                        }
                    }
                }
            }
        }




        private function checkSubscriptionExpirationSettings($new_status)
        {
            $subExpireSetting= array();
            if ($new_status == 'expired' || $new_status == 'cancelled') {
                $wooIntSettings=get_option('eb_woo_int_settings', array());
                $onSubscriptionExpiration=$wooIntSettings['wi_on_subscription_expiration'];
                if ($onSubscriptionExpiration === 'do-nothing') {
                    return -1;
                } elseif ($onSubscriptionExpiration === 'suspend') {
                    $subExpireSetting['suspend']=1;
                } elseif ($onSubscriptionExpiration === 'unenroll') {
                    $subExpireSetting['unenroll']=1;
                }
            }
            return $subExpireSetting;
        }

        /**
         * Function to update enrollment/unenrollment when order status changes.
         * This function does not handle subsciption orders.
         * @since 1.1.3
         */
        public function wcOrderStatusChanged($order_id, $old_status, $new_status)
        {
            //enrol w.r.t. order status?
            $statuses = array(
                'completed'  => true,
                'processing' => false,
                'on-hold'    => false,
                'cancelled'  => false,
                'failed'     => false,
                'refunded'   => false,
            );

            if (isset($statuses[$new_status]) && $statuses[$new_status] === true) {
                // Enrol.
                $this->handleOrderComplete($order_id);
            } elseif ($new_status == 'cancelled' && $statuses[$new_status] === false) {
                // Unenrol.
                $this->handleOrderCancel($order_id);
            }

            do_action('wooint_after_order_status_changed', $order_id, $old_status, $new_status);
        }

        /**
        * This function will disable guest checkout option if cart contains course associated products
        * @param $value
        * @return $value (yes to enable guest checkout , no to disable guest checkout)
        */
        public function disableGuestCheckout($value)
        {
            if (is_admin()) {
                return $value;
            }
            //$value = "yes";
            if (WC()->cart) {
                $cart = WC()->cart->get_cart();
                foreach ($cart as $item) {
                    $_product = $item['data'];
                    $_product_id = $_product->get_id();

                    $product_options = get_post_meta($_product_id, 'product_options', true);
                    // if (! empty($product_options) && isset($product_options['moodle_post_course_id']) && ! empty($product_options['moodle_post_course_id'])) {
                    if (check_value_set($product_options, 'moodle_post_course_id')) {
                        $value = "no";
                        break;
                    }
                }
            }
            return $value;
        }
    }
}

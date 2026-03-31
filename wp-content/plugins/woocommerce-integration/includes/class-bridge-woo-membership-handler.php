<?php

namespace NmBridgeWoocommerce;

/**
* Functionality to handle all the membership related tasks.
*
* Below is the list of functionalities added
*
* ----------- Functionality 1 ----------
* If any of the product is associated with membership then add all the courses of all products associated to that membership.
* called on the order completion hook, Defination is in function _getMoodleCourseIdsForOrder
*
*------------- Functionality 2 ----------
*Update membership-id column in moodle_enrollment table for all courses which associated to the memberships i.e all courses associated to all products in membership.
*called on the order completion hook, Defination is in this file.
*
*
*
*
*
* @since 2.0.0
*/
class WooIntMembershipHandler
{

    private $version;
    private $pluginName;
    public function __construct($pluginName, $version)
    {

        $this->pluginName = $pluginName;
        $this->version = $version;
    }

    /**
     * get associated membership ids to the course.
     * @param  course id
     * @param  user id
     * @return [type] [description]
     */
    public function getMembershipId($courseId, $userId)
    {
        global $wpdb;
        $tblName = $wpdb->prefix."moodle_enrollment";
        $oldMemberships = $wpdb->get_var($wpdb->prepare("SELECT membership_id FROM $tblName WHERE course_id = %d AND user_id = %d", array($courseId, $userId)));
        $oldMemberships = maybe_unserialize($oldMemberships);
        return $oldMemberships;
    }


    /**
     * Functionality to get products associated to the membership
     * @param membershipId
     * @return array of the associated products
     */
    public function getProductsFromMembershipId($membershipId)
    {
        $associatedProducts = get_post_meta($membershipId, "_product_ids", 1);
        return $associatedProducts;
    }


    /**
     * Functionality to get courses from the membership id
     * @param   $membershipId
     * @return $totalCourses all associated courses to the membership
     */
    public function getCoursesFromMembershipId($membershipId)
    {
        $productsList = $this->getProductsFromMembershipId($membershipId);

        $totalCourses = array();
        foreach ($productsList as $productId) {
            $newCourses = getWpCoursesFromProductId($productId);
            if ( is_array( $newCourses ) ) { // Added this check
                $totalCourses = array_unique(array_merge($totalCourses, $newCourses));
            }
        }
        return $totalCourses;
    }


    /**
     * Functionality to alter table and store associated membership ids of the users.
     */
    public function addMembershipColumnInMoodleEnrollment()
    {
        global $wpdb;

        $usrEnrolTbl = $wpdb->prefix . 'moodle_enrollment';
        $colName = "membership_id";
        $colType = "varchar(200)";
        $query = "SHOW COLUMNS FROM `$usrEnrolTbl` LIKE '$colName';";
        $exists = $wpdb->query($query);

        //  Checks the column exist or not if not exist then add the column into the databse.
        if (!$exists) {
            $query = "ALTER TABLE `$usrEnrolTbl` ADD COLUMN (`$colName` $colType);";
            $wpdb->query($query);
        }
    }


    /**
     * This function handles the orders having products which are associated to the membership.
     * @param  $order  object of the woocommerce order
     * @param  $userId user id
     * @return [type]         [description]
     */
    public function handleMembsershipOrder($order, $userId)
    {

        $orderId = $order->get_id();

        //get post meta where all the memberships are stored.
        $orderMemberships = get_post_meta($orderId, "eb_order_associated_memberships", 1);

        $orderMemberships = maybe_unserialize($orderMemberships);

        if ($orderMemberships && !empty($orderMemberships)) {
            //foreach throgh each membership
            foreach ($orderMemberships as $membership) {
                $membershipProducts = $this->getProductsFromMembershipId($membership);
                //for each for each associated product
                foreach ($membershipProducts as $productId) {
                    $courses = getWpCoursesFromProductId($productId);
                    //for each throgh each course of the product.
                    foreach ($courses as $courseId) {
                        //Update membership ids on moodle enrollment table.
                        $this->updateMembershipIdOnMoodleEnrollmentTbl($courseId, $userId, $orderMemberships);
                    }
                }
            }
            $orderMemberships = delete_post_meta($orderId, "eb_order_associated_memberships");
        }
    }


    /**
     * check if the product is associated with membership or membership is asscoiated with the product return all memberships to which a product is associated if not associated then return blank array.
     * @return [type] [description]
     */
    public function getProductsAssociatedWithMembership($singleItem)
    {
        $totalProductMemberships  = array();
        $product                  = wc_get_product($singleItem['product_id']);
        $membershipPlans          = $this->getMembershipPlans();
        $variationMemberships     = array();
        $productId                = $singleItem['product_id'];

        if ($product && $product->is_type('variable') && isset($singleItem['variation_id'])) {
            //The line item is a variable product, so consider its variation.
            $variationId = $singleItem['variation_id'];

            //Get Memberships associated with the product.
            /*$parentProductId = $singleItem['product_id'];
            $parentProductIdMemberships = $this->returnMembershipsAssociatedWithProduct($parentProductId, $membershipPlans);*/

            //get memberships associated with the variation.
            $variationMemberships = $this->returnMembershipsAssociatedWithProduct($variationId, $membershipPlans);

            // merge both the memberships and create new array.
        }

        $totalProductMemberships = $this->returnMembershipsAssociatedWithProduct($productId, $membershipPlans);
        $totalProductMemberships = array_unique(array_merge($totalProductMemberships, $variationMemberships));

        return $totalProductMemberships;
    }





    /**
     * This function is responsible to return the associated memberships to the product.
     * @param  [type] $productId       [description]
     * @param  [type] $membershipPlans [description]
     * @return [type]                  [description]
     */
    public function returnMembershipsAssociatedWithProduct($productId, $membershipPlans)
    {
        $associatedMemberships = array();

        foreach ($membershipPlans as $membership) {
            if (in_array($productId, $membership["associated_products"]) && !in_array($membership["membership_id"], $associatedMemberships)) {
                array_push($associatedMemberships, $membership["membership_id"]);
            }
        }
        return $associatedMemberships;
    }


    /**
     * Update membership id in the moodle enrollment table.
     * @param  [type] $courseId       [description]
     * @param  [type] $userId         [description]
     * @param  [type] $newMemberships [description]
     * @return [type]                 [description]
     */
    public function updateMembershipIdOnMoodleEnrollmentTbl($courseId, $userId, $newMemberships)
    {
        global $wpdb;
        $tblName = $wpdb->prefix."moodle_enrollment";

        // get previous membership_id data.
        $oldMemberships = $this->getMembershipId($courseId, $userId);

        if (!empty($oldMemberships)) {
            //check if the Membership ID is already in the array.
            $newMemberships = array_unique(array_merge($oldMemberships, $newMemberships));
        }

        $newMemberships = maybe_serialize($newMemberships);

        //updating membsership id.
        $wpdb->query($wpdb->prepare("UPDATE $tblName SET membership_id = %s WHERE course_id = %d AND user_id = %d", array($newMemberships, $courseId, $userId)));
    }




    /**
     * Return all available membership plans.
     * @since 2.0.0
     */
    public function getMembershipPlans()
    {
        $membershipPlansArray = array();

        $args = array('posts_per_page' => -1);
        $args['post_type'] = 'wc_membership_plan';
        $membershipPlans = get_posts($args);

        if (!empty($membershipPlans)) {
            foreach ($membershipPlans as $membership) {
                $associatedProducts = $this->getProductsFromMembershipId($membership->ID);

                if (!empty($associatedProducts)) {
                    array_push(
                        $membershipPlansArray,
                        array(
                            "membership_id"       => $membership->ID,
                            "associated_products" => $associatedProducts
                        )
                    );
                }
            }
        }
        return $membershipPlansArray;
    }




    /**
     * handle membership status chanege.
     * @return [type] [description]
     */
    public function handleMembsershipStatusChange($userMembership, $oldStatus, $newStatus)
    {

        $userId = $userMembership->get_user_id();
        $membershipId = $userMembership->get_plan_id();
        $orderManager = new BridgeWoocommerceOrderManager($this->pluginName, $this->version);

        //Added because of the PSR2 issue.
        $oldStatus = $oldStatus;

        switch ($newStatus) {
            case 'active':
                //Enroll user to the course but what if the old status of the user is delayed, pending cancellation and cancelled at that time user will get enrolled again in the course so perform all these actions only if the old status of the user is paused and expired.

                // if ($oldStatus == "paused" || $oldStatus == "expired") {
                $totalCourses = $this->getCoursesFromMembershipId($membershipId);

                //process only if membership have any courses associated
                if (!empty($totalCourses)) {
                    $this->addEnrollmentEntryWithMembershipId($totalCourses, $userId, $membershipId);
                }
                // }

                break;

            case 'paused':
                //suspend user from all the courses and remove user enrollment from the wp courses.
                //get all products from membership

                $totalCourses = $this->getCoursesFromMembershipId($membershipId);
                //process only if membership have any courses associated
                if (!empty($totalCourses)) {
                    $orderManager->_enrollUserInCourses($userId, $totalCourses, 1);
                }

                break;

            case 'expired':
            case 'cancelled':
                //check if the count is more than 1 then just delete the memberships from the moodle enrollment table.
                //and if the count is 1 then delete whole role.
                $optionName = 'wi_on_membership_'.$newStatus;

                $totalCourses = $this->getCoursesFromMembershipId($membershipId);
                //process only if membership have any courses associated
                if (!empty($totalCourses)) {
                    $wooIntSettings = maybe_unserialize(get_option('eb_woo_int_settings', false));
                    // $status = maybe_unserialize(get_option("wi_on_membership_expiration"));
                    if (isset($wooIntSettings[$optionName]) && $wooIntSettings[$optionName] == 'suspend') {
                        $orderManager->_enrollUserInCourses($userId, $totalCourses, 1);
                    } elseif (isset($wooIntSettings[$optionName]) && $wooIntSettings[$optionName] == 'unenroll') {
                        $orderManager->_enrollUserInCourses($userId, $totalCourses, 0, 1);
                    }
                }

                break;


            /*case 'cancelled':

                break;*/

            default:
                # code...
                break;
        }



        /*$wp_user = get_userdata( $user_id );
        $roles   = $wp_user->roles;
        // Bail if the member doesn't currently have the Site Member role or is an active member
        if ( ! in_array( 'site_member', $roles ) || wc_memberships_is_user_active_member( $user_id, $user_membership->get_plan_id() ) ) {
            return;
        }
        $wp_user->remove_role( 'site_member' );
        $wp_user->add_role( 'customer' );*/
    }



    /**
     * add enrollment record here a new record can be added or the existing one can be modified.
     * @param  $totalCourses
     * @param  $userId
     * @param  $membershipId
     */
    public function addEnrollmentEntryWithMembershipId($totalCourses, $userId, $membershipId)
    {
        global $wpdb;
        $tblName = $wpdb->prefix."moodle_enrollment";

        foreach ($totalCourses as $courseId) {
            //get exitsing record if any.
            $oldMemberships = $wpdb->get_var($wpdb->prepare("SELECT membership_id, act_cnt FROM $tblName WHERE course_id = %d AND user_id = %d", array($courseId, $userId)));
            $oldMemberships = maybe_unserialize($oldMemberships);

            $orderManager = new BridgeWoocommerceOrderManager($this->pluginName, $this->version);
            if (empty($oldMemberships)) {
                //if no enrollment entry of the user for the same course then update all the things
                $orderManager->_enrollUserInCourses($userId, array($courseId));
            }
            $membsershipArray = array($membershipId);
            $this->updateMembershipIdOnMoodleEnrollmentTbl($courseId, $userId, $membsershipArray);
        }
    }
}

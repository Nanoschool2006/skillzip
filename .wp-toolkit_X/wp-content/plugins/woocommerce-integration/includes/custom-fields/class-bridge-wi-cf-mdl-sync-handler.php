<?php
/**
 * Setup plugin menus in WP admin.
 *
 * @link       https://edwiser.org
 * @since      2.1.4
 *
 * @package    Woocommerce Integration
 * @subpackage Woocommerce Integration/includes/custom-fields
 */

namespace NmBridgeWoocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 *  Class
 * Woocommerce my-accnt page update handler function is written in the existing function and which is wi_my_account_user_profile_update.
 */
class Bridge_Wi_Cf_Mdl_Sync_Handler {

	/**
	 * Handles functionality on Checkout Update.
	 */	
	public function wi_cf_checkout_update( $user_id ) {

		// Check if Moodle user id is present if present process field update.
		$moodle_user_id = get_user_meta( $user_id, 'moodle_user_id', true );
		if ( $moodle_user_id ) {
			$custom_fields = $this->wi_cf_create_data( $user_id );
			$request_data  = array(
	            "id"           => $moodle_user_id,
	            "customfields" => $custom_fields
	        );

	        $response = \app\wisdmlabs\edwiserBridge\edwiserBridgeInstance()->connectionHelper()->connectMoodleWithArgsHelper(
	            "core_user_update_users",
	            array(
	            	'users' => array( $request_data )
	            )
	        );
		}
	}

	/**
	 * Bridge User account update.
	 */
	public function wi_cf_bridge_user_accnt_update( $user_data, $update ) {
		if ( $update ) {
			$user_id = get_current_user_id();
			// This is to avoid processing this function on my-account page form submit.
			if ( ! isset($_POST['action']) || 'save_account_details' != $_POST['action'] ) {
				$user_data['customfields'] = $this->wi_cf_create_data( $user_id, 'edwiser-user-accnt' );
			}
		}

		return $user_data;
	}

	/**
	 * Creating array of the data which will be created.
	 */
	public function wi_cf_create_data( $user_id, $page = 'checkout') {
		$data_array = array();

		// foreach through custom fields and the check by name if the post data is set if set then add data to the array.
		$fields = get_option( 'eb_wi_custom_fields', array() );
		foreach ( $fields as $field_name => $field_details ) {
			// if ( isset( $_POST[$field_name] ) ) {
			if ( 'checkout' == $page || ( 'checkout' != $page && $field_details[$page] ) ) {

				$field_value = isset( $_POST[$field_name] ) ? sanitize_text_field( $_POST[$field_name] ) : '';

				if ( 'checkbox' == $field_details['type'] && ! empty( $field_value ) ) {
					$field_value = 1;
				}
	            
	            // update WP fields here. 
	            update_user_meta( $user_id, $field_name, $field_value );

				if ( ! isset( $field_details['sync-on-moodle'] ) || ! $field_details['sync-on-moodle'] ) {
					continue;
				}

				// if ( 'checkout' == $page || ( 'checkout' != $page && $field_details[$page] ) ) {
				array_push(
					$data_array,
					array(
                    	"type"  => $field_name,
                    	"value" => $field_value
                	)
                );
				// }
			}
		}

		return $data_array;
	}

}

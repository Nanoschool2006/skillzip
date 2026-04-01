<?php
/**
 * The which handles all the functionality related to the my-account page.
 *
 *
 * @link       http://wisdmlabs.com
 * @since      2.1.0
 *
 * @package    Bridge_Woocommerce
 * @subpackage Bridge_Woocommerce/includes
 */

namespace NmBridgeWoocommerce {

    use \app\wisdmlabs\edwiserBridge\EdwiserBridge;

    class Bridge_Woo_My_Account_Page_Handler
    {


    	/**
    	 * My account page user creation request handled here.
    	 * @param int $user_id User Id
    	 * @param int $new_customer_data Data of the new customer which we are registering
    	 * @param int $password_generated Password generated.
    	 */
    	public function my_account_page_user_creation($user_id, $new_customer_data, $password_generated)
    	{
    		$eb_woo_settings = get_option('eb_woo_int_settings', array());
        	if (check_value_set($eb_woo_settings, 'wi_enable_my_account_user_creation') && 'yes' == $eb_woo_settings['wi_enable_my_account_user_creation']) {
	            if (check_value_set($_POST, 'eb_first_name') && check_value_set($_POST, 'eb_last_name')) {
		            wp_update_user([
					    'ID'         => $user_id, // this is the ID of the user you want to update.
					    'first_name' => $_POST['eb_first_name'],
					    'last_name'  => $_POST['eb_last_name'],
					]);
	            }

	            $user = get_userdata($user_id);
	            $user_data = array(
	            	// 'id'        => $user_id, // moodle user id
	                'firstname' => isset( $_POST['eb_first_name'] ) ? $_POST['eb_first_name'] : $user->first_name, // added first name retrival from user object in case request is coming from checkout page
	                'lastname'  => isset( $_POST['eb_last_name'] ) ? $_POST['eb_last_name'] : $user->last_name, // added last name retrival from user object in case request is coming from checkout page
	                'password'  => $new_customer_data['user_pass'],
	                'username'  => $user->user_login,
	                'email'     => $user->user_email,
	                'auth'      => 'manual',
	            );


	            // require_once EB_PLUGIN_DIR.'includes/class-eb.php';
	            // $edwiser_bridge->userManager()->linkMoodleUser($user);
	            
	            // check if the email is already created or not if already created then just send the link email.
	            $user_linked = 0;
	            $edwiser_bridge = new EdwiserBridge();
	            if ($this->is_mdl_email_available($user->user_email)) {
	            	$user_linked = 1;
	            	$moodle_user = $edwiser_bridge->userManager()->createMoodleUser($user_data);
	            	if (isset($moodle_user['user_created']) && $moodle_user['user_created'] == 1 && is_object($moodle_user['user_data'])) {
	                    update_user_meta($user_id, 'moodle_user_id', $moodle_user['user_data']->id);
	                }
	            } else {
		            $edwiser_bridge->userManager()->linkMoodleUser($user);
	            }

	            if (isset($moodle_user['user_created']) && $moodle_user['user_created'] == 1 && is_object($moodle_user['user_data'])) {
                    update_user_meta($user_id, 'moodle_user_id', $moodle_user['user_data']->id);

                	if ($user_linked) {	                
	                    $args = array(
			                'user_email' => $user_data['email'],
							'username'   => $moodle_user['user_data']->username,
							'first_name' => $user_data['firstname'],
							'last_name'  => $user_data['lastname'],
							'password'   => $user_data['password'],
			            );
			            // create a new action hook with user details as argument.
			            do_action('eb_linked_to_existing_wordpress_to_new_user', $args);
                	}
                }
    		}
    	}



    	/**
		 * Add First & Last Name to My Account Register Form - WooCommerce
		 */  
		///////////////////////////////
		// 1. ADD FIELDS
		public function wi_add_name_fields_woo_account_registration() {
		    $eb_woo_settings = get_option('eb_woo_int_settings', array());
        	if (check_value_set($eb_woo_settings, 'wi_enable_my_account_user_creation') && 'yes' == $eb_woo_settings['wi_enable_my_account_user_creation']) {
			    ?>
			    <p class="form-row form-row-first">
			    <label for="eb_first_name"><?php _e( 'First name', 'woocommerce-integration' ); ?> <span class="required">*</span></label>
			    <input type="text" class="input-text" name="eb_first_name" id="eb_first_name" value="<?php if ( ! empty( $_POST['eb_first_name'] ) ) esc_attr_e( $_POST['eb_first_name'] ); ?>" />
			    </p>
			  
			    <p class="form-row form-row-last">
			    <label for="eb_last_name"><?php _e( 'Last name', 'woocommerce-integration' ); ?> <span class="required">*</span></label>
			    <input type="text" class="input-text" name="eb_last_name" id="eb_last_name" value="<?php if ( ! empty( $_POST['eb_last_name'] ) ) esc_attr_e( $_POST['eb_last_name'] ); ?>" />
			    </p>
			  
			    <div class="clear"></div>
			    <?php
			}
		}
		  
		/**
		 *Validate name fields i.e check if the fields entered are non empty on the woocommerce my-account registration page.
		 */
		// 2. VALIDATE FIELDS
		public function wi_validate_name_fields( $errors, $username, $email ) {

		    if ( isset( $_POST['eb_first_name'] ) && empty( $_POST['eb_first_name'] ) ) {
		        $errors->add( 'first_name_error', __( ' First name is required!', 'woocommerce-integration' ) );
		    }
		    if ( isset( $_POST['eb_last_name'] ) && empty( $_POST['eb_last_name'] ) ) {
		        $errors->add( 'last_name_error', __( ' Last name is required!.', 'woocommerce-integration' ) );
		    }
		    return $errors;
		}




		/**
		 * This function handles user profile field update on the Moodle site.
		 * @var int $user_id User id
		 */
       	public function wi_my_account_user_profile_update($user_id)
    	{

    		$eb_woo_settings = get_option('eb_woo_int_settings', array());
        	if (check_value_set($eb_woo_settings, 'wi_enable_my_account_field_update') && 'yes' == $eb_woo_settings['wi_enable_my_account_field_update']) {

	        	$moodle_user_id = get_user_meta($user_id, 'moodle_user_id', true); // get moodle user id

				//if moodle user id is not set then return
				if( empty($moodle_user_id) ) {
					return;
				}

				/*
				 * Password Update conditions will come here
				 */
	    		$user_data = array(
	            	'id'        => $moodle_user_id, // moodle user id
	                'firstname' => $_POST['account_first_name'],
	                'lastname'  => $_POST['account_last_name'],
	                // 'password'  => $password,
	                // 'username'  => $user->user_login,
	                // 'lang'      => $language,
	            );


	            $user = get_userdata( $user_id );
	            
	            // If the password and email is changed then only add those fields.  
	            if (check_value_set($_POST, 'password_1')) {
	            	$user_data['password'] = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : ''; 
	            }

		    	// if ($user->user_email != $_POST['account_email']) {
		    	if (check_value_set($_POST, 'account_email')) {
	                $user_data['email'] = $_POST['account_email'];
	            }

				//migration code
                $custom_field_plugin_path = 'edwiser-custom-fields/edwiser-custom-fields.php';
                if( ! is_plugin_active( $custom_field_plugin_path ) ){
					$mdl_cutom_field_arr = array();
					// Functionality to handle the custom field synchronization.
					$custom_fields = get_option( 'eb_wi_custom_fields', array() );

					foreach ( $custom_fields as $field_name => $field_details ) {
						if ( isset( $field_details['woo-my-accnt'] ) && $field_details['woo-my-accnt'] ) {

							$field_value = isset( $_POST[$field_name] ) ? sanitize_text_field( $_POST[$field_name] ) : '' ;

							if ( 'checkbox' == $field_details['type'] && ! empty( $field_value ) ) {
								$field_value = 1;
							}

							update_user_meta( $user_id, $field_name, $field_value );

							if ( isset( $field_details['sync-on-moodle'] ) && ! empty( $field_details['sync-on-moodle'] ) ) {

								array_push(
									$mdl_cutom_field_arr,
									array(
										"type"  => $field_name,
										"value" => $field_value
									)
								);
							}
						}
					}

					$user_data['customfields'] = $mdl_cutom_field_arr;
				}

	    		$edwiser_bridge = new EdwiserBridge();
	            $edwiser_bridge->userManager()->createMoodleUser($user_data, 1);
        	}
    	}


    	/**
    	 * Validate my account page fields for the profile fields update.
    	 *
    	 */
    	public function validate_my_account_page_fields(&$args, &$user_form_data)
    	{
    		// wc_add_notice( '<strong>' . esc_html( $field_label ) . '</strong> ' . __( 'is a required field.', 'woocommerce' ), 'error' );

	    	// CHeck if user name is avaialable. 
	    	/*if (!$this->isMoodleUsernameAvailable($user_data['username'])) {
	    		wc_add_notice( '<strong>' . esc_html( $field_label ) . '</strong> ' . __( 'is a required field.', 'woocommerce-integration' ), 'error' );
	    	}*/

            $user = get_userdata($user_form_data->ID);

	    	// check if the email is available.
	    	if ($user->user_email != $user_form_data->user_email && !$this->is_mdl_email_available($user_form_data->user_email)) {

	    		// wc_add_notice( '<strong>' . esc_html( $field_label ) . '</strong> ' . __( '.', 'woocommerce-integration' ), 'error' );
	    		wc_add_notice( '<strong>' . __('Email', 'woocommerce-integration') . '</strong> ' . __( ' already exist on Moodle please use different email.', 'woocommerce-integration' ), 'error' );
	    	}

    	} 




    	/**
    	 * Check if the user email exists on the Moodle site before updating it.
    	 *
    	 */
    	public function is_mdl_email_available($email)
		{
		    //global $wpdb;

		    $edwiserBridgeInstance = new EdwiserBridge();
		    $email = sanitize_user($email); // get sanitized username
		    //$user       = array();
		    $webservice_function = 'core_user_get_users_by_field';

		    // prepare request data array
		    $request_data = array('field' => 'email', 'values' => array($email));
		    // $response = edwiserBridgeInstance()->connectionHelper()->connectMoodleWithArgsHelper($webservice_function, $request_data);
		    $response = $edwiserBridgeInstance->connectionHelper()->connectMoodleWithArgsHelper($webservice_function, $request_data);

		    // return true only if username is available
		    if ($response['success'] == 1 && empty($response['response_data'])) {
		        return true;
		    } else {
		        return false;
		    }
		}


		/**
		 * below are the steps to add new tab in the woocommerce my-account page.  
 	 	 */
		// Step 1. Add rewrite rule. 
		public function wi_add_my_courses_endpoint()
		{
            add_rewrite_endpoint( 'eb_my_courses', EP_ROOT | EP_PAGES );
        }
           
           
           
        // ------------------
        // 2. Add new query var
        public function wi_add_my_courses_query_vars($vars)
        {
            $vars[] = 'eb_my_courses';
            return $vars;
        }
           
           
           
        // ------------------
        // 3. Insert the new endpoint into the My Account menu
        public function wi_add_my_courses_link_my_account($items)
        {
        	$eb_woo_settings = get_option('eb_woo_int_settings', array());
        	$new_items = array();
        	if (check_value_set($eb_woo_settings, 'wi_show_my_courses_on_my_account') && 'yes' == $eb_woo_settings['wi_show_my_courses_on_my_account']) {

            	foreach ($items as $key => $value) {
            		if ('customer-logout' == $key) {
		            	$new_items['eb_my_courses'] = esc_html__( 'My Courses', 'woocommerce-integration' );
            		}
            		$new_items[$key] = $value;
            	}
        	} else {
        		$new_items = $items;
        	}

            return $new_items;
        }
           
           
           
        // ------------------
        // 4. Add content to the new endpoint
        // Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
        public function wi_add_my_courses_content()
        {
            // get selected my courses page content. 
            $eb_general_option = get_option('eb_general', array());

            // If page is selected then show its content
            if (\NmBridgeWoocommerce\check_value_set($eb_general_option, 'eb_my_courses_page_id')) {
                $content = get_post($eb_general_option['eb_my_courses_page_id']);

                echo do_shortcode($content->post_content);
            } else {

                // echo the default shortcode.
                echo do_shortcode('[eb_my_courses my_courses_wrapper_title="My Courses" recommended_courses_wrapper_title="Recommended Courses" number_of_recommended_courses="4" ]');
            }
        }

       	/**
		* Flush rewrite rules.
		*/
		public function wi_flush_rewrite_rules()
		{
				flush_rewrite_rules();
		} 
      
    }
}

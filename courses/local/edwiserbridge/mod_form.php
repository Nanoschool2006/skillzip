<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * settings mod form
 * @package   local_edwiserbridge
 * @author    Wisdmlabs
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");



/**
*form shown while adding activity.
*/
class edwiserbridge_navigation_form extends moodleform
{
	public function definition()
	{
		global $CFG;
		$mform = $this->_form;

		$current_tab = isset( $_GET["tab"] ) ? $_GET["tab"] : '';

		$summary_status = eb_get_summary_status();

		$summary = 'summary' === $current_tab ? 'active-tab eb-tabs eb_summary_tab summary_tab_' . $summary_status : 'eb-tabs eb_summary_tab  summary_tab_' . $summary_status;

		$tabs = array(
			array(
				'link'  => $CFG->wwwroot . "/local/edwiserbridge/edwiserbridge.php?tab=settings",
				'label' => get_string( 'tab_mdl_required_settings', 'local_edwiserbridge' ),
				'css'   => 'settings' === $current_tab ? 'active-tab eb-tabs' : 'eb-tabs',
			),
			array(
				'link'  => $CFG->wwwroot . "/local/edwiserbridge/edwiserbridge.php?tab=service",
				'label' => get_string( 'tab_service', 'local_edwiserbridge' ),
				'css'   => 'service' === $current_tab ? 'active-tab eb-tabs' : 'eb-tabs',
			),
			array(
				'link'  => $CFG->wwwroot . "/local/edwiserbridge/edwiserbridge.php?tab=connection",
				'label' => get_string( 'tab_conn', 'local_edwiserbridge' ),
				'css'   => 'connection' === $current_tab ? 'active-tab eb-tabs' : 'eb-tabs',
			),
			array(
				'link'  => $CFG->wwwroot . "/local/edwiserbridge/edwiserbridge.php?tab=synchronization",
				'label' => get_string( 'tab_synch', 'local_edwiserbridge' ),
				'css'   => 'synchronization' === $current_tab ? 'active-tab eb-tabs' : 'eb-tabs',
			),
			array(
				'link'  => $CFG->wwwroot . "/local/edwiserbridge/edwiserbridge.php?tab=summary",
				'label' => get_string( 'summary', 'local_edwiserbridge' ),
				'css'   => $summary,
			),
		);

		$mform->addElement( 'html', '<div class="eb-tabs-cont">' . $this->print_tabs( $tabs ) . '</div>' );
	}

	/**
	 *
	 * Preapares and print the list of the tab links.
	 *
	 * @param array $tabs an array of settings array.
	 */
	private function print_tabs( $tabs ) {
		ob_start();
		foreach ( $tabs as $tab ) {
			?>
			<a href="<?php echo $tab['link']; ?>" class="<?php echo $tab['css']; ?>">
				<?php echo $tab['label']; ?>
			</a>
			<?php
		}
		return ob_get_clean();
	}

	public function validation( $data, $files )
	{
		return array();
	}
}


/**
* Used to create web service.
*/
class edwiserbridge_service_form extends moodleform
{
	public function definition()
	{
		global $CFG;

		$mform            = $this->_form;
		$existingservices = eb_get_existing_services();
		$authusers        = eb_get_administrators();

		$token       = isset($CFG->edwiser_bridge_last_created_token) ? $CFG->edwiser_bridge_last_created_token : ' - ';
		$service     = isset($CFG->ebexistingserviceselect) ? $CFG->ebexistingserviceselect : '';
		$token_field = '';

		// 1st Field Service list
		$select = $mform->addElement('select', 'eb_sevice_list', get_string('existing_serice_lbl', 'local_edwiserbridge'), $existingservices);
		$mform->addHelpButton('eb_sevice_list', 'eb_mform_service_desc', 'local_edwiserbridge');
		$select->setMultiple(false);

		// $repeateloptions['wp_name']['helpbutton']  = array("wordpress_site_name", "local_edwiserbridge");

		// 2nd Field Service input name 
		$mform->addElement('text', 'eb_service_inp', get_string('new_service_inp_lbl', 'local_edwiserbridge'), array('class'=>'eb_service_field'));
		$mform->setType('eb_service_inp', PARAM_TEXT);


		// 3rd field Users List.
		$select = $mform->addElement('select', 'eb_auth_users_list', get_string('new_serivce_user_lbl', 'local_edwiserbridge'), $authusers, array('class'=>''));
		$select->setMultiple(false);


		// $mform->addElement('static', 'eb_mform_lang_wrap', get_string('lang_label', 'local_edwiserbridge'), '<div class="eb_copy_txt_wrap"> <div style="width:60%;"> <b class="eb_copy" id="eb_mform_lang">' . $CFG->lang . '</b> </div> <div>  <i class="fa fa-clipboard eb_primary_copy_btn" aria-hidden="true"></i> </div></div>');

		$mform->addElement('static', 'eb_mform_lang_wrap', get_string('lang_label', 'local_edwiserbridge'), '<div class="eb_copy_txt_wrap eb_copy_div"> <div style="width:60%;"> <b class="eb_copy" id="eb_mform_lang">' . $CFG->lang . '</b> </div> <div>  <button class="btn btn-primary eb_primary_copy_btn">'.get_string('copy', 'local_edwiserbridge') .'</button></div></div>');


		// $mform->addElement('static', 'eb_mform_site_url_wrap', get_string('site_url', 'local_edwiserbridge'), '<div class="eb_copy_txt_wrap"> <div style="width:60%;"> <b id="eb_mform_site_url">' . $CFG->wwwroot . '</b> </div> <div> <span style="color:#1177d1">'. get_string('copy', 'local_edwiserbridge') .'</span></div></div>');
		$mform->addHelpButton('eb_mform_lang_wrap', 'eb_mform_lang_desc', 'local_edwiserbridge');



		// 4th field Site Url
		$mform->addElement('static', 'eb_mform_site_url_wrap', get_string('site_url', 'local_edwiserbridge'), '<div class="eb_copy_txt_wrap eb_copy_div"> <div style="width:60%;"> <b class="eb_copy" id="eb_mform_site_url">' . $CFG->wwwroot . '</b> </div> <div> <button class="btn btn-primary eb_primary_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</button></div></div>');
		// $mform->addElement('static', 'eb_mform_site_url_wrap', get_string('site_url', 'local_edwiserbridge'), '<div class="eb_copy_txt_wrap"> <div style="width:60%;"> <b id="eb_mform_site_url">' . $CFG->wwwroot . '</b> </div> <div> <span style="color:#1177d1">'. get_string('copy', 'local_edwiserbridge') .'</span></div></div>');
		$mform->addHelpButton('eb_mform_site_url_wrap', 'eb_mform_ur_desc', 'local_edwiserbridge');

		if (!empty($service)) {
			//If the token available then show the token
			$token_field = eb_create_token_field($service, $token);
		} else {
			// If service is empty then show just the blank text with dash
			$token_field = $token;
		}




		// 5th field Token
		$mform->addElement('static', 'eb_mform_token_wrap', get_string('token', 'local_edwiserbridge'), '<b id="eb_mform_token">' . $token_field . '</b>');
		$mform->addHelpButton('eb_mform_token_wrap', 'eb_mform_token_desc', 'local_edwiserbridge');



		$mform->addElement('static', 'eb_mform_common_error', '', '<div id="eb_common_err"></div><div id="eb_common_success"></div>');

		$mform->addElement('button', 'eb_mform_create_service', get_string("link", 'local_edwiserbridge'));

		if( ! class_exists('webservice')){
			require_once $CFG->dirroot."/webservice/lib.php";
		}


		//set default values
		if (!empty($service)) {
			$mform->setDefault("eb_sevice_list", $service);
		}


		$mform->addElement(
			'html',
			'<div class="eb_connection_btns">
				
				<a href="'.$CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection'.'" class="btn btn-primary eb_setting_btn" > '.get_string("next", 'local_edwiserbridge').'</a>

			</div>
			');
	}

	public function validation($data, $files)
	{
		return array();
	}
}


/**
*form shown while adding activity.
*/
class edwiserbridge_connection_form extends moodleform
{
	public function definition()
	{
		$defaultvalues = get_connection_settings();
		$mform = $this->_form;
		$repeatarray = array();

		$repeatarray[] = $mform->createElement('header', 'wp_header', get_string('wp_site_settings_title', 'local_edwiserbridge'). "<div class ='test'> </div>");

		$repeatarray[] = $mform->createElement('text', 'wp_name', get_string('wordpress_site_name', 'local_edwiserbridge'), 'size="35"');
		$repeatarray[] = $mform->createElement('text', 'wp_url', get_string('wordpress_url', 'local_edwiserbridge'), 'size="35"');
		$repeatarray[] = $mform->createElement('text', 'wp_token', get_string('wp_token', 'local_edwiserbridge'), 'size="35"');
		$repeatarray[] = $mform->createElement('hidden', 'wp_remove', 'no');


		$buttonarray = array();
		$buttonarray[] = $mform->createElement('button', 'eb_test_connection', get_string("wp_test_conn_btn", "local_edwiserbridge"), "", "");
		$buttonarray[] = $mform->createElement('button', 'eb_remove_site', get_string("wp_test_remove_site", "local_edwiserbridge"));
		$buttonarray[] = $mform->createElement('html', '<div id="eb_test_conne_response"> </div>');

		$repeatarray[] = $mform->createElement("group", "eb_buttons", "", $buttonarray);


		/*
		 * Data type of each field.
		 */
		$repeateloptions = array();
		$repeateloptions['wp_name']['type']    = PARAM_TEXT;
		$repeateloptions['wp_url']['type']     = PARAM_TEXT;
		$repeateloptions['wp_token']['type']   = PARAM_TEXT;
		$repeateloptions['wp_remove']['type']  = PARAM_TEXT;


		/*
		 * Name of each field.
		 */
		$repeateloptions['wp_name']['helpbutton']  = array("wordpress_site_name", "local_edwiserbridge");
		$repeateloptions['wp_token']['helpbutton'] = array("token", "local_edwiserbridge");
		$repeateloptions['wp_url']['helpbutton']   = array("wordpress_url", "local_edwiserbridge");

		/*
		 * Adding rule for each field.
		 */
		/*$repeateloptions['wp_name']['rule']  = 'required';
		$repeateloptions['wp_url']['rule']   = 'required';
		$repeateloptions['wp_token']['rule'] = 'required';*/


		$count = 1;
		if (!empty($defaultvalues) && !empty($defaultvalues["eb_connection_settings"])) {
			$count = count($defaultvalues["eb_connection_settings"]);
			$siteNo = 0;
			foreach ($defaultvalues["eb_connection_settings"] as $key => $value) {
				$mform->setDefault("wp_name[".$siteNo."]", $value["wp_name"]);
				$mform->setDefault("wp_url[".$siteNo."]", $value["wp_url"]);
				$mform->setDefault("wp_token[".$siteNo."]", $value["wp_token"]);
				$siteNo++;
			}
		}


		$this->repeat_elements($repeatarray, $count, $repeateloptions, 'eb_connection_setting_repeats', 'eb_option_add_fields', 1, get_string("add_more_sites", "local_edwiserbridge"), true);

		//closing header section
		$mform->closeHeaderBefore('eb_option_add_fields');


		$mform->addElement(
			'html',
			'<div class="eb_connection_btns">
				<input type="submit" class="btn btn-primary eb_setting_btn" id="conne_submit" name="conne_submit" value="'.get_string("save", "local_edwiserbridge").'">
				<input type="submit" class="btn btn-primary eb_setting_btn" id="conne_submit_continue" name="conne_submit_continue" value="'.get_string("save_cont", "local_edwiserbridge").'">
			</div>');



		//fill form with the existing values
		// $this->add_action_buttons(false);
	}

	public function validation($data, $files)
	{
		$errors = parent::validation($data, $files);

		$processedData = $data;
		for ($i = count($data["wp_name"]) - 1; $i >= 0; $i--) {
			//Delete the current values from the copy of the data array.
			unset($processedData["wp_name"][$i]);
			unset($processedData["wp_url"][$i]);

			if (empty($data["wp_name"][$i])) {
				$errors['wp_name['.$i.']'] = get_string('required', 'local_edwiserbridge');
			} elseif (in_array($data["wp_name"][$i], $processedData["wp_name"])) {
				//checking if the current name value exitsts in array.
				$errors['wp_name['.$i.']'] = get_string('sitename-duplicate-value', 'local_edwiserbridge');
			}


			if (empty($data["wp_url"][$i])) {
				$errors['wp_url['.$i.']'] = get_string('required', 'local_edwiserbridge');
			} elseif (in_array($data["wp_url"][$i], $processedData["wp_url"])) {
				//checking if the current URL value exitsts in array.
				$errors['wp_url['.$i.']'] = get_string('url-duplicate-value', 'local_edwiserbridge');
			}


			if (empty($data["wp_token"][$i])) {
				$errors['wp_token['.$i.']'] = get_string('required', 'local_edwiserbridge');
			}

			//if the site settings is removed then remove the validation errors also.
			if (isset($errors['wp_name['.$i.']']) && isset($errors['wp_url['.$i.']']) && isset($errors['wp_token['.$i.']']) && isset($data['wp_remove'][$i]) && 'yes' == $data['wp_remove'][$i]) {
				unset($errors['wp_name['.$i.']']);
				unset($errors['wp_url['.$i.']']);
				unset($errors['wp_token['.$i.']']);
			}
		}
		return $errors;
	}
}


/**
* form shown while adding activity.
*/
class edwiserbridge_synchronization_form extends moodleform
{
	public function definition()
	{
		$mform = $this->_form;
		$sites = get_site_list();
		$site_keys = array_keys($sites);
		$defaultvalues = get_synch_settings($site_keys[0]);

		$mform->addElement('select', 'wp_site_list', get_string('site-list', 'local_edwiserbridge'), $sites);

		// 1st Field
		// Course enrollment
		$mform->addElement('advcheckbox', 'course_enrollment', get_string('enrollment_checkbox', 'local_edwiserbridge'), get_string("enrollment_checkbox_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 2nd field
		// Course unenrollment
		$mform->addElement('advcheckbox', 'course_un_enrollment', get_string('unenrollment_checkbox', 'local_edwiserbridge'), get_string("unenrollment_checkbox_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));
		
		// 3rd field.
		// Course deletion.
		$mform->addElement('advcheckbox', 'course_deletion', get_string('course_deletion', 'local_edwiserbridge'), get_string("course_deletion_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 4th field.
		// user creation.
		$mform->addElement('advcheckbox', 'user_creation', get_string('user_creation', 'local_edwiserbridge'), get_string("user_creation_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));
		
		// 5th field.
		// User update
		$mform->addElement('advcheckbox', 'user_deletion', get_string('user_deletion', 'local_edwiserbridge'), get_string("user_deletion_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 6th field.
		// User deletion
		$mform->addElement('advcheckbox', 'user_updation', get_string('user_updation', 'local_edwiserbridge'), get_string("user_updation_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));


		//fill form with the existing values
		
		if (!empty($defaultvalues)) {
			$mform->setDefault("course_enrollment", $defaultvalues["course_enrollment"]);
			$mform->setDefault("course_un_enrollment", $defaultvalues["course_un_enrollment"]);
			$mform->setDefault("user_creation", $defaultvalues["user_creation"]);
			$mform->setDefault("user_deletion", $defaultvalues["user_deletion"]);
			$mform->setDefault("course_deletion", $defaultvalues["course_deletion"]);
			$mform->setDefault("user_updation", $defaultvalues["user_updation"]);
		}



		 $mform->addElement(
			'html',
			'<div class="eb_connection_btns">
				<input type="submit" class="btn btn-primary eb_setting_btn" id="sync_submit" name="sync_submit" value="'.get_string("save", "local_edwiserbridge").'">
				<input type="submit" class="btn btn-primary eb_setting_btn" id="sync_submit_continue" name="sync_submit_continue" value="'.get_string("save_cont", "local_edwiserbridge").'">
			</div>');



		// $this->add_action_buttons();
	}

	public function validation($data, $files)
	{
		return array();
	}
}



/**
* Used to create web service.
*/
class edwiserbridge_settings_form extends moodleform
{
	public function definition()
	{
		global $CFG;

		$mform            = $this->_form;
		// $existingservices = eb_get_existing_services();
		// $authusers        = eb_get_administrators();
		$defaultvalues = get_required_settings();

		// 1st field.
		$mform->addElement('advcheckbox', 'rest_protocol', get_string('web_rest_protocol_cb', 'local_edwiserbridge'), get_string("web_rest_protocol_cb_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 2nd field.
		$mform->addElement('advcheckbox', 'web_service', get_string('web_service_cb', 'local_edwiserbridge'), get_string("web_service_cb_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 3rd field.
		$mform->addElement('advcheckbox', 'pass_policy', get_string('password_policy_cb', 'local_edwiserbridge'), get_string("password_policy_cb_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		// 4th field.
		$mform->addElement('advcheckbox', 'extended_username', get_string('extended_char_username_cb', 'local_edwiserbridge'), get_string("extended_char_username_cb_desc", "local_edwiserbridge"), array('group' => 1), array(0, 1));

		//fill form with the existing values
		if (!empty($defaultvalues)) {
			$mform->setDefault("rest_protocol", $defaultvalues["rest_protocol"]);
			$mform->setDefault("web_service", $defaultvalues["web_service"]);
			$mform->setDefault("pass_policy", $defaultvalues["pass_policy"]);
			$mform->setDefault("extended_username", $defaultvalues["extended_username"]);
		}


		$mform->addElement(
			'html',
			'<div class="eb_connection_btns">
				<input type="submit" class="btn btn-primary eb_setting_btn" id="settings_submit" name="settings_submit" value="'.get_string("save", "local_edwiserbridge").'">

				<input type="submit" class="btn btn-primary eb_setting_btn" id="settings_submit_continue" name="settings_submit_continue" value="'.get_string("save_cont", "local_edwiserbridge").'">
			</div>');


		// $this->add_action_buttons(false);
	}

	public function validation($data, $files)
	{
		return array();
	}
}


/**
* Used to create web service.
*/
class edwiserbridge_summary_form extends moodleform
{
	public function definition()
	{
		global $CFG;
		$plugins_vdata = $this->get_plugin_version_data(); 
		$mform        = $this->_form;

		$token        = isset($CFG->edwiser_bridge_last_created_token) ? $CFG->edwiser_bridge_last_created_token : ' - ';
		$service      = isset($CFG->ebexistingserviceselect) ? $CFG->ebexistingserviceselect : '';
		$token_field  = '';
		$service_name = '';
		$missing_cap_msg  = '<span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003; </span>';
		$url              = $CFG->wwwroot."/admin/webservice/service_users.php?id=$service";
		$functions_page   = "<a href='$url' target='_blank'>here</a>";
		/**
		 * Check web service user have a capability to use the web service.
		 */
		$webservicemanager = new webservice();
		if(!empty($service)){
			$allowedusers = $webservicemanager->get_ws_authorised_users($service);
			$usersmissingcaps = $webservicemanager->get_missing_capabilities_by_users($allowedusers, $service);
			$webservicemanager->get_external_service_by_id($service);
			foreach ($allowedusers as &$alloweduser) {
				if (!is_siteadmin($alloweduser->id) and array_key_exists($alloweduser->id, $usersmissingcaps)) {
					$missing_cap_msg = "<span class='summ_error'>User don't have web service access capabilities, click $functions_page to know more.</span>";
				}
			}
		
			/**
			 * Get the web service name.
			 */
			$serviceObj = $webservicemanager->get_external_service_by_id($service);
			if (isset($serviceObj->name)) {
				$service_name = $serviceObj->name;
			}

			if (!empty($service)) {
				//If the token available then show the token
				$token_field = eb_create_token_field($service, $token);
			} else {
				// If service is empty then show just the blank text with dash
				$token_field = $token;
			}

		}else{
			$missing_cap_msg = "<span class='summ_error'>User don't have web service access capabilities, click $functions_page to know more.</span>";
		}
		$summary_array = array(
			'summary_setting_section' => array(
				'webserviceprotocols' => array(
					'label'          => get_string('sum_rest_proctocol', 'local_edwiserbridge'),
					'expected_value' => 'dynamic',
					'value'          => 1,
					'error_msg'      =>  get_string('sum_error_rest_proctocol', 'local_edwiserbridge'),
					'error_link'     => $CFG->wwwroot."/local/edwiserbridge/edwiserbridge.php?tab=settings"
				),
				'enablewebservices'   => array(
					'expected_value' => 1,
					'label'          => get_string('sum_web_services', 'local_edwiserbridge'),
					'error_msg'      => get_string('sum_error_web_services', 'local_edwiserbridge'),
					'error_link'     => $CFG->wwwroot."/local/edwiserbridge/edwiserbridge.php?tab=settings"
				),
				'passwordpolicy'     => array(
					'expected_value' => 0,
					'label'          => get_string('sum_pass_policy', 'local_edwiserbridge'),
					'error_msg'      => get_string('sum_error_pass_policy', 'local_edwiserbridge'),
					'error_link'     => $CFG->wwwroot."/local/edwiserbridge/edwiserbridge.php?tab=settings"
				),
				'extendedusernamechars' => array(
					'expected_value' => 1,
					'label'          => get_string('sum_extended_char', 'local_edwiserbridge'),
					'error_msg'      => get_string('sum_error_extended_char', 'local_edwiserbridge'),
					'error_link'     => $CFG->wwwroot."/local/edwiserbridge/edwiserbridge.php?tab=settings"
				),
				'uptodatewebservicefunction' => array(
					'expected_value' => 'static',
					'label'          => get_string('web_service_status', 'local_edwiserbridge'),
					'value'             => "<div id='web_service_status' data-serviceid='$service'>Checking...</div>"
				),
				'webservicecap' => array(
					'expected_value' => 'static',
					'label'          => get_string('web_service_cap', 'local_edwiserbridge'),
					'value'          => "<div id='web_service_status'>$missing_cap_msg</div>"
				)
			),
			'summary_connection_section'  => array(
				'url' => array(
					'label'          => get_string('mdl_url', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          => '<div class="eb_copy_text_wrap" data> <span class="eb_copy_text" title="'. get_string('click_to_copy', 'local_edwiserbridge') .'">'. $CFG->wwwroot .'</span>'.' <span class="eb_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</span></div>'

				),
				'service_name' => array(
					'label'          => get_string('web_service_name', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" title="'. get_string('click_to_copy', 'local_edwiserbridge') .'">'. $service_name .'</span>'.' <span class="eb_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</span></div>'

				),
				'token' => array(
					'label'          => get_string('token', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          =>  '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" title="'. get_string('click_to_copy', 'local_edwiserbridge') .'">'. $token .'</span> <span class="eb_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</span></div>'

				),
				'lang_code' => array(
					'label'          => get_string('lang_label', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'         => '<div class="eb_copy_text_wrap"> <span class="eb_copy_text" title="'. get_string('click_to_copy', 'local_edwiserbridge') .'">'. $CFG->lang .'</span> <span class="eb_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</span></div>'
			   ),
			),
			'edwiser_bridge_plugin_summary'  => array(
				'' => array(
					'label'          => '',
					'expected_value' => 'static',
					'value'          => $this->get_plugin_fetch_link(),

				),
				'mdl_edwiser_bridge' => array(
					'label'          => get_string('mdl_edwiser_bridge_lbl', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          => $plugins_vdata['edwiserbridge'],

				),
				'mdl_edwiser_bridge_sso' => array(
					'label'          => get_string('mdl_edwiser_bridge_sso_lbl', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          => $plugins_vdata['wdmwpmoodle'],

				),
				'mdl_edwiser_bridge_bp' => array(
					'label'          => get_string('mdl_edwiser_bridge_bp_lbl', 'local_edwiserbridge'),
					'expected_value' => 'static',
					'value'          => $plugins_vdata['wdmgroupregistration'],

				),
			)
		);





		$error   = 0;
		$warning = 0;
		$html    = '';

		foreach ($summary_array as $section_key => $section) {
			
			$html .= '<div class="summary_section"> <div class="summary_section_title">'. get_string($section_key, 'local_edwiserbridge') .'</div>';
		
			$html .= '<table class="summary_section_tbl">';

			foreach ($section as $key => $value) {
				$html .= "<tr id='$key'><td class='sum_label'>";
				$html .= $value['label'];
				$html .= '</td>';


				if ($value['expected_value'] === 'static') {

					// $html .= '<td class="sum_status"> <span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003;</span></td>';

					$html .= '<td class="sum_status">' . $value['value'] . '<td>';

				} elseif ($value['expected_value'] === 'dynamic') {
					if ($key == 'webserviceprotocols') {
						$active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
						if (!in_array('rest', $active_webservices)) {
							// return 'error';

							$html .= '<td class="sum_status">
								<span class="summ_error"> '. $value['error_msg'] .'<a href="'.$value['error_link'].'" target="_blank" >'.get_string('here', 'local_edwiserbridge').'</a> </span>
							</td>';       
							$error = 1;

						} else {
							$success_msg = 'Disabled';
							if ($value['expected_value']) {
								$success_msg = 'Enabled';
							}


							$html .= '<td class="sum_status">
								<span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003; </span>
								<span style="color: #7ad03a;"> '. $success_msg .' </span>
							</td>';
						}
					}


				} elseif (isset($CFG->$key) && $value['expected_value'] == $CFG->$key) {

					$success_msg = 'Disabled';
					if ($value['expected_value']) {
						$success_msg = 'Enabled';
					}

					$html .= '<td class="sum_status">
								<span class="summ_success" style="font-weight: bolder; color: #7ad03a; font-size: 22px;">&#10003; </span>
								<span style="color: #7ad03a;"> '. $success_msg .' </span>
							</td>';
				} else {

					// $html .= '<td class="sum_status"><span class="summ_error">&#9888; '. $value['error_msg'] .'<a href="'.$value['error_link'].'" target="_blank" >'.get_string('here', 'local_edwiserbridge').'</a> </span></td>';

					 $html .= '<td class="sum_status" id="'.$key.'">
								<span class="summ_error"> '. $value['error_msg'] .'<a href="'.$value['error_link'].'" target="_blank" >'.get_string('here', 'local_edwiserbridge').'</a> </span>
							</td>';       
					$error = 1;
				}
				$html .= '</td>
						</tr>';
			}

			$html .= '</table>';
			$html .= ' </div>';
		}

		$mform->addElement(
			'html',
			$html
		);
	}

	private function get_plugin_fetch_link(){
		global $CFG;
		$url = $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=summary&fetch_data=true';
		return "<a href='{$url}'><i class='fa fa-refresh'></i> ".get_string('mdl_edwiser_bridge_fetch_info', 'local_edwiserbridge')."</a>";
	}

	private function get_plugin_version_data(){
		$plugins_data =array();
		$pluginman = \core_plugin_manager::instance();
        $localplugin = $pluginman->get_plugins_of_type('local');
		$plugins_data['edwiserbridge']=isset($localplugin['edwiserbridge'])?$localplugin['edwiserbridge']->release:get_string('mdl_edwiser_bridge_txt_not_avbl','local_edwiserbridge');
		$plugins_data['wdmgroupregistration']=isset($localplugin['wdmgroupregistration'])?$localplugin['wdmgroupregistration']->release:get_string('mdl_edwiser_bridge_txt_not_avbl','local_edwiserbridge');
        $authplugin = $pluginman->get_plugins_of_type('auth');
		$plugins_data['wdmwpmoodle']=isset($authplugin['wdmwpmoodle'])?$authplugin['wdmwpmoodle']->release:get_string('mdl_edwiser_bridge_txt_not_avbl','local_edwiserbridge');
		$fetch_data =(isset($_GET['fetch_data']) && 'true'===$_GET['fetch_data']) ? true : false;
		$remote_data = $this->get_remote_plugins_data($fetch_data);
		$version_info = array(
			'edwiserbridge'=>$plugins_data['edwiserbridge']."<span style='padding-left:1rem;color:limegreen;'>".get_string('mdl_edwiser_bridge_txt_latest','local_edwiserbridge')." </span>",
			'wdmgroupregistration' => $plugins_data['wdmgroupregistration']."<span style='padding-left:1rem;color:limegreen;'>".get_string('mdl_edwiser_bridge_txt_latest','local_edwiserbridge')." </span>",
			'wdmwpmoodle' => $plugins_data['wdmwpmoodle']."<span style='padding-left:1rem;color:limegreen;'>".get_string('mdl_edwiser_bridge_txt_latest','local_edwiserbridge')." </span>",
		);
		if(false !== $remote_data){
			if(isset( $remote_data->moodle_edwiser_bridge->version ) && version_compare($plugins_data['edwiserbridge'],$remote_data->moodle_edwiser_bridge->version,"<")){
				$version_info['edwiserbridge'] =$plugins_data['edwiserbridge']. "<span  style='padding-left:1rem;'>(".$remote_data->moodle_edwiser_bridge->version.")<a href='".$remote_data->moodle_edwiser_bridge->url."' title='".get_string('mdl_edwiser_bridge_txt_download_help','local_edwiserbridge')."'>".get_string('mdl_edwiser_bridge_txt_download','local_edwiserbridge')."</a></span>";
			}
			if(isset( $remote_data->moodle_edwiser_bridge_bp->version ) && version_compare($plugins_data['wdmgroupregistration'],$remote_data->moodle_edwiser_bridge_bp->version,"<")){
				$version_info['wdmgroupregistration'] =$plugins_data['wdmgroupregistration']. "<span  style='padding-left:1rem;'>(".$remote_data->moodle_edwiser_bridge_bp->version.")<a href='".$remote_data->moodle_edwiser_bridge_bp->url."' title='".get_string('mdl_edwiser_bridge_txt_download_help','local_edwiserbridge')."'>".get_string('mdl_edwiser_bridge_txt_download','local_edwiserbridge')."</a></span>";
			}
			if(isset( $remote_data->moodle_edwiser_bridge_sso->version ) && version_compare($plugins_data['wdmwpmoodle'],$remote_data->moodle_edwiser_bridge_sso->version,"<")){
				$version_info['wdmwpmoodle'] =$plugins_data['wdmwpmoodle']. "<span  style='padding-left:1rem;'>(".$remote_data->moodle_edwiser_bridge_sso->version.")<a href='".$remote_data->moodle_edwiser_bridge_sso->url."' title='".get_string('mdl_edwiser_bridge_txt_download_help','local_edwiserbridge')."'>".get_string('mdl_edwiser_bridge_txt_download','local_edwiserbridge')."</a></span>";
			}	
		}	
		return $version_info;
	}

	private function get_remote_plugins_data($fetch_data){
		$data = get_config('local_edwiserbridge','edwiserbridge_plugins_versions');
		$request_data =true;
		
		if($data || $fetch_data){
			$data = json_decode($data);
			if(isset($data->data) && isset($data->time) && $data->time >time()){
				$output= json_decode($data->data);
				$request_data = false;
			}
		}
		if($request_data){
			if(!function_exists('curl_version')){
				return false;
			}

			$curl = curl_init();
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => "https://edwiser.org/edwiserdemoimporter/bridge-free-plugin-info.json",
				CURLOPT_TIMEOUT => 100,
				CURLOPT_SSL_VERIFYHOST=>0,
				CURLOPT_SSL_VERIFYPEER=>0,
			));
			$output = curl_exec($curl);
			$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			if(200 === $httpcode){
				$data = array(
					'time'=>time()+(60*60*24),
					'data'=>$output,
				);
				set_config('edwiserbridge_plugins_versions',json_encode($data),'local_edwiserbridge');
			}
			$output=json_decode($output);
		}
		return $output;
	}

	public function validation($data, $files)
	{
		return array();
	}
}






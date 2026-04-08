<?php

require_once(dirname(__FILE__)."/classes/class-api-handler.php");
require_once(dirname(__FILE__)."/classes/class-settings-handler.php");
require_once("{$CFG->libdir}/completionlib.php");


function local_edwiserbridge_extend_settings_navigation($settingsnav, $context)
{

}


/**
 * [save_connection_form_settings description]
 * @param  [type] $form_data [description]
 * @return [type]            [description]
 */
function save_connection_form_settings($form_data)
{
    if (count($form_data->wp_url) != count($form_data->wp_token)) {
        return;
    }

    $connection_settings = array();
    for ($i=0; $i<count($form_data->wp_url); $i++) {
        if (!empty($form_data->wp_url[$i]) && !empty($form_data->wp_token[$i]) && !empty($form_data->wp_name[$i])) {
            $connection_settings[$form_data->wp_name[$i]] = array(
                "wp_url" => $form_data->wp_url[$i],
                "wp_token" => $form_data->wp_token[$i],
                "wp_name" => $form_data->wp_name[$i]
            );
        }
    }
    set_config("eb_connection_settings", serialize($connection_settings));
}

/**
 * Save the synch settings for the individual site
 */
function save_synchronization_form_settings($form_data)
{
    global $CFG;
    $synch_settings = array();
    $connection_settings = unserialize($CFG->eb_connection_settings);
    $connection_settings_keys = array_keys($connection_settings);


    if (in_array($form_data->wp_site_list, $connection_settings_keys)) {
        $existing_synch_settings = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : array();
        $synch_settings = $existing_synch_settings;
        $synch_settings[$form_data->wp_site_list] = array(
        // "course_enrollment"    => $form_data->wp_site_list,
        "course_enrollment"    => $form_data->course_enrollment,
        "course_un_enrollment" => $form_data->course_un_enrollment,
        "user_creation"        => $form_data->user_creation,
        "user_deletion"        => $form_data->user_deletion,
        "course_deletion"      => $form_data->course_deletion,
        "user_updation"        => $form_data->user_updation
        );
    } else {
        $synch_settings[$form_data->wp_site_list] = array(
            // "course_enrollment"    => $form_data->wp_site_list,
            "course_enrollment"    => $form_data->course_enrollment,
            "course_un_enrollment" => $form_data->course_un_enrollment,
            "user_creation"        => $form_data->user_creation,
            "user_deletion"        => $form_data->user_deletion,
            "course_deletion"      => $form_data->course_deletion,
            "user_updation"        => $form_data->user_updation
        );
    }
    set_config("eb_synch_settings", serialize($synch_settings));
}



function save_settings_form_settings($form_data)
{
    // echo $CFG->extendedusernamechars;

    if (isset($form_data->web_service) && isset($form_data->pass_policy) && isset($form_data->extended_username)) {

        $active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);

        if ($form_data->rest_protocol) {
            $active_webservices[] = 'rest';
        } else {
            $key = array_search('rest', $active_webservices);
            unset($active_webservices[$key]);
        }


        set_config('webserviceprotocols', implode(',', $active_webservices));
        set_config("enablewebservices", $form_data->web_service);
        set_config("extendedusernamechars", $form_data->extended_username);
        set_config("passwordpolicy", $form_data->pass_policy);

    }

}



function get_required_settings()
{
    // echo $CFG->extendedusernamechars;
    global $CFG;

    $required_settings = array();

    $active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
    
    // $start_zero = array_values($active_webservices);

    $required_settings['rest_protocol'] = 0;
    if (false !== array_search('rest', $active_webservices)) {
        $required_settings['rest_protocol'] = 1;
    }



    $required_settings['web_service'] = isset($CFG->enablewebservices) ? $CFG->enablewebservices : false;
    $required_settings['extended_username'] = isset($CFG->extendedusernamechars) ? $CFG->extendedusernamechars : false;
    $required_settings['pass_policy'] = isset($CFG->passwordpolicy) ? $CFG->passwordpolicy : false;

    return $required_settings;
}



/**
 * returns connection settings saved in the settings form.
 */
function get_connection_settings()
{
    global $CFG;
    $reponse["eb_connection_settings"] = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;
    return $reponse;
}


/**
 * returns individual sites data.
 * @param  [type] $index [description]
 * @return [type]        [description]
 */
function get_synch_settings($index)
{
    global $CFG;
    $reponse = isset($CFG->eb_synch_settings) ? unserialize($CFG->eb_synch_settings) : false;

    $data = array(
        "course_enrollment"    => 0,
        "course_enrollment"    => 0,
        "course_un_enrollment" => 0,
        "user_creation"        => 0,
        "user_deletion"        => 0,
        "course_deletion"      => 0,
        "user_updation"        => 0,
    );

    if (isset($reponse[$index]) && !empty($reponse[$index])) {
        return $reponse[$index];
    }
    return $data;
}


/**
 * returns all the sites created in the edwiser settings.
 * @return [type] [description]
 */
function get_site_list()
{
    global $CFG;
    $reponse = isset($CFG->eb_connection_settings) ? unserialize($CFG->eb_connection_settings) : false;

    $sites = array();
    if ($reponse && count($reponse)) {
        foreach ($reponse as $key => $value) {
            $sites[$key] = $value["wp_name"];
        }
    } else {
        return array("" => "--- No Sites Available ---");
    }
    return $sites;
}



/**
 * Returns the main instance of EDW to prevent the need to use globals.
 *
 * @since  1.0.0
 *
 * @return EDW
 */
function api_handler_instance()
{
    return api_handler::instance();
}


/**
 * returns the list of courses in which user is enrolled
 * @return [type]          [description]
 */
function get_array_of_enrolled_courses($user_id)
{
    $enrolled_courses = enrol_get_users_courses($user_id);
    $courses = array();

    foreach ($enrolled_courses as $value) {
        array_push($courses, $value->id);
    }
    return $courses;
}

/**
 * removes processed coureses from the course whose progress is already provided.
 * @return [type]            [description]
 */
function remove_processed_coures($course_id, $courses)
{
    if (($key = array_search($course_id, $courses)) !== false) {
        unset($courses[$key]);
    }
    return $courses;
}

/**
 * functionality to check if the request is from wordpress and the stop processing the enrollment and unenrollment.
 * @return
 */
function check_if_request_is_from_wp()
{
    if (isset($_POST) && isset($_POST["enrolments"])) {
        return 1;
    }
    return 0;
}






/*-----------------------------------------------------------
 *   Functions used in Settings page  
 *----------------------------------------------------------*/


function eb_get_administrators()
{

    $admins = get_admins(); 
    $settings_arr['']       = get_string('new_serivce_user_lbl', 'local_edwiserbridge');

    foreach ($admins as $value) {
        $settings_arr[$value->id] = $value->email;
    }
    return $settings_arr;
}




function eb_get_existing_services()
{
    global $DB, $CFG;
    $result = $DB->get_records("external_services", null, '','id, name');
    $settings_arr[''] = get_string('existing_serice_lbl', 'local_edwiserbridge');
    $settings_arr['create'] = ' - ' . get_string('new_web_new_service', 'local_edwiserbridge') . ' - ';


    foreach ($result as $value) {
        $settings_arr[$value->id] = $value->name;
    }

    return $settings_arr;
}





function eb_get_service_tokens($service_id)
{
    global $DB, $CFG;

    $result = $DB->get_records("external_tokens", null, '','token, externalserviceid');

    foreach ($result as $value) {
        $settings_arr[] = array('token' => $value->token, 'id' => $value->externalserviceid);
    }

    return $settings_arr;
}




function eb_create_token_field($service_id, $existing_token = '')
{

    $tokens_list = eb_get_service_tokens($service_id);

    $html = '<div class="eb_copy_txt_wrap">
                <div style="width:60%;">
                    <select class="eb_copy" class="custom-select" name="eb_token" id="id_eb_token">
                    <option value="">'. get_string('token_dropdown_lbl', 'local_edwiserbridge') .'</option>';

    foreach ($tokens_list as $token) {
        $selected = '';
        $display = '';

        if(isset($token['token']) && $token['token'] == $existing_token) {
            $selected = " selected"; 
        }

        if(isset($token['id']) && $token['id'] != $service_id) {
            $display = 'style="display:none"'; 
        }


        $html .= '<option data-id="'. $token['id'] .'" value="'. $token['token'] .'" '. $display ." " . $selected.'>'. $token['token'] .'</option>';
    }


    $html .= '      </select>
                </div>
                <div> <button class="btn btn-primary eb_primary_copy_btn">'. get_string('copy', 'local_edwiserbridge') .'</button> </div>
            </div>';

    return $html;
}








function eb_get_service_info($service_id)
{
    global $DB;
    $functions = array(
        array('externalserviceid' => $service_id, 'functionname' => 'core_user_create_users'),
        array('externalserviceid' => $service_id, 'functionname' => 'core_user_get_users_by_field'),
        array('externalserviceid' => $service_id, 'functionname' => 'core_user_update_users'),
        array('externalserviceid' => $service_id, 'functionname' => 'core_course_get_courses'),
        array('externalserviceid' => $service_id, 'functionname' => 'core_course_get_categories'),
        array('externalserviceid' => $service_id, 'functionname' => 'enrol_manual_enrol_users'),
        array('externalserviceid' => $service_id, 'functionname' => 'enrol_manual_unenrol_users'),
        array('externalserviceid' => $service_id, 'functionname' => 'core_enrol_get_users_courses'),
        array('externalserviceid' => $service_id, 'functionname' => 'eb_test_connection'),
        array('externalserviceid' => $service_id, 'functionname' => 'eb_get_site_data'),
        array('externalserviceid' => $service_id, 'functionname' => 'eb_get_course_progress'),
        array('externalserviceid' => $serviceid, 'functionname' => 'eb_get_edwiser_plugins_info'),
    );


    $count = 0;


    foreach ($functions as $function) {
        if (!$DB->record_exists('external_services_functions', array('functionname' => $function['functionname'], 'externalserviceid' => $service_id))) {
            $count ++;
        }
    }



    //add extension functions if they are present
    return $count;
}



function eb_get_summary_status()
{
    global $CFG;

    $settings_array = array(
        'enablewebservices'     => 1,
        'passwordpolicy'        => 0,
        'extendedusernamechars' => 1,
        'webserviceprotocols'   => 1

    );

    foreach ($settings_array as $key => $value) {
        if(isset($CFG->$key) && $value != $CFG->$key) {
            if ($key == 'webserviceprotocols') {
                $active_webservices = empty($CFG->webserviceprotocols) ? array() : explode(',', $CFG->webserviceprotocols);
                if (!in_array('rest', $active_webservices)) {
                    return 'error';
                }
            } else {
                return 'error';
            }
        }
    }

    $service_array = array(
        'ebexistingserviceselect',
        'edwiser_bridge_last_created_token'
    );


    foreach ($service_array as $value) {

        if(empty($CFG->$value)) {
            return 'warning';
        }
    }

    return 'sucess';

}


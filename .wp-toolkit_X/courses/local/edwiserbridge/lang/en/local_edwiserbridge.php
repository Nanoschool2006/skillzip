<?php

/**
 * Language File
 *
 * @package   local_edwiser
 * @author    Wisdmlabs
 */


/**************  by default strings used by the moodle  ****************/

$string["local_edwiserbridgedescription"] = "";
$string['modulename'] = "Edwiser Bridge";
$string['modulenameplural'] = "Edwiser Bridge";
$string['pluginname'] = 'Edwiser Bridge';
$string['pluginadministration'] = "Edwiser Bridge administrator";
$string['modulename_help'] = '';
$string['blank'] = '';
/**************  end of the strings used by default by the moodle  ****************/

/*** TABS  ***/
$string['tab_service'] = 'Web Service';
$string['tab_conn'] = 'Wordpress Site';
$string['tab_synch'] = 'Synchronization';
$string['tab_mdl_required_settings'] = 'General';
$string['summary'] = 'Summary';
/*******/
$string['lang_label'] = 'Language Code';
/******* navigation menu and settings page   ********/

$string["wp_site_settings_title"] = "Site Settings :";

// $string["nav_name"] = "Edwiser Bridge Settings";
$string["nav_name"] = "Settings";
$string["default_settings_nav"] = "Settings";


$string["edwiserbridge"] = "Edwiser Bridge";
$string["eb-setting-page-title"] = "Edwiser Bridge Two Way Synchronization Settings";
$string["eb-setting-page-title_help"] = "Edwiser Bridge Two Way Synchronization Settings";

// $string[""] = "Wordpress Synchronization Settings";
$string["enrollment_checkbox"] = "Enable User Enrollment.";
$string["enrollment_checkbox_desc"] = "Enroll user from Moodle to Wordpress for linked users.";
$string["unenrollment_checkbox"] = "Enable User Un-enrollment.";
$string["unenrollment_checkbox_desc"] = "Unenroll user from Moodle to Wordpress for linked users.";
$string["user_creation"] = "Enable User Creation";
$string["user_creation_desc"] = "Create user In linked Wordpress site when created in Moodle Site.";
$string["user_deletion"] = "Enable User Deletion";
$string["user_deletion_desc"] = "Delete user In linked Wordpress site when deleted in Moodle Site.";

$string["course_deletion"] = "Enable Course Deletion";
$string["course_deletion_desc"] = "This won't delete course but it will mark course as deleted in linked Wordpress site.";
$string["user_updation"] = "Enable User Update";
$string["user_updation_desc"] = "This will update user first name, last name and password and won't update Username and Email.";

$string["wp_settings_section"] = "Wordpress Connection Settings";
$string["wordpress_url"] = "Wordpress URL";
$string["wp_token"] = "Access Token";
$string["wp_test_conn_btn"] = "Test Connection";
$string["wp_test_remove_site"] = "Remove Site";
$string["add_more_sites"] = "Add New Site";
$string["wordpress_site_name"] = "Site Name";
$string["site-list"] = "Wordpress Sites";

$string['next'] = 'Next';
$string['save'] = 'Save';
$string['save_cont'] = 'Save and Continue';

$string["token_help"] = "Please enter access token used in Wordpress in connection setting";
$string["wordpress_site_name_help"] = "Please enter unique site name.";
$string["wordpress_url_help"] = "Please enter Wordpress site URL.";

$string["token"] = "Access Token";

$string['existing_web_service_desc'] = 'Select existing web service if you have created already.';
$string['new_web_service_desc'] = 'Create new web service';
$string['new_web_new_service'] = 'Create new web service';

$string['new_service_inp_lbl'] = 'Web Service Name';
$string['new_serivce_user_lbl'] = 'Select User';
$string['existing_serice_lbl'] = 'Select web service';
$string['token_dropdown_lbl'] = 'Select Token';

$string['web_service_token'] = 'Token generated after creating service';
$string['moodle_url'] = 'Moodle site URL.';
$string['web_service_name'] = 'Web Service Name';
$string['web_service_auth_user'] = 'Authorized user.';

$string['existing_service_desc'] = 'Edwiser web-service functions will get added into it and also be used as reference for upcoming updates.';
$string['auth_user_desc'] = 'All admin users used as Authorized User while creating token.';

$string['eb_settings_msg'] = 'To complete Edwiser Bridge Set up ';
$string['click_here'] = ' Click Here ';

$string['eb_mform_service_desc'] = 'Service desc';
$string['eb_mform_service_desc_help'] = 'Edwiser web-service functions will get added into it and also be used as reference for upcoming updates.';

$string['eb_mform_token_desc'] = 'Token';
$string['eb_mform_token_desc_help'] = 'This is your last created token used in wp for site integration.';
$string['eb_mform_ur_desc_help'] = 'Please copy this URL and paste it to your Wordpress site to complete the connection between Moodle and Wordpress.';
$string['eb_mform_ur_desc'] = 'Site URL';

$string['eb_mform_lang_desc_help'] = 'Please copy this language code and paste it to your Wordpress site to complete the connection between Moodle and Wordpress.';
$string['eb_mform_lang_desc'] = 'Site Language Code';

$string['site_url'] = 'Site URL';
/*********************************/

/*********** Settings page validation and Modal strings************/
$string['create_service_shortname_err'] = 'Unable to create the webservice please contact plugin owner.';
$string['create_service_name_err'] =   'This name is already in use please use different name.';
$string['create_service_creation_err'] = 'Unable to create the webservice please contact plugin owner.';
$string['empty_userid_err'] = 'Please select the user.';
$string['eb_link_success'] = 'Web service sucessfully linked.';
$string['eb_link_err'] = 'Unable to link the web service.';
$string['eb_service_select_err'] = 'Please select valid external web service.';
$string['eb_service_info_error'] = ' service functions missing in your currently selected service, Please Update service to add all missing webservice functions.';

$string['dailog_title'] = 'Token And Url';
$string['site_url'] = 'Site Url ';
$string['token'] = 'Token ';
$string['copy'] = 'Copy';
$string['copied'] = 'Copied !!!';
$string['create'] = 'Create Web Service';
$string['link'] = 'Update Web Service';
$string['click_to_copy'] = 'Click to copy.';
$string['pop_up_info'] = 'Copy Moodle site URL and the token to your Wordpress site Edwiser Bridge connection settings.';

/**********************************/

/******  Form validation.  ******/
$string['required'] = "- You must supply a value here.";
$string['sitename-duplicate-value'] = " - Site Name already exists, Please provide a different value.";
$string['url-duplicate-value'] = " - Wordpress Url already exists, Please provide a different value.";
/************/

/*****  web service  *******/
$string["web_service_wp_url"] = "Wordpress site URL.";
$string["web_service_wp_token"] = "Web service token.";

$string["web_service_test_conn_status"] = '1 if successful connection and 0 on failure.';
$string["web_service_test_conn_msg"] = 'Success or error message.';

$string["web_service_site_index"] = "Site index is the nth no. of site saved in Edwiser Bridge settings.";

$string["web_service_course_enrollment"] = "Checks if the course enrollment is performed for the saved site";
$string["web_service_course_un_enrollment"] = "Checks if the course un-enrollment is performed for the saved site";
$string["web_service_user_creation"] = "Checks if the user creation is performed for the saved site";
$string["web_service_user_deletion"] = "Checks if the user deletion is performed for the saved site";

$string["web_service_offset"] = "This is the offset for the select query.";
$string["web_service_limit"] = "This limits the number of users returned.";
$string["web_service_search_string"] = "This string will be searched in the select query.";
$string["web_service_total_users"] = "Total number of users present in Moodle.";

$string["web_service_id"] = "User Id.";
$string["web_service_username"] = "Username of the user.";
$string["web_service_firstname"] = "Firstname of the user.";
$string["web_service_lastname"] = "Lastname of the user.";
$string["web_service_email"] = "Email of the user.";
$string['eb_plugin_name']= "Plugin Name";
$string['eb_plugin_version']= "Plugin Version";
/******/

/****  error handling  ***/
$string["default_error"] = "Please check the URL or wordpress site permalink: to know more about this error <a href='https://edwiser.helpscoutdocs.com/collection/85-edwiser-bridge-plugin'  target='_blank'> click here </a>";

$string['eb_empty_name_err'] = 'Please enter valid service name.';
$string['eb_empty_user_err'] = 'Please select user.';

/**/
$string['please_enable'] = 'Error : Please fix this issue in settings';

/*****************  Set up Moodle Settings  *****************/
$string["password_policy_cb"] = "Password Policy.";
$string["password_policy_cb_desc"] = "If enabled, user passwords will be checked against the password policy as specified in the settings below. Enabling the password policy will not affect existing users until they decide to, or are required to, change their password.";

$string["extended_char_username_cb"] = "Allow extended characters in usernames.";
$string["extended_char_username_cb_desc"] = 'Enable this setting to allow students to use any characters in their usernames (note this does not affect their actual names). The default is "false" which restricts usernames to be alphanumeric lowercase characters, underscore (_), hyphen (-), period (.) or at symbol (@).';

$string["web_service_cb"] = "Enable Web Services.";
$string["web_service_cb_desc"] = "Recomended:yes";

$string["web_rest_protocol_cb"] = "Enable REST Protocol.";
$string["web_rest_protocol_cb_desc"] = "Recomended:yes";
/**********************************/

/********  Summary page  ********/
$string['sum_rest_proctocol'] = 'Rest Protocol';
$string['sum_web_services'] = 'Web Service';
$string['sum_pass_policy'] = 'Password Policy';
$string['sum_extended_char'] = 'Allow Extended Characters In Username';
$string['sum_service_link'] = 'Service Linked';
$string['sum_token_link'] = 'Token Linked';
$string['web_service_status'] = 'Web Service Function';
$string['web_service_cap'] = 'Capability';

$string['sum_error_rest_proctocol'] = 'Error: Please enable Rest Protocol';
$string['sum_error_web_services'] = 'Error: Please enable Web Service';
$string['sum_error_pass_policy'] = 'Error: Please disable Password Policy';
$string['sum_error_extended_char'] = 'Error: Please enable Allow Extended Characters in username';
$string['sum_error_service_link'] = 'Error: Please update Service and Token';
$string['sum_error_token_link'] = 'Error: Please update Token ';

$string['here'] = ' here ';

$string['summary_setting_section'] = 'General Settings Summary';
$string['summary_connection_section'] = 'Connection Settings Summary';
$string['edwiser_bridge_plugin_summary'] = 'Edwiser Bridge Plugin Summary';

$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';

$string['mdl_url'] = 'Moodle URL';
$string['wp_test_connection_failed'] = 'or Wordpress permalink is not postname. Also, check if you have any firewall or security plugin, If yes  Whitelist Moodle URL and IP. If this does not fix then connect with your Hosting providers.';
/**************/

/*****************************  ADDED FOR SETTINGS PAGE   *****************************/
$string["manual_notification"] = "MANUAL NOTIFICATION";


/*********  Form error Handling.    *******/
$string['service_name_empty'] = 'Please enter web service name';
$string['user_empty'] = 'Please select User';
$string['token_empty'] = 'Please select Token';
// $string['service_name_empty'] = 'Please enter web service name';

$string['web_service_creation_status'] = 'Web service creation status';
$string['web_service_creation_msg'] = 'Web service creation message';


/*
 * GDPR compatibility strings.
 */

$string['privacy:metadata:wp_site'] = 'In order to integrate with a WordPress site, user data needs to be exchanged with WordPress. Which will perform actions like user creation, user deletion, user metedata update, user enrollment synchronization and user un-enrollment synchronization on WordPress site.';
$string['privacy:metadata:wp_site:userid'] = 'The userid is sent from Moodle to perform any of the actions mentioned in above site description on WordPress site.';
$string['privacy:metadata:wp_site:email'] = 'Your email is sent to the WordPress site to perform any of the actions mentioned in above site description on WordPress site';
$string['privacy:metadata:wp_site:username'] = 'The username is sent from Moodle to perform any of the actions mentioned in above site description on WordPress site';
$string['privacy:metadata:wp_site:firstname'] = 'The firstname is sent from Moodle to perform any of the actions mentioned in above site description on WordPress site';
$string['privacy:metadata:wp_site:lastname'] = 'The lastname is sent from Moodle to perform any of the actions mentioned in above site description on WordPress site';
$string['privacy:metadata:wp_site:password'] = 'The password is sent from Moodle to perform any of the actions mentioned in above site description on WordPress site';

/**
 * Plugin stats.
 */
$string['mdl_edwiser_bridge_lbl'] ='Edwiser Bridge Moodle:';
$string['mdl_edwiser_bridge_bp_lbl'] ='Edwiser Bridge Bulk Purchase Moodle:';
$string['mdl_edwiser_bridge_sso_lbl'] ='Edwiser Bridge Single Sign On Moodle:';
$string['mdl_edwiser_bridge_txt_latest'] = 'Latest';
$string['mdl_edwiser_bridge_txt_download'] = 'Download';
$string['mdl_edwiser_bridge_txt_download_help'] = 'Click here to downaload the plugin file.';
$string['mdl_edwiser_bridge_txt_not_avbl'] = 'Not Available';
$string['mdl_edwiser_bridge_fetch_info'] = 'Check for update';

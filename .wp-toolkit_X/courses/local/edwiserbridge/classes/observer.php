<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/edwiserbridge/lib.php');
require_once($CFG->dirroot.'/user/lib.php');


class local_edwiserbridge_observer {

    /**
     * functionality to handle user enrollment event.
     */
    public static function user_enrolment_created(core\event\user_enrolment_created $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));

        $request_data = array(
            'action'     => 'course_enrollment',
            'user_id'    => $event->relateduserid,
            'course_id'  => $event->courseid,
            'user_name'  => $user_data[$event->relateduserid]->username,
            'first_name' => $user_data[$event->relateduserid]->firstname,
            'last_name'  => $user_data[$event->relateduserid]->lastname,
            'email'      => $user_data[$event->relateduserid]->email
        );


        if (check_if_request_is_from_wp()) {
            return;
        }

        $api_handler = api_handler_instance();
        // $api_handler = new api_handler();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);
            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value['wp_name']]['course_enrollment'] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $request_data['secret_key'] = $value['wp_token'];

                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }


    /**
     * functionality to handle user un enrollment event
     */
    public static function user_enrolment_deleted(core\event\user_enrolment_deleted $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));
        $request_data = array(
            'action'     => 'course_un_enrollment',
            'user_id'    => $event->relateduserid,
            'course_id'  => $event->courseid,
            'user_name'  => $user_data[$event->relateduserid]->username,
            'first_name' => $user_data[$event->relateduserid]->firstname,
            'last_name'  => $user_data[$event->relateduserid]->lastname,
            'email'      => $user_data[$event->relateduserid]->email
        );

        //checks if the request is from the wordpress site or from te Moodle site itself
        if (check_if_request_is_from_wp()) {
            return;
        }

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {

                if ($synch_conditions[$value['wp_name']]['course_un_enrollment'] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $request_data['secret_key'] = $value['wp_token'];
                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }


    /**
     * functionality to handle user creation event
     */
    public static function user_created(core\event\user_created $event)
    {

        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));

        // User password should be encrypted. Using Openssl for it.
        // We will use token as the key as it is present on both sites.
        // Open SSL encryption initialization.
        $enc_method = 'AES-128-CTR';


        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value["wp_name"]]["user_creation"] && $value['wp_token']) {
                    $password = '';
                    $enc_iv   = '';
                    // If new password in not empty
                    if (isset($_POST['newpassword']) && $_POST['newpassword']) {
                        $enc_key   = openssl_digest($value["wp_token"], 'SHA256', true);
                        $enc_iv = substr(hash('sha256', $value["wp_token"]), 0, 16);
                        // $crypttext = openssl_encrypt($_POST['newpassword'], $enc_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
                        $password = openssl_encrypt($_POST['newpassword'], $enc_method, $enc_key, 0, $enc_iv);
                    }

                    $request_data = array(
                        'action' => 'user_creation',
                        'user_id'     => $event->relateduserid,
                        'user_name'   => $user_data[$event->relateduserid]->username,
                        'first_name'  => $user_data[$event->relateduserid]->firstname,
                        'last_name'   => $user_data[$event->relateduserid]->lastname,
                        'email'       => $user_data[$event->relateduserid]->email,
                        'password'    => $password,
                        'enc_iv'      => $enc_iv,
                        'secret_key' => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                    );

                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }

    }

    /**
     * functionality to handle user update event
     */
    public static function user_updated(core\event\user_updated $event)
    {
        global $CFG;
        $user_data = user_get_users_by_id(array($event->relateduserid));
       
        // User password should be encrypted. Using Openssl for it.
        // We will use token as the key as it is present on both sites.
        // Open SSL encryption initialization.
        $enc_method = 'AES-128-CTR';

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if (isset($synch_conditions[$value["wp_name"]]["user_updation"]) && $synch_conditions[$value["wp_name"]]["user_updation"] && $value['wp_token']) {
                    $password = '';
                    $enc_iv   = '';

                    // If new password in not empty
                    if (isset($_POST['newpassword']) && $_POST['newpassword']) {

                        $enc_key   = openssl_digest($value["wp_token"], 'SHA256', true);
                        $enc_iv = substr(hash('sha256', $value["wp_token"]), 0, 16);

                        // $crypttext = openssl_encrypt($_POST['newpassword'], $enc_method, $enc_key, 0, $enc_iv) . "::" . bin2hex($enc_iv);
                        $password = openssl_encrypt($_POST['newpassword'], $enc_method, $enc_key, 0, $enc_iv);

                    }


                    $request_data = array(
                        'action'     => 'user_updated',
                        'user_id'    => $event->relateduserid,
                        // "user_name"   => $user_data[$event->relateduserid]->username,
                        'first_name' => $user_data[$event->relateduserid]->firstname,
                        'last_name'  => $user_data[$event->relateduserid]->lastname,
                        'email'      => $user_data[$event->relateduserid]->email,
                        'country'    => $user_data[$event->relateduserid]->country,
                        'city'       => $user_data[$event->relateduserid]->city,
                        'phone'      => $user_data[$event->relateduserid]->phone1,
                        'password'   => $password,
                        'enc_iv'     => $enc_iv,
                        'secret_key' => $value['wp_token'], // Adding Token for verification in WP from Moodle.
                    );

                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }

    /**
     * functionality to handle user deletion event
     */
    public static function user_deleted(core\event\user_deleted $event)
    {
        global $CFG;
        $request_data = array(
            'action'  => 'user_deletion',
            'user_id' => $event->relateduserid
        );

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if ($synch_conditions[$value["wp_name"]]["user_deletion"] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $request_data['secret_key'] = $value['wp_token'];

                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }


    /**
     * Functionality to handle Course deletion event. 
     */
    public static function course_deleted(core\event\course_deleted $event)
    {
        global $CFG;

        $request_data = array(
            'action'    => 'course_deleted',
            'course_id' => $event->objectid,
        );

        $api_handler = api_handler_instance();
        if (isset($CFG->eb_connection_settings)) {
            $sites = unserialize($CFG->eb_connection_settings);
            $synch_conditions = unserialize($CFG->eb_synch_settings);

            foreach ($sites as $key => $value) {
                if (isset($synch_conditions[$value["wp_name"]]["course_deletion"]) && $synch_conditions[$value["wp_name"]]["course_deletion"] && $value['wp_token']) {
                    // Adding Token for verification in WP from Moodle.
                    $request_data['secret_key'] = $value['wp_token'];

                    $api_handler->connect_to_wp_with_args($value["wp_url"], $request_data);
                }
            }
        }
    }
}

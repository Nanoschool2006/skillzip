<?php

/**
 * External Web Service Template
 *
 * @package    local
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/user/externallib.php');
require_once($CFG->dirroot . '/cohort/externallib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot. '/user/lib.php');
require_once($CFG->dirroot. '/cohort/lib.php');


/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL | E_STRICT);
*/
class local_wdmgroupregistration_external extends external_api
{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_manage_cohort_enrollment_parameters()
    {
        return new external_function_parameters(
            array(
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseId' => new external_value(PARAM_INT, 'Course Id in which cohort wil be enrolled.', VALUE_REQUIRED),
                            'cohortId' => new external_value(PARAM_INT, 'Cohort Id which will be enrolled in the course.', VALUE_REQUIRED)
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_manage_cohort_enrollment($cohort)
    {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED

        $params = self::validate_parameters(
            self::eb_manage_cohort_enrollment_parameters(),
            array('cohort' => $cohort)
        );


        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }


        foreach ($params['cohort'] as $cohortDetails) {
            $cohortDetails = (object)$cohortDetails;
            if (isset($cohortDetails->cohortId) && !empty($cohortDetails->cohortId) && isset($cohortDetails->courseId) && !empty($cohortDetails->courseId)) {
                $courseid = $cohortDetails->courseId;
                $cohortid = $cohortDetails->cohortId;
                if (!enrol_is_enabled('cohort')) {
                    // Not enabled.
                    return "disabled";
                }
                $enrol = enrol_get_plugin('cohort');

                $course = $DB->get_record('course', array('id' => $courseid));

                $instance = array();
                $instance['name'] = '';
                $instance['status'] = ENROL_INSTANCE_ENABLED; // Enable it.
                $instance['customint1'] = $cohortid; // Used to store the cohort id.
                $instance['roleid'] = 5; // Default role for cohort enrol which is usually student.
                $instance['customint2'] = 0; // Optional group id.
                $instanceId = $enrol->add_instance($course, $instance);

                // Sync the existing cohort members.
                $trace = new null_progress_trace();
                enrol_cohort_sync($trace, $course->id);
                $trace->finished();
            }
        }
        return $instanceId;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_manage_cohort_enrollment_returns()
    {
        return new external_value(PARAM_INT, 'Id of the instance');
    }






    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_delete_cohort_parameters()
    {
        return new external_function_parameters(
            array(
                'cohort' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'cohortId' => new external_value(PARAM_INT, 'Cohort Id which will be deleted in Moodle', VALUE_REQUIRED)
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_delete_cohort($cohort)
    {
        global $USER, $DB;

        //Parameter validation
        //REQUIRED

        $params = self::validate_parameters(
            self::eb_delete_cohort_parameters(),
            array('cohort' => $cohort)
        );


        //Context validation
        //OPTIONAL but in most web service it should present
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        //Capability checking
        //OPTIONAL but in most web service it should present
        if (!has_capability('moodle/user:viewdetails', $context)) {
            throw new moodle_exception('cannotviewprofile');
        }

        $response = array(
            "status" => 1
        );

        foreach ($params["cohort"] as $cohortDetails) {
            try {
                $cohort = $DB->get_record('cohort', array('id' => $cohortDetails["cohortId"]), '*', MUST_EXIST);
                if (isset($cohort->id)) {
                    $context = context::instance_by_id($cohort->contextid, MUST_EXIST);
                    cohort_delete_cohort($cohort);
                } else {
                    throw new Exception('Error');
                }
            } catch (Exception $e) {
                $response['status'] = 0;
            }
        }
        return $response;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_delete_cohort_returns()
    {
        return new external_single_structure(
            array(
                'status'  => new external_value(PARAM_TEXT, 'This will return 1 if successful connection and 0 on failure')
            )
        );
    }






        /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function eb_manage_user_cohort_enrollment_parameters()
    {
        return new external_function_parameters(
            array(
                'cohort_id' => new external_value(PARAM_INT, get_string('api_cohort_id', 'local_wdmgroupregistration'), VALUE_REQUIRED),
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'firstname' => new external_value(PARAM_TEXT, get_string('api_firstname', 'local_wdmgroupregistration'), VALUE_REQUIRED),
                            'lastname' => new external_value(PARAM_TEXT, get_string('api_lastname', 'local_wdmgroupregistration'), VALUE_REQUIRED),
                            'password' => new external_value(PARAM_TEXT, get_string('api_password', 'local_wdmgroupregistration'), VALUE_REQUIRED),
                            'username' => new external_value(PARAM_TEXT, get_string('api_username', 'local_wdmgroupregistration'), VALUE_REQUIRED),
                            'email' => new external_value(PARAM_TEXT, get_string('api_email', 'local_wdmgroupregistration'), VALUE_REQUIRED)
                        )
                    )
                )
            )
        );
    }

    /**
     * Function responsible for enrolling cohort in course
     * @return string welcome message
     */
    public static function eb_manage_user_cohort_enrollment($cohort_id, $users)
    {
        global $USER, $DB, $CFG;
        $error          = 0;
        $error_msg      = '';
        $users_response = array();
        //Parameter validation
        //REQUIRED

// $serialize = serialize($users);

        // throw new invalid_parameter_exception('category not exists: category '. ''/*$serialize */." ::: ");


        $params = self::validate_parameters(
            self::eb_manage_user_cohort_enrollment_parameters(),
            array('cohort_id' => $cohort_id, 'users' => $users)
        );


        // throw new invalid_parameter_exception('category not exists: category '. ''/*$serialize */." ::: ");


        // Check 
        if (!$DB->record_exists('cohort', array('id' => $params['cohort_id']))) {
            $error      = 1;
            $error_msg  = 'Cohort_does_not_exist';

        } else {

            foreach ($params['users'] as $user) {
                // Create user if the new user
                
                $enrolled       = 0;
                // $creation_error = 0;
                $existing_user = $DB->get_record('user', array('email' => $user['email']), '*');


                // check if email exists if yes then dont create new user
                // if ($DB->record_exists('user', array('email' => $user['email']))) {
                if (isset($existing_user->id)) {

                    $user_id = $existing_user->id;

                } else {
                    // create new user
                    // check if the user name is available for new user.

                    $o_user_name = $user['username'];
                    $append = 1;

                    while ($DB->record_exists('user', array('username' => $user['username']))) {
                        $user['username'] = $o_user_name.$append;
                        ++$append;
                    }

                    $user['confirmed']  = 1;
                    $user['mnethostid'] = $CFG->mnet_localhost_id;
                    $user_id = user_create_user($user, 1, false);
                    // $user_id = user_create_user($user['user_name'], $updatepassword, false);

                    if (!$user_id) {

                        array_push(
                            $users_response,
                            array(
                                'user_id'        => 0,
                                'email'          => $user['email'],
                                'enrolled'       => 0,
                                'cohort_id'      => $params['cohort_id'],
                                'creation_error' => 1
                            )
                        );


                        // Unable to create user.
                        continue;
                    }

                }

                $cohort = array(
                    'cohorttype' => array('type' => 'id', 'value' => $params['cohort_id']),
                    'usertype' => array('type' => 'id', 'value' => $user_id)
                );


                $flag = 'aaa';

                // Add User to cohort.
                // $add_cohort_members_response = core_cohort_external::add_cohort_members(array($cohort));
                if (!$DB->record_exists('cohort_members', array('cohortid' => $params['cohort_id'], 'userid' => $user_id))) {
                $flag = 'bbbb';

                    cohort_add_member($params['cohort_id'], $user_id);
                    $enrolled = 1;
                }


                array_push(
                    $users_response,
                    array(
                        'user_id'        => $user_id,
                        'username'       => $user['username'],
                        'password'       => $user['password'],
                        'email'          => $user['email'],
                        'enrolled'       => $enrolled,
                        'cohort_id'      => $params['cohort_id'],
                        'creation_error' => 0
                    )
                );




                // update user role 
                /*core_role_external::assign_roles(
                    array(
                        array(
                            'roleid'    => 5,
                            'userid'    => $user_id,
                            'contextid' => 1
                        )
                    )
                );*/


            }  
        }



        return array(
            'error'     => $error,
            'error_msg' => $error_msg,
            'users'     => $users_response
        );
    }




    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function eb_manage_user_cohort_enrollment_returns()
    {

        return new external_function_parameters(
            array(
                'error'     => new external_value(PARAM_INT, get_string('api_error', 'local_wdmgroupregistration')),
                'error_msg' => new external_value(PARAM_TEXT, get_string('api_error_msg', 'local_wdmgroupregistration')),
                // 'total_users' => new external_value(PARAM_INT, ''),
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'user_id'        => new external_value(PARAM_INT, get_string('api_user_id', 'local_wdmgroupregistration')),
                            'username'        => new external_value(PARAM_TEXT, get_string('api_username', 'local_wdmgroupregistration')),
                            'password'        => new external_value(PARAM_TEXT, get_string('api_password', 'local_wdmgroupregistration')),
                            'email'          => new external_value(PARAM_TEXT, get_string('api_email', 'local_wdmgroupregistration')),
                            'enrolled'       => new external_value(PARAM_INT, get_string('api_enrolled', 'local_wdmgroupregistration')),
                            'cohort_id'      => new external_value(PARAM_INT, get_string('api_cohort_id', 'local_wdmgroupregistration')),
                            // 'existing_user' => new external_value(PARAM_INT, get_string('web_service_email', 'local_wdmgroupregistration')),
                            'creation_error' => new external_value(PARAM_INT, get_string('api_creation_error', 'local_wdmgroupregistration'))
                        )
                    )
                )
            )
        );

    }

}

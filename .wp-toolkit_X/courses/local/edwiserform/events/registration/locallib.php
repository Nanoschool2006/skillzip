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
 * Registration form functionality
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/edwiserform/events/events.php');

/**
 * Registraion event definition
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edwiserform_events_registration extends edwiserform_events_plugin {

    /**
     * Return does event require logged in user
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function login_allowed() {
        return false;
    }

    /**
     * Check if user profile's required parameter is missing from reqeust
     * @param  stdClass $user User object
     * @return array          Errors
     * @since  Edwiser Form 1.0.0
     */
    public function user_required_parameter_empty($user) {
        $errors = [];
        $required = $this->get_form_elements();
        foreach ($required as $param => $required) {
            if ($required == true && $user->$param == '') {
                $errors[$param] = get_string("efb-field-$param-warning", "edwiserformevents_registration");
            }
        }
        return $errors;
    }

    /**
     * Validate user data
     * @param  stdClass $user Object to validate
     * @return array          Errors
     * @since  Edwiser Form 1.0.0
     */
    public function user_validation($user) {
        global $DB;
        $errors = $this->user_required_parameter_empty($user);
        if (!isset($errors["username"]) && $user->username != '') {
            $userexist = $DB->count_records("user", array("username" => $user->username));
            if ($userexist) {
                $errors["username"] = get_string("efb-field-username-duplicate-warning", "edwiserformevents_registration");
            }
        }
        if (!get_config("core", "allowaccountssameemail")) {
            $sql = "SELECT * FROM {user} WHERE email = ? and deleted = 0";
            $userexist = $DB->get_records_sql($sql, array($user->email));
            if ($userexist) {
                $errors["email"] = get_string("efb-field-email-duplicate-warning", "edwiserformevents_registration");
            }
        }
        if (property_exists($user, "email2") && $user->email != $user->email2) {
            $errors["email2"] = get_string("efb-field-email2-warning", "edwiserformevents_registration");
        }
        $passworderror = "";
        if (!check_password_policy($user->password, $passworderror)) {
            $passworderror = str_replace("</div><div>", " ", $passworderror);
            $passworderror = str_replace("<div>", "", $passworderror);
            $passworderror = str_replace("</div>", "", $passworderror);
        }
        if ($passworderror != "") {
            $errors["password"] = $passworderror;
        }
        if (property_exists($user, "password2") && $user->password != $user->password2) {
            $errors["password2"] = get_string("efb-field-password2-warning", "edwiserformevents_registration");
        }
        return count($errors) ? $errors : [];
    }

    /**
     * Map user field name with value
     * @param  stdClass $data
     * @return stdClass user
     * @since  Edwiser Form 1.0.0
     */
    private function map_user_fields($data) {
        global $DB;
        $user = new stdClass;
        foreach ($data as $field) {
            $name = $field['name'];
            $user->$name = $field['value'];
        }
        return $user;
    }

    /**
     * Validate user submitted form data
     * @param  stdClass $form
     * @param  stdClass $data
     * @return array errors
     * @since  Edwiser Form 1.0.0
     */
    public function validate_form_data($form, $data) {
        $data = json_decode($data, true);
        $user = $this->map_user_fields($data);
        return $this->user_validation($user);
    }

    /**
     * Find custom fields in user object and enclose in custom field and save
     * @param object $user Submite user object
     * @since Edwiser Form 1.0.0
     */
    private function save_custom_fields($user) {
        global $CFG;
        $fields = array(
            'id',
            'username',
            'password',
            'password2',
            'createpassword',
            'firstname',
            'lastname',
            'auth',
            'idnumber',
            'lang',
            'calendartype',
            'theme',
            'timezone',
            'email',
            'email2',
            'mailformat',
            'description',
            'city',
            'country',
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename'
        );
        foreach ((array)$user as $field => $value) {
            if (!in_array($field, $fields)) {
                $field = "profile_field_".$field;
                $user->$field = $value;
            }
        }
        require_once($CFG->dirroot . '/user/profile/lib.php');
        profile_save_data($user);
    }

    /**
     * Execute event action after form submission
     * @param  object $form Form object
     * @param  object $data Data submitted by user
     * @return array       Event action response
     * @since  Edwiser Form 1.0.0
     */
    public function event_action($form, $data) {
        global $CFG, $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $data = json_decode($data, true);
        $user = $this->map_user_fields($data);
        $response = [
            "status" => false,
            "noemail" => get_config('edwiserformevents_registration', 'disable_confirmation_' . $form->id),
            "msg" => "",
            "errors" => "{}"
        ];
        require_once($CFG->dirroot . '/user/lib.php');
        try {
            $errors = $this->user_validation($user);
            if (!empty($errors)) {
                $response["errors"] = json_encode($errors);
                $response["msg"] = get_string('efb-registration-failed', 'edwiserformevents_registration');
            } else {
                if (!property_exists($user, "password")) {
                    $user->password = generate_password();
                }
                $user->auth = $CFG->registerauth;
                if ($user->auth == '') {
                    $user->auth = 'email';
                }
                $user->mnethostid = $CFG->mnet_localhost_id;
                $user->password = md5($user->password);
                $user->secret = random_string(15);
                $userid = user_create_user($user, false, false);
                if ($userid) {
                    $user->id = $userid;
                    $this->save_custom_fields($user);
                    core\session\manager::login_user(core_user::get_user($userid));
                    send_confirmation_email($user);
                    $response["status"] = true;
                    $response["msg"] = get_string('efb-registration-success', 'edwiserformevents_registration');
                } else {
                    $response["msg"] = get_string('efb-registration-failed', 'edwiserformevents_registration');
                }
            }
        } catch (exception $ex) {
            $response['msg'] = $ex->getMessage();
            return $response;
        }
        return $response;
    }

    /**
     * Revert executed event action
     * @param  object  $form Form object
     * @param  object  $data Data submitted by user
     * @return string        Event reverting response
     * @since  Edwiser Form 1.4.1
     */
    public function revert_event_action($form, $data) {
        global $CFG, $DB, $PAGE;
        $PAGE->set_context(context_system::instance());
        $data = json_decode($data, true);
        $user = $this->map_user_fields($data);
        $errors = $this->user_validation($user);
        if (!empty($errors)) {
            return true;
        }
        $user = $DB->get_record('user', array('username' => $user->username, 'email' => $user->email));
        if ($user) {
            $DB->delete_records('user', array('id' => $user->id, 'username' => $user->username, 'email' => $user->email));
        }
        return true;
    }

    /**
     * Execute action when all events are processed
     * @param  object  $form Form object
     * @param  object  $data Data submitted by user
     * @return boolean       Action response
     */
    public function event_post_action($form, $data) {
        global $USER;
        if ($USER->id != 0) {
            core\session\manager::kill_user_sessions($USER->id);
        }
        return true;
    }

    /**
     * Get form elements in array. If value is true then that field is mandatory
     * @return array user fields
     * @since  Edwiser Form 1.0.0
     */
    private function get_form_elements() {
        return array(
            'username' => true,
            'firstname' => true,
            'lastname' => true,
            'gender' => false,
            'email' => true,
            'email2' => false,
            'password' => false,
            'password2' => false,
            'phone' => false,
            'country' => false,
            'address' => false
        );
    }

    /**
     * Get default settings for user fields
     * @return array user fields
     * @since  Edwiser Form 1.0.0
     */
    private function get_default_settings() {
        return array('username' => '',
            'firstname' => '',
            'lastname' => '',
            'gender' => '',
            'email' => '',
            'email2' => '',
            'password' => '',
            'phone' => '',
            'country' => '',
            'address' => ''
        );
    }

    /**
     * Return true if event support form data update
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function support_form_data_update() {
        return false;
    }

    /**
     * Return true if plugin has event on form submission
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function has_event() {
        return true;
    }

    /**
     * Verify form fields
     * @param  string $formdef Definition of form
     * @return array           [status, error message]
     * @since  Edwiser Form 1.0.0
     */
    public function verify_form_fields($formdef) {
        global $DB;
        $formdef = json_decode($formdef, true);
        $fields = $formdef['fields'];
        $template = $DB->get_field('efb_form_templates', 'definition', array('name' => 'registration'));
        $template = json_decode($template, true);
        $templatefields = $template['fields'];
        $errors = [];
        foreach ($templatefields as $templatefield) {
            if (isset($templatefield['attrs']) &&
                isset($templatefield['attrs']['name']) &&
                isset($templatefield['attrs']['required']) &&
                $templatefield['attrs']['required']
            ) {
                $present = false;
                foreach ($fields as $field) {
                    if (isset($field['attrs']['name']) &&
                        isset($templatefield['attrs']['name']) &&
                        $templatefield['attrs']['name'] == $field['attrs']['name']
                    ) {
                        $present = true;
                    }
                }
                if (!$present) {
                    $errors[] = $templatefield['config']['label'];
                }
            }
        }
        if (count($errors) > 0) {
            return get_string(
                'efb-event-fields-missing',
                'local_edwiserform',
                array(
                    'event' => get_string('efb-event-registration-name', 'edwiserformevents_registration'),
                    'fields' => implode(', ', $errors)
                )
            );
        }
        return '';
    }

    /**
     * Return index of event to sort before executing
     * @return integer
     * @since  Edwiser Form 1.0.0
     */
    public function get_index() {
        return 1;
    }

    /**
     * Returns does plugin needs activation before using this
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function is_pro_plugin() {
        return true;
    }

    /**
     * Returns does teacher can create this type of form
     * @return bool
     * @since  Edwiser Form 1.2
     */
    public function teacher_allowed() {
        return false;
    }

    /**
     * Does plugin support actions on form data list view
     * @return boolean
     * @since  Edwiser Form 1.3.2
     */
    public function support_form_data_list_actions() {
        return true;
    }

    /**
     * Get html strings of action supported registration plugin
     * @param  boolean $status Current status of registration
     * @return string          Html action string
     * @since  Edwiser Form 1.3.2
     */
    private function get_registration_actions($status) {
        $actions = '';
        $actions .= html_writer::tag(
            'a',
            get_string('confirm', 'edwiserformevents_registration'),
            array(
                'href' => '#',
                'class' => 'registration-action efb-data-action' . (USER_UNCONFIRMED === $status ? ' show' : ''),
                'data-action' => USER_UNCONFIRMED
            )
        );
        $actions .= html_writer::tag(
            'a',
            get_string('unsuspenduser', 'edwiserformevents_registration'),
            array(
                'href' => '#',
                'class' => 'registration-action efb-data-action' . (USER_SUSPENDED === $status ? ' show' : ''),
                'data-action' => USER_SUSPENDED
            )
        );
        $actions .= html_writer::tag(
            'a',
            get_string('suspenduser', 'edwiserformevents_registration'),
            array(
                'href' => '#',
                'class' => 'registration-action efb-data-action' . (USER_UNSUSPENDED === $status ? ' show' : ''),
                'data-action' => USER_UNSUSPENDED
            )
        );
        return $actions;
    }

    /**
     * Get user action should be visible in form data list
     * @param  Mixed   $userorid User id or user object
     * @return Integer           [
     *                             Not confirmed => 1,
     *                             Suspended     => 2,
     *                             Not suspended => 3
     *                           ]
     */
    public function get_possible_user_action($userorid) {
        if (!is_object($userorid)) {
            $user = core_user::get_user($userorid);
        } else {
            $user = $userorid;
        }
        if ($user->confirmed == 0) {
            return USER_UNCONFIRMED;
        }
        if ($user->suspended == 0) {
            return USER_UNSUSPENDED;
        }
        return USER_SUSPENDED;
    }

    /**
     * Return actions supported by the event
     * @param  stdClass $submission Form submission
     * @return string               html string actions
     * @since  Edwiser Form 1.3.2
     */
    public function form_data_list_actions($submission) {
        $status = $this->get_possible_user_action($submission->userid);
        return $this->get_registration_actions($status);
    }

    /**
     * Return apply action
     * @param  integer $userid User id
     * @param  string  $action Actions to be performed on user
     * @return array           Response
     * @since  Edwiser Form 1.3.2
     */
    public function process_registration_action($userid, $action) {
        global $DB, $PAGE;
        $user = core_user::get_user($userid);
        if (!is_object($user)) {
            if ($this->userdeletion) {
                return [
                    'status' => true,
                    'msg' => '',
                    'type' => 0
                ];
            }
            throw new moodle_exception('usernotupdatednotexists');
        }
        $response = [
            'status' => false,
            'msg' => '',
            'type' => 0
        ];
        $context = context_system::instance();
        $PAGE->set_context($context);

        switch($action) {
            case USER_UNCONFIRMED:
                $user->confirmed = true;
                $DB->update_record('user', $user);
                break;
            case USER_SUSPENDED:
                $user->suspended = false;
                $DB->update_record('user', $user);
                break;
            case USER_UNSUSPENDED:
                $user->suspended = true;
                $DB->update_record('user', $user);
                break;
            default:
                $response['msg'] = get_string('unsupportedaction', 'edwiserformevents_registration');
                return $response;
        }
        $response = [
            'status' => true,
            'msg' => get_string('action' . $action . 'success', 'edwiserformevents_registration'),
            'type' => $this->get_possible_user_action($user)
        ];
        return $response;
    }

    /**
     * Action to be performed by event on submission deletion
     * @param  Object $form Form object
     * @param  Object $data Submission data
     * @return String       Deletion operation status
     * @since  Edwiser Form 1.3.2
     */
    public function submission_deletion_action($form, $data) {
        $this->userdeletion = true;
        return $this->process_registration_action($data->userid, USER_UNSUSPENDED)['msg'];
    }

    /**
     * Return url to show in address bar
     * @param  Object $form Form object
     * @return String       Address bar
     */
    public function visible_url($form) {
        global $CFG;
        return $CFG->wwwroot . '/login/signup.php';
    }

    /**
     * Does event have some settings for the form
     * @return boolean
     * @since  Edwiser Form 1.5.3
     */
    public function has_settings() {
        return true;
    }

    /**
     * Get form settings supported by enrolment event
     * @param integer $formid id of form
     * @since Edwiser Form 1.5.3
     */
    public function get_settings($formid = false) {
        global $PAGE;
        $PAGE->set_context(context_system::instance());
        $form = new MoodleQuickForm('', '', '');
        $disabled = get_config('edwiserformevents_registration', 'disable_confirmation_' . $formid);
        $form->addElement(
            "checkbox",
            "registration-disable-confirmation",
            get_string("registration-disable-confirmation", "edwiserformevents_registration"),
            get_string("registration-disable-confirmation_desc", "edwiserformevents_registration"),
            false
        )->setValue($disabled);
        $form->closeHeaderBefore('registration-settings');
        $form->display();
    }

    /**
     * Creating new form
     * @param  integer $formid  id of form
     * @param  bool    $setting Event setting
     * @return intger           new form id
     * @since  Edwiser Form 1.5.3
     */
    public function create_new_form($formid, $setting) {
        if ($setting == true) {
            set_config('disable_confirmation_' . $formid, $setting, 'edwiserformevents_registration');
        } else {
            unset_config('disable_confirmation_' . $formid, 'edwiserformevents_registration');
        }
        return true;
    }

    /**
     * Update form settings
     * @param  integer $formid id of form
     * @param  bool  $setting  Event setting
     * @return integer|boolean status of form updation
     * @return Edwiser Form 1.5.3
     */
    public function update_form($formid, $setting) {
        return $this->create_new_form($formid, $setting);
    }
}

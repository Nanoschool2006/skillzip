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
 * Login event functionality
 * @package   edwiserformevents_login
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/edwiserform/events/events.php');
require_once($CFG->dirroot . '/login/lib.php');

/**
 * Login event definition
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edwiserform_events_login extends edwiserform_events_plugin {

    /**
     * Perform action while enabling and disabling form
     * @param  stdClass $form     Form object
     * @param  string   $action   enable|disable
     * @param  array    $response Previous response
     * @return array              Processed response
     * @since  Edwiser Form 1.0.0
     */
    public function enable_disable_form($form, $action, $response) {
        global $CFG;
        $loginurl = $CFG->wwwroot . "/local/edwiserform/form.php?id=".$form->id;
        if ($action == 'enable') {
            set_config('alternateloginurl', $loginurl);
            return [
                'status' => true,
                'msg' => get_string("$action-success", "edwiserformevents_login")
            ];
        }
        $current = get_config("core", "alternateloginurl");
        if ($loginurl == $current) {
            set_config('alternateloginurl', "");
        }
        return [
            'status' => true,
            'msg' => get_string("$action-success", "edwiserformevents_login")
        ];
    }

    /**
     * Return does event require logged in user
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function login_allowed() {
        return false;
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
     * Map user field name with value
     * @param  stdClass $submission Submission data
     * @return stdClass             User object
     * @since Edwiser Form 1.0.0
     */
    private function map_user_fields($submission) {
        $submission = json_decode($submission);
        $data = new stdClass;
        foreach ($submission as $input) {
            $name = $input->name;
            $data->$name = $input->value;
        }
        return $data;
    }

    /**
     * Execute event action after form submission
     * @param  object $form   Form object
     * @param  object $data   Data submitted by user
     * @return boolean|string enrol|unenrolment status
     * @since  Edwiser Form 1.0.0
     */
    public function event_action($form, $data) {
        global $DB, $CFG, $SESSION, $OUTPUT, $PAGE, $USER;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $response = array(
            'status' => false,
            'msg' => '',
            'errors' => "{}"
        );
        $errors = [];
        $data = $this->map_user_fields($data);
        $username = isset($data->username) ? $data->username : '';
        $password = isset($data->password) ? $data->password : '';
        $_POST['username'] = $username;
        $_POST['password'] = $password;
        $frm  = false;
        $user = false;

        $authsequence = get_enabled_auth_plugins(true); // Auths, in sequence.
        foreach ($authsequence as $authname) {
            $authplugin = get_auth_plugin($authname);
            // The auth plugin's loginpage_hook() can eventually set $frm and/or $user.
            $authplugin->loginpage_hook();
        }
        if ($user == false || $frm == false) {
            $user = authenticate_user_login($username, $password);
        }

        if (is_enabled_auth('none') ) {
            if ($frm->username !== core_user::clean_field($frm->username, 'username')) {
                $response['msg'] = get_string('username').': '.get_string("invalidusername");
                return $response;
            }
        }

        if ((isset($frm->username) && $frm->username == 'guest') and empty($CFG->guestloginbutton)) {
            $user = false;    // Can't log in as guest if guest button is disabled.
            $frm = false;
        } else if (!$user) {
            $user = authenticate_user_login($username, $password, false);
        }

        // Intercept 'restored' users to provide them with info & reset password.
        if (!$user and $frm and is_restored_user($frm->username)) {
            $response['msg'] = $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
            require_once('restored_password_form.php'); // Use our "supplanter" login_forgot_password_form. MDL-20846.
            $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
            $response['msg'] .= $form->display();
            return $response;
        }
        if ($user) {
            // Language setup.
            if (isguestuser($user)) {
                // No predefined language for guests - use existing session or default site lang.
                unset($user->lang);

            } else if (!empty($user->lang)) {
                // Unset previous session language - use user preference instead.
                unset($SESSION->lang);
            }

            if (empty($user->confirmed)) {       // This account was never confirmed.
                $response['msg'] = $OUTPUT->box(get_string("emailconfirmsent", "", $user->email), "generalbox boxaligncenter");
                $resendconfirmurl = new moodle_url('/login/index.php',
                    [
                        'username' => $username,
                        'password' => $password,
                        'resendconfirmemail' => true
                    ]
                );
                $response['msg'] .= $OUTPUT->single_button($resendconfirmurl, get_string('emailconfirmationresend'));
                core\session\manager::kill_user_sessions($user->id);
                return $response;
            }

            \core\session\manager::login_user($user);

            // Reload preferences from DB.
            unset($USER->preference);
            check_user_preferences_loaded($USER);

            // Update login times.
            update_user_login_times();

            // Extra session prefs init.
            set_login_session_preferences();

            // Trigger login event.
            $event = \core\event\user_loggedin::create(
                array(
                    'userid' => $USER->id,
                    'objectid' => $USER->id,
                    'other' => array('username' => $USER->username),
                )
            );
            $event->trigger();

            // Queue migrating the messaging data, if we need to.
            if (!get_user_preferences('core_message_migrate_data', false, $USER->id)) {
                // Check if there are any legacy messages to migrate.
                if (\core_message\helper::legacy_messages_exist($USER->id)) {
                    \core_message\task\migrate_message_data::queue_task($USER->id);
                } else {
                    set_user_preference('core_message_migrate_data', true, $USER->id);
                }
            }

            if (isguestuser()) {
                // No need to continue when user is THE guest.
                $response['status'] = true;
                $response['data'] = json_encode([
                    'action' => 'redirect',
                    'url'    => $CFG->wwwroot
                ]);
                return $response;
            }

            // Select password change url.
            $userauth = get_auth_plugin($USER->auth);

            \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

            if (empty($CFG->rememberusername) or ($CFG->rememberusername == 2 and empty($frm->rememberusername))) {
                // No permanent cookies, delete old one if exists.
                set_moodle_cookie('');

            } else if (empty($CFG->nolastloggedin)) {
                set_moodle_cookie($USER->username);
            }

            $urltogo = core_login_get_return_url();

            $userauth = get_auth_plugin($USER->auth);
            if (!isguestuser() and !empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
                $externalchangepassword = false;
                if ($userauth->can_change_password()) {
                    $passwordchangeurl = $userauth->change_password_url();
                    if (!$passwordchangeurl) {
                        $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
                    } else {
                        $externalchangepassword = true;
                    }
                } else {
                    $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
                }
                $daystwoexpire = $userauth->password_expire($USER->username);
                $response['status'] = true;
                $response['data'] = json_encode([
                    'action' => 'confirmredirect',
                    'url'    => $CFG->wwwroot
                ]);
                if (intval($daystwoexpire) > 0 && intval($daystwoexpire) < intval($userauth->config->expiration_warning)) {
                    $response['msg'] = $OUTPUT->confirm(
                        get_string('auth_passwordwillexpire', 'auth', $daystwoexpire),
                        $passwordchangeurl,
                        $urltogo
                    );
                    return $response;
                } else if (intval($daystwoexpire) < 0 ) {
                    if ($externalchangepassword) {
                        // We end the session if the change password form is external. This prevents access to the site
                        // until the password is correctly changed.
                        require_logout();
                    } else {
                        // If we use the standard change password form, this user preference will be reset when the password
                        // is changed. Until then it will prevent access to the site.
                        set_user_preference('auth_forcepasswordchange', 1, $USER);
                    }
                    $response['msg'] = $OUTPUT->confirm(get_string('auth_passwordisexpired', 'auth'), $passwordchangeurl, $urltogo);
                    return $response;
                }
            }
            $response['status'] = true;
            $response['data'] = json_encode([
                'action' => 'redirect',
                'url'    => $CFG->wwwroot
            ]);
        } else {
            if (!isset($data->password)) {
                $response['msg'] = get_string('invalidlogin');
            } else {
                $errors['password'] = get_string('invalidlogin');
            }
            $response['errors'] = json_encode($errors);
        }
        return $response;
    }

    /**
     * Return index of event to sort before executing
     * @return integer
     * @since  Edwiser Form 1.0.0
     */
    public function get_index() {
        return 0;
    }

    /**
     * Returns does plugin support form submission
     * @return bool
     * @since  Edwiser Form 1.2.2
     */
    public function can_save_data() {
        return false;
    }

    /**
     * Return url to show in address bar
     * @param  Object $form Form object
     * @return String       Address bar
     */
    public function visible_url($form) {
        global $CFG;
        return $CFG->wwwroot . '/login/index.php';
    }
}

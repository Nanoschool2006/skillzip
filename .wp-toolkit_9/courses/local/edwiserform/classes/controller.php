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
 * Edwiser Forms controller methods.
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace local_edwiserform;

defined('MOODLE_INTERNAL') || die;

define('USER_UNCONFIRMED', 1);
define('USER_SUSPENDED', 2);
define('USER_UNSUSPENDED', 3);
define("EDWISERFORM_COMPONENT", "local_edwiserform");
define("EDWISERFORM_SUCCESS_FILEAREA", "successmessage");
define("EDWISERFORM_EMAIL_FILEAREA", "email");
define("EDWISERFORM_USER_FILEAREA", "userfiles");
define("UNAUTHORISED_USER", 1);
define("ADMIN_PERMISSION", 2);
define("SUPPORTED_FORM_STYLES", 4);
define('FORM_UPDATE', 1);
define('FORM_OVERITE', 2);
define('UPDATE_FAILED', 3);

require_once($CFG->dirroot . '/local/edwiserform/events/events.php');

use edwiserform_events_plugin;
use moodle_exception;
use moodle_url;
use core_component;
use context_system;
use stdClass;

/**
 * Class contains general methods of Edwiser Forms.
 *
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {

    /**
     * $instance for this class
     * @var controller
     */
    private static $instance = null;

    /**
     * Private constructor for singletone class
     */
    private function __construct() {
    }

    /**
     * Return singletone controller instance
     *
     * @return controller Controller class instance
     */
    public static function instance() {
        if (self::$instance == null) {
            self::$instance = new controller();
        }
        return self::$instance;
    }

    /**
     * Return events sub plugin object
     *
     * @return object
     * @since Edwiser Form 1.0.0
     */
    public function get_plugins() {
        global $CFG;
        $result = array();

        $names = core_component::get_plugin_list('edwiserformevents');
        foreach ($names as $name => $path) {
            if (file_exists($path . '/locallib.php')) {
                require_once($path . '/locallib.php');
                $shortsubtype = substr('edwiserformevents', strlen('edwiserform'));
                $pluginclass = 'edwiserform_' . $shortsubtype . '_' . $name;
                $result[$name] = new $pluginclass($this, $name);
            }
        }
        return $result;
    }

    /**
     * Return events sub plugins array object
     *
     * @param string $type of subplugin
     * @return object
     * @since Edwiser Form 1.0.0
     */
    public function get_plugin($type) {
        global $CFG;
        $result = null;

        $names = core_component::get_plugin_list('edwiserformevents');
        foreach ($names as $name => $path) {
            if ($name == $type && file_exists($path . '/locallib.php')) {
                require_once($path . '/locallib.php');
                $shortsubtype = substr('edwiserformevents', strlen('edwiserform'));
                $pluginclass = 'edwiserform_' . $shortsubtype . '_' . $name;
                $result = new $pluginclass($this, $name);
            }
        }
        return $result;
    }

    /**
     * Returns value from array at given key. If key not found then returning third parameter or empty value
     *
     * @param  array  $array The array of value
     * @param  string $key to find in the array
     * @param  string $value optional value to return if key not found
     * @return mixed  value found at key location in array
     * @since  Edwiser Form 1.0.0
     */
    public function get_array_val($array, $key, $value = "") {
        // Check if key exist in the array.
        if (isset($array[$key]) && !empty($array[$key])) {
            $value = $array[$key];
        }
        return $value;
    }

    /**
     * Check whether user can save data into form
     *
     * @param  stdClass $form object of form with definition and settings
     * @param  object   $plugin object of selected event
     * @return array    [status 0-cannot submit but have data|1-can submit|2-can submit and have data,
     *                   data previous submitted data]
     * @since  Edwiser Form 1.2.0
     */
    public function can_save_data($form, $plugin) {
        global $DB, $USER;
        $responce = ['status' => 1];

        if (!$this->is_logged_and_not_guest()) {
            return $responce;
        }
        if ($plugin != null && $plugin->support_multiple_submissions()) {
            return $responce;
        }
        $formid = $form->id;
        $sql = "SELECT f.type, f.data_edit, fd.submission FROM {efb_forms} f
                  JOIN {efb_form_data} fd ON f.id = fd.formid
                 WHERE f.id = ?
                   AND fd.userid = ?";
        $form = $DB->get_record_sql($sql, array($formid, $USER->id));
        if ($form && ($form->type == 'blank' || $plugin->can_save_data())) {
            if ($form->data_edit) {
                $responce['data'] = $form->submission;
                $responce['status'] = 2;
            } else {
                $responce['status'] = 0;
            }
        }
        return $responce;
    }

    /**
     * Return base class of events plugin
     *
     * @return edwiserform_events_plugin
     * @since Edwiser Form 1.2.0
     */
    public function get_events_base_plugin() {
        global $CFG;
        return new edwiserform_events_plugin();
    }

    /**
     * Sorting events based on index provided by get_index
     *
     * @param object $eventa event object of event at x index in events array
     * @param object $eventb event object of event at x+1 index in events array
     * @return integer 0|1|-1 if equal|greater|less
     * @since Edwiser Form 1.0.0
     */
    public function sort_events($eventa, $eventb) {
        if ($eventa->get_index() == $eventb->get_index()) {
            return 0;
        }
        return $eventa->get_index() > $eventb->get_index() ? 1 : -1;
    }

    /**
     * Generate user to send email
     *
     * @param string $email email id
     * @param string $name name of user (optional)
     * @param integer $id id of user (optional)
     * @return stdClass emailuser
     * @since Edwiser Form 1.0.0
     */
    public function generate_email_user($email, $name = '', $id = -99) {
        $emailuser = new stdClass();
        $emailuser->email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailuser->email = '';
        }
        $name = format_text($name, FORMAT_HTML, array('trusted' => false, 'noclean' => false));
        $emailuser->firstname = trim(filter_var($name, FILTER_SANITIZE_STRING));
        $emailuser->lastname = '';
        $emailuser->maildisplay = true;
        $emailuser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML emails.
        $emailuser->id = $id;
        $emailuser->firstnamephonetic = '';
        $emailuser->lastnamephonetic = '';
        $emailuser->middlename = '';
        $emailuser->alternatename = '';
        return $emailuser;
    }

    /**
     * Send email to user
     *
     * @param  stdClass $from user
     * @param  stdClass $to user
     * @param  stdClass $subject of email
     * @param  stdClass $messagehtml email body
     * @return boolean email sending status
     * @since Edwiser Form 1.0.0
     */
    public function edwiserform_send_email($from, $to, $subject, $messagehtml) {
        global $PAGE;
        $context = context_system::instance();
        $PAGE->set_context($context);
        $fromemail = $this->generate_email_user($from);
        $toemail = $this->generate_email_user($to);
        $messagetext = html_to_text($messagehtml);
        return email_to_user($toemail, $fromemail, $subject, $messagetext, $messagehtml, '', '', true, $fromemail->email);
    }

    /**
     * Generate a draft itemid
     *
     * @category files
     * @return int a random but available draft itemid that can be used to create a new draft file area.
     * @since Edwiser Form 1.0.0
     */
    public function file_get_unused_edwiserform_itemid() {
        global $DB, $USER;

        $contextid = context_system::instance()->id;

        $fs = get_file_storage();
        $itemid = rand(1, 999999999);
        while ($files = $fs->get_area_files($contextid, EDWISERFORM_COMPONENT, EDWISERFORM_USER_FILEAREA, $itemid)) {
            $itemid = rand(1, 999999999);
        }

        return $itemid;
    }

    /**
     * Delete area files of edwiserform
     *
     * @param string  $filearea File area where files are stored
     * @param integer $itemid   Item id to delete files
     * @since Edwiser Form 1.1.0
     */
    public function delete_edwiserform_files($filearea, $itemid) {
        if ($itemid < 1) {
            return;
        }
        $fs = get_file_storage();
        $fs->delete_area_files(context_system::instance()->id, EDWISERFORM_COMPONENT, $filearea, $itemid);
    }

    /**
     * Return true when user is logged and user is not guest
     *
     * @return boolean
     */
    public function is_logged_and_not_guest() {
        global $USER;
        if ($USER->id == 0 || $USER->id == 1) {
            return false;
        }
        return true;
    }

    /**
     * Check whether current user is enrolled as teacher in any course
     *
     * @param  int $userid id of user or no parameter for current user
     *
     * @return bool true if user is teacher
     * @since  Edwiser Form 1.2
     */
    public function is_teacher($userid = false) {
        global $USER, $DB;
        if ($userid == false) {
            $userid = $USER->id;
        }

        $sql = "SELECT count(ra.id) FROM {role_assignments} ra
                  JOIN {role} r ON ra.roleid = r.id
                 WHERE ra.userid = ?
                   AND r.archetype IN ('editingteacher', 'teacher')";
        $teachers = $DB->get_field_sql($sql, array($userid));
        return $teachers > 0;
    }

    /**
     * Check whether user can create, view form list or form data.
     *
     * @param  integer $userid id of user
     * @param  boolean $return true if return wheather user can create form
     * @return boolean
     * @since Edwiser Form 1.2.0
     */
    public function can_create_or_view_form($userid = false, $return = false) {
        global $USER, $DB;
        if (!$userid) {
            $userid = $USER->id;
        }
        // User is not logged in so not allowed.
        if (!$userid) {
            if ($return) {
                return false;
            }
            throw new moodle_exception('efb-cannot-create-form', 'local_edwiserform', new moodle_url('/my/'));
        }

        // User is site admin so allowed.
        if (is_siteadmin($userid)) {
            return true;
        }

        // User is not teacher so not allowed.
        if (!$this->is_teacher()) {
            if ($return) {
                return false;
            }
            throw new moodle_exception(
                'efb-cannot-create-form',
                'local_edwiserform',
                new moodle_url('/my/'),
                null,
                get_string('efb-contact-admin', 'local_edwiserform')
            );
        }

        // User is teacher.
        if (!get_config('local_edwiserform', 'enable_teacher_forms')) {

            // Admin disallowed teacher from creating/viewing form.
            if ($return) {
                return false;
            }
            throw new moodle_exception(
                'efb-admin-disabled-teacher',
                'local_edwiserform',
                new moodle_url('/my/'),
                null,
                get_string('efb-contact-admin', 'local_edwiserform')
            );
        }

        // User is teacher and admin allowing teacher to create/view form.
        return true;
    }

    /**
     * Merge input array with default array
     * @param  array $default Default array
     * @param  array $input   Input array
     * @return array          Merged array
     */
    public function merge_to_default($default, $input) {
        foreach ($input as $key => $value) {
            $default[$key] = $value;
        }
        return $default;
    }

    /**
     * Check if form is used as activity
     * @param  object|id  $formorid Form object or id
     * @return int               Activity object
     */
    public function get_activity($formorid) {
        global $DB;
        $dbman = $DB->get_manager();
        if (!$dbman->table_exists('edwiserform')) {
            return false;
        }
        if (gettype($formorid) == 'integer') {
            $formorid = $DB->get_record('efb_forms', array('id' => $formorid));
        }
        $activity = $DB->get_records_sql('SELECT ef.id
                                       FROM {edwiserform} ef
                                       JOIN {course_modules} cm ON ef.id  = cm.instance
                                       JOIN {modules} m ON cm.module = m.id
                                      WHERE form = ?
                                        AND cm.deletioninprogress <> 1
                                        AND m.name = "edwiserform"', array($formorid->id));
        if (count($activity) == 0) {
            return false;
        }
        return reset($activity)->id;
    }
}

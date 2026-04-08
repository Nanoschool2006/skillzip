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
 * Enrolment event functionality
 * @package   edwiserformevents_enrolment
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/edwiserform/events/events.php');
require_once($CFG->dirroot . '/user/lib.php');

/**
 * Enrolment event class definition
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edwiserform_events_enrolment extends edwiserform_events_plugin {

    /**
     * Return does event require logged in user
     * @return boolean
     * @since Edwiser Form 1.0.0
     */
    public function login_required() {
        return true;
    }

    /**
     * Funcion to enrol or unenrol user in the courses
     * @param  string  $courses comma separated string containing course id's
     * @param  integer $userid  if of user to enrol|unenrol in courses
     * @param  string  $action  enrol|unenrol
     * @return boolean|string   status on enrol|unenrolment
     * @since  Edwiser Form 1.0.0
     */
    public function enrol_unenrol_user($courses, $userid = null, $action = 'enrol') {
        global $DB, $USER;
        $user = $USER;
        if ($userid != null) {
            $user = core_user::get_user($userid);
        }

        $enrolplugin = enrol_get_plugin('manual');

        if (!in_array($action, array('enrol', 'unenrol'))) {
            return false;
        }
        $action .= '_user';
        if (!is_array($courses)) {
            $courses = explode(',', $courses);
        }
        foreach ($courses as $course) {
            $course = trim($course);
            $instances = enrol_get_instances($course, true);
            foreach ($instances as $instance) {
                if ($instance->enrol === 'manual') {
                    break;
                }
            }
            $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
            $enrolplugin->$action($instance, $user->id, $role->id);
        }
        $param = array(
            'name' => fullname($user),
            'action' => str_replace('_user', '', $action)
        );
        return get_string('efb-enrol-unenrol-success', 'edwiserformevents_enrolment', $param);
    }

    /**
     * Execute event action after form submission
     * @param  object $form   Form object
     * @param  object $data   Data submitted by user
     * @return boolean|string enrol|unenrolment status
     * @since  Edwiser Form 1.0.0
     */
    public function event_action($form, $data) {
        global $DB;
        $courses = $DB->get_field('efb_forms_enrolment', 'courses', array('formid' => $form->id));
        if (!empty($courses)) {
            return array(
                'status' => true,
                'msg' => $this->enrol_unenrol_user($courses)
            );
        }
        return array(
            'status' => true,
            'msg'    => get_string('efb-no-courses-warning-submission', 'edwiserformevents_enrolment')
        );
    }

    /**
     * Action to be performed by event on submission deletion
     * @param  Object $form Form object
     * @param  Object $data Submission data
     * @return String       Deletion operation status
     * @since  Edwiser Form 1.3.2
     */
    public function submission_deletion_action($form, $data) {
        global $DB;
        $courses = $DB->get_field('efb_forms_enrolment', 'courses', array('formid' => $form->id));
        if (!empty($courses)) {
            return $this->enrol_unenrol_user($courses, $data->userid, 'unenrol');
        }
        return get_string('efb-no-courses-warning-submission', 'edwiserformevents_enrolment');
    }

    /**
     * Does plugin support actions on form data list view
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function support_form_data_list_actions() {
        return true;
    }

    /**
     * Get html strings of action supported enrolment plugin
     * @param  boolean $status current status of enrolment
     * @return string          html action string
     * @since  Edwiser Form 1.0.0
     */
    private function get_enrolment_actions($status) {
        $actions = '';
        $actions .= html_writer::tag(
            'a',
            get_string('enrol', 'core_enrol'),
            array(
                'href' => '#',
                'class' => 'enrolment-action efb-data-action' . (!$status ? ' show' : ''),
                'data-action' => 'enrol'
            )
        );
        $actions .= html_writer::tag(
            'a',
            get_string('unenrol', 'core_enrol'),
            array(
                'href' => '#',
                'class' => 'enrolment-action efb-data-action' . ($status ? ' show' : ''),
                'data-action' => 'unenrol'
            )
        );
        return $actions;
    }

    /**
     * Return actions supported by the event
     * @param  stdClass $submission Form submission
     * @return string               html string actions
     * @since  Edwiser Form 1.2.0
     */
    public function form_data_list_actions($submission) {
        global $DB;
        $status = true;
        $courses = $DB->get_field('efb_forms_enrolment', 'courses', array('formid' => $submission->formid));
        $courses = explode(',', $courses);
        if (empty($courses)) {
            return get_string('efb-enrolment-courses-empty', 'edwiserformevents_enrolment');
        }
        foreach ($courses as $key => $course) {
            $course = trim($course);
            $instances = enrol_get_instances($course, true);
            foreach ($instances as $instance) {
                if ($instance->enrol === 'manual') {
                    break;
                }
            }
            $status = $status && $DB->record_exists(
                'user_enrolments',
                array('enrolid' => $instance->id, 'userid' => $submission->userid)
            );
        }
        $user = user_get_users_by_id([$submission->userid]);
        if ($user && isset(reset($user)->deleted) && reset($user)->deleted == 1) {
            return get_string('user-deleted', 'edwiserformevents_enrolment');
        }
        return $this->get_enrolment_actions($status);
    }

    /**
     * Form data list js files
     * @since Edwiser Form 1.0.0
     */
    public function form_data_list_js() {
        global $CFG, $PAGE;
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . '/local/edwiserform/events/enrolment/amd/src/form-data-list.js'));
    }

    /**
     * Does event have some settings for the form
     * @return boolean
     * @since  Edwiser Form 1.0.0
     */
    public function has_settings() {
        return true;
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
     * Get form settings supported by enrolment event
     * @param integer $formid id of form
     * @since Edwiser Form 1.0.0
     */
    public function get_settings($formid = false) {
        global $PAGE, $CFG;
        $PAGE->set_context(context_system::instance());
        $form = new MoodleQuickForm('', '', '');
        $courses = $this->get_courses_list();
        $selectedcourses = isset($formid) ? $this->get_selected_courses($formid) : [];
        $form->addElement(
            "select",
            "enrolment-courses",
            get_string("efb-enrolment-courses-list", "edwiserformevents_enrolment"),
            $courses,
            array('multiple' => true)
        )->setValue($selectedcourses);
        $form->closeHeaderBefore('enrolment-settings');
        $form->display();
    }

    /**
     * Get selected courses of form
     * @param  integer $id form id
     * @return array       courses
     * @since  Edwiser Form 1.0.0
     */
    private function get_selected_courses($id) {
        global $DB;
        $courses = $DB->get_field('efb_forms_enrolment', 'courses', array('formid' => $id));
        return explode(',', $courses);
    }

    /**
     * Provides functionality to get the list of the courses in the form of the array("id"=>fullname)
     * @return array
     * @since  Edwiser Form 1.0.0
     */
    private function get_courses_list() {
        global $DB;
        $courses    = array();
        $courselist = get_courses("all", "c.sortorder ASC", "c.id, c.fullname, c.visible");
        unset($courselist[get_site()->id]);
        foreach ($courselist as $course) {
            $courses[$course->id] = $course->fullname;
        }
        return $courses;
    }

    /**
     * Imploding the courses with comma selected by admin
     * @param  integer $formid  id of form
     * @param  array   $courses Courses
     * @return string|stdClass  settings
     * @since  Edwiser Form 1.0.0
     */
    private function filter_settings($formid, $courses) {
        $settings = new stdClass;
        $settings->formid = $formid;
        $settings->courses = implode(',', $courses);
        return $settings;
    }

    /**
     * Creating new form
     * @param  integer $formid           id of form
     * @param  string|stdClass $settings Event settings
     * @return intger                    new form id
     * @since  Edwiser Form 1.2.0
     */
    public function create_new_form($formid, $settings) {
        global $DB;
        $settings = $this->filter_settings($formid, $settings);
        return $DB->insert_record('efb_forms_enrolment', $settings);
    }

    /**
     * Update form settings
     * @param  integer $formid   id of form
     * @param  string  $settings Event settings
     * @return integer|boolean   status of form updation
     * @return Edwiser Form 1.0.0
     */
    public function update_form($formid, $settings) {
        global $DB;
        $id = $DB->get_field('efb_forms_enrolment', 'id', array('formid' => $formid));
        if (!$id) {
            return $this->create_new_form($formid, $settings);
        }
        $settings = $this->filter_settings($formid, $settings);
        $settings->id = $id;
        return $DB->update_record('efb_forms_enrolment', $settings);
    }

    /**
     * Verify form settings
     * @param  array $settings Event settings
     * @return string          Form verification status. If empty then no error otherwise error string
     * @since  Edwiser Form 1.0.0
     */
    public function verify_form_settings($settings) {
        if (empty($settings)) {
            return get_string('efb-enrolment-courses-empty', 'edwiserformevents_enrolment');
        }
        return '';
    }

    /**
     * Return index of event to sort before executing
     * @return integer
     * @since  Edwiser Form 1.0.0
     */
    public function get_index() {
        return 2;
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
     * Execute event action after form submission
     * @param  object $form Form object
     * @param  object $data Data submitted by user
     * @return object       Object with attached event data
     */
    public function attach_data($form, $data) {
        return $this->attach_common_data($form, $data);
    }

    /**
     * Action to be performed by event on form deletion
     * @param  Object $form Form object
     * @return String       Deletion operation status
     * @since  Edwiser Form 1.3.2
     */
    public function form_deletion_action($form) {
        global $DB;
        return $DB->delete_records('efb_forms_enrolment', array('formid' => $form->id));
    }
}

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
 * This file contains the forms to create and edit an instance of this module
 *
 * @package     mod_edwiserform
 * @copyright   2018 WisdmLabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Edwiser Forms 1.0.1
 */


defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require_once($CFG->dirroot.'/course/moodleform_mod.php');

use local_edwiserform\controller;

/**
 * edwiserform settings form.
 *
 * @package   mod_edwiserform
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edwiserform_mod_form extends moodleform_mod {

    /**
     * Called to define this moodle form
     *
     * @return void
     */
    public function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $USER;
        $controller = controller::instance();
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'edwiserform'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'edwiserform'));

        $sql = "SELECT id, title, author, author2, type, enabled, deleted, created, modified FROM {efb_forms} WHERE deleted = '0'";
        $param = [];
        if (!is_siteadmin() && $controller->can_create_or_view_form()) {
            $sql .= " AND author=? ";
            $param[] = $USER->id;
        }
        $forms = $DB->get_records_sql($sql, $param);
        foreach ($forms as $id => $form) {
            $plugin = $controller->get_plugin($form->type);
            if ($form->type == 'blank' || $plugin->login_allowed()) {
                $forms[$id] = $form->title;
            } else {
                unset($forms[$id]);
            }
        }

        $select = $mform->addElement('select', 'form', get_string('form', 'edwiserform'), $forms, array('class' => 'mb-0'));

        $mform->addElement('html', $PAGE->get_renderer('mod_edwiserform')->render_from_template('mod_edwiserform/mod_form', null));

        $PAGE->requires->strings_for_js(['preview', 'warning', 'formopen', 'discard', 'wait'], 'mod_edwiserform');
        $PAGE->requires->js_call_amd('mod_edwiserform/mod_form', 'init');

        $this->standard_coursemodule_elements();

        $this->apply_admin_defaults();

        $this->add_action_buttons();
    }

    /**
     * Add any custom completion rules to the form.
     *
     * @return array Contains the names of the added form elements
     */
    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('advcheckbox', 'completionsubmit', '', get_string('completionsubmit', 'edwiserform'));
        // Enable this completion rule by default.
        $mform->setDefault('completionsubmit', 1);
        return array('completionsubmit');
    }

    /**
     * Determines if completion is enabled for this module.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $formid = $data['form'];

        local_edwiserform\external\efb_api::enable_disable_form($formid, 'enable');

        return $errors;
    }

}

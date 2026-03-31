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
 * This file contains the function to manipulate form instance
 *
 * @package     mod_edwiserform
 * @copyright   2018 WisdmLabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Edwiser Forms 1.0.1
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds an edwiserform instance
 *
 * This is done by calling the add_instance() method of the edwiserform type class
 * @param stdClass $data
 * @param mod_edwiserform_mod_form $form
 * @return int The instance id of the new edwiserform
 */
function edwiserform_add_instance(stdClass $data, $form) {
    global $DB;
    return $DB->insert_record('edwiserform', $data, true);
}

/**
 * delete an edwiserform instance
 * @param int $id
 * @return bool
 */
function edwiserform_delete_instance($id) {
    global $DB;
    return $DB->delete_records('edwiserform', array('id' => $id));
}

/**
 * Update an edwiserform instance
 *
 * This is done by calling the update_instance() method of the edwiserform type class
 * @param stdClass $data
 * @param stdClass $form - unused
 * @return object
 */
function edwiserform_update_instance(stdClass $data, $form) {
    global $DB;
    $record = new stdClass;
    $record->id = $data->instance;
    $record->name = $data->name;
    $record->intro = $data->intro;
    $record->introformat = $data->introformat;
    $record->form = $data->form;
    return $DB->update_record('edwiserform', $record);
}

/**
 * Return the list if Moodle features this module supports
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function edwiserform_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * extend an edwiserform navigation settings
 *
 * @param settings_navigation $settings
 * @param navigation_node $navref
 * @return void
 */
function edwiserform_extend_settings_navigation(settings_navigation $settings, navigation_node $navref) {
    global $PAGE, $DB;

    $formrecord = $DB->get_record('edwiserform', array('id' => $PAGE->cm->instance));

    $form = $DB->get_record('efb_forms', array('id' => $formrecord->form));

    if (!$form) {
        throw new moodle_exception('invalidform', 'mod_edwiserform');
    }

    // Link to gradebook.
    if (has_capability('mod/edwiserform:viewdata', $PAGE->cm->context)) {
        $link = new moodle_url('/local/edwiserform/view.php', array('page' => 'viewdata', 'formid' => $form->id));
        $linkname = get_string('viewdata', 'edwiserform');
        $node = $navref->add($linkname, $link, navigation_node::TYPE_SETTING);
    }

}


/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_edwiserform_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'assign');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

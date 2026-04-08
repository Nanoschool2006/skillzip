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
 * This file initialize page to show form
 *
 * @package     mod_edwiserform
 * @copyright   2018 WisdmLabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Edwiser Forms 1.0.1
 */

require_once('../../config.php');

global $PAGE, $OUTPUT, $DB;

$id = required_param('id', PARAM_INT);

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'edwiserform');

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

require_capability('mod/edwiserform:view', $context);

$url = new moodle_url('/mod/edwiserform/view.php', array('id' => $id));

$PAGE->set_url($url);

$formrecord = $DB->get_record('edwiserform', array('id' => $cm->instance));

$form = $DB->get_record('efb_forms', array('id' => $formrecord->form));

if (!$form) {
    throw new moodle_exception('invalidform', 'mod_edwiserform');
}

$out = format_text('[edwiser-form id="' . $form->id . '"]');
$PAGE->requires->js_call_amd('mod_edwiserform/main', 'init');
echo $OUTPUT->header();
echo html_writer::tag('input', '', array(
    'type'  => 'hidden',
    'id'    => 'edwiser-activity',
    'value' => true
));
echo html_writer::tag('input', '', array(
    'type'  => 'hidden',
    'id'    => 'cmid',
    'value' => $id
));
echo $out;
echo $OUTPUT->footer();

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
 * Edwiser Grader initial page.
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/blocks/edwiser_grader/lib.php');

global $PAGE, $OUTPUT, $COURSE;
$id = required_param('id', PARAM_INT);
$gdm = required_param('gdm', PARAM_TEXT);

if ($id && $gdm) {
    if (!$cm = get_coursemodule_from_id('quiz', $id)) {
        print_error('invalidcoursemodule');
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
} else {
    print_error('invalidcoursemodule');
}

// Check login and get context.
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$requirements = new \block_edwiser_grader\page_requirements();

// TODO Add capabilty for View.

$PAGE->set_pagelayout('popup');
$PAGE->set_context($context);
$title = $course->shortname;
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->set_cm($cm, $course);
$urlparams = array('gdm' => $gdm, 'id' => $id);
$PAGE->set_url(new moodle_url('/blocks/edwiser_grader/grader.php', $urlparams));
$PAGE->requires->css('/blocks/edwiser_grader/style/styles.min.css');
$PAGE->requires->css('/blocks/edwiser_grader/style/bootstrap-select.css');
$stringmanager = get_string_manager();
$strings = $stringmanager->load_component_strings('block_edwiser_grader', 'en');
$PAGE->requires->strings_for_js(array_keys($strings), 'block_edwiser_grader');

$PAGE->requires->js_call_amd('block_edwiser_grader/grader', 'init', array($context->id));

$requirements->grader_js_module(quiz_get_js_module());

echo $OUTPUT->header();
$output = render_grader_content($cm->instance, $id, $gdm);
echo $output;
echo $OUTPUT->footer();

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
 *
 * Add and remove users for grading
 *
 * This does adding and removing users for grading.
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot.'/blocks/edwiser_grader/lib.php');

admin_externalpage_setup('block_edwiser_grader_addremoveusers');

// Add page configuration.
$systemcontext = context_system::instance();
$title = get_string('add_remove_users', 'block_edwiser_grader');
$PAGE->set_context($systemcontext);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url($CFG->wwwroot."/blocks/edwiser_grader/classes/add_remove_users.php");
$PAGE->requires->css('/blocks/edwiser_grader/style/userassignment.css');
$PAGE->requires->js_call_amd('block_edwiser_grader/settings', 'init');

echo $OUTPUT->header();
$output = render_add_remove_users_content();
echo $output;
echo $OUTPUT->footer();

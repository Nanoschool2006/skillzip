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
 * Edwiser Site Monitor block installation.
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to manipulate database values on plugin installation
 * @return boll true
 */
function xmldb_block_edwiser_grader_install() {
    global $DB, $CFG;
    $systempage = $DB->get_record('my_pages', array('userid' => null, 'private' => 1));

    $page = new moodle_page();
    $page->set_context(context_system::instance());

    // Selecting default region for blocks i.e. content.
    $page->blocks->add_region('content');

    // Adding blocks for admin.
    $admin = get_admin();
    if ($admin != false) {
        $page->set_context(context_user::instance($admin->id));
        $page->blocks->add_block('edwiser_grader', 'content', -2, false, 'my-index');
    }

    // Remove previous tours.
    removetours('Grader Tour');
    removetours('User Based Grading');
    removetours('Question Based Grading');

    // Add User tours.
    $graderblockconfigraw = file_get_contents($CFG->dirroot."/blocks/edwiser_grader/db/graderblock.json");
    $tour = tool_usertours\manager::import_tour_from_json($graderblockconfigraw);
    $userbasedconfigraw = file_get_contents($CFG->dirroot."/blocks/edwiser_grader/db/userbased.json");
    $tour = tool_usertours\manager::import_tour_from_json($userbasedconfigraw);
    $questionbasedconfigraw = file_get_contents($CFG->dirroot."/blocks/edwiser_grader/db/questionbased.json");
    $tour = tool_usertours\manager::import_tour_from_json($questionbasedconfigraw);
    return true;
}

/**
 * Remove tours from database
 * @param  string $tourname Tour name
 */
function removetours($tourname) {
    global $DB;
    $tours = $DB->get_records('tool_usertours_tours', array ('name' => $tourname));
    foreach ($tours as $tour) {
        $tour = tool_usertours\tour::instance($tour->id);
        $tour->remove();
    }
}

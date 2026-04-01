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
 * @package     mod_edwiserform
 * @copyright   (c) 2019 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * upgrade this edwiserform plugin database
 * @param int $oldversion The old version of the edwiserform mod plugin
 * @return bool
 */
function xmldb_edwiserform_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('edwiserform');

    // Adding style field.
    $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, 10, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $sql = "SELECT cm.instance id, cm.course course
              FROM {course_modules} cm
              JOIN {modules} m ON cm.module = m.id
              JOIN {edwiserform} ef ON cm.instance = ef.id
             WHERE m.name = ? AND ef.course IS NULL";
    $edwiserforms = $DB->get_records_sql($sql, array('edwiserform'));
    if (!empty($edwiserforms)) {
        foreach ($edwiserforms as $edwiserform) {
            $DB->update_record('edwiserform', $edwiserform);
        }
    }
    return true;
}

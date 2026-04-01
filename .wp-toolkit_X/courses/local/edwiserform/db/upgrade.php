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
 * Edwiser Forms upgrade hook.
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade this edwiserform plugin database
 * @param int $oldversion The old version of the edwiserform local plugin
 * @return bool
 */
function xmldb_local_edwiserform_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('efb_forms');

    // Adding events field.
    $field = new xmldb_field('events', XMLDB_TYPE_TEXT, null, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Adding enable_notifi_email field.
    $field = new xmldb_field('enable_notifi_email', XMLDB_TYPE_BINARY, null, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Adding notifi_email_body field.
    $field = new xmldb_field('notifi_email_body', XMLDB_TYPE_TEXT, null, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Adding style field.
    $field = new xmldb_field('style', XMLDB_TYPE_INTEGER, 2, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('allowsubmissionsfromdate', XMLDB_TYPE_INTEGER, 10, null, true, null, 0);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $field = new xmldb_field('allowsubmissionstodate', XMLDB_TYPE_INTEGER, 10, null, true, null, 0);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Adding notifi_email_subject field.
    $field = new xmldb_field('notifi_email_subject', XMLDB_TYPE_CHAR, 1000, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    // Adding confirmation_subject field.
    $field = new xmldb_field('confirmation_subject', XMLDB_TYPE_CHAR, 1000, null, false, false);
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }

    $forms = $DB->get_records('efb_forms', array('confirmation_subject' => null));

    if (!empty($forms)) {
        foreach ($forms as $id => $form) {
            $form->confirmation_subject = get_string('efb-lbl-confirmation-default-subject', 'local_edwiserform');
            $DB->update_record('efb_forms', $form);
        }
    }

    if ($oldversion < 2019061800) {
        // Updated data type of efb_forms table.
        $table = new xmldb_table('efb_forms');
        $field = new xmldb_field('created', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('modified', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);

        $forms = $DB->get_records('efb_forms', [], '', 'id, created, modified');
        if (!empty($forms)) {
            foreach ($forms as $id => $form) {
                $modify = false;
                if ($form->created != '' && $form->created != null && !is_numeric($form->created)) {
                    $modify = true;
                    $form->created = strtotime($form->created);
                }
                if ($form->modified != '' && $form->modified != null && !is_numeric($form->modified)) {
                    $modify = true;
                    $form->modified = strtotime($form->modified);
                }
                if ($modify == true) {
                    $DB->update_record('efb_forms', $form);
                }
            }
        }

        $field = new xmldb_field('created', XMLDB_TYPE_INTEGER, 10);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('modified', XMLDB_TYPE_INTEGER, 10);
        $dbman->change_field_type($table, $field);

        // Updated data type of efb_form_data table.
        $table = new xmldb_table('efb_form_data');
        $field = new xmldb_field('date', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('updated', XMLDB_TYPE_CHAR, 50, null, false, false);
        $dbman->change_field_type($table, $field);

        $forms = $DB->get_records('efb_form_data', [], '', 'id, date, updated');
        if (!empty($forms)) {
            foreach ($forms as $id => $form) {
                $modify = false;
                if ($form->date != '' && $form->date != null && !is_numeric($form->date)) {
                    $modify = true;
                    $form->date = strtotime($form->date);
                }
                if ($form->updated != '' && $form->updated != null && !is_numeric($form->updated)) {
                    $modify = true;
                    $form->updated = strtotime($form->updated);
                }
                if ($modify == true) {
                    $DB->update_record('efb_form_data', $form);
                }
            }
        }

        $field = new xmldb_field('date', XMLDB_TYPE_INTEGER, 10);
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('updated', XMLDB_TYPE_INTEGER, 10);
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, 2019061800, 'local', 'edwiserform');
    }

    if ($oldversion < 2019110700) {
        $table = new xmldb_table('efb_form_data');
        $field = new xmldb_field('submission', XMLDB_TYPE_TEXT, null, null, true, false);
        $dbman->change_field_type($table, $field);
    }
    return true;
}

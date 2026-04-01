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
 * Edwiser form cleanup task
 *
 * @package local_edwiserform
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_edwiserform\task;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/edwiserform/locallib.php');

use context_system;
use dml_exception;
use edwiserform;

class cleanup extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanup_task', 'local_edwiserform');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute() {
        global $DB;

        $edwiserform = new edwiserform();

        $forms = $DB->get_records('efb_forms', array('deleted' => true));
        $this->log_start('Edwiser Form cleanup started.');
        foreach ($forms as $form) {
            // Explode events into array to use further. If events is empty then assign empty array to skip event check.
            $events = $form->events == "" ? [] : explode(',', $form->events);
            if (!empty($events)) {
                foreach ($events as $key => $event) {
                    $plugin = $this->controller->get_plugin($event);
                    $events[$key] = $plugin;
                }
            }
            $this->log_start("Deleting form {$form->id}.");
            $this->log_start("Deleting form data of {$form->id}");
            $data = $DB->get_records_sql(
                'SELECT * FROM {efb_form_data}
                  WHERE formid = :formid',
                array('formid' => $form->id)
            );
            if ($data) {
                $this->log_start("Deleting form user file of {$form->id}.");
                foreach ($data as $userdata) {
                    // If there is files in the submission then delete it first.
                    if (stripos($userdata->submission, '"type":"file"') != -1) {
                        $edwiserform->delete_user_uploaded_files($userdata);
                    }
                    // If form has any events enabled then trigger its submission_deletion_action.
                    if (!empty($events)) {
                        foreach ($events as $plugin) {
                            $this->log($plugin->submission_deletion_action($form, $userdata));
                        }
                    }
                }
                $this->log_finish("Deleted form user files of {$form->id}.");
            }
            try {
                $status = $DB->delete_records('efb_form_data', array('formid' => $form->id));
                if ($status) {
                    $this->log_finish("Deleted form data of {$form->id}.");
                } else {
                    $this->log_finish("Deleting form user files of {$form->id} failed.");
                }
            } catch (dml_exception $ex) {
                $this->log_finish("Deleting form data of {$form->id} failed.");
            }
            try {
                $status = $DB->delete_records('efb_forms', array('deleted' => true));
                if ($status) {
                    $this->log_finish("Deleted form {$form->id}.");
                } else {
                    $this->log_finish("Deleting form {$form->id} failed.");
                }
            } catch (dml_exception $ex) {
                $this->log_finish("Deleting form {$form->id} failed.");
            }
            // If form has any events enabled then trigger its submission_deletion_action.
            if (!empty($events)) {
                foreach ($events as $plugin) {
                    $this->log($plugin->form_deletion_action($form, $userdata));
                }
            }
        }
        $fs = get_file_storage();
        $fs->delete_area_files(context_system::instance()->id, EDWISERFORM_COMPONENT, 'export');
        $this->log_finish('Edwiser Form cleanup task completed.');
    }
}

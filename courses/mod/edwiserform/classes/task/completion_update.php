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

namespace mod_edwiserform\task;
defined('MOODLE_INTERNAL') || die();

use completion_info;

/**
 * A schedule task for edwiserform cron.
 *
 * @package     mod_edwiserform
 * @copyright   (c) 2019 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Yogesh Shirsath
 */
class completion_update extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask_completion', 'mod_edwiserform');
    }

    /**
     * Run assignment cron.
     */
    public function execute() {
        global $CFG, $DB;

        // Marking activities as complete
        $formactivities = $DB->get_records_sql("
            SELECT cm.id as cmid, efd.userid
              FROM {edwiserform} eda
              JOIN {efb_form_data} efd ON eda.form = efd.formid
              JOIN {course_modules} cm ON eda.id = cm.instance
              JOIN {modules} m ON cm.module = m.id
                               AND m.name = 'edwiserform'
         LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid
                                                  AND efd.userid = cmc.userid
             WHERE cm.completion <> 0
               AND cmc.userid IS NULL");

        if ($formactivities || count($formactivities) > 0) {
            foreach ($formactivities as $form) {
                list($course, $cm) = get_course_and_cm_from_cmid($form->cmid);
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_COMPLETE, $form->userid);
                }
            }
        }


        // Marking activities as incomplete
        $formactivities = $DB->get_records_sql("
            SELECT cm.id as cmid, cmc.userid
              FROM mdl_edwiserform eda
              JOIN mdl_course_modules cm ON eda.id = cm.instance
              JOIN mdl_modules m ON cm.module = m.id
                                AND m.name = 'edwiserform'
              JOIN mdl_course_modules_completion cmc ON cm.id = cmc.coursemoduleid
         LEFT JOIN mdl_efb_form_data efd ON eda.form = efd.formid
                                        AND cmc.userid = efd.userid
             WHERE cm.completion <> 0");

        if ($formactivities || count($formactivities) > 0) {
            foreach ($formactivities as $form) {
                list($course, $cm) = get_course_and_cm_from_cmid($form->cmid);
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm)) {
                    $completion->update_state($cm, COMPLETION_INCOMPLETE, $form->userid);
                }
            }
        }
        return true;
    }
}

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
 * Edwiser Grader Plugin dry_run_regrade trait.
 *
 * @package    block_edwiser_grader
 * @subpackage external
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader\external;
defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_value;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');
require_once($CFG->dirroot.'/mod/quiz/report/reportlib.php');

/**
 * Trait implementing the external function block_edwisergrader_dry_run_regrade.
 */
trait dry_run_regrade {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function dry_run_regrade_parameters() {
        return new external_function_parameters(
            array (
                'cmid' => new external_value(PARAM_INT, 'Course Module ID.', VALUE_DEFAULT, 0),
                'dryrun' => new external_value(PARAM_BOOL, 'True for dry run and false for regrade', VALUE_DEFAULT, true)
            )
        );
    }

    /**
     * Dry Run a Full Regrade
     * @param  int   $cmid   Course module id
     * @param  bool  $dryrun True to dry run
     * @return array         Dry run result
     **/
    public static function dry_run_regrade($cmid, $dryrun = true) {
        global $DB, $PAGE;
        $PAGE->set_context(\context_system::instance());
        $cm = get_coursemodule_from_id('quiz', $cmid);
        $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
        self::regrade_attempts($quiz, $cmid, $dryrun);
        if ($dryrun == true) {
            $result['regrade'] = get_string('dryregradedsuccessmsg', 'block_edwiser_grader');
        } else {
            $result['regrade'] = get_string('regradedsuccessmsg', 'block_edwiser_grader');
        }
        return $result;
    }

    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function dry_run_regrade_returns() {
        return new \external_single_structure(
            array(
                'regrade' => new external_value(PARAM_RAW, 'Regraded Attempt'),
            )
        );
    }
    /**
     * Regrade Attempts
     * @param  Object  $quiz   Quiz Object
     * @param  integer $cmid   Course Module ID
     * @param  boolean $dryrun Dry run enabled or not.
     */
    public static function regrade_attempts($quiz, $cmid, $dryrun = false) {
        global $DB;

        $sql = "SELECT quiza.*
                  FROM {quiz_attempts} quiza";
        $where = "quiz = :qid AND preview = 0";
        $params = array('qid' => $quiz->id);

        $sql .= "\nWHERE {$where}";
        $attempts = $DB->get_records_sql($sql, $params);

        if (!$attempts) {
            return;
        }
        self::clear_regrade_table($quiz);
        foreach ($attempts as $attempt) {
            $attemptobj = quiz_create_attempt_handling_errors($attempt->id, $cmid);
            regrade_quiz_attempt::regrade_attempt($attemptobj, $dryrun);
        }

        if (!$dryrun) {
            quiz_update_all_attempt_sumgrades($quiz);
            quiz_update_all_final_grades($quiz);
            quiz_update_grades($quiz);
            self::clear_regrade_table($quiz);
        }
    }
    /**
     * Clear Regrade Database Table.
     * @param  Object $quiz Quiz Object.
     */
    public function clear_regrade_table($quiz) {
        global $DB;

        // Fetch all attempts that need regrading.
        $select = "questionusageid IN (
                    SELECT uniqueid
                      FROM {quiz_attempts} quiza";
        $where = "WHERE quiza.quiz = :qid";
        $params = array('qid' => $quiz->id);
        $select .= "\n$where)";

        $DB->delete_records_select('quiz_overview_regrades', $select, $params);
    }
}

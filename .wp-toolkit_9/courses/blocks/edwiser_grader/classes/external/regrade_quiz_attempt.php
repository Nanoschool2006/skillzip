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
 * Edwiser Grader Plugin regrade_quiz_attempt trait.
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
 * Trait implementing the external function block_edwisergrader_get_attempt_questions.
 */
trait regrade_quiz_attempt {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function regrade_quiz_attempt_parameters() {
        return new external_function_parameters(
            array (
                'attempts' => new external_value(PARAM_RAW, 'Selected Attempt Ids.', VALUE_DEFAULT, 0),
                'cmid' => new external_value(PARAM_INT, 'Course Module ID.', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Regrade Selected Attempt
     * @param Array $attempts Attempts Array
     * @param Integer $cmid Course Module Id
     **/
    public static function regrade_quiz_attempt($attempts, $cmid) {
        global $PAGE;
        $PAGE->set_context(\context_system::instance());
        $attempts   = json_decode($attempts);
        foreach ($attempts as $attemptid) {
            $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
            self::regrade_attempt($attemptobj);
        }
        $result['regrade'] = get_string('regradedsuccessmsg', block_edwiser_grader);
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function regrade_quiz_attempt_returns() {
        return new \external_single_structure(
            array(
                'regrade' => new external_value(PARAM_RAW, 'Regraded Attempt'),
            )
        );
    }
    /**
     * Regrade a particular quiz attempt. Either for real ($dryrun = false), or
     * as a pretend regrade to see which fractions would change. The outcome is
     * stored in the quiz_overview_regrades table.
     *
     * Note, $attempt is not upgraded in the database. The caller needs to do that.
     * However, $attempt->sumgrades is updated, if this is not a dry run.
     *
     * @param object $attempt the quiz attempt to regrade.
     * @param bool $dryrun if true, do a pretend regrade, otherwise do it for real.
     * @param array $slots if null, regrade all questions, otherwise, just regrade
     *      the quetsions with those slots.
     */
    public static function regrade_attempt($attempt, $dryrun = false, $slots = null) {
        global $DB;
        // Need more time for a quiz with many questions.
        \core_php_time_limit::raise(300);

        $transaction = $DB->start_delegated_transaction();
        $quba = \question_engine::load_questions_usage_by_activity($attempt->get_uniqueid());

        if (is_null($slots)) {
            $slots = $quba->get_slots();
        }

        $finished = $attempt->state == \quiz_attempt::FINISHED;
        foreach ($slots as $slot) {
            $qqr = new \stdClass();
            $qqr->oldfraction = $quba->get_question_fraction($slot);

            $quba->regrade_question($slot, $finished);

            $qqr->newfraction = $quba->get_question_fraction($slot);

            if (abs($qqr->oldfraction - $qqr->newfraction) > 1e-7) {
                $qqr->questionusageid = $quba->get_id();
                $qqr->slot = $slot;
                $qqr->regraded = empty($dryrun);
                $qqr->timemodified = time();
                $DB->insert_record('quiz_overview_regrades', $qqr, false);
            }
        }
        if (!$dryrun) {
            \question_engine::save_questions_usage_by_activity($quba);
            dry_run_regrade::clear_regrade_table($attempt->get_quiz());
        }

        $transaction->allow_commit();

        // Really, PHP should not need this hint, but without this, we just run out of memory.
        $quba = null;
        $transaction = null;
        gc_collect_cycles();
    }
}

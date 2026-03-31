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
 * Edwiser Grader Plugin get_attempt_questions trait.
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

/**
 * Trait implementing the external function block_edwisergrader_get_attempt_questions.
 */
trait get_attempt_questions {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_attempt_questions_parameters() {
        return new external_function_parameters(
            array (
                'attemptid' => new external_value(PARAM_INT, 'Attempt ID.', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Get attempt questions
     * @param Integer $attemptid                  Attempt Id
     **/
    public static function get_attempt_questions($attemptid) {
        $review     = 1;
        $page       = 'all';
        $questions  = array();
        $attemptobj     = get_attempt_object($attemptid);
        $displayoptions = $attemptobj->get_display_options($review);
        foreach ($attemptobj->get_slots($page) as $slot) {
            if ($attemptobj->is_real_question($slot)) {
                $question['number']           = $attemptobj->get_question_number($slot);
                $question['status']           = self::get_question_status($attemptobj, $slot, $displayoptions->correctness);
                $quizattempt                  = $attemptobj->get_question_attempt($slot);
                $qusageid                     = $quizattempt->get_usage_id();
                $showcorrectness              = $displayoptions->correctness && $quizattempt->has_marks();
                $questionstate                = $attemptobj->get_question_attempt($slot)->get_state_class($showcorrectness);
                $question['slot']             = $slot;
                $question['attemptid']        = $attemptid;
                $usergrade                    = $attemptobj->get_question_mark($slot);
                $questionmaxgrade             = $attemptobj->get_question_attempt($slot)->get_max_mark();
                $question['regrademarks']     = self::get_regraded_value($qusageid, $slot, $questionmaxgrade);
                $question['questiongrade']    = self::calculate_question_grade($usergrade, $questionmaxgrade, $questionstate);
                $questions[] = $question;
            }
        }
        // Sort by quizstatus in descending order.
        usort($questions, function($first, $second) {
            return $first['slot'] <=> $second['slot'];
        });
        return $questions;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_attempt_questions_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'number' => new external_value(PARAM_INT, 'Question Number'),
                    'status' => new external_value(PARAM_RAW, 'Question Status'),
                    'slot' => new external_value(PARAM_INT, 'Question slot'),
                    'attemptid' => new external_value(PARAM_RAW, 'Question attempt id'),
                    'regrademarks' => new external_value(PARAM_RAW, 'Question ReGrade'),
                    'questiongrade' => new external_value(PARAM_RAW, 'Question Grade')
                )
            )
        );
    }
    /**
     * Get Question Status
     * @param Object $attemptobj Attempt Object
     * @param Integer $slot Question slot
     * @param Text $correctness Question display correctness
     *
     * @return Text Question status
     */
    public static function get_question_status($attemptobj, $slot, $correctness) {
        $status = $attemptobj->get_question_status($slot, $correctness);
        $status = preg_replace('/\s+/', '', strtolower($status));
        return $status;
    }
    /**
     * Calculates Question Grade
     * @param  Float $usergrade     User Grade
     * @param  Float $questiongrade Question Grade
     * @param String $questionstate Question State
     * @return Text                User Grade in text
     */
    public static function calculate_question_grade($usergrade, $questiongrade, $questionstate) {
        if ($usergrade) {
            if (floor($usergrade) == $usergrade) {
                $usergrade = floor($usergrade);
            } else {
                $usergrade = number_format($usergrade, 2);
            }
            $grade = (float)$usergrade . ' / ' . (float)$questiongrade;
        } else if ($questionstate === 'notanswered') {
            $grade = 'NA';
        } else {
            $grade = 'RG';
        }
        return $grade;
    }
    /**
     * Get the regraded value of a question.
     * @param  Integer $qusageid Question usage ID
     * @param  Integer $slot     Slot ID
     * @param  Integer $maxmarks Maximum alloted marks
     * @return Float/Boolean         Regraded value.
     */
    public static function get_regraded_value($qusageid, $slot, $maxmarks) {
        global $DB;
        $record = $DB->get_record('quiz_overview_regrades', array('questionusageid' => $qusageid, 'slot' => $slot));
        if ($record) {
            $newmark = (float)$record->newfraction * (float)$maxmarks;
            if (floor($newmark) == $newmark) {
                $newmark = floor($newmark);
            } else {
                $newmark = number_format($newmark, 2);
            }
            return $newmark . " / " . (float)$maxmarks;
        }
        return false;
    }
}

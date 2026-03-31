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
 * Edwiser Grader Plugin grade_question trait.
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
trait grade_question {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function grade_question_parameters() {
        return new external_function_parameters(
            array (
                'slot' => new external_value(PARAM_INT, 'Slot ID.', VALUE_DEFAULT, 0),
                'attemptid' => new external_value(PARAM_INT, 'Attempt ID.', VALUE_DEFAULT, 0),
                'cmid' => new external_value(PARAM_INT, 'Course Module ID.', VALUE_DEFAULT, 0),
                'comment' => new external_value(PARAM_RAW, 'Grade Comment', VALUE_DEFAULT, 0),
                'marks' => new external_value(PARAM_FLOAT, 'Grade Marks', VALUE_DEFAULT, 0),
                'studid' => new external_value(PARAM_INT, 'Student ID', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Grade Questions
     * @param  Integer $slot      Slot Id
     * @param  Integer $attemptid Attempt Id
     * @param  Integer $cmid      Course Module Id
     * @param  String $comment    Comment
     * @param  Integer $marks     Marks
     * @param  Integer $studid    Student Id
     * @return Array Array of grades.
     */
    public static function grade_question($slot, $attemptid, $cmid, $comment, $marks, $studid) {
        global $PAGE, $DB;
        $PAGE->set_context(\context_system::instance());
        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
        $quba       = \question_engine::load_questions_usage_by_activity($attemptobj->get_uniqueid());
        $marks      = ($marks == -1) ? null : $marks;
        $quba->get_question_attempt($slot)->manual_grade($comment, $marks , FORMAT_HTML, time() + 1200);
        \question_engine::save_questions_usage_by_activity($quba);
        $update = new \stdClass();
        $update->id = $attemptobj->get_attemptid();
        $update->timemodified = time() + 1200;
        $update->sumgrades = $quba->get_total_mark();
        $DB->update_record('quiz_attempts', $update);
        quiz_save_best_grade($attemptobj->get_quiz(), $studid);
        $result['submit'] = 'Submitted';
        $result['grade']  = get_attempt_grade($attemptid, $attemptobj->get_quiz()->id);
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function grade_question_returns() {
        return new \external_single_structure(
            array(
                'submit' => new external_value(PARAM_RAW, 'submit Data'),
                'grade' => new external_value(PARAM_RAW, 'Grade for attempt')
            )
        );
    }
}

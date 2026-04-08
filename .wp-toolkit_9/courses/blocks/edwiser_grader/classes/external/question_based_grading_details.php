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
 * Edwiser Grader Plugin question_based_grading_details trait.
 *
 * @package    block_edwiser_grader
 * @subpackage external
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader\external;
defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_multiple_structure;
use external_value;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');
require_once($CFG->dirroot.'/mod/quiz/report/reportlib.php');
require_once('question_based_grading.php');

/**
 * Trait implementing the external function block_edwisergrader_question_based_grading_details.
 */
trait question_based_grading_details {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function question_based_grading_details_parameters() {
        return new external_function_parameters(
            array (
                'cmid' => new external_value(PARAM_INT, 'Course Module ID', VALUE_DEFAULT, 0),
                'mode' => new external_value(PARAM_TEXT, 'grading mode', VALUE_DEFAULT, 0),
                'slots' => new external_multiple_structure(
                     new external_value(PARAM_INT, 'question slot'),
                     'Question slots',
                     VALUE_DEFAULT,
                     [0]
                ),
                'qid' => new external_value(PARAM_INT, 'question ID', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Fetch question based grading details from course module id/quiz
     * @param  int    $cmid  Course Module Id
     * @param  string $mode  Grading mode
     * @param  array  $slots Grading slots
     * @param  int    $qid   Quiz id
     * @return array         Gradable user attempts array
     */
    public static function question_based_grading_details($cmid, $mode, $slots, $qid) {
        $data = [];
        $cmod           = get_coursemodule_from_id('quiz', $cmid);
        $cmid           = $cmod->instance;
        $courseid       = $cmod->course;
        $context        = \context_course::instance($courseid);
        foreach ($slots as $slot) {
            list($qubaids, $count) = self::get_usage_ids_where_question_in_state($mode, $slot, $qid, $cmid, $cmod, $context);
            if (empty($qubaids)) {
                continue;
            }
            $count = $count;
            $attempts = self::load_attempts_by_usage_ids($qubaids, $cmid);
            foreach ($attempts as $key => $attempt) {
                $attempt->slot = $slot;
                $attempts[$key] = $attempt;
            }
            $data = array_merge(array_values($attempts), $data);
        }
        if (!get_config('block_edwiser_grader', 'studentnameoption')) {
            $anonymous = get_string('anonymous', 'block_edwiser_grader');
            $user = get_string('user', 'block_edwiser_grader');
            foreach ($data as $key => $value) {
                $value->firstname = $anonymous;
                $value->lastname = $user . ' ' . ($key + 1);
                $data[$key] = $value;
            }
        }
        return $data;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function question_based_grading_details_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Attempt Id'),
                    'slot' => new external_value(PARAM_INT, 'Slot number'),
                    'userid' => new external_value(PARAM_INT, 'User Id'),
                    'attempt' => new external_value(PARAM_INT, 'Attempt number'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                )
            )
        );
    }
    /**
     * Get question usage id's
     * @param  Text  $summarystate    Grade Summary State
     * @param  Integer  $slot         Slot
     * @param  Integer  $questionid   Question ID
     * @param  Integer  $cmid         Course module ID
     * @param  object  $cmod         Course Module
     * @param  object  $context      Course Context
     * @param  string  $orderby      Order by
     * @param  integer $page         Page
     * @param  integer  $pagesize    page size
     * @return Array Question usage id's
     */
    public static function get_usage_ids_where_question_in_state(
        $summarystate,
        $slot,
        $questionid = null,
        $cmid,
        $cmod,
        $context,
        $orderby = 'random',
        $page = 0,
        $pagesize = null
    ) {
        $dm = new \question_engine_data_mapper();
        if ($pagesize && $orderby != 'random') {
            $limitfrom = $page * $pagesize;
        } else {
            $limitfrom = 0;
        }

        $qubaids = question_based_grading::get_qubaids_condition($cmid, $cmod, $context);

        $params = array();
        if ($orderby == 'date') {
            list($statetest, $params) = $dm->in_summary_state_test(
                    'manuallygraded', false, 'mangrstate');
            $orderby = "(
                    SELECT MAX(sortqas.timecreated)
                    FROM {question_attempt_steps} sortqas
                    WHERE sortqas.questionattemptid = qa.id
                        AND sortqas.state $statetest
                    )";
        } else if ($orderby == 'studentfirstname' ||
            $orderby == 'studentlastname' ||
            $orderby == 'idnumber'
        ) {
            $qubaids->from .= " JOIN {user} u ON quiza.userid = u.id ";
            /* For name sorting, map orderby form value to
             Actual column names; 'idnumber' maps naturally */
            switch ($orderby) {
                case "studentlastname":
                    $orderby = "u.lastname, u.firstname";
                    break;
                case "studentfirstname":
                    $orderby = "u.firstname, u.lastname";
                    break;
            }
        }

        return $dm->load_questions_usages_where_question_in_state($qubaids, $summarystate,
                $slot, $questionid, $orderby, $params, $limitfrom, $pagesize);
    }
    /**
     * Get attempts by question usage id's
     * @param  Array $qubaids question usage id's
     * @param  Integer $quizid  quiz id
     * @return Arrayy         Array of attempts
     */
    public static function load_attempts_by_usage_ids($qubaids, $quizid) {
        global $DB;
        list($asql, $params) = $DB->get_in_or_equal($qubaids);
        $params[] = \quiz_attempt::FINISHED;
        $params[] = $quizid;
        $fields = 'quiza.id, quiza.userid, quiza.attempt, u.firstname, u.lastname';
        $attemptsbyid = $DB->get_records_sql("
                SELECT $fields
                FROM {quiz_attempts} quiza
                JOIN {user} u ON u.id = quiza.userid
                WHERE quiza.uniqueid $asql AND quiza.state = ? AND quiza.quiz = ?",
                $params);
        return $attemptsbyid;
    }
}

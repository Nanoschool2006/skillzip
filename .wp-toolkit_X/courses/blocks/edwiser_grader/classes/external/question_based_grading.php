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
 * Edwiser Grader Plugin question_based_grading trait.
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
use html_writer;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');
require_once($CFG->dirroot.'/mod/quiz/report/reportlib.php');

/**
 * Trait implementing the external function block_edwisergrader_question_based_grading.
 */
trait question_based_grading {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function question_based_grading_parameters() {
        return new external_function_parameters(
            array (
                'cmid' => new external_value(PARAM_INT, 'Course Module ID', VALUE_DEFAULT, 0),
                'includeauto' => new external_value(PARAM_TEXT, 'Include auto grading', VALUE_DEFAULT, false),
            )
        );
    }
    /**
     * Question based grading
     * @param  int  $cmid        Course Module Id
     * @param  int  $includeauto Include auto grading
     * @return Array             Array of grades.
     */
    public static function question_based_grading($cmid, $includeauto = false) {
        global $PAGE, $DB;
        $PAGE->set_context(\context_system::instance());
        $cmod           = get_coursemodule_from_id('quiz', $cmid);
        $cmid           = $cmod->instance;
        $courseid       = $cmod->course;
        $context        = \context_course::instance($courseid);
        $quiz           = $DB->get_record('quiz', array('id' => $cmid));
        $questions      = quiz_report_get_significant_questions($quiz);
        $statecounts    = self::get_question_state_summary(array_keys($questions), $cmid, $cmod, $context);
        $statecounts    = array_values($statecounts);
        $data           = array();

        foreach ($statecounts as $counts) {
            if ($counts->all == 0) {
                continue;
            }
            if (!$includeauto && $counts->needsgrading == 0 && $counts->manuallygraded == 0) {
                continue;
            }

            if (!empty($data[$counts->questionid])) {
                $row = $data[$counts->questionid];
                $row['slots'][$counts->slot] = true;
                $row['qgrade'] += $counts->needsgrading;
                $row['qupdategrade'] += $counts->manuallygraded;

                if ($includeauto) {
                    $row['qautograde'] += $counts->autograded;
                } else {
                    $row['qautograde'] += $counts->autograded;
                }

                $row['qgradeall'] += $counts->all;

                if (!empty($row['regradestatus'])) {
                    // Regrade Status.
                    list($qubaids, $count) = question_based_grading_details::get_usage_ids_where_question_in_state(
                        'all', $counts->slot, $counts->questionid, $cmid, $cmod, $context);
                    $regradestatus = self::is_regraded($qubaids, $counts->slot, $questions[$counts->slot]->maxmark);
                    $row['regradestatus'] = $regradestatus;
                }
            } else {
                $row = array();
                $row['qnum'] = $questions[$counts->slot]->number;
                $row['slots'] = [$counts->slot => true];
                $row['qtypeicon'] = $PAGE->get_renderer('question', 'bank')->qtype_icon($questions[$counts->slot]->type);
                $row['qname'] = format_string($counts->name);
                $row['qgrade'] = $counts->needsgrading;
                $row['qupdategrade'] = $counts->manuallygraded;

                if ($includeauto) {
                    $row['qautograde'] = $counts->autograded;
                } else {
                    $row['qautograde'] = $counts->autograded;
                }

                $row['qgradeall'] = $counts->all;

                // Regrade Status.
                list($qubaids, $count) = question_based_grading_details::get_usage_ids_where_question_in_state(
                    'all', $counts->slot, $counts->questionid, $cmid, $cmod, $context);
                $regradestatus = self::is_regraded($qubaids, $counts->slot, $questions[$counts->slot]->maxmark);
                $row['regradestatus'] = $regradestatus;
            }
            $data[$counts->questionid] = $row;
        }
        foreach ($data as $key => $row) {
            $slots = array_keys($row['slots']);
            $row['qgrade'] = self::format_count_for_table(
                $key,
                $slots,
                $row['qgrade'],
                'needsgrading',
                'grade'
            );
            $row['qupdategrade'] = self::format_count_for_table(
                $key,
                $slots,
                $row['qupdategrade'],
                'manuallygraded',
                'updategrade'
            );
            $row['qautograde'] = self::format_count_for_table(
                $key,
                $slots,
                $row['qautograde'],
                'autograded',
                'updategrade'
            );
            $row['qgradeall'] = self::format_count_for_table(
                $key,
                $slots,
                $row['qgradeall'],
                'all',
                'gradeall'
            );
            $data[$key] = $row;
        }
        return $data;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function question_based_grading_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'qnum' => new external_value(PARAM_RAW, 'question number'),
                    'qtypeicon' => new external_value(PARAM_RAW, 'question type icon'),
                    'qname' => new external_value(PARAM_RAW, 'question name'),
                    'qgrade' => new external_value(PARAM_RAW, 'question grade count and link as HTML'),
                    'qupdategrade' => new external_value(PARAM_RAW, 'question update grade count and link as HTML'),
                    'qautograde' => new external_value(PARAM_RAW, 'question auto graded count and link as HTML'),
                    'qgradeall' => new external_value(PARAM_RAW, 'question grade all count and link as HTML'),
                    'regradestatus' => new external_value(PARAM_BOOL, 'if the question needs regrading'),
                )
            )
        );
    }
    /**
     * Get question summary.
     * @param  Array $slots   Slots array
     * @param  Integer $cmid   Course module ID
     * @param  Object $cmod    Course Module
     * @param  Object $context Context
     * @return Array          Array of question state counts
     */
    public static function get_question_state_summary($slots, $cmid, $cmod, $context) {
        $dm = new \question_engine_data_mapper();
        return $dm->load_questions_usages_question_state_summary(
                self::get_qubaids_condition($cmid, $cmod, $context), $slots);
    }
    /**
     * Get qubaids join.
     * @param  Integer $cmid    Course module ID
     * @param  Object $cmod    Course module object
     * @param  Object $context Context
     * @return Text          qubaids join
     */
    public static function get_qubaids_condition($cmid, $cmod, $context) {
        $where = "quiza.quiz = :mangrquizid AND
                quiza.preview = 0 AND
                quiza.state = :statefinished";
        $params = array('mangrquizid' => $cmid, 'statefinished' => \quiz_attempt::FINISHED);
        $usersjoin = '';
        $currentgroup = groups_get_activity_group($cmod, true);
        $enrolleduserscount = count_enrolled_users($context,
                array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), $currentgroup);
        if ($currentgroup) {
            $userssql = get_enrolled_sql($context,
                    array('mod/quiz:reviewmyattempts', 'mod/quiz:attempt'), $currentgroup);
            if ($enrolleduserscount < 1) {
                $where .= ' AND quiza.userid = 0';
            } else {
                $usersjoin = "JOIN ({$userssql[0]}) AS enr ON quiza.userid = enr.id";
                $params += $userssql[1];
            }
        }
        return new \qubaid_join("{quiz_attempts} quiza $usersjoin ", 'quiza.uniqueid', $where, $params);
    }
    /**
     * format counts for showing in table.
     * @param  int    $questionid  Question id
     * @param  string $slots       Answered slots
     * @param  Object $counts      Counts object
     * @param  String $type        Question type
     * @param  String $gradestring Question grading type
     * @return HTML              Result HTML
     */
    public static function format_count_for_table($questionid, $slots, $counts, $type, $gradestring) {
        $result = $counts;
        if ($counts > 0) {
            $result = html_writer::link(
                '#',
                $counts . ' ' . get_string($gradestring, "quiz_grading"),
                array(
                    'data-slot' => implode(',', $slots),
                    'data-mode' => $type,
                    'data-qid' => $questionid,
                    'data-grade' => $gradestring
                )
            );

        }
        return $result;
    }

    /**
     * Check if attempt is graded or not
     * @param  array   $qubaids  Question usage ids
     * @param  int     $slot     Question slot
     * @param  float   $maxmarks Question Maximum marks
     * @return boolean           true if graded else false
     */
    public static function is_regraded($qubaids, $slot, $maxmarks) {
        foreach ($qubaids as $qusageid) {
            $regrade = get_attempt_questions::get_regraded_value($qusageid, $slot, $maxmarks);
            if ($regrade !== false) {
                return true;
            }
        }
        return false;
    }
}

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
 * Edwiser Grader Plugin get_course_quizzes trait.
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
use moodle_url;

require_once($CFG->libdir.'/externallib.php');

/**
 * Trait implementing the external function block_edwisergrader_get_course_quizzes.
 */
trait get_course_quizzes {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_course_quizzes_parameters() {
        return new external_function_parameters(
            array (
                'courseid' => new external_value(PARAM_INT, 'Course id.', VALUE_DEFAULT, 0),
                'moduleid' => new external_value(PARAM_INT, 'Quiz id', VALUE_DEFAULT, 0)
            )
        );
    }
    /**
     * Get course quizzes.
     * @param Integer $courseid                  Course Id
     * @param Integer $moduleid                  Module Id
     **/
    public static function get_course_quizzes($courseid, $moduleid) {
        global $CFG, $PAGE;
        $PAGE->set_context(\context_course::instance($courseid));
        // Get course quizzes.
        if ($moduleid != 0) {
            global $DB;
            $cm = get_coursemodule_from_id('quiz', $moduleid);
            $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
            $quiz->coursemodule = $moduleid;
            $quizzes['quizzes'][] = (array) $quiz;
        } else {
            $quizzes = \mod_quiz_external::get_quizzes_by_courses(array($courseid));
        }
        $result = array();
        foreach ($quizzes['quizzes'] as $quiz) {
            $allstudents = self::get_all_students($quiz['id']);
            $attemptscount = self::get_users_attempt_count($quiz['id']);
            if ($attemptscount) {
                $quizdata['quizname']   = $quiz['name'];
                $quizdata['quiztitle']   = format_text($quiz['name']);
                $quizdata['gradedusers'] = self::grade_users_count($quiz['id'], 'NOT');
                $quizdata['notgradedusers'] = self::grade_users_count($quiz['id']);
                $quizdata['notattemptedusers'] = $allstudents - $attemptscount;
                $quizdata['regrade'] = false;
                // Check if all users are graded or not.
                if ($quizdata['notgradedusers'] == 0) {
                    $quizdata['quizstatus'] = 'completed';
                    if ($quizdata['gradedusers'] != 0 ) {
                        $quizdata['regrade'] = true;
                    }
                } else {
                    $quizdata['quizstatus'] = 'pending';
                }
                $urlparams = array('gdm' => 'user', 'id' => $quiz['coursemodule']);
                $gradeurl = new moodle_url('/blocks/edwiser_grader/grader.php', $urlparams);
                $quizdata['gradeurl'] = $gradeurl->out(false);
                array_push($result, $quizdata);
            }
        }
        // Sort by quizstatus in descending order.
        usort($result, function($first, $second) {
            return $second['notgradedusers'] - $first['notgradedusers'];
        });
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_course_quizzes_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'quizname' => new external_value(PARAM_RAW, 'quiz name'),
                    'quiztitle' => new external_value(PARAM_RAW, 'quiz title'),
                    'gradedusers' => new external_value(PARAM_INT, 'graded users count'),
                    'notgradedusers' => new external_value(PARAM_INT, 'non graded users count'),
                    'notattemptedusers' => new external_value(PARAM_INT, 'non attempted users count'),
                    'regrade' => new external_value(PARAM_BOOL, 'regrade is true or false'),
                    'quizstatus' => new external_value(PARAM_TEXT, 'graded users status'),
                    'gradeurl' => new external_value(PARAM_RAW, 'grade page url for the quiz'),
                )
            )
        );
    }
    /**
     * Get count of users which are graded or not graded.
     * @param Integer $quizid Quiz ID
     * @param String $gradeinfo Gradeinfo whether to add NULL or not in query
     * @return Integer count of users
     */
    public static function grade_users_count($quizid, $gradeinfo = '') {
        global $DB;
        $params[] = $quizid;
        // Query to get the count of users for graded added NULL with $gradeinfo.
        $sql = "SELECT COUNT(DISTINCT(u.id)) as users
        FROM {user} u
        JOIN {quiz_attempts} qa ON qa.userid = u.id
        WHERE qa.quiz = ? AND qa.state = 'finished' AND qa.preview = 0 AND qa.sumgrades IS ".$gradeinfo." NULL";

        $data = $DB->get_record_sql($sql, $params);
        return $data->users;
    }
    /**
     * Get users count who have attempted the quiz
     * @param Integer $quizid Quiz ID
     * @return Integer Count of users
     */
    public static function get_users_attempt_count($quizid) {
        global $DB;
        $params[] = $quizid;
        // Query to get the count of users.
        $sql = "SELECT COUNT(DISTINCT(u.id)) as users
        FROM {user} u
        JOIN {quiz_attempts} qa ON qa.userid = u.id
        WHERE qa.quiz = ? AND qa.preview = 0";

        $data = $DB->get_record_sql($sql, $params);
        return $data->users;
    }

    /**
     * Get count of users who are enrolled as students
     * @param  Integer $quizid  Quiz id.
     * @return Integer Count of users
     */
    public static function get_all_students($quizid) {
        global $DB;
        $sql = "SELECT COUNT(u.id) as nausers
            FROM {course} c
            JOIN {context} ctx ON c.id = ctx.instanceid AND ctx.contextlevel = ?
            JOIN {enrol} e ON c.id = e.courseid
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            JOIN {user} u ON ue.userid = u.id
            JOIN {role_assignments} ra ON ctx.id = ra.contextid AND u.id = ra.userid
            JOIN {role} r ON ra.roleid = r.id AND r.shortname = 'student'
            JOIN {quiz} q ON c.id = q.course
            WHERE q.id = ?";
        $params = array(CONTEXT_COURSE, $quizid);
        $result = $DB->get_record_sql($sql, $params);
        return $result->nausers;
    }
}

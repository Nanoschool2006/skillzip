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
 * Edwiser Grader Plugin get_question_details trait.
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
use core_user;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');
require_once($CFG->dirroot . '/blocks/edwiser_grader/comment_form.php');
require_once('get_attempt_questions.php');

/**
 * Trait implementing the external function block_edwisergrader_get_attempt_questions.
 */
trait get_question_details {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_question_details_parameters() {
        return new external_function_parameters(
            array (
                'slot' => new external_value(PARAM_INT, 'Question Number.', VALUE_DEFAULT, 0),
                'attemptid' => new external_value(PARAM_INT, 'Attempt ID.', VALUE_DEFAULT, 0),
                'cmid' => new external_value(PARAM_INT, 'Course Module ID.', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Get question details.
     * @param Integer $slot Question Number
     * @param Integer $attemptid Attempt Id
     * @param Integer $cmid COurse module Id
     **/
    public static function get_question_details($slot, $attemptid, $cmid) {
        global $PAGE, $CFG;

        // To remove the warning for set_url since we will call render_question_for_commenting to get the HTML
        // content for question, answer and comments.
        $PAGE->set_url($CFG->wwwroot."/blocks/edwiser_grader/grader.php");

        $PAGE->set_context(\context_system::instance());

        $requirements = new \block_edwiser_grader\page_requirements();

        $attemptobj = quiz_create_attempt_handling_errors($attemptid, $cmid);
        $result['quedata']    = $attemptobj->render_question_for_commenting($slot);
        $result['jscode']     = $requirements->get_grader_end_code();
        $result['quename']    = $attemptobj->get_quiz_name();
        $usergrade            = $attemptobj->get_question_mark($slot);
        $questionmaxgrade     = $attemptobj->get_question_attempt($slot)->get_max_mark();
        $gradestate           = get_attempt_questions::get_question_status($attemptobj, $slot, 1);
        $result['gradestate'] = self::get_question_grade_state($gradestate);
        $result['questiontype'] = $attemptobj->get_question_type_name($slot);
        if (!get_config('block_edwiser_grader', 'studentnameoption')) {
            $result['user'] = '';
        } else {
            $user = core_user::get_user($attemptobj->get_userid());
            $result['user'] = ucfirst($user->firstname).' '.ucfirst($user->lastname);
        }
        $result['marks'] = get_attempt_questions::calculate_question_grade($usergrade, $questionmaxgrade, $gradestate);
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_question_details_returns() {
        return new \external_single_structure(
            array(
                'user' => new external_value(PARAM_TEXT, 'User name'),
                'jscode' => new external_value(PARAM_RAW, 'Js code to initialize'),
                'quedata' => new external_value(PARAM_RAW, 'Question Data'),
                'quename' => new external_value(PARAM_TEXT, 'Question name'),
                'gradestate' => new external_value(PARAM_RAW, 'Grade State'),
                'questiontype' => new external_value(PARAM_RAW, 'Question Type'),
                'marks' => new external_value(PARAM_RAW, 'Marks')
            )
        );
    }
    /**
     * Get question grade state.
     * @param  string $gradestate Grade state
     * @return string             Grade state
     */
    public static function get_question_grade_state($gradestate) {
        if ($gradestate == 'partiallycorrect') {
            $gradestate = 'partially correct';
        } else if ($gradestate == 'notanswered') {
            $gradestate = 'incorrect';
        } else if ($gradestate == 'requiresgrading') {
            $gradestate = 'requires grading';
        }
        return ucwords($gradestate);
    }
}

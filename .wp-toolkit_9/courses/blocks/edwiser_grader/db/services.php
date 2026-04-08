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
 * Edwiser Grader Plugin
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'block_edwiser_grader_get_course_quizzes' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_course_quizzes',
        'classpath' => '',
        'description' => 'Gets all the quizzes from course',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_not_graded_attempts' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_not_graded_attempts',
        'classpath' => '',
        'description' => 'Gets all the not graded attempts of the quiz for all users',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_graded_attempts' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_graded_attempts',
        'classpath' => '',
        'description' => 'Gets all the graded attempts of the quiz for all users',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_attempt_questions' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_attempt_questions',
        'classpath' => '',
        'description' => 'Gets all questions of user quiz attempt',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_question_details' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_question_details',
        'classpath' => '',
        'description' => 'Gets question details from user attempted quiz',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_display_grade_chart' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'display_grade_chart',
        'classpath' => '',
        'description' => 'Displays the grade chart',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_grade_question' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'grade_question',
        'classpath' => '',
        'description' => 'Grade the attempted question',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_quiz_attempts' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_quiz_attempts',
        'classpath' => '',
        'description' => 'Get quiz attempts',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_delete_quiz_attempt' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'delete_quiz_attempt',
        'classpath' => '',
        'description' => 'Delete the selected attempt.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_regrade_quiz_attempt' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'regrade_quiz_attempt',
        'classpath' => '',
        'description' => 'Regrade the selected attempt.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_dry_run_regrade' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'dry_run_regrade',
        'classpath' => '',
        'description' => 'Dry run the regraded attempt.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_question_based_grading' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'question_based_grading',
        'classpath' => '',
        'description' => 'Question based grading.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_question_based_grading_details' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'question_based_grading_details',
        'classpath' => '',
        'description' => 'Question based grading details.',
        'type' => 'write',
        'ajax' => true,
    ],
    'block_edwiser_grader_get_licensed_users' => [
        'classname' => 'block_edwiser_grader\external\api',
        'methodname' => 'get_licensed_users',
        'classpath' => '',
        'description' => 'Get Licensed users according to selected site.',
        'type' => 'write',
        'ajax' => true,
    ],
];

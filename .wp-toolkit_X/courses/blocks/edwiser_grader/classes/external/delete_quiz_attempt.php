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
 * Edwiser Grader Plugin delete_quiz_attempt trait.
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
 * Trait implementing the external function block_edwisergrader_delete_quiz_attempt.
 */
trait delete_quiz_attempt {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function delete_quiz_attempt_parameters() {
        return new external_function_parameters(
            array (
                'attempts' => new external_value(PARAM_RAW, 'Selected Attempt Ids.', VALUE_DEFAULT, 0),
                'quizid' => new external_value(PARAM_INT, 'Quiz ID.', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Delete Quiz Attempt
     * @param Integer $attempts Array of attempt id's
     * @param Integer $quizid Quiz ID
     **/
    public static function delete_quiz_attempt($attempts, $quizid) {
        $attempts = json_decode($attempts);
        $cmod   = get_coursemodule_from_id('quiz', $quizid);
        $cmid   = $cmod->instance;
        $quiz   = \quiz_access_manager::load_quiz_and_settings($cmid);
        foreach ($attempts as $attempt) {
            quiz_delete_attempt($attempt, $quiz);
        }
        $result['delete'] = get_string('deletedsuccessmsg', block_edwiser_grader);
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function delete_quiz_attempt_returns() {
        return new \external_single_structure(
            array(
                'delete' => new external_value(PARAM_RAW, 'Delete Attempt'),
            )
        );
    }
}

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
 * Edwiser Grader Plugin get_quiz_attempts trait.
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
trait get_quiz_attempts {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_quiz_attempts_parameters() {
        return new external_function_parameters(
            array (
                'quizid' => new external_value(PARAM_INT, 'Quiz ID.', VALUE_DEFAULT, 0),
                'selectedattemptsfrom' => new external_value(PARAM_TEXT, 'Selected Attempts From', VALUE_DEFAULT, 0),
                'selectedattempts' => new external_value(PARAM_TEXT, 'Selected Attempts Filters', VALUE_DEFAULT, 0),
                'username' => new external_value(PARAM_RAW, 'Name of user', VALUE_DEFAULT, ''),
                'needsregrade' => new external_value(PARAM_TEXT, 'Needs Regrading option', VALUE_DEFAULT, ''),
            )
        );
    }
    /**
     * Get quiz attempts
     * @param  Integer $quizid               [description]
     * @param  String  $selectedattemptsfrom Selected Attempt Filter
     * @param  String  $selectedattempts     Selected state filter
     * @param  string  $username             User Name
     * @param  Boolean  $needsregrade        Show regarded attempts enabled or not.
     * @return Array Array of grade data.
     */
    public static function get_quiz_attempts($quizid, $selectedattemptsfrom, $selectedattempts, $username, $needsregrade) {
        $cmod               = get_coursemodule_from_id('quiz', $quizid);
        $cmid               = $cmod->instance;
        $result             = array();
        $gradedcount = $nongradedcount = 0;
        if ($selectedattemptsfrom != 'unaq') {
            $gradedcount        = get_quiz_users_count($cmid, $selectedattemptsfrom,
            $selectedattempts, 'NOT', $username, $needsregrade);
        }
        $nongradedcount     = get_quiz_users_count($cmid, $selectedattemptsfrom, $selectedattempts, '', $username, $needsregrade);
        $result['gradedcount']      = $gradedcount;
        $result['nongradedcount']   = $nongradedcount;
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_quiz_attempts_returns() {
        return new \external_single_structure(
            array(
                'gradedcount' => new external_value(PARAM_INT, 'Graded Count'),
                'nongradedcount' => new external_value(PARAM_INT, 'Non Graded Count'),
            )
        );
    }
}

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
 * Edwiser Grader Plugin get_not_graded_attempts trait.
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
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/blocks/edwiser_grader/lib.php');

/**
 * Trait implementing the external function block_edwisergrader_get_not_graded_attempts.
 */
trait get_not_graded_attempts {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_not_graded_attempts_parameters() {
        return new external_function_parameters(
            array (
                'quizid' => new external_value(PARAM_INT, 'Quiz ID.', VALUE_DEFAULT, 0),
                'selectedattemptsfrom' => new external_value(PARAM_TEXT, 'Selected Attempts From', VALUE_DEFAULT, 0),
                'selectedattempts' => new external_value(PARAM_TEXT, 'Selected Attempts Filters', VALUE_DEFAULT, 0),
                'page' => new external_value(PARAM_INT, 'Page.', VALUE_DEFAULT, 1),
                'username' => new external_value(PARAM_RAW, 'Name of user', VALUE_DEFAULT, ''),
                'sortfilter' => new external_value(PARAM_INT, 'Sorting order', VALUE_DEFAULT, 1),
                'soap' => new external_value(PARAM_TEXT, 'At most one finished attempt option', VALUE_DEFAULT, ''),
                'needsregrade' => new external_value(PARAM_TEXT, 'Needs Regrading option', VALUE_DEFAULT, ''),
            )
        );
    }
    /**
     * Get non graded attempts.
     * @param  Integer  $quizid               Quiz ID
     * @param  String  $selectedattemptsfrom Selected Attempt Filter
     * @param  String  $selectedattempts     Selected state filter
     * @param  Integer $page                 Page Number
     * @param  string  $username             User Name
     * @param  integer $sortfilter           Sort Filter
     * @param  Boolean  $soap                Show at most one finished attempt enabled or not.
     * @param  Boolean  $needsregrade        Shoe regarded attempts enabled or not.
     * @return String[]                      Array of user data.
     */
    public static function get_not_graded_attempts($quizid, $selectedattemptsfrom,
        $selectedattempts, $page = 1, $username = '', $sortfilter = 1, $soap, $needsregrade) {
        global $DB, $PAGE;
        $PAGE->set_context(\context_system::instance());
        $cmod   = get_coursemodule_from_id('quiz', $quizid);
        $cmid   = $cmod->instance;
        $limitfrom   = $page - 1;
        $limitfrom   = $limitfrom * 5;
        // Get all users who have attemped the quiz.
        $users       = get_quiz_attempted_users($cmid, $selectedattemptsfrom,
        $selectedattempts, $limitfrom, '', $username, $sortfilter, $needsregrade);
        $result = array();
        foreach ($users as $user) {
            $userinfo       = $DB->get_record('user', array('id' => $user));
            // Get attempts made by user.
            $attempts       = get_user_quiz_attempts($userinfo->id, $cmid, $selectedattempts, '', $soap, $needsregrade);
            if (count($attempts) != 0 || $selectedattemptsfrom == 'unaq' || $selectedattemptsfrom == 'au') {
                $studnameoption         = get_config('block_edwiser_grader', 'studentnameoption');
                $userdata['userid']         = $userinfo->id;
                $userdata['attemptdetails'] = array_values($attempts);
                // Check if student name display settings is enabled or not.
                if ($studnameoption) {
                    $userdata['username']       = ucfirst($userinfo->firstname).' '.ucfirst($userinfo->lastname);
                    $userimg                    = new \user_picture($userinfo);
                    $userimg->size              = 100;
                    $imagelink                  = $userimg->get_url($PAGE)->out(false);
                    $userdata['userimg']        = $imagelink;
                    $userdata['useremail']      = $userinfo->email;
                } else {
                    $userdata['username']       = '';
                    $userdata['userimg']        = '';
                    $userdata['useremail']      = '';
                }
                $userdata['status']         = 'notgraded';
                array_push($result, $userdata);
                unset($attempts);
            }
        }
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_not_graded_attempts_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                array(
                    'userid' => new external_value(PARAM_INT, 'user id'),
                    'username' => new external_value(PARAM_RAW, 'user name'),
                    'userimg' => new external_value(PARAM_RAW, 'user profile image'),
                    'useremail' => new external_value(PARAM_RAW, 'user email'),
                    'status' => new external_value(PARAM_TEXT, 'status'),
                    'attemptdetails' => new \external_multiple_structure(
                        new \external_single_structure(
                            array(
                                'attemptid' => new external_value(PARAM_INT, 'attempt id'),
                                'attemptnumber' => new external_value(PARAM_INT, 'attempt number'),
                                'timestart' => new external_value(PARAM_RAW, 'quiz start time'),
                                'timefinish' => new external_value(PARAM_RAW, 'quiz finish time'),
                                'timetaken' => new external_value(PARAM_RAW, 'quiz taken time'),
                                'classname' => new external_value(PARAM_TEXT, 'classname'),
                                'grade' => new external_value(PARAM_TEXT, 'grade'),
                            )
                        )
                    ),
                )
            )
        );
    }
}

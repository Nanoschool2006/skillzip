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
 * Edwiser Grader Plugin get_licensed_users trait.
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
require_once($CFG->dirroot . '/blocks/edwiser_grader/classes/license_controller.php');

/**
 * Trait implementing the external function block_edwisergrader_get_attempt_questions.
 */
trait get_licensed_users {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function get_licensed_users_parameters() {
        return new external_function_parameters(
            array (
                'selectedsite' => new external_value(PARAM_TEXT, 'Selected Site', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Get licensed users from edwiser site.
     * @param  string $selectedsite Selected licensed site
     * @return array                Users list
     */
    public static function get_licensed_users($selectedsite) {
        global $PAGE, $CFG;
        $siteurl = parse_url($CFG->wwwroot, PHP_URL_HOST).''.parse_url($CFG->wwwroot, PHP_URL_PATH);
        $lcontroller = new \edwiser_grader_license_controller();
        $lusers = $lcontroller->edd_get_users_from_api();
        $users = array();
        foreach ($lusers->users as $key => $user) {
            if ($user->site == $selectedsite) {
                $user->key = $key;
                if ($user->site !== $siteurl) {
                    $user->userdisable = 'disabled';
                } else {
                    $user->userdisable = '';
                }
                array_push($users, $user);
            }
        }
        $context['lusers'] = $users;
        return $context;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function get_licensed_users_returns() {
        return new \external_single_structure(
            array(
                'lusers' => new \external_multiple_structure(
                    new \external_single_structure(
                        array(
                            'site' => new external_value(PARAM_RAW, 'attempt id'),
                            'user' => new external_value(PARAM_RAW, 'attempt number'),
                            'key' => new external_value(PARAM_RAW, 'quiz start time'),
                            'userdisable' => new external_value(PARAM_RAW, 'quiz start time'),
                        )
                    )
                )
            )
        );
    }
}

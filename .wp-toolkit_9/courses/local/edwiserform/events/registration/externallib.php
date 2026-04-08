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
 * Registraion event external service
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace edwiserformevents_registration\external;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . "/local/edwiserform/events/registration/locallib.php");
use external_api;
use external_value;
use external_function_parameters;
use external_single_structure;

/**
 * Service definition for user registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registration_api extends \external_api {

    /**
     * Describes the parameters for registration action
     * @return external_function_parameters
     * @since  Edwiser Forms 1.3.2
     */
    public static function registration_action_parameters() {
        return new external_function_parameters(
            [
                "formid" => new external_value(PARAM_INT, "Form id", VALUE_REQUIRED),
                "userid" => new external_value(PARAM_INT, "User id", VALUE_REQUIRED),
                "action" => new external_value(PARAM_INT, "Action to perform on user submission", VALUE_DEFAULT, "enrol")
            ]
        );
    }

    /**
     * Enrol/Unenrol user in courses list saved in the form
     * @param  int    $formid Id of form
     * @param  int    $userid Id of user
     * @param  string $action Action (enrol|unenrol)
     * @return array          Response of enrolement or unregistration action
     * @since  Edwiser Form 1.3.2
     */
    public static function registration_action($formid, $userid, $action) {
        $registration = new \edwiserform_events_registration();
        return $registration->process_registration_action($userid, $action);
    }

    /**
     * Returns description of method parameters for registration action
     * @return external_single_structure
     * @since  Edwiser Form 1.3.2
     */
    public static function registration_action_returns() {
        return new external_single_structure(
            [
                "status" => new external_value(PARAM_BOOL, "Form deletion status."),
                "msg"    => new external_value(PARAM_TEXT, "Form deletion message."),
                "type"   => new external_value(PARAM_INT, "Visible action after updation")
            ]
        );
    }
}

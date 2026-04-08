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
 * External assign API
 *
 * @package    mod_edwiserform
 * @since      Edwiser Form Activity 1.0
 * @copyright  Wisdmlabs 2019
 * @author     Yogesh Shirsath
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/user/externallib.php");
require_once("$CFG->dirroot/mod/assign/locallib.php");

/**
 * Assign functions
 * @copyright Wisdmlabs 2019
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_edwiserform_external extends external_api {

    /**
     * Describes the parameters for form_submitted.
     *
     * @return external_function_parameters
     * @since  Edwiser Form Activity 1.0
     */
    public static function form_submitted_parameters() {
        return new external_function_parameters (
            array(
                'cmid' => new external_value(PARAM_INT, 'course module instance id')
            )
        );
    }

    /**
     * Update the module completion status.
     *
     * @param int $assignid assign instance id
     * @return array of warnings and status result
     * @since  Edwiser Form Activity 1.0
     */
    public static function form_submitted($cmid) {
        global $USER;
        list($course, $cm) = get_course_and_cm_from_cmid($cmid);
        $completion = new completion_info($course);
        if ($active = $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
            $data = $completion->get_data($cm, false, $USER->id);
            $data->completionstate = $active;
            $completion->internal_set_data($cm, $data);
        }
    }

    /**
     * Describes the form_submitted return value.
     *
     * @return external_single_structure
     * @since  Edwiser Form Activity 1.0
     */
    public static function form_submitted_returns() {
        return null;
    }
}

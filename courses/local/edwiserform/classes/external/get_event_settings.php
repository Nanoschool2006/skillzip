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
 * Trait for get event settings service.
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace local_edwiserform\external;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\controller;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * Service definition for get event settings.
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait get_event_settings {

    /**
     * Describes the parameters for get event settings.
     * @return external_function_parameters
     * @since  Edwiser Forms 1.0.0
     */
    public static function get_event_settings_parameters() {
        return new external_function_parameters(
            [
                "event" => new external_value(PARAM_TEXT, "Enable or disable login page", VALUE_REQUIRED),
                "id" => new external_value(PARAM_INTEGER, "Form id", VALUE_DEFAULT, 0)
            ]
        );
    }

    /**
     * Fetch events settings render and send html settings to admin.
     * @param  string  $event The name of event to fetch
     * @param  integer $id    Form id
     * @return array          array[status, settings]
     * @since  Edwiser Form 1.0.0
     */
    public static function get_event_settings($event, $id = false) {
        global $DB, $CFG;

        $controller = controller::instance();

        $responce = array(
            "status" => false,
            "settings" => get_string('efb-settings-event-not-found', 'local_edwiserform')
        );
        $plugin = $controller->get_plugin($event);
        if ($plugin != null && $plugin->has_settings()) {

            // Start output buffer because plugin is going to echo the settings html.
            ob_start();
            $plugin->get_settings($id);
            $responce = array(
                'status' => true,
                "settings" => ob_get_clean()
            );
        }
        return $responce;
    }

    /**
     * Returns description of method parameters for get event settings.
     * @return external_single_structure
     * @since  Edwiser Form 1.0.0
     */
    public static function get_event_settings_returns() {
        return new external_single_structure(
            [
                "status" => new external_value(PARAM_BOOL, "Login form enable/disable status"),
                "settings"    => new external_value(PARAM_RAW, "Login form enable/disable operation message")
            ]
        );
    }
}

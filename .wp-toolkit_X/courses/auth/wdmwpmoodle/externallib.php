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
 * External course participation api.
 *
 * This api is mostly read only, the actual enrol and unenrol
 * support is in each enrol plugin.
 *
 * @category   external
 *
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once "$CFG->libdir/externallib.php";

/**
 * Manual enrolment external functions.
 *
 * @category   external
 *
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @since Moodle 2.2
 */
class auth_sso_token_verify_external extends external_api
{
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token_parameters()
    {
        return new external_function_parameters(
            array(
                'token' => new external_value(PARAM_TEXT, 'Token to verify'),
            )
        );
    }

    /**
     * Returns description of method parameters.
     *
     * @return bool
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token($token)
    {
        $params = self::validate_parameters(
            self::wdm_sso_verify_token_parameters(),
            array('token' => $token)
        );
        $responce = array('success' => false,'msg' => 'Invalid token provided,please check token and try again');
        $mdl_key = get_config('auth_wdmwpmoodle', 'sharedsecret');
        if ($params['token'] == $mdl_key) {
            $responce['success'] = true;
            $responce['msg'] = 'Token verified successfully';
        }

        return $responce;
    }

    /**
     * Returns description of method parameters.
     *
     * @return bool
     *
     * @since SSO 1.2.1
     */
    public static function wdm_sso_verify_token_returns()
    {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'true if the token matches otherwise false'),
                'msg' => new external_value(PARAM_RAW, 'Sucess faile message'),
                )
        );
    }
}

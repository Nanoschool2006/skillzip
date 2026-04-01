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
 * Trait for get form definition service.
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
use external_multiple_structure;
use external_value;
use html_writer;
use moodle_url;
use context_system;

/**
 * Service definition for get form definition
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait get_form_definition {

    /**
     * Describes the parameters for get form definition
     * @return external_function_parameters
     * @since  Edwiser Forms 1.0.0
     */
    public static function get_form_definition_parameters() {
        return new external_function_parameters(
                array(
            'form_id' => new external_value(PARAM_INT, 'Form id found in shortcode.', VALUE_REQUIRED),
            'countries' => new external_value(PARAM_BOOL, 'If true then countries values will be sent', VALUE_DEFAULT, false)
                )
        );
    }

    /**
     * Fetch form definition from database attach user's submission, common profile data and send it in response
     * @param  integer $formid if of the form
     * @return array   [status, title, definition, formtype, style, action, data, msg]
     * @since  Edwiser Form 1.2.0
     */
    public static function get_form_definition($formid, $countries) {
        global $DB, $CFG;

        $controller = controller::instance();

        $response = array(
            'status' => false,
            'title' => '',
            'definition' => '',
            'formtype' => 'blank',
            'style' => 1,
            'action' => '',
            'data' => '',
            'visibleurl' => '',
            'msg' => get_string("efb-form-not-found", "local_edwiserform", ''.$formid)
        );
        if ($formid > 0) {
            $form = $DB->get_record('efb_forms', array('id' => $formid));

            // If form id is invalid then returning false response.
            if (!$form || $form->deleted) {
                return $response;
            }

            // If form is not enabled then returning response with form not enabled message.
            if (!$form->enabled) {
                $response['msg'] = get_string("efb-form-not-enabled", "local_edwiserform", ''.$form->title);
                return $response;
            }
            $response["form_id"] = $formid;
            $plugin = null;
            if ($form->type != 'blank') {
                $plugin = $controller->get_plugin($form->type);
            }
            if (!$controller->is_logged_and_not_guest()) {

                // Checking whether selected form type XYZ can be viewed while user is not logged in.
                // If no then returning response with login to use form.
                if ($form->type == 'blank' || $plugin->login_required()) {
                    $link = html_writer::link(
                        new moodle_url($CFG->wwwroot . "/login/index.php"),
                        get_string("efb-form-loggedin-required-click", "local_edwiserform")
                    );
                    $response["msg"] = get_string("efb-form-loggedin-required", "local_edwiserform", $link);
                    return $response;
                }
            } else {

                // Checking whether selected form type XYZ can be viewed while user is logged in.
                // If no then returning response with not allowed while logged in.
                if ($form->type != 'blank' && !$plugin->login_allowed()) {
                    $response["msg"] = get_string("efb-form-loggedin-not-allowed", "local_edwiserform", $CFG->wwwroot);
                    $response["msg"] .= self::live_demo_form_message();
                    return $response;
                }
            }
            $response["formtype"] = $form->type;
            $response["title"] = $form->title;
            self::validate_form($form, $plugin, $response);
            if ($form->type != 'blank') {

                // This feature is going to add in future update. Whether form is going to submit data to external url.
                $response['action']  = $plugin->get_action_url();
            }
            $response['style'] = $form->style;
        }
        if ($countries == true) {
            $countries = get_string_manager()->get_list_of_countries();
            $response['countries'] = json_encode($countries);
        }
        return $response;
    }

    /**
     * Get live demo message
     * @return string Message
     * @since  Edwiser Form 1.2.0
     */
    public static function live_demo_form_message() {

        $controller = controller::instance();

        if ($controller->can_create_or_view_form(false, true)) {
            return get_string('livedemologgedinmessage', 'local_edwiserform');
        }
        return '';
    }

    /**
     * Validate whether whether user can submit data into form and attach previously submitted data.
     * @param  stdClass $form     Standard class object of form with main settings
     * @param  object   $plugin   Object of selected form type
     * @param  array    $response Reference array with [status, title, definition, formtype, action, data, msg]
     * @return array              [status, title, definition, formtype, action, data, msg]
     * @since  Edwiser Form 1.1.0
     */
    public static function validate_form($form, $plugin, &$response) {
        global $CFG;

        $controller = controller::instance();

        if ($form->allowsubmissionsfromdate > 0 && time() < $form->allowsubmissionsfromdate) {
            $response['msg'] = get_string(
                'submissionbeforedate',
                'local_edwiserform',
                date('F j, Y, g:i a', $form->allowsubmissionsfromdate)
            );
            return;
        }
        if ($form->allowsubmissionstodate > 0 && time() > $form->allowsubmissionstodate) {
            $response['msg'] = get_string(
                'submissionafterdate',
                'local_edwiserform',
                date('F j, Y, g:i a', $form->allowsubmissionstodate)
            );
            return;
        }
        // Check that user can save data into form or not.
        $canuser = $controller->can_save_data($form, $plugin);
        switch ($canuser['status']) {
            case 0:
                // User previously submitted data into form but admin disabled user from re-submitting data.
                $response["msg"] = get_string("efb-form-submission-found", "local_edwiserform", $CFG->wwwroot);
                break;
            case 2:
                // User previously submitted data into form and can re-submit data to edit previous submission.
                $response["data"] = $canuser["data"];
            case 1:
                // User can submit data into form.
                $response["definition"] = $form->definition;
                $response["msg"] = get_string("efb-form-definition-found", "local_edwiserform");
                $response["status"] = true;
                break;
            default:
                $response["msg"] = get_string("efb-unknown-error", "local_edwiserform");
                break;
        }
        if ($form->type != 'blank') {
            // Attaching extra data to the form data.
            $response['data'] = $plugin->attach_data($form, $response["data"]);
        } else {
            $events = $controller->get_events_base_plugin();
            $response['data'] = $events->attach_common_data($form, $response["data"]);
        }
        $response['data'] = self::add_file_url($response['data']);
        if ($response['data'] == null) {
            $response['data'] = '';
        }
        return $response;
    }

    /**
     * Check for input with type file and add file url to data
     *
     * @param  string $data
     * @return string $data
     * @since  Edwiser Form 1.1.0
     */
    public static function add_file_url($data) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/moodlelib.php');
        $data = json_decode($data);
        if (!is_array($data)) {
            return json_encode($data);
        }
        foreach ($data as $key => $input) {
            if (isset($input->type) && $input->type == "file") {
                if ($input->value != 0) {
                    $fs = get_file_storage();
                    if ($files = $fs->get_area_files(
                        context_system::instance()->id,
                        EDWISERFORM_COMPONENT,
                        EDWISERFORM_USER_FILEAREA,
                        $input->value
                    )) {
                        foreach ($files as $file) {
                            if ($file->get_filename() != '.') {
                                break;
                            }
                        }
                        if ($file->get_filename() != '.') {
                            $data[$key]->value = moodle_url::make_pluginfile_url(
                                $file->get_contextid(),
                                EDWISERFORM_COMPONENT,
                                EDWISERFORM_USER_FILEAREA,
                                $input->value,
                                '/',
                                $file->get_filename()
                            )->__toString();
                            $data[$key]->filename = $file->get_filename();
                            $data[$key]->fileitemid = $file->get_itemid();
                        }
                        continue;
                    }
                    $data[$key]->value = 0;
                }
            }
        }
        return json_encode($data);
    }

    /**
     * Returns description of method parameters for get form definition
     * @return external_single_structure
     * @since  Edwiser Form 1.1.0
     */
    public static function get_form_definition_returns() {
        return new external_single_structure(
            [
                'status' => new external_value(PARAM_BOOL, 'Form status.'),
                'title' => new external_value(PARAM_TEXT, 'Form title'),
                'definition'    => new external_value(PARAM_RAW, 'Form data or message.'),
                'formtype' => new external_value(PARAM_TEXT, 'Form type.'),
                'style' => new external_value(PARAM_INT, 'Form style.'),
                'action' => new external_value(PARAM_TEXT, 'Form action'),
                'visibleurl' => new external_value(PARAM_RAW, 'Url to show in address bar'),
                'data' => new external_value(PARAM_TEXT, 'Form data if previous submission present'),
                'msg' => new external_value(PARAM_RAW, 'Form definition status'),
                'countries' => new external_value(PARAM_TEXT, 'Countries list', VALUE_DEFAULT, '')
            ]
        );
    }

}

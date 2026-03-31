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
 * Trait for create_new_form service
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 * @author    Sudam
 */

namespace local_edwiserform\external;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\controller;
use external_single_structure;
use external_function_parameters;
use external_value;
use stdClass;
use context_system;

/**
 * Service definition for create new form
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait create_new_form {

    /**
     * Returns the functional parameter for create and update form methods.
     * @param  boolean                      $id          true if want to add id in parameters
     * @param  boolean                      $forceupdate If true then add forceupdate to settings array
     * @return external_function_parameters              Functional parameters
     * @since  Edwiser Form 1.2.1
     */
    public static function get_create_update_form_parameters($id = false, $forceupdate = false) {
        $settings = array(
            'title' => new external_value(PARAM_TEXT, 'Form title', VALUE_REQUIRED),
            'description' => new external_value(PARAM_TEXT, 'Form description.', VALUE_DEFAULT, ''),
            'data_edit' => new external_value(PARAM_BOOL, 'Is form editable. Boolean true/flase', VALUE_REQUIRED),
            'type' => new external_value(PARAM_TEXT, 'Type of the form', VALUE_REQUIRED),
            'events' => new external_value(PARAM_RAW, 'Events selected', VALUE_DEFAULT, ''),
            'enable_notifi_email' => new external_value(PARAM_BOOL, 'Enable or disable email notification', VALUE_DEFAULT, ''),
            'notifi_email' => new external_value(
                PARAM_TEXT,
                'Notification email address. This value is required if the form type is contact us',
                VALUE_DEFAULT,
                ''
            ),
            'notifi_email_subject' => new external_value(PARAM_RAW, 'Email subject', VALUE_DEFAULT, ''),
            'notifi_email_body' => new external_value(PARAM_RAW, 'Email body', VALUE_DEFAULT, ''),
            "emailbodydraftitemid" => new external_value(PARAM_INT, 'Draft item id for email body', VALUE_DEFAULT, ''),
            'confirmation_subject' => new external_value(PARAM_RAW, 'Confirmation subject', VALUE_DEFAULT, ''),
            'confirmation_message' => new external_value(
                PARAM_RAW,
                'Message to show after successfull submission',
                VALUE_DEFAULT,
                ''
            ),
            "msgdraftitemid" => new external_value(PARAM_INT, 'Draft item id for message', VALUE_DEFAULT, ''),
            'eventsettings' => new external_value(PARAM_RAW, 'Event settings', VALUE_DEFAULT, ''),
            'style' => new external_value(PARAM_INT, 'Style selected for form', VALUE_DEFAULT, ''),
            'allowsubmissionsfromdate' => new external_value(PARAM_INT, 'Date from form will be visible'),
            'allowsubmissionstodate' => new external_value(PARAM_INT, 'Date thru form will be visible')
        );
        if ($id == true) {
            $settings['id'] = new external_value(PARAM_INT, 'Form id', VALUE_REQUIRED);
        }
        if ($forceupdate == true) {
            $settings['forceupdate'] = new external_value(PARAM_BOOL, 'Forcefully update form definition', VALUE_DEFAULT, false);
        }
        return new external_function_parameters(
            array(
                "setting" => new external_single_structure(
                    $settings
                ),
                'formdef' => new external_value(PARAM_RAW, 'Form signuture in json format.', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Return the response structure of create and update form services.
     * @return external_single_structure return structure
     * @since  Edwiser Form 1.2.1
     */
    public static function get_create_update_form_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'Form deletion status.'),
                'formid' => new external_value(PARAM_INT, 'Form id.'),
                'msg' => new external_value(PARAM_RAW, 'Form deletion message.')
            )
        );
    }

    /**
     * Describes the parameters for create new form.
     * @return external_function_parameters
     * @since  Edwiser Forms 1.1.0
     */
    public static function create_new_form_parameters() {
        return self::get_create_update_form_parameters();
    }

    /**
     * Creating new form using form definition and settings.
     * @param  array $settings The settings of form including title, description, data_edit, type, events,
     *                         enable_notifi_email, notifi_email, notifi_email_subjec, notifi_email_body,
     *                         emailbodydraftitemid, confirmation_subjec, confirmation_message, msgdraftitemid,
     *                         eventsettings
     * @param  string $formdef json string of form definition
     * @return array           [status, msg, formid] of form creation process
     * @since  Edwiser Form 1.2.0
     */
    public static function create_new_form($settings, $formdef) {
        global $PAGE;

        // Validating functional parameter.
        $params = self::validate_parameters(
            self::create_new_form_parameters(),
            array("setting" => $settings, "formdef" => $formdef)
        );

        $controller = controller::instance();

        $responce = array(
            'status' => 0,
            'msg' => get_string("efb-form-setting-save-fail-msg", "local_edwiserform"),
            'formid' => 0
        );
        $type = $controller->get_array_val($settings, "type");

        // Decoding events settings to verify before saving them.
        $eventsettings = json_decode($controller->get_array_val($settings, "eventsettings"), true);

        // Explode events into array to use further. If events is empty then assign empty array to skip event check.
        if ($controller->get_array_val($settings, "events") == "") {
            $events = [];
        } else {
            $events = explode(',', $controller->get_array_val($settings, "events"));
        }

        $errormessages = [];
        if (!empty($events)) {
            foreach ($events as $event) {
                $plugin = $controller->get_plugin($event);

                // Verifying event settings before creating form with event.
                if (isset($eventsettings[$event])) {
                    $status = $plugin->verify_form_settings($eventsettings[$event]);
                    if ($status != '') {
                        $errormessages[] = $status;
                    }
                }

                // Verifying form field for missing field.
                $status = $plugin->verify_form_fields($formdef);
                if ($status != '') {
                    $errormessages[] = $status;
                }
            }
        }

        // If events return some error then enclosing those erros in the table format.
        if (count($errormessages) > 0) {
            foreach ($errormessages as $index => $error) {
                $errormessages[$index] = array(
                    'index' => $index + 1,
                    'error' => $error
                );
            }
            $templatecontext = new stdClass;
            $templatecontext->errors = $errormessages;
            $context = context_system::instance();
            $PAGE->set_context($context);

            // Rendering errors in table format using template.
            $responce['msg'] = $PAGE->get_renderer('local_edwiserform')->render_from_template(
                'local_edwiserform/form_creation_updation_error',
                $templatecontext
            );
            return $responce;
        }

        // Saving form settings and definition.
        $formid = self::save_form($params['setting'], $params['formdef']);
        if ($formid > 0) {
            if (!empty($events)) {
                foreach ($events as $event) {
                    $plugin = $controller->get_plugin($event);

                    // If admin selected any event with the form and form have some settings.
                    // Then passing those to event for further handling.
                    $plugin->create_new_form($formid, $eventsettings[$event]);
                }
            }
            $responce['status'] = 1;
            $responce['msg'] = get_string("efb-form-setting-save-msg", "local_edwiserform");
            $responce['formid'] = $formid;
        }
        return $responce;
    }

    /**
     * Save form definition and settings.
     * @param  array  $setting    Form settings
     * @param  string $definition json format string
     * @return mixes              id of created form or error message
     * @since  Edwiser Form 1.1.0
     */
    private static function save_form($setting, $definition) {
        global $DB, $USER, $CFG;

        $controller = controller::instance();

        $data = new \stdClass();
        $data->title = $controller->get_array_val($setting, "title");
        $data->description = $controller->get_array_val($setting, "description");
        $data->author = $USER->id;
        $data->type = $controller->get_array_val($setting, "type");
        $data->events = $controller->get_array_val($setting, "events");
        $data->enable_notifi_email = $controller->get_array_val($setting, "enable_notifi_email", "");
        $data->notifi_email_subject = $controller->get_array_val($setting, "notifi_email_subject", "");
        $data->notifi_email = $controller->get_array_val($setting, "notifi_email", "");
        $data->notifi_email_body = "";
        $data->confirmation_subject = $controller->get_array_val($setting, "confirmation_subject", "");
        $data->message = "";
        $data->data_edit = $controller->get_array_val($setting, "data_edit");
        $data->style = $controller->get_array_val($setting, "style", 1);
        $data->allowsubmissionsfromdate = $controller->get_array_val($setting, "allowsubmissionsfromdate", 0);
        $data->allowsubmissionstodate = $controller->get_array_val($setting, "allowsubmissionstodate", 0);
        $data->definition = $definition;
        $data->created = time();
        $data->enabled = 0;
        $data->deleted = 0;
        try {
            $result = $DB->insert_record("efb_forms", $data, $returnid = true, $bulk = false);
            $form = new stdClass;
            $form->id = $result;
            $context = context_system::instance();
            require_once($CFG->libdir . "/filelib.php");
            $form->message = file_save_draft_area_files(
                $controller->get_array_val($setting, "msgdraftitemid", 0),
                $context->id,
                EDWISERFORM_COMPONENT,
                EDWISERFORM_SUCCESS_FILEAREA,
                $result,
                array('subdirs' => false),
                $controller->get_array_val($setting, "confirmation_message", "")
            );
            $form->notifi_email_body = file_save_draft_area_files(
                $controller->get_array_val($setting, "emailbodydraftitemid", 0),
                $context->id,
                EDWISERFORM_COMPONENT,
                EDWISERFORM_SUCCESS_FILEAREA,
                $result,
                array('subdirs' => false),
                $controller->get_array_val($setting, "notifi_email_body", "")
            );
            $DB->update_record("efb_forms", $form);
        } catch (\Exception $ex) {
            $result = $ex->getMessage();
        }
        return $result;
    }

    /**
     * Returns description of method parameters for create new form.
     * @return external_single_structure
     * @since  Edwiser Form 1.0.0
     */
    public static function create_new_form_returns() {
        return self::get_create_update_form_returns();
    }
}

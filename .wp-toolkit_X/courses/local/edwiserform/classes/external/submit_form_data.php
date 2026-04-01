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
 * Trait for submit form data service.
 *
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace local_edwiserform\external;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\controller;
use external_single_structure;
use external_function_parameters;
use external_value;
use context_system;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Service definition for submit form data.
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait submit_form_data {

    /**
     * Describes the parameters for submit for datd.
     * @return external_function_parameters
     * @since  Edwiser Forms 1.0.0
     */
    public static function submit_form_data_parameters() {
        return new external_function_parameters(
            array(
                'formid' => new external_value(PARAM_TEXT, 'Form id', VALUE_REQUIRED),
                'data' => new external_value(PARAM_TEXT, 'Form data submited by user.', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Save usr's submitted form data, process events, send confirmation and notification email.
     * @param  integer $formid The form id
     * @param  string  $data   json string of user's submission data
     * @return array           [status, msg, errors(encodded errors array)]
     * @since  Edwiser Form 1.2.1
     */
    public static function submit_form_data($formid, $data) {
        global $DB, $USER, $CFG;

        $controller = controller::instance();

        $responce = array(
            'status' => false,
            'msg' => get_string("efb-form-data-submission-failed", "local_edwiserform"),
            'errors' => "{}"
        );
        $form = $DB->get_record('efb_forms', array('id' => $formid));
        if (!$form) {
            self::delete_files($data);
            return $responce;
        }
        if ($form->allowsubmissionsfromdate > 0 && time() < $form->allowsubmissionsfromdate) {
            $responce['msg'] = get_string(
                'submissionbeforedate',
                'local_edwiserform',
                date('F j, Y, g:i a', $form->allowsubmissionsfromdate)
            );
            return $responce;
        }
        if ($form->allowsubmissionstodate > 0 && time() > $form->allowsubmissionstodate) {
            $responce['msg'] = get_string(
                'submissionafterdate',
                'local_edwiserform',
                date('F j, Y, g:i a', $form->allowsubmissionstodate)
            );
            return $responce;
        }
        $events = [];
        $plugin = null;
        $plugintypeinevent = false;
        $submissionsupports = true;

        // Check whether user can submit data into form having XYZ plugin.
        if (!empty($form->events)) {
            foreach (explode(',', $form->events) as $event) {
                if ($form->type == $event) {
                    $plugintypeinevent = true;
                }
                $event = $controller->get_plugin($event);
                $submissionsupports &= $event->can_save_data();
                $events[] = $event;
            }
        }

        // Sorting events on the basis of index specified in event class.
        uasort($events, array($controller, 'sort_events'));
        if ($form->type != 'blank') {
            $plugin = $controller->get_plugin($form->type);
            if (!$plugin->can_save_data()) {
                return $plugin->event_action($form, $data);
            }
            $submissionsupports = $plugin->can_save_data();
        }
        if (!$submissionsupports) {
            self::delete_files($data);
            return array(
                'status' => false,
                'msg' => get_string("efb-form-data-submission-not-supported", "local_edwiserform")
            );
        }
        $pluginmessage = "";

        if (!empty($events)) {
            $errors = [];
            foreach ($events as $event) {

                // Validating form data for events selected in form events.
                $errors = array_merge($errors, $event->validate_form_data($form, $data));
            }
            if (!empty($errors)) {
                self::delete_files($data);
                return [
                    'status' => false,
                    'msg' => get_string('efb-invalid-form-data', 'local_edwiserform'),
                    'errors' => json_encode($errors)
                ];
            }
        }
        $noemail = false;
        if (!empty($events)) {
            foreach ($events as $event) {
                if (!$event->can_save_data()) {
                    return $event->event_action($form, $data);
                }
                // Processing event action before saving form data.
                $pluginresponce = $event->event_action($form, $data);
                if ($pluginresponce['status'] === false) {
                    self::delete_files($data);
                    $event->revert_event_action($form, $data);
                    return $pluginresponce;
                }
                if (isset($pluginresponce['noemail']) && $pluginresponce['noemail'] == true) {
                    $noemail = true;
                }
                if (isset($pluginresponce['msg'])) {
                    $pluginmessage .= '<p>' . $pluginresponce['msg'] . '</p>';
                }
            }
        }
        // Userid is required to save form data to associate with specific or empty user.
        if ($controller->is_logged_and_not_guest()) {
            $userid = $USER->id;
        } else {
            $userid = 0;
        }
        if ($form->type == 'blank') {
            $supportmultiple = false;
            $supportformdataupdate = true;
        } else {
            $supportmultiple = $plugin->support_multiple_submissions();
            $supportformdataupdate = $plugin->support_form_data_update();
        }
        if ($supportmultiple) {
            $submission = $DB->get_records("efb_form_data", array('formid' => $formid, 'userid' => $userid));
        } else {
            $submission = $DB->get_record("efb_form_data", array('formid' => $formid, 'userid' => $userid));
        }
        $olddata = "";
        if (!empty($events)) {
            foreach ($events as $event) {
                $supportformdataupdate &= $event->support_form_data_update();
            }
        }
        if ($supportmultiple || !$submission || !$supportformdataupdate || $userid == 0) {
            $submission = new stdClass;
            $submission->formid = $formid;
            $submission->userid = $userid;
            $submission->submission = $data;
            $submission->date = time();
            $status = $DB->insert_record("efb_form_data", $submission);
            $submission->id = $status;
        } else if ($submission && !is_array($submission) && $supportformdataupdate) {
            $olddata = $submission->submission;
            $data = self::verify_user_files($data, $submission->submission);
            $submission->submission = $data;
            $submission->updated = time();
            $status = $DB->update_record("efb_form_data", $submission);
        }
        if ($status) {
            $responce['status'] = true;
            $responce['msg'] = '<p>' . get_string(
                "efb-form-data-submission-successful",
                "local_edwiserform",
                $CFG->wwwroot
            ) . '</p>';
            $responce['msg'] .= $pluginmessage;

            // Sending confirmation email to user.
            if ($form->message && !$noemail) {
                $responce['msg'] .= self::confirmation($form, $submission->submission);
            }

            // Sending notification email to admin/emails from form settings.
            if ($form->enable_notifi_email) {
                $responce['msg'] .= self::notify($form, $submission->submission);
            }
        }
        if (!empty($events)) {
            foreach ($events as $event) {
                // Processing event post action after event action.
                $pluginresponce = $event->event_post_action($form, $data);
            }
        }
        return $responce;
    }

    /**
     * Delete submited data for file submissions
     * @param  stdClass $data
     * @param  string $itemid optional
     * @since  Edwiser Form 1.1.0
     */
    public static function delete_files($data, $itemid = false) {
        $controller = controller::instance();

        if (is_string($data)) {
            $data = json_decode($data);
        }
        foreach ($data as $key => $input) {
            if (isset($input->type) && $input->type == "file") {
                $controller->delete_edwiserform_files(EDWISERFORM_USER_FILEAREA, $input->value);
            }
        }
    }

    /**
     * Check submited data for file submissions and old files
     * @param  array  $data in which field is available
     * @param  string $name to find in fields
     * @return string       data of field
     * @since  Edwiser Form 1.1.0
     */
    public static function get_data_field_by_name($data, $name) {
        foreach ($data as $key => $input) {
            if ($input->name == $name && isset($input->type) && $input->type == "file") {
                return $input->value;
            }
        }
        return 0;
    }

    /**
     * Check submited data for file submissions and old files
     * @param  stdClass $newdata new data submitted in form
     * @param  stdClass $olddata old data submitted previously
     * @return stdClass          validated data
     * @since  Edwiser Form 1.1.0
     */
    public static function verify_user_files($newdata, $olddata) {
        global $DB, $CFG;

        $controller = controller::instance();

        require_once($CFG->libdir . '/moodlelib.php');

        $newdata = json_decode($newdata);
        $olddata = json_decode($olddata);
        foreach ($newdata as $key => $input) {
            if (isset($input->type) && $input->type == "file") {
                $oldfile = self::get_data_field_by_name($olddata, $input->name);
                if (isset($input->delete) && $input->delete == true) {
                    $controller->delete_edwiserform_files(EDWISERFORM_USER_FILEAREA, $oldfile);
                    unset($input->delete);
                    continue;
                }
                if ($input->value == 0) {
                    $newdata[$key]->value = $oldfile;
                    continue;
                }
                if ($oldfile != 0) {
                    $controller->delete_edwiserform_files(EDWISERFORM_USER_FILEAREA, $oldfile);
                    continue;
                }
            }
        }
        return json_encode($newdata);
    }


    /**
     * Find label of field by field's name attribute
     * @param  string $name   The name attribtue of field
     * @param  array  $fields Array of fields from form definition
     * @return array          Feld details array
     * @since  Edwiser Form 1.0.0
     */
    public static function get_field_using_name($name, $fields) {
        foreach ($fields as $field) {
            if (isset($field['attrs']['name']) && $field['attrs']['name'] == $name) {
                return $field;
            }
        }
        return false;
    }

    /**
     * Replacing template tags with data
     * @param  string   $body        html body of template
     * @param  stdClass $form        Form object with definition and settings
     * @param  array    $submission  Data submitted by user through form
     * @param  boolean  $includepass Skip password to be added in all fields
     * @return string                filter html body
     * @since  Edwiser Form 1.2.1
     */
    public static function filter_body_tags($body, $form, $submission, $includepass = false) {
        global $PAGE, $USER, $DB, $COURSE;

        $controller = controller::instance();
        $namefields = get_all_user_name_fields(true);
        $author = $DB->get_record_sql('SELECT ' . $namefields . ' FROM {user} WHERE id = ?', array($form->author));
        $tags = array(
            "{SITE_NAME}" => $COURSE->fullname,
            "{AUTHOR_NAME}" => fullname($author),
            "{AUTHOR_FIRSTNAME}" => $author->firstname,
            "{AUTHOR_LASTNAME}" => $author->lastname,
            "{FORM_TITLE}" => $form->title,
            "{USER_LINK}" => html_writer::tag(
                "a",
                fullname($USER),
                array('href' => new moodle_url("/user/profile.php", array('id' => $USER->id)))
            )
        );
        if (!$controller->is_logged_and_not_guest()) {
            $namefields = get_all_user_name_fields();
            $user = new stdClass;
            foreach ($namefields as $key => $value) {
                $user->$key = "";
            }
            $user->firstname = self::submission_get_value_from_name($submission, 'firstname');
            $user->lastname = self::submission_get_value_from_name($submission, 'lastname');
        } else {
            $user = $USER;
        }
        $tags["{USER_FULLNAME}"] = fullname($user);
        $tags["{USER_FIRSTNAME}"] = $user->firstname;
        $tags["{USER_LASTNAME}"] = $user->lastname;
        foreach ($tags as $tag => $value) {
            $body = str_replace($tag, $value, $body);
        }

        // Filtering view form data page tag with page link.
        preg_match_all('({VIEW_DATA_LINK\s+LABEL="(\w+|\d+|\_+)"})', $body, $links);
        if (count($links[0])) {
            foreach ($links[0] as $index => $tag) {
                $link = html_writer::tag(
                    "a",
                    $links[1][$index],
                    array("href" => new moodle_url(
                        "/local/edwiserform/view.php",
                        array(
                            'page' => 'viewdata',
                            'formid' => $form->id
                        )
                    ))
                );
                $body = str_replace($tag, $link, $body);
            }
        }

        // Adding form submission fields in the email.
        if (strpos($body, '{ALL_FIELDS}') != -1) {
            $definition = json_decode($form->definition, true);
            $fields = $definition['fields'];
            $messagehtml = '';
            $data = new stdClass;
            $data->rows = [];
            $rows = [];
            foreach ($submission as $input) {
                $row = new stdClass;
                $field = self::get_field_using_name($input->name, $fields);
                $label = $input->name;
                $value = $input->value;
                if ($field) {
                    if ($includepass == false && $field['tag'] == 'input' && $field['attrs']['type'] == 'password') {
                        continue;
                    }
                    if (isset($field['attrs']['type']) && in_array($field['attrs']['type'], ['radio', 'checkbox'])) {
                        // If element is radio or checkbox then show label instead of value.
                        foreach ($field['options'] as $option) {
                            if ($option['value'] == $value) {
                                $value = $option['label'];
                                break;
                            }
                        }
                    }
                    $label = $field['config']['label'];
                }
                $row->field = strip_tags($label);
                $row->value = $value;
                if (isset($rows[$row->field])) {
                    if (!isset($rows[$row->field]->list)) {
                        $rows[$row->field] = (object) [
                            'list' => true,
                            'field' => $row->field,
                            'items' => [$rows[$row->field]->value]
                        ];
                    }
                    $rows[$row->field]->items[] = $row->value;
                } else {
                    $rows[$row->field] = $row;
                }
            }
            $data->rows = array_values($rows);
            $PAGE->set_context(context_system::instance());
            $fieldtable = $PAGE->get_renderer('local_edwiserform')->render_from_template('local_edwiserform/field_table', $data);
            $body = str_replace('{ALL_FIELDS}', $fieldtable, $body);
        }
        return $body;
    }

    /**
     * Get value from submission using field name
     * @param  array  $submission Submitted data
     * @param  string $name       Name of field
     * @return string             Value of submitted field
     * @since  Edwiser Form 1.2.1
     */
    public static function submission_get_value_from_name($submission, $name) {
        foreach ($submission as $input) {
            if ($input->name == $name) {
                return $input->value;
            }
        }
        return "";
    }

    /**
     * Get email from form definition and submission
     * @param  array  $definition Form definition
     * @param  array  $submission Data submitted in the form
     * @return string             Email id if present in form and submission else blank
     * @since  Edwiser Form 1.2.1
     */
    public static function email_from_form($definition, $submission) {
        $definition = json_decode($definition, true);
        $fields = $definition['fields'];
        foreach ($fields as $field) {
            if ($field['tag'] = 'input' &&
                isset($field['attrs']['type']) &&
                isset($field['attrs']['name']) &&
                $field['attrs']['type'] == 'email'
            ) {
                if ($value = self::submission_get_value_from_name($submission, $field['attrs']['name'])) {
                    return $value;
                }
            }
        }
        return "";
    }

    /**
     * Sending confirmation email to user who submitted form
     * @param  stdClass $form The form object with definition and settings
     * @param  array $submission Data submitted by user through form
     * @return string success message when email sent succussful or failed message
     * @since  Edwiser Form 1.2.1
     */
    public static function confirmation($form, $submission) {
        global $USER;

        $controller = controller::instance();

        $email = "";
        $submission = json_decode($submission);
        if (!$controller->is_logged_and_not_guest()) {
            $email = self::email_from_form($form->definition, $submission);
        }
        if ($USER->id != 0 && !empty($USER->email)) {
            $email = $USER->email;
        }
        if (!$email) {
            return get_string('efb-confirmation-email-failed', 'local_edwiserform');
        }
        $context = context_system::instance();

        // Filtering email body tempalte tags before sending email.
        $messagehtml = self::filter_body_tags($form->message, $form, $submission, true);
        $messagehtml = file_rewrite_pluginfile_urls(
            $messagehtml,
            'pluginfile.php',
            $context->id,
            EDWISERFORM_COMPONENT,
            EDWISERFORM_SUCCESS_FILEAREA,
            $form->id
        );
        if ($controller->edwiserform_send_email(
            get_config("core", "smtpuser"),
            $email,
            $form->confirmation_subject,
            $messagehtml
        )) {
            return get_string('efb-confirmation-email-success', 'local_edwiserform');
        }
        return get_string('efb-confirmation-email-failed', 'local_edwiserform');
    }

    /**
     * Sending notification email to admin/emails from form settings
     * @param  stdClass $form The form object with definition and settings
     * @param  array $submission Data submitted by user through form
     * @return string success message when email sent succussful or failed message or empty if no email found
     * @since  Edwiser Form 1.0.0
     */
    public static function notify($form, $submission) {
        global $CFG, $USER, $DB;

        $controller = controller::instance();

        $submission = json_decode($submission);
        $subject = $form->notifi_email_subject;

        $context = context_system::instance();

        // Filtering email body tempalte tags before sending email.
        $messagehtml = self::filter_body_tags($form->notifi_email_body, $form, $submission);
        $messagehtml = file_rewrite_pluginfile_urls(
            $messagehtml,
            'pluginfile.php',
            $context->id,
            EDWISERFORM_COMPONENT,
            EDWISERFORM_SUCCESS_FILEAREA,
            $form->id
        );
        if ($form->notifi_email) {
            $emails = explode(',', $form->notifi_email);
            $status = true;
            foreach ($emails as $email) {
                $status = $status && $controller->edwiserform_send_email(
                    get_config("core", "smtpuser"),
                    $email,
                    $subject,
                    $messagehtml
                );
            }
            if ($status != true) {
                return get_string('efb-notify-email-failed', 'local_edwiserform');
            }
            return get_string('efb-notify-email-success', 'local_edwiserform');
        }
        $authoremail = $DB->get_field('user', 'email', array('id' => $form->author));
        if ($authoremail) {
            if ($controller->edwiserform_send_email(get_config("core", "smtpuser"), $authoremail, $subject, $messagehtml)) {
                return get_string('efb-notify-email-success', 'local_edwiserform');
            }
            return get_string('efb-notify-email-failed', 'local_edwiserform');
        }
        return '';
    }

    /**
     * Returns description of method parameters for submit form data
     * @return external_single_structure
     * @since  Edwiser Form 1.0.0
     */
    public static function submit_form_data_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'Form data submission status.'),
                'data' => new external_value(PARAM_RAW, 'Action after submission.', VALUE_DEFAULT, ''),
                'msg' => new external_value(PARAM_RAW, 'Form data submission message'),
                'errors' => new external_value(PARAM_RAW, 'Error array found in form submission')
            )
        );
    }

}

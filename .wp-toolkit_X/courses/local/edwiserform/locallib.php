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
 * Edwiser Form root class
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');

use local_edwiserform\output\add_new_form;
use local_edwiserform\output\list_form;
use local_edwiserform\output\list_form_data;
use local_edwiserform\output\import_form;
use local_edwiserform\controller;

/**
 * Edwiser Form root class definition
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edwiserform {

    /**
     * Renderer output object
     * @var core_renderer
     */
    private $output;

    /**
     * Edwiser Forms $controller class instance
     * @var controller
     */
    private $controller;

    /**
     * Constructor to initialize required variables
     */
    public function __construct() {
        $this->controller = controller::instance();
    }

    /**
     * Delete the file uploaded by user in the form
     * @param object $data user submission
     * @since Edwiser Form 1.1.0
     */
    public function delete_user_uploaded_files($data) {
        $submissions = json_decode($data->submission);
        foreach ($submissions as $submission) {
            if (isset($submission->type) && $submission->type == "file") {
                $this->controller->delete_edwiserform_files(EDWISERFORM_USER_FILEAREA, $submission->value);
            }
        }
    }

    /**
     * Get renderer for edwiserform plugin
     * @return stdClass $outpu
     * @since Edwiser Form 1.0.0
     */
    public function get_renderer() {
        global $PAGE;
        if ($this->output) {
            return $this->output;
        }
        $this->output = $PAGE->get_renderer('local_edwiserform');
        return $this->output;
    }

    /**
     * Parse xml file and create stdClass
     * @param  string   $xml xml file content
     * @return stdClass      form
     * @since  Edwiser Form 1.0.0
     */
    public function parse_xml($xml) {
        $xml = simplexml_load_string($xml);
        $form = new stdClass;
        foreach ($xml as $key => $obj) {
            $form->$key = (string)$obj;
        }
        return $form;
    }

    /**
     * Verify data form XML file
     * @param  object $form Form data
     * @return array        Error found in XML data
     * @since  Edwiser Form 1.0.0
     */
    public function verify_import_data($form) {
        $errors = [];
        if (!$form) {
            $errors[] = get_string('efb-import-empty-file', 'local_edwiserform');
        } else {
            $check = array('title', 'description', 'definition', 'type');
            foreach ($check as $obj) {
                if (!isset($form->$obj)) {
                    $errors[] = get_string('efb-import-invalid-file-no-'.$obj, 'local_edwiserform');
                }
            }
            if ($errors == '' && $form->type == 'enrolment') {
                if (!isset($form->courses)) {
                    $errors[] = get_string('efb-import-invalid-file-no-courses', 'local_edwiserform');
                }
            }
        }
        return $errors;
    }

    /**
     * Create form from imported data using xml file
     * @param  stdClass $form obejct
     * @return integer        new formid
     * @since  Edwiser Form 1.0.0
     */
    public function create_imported_form($form) {
        global $USER, $DB;
        $form->author = $USER->id;
        $form->enabled = 0;
        $form->deleted = 0;
        return $DB->insert_record('efb_forms', $form);
    }

    /**
     * Import form along with definition and settings provided using xml file
     * @param  moodleform $mform Moodle form for import form
     * @return array $errors     found in form
     * @since  Edwiser Form 1.0.0
     */
    protected function import_form($mform) {
        $errors = [];
        if ($mform->is_submitted()) {
            $xml = $mform->get_file_content('import_file');
            $form = $this->parse_xml($xml);
            $errors = $this->verify_import_data($form);
            if (empty($errors)) {
                $formid = $this->create_imported_form($form);
                if ($formid) {
                    $url = new moodle_url('/local/edwiserform/view.php', array('page' => 'listforms'));
                    redirect($url);
                }
            }
        }
        return $errors;
    }

    /**
     * Get plugin license data to pass js
     * @return String set license variable with current license status
     * @since  Edwiser Form 1.3.1
     */
    private function get_license_data_for_js() {
        $lcontroller = new local_edwiserform_license_controller();
        $out = "";
        $out .= html_writer::start_tag('script', array('type' => 'text/javascript'));
        $out .= 'var license = "' . $lcontroller->getDataFromDb() . '";';
        $out .= html_writer::end_tag('script');
        return $out;
    }

    /**
     * Export form submissions in CSV file
     * @param Array  $rows     Submission data along with header in rows format
     * @param String $filename Filename for CSV file
     * @since Edwiser Form 1.3.2
     */
    public function export_csv($rows, $filename) {
        // Output headers so that the file is downloaded rather than displayed.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        // Create a file pointer connected to the output stream.
        $output = fopen('php://output', 'w');

        foreach ($rows as $row) {
            // Output the column headings.
            fputcsv($output, $row);
        }
    }

    /**
     * Export form submissions in Excel Sheet
     * @param Array  $rows     Submission data along with header in rows format
     * @param String $filename Filename for Excel Sheet
     * @since Edwiser Form 1.3.2
     */
    public function export_xlsx($rows, $filename) {
        global $CFG;
        require_once($CFG->libdir.'/excellib.class.php');
        $workbook = new MoodleExcelWorkbook($filename);
        $sheet = $workbook->add_worksheet($filename);
        foreach ($rows as $row => $cells) {
            foreach ($cells as $col => $cell) {
                $sheet->write($row, $col, $cell);
            }
        }
        $workbook->close();
    }

    /**
     * Main function responsible to show create_new_form|listforms|listformdata page
     * It also calls js file and extra stylesheets
     * @param  string $page page to view
     * @return string       page output
     * @since  Edwiser Form 1.0.0
     */
    public function view($page) {
        global $USER, $CFG, $PAGE;
        $out = "";
        $out .= $this->get_license_data_for_js();
        $js = [new moodle_url('https://www.google.com/recaptcha/api.js')];
        $css = [new moodle_url($CFG->wwwroot .'/local/edwiserform/style/datatables.css')];
        $themedependentcss = '/local/edwiserform/style/common_' . $PAGE->theme->name . '.css';
        if (file_exists($CFG->dirroot . $themedependentcss)) {
            $css[] = new moodle_url($CFG->wwwroot . $themedependentcss);
        }
        switch ($page) {
            case 'newform':
                $this->controller->can_create_or_view_form($USER->id);
                $formid = optional_param('formid', null, PARAM_FLOAT);
                $out .= $this->get_renderer()->render(new add_new_form($formid));
                if ($formid) {
                    $page = 'editform';
                }
                $css[] = new moodle_url($CFG->wwwroot .'/local/edwiserform/style/formedit.css');
                $themedependentcss = '/local/edwiserform/style/formedit_' . $PAGE->theme->name . '.css';
                if (file_exists($CFG->dirroot . $themedependentcss)) {
                    $css[] = new moodle_url($CFG->wwwroot . $themedependentcss);
                }
                $out .= html_writer::start_tag('div', array('id' => 'root-page-loading', 'style' => '
                    position: fixed;
                    width: 100%;
                    top: 0;
                    left: 0;
                    height: 100%;
                    background: white;
                '));
                $out .= html_writer::start_tag('div', array('style' => '
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    text-align: center;
                '));
                $out .= get_string("error-occured-while-loading", "local_edwiserform");
                $out .= html_writer::end_tag('div');
                $out .= html_writer::end_tag('div');
                $sitekey = get_config('local_edwiserform', 'google_recaptcha_sitekey');
                if (trim($sitekey) == '') {
                    $sitekey = 'null';
                }
                $PAGE->requires->js_call_amd('local_edwiserform/new_form_main', 'init', array($sitekey));
                break;
            case 'listforms':
                $PAGE->requires->js_call_amd('local_edwiserform/form_list', 'init');
                $out .= $this->get_renderer()->render(new list_form());
                break;
            case 'viewdata':
                $formid = optional_param('formid', null, PARAM_FLOAT);
                $PAGE->requires->js_call_amd('local_edwiserform/form_data_list', 'init', array($formid, $this->controller->can_create_or_view_form(false, true)));
                $out .= $this->get_renderer()->render(new list_form_data($formid));
                break;
            case 'import':
                require_once($CFG->dirroot.'/local/edwiserform/classes/import_form.php');
                $mform = new local_edwiserform_import_form();
                $errors = $this->import_form($mform);
                $out .= $this->get_renderer()->render(new import_form($mform, $errors));
                break;
        }
        foreach ($js as $jsfile) {
            $PAGE->requires->js($jsfile);
        }
        foreach ($css as $cssfile) {
            $PAGE->requires->css($cssfile);
        }
        return $out;
    }
}

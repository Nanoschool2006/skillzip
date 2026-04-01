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
 * Export form definition in specified format
 *
 * @package   local_edwiserform
 * @copyright WisdmLabs
 * @author Yogesh Shirsath
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

$formid = required_param('id', PARAM_INT);
$action = optional_param('action', 'form', PARAM_ALPHA);
$type = optional_param('type', 'csv', PARAM_ALPHA);
$urlparams = array('id' => $formid);
$form = $DB->get_record('efb_forms', array('id' => $formid));
$out = "";
$out .= html_writer::start_tag("div", array("class" => "form form-page"));
if (!$form) {
    $title = "Invalid form";
    $out = "404. Form not found.";
} else {
    $title = "Export";
    if ($action == "form") {
        $filename = $form->title . ".json";
        $data = [];
        $data["definition"] = json_decode($form->definition, true);
        $data = json_encode($data);
        // Output headers so that the file is downloaded rather than displayed.
        header('Content-Type: text/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        // Create a file pointer connected to the output stream.
        $output = fopen('php://output', 'w');

        fputs($output, $data);
        die;
    } else {
        $filename = $form->title . '-export-data-' . date("d-m-Y-h:i:s:A") . "." . $type;
        require_once($CFG->dirroot . "/local/edwiserform/locallib.php");
        $object = new local_edwiserform\output\list_form_data($formid);
        [$rows, $errors] = $object->get_submissions_list([], '', true, true);
        $edwiserform = new edwiserform();
        $exportfunction = 'export_' . $type;
        if (method_exists($edwiserform, $exportfunction)) {
            $edwiserform->$exportfunction($rows, $filename);
            die;
        } else {
            $out = 'Invalid file type.';
        }
    }
}
$out .= html_writer::end_tag("div");
$url = new moodle_url('/local/edwiserform/form.php', $urlparams);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout("popup");
echo $OUTPUT->header();
echo $out;
echo $OUTPUT->footer();

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
 * Description of list form data class
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace local_edwiserform\output;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\controller;
use renderable;
use templatable;
use stdClass;
use html_writer;
use moodle_url;
use context_system;

/**
 * Class contains method to list out all forms data using datatable. Also filter using ajax.
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_form_data implements renderable, templatable {

    /**
     * Edwiser Forms $controller class instance
     * @var controller
     */
    private $controller;

    /**
     * Form id, this will be the form id to edit or it can be the null in case of the new form creation.
     * @var Integer
     */
    private $formid         = null;

    /**
     * Selected event plugin
     * @var null
     */
    private $plugin         = null;

    /**
     * Constructor for list form data renderable
     *
     * @param  integer $formid The id of form when re-editing form otherwise null
     * @return void
     * @since  Edwiser Form 1.0.0
     */
    public function __construct($formid = null) {
        global $DB;
        $this->decoded = false;
        $this->controller = controller::instance();
        $this->formid = $formid;
        $this->form = $DB->get_record('efb_forms', array('id' => $this->formid));
        $this->plugin = $this->form->type == 'blank' ? null : $this->controller->get_plugin($this->form->type);
        $this->events = $this->form->events != "" ? explode(',', $this->form->events) : [];
        foreach ($this->events as $key => $event) {
            unset($this->events[$key]);
            $this->events[$event] = $this->controller->get_plugin($event);
        }
        $this->supportsubmission = $this->form->type == 'blank' ? true : $this->plugin->can_save_data();
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @since  Edwiser Form 1.2.0
     */
    public function export_for_template(\renderer_base $output) {
        global $DB, $CFG, $PAGE;
        $PAGE->requires->strings_for_js(['selectallornone'], 'core_form');
        $PAGE->requires->strings_for_js(['delete'], 'core');
        $data = new stdClass();
        if (!$this->supportsubmission) {
            $data->submissionnotsupport = get_string("efb-form-data-submission-not-supported", "local_edwiserform");
            return $data;
        }
        $data->formid = $this->formid;
        $data->heading = $this->form->title;

        // Add default checkbox to Select All/None and user in header.

        $data->headings = [];
        // Don't show buttons is don't have permissions.
        if ($this->controller->can_create_or_view_form(false, true)) {
            $data->headings[] = html_writer::tag(
                'input',
                '',
                array(
                    'type' => 'checkbox',
                    'class' => 'submission-check-all'
                )
            );
        }
        $data->headings[] = html_writer::tag(
            'div',
            get_string("efb-form-data-heading-user", "local_edwiserform"),
            array('style' => 'width: 200px;')
        );
        $data->headings[] = html_writer::tag(
            'div',
            get_string("efb-tbl-heading-created", "local_edwiserform"),
            array('style' => 'width: 120px;')
        );

        // Fetch headings from form.
        $headings = $this->get_headings();

        // Get actions buttons like Add new form and list forms.
        $data->pageactions = $this->get_page_actions();

        if (empty($headings)) {
            $data->nodata = get_string("efb-form-data-no-data", "local_edwiserform");
            return $data;
        }

        if ($this->controller->can_create_or_view_form(false, true)) {
            // Check if form events has any action buttons and js to handle events.
            foreach ($this->events as $key => $event) {
                if ($event->support_form_data_list_actions()) {
                    $PAGE->requires->js_call_amd('edwiserformevents_enrolment/form-data-list', 'init');
                }
            }
        }

        // Prepare remaining table header.
        if ($headings) {
            $headingsmap = $this->get_name_label_map();
            foreach ($headings as $value) {
                $value = isset($headingsmap[$value]) ? $headingsmap[$value] : $value;
                $data->headings[] = ucfirst($value);
            }
            $data->rows = [];
        }
        return $data;
    }

    /**
     * Get user submitted file url using itemid
     *
     * @param  integer $itemid  file itemid
     * @param  bool    $urlonly if true then only url will returned or anchor tag will be returned
     * @return string           url
     * @since  Edwiser Form 1.1.0
     */
    public function get_file_url($itemid, $urlonly = false) {
        if ($itemid != 0) {
            $fs = get_file_storage();
            if ($files = $fs->get_area_files(
                context_system::instance()->id,
                EDWISERFORM_COMPONENT,
                EDWISERFORM_USER_FILEAREA,
                $itemid
            )) {
                foreach ($files as $file) {
                    if ($file->get_filename() != '.') {
                        break;
                    }
                }
                if ($file->get_filename() != '.') {
                    $url = moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        EDWISERFORM_COMPONENT,
                        EDWISERFORM_USER_FILEAREA,
                        $itemid,
                        '/',
                        $file->get_filename()
                    )->__toString();
                    $filename = $file->get_filename();
                    if ($urlonly == true) {
                        return $url;
                    }
                    $options = array('href' => $url, 'target' => '_blank', 'data-itemid' => $itemid);
                    return html_writer::tag('a', $filename, $options);
                }
            }
        }
        return '-';
    }

    /**
     * Returns total number of form data submitted by user in the XYZ form with search criteria
     *
     * @param  integet $formid The id of form
     * @param  string  $search query string to search in the data
     * @return integer         count submission made in the form with filter result
     * @since  Edwiser Form 1.0.0
     */
    public function get_submission_count($formid, $search = '') {
        global $DB, $USER;
        if ($search != "") {
            $search = " AND submission LIKE '%\"value\":\"%" . $search . "%\"%'";
        }
        $sql = "SELECT COUNT(id) total FROM {efb_form_data} WHERE formid = ? $search";
        return $DB->get_record_sql($sql, [$formid])->total;
    }

    /**
     * Get field details using submission name
     * @param  string     $name Name of element
     * @return array|null       If field exist then return field array else null
     * @since  Edwiser Form 1.4.3
     */
    private function get_field_by_name($name) {
        if (!$this->decoded) {
            $this->form->definition = json_decode($this->form->definition, true);
            $this->decoded = true;
        }

        foreach ($this->form->definition['fields'] as $field) {
            if (isset($field['attrs']) && isset($field['attrs']['name']) && $field['attrs']['name'] == $name) {
                return $field;
            }
        }

        return null;
    }


    /**
     * Fetch and return form submissions based on search and sort criteria from data table
     *
     * @param  string $limit number of rows to select
     * @param  string $search query parameter to search in columns
     * @param  boolean $returnheader header row added at first index of rows if true
     * @param  boolean $skipfirst skip user name column and listing if true
     * @return array rows
     * @since  Edwiser Form 1.2.0
     */
    public function get_submissions_list($limit = [], $search = "", $returnheader = false, $skipfirst = false) {
        global $DB, $USER;

        $errors = [];

        // Assign default limits if not passed in $limit.
        $limit = $this->controller->merge_to_default(['from' => 0, 'to' => 0], $limit);

        // Get heading to arrange form data.
        $headings = $this->get_headings();
        $param = [$this->formid];

        // Prepare search part of sql if search is set.
        $searchsql = "";
        if ($search) {
            $param[] = "%$search%";
            $searchsql .= " AND submission LIKE ? ";
        }

        // If user is not admin or author then list only own submissions.
        if (!is_siteadmin() && $this->form->author != $USER->id && $this->form->author2 != $USER->id) {
            $param[] = $USER->id;
            $searchsql .= " AND userid = ? ";
        }
        $sql = "SELECT * FROM {efb_form_data} WHERE formid = ? $searchsql";
        $records = $DB->get_records_sql($sql, $param, $limit['from'], $limit['to']);
        $rows = [];

        // If $returnheader is true(For data export) then return header part of table.
        if ($returnheader) {
            $row = [];
            foreach ($headings as $value) {
                $value = isset($headingsmap[$value]) ? $headingsmap[$value] : $value;
                $row[] = ucfirst($value);
            }
            $rows[] = $row;
        }
        foreach ($this->events as $key => $event) {
            if (!$event->support_form_data_list_actions()) {
                unset($key);
            }
        }
        $duplicatefields = false;
        foreach ($records as $record) {
            $formdata = [];
            $submission = $record->submission;
            $submission = json_decode($submission);
            list($usql, $uparams) = $DB->get_in_or_equal($record->userid);
            $sql = "SELECT id," . get_all_user_name_fields(true) . " FROM {user} WHERE id " . $usql . " AND deleted = false";

            // If $skipfirst is set(For data export) then skipping checkbox and user profile link.
            $additionalcolumns = 2;
            if (!$skipfirst) {
                if ($this->controller->can_create_or_view_form(false, true)) {
                    $additionalcolumns++;
                    $formdata[] = html_writer::tag(
                        'input',
                        '',
                        array(
                            'type' => 'checkbox',
                            'class' => 'submission-check',
                            'data-value' => $record->id
                        )
                    );
                    $action = '';
                    if (!empty($this->events)) {
                        foreach ($this->events as $event) {
                            $action .= $event->form_data_list_actions($record);
                        }
                    }
                    $action .= html_writer::tag(
                        'a',
                        get_string('delete'),
                        array(
                            'href' => '#',
                            'class' => 'efb-data-action delete-action text-danger show',
                            'data-value' => $record->id
                        )
                    );
                }
                if ($user = $DB->get_record_sql($sql, $uparams)) {
                    $userlink = html_writer::tag(
                        'a',
                        fullname($user),
                        array(
                            'href' => new moodle_url('/user/profile.php', array('id' => $record->userid)),
                            'target' => '_blank',
                            'class' => 'formdata-user',
                            'data-userid' => $record->userid
                        )
                    );
                    if ($this->controller->can_create_or_view_form(false, true)) {
                        $userlink .= html_writer::tag(
                            'div',
                            $action,
                            array('class' => 'efb-data-actions')
                        );
                    }
                    $formdata[] = $userlink;
                } else {
                    $formdata[] = '-';
                }
                $formdata[] = date('d-m-Y H:i:s', $record->date);
            }

            // Initializing form submission index to arrange in header order.
            foreach ($headings as $index) {
                $formdata[$index] = null;
            }

            // Check for duplicate form fields
            if (count($headings) - (count($formdata) - $additionalcolumns) != 0) {
                $duplicatefields = true;
                for ($i = count($formdata); $i < count($headings); $i++) {
                    $formdata[$i] = '';
                }
            }

            // Iterating submission and assigning to $formdata.
            foreach ($submission as $elem) {

                $value = $elem->value;

                $field = $this->get_field_by_name($elem->name);

                if ($field != null && $field['tag']) {
                    // If element is file then generating file url.
                    if (isset($field['attrs']['type']) && $field['attrs']['type'] == 'file') {
                        $value = $this->get_file_url($elem->value, $skipfirst);
                    } else if (isset($field['attrs']['type']) && in_array($field['attrs']['type'], ['radio', 'checkbox'])) {
                        // If element is radio or checkbox then show label instead of value.
                        foreach ($field['options'] as $option) {
                            if ($option['value'] == $value) {
                                $value = $option['label'];
                                break;
                            }
                        }
                    }
                }

                // If element is support multiple values like checkbox then converting simple value to array.
                if (isset($formdata[$elem->name])) {
                    if (!is_array($formdata[$elem->name])) {
                        $formdata[$elem->name] = array($formdata[$elem->name]);
                    }
                    $formdata[$elem->name][] = $value;
                    $value = $formdata[$elem->name];
                }
                if (array_key_exists($elem->name, $formdata)) {
                    $formdata[$elem->name] = $value;
                }
            }
            $formdata = array_values($formdata);

            // If element support multiple values like checkbox then generating unordered list from array.
            foreach ($formdata as $key => $value) {
                if (is_array($value)) {
                    if ($skipfirst) {
                        $formdata[$key] = implode("; ", $value);
                    } else {
                        $formdata[$key] = html_writer::start_tag('ul');
                        $formdata[$key] .= html_writer::start_tag('li');
                        $formdata[$key] .= implode("</li><li>", $value);
                        $formdata[$key] .= html_writer::end_tag('li');
                        $formdata[$key] .= html_writer::end_tag('ul');
                    }
                }
            }
            $rows[] = $formdata;
        }

        if ($duplicatefields) {
            $errors[] = get_string('duplicate-form-fields', 'local_edwiserform');
        }
        return [$rows, $errors];
    }

    /**
     * Return array having field name and index and it's label as value
     *
     * @return array map of fields name->label
     * @since Edwiser Form 1.0.0
     */
    private function get_name_label_map() {
        global $DB;
        $def = $this->form->definition;
        if (!$def) {
            return false;
        }
        $def = json_decode($def, true);
        $fields = $def["fields"];
        $map = [];
        foreach ($fields as $field) {
            if (isset($field["attrs"]["name"]) && !isset($map[$field["attrs"]["name"]])) {
                $map[$field["attrs"]["name"]] = strip_tags($field["config"]["label"]);
            }
        }
        return $map;
    }

    /**
     * Get column heading of form based on form fileds and there arrangement
     *
     * @return array heading
     * @since Edwiser Form 1.1.0
     */
    public function get_headings() {
        global $DB;
        $def = $this->form->definition;
        if (!$def) {
            return false;
        }
        $def = json_decode($def, true);
        $headings = [];
        foreach ($def["stages"] as $stage) {
            $headings = $this->get_stage($def, $stage, $headings);
        }
        if (!count($headings)) {
            return false;
        }
        return $headings;
    }

    /**
     * Get fields from stage
     *
     * @param array $def form definition
     * @param array $stage data of stage containing rows
     * @param array $headings
     * @return array headings
     * @since Edwiser Form 1.1.0
     */
    private function get_stage(&$def, $stage, $headings) {
        foreach ($stage["rows"] as $row) {
            $headings = $this->get_row($def, $def["rows"][$row], $headings);
        }
        return $headings;
    }

    /**
     * Get fields from row
     *
     * @param array $def form definition
     * @param array $row data of row containing columns
     * @param array $headings
     * @return array headings
     * @since Edwiser Form 1.1.0
     */
    private function get_row(&$def, $row, $headings) {
        foreach ($row["columns"] as $column) {
            $headings = $this->get_column($def, $def["columns"][$column], $headings);
        }
        return $headings;
    }

    /**
     * Get fields from column
     *
     * @param array $def form definition
     * @param array $column data of column containing fileds
     * @param array $headings
     * @return array headings
     * @since Edwiser Form 1.1.0
     */
    private function get_column(&$def, $column, $headings) {
        foreach ($column["fields"] as $field) {
            $headings = $this->get_field($def["fields"][$field], $headings);
        }
        return $headings;
    }

    /**
     * Add field name into headings array and return
     *
     * @param array $field data
     * @param array $headings
     * @return array headings
     * @since Edwiser Form 1.1.0
     */
    private function get_field($field, $headings) {
        switch ($field["tag"]) {
            case "input":
                if ($field["attrs"]["type"] == "password") {
                    break;
                }
            case "select":
            case "textarea":
                $headings[] = $field["attrs"]["name"];
                break;
        }
        return $headings;
    }


    /**
     * Get page actions
     *
     * @return array page actions
     * @since Edwiser Form 1.1.0
     */
    private function get_page_actions() {
        // Don't show buttons is don't have permissions.
        if (!$this->controller->can_create_or_view_form(false, true)) {
            return [];
        }
        $actions = array(
            array(
                'url' => new moodle_url('/local/edwiserform/view.php', array('page' => 'newform')),
                'label' => get_string('efb-heading-newform', 'local_edwiserform'),
                'icon'  => 'edit'
            ),
            array(
                'url' => new moodle_url('/local/edwiserform/view.php', array('page' => 'listforms')),
                'label' => get_string('efb-heading-listforms', 'local_edwiserform'),
                'icon'  => 'list'
            )
        );
        return $actions;
    }
}

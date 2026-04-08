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
 * Description of list_form
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 * @author    Sudam
 */

namespace local_edwiserform\output;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\controller;
use templatable;
use html_writer;
use renderable;
use moodle_url;
use stdClass;

/**
 * Class contains method to list out all forms using datatable. Also filter using ajax.
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class list_form implements renderable, templatable {

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
     * Function to export the renderer data in a format that is suitable for a
     * mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @since  Edwiser Form 1.2.0
     */
    public function export_for_template(\renderer_base $output) {
        $data = new stdClass();
        $data->heading = "Forms";
        $data->headings = array(
            html_writer::tag('div', get_string("efb-tbl-heading-title", "local_edwiserform"), array('style' => 'width: 80px;')),
            get_string("efb-tbl-heading-type", "local_edwiserform"),
            html_writer::tag(
                'div',
                get_string("efb-tbl-heading-shortcode", "local_edwiserform"),
                array('style' => 'width: 160px;')
            ),
            html_writer::tag('div', get_string("efb-tbl-heading-author", "local_edwiserform"), array('style' => 'width: 120px;')),
            html_writer::tag('div', get_string("efb-tbl-heading-created", "local_edwiserform"), array('style' => 'width: 120px;')),
            html_writer::tag('div', get_string("efb-tbl-heading-author2", "local_edwiserform"), array('style' => 'width: 120px;')),
            html_writer::tag('div', get_string("efb-tbl-heading-modified", "local_edwiserform"), array('style' => 'width: 120px;')),
            html_writer::tag('div', get_string("allowsubmissionsfromdate", "local_edwiserform"), array('style' => 'width: 80px;')),
            html_writer::tag('div', get_string("allowsubmissionstodate", "local_edwiserform"), array('style' => 'width: 80px;')),
            html_writer::tag('div', get_string("efb-tbl-heading-action", "local_edwiserform"), array('style' => 'width: 80px;')),
        );
        $data->pageactions = $this->get_page_actions();
        if (!empty($formslist)) {
            $data->rows = $formslist;
        } else {
            $data->norowsmsg = get_string('no-record-found', 'local_edwiserform');
        }
        return $data;
    }

    /**
     * Return array of page action
     *
     * @return array $actions
     * @since  Edwiser Form 1.0.0
     */
    private function get_page_actions() {
        $actions = array(
            array(
                'url'   => new moodle_url('/local/edwiserform/view.php', array('page' => 'newform')),
                'label' => get_string('efb-heading-newform', 'local_edwiserform'),
                'icon'  => 'edit'
            )
        );
        return $actions;
    }

    /**
     * Returns total number of form created by admin/teacher with search filter criteria
     * @param  string  $search query string to search in the data
     * @return integer count forms created
     * @since  Edwiser Form 1.0.0
     */
    public function get_form_count($search) {
        global $DB, $USER;
        $param = [0];
        $where = " WHERE deleted = ?";
        if ($search != "") {
            $param[] = "%" . $search . "%";
            $where .= " AND title LIKE ?";
        }
        if (!is_siteadmin()) {
            $where .= " AND author = ?";
            $param[] = $this->controller->can_create_or_view_form() ? $USER->id : 0;
        }
        $sql = "SELECT COUNT(id) total FROM {efb_forms} $where";
        return $DB->get_record_sql($sql, $param)->total;
    }

    /**
     * Fetch and return forms based on search and sort criteria from data table
     *
     * @param  string $limit number of rows to select
     * @param  string $search query parameter to search in columns
     * @param  integer $sortcolumn index of column that need to be sorted
     * @param  string $sortdir sorting order ASC|DESC
     * @return array rows
     * @since  Edwiser Form 1.1.0
     */
    public function get_forms_list($limit = [], $search = "", $sortcolumn = 0, $sortdir = "") {
        global $DB, $USER;

        // Assign default limits if not passed in $limit.
        $limit = $this->controller->merge_to_default(['from' => 0, 'to' => 0], $limit);

        $rows = array();

        // Table header.
        $colarray = array(
            "0" => "title",
            "1" => "type",
            "3" => "author",
            "4" => "created",
            "5" => "author2",
            "6" => "modified",
            "7" => "allowsubmissionsfromdate",
            "8" => "allowsubmissionstodate"
        );

        // Prepare search part of sql if searchtext is set.
        $searchquery = " ";
        if ($search) {
            $searchquery = " title LIKE '%" . $search . "%' and ";
        }

        // Prepare ordering part of sql if sortcolumn and sortdir is set.
        $orderbyquery = " ";
        if (!empty($sortdir) && array_key_exists($sortcolumn, $colarray)) {
            $orderbyquery = " ORDER BY ".$colarray[$sortcolumn]. " ".$sortdir . " ";
        }

        $sql = "SELECT id, title, author, author2,
                    type, enabled, deleted, created,
                    modified, allowsubmissionsfromdate,
                    allowsubmissionstodate
                   FROM {efb_forms} WHERE" . $searchquery . "deleted = '0'";
        $param = [];

        // If user is not admin then list form with author/author2 is set as current user.
        if (!is_siteadmin()) {
            $sql .= " and author=? ";
            $param[] = $this->controller->can_create_or_view_form() ? $USER->id : 0;
        }
        $sql .= $orderbyquery;
        $records = $DB->get_records_sql($sql, $param, $limit['from'], $limit['to']);
        foreach ($records as $record) {
            $rows[] = array(
                "shortcode" => '[edwiser-form id="'.$record->id.'"]',
                "title" => strip_tags(format_text($record->title)),
                "type" => $record->type,
                "author" => $this->get_user_name($record->author), // Get author's fullname.
                "created" => date('d-m-Y H:i:s', $record->created),
                "author2" => $record->author2 ? $this->get_user_name($record->author2) : '-', // Get second author's fullname.
                "modified" => date('d-m-Y H:i:s', empty($record->modified) ? $record->created : $record->modified),
                "allowsubmissionsfromdate" => $record->allowsubmissionsfromdate == 0 ? '-' : date(
                    'd-m-Y H:i:s',
                    $record->allowsubmissionsfromdate
                ),
                "allowsubmissionstodate" => $record->allowsubmissionstodate == 0 ? '-' : date(
                    'd-m-Y H:i:s',
                    $record->allowsubmissionstodate
                ),
                "actions" => $this->get_form_actions($record) // Get actions supported by form.
            );
        }
        return $rows;
    }

    /**
     * Get html string form action Enable-Disable
     *
     * @param int  $id      form id
     * @param bool $enabled does form is enabled or disabled
     *
     * @return string html format string containing enable-disable action
     * @since  Edwiser Form 1.2.0
     */
    private function get_enable_disable_button($id, $enabled) {
        $html = "";
        $enabletitle = get_string('efb-form-action-enable-title', 'local_edwiserform');
        $disabletitle = get_string('efb-form-action-disable-title', 'local_edwiserform');
        $html .= html_writer::start_tag(
            'label',
            array('class' => 'efb-switch', 'title' => $enabled ? $disabletitle : $enabletitle)
        );
        $html .= html_writer::checkbox(
            'efb-switch-input',
            '',
            $enabled,
            '',
            array('data-formid' => $id, 'data-enable-title' => $enabletitle, 'data-disable-title' => $disabletitle)
        );
        $html .= html_writer::start_tag('div', array('class' => 'switch-container efb-enable-disable-form'));
        $html .= html_writer::tag('div', '', array('class' => 'switch-background bg-success'));
        $html .= html_writer::tag('div', '', array('class' => 'switch-lever bg-success'));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('label');
        return $html;
    }


    /**
     * Get html string for form actions like Enable-Disable, View data, Preview form, Edit form,
     * Export form definition delete form
     *
     * @param stdClass $form form object with settings
     *
     * @return string html format string containing actions
     * @since  Edwiser Form 1.2.0
     */
    private function get_form_actions($form) {
        global $DB, $CFG;

        $enabled = $form->type == "login" ? get_config("core", "alternateloginurl") : $form->enabled;
        $actions[] = $this->get_enable_disable_button($form->id, $enabled);

        $viewdata = array(
            "icon" => "icon fa fa-table fa-fw text-primary",
            "title" => get_string("efb-form-action-view-data-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-view-data",
                "target" => "_blank",
                "href" => new moodle_url("/local/edwiserform/view.php", array("page" => "viewdata", "formid" => $form->id))
            )
        );
        if ($form->type == 'blank') {
            $actions[] = $viewdata;
        } else {
            $plugin = $this->controller->get_plugin($form->type);
            if ($plugin->can_save_data()) {
                $actions[] = $viewdata;
            }
        }

        // Form live demo link.
        $actions[] = array(
            "icon" => "icon fa fa-file-text fa-fw text-primary",
            "title" => get_string("efb-form-action-live-demo-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-live-demo",
                "target" => "_blank",
                "href" => new moodle_url("/local/edwiserform/form.php", array("id" => $form->id))
            )
        );

        // Preview form link.
        $actions[] = array(
            "icon" => "icon fa fa-eye fa-fw text-primary",
            "title" => get_string("efb-form-action-preview-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-preview",
                "target" => "_blank",
                "href" => new moodle_url("/local/edwiserform/preview.php", array("id" => $form->id))
            )
        );

        // Edit form link.
        $actions[] = array(
            "icon" => "icon fa fa-edit fa-fw text-primary",
            "title" => get_string("efb-form-action-edit-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-edit",
                "target" => "_blank",
                "href" => new moodle_url("/local/edwiserform/view.php", array("page" => "newform", "formid" => $form->id))
            )
        );

        // Export form definition link.
        $actions[] = array(
            "icon" => "icon fa fa-share-square fa-fw text-primary",
            "title" => get_string("efb-form-action-export-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-export",
                "target" => "_blank",
                "data-formid" => $form->id,
                "href" => new moodle_url("/local/edwiserform/export.php", array("id" => $form->id))
            )
        );

        // Delete form link.
        $actions[] = array(
            "icon" => "icon fa fa-trash fa-fw text-primary",
            "title" => get_string("efb-form-action-delete-title", "local_edwiserform"),
            "attrs" => array(
                "class" => "efb-form-delete",
                "target" => "_blank",
                "href" => "#",
                "data-formid" => $form->id
            )
        );

        $html = "";
        foreach ($actions as $action) {
            if (is_string($action)) {
                $html .= $action;
                continue;
            }
            $icon = html_writer::tag(
                'i',
                '',
                array(
                    'class' => $action['icon'],
                    'aria-hidden' => 'true',
                    'title' => $action['title']
                )
            );
            $html .= html_writer::tag('a', $icon, $action['attrs']);
        }
        return $html;
    }

    /**
     * Return name of user
     *
     * @param  integer $userid id of user
     * @return string name of user by concatenation of firstname and last name
     * @since  Edwiser Form 1.1.0
     */
    private function get_user_name($userid) {
        global $DB;
        $user = $DB->get_record("user", array("id" => $userid));
        return fullname($user);
    }

}

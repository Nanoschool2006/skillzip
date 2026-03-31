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
 * Registration form page
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Redirect user to our enabled registration form
 */
function edwiserformevents_registration_pre_signup_requests() {
    global $DB, $OUTPUT, $PAGE;
    $sql = 'SELECT *
              FROM {efb_forms}
             WHERE type = "registration"
               AND enabled = 1
               AND deleted = 0';
    $forms = $DB->get_records_sql($sql);
    if (empty($forms)) {
        return true;
    }
    $form = current($forms);
    $title = $form->title;
    $shortcode = "[edwiser-form id='{$form->id}']";
    $out = "";
    $out .= html_writer::start_tag("div", array("class" => "form form-page"));
    $out .= html_writer::tag('input', '', array('type' => 'hidden', 'id' => 'edwiserform-fullpage', 'value' => true));
    $out .= format_text($shortcode);
    $out .= html_writer::end_tag("div");
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    $PAGE->set_pagelayout("popup");
    echo $OUTPUT->header();
    echo $out;
    echo $OUTPUT->footer();
    die;
}

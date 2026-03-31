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
 * This is comment form for Edwiser Grader Plugin.
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * The form for handling commentbox.
 */
class edwiser_commentbox_edit_form extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform    = $this->_form;
        $editoroptions = $this->_customdata['editoroptions'];
        $commenttext = $this->_customdata['commenttext'];

        $mform->addElement('editor', 'comment_editor', null, null, $editoroptions)->setValue(array('text' => $commenttext));;
        $mform->setType('comment_editor', PARAM_RAW);
        $mform->disable_form_change_checker();
    }
}

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
 * Block code for Edwiser Grader Plugin.
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/edwiser_grader/lib.php');

/**
 * Class to intiate the grader block.
 */
class block_edwiser_grader extends block_base {

    /**
     * Initialize block settings
     * @return string Block title
     */
    public function init() {
        $this->title = get_string('title', 'block_edwiser_grader');
    }

    /**
     * Get content of block
     * @return null|string Null of block html content
     */
    public function get_content() {
        global $USER, $COURSE;
        $id = optional_param('id',  0, PARAM_INT);
        // Check user can add this block or not.
        if (!can_user_add_block($USER->id, $COURSE->id)) {
            return null;
        }

        // If the content already exists then return.
        if (isset($this->content)) {
            return $this->content;
        }
        // Check if instance exists or not.
        if (!empty($this->instance)) {
            $this->content = new stdClass();
            $this->content->text = generate_blockcontent($this->instance);
        } else {
            $this->content = '';
        }
        return $this->content;
    }

    /**
     * Defines the pages of the moodle site on which the block can be added.
     * @return array() List of pages
     */
    public function applicable_formats() {
        global $USER, $COURSE;
        if (can_user_add_block($USER->id, $COURSE->id)) {
            return array('my' => true, 'course-view' => true, 'mod-quiz' => true);
        }
        if (defined('CLI_SCRIPT') && CLI_SCRIPT == true) {
            return array('my' => true);
        }
        return array();
    }

    /**
     * If the muliple instances of the block can be added
     * @return true
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * If the setting is enabled when the editing is turned on.
     * @return boolean
     */
    public function has_config() {
        return true;
    }
}

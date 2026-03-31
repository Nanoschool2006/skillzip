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
 * Edwiser Grader Plugin
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Edwiser grader renderer class
 */
class block_edwiser_grader_renderer extends plugin_renderer_base {
    /**
     * Renders the template for block content.
     * @param  \block_edwiser_grader\output\blockcontent_model $obj Renderable object used to fetch
     *                                                              the details to be passed in mustache.
     * @return String HTML content
     */
    public function render_blockcontent_model(\block_edwiser_grader\output\blockcontent_model $obj) {
        return $this->render_from_template('block_edwiser_grader/blockcontent', $obj->export_for_template($this));
    }

    /**
     * Renders the template for custom grader page.
     * @param  \block_edwiser_grader\output\grader_model $obj Renderable object used to fetch
     *                                                        the details to be passed in mustache.
     * @return String HTML content
     */
    public function render_grader_model(\block_edwiser_grader\output\grader_model $obj) {
        return $this->render_from_template('block_edwiser_grader/grader', $obj->export_for_template($this));
    }

    /**
     * Renders the template for custom grader page.
     * @param  \block_edwiser_grader\output\add_remove_users_model $obj Renderable object used to fetch
     *                                                                  the details to be passed in mustache.
     * @return String HTML content
     */
    public function render_add_remove_users_model(\block_edwiser_grader\output\add_remove_users_model $obj) {
        return $this->render_from_template('block_edwiser_grader/add_remove_users', $obj->export_for_template($this));
    }
}

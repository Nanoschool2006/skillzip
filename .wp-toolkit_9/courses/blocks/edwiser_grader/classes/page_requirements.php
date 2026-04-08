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
 * @package    block_edwiser_grader
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader;

defined('MOODLE_INTERNAL') || die();

use page_requirements_manager;
use html_writer;

/**
 * Page requirements manager for grader
 */
class page_requirements extends page_requirements_manager {

    /**
     * Get end code for questions
     * @return string js code
     */
    public function get_grader_end_code() {
        global $PAGE;
        $output = '';

        // First include must be to a module with no dependencies, this prevents multiple requests.
        $prefix = 'M.util.js_pending("core/first");';
        $prefix .= "require(['core/first'], function() {\n";
        $suffix = "\n});";
        $suffix .= 'M.util.js_complete("core/first");';
        $output .= html_writer::script(implode(";\n", $PAGE->requires->amdjscode));
        return $output;
    }

    /**
     * Load modules for question modal
     * @param  array $module Module array
     */
    public function grader_js_module($module) {
        global $PAGE;
        $uniqid = html_writer::random_id();
        $startjs = " M.util.js_pending('" . $uniqid . "');";
        $endjs = " M.util.js_complete('" . $uniqid . "');";

        $PAGE->requires->js_module($module);

        $modulename = $module['name'];
        $jscode = "$startjs Y.use('$modulename', function(Y) { $endjs });";
        $PAGE->requires->jsinitcode[] = $jscode;
    }
}

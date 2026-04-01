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
 * @subpackage output
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

require_once($CFG->dirroot . '/mod/quiz/classes/external.php');

/**
 * Block content renderable class
 */
class blockcontent_model implements renderable, templatable {

    /**
     * Block instance
     * @var block_edwiser_grader
     */
    private $block = null;
    /**
     * Constructor.
     *
     * @param Object $block instance of blocks
     */
    public function __construct($block) {
        $this->block = $block;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return Object
     */
    public function export_for_template(renderer_base $output) {
        global $COURSE, $USER;
        $output = null;
        $context = new \stdClass();

        // Set the course id needed to load quiz details.
        $context->courseid = $COURSE->id;
        // Check if the block is present on dashboard.
        if ($this->block->pagetypepattern === "my-index") {
            $context->dashboardpage = true;
            // Get all the courses of the current user.
            $courses = get_user_courses($USER->id);
            $output = array();
            foreach ($courses as $course) {
                if (!isset($course->format) || $course->format !== 'site') {
                    $coursedetails = new \stdClass();
                    $coursedetails->id = $course->id;
                    $coursedetails->name = format_text($course->fullname);
                    array_push($output, $coursedetails);
                }
            }
            // Set the courseid to first course of the list.
            if (!empty($output)) {
                $context->courseid = $output[0]->id;
            }
            $context->courses = $output;
        }
        if ($this->block->pagetypepattern === "mod-quiz-view") {
            $context->quizpage = "edg-quizpage";
        }
        return $context;
    }
}

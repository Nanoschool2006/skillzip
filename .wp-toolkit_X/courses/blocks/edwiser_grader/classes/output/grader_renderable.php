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

require_once($CFG->dirroot . '/blocks/edwiser_grader/comment_form.php');
require_once($CFG->dirroot . '/mod/quiz/classes/external.php');

/**
 * Edwiser grader renderable class
 */
class grader_model implements renderable, templatable {


    /**
     * Quiz id
     * @var int
     */
    private $quizid = null;

    /**
     * Course module id
     * @var int
     */
    private $cmid = null;

    /**
     * Grading method user/question
     * @var string
     */
    private $gdm = null;
    /**
     * Constructor.
     *
     * @param Integer $quizid Id of Quiz
     * @param Integer $cmid Course Module Id
     * @param string $gdm User based or question based grading(Grading method)
     */
    public function __construct($quizid, $cmid, $gdm) {
        $this->quizid = $quizid;
        $this->cmid = $cmid;
        $this->gdm = $gdm;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return Object
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $OUTPUT, $CFG;
        $output = null;
        $context = new \stdClass();

        // Get Quiz Object.
        $quiz = $DB->get_record('quiz', array('id' => $this->quizid));

        // Get Quiz Course Module and Course Details.
        $cm = get_coursemodule_from_id('quiz', $this->cmid);
        $course = $DB->get_record('course', array('id' => $cm->course));
        $context->coursetitle = format_text($course->fullname);
        $context->breadcrumbs = $OUTPUT->navbar();
        $context->quiztitle = format_text($cm->name);
        $context->dryrunstatus = get_string('inactive');

        // Get total attempts for quiz.
        $context->attempts = get_all_quizattempts($this->quizid);

        // Get the grading method details.
        $context->gradingmethod = get_quiz_grading_method($quiz->grademethod);

        // Get dry run status.
        $sql = "SELECT * FROM {quiz_overview_regrades} qor
                JOIN {quiz_attempts} qa
                ON qa.uniqueid = qor.questionusageid
                where qa.quiz = ?";

        $records = $DB->get_records_sql($sql, array($cm->instance));
        if (count($records)) {
            $context->dryrunstatus = get_string('active');
            $context->dryclass = "edg-active";
        }

        // Download Form Data.
        $context->cmid = $this->cmid;
        $context->downloadurl = $CFG->wwwroot."/mod/quiz/report.php";
        $context->sessionkey = sesskey();

        // Get back url.
        $backurl = get_user_preferences('edgbackurl');
        if (isset($backurl) && $backurl != null) {
            $context->backurl = $backurl;
        } else {
            $context->backurl = $CFG->wwwroot.'/my/';
        }

        // User based or Question based page.
        if ($this->gdm == 'user') {
            $context->gduser = true;
        }

        if ($this->gdm == 'question') {
            $context->gdquestion = true;
        }

        // Student Name is to be displayed or hide.
        $context->showname = get_config('block_edwiser_grader', 'studentnameoption');

        return $context;
    }
}

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
 * Edwiser Grader Plugin api
 *
 * @package    block_edwiser_grader
 * @subpackage external
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader\external;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/externallib.php');
use external_api;

/**
 * Provides an external API of the block.
 *
 * Each external function is implemented in its own trait. This class
 * aggregates them all.
 */
class api extends external_api {
    use get_course_quizzes;
    use get_not_graded_attempts;
    use get_graded_attempts;
    use get_attempt_questions;
    use get_question_details;
    use display_grade_chart;
    use grade_question;
    use get_quiz_attempts;
    use delete_quiz_attempt;
    use regrade_quiz_attempt;
    use dry_run_regrade;
    use question_based_grading;
    use question_based_grading_details;
    use get_licensed_users;
}

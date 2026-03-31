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
 * Edwiser Grader Plugin display_grade_chart trait.
 *
 * @package    block_edwiser_grader
 * @subpackage external
 * @copyright  Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_edwiser_grader\external;
defined('MOODLE_INTERNAL') || die();

use external_function_parameters;
use external_value;

require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');
require_once($CFG->dirroot.'/mod/quiz/report/reportlib.php');

/**
 * Trait implementing the external function block_edwisergrader_display_grade_chart.
 */
trait display_grade_chart {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function display_grade_chart_parameters() {
        return new external_function_parameters(
            array (
                'cmid' => new external_value(PARAM_INT, 'Course Module ID.', VALUE_DEFAULT, 0),
            )
        );
    }
    /**
     * Display's the grade chart.
     * @param Integer $cmid Course Module ID.
     **/
    public static function display_grade_chart($cmid) {
        global $DB, $PAGE;
        $PAGE->set_context(\context_system::instance());
        $cmod       = get_coursemodule_from_id('quiz', $cmid);
        $quizid     = $cmod->instance;
        $quiz       = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
        $quizdata   = self::get_band_counts_and_width($quiz);
        $bands      = $quizdata['bands'];
        $bandwidth  = $quizdata['bandwidth'];
        $labels     = self::get_band_labels($bands, $bandwidth, $quiz);
        $data       = quiz_report_grade_bands($bandwidth, $bands, $quiz->id, new \core\dml\sql_join());
        $result['labels'] = json_encode($labels, JSON_NUMERIC_CHECK);
        $result['data']   = json_encode($data, JSON_NUMERIC_CHECK);
        return $result;
    }
    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function display_grade_chart_returns() {
        return new \external_single_structure(
            array(
                'labels' => new external_value(PARAM_RAW, 'Grapgh Labels'),
                'data' => new external_value(PARAM_RAW, 'Graph Data'),
            )
        );
    }
    /**
     * Function to get band counts and width
     * @param  object $quiz Quiz Object
     * @return Array       Array of bands and width.
     */
    public static function get_band_counts_and_width($quiz) {
        $bands = $quiz->grade;
        while ($bands > 20 || $bands <= 10) {
            if ($bands > 50) {
                $bands /= 5;
            } else if ($bands > 20) {
                $bands /= 2;
            }
            if ($bands < 4) {
                $bands *= 5;
            } else if ($bands <= 10) {
                $bands *= 2;
            }
        }
        // See MDL-34589. Using doubles as array keys causes problems in PHP 5.4, hence the explicit cast to int.
        $bands = (int) ceil($bands);
        $result['bands']     = $bands;
        $result['bandwidth'] = $quiz->grade / $bands;
        return $result;
    }
    /**
     * Get the bands labels.
     *
     * @param int $bands The number of bands.
     * @param int $bandwidth The band width.
     * @param object $quiz The quiz object.
     * @return string[] The labels.
     */
    public static function get_band_labels($bands, $bandwidth, $quiz) {
        $bandlabels = [];
        for ($i = 1; $i <= $bands; $i++) {
            $bandlabels[] = quiz_format_grade($quiz, ($i - 1) * $bandwidth) . ' - ' . quiz_format_grade($quiz, $i * $bandwidth);
        }
        return $bandlabels;
    }
}

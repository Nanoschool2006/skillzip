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
define("BLOCKNAME", 'edwiser_grader');

require_once($CFG->dirroot. '/blocks/edwiser_grader/classes/output/blockcontent_renderable.php');
require_once($CFG->dirroot. '/blocks/edwiser_grader/classes/output/grader_renderable.php');
require_once($CFG->dirroot. '/blocks/edwiser_grader/classes/output/add_remove_users_renderable.php');
require_once($CFG->dirroot. '/blocks/edwiser_grader/comment_form.php');
require_once($CFG->dirroot. '/blocks/edwiser_grader/classes/license_controller.php');
require_once($CFG->dirroot. '/user/lib.php');

/**
 * Return the renderer of the block.
 * @return object Page renderer.
 */
function get_block_renderer() {
    global $PAGE;
    $pluginrender = 'block_'.BLOCKNAME;
    $renderer = $PAGE->get_renderer($pluginrender);
    return $renderer;
}

/**
 * Returns the html for block content.
 * @param  object $block   Block instance object
 * @return string $content HTML content
 */
function generate_blockcontent($block) {
    global $PAGE;
    $renderable = new \block_edwiser_grader\output\blockcontent_model($block);
    $PAGE->requires->css('/blocks/edwiser_grader/style/styles.min.css');
    $PAGE->requires->js_call_amd('block_edwiser_grader/main', 'init');
    $renderer = get_block_renderer();
    // Check if license activated or not.
    $licensecheck = edg_check_license();
    $validusercheck = edg_check_user_has_grade_access();
    $licensenotice = edg_show_license_notice();
    if ($licensecheck) {
        $content = $licensecheck;
    } else if ($licensenotice) {
        $content = $licensenotice;
    } else if ($validusercheck) {
        $content = $validusercheck;
    } else {
        $content = $renderer->render($renderable);
    }
    $actuallink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
    ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    set_user_preference('edgbackurl', $actuallink);
    return $content;
}
/**
 * Return the list of courses in which user is capable to grade.
 * @param  int    $userid  Id of the user.
 * @return array  $courses List of courses.
 */
function get_user_courses($userid) {
    global $DB;

    // Check if the user is administrator.
    if (is_siteadmin()) {
        return get_courses();
    }

    $params['userid'] = $userid;
    $params['ext_grade'] = 'mod/quiz:grade';
    $sql = "SELECT c.id, c.fullname
        FROM {role_assignments} ra
        JOIN {role} r ON ra.roleid = r.id
        JOIN {role_capabilities} rc ON rc.roleid = r.id
        JOIN {user} u ON u.id = ra.userid
        JOIN {context} ct ON ra.contextid = ct.id
        JOIN {course} c ON ct.instanceid = c.id
        WHERE ra.userid = :userid AND rc.capability = :ext_grade";
    $courses = $DB->get_records_sql($sql, $params);
    return $courses;
}
/**
 * Check if user has capability to add the block or not.
 * @param  integer $userid   User ID
 * @param  integer $courseid Course Id
 * @return bool              True or False
 */
function can_user_add_block($userid, $courseid) {
    global $DB;
    // Check if the user is administrator.
    if (is_siteadmin()) {
        return true;
    }
    $params['userid'] = $userid;
    $params['ext_grade'] = 'mod/quiz:grade';
    $coursequery = "";
    if ($courseid != 1) {
        $coursequery = " AND c.id = :cid";
        $params['cid'] = $courseid;
    }
    $sql = "SELECT c.id
        FROM {role_assignments} ra
        JOIN {role} r ON ra.roleid = r.id
        JOIN {role_capabilities} rc ON rc.roleid = r.id
        JOIN {user} u ON u.id = ra.userid
        JOIN {context} ct ON ra.contextid = ct.id
        JOIN {course} c ON ct.instanceid = c.id
        WHERE ra.userid = :userid AND rc.capability = :ext_grade"
        .$coursequery;
    $courses = $DB->get_records_sql($sql, $params);
    if (!empty($courses)) {
        return true;
    }
    return false;
}

/**
 * Return the content for custom grader page.
 * @param  int     $quizid  Id of quiz which is to be graded.
 * @param  int     $cmid    Course Module Id
 * @param  string  $gdm     Grading method user/question
 * @return string  $content HTML Content
 */
function render_grader_content($quizid, $cmid, $gdm) {
    $renderable = new \block_edwiser_grader\output\grader_model($quizid, $cmid, $gdm);
    $renderer = get_block_renderer();
    // Check if license activated or not.
    $licensecheck = edg_check_license();
    $validusercheck = edg_check_user_has_grade_access();
    $licensenotice = edg_show_license_notice();
    if ($licensecheck) {
        $content = $licensecheck;
    } else if ($licensenotice) {
        $content = $licensenotice;
    } else if ($validusercheck) {
        $content = $validusercheck;
    } else {
        $content = $renderer->render($renderable);
    }
    return $content;
}

/**
 * Return the content for custom grader page.
 * @return string  $content HTML Content
 */
function render_add_remove_users_content() {
    $renderable = new \block_edwiser_grader\output\add_remove_users_model();
    $renderer = get_block_renderer();
    // Check if license activated or not.
    $licensecheck = edg_check_license();
    $licensenotice = edg_show_license_notice();
    if ($licensecheck) {
        $content = $licensecheck;
    } else if ($licensenotice) {
        $content = $licensenotice;
    } else {
        $content = $renderer->render($renderable);
    }
    return $content;
}

/**
 * Returns the total attempts made in the quiz.
 * @param  int $quizid ID of the quiz.
 * @return int         Attempt Count.
 */
function get_all_quizattempts($quizid) {
    global $DB;
    $attemptsql = "SELECT COUNT(*) as attempt FROM
    {quiz_attempts} where quiz = ? and preview = 0";

    $attemptobj = $DB->get_record_sql($attemptsql, array($quizid));
    return $attemptobj->attempt;
}
/**
 * Returns the name of the grading method for the quiz.
 * @param  int    $gradingindex  The index for grading methods.
 * @return string                String set for particular grading method index.
 */
function get_quiz_grading_method($gradingindex) {
    $gradingmethods = quiz_get_grading_options();
    return $gradingmethods[$gradingindex];
}
/**
 * Function to get the quiz attempt information
 * @param  Object $attemptdata Attempt Data Object
 * @param  int    $number      Question Number
 * @param  int    $cmid        Course Module ID
 * @param  string $classname   Class name for question number button
 * @return array               Attempt information array
 */
function get_attempt_info($attemptdata, $number, $cmid, $classname = '') {
    $result = array();
    $timestart  = userdate($attemptdata->timestart, " %d %b %Y | %I:%M %p");
    $timefinish = userdate($attemptdata->timefinish, " %d %b %Y | %I:%M %p");
    $result['attemptid']        = $attemptdata->id;
    $result['attemptnumber']    = $number;
    $result['timestart']        = $timestart;
    $result['timefinish']       = $timefinish;
    $result['classname']        = $classname;
    $result['grade']            = get_attempt_grade($attemptdata->id, $cmid);
    if ($attemptdata->timefinish) {
        $timetaken            = format_time($attemptdata->timefinish - $attemptdata->timestart);
        $result['timetaken']    = $timetaken;
    } else {
        $result['timetaken']  = '';
        $result['timefinish'] = '';
    }
    return $result;
}
/**
 * Get Attempt Grade
 * @param  int    $attemptid Users quiz attempt id
 * @param  int    $cmid      Course Module ID
 * @return string            Total Grade String
 */
function get_attempt_grade($attemptid, $cmid) {
    global $DB;
    $quiz       = $DB->get_record('quiz',  array('id' => $cmid), '*');
    $sumgrades  = $DB->get_field('quiz_attempts', 'sumgrades', array('id' => $attemptid));
    $sumgrades  = quiz_rescale_grade($sumgrades, $quiz, false);
    if (!is_null($sumgrades)) {
        $totalgrade = $DB->get_field('quiz', 'grade', array('id' => $cmid));
        if (floor($sumgrades) == $sumgrades) {
            $sumgrades = floor($sumgrades);
        } else {
            $sumgrades = number_format($sumgrades, 2);
        }
        $totalgrade = format_float($totalgrade, 2);
        $grade      = $sumgrades . " / " . $totalgrade;
    } else {
        $grade      = "-- / --";
    }
    return $grade;
}
/**
 * Get quiz attempted users
 * @param  int    $quizid               Quiz ID
 * @param  string $selectedattemptsfrom Selected attempted filter
 * @param  string $selectedattempts     Attempted states
 * @param  int    $limitfrom            Limit From
 * @param  string $gradeinfo            Grade Information
 * @param  string $username             User Name
 * @param  string $sortfilter           Sort Filter
 * @param  string $needsregrade         Regraded attempt string
 * @return array                        User Ids
 */
function get_quiz_attempted_users($quizid, $selectedattemptsfrom, $selectedattempts,
    $limitfrom = 0, $gradeinfo = '', $username = '', $sortfilter = 1, $needsregrade) {
    $params[] = $quizid;
    $quiz       = \quiz_access_manager::load_quiz_and_settings($quizid);
    $courseid   = $quiz->course;
    $userquery  = '';
    $allusers = array();
    if ($username != '') {
        $params[] = "%".$username."%";
        $params[] = "%".$username."%";
        $userquery = 'AND ( u.firstname LIKE ? OR u.lastname LIKE ? )';
    }
    switch ($sortfilter) {
        case 1: $sortquery = "ORDER BY t.timefinish DESC";
            break;
        case 2: $sortquery = "ORDER BY t.timefinish ASC";
            break;
        case 3: $sortquery = "ORDER BY t.firstname ASC, t.lastname ASC";
            break;
        case 4: $sortquery = "ORDER BY t.firstname DESC, t.lastname DESC";
            break;
        default: $sortquery = "ORDER BY t.timefinish DESC";
            break;
    }
    /* This will get the selected filter from "selected attempts from".
    following are the short forms for the filters
    uaq - Enrolled Users Who Have Attempted The Quiz
    unaq - Enrolled Users Who Have Not Attempted The Quiz
    au -All Users Who Have Attempted The Quiz */
    switch ($selectedattemptsfrom) {
        case 'uaq':
            $data = get_enrolled_users_with_filter('uaq', $selectedattempts, $courseid
                , $quizid, $gradeinfo, $limitfrom, $params, $userquery, $sortquery);
            $data = array_keys($data);
            if ($needsregrade) {
                $allusers = enrolled_users_count_with_filter('uaq', $selectedattempts, $courseid,
                $quizid, $gradeinfo, $params, $userquery);
            }
            break;
        case 'unaq':
            $data = get_enrolled_users_with_filter('unaq', $selectedattempts, $courseid
                , $quizid, $gradeinfo, $limitfrom, $params, $userquery, $sortquery);
            $data = array_keys($data);
            break;
        case 'au':
            $data = get_enrolled_users_with_filter('au', $selectedattempts, $courseid
                , $quizid, $gradeinfo, $limitfrom, $params, $userquery, $sortquery);
            $data = array_keys($data);
            if ($needsregrade) {
                $allusers = enrolled_users_count_with_filter('au', $selectedattempts, $courseid,
                $quizid, $gradeinfo, $params, $userquery);
            }
            break;
        default:
            $data = [];
            break;
    }
    // Check if regrading is selected or not.
    if ($needsregrade) {
        $data = get_regraded_attempt_users($allusers, $quiz->id, $gradeinfo, $limitfrom);
    }
    return $data;
}
/**
 * Get Users count on graded and non-graded basis
 * @param  int    $quizid               Quiz ID
 * @param  string $selectedattemptsfrom Attempts from filter
 * @param  string $selectedattempts     Attempts states
 * @param  string $gradeinfo            Grade Information
 * @param  string $username             User Name
 * @param  string $needsregrade         Regrading string
 * @return int                          Userscount
 */
function get_quiz_users_count($quizid, $selectedattemptsfrom, $selectedattempts, $gradeinfo, $username, $needsregrade) {
    $params[] = $quizid;
    $quiz       = \quiz_access_manager::load_quiz_and_settings($quizid);
    $courseid   = $quiz->course;
    $userquery  = '';
    if ($username != '') {
        $params[] = "%".$username."%";
        $params[] = "%".$username."%";
        $userquery = 'AND ( u.firstname LIKE ? OR u.lastname LIKE ? )';
    }
    // Query to get the count of users.
    switch ($selectedattemptsfrom) {
        case 'uaq':
            $data = enrolled_users_count_with_filter('uaq', $selectedattempts, $courseid,
                $quizid, $gradeinfo, $params, $userquery);
            break;
        case 'unaq':
            $data = enrolled_users_count_with_filter('unaq', $selectedattempts, $courseid,
            $quizid, $gradeinfo, $params, $userquery);
            break;
        case 'au':
            $data = enrolled_users_count_with_filter('au', $selectedattempts, $courseid,
            $quizid, $gradeinfo, $params, $userquery);
            break;
        default:
            $data = 0;
            break;
    }
    if ($needsregrade) {
        $data = get_regraded_attempt_users($data, $quiz->id, $gradeinfo);
    }
    return count($data);
}
/**
 * Get user quiz attempt details
 * @param  int    $userid User ID
 * @param  int    $cmid Course Module ID
 * @param  int    $selectedattempts Selected attempt states
 * @param  string $gradeinfo Course Quiz Gradeinfo
 * @param  string $soap At most one finished attempt string.
 * @param  string $needsregrade Regrading string
 * @return array      attempt Array.
 */
function get_user_quiz_attempts($userid, $cmid, $selectedattempts, $gradeinfo = '', $soap, $needsregrade) {
    global $DB;
    $params[] = $userid;
    $params[] = $cmid;
    $result   = array();
    $soapactive = false;
    // Query to get the count of users.
    $sql = "SELECT id, timestart, timefinish, userid, state, attempt
    FROM {quiz_attempts} qa
    WHERE qa.userid = ? AND qa.quiz = ? ".$selectedattempts." AND qa.preview = 0 AND
    qa.sumgrades IS ".$gradeinfo." NULL";
    $data = $DB->get_records_sql($sql, $params);
    if (!empty($data)) {
        $attemptcount = count($data);
        $count = 1;
        foreach ($data as $attemptdata) {
            if ($count === $attemptcount) {
                $result[] = get_attempt_info($attemptdata, $attemptdata->attempt, $cmid, 'active');
                if ($attemptdata->state == 'inprogress') {
                    $soapactive = true;
                }
            } else {
                $result[] = get_attempt_info($attemptdata, $attemptdata->attempt, $cmid);
            }
            $count++;
        }
        if ($needsregrade) {
            $result = get_regraded_attempts($result, $cmid, $gradeinfo);
        }
        if ($soap) {
            if ($soapactive) {
                $result = array_slice($result, -2);
            } else {
                $result = array_slice($result, -1);
            }
        }
    }
    return $result;
}
/**
 * Function to get the Attempt object
 * @param  int $attemptid Attempt ID
 * @return object            attempt Object.
 */
function get_attempt_object($attemptid) {
    global $DB;
    $attempt    = $DB->get_record('quiz_attempts', array('id' => $attemptid), '*', MUST_EXIST);
    $quiz       = \quiz_access_manager::load_quiz_and_settings($attempt->quiz);
    $course     = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
    $cmod       = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
    $attemptobj     = new \quiz_attempt($attempt, $quiz, $cmod, $course, true);
    return $attemptobj;
}
/**
 * Function to get enrolled users with filters
 * @param  string $from             Selected Filter
 * @param  string $selectedattempts Selected States
 * @param  int $courseid        Course ID
 * @param  int $quizid          Quiz ID
 * @param  string $gradeinfo        Grade Info
 * @param  int $limitfrom       Pagination Limit Base
 * @param  array $params            SQL query parameters
 * @param  string $userquery        User SQL query
 * @param  string $sortquery        Sort SQL query
 * @return array $data             SQL query result.
 */
function get_enrolled_users_with_filter($from, $selectedattempts, $courseid, $quizid,
    $gradeinfo, $limitfrom, $params, $userquery, $sortquery) {
    global $DB;
    $quizid = $quizid;
    if ($from == 'uaq') {
        $sql = "SELECT u.id, MAX(qa.timefinish) as timefinish, u.firstname, u.lastname
            FROM {user} u
            JOIN {quiz_attempts} qa ON qa.userid = u.id
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN {course} c ON c.id = ct.instanceid AND e.courseid = c.id ";
        $sql .= "WHERE qa.quiz = ? AND qa.preview = 0 ".$selectedattempts.
        " AND u.deleted = 0 ".$userquery." AND c.id = ".$courseid.
        " AND qa.sumgrades IS ".$gradeinfo." NULL GROUP BY u.id, u.firstname, u.lastname";
        $sql = 'SELECT t.id as eguser FROM ('.$sql.') t '.$sortquery;
        $data = $DB->get_records_sql($sql, $params, $limitfrom, 5);

    } else if ($from == 'unaq') {
        $sql = "SELECT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN {course} c ON c.id = ct.instanceid AND e.courseid = c.id ";
        $sql .= "WHERE c.id = ".$courseid." AND u.id NOT IN (SELECT qa.userid FROM
            {quiz_attempts} qa WHERE qa.quiz = ?) ".$userquery." GROUP BY u.id, u.firstname, u.lastname";
        $sql = 'SELECT t.id as eguser FROM ('.$sql.') t '.$sortquery;
        $data = $DB->get_records_sql($sql, $params, $limitfrom, 5);
    } else if ($from == 'au') {
        $sql = "SELECT u.id, MAX(qa.timefinish) as timefinish, u.firstname, u.lastname
            FROM {user} u
            JOIN {quiz_attempts} qa ON qa.userid = u.id
            WHERE qa.quiz = ? AND qa.preview = 0 ".$selectedattempts.
            " ".$userquery." AND qa.sumgrades IS ".$gradeinfo.
            " NULL GROUP BY u.id, u.firstname, u.lastname";
        $sql = 'SELECT t.id as eguser FROM ('.$sql.') t '.$sortquery;
        $data = $DB->get_records_sql($sql, $params, $limitfrom, 5);
    }
    return $data;
}
/**
 * Function to get enrolled users count with filter
 * @param  string $from             Selected Filter
 * @param  string $selectedattempts Selected States
 * @param  int    $courseid         Course ID
 * @param  int    $quizid           Quiz ID
 * @param  string $gradeinfo        Grade Info
 * @param  array  $params           SQL query parameters
 * @param  string $userquery        User SQL query
 * @return array  $data             SQL query result.
 */
function enrolled_users_count_with_filter($from, $selectedattempts, $courseid, $quizid, $gradeinfo, $params, $userquery) {
    global $DB;
    $quizid = $quizid;
    if ($from == 'uaq') {
        $sql = "SELECT DISTINCT(u.id) as eguser
            FROM {user} u
            JOIN {quiz_attempts} qa ON qa.userid = u.id
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN {course} c ON c.id = ct.instanceid AND e.courseid = c.id ";
        $sql .= "WHERE qa.quiz = ? AND qa.preview = 0 ".$selectedattempts.
        " AND u.deleted = 0 ".$userquery." AND c.id = ".$courseid.
        " AND qa.sumgrades IS ".$gradeinfo." NULL ";
        $data = $DB->get_records_sql($sql, $params);
        $data = array_keys($data);
    } else if ($from == 'unaq') {
        $sql = "SELECT u.id
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {role_assignments} ra ON ra.userid = u.id
            JOIN {context} ct ON ct.id = ra.contextid AND ct.contextlevel = 50
            JOIN {course} c ON c.id = ct.instanceid AND e.courseid = c.id ";
        $sql .= "WHERE c.id = ".$courseid." AND u.id NOT IN (SELECT qa.userid FROM
            {quiz_attempts} qa WHERE qa.quiz = ?) ".$userquery." GROUP BY u.id";
        $sql = 'SELECT t.id as eguser FROM ('.$sql.') t '.$sortquery;
        $data = $DB->get_records_sql($sql, $params);
        $data = array_keys($data);
    } else if ($from == 'au') {
        $sql = "SELECT DISTINCT(u.id) as eguser
                FROM {user} u
                JOIN {quiz_attempts} qa ON qa.userid = u.id
                WHERE qa.quiz = ? ".$userquery." AND qa.preview = 0 ".$selectedattempts." AND qa.sumgrades IS ".$gradeinfo." NULL";
        $data = $DB->get_records_sql($sql, $params);
        $data = array_keys($data);
    }
    return $data;
}
/**
 * Function to get attempt regraded users
 * @param  array   $data      Array of quiz attempts
 * @param  int     $quizid    Quiz ID
 * @param  string  $gradeinfo Grade info
 * @param  int     $limitfrom Limit from user list(For pagination)
 * @return array   $data      Array of quiz attempts
 */
function get_regraded_attempt_users($data, $quizid, $gradeinfo, $limitfrom = null) {
    global $DB;
    $params['cquiz'] = $quizid;
    $sql = "SELECT DISTINCT(quiza.userid) as attemptid FROM {quiz_attempts} quiza
        JOIN {quiz_overview_regrades} qqr ON quiza.uniqueid = qqr.questionusageid
        WHERE quiza.quiz = :cquiz AND quiza.preview = 0 AND
        qqr.regraded = 0 AND quiza.sumgrades IS ".$gradeinfo." NULL ";
    $regradedusers = array_keys($DB->get_records_sql($sql, $params));
    if (!empty($data)) {
        $result = array_intersect($regradedusers, $data);
    }
    if ($limitfrom !== null) {
        $result = array_slice($result, $limitfrom, 5);
    }
    return $result;
}
/**
 * Function to get regraded attempts
 * @param  array  $data      Array of quiz attempts
 * @param  int    $quizid    Quiz ID
 * @param  string $gradeinfo Grade info
 * @return array  $data      Array of quiz attempts
 */
function get_regraded_attempts($data, $quizid, $gradeinfo) {
    global $DB;
    $params['cquiz'] = $quizid;
    $sql = "SELECT DISTINCT(quiza.id) as attemptid FROM {quiz_attempts} quiza
        JOIN {quiz_overview_regrades} qqr ON quiza.uniqueid = qqr.questionusageid
        WHERE quiza.quiz = :cquiz AND quiza.preview = 0 AND qqr.regraded = 0
        AND quiza.sumgrades IS ".$gradeinfo." NULL";
    $regradedattempts = array_keys($DB->get_records_sql($sql, $params));
    $count = 0;
    $activeclass = false;
    if (!empty($data)) {
        foreach ($data as $attemptdata) {
            if (!in_array($attemptdata['attemptid'], $regradedattempts)) {
                if (isset($attemptdata['classname'])) {
                    $activeclass = true;
                }
                unset($data[$count]);
            }
            $count++;
        }
        $data = array_values($data);
        $last = count($data);
        if ($activeclass && $last) {
            $data[$last - 1]['classname'] = 'active';
        }
    }
    return $data;
}
/**
 * Function to display the comment form on grader page.
 * @param  array $args argumnts array
 * @return HTML
 */
function block_edwiser_grader_output_fragment_comment_form($args) {
    global $CFG;
    $editoroptions = array(
        'maxfiles' => 0,
        'maxbytes' => 0,
        'trusttext' => false,
        'noclean' => true,
        'autosave' => false
    );
    $options = array(
        'editoroptions' => $editoroptions,
        'commenttext' => $args['commenttext']
    );
    $commentform = new \edwiser_commentbox_edit_form(null, $options, 'post', '',
        array("class" => "edg-comment-form"));
    return $commentform->render();
}
/**
 * Get user object by userid
 * @param  int   $userid User ID
 * @return object        User object
 */
function edg_get_user_by_id($userid) {
    global $DB;
    $user = $DB->get_record('user',  array('id' => $userid, 'deleted' => 0,
        'suspended' => 0));
    return $user;
}
/**
 * Check if license is activated or not
 * @return HTML or bool
 */
function edg_check_license() {
    global $DB;
    // Check License activated or not.
    $pluginslug = 'edwiser-grader';
    $status = $DB->get_field_select(
        'config_plugins',
        'value',
        'name = :name',
        array('name' => 'edd_' . $pluginslug . '_license_status'),
        IGNORE_MISSING
    );
    if ($status !== 'valid') {
        $lalert = '<div id="edg-grader-page"><div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <h4><i class="icon fa fa-ban"></i> Alert!</h4>';

        if (is_siteadmin()) {
            $lalert .= get_string('licensenotactiveadmin', 'block_edwiser_grader');
        } else {
            $lalert .= get_string('licensenotactive', 'block_edwiser_grader');
        }
        $lalert .= '</div></div>';
        return $lalert;
    }
    return false;
}
/**
 * Shows warning if user has not access to grading page.
 * @return HTML
 */
function edg_check_user_has_grade_access() {
    return false;
    global $DB;
    $licensedusers = $DB->get_record('config_plugins',  array('name' => 'edg_edwiser-grader_licensed_users'), 'value');
    if ($licensedusers != false) {
        $licensedusers = unserialize($licensedusers->value);
    } else {
        $licensedusers = array();
    }
    $validuser = edg_check_valid_licensed_user($licensedusers);
    if (!$validuser) {
        if (is_siteadmin()) {
            return '<div class="alert alert-danger">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.
                  get_string('invaliduser', 'block_edwiser_grader').' '.get_string('adduserhere', 'block_edwiser_grader').'
                </div>';
        } else {
            return '<div class="alert alert-danger">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                  <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string('invaliduser', 'block_edwiser_grader').'
                </div>';
        }
    }
}
/**
 * Checks current user is valid licensed user or not
 * @param  array $licensedusers Licensed user array
 * @return bool                 current user is valid or not
 */
function edg_check_valid_licensed_user($licensedusers) {
    global $USER, $CFG;
    $userinfo = current(user_get_users_by_id(array($USER->id)));
    $site     = parse_url($CFG->wwwroot, PHP_URL_HOST).''.parse_url($CFG->wwwroot, PHP_URL_PATH);
    foreach ($licensedusers as $luser) {
        if ($luser->user == $userinfo->email && $luser->site == $site) {
            return true;
        }
    }
    return false;
}
/**
 * Return HTML for license notice.
 * @return HTML
 */
function edg_show_license_notice() {
    // Get license data from license controller.
    $lcontroller = new edwiser_grader_license_controller();
    $getlidatafromdb = $lcontroller->get_data_from_db();
    $lalert = '';
    if (isloggedin() && !isguestuser()) {
        if ('available' != $getlidatafromdb) {
            $lalert .= '<div class="alert alert-danger text-center license-nag bg-red-800 text-white">
            <button type="button" class="close text-white" data-dismiss="alert" aria-hidden="true">×</button>';

            if (is_siteadmin()) {
                $lalert .= '<strong>'.get_string('licensenotactiveadmin', 'block_edwiser_grader').'</strong>';
            } else {
                $lalert .= get_string('licensenotactive', 'block_edwiser_grader');
            }

            $lalert .= '</div>';
        }
    }
    return $lalert;
}
/**
 * Get the users who can update or modify the grades
 * @param  string $ufilterquery user filter query string
 * @param  array  $params       filter query parameters
 * @return array                array of users
 */
function grade_modify_users($ufilterquery = '', $params = array()) {
    global $DB, $CFG;
    $params['ext_grade'] = 'mod/quiz:grade';
    if ($ufilterquery) {
        $ufilterquery = ' AND '.$ufilterquery;
    } else {
        $ufilterquery = ' AND u.deleted <>1';
    }
    // To get the users who can grade the quiz.
    $sql = "SELECT DISTINCT(u.id), CONCAT(u.firstname, ' ', u.lastname, ' (', u.email, ')') as name
        FROM {role_assignments} ra
        JOIN {role} r ON ra.roleid = r.id
        JOIN {role_capabilities} rc ON rc.roleid = r.id
        JOIN {user} u ON u.id = ra.userid
        JOIN {context} ct ON ra.contextid = ct.id
        JOIN {course} c ON ct.instanceid = c.id
        WHERE rc.capability = :ext_grade".$ufilterquery;
    $users = $DB->get_records_sql($sql, $params);

    // To get the site admins with user filter query.
    $sql = "SELECT u.id, CONCAT(u.firstname, ' ', u.lastname, ' (', u.email, ')') as name
        FROM {user} u
        WHERE u.id IN (".$CFG->siteadmins.")".$ufilterquery;
    // We want the same order as in $CFG->siteadmins.
    $admins = $DB->get_records_sql($sql, $params);
    foreach ($admins as $admin) {
        if (!is_user_present($admin->id, $users)) {
            array_push($users, $admin);
        }
    }
    return $users;
}
/**
 * Checks is the user is alredy present in array.
 *
 * @param  int   $id    User id
 * @param  array $users Users array
 * @return boll         True if user present in array of ojbect
 */
function is_user_present($id, $users) {
    foreach ($users as $user) {
        if ($user->id == $id) {
            return true;
        }
    }
    return false;
}
/**
 * Check if user is already exists or not
 * @param  string $site          Site URL
 * @param  string $email         Email ID
 * @param  array  $licensedusers Licensed users array
 * @return bool
 */
function edg_check_user_already_exists($site, $email, $licensedusers) {
    if (!empty($licensedusers)) {
        foreach ($licensedusers as $user) {
            if ($email == $user->user && $site == $user->site) {
                return true;
            }
        }
    }
    return false;
}
/**
 * Add grader block when user is added in licensed users.
 * @param integer $userid User ID
 */
function edg_add_grader_block($userid) {
    global $DB;
    // Delete all the previous block if exists to prevent multiple instances.
    $userobject = \core_user::get_user($userid, '*', MUST_EXIST);
    edg_remove_grader_block($userobject->email);

    $systempage = $DB->get_record('my_pages', array('userid' => $userid, 'private' => 1));
    $page = new moodle_page();
    // Selecting default region for blocks i.e. content.
    $page->blocks->add_region('content');
    $page->set_context(context_user::instance($userid));
    if ($systempage) {
        $page->blocks->add_block('edwiser_grader', 'content', -2, false, 'my-index', $systempage->id);
    }
}
/**
 * Function to remove edwiser_grader block on user license removal.
 * @param string $useremail user email
 */
function edg_remove_grader_block($useremail) {
    global $DB;
    $users = $DB->get_records('user',  array('email' => $useremail),  '',  'id');
    $users = array_keys($users);
    foreach ($users as $userid) {
        $params = array('edwiser_grader', CONTEXT_USER, $userid);
        $sql = "SELECT bi.*
                FROM {block_instances} bi
                JOIN {context} c ON c.id = bi.parentcontextid
                WHERE bi.blockname = ? AND c.contextlevel = ? AND c.instanceid = ?";
        $instances = $DB->get_records_sql($sql, $params);
        if (!empty($instances)) {
            foreach ($instances as $instance) {
                blocks_delete_instance($instance);
            }
        }
    }
}
/**
 * Function that will add all the users from "All Users" column to "Licensed Users"
 * @param  array    $users         Users list
 * @param  array    $licensedusers Licensed user array
 * @return Resposne
 */
function edg_add_user_to_license($users, $licensedusers) {
    global $CFG;
    $lcontroller = new edwiser_grader_license_controller();
    $site = parse_url($CFG->wwwroot, PHP_URL_HOST).''.parse_url($CFG->wwwroot, PHP_URL_PATH);
    $resp = '';

    $newlicensedusers = array();
    foreach ($users as $userid) {
        $userdata = edg_get_user_by_id($userid);
        $exists = edg_check_user_already_exists($site, $userdata->email, $licensedusers);
        if (!$exists) {
            $luser = new stdClass();
            $luser->site = $site;
            $luser->user = $userdata->email;
            $newlicensedusers[] = $luser;
            edg_add_grader_block($userid);
        }
    }
    $licensedusers = array_merge($licensedusers, $newlicensedusers);
    foreach ($licensedusers as $key => $object) {
        if ($object->site !== $site) {
            unset($licensedusers[$key]);
        }
    }
    $resp = $lcontroller->handle_users_api('update', $licensedusers);
    $resp = json_decode($resp);
    if (!empty($resp) && isset($resp->users)) {
        set_config('edg_edwiser-grader_licensed_users', serialize($resp->users), 'block_edwiser_grader');
    }
    return $resp;
}
/**
 * Function to remove users from licensed users
 * @param  array    $users         Users list
 * @param  array    $licensedusers Licensed Users
 * @return Response
 */
function edg_remove_users_from_license($users, $licensedusers) {
    global $CFG;
    $lcontroller = new edwiser_grader_license_controller();
    $site = parse_url($CFG->wwwroot, PHP_URL_HOST).''.parse_url($CFG->wwwroot, PHP_URL_PATH);
    $resp = '';
    foreach ($users as $index) {
        if (isset($licensedusers[$index])) {
            $usersite = $licensedusers[$index]->site;
            if ($usersite == $site) {
                edg_remove_grader_block($licensedusers[$index]->user);
                unset($licensedusers[$index]);
            }
        } else {
            return $resp;
        }
    }

    foreach ($licensedusers as $key => $object) {
        if ($object->site !== $site) {
            unset($licensedusers[$key]);
        }
    }

    $resp = $lcontroller->handle_users_api('update', $licensedusers);
    $resp = json_decode($resp);

    if (!empty($resp) && isset($resp->users)) {
        set_config('edg_edwiser-grader_licensed_users', serialize($resp->users), 'block_edwiser_grader');
    }
    return $resp;
}

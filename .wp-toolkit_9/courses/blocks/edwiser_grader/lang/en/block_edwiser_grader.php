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
 * Strings for component 'block_edwiser_grader', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Edwiser RapidGrader';
$string['title'] = 'Edwiser RapidGrader';
$string['coursequizzes'] = 'Course Quizzes';
$string['edwiser_grader:addinstance'] = 'Add a new edwiser grader activity';
$string['edwiser_grader:myaddinstance'] = 'Add a new edwiser grader activity';
$string['requiresgrading'] = 'Requires Grading';
$string['gradeddone'] = 'Graded';
$string['notattempted'] = 'Not Attempted';
$string['selectcourses'] = 'Select Courses';
$string['noquizzesmsg'] = 'No quizzes available/attempted in this course.';
$string['noattemptmademsg'] = 'No attempt made in the quiz.';
$string['noquizfoundmsg'] = 'Quiz not found.';
$string['searchplaceholder'] = 'Search by quiz name ...';
$string['notyetgraded'] = 'Not Graded Users';
$string['graded'] = 'Graded Users';
$string['grademessage'] = 'The one attempt for each user that is graded is highlighted';
$string['requiregrading'] = 'Require Grading';
$string['notgradedquestion'] = 'Show only non-graded questions';
$string['timetaken'] = 'Time Taken';
$string['notanswered'] = 'Not Answered';
$string['more'] = 'More';
$string['viewless'] = 'View Less';
$string['viewresponsehistory'] = 'View Response History';
$string['responsehistory'] = 'Response History';
$string['successupdatemsg'] = 'Marks updated successfully !!!';
$string['markserrormessage'] = 'Incorrect marks. Please enter correct marks !!!';
$string['regradeall'] = 'Regrade All';
$string['dryrunregrade'] = 'Dry Run a Full Regrade';
$string['microsoftexcel'] = 'Microsoft Excel (.xlsx)';
$string['htmltable'] = 'HTML table';
$string['jsonformat'] = 'Javascript Object Notation (.json)';
$string['opendocs'] = 'OpenDocument (.ods)';
$string['selectfilters'] = 'Select Filters';
$string['enterusrmsg'] = 'Enter user name';
$string['newestfirst'] = 'Newest First';
$string['oldestfirst'] = 'Oldest First';
$string['AtoZ'] = 'A - Z';
$string['ZtoA'] = 'Z - A';
$string['deletedselected'] = 'Delete Selected';
$string['regradeselected'] = 'Regrade Selected';
$string['setfilters'] = 'Set Filters';
$string['showattemptfrom'] = 'Show Attempts From';
$string['showattempt'] = 'Show Attempts';
$string['reportshowonlyfinished'] = 'Show at most one finished attempt per user';
$string['regradedattemptsmsg'] = 'Show only attempts that have been regraded OR marked as needed grading.';
$string['markseachquestion'] = 'Show marks for each question';
$string['applyfilter'] = 'Apply Filter';
$string['deletedsuccessmsg'] = 'Attempt deleted successfully !!!';
$string['regradedsuccessmsg'] = 'Attempt regraded successfully !!!';
$string['norecordsmessage'] = 'No records found :(';
$string['dryregradedsuccessmsg'] = "Dry run completed successfully !!!";
$string['marks'] = 'Marks';
$string['dryruninprogress'] = 'Dry Run in progress ...';
$string['regradeinprogress'] = 'Regrading in progress ...';
$string['noattemptsmsg'] = 'No attempts made !!!';
$string['goback'] = 'Go Back';
$string['deletequestion'] = 'Do you really want to delete the selected attempts ?';
$string['deleteboxheader'] = 'Attempt Delete Confirmation';
$string['ok'] = 'OK';
$string['gradechart'] = 'Grade Chart';
$string['downloadreport'] = 'Download Report';
$string['regrade'] = 'Regrade';
$string['selectall'] = 'Select All';
$string['grade'] = 'Grade';
$string['participants'] = 'Participants';
$string['question'] = 'Question';
$string['studentresponse'] = 'Student\'s Response';
$string['commentandgrade'] = 'Comment and Grade';
$string['type'] = 'Question Type';
$string['qnumber'] = 'Question Number';
$string['graderinfo'] = 'Grader Info';
$string['regrade'] = 'Re-Grade';
$string['userbasedgrading'] = 'User based grading';
$string['questionbasedgrading'] = 'Question based grading';
$string['studentnameoption'] = 'Show student name to teacher';
$string['search'] = 'Search Teachers Name';
$string['searchquestion'] = 'Search Question Name';
$string['noquestionsfound'] = 'No questions found ...';
$string['students'] = 'Students';
$string['attempt'] = 'Attempt';
$string['processingmsg'] = "Your marks is being updated ...";
$string['configtitle'] = 'Edwiser RapidGrader';
$string['edg-genral-settings'] = 'General Settings';
$string['licensestatus'] = 'License Status';
$string['add_remove_users'] = 'Add / Remove Users';
$string['userroles'] = 'User Roles';
$string['edg-general-desc'] = 'Allow teachers to see a students name while grading.';
/* License Settings */
$string['edwisergraderlicenseactivation'] = 'Edwiser RapidGrader License Activation';
$string['licensestatus'] = 'License Status';
$string['licensenotactive'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license in Edwiser RapidGrader settings.';
$string['licensenotactiveadmin'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license <a href="'.$CFG->wwwroot.'/blocks/edwiser_grader/classes/edwiser_grader_license.php" >here</a>.';
$string['activatelicense'] = 'Activate License';
$string['deactivatelicense'] = 'Deactivate License';
$string['renewlicense'] = 'Renew License';
$string['active'] = 'Active';
$string['notactive'] = 'Not Active';
$string['expired'] = 'Expired';
$string['licensekey'] = 'License key';
$string['noresponsereceived'] = 'No response received from the server. Please try again later.';
$string['licensekeydeactivated'] = 'License Key is deactivated.';
$string['siteinactive'] = 'Site inactive (Press Activate license to activate plugin).';
$string['entervalidlicensekey'] = 'Please enter valid license key.';
$string['licensekeyisdisabled'] = 'Your license key is Disabled.';
$string['licensekeyhasexpired'] = "Your license key has Expired. Please, Renew it.";
$string['licensekeyactivated'] = "Your license key is activated.";
$string['enterlicensekey'] = "Please enter correct license key.";
$string['invaliduser'] = '<strong>Alert!</strong> You do not have permission to grade.';
$string['adduserhere'] = 'You can add users <a href="'.$CFG->wwwroot.'/blocks/edwiser_grader/classes/add_remove_users.php" >here</a>.';
$string['usersaddsuccess'] = "Users added successfully.";
$string['useraddsuccess'] = "User added successfully.";
$string['usersremovesuccess'] = "Users removed successfully.";
$string['userremovesuccess'] = "User removed successfully.";
$string['userremovefail'] = "Can not remove other site users.";
$string['filtererrormsg'] = 'You must select at least one state (Inprogress, Overdue etc.)';
$string['nocoursesmsg'] = 'No courses available/visible. Please contact Site Administrator.';
$string['attemptinprogress'] = 'The attempt is in-progress.';
$string['licensedusers'] = 'Licensed Users ({$a->count}/{$a->total})';
$string['nolicensedusers'] = 'No Licensed Users';
$string['quesgradedauto'] = 'Also show questions that have been graded automatically';
$string['needsregrading'] = 'Needs Regrading';
$string['qno'] = 'Q#';
$string['qtype'] = 'Question Type';
$string['qname'] = 'Question Name';
$string['alreadygraded'] = 'Already Graded';
$string['total'] = 'Total';
$string['userexists'] = 'Selected user is already added.';
$string['autograded'] = 'Automatically Graded';
$string['success'] = 'Success';
$string['alert'] = 'Alert!';
$string['allusers'] = 'All Users';
$string['licensedusers'] = 'Licensed Users';
$string['upgradeplan'] = 'Upgrade Plan';
$string['seatspurchased'] = 'Seats Purchased';
$string['teachersenrolled'] = 'Teachers Enrolled';
$string['licensedsites'] = 'License Activated Sites';
$string['addremovemsg'] = 'You can not add or remove users from other sites.';
$string['anonymous'] = 'Anonymous';
$string['user'] = 'User';

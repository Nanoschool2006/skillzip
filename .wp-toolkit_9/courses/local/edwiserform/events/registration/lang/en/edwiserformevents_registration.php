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
 * English language
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

require_once($CFG->dirroot . '/local/edwiserform/classes/controller.php');

$string['pluginname'] = 'Registration Form';
$string['efb-event-registration-name'] = 'User Registration Form';
$string['efb-event-registration-desc'] = 'Use this form whenever you want to customize the existing registration form of Moodle. Works best when you want to only get relevant information from your users during registration.';
$string['efb-header-registration-settings'] = 'Registration Settings';
$string['efb-select-element'] = 'Select Element';
$string['efb-field-firstname'] = 'Firstname';
$string['efb-field-lastname'] = 'Lastname';
$string['efb-field-username'] = 'Username';
$string['efb-field-password'] = 'Password';
$string['efb-field-password2'] = 'Password Again';
$string['efb-field-gender'] = 'Gender';
$string['efb-field-email'] = 'Email';
$string['efb-field-email2'] = 'Email Again';
$string['efb-field-phone'] = 'Phone';
$string['efb-field-country'] = 'Country';
$string['efb-field-address'] = 'Address';
$string['efb-field-username-warning'] = 'Username required';
$string['efb-field-username-duplicate-warning'] = 'Username duplicate';
$string['efb-field-firstname-warning'] = 'Firstname required';
$string['efb-field-lastname-warning'] = 'Lastname required';
$string['efb-field-email-warning'] = 'Email required';
$string['efb-field-email-duplicate-warning'] = 'Email duplicate';
$string['efb-field-email2-warning'] = 'Email does not match';
$string['efb-field-password2-warning'] = 'Password does not match';
$string['efb-registration-failed'] = 'Unable to register.';
$string['efb-registration-success'] = 'Registered successfully.';
$string['efb-header-registration-enable'] = 'Enable registration';
$string['registration-disable-confirmation'] = 'Disable confirmation message';
$string['registration-disable-confirmation_desc'] = 'By enabling this, form\'s confirmation message will not be sent. Only user account confirmation email will be sent.';

// Form data list string.
$string['confirm'] = 'Confirm';
$string['suspenduser'] = 'Suspend';
$string['unsuspenduser'] = 'Unsuspend';
$string['unsupportedaction'] = 'Action is not supported.';
$string['action' . USER_UNCONFIRMED . 'success'] = 'User account confirmed successfully.';
$string['action' . USER_UNSUSPENDED . 'success'] = 'User account suspended successfully.';
$string['action' . USER_SUSPENDED . 'success'] = 'User account unsuspended successfully.';

// Task strings.
$string['fix_auth'] = 'Fix auth issues';

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
 * Edwiser Forms settings
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

$lcontroller = new local_edwiserform_license_controller();
$lcontroller->addData();

// Add admin menues.
$ADMIN->add('modules', new admin_category('edwiserform', new lang_string("pluginname", "local_edwiserform")));
$ADMIN->add('edwiserform',
            new admin_externalpage(
                'efbnewform',
                new lang_string("efb-heading-newform", "local_edwiserform"),
                new moodle_url("/local/edwiserform/view.php?page=newform")
            )
        );
$ADMIN->add('edwiserform',
            new admin_externalpage(
                'efblistforms',
                new lang_string("efb-heading-listforms", "local_edwiserform"),
                new moodle_url("/local/edwiserform/view.php?page=listforms")
            )
        );
$ADMIN->add('edwiserform',
            new admin_externalpage(
                'efbsettings',
                new lang_string("efb-settings", "local_edwiserform"),
                new moodle_url("/admin/settings.php?section=local_edwiserform")
            )
        );

// Add admin menues.
// Adding tab setting for the edwiserform.
$settings = new local_edwiserform_admin_settingspage_tabs('local_edwiserform', get_string('configtitle', 'local_edwiserform'));

$ADMIN->add('localplugins', $settings);



// General settings tab.
$page = new admin_settingpage('local_edwiserform_general', get_string('efb-general-settings', 'local_edwiserform'));

// Genral settings tab heading.
$page->add(new admin_setting_heading(
    'local_edwiserform_general',
    get_string('efb-general-settings', 'local_edwiserform'),
    format_text('', FORMAT_MARKDOWN)
));

// Checkbox for enabling teacher to create new form.
$page->add(new admin_setting_configcheckbox(
    "local_edwiserform/enable_teacher_forms",
    new lang_string("efb-enable-user-level-from-creation", "local_edwiserform"),
    new lang_string("efb-des-enable-user-level-from-creation", "local_edwiserform"),
    false
));

// Google Recaptcha site key.
$page->add(new admin_setting_configtext(
    "local_edwiserform/google_recaptcha_sitekey",
    new lang_string("efb-google-recaptcha-sitekey", "local_edwiserform"),
    new lang_string("efb-desc-google-recaptcha-sitekey", "local_edwiserform"),
    'null'
));

// Enable navigation using sidebar.
$page->add(new admin_setting_configcheckbox(
    "local_edwiserform/enable_sidebar_navigation",
    new lang_string("efb-enable-site-navigation", "local_edwiserform"),
    new lang_string("efb-desc-enable-site-navigation", "local_edwiserform"),
    true
));

// Usage tracking GDPR setting.
$page->add(new admin_setting_configcheckbox(
    'local_edwiserform/enableusagetracking',
    new lang_string('enableusagetracking', 'local_edwiserform'),
    new lang_string('enableusagetrackingdesc', 'local_edwiserform'),
    true
));

$settings->add($page);

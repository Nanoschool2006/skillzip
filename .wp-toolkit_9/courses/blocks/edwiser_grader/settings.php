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
 * Plugin administration pages are defined here.
 *
 * @package   block_edwiser_grader
 * @copyright Copyright (c) 2020 WisdmLabs. (http://www.wisdmlabs.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$settings = null;
if (is_siteadmin()) {
    $ADMIN->add(
        'blocksettings',
        new admin_category('block_edwiser_grader_category', get_string('pluginname', 'block_edwiser_grader'))
    );
    $settingspage = new admin_settingpage('edgsettings' , get_string('edg-genral-settings', 'block_edwiser_grader'));
    $ADMIN->add('block_edwiser_grader_category', $settingspage);
    // Setting to display the student name on grader page.
    $settingspage->add(new admin_setting_configcheckbox(
        // This is the reference you will use to your configuration.
        'block_edwiser_grader/studentnameoption',
        // This is the friendly title for the config, which will be displayed.
        new lang_string('studentnameoption', 'block_edwiser_grader'),
        // This is helper text for this config field.
        new lang_string('edg-general-desc', 'block_edwiser_grader'),
        // This is the default value.
        1
    ));

    // License Status Setting Page.
    $ADMIN->add(
        'block_edwiser_grader_category',
        new admin_externalpage(
                'block_edwiser_grader_licensestatus',
                 get_string('licensestatus', 'block_edwiser_grader'),
                 new moodle_url('/blocks/edwiser_grader/classes/edwiser_grader_license.php'),
                'moodle/site:config'
        )
    );

    // Add Remove Users for User based licensing Settings Page.
    // @codingStandardsIgnoreLine
    /* $ADMIN->add(
        'block_edwiser_grader_category',
        new admin_externalpage(
                'block_edwiser_grader_addremoveusers',
                 get_string('add_remove_users', 'block_edwiser_grader'),
                 new moodle_url('/blocks/edwiser_grader/classes/add_remove_users.php'),
                'moodle/site:config'
        )
    ); */
}

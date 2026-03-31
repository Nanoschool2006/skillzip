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
 * Edwiser form fix auth task
 *
 * @package edwiserformevents_registration
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace edwiserformevents_registration\task;

defined('MOODLE_INTERNAL') || die;

class fix_auth extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('fix_auth', 'edwiserformevents_registration');
    }

    /**
     * Execute the scheduled task.
     * Fixed user auth issue for previous user whose auth is set to empty.
     */
    public function execute() {
        global $DB;

        $this->log_start('Starting fix authentication issues task');

        $this->log('Checking user account with authentication issue created using Edwiser Form registration.');
        $users = $DB->get_records_sql("
            SELECT u.* FROM {efb_form_data} fd
            JOIN {efb_forms} f ON fd.formid = f.id
            JOIN {user} u ON fd.userid = u.id
            WHERE (f.type = ?
                OR f.events LIKE ?)
            AND u.auth = ''
        ", array('registration', '%registration%'));

        if (empty($users)) {
            $this->log('No authentication issues found.');
        } else {
            if (count($users) == 1) {
                $this->log("Found " . 1 . " user account with authentication issue.");
            } else {
                $this->log("Found " . count($users) . " user accounts with authentication issue.");
            }
            foreach ($users as $id => $user) {
                $user->auth = 'email';
                $DB->update_record('user', $user);
                $this->log("Fixed authentication issue of userid {$id}.");
            }
        }

        $this->log_finish('Finished fix authentication issues task.');
    }
}

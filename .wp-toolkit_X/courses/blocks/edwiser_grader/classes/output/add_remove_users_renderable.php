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
require_once($CFG->dirroot . '/blocks/edwiser_grader/classes/license_controller.php');

/**
 * Add or remove users from allowed teachers list
 */
class add_remove_users_model implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return Object
     */
    public function export_for_template(renderer_base $output) {
        global $SESSION, $CFG;
        $output = null;
        $context = new \stdClass();
        $siteurl = parse_url($CFG->wwwroot, PHP_URL_HOST).''.parse_url($CFG->wwwroot, PHP_URL_PATH);
        $licensedusers = get_config('block_edwiser_grader', 'edg_edwiser-grader_licensed_users');
        if ($licensedusers) {
            $licensedusers = unserialize($licensedusers);
        } else {
            $licensedusers = array();
        }

        if (isset($_POST['adduser']) && isset($_POST['ausers']) && !empty($_POST['ausers'])) {
            $resp = edg_add_user_to_license($_POST['ausers'], $licensedusers);
            if (isset($resp->success) && $resp->success) {
                if (empty($resp->modified->new) && empty($resp->modified->updated)) {
                    $context->errormsg = get_string('userexists',  'block_edwiser_grader');
                } else {
                    $context->successmsg = get_string('usersaddsuccess',  'block_edwiser_grader');
                }
            }
        }

        if (isset($_POST['removeuser']) && isset($_POST['lusers']) && !empty($_POST['lusers'])) {
            $resp = edg_remove_users_from_license($_POST['lusers'], $licensedusers);
            if (isset($resp->success) && $resp->success) {
                $context->successmsg = get_string('usersremovesuccess',  'block_edwiser_grader');
            }
            if (isset($resp->modified->removed) && empty($resp->modified->removed)) {
                $context->errormsg = get_string('userremovefail',  'block_edwiser_grader');
            }
        }

        $lcontroller = new \edwiser_grader_license_controller();
        $lusers = $lcontroller->edd_get_users_from_api();
        $lsites = array();
        $susers = array();
        foreach ($lusers->users as $key => $user) {
            if ($user->site == $siteurl) {
                $user->key = $key;
                array_push($susers, $user);
            }
            array_push($lsites, $user->site);
        }
        $sites = array();
        foreach (array_unique($lsites) as $site) {
            $sitedetails = new \stdClass();
            $sitedetails->name = $site;
            array_push($sites, $sitedetails);
        }
        $ausers = grade_modify_users();
        $context->sites = $sites;
        $context->seatspurchased = $lusers->limit;
        $context->teachersenrolled = $lusers->used_seats;
        $context->lusers = $susers;
        $context->ausers = array_values($ausers);
        $context->sesskey = sesskey();
        return $context;
    }
}

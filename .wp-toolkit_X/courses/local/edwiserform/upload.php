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
 * Ajax endpoint to upload file
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 * @since     Edwiser Form 1.1.0
 */

require_once('../../config.php');

use local_edwiserform\controller as controller;

define('AJAX', true);

$controller = controller::instance();

require_login();

$file = $_FILES[0];

if (($file['size'] > get_max_upload_file_size($CFG->maxbytes))) {
    $response = array(
        'status' => false,
        'msg' => get_string('userquotalimit')
    );
    echo json_encode($response);
    die;
}


$fs = get_file_storage();

$filerecord = new stdClass;
$filerecord->contextid = context_system::instance()->id;
$filerecord->component = EDWISERFORM_COMPONENT;
$filerecord->filearea  = EDWISERFORM_USER_FILEAREA;
$filerecord->filepath  = '/';
$filerecord->filename  = $file["name"];
$filerecord->itemid    = $controller->file_get_unused_edwiserform_itemid();
$filerecord->license   = $CFG->sitedefaultlicense;
if ($controller->is_logged_and_not_guest()) {
    $filerecord->userid    = $USER->id;
    $filerecord->author    = $USER->firstname . ' ' . $USER->lastname;
}
$filerecord->source    = serialize((object)array('source' => $file["name"]));
$fs->create_file_from_pathname($filerecord, $file["tmp_name"]);
$response = array(
    'status' => true,
    'itemid' => $filerecord->itemid
);
echo json_encode($response);

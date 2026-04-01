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
 * @package   local_edwiserpagebuilder
 * @copyright (c) 2021 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Sudam Chakor
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_edwiserpagebuilder_get_media_list' => [
        'classname'     => 'local_edwiserpagebuilder\external\epb_api',
        'methodname'    => 'edwiser_get_media_list',
        'classpath'     => '',
        'description'   => 'List down the media files from the given context.',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
    ],
    'local_edwiserpagebuilder_save_media_files' => [
        'classname'     => 'local_edwiserpagebuilder\external\epb_api',
        'methodname'    => 'edwiser_save_media_files',
        'classpath'     => '',
        'description'   => 'Saves the media files in the draft area.',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
    ],
    'local_edwiserpagebuilder_delete_media_file' => [
        'classname'     => 'local_edwiserpagebuilder\external\epb_api',
        'methodname'    => 'edwiser_delete_media_file',
        'classpath'     => '',
        'description'   => 'Deletes the given media file.',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
    ],
    'edwiserpagebuilder_fetch_blocks_list' => [
        'classname'     => 'local_edwiserpagebuilder\external\epb_api',
        'methodname'    => 'edwiser_fetch_blocks_list',
        'classpath'     => '',
        'description'   => 'Fetches edwiser blocks list.',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
    ],
    'edwiserpagebuilder_update_block_content' => [
        'classname'     => 'local_edwiserpagebuilder\external\epb_api',
        'methodname'    => 'edwiser_update_block_content',
        'classpath'     => '',
        'description'   => 'Updates block content by fetching new content from json.',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
    ],
];

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
 * @author    Gourav Govande
 */

namespace local_edwiserpagebuilder;

defined('MOODLE_INTERNAL') || die;

/**
 * Blocks Manager
 */
class block_handler
{
    // Class members declaration
    private $id;
    private $title;
    private $label;
    private $thumbnail;
    private $content;
    private $version;
    private $update;
    private $visible;

    // Table name where block data is stored at.
    private $table = "edw_page_blocks";

    // Insert or Update the block content.
    public function make_entry($data) {
        global $DB;

        $record = $this->get_data_with_title($data->title);
        try {
            if (!$record) {
                // Make an entry for new data.
                    $DB->insert_record($this->table, $data, true, false);
                // $this->cache->set();
            } else {
                // Update the db only when latest version is available.
                if ($data->version > $record->version) {
                    // Update - updateavailable parameter only
                    $record->updateavailable = 1;
                    $record->thumbnail = $data->thumbnail;
                    $DB->update_record($this->table, $record, false);
                }
            }
        } catch (Exception $ex) {
            return $e->getMessage();
        }
        return true;
    }

    // Fetch and update the block content.
    public function update_block_content($data) {
        global $DB;
        $record = $this->get_data_with_title($data->title);
        if ($record) {
            $data->id = $record->id;
            $data->updateavailable = 0;
            if ($data->version > $record->version) {
                try {
                    $DB->update_record($this->table, $data, false);
                    return true;
                } catch (Exception $ex) {
                    return $e->getMessage();
                }
            }
        }
    }

    // Get the DB record for given title.
    public function get_data_with_title($title) {
        global $DB;

        $record = $DB->get_record($this->table, array('title' => $title), '*');

        if (!$record) {
            return null;
        }

        return $record;
    }

    // public function get_cache_object() {
    // return \cache::make('local_edwiserpagebuilder', 'edwiserblockcontent');
    // }
    /**
     * Fetch blocks list
     * @param limitfrom = 0
     * @param limitto Count, if -1 returns all the blocks
     */
    public function fetch_blocks_list($limitfrom = 0, $limitto = -1) {
        global $DB;

        if ($limitto == -1) {
            return $DB->get_records(
                $this->table,
                array('visible' => 1),    // no condition applied
                'title',    // sort by title
                '*'
            );
        }
        return $DB->get_records(
            $this->table,
            array('visible' => 1),    // no condition applied
            'title',    // sort by title
            '*',        // Get all the fields
            $limitfrom, // Limit start
            $limitto    // limit upto
        );
    }

}

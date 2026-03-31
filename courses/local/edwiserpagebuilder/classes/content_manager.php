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

global $CFG;
require_once($CFG->dirroot . "/local/edwiserpagebuilder/lib.php");
define_cdn_constants();

use stdClass;

/**
 * content_manager class handles everything related to block contents.
 */
class content_manager
{

    public function get_json_file_data($url) {
        global $CFG;

        require_once($CFG->libdir . "/filelib.php");

        $c = new \curl;
        $html = $c->get($url);

        // TODO :  Error handling
        return json_decode($html);
    }

    // Update the block content.
    public function update_block_content() {

        $bm = new block_handler();

        // Here we Update all the blocks content.
        $data = $this->get_json_file_data(BLOCKS_LIST_URL);
        foreach ($data->blocks as $key => $block) {

            $content = $this->get_json_file_data(BLOCKS_CONTENT_URL . $block->title . ".json");

            if ($content) {
                // Encrypting the content
                $content->content = json_encode($content->content);
                $bm->make_entry($content);
            }
        }
    }

    public function update_block_content_by_name($blockname) {
        $bm = new block_handler();

        if ($blockname != "") {
            // Here we update the block content by block name.
            $content = $this->get_json_file_data(BLOCKS_CONTENT_URL . $blockname . ".json");

            if ($content) {
                // Encrypting the content
                $content->content = json_encode($content->content);
                return $bm->update_block_content($content);// true to update the content.
            } else {
                return get_string("unabletofetchjson", "local_edwiserpagebuilder");
            }
        } else {
            return get_string("provideproperblockname", "local_edwiserpagebuilder");
        }
    }

    public function generate_add_block_modal() {
        global $PAGE, $CFG, $OUTPUT;

        require_once($CFG->libdir . '/blocklib.php');

        $blockslist = [];
        if (check_plugin_available("block_edwiseradvancedblock")) {
            $bm = new block_handler();
            $blocks = $bm->fetch_blocks_list(); // Fetching Edwiser Blocks
            $templatecontext['edwpageurl'] = strstr($PAGE->url->out(false), "?");
            $templatecontext['can_fetch_blocks'] = true;
            foreach ($blocks as $key => $block) {
                $obj = new stdClass();
                $actionurl = $PAGE->url->out(false, array('bui_addblock' => '', 'sesskey' => sesskey()));
                $obj->url = strstr($actionurl, "?");// removes string upto substring i.e. "?"
                $obj->name = "edwiseradvancedblock";
                $obj->section = $block->title;
                $obj->title = $block->label;
                $obj->thumbnail = str_replace("{{>cdnurl}}", CDNIMAGES, $block->thumbnail);
                $obj->updateavailable = $block->updateavailable;
                $blockslist[] = $obj;
            }
        }

        $bm = new \block_manager($PAGE);
        $bm->load_blocks(); // Loading all block plugins
        $coreblocks = $bm->get_addable_blocks();
        $blockslist = array_merge($blockslist, $coreblocks); // Fetching other block plugins

        foreach ($blockslist as $key => $block) {
            $actionurl = $PAGE->url->out(false, array('bui_addblock' => '', 'sesskey' => sesskey()));
            $block->url = strstr($actionurl, "?");// removes string upto substring i.e. "?"

            if (!isset($block->thumbnail)) {
                $block->thumbnail = $CFG->wwwroot . "/local/edwiserpagebuilder/default.png";
            }

            // Remove edwiseradvancedblock from list
            if (!isset($block->section) && $block->name == "edwiseradvancedblock") {
                unset($blockslist[$key]);
            }
        }

        $blockslist = array_values($blockslist);

        $templatecontext['blocks'] = $blockslist;

        return $OUTPUT->render_from_template('local_edwiserpagebuilder/custom_modal', $templatecontext);
    }

    public function create_floating_add_a_block_button() {
        global $OUTPUT;

        $context['buttons']['ele_id'] = 'epbaddblockbutton';
        $context['buttons']['bgcolor'] = '#11c26d';
        $context['buttons']['title'] = get_string('addblock', 'core');
        $context['buttons']['icon'] = 'fa fa-plus';

        return $OUTPUT->render_from_template('local_edwiserpagebuilder/floating_buttons', $context);
    }
}

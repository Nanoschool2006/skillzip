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
 * Import form class definition
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

namespace local_edwiserform\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use renderable;
use templatable;
use stdClass;

/**
 * Class contains definition for import form
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form implements renderable, templatable {

    /**
     * mform object
     * @var null
     */
    private $mform = null;

    /**
     * Form values errors list
     * @var array
     */
    private $errors = [];

    /**
     * Constructor to initialize required variables
     *
     * @param object $mform  Import form object
     * @param array  $errors Errors list
     */
    public function __construct($mform, $errors) {
        $this->mform = $mform;
        $this->errors = $errors;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template.
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @since  Edwiser Form 1.0.0
     */
    public function export_for_template(\renderer_base $output) {
        global $CFG;
        $data = new stdClass;
        $data->pageactions = $this->get_page_actions();
        $data->form = $this->mform->render();
        $data->haserrors = !empty($this->errors);
        $data->errors = $this->errors;
        return $data;
    }

    /**
     * Get list of page actions
     * @return array Page actions
     */
    private function get_page_actions() {
        $actions = array(
            array(
                'url' => new moodle_url('/local/edwiserform/view.php', array('page' => 'newform')),
                'label' => get_string('efb-heading-newform', 'local_edwiserform')
            ),
            array(
                'url' => new moodle_url('/local/edwiserform/view.php', array('page' => 'listforms')),
                'label' => get_string('efb-heading-listforms', 'local_edwiserform')
            ),
            array(
                'url' => new moodle_url('/admin/settings.php', array('section' => 'local_edwiserform')),
                'label' => get_string('efb-settings', 'local_edwiserform')
            ),
        );
        return $actions;
    }

}

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
 * Version information
 *
 * @package     mod_edwiserform
 * @copyright   2019 WisdmLabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Edwiser Forms 1.2.0
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_edwiserform';
$plugin->release = '1.2.0';
$plugin->version  = 2020062600;
$plugin->requires = 2016052314;
$plugin->maturity = MATURITY_STABLE;
$plugin->dependencies = array('local_edwiserform' => 2020022100);

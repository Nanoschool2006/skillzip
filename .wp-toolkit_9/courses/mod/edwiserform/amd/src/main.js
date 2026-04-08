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
 * Add preview button after forms select box
 *
 * @package     mod_edwiserform
 * @copyright   2018 WisdmLabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since       Edwiser Form 1.0.0
 */
define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {
	return {
		init: function() {
			$(document).ready(function() {
				$('body').on('formSubmitted', function(event) {
					console.log(event);
					if ($('#cmid').val() == event.cmid) {
						promise = ajax.call([{
							methodname: 'mod_edwiserform_submitted',
							args: {
								cmid: event.cmid
							}
						}]);
						promise[0].fail(function(ex) {
							notification.exception(ex);
							console.log(ex);
						});
					}
				});
			});
		}
	}
});

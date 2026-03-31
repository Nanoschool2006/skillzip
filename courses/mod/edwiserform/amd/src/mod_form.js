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
define(['jquery', 'local_edwiserform/formviewer'], function($) {
	return {
		init: function() {
			$(document).ready(function() {

				var newpagelink = M.cfg.wwwroot + '/local/edwiserform/view.php?page=newform&mod_edwiserform=true';

				var windowproperties = 'directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no,width=1280,height=800';

				window['createeditform'] = null;

				/**
				 * Function to add newly created form in select dropdown and set current current
				 * @param  {Object} data Response data from Add New Form or Edit Form window
				 */
				window.local_edwiserform_add_new_form = function(data) {
					$('select#id_form').append('<option value="' + data.formid + '">' + data.formname + '</option>');
					$('#id_form').val(data.formid);
					createeditform.close();
					createeditform = null;
				};

				/**
				 * Function to update form name edited
				 * @param  {Object} data Response data from Edit Form window
				 */
				window.local_edwiserform_edit_form = function(data) {
					$('select#id_form option[value="' + data.formid + '"]').text(data.formname);
					$('#id_form').val(data.formid);
					createeditform.close();
					createeditform = null;
				}

				/**
				 * Show warning to user if he is already edting for in another window
				 * @param  {String} link Link to open if user confirms
				 */
				function form_open_warning(link) {
					Formeo.dom.multiActions(
	                    'warning',
	                    M.util.get_string('warning', 'mod_edwiserform'),
	                    M.util.get_string('formopen', 'mod_edwiserform'),
	                    [{
	                        title: M.util.get_string('discard', 'mod_edwiserform'),
	                        type: 'warning',
	                        action: function() {
	                        	createeditform.close();
	                            createeditform = window.open(link, null, windowproperties);
	                        }
	                    }, {
	                        title: M.util.get_string('wait', 'mod_edwiserform'),
	                        type: 'success'
	                    }]
	                );
				}

				// Open window to preview form
				$('body').on('click', '#preview-form', function(event) {
					event.preventDefault();
					window.open(M.cfg.wwwroot + '/local/edwiserform/preview.php?id=' + $('#id_form').val(), null, windowproperties);
				});

				// Open window to create new form
				$('body').on('click', '#add-new-form', function(event) {
					event.preventDefault();
					if (createeditform != null && createeditform.closed == false) {
						form_open_warning(newpagelink);
						return;
					}
					createeditform = window.open(newpagelink, null, windowproperties);
				});

				// Open window to edit existing form
				$('body').on('click', '#edit-form', function(event) {
					var link = newpagelink + '&formid=' + $('#id_form').val();
					event.preventDefault();
					if (createeditform != null && createeditform.closed == false) {
						form_open_warning(link);
						return;
					}
					createeditform = window.open(link, null, windowproperties);
				});

				// Open window to view all forms
				$('body').on('click', '#all-forms', function(event) {
					window.open(M.cfg.wwwroot + '/local/edwiserform/');
				});
			});
		}
	};
});

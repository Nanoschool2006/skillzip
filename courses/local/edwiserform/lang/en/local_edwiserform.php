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
 * English language
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
$string['pluginname'] = "Edwiser Forms";
$string['efb-heading-newform'] = "Add New Form";
$string['efb-heading-editform'] = "Edit Form";
$string['efb-heading-listforms'] = "View All Forms";
$string['listforms-empty'] = "You don't have any form. Click on Add New Form to create one.";
$string['listformdata-empty'] = "There are no submissions yet.";
$string["efb-heading-listforms-showing"] = 'Showing {$a->start} to {$a->end} of {$a->total} entries';
$string["efb-heading-viewdata"] = "View data";
$string['efb-heading-import'] = 'Import form';
$string['efb-settings'] = "Settings";
$string['efb-header-settings'] = "Form Settings";
$string['efb-settings-general'] = "General";
$string['efb-settings-notification'] = "Notification";
$string['efb-settings-confirmation'] = "Confirmation";
$string['efb-settings-events'] = "Events";
$string['efb-event-fields-missing'] = '{$a->event} required fields missing:
Missing fields: {$a->fields}';
$string['efb-cannot-remove-event'] = 'You have selected \'{$a}\' template. Cannot remove this event.';
$string['efb-settings-event-not-found'] = 'Event settings not found.';
$string['efb-plugin-name'] = "Form Builder";
$string['efb-google-recaptcha-not-loaded'] = 'Unable to load Google Recaptcha. Please try to refresh the page.';
$string['efb-invalid-page'] = "Invalid";
$string['efb-btn-next'] = "Next";
$string['efb-btn-previous'] = "Previous";
$string['efb-btn-save'] = "Save Changes";
$string['efb-lbl-form-setup'] = "Templates";
$string['efb-lbl-form-settings'] = "Settings";
$string['efb-lbl-form-builder'] = "Fields";
$string['efb-lbl-form-preview'] = "Preview";
$string['efb-form-builder-step'] = 'Build Form';
$string['efb-lbl-title'] = "Title";
$string["efb-lbl-title-warning"] = "Title cannot be empty.";
$string["efb-lbl-courses-warning"] = "Please select at least 1 course.";
$string["efb-lbl-empty-form-warning"] = "Please add fields in form.";
$string['efb-lbl-description'] = "Description";
$string['efb-lbl-description-warning'] = "Description cannot be empty.";
$string['efb-lbl-allowedit'] = "Allow Data Edit";
$string['efb-lbl-allowedit-desc'] = "Allow user to edit submitted data.";
$string['efb-lbl-event'] = "Select Event";
$string['efb-lbl-event-choose'] = "Choose events";
$string['efb-lbl-event-search'] = "Search events";
$string['efb-lbl-event-search-not-found'] = "No results";
$string['efb-lbl-courses-list'] = "Select Courses";
$string['efb-enable-notification'] = 'Enable notification';
$string['efb-enable-notification-desc'] = 'Enable email notification.';
$string['efb-lbl-notifi-email'] = "Recipient Email Address";
$string['efb-lbl-notifi-email-warning'] = "Invalid email address.";
$string['efb-lbl-notifi-email-duplicate'] = "Email is already present in list.";
$string['efb-lbl-multiplesubmission'] = 'Note: Selected template supports multiple submission';
$string['error-occured-while-loading'] = 'Please wait while the page is loading. <br> If nothing appears then try to reload the page.';
$string['efb-email-body-restore-desc'] = '<a href="#" class="efb-email-body-restore" data-id="{$a->id}" data-string="{$a->string}">Restore</a> email body to default.';
$string['efb-recipient-email-desc'] = 'Default: Author\'s email address';
$string['efb-cannot-create-form'] = "You are not allowed create form.";
$string['efb-forms-get-title-desc'] = 'Please enter title and description';
$string['efb-forms-update-confirm'] = "Do you want to create new form or overwrite existing?";
$string['efb-forms-update-create-new'] = "New";
$string['efb-forms-update-overwrite-existing'] = "Overwrite";
$string['efb-admin-disabled-teacher'] = "You are not allowed create form. Contact admin to enable form creation.";
$string['efb-contact-admin'] = "Please contact site admin.";
$string['efb-notify-email-subject'] = 'New user submission';
$string['efb-notify-email-subject-setting'] = 'Email Subject';
$string['efb-notify-email-body'] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hi {AUTHOR_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{USER_LINK} has made submission in {FORM_TITLE} form. To see all submission {VIEW_DATA_LINK LABEL="click"} here.</div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div></td></tr>
</tbody>
</table>
</div>';
$string['efb-email-show-tags'] = 'Show tags';
$string['efb-email-hide-tags'] = 'Hide tags';
$string['efb-email-body-tags'] = [
    "{SITE_NAME}" => "Will be replaced with site name",
    "{USER_FULLNAME}" => "Firstname and Lastname of user",
    "{USER_FIRSTNAME}" => "User's firstname",
    "{USER_LASTNAME}" => "User's lastname",
    "{AUTHOR_NAME}" => "Firstname and Lastname of author",
    "{AUTHOR_FIRSTNAME}" => "Author's firstname",
    "{AUTHOR_LASTNAME}" => "Author's lastname",
    "{FORM_TITLE}" => "Title of form",
    "{USER_LINK}" => "Link of user with fullname",
    "{ALL_FIELDS}" => "All fields from form",
    "{VIEW_DATA_LINK LABEL=\"click\"}" => "Link to view submission with custom label"
];
$string['efb-notify-email-body-setting'] = 'Email Body';
$string['efb-confirmation-email-failed'] = "<p>Unable to send confirmation email.</p>";
$string['efb-confirmation-email-success'] = "<p>Confirmation email sent successfully.</p>";
$string['efb-notify-email-failed'] = "<p>Unable to notify author.</p>";
$string['efb-notify-email-success'] = "<p>Notified to author successfully.</p>";
$string['efb-delete-form-and-data'] = '{$a->title} form with ID({$a->id}) will be deleted along with its submissions. Are you sure you want to delete this form?';
$string['deletesubmission'] = 'Delete submissions?';
$string['deletesubmissionmsg'] = 'Are you sure you want to delete selected submissions?';
$string['submissionsdeleted'] = 'Submissions deleted successfully.';
$string['emptysubmission'] = 'Please select submissions to delete';
$string['note'] = 'Note:';
$string['clickonshortcode'] = 'Click on shortcode to copy';
$string['shortcodecopied'] = '{$a} copied to clipboard';
$string['hey-wait'] = 'Hey Wait';
$string['efb-search-form'] = 'Search Forms:';
$string['efb-form-enter-title'] = 'Please give a name to your form';
$string['no-record-found'] = 'No record found';
$string['efb-missing-name-attribute-field'] = 'Please provide name in the: <strong>{$a}</strong>. This is important to run form properly.';
$string['efb-form-style'] = 'Style';
$string['allowsubmissionsfromdate'] = 'Start date';
$string['allowsubmissionstodate'] = 'End date';
$string['allowsubmissionscollapseddate'] = 'Form end date must be after the start date.';
$string['submissionbeforedate'] = 'Form will be available from (<strong>{$a}</strong>).';
$string['submissionafterdate'] = 'Form is expired on (<strong>{$a}</strong>).';
$string['efb-file-choosefile'] = 'Choose file';
$string['options-selected-count'] = '{$a->count} of {$a->max} options selected';
$string['fullpage-link-message'] = '<a class="efb-view-fullpage" href="#">Click here</a> to view form in new tab.';
$string['fullpage-link-clicked'] = 'The form is opened in another tab.';

// Template event string.
$string['efb-event-blank-name'] = "Blank Form";
$string['efb-event-blank-desc'] = "This lets you create any form of your choice using Drag and Drop form builder.";
$string['efb-event-hover-text'] = "Select this form";

// Setup template strings start.
$string["efb-template-inactive-license"] = '<strong>{$a}</strong> is part of Edwiser Forms Pro version. Activate your license now to avail this feature.';
$string['efb-setup-msg-select-tmpl'] = "To speed up the process, you can select from one of our pre-made templates";
$string['efb-lbl-form-setup-formname'] = "Form Name";
$string['efb-lbl-form-setup-formname-placeholder'] = "Enter your form name here…";
$string['efb-lbl-form-setup-formname-sub-heading'] = "Select a Template";
$string['efb-form-editing'] = "Now editing";
$string['efb-select-template-warning'] = 'Please select template before proceeding';
$string["efb-template-not-found"] = "Template not found.";
$string["efb-template-name-not-valid"] = "Template name is not valid";
$string["efb-template-found"] = "Template found";
$string["operation-failed"] = "Operation failed";
$string["active"] = "SELECTED";
$string["enrol-success"] = 'Enrollment of user {$a} in courses is successful';
$string["unenrol-success"] = 'Unenrollment of user {$a} in courses is successful';
$string["enrol-success"] = 'Enrollment of user {$a} in courses is unsuccessful';
$string["unenrol-success"] = 'Unenrollment of user {$a} in courses is unsuccessful';
$string["attention"] = "Attention";
$string["efb-template-change-warning"] = "All fields from current form will be deleted. Are you sure you want to continue?";
$string['login'] = 'Login';
$string['register'] = 'Create New Account';

// Setup template strings end.

$string["efb-tbl-heading-title"] = "Title";
$string["efb-tbl-heading-type"] = "Type";
$string["efb-tbl-heading-shortcode"] = "Shortcode";
$string["efb-tbl-heading-author"] = "Author";
$string["efb-tbl-heading-author2"] = "Updated By";
$string["efb-tbl-heading-created"] = "Created On";
$string["efb-tbl-heading-submitted"] = "Submitted On";
$string["efb-tbl-heading-modified"] = "Last Updated";
$string["efb-tbl-heading-versions"] = "Versions";
$string["efb-tbl-heading-version"] = "Version";
$string["efb-tbl-heading-action"] = "Manage";
$string["efb-form-action-edit-title"] = "Edit form";
$string["efb-form-action-delete-title"] = "Delete";
$string["efb-form-action-view-data-title"] = "View data";
$string["efb-form-action-preview-title"] = "Preview form";
$string["efb-form-action-live-demo-title"] = "Live demo";
$string['livedemologgedinmessage'] = "If you are expecting live demo then open link in incognito mode.";
$string["efb-form-action-enable-title"] = "Enable form";
$string["efb-form-action-disable-title"] = "Disable form";
$string["efb-form-action-enable-failed"] = "Form enable failed";
$string["efb-form-action-disable-failed"] = "Form disable failed";
$string["efb-form-action-enable-success"] = "Form enable success";
$string["efb-form-action-disable-success"] = "Form disable success";
$string["efb-form-setting-save-msg"] = "Form saved successfuly. You will redirected to forms list. Click Ok to redirect manually.";
$string["efb-form-setting-saved"] = "Your form is already saved. You will redirected to forms list.";
$string["efb-form-setting-save-fail-msg"] = "Error while saving form definition.";
$string["efb-form-def-save-fail-msg"] = "Error while saving form definition.";
$string["efb-form-def-update-fail-msg"] = "Failed to update form.";
$string["form-def-overwrite"] = 'User submissions present. Previous submissions may not be listed properly if there is change in form fields. Do you still want to overwrite?';
$string["efb-form-setting-update-fail-msg"] = "Unable to update form.";
$string["efb-form-setting-update-msg"] = "Form has been updated successfuly.";
$string["efb-list-form-data-page-title"] = "List Form Data.";
$string["duplicate-form-fields"] = "Found duplicate form fields. Please delete all submissions and modify form fields.";
$string["efb-msg-form-delete-success"] = "Form deleted successfuly";
$string["efb-msg-form-delete-fail"] = "Form delete failed";
$string["efb-lbl-confirmation-subject"] = "Form Confirmation Email Subject";
$string["efb-lbl-confirmation-default-subject"] = 'Form submitted successfully.';
$string["efb-lbl-confirmation-msg"] = "Form Confirmation Email Message";
$string["efb-confirmation-default-msg"] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hi {USER_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Thank you for submission in {FORM_TITLE}.</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{ALL_FIELDS}</div></td></tr></tbody></table><br>
</div>';
$string["efb-valid-form-data"] = "Form data is valid";
$string["efb-invalid-form-data"] = "Form data is not valid";
$string["efb-login-form-disable-different-form"] = "Cannot disable. Another login form is active";
$string['efb-technical-issue-reload'] = 'Technical problem. Please reload the page.';

// Import export form.
$string["efb-form-action-export-title"] = "Export form";
$string["activate-license"] = "(Activate License)";
$string["exportcsv"] = 'Export CSV{$a}';
$string["exportxlsx"] = 'Export Excelsheet{$a}';
$string['efb-form-import'] = 'Import form';
$string['efb-import-file'] = 'Select .xml file to import';
$string['efb-import-file_help'] = 'Select .xml file to import form. This form will appear in list and can be used in future.';
$string['efb-import-no-file'] = 'Please select file';
$string['efb-import-empty-file'] = 'File do not contain anything';
$string['efb-import-invalid-file-no-title'] = 'Form does not have title';
$string['efb-import-invalid-file-no-description'] = 'Form does not have description';
$string['efb-import-invalid-file-no-definition'] = 'Form does not have definition';
$string['efb-import-invalid-file-no-type'] = 'Form does not have form type';
$string['efb-import-invalid-file-no-courses'] = 'Form does not have courses';
$string['apply'] = 'Apply';
$string['selectbulkaction'] = 'Please select action';
/* End */

// Form viewer strings.
$string["efb-form-not-found"] = 'Form {$a} not found.';
$string["efb-form-not-enabled"] = 'Form {$a} not enable.';
$string["efb-form-data-heading-action"] = "Action";
$string["efb-form-data-heading-user"] = "User Profile";
$string["efb-form-data-submission-successful"] = '<p>Form submitted successfully. <a href="{$a}">Click</a> here to visit homepage.</p>';
$string["efb-form-data-submission-failed"] = "<p>Form data submission failed</p>";
$string["efb-form-data-submission-not-supported"] = "This form type does not support form submission";
$string["efb-form-data-action-no-action"] = "No actions";
$string["efb-form-data-no-data"] = "Found empty form definition. Nothing to display.";
$string["efb-form-submission-found"] = '<p>You already submitted a response. You are not allowed to edit or submit a response. <a href="{$a}">Click</a> here to visit homepage.</p>';
$string["efb-unknown-error"] = "Unknown error";
$string["efb-form-definition-found"] = "Form definition found";
$string["efb-form-loggedin-required"] = 'You need to login before seeing the form. {$a} here to login.';
$string["efb-form-loggedin-required-click"] = "Click";
$string["efb-form-loggedin-not-allowed"] = 'Form cannot be shown while you logged in. <a href="{$a}">Click here for homepage.</a><br>';
$string["delete-userfile-on-save-notice"] = "File will be deleted at form submission.";
$string["cancel-delete-userfile"] = "Cancel Deletion";
$string["user-file-replace"] = "Previously uploaded file will be replace. Do you want to continue?";

/* JS Strings */
$string["action.add.attrs.attr"] = "What attribute would you like to add?";
$string['attribute-help'] = 'What is attribute? <a target="_blank" href="https://www.google.com/search?q=what+is+html+form+field+attributes&oq=what+is+html+form+field+attr">Click here for help.</a>';
$string["action.add.attrs.value"] = "Default Value";
$string["address"] = "Address";
$string["allFieldsRemoved"] = "All fields were removed.";
$string["allowSelect"] = "Allow Select";
$string["attribute"] = "Attribute";
$string["attributes"] = "Attributes";
$string["class"] = $string["attrs.class"] = $string["attrs.className"] = "Css Class";
$string["attrs.required"] = "Required";
$string["attrs.type"] = "Type";
$string["attrs.id"] = "Id";
$string["attrs.title"] = "Title";
$string['attrs.list'] = 'List';
$string["attrs.style"] = "Style";
$string["attrs.dir"] = "Direction";
$string['attrs.href'] = 'Address';
$string["attrs.placeholder"] = "Placeholder";
$string["attrs.name"] = "Name";
$string["attrs.template"] = $string["placeholder.template"] = "Templates";
$string["attrs.value"] = "Value";
$string["attributenotpermitted"] = "Attribute {{attr}}: not permitted";
$string["addcondition"] = "+ Condition";
$string["advanceFields"] = "Advance Fields";
$string["autocomplete"] = "Autocomplete";
$string["button"] = "Button";
$string["cannotBeEmpty"] = "This field cannot be empty";
$string["checkbox"] = "Checkbox";
$string["checkboxes"] = "Checkboxes";
$string["checkboxGroup"] = "Checkbox Group";
$string["clearstep"] = "Clear";
$string["clearallsteps"] = "Clear steps";
$string["clearstoragemessage"] = "Local storage is full. Please clear it to proceed further.";
$string["clearstorageautomatic"] = "Clear Automatically";
$string["clearstoragemanually"] = "Clear Manually";
$string["clearform"] = "Clear form";
$string['condition-choose-source'] = "Choose source";
$string["confirmclearstep"] = "Are you sure you want to remove all fields from current step?";
$string["confirmclearallsteps"] = "Are you sure you want to remove all fields from all steps?";
$string["confirmclearform"] = "Are you sure you want to clear form? All fields will be removed. If any event is selected then it's required fields will be added.";
$string["confirmresetform"] = "Are you sure you want to reset form? All fields will be removed and selected templates fields will be added. If any event is selected then it's required fields will be added.";
$string["close"] = "Close";
$string["column"] = "Column";
$string["city"] = "City/Town";
$string["content"] = "Content";
$string["control"] = "Control";
$string["controlGroups.nextGroup"] = "Next Group";
$string["controlGroups.prevGroup"] = "Previous Group";
$string["copy"] = "Copy To Clipboard";
$string["customcssstyle"] = "Custom Css Style";
$string["cannotremove"] = 'Cannot remove {$a}. Contains template elements:<br>';
$string["columnlayout"] = "Define a column layout";
$string["columnwidths"] = "Define column widths";
$string["control-name"] = "Name - First Name & Last Name";
$string["custom"] = "Custom";
$string["conditions"] = "Conditions";
$string["country"] = "Country";
$string["cancel"] = "Cancel";
$string["danger"] = "Danger";
$string["dark"] = "Dark";
$string["datalist"] = "Datalist";
$string["dragndrop"] = '{$a} drag and drop';
$string["default"] = "Default";
$string["description"] = "Help Text";
$string["descriptionField"] = "Description";
$string["devMode"] = "Developer Mode";
$string["divider"] = "Divider";
$string["display-label"] = "Field label position";
$string["display-label-off"] = "No label";
$string["display-label-top"] = "Top";
$string["display-label-left"] = "Left";
$string['page-background-opacity'] = 'Page background opacity';
$string["editing.row"] = "Editing Row";
$string["editNames"] = "Edit Names";
$string["editorTitle"] = "Form Elements";
$string["editXML"] = "Edit XML";
$string["en-US"] = "English";
$string["email"] = "Email";
$string["field"] = "Field";
$string["form-width"] = "Width(%)";
$string["form-responsive"] = "Form responsive";
$string["form-padding"] = "Padding(px)";
$string["fieldNonEditable"] = "This field cannot be edited.";
$string["fieldRemoveWarning"] = "Are you sure you want to remove this field?";
$string["fileUpload"] = "File Upload";
$string["firstname"] = "Firstname";
$string["formUpdated"] = "Form Updated";
$string["FormTitle"] = "Form Title";
$string["Formnovalidate"] = "Form novalidate";
$string["getStarted"] = "Drag a field from the right to this area";
$string["group"] = "Group";
$string["grouped"] = "Grouped";
$string['recaptcha-error'] = 'Please verify that you are not a robot.';
$string["header"] = "Header";
$string["hidden"] = "Hidden Input";
$string["hide"] = "Edit";
$string["htmlElements"] = "HTML Elements";
$string["import-form-button"] = "Import";
$string["import-form-title"] = "Select json file to import form";
$string["info"] = "Info";
$string["input.date"] = "Date";
$string["info"] = $string["primary"] = "Information";
$string["input.text"] = "Text - Single Line Text";
$string["legendfieldset"] = "Legend for fieldset";
$string["label"] = "Label";
$string["labelCount"] = "{label} {count}";
$string["labelEmpty"] = "Field Label cannot be empty";
$string["lastname"] = "Lastname";
$string["later"] = "Later";
$string["layout"] = "Layout";
$string["limitRole"] = "Limit access to one or more of the following roles:";
$string["light"] = "Light";
$string["link"] = "Link";
$string["mandatory"] = "Mandatory";
$string["maxlength"] = "Max Length";
$string["meta.group"] = "Group";
$string["meta.icon"] = "Ico";
$string["meta.label"] = "Label";
$string["minOptionMessage"] = "This field requires a minimum of 2 options";
$string["mobile"] = "Mobile";
$string["nofields"] = "There are no fields to clear";
$string["name"] = "Name";
$string["number"] = "Number";
$string["no"] = "No";
$string["off"] = "Off";
$string["on"] = "On";
$string["ok"] = "Ok";
$string["option"] = "Option";
$string["optional"] = "optional";
$string["optionEmpty"] = "Option value required";
$string["optionLabel"] = "Option {count}";
$string["options"] = "Options";
$string["panelEditButtons.attrs"] = "+ Attribute";
$string["panelEditButtons.options"] = "+ Option";
$string["panelEditButtons.tabs"] = "+ Tab";
$string["panelLabels.attrs"] = "Attrs";
$string["panelLabels.config"] = "Config";
$string["panelLabels.meta"] = "Meta";
$string["panelLabels.options"] = "Options";
$string["paragraph"] = "Paragraph";
$string["preview"] = "Preview";
$string["primary"] = "Primary";
$string["placeholder"] = "Placeholder";
$string["placeholder.id"] = "Id";
$string["placeholder.className"] = $string["placeholder.class"] = "Space separated classes";
$string["placeholder.email"] = "Enter you email";
$string["placeholder.label"] = "Label";
$string["placeholder.list"] = 'List';
$string["placeholder.password"] = "Enter your password";
$string["placeholder.placeholder"] = "Placeholder";
$string['placeholder.href'] = 'Website address. Ex: http://google.com. (Note: Protocol is necessary. Ex: http:// or https://)';
$string["placeholder.text"] = "Enter some Text";
$string["placeholder.textarea"] = "Enter a lot of text";
$string["placeholder.required"] = "Required";
$string["placeholder.type"] = "Type";
$string["placeholder.name"] = "Name";
$string["placeholder.style"] = "ex:
background-color: white;
border-color: 1px solid black;";
$string["placeholder.required"] = "Required";
$string["placeholder.selected"] = "Selected";
$string["password"] = "Password";
$string["panelLabels.logics"] = "Logics";
$string["panelEditButtons.logics"] = "Logics";
$string["panelLabels.logics"] = "Logics";
$string["panelEditButtons.logics"] = "Logics";
$string["proceed"] = "Proceed";
$string["row.settings.inputGroup.aria"] = "Aria";
$string["radio"] = "Radio";
$string["radioGroup"] = "Radio Group - Radio Button";
$string['recaptcha-row'] = 'Conditions are not supported. This row contains recaptcha element. Remove old conditions if added previously.';
$string["remove"] = "Remove";
$string["removeMessage"] = "Remove Element";
$string["required"] = "Required";
$string["reset"] = "Reset";
$string["reCaptcha"] = "ReCaptcha";
$string["richText"] = "Rich Text Editor";
$string["roles"] = "Access";
$string["row"] = "Row";
$string["row.makeInputGroup"] = "Make this row an input group.";
$string["row.makeInputGroupDesc"] = "Input Groups enable users to add sets of inputs at a time.";
$string["row.settings.fieldsetWrap"] = "Wrap row in a &lt;fieldset&gt; tag";
$string["row.settings.fieldsetWrap.aria"] = "Wrap Row in Fieldset";
$string["save"] = "Save";
$string["secondary"] = "Secondary";
$string["select"] = "Select - Dropdown";
$string["selectColor"] = "Select Color";
$string["selectionsMessage"] = "Allow Multiple Selections";
$string["selectOptions"] = "Options";
$string["separator"] = "Separator";
$string["settings"] = "Settings";
$string["size"] = "Size";
$string["sizes"] = "Sizes";
$string["sizes.lg"] = "Large";
$string["sizes.m"] = "Default";
$string["sizes.sm"] = "Small";
$string["sizes.xs"] = "Extra Small";
$string["standardFields"] = "Standard Fields";
$string["styles"] = "Styles";
$string["styles.btn"] = "Button Style";
$string["styles.btn.danger"] = "Danger";
$string["styles.btn.default"] = "Default";
$string["styles.btn.info"] = "Info";
$string["styles.btn.primary"] = "Primary";
$string["styles.btn.success"] = "Success";
$string["styles.btn.warning"] = "Warning";
$string["subtype"] = "Type";
$string["success"] = "Success";
$string["submit"] = "Submit";
$string["Tags"] = "Tags";
$string["tab"] = "Tabs";
$string["text"] = "Text Field";
$string["textarea"] = "Text Area - Paragraph Text";
$string["this"] = "This";
$string["toggle"] = "Toggle";
$string["ungrouped"] = "Un-Grouped";
$string["UntitledForm"] = "Untitled Form";
$string["upgrade"] = "Upgrade to PRO";
$string["username"] = "Username";
$string["value"] = $string["placeholder.value"] = "Value";
$string["warning"] = "Warning";
$string["warning"] = "Warning";
$string["website"] = "Website";
$string["yes"] = "Yes";

// Tooltips for Form editor.
$string['row-move'] = 'Order row';
$string['row-edit'] = 'Edit row, column, conditional logic properties';
$string['row-clone'] = 'Duplicate row with its columns and fields';
$string['row-remove'] = 'Remove row along with columns and fields';

$string['column-remove'] = 'Remove column along with fields';
$string['column-clone'] = 'Duplicate column with all fields inside it';
$string['column-move'] = 'Move/Order column in any row';

$string['field-move'] = 'Move field in any row/column';
$string['field-edit'] = 'Edit field properties/options';
$string['field-clone'] = 'Duplicate field and its properties';
$string['field-remove'] = 'Remove field';

$string['delete-form'] = 'Delete everything';
$string['reset-form'] = 'Reset form to default';
$string['edit-form'] = 'Edit form settings';

$string['remove-attrs'] = 'Remove attribute';
$string['remove-options'] = 'Remove option';
$string['remove-configs'] = 'Remove config';
$string['remove-condition'] = 'Remove condition';
$string['order-option'] = 'Order option';

// Editing panel heading.
$string['select-options-label'] = 'Option Label';
$string['select-options-value'] = 'Option Value';
$string['input-radio-options-name'] = 'Radio Name';
$string['input-radio-options-label'] = 'Radio Label';
$string['input-radio-options-value'] = 'Radio Value';
$string['input-checkbox-options-name'] = 'Checkbox Name';
$string['input-checkbox-options-label'] = 'Checkbox Label';
$string['input-checkbox-options-value'] = 'Checkbox Value';
$string['input-radio-options-selected'] = $string['input-checkbox-options-selected'] = $string['select-options-selected'] = '';
$string['datalist-options-value'] = 'List option value';

// Validator Strings.
$string['input-invalid-type'] = 'Invalid input type in <strong>{$a}</strong>';
$string['select-option-invalid'] = 'Invalid option in <strong>{$a}</strong>.';
$string['input-radio-option-invalid'] = 'Invalid radio option in <strong>{$a}</strong>';
$string['input-checkbox-option-invalid'] = 'Invalid checkbox option in <strong>{$a}</strong>';
$string['input-all-option-invalid'] = $string['select-option-invalid'];

/* Tab configuration strings for designer */
$string["untitled"] = "Untitled";
$string["stepindex"] = "Step {{index}}";
$string["containersettings"] = "Container Settings";
$string["category-container-default"] = "Default steps";
$string["category-container-complete"] = "Completed steps";
$string["category-container-active"] = "Active step";
$string["category-container-danger"] = "Warning step";
$string['category-container-page'] = "Page settings";
$string["category-container-form"] = "Form settings";
$string["category-container-submit"] = "Submit button settings";
$string["backgroundcolor"] = 'Background color';
$string['bordercolor'] = 'Border color';
$string['textcolor'] = 'Text color';

/* Form designer form setting */
$string['submitbuttontext'] = 'Label';
$string['submitbuttonprocessingtext'] = 'Processing label';
$string['submitbuttonposition'] = 'Position';
$string['position-left'] = 'Left';
$string['position-center'] = 'Center';
$string['position-right'] = 'Right';
$string['backgroundcolor'] = 'Background color';
$string['textcolor'] = 'Label color';

/* Cron job string */
$string['cleanup_task'] = 'Edwiser Form Cleanup Task';

/* Settings strings */
$string['configtitle'] = 'Edwiser Forms';
/* General Settings */
$string['efb-general-settings'] = "General Settings";
$string['efb-enable-user-level-from-creation'] = "Allow teacher to create new form";
$string['efb-des-enable-user-level-from-creation'] = "This will allow teacher to add the new form and view form submitted data.";
$string['efb-google-recaptcha-sitekey'] = 'Google reCaptcha site key';
$string['efb-desc-google-recaptcha-sitekey'] = 'Enter google reCaptcha sitekey to use reCaptcha in your form. Note: Only reCAPTCHA v2.';
$string['efb-enable-site-navigation'] = 'Enable navigation using sidebar';
$string['efb-desc-enable-site-navigation'] = 'Enable this to add navigation links in sidebar.';

// Usage tracking.
$string['enableusagetracking'] = "Enable Usage Trakcing";
$string['enableusagetrackingdesc'] = "<strong>USAGE TRACKING NOTICE</strong>

<hr class='text-muted' />

<p>Edwiser from now on will collect anonymous data to generate product usage statistics.</p>

<p>This information will help us guide the development in right direction and the Edwiser community prosper.</p>

<p>Having said that we don't gather your personal data or of your students during this process. You can disable this from the plugin whenever you wish to opt out of this service.</p>

<p>An overview of the data collected is available <strong><a href='https://forums.edwiser.org/topic/67/anonymously-tracking-the-usage-of-edwiser-products' target='_blank'>here</a></strong>.</p>";

/* License Settings */
$string['edwiserformlicenseactivation'] = 'Edwiser Forms License Activation';
$string['licensestatus'] = 'License Status';
$string['licensenotactive'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license in Edwiser Form settings.';
$string['licensenotactiveadmin'] = '<strong>Alert!</strong> License is not activated , please <strong>activate</strong> the license <a href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingremui#remuilicensestatus" >here</a>.';
$string['activatelicense'] = 'Activate License';
$string['deactivatelicense'] = 'Deactivate License';
$string['renewlicense'] = 'Renew License';
$string['active'] = 'Active';
$string['notactive'] = 'Not Active';
$string['expired'] = 'Expired';
$string['licensekey'] = 'License key';
$string['noresponsereceived'] = 'No response received from the server. Please try again later.';
$string['licensekeydeactivated'] = 'License Key is deactivated.';
$string['siteinactive'] = 'Site inactive (Press Activate license to activate plugin).';
$string['entervalidlicensekey'] = 'Please enter valid license key.';
$string['licensekeyisdisabled'] = 'Your license key is Disabled.';
$string['licensekeyhasexpired'] = "Your license key has Expired. Please, Renew it.";
$string['licensekeyactivated'] = "Your license key is activated.";
$string['enterlicensekey'] = "Please enter correct license key.";
/*****************************/

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
 * Add new form renderable class definition.
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 * @author    Sudam Chakor
 */

namespace local_edwiserform\output;

defined('MOODLE_INTERNAL') || die();

use local_edwiserform\form_basic_settings;
use local_edwiserform\new_form_sections;
use local_edwiserform\controller;
use context_system;
use templatable;
use renderable;
use moodle_url;
use stdClass;

/**
 * Class contains methods of add new form page content
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_new_form implements renderable, templatable {

    /**
     * Edwiser Forms $controller class instance
     * @var controller
     */
    private $controller;

    /**
     *
     * @var Integer Form id, this will be the form id to edit or it can be the null in case of the new form creation.
     */
    private $formid         = null;

    /**
     * Form sections
     * @var null
     */
    private $formsections = null;

    /**
     * Plugins installed in events directory
     * @var array
     */
    private $plugin = [];

    /**
     * Constructor for add new form renderable
     * @param  integer $formid The id of form when re-editing form otherwise null
     * @return void
     * @since  Edwiser Form 1.0.0
     */
    public function __construct($formid = null) {
        $this->controller    = controller::instance();
        $this->formid        = $formid;
        $this->plugins       = $this->controller->get_plugins();
        $this->form_sections = new new_form_sections();
        $this->teacher       = !is_siteadmin() & $this->controller->is_teacher();
        $this->form          = null;
        if ($formid != null) {
            global $DB;
            $this->form = $DB->get_record("efb_forms", array("id" => $this->formid));
        }
        $this->set_default_section_data();
    }

    /**
     * Method provide the functionality to get the forms previous settings.
     * @return array Previous form settings
     * @since  Edwiser Form 1.1.0
     */
    private function get_form_settings() {
        $data = array();
        if ($this->form) {
            $msgdraftid = file_get_submitted_draft_itemid(EDWISERFORM_SUCCESS_FILEAREA);
            $context = context_system::instance();
            $message = file_prepare_draft_area(
                $msgdraftid,
                $context->id,
                EDWISERFORM_COMPONENT,
                EDWISERFORM_SUCCESS_FILEAREA,
                $this->form->id,
                null,
                $this->form->message
            );
            $emailbodydraftid = file_get_submitted_draft_itemid(EDWISERFORM_EMAIL_FILEAREA);
            $context = context_system::instance();
            $notifiemailbody = file_prepare_draft_area(
                $emailbodydraftid,
                $context->id,
                EDWISERFORM_COMPONENT,
                EDWISERFORM_EMAIL_FILEAREA,
                $this->form->id,
                null,
                $this->form->notifi_email_body
            );
            $data = array(
                "id"             => $this->form->id,
                "title"          => $this->form->title,
                "editdata"       => $this->form->data_edit,
                "description"    => $this->form->description,
                "type"           => $this->form->type,
                "events"         => !empty($this->form->events) ? explode(",", $this->form->events) : [],
                "enable_notification" => $this->form->enable_notifi_email,
                "notifi_email"   => $this->form->notifi_email,
                "notifi_email_subject" => $this->form->notifi_email_subject,
                "confirmation_subject" => $this->form->confirmation_subject,
                "confirmation_msg" => array(
                    "itemid" => $msgdraftid,
                    "format" => FORMAT_HTML,
                    "text"   => $message
                ),
                "notifi_email_body" => array(
                    "itemid" => $emailbodydraftid,
                    "format" => FORMAT_HTML,
                    "text"   => $notifiemailbody
                )
            );
            if ($this->form->allowsubmissionsfromdate > 0) {
                $data['allowsubmissionsfromdate'] = $this->form->allowsubmissionsfromdate;
            }
            if ($this->form->allowsubmissionsfromdate > 0) {
                $data['allowsubmissionstodate'] = $this->form->allowsubmissionstodate;
            }
        }
        return $data;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template.
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     * @since  Edwiser Form 1.0.0
     */
    public function export_for_template(\renderer_base $output) {
        global $PAGE, $CFG;
        $this->form_sections->set_form_action(get_string("efb-form-editing", "local_edwiserform"));
        if ($this->form) {
            $PAGE->requires->data_for_js('formdefinition', $this->form->definition);
            $this->form_sections->set_formid($this->formid);
        }
        $PAGE->requires->data_for_js('mod_edwiserform_return', optional_param('mod_edwiserform', false, PARAM_BOOL));
        $logo = $output->get_logo_url();
        if (!$logo) {
            $logo = $output->image_url("edwiser-logoalternate", "local_edwiserform");
        }
        $this->form_sections->set_logo($logo);
        $this->form_sections->set_builder_icons(file_get_contents($CFG->dirroot . '/local/edwiserform/pix/formeo-sprite.svg'));
        $countries = get_string_manager()->get_list_of_countries();
        $this->form_sections->set_countries(json_encode($countries));
        return $this->form_sections->get_form_section_data();
    }

    /**
     * Get container to show form styles
     *
     * @return string style dom container
     * @since  Edwiser Form 1.1.0
     */
    private function get_form_style_container() {
        global $PAGE, $CFG;
        $selected = (isset($this->form) && $this->form->style != null) ? $this->form->style : 1;
        $PAGE->requires->data_for_js('supportedStyles', SUPPORTED_FORM_STYLES);
        $PAGE->requires->data_for_js('selectedStyle', $selected);
        $styles = [];
        for ($i = 1; $i <= SUPPORTED_FORM_STYLES; $i++) {
            $styles[] = [
                'id' => $i,
                'label' => get_string('efb-form-style', 'local_edwiserform')." ".$i,
                'active' => $i == $selected,
                'url' => $CFG->wwwroot . '/local/edwiserform/pix/form_style_' . $i . '.png'
            ];
        }
        return $styles;
    }

    /**
     * Set defaut section data and current form data this sets sidebar navigation buttons,
     * header buttons and panel containers
     *
     * @since  Edwiser Form 1.1.0
     */
    private function set_default_section_data() {
        global $PAGE;
        $settingsparam = array(
            'plugins' => $this->plugins,
            'teacher' => $this->teacher
        );
        $formsettings = new form_basic_settings(null, $settingsparam);
        $formdata     = $this->get_form_settings();
        $formsettings->set_data($formdata);
        $headerbutton = array(
            array(
                "id"    => "efb-heading-listforms",
                "label" => get_string("efb-heading-listforms", "local_edwiserform"),
                "url"   => new moodle_url('/local/edwiserform/view.php?page=listforms'),
                "icon"  => "list"
            ),
            array(
                "id"    => "efb-btn-save-form-settings",
                "label" => get_string("efb-btn-save", "local_edwiserform"),
                "url"   => "#",
                "class" => "d-none",
                "icon"  => "check"
            )
        );
        $navitem      = array(
            array(
                "id"      => "efb-form-setup",
                "active"  => $this->form ? "" : "active",
                "panelid" => "#efb-cont-form-setup",
                "label"   => get_string("efb-lbl-form-setup", "local_edwiserform"),
                "icon"    => "fa-cog"
            ),
            array(
                "id"      => "efb-form-settings",
                "panelid" => "#efb-cont-form-settings",
                "label"   => get_string("efb-lbl-form-settings", "local_edwiserform"),
                "icon"    => "fa-wrench"
            ),
            array(
                "id"      => "efb-form-builder",
                "active"  => $this->form ? "active" : "",
                "panelid" => "#efb-cont-form-builder",
                "label"   => get_string("efb-lbl-form-builder", "local_edwiserform"),
                "icon"    => "fa-list-alt"
            ),
            array(
                "id"      => "efb-form-preview",
                "panelid" => "#efb-cont-form-preview",
                "label"   => get_string("efb-lbl-form-preview", "local_edwiserform"),
                "icon"    => "fa-eye",
                "class"   => "d-none",
            )
        );
        $preview = new stdClass;
        $preview->styles = $this->get_form_style_container();
        $panels        = array(
            array(
                "id"      => "efb-cont-form-settings",
                "heading" => get_string("efb-lbl-form-settings", "local_edwiserform"),
                "body"    => "<div class='efb-form-settings'>".$formsettings->render()."</div>"
            ),
            array(
                "id"      => "efb-cont-form-builder",
                "active"  => $this->form ? "active" : "",
                "heading" => get_string("efb-lbl-form-builder", "local_edwiserform"),
                "body"    => "<form class='build-form'></form>",
                "button"  => "<button
                    class='efb-form-step efb-form-step-preview d-none btn btn-primary'
                    data-id='efb-form-preview'>
                    <i class='fa fa-eye'></i>
                    </button>"
            ),
            array(
                "id"      => "efb-cont-form-preview",
                "heading" => get_string("efb-lbl-form-preview", "local_edwiserform"),
                "body"    => $PAGE->get_renderer('local_edwiserform')->render_from_template(
                    'local_edwiserform/new_form_preview_container',
                    $preview
                ),
                "button"  => "<button
                    class='efb-form-step efb-form-step-builder btn btn-primary'
                    data-id='efb-form-builder'>
                    <i class='fa fa-eye-slash'></i>
                    </button>"
            ),
        );
        $this->form_sections->set_builder_active($this->form ? "" : "active");
        $this->form_sections->set_nav_item($navitem);
        $this->form_sections->set_panels($panels);
        $this->form_sections->set_header_button($headerbutton);
        $this->form_sections->set_panel_setup($this->setup_data());
    }


    /**
     * Setup templates
     *
     * @return object $setup
     * @since  Edwiser Form 1.2.0
     */
    private function setup_data() {

        $templates = array(
            array(
                "tmpl_id"        => "blank",
                "tmpl_name"      => get_string("efb-event-blank-name", "local_edwiserform"),
                "tmpl_hover_txt" => get_string("efb-event-hover-text", "local_edwiserform"),
                "desc"           => get_string("efb-event-blank-desc", "local_edwiserform"),
                "pro"            => true
            )
        );
        foreach ($this->plugins as $pluginname => $plugin) {
            if ($this->teacher == true && !$plugin->teacher_allowed()) {
                continue;
            }
            $templates[] = array(
                "tmpl_id"        => $pluginname,
                "tmpl_name"      => get_string("efb-event-$pluginname-name", "edwiserformevents_$pluginname"),
                "tmpl_hover_txt" => get_string("efb-event-hover-text", "local_edwiserform"),
                "desc"           => get_string("efb-event-$pluginname-desc", "edwiserformevents_$pluginname"),
                "pro"   => $plugin->is_pro_plugin()
            );
        }
        if ($this->form) {
            $setup["form_name"]["value"] = $this->form->title;
            foreach ($templates as $key => $value) {
                $templates[$key]["active"] = $value["tmpl_id"] == $this->form->type;
            }
        }

        $heading = $this->form ? 'builder' : 'setup';
        $title = $this->form ? $this->form->title : '';
        $setup = array(
            "id"              => "efb-cont-form-setup",
            "heading"         => get_string("efb-lbl-form-$heading", "local_edwiserform"),
            "title"           => $title,
            "active"          => "active",
            "msg_select_tmpl" => get_string("efb-setup-msg-select-tmpl", "local_edwiserform"),
            "form_name"       => array(
                "label"       => get_string("efb-lbl-form-setup-formname", "local_edwiserform"),
                "placeholder" => get_string("efb-lbl-form-setup-formname-placeholder", "local_edwiserform")
            ),
            "list"            => $templates
        );
        return (object) $setup;
    }
}

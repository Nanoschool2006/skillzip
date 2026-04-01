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
 * Login hook
 * @package   edwiserformevents_login
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Login hook for login event
 * @return bool true
 */
function xmldb_edwiserformevents_login_install() {
    global $DB;
    $record = $DB->get_record('efb_form_templates', array('name' => 'login'));
    $new  = false;
    if (!$record) {
        $new = true;
        $record = new stdClass;
        $record->name = 'login';
    }

    // @codingStandardsIgnoreLine
    $record->definition = '{"id":"a007ed4a-767f-4e4e-91c2-9590d21c4355","settings":{"formSettings":{"form":{"class":{"title":"Css Class","id":"class","type":"text","value":"efb-form"},"background-color":{"title":"Background color","id":"background-color","type":"color","value":"#ffffff"},"width":{"title":"Width(%)","id":"width","type":"range","value":"100","attrs":{"min":"20","max":"100"}},"padding":{"title":"Padding(px)","id":"padding","type":"range","value":"40","attrs":{"min":"0","max":"100"}},"color":{"title":"Label color","id":"color","type":"color","value":"#000000"},"display-label":{"title":"Field label position","id":"display-label","type":"select","value":"top","options":{"option1":{"value":"off","label":"No label","selected":false},"option2":{"value":"top","label":"Top","selected":true},"option3":{"value":"left","label":"Left","selected":false}}},"style":{"title":"Custom Css Style","id":"style","type":"textarea","value":""}},"submit":{"class":{"title":"Css Class","id":"class","type":"text","value":"btn btn-primary"},"text":{"title":"Label","id":"text","type":"text","value":"Submit"},"processing-text":{"title":"Processing label","id":"processing-text","type":"text","value":"Submitting...."},"position":{"title":"Position","id":"position","type":"select","value":"center","options":{"option1":{"value":"left","label":"Left","selected":false},"option2":{"value":"center","label":"Center","selected":true},"option3":{"value":"right","label":"Right","selected":false}}},"style":{"title":"Custom Css Style","id":"style","type":"textarea","value":""}}}},"stages":{"1e5118d8-d441-464c-aa19-4db06ff8d109":{"title":"Step 1","id":"1e5118d8-d441-464c-aa19-4db06ff8d109","settings":[],"rows":["31ba3e2c-86d2-4418-85b8-4c44460d3250"]}},"rows":{"31ba3e2c-86d2-4418-85b8-4c44460d3250":{"columns":["02ac20fe-c3f6-4d48-b356-62287bacfd41"],"id":"31ba3e2c-86d2-4418-85b8-4c44460d3250","config":{"fieldset":false,"legend":"","inputGroup":false},"attrs":{"className":"f-row"},"conditions":[]}},"columns":{"02ac20fe-c3f6-4d48-b356-62287bacfd41":{"fields":["58dae40e-03a1-4335-ab55-04531dd10144","4e4600de-5197-480d-a588-eb433c0cbcaf","d3eeecc5-3523-4407-af27-a3fc2edb8ee2"],"id":"02ac20fe-c3f6-4d48-b356-62287bacfd41","config":{"width":"100%"},"style":"width: 100%","tag":"div","content":[{"tag":"input","attrs":{"type":"text","required":true,"className":"form-control","style":"","placeholder":"Username"},"config":{"disabledAttrs":["type","template","name"],"label":"Username"},"meta":{"group":"standard","icon":"text-input","id":"text-input"},"fMap":"attrs.value","id":"58dae40e-03a1-4335-ab55-04531dd10144"},{"tag":"input","attrs":{"type":"password","required":true,"className":"form-control","style":"","placeholder":"Password"},"config":{"label":"Password","disabledAttrs":["type","template","name"]},"meta":{"group":"standard","icon":"password","id":"password"},"id":"4e4600de-5197-480d-a588-eb433c0cbcaf"},{"tag":"div","attrs":{"className":"g-recaptcha","placeholder":"ReCaptcha"},"config":{"disabledAttrs":["template","className"],"recaptcha":true,"label":"ReCaptcha","single":true},"meta":{"group":"advance","icon":"recaptcha","id":"recaptcha"},"id":"d3eeecc5-3523-4407-af27-a3fc2edb8ee2"}],"attrs":{"className":["f-render-column"]}}},"fields":{"58dae40e-03a1-4335-ab55-04531dd10144":{"tag":"input","attrs":{"type":"text","required":true,"className":"form-control","name":"username","style":"","placeholder":"Username","template":true},"config":{"disabledAttrs":["type","template"],"label":"Username"},"meta":{"group":"standard","icon":"text-input","id":"text-input"},"fMap":"attrs.value","id":"58dae40e-03a1-4335-ab55-04531dd10144"},"4e4600de-5197-480d-a588-eb433c0cbcaf":{"tag":"input","attrs":{"type":"password","required":true,"className":"form-control","name":"password","style":"","placeholder":"Password","template":true},"config":{"label":"Password","disabledAttrs":["type","template"]},"meta":{"group":"standard","icon":"password","id":"password"},"id":"4e4600de-5197-480d-a588-eb433c0cbcaf"},"d3eeecc5-3523-4407-af27-a3fc2edb8ee2":{"tag":"div","attrs":{"className":"g-recaptcha","placeholder":"ReCaptcha"},"config":{"disabledAttrs":["template","className"],"recaptcha":true,"label":"ReCaptcha","single":true},"meta":{"group":"advance","icon":"recaptcha","id":"recaptcha"},"id":"d3eeecc5-3523-4407-af27-a3fc2edb8ee2"}}}';
    if ($new) {
        $DB->insert_record('efb_form_templates', $record, false);
        return;
    }
    $DB->update_record('efb_form_templates', $record, false);
    return;
}

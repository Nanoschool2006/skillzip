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
 * Install hook
 * @package   edwiserformevents_enrolment
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install hook for enrolment event
 * @return bool true
 */
function xmldb_edwiserformevents_enrolment_install() {
    global $DB;
    $record = $DB->get_record('efb_form_templates', array('name' => 'enrolment'));
    $new  = false;
    if (!$record) {
        $new = true;
        $record = new stdClass;
        $record->name = 'enrolment';
    }

    // @codingStandardsIgnoreLine
    $record->definition = '{"id":"a007ed4a-767f-4e4e-91c2-9590d21c4355","settings":{"formSettings":{"form":{"class":{"title":"Css Class","id":"class","type":"text","value":"efb-form"},"background-color":{"title":"Background color","id":"background-color","type":"color","value":"#ffffff"},"width":{"title":"Width(%)","id":"width","type":"range","value":"100","attrs":{"min":"20","max":"100"}},"padding":{"title":"Padding(px)","id":"padding","type":"range","value":"40","attrs":{"min":"0","max":"100"}},"color":{"title":"Label color","id":"color","type":"color","value":"#000000"},"display-label":{"title":"Field label position","id":"display-label","type":"select","value":"top","options":{"option1":{"value":"off","label":"No label","selected":false},"option2":{"value":"top","label":"Top","selected":true},"option3":{"value":"left","label":"Left","selected":false}}},"style":{"title":"Custom Css Style","id":"style","type":"textarea","value":""}},"submit":{"class":{"title":"Css Class","id":"class","type":"text","value":"btn btn-primary"},"text":{"title":"Label","id":"text","type":"text","value":"Submit"},"processing-text":{"title":"Processing label","id":"processing-text","type":"text","value":"Submitting...."},"position":{"title":"Position","id":"position","type":"select","value":"center","options":{"option1":{"value":"left","label":"Left","selected":false},"option2":{"value":"center","label":"Center","selected":true},"option3":{"value":"right","label":"Right","selected":false}}},"style":{"title":"Custom Css Style","id":"style","type":"textarea","value":""}}}},"stages":{"1e5118d8-d441-464c-aa19-4db06ff8d109":{"title":"Step 1","id":"1e5118d8-d441-464c-aa19-4db06ff8d109","settings":[],"rows":["27284b01-bc63-467b-b041-79f4a575c639","d85a6071-95d2-4fbe-a5ab-6ae7616a339a"]}},"rows":{"27284b01-bc63-467b-b041-79f4a575c639":{"columns":["9e4f7e70-1f52-4bfc-aa10-8a1061981dec","9206199c-e98c-4b9d-b542-e8c31586aacc"],"id":"27284b01-bc63-467b-b041-79f4a575c639","config":{"fieldset":false,"legend":"","inputGroup":false},"attrs":{"className":"f-row"},"conditions":[]},"d85a6071-95d2-4fbe-a5ab-6ae7616a339a":{"columns":["b645fd4d-871d-4fe4-ab05-d20ef300cc5d"],"id":"d85a6071-95d2-4fbe-a5ab-6ae7616a339a","config":{"fieldset":false,"legend":"","inputGroup":false},"attrs":{"className":"f-row"},"conditions":[]}},"columns":{"9e4f7e70-1f52-4bfc-aa10-8a1061981dec":{"fields":["20a34479-febf-44a2-82a9-f1f51a91ddda"],"id":"9e4f7e70-1f52-4bfc-aa10-8a1061981dec","config":{"width":"50%"},"className":[]},"9206199c-e98c-4b9d-b542-e8c31586aacc":{"fields":["248b88a7-de29-43e4-b535-05055f522042"],"id":"9206199c-e98c-4b9d-b542-e8c31586aacc","config":{"width":"50%"},"className":[]},"b645fd4d-871d-4fe4-ab05-d20ef300cc5d":{"fields":["719f9018-0e7a-4ec8-9f00-0a359ae00f8b","2ff5c60d-57d9-4bc9-a62f-0e4ca2b29f70"],"id":"b645fd4d-871d-4fe4-ab05-d20ef300cc5d","config":{"width":"100%"},"className":[]}},"fields":{"20a34479-febf-44a2-82a9-f1f51a91ddda":{"tag":"input","attrs":{"type":"text","required":true,"name":"firstname","className":"form-control","style":"","placeholder":"Firstname","template":true},"config":{"disabledAttrs":["type","template"],"label":"Firstname"},"meta":{"group":"standard","icon":"text-input","id":"text-input"},"fMap":"attrs.value","id":"20a34479-febf-44a2-82a9-f1f51a91ddda"},"248b88a7-de29-43e4-b535-05055f522042":{"tag":"input","attrs":{"type":"text","required":true,"className":"form-control","name":"lastname","style":"","placeholder":"Lastname","template":true},"config":{"disabledAttrs":["type","template"],"label":"Lastname"},"meta":{"group":"standard","icon":"text-input","id":"text-input"},"fMap":"attrs.value","id":"248b88a7-de29-43e4-b535-05055f522042"},"719f9018-0e7a-4ec8-9f00-0a359ae00f8b":{"tag":"input","attrs":{"type":"email","required":true,"className":"form-control","name":"email","style":"","placeholder":"Email","template":true},"config":{"disabledAttrs":["type","template"],"label":"Email"},"meta":{"group":"standard","icon":"email","id":"email"},"fMap":"attrs.value","id":"719f9018-0e7a-4ec8-9f00-0a359ae00f8b"},"2ff5c60d-57d9-4bc9-a62f-0e4ca2b29f70":{"tag":"input","attrs":{"type":"date","required":false,"className":"form-control","name":"date","style":"","placeholder":"Date","template":true},"config":{"disabledAttrs":["type","template"],"label":"Date"},"meta":{"group":"standard","icon":"calendar","id":"date-input"},"id":"2ff5c60d-57d9-4bc9-a62f-0e4ca2b29f70"}}}';
    if ($new) {
        $DB->insert_record('efb_form_templates', $record, false);
        return;
    }
    $DB->update_record('efb_form_templates', $record, false);
    return;
}

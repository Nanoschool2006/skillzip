<?php
/*
 * File displays the edwiser bridge settings.
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('mod_form.php');


global $CFG, $COURSE, $PAGE;

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');


// Restrict normal user to access this page
admin_externalpage_setup('edwiserbridge_conn_synch_settings');


$stringmanager = get_string_manager();
$strings = $stringmanager->load_component_strings('local_edwiserbridge', 'en');
$PAGE->requires->strings_for_js(array_keys($strings), 'local_edwiserbridge');



// Require Login
require_login();
$context = context_system::instance();
$baseurl = $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection';



/*
* Creating array of the objects which will be created.
*/
$mform = array(
    'service' => array
    (
        'url' => $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=service',
        'id'  => 'eb_service_form'
    ),
    'connection' => array
    (
        'url' => $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=connection',
        'id'  => 'eb_connection_form'
    ),
    'synchronization' => array
    (
        'url' => $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=synchronization',
        'id'  => 'eb_synch_form'
    ),
    'settings' => array
    (
        'url' => $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=settings',
        'id'  => 'eb_settings_form'
    ),
    'summary' => array
    (
        'url' => $CFG->wwwroot.'/local/edwiserbridge/edwiserbridge.php?tab=summary',
        'id'  => 'eb_summary_form'
    )

);

$mform_navigation = new edwiserbridge_navigation_form();


/*
 * Necessary page requirements.
 */
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
// $PAGE->set_url('/local/edwiserbridge/edwiserbridge.php?tab=connection');
$PAGE->set_url('/local/edwiserbridge/edwiserbridge.php?tab=settings');

$PAGE->set_title(get_string('eb-setting-page-title', 'local_edwiserbridge'));
$PAGE->requires->css('/local/edwiserbridge/styles/style.css');
$PAGE->requires->js_call_amd("local_edwiserbridge/edwiser_bridge", "init");


echo $OUTPUT->header();
echo $OUTPUT->container_start();


/*
 * Navigation form
 */
$mform_navigation->display();



/*
* Functionality to display tab wise forms
*/
foreach ($mform as $key => $mform_data) {
    // Create object.  
    $object_name = 'edwiserbridge_'. $key .'_form';
    $object = new $object_name($mform_data['url'], null, 'post', '', array("id" => $mform_data['id']), true, null);

    if ($form_data = $object->get_data()) {
        //In this case you process validated data. $mform->get_data() returns data posted in form.

        //Calling the save function for each tabn if present.
        $function_name = 'save_'. $key .'_form_settings';

        if (function_exists($function_name)) {
            $function_name($form_data);
        }
    }

        //Display connection form  for the first time.
    if (isset($_GET["tab"]) && $_GET["tab"] == $key) {
        $object->display();
    }
}

echo $OUTPUT->container_end();
echo $OUTPUT->footer();

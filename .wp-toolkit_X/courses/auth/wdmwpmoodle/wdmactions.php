<?php
/**
 * This file triggers custom actions.
 *
 * @author  WisdmLabs
 * @version 1.2
 */

global $SESSION, $CFG;

require '../../config.php';

if (isset($_GET['q']) && $_GET['q'] === 'flags') {
    $res['v'] = get_config('auth_wdmwpmoodle', 'version');
    $mdl_key = get_config('auth_wdmwpmoodle', 'sharedsecret');
    $res['k'] = strrev($mdl_key);
    echo json_encode($res);
    exit();
}

$SESSION->wantsurl = $CFG->wwwroot;
redirect($SESSION->wantsurl);

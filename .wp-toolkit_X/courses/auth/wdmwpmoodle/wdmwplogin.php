<?php
/**
 * This file triggers WordPress login after moodle login.
 *
 * @author  WisdmLabs
 * @version 1.2
 */

//defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

require '../../config.php';

global $CFG, $USER, $SESSION, $DB;

function wdmRedirectToRoot()
{
    global $CFG, $SESSION;
    $SESSION->wantsurl = $CFG->wwwroot;
    redirect( $SESSION->wantsurl );
}



// Requested to wp login.
if ( isset( $_GET['wdmaction'] ) && $_GET['wdmaction'] === 'login' ) {


    // User is not logged in or is a guest user.
    if ( ! isloggedin() || isguestuser() ) {
        wdmRedirectToRoot();
    }


    if ( ! isset( $_GET['wpsiteurl'] ) || ! filter_var( $_GET['wpsiteurl'], FILTER_VALIDATE_URL ) ) {
        wdmRedirectToRoot();
    }


    if ( ! isset( $_GET['mdl_uid'] ) || empty( $_GET['mdl_uid'] ) ) {
        wdmRedirectToRoot();
    }


    // All checks are passed. Redirect to wp site for login.
    $redirect_to = strtok( $_GET['wpsiteurl'], '?' ) .'?wdmaction=login&mdl_uid=' . $_GET['mdl_uid'] . '&verify_code=' . $_GET['verify_code'];


    redirect( $redirect_to );
}

wdmRedirectToRoot();

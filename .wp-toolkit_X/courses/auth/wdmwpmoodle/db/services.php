<?php
/**
 * Web service local plugin template external functions and service definitions.
 *
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'wdm_sso_verify_token' => array(
                'classname' => 'auth_sso_token_verify_external',
                'methodname' => 'wdm_sso_verify_token',
                'classpath' => 'auth/wdmwpmoodle/externallib.php',
                'description' => 'Return boolean value true if token matches otherwise false.',
                'type' => 'read',
        ),
);

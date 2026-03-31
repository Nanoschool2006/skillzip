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


$string['auth_wdmwpmoodle_secretkey'] = 'Secret Key';
$string['auth_wdmwpmoodle_secretkey_desc'] = 'Must match with the Secret Key of Wordpress plugin Edwiser Bridge SSO.';

$string['auth_wdmwpmoodledescription'] = 'Uses Wordpress user details to log onto Moodle.';
$string['pluginname'] = 'Edwiser Bridge SSO';

$string['auth_wdmwpmoodle_wpsiteurl_lbl'] = 'WordPress Site URL';
$string['auth_wdmwpmoodle_wpsiteurl_desc'] = 'This is necessary to login/logout from WordPress site when a user logs in or logs out from Moodle. Do not forget to add http or https before the URL.';
$string['auth_wdmwpmoodle_wpsiteurl_desc_with_warning'] = 'This is necessary to login/logout from WordPress site when a user logs in or logs out from Moodle. Do not forget to add http or https before the URL.<p><span style = "color:red">Warning: </span><a href ="http://php.net/manual/en/book.mcrypt.php" target="_blank">Mcrypt</a> PHP extension is missing! Enable this to work SSO properly.</p>';
$string['auth_wdmwpmoodle_war_warning'] = 'Warning';
$string['auth_wdmwpmoodle_war_mcrypt'] = 'Mcrypt';
$string['auth_wdmwpmoodle_war_desc'] = 'PHP extension is missing! Enable this to work SSO properly.';

$string['auth_wdmwpmoodle_logoutredirecturl_lbl'] = 'Logout Redirect URL';
$string['auth_wdmwpmoodle_logoutredirecturl_desc'] = 'Users will be redirected to this URL after logout. Keep it blank for default redirection. Do not forget to add http or https before the URL.';
$string['privacy:metadata'] = 'The Edwiser Bridge SSO authentication plugin does not store any personal data.';

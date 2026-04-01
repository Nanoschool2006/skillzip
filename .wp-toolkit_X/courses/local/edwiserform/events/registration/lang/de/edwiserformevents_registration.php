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
 * German language
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

require_once($CFG->dirroot . '/local/edwiserform/classes/controller.php');

$string['pluginname'] = 'Anmeldeformular';
$string['efb-event-registration-name'] = 'Benutzerregistrierungsformular';
$string['efb-event-registration-desc'] = 'Verwenden Sie dieses Formular, wenn Sie das vorhandene Registrierungsformular von Moodle anpassen möchten. Funktioniert am besten, wenn Sie während der Registrierung nur relevante Informationen von Ihren Benutzern erhalten möchten.';
$string['efb-header-registration-settings'] = 'Registrierungseinstellungen';
$string['efb-select-element'] = 'Element auswählen';
$string['efb-field-firstname'] = 'Vorname';
$string['efb-field-lastname'] = 'Nachname';
$string['efb-field-username'] = 'Nutzername';
$string['efb-field-password'] = 'Passwort';
$string['efb-field-password2'] = 'Passwort erneut';
$string['efb-field-gender'] = 'Geschlecht';
$string['efb-field-email'] = 'Email';
$string['efb-field-email2'] = 'E-Mail erneut';
$string['efb-field-phone'] = 'Telefon';
$string['efb-field-country'] = 'Land';
$string['efb-field-address'] = 'Adresse';
$string['efb-field-username-warning'] = 'Benutzername erforderlich';
$string['efb-field-username-duplicate-warning'] = 'Benutzername duplizieren';
$string['efb-field-firstname-warning'] = 'Vorname (erforderlich';
$string['efb-field-lastname-warning'] = 'Nachname erforderlich';
$string['efb-field-email-warning'] = 'Email (erforderlich';
$string['efb-field-email-duplicate-warning'] = 'E-Mail-Duplikat';
$string['efb-field-email2-warning'] = 'Email stimmt nicht überein';
$string['efb-field-password2-warning'] = 'Passwort stimmt nicht überein';
$string['efb-registration-failed'] = 'Registrierung nicht möglich';
$string['efb-registration-success'] = 'Erfolgreich registriert.';
$string['efb-header-registration-enable'] = 'Registrierung aktivieren';
$string['registration-disable-confirmation'] = 'Bestätigungsnachricht deaktivieren';
$string['registration-disable-confirmation_desc'] = "Wenn Sie dies aktivieren, wird die Bestätigungsnachricht des Formulars nicht gesendet. Es wird nur eine Bestätigungs-E-Mail für das Benutzerkonto gesendet.";

// Form data list string.
$string['confirm'] = 'Bestätigen';
$string['suspenduser'] = 'Aussetzen';
$string['unsuspenduser'] = 'Nicht aufhängen';
$string['unsupportedaction'] = 'Aktion wird nicht unterstützt.';
$string['action' . USER_UNCONFIRMED . 'success'] = 'Benutzerkonto erfolgreich bestätigt.';
$string['action' . USER_UNSUSPENDED . 'success'] = 'Benutzerkonto erfolgreich gesperrt.';
$string['action' . USER_SUSPENDED . 'success'] = 'Benutzerkonto erfolgreich gesperrt.';

// Task strings.
$string['fix_auth'] = 'Beheben Sie Authentifizierungsprobleme';

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
 * Polish language
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

require_once($CFG->dirroot . '/local/edwiserform/classes/controller.php');

$string['pluginname'] = 'Formularz rejestracyjny';
$string['efb-event-registration-name'] = 'Formularz rejestracyjny użytkownika';
$string['efb-event-registration-desc'] = 'Użyj tego formularza, gdy chcesz dostosować istniejący formularz rejestracyjny Moodle. Działa najlepiej, gdy chcesz uzyskać istotne informacje od użytkowników podczas rejestracji.';
$string['efb-header-registration-settings'] = 'Ustawienia rejestracji';
$string['efb-select-element'] = 'Wybierz element';
$string['efb-field-firstname'] = 'Imię';
$string['efb-field-lastname'] = 'Nazwisko';
$string['efb-field-username'] = 'Nazwa Użytkownika';
$string['efb-field-password'] = 'Hasło';
$string['efb-field-password2'] = 'Hasło ponownie';
$string['efb-field-gender'] = 'Płeć';
$string['efb-field-email'] = 'E-mail';
$string['efb-field-email2'] = 'Email ponownie';
$string['efb-field-phone'] = 'Telefon';
$string['efb-field-country'] = 'Kraj';
$string['efb-field-address'] = 'Adres';
$string['efb-field-username-warning'] = 'Nazwa użytkownika (wymagana';
$string['efb-field-username-duplicate-warning'] = 'Duplikat nazwy użytkownika';
$string['efb-field-firstname-warning'] = 'Imię (wymagane';
$string['efb-field-lastname-warning'] = 'Wymagane nazwisko';
$string['efb-field-email-warning'] = 'Email (wymagany';
$string['efb-field-email-duplicate-warning'] = 'Duplikat e-maila';
$string['efb-field-email2-warning'] = 'email nie pasuje';
$string['efb-field-password2-warning'] = 'Hasło Nie pasuje';
$string['efb-registration-failed'] = 'Nie można się zarejestrować.';
$string['efb-registration-success'] = 'Zarejestrowany pomyślnie.';
$string['efb-header-registration-enable'] = 'Włącz rejestrację';
$string['registration-disable-confirmation'] = 'Wyłącz komunikat potwierdzający';
$string['registration-disable-confirmation_desc'] = "Włączając tę ​​opcję, wiadomość z potwierdzeniem formularza nie będzie wysyłana. Zostanie wysłany tylko e-mail z potwierdzeniem konta użytkownika.";

// Form data list string.
$string['confirm'] = 'Potwierdzać';
$string['suspenduser'] = 'Zawieszać';
$string['unsuspenduser'] = 'Anuluj zawieszenie';
$string['unsupportedaction'] = 'Działanie nie jest obsługiwane.';
$string['action' . USER_UNCONFIRMED . 'success'] = 'Konto użytkownika zostało pomyślnie potwierdzone.';
$string['action' . USER_UNSUSPENDED . 'success'] = 'Konto użytkownika zostało zawieszone pomyślnie.';
$string['action' . USER_SUSPENDED . 'success'] = 'Konto użytkownika zostało zawieszone pomyślnie.';

// Task strings.
$string['fix_auth'] = 'Napraw problemy z uwierzytelnianiem';

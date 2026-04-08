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
 * Spanish language
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

require_once($CFG->dirroot . '/local/edwiserform/classes/controller.php');

$string['pluginname'] = 'Formulario de inscripción';
$string['efb-event-registration-name'] = 'Formulario de registro de usuario';
$string['efb-event-registration-desc'] = 'Utilice este formulario cuando desee personalizar el formulario de registro existente de Moodle. Funciona mejor cuando solo desea obtener información relevante de sus usuarios durante el registro.';
$string['efb-header-registration-settings'] = 'Configuraciones de registro';
$string['efb-select-element'] = 'Seleccione Elemento';
$string['efb-field-firstname'] = 'Nombre de pila';
$string['efb-field-lastname'] = 'Apellido';
$string['efb-field-username'] = 'Nombre de usuario';
$string['efb-field-password'] = 'Contraseña';
$string['efb-field-password2'] = 'Contraseña de nuevo';
$string['efb-field-gender'] = 'Género';
$string['efb-field-email'] = 'Email';
$string['efb-field-email2'] = 'Envía un email de nuevo';
$string['efb-field-phone'] = 'Teléfono';
$string['efb-field-country'] = 'País';
$string['efb-field-address'] = 'Dirección';
$string['efb-field-username-warning'] = 'Nombre de usuario (requerido';
$string['efb-field-username-duplicate-warning'] = 'Nombre de usuario duplicado';
$string['efb-field-firstname-warning'] = 'Primer nombre (requerido';
$string['efb-field-lastname-warning'] = 'Apellido Obligatorio';
$string['efb-field-email-warning'] = 'Correo electronico (requerido';
$string['efb-field-email-duplicate-warning'] = 'Email duplicado';
$string['efb-field-email2-warning'] = 'el email no coincide';
$string['efb-field-password2-warning'] = 'Las contraseñas no coinciden';
$string['efb-registration-failed'] = 'No se puede registrar.';
$string['efb-registration-success'] = 'Registrado correctamente.';
$string['efb-header-registration-enable'] = 'Habilitar el registro';
$string['registration-disable-confirmation'] = 'Deshabilitar mensaje de confirmación';
$string['registration-disable-confirmation_desc'] = "Al habilitar esto, no se enviará el mensaje de confirmación del formulario. Solo se enviará un correo electrónico de confirmación de la cuenta de usuario.";

// Form data list string.
$string['confirm'] = 'Confirmar';
$string['suspenduser'] = 'Suspender';
$string['unsuspenduser'] = 'Unsopendido';
$string['unsupportedaction'] = 'La acción no es compatible.';
$string['action' . USER_UNCONFIRMED . 'success'] = 'Cuenta de usuario confirmada con éxito.';
$string['action' . USER_UNSUSPENDED . 'success'] = 'Cuenta de usuario suspendida con éxito.';
$string['action' . USER_SUSPENDED . 'success'] = 'Cuenta de usuario sin suspensión con éxito.';

// Task strings.
$string['fix_auth'] = 'Solucionar problemas de autenticación';

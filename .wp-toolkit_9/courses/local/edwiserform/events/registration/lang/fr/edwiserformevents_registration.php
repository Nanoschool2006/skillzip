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
 * French language
 * @package   edwiserformevents_registration
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

require_once($CFG->dirroot . '/local/edwiserform/classes/controller.php');

$string['pluginname'] = 'Formulaire d\'inscription';
$string['efb-event-registration-name'] = 'Formulaire d\'inscription d\'utilisateur';
$string['efb-event-registration-desc'] = 'Utilisez ce formulaire chaque fois que vous souhaitez personnaliser le formulaire d’enregistrement existant de Moodle. Fonctionne mieux lorsque vous souhaitez uniquement obtenir des informations pertinentes de vos utilisateurs lors de l’enregistrement.';
$string['efb-header-registration-settings'] = 'Paramètres d\'inscription';
$string['efb-select-element'] = 'Sélectionner un élément';
$string['efb-field-firstname'] = 'Prénom';
$string['efb-field-lastname'] = 'Nom de famille';
$string['efb-field-username'] = 'Nom d\'utilisateur';
$string['efb-field-password'] = 'Mot de passe';
$string['efb-field-password2'] = 'Mot de passe à nouveau';
$string['efb-field-gender'] = 'Le sexe';
$string['efb-field-email'] = 'Email';
$string['efb-field-email2'] = 'Renvoyez un mail';
$string['efb-field-phone'] = 'Téléphone';
$string['efb-field-country'] = 'Pays';
$string['efb-field-address'] = 'Adresse';
$string['efb-field-username-warning'] = 'Nom d\'utilisateur (requis';
$string['efb-field-username-duplicate-warning'] = 'Nom d\'utilisateur en double';
$string['efb-field-firstname-warning'] = 'Prénom (obligatoire';
$string['efb-field-lastname-warning'] = 'Nom requis';
$string['efb-field-email-warning'] = 'Email (requis';
$string['efb-field-email-duplicate-warning'] = 'Email dupliqué';
$string['efb-field-email2-warning'] = 'Email ne correspond pas';
$string['efb-field-password2-warning'] = 'Le mot de passe ne correspond pas';
$string['efb-registration-failed'] = 'Impossible de s\'inscrire.';
$string['efb-registration-success'] = 'Enregistré avec succès.';
$string['efb-header-registration-enable'] = 'Activer l\'inscription';
$string['registration-disable-confirmation'] = 'Désactiver le message de confirmation';
$string['registration-disable-confirmation_desc'] = "En activant cette option, le message de confirmation du formulaire ne sera pas envoyé. Seul l'e-mail de confirmation du compte utilisateur sera envoyé.";

// Form data list string.
$string['confirm'] = 'Confirmer';
$string['suspenduser'] = 'Suspendre';
$string['unsuspenduser'] = 'Annuler la suspension';
$string['unsupportedaction'] = 'L\'action n\'est pas prise en charge.';
$string['action' . USER_UNCONFIRMED . 'success'] = 'Le compte utilisateur a été confirmé avec succès.';
$string['action' . USER_UNSUSPENDED . 'success'] = 'Le compte utilisateur a bien été suspendu.';
$string['action' . USER_SUSPENDED . 'success'] = 'Compte utilisateur non suspendu avec succès.';

// Task strings.
$string['fix_auth'] = 'Résoudre les problèmes d\'authentification';

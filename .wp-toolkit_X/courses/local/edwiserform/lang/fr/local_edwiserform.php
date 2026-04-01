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
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
$string['pluginname'] = "Edwiser Forms";
$string['efb-heading-newform'] = "Ajouter un nouveau formulaire";
$string['efb-heading-editform'] = "Modifier le formulaire";
$string['efb-heading-listforms'] = "Voir tous les formulaires";
$string["efb-heading-viewdata"] = "Voir les données";
$string['efb-heading-import'] = 'Formulaire d\'importation';
$string['efb-settings'] = "Réglages";
$string['efb-header-settings'] = "Paramètres du formulaire";
$string['efb-settings-general'] = "Général";
$string['efb-settings-notification'] = "Notification";
$string['efb-settings-confirmation'] = "Confirmation";
$string['efb-settings-events'] = "Événements";
$string['efb-event-fields-missing'] = '{$a->event} champs obligatoiresg:
Missing fields: {$a->fields}';
$string['efb-cannot-remove-event'] = 'Vous avez sélectionné le modèle \'{$a}\'. Impossible de supprimer cet événement.';
$string['efb-settings-event-not-found'] = 'Paramètres d\'événement non trouvés.';
$string['efb-plugin-name'] = "Form Builder";
$string['efb-google-recaptcha-not-loaded'] = 'Impossible de charger Google Recaptcha. S\'il vous plaît essayez de rafraîchir la page.';
$string['efb-invalid-page'] = "Invalide";
$string['efb-btn-next'] = "Suivant";
$string['efb-btn-previous'] = "précédent";
$string['efb-btn-save'] = "Sauvegarder les modifications";
$string['efb-lbl-form-setup'] = "Modèles";
$string['efb-lbl-form-settings'] = "Réglages";
$string['efb-lbl-form-builder'] = "Des champs";
$string['efb-lbl-form-preview'] = "Aperçu";
$string['efb-form-builder-step'] = 'Formulaire de construction';
$string['efb-lbl-title'] = "Titre";
$string["efb-lbl-title-warning"] = "Le titre ne peut être vide.";
$string["efb-lbl-courses-warning"] = "Veuillez sélectionner au moins 1 cours.";
$string["efb-lbl-empty-form-warning"] = "S'il vous plaît ajouter des champs dans le formulaire.";
$string['efb-lbl-description'] = "La description";
$string['efb-lbl-description-warning'] = "La description ne peut pas être vide.";
$string['efb-lbl-allowedit'] = "Autoriser la modification des données";
$string['efb-lbl-allowedit-desc'] = "Autoriser l'utilisateur à modifier les données soumises.";
$string['efb-lbl-event'] = "Sélectionnez un événement";
$string['efb-lbl-event-choose'] = "Choisissez des événements";
$string['efb-lbl-event-search'] = "Rechercher des événements";
$string['efb-lbl-event-search-not-found'] = "Aucun résultat";
$string['efb-lbl-courses-list'] = "Sélectionnez des cours";
$string['efb-enable-notification'] = 'Activer la notification';
$string['efb-enable-notification-desc'] = 'Activer la notification par courrier électronique.';
$string['efb-lbl-notifi-email'] = "Adresse email du destinataire";
$string['efb-lbl-notifi-email-warning'] = "Adresse e-mail invalide.";
$string['efb-lbl-notifi-email-duplicate'] = "Le courrier électronique est déjà présent dans la liste.";
$string['efb-email-body-restore-desc'] = '<a href="#" class="efb-email-body-restore" data-id="{$a->id}" data-string="{$a->string}">Restaurer le corps de l\'e-mail </a> par défaut.';
$string['efb-recipient-email-desc'] = 'Par défaut: adresse e-mail de l\'auteur';
$string['efb-cannot-create-form'] = "Vous n'êtes pas autorisé à créer un formulaire.";
$string['efb-forms-get-title-desc'] = 'S\'il vous plaît entrer le titre et la description';
$string['efb-forms-update-confirm'] = "Voulez-vous créer un nouveau formulaire ou écraser le formulaire existant?";
$string['efb-forms-update-create-new'] = "Nouveau";
$string['efb-forms-update-overwrite-existing'] = "Écraser";
$string['efb-admin-disabled-teacher'] = "Vous n'êtes pas autorisé à créer un formulaire. Contactez l'administrateur pour activer la création de formulaire.";
$string['efb-contact-admin'] = "Veuillez contacter l'administrateur du site.";
$string['efb-notify-email-subject'] = 'Nouvelle soumission d\'utilisateur';
$string['efb-notify-email-subject-setting'] = 'Sujet du courriel';
$string['efb-notify-email-body'] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">salut {AUTHOR_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{USER_LINK} a soumis le formulaire sous la forme {FORM_TITLE}. Pour voir toutes les soumissions {VIEW_DATA_LINK LABEL="cliquez"} ici.</div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div></td></tr>
</tbody>
</table>
</div>';
$string['efb-email-show-tags'] = 'Voir les étiquettes';
$string['efb-email-hide-tags'] = 'Masquer les tags';
$string['efb-email-body-tags'] = [
    "{SITE_NAME}" => "Sera remplacé par le nom du site",
    "{USER_FULLNAME}" => "Prénom et Nom d'utilisateur",
    "{USER_FIRSTNAME}" => "Prénom de l'utilisateur",
    "{USER_LASTNAME}" => "Nom d'utilisateur",
    "{AUTHOR_NAME}" => "Prénom et nom de l'auteur",
    "{AUTHOR_FIRSTNAME}" => "Prénom de l'auteur",
    "{AUTHOR_LASTNAME}" => "Nom de l'auteur",
    "{FORM_TITLE}" => "Titre du formulaire",
    "{USER_LINK}" => "Lien d'utilisateur avec nom complet",
    "{ALL_FIELDS}" => "Tous les champs du formulaire",
    "{VIEW_DATA_LINK LABEL=\"cliquez\"}" => "Lien pour afficher la soumission avec une étiquette personnalisée"
];
$string['efb-notify-email-body-setting'] = 'Corps de l\'e-mail';
$string['efb-confirmation-email-failed'] = "<p> Impossible d'envoyer un e-mail de confirmation. </p>";
$string['efb-confirmation-email-success'] = "<p> Email de confirmation envoyé avec succès. </p>";
$string['efb-notify-email-failed'] = "<p> Impossible de notifier l'auteur. </p>";
$string['efb-notify-email-success'] = "<p> Il a été notifié que l'auteur a réussi. </p>";
$string['clickonshortcode'] = 'Note: Cliquez sur le shortcode pour copier';
$string['shortcodecopied'] = '{$a} copié dans le presse-papiers';
$string['hey-wait'] = 'Hé, attends';
$string['efb-search-form'] = 'Formulaires de recherche:';
$string['efb-form-enter-title'] = 'Merci de donner un nom à votre formulaire';
$string['no-record-found'] = 'Aucun Enregistrement Trouvé';
$string['efb-form-style'] = 'Modes';
$string['allowsubmissionsfromdate'] = 'Date de début';
$string['allowsubmissionstodate'] = 'Date de fin';
$string['allowsubmissionscollapseddate'] = 'À ce jour doit être supérieur à la date de début';
$string['efb-file-choosefile'] = 'Choisir le fichier';
$string['options-selected-count'] = '{$a->count} des {$a->max} options sélectionnées';

// Template event string.
$string['efb-event-blank-name'] = "Formulaire BLANC";
$string['efb-event-blank-desc'] = "Le formulaire vierge vous permet de créer tout type de formulaire à l'aide de notre générateur Glisser-déposer.";
$string['efb-event-hover-text'] = "Sélectionnez ce formulaire";

// Setup template strings start.
$string['efb-setup-msg-select-tmpl'] = "Pour accélérer le processus, vous pouvez sélectionner l'un de nos modèles prédéfinis.";
$string['efb-lbl-form-setup-formname'] = "Nom de forme";
$string['efb-lbl-form-setup-formname-placeholder'] = "Entrez votre nom de formulaire ici…";
$string['efb-lbl-form-setup-formname-sub-heading'] = "Sélectionnez un modèle";
$string['efb-form-editing'] = "Maintenant édition";
$string['efb-select-template-warning'] = 'Veuillez sélectionner un modèle avant de continuer';
$string["efb-template-not-found"] = "Modèle non trouvé.";
$string["efb-template-name-not-valid"] = "Le nom du modèle n'est pas valide";
$string["efb-template-found"] = "Modèle trouvé";
$string["operation-failed"] = "L'opération a échoué";
$string["active"] = "CHOISI";
$string["enrol-success"] = 'L\'inscription de l\'utilisateur {$a} aux cours est réussie';
$string["unenrol-success"] = 'La désinscription de l\'utilisateur {$a} dans les cours est réussie';
$string["enrol-success"] = 'L\'inscription de l\'utilisateur {$a} dans les cours a échoué';
$string["unenrol-success"] = 'La désinscription de l\'utilisateur {$a} dans les cours a échoué';
$string["attention"] = "Attention";
$string["efb-template-change-warning"] = "Tous les champs du formulaire actuel seront supprimés. Es-tu sur de vouloir continuer?";

// Setup template strings end.
$string["efb-tbl-heading-title"] = "Titre";
$string["efb-tbl-heading-type"] = "Type";
$string["efb-tbl-heading-shortcode"] = "Petit code";
$string["efb-tbl-heading-author"] = "Auteur";
$string["efb-tbl-heading-author2"] = "Mis à jour par";
$string["efb-tbl-heading-created"] = "Créé sur";
$string["efb-tbl-heading-submitted"] = "Soumis le";
$string["efb-tbl-heading-modified"] = "Dernière mise à jour";
$string["efb-tbl-heading-versions"] = "Les versions";
$string["efb-tbl-heading-version"] = "Version";
$string["efb-tbl-heading-action"] = "Gérer";
$string["efb-form-action-edit-title"] = "Modifier le formulaire";
$string["efb-form-action-delete-title"] = "Effacer";
$string["efb-form-action-view-data-title"] = "Voir les données";
$string["efb-form-action-preview-title"] = "Formulaire de prévisualisation";
$string["efb-form-action-live-demo-title"] = "Démo en direct";
$string['livedemologgedinmessage'] = "Si vous attendez une démo en direct, ouvrez Link en mode incognito.";
$string["efb-form-action-enable-title"] = "Activer le formulaire";
$string["efb-form-action-disable-title"] = "Désactiver le formulaire";
$string["efb-form-action-enable-failed"] = "L'activation du formulaire a échoué";
$string["efb-form-action-disable-failed"] = "La désactivation du formulaire a échoué";
$string["efb-form-action-enable-success"] = "La forme permet le succès";
$string["efb-form-action-disable-success"] = "Form désactiver le succès";
$string["efb-form-setting-save-msg"] = "Formulaire enregistré avec succès. Vous serez redirigé vers la liste des formulaires. Cliquez sur Ok pour rediriger manuellement.";
$string["efb-form-setting-saved"] = "Votre formulaire est déjà enregistré. Vous serez redirigé vers la liste des formulaires.";
$string["efb-form-setting-save-fail-msg"] = "Erreur lors de l'enregistrement de la définition du formulaire.";
$string["efb-form-def-save-fail-msg"] = "Erreur lors de l'enregistrement de la définition du formulaire.";
$string["efb-form-def-update-fail-msg"] = "Impossible d'écraser le formulaire. Soumissions utilisateur présentes. Essayez de créer un nouveau formulaire.";
$string["efb-form-setting-update-fail-msg"] = "Impossible de mettre à jour le formulaire.";
$string["efb-form-setting-update-msg"] = "Le formulaire a été mis à jour avec succès. Cliquez sur OK pour rediriger vers la liste des formulaires.";
$string["efb-list-form-data-page-title"] = "Liste des données de formulaire.";
$string["duplicate-form-fields"] = "Champs de formulaire en double trouvés. Veuillez supprimer toutes les soumissions et modifier les champs du formulaire.";
$string["efb-msg-form-delete-success"] = "Formulaire supprimé avec succès";
$string["efb-msg-form-delete-fail"] = "Échec de la suppression du formulaire";
$string["efb-confirmation-default-msg"] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Salut {USER_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Merci pour la soumission dans {FORM_TITLE}.</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{ALL_FIELDS}</div></td></tr></tbody></table><br>
</div>';
$string["efb-valid-form-data"] = "Les données de formulaire sont valides";
$string["efb-invalid-form-data"] = "Les données de formulaire ne sont pas valides";
$string["efb-login-form-disable-different-form"] = "Impossible de désactiver. Un autre formulaire de connexion est actif";
$string['efb-technical-issue-reload'] = 'Problème technique. Veuillez recharger la page.';

// Import export form.
$string["efb-form-action-export-title"] = "Formulaire d'exportation";
$string["exportcsv"] = "Exporter au format CSV";
$string["exportcsv-license-activate"] = "Export CSV (Activer la licence)";
$string['efb-form-import'] = 'Formulaire d\'importation';
$string['efb-import-file'] = 'Sélectionnez le fichier .xml à importer';
$string['efb-import-file_help'] = 'Sélectionnez le fichier .xml à importer. Ce formulaire apparaîtra dans la liste et pourra être utilisé à l\'avenir.';
$string['efb-import-no-file'] = 'Veuillez sélectionner un fichier';
$string['efb-import-empty-file'] = 'Le fichier ne contient rien';
$string['efb-import-invalid-file-no-title'] = 'Le formulaire n\'a pas de titre';
$string['efb-import-invalid-file-no-description'] = 'Le formulaire n\'a pas de description';
$string['efb-import-invalid-file-no-definition'] = 'Le formulaire n\'a pas de définition';
$string['efb-import-invalid-file-no-type'] = 'Le formulaire n\'a pas de type de formulaire';
$string['efb-import-invalid-file-no-courses'] = 'Le formulaire n\'a pas de cours';
/* End */

// Form viewer strings.
$string["efb-form-not-found"] = 'Formulaire {$a} introuvable.';
$string["efb-form-not-enabled"] = 'Formulaire {$a} non activé.';
$string["efb-form-data-heading-action"] = "action";
$string["efb-form-data-heading-user"] = "Utilisateur";
$string["efb-form-data-submission-failed"] = "<p> Échec de la soumission des données de formulaire </p>";
$string["efb-form-data-submission-not-supported"] = "This form type does not support form submission";
$string["efb-form-data-action-no-action"] = "Aucune action";
$string["efb-form-data-no-data"] = "Définition de formulaire vide trouvée. Rien à afficher.";
$string["efb-unknown-error"] = "Erreur inconnue";
$string["efb-form-definition-found"] = "Définition de formulaire trouvée";
$string["efb-form-loggedin-required"] = 'Vous devez vous connecter avant de voir le formulaire. {$a} ici pour vous connecter.';
$string["efb-form-loggedin-required-click"] = "Cliquez sur";
$string["efb-form-loggedin-not-allowed"] = 'Le formulaire ne peut pas être affiché pendant que vous êtes connecté. <a href="{$a}">Cliquez ici pour la page d\'accueil.</a><br>';
$string["delete-userfile-on-save-notice"] = "Le fichier sera supprimé lors de la soumission du formulaire.";
$string["cancel-delete-userfile"] = "Annuler la suppression";
$string["user-file-replace"] = "Le fichier précédemment téléchargé sera remplacé. Voulez-vous continuer?";

/* JS Strings */
$string["action.add.attrs.attr"] = "Quel attribut voudriez-vous ajouter?";
$string["action.add.attrs.value"] = "Valeur par défaut";
$string["address"] = "Adresse";
$string["allFieldsRemoved"] = "Tous les champs ont été supprimés.";
$string["allowSelect"] = "Autoriser la sélection";
$string["attribute"] = "Attribut";
$string["attributes"] = "Les attributs";
$string["class"] = $string["attrs.class"] = $string["attrs.className"] = "Classe Css";
$string["attrs.required"] = "Champs obligatoires";
$string["attrs.type"] = "Type";
$string["attrs.id"] = "Id";
$string["attrs.title"] = "Titre";
$string["attrs.style"] = "Style";
$string["attrs.dir"] = "Direction";
$string['attrs.href'] = 'Adresse';
$string["attrs.placeholder"] = "Espace réservé";
$string["attrs.name"] = "prénom";
$string["attrs.template"] = $string["placeholder.template"] = "Modèles";
$string["attrs.value"] = "Valeur";
$string["attributenotpermitted"] = "Attribut {{attr}}: non autorisé";
$string["addcondition"] = "+ Condition";
$string["advanceFields"] = "Champs avancés";
$string["autocomplete"] = "Autocomplete";
$string["button"] = "Bouton";
$string["cannotBeEmpty"] = "Ce champ ne peut pas être vide";
$string["checkbox"] = "Case à cocher";
$string["checkboxes"] = "Cases à cocher";
$string["checkboxGroup"] = "Groupe de cases à cocher";
$string["clearstep"] = "Clair";
$string["clearallsteps"] = "Étapes claires";
$string["clearstoragemessage"] = "Le stockage local est plein. Veuillez le dégager pour continuer.";
$string["clearstorageautomatic"] = "Effacer automatiquement";
$string["clearstoragemanually"] = "Effacer manuellement";
$string["clearform"] = "Forme claire";
$string["confirmclearstep"] = "Êtes-vous sûr de vouloir supprimer tous les champs de l'étape en cours?";
$string["confirmclearallsteps"] = "Êtes-vous sûr de vouloir supprimer tous les champs de toutes les étapes?";
$string["confirmclearform"] = "Êtes-vous sûr de vouloir effacer le formulaire?";
$string["confirmresetform"] = "Êtes-vous sûr de vouloir réinitialiser le formulaire? Tous les champs seront supprimés et les champs de modèles sélectionnés seront ajoutés. Si un événement est sélectionné, les champs obligatoires seront ajoutés.";
$string["close"] = "Fermer";
$string["column"] = "Colonne";
$string["city"] = "Ville";
$string["content"] = "Contenu";
$string["control"] = "Contrôle";
$string["controlGroups.nextGroup"] = "Groupe suivant";
$string["controlGroups.prevGroup"] = "Groupe précédent";
$string["copy"] = "Copier dans le presse-papier";
$string["customcssstyle"] = "Style Css Personnalisé";
$string["cannotremove"] = 'Impossible de supprimer {$a}. Contient des éléments de modèle: <br>';
$string["columnlayout"] = "Définir une disposition de colonne";
$string["columnwidths"] = "Définir les largeurs de colonne";
$string["control-name"] = "Nom - Prénom et Nom";
$string["custom"] = "Douane";
$string["conditions"] = "Conditions";
$string["country"] = "Pays";
$string["cancel"] = "Annuler";
$string["danger"] = "Danger";
$string["dark"] = "Foncé";
$string["datalist"] = "Datalist";
$string["dragndrop"] = '{$un} glisser-déposer';
$string["default"] = "Défaut";
$string["description"] = "Texte d'aide";
$string["descriptionField"] = "La description";
$string["devMode"] = "Mode développeur";
$string["divider"] = "Diviseur";
$string["display-label"] = "Position de l'étiquette de champ";
$string["display-label-off"] = "Pas de label";
$string["display-label-top"] = "Haut";
$string["display-label-left"] = "La gauche";
$string["editing.row"] = "Ligne d'édition";
$string["editNames"] = "Modifier les noms";
$string["editorTitle"] = "Éléments de formulaire";
$string["editXML"] = "Modifier le XML";
$string["en-US"] = "Anglais";
$string["email"] = "Email";
$string["field"] = "Champ";
$string["form-width"] = "Largeur(%)";
$string["form-padding"] = "Rembourrage (px)";
$string["fieldNonEditable"] = "Ce champ ne peut pas être édité.";
$string["fieldRemoveWarning"] = "Êtes-vous sûr de vouloir supprimer ce champ?";
$string["fileUpload"] = "Téléchargement de fichiers";
$string["firstname"] = "Prénom";
$string["formUpdated"] = "Formulaire mis à jour";
$string["FormTitle"] = "Titre du formulaire";
$string["Formnovalidate"] = "Forme novalidate";
$string["getStarted"] = "Faites glisser un champ de la droite vers cette zone";
$string["group"] = "Groupe";
$string["grouped"] = "Groupé";
$string['recaptcha-error'] = 'Veuillez vérifier que vous n\'êtes pas un robot.';
$string["header"] = "Entête";
$string["hidden"] = "Entrée cachée";
$string["hide"] = "modifier";
$string["htmlElements"] = "Éléments HTML";
$string["import-form-button"] = "Importation";
$string["import-form-title"] = "Sélectionnez le fichier json à importer du formulaire";
$string["info"] = "Info";
$string["input.date"] = "Rendez-vous amoureux";
$string["info"] = $string["primary"] = "Information";
$string["input.text"] = "Texte - Texte simple";
$string["legendfieldset"] = "Légende pour fieldet";
$string["label"] = "Étiquette";
$string["labelCount"] = "{label} {count}";
$string["labelEmpty"] = "L'étiquette de champ ne peut pas être vide";
$string["lastname"] = "Nom de famille";
$string["later"] = "Plus tard";
$string["layout"] = "Disposition";
$string["limitRole"] = "Limitez l'accès à un ou plusieurs des rôles suivants:";
$string["light"] = "Lumière";
$string["link"] = "Lien";
$string["mandatory"] = "Obligatoire";
$string["maxlength"] = "Longueur maximale";
$string["meta.group"] = "Groupe";
$string["meta.icon"] = "Ico";
$string["meta.label"] = "Étiquette";
$string["minOptionMessage"] = "Ce champ nécessite un minimum de 2 options";
$string["mobile"] = "Mobile";
$string["nofields"] = "Il n'y a pas de champs à effacer";
$string["name"] = "prénom";
$string["number"] = "Nombre";
$string["no"] = "Non";
$string["off"] = "De";
$string["on"] = "Sur";
$string["ok"] = "D'accord";
$string["option"] = "Option";
$string["optional"] = "optionnel";
$string["optionEmpty"] = "Valeur d'option requise";
$string["optionLabel"] = "Option {count}";
$string["options"] = "Les options";
$string["panelEditButtons.attrs"] = "+ Attribut";
$string["panelEditButtons.options"] = "+ Option";
$string["panelEditButtons.tabs"] = "+ Tab";
$string["panelLabels.attrs"] = "Attire";
$string["panelLabels.config"] = "Config";
$string["panelLabels.meta"] = "Méta";
$string["panelLabels.options"] = "Les options";
$string["paragraph"] = "Paragraphe";
$string["preview"] = "Aperçu";
$string["primary"] = "Primaire";
$string["placeholder"] = "Espace réservé";
$string["placeholder.email"] = "Entrez votre email";
$string["placeholder.label"] = "Étiquette";
$string["placeholder.password"] = "Tapez votre mot de passe";
$string["placeholder.placeholder"] = "Espace réservé";
$string['placeholder.href'] = 'Adresse du site Web. Ex: http://google.com. (Remarque: le protocole est nécessaire. Ex: http:// ou https://)';
$string["placeholder.text"] = "Entrez du texte";
$string["placeholder.textarea"] = "Entrez beaucoup de texte";
$string["placeholder.required"] = "Champs obligatoires";
$string["placeholder.type"] = "Type";
$string["placeholder.name"] = "prénom";
$string["placeholder.style"] = "ex:
background-color: white;
border-color: 1px solid black;";
$string["placeholder.required"] = "Champs obligatoires";
$string["placeholder.selected"] = "Choisi";
$string["password"] = "Mot de passe";
$string["panelLabels.logics"] = "Les logiques";
$string["panelEditButtons.logics"] = "Les logiques";
$string["panelLabels.logics"] = "Les logiques";
$string["panelEditButtons.logics"] = "Les logiques";
$string["proceed"] = "Procéder";
$string["row.settings.inputGroup.aria"] = "Aria";
$string["radio"] = "Radio";
$string["radioGroup"] = "Groupe de radio - bouton radio";
$string['recaptcha-row'] = 'Les conditions ne sont pas prises en charge. Cette ligne contient l\'élément recaptcha. Supprimez les anciennes conditions si elles ont été ajoutées précédemment.';
$string["remove"] = "Retirer";
$string["removeMessage"] = "Supprimer l'élément";
$string["required"] = "Champs obligatoires";
$string["reset"] = "Réinitialiser";
$string["reCaptcha"] = "ReCaptcha";
$string["richText"] = "Éditeur de texte enrichi";
$string["roles"] = "Accès";
$string["row"] = "Rangée";
$string["row.makeInputGroup"] = "Faites de cette ligne un groupe d’entrée.";
$string["row.makeInputGroupDesc"] = "Les groupes d'entrées permettent aux utilisateurs d'ajouter des ensembles d'entrées à la fois.";
$string["row.settings.fieldsetWrap"] = "Envelopper la ligne dans un & lt; fieldset & gt; étiquette";
$string["row.settings.fieldsetWrap.aria"] = "Wrap Row in Fieldset";
$string["save"] = "sauvegarder";
$string["secondary"] = "Secondaire";
$string["select"] = "Sélectionner - Liste déroulante";
$string["selectColor"] = "Sélectionnez la couleur";
$string["selectionsMessage"] = "Autoriser les sélections multiples";
$string["selectOptions"] = "Les options";
$string["separator"] = "Séparateur";
$string["settings"] = "Réglages";
$string["size"] = "Taille";
$string["sizes"] = "Tailles";
$string["sizes.lg"] = "Grand";
$string["sizes.m"] = "Défaut";
$string["sizes.sm"] = "Petit";
$string["sizes.xs"] = "Super petit";
$string["standardFields"] = "Champs standard";
$string["styles"] = "modes";
$string["styles.btn"] = "Style de bouton";
$string["styles.btn.danger"] = "Danger";
$string["styles.btn.default"] = "Défaut";
$string["styles.btn.info"] = "Info";
$string["styles.btn.primary"] = "Primaire";
$string["styles.btn.success"] = "Succès";
$string["styles.btn.warning"] = "Attention";
$string["subtype"] = "Type";
$string["success"] = "Succès";
$string["submit"] = "Soumettre";
$string["Tags"] = "Mots clés";
$string["tab"] = "Onglets";
$string["text"] = "Champ de texte";
$string["textarea"] = "Zone de texte - Texte de paragraphe";
$string["this"] = "Ce";
$string["toggle"] = "Basculer";
$string["ungrouped"] = "Non groupé";
$string["UntitledForm"] = "Formulaire sans titre";
$string["upgrade"] = "Passer à Pro";
$string["username"] = "Nom d'utilisateur";
$string["value"] = $string["placeholder.value"] = "Valeur";
$string["warning"] = "Attention";
$string["warning"] = "Attention";
$string["website"] = "Site Internet";
$string["yes"] = "Oui";

/* Tab configuration strings for designer */
$string["untitled"] = "Sans titre";
$string["stepindex"] = "Étape {{index}}";
$string["containersettings"] = "Paramètres du conteneur";
$string["category-container-default"] = "Étapes par défaut";
$string["category-container-complete"] = "Étapes terminées";
$string["category-container-active"] = "Étape active";
$string["category-container-danger"] = "Étape d'avertissement";
$string["category-container-form"] = "Paramètres de formulaire";
$string["category-container-submit"] = "Paramètres du bouton Soumettre";
$string["backgroundcolor"] = 'Couleur de fond';
$string['bordercolor'] = 'Couleur de la bordure';
$string['textcolor'] = 'Couleur du texte';

/* Form designer form setting */
$string['submitbuttontext'] = 'Étiquette';
$string['submitbuttonprocessingtext'] = 'Étiquette de traitement';
$string['submitbuttonposition'] = 'Position';
$string['position-left'] = 'La gauche';
$string['position-center'] = 'Centre';
$string['position-right'] = 'Droite';
$string['backgroundcolor'] = 'Couleur de fond';
$string['textcolor'] = 'Couleur de l\'étiquette';

/* Cron job string */
$strins['efb-cron-start'] = 'Edwiser Form Cron a commencé';
$string['efb-delete-form-data-cron-start'] = 'Suppression des données de formulaire de {$a}.';
$string['efb-delete-form-data-cron-failed'] = 'La suppression des données de formulaire de {$a} a échoué.';
$string['efb-delete-form-data-cron-end'] = 'Données de formulaire supprimées de {$a}.';
$string['efb-delete-form-cron-start'] = 'Suppression du formulaire {$a}.';
$string['efb-delete-form-cron-failed'] = 'La suppression du formulaire {$a} a échoué.';
$string['efb-delete-form-cron-end'] = 'Formulaire supprimé {$a}.';
$string['efb-delete-form-user-files-cron-start'] = 'Suppression du fichier utilisateur du formulaire de {$a}.';
$string['efb-delete-form-user-files-cron-failed'] = 'La suppression des fichiers utilisateur du formulaire de {$a} a échoué.';
$string['efb-delete-form-user-files-cron-end'] = 'Fichiers utilisateur de formulaire supprimés de {$a}.';
$string['efb-cron-end'] = 'Edwiser Form Cron terminé';
$string['cleanup_task'] = 'Edwiser Form Tâche de nettoyage';

/* Settings strings */
$string['configtitle'] = 'Edwiser Forms';
/* General Settings */
$string['efb-general-settings'] = "réglages généraux";
$string['efb-enable-user-level-from-creation'] = "Autoriser l'enseignant à créer un nouveau formulaire";
$string['efb-des-enable-user-level-from-creation'] = "Cela permettra à l'enseignant d'ajouter le nouveau formulaire et d'afficher les données soumises.";
$string['efb-google-recaptcha-sitekey'] = 'Clé de site Google reCaptcha';
$string['efb-desc-google-recaptcha-sitekey'] = 'Entrez google reCaptcha sitekey pour utiliser reCaptcha dans votre formulaire. Remarque: Uniquement reCAPTCHA v2.';
$string['efb-enable-site-navigation'] = 'Activer la navigation à l\'aide de la barre latérale';
$string['efb-desc-enable-site-navigation'] = 'Activez cette option pour ajouter des liens de navigation dans la barre latérale.';

// Usage tracking.
$string['enableusagetracking'] = "Activer le trafic d'utilisation";
$string['enableusagetrackingdesc'] = "<strong>Avis de suivi de l'utilisation</strong>

<hr class='text-muted' />

<p>Dorénavant, Edwiser collectera des données anonymes pour générer des statistiques d'utilisation des produits.</p>

<p>Ces informations nous aideront à orienter le développement dans la bonne direction et à faire prospérer la communauté Edwiser.</p>

<p>Cela dit, nous ne collectons pas vos données personnelles ni celles de vos étudiants au cours de ce processus. Vous pouvez désactiver cela à partir du plugin chaque fois que vous souhaitez vous désinscrire de ce service.</p>

<p>Un aperçu des données collectées est disponible <strong><a href='https://forums.edwiser.org/topic/67/anonymously-tracking-the-usage-of-edwiser-products' target='_blank'>ici</a></strong>.</p>";

/* License Settings */
$string['edwiserformlicenseactivation'] = 'Activation de la licence de formulaires Edwiser';
$string['licensestatus'] = 'Statut de la licence';
$string['licensenotactive'] = '<strong>Alerte!</strong> La licence n\'est pas activée. Veuillez <strong> activer </strong> la licence dans les paramètres du formulaire Edwiser.';
$string['licensenotactiveadmin'] = '<strong>Alerte!</strong> La licence n\'est pas activée. Veuillez <strong> activer </strong> la licence. <a href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingremui#remuilicensestatus" >here</a>.';
$string['activatelicense'] = 'Activer la licence';
$string['deactivatelicense'] = 'Désactiver la licence';
$string['renewlicense'] = 'Renouveler la licence';
$string['active'] = 'actif';
$string['notactive'] = 'Pas actif';
$string['expired'] = 'Expiré';
$string['licensekey'] = 'Clé de licence';
$string['noresponsereceived'] = 'Aucune réponse reçue du serveur. Veuillez réessayer plus tard.';
$string['licensekeydeactivated'] = 'La clé de licence est désactivée.';
$string['siteinactive'] = 'Site inactif (Appuyez sur Activer la licence pour activer le plugin).';
$string['entervalidlicensekey'] = 'Veuillez entrer une clé de licence valide.';
$string['licensekeyisdisabled'] = 'Votre clé de licence est désactivée.';
$string['licensekeyhasexpired'] = "Votre clé de licence a expiré. S'il vous plaît, renouvelez-le.";
$string['licensekeyactivated'] = "Votre clé de licence est activée.";
$string['enterlicensekey'] = "Veuillez entrer la clé de licence correcte.";
/*****************************/

$string['efb-cron-start'] = 'Edwiser Form Cron Started.';
$string['listforms-empty'] = 'Vous n\'avez aucune forme. Cliquez sur Ajouter un nouveau formulaire pour en créer un.';
$string['listformdata-empty'] = "Il n'y a pas encore de soumissions.";
$string['efb-heading-listforms-showing'] = 'Affichage de {$a->start} à {$a->end} de {$a->total} entrées';
$string['efb-delete-form-and-data'] = 'Le formulaire {$a->title} avec ID ({$a->id}) sera supprimé avec ses soumissions. Êtes-vous sûr de vouloir supprimer ce formulaire?';
$string['efb-missing-name-attribute-field'] = 'Indiquez le nom dans: <strong>{$a}</strong>. Ceci est important pour exécuter le formulaire correctement.';
$string['submissionbeforedate'] = 'Le formulaire sera disponible à partir de (<strong>{$a}</strong>).';
$string['submissionafterdate'] = 'Le formulaire a expiré le (<strong>{$a}</strong>).';
$string['fullpage-link-message'] = '<a class="efb-view-fullpage" href="#"> Cliquez ici </a> pour afficher le formulaire dans un nouvel onglet.';
$string['fullpage-link-clicked'] = 'Le formulaire est ouvert dans un autre onglet.';
$string['efb-template-inactive-license'] = '<strong>{$a}</strong> fait partie de la version d\'Edwiser Forms Pro. Activez votre licence maintenant pour bénéficier de cette fonctionnalité.';
$string['efb-lbl-confirmation-subject'] = 'Objet de l\'e-mail de confirmation de formulaire';
$string['efb-lbl-confirmation-default-subject'] = 'Formulaire soumis avec succès.';
$string['efb-lbl-confirmation-msg'] = 'Message de confirmation de formulaire';
$string['efb-form-data-submission-successful'] = '<p> Formulaire soumis avec succès. <a href="{$aBuch"> Cliquez </a> pour visiter la page d\'accueil. </p>';
$string['efb-form-submission-found'] = '<p> Vous avez déjà envoyé une réponse. Vous n\'êtes pas autorisé à modifier ou à soumettre une réponse. <a href="{$aBuch"> Cliquez </a> pour visiter la page d\'accueil. </p>';
$string['condition-choose-source'] = 'Choisissez la source';
$string['page-background-opacity'] = 'Opacité de fond de page';
$string['form-responsive'] = 'Formulaire sensible';
$string['placeholder.class'] = 'Classes séparées par des espaces';
$string['placeholder.className'] = 'Classes séparées par des espaces';
$string['row-move'] = 'Rangée de commande';
$string['row-edit'] = 'Modifier les propriétés de la ligne, de la colonne et de la logique conditionnelle';
$string['row-clone'] = 'Dupliquer la ligne avec ses colonnes et ses champs';
$string['row-remove'] = 'Supprimer la ligne avec les colonnes et les champs';
$string['column-remove'] = 'Supprimer la colonne avec les champs';
$string['column-clone'] = 'Colonne en double avec tous les champs qu\'elle contient';
$string['column-move'] = 'Colonne Déplacer/Ordre dans n\'importe quelle ligne';
$string['field-move'] = 'Déplacer le champ dans n\'importe quelle ligne/colonne';
$string['field-edit'] = 'Modifier les propriétés/options du champ';
$string['field-clone'] = 'Champ en double et ses propriétés';
$string['field-remove'] = 'Supprimer le champ';
$string['delete-form'] = 'Supprimer tout';
$string['reset-form'] = 'Réinitialiser le formulaire par défaut';
$string['edit-form'] = 'Modifier les paramètres du formulaire';
$string['remove-attrs'] = 'Supprimer l\'attribut';
$string['remove-options'] = 'Supprimer l\'option';
$string['remove-configs'] = 'Supprimer la configuration';
$string['remove-condition'] = 'Supprimer la condition';
$string['order-option'] = 'Option de commande';
$string['select-options-label'] = 'Option Label';
$string['select-options-value'] = 'Valeur d\'option';
$string['input-radio-options-name'] = 'Nom de la radio';
$string['input-radio-options-label'] = 'Étiquette radio';
$string['input-radio-options-value'] = 'Valeur radio';
$string['input-checkbox-options-name'] = 'Nom de la case à cocher';
$string['input-checkbox-options-label'] = 'Libellé de la case à cocher';
$string['input-checkbox-options-value'] = 'Valeur de la case à cocher';
$string['input-invalid-type'] = 'Type d\'entrée non valide dans <strong>{$a}</strong>';
$string['select-option-invalid'] = 'Option non valide dans <strong>{$a}</strong>.';
$string['input-radio-option-invalid'] = 'Option de radio non valide dans <strong>{$a}</strong>';
$string['input-checkbox-option-invalid'] = 'Option de case à cocher non valide dans <strong>{$a}</strong>';
$string['input-all-option-invalid'] = 'Option non valide dans <strong>{$a}</strong>.';
$string['category-container-page'] = 'Paramètres de la page';
$string['select-options-selected'] = '';
$string['input-checkbox-options-selected'] = '';
$string['input-radio-options-selected'] = '';

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
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
$string['pluginname'] = "Edwiser Forms";
$string['efb-heading-newform'] = "Neues Formular hinzufügen";
$string['efb-heading-editform'] = "Formular bearbeiten";
$string['efb-heading-listforms'] = "Alle Formulare anzeigen";
$string["efb-heading-viewdata"] = "Daten anzeigen";
$string['efb-heading-import'] = 'Formular importieren';
$string['efb-settings'] = "Einstellungen";
$string['efb-header-settings'] = "Formulareinstellungen";
$string['efb-settings-general'] = "Allgemein";
$string['efb-settings-notification'] = "Benachrichtigungen";
$string['efb-settings-confirmation'] = "Bestätigung";
$string['efb-settings-events'] = "Meldungen";
$string['efb-event-fields-missing'] = '{$a->event} Pflichtfelder fehlen:
Fehlende Angaben: {$a->fields}';
$string['efb-cannot-remove-event'] = 'Sie haben das Template \'{$a}\' ausgewählt. Dieses Ereignis kann nicht entfernt werden.';
$string['efb-settings-event-not-found'] = 'Angaben nicht gefunden.';
$string['efb-plugin-name'] = "Form Builder";
$string['efb-google-recaptcha-not-loaded'] = 'Google Recaptcha kann nicht geladen werden. Bitte versuchen Sie, die Seite zu aktualisieren.';
$string['efb-invalid-page'] = "Ungültig";
$string['efb-btn-next'] = "Nächste";
$string['efb-btn-previous'] = "Vorherige";
$string['efb-btn-save'] = "Einstellungen speichern";
$string['efb-lbl-form-setup'] = "Templates";
$string['efb-lbl-form-settings'] = "Einstellungen";
$string['efb-lbl-form-builder'] = "Felder";
$string['efb-lbl-form-preview'] = "Vorschau";
$string['efb-form-builder-step'] = 'Formular erstellen';
$string['efb-lbl-title'] = "Titel";
$string["efb-lbl-title-warning"] = "Es muss ein Titel vergeben werden.";
$string["efb-lbl-courses-warning"] = "Bitte wählen Sie mindestens einen Kurs aus.";
$string["efb-lbl-empty-form-warning"] = "Bitte fügen Sie dem Formular Felder hinzu.";
$string['efb-lbl-description'] = "Beschreibung";
$string['efb-lbl-description-warning'] = "Es muss eine Beschreibung eingetragen werden.";
$string['efb-lbl-allowedit'] = "Datenbearbeitung zulassen";
$string['efb-lbl-allowedit-desc'] = "Nutzer erlauben, eingereichte Daten zu bearbeiten.";
$string['efb-lbl-event'] = "Veranstaltung auswählen";
$string['efb-lbl-event-choose'] = "Veranstaltung wählen";
$string['efb-lbl-event-search'] = "Veranstaltung suchen";
$string['efb-lbl-event-search-not-found'] = "Kein Resultat";
$string['efb-lbl-courses-list'] = "Kurse auswählen";
$string['efb-enable-notification'] = 'Benachrichtigung aktivieren';
$string['efb-enable-notification-desc'] = 'E-Mail Benachrichtigung aktivieren.';
$string['efb-lbl-notifi-email'] = "E-Mail Adresse des Empfängers";
$string['efb-lbl-notifi-email-warning'] = "Ungültige E-Mail Adresse.";
$string['efb-lbl-notifi-email-duplicate'] = "E-Mail Adresse ist bereits in der Lsite vorhanden.";
$string['efb-email-body-restore-desc'] = '<a href="#" class="efb-email-body-restore" data-id="{$a->id} "data-string =" {$a->string} "> Wiederherstellen </a> E-Mail Textkörper auf Standard.';
$string['efb-recipient-email-desc'] = 'Standard: E-Mail Adresse des Autors';
$string['efb-cannot-create-form'] = "Sie haben nicht die Berechtigung ein Formular zu erstellen.";
$string['efb-forms-get-title-desc'] = 'Bitte geben Sie einen Titel und eine Beschreibung ein.';
$string['efb-forms-update-confirm'] = "Möchten Sie ein neues Formular erstellen oder ein bestehendes überschreiben?";
$string['efb-forms-update-create-new'] = "Neu";
$string['efb-forms-update-overwrite-existing'] = "überschreiben";
$string['efb-admin-disabled-teacher'] = "Sie haben nicht die Berechtigung ein Formular zu erstellen. Kontaktieren Sie Ihren Admin, um Formulare erstellen zu können.";
$string['efb-contact-admin'] = "Bitte kontaktieren Sie den Administrator der Website.";
$string['efb-notify-email-subject'] = 'Neue Einreichung eines Benutzers';
$string['efb-notify-email-subject-setting'] = 'E-mail Betreff';
$string['efb-notify-email-body'] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hallo {AUTHOR_FIRSTNAME}, </div> <div style = "font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"> {USER_LINK} heeft inzending ingediend in {FORM_TITLE} het formulier. Om alle inzending {VIEW_DATA_LINK LABEL="klik"} hier te zien.</div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div></td></tr>
</tbody>
</table>
</div>';
$string['efb-email-show-tags'] = 'Tags anzeigen';
$string['efb-email-hide-tags'] = 'Tags verbergen';
$string['efb-email-body-tags'] = [
    "{SITE_NAME}" => "Wird durch den Seitennamen ersetzt",
    "{USER_FULLNAME}" => "Vor- und Nachname des Benutzers",
    "{USER_FIRSTNAME}" => "Vorname des Benutzers",
    "{USER_LASTNAME}" => "Nachnahme des Benutzers",
    "{AUTHOR_NAME}" => "Vor- und Nachnahme des Autors",
    "{AUTHOR_FIRSTNAME}" => "Vorname des Autors",
    "{AUTHOR_LASTNAME}" => "Nachname des Autors",
    "{FORM_TITLE}" => "Titel des Formulars",
    "{USER_LINK}" => "Link des Benutzers mit vollem Namen",
    "{ALL_FIELDS}" => "Alle Felder des Formulars",
    "{VIEW_DATA_LINK LABEL=\"klik\"}" => "Link zur Ansicht einer Einreichung mit benutzerdefiniertem Label."
];
$string['efb-notify-email-body-setting'] = 'E-Mail body';
$string['efb-confirmation-email-failed'] = "<p> Es konnte keine Bestätigungs-E-Mail versendet werden. </p>";
$string['efb-confirmation-email-success'] = "<p> Bestätigungs-E-Mail wurde erfolgreich versendet. </p>";
$string['efb-notify-email-failed'] = "<p> Der Autor konnte nicht benachritigt werden. </p>";
$string['efb-notify-email-success'] = "<p> Der Autor wurde erfolgreich benachrichtigt. </p>";
$string['clickonshortcode'] = 'Hinweis: Klicken Sie auf den Shortcode, um ihn zu kopieren.';
$string['shortcodecopied'] = '{$a} in die Zwischenablage kopiert';
$string['hey-wait'] = 'Hey warte';
$string['efb-search-form'] = 'Suche nach Formularen:';
$string['efb-form-enter-title'] = 'Bitte geben Sie mir einen Namen des Formulars';
$string['no-record-found'] = 'Kein Ergebnis gefunden';
$string['efb-form-style'] = 'Style';
$string['allowsubmissionsfromdate'] = 'Startdatum';
$string['allowsubmissionstodate'] = 'Enddatum';
$string['allowsubmissionscollapseddate'] = 'Das Bis-Datum muss größer sein als das Von-Datum.';
$string['efb-file-choosefile'] = 'Datei auswählen';
$string['options-selected-count'] = '{$a->count} von {$a->max} Optionen ausgewählt';

// Template event string.
$string['efb-event-blank-name'] = "Leeres Formular";
$string['efb-event-blank-desc'] = "Das leere Formular ermöglicht es Ihnen ein beliebiges Formular mit Hilfe unsere Drag&Drop Builders anzulegen.";
$string['efb-event-hover-text'] = "Diese Formular auswählen";

// Setup template strings starts.
$string['efb-setup-msg-select-tmpl'] = "Um den Prozess zu beschleunigen, können Sie auch aus einer unserer Vorlagen auswählen.";
$string['efb-lbl-form-setup-formname'] = "Formularname";
$string['efb-lbl-form-setup-formname-placeholder'] = "Geben Sie hier den Titel Ihres Formulars ein ...";
$string['efb-lbl-form-setup-formname-sub-heading'] = "Wählen Sie eine Vorlage";
$string['efb-form-editing'] = "Jetzt bearbeiten";
$string['efb-select-template-warning'] = 'Bitte wählen Sie eine Vorlage aus, bevor Sie fortfahren.';
$string["efb-template-not-found"] = "Vorlage nicht gefunden.";
$string["efb-template-name-not-valid"] = "Name der Vorlage ist ungültig";
$string["efb-template-found"] = "Vorlage gefunden";
$string["operation-failed"] = "Vorgang fehlgeschlagen";
$string["active"] = "AUSGEWÄHLT";
$string["enrol-success"] = 'Benutzer {$a} erfolgreich in die Kurse eingeschrieben.';
$string["unenrol-success"] = 'Benutzer {$a} erfolgreich aus den Kursen abgemeldet.';
$string["enrol-success"] = 'Benutzer {$a} konnte nicht in den Kurs eingeschrieben werden.';
$string["unenrol-success"] = 'Benutzer {$a} konnte nicht aus dem Kurs abgemeldet werden.';
$string["attention"] = "Achtung";
$string["efb-template-change-warning"] = "Alle Felder des aktuellen Formulars werden gelöscht. Sind Sie sich sicher, dass Sie fortfahren möchten?";

// Setup template strings end.
$string["efb-tbl-heading-title"] = "Titel";
$string["efb-tbl-heading-type"] = "Art";
$string["efb-tbl-heading-shortcode"] = "Shortcode";
$string["efb-tbl-heading-author"] = "Autor";
$string["efb-tbl-heading-author2"] = "Aktualisiert von";
$string["efb-tbl-heading-created"] = "Erstellt am";
$string["efb-tbl-heading-modified"] = "Zuletzt aktualisiert";
$string["efb-tbl-heading-versions"] = "Versionen";
$string["efb-tbl-heading-version"] = "Version";
$string["efb-tbl-heading-action"] = "Verwalten";
$string["efb-form-action-edit-title"] = "Formular bearbeiten";
$string["efb-form-action-delete-title"] = "Löschen";
$string["efb-form-action-view-data-title"] = "Daten anzeigen";
$string["efb-form-action-preview-title"] = "Formularvorschau";
$string["efb-form-action-live-demo-title"] = "Live-Demo";
$string['livedemologgedinmessage'] = "Wenn Sie eine Live-Demo erwarten, öffnen Sie den Link im Inkognito-Modus.";
$string["efb-form-action-enable-title"] = "Formular aktivieren";
$string["efb-form-action-disable-title"] = "Formular deaktivieren";
$string["efb-form-action-enable-failed"] = "Formularaktivierung fehlgeschlagen";
$string["efb-form-action-disable-failed"] = "Formulardeaktivierung fehlgeschlagen";
$string["efb-form-action-enable-success"] = "Formular erfolgreich aktiviert";
$string["efb-form-action-disable-success"] = "Formular erfolgreich deaktiviert";
$string["efb-form-setting-save-msg"] = "Formular erfolgreich gespeichert. Sie werden zur Formularliste weitergeleitet. Klicken Sie auf OK, um die Umleitung manuell durchzuführen.";
$string["efb-form-setting-saved"] = "Ihr Formular wurde bereits gespeichert. Sie werden zur Formularliste weitergeleitet.";
$string["efb-form-setting-save-fail-msg"] = "Fehler beim Speichern der Formularbeschreibung.";
$string["efb-form-def-save-fail-msg"] = "Fehler beim Speichern der Formularbeschreibung.";
$string["efb-form-def-update-fail-msg"] = "Das Formular kann nicht überschrieben werden. Benutzerbeiträge vorhanden. Bitte erstellen Sie ein neues Formular.";
$string["efb-form-setting-update-fail-msg"] = "Formular kann nicht aktualisiert werden.";
$string["efb-form-setting-update-msg"] = "Das Formular wurde erfolgreich aktualisiert. Klicken Sie auf Ok, um zur Formularliste zu gelangen.";
$string["efb-list-form-data-page-title"] = "Formular-Daten auflisten.";
$string["duplicate-form-fields"] = "Doppelte Formularfelder gefunden. Bitte löschen Sie alle Einsendungen und ändern Sie die Formularfelder.";
$string["efb-msg-form-delete-success"] = "Formular wurde erfolgreich gelöscht";
$string["efb-msg-form-delete-fail"] = "Das Löschen des Formulars ist fehlgeschlagen";
$string["efb-confirmation-default-msg"] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hoi {USER_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Bedankt voor de inzending in {FORM_TITLE}.</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{ALL_FIELDS}</div></td></tr></tbody></table><br>
</div>';
$string["efb-valid-form-data"] = "Formulardaten sind gültig";
$string["efb-invalid-form-data"] = "Formulardaten sind ungültig";
$string["efb-login-form-disable-different-form"] = "Kann nicht deaktiviert werden. Ein weiteres Anmeldeformular ist aktiv.";
$string['efb-technical-issue-reload'] = 'Technisches Problem. Bitte Seite neu laden.';

// Import export form.
$string["efb-form-action-export-title"] = "Formular exportieren";
$string["exportcsv"] = "CSV exportieren";
$string["exportcsv-license-activate"] = "CSV exportieren (Lizenz aktivieren)";
$string['efb-form-import'] = 'Formular importieren';
$string['efb-import-file'] = 'Für den Import eine .xml-Datei auswählen';
$string['efb-import-file_help'] = '.xml-Datei zum Import eines Formulars auswählen. Dieses Formular erscheint in der Liste und kann in der Zukunft verwendet werden.';
$string['efb-import-no-file'] = 'Bitte wählen Sie ein Formular aus';
$string['efb-import-empty-file'] = 'Die Datei ist leer';
$string['efb-import-invalid-file-no-title'] = 'Formular hat keinen Titel';
$string['efb-import-invalid-file-no-description'] = 'Formular hat keine Beschreibung';
$string['efb-import-invalid-file-no-definition'] = 'Formular hat keine Definition';
$string['efb-import-invalid-file-no-type'] = 'Formular hat hat keinen Formulartyp';
$string['efb-import-invalid-file-no-courses'] = 'Formular hat keine Kurse';
/* End */

// Form viewer strings.
$string["efb-form-not-found"] = 'Formular {$a} nicht gefunden.';
$string["efb-form-not-enabled"] = 'Formular {$a} nicht aktiviert.';
$string["efb-form-data-heading-action"] = "Aktion";
$string["efb-form-data-heading-user"] = "Nutzer";
$string["efb-form-data-submission-failed"] = "<p>Formular Datenübertragung fehlgeschlagen</p>";
$string["efb-form-data-submission-not-supported"] = "Dieser Formulartyp unterstützt das Einreichen von Formularen nicht";
$string["efb-form-data-action-no-action"] = "Keine Aktionen";
$string["efb-form-data-no-data"] = "Leere Formularbeschreibung gefunden. Es kann nichts angezeigt werden.";
$string["efb-unknown-error"] = "Unbekannter Fehler";
$string["efb-form-definition-found"] = "Formularbeschreibung gefunden";
$string["efb-form-loggedin-required"] = 'Sie müssen sich zuerst anmelden, um das Formular zu sehen. {$a} hier zum Login.';
$string["efb-form-loggedin-required-click"] = "Klick";
$string["efb-form-loggedin-not-allowed"] = 'Das Formular kann nicht angezeigt werden, während Sie angemeldet sind. <a href="{$a}">Klicken Sie hier, um zur Startseite zu gelangen.</a><br>';
$string["delete-userfile-on-save-notice"] = "Die Datei wird bei der Übermittlung des Formular gelöscht.";
$string["cancel-delete-userfile"] = "Löschen abbrechen";
$string["user-file-replace"] = "Vorher hochgeladene Datei wird überschrieben. Wollen Sie fortfahren?";

/* JS Strings */
$string["action.add.attrs.attr"] = "Welches Attribut wollen Sie hinzufügen?";
$string["action.add.attrs.value"] = "Standardwert";
$string["address"] = "Adresse";
$string["allFieldsRemoved"] = "Alle Felder wurden entfernt.";
$string["allowSelect"] = "Auswahl erlauben";
$string["attribute"] = "Attribut";
$string["attributes"] = "Attribute";
$string["class"] = $string["attrs.class"] = $string["attrs.className"] = "CSS-Klasse";
$string["attrs.required"] = "Erforderlich";
$string["attrs.type"] = "Typ";
$string["attrs.id"] = "ID";
$string["attrs.title"] = "Titel";
$string["attrs.style"] = "Style";
$string["attrs.dir"] = "Richtung";
$string['attrs.href'] = 'Adresse';
$string["attrs.placeholder"] = "Platzhalter";
$string["attrs.name"] = "Name";
$string["attrs.template"] = $string["placeholder.template"] = "Vorlagen";
$string["attrs.value"] = "Werte";
$string["attributenotpermitted"] = "Attribut {{attr}}: nicht erlaubt";
$string["addcondition"] = "+ Bedingung";
$string["advanceFields"] = "Vorauswahlen";
$string["autocomplete"] = "Autocomplete";
$string["button"] = "Button";
$string["cannotBeEmpty"] = "Dieses Feld muss ausgefüllt werden";
$string["checkbox"] = "Auswahlfeld";
$string["checkboxes"] = "Auswahlfelder";
$string["checkboxGroup"] = "Auswahlfeld Gruppen";
$string["clearstep"] = "Löschen";
$string["clearallsteps"] = "Schritte rückgängig machen";
$string["clearform"] = "Formularinhalt löschen";
$string["clearstoragemessage"] = "Der lokale Speicher ist voll. Bitte löschen Sie es, um fortzufahren.";
$string["clearstorageautomatic"] = "Automatisch löschen";
$string["clearstoragemanually"] = "Manuell löschen";
$string["confirmclearstep"] = "Sind Sie sicher, dass Sie alle Felder aus dem aktuellen Schritt entfernen möchten?";
$string["confirmclearallsteps"] = "Sind Sie sicher, dass Sie alle Felder aus allen vorherigen Schritten entfernen möchten?";
$string["confirmclearform"] = "Sind Sie sicher, dass Sie den Formularinhalt löschen möchten?";
$string["confirmresetform"] = "Möchten Sie das Formular wirklich zurücksetzen? Alle Felder werden entfernt und ausgewählte Vorlagenfelder werden hinzugefügt. Wenn ein Ereignis ausgewählt ist, werden die erforderlichen Felder hinzugefügt.";
$string["close"] = "Schließen";
$string["column"] = "Spalte";
$string["city"] = "Ort/Stadt";
$string["content"] = "Inhalt";
$string["control"] = "Kontrolle";
$string["controlGroups.nextGroup"] = "Nächste Gruppe";
$string["controlGroups.prevGroup"] = "Vorherige Gruppe";
$string["copy"] = "Zu Clipboard kopieren";
$string["customcssstyle"] = "Benutzerdefinierter CSS-Stil";
$string["cannotremove"] = 'Kann {$a} nicht entfernen. Enthält Vorlagen-Elemente: <br>';
$string["columnlayout"] = "Spaltenlayout festlegen";
$string["columnwidths"] = "Spaltenbreite festlegen";
$string["control-name"] = "Name - Vor- & Nachname";
$string["custom"] = "Benutzerdefiniert";
$string["conditions"] = "Voraussetzungen";
$string["country"] = "Land";
$string["cancel"] = "Abbrechen";
$string["danger"] = "Achtung";
$string["dark"] = "Dunkel";
$string["datalist"] = "Datenliste";
$string["dragndrop"] = '{$a} drag & drop';
$string["default"] = "Standard";
$string["description"] = "Hilfetext";
$string["descriptionField"] = "Beschreibung";
$string["devMode"] = "Entwicklermodus";
$string["divider"] = "Trenner/Divider";
$string["display-label"] = "Position der Feldbezeichnung";
$string["display-label-off"] = "Kein Label";
$string["display-label-top"] = "Oben";
$string["display-label-left"] = "Links";
$string["editing.row"] = "Bearbeitungszeile";
$string["editNames"] = "Namen bearbeiten";
$string["editorTitle"] = "Formularelemente";
$string["editXML"] = "XML bearbeiten";
$string["en-US"] = "Englisch";
$string["email"] = "E-Mail";
$string["field"] = "Feld";
$string["form-width"] = "Breite(%)";
$string["form-padding"] = "Padding (px)";
$string["fieldNonEditable"] = "Dieses Feld kann nicht bearbeitet werden.";
$string["fieldRemoveWarning"] = "Sind Sie sicher, dass Sie dieses Feld entfernen möchten?";
$string["fileUpload"] = "Datei-Upload";
$string["firstname"] = "Vorname";
$string["formUpdated"] = "Formular aktualisiert";
$string["FormTitle"] = "Formulartitel";
$string["Formnovalidate"] = "Formular wird nicht auf Vollständigkeit überprüft";
$string["getStarted"] = "Ziehen Sie ein Feld von rechts in diesen Bereich";
$string["group"] = "Gruppe";
$string["grouped"] = "Gruppiert";
$string['recaptcha-error'] = 'Bitte bestätigen Sie, dass Sie kein Roboter sind.';
$string["header"] = "Header";
$string["hidden"] = "Verborgener Inhalt";
$string["hide"] = "Bearbeiten";
$string["htmlElements"] = "HTML-Elemente";
$string["import-form-button"] = "Importieren";
$string["import-form-title"] = "Json-Datei zum Importieren des Formulars auswählen";
$string["info"] = "Info";
$string["input.date"] = "Datum";
$string["info"] = $string["primary"] = "Information";
$string["input.text"] = "Text - Einzeiliger Text";
$string["legendfieldset"] = "Legende für Fieldset";
$string["label"] = "Label";
$string["labelCount"] = "{label} {count}";
$string["labelEmpty"] = "Das Feld Label darf nicht leer sein";
$string["lastname"] = "Nachname";
$string["later"] = "Später";
$string["layout"] = "Layout";
$string["limitRole"] = "Beschränken Sie den Zugriff auf eine oder mehrere der folgenden Rollen:";
$string["light"] = "Light";
$string["link"] = "Link";
$string["mandatory"] = "Verpfltichtend";
$string["maxlength"] = "Maximale Länge";
$string["meta.group"] = "Gruppe";
$string["meta.icon"] = "Icon";
$string["meta.label"] = "Label";
$string["minOptionMessage"] = "Dieses Feld erfordert mindestens zwei Optionen";
$string["mobile"] = "Mobil";
$string["nofields"] = "Keine zu löschende Felder";
$string["name"] = "Name";
$string["number"] = "Nummer";
$string["no"] = "Nein";
$string["off"] = "Aus";
$string["on"] = "An";
$string["ok"] = "OK";
$string["option"] = "Option";
$string["optional"] = "optional";
$string["optionEmpty"] = "Optionswert erforderlich";
$string["optionLabel"] = "Option {count}";
$string["options"] = "Optionen";
$string["panelEditButtons.attrs"] = "+ Attribute";
$string["panelEditButtons.options"] = "+ Option";
$string["panelEditButtons.tabs"] = "+ Tab";
$string["panelLabels.attrs"] = "Attribute";
$string["panelLabels.config"] = "Config";
$string["panelLabels.meta"] = "Meta";
$string["panelLabels.options"] = "Optionen";
$string["paragraph"] = "Absatz";
$string["preview"] = "Vorschau";
$string["primary"] = "primär";
$string["placeholder"] = "Platzhalter";
$string["placeholder.email"] = "Geben Sie Ihre E-Mail Adresse ein";
$string["placeholder.label"] = "Label";
$string["placeholder.password"] = "Geben Sie Ihr Passwort ein";
$string["placeholder.placeholder"] = "Platzhalter";
$string['placeholder.href'] = 'Webseitenadressse. Beispiel: http://google.com. (Hinweis: Protokoll ist erforderlich. Beispiel: http:// oder https://)';
$string["placeholder.text"] = "Geben Sie einen Text ein";
$string["placeholder.textarea"] = "Geben Sie hier viel Text ein";
$string["placeholder.required"] = "Verplichtend";
$string["placeholder.type"] = "Typ";
$string["placeholder.name"] = "Name";
$string["placeholder.style"] = "ex:
background-color: white;
border-color: 1px solid black;";
$string["placeholder.required"] = "Erforderlich";
$string["placeholder.selected"] = "Ausgewählt";
$string["password"] = "Passwort";
$string["panelLabels.logics"] = "Logiken";
$string["panelEditButtons.logics"] = "Logiken";
$string["panelLabels.logics"] = "Logiken";
$string["panelEditButtons.logics"] = "Logiken";
$string["proceed"] = "Fortfahren";
$string["row.settings.inputGroup.aria"] = "Aria";
$string["radio"] = "Radio";
$string["radioGroup"] = "Radio Gruppe - Radio Button";
$string['recaptcha-row'] = 'Bedingungen werden nicht unterstützt. Diese Zeile enthält ein Recaptcha-Element. Entfernen Sie alte Bedingungen, falls diese zuvor hinzugefügt wurden.';
$string["remove"] = "Entfernen";
$string["removeMessage"] = "Element entfernen";
$string["required"] = "Erforderlich";
$string["reset"] = "Reset";
$string["reCaptcha"] = "ReCaptcha";
$string["richText"] = "Rich Text Editor";
$string["roles"] = "Zugang";
$string["row"] = "Reihe";
$string["row.makeInputGroup"] = "Diese Zeile zu einer Eingabegruppe machen.";
$string["row.makeInputGroupDesc"] = "Eingabegruppen ermöglichen es Benutzern, mehrere Eingaben auf einmal hinzuzufügen.";
$string["row.settings.fieldsetWrap"] = "Zeilenumbruch in einm & lt; -veldset & gt; Tag";
$string["row.settings.fieldsetWrap.aria"] = "Zeilenumbruch im Fieldset";
$string["save"] = "Speichern";
$string["secondary"] = "Untergeordnet";
$string["select"] = "Auswahl - Dropdown";
$string["selectColor"] = "Farbe auswählen";
$string["selectionsMessage"] = "Mehrere Auswahlmöglichkeiten zulassen";
$string["selectOptions"] = "Optionen";
$string["separator"] = "Trenner";
$string["settings"] = "Einstellungen";
$string["size"] = "Größe";
$string["sizes"] = "Maße";
$string["sizes.lg"] = "Groß";
$string["sizes.m"] = "Standard";
$string["sizes.sm"] = "Klein";
$string["sizes.xs"] = "Extra klein";
$string["standardFields"] = "Standardfelder";
$string["styles"] = "Styles";
$string["styles.btn"] = "Button Style";
$string["styles.btn.danger"] = "Achtung";
$string["styles.btn.default"] = "Standard";
$string["styles.btn.info"] = "Info";
$string["styles.btn.primary"] = "Primär";
$string["styles.btn.success"] = "Erfolg";
$string["styles.btn.warning"] = "Warnung";
$string["subtype"] = "Typ";
$string["success"] = "Erfolg";
$string["submit"] = "Einreichen";
$string["Tags"] = "Tags";
$string["tab"] = "Tabs";
$string["text"] = "Textfeld";
$string["textarea"] = "Textfeld - Absatztext";
$string["this"] = "Das";
$string["toggle"] = "toggle";
$string["ungrouped"] = "Ungruppiert";
$string["UntitledForm"] = "Formular ohne Titel";
$string["upgrade"] = "Upgrade auf Pro-Version";
$string["username"] = "Nutzername";
$string["value"] = $string["placeholder.value"] = "Wert";
$string["warning"] = "Warnung";
$string["warning"] = "Warnung";
$string["website"] = "Website";
$string["yes"] = "Ja";

/* Tab configuration strings for designer */
$string["untitled"] = "Unbenannt";
$string["stepindex"] = "Schritt {{index}}";
$string["containersettings"] = "Container-Einstellungen";
$string["category-container-default"] = "Standardschritte";
$string["category-container-complete"] = "Abgeschlossene Schritte";
$string["category-container-active"] = "Durchgeführte Schritte";
$string["category-container-danger"] = "Warnhinweis";
$string["category-container-form"] = "Formulareinstellungen";
$string["category-container-submit"] = "Einreichen-Button Einstellungen";
$string["backgroundcolor"] = 'Hintergrundfarbe';
$string['bordercolor'] = 'Rahmenfarbe';
$string['textcolor'] = 'Textfarbe';

/* Form designer form setting */
$string['submitbuttontext'] = 'Label';
$string['submitbuttonprocessingtext'] = 'Bearbeitungslabel';
$string['submitbuttonposition'] = 'Position';
$string['position-left'] = 'Links';
$string['position-center'] = 'Mitte';
$string['position-right'] = 'Rechts';
$string['backgroundcolor'] = 'Hintergrundfarbe';
$string['textcolor'] = 'Label-Farbe';

/* Cron job string */
$string['cleanup_task'] = 'Edwiser Form Bereinigungsaufgabe';

/* Settings strings */
$string['configtitle'] = 'Edwiser Forms';
/* General Settings */
$string['efb-enable-user-level-from-creation'] = "Dozent/in erlauben, ein neues Formular zu erstellen";
$string['efb-des-enable-user-level-from-creation'] = "Dies ermöglicht es dem/der Dozent/in, das neue Formular hinzuzufügen und die übermittelten Daten anzuzeigen.";
$string['efb-google-recaptcha-sitekey'] = 'Google reCaptcha site key';
$string['efb-desc-google-recaptcha-sitekey'] = 'Geben Sie den google reCaptcha site key ein, um reCaptcha in Ihrem Formular zu verwenden. Remarque: Uniquement reCAPTCHA v2.';
$string['efb-enable-site-navigation'] = 'Aktivieren Sie die Navigation über die Seitenleiste';
$string['efb-desc-enable-site-navigation'] = 'Aktivieren Sie diese Option, um Navigationslinks in der Seitenleiste hinzuzufügen.';

// Usage tracking.
$string['enableusagetracking'] = "Aktivieren Sie Usage Trakcing";
$string['enableusagetrackingdesc'] = "<strong>Hinweis zur Nutzungsverfolgung</strong>

<hr class='text-muted' />

<p>Edwiser sammelt ab sofort anonyme Daten, um Produktnutzungsstatistiken zu erstellen.</p>

<p>Diese Informationen werden uns helfen, die Entwicklung in die richtige Richtung zu lenken und die Edwiser-Community zu florieren.</p>

<p>Allerdings sammeln wir während dieses Prozesses weder Ihre persönlichen Daten noch Ihre Schüler. Sie können dies über das Plugin deaktivieren, wenn Sie diesen Dienst deaktivieren möchten.</p>

<p>Eine Übersicht über die erhobenen Daten finden Sie <strong><a href='https://forums.edwiser.org/topic/67/anonymously-tracking-the-usage-of-edwiser-products' target='_blank'>hier</a></strong>.</p>";

/* License Settings */
$string['edwiserformlicenseactivation'] = 'Edwiser Forms Lizenzaktivierung';
$string['licensestatus'] = 'Lizenzstatus';
$string['licensenotactive'] = '<strong> Achtung! </strong> Lizenz ist nicht aktiviert, bitte <strong> aktivieren </strong> Sie die Lizenz in den Edwiser-Einstellungen.';
$string['licensenotactiveadmin'] = '<strong> Achtung! </strong> Lizenz ist nicht aktiviert, bitte <strong> aktivieren </strong> Sie die Lizenz <a href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingremui#remuilicensestatus" >hier</a>.';
$string['activatelicense'] = 'Lizenz aktivieren';
$string['deactivatelicense'] = 'Lizenz deaktivieren';
$string['renewlicense'] = 'Lizenz verlängern';
$string['active'] = 'Aktiv';
$string['notactive'] = 'Inaktiv';
$string['expired'] = 'Abgelaufen';
$string['licensekey'] = 'Lizenzschlüssel';
$string['noresponsereceived'] = 'Keine Antwort vom Server. Bitte versuchen Sie es später noch einmal.';
$string['licensekeydeactivated'] = 'Lizenzschlüssel ist deaktiviert.';
$string['siteinactive'] = 'Seite inaktiv (Bitte aktivieren Sie Ihre Lizenz, um das Plugin zu aktivieren).';
$string['entervalidlicensekey'] = 'Bitte geben Sie einen gültigen Lizenzschlüssel ein.';
$string['licensekeyisdisabled'] = 'Ihr Lizenzschlüssel ist ungültig.';
$string['licensekeyhasexpired'] = "Ihr Lizenzschlüssel ist abgelaufen. Bitte erneuern Sie diesen.";
$string['licensekeyactivated'] = "Ihr Lizenzschlüssel ist aktiviert.";
$string['enterlicensekey'] = "Bitte geben Sie einen korrekten Lizenzschlüssel ein.";
/*****************************/

$string['efb-tbl-heading-submitted'] = 'Eingereicht am';
$string['efb-cron-start'] = 'Edwiser Form Cron gestartet.';
$string['efb-general-settings'] = 'Allgemeine Einstellungen';
$string['listforms-empty'] = 'Sie haben keine Form. Klicken Sie auf Neues Formular hinzufügen, um ein neues Formular zu erstellen.';
$string['listformdata-empty'] = "Es liegen noch keine Beiträge vor.";
$string['efb-heading-listforms-showing'] = 'Zeigt {$a->start} bis {$a->end} von {$a->total} Einträgen an';
$string['efb-delete-form-and-data'] = 'Das {$a->title} -Formular mit der ID ({$a->id}) wird zusammen mit den Einsendungen gelöscht. Möchten Sie dieses Formular wirklich löschen?';
$string['efb-missing-name-attribute-field'] = 'Bitte geben Sie im Feld <strong>{$a}</strong> einen Namen an. Dies ist wichtig, um das Formular ordnungsgemäß auszuführen.';
$string['submissionbeforedate'] = 'Das Formular ist ab (<strong>{$a}</strong>) verfügbar.';
$string['submissionafterdate'] = 'Das Formular ist abgelaufen am (<strong>{$a}</strong>).';
$string['fullpage-link-message'] = '<a class="efb-view-fullpage" href="#"> Klicken Sie hier </a>, um das Formular in einem neuen Tab anzuzeigen.';
$string['fullpage-link-clicked'] = 'Das Formular wird auf einer anderen Registerkarte geöffnet.';
$string['efb-template-inactive-license'] = '<strong>{$a}</strong> ist Teil der Edwiser Forms Pro-Version. Aktivieren Sie jetzt Ihre Lizenz, um diese Funktion nutzen zu können.';
$string['efb-lbl-confirmation-subject'] = 'Formular Bestätigung E-Mail Betreff';
$string['efb-lbl-confirmation-default-subject'] = 'Formular erfolgreich eingereicht.';
$string['efb-lbl-confirmation-msg'] = 'Formularbestätigungs-E-Mail';
$string['efb-form-data-submission-successful'] = '<p> Formular erfolgreich gesendet. <a href="{$a}"> Klicken Sie hier </a>, um die Startseite zu besuchen. </p>';
$string['efb-form-submission-found'] = '<p> Sie haben bereits eine Antwort gesendet. Sie dürfen keine Antwort bearbeiten oder senden. <a href="{$a}"> Klicken Sie hier </a>, um die Startseite zu besuchen. </p>';
$string['condition-choose-source'] = 'Quelle auswählen';
$string['page-background-opacity'] = 'Opazität des Seitenhintergrunds';
$string['form-responsive'] = 'Formular reagiert';
$string['placeholder.class'] = 'Durch Leerzeichen getrennte Klassen';
$string['placeholder.className'] = 'Durch Leerzeichen getrennte Klassen';
$string['row-move'] = 'Zeile bestellen';
$string['row-edit'] = 'Bearbeiten Sie die Eigenschaften für Zeilen, Spalten und bedingte Logik';
$string['row-clone'] = 'Doppelte Zeile mit ihren Spalten und Feldern';
$string['row-remove'] = 'Entfernen Sie die Zeile zusammen mit den Spalten und Feldern';
$string['column-remove'] = 'Spalte mit Feldern entfernen';
$string['column-clone'] = 'Doppelte Spalte mit allen Feldern darin';
$string['column-move'] = 'Spalte in beliebiger Zeile verschieben/ordnen';
$string['field-move'] = 'Feld in beliebiger Zeile/Spalte verschieben';
$string['field-edit'] = 'Feldeigenschaften/Optionen bearbeiten';
$string['field-clone'] = 'Feld duplizieren und seine Eigenschaften';
$string['field-remove'] = 'Feld entfernen';
$string['delete-form'] = 'Alles löschen';
$string['reset-form'] = 'Formular auf Standard zurücksetzen';
$string['edit-form'] = 'Formulareinstellungen bearbeiten';
$string['remove-attrs'] = 'Attribut entfernen';
$string['remove-options'] = 'Option entfernen';
$string['remove-configs'] = 'Konfiguration entfernen';
$string['remove-condition'] = 'Zustand entfernen';
$string['order-option'] = 'Bestelloption';
$string['select-options-label'] = 'Optionsetikett';
$string['select-options-value'] = 'Optionswert';
$string['input-radio-options-name'] = 'Radioname';
$string['input-radio-options-label'] = 'Radio Label';
$string['input-radio-options-value'] = 'Radiowert';
$string['input-checkbox-options-name'] = 'Name des Kontrollkästchens';
$string['input-checkbox-options-label'] = 'Beschriftung des Kontrollkästchens';
$string['input-checkbox-options-value'] = 'Kontrollkästchen Wert';
$string['input-invalid-type'] = 'Ungültiger Eingabetyp in <strong>{$a}</strong>';
$string['select-option-invalid'] = 'Ungültige Option in <strong>{$a}</strong>.';
$string['input-radio-option-invalid'] = 'Ungültige Radiooption in <strong>{$a}</strong>';
$string['input-checkbox-option-invalid'] = 'Ungültige Kontrollkästchenoption in <strong>{$a}</strong>';
$string['input-all-option-invalid'] = 'Ungültige Option in <strong>{$a}</strong>.';
$string['category-container-page'] = 'Seiteneinstellungen';
$string['select-options-selected'] = '';
$string['input-checkbox-options-selected'] = '';
$string['input-radio-options-selected'] = '';

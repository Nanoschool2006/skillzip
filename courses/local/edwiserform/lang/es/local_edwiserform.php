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
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */
$string['pluginname'] = "Formas Edwiser";
$string['efb-heading-newform'] = "Añadir nuevo formulario";
$string['efb-heading-editform'] = "Editar formulario";
$string['efb-heading-listforms'] = "Ver todos los formularios";
$string["efb-heading-viewdata"] = "Ver datos";
$string['efb-heading-import'] = 'Formulario de importación';
$string['efb-settings'] = "Ajustes";
$string['efb-header-settings'] = "Configuración de formulario";
$string['efb-settings-general'] = "General";
$string['efb-settings-notification'] = "Notificación";
$string['efb-settings-confirmation'] = "Confirmación";
$string['efb-settings-events'] = "Eventos";
$string['efb-event-fields-missing'] = '{$a->event} campos requeridos missing:
Missing fields: {$a->fields}';
$string['efb-cannot-remove-event'] = 'Has seleccionado la plantilla \'{$a}\'. No se puede eliminar este evento.';
$string['efb-settings-event-not-found'] = 'No se han encontrado los ajustes del evento.';
$string['efb-plugin-name'] = "Form Builder";
$string['efb-google-recaptcha-not-loaded'] = 'No se puede cargar Google Recaptcha. Por favor, intente actualizar la página.';
$string['efb-invalid-page'] = "Inválido";
$string['efb-btn-next'] = "Siguiente";
$string['efb-btn-previous'] = "Anterior";
$string['efb-btn-save'] = "Guardar cambios";
$string['efb-lbl-form-setup'] = "Plantillas";
$string['efb-lbl-form-settings'] = "Ajustes";
$string['efb-lbl-form-builder'] = "Campos";
$string['efb-lbl-form-preview'] = "Avance";
$string['efb-form-builder-step'] = 'Forma de construcción';
$string['efb-lbl-title'] = "Título";
$string["efb-lbl-title-warning"] = "El título no puede estar vacío.";
$string["efb-lbl-courses-warning"] = "Por favor seleccione al menos 1 curso.";
$string["efb-lbl-empty-form-warning"] = "Por favor agregue campos en el formulario.";
$string['efb-lbl-description'] = "Descripción";
$string['efb-lbl-description-warning'] = "La descripción no puede estar vacía.";
$string['efb-lbl-allowedit'] = "Permitir edición de datos";
$string['efb-lbl-allowedit-desc'] = "Permitir al usuario editar los datos enviados.";
$string['efb-lbl-event'] = "Seleccionar evento";
$string['efb-lbl-event-choose'] = "Elegir eventos";
$string['efb-lbl-event-search'] = "Buscar eventos";
$string['efb-lbl-event-search-not-found'] = "No hay resultados";
$string['efb-lbl-courses-list'] = "Seleccionar cursos";
$string['efb-enable-notification'] = 'Habilitar la notificación';
$string['efb-enable-notification-desc'] = 'Habilitar la notificación por correo electrónico.';
$string['efb-lbl-notifi-email'] = "Dirección de Correo Electrónico del Destinatario";
$string['efb-lbl-notifi-email-warning'] = "Dirección de correo electrónico no válida.";
$string['efb-lbl-notifi-email-duplicate'] = "El correo electrónico ya está presente en la lista.";
$string['efb-email-body-restore-desc'] = '<a href="#" class="efb-email-body-restore" data-id="{$a->id}" data-string="{$a->string}">Restaurar</a> cuerpo del correo electrónico a predeterminado.';
$string['efb-recipient-email-desc'] = 'Predeterminado: Dirección de correo electrónico del autor';
$string['efb-cannot-create-form'] = "No se le permite crear formulario.";
$string['efb-forms-get-title-desc'] = 'Por favor ingrese el título y la descripción';
$string['efb-forms-update-confirm'] = "¿Quieres crear una nueva forma o sobrescribir la existente?";
$string['efb-forms-update-create-new'] = "Nuevo";
$string['efb-forms-update-overwrite-existing'] = "Exagerar";
$string['efb-admin-disabled-teacher'] = "No se le permite crear formulario. Póngase en contacto con el administrador para habilitar la creación de formularios.";
$string['efb-contact-admin'] = "Por favor, póngase en contacto con el administrador del sitio.";
$string['efb-notify-email-subject'] = 'Nuevo envío de usuario';
$string['efb-notify-email-subject-setting'] = 'Asunto del email';
$string['efb-notify-email-body'] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hola {AUTHOR_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{USER_LINK} ha realizado el envío en forma {FORM_TITLE}. Para ver todo el envío {VIEW_DATA_LINK LABEL="hacer clic"} aquí.</div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div>
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;"></div></td></tr>
</tbody>
</table>
</div>';
$string['efb-email-show-tags'] = 'Mostrar etiquetas';
$string['efb-email-hide-tags'] = 'Ocultar etiquetas';
$string['efb-email-body-tags'] = [
    "{SITE_NAME}" => "Será reemplazado con el nombre del sitio",
    "{USER_FULLNAME}" => "Nombre y apellido del usuario",
    "{USER_FIRSTNAME}" => "Nombre del usuario",
    "{USER_LASTNAME}" => "Nombre del usuario",
    "{AUTHOR_NAME}" => "Nombre y apellido del autor.",
    "{AUTHOR_FIRSTNAME}" => "Nombre del autor",
    "{AUTHOR_LASTNAME}" => "Apellido del autor",
    "{FORM_TITLE}" => "Título de la forma",
    "{USER_LINK}" => "Enlace del usuario con el nombre completo",
    "{ALL_FIELDS}" => "Todos los campos del formulario.",
    "{VIEW_DATA_LINK LABEL=\"hacer clic\"}" => "Enlace para ver la presentación con etiqueta personalizada"
];
$string['efb-notify-email-body-setting'] = 'Email Body';
$string['efb-confirmation-email-failed'] = "<p> No se puede enviar el correo electrónico de confirmación. </p>";
$string['efb-confirmation-email-success'] = "<p> El correo electrónico de confirmación se envió correctamente. </p>";
$string['efb-notify-email-failed'] = "<p> No se puede notificar al autor </p>";
$string['efb-notify-email-success'] = "<p> Notificado al autor con éxito. </p>";
$string['clickonshortcode'] = 'Nota: Haga clic en el código corto para copiar';
$string['shortcodecopied'] = '{$a} copiado al portapapeles';
$string['hey-wait'] = 'Hey, espera';
$string['efb-search-form'] = 'Formularios de búsqueda:';
$string['efb-form-enter-title'] = 'Por favor dale un nombre a tu formulario';
$string['no-record-found'] = 'Ningún record fue encontrado';
$string['efb-form-style'] = 'Estilos';
$string['allowsubmissionsfromdate'] = 'Fecha de inicio';
$string['allowsubmissionstodate'] = 'Fecha final';
$string['allowsubmissionscollapseddate'] = 'Hasta la fecha debe ser mayor que la fecha Desde';
$string['efb-file-choosefile'] = 'Elija el archivo';
$string['options-selected-count'] = '{$a->count} de {$a->max} opciones seleccionadas';

// Template event string.
$string['efb-event-blank-name'] = "Formulario en blanco";
$string['efb-event-blank-desc'] = "El formulario en blanco le permite crear cualquier tipo de formulario con nuestro generador de arrastrar y soltar.";
$string['efb-event-hover-text'] = "Selecciona este formulario";

// Setup template strings start.
$string['efb-setup-msg-select-tmpl'] = "To speed up the process, you can select from one of our pre-made templates";
$string['efb-lbl-form-setup-formname'] = "Nombre del formulario";
$string['efb-lbl-form-setup-formname-placeholder'] = "Ingrese el nombre de su formulario aquí ...";
$string['efb-lbl-form-setup-formname-sub-heading'] = "Seleccione una plantilla";
$string['efb-form-editing'] = "Ahora editando";
$string['efb-select-template-warning'] = 'Por favor, seleccione la plantilla antes de continuar';
$string["efb-template-not-found"] = "Plantilla no encontrada.";
$string["efb-template-name-not-valid"] = "El nombre de la plantilla no es válido";
$string["efb-template-found"] = "Plantilla encontrada";
$string["operation-failed"] = "Operación fallida";
$string["active"] = "SELECCIONADO";
$string["enrol-success"] = 'La inscripción del usuario {$a} en los cursos es exitosa';
$string["unenrol-success"] = 'La cancelación de la inscripción del usuario {$a} en los cursos es exitosa';
$string["enrol-success"] = 'La inscripción del usuario {$a} en los cursos no tiene éxito';
$string["unenrol-success"] = 'La cancelación de la inscripción del usuario {$a} en los cursos no tiene éxito';
$string["attention"] = "Atención";
$string["efb-template-change-warning"] = "Todos los campos del formulario actual serán eliminados. Estás seguro de que quieres continuar?";

// Setup template strings end.
$string["efb-tbl-heading-title"] = "Título";
$string["efb-tbl-heading-type"] = "Tipo";
$string["efb-tbl-heading-shortcode"] = "Código corto";
$string["efb-tbl-heading-author"] = "Autor";
$string["efb-tbl-heading-author2"] = "Actualizado por";
$string["efb-tbl-heading-created"] = "Creado en";
$string["efb-tbl-heading-submitted"] = "Enviado el";
$string["efb-tbl-heading-modified"] = "Última actualización";
$string["efb-tbl-heading-versions"] = "Versiones";
$string["efb-tbl-heading-version"] = "Versión";
$string["efb-tbl-heading-action"] = "Gestionar";
$string["efb-form-action-edit-title"] = "Editar formulario";
$string["efb-form-action-delete-title"] = "Borrar";
$string["efb-form-action-view-data-title"] = "Ver datos";
$string["efb-form-action-preview-title"] = "Formulario de vista previa";
$string["efb-form-action-live-demo-title"] = "Live demonstratie";
$string['livedemologgedinmessage'] = "Als je een live demo verwacht, open dan de link in de incognitomodus.";
$string["efb-form-action-enable-title"] = "Habilitar formulario";
$string["efb-form-action-disable-title"] = "Deshabilitar formulario";
$string["efb-form-action-enable-failed"] = "Error de habilitación de formulario";
$string["efb-form-action-disable-failed"] = "Error al deshabilitar el formulario";
$string["efb-form-action-enable-success"] = "La forma permite el éxito";
$string["efb-form-action-disable-success"] = "Forma deshabilitar el éxito";
$string["efb-form-setting-save-msg"] = "Forma guardada con éxito. Serás redirigido a la lista de formularios. Haga clic en Aceptar para redirigir manualmente.";
$string["efb-form-setting-saved"] = "Tu formulario ya está guardado. Serás redirigido a la lista de formularios.";
$string["efb-form-setting-save-fail-msg"] = "Error al guardar la definición del formulario.";
$string["efb-form-def-save-fail-msg"] = "Error al guardar la definición del formulario.";
$string["efb-form-def-update-fail-msg"] = "No se puede sobrescribir la forma. Presentaciones de usuarios presentes. Intenta crear una nueva forma.";
$string["efb-form-setting-update-fail-msg"] = "No se puede actualizar el formulario.";
$string["efb-form-setting-update-msg"] = "La forma se ha actualizado con éxito. Haga clic en Aceptar para redirigir a la lista de formularios.";
$string["efb-list-form-data-page-title"] = "Lista de datos del formulario.";
$string["duplicate-form-fields"] = "Se encontraron campos de formulario duplicados. Elimine todos los envíos y modifique los campos del formulario.";
$string["efb-msg-form-delete-success"] = "Formulario eliminado exitosamente";
$string["efb-msg-form-delete-fail"] = "Form delete failed";
$string["efb-confirmation-default-msg"] = '<div style="background-color: #efefef; -webkit-text-size-adjust: none !important; margin: 0; padding: 70px 70px 70px 70px;"><table id="template_container" style="text-align: center; padding-bottom: 20px; background-color: rgb(223, 223, 223); box-shadow: rgba(0, 0, 0, 0.024) 0px 0px 0px 3px !important; border-radius: 6px !important; margin: auto;" border="0" width="500" cellspacing="0" cellpadding="0">
<tbody>
<tr>
<td style="background-color: #1177d1;border-top-left-radius: 6px !important;border-top-right-radius: 6px !important;border-bottom: 0;font-family: Arial;font-weight: bold;line-height: 100%;vertical-align: middle;">
<h1 style="text-align: center;color: white;margin: 0px;padding: 28px 24px;display: block;font-family: Arial;font-size: 30px;font-weight: bold;">{SITE_NAME}</h1>
</td>
</tr>
<tr>
<td style="padding: 20px; background-color: #dfdfdf; border-radius: 6px !important;" align="center" valign="top">
<div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Hola {USER_FIRSTNAME},</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">Gracias por la sumisión en{FORM_TITLE}.</div><div style="font-family: Arial; font-size: 14px; line-height: 150%; text-align: left;">{ALL_FIELDS}</div></td></tr></tbody></table><br>
</div>';
$string["efb-valid-form-data"] = "Los datos del formulario son válidos";
$string["efb-invalid-form-data"] = "Los datos del formulario no son válidos";
$string["efb-login-form-disable-different-form"] = "No se puede deshabilitar. Otro formulario de inicio de sesión está activo";
$string['efb-technical-issue-reload'] = 'Problema técnico. Por favor recarga la página.';

// Import export form.
$string["efb-form-action-export-title"] = "Formulario de exportación";
$string["exportcsv"] = "Exportar CSV";
$string["exportcsv-license-activate"] = "Exportar CSV (Activar Licencia)";
$string['efb-form-import'] = 'Formulario de importación';
$string['efb-import-file'] = 'Seleccione el archivo .xml para importar';
$string['efb-import-file_help'] = 'Seleccione el archivo .xml para importar formulario. Este formulario aparecerá en la lista y se puede utilizar en el futuro.';
$string['efb-import-no-file'] = 'Por favor seleccione el archivo';
$string['efb-import-empty-file'] = 'El archivo no contiene nada';
$string['efb-import-invalid-file-no-title'] = 'Formulario no tiene titulo';
$string['efb-import-invalid-file-no-description'] = 'El formulario no tiene descripción';
$string['efb-import-invalid-file-no-definition'] = 'La forma no tiene definición';
$string['efb-import-invalid-file-no-type'] = 'El formulario no tiene tipo de formulario';
$string['efb-import-invalid-file-no-courses'] = 'La forma no tiene cursos.';
/* End */

// Form viewer strings.
$string["efb-form-not-found"] = 'Formulario {$a} no encontrado.';
$string["efb-form-not-enabled"] = 'El formulario {$a} no está habilitado.';
$string["efb-form-data-heading-action"] = "Acción";
$string["efb-form-data-heading-user"] = "Usuario";
$string["efb-form-data-submission-failed"] = "<p> Error al enviar los datos del formulario </p>";
$string["efb-form-data-submission-not-supported"] = "Este tipo de formulario no admite el envío de formularios";
$string["efb-form-data-action-no-action"] = "Sin acciones";
$string["efb-form-data-no-data"] = "Se encontró la definición de forma vacía. Nada que mostrar.";
$string["efb-unknown-error"] = "Error desconocido";
$string["efb-form-definition-found"] = "Se encontró la definición del formulario";
$string["efb-form-loggedin-required"] = 'Debes iniciar sesión antes de ver el formulario. {$a} aquí para iniciar sesión.';
$string["efb-form-loggedin-required-click"] = "Hacer clic";
$string["efb-form-loggedin-not-allowed"] = 'No se puede mostrar el formulario mientras inició sesión. <a href="{$a}">Haga clic aquí para ver la página principal.</a><br>';
$string["delete-userfile-on-save-notice"] = "El archivo será borrado en el envío del formulario.";
$string["cancel-delete-userfile"] = "Cancelar borrado";
$string["user-file-replace"] = "El archivo previamente cargado será reemplazado. ¿Quieres continuar?";

/* JS Strings */
$string["action.add.attrs.attr"] = "¿Qué atributo te gustaría agregar?";
$string["action.add.attrs.value"] = "Valor por defecto";
$string["address"] = "Dirección";
$string["allFieldsRemoved"] = "Todos los campos fueron eliminados.";
$string["allowSelect"] = "Permitir seleccionar";
$string["attribute"] = "Atributo";
$string["attributes"] = "Atributos";
$string["class"] = $string["attrs.class"] = $string["attrs.className"] = "Clase css";
$string["attrs.required"] = "Necesario";
$string["attrs.type"] = "Tipo";
$string["attrs.id"] = "Carné de identidad";
$string["attrs.title"] = "Título";
$string["attrs.style"] = "Estilo";
$string["attrs.dir"] = "Dirección";
$string['attrs.href'] = 'Address';
$string["attrs.placeholder"] = "Marcador de posición";
$string["attrs.name"] = "Nombre";
$string["attrs.template"] = $string["placeholder.template"] = "Plantillas";
$string["attrs.value"] = "Valor";
$string["attributenotpermitted"] = "Atributo {{attr}}: no permitido";
$string["addcondition"] = "+ Condición";
$string["advanceFields"] = "Campos de avance";
$string["autocomplete"] = "Autocompletar";
$string["button"] = "Botón";
$string["cannotBeEmpty"] = "Este campo no puede estar vacío";
$string["checkbox"] = "Caja";
$string["checkboxes"] = "Casillas de verificación";
$string["checkboxGroup"] = "Grupo de casilla de verificación";
$string["clearstep"] = "Claro";
$string["clearallsteps"] = "Pasos claros";
$string["clearstoragemessage"] = "El almacenamiento local está lleno. Por favor, aclararlo para continuar.";
$string["clearstorageautomatic"] = "Borrar automáticamente";
$string["clearstoragemanually"] = "Borrar manualmente";
$string["clearform"] = "Forma clara";
$string["confirmclearstep"] = "¿Está seguro de que desea eliminar todos los campos del paso actual?";
$string["confirmclearallsteps"] = "¿Está seguro de que desea eliminar todos los campos de todos los pasos?";
$string["confirmclearform"] = "¿Estás seguro de que quieres borrar el formulario?";
$string["confirmresetform"] = "¿Estás seguro de que quieres restablecer el formulario? Todos los campos se eliminarán y las plantillas seleccionadas se agregarán. Si se selecciona algún evento, entonces se agregarán los campos obligatorios.";
$string["close"] = "Cerrar";
$string["column"] = "Columna";
$string["city"] = "Ciudad/pueblo";
$string["content"] = "Contenido";
$string["control"] = "Controlar";
$string["controlGroups.nextGroup"] = "Siguiente grupo";
$string["controlGroups.prevGroup"] = "Grupo anterior";
$string["copy"] = "Copiar al portapapeles";
$string["customcssstyle"] = "Estilo CSS personalizado";
$string["cannotremove"] = 'No se puede eliminar {$a}. Contiene elementos de plantilla: <br>';
$string["columnlayout"] = "Definir un diseño de columna.";
$string["columnwidths"] = "Definir anchos de columna";
$string["control-name"] = "Nombre - Nombre y Apellido";
$string["custom"] = "Personalizado";
$string["conditions"] = "Condiciones";
$string["country"] = "País";
$string["cancel"] = "Cancelar";
$string["danger"] = "Peligro";
$string["dark"] = "Oscuro";
$string["datalist"] = "Lista de datos";
$string["dragndrop"] = '{$a} arrastrar y soltar';
$string["default"] = "Defecto";
$string["description"] = "texto de ayuda";
$string["descriptionField"] = "Descripción";
$string["devMode"] = "Modo desarrollador";
$string["divider"] = "Divisor";
$string["display-label"] = "Posición de la etiqueta de campo";
$string["display-label-off"] = "Sin etiqueta";
$string["display-label-top"] = "Parte superior";
$string["display-label-left"] = "Izquierda";
$string["editing.row"] = "Fila de edición";
$string["editNames"] = "Editar nombres";
$string["editorTitle"] = "Elementos de formulario";
$string["editXML"] = "Editar XML";
$string["en-US"] = "Inglés";
$string["email"] = "Email";
$string["field"] = "Campo";
$string["form-width"] = "Anchura(%)";
$string["form-padding"] = "Relleno (px)";
$string["fieldNonEditable"] = "Este campo no puede ser editado.";
$string["fieldRemoveWarning"] = "¿Estás seguro de que quieres eliminar este campo?";
$string["fileUpload"] = "Subir archivo";
$string["firstname"] = "Nombre de pila";
$string["formUpdated"] = "Formulario actualizado";
$string["FormTitle"] = "Título del formulario";
$string["Formnovalidate"] = "Formulario novalidate";
$string["getStarted"] = "Arrastra un campo de la derecha a esta área.";
$string["group"] = "Grupo";
$string["grouped"] = "Agrupados";
$string['recaptcha-error'] = 'Por favor verifica que no eres un robot.';
$string["header"] = "Encabezamiento";
$string["hidden"] = "Entrada oculta";
$string["hide"] = "Editar";
$string["htmlElements"] = "Elementos HTML";
$string["import-form-button"] = "Importar";
$string["import-form-title"] = "Seleccione el archivo json para importar formulario";
$string["info"] = "Información";
$string["input.date"] = "Fecha";
$string["info"] = $string["primary"] = "Información";
$string["input.text"] = "Texto - Texto de una sola línea";
$string["legendfieldset"] = "Leyenda para fieldset";
$string["label"] = "Etiqueta";
$string["labelCount"] = "{etiqueta} {cuenta}";
$string["labelEmpty"] = "La etiqueta de campo no puede estar vacía";
$string["lastname"] = "Apellido";
$string["later"] = "Luego";
$string["layout"] = "Diseño";
$string["limitRole"] = "Limite el acceso a uno o más de los siguientes roles:";
$string["light"] = "Ligero";
$string["link"] = "Enlazar";
$string["mandatory"] = "Obligatorio";
$string["maxlength"] = "Longitud máxima";
$string["meta.group"] = "Grupo";
$string["meta.icon"] = "Ico";
$string["meta.label"] = "Etiqueta";
$string["minOptionMessage"] = "Este campo requiere un mínimo de 2 opciones.";
$string["mobile"] = "Móvil";
$string["nofields"] = "No hay campos para borrar";
$string["name"] = "Nombre";
$string["number"] = "Número";
$string["no"] = "No";
$string["off"] = "Apagado";
$string["on"] = "En";
$string["ok"] = "De acuerdo";
$string["option"] = "Opción";
$string["optional"] = "Opcional";
$string["optionEmpty"] = "Valor de opción requerido";
$string["optionLabel"] = "Opción {cuenta}";
$string["options"] = "Opciones";
$string["panelEditButtons.attrs"] = "+ Atributo";
$string["panelEditButtons.options"] = "+ Opción";
$string["panelEditButtons.tabs"] = "+ Tab";
$string["panelLabels.attrs"] = "Attrs";
$string["panelLabels.config"] = "Config";
$string["panelLabels.meta"] = "Meta";
$string["panelLabels.options"] = "Opciones";
$string["paragraph"] = "Párrafo";
$string["preview"] = "Avance";
$string["primary"] = "Primario";
$string["placeholder"] = "Marcador de posición";
$string["placeholder.email"] = "Ingrese su correo electrónico";
$string["placeholder.label"] = "Etiqueta";
$string["placeholder.password"] = "Ingresa tu contraseña";
$string["placeholder.placeholder"] = "Marcador de posición";
$string['placeholder.href'] = 'Dirección web. Ej: http://google.com. (Nota: el protocolo es necesario. Ej: http:// o https://)';
$string["placeholder.text"] = "Introduce algún texto";
$string["placeholder.textarea"] = "Introduce mucho texto";
$string["placeholder.required"] = "Necesario";
$string["placeholder.type"] = "Tipo";
$string["placeholder.name"] = "Nombre";
$string["placeholder.style"] = "ex:
background-color: white;
border-color: 1px solid black;";
$string["placeholder.required"] = "Necesario";
$string["placeholder.selected"] = "Seleccionado";
$string["password"] = "Contraseña";
$string["panelLabels.logics"] = "Lógicas";
$string["panelEditButtons.logics"] = "Lógicas";
$string["panelLabels.logics"] = "Lógicas";
$string["panelEditButtons.logics"] = "Lógicas";
$string["proceed"] = "Proceder";
$string["row.settings.inputGroup.aria"] = "Aria";
$string["radio"] = "Radio";
$string["radioGroup"] = "Grupo de Radio - Botón de Radio";
$string['recaptcha-row'] = 'Las condiciones no son compatibles. Esta fila contiene el elemento recaptcha. Elimine las condiciones anteriores si se agregó previamente.';
$string["remove"] = "retirar";
$string["removeMessage"] = "Eliminar Elemento";
$string["required"] = "Necesario";
$string["reset"] = "Reiniciar";
$string["reCaptcha"] = "ReCaptcha";
$string["richText"] = "Editor de texto enriquecido";
$string["roles"] = "Acceso";
$string["row"] = "Fila";
$string["row.makeInputGroup"] = "Haz de esta fila un grupo de entrada.";
$string["row.makeInputGroupDesc"] = "Los grupos de entrada permiten a los usuarios agregar conjuntos de entradas a la vez.";
$string["row.settings.fieldsetWrap"] = "Ajustar fila en un & lt; fieldset & gt; etiqueta";
$string["row.settings.fieldsetWrap.aria"] = "Envuelva la fila en Fieldset";
$string["save"] = "Salvar";
$string["secondary"] = "Secundario";
$string["select"] = "Seleccionar - desplegable";
$string["selectColor"] = "Seleccionar el color";
$string["selectionsMessage"] = "Permitir selecciones múltiples";
$string["selectOptions"] = "Opciones";
$string["separator"] = "Separador";
$string["settings"] = "Ajustes";
$string["size"] = "tamaño";
$string["sizes"] = "Tamaños";
$string["sizes.lg"] = "Grande";
$string["sizes.m"] = "Defecto";
$string["sizes.sm"] = "Pequeña";
$string["sizes.xs"] = "Extra Pequeño";
$string["standardFields"] = "Campos estandar";
$string["styles"] = "Estilos";
$string["styles.btn"] = "Estilo de botón";
$string["styles.btn.danger"] = "Peligro";
$string["styles.btn.default"] = "Defecto";
$string["styles.btn.info"] = "Información";
$string["styles.btn.primary"] = "Primario";
$string["styles.btn.success"] = "Éxito";
$string["styles.btn.warning"] = "Advertencia";
$string["subtype"] = "Tipo";
$string["success"] = "Éxito";
$string["submit"] = "Enviar";
$string["Tags"] = "Etiquetas";
$string["tab"] = "Pestañas";
$string["text"] = "Campo de texto";
$string["textarea"] = "Área de texto - Texto de párrafo";
$string["this"] = "Esta";
$string["toggle"] = "Palanca";
$string["ungrouped"] = "Sin agrupar";
$string["UntitledForm"] = "Forma sin titulo";
$string["upgrade"] = "Actualizar a PRO";
$string["username"] = "Nombre de usuario";
$string["value"] = $string["placeholder.value"] = "Valor";
$string["warning"] = "Advertencia";
$string["warning"] = "Advertencia";
$string["website"] = "Sitio web";
$string["yes"] = "Sí";

/* Tab configuration strings for designer */
$string["untitled"] = "Intitulado";
$string["stepindex"] = "Paso {{index}}";
$string["containersettings"] = "Configuraciones de contenedores";
$string["category-container-default"] = "Pasos predeterminados";
$string["category-container-complete"] = "Pasos completados";
$string["category-container-active"] = "Paso activo";
$string["category-container-danger"] = "Paso de advertencia";
$string["category-container-form"] = "Configuración de formulario";
$string["category-container-submit"] = "Enviar configuración de botones";
$string["backgroundcolor"] = 'Color de fondo';
$string['bordercolor'] = 'Color del borde';
$string['textcolor'] = 'Color de texto';

/* Form designer form setting */
$string['submitbuttontext'] = 'Etiqueta';
$string['submitbuttonprocessingtext'] = 'Etiqueta de procesamiento';
$string['submitbuttonposition'] = 'Posición';
$string['position-left'] = 'Izquierda';
$string['position-center'] = 'Centrar';
$string['position-right'] = 'Derecha';
$string['backgroundcolor'] = 'Color de fondo';
$string['textcolor'] = 'Color de la etiqueta';

/* Cron job string */
$string['cleanup_task'] = 'Edwiser Form Tarea de limpieza';

/* Settings strings */
$string['configtitle'] = 'Formas Edwiser';
/* General Settings */
$string['efb-general-settings'] = "Configuración general";
$string['efb-enable-user-level-from-creation'] = "Permitir al profesor crear una nueva forma";
$string['efb-des-enable-user-level-from-creation'] = "Esto permitirá al maestro agregar el nuevo formulario y ver los datos enviados del formulario.";
$string['efb-google-recaptcha-sitekey'] = 'Google reCaptcha clave del sitio';
$string['efb-desc-google-recaptcha-sitekey'] = 'Ingrese google reCaptcha sitekey para usar reCaptcha en su formulario. Nota: Sólo reCAPTCHA v2.';
$string['efb-enable-site-navigation'] = 'Habilitar la navegación usando la barra lateral';
$string['efb-desc-enable-site-navigation'] = 'Habilita esto para agregar enlaces de navegación en la barra lateral.';

// Usage tracking.
$string['enableusagetracking'] = "Habilitar el seguimiento de uso";
$string['enableusagetrackingdesc'] = "<strong>Aviso de seguimiento de uso</strong>

<hr class='text-muted' />

<p> Edwiser de ahora en adelante recopilará datos anónimos para generar estadísticas de uso del producto. </p>

<p> Esta información nos ayudará a guiar el desarrollo en la dirección correcta y la comunidad de Edwiser prosperará. </p>

<p> Una vez dicho esto, no recopilamos sus datos personales ni los de sus estudiantes durante este proceso. Puede deshabilitar esto desde el complemento cada vez que desee darse de baja de este servicio. </p>

<p>Una descripción general de los datos recopilados está disponible <strong><a href='https://forums.edwiser.org/topic/67/anonymously-tracking-the-usage-of-edwiser-products' target='_blank'>aquí</a></strong>.</p>";

/* License Settings */
$string['edwiserformlicenseactivation'] = 'Edwiser Forms License Activation';
$string['licensestatus'] = 'Estado de la licencia';
$string['licensenotactive'] = '<strong> Alert! </strong> La licencia no está activada, <strong> active </strong> la licencia en la configuración de Edwiser Form.';
$string['licensenotactiveadmin'] = '<strong><strong> ¡Alerta! </strong> La licencia no está activada, <strong> active </strong> la licencia <a href="'.$CFG->wwwroot.'/admin/settings.php?section=themesettingremui#remuilicensestatus" >here</a>.';
$string['activatelicense'] = 'Activar licencia';
$string['deactivatelicense'] = 'Desactivar licencia';
$string['renewlicense'] = 'Renovar licencia';
$string['active'] = 'Activo';
$string['notactive'] = 'No activo';
$string['expired'] = 'Muerto';
$string['licensekey'] = 'Clave de licencia';
$string['noresponsereceived'] = 'No se recibió respuesta del servidor. Por favor, inténtelo de nuevo más tarde.';
$string['licensekeydeactivated'] = 'La clave de licencia está desactivada.';
$string['siteinactive'] = 'Sitio inactivo (Presione activar licencia para activar el complemento).';
$string['entervalidlicensekey'] = 'Por favor, introduzca una clave de licencia válida.';
$string['licensekeyisdisabled'] = 'Su clave de licencia está deshabilitada.';
$string['licensekeyhasexpired'] = "Su clave de licencia ha caducado. Por favor, renuévalo.";
$string['licensekeyactivated'] = "Su clave de licencia está activada.";
$string['enterlicensekey'] = "Por favor, introduzca la clave de licencia correcta.";
/*****************************/

$string['efb-cron-start'] = 'Edwiser Form Cron comenzó.';
$string['listforms-empty'] = 'Usted no tiene ninguna forma. Haga clic en Agregar nuevo formulario para crear uno.';
$string['listformdata-empty'] = "Aún no hay envíos.";
$string['efb-heading-listforms-showing'] = 'Mostrando {$a->start} a {$a->end} de {$a->total} entradas';
$string['efb-delete-form-and-data'] = 'El formulario {$a->title} con ID ({$a->id}) se eliminará junto con sus envíos. ¿Estás seguro de que quieres borrar este formulario?';
$string['efb-missing-name-attribute-field'] = 'Indique el nombre en: <strong>{$a}</strong>. Esto es importante para ejecutar la forma correctamente.';
$string['submissionbeforedate'] = 'El formulario estará disponible en (<strong>{$a}</strong>).';
$string['submissionafterdate'] = 'El formulario ha caducado el (<strong>{$a}</strong>).';
$string['fullpage-link-message'] = '<a class="efb-view-fullpage" href="#"> Haga clic aquí </a> para ver el formulario en una nueva pestaña.';
$string['fullpage-link-clicked'] = 'El formulario se abre en otra pestaña.';
$string['efb-template-inactive-license'] = '<strong>{$a}</strong> es parte de la versión Pro de Edwiser Forms. Activa tu licencia ahora para aprovechar esta característica.';
$string['efb-lbl-confirmation-subject'] = 'Asunto de correo electrónico de confirmación de formulario';
$string['efb-lbl-confirmation-default-subject'] = 'Formulario enviado con éxito.';
$string['efb-lbl-confirmation-msg'] = 'Mensaje de correo electrónico de confirmación de formulario';
$string['efb-form-data-submission-successful'] = '<p> Formulario enviado correctamente. <a href="{$a}"> Haga clic </a> aquí para visitar la página principal. </p>';
$string['efb-form-submission-found'] = '<p> Ya has enviado una respuesta. No está permitido editar o enviar una respuesta. <a href="{$a}"> Haga clic </a> aquí para visitar la página principal. </p>';
$string['condition-choose-source'] = 'Elige la fuente';
$string['page-background-opacity'] = 'Opacidad de fondo de página';
$string['form-responsive'] = 'Forma sensible';
$string['placeholder.class'] = 'Clases separadas por espacios';
$string['placeholder.className'] = 'Clases separadas por espacios';
$string['row-move'] = 'Fila de orden';
$string['row-edit'] = 'Editar fila, columna, propiedades lógicas condicionales';
$string['row-clone'] = 'Duplicar fila con sus columnas y campos.';
$string['row-remove'] = 'Eliminar fila junto con columnas y campos';
$string['column-remove'] = 'Eliminar columna junto con campos';
$string['column-clone'] = 'Columna duplicada con todos los campos dentro de ella';
$string['column-move'] = 'Mover/ordenar columna en cualquier fila';
$string['field-move'] = 'Mover campo en cualquier fila/columna';
$string['field-edit'] = 'Editar propiedades/opciones de campo';
$string['field-clone'] = 'Campo duplicado y sus propiedades.';
$string['field-remove'] = 'Quitar campo';
$string['delete-form'] = 'Elimina todo';
$string['reset-form'] = 'Restablecer formulario a predeterminado';
$string['edit-form'] = 'Editar configuración de formulario';
$string['remove-attrs'] = 'Eliminar atributo';
$string['remove-options'] = 'Eliminar opción';
$string['remove-configs'] = 'Eliminar config';
$string['remove-condition'] = 'Quitar condición';
$string['order-option'] = 'Opción de orden';
$string['select-options-label'] = 'Etiqueta de opción';
$string['select-options-value'] = 'Valor de la opción';
$string['input-radio-options-name'] = 'Nombre de radio';
$string['input-radio-options-label'] = 'Etiqueta de radio';
$string['input-radio-options-value'] = 'Valor de radio';
$string['input-checkbox-options-name'] = 'Nombre de casilla de verificación';
$string['input-checkbox-options-label'] = 'Etiqueta de casilla de verificación';
$string['input-checkbox-options-value'] = 'Valor de casilla de verificación';
$string['input-invalid-type'] = 'Tipo de entrada no válido en <strong>{$a}</strong>';
$string['select-option-invalid'] = 'Opción no válida en <strong>{$a}</strong>.';
$string['input-radio-option-invalid'] = 'Opción de radio no válida en <strong>{$a}</strong>';
$string['input-checkbox-option-invalid'] = 'Opción de casilla de verificación no válida en <strong>{$a}</strong>';
$string['input-all-option-invalid'] = 'Opción no válida en <strong>{$a}</strong>.';
$string['category-container-page'] = 'Configuración de página';
$string['select-options-selected'] = '';
$string['input-checkbox-options-selected'] = '';
$string['input-radio-options-selected'] = '';

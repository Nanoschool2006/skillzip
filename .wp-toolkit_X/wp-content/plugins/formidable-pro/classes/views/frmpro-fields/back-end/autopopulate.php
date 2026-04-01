<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( ! class_exists( 'FrmTextToggleStyleComponent' ) ) {
	include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/backwards-compatibility/autopopulate.php';
	return;
}

$class_attr = 'default-value-section-' . $field['id'] . ' frm-lookup-box-' . $field['id'] . ( isset( $default_value_types['get_values_field']['current'] ) ? '' : ' frm_hidden' );
FrmProLookupFieldsController::show_autopopulate_value_section_in_form_builder( $field, compact( 'class_attr' ) );

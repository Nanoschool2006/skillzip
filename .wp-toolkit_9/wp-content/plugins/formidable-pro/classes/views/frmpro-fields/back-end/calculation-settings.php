<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

FrmAppHelper::show_search_box(
	array(
		'input_id'    => 'frm_calc_' . $field['id'],
		'placeholder' => __( 'Search Fields', 'formidable' ),
		'tosearch'    => 'frm-field-list-' . $field['id'],
	)
);
?>

<ul
	class="frm_code_list frm-full-hover frm-short-list"
	data-exclude="<?php echo esc_attr( json_encode( FrmProField::exclude_from_calcs() ) ); ?>"
	id="frm-calc-list-<?php echo esc_attr( $field['id'] ); ?>"
></ul>

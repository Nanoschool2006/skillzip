<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>

<div id="frm-date-range-options-<?php echo esc_attr( $field['id'] ); ?>">
	<?php
	if ( FrmField::get_option( $field, 'is_range_end_field' ) ) {
		?>
			<input type="hidden" id="frm_is_range_end_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[is_range_end_field_<?php echo esc_attr( $field['id'] ); ?>]" value="1" />
			<input type="hidden" id="frm_range_start_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[range_start_field_<?php echo esc_attr( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['range_start_field'] ); ?>" />
		<?php
	}
	if ( FrmField::get_option( $field, 'is_range_start_field' ) ) {
		?>
			<input type="hidden" id="frm_is_range_start_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[is_range_start_field_<?php echo esc_attr( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['is_range_start_field'] ); ?>" />
			<input type="hidden" id="frm_range_end_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[range_end_field_<?php echo esc_attr( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['range_end_field'] ); ?>" />
		<?php
	}
	?>
</div>
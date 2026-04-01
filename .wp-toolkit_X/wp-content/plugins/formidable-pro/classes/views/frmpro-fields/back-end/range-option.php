<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_form_field <?php echo $show_jquery_placeholder ? 'frm_noallow' : ''; ?>">
	<label for="frm_range_field_<?php echo esc_attr( $field['id'] ); ?>" class="frm_help frm-mb-0 frm-field-date-or-time-enable-range <?php echo ! empty( $field['is_range_end_field'] ) || $show_jquery_placeholder ? 'frm-disabled' : ''; ?>" title="<?php echo esc_attr( $range_option_tooltip ); ?>" >
		<input type="checkbox" id="frm_range_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[range_field_<?php echo esc_attr( $field['id'] ); ?>]" data-range-type="<?php echo esc_attr( $field['type'] ); ?>"  data-field-id="<?php echo (int) $field['id']; ?>" value="1" <?php checked( ( ! empty( $field['range_field'] ) || ! empty( $field['is_range_end_field'] ) ) && ! $show_jquery_placeholder, 1 ); ?>/>
		<?php
		/* translators: %s: Field type */
		printf( esc_html__( '%s Range', 'formidable-pro' ), esc_html( ucfirst( $field['type'] ) ) );
		?>
	</label>
</div>

<?php if ( $show_jquery_placeholder ) { ?>
	<div class="frm_note_style">
		<?php
		/* translators: %1$s: Datepicker library name. %2$s: Link to switch to Flatpickr. */
		printf(
			esc_html__( 'Date ranges are not supported with %1$s. %2$s to enable this.', 'formidable-pro' ),
			esc_html( FrmProAppHelper::use_jquery_datepicker() ? 'jQuery' : 'Flatpickr' ),
			'<a href="' . esc_url( admin_url( 'admin.php?page=formidable-settings&t=general_settings' ) ) . '" target="_blank">' . esc_html__( 'Switch to Flatpickr', 'formidable-pro' ) . '</a>'
		);
		?>
	</div>
<?php } ?>

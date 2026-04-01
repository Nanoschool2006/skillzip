<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field frm6_followed frm-my-sm frm_sep_val_<?php echo esc_attr( $field['type'] ); ?>">
	<label class="frm-h-stack-xs" for="separate_value_<?php echo absint( $field['id'] ); ?>">
		<input type="checkbox" name="field_options[separate_value_<?php echo absint( $field['id'] ); ?>]" id="separate_value_<?php echo absint( $field['id'] ); ?>" value="1" <?php checked( $field['separate_value'], 1 ); ?> class="frm_toggle_sep_values" />
		<span><?php esc_html_e( 'Use separate values', 'formidable' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			sprintf( __( 'Add a separate value to use for calculations, email routing, saving to the database, and many other uses. The option values are saved while the option labels are shown in the form. Use [%s] to show the saved value in emails or views.', 'formidable-pro' ), $field['id'] . ' show=value' ),
			array(
				'data-placement' => 'right',
				'data-container' => 'body',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
</p>

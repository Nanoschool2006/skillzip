<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field">
	<label for="frm_conf_field_<?php echo absint( $field['id'] ); ?>">
		<?php esc_html_e( 'Require Confirmation', 'formidable-pro' ); ?>
	</label>
	<select name="field_options[conf_field_<?php echo absint( $field['id'] ); ?>]" class="conf_field" id="frm_conf_field_<?php echo absint( $field['id'] ); ?>">
		<option value="" <?php selected( $field['conf_field'], '' ); ?>>
			<?php esc_html_e( 'No Confirmation', 'formidable-pro' ); ?>
		</option>
		<option value="inline" <?php selected( $field['conf_field'], 'inline' ); ?>>
			<?php esc_html_e( 'Inline', 'formidable-pro' ); ?>
		</option>
		<option value="below" <?php selected( $field['conf_field'], 'below' ); ?>>
			<?php esc_html_e( 'Below Field', 'formidable-pro' ); ?>
		</option>
	</select>
</p>
<p class="frm-conf-box-<?php echo esc_attr( $field['id'] . ( ! empty( $field['conf_field'] ) ? '' : ' frm_hidden' ) ); ?>">
	<label for="frm_conf_label_<?php echo absint( $field['id'] ); ?>" class="frm-mb-0">
		<input type="checkbox" id="frm_conf_label_<?php echo absint( $field['id'] ); ?>" class="frm-conf-label-checkbox" value="1" <?php checked( ! empty( $field['conf_label'] ) ); ?> />
		<input type="hidden" name="field_options[conf_label_<?php echo absint( $field['id'] ); ?>]" value="<?php echo ! empty( $field['conf_label'] ) ? '1' : '0'; ?>" />
		<?php esc_html_e( 'Show confirmation field label', 'formidable-pro' ); ?>
	</label>
</p>

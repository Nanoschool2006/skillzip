<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p>
	<label class="frm-h-stack-xs" for="lookup_filter_current_user_<?php echo absint( $field['id'] ); ?>">
		<input type="checkbox" name="field_options[lookup_filter_current_user_<?php echo absint( $field['id'] ); ?>]" id="lookup_filter_current_user_<?php echo absint( $field['id'] ); ?>" value="1" <?php checked( $field['lookup_filter_current_user'], 1 ); ?> />
		<?php esc_html_e( 'Limit options to those created by the current user', 'formidable-pro' ); ?>
		<?php FrmAppHelper::tooltip_icon( __( 'Does not apply to administrators.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
</p>

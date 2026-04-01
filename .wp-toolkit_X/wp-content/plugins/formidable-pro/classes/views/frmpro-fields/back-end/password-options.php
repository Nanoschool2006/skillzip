<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p>
	<label class="frm-h-stack-xs" for="strong_pass_<?php echo esc_attr( $field['id'] ); ?>">
		<input type="checkbox" id="strong_pass_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[strong_pass_<?php echo esc_attr( $field['id'] ); ?>]" value="1" <?php checked( $field['strong_pass'], 1 ); ?> />
		<span><?php esc_html_e( 'Require a strong password', 'formidable-pro' ); ?></span>
		<?php FrmAppHelper::tooltip_icon( __( 'A strong password is at least 8 characters long and includes a an uppercase letter, a lowercase letter, a number, and a character.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
</p>
<p>
	<label class="frm-h-stack-xs" for="strength_meter_<?php echo esc_attr( $field['id'] ); ?>">
		<input type="checkbox" id="strength_meter_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[strength_meter_<?php echo esc_attr( $field['id'] ); ?>]" value="1" <?php checked( $field['strength_meter'], 1 ); ?> />
		<span><?php esc_html_e( 'Show password strength meter', 'formidable-pro' ); ?></span>
		<?php FrmAppHelper::tooltip_icon( __( 'Display a meter to the user showing the password requirements and strength of the typed password. This will only apply when the field is not within a Repeater.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
</p>
<p>
	<label class="frm-h-stack-xs" for="show_password_<?php echo esc_attr( $field['id'] ); ?>">
		<input type="checkbox" id="show_password_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[show_password_<?php echo esc_attr( $field['id'] ); ?>]" class="frm_show_password_setting_input" data-fid="<?php echo esc_attr( $field['id'] ); ?>" value="1" <?php checked( $field['show_password'], 1 ); ?> />
		<span><?php esc_html_e( 'Include a show/hide password icon', 'formidable-pro' ); ?></span>
		<?php FrmAppHelper::tooltip_icon( __( 'Display an icon in the input box to allow users to make the password visible as they type.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
</p>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field frm_auto_grow_option">
	<label class="frm-h-stack-xs" id="for_frm_auto_grow_field_<?php echo esc_attr( $field['id'] ); ?>" for="frm_auto_grow_field_<?php echo esc_attr( $field['id'] ); ?>">
		<input type="checkbox" id="frm_auto_grow_field_<?php echo esc_attr( $field['id'] ); ?>" name="field_options[auto_grow_<?php echo esc_attr( $field['id'] ); ?>]" value="1" <?php checked( $field['auto_grow'], 1 ); ?> />
		<span><?php esc_html_e( 'Auto Grow', 'formidable-pro' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			__( 'Auto Grow: Automatically expand the height of the field when the text reaches the maximum rows', 'formidable-pro' ),
			array(
				'data-placement' => 'left',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
</p>

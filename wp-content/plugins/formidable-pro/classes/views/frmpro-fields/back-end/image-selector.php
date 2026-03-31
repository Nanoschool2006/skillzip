<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$show_image = FrmProImages::should_show_images( $field );
?>
<div class="frm_image_preview_wrapper frm_option_key field_<?php echo esc_attr( $field['id'] ); ?>_image_id <?php echo esc_attr( $show_image ? '' : ' frm_hidden ' ); ?>">
	<input type="hidden" class="frm_image_id" data-frmchange="trim"
			name="field_options[options_<?php echo esc_attr( $field['id'] ); ?>][<?php echo esc_attr( $opt_key ); ?>][image]"
			id="field_image_<?php echo esc_attr( $field['id'] . '-' . $opt_key ); ?>"
			value="<?php echo esc_attr( ! empty( $image['id'] ) ? $image['id'] : '0' ); ?>" />
	<div class="frm_image_preview_frame <?php echo ! empty( $image['url'] ) ? '' : 'frm_hidden'; ?>">
		<div class="frm_image_styling_frame">
			<img id="frm_image_preview_<?php echo esc_attr( $field['id'] . '-' . $opt_key ); ?>" src="<?php echo esc_url( ! empty( $image['url'] ) ? $image['url'] : '' ); ?>" class="frm_image_preview" alt="<?php echo esc_attr( $opt ); ?>" />
			<div class="frm_image_data">
				<div class="frm_image_preview_title"><?php echo esc_html( $image['filename'] ); ?></div>
				<div class="frm_remove_image_option frm-h-stack" title="<?php esc_attr_e( 'Remove image', 'formidable-pro' ); ?>">
					<?php FrmAppHelper::icon_by_class( 'frmfont frm_delete_icon' ); ?>
					<span><?php esc_html_e( 'Delete', 'formidable' ); ?></span>
				</div>
			</div>
		</div>
	</div>
	<button type="button" class="frm_choose_image_box frm_button frm-flex-center frm-gap-xs frm_no_style_button<?php echo ! empty( $image['url'] ) ? ' frm_hidden' : ''; ?>">
		<?php FrmAppHelper::icon_by_class( 'frmfont frm_upload3_icon frm_svg20' ); ?>
		<span><?php esc_html_e( 'Upload image', 'formidable-pro' ); ?></span>
	</button>
</div>
<?php
unset( $show_image );

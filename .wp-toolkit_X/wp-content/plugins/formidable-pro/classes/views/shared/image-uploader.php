<?php
/**
 * View for image uploader
 *
 * @since 6.25
 *
 * @package FormidablePro
 *
 * @var array $args See {@see FrmProSettingsController::image_uploader()}.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$class = 'frm-image-upload-setting';

if ( ! empty( $args['img_id'] ) ) {
	$class .= ' frm-has-uploaded-image';
}
?>
<div class="<?php echo esc_attr( $class ); ?>">
	<div class="frm-image-upload-preview">
		<?php if ( $args['img_id'] ) : ?>
			<img src="<?php echo esc_url( wp_get_attachment_image_url( $args['img_id'] ) ); ?>" alt="" class="frm-image-upload-preview-img" />
		<?php endif; ?>
		<img src="<?php echo esc_url( FrmAppHelper::plugin_url() . '/images/email-styles/placeholder.png' ); ?>" alt="" />
	</div>

	<div class="frm-image-upload-btns">
		<button type="button" class="frm-upload-image-btn frm-button-secondary"><?php esc_html_e( 'Upload Image', 'formidable' ); ?></button>
		<br />
		<button type="button" class="frm-remove-image-btn frm-button-secondary"><?php esc_html_e( 'Remove Image', 'formidable-pro' ); ?></button>
	</div>

	<input type="hidden" name="frm_email_image_id" class="frm-image-id-input" value="<?php echo intval( $args['img_id'] ); ?>" />
</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="frm-remaining-qty-modal" class="frm_hidden frm-modal frm_common_modal frm_wrap">
	<div class="postbox">
	<div class="frm_modal_top">
		<div class="frm-modal-title frm-pb-md">
			<?php esc_html_e( 'Option Limits Settings', 'formidable-pro' ); ?>
		</div>
		<div>
			<a class="dismiss" title="<?php esc_attr_e( 'Close', 'formidable' ); ?>">
				<?php FrmAppHelper::icon_by_class( 'frmfont frm_close_icon', array( 'aria-label' => __( 'Close', 'formidable' ) ) ); ?>
			</a>
		</div>
	</div>
	<div class="frm_grid_container">
		<div class="frm-px-md">
			<label for="frm-remaining-qty-label">
				<?php esc_html_e( 'Remaining Label', 'formidable-pro' ); ?>
			</label>
			<input type="text" id="frm-remaining-qty-label"/>
			<label for="frm-exhausted-message" class="frm-mt-sm">
				<?php esc_html_e( 'Exhausted Message', 'formidable-pro' ); ?>
			</label>
			<input type="text" id="frm-exhausted-message"/>
		</div>
	</div>
	<div class="frm_modal_footer frm-mt-lg">
		<button class="button-primary frm-button-primary" id="frm-update-remaining-qty-messages">
			<?php esc_attr_e( 'Save Changes', 'formidable-pro' ); ?>
		</button>
	</div>
	</div>
</div>

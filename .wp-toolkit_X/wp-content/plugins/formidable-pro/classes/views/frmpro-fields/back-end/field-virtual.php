<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div class="frm_form_field">
	<span class="frm-with-left-icon frm-block">
		<?php FrmAppHelper::icon_by_class( 'frmfont frm-virtual-field-icon', array( 'aria-hidden' => 'true' ) ); ?>
		<input type="text" id="<?php echo esc_attr( $html_id ); ?>" value="<?php esc_attr_e( 'Virtual fields will not show in your form.', 'formidable-pro' ); ?>" disabled />
	</span>
</div>

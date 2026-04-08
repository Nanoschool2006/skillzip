<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<button id="publish" class="button-primary frm-button-primary" type="button">
	<?php esc_html_e( 'Update', 'formidable' ); ?>
</button>

<button id="frm_load_view_in_new_tab" class="button-secondary frm-button-secondary" type="button">
	<?php esc_html_e( 'View', 'formidable' ); ?>
</button>

<a href="#" role="button" class="frm_submit_form frm-button-tertiary frm_button_submit frm-embed-view" tabindex="0" role="button">
	<?php esc_html_e( 'Embed', 'formidable' ); ?>
</a>
<input type="hidden" id="form_id" value ="" />

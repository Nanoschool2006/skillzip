<div class="frm_grid_container">
	<div class="frm12 frm_form_field">
		<?php if ( get_post_meta( $form_action->ID, 'frm_zapier_test_hook' ) ) { ?>
			<div class="frm_warning_style">
				<?php esc_html_e( 'This action is only temporary and will be unsubscribed automatically.', 'frmzap' ); ?>
			</div>
		<?php } ?>
		<label for="<?php echo esc_attr( $this->get_field_id( 'zap_url' ) ); ?>">
			<?php esc_html_e( 'Zapier WebHook URL', 'frmzap' ); ?>
		</label>
		<input type="hidden" name="<?php echo esc_attr( $action_control->get_field_name( 'zap_url' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'zap_url' ) ); ?>" value="<?php echo esc_url( $action_control->zap_url ); ?>" />
		<p><?php echo esc_url( $action_control->zap_url ); ?></p>
		<p class="howto" id="frmzap_desc_<?php echo esc_attr( $this->get_field_id( 'zap_url' ) ); ?>">
			<?php esc_html_e( 'Form actions can be set up in Zapier', 'frmzap' ); ?>.
			<?php if ( empty( $action_control->zap_url ) ) { ?>
			<a href="https://zapier.com/app/editor" target="_blank" rel="noopener">
				<?php esc_html_e( 'Get WebHook URL', 'frmzap' ); ?>
			</a>
			<?php } else { ?>
			<a href="https://zapier.com/app/zaps" target="_blank" rel="noopener">
				<?php esc_html_e( 'Edit Zap', 'frmzap' ); ?>
			</a>
			<?php } ?>
		</p>
	</div>
</div>

<?php
/**
 * @since 2.0
 * @var FrmRegResetPWForm $form
 */
?>

<div id="<?php echo esc_attr( $form->get_html_id() ); ?>" class="<?php echo esc_attr( $form->get_class() ); ?>">

	<form id="resetpasswordform_<?php echo esc_attr( $form->get_form_number() ); ?>" action="<?php echo esc_url( site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>" method="post" class="frm-show-form">

		<?php if ( count( $form->get_errors() ) > 0 ) : ?>
			<div class="frm_error_style">
				<?php foreach ( $form->get_errors() as $error ) : ?>
					<?php echo FrmAppHelper::kses( $error, 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="frm_form_fields">
			<fieldset>

				<div class="frm_description">
					<p><?php echo FrmAppHelper::kses( $form->get_description(), 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>

				<div class="frm_form_field form-field <?php echo esc_attr( $this->label_class ); ?>">
					<label for="<?php echo esc_attr( $form->get_first_field_id() ); ?>" class="frm_primary_label"><?php esc_html_e( 'New Password', 'frmreg' ); ?>
						<span class="frm_required">*</span>
					</label>
					<?php $this->maybe_show_password_widgets( $args['password_visibility_toggle'], $args['password_strength'] ); ?>
				</div>

				<div class="frm_form_field form-field <?php echo esc_attr( $this->label_class ); ?>">
					<label for="<?php echo esc_attr( $form->get_second_field_id() ); ?>" class="frm_primary_label"><?php esc_html_e( 'Confirm New Password', 'frmreg' ); ?>
						<span class="frm_required">*</span>
					</label>
					<?php $this->maybe_show_password_widgets( $args['password_visibility_toggle'], false, true ); ?>
				</div>

				<p class="description"><?php echo esc_html( wp_get_password_hint() ); ?></p>

				<input type="hidden" id="<?php echo esc_attr( $form->get_user_field_id() ); ?>" name="rp_login" value="<?php echo esc_attr( $form->get_login() ); ?>" autocomplete="off" />
				<input type="hidden" name="rp_key" value="<?php echo esc_attr( $form->get_key() ); ?>" />


				<div class="frm_submit">
					<input value="<?php echo esc_attr( $form->get_submit_text() ); ?>" type="submit">
				</div>

			</fieldset>
		</div>
	</form>
</div>

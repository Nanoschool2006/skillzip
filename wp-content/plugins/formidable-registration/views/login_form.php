<?php
/**
 * @since 2.01
 * @var FrmRegLoginForm $login_form
 * @var bool $showing_error_messages
 * @var bool $showing_messages
 */
?>

<div id="<?php echo esc_attr( $login_form->get_html_id() ); ?>" class="<?php echo esc_attr( $login_form->get_class() ); ?>">

	<?php if ( $login_form->get_slide() ) { ?>
		<span class="frm-open-login">
			<a href="#"><?php echo esc_html( $login_form->get_submit_label() ); ?> &rarr;</a>
		</span>
	<?php } ?>
	<form method="post" action="<?php echo esc_url( $login_form->get_form_action_url() ); ?>" >

		<?php if ( $showing_error_messages ) { ?>
			<!-- Errors -->
			<div class="frm_error_style">
				<?php foreach ( $login_form->get_errors() as $error ) : ?>
					<?php echo FrmAppHelper::kses( $error, 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
			<?php
		} ?>

		<?php if ( $showing_messages ) { ?>
			<!-- Success Messages -->
			<div class="frm_message">
				<?php foreach ( $login_form->get_messages() as $message ) : ?>
					<?php echo FrmAppHelper::kses( $message, 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endforeach; ?>
			</div>
		<?php } ?>
		<div class="frm_form_fields">
			<fieldset>
				<div class="frm_fields_container">

				<div class="<?php echo esc_attr( $login_form->get_username_class() ); ?>">
					<label for="<?php echo esc_attr( $login_form->get_username_id() ); ?>" class="frm_primary_label"><?php
						echo esc_html( $login_form->get_username_label() ) ?>
					</label>
					<input id="<?php echo esc_attr( $login_form->get_username_id() ); ?>" name="log" value="<?php echo esc_attr( $login_form->get_username_value() ); ?>" placeholder="<?php echo esc_attr( $login_form->get_username_placeholder() ); ?>" type="text">
				</div>

				<div class="<?php echo esc_attr( $login_form->get_password_class() ); ?>">
					<label for="<?php echo esc_attr( $login_form->get_password_id() ); ?>" class="frm_primary_label"><?php
						echo esc_html( $login_form->get_password_label() ) ?>
					</label>
					<input id="<?php echo esc_attr( $login_form->get_password_id() ); ?>" name="pwd" value="" type="password" placeholder="<?php echo esc_attr( $login_form->get_password_placeholder() ); ?>" >
				</div>

				<input type="hidden" name="redirect_to" value="<?php echo esc_url( $login_form->get_redirect() ); ?>" />

				<?php if ( $login_form->get_layout() === 'h' ) {
					$login_form->get_login_button_html();
				} ?>

				<?php if ( $login_form->get_show_remember() ) { ?>
				<div class="<?php echo esc_attr( $login_form->get_remember_class() ); ?>">
					<div class="frm_opt_container">
						<div class="frm_checkbox">
							<label for="<?php echo esc_attr( $login_form->get_remember_id() ); ?>">
								<input name="rememberme" id="<?php echo esc_attr( $login_form->get_remember_id() ); ?>" value="forever"<?php echo $login_form->get_remember_value() ? ' checked="checked"' : ''; ?> type="checkbox" /><?php
								echo esc_html( $login_form->get_remember_label() ); ?>
							</label>
						</div>
					</div>
				</div>
				<?php } ?>

				<?php if ( $login_form->get_show_lost_password_link() ) { ?>
				<div class="<?php echo esc_attr( $login_form->get_lost_password_class() ); ?>">
					<a class="forgot-password" href="<?php echo esc_url( $login_form->reset_password_link() ); ?>">
						<?php echo esc_html( $login_form->get_lost_password_label() ); ?>
					</a>
				</div>
				<?php } ?>

				<?php
				if ( $login_form->get_layout() === 'v' ) {
					$login_form->get_login_button_html();
				}
				?>

				<?php if ( $login_form->get_show_register_link() ) { ?>
				<div class="<?php echo esc_attr( $login_form->get_register_link_class() ); ?>">
					<a href="<?php echo esc_url( $login_form->register_link() ); ?>">
						<?php echo esc_html( $login_form->get_register_label() ); ?>
					</a>
				</div>
				<?php } ?>

				<div style="clear:both;"></div>

				</div><!-- End .frm_fields_container -->
			</fieldset>
		</div>
	</form>
</div>

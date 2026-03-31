<div class="frm_grid_container">
	<p class="frm6 frm_form_field">
		<label>
			<?php
			esc_html_e( 'User Email', 'frmreg' );
			FrmRegAppController::show_svg_tooltip( __( 'Only Email fields will show here. An email field must be selected if you would like to register new users with this form.', 'frmreg' ) );
			?>
		</label>

		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_email' ) ); ?>">
			<option value="">- <?php esc_html_e( 'None', 'frmreg' ); ?> -</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'email' === $field->type ) {
						?>
						<option value="<?php echo absint( $field->id ); ?>" <?php selected( $form_action->post_content['reg_email'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_form_field">
		<label><?php esc_html_e( 'Username', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_username' ) ); ?>">
			<option value=""><?php esc_html_e( 'Automatically generate from email address', 'frmreg' ); ?></option>
			<option value="-1" <?php selected( $form_action->post_content['reg_username'], '-1' ); ?>>
				<?php esc_html_e( 'Use Full Email Address', 'frmreg' ); ?>
			</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'text' === $field->type ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_username'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<?php $id_attr = $this->get_field_id( 'reg_password' ); ?>
	<p class="frm6 frm_form_field">
		<label for="<?php echo esc_attr( $id_attr ); ?>"><?php esc_html_e( 'Password', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_password' ) ); ?>" id="<?php echo esc_attr( $id_attr ); ?>>" class="frm_reg_password">
			<option value=""><?php esc_html_e( 'Set with link in email notification', 'frmreg' ); ?></option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				$password_field_types = (array) apply_filters( 'frmreg_password_field_types', array( 'text', 'password' ) );
				foreach ( $fields as $field ) {
					if ( in_array( $field->type, $password_field_types, true ) ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_password'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
					unset( $field );
				}
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_first frm_form_field">
		<label><?php esc_html_e( 'First Name', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_first_name' ) ); ?>">
			<option value="">- <?php esc_html_e( 'None', 'frmreg' ); ?> -</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'text' === $field->type || 'name' === $field->type ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_first_name'], $field->id ); ?>>
							<?php
							echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) );

							if ( 'name' === $field->type ) {
								echo ' (';
								esc_html_e( 'First', 'formidable' );
								echo ')';
							}
							?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_form_field">
		<label><?php esc_html_e( 'Last Name', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_last_name' ) ); ?>">
			<option value="">- <?php esc_html_e( 'None', 'frmreg' ); ?> -</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'text' === $field->type || 'name' === $field->type ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_last_name'], $field->id ); ?>>
							<?php
							echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) );

							if ( 'name' === $field->type ) {
								echo ' (';
								esc_html_e( 'Last', 'formidable' );
								echo ')';
							}
							?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_form_field">
		<label><?php esc_html_e( 'Display Name', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_display_name' ) ); ?>">
			<option value=""><?php esc_html_e( 'Same as Username', 'frmreg' ); ?></option>
			<option value="display_firstlast" <?php selected( $form_action->post_content['reg_display_name'], 'display_firstlast' ); ?>>
				<?php esc_html_e( 'First Last', 'frmreg' ); ?>
			</option>
			<option value="display_lastfirst" <?php selected( $form_action->post_content['reg_display_name'], 'display_lastfirst' ); ?>>
				<?php esc_html_e( 'Last First', 'frmreg' ); ?>
			</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( in_array( $field->type, array( 'text', 'select', 'radio' ), true ) ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_display_name'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_form_field">
		<label><?php esc_html_e( 'Website', 'frmreg' ); ?></label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_user_url' ) ); ?>">
			<option value="">- <?php esc_html_e( 'None', 'frmreg' ); ?> -</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'url' === $field->type ) {
						?>
						<option value="<?php echo absint( $field->id ); ?>" <?php selected( $form_action->post_content['reg_user_url'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<p class="frm6 frm_form_field">
		<label><?php esc_html_e( 'User Role', 'frmreg' ); ?></label>
		<?php FrmAppHelper::wp_roles_dropdown( $this->get_field_name( 'reg_role' ), $form_action->post_content['reg_role'] ); ?>
	</p>

	<p class="frm6 frm_form_field">
		<label>
			<?php
			esc_html_e( 'Avatar', 'frmreg' );
			FrmRegAppController::show_svg_tooltip( __( 'Only file upload fields will show here.', 'frmreg' ) );
			?>
		</label>
		<select name="<?php echo esc_attr( $this->get_field_name( 'reg_avatar' ) ); ?>">
			<option value="">- <?php esc_html_e( 'None', 'frmreg' ); ?> -</option>
			<?php
			if ( isset( $fields ) && is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					if ( 'file' === $field->type ) {
						?>
						<option value="<?php echo esc_attr( $field->id ); ?>" <?php selected( $form_action->post_content['reg_avatar'], $field->id ); ?>>
							<?php echo esc_html( substr( esc_attr( stripslashes( $field->name ) ), 0, 50 ) ); ?>
						</option>
						<?php
					}
				}
				unset( $field );
			}
			?>
		</select>
	</p>

	<?php $id_attr = $this->get_field_id( 'reg_auto_login' ); ?>
	<p class="frm_reg_auto_login_row"<?php echo ( $show_auto_login ) ? '' : ' style="display:none"'; ?>>
		<label for="<?php echo esc_attr( $id_attr ); ?>">
			<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'login' ) ); ?>" value="1" id="<?php echo esc_attr( $id_attr ); ?>" <?php checked( $form_action->post_content['login'], 1 ); ?> />
			<?php esc_html_e( 'Automatically log in users who submit this form', 'frmreg' ); ?>
		</label>
	</p>
	<p class="frm_reg_auto_login_msg" <?php echo ( ! $show_auto_login && $form_action->post_content['login'] == '1' ) ? ' ' : 'style="display:none"'; ?>>
		<span><?php esc_html_e( 'Please note: the automatic login option will appear if you map the Password setting to a Password field in your form and this action runs for the main entry.', 'frmreg' ); ?></span>
	</p>
</div>

<!--User Meta-->
<?php include FrmRegAppHelper::path() . '/views/user_meta_settings.php'; ?>

<!--User Moderation-->
<h3><?php esc_html_e( 'User Moderation', 'frmreg' ); ?></h3>

<table class="form-table frm_reg_user_moderation_section" <?php echo ( $show_auto_login ) ? '' : ' style="display:none"'; ?>>
	<tr>
		<td width="250px">
			<?php $id_attr = $this->get_field_id( 'reg_moderate_email' ); ?>
			<label for="<?php echo esc_attr( $id_attr ); ?>" <?php FrmRegAppHelper::add_tooltip( 'mod_email' ); ?>>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'reg_moderate' ) ); ?>[]" value="email" id="<?php echo esc_attr( $id_attr ); ?>" <?php FrmRegAppHelper::array_checked( $form_action->post_content['reg_moderate'], 'email' ); ?> /> <?php esc_html_e( 'Email confirmation', 'frmreg' ); ?>
			</label>
		</td>
		<td style="padding-top:0;<?php echo $form_action->post_content['reg_moderate'] ? '' : 'display:none;'; ?>">
			<div class="frm_on_email_confirmation_type_setting frm_grid_container frm_on_submit_type_setting">
				<?php
				foreach ( $redirect_types as $type => $type_data ) :
					$input_id = $this->get_field_id( 'on_email_confirmation_' . $type );
					?>
					<div class="frm_on_email_confirmation_type frm_form_field frm6">
						<input
							type="radio"
							id="<?php echo esc_attr( $input_id ); ?>"
							name="<?php echo esc_attr( $this->get_field_name( 'on_email_confirmation' ) ); ?>"
							value="<?php echo esc_attr( $type ); ?>"
							<?php checked( $type, $on_email_confirmation ); ?>
						/>
						<label for="<?php echo esc_attr( $input_id ); ?>">
							<?php
							FrmAppHelper::icon_by_class( $type_data['icon'], array( 'echo' => true ) );
							echo esc_html( $type_data['label'] );
							?>
						</label>
					</div>
				<?php endforeach; ?>
			</div>
			<?php
			foreach ( $redirect_types as $type_name => $type ) {
				$css_class = 'frm_on_email_confirmation_dependent_setting';
				if ( $on_email_confirmation !== $type_name ) {
					$css_class .= ' frm_hidden';
				}
				?>
				<div class="<?php echo esc_attr( $css_class ); ?>" data-show-if-<?php echo esc_attr( $type_name ); ?>>
					<?php
					if ( is_callable( $type['sub_settings'] ) ) {
						call_user_func( $type['sub_settings'], $redirect_type_args );
					}
					?>
				</div>
				<?php
			}
			?>
		</td>
	</tr>
	<tr class="frm_hidden">
		<td colspan="2">
			<label <?php FrmRegAppHelper::add_tooltip( 'mod_admin' ); ?>>
				<input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'reg_moderate' ) ); ?>[]" value="admin" <?php FrmRegAppHelper::array_checked( $form_action->post_content['reg_moderate'], 'admin' ); ?> />
				<?php esc_html_e( 'Admin approval', 'frmreg' ); ?>
			</label>
		</td>
	</tr>
</table>

<span class="frm_reg_user_moderation_msg" <?php echo ( ! $show_auto_login ) ? '' : ' style="display:none"'; ?>>
	<?php esc_html_e( 'Please note: the Email Confirmation option will appear if you map the Password setting to a Password field in your form.', 'frmreg' ); ?>
</span>

<!--Permissions-->
<?php include FrmRegAppHelper::path() . '/views/permission_settings.php'; ?>

<!--Multisite Settings-->
<?php
if ( is_multisite() ) {
	include FrmRegAppHelper::path() . '/views/multisite_settings.php';
}
?>

<!--Email Buttons-->
<?php include FrmRegAppHelper::path() . '/views/email_buttons.php'; ?>

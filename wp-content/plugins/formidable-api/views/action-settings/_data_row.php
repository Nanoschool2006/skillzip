<div id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_' . $row_num ) ); ?>" class="frm_postmeta_row frm_grid_container">
	<div class="frm4 frm_form_field">
		<label class="screen-reader-text" for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_key' . $row_num ) ); ?>">
			<?php esc_html_e( 'Name' ); ?>
		</label>

		<input type="text" value="<?php echo esc_attr( $data['key'] ); ?>" name="<?php echo esc_attr( $action_control->get_field_name( 'data_fields' ) ); ?>[<?php echo absint( $row_num ); ?>][key]" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_key' . $row_num ) ); ?>" class="frm_not_email_message" />
	</div>
	<div class="frm7 frm_form_field">
		<label class="screen-reader-text" for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_value' . $row_num ) ); ?>">
			<?php esc_html_e( 'Value', 'formidable' ); ?>
		</label>
		<input type="text" name="<?php echo esc_attr( $action_control->get_field_name( 'data_fields' ) ); ?>[<?php echo absint( $row_num ); ?>][value]" value="<?php echo esc_attr( $data['value'] ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_value' . $row_num ) ); ?>" class="frm_not_email_message" />
	</div>
	<div class="frm1 frm_form_field frm-inline-select">
		<?php if ( $row_num ) { ?>
			<a href="javascript:void(0)" class="frm_remove_tag" data-removeid="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_' . $row_num ) ); ?>"><?php FrmAppHelper::icon_by_class( 'frm_icon_font frm_minus1_icon' ); ?></a>
		<?php } ?>
		<a href="javascript:void(0)" class="frm_add_tag frmapi_add_data_row"><?php FrmAppHelper::icon_by_class( 'frm_icon_font frm_plus1_icon' ); ?></a>
	</div>
</div>

<div class="frm_grid_container">
<p class="frm6 frm_form_field">
	<label for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_url' ) ); ?>" style="display:block;">
		<?php esc_html_e( 'Notification URL', 'frmapi' ); ?>
		<span class="frm_required">*</span>
	</label>
	<input type="text" name="<?php echo esc_attr( $action_control->get_field_name( 'url' ) ); ?>" value="<?php echo esc_attr( $form_action->post_content['url'] ); ?>" class="frm_not_email_message widefat" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_url' ) ); ?>" />
	<span class="howto"><?php esc_html_e( 'Notify this URL when the hook selected above is triggered.', 'frmapi' ); ?></span>
</p>

<p class="frm6 frm_form_field">
	<label for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_basic_auth' ) ); ?>">
		<?php esc_html_e( 'Basic Auth', 'frmapi' ); ?>
		<?php FrmAPISettingsController::show_svg_tooltip( esc_html__( 'A colon (:) separated username, password combo for standard HTTP authentication. This key will be provided by the service you are connecting to if it is required.', 'frmapi' ) ); ?>
	</label>
	<input type="text" name="<?php echo esc_attr( $action_control->get_field_name( 'api_key' ) ); ?>" value="<?php echo esc_attr( $api_key ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'Username:Password', 'frmapi' ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_basic_auth' ) ); ?>" />

	<a class="frmapi_test_connection button-secondary frm-button-secondary alignright" style="margin:var(--gap-sm) 0;">
		<?php esc_html_e( 'Test Connection', 'frmapi' ); ?>
	</a>
	<span class="frmapi_test_resp frm_required frm-inline-select frm-block frm_clear"></span>
</p>

<p class="frm6 frm_form_field">
	<label class="frm_left_label" for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_format' ) ); ?>">
		<?php esc_html_e( 'Data Format', 'frmapi' ); ?>
		<?php FrmAPISettingsController::show_svg_tooltip( esc_html__( 'JSON is a standard format for most REST APIs. The Form option will submit a form on another page with any name value pairs of your choosing. If you select Form, there must be a form displayed on the page you submit to.', 'frmapi' ) ); ?>
	</label>
	<select name="<?php echo esc_attr( $action_control->get_field_name( 'format' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_data_format' ) ); ?>" class="frmapi_data_format">
		<?php foreach ( array( 'json', 'form', 'raw' ) as $format ) { ?>
			<option value="<?php echo esc_attr( $format ); ?>" <?php selected( $format, $form_action->post_content['format'] ); ?>>
				<?php echo esc_html( $format ); ?>
			</option>
		<?php } ?>
	</select>
</p>

<p class="frm6 frm_form_field">
	<label for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_method' ) ); ?>">
		<?php esc_html_e( 'Method', 'frmapi' ); ?>
		<?php FrmAPISettingsController::show_svg_tooltip( esc_html__( 'The method determines how the data is handled on the receiving end. Generally, POST = create, GET = read, PUT/PATCH = update, DELETE = delete.', 'frmapi' ) ); ?>
	</label>
	<select name="<?php echo esc_attr( $action_control->get_field_name( 'method' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_method' ) ); ?>">
		<?php foreach ( array( 'POST', 'GET', 'PUT', 'PATCH', 'DELETE' ) as $method ) { ?>
			<option value="<?php echo esc_attr( $method ); ?>" <?php selected( $method, $form_action->post_content['method'] ); ?>>
				<?php echo esc_html( $method ); ?>
			</option>
		<?php } ?>
	</select>
</p>

<p class="frm6 frm_form_field">
	<label class="frm_left_label" for="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_charset' ) ); ?>">
		<?php esc_html_e( 'Character Set', 'frmapi' ); ?>
		<?php FrmAPISettingsController::show_svg_tooltip( esc_html__( 'Character Set is a defined list of characters recognized by the computer hardware and software.', 'frmapi' ) ); ?>
	</label>
	<select name="<?php echo esc_attr( $action_control->get_field_name( 'charset' ) ); ?>" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_api_charset' ) ); ?>" >
		<?php
		$option_labels = array(
			''             => __( 'Select charset', 'frmapi' ),
			'blog_charset' => __( 'Use blog charset', 'frmapi' ),
		);

		$default  = $form_action->ID === '' ? 'blog_charset' : '';
		$selected = ! empty( $form_action->post_content['charset'] ) ? $form_action->post_content['charset'] : $default;
		$charsets = array( '', 'blog_charset', 'UTF-8', 'ASCII', 'ANSI' );

		/**
		 * Updates the list of charset options.
		 *
		 * @since 1.12
		 *
		 * @param array $charsets list of charsets.
		 */
		$charsets = apply_filters( 'frm_api_charset_options', $charsets );
		?>
		<?php foreach ( $charsets as $charset ) { ?>
			<option value="<?php echo esc_attr( $charset ); ?>"
				<?php
				selected( $selected, $charset );
				?>
			>
				<?php echo esc_html( in_array( $charset, array_keys( $option_labels ), true ) ? $option_labels[ $charset ] : $charset ); ?>
			</option>
		<?php } ?>
	</select>
</p>

<p class="frm_data_raw <?php echo esc_attr( 'raw' == $form_action->post_content['format'] ? '' : 'frm_hidden' ); ?>" >
	<span class="frm-flex-box frm-justify-between" style="align-items: flex-end;">
		<label for="<?php echo esc_attr( $action_control->get_field_id( 'frm_raw_format' ) ); ?>">
			<?php esc_html_e( 'Raw Data', 'frmapi' ); ?>
		</label>
		<a class="frmapi_insert_default_json button-secondary alignright" style="margin-bottom:4px;">
			<?php esc_html_e( 'Insert Default', 'frmapi' ); ?>
		</a>
	</span>
	<textarea name="<?php echo esc_attr( $action_control->get_field_name( 'data_format' ) ); ?>" class="frm_not_email_message" rows="5" id="<?php echo esc_attr( $action_control->get_field_id( 'frm_raw_format' ) ); ?>"><?php echo esc_html( $form_action->post_content['data_format'] ); ?></textarea>
</p>

</div>

<h3><?php esc_html_e( 'Map Data', 'frmapi' ); ?></h3>
<div class="frm_add_remove frm_name_value frm_data_json <?php echo esc_attr( 'raw' == $form_action->post_content['format'] ? 'frm_hidden' : '' ); ?>">
	<p class="frm_grid_container frm_no_margin">
		<label class="frm4 frm_form_field">
			<?php esc_html_e( 'Key', 'formidable' ); ?>
		</label>
		<label class="frm6 frm_form_field">
			<?php esc_html_e( 'Value', 'formidable' ); ?>
		</label>
	</p>

	<div class="frmapi_data_rows">
			<?php
			foreach ( $data_fields as $row_num => $data ) {
				if ( ( isset( $data['key'] ) && ! empty( $data['key'] ) ) || $row_num == 0 ) {
					include( dirname( __FILE__ ) . '/_data_row.php' );
				}
				unset( $row_num, $data );
			}
			?>
	</div>
</div>
<br/>

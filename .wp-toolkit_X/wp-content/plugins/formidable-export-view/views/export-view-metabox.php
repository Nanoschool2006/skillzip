<div class="frm_grid_container">
	<p class="frm4 frm_form_field show_btn_container">
		<?php esc_html_e( 'Export Link', 'formidable-export-view' ); ?>
		<span class="frm_help frm_icon_font frm_tooltip_icon"
			  title="<?php esc_attr_e( 'If selected, a link for the user to download the View as a CSV file will be displayed under the View.', 'formidable-export-view' ); ?>"></span>
	</p>
	<p class="frm8 frm_form_field">
		<label for="frm_show_export_view">
			<input type="checkbox" id="frm_show_export_view" name="options[show_export_view]"
			   value="1" <?php checked( isset( $vars['show_export_view'] ) ? $vars['show_export_view'] : false, 1, true ); ?> />
			<?php esc_html_e( 'Show Export Link with View', 'formidable-export-view' ); ?>
		</label>
	</p>

	<p class="frm4 frm_form_field filename_container frm-export-view-setting ">
		<label for="frm_filename"><?php esc_attr_e( 'Filename', 'formidable-export-view' ); ?>
			<span class="frm_help frm_icon_font frm_tooltip_icon"
			title="<?php esc_attr_e( 'Filename of View export. Leave blank to use View title.', 'formidable-export-view' ); ?>"></span>
		</label>
	</p>
	<p class="frm8 frm_form_field">
		<input type="text" id="frm_filename" name="options[filename]" class="frm_full"
			   value="<?php echo esc_attr( isset( $vars['filename'] ) ? $vars['filename'] : '' ); ?>"
			   placeholder="<?php esc_attr_e( 'Leave blank to use View title', 'formidable-export-view' ); ?>"/>
	</p>

	<p class="frm4 frm_form_field view_export_link_container frm-export-view-setting ">
		<label for="frm_export_link_text">
			<?php esc_attr_e( 'Export Link Text', 'formidable-export-view' ); ?>
			<span class="frm_help frm_icon_font frm_tooltip_icon"
				title="<?php esc_attr_e( 'Text of link to download CSV on front-end.', 'formidable-export-view' ); ?>">
			</span>
		</label>
	</p>
	<p class="frm8 frm_form_field">
		<input type="text" id="frm_export_link_text" name="options[export_link_text]" class="frm_full"
			   value="<?php echo esc_attr( isset( $vars['export_link_text'] ) ? $vars['export_link_text'] : __( 'Export to CSV', 'formidable-export-view' ) ); ?>"/>
	</p>

	<p class="frm4 frm_form_field param_container frm-export-view-setting ">
		<?php esc_html_e( 'Include params with your CSV export?', 'formidable-export-view' ); ?>
		<span class="frm_help frm_icon_font frm_tooltip_icon"
			title="<?php esc_attr_e( 'If selected, params will be available to your View export for filtering and display.  These params can be seen and changed easily using browser tools, so including params is not recommended for Views with sensitive data.', 'formidable-export-view' ); ?>"></span>
	</p>
	<fieldset class="frm8 frm_form_field">
		<p>
			<label for="frm_export_with_params_yes">
				<input type="radio" id="frm_export_with_params_yes" name="options[export_with_params]" value="1" <?php checked( isset( $vars['export_with_params'] ) ? $vars['export_with_params'] : false, 1, true ); ?> />
				<?php esc_html_e( 'Yes (more flexible, not recommended for Views with sensitive data)', 'formidable-export-view' ); ?>
			</label>
		</p>
		<p>
			<label for="frm_export_with_params_no">
				<input type="radio" id="frm_export_with_params_no" name="options[export_with_params]" value="0" <?php checked( isset( $vars['export_with_params'] ) ? $vars['export_with_params'] : 0, 0, true ); ?> />
				<?php esc_html_e( 'No (more secure)', 'formidable-export-view' ); ?>
			</label>
		</p>
	</fieldset>

	<input type="hidden" id="frm_view_export_possible" name="options[view_export_possible]"
		value="<?php echo esc_attr( isset( $vars['view_export_possible'] ) ? $vars['view_export_possible'] : 0 ); ?>"/>
</div>

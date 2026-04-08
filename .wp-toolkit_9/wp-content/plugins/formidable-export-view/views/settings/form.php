<table class="form-table">
	<tr class="form-field" valign="top">
		<td width="200px">
			<label><?php esc_html_e( 'Schedule CSV Export', 'formidable-export-view' ); ?>
				<span class="frm_help frm_icon_font frm_tooltip_icon"
					  title="<?php esc_attr_e( 'If you don\'t see a table View on this list, try going to the View and updating it.', 'formidable-export-view' ); ?>"></span>

			</label>
		</td>
		<td>
			<fieldset>
				<?php
				if ( $has_views ) {
					foreach ( $views as $view_id => $view_label ) {
						?>
						<div class="frm_checkbox">
							<label for="view-export-<?php echo esc_attr( $view_id ); ?>">
								<input type="checkbox" name="frm_export_view_export_view_id[]"
										id="view-export-<?php echo esc_attr( $view_id ); ?>"
										value="<?php echo esc_attr( $view_id ); ?>"
									<?php echo in_array( $view_id, $selected_views ) ? 'checked="checked"' : ''; ?>
								/>
								<?php echo esc_html( $view_label ); ?>
							</label>
						</div>
						<?php
					}
				} else {
					?>
					<p><?php esc_html_e( 'No Views available for export.', 'formidable-export-view' ); ?></p>
				<?php } ?>
		</td>
	</tr>
	<tr class="form-field" valign="top">
		<td>
			<label for="frm_export_view_frequency_period"><?php esc_html_e( 'Frequency', 'formidable-export-view' ); ?></label>
		</td>
		<td>
			<input id="frm_export_view_frequency_period" value="<?php echo esc_attr( $export_settings->settings->frequency ); ?>"
				   name="frm_export_view_frequency" style="width:45px" type="text">
			<select name="frm_export_view_frequency_period" class="auto_width">
				<option <?php selected( $export_settings->settings->frequency_period, 'days', true ); ?> value="days">
					<?php esc_html_e( 'Days', 'formidable-export-view' ); ?>
				</option>
				<option <?php selected( $export_settings->settings->frequency_period, 'months', true ); ?> value="months">
					<?php esc_html_e( 'Months', 'formidable-export-view' ); ?>
				</option>
			</select>
		</td>
	</tr>
	<tr class="form-field frm_form_fields" valign="top">
		<td>
			<label for="frm_export_view_csv_format">
				<?php esc_html_e( 'Format', 'formidable-export-view' ); ?>
				<span class="frm_help frm_icon_font frm_tooltip_icon"
					  title="<?php esc_attr_e( 'If your CSV special characters are not working correctly, try a different formatting option.', 'formidable-export-view' ); ?>"></span>
			</label>
		</td>
		<td>
			<select id="frm_export_view_csv_format" name="frm_export_view_csv_format" class="auto_width">
				<?php foreach ( $formats as $format ) { ?>
					<option <?php selected( $export_settings->settings->csv_format, $format, true ); ?>
							value="<?php echo esc_attr( $format ); ?>"><?php echo esc_html( $format ); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr class="form-field frm_form_fields" valign="top">
		<td>
			<label for="frm_export_view_csv_col_sep">
				<?php esc_html_e( 'Column separation', 'formidable-export-view' ); ?>
			</label>
		</td>
		<td>
			<input style="width:45px" id="frm_export_view_csv_col_sep" name="frm_export_view_csv_col_sep"
				   value="<?php echo esc_attr( $export_settings->settings->csv_col_sep ); ?>"
				   type="text" maxlength="1"/>
		</td>
	</tr>
	<tr class="form-field" valign="top">
		<td>
			<label for="frm_export_view_upload_dir"><?php esc_html_e( 'Destination Directory Path', 'formidable-export-view' ); ?></label>
		</td>
		<td>
			<input
				value="<?php echo esc_attr( $export_settings->settings->upload_dir ); ?>"
				id="frm_export_view_upload_dir"
				name="frm_export_view_upload_dir"
				placeholder="<?php echo esc_attr_e( 'Leave blank to use wp-content/uploads/formidable/exports', 'formidable-export-view' ); ?>"
				type="text"/>
			<?php if ( defined( 'ABSPATH' ) ) { ?>
				<p><?php esc_html_e( 'Root', 'formidable-export-view' ); ?>: <?php echo esc_html( ABSPATH ); ?> </p>
			<?php } ?>
		</td>
	</tr>
</table>

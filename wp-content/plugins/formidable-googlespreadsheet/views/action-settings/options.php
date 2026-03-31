
<table class="form-table frm-no-margin">
	<tbody>
		<tr class="googlespreadsheet_object">
			<td>
				<div class="frm_grid_container">
					<p class="frm6">
						<label>
							<?php esc_html_e( 'Select File', 'formidable-google-sheets' ); ?>
							<span class="frm_required">*</span>
						</label>
						<?php
						FrmGoogleSpreadsheetAppHelper::show_files_dropdown(
							array(
								'files'    => is_wp_error( $files ) ? array() : $files,
								'selected' => $spreadsheet_id,
								'name'     => $action_control->get_field_name( 'spreadsheet_id' ),
								'id'       => $action_control->get_field_id( 'spreadsheet_id' ),
							)
						);
						?>
					</p>
					<p class="frm6<?php echo ( empty( $sheets ) || count( $sheets ) <= 1 ) ? ' frm_hidden' : ''; ?>">
						<label>
							<?php esc_html_e( 'Select Sheet', 'formidable-google-sheets' ); ?>
							<span class="frm_required">*</span>
						</label>
						<select name="<?php echo esc_attr( $action_control->get_field_name( 'sheet_id' ) ); ?>">
							<?php if ( ! empty( $sheets ) ) : ?>
								<?php foreach ( $sheets as $sheet ) : ?>
									<option value="<?php echo esc_attr( $sheet->id ); ?>" <?php selected( $sheet_id, $sheet->id ); ?>>
										<?php echo esc_html( $sheet->label ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</p>
					<p>
						<a href="#" id="clrcache-googlespreadsheet" class="button frm-button-secondary" >
							<?php esc_html_e( 'Clear Cache', 'formidable-google-sheets' ); ?>
						</a>
						<span style="float:none" class="clrcache-googlespreadsheet-spinner spinner"></span>
					</p>
					<?php if ( is_wp_error( $files ) || empty( $files ) ) : ?>
						<div class="frm_error_style">
							<strong>
								<?php esc_html_e( 'Oops!', 'formidable-google-sheets' ); ?>
							</strong>
							<?php if ( is_wp_error( $files ) ) : ?>
								<?php
									printf(
										/* translators: %1$s: Google console link HTML %2$s: end link HTML %3$s: Doc link 2 HTML %4$s: end link HTML */
										esc_html__( 'Please ensure you have enabled the Google Drive API and Google Sheets API in the %1$sGoogle cloud console%2$s. %3$sLearn more%4$s.', 'formidable-google-sheets' ),
										'<a href="https://console.cloud.google.com/apis" target="_blank" rel="noopener">',
										'</a>',
										'<br/><a href="' . esc_url( 'https://formidableforms.com/knowledgebase/google-spreadsheet-forms/' ) . '" target="_blank" rel="noopener">',
										'</a>'
									);
								?>
							<?php else : ?>
								<?php esc_html_e( 'There are no sheets in your Google Drive. Please create at least one.', 'formidable-google-sheets' ); ?>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>

				<div class="clear"></div>

				<div>
					<h3>
						<?php esc_html_e( 'Map Sheet Headers', 'formidable-google-sheets' ); ?>
					</h3>
					<div class="frm_googlespreadsheet_fields frm_grid_container <?php echo esc_attr( $action_control->get_field_id( 'frm_googlespreadsheet_fields' ) ); ?>">
						<?php
						if ( ! empty( $headers ) ) {
							include dirname( __FILE__ ) . '/_match_fields.php';
						} elseif ( ! empty( $files ) && ! empty( $spreadsheet_id ) ) {
							include dirname( __FILE__ ) . '/_match_fields_error.php';
						}
						?>
					</div>
				</div>

				<div>
					<h3>
						<?php esc_html_e( 'Entry Operations', 'formidable-google-sheets' ); ?>
					</h3>
					<a href="#"  data-actionid="<?php echo esc_attr( $form_action->ID ); ?>" class="sync-google-spreadsheet button frm-button-secondary frm_help" data-placement="right" title="<?php esc_attr_e( 'This will append all the existing form entries to the sheet selected above. Remember to map the sheet headers to form fields before sending.', 'formidable-google-sheets' ); ?>">
						<?php esc_html_e( 'Send Existing Entries', 'formidable-google-sheets' ); ?>
					</a>
					<span id="spreadsheet_sync_spinner-<?php echo esc_attr( $form_action->ID ); ?>" style="float:none" class="spinner"></span>
					<span class="spreadsheet_sync_result"></span>
					<p class="description">
						<?php esc_html_e( 'Please select the File and Sheet to which you want to send data.', 'formidable-google-sheets' ); ?>
					</p>
				</div>
			</td>
		</tr>
	</tbody>
</table>

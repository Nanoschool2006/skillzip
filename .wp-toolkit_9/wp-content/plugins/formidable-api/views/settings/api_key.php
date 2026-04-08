<table class="form-table">
	<tr class="form-field">
		<td width="200px" style="vertical-align: middle;">
			<label><?php esc_html_e( 'API Key', 'frmapi' ); ?></label>
		</td>
		<td>
			<div class="frm-flex-box frm-items-center frm-gap-0 ">
				<input id="frm-refresh-api-key-input" type="text" class="frm_select_box frm-w-auto frm-px-0" value="<?php echo esc_attr( $api_key ); ?>" style="background:transparent;border:none;text-align:left;box-shadow:none;min-width:230px;" />
				<a class="frm-refresh-api-key" href="#" title="<?php esc_attr_e( 'Refresh API KEY', 'frmapi' ); ?>" style="line-height: 0; color: #a2acbb;" data-refresh-msg="<?php echo esc_attr( $refresh_message ); ?>">
					<svg width="24" height="24" style="fill: transparent">
						<use href="<?php echo esc_url( FrmAPIAppHelper::plugin_url() . '/images/icons.svg' ); ?>#frm_refresh"></use>
					</svg>
				</a>
			</div>
		</td>
	</tr>
</table>

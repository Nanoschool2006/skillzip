<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field">
	<label class="frm-h-stack-xs" for="start_year_<?php echo absint( $field['id'] ); ?>">
		<span><?php esc_html_e( 'Year Range', 'formidable-pro' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			__( 'Use four digit years or +/- years to make it dynamic. For example, use -5 for the start year and +5 for the end year.', 'formidable-pro' ),
			array(
				'data-placement' => 'right',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>

	<span class="frm_grid_container" id="frm-date-year-range-container-<?php echo absint( $field['id'] ); ?>">
		<span class="frm6 frm_form_field frm-range-min">
			<input class="<?php echo esc_attr( FrmField::get_option( $field, 'is_range_start_field' ) ? 'frm_sync_range_fields' : '' ); ?>" type="text" name="field_options[start_year_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['start_year'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Start', 'formidable-pro' ); ?>" size="4"/>
		</span>
		<span class="frm6 frm_last frm_form_field">
			<input class="<?php echo esc_attr( FrmField::get_option( $field, 'is_range_start_field' ) ? 'frm_sync_range_fields' : '' ); ?>" type="text" name="field_options[end_year_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['end_year'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'End', 'formidable-pro' ); ?>" size="4"/>
		</span>
	</span>
</p>

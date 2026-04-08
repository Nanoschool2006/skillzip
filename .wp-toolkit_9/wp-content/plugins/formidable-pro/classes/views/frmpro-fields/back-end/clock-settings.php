<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$step_unit = FrmProTimeFieldsController::get_step_unit( $field );
?>
<p id="frm_linked_date_field_<?php echo absint( $field['id'] ); ?>" class="frm_form_field<?php echo esc_attr( ! empty( $field['unique'] ) ? '' : ' frm_hidden' ); ?>">
	<label class="frm-h-stack-xs">
		<span><?php esc_html_e( 'Linked Date Field', 'formidable-pro' ); ?></span>
		<?php FrmAppHelper::tooltip_icon( __( 'Link a date field to this time field. Times already chosen on the same day won\'t be available.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
	<select class="frm-time-field-linked-date-field" name="field_options[linked_date_field_<?php echo absint( $field['id'] ); ?>]">
		<?php foreach ( FrmProTimeOptions::get_linked_date_field_options() as $option ) : ?>
			<option value="<?php echo esc_attr( $option['value'] ); ?>" <?php selected( $field['linked_date_field'], $option['value'] ); ?>>
				ID <?php echo esc_html( $option['value'] ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</p>
<p class="frm12 frm_first frm_form_field frm-number-range">
	<label class="frm-h-stack-xs">
		<span><?php esc_html_e( 'Time Range', 'formidable-pro' ); ?></span>
		<?php FrmAppHelper::tooltip_icon( __( 'This uses 24-hour clockbase.', 'formidable-pro' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>

	<span class="frm_grid_container">
		<span class="frm6 frm_form_field frm-range-min">
			<input type="text" name="field_options[start_time_<?php echo absint( $field['id'] ); ?>]" id="start_time_<?php echo absint( $field['id'] ); ?>" value="<?php echo esc_attr( $field['start_time'] ); ?>" size="5"/>
		</span>
		<span class="frm6 frm_last frm_form_field">
			<input type="text" name="field_options[end_time_<?php echo absint( $field['id'] ); ?>]" id="end_time_<?php echo absint( $field['id'] ); ?>" value="<?php echo esc_attr( $field['end_time'] ); ?>" size="5"/>
		</span>
	</span>
</p>

<p class="frm12 frm_first frm_form_field">
	<label for="frm_step_<?php echo esc_attr( $field['field_key'] ); ?>">
		<?php esc_html_e( 'Step', 'formidable' ); ?>
	</label>

	<span class="frm_grid_container">
		<span class="frm6 frm_form_field">
			<input type="text" name="field_options[step_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['step'] ); ?>" id="frm_step_<?php echo esc_attr( $field['field_key'] ); ?>" min="1" max="1440" />
		</span>

		<span class="frm6 frm_last frm_form_field">
			<select name="field_options[step_unit_<?php echo absint( $field['id'] ); ?>]" id="step_unit_<?php echo absint( $field['id'] ); ?>">
				<option
					value="<?php echo esc_attr( FrmProTimeFieldsController::STEP_UNIT_MINUTE ); ?>"
					<?php selected( $field['step_unit'], FrmProTimeFieldsController::STEP_UNIT_MINUTE ); ?>
				><?php esc_html_e( 'Minute(s)', 'formidable-pro' ); ?></option>
				<option
					value="<?php echo esc_attr( FrmProTimeFieldsController::STEP_UNIT_SECOND ); ?>"
					<?php selected( $field['step_unit'], FrmProTimeFieldsController::STEP_UNIT_SECOND ); ?>
				><?php esc_html_e( 'Second(s)', 'formidable-pro' ); ?></option>
				<option
					value="<?php echo esc_attr( FrmProTimeFieldsController::STEP_UNIT_MILLISECOND ); ?>"
					<?php selected( $field['step_unit'], FrmProTimeFieldsController::STEP_UNIT_MILLISECOND ); ?>
				><?php esc_html_e( 'Millisecond(s)', 'formidable-pro' ); ?></option>
			</select>
		</span>
	</span>
</p>

<p class="frm12 frm_form_field">
	<label><?php esc_html_e( 'Time Format', 'formidable-pro' ); ?></label>

	<select name="field_options[clock_<?php echo absint( $field['id'] ); ?>]">
		<option value="12" <?php selected( $field['clock'], 12 ); ?>>
			<?php esc_html_e( '12 hour clock', 'formidable-pro' ); ?>
		</option>
		<option value="24" <?php selected( $field['clock'], 24 ); ?>>
			<?php esc_html_e( '24 hour clock', 'formidable-pro' ); ?>
		</option>
	</select>
</p>

<?php $hidden = FrmProTimeFieldsController::STEP_UNIT_MINUTE === $step_unit ? '' : ' frm_hidden'; ?>
<p class="frm12 frm_form_field<?php echo esc_attr( $hidden ); ?>">
	<label for="single_time_<?php echo esc_attr( $field['id'] ); ?>">
		<input type="checkbox" name="field_options[single_time_<?php echo esc_attr( $field['id'] ); ?>]" id="single_time_<?php echo esc_attr( $field['id'] ); ?>" value="1" <?php echo FrmField::is_option_true( $field, 'single_time' ) ? 'checked="checked"' : ''; ?> />
		<?php esc_html_e( 'Show a single time dropdown', 'formidable-pro' ); ?>
	</label>
</p>
<?php unset( $step_unit, $hidden ); ?>

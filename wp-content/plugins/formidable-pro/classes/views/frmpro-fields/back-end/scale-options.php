<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm8 frm_first frm_form_field frm-number-range">
	<label class="frm-font-semibold frm-text-grey-600 frm-mb-xs"><?php esc_html_e( 'Number Range', 'formidable' ); ?></label>

	<span class="frm_grid_container">
		<span class="frm6 frm_form_field frm-range-min">
			<label for="scale_minnum_<?php echo absint( $field['id'] ); ?>">
				<?php esc_html_e( 'Min Value', 'formidable' ); ?>
			</label>
			<input
				type="number"
				name="field_options[minnum_<?php echo absint( $field['id'] ); ?>]"
				value="<?php echo esc_attr( $field['minnum'] ); ?>"
				class="scale_minnum frm_scale_opt"
				id="scale_minnum_<?php echo absint( $field['id'] ); ?>"
			/>
		</span>
		<span class="frm6 frm_last frm_form_field">
			<label for="scale_maxnum_<?php echo absint( $field['id'] ); ?>">
				<?php esc_html_e( 'Max Value', 'formidable' ); ?>
			</label>
			<input
				type="number"
				name="field_options[maxnum_<?php echo absint( $field['id'] ); ?>]"
				value="<?php echo esc_attr( $field['maxnum'] ); ?>"
				class="scale_maxnum frm_scale_opt"
				id="scale_maxnum_<?php echo absint( $field['id'] ); ?>"
			/>
		</span>
	</span>
</p>
<p class="frm4 frm_last frm_form_field frm-step frm-self-end">
	<label for="frm_step_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'Step', 'formidable' ); ?>
	</label>
	<input type="number" name="field_options[step_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['step'] ); ?>" id="frm_step_<?php echo esc_attr( $field['id'] ); ?>" class="frm_scale_opt" />
</p>

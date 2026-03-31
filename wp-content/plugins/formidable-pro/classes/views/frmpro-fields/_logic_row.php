<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<div id="frm_logic_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>" class="frm_logic_row frm-flex-col frm-items-stretch frm-gap-xs">
	<span class="frm-logic-rule"><span class="frm-logic-rule-text"></span></span>

	<select name="field_options[hide_field_<?php echo esc_attr( $field['id'] ); ?>][]" class="frm_logic_field_opts" data-type="<?php echo esc_attr( $field['type'] ); ?>">
		<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
		<?php
		$sel         = false;
		$prefiltered = FrmProConditionalLogicOptionData::data_is_available( $field['form_id'] );

		foreach ( $form_fields as $ff ) {
			if ( ! FrmProConditionalLogicController::is_field_present_in_logic_options( $field, $ff, $prefiltered ) ) {
				continue;
			}

			if ( $ff->id == $hide_field ) {
				$sel = true;
			}

			FrmProHtmlHelper::echo_dropdown_option(
				$ff->name,
				(string) $ff->id === (string) $hide_field,
				array(
					'value' => $ff->id,
				)
			);
		}
		?>
	</select>

	<?php
	// Remove conditional logic if the field doesn't exist
	if ( $hide_field && ! $sel ) {
	?>
	<script type="text/javascript">jQuery(document).ready(function(){frmAdminBuild.triggerRemoveLogic(<?php echo (int) $field['id']; ?>, '<?php echo esc_attr( $meta_name ); ?>');});</script>
	<?php } ?>

	<select name="field_options[hide_field_cond_<?php echo esc_attr( $field['id'] ); ?>][]">
		<?php
		$field['hide_field_cond'][ $meta_name ] = isset( $field['hide_field_cond'][ $meta_name ] ) ? htmlspecialchars_decode( $field['hide_field_cond'][ $meta_name ] ) : '';
		$condition_options                      = array(
			'=='       => __( 'equals', 'formidable-pro' ),
			'!='       => __( 'does not equal', 'formidable-pro' ) . ' &nbsp;',
			'>'        => __( 'is greater than', 'formidable-pro' ),
			'>='       => __( 'is greater than or equal to', 'formidable-pro' ),
			'<'        => __( 'is less than', 'formidable-pro' ),
			'<='       => __( 'is less than or equal to', 'formidable-pro' ),
			'LIKE'     => __( 'contains', 'formidable-pro' ),
			'not LIKE' => __( 'does not contain', 'formidable-pro' ) . ' &nbsp;',
			'LIKE%'    => __( 'starts with', 'formidable-pro' ) . ' &nbsp;',
			'%LIKE'    => __( 'ends with', 'formidable-pro' ) . ' &nbsp;',
		);

		foreach ( $condition_options as $option_value => $option_label ) {
			FrmProHtmlHelper::echo_dropdown_option(
				$option_label,
				$field['hide_field_cond'][ $meta_name ] === $option_value,
				array( 'value' => $option_value )
			);
		}
		?>
	</select>

	<span id="frm_show_selected_values_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>">
		<?php
		$selector_field_id = $hide_field && is_numeric( $hide_field ) ? (int) $hide_field : 0;
		$selector_args     = array(
			'html_name' => 'field_options[hide_opt_' . $field['id'] . '][]',
			'value'     => $field['hide_opt'][ $meta_name ] ?? '',
			'source'    => $field['type'],
			'truncate'  => 40, // The default is 25. Allow more since there is available space.
		);

		FrmProFieldsHelper::show_field_value_selector( $field['hide_field_cond'][ $meta_name ], $selector_field_id, $selector_args );
		?>
	</span>

	<a href="javascript:void(0)" class="frm_remove_tag frm-h-stack frm-leading-none frm-mt-2xs" data-removeid="frm_logic_<?php echo esc_attr( $field['id'] . '_' . $meta_name ); ?>" data-showlast="#logic_<?php echo esc_attr( $field['id'] ); ?>" data-hidelast="#frm_logic_rows_<?php echo absint( $field['id'] ); ?>">
		<?php FrmAppHelper::icon_by_class( 'frmfont frm_minus1_icon frm_svg14' ); ?>
		<span class="frm-ml-2xs"><?php esc_html_e( 'Remove', 'formidable' ); ?></span>
	</a>
</div>

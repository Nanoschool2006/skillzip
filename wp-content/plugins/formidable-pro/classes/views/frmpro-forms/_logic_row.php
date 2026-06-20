<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

// Backwards compatibility "@since 6.31".
if ( ! FrmProAppHelper::lite_supports_form_actions_refresh() ) {
	include __DIR__ . '/backwards-compatibility/_logic_row.php';
	return;
}
?>
<div id="<?php echo esc_attr( $id ); ?>" class="frm_logic_row frm_logic_row_<?php echo esc_attr( $key ); ?> frm_grid_container">
	<span class="frm-logic-rule"><span class="frm-logic-rule-text"></span></span>

	<p class="frm4 frm_form_field">
		<select
			name="<?php echo esc_attr( $names['hide_field'] ); ?>"
			<?php if ( ! empty( $onchange ) ) { ?>
				onchange="<?php echo $onchange; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			<?php } ?>
		>
			<option value=""><?php esc_html_e( '&mdash; Select &mdash;' ); ?></option>
			<?php
			foreach ( $form_fields as $ff ) {
				if ( is_array( $ff ) ) {
					$ff = (object) $ff;
				}

				if ( $ff->type === 'range' && FrmField::get_option( $ff, 'is_range_slider' ) ) {
					continue;
				}

				if ( in_array( $ff->type, $exclude_fields, true ) || FrmProField::is_list_field( $ff ) ) {
					continue;
				}

				$selected = isset( $condition['hide_field'] ) && (int) $ff->id === (int) $condition['hide_field'];
				?>
				<option value="<?php echo esc_attr( $ff->id ); ?>"<?php selected( $selected ); ?>><?php echo esc_html( $ff->name ); ?></option>
				<?php
				unset( $ff );
			}
			?>
		</select>
	</p>

	<p class="frm4 frm_form_field">
		<select name="<?php echo esc_attr( $names['hide_field_cond'] ); ?>">
			<?php
			$condition_options = array(
				'=='       => __( 'equals', 'formidable-pro' ),
				'!='       => __( 'does not equal', 'formidable-pro' ) . ' &nbsp;',
				'>'        => __( 'is greater than', 'formidable-pro' ),
				'>='       => __( 'is greater than or equal to', 'formidable-pro' ),
				'<'        => __( 'is less than', 'formidable-pro' ),
				'<='       => __( 'is less than or equal to', 'formidable-pro' ),
				'LIKE'     => __( 'contains', 'formidable-pro' ),
				'not LIKE' => __( 'does not contain', 'formidable-pro' ),
				'LIKE%'    => __( 'starts with', 'formidable-pro' ),
				'%LIKE'    => __( 'ends with', 'formidable-pro' ),
			);

			foreach ( $condition_options as $option_value => $option_label ) {
				FrmProHtmlHelper::echo_dropdown_option(
					$option_label,
					$condition['hide_field_cond'] === $option_value,
					array( 'value' => $option_value )
				);
			}
			?>
		</select>
	</p>
	<p class="frm4 frm_form_field">
		<span id="frm_show_selected_values_<?php echo esc_attr( $key . '_' . $meta_name ); ?>"><?php
			$selector_field_id = $condition['hide_field'] && is_numeric( $condition['hide_field'] ) ? (int) $condition['hide_field'] : 0;
			$selector_args     = array(
				'html_name' => $names['hide_opt'],
				'value'     => $condition['hide_opt'] ?? '',
				'source'    => 'form_actions',
			);

			FrmProFieldsHelper::show_field_value_selector( $condition['hide_field_cond'], $selector_field_id, $selector_args );
		?></span>
	</p>

	<a href="javascript:void(0)" class="frm_remove_tag frm-h-stack frm-leading-none frm-mt-xs" data-removeid="<?php echo esc_attr( $id ); ?>" <?php echo ! empty( $showlast ) ? 'data-showlast="' . esc_attr( $showlast ) . '"' : ''; ?> <?php echo ! empty( $hidelast ) ? 'data-hidelast="' . esc_attr( $hidelast ) . '"' : ''; ?>>
		<?php FrmAppHelper::icon_by_class( 'frmfont frm_minus1_icon frm_svg14' ); ?>
		<span class="frm-ml-2xs"><?php esc_html_e( 'Remove', 'formidable' ); ?></span>
	</a>
</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<a
	href="javascript:void(0)"
	id="logic_<?php echo absint( $field['id'] ); ?>"
	class="frm_add_logic_row frm_add_logic_link frm-collapsed frm-flex-justify <?php echo ! empty( $field['hide_field'] ) && ( count( $field['hide_field'] ) > 1 || reset( $field['hide_field'] ) != '' ) ? ' frm_hidden' : ''; ?>"
	role="button" aria-expanded="false" tabindex="0" aria-controls="collapsible-section" aria-label="<?php esc_attr_e( 'Collapsible Conditional Logic Settings', 'formidable-pro' ); ?>"
>
	<?php esc_html_e( 'Conditional Logic', 'formidable' ); ?>
	<?php FrmAppHelper::icon_by_class( 'frmfont frm_arrowdown8_icon', array( 'aria-hidden' => 'true' ) ); ?>
</a>

<div class="frm_logic_rows frm_add_remove frm-toggle-group<?php echo ! empty( $field['hide_field'] ) && ( count( $field['hide_field'] ) > 1 || reset( $field['hide_field'] ) != '' ) ? '' : ' frm_hidden'; ?>" id="frm_logic_rows_<?php echo absint( $field['id'] ); ?>">
	<h3 aria-expanded="true" tabindex="0" role="button" aria-label="<?php esc_attr_e( 'Collapsible Conditional Logic Settings', 'formidable-pro' ); ?>" aria-controls="collapsible-section">
		<?php esc_html_e( 'Conditional Logic', 'formidable' ); ?>
		<?php FrmAppHelper::icon_by_class( 'frmfont frm_arrowdown8_icon', array( 'aria-hidden' => 'true' ) ); ?>
	</h3>

	<div class="frm-collapse-me" role="group">
		<?php
		if ( class_exists( 'FrmTextToggleStyleComponent' ) ) { // Backwards compatibility "@since 6.24".
			?>
			<input type="hidden" name="field_options[enable_conditional_logic_<?php echo esc_attr( $field['id'] ); ?>]" value="1" />
			<?php
			FrmHtmlHelper::toggle(
				'frm-enable-conditional-logic[' . $field['id'] . ']',
				'frm-enable-conditional-logic[' . $field['id'] . ']',
				array(
					'echo'        => true,
					'show_labels' => true,
					'on_label'    => __( 'Enable Conditional Logic', 'formidable-pro' ),
					'value'       => '1',
					'checked'     => true,
					'div_class'   => 'frm-leading-none frm-my-sm',
					'input_html'  => array(
						'data-group-name' => 'conditional-logic',
						'data-enable'     => '#frm_logic_row_{id},#frm_logic_rows_{id} .frm_add_logic_row',
					),
				)
			);
		}
		?>
		<div id="frm_logic_row_<?php echo absint( $field['id'] ); ?>" class="frm-mb-sm">
			<div class="frm-flex frm-flex-wrap frm-gap-xs frm-items-center frm-mt-md frm-mb-sm">
				<select class="frm-grow frm-w-fit frm-m-0" name="field_options[show_hide_<?php echo absint( $field['id'] ); ?>]">
					<?php
					foreach ( FrmProConditionalLogicController::get_show_hide_options( $field ) as $value => $label ) {
						FrmProHtmlHelper::echo_dropdown_option( $label, $value === $field['show_hide'], array( 'value' => $value ) );
					}
					?>
				</select>

				<span class="frm-white-space-nowrap frm-text-grey-700 frm-pr-lg">
					<?php
					if ( in_array( $field['type'], array( 'submit', 'break' ), true ) ) {
						esc_html_e( 'if the following match:', 'formidable-pro' );
					} else {
						esc_html_e( 'this field if the following match:', 'formidable-pro' );
					}
					?>
				</span>
			</div>

			<?php
			FrmProHtmlHelper::echo_radio_group(
				'field_options[any_all_' . absint( $field['id'] ) . ']',
				array(
					'any' => esc_html__( 'Any', 'formidable-pro' ),
					'all' => esc_html__( 'All', 'formidable-pro' ),
				),
				$field['any_all']
			);

			if ( ! empty( $field['hide_field'] ) ) {
				foreach ( (array) $field['hide_field'] as $meta_name => $hide_field ) {
					include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/_logic_row.php';
				}
			}
			?>
		</div>

		<a href="javascript:void(0)" class="frm-flex-center frm-gap-2xs frm_add_logic_row button frm-button-secondary">
			<?php FrmAppHelper::icon_by_class( 'frmfont frm_plus1_icon frm_svg12' ); ?>
			<span><?php esc_html_e( 'Add Condition', 'formidable-pro' ); ?></span>
		</a>
	</div>
</div>

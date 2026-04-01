<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

if ( ! class_exists( 'FrmTextToggleStyleComponent' ) ) {
	include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/backwards-compatibility/calculations.php';
	return;
}

$calc              = $field['field_options']['calc'] ?? $field['calc'];
$calc_type         = $field['calc_type'];
$class_attr        = 'frm-calc-for-' . $field['id'] . ' default-value-section-' . $field['id'] . ( isset( $default_value_types['calc']['current'] ) ? '' : ' frm_hidden' );
$math_button_class = 'frm-field-formula-button frm-math-button';

if ( 'text' === $calc_type ) {
	$math_button_class .= ' frm_disabled';
}
?>
<div class="<?php echo esc_attr( $class_attr ); ?>">
	<div class="frm_form_field">
		<?php
		new FrmTextToggleStyleComponent(
			'field_options[calc_type_' . esc_attr( $field['id'] ) . ']',
			$calc_type,
			array(
				'id'            => 'calc_type_' . $field['id'],
				'default_value' => '',
				'options'       => array(
					array(
						'label' => __( 'Text', 'formidable' ),
						'value' => 'text',
					),
					array(
						'label' => __( 'Math', 'formidable-pro' ),
						'value' => '',
					),
				),
			)
		);
		?>
	</div>

	<div class="frm_form_field frm-my-sm">
		<label class="frm-has-required" for="frm_calc_<?php echo absint( $field['id'] ); ?>">
			<span><?php esc_html_e( 'Field Formula', 'formidable-pro' ); ?></span>
			<span class="frm-required">*</span>
		</label>

		<div class="frm-field-formula" data-field-id="<?php echo absint( $field['id'] ); ?>">
			<textarea
				id="frm_calc_<?php echo absint( $field['id'] ); ?>"
				name="field_options[calc_<?php echo absint( $field['id'] ); ?>]"
				class="frm-field-formula-editor frm-calc-field"
				placeholder="<?php esc_attr_e( 'Click "Insert field or press Cmd/Ctrl+K" and start typing the name or ID of a field include them in your calculations. Example: [12]+[13]', 'formidable-pro' ); ?>"
				rows="3"
				cols="30"
			><?php echo esc_html( $calc ); ?></textarea>
			<div class="frm-field-formula-height"></div>

			<ul class="frm-field-formula-buttons">
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text -frm-mt-2xs">.</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">%</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">)</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">(</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">/</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text frm-mt-2xs">*</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">-</span>
				</li>
				<li class="<?php echo esc_attr( $math_button_class ); ?>" role="button">
					<span class="frm-math-button-text">+</span>
				</li>

				<li class="frm-field-formula-button frm-field-formula-insert-field frm-show-inline-modal frm-open-calc frm-force-mr-auto" role="button" data-open="frm-calc-box-<?php echo esc_attr( $field['id'] ); ?>">
					<span><?php FrmAppHelper::icon_by_class( 'frmfont frm_more_horiz_icon frm_svg12' ); ?></span>
					<span class="frm-white-space-nowrap"><?php esc_html_e( 'Insert Field', 'formidable-pro' ); ?></span>
				</li>
			</ul>

			<?php
			FrmFieldsHelper::inline_modal(
				array(
					'title'        => class_exists( 'FrmTextToggleStyleComponent' ) ? '' : __( 'Calculate Default Value', 'formidable-pro' ), // Backwards compatibility "@since 6.24".
					'callback'     => array( 'FrmProFieldsController', 'calculation_settings' ),
					'args'         => compact( 'field' ),
					'id'           => 'frm-calc-box-' . $field['id'],
					'dismiss-icon' => false,
				)
			);
			?>
		</div>
	</div>
</div>

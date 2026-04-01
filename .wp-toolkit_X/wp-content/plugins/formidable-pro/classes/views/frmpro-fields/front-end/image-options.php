<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Show the radio field on the front-end.
 * Extra line breaks show as space on the front-end when
 * the form is double filtered and not minimized.
 */

if ( empty( $field['options'] ) ) {
	return;
}

if ( FrmProFieldsController::should_skip_rendering_choices_for_field( false, $field ) ) {
	return;
}

$image_size                           = ! empty( $field['image_size'] ) ? $field['image_size'] : FrmProImages::get_default_size();
$image_class                          = ! empty( $field['image_options'] ) ? ' frm_image_option frm_image_' . $image_size : '';
$frontend_includes_a_placeholder_icon = '1' === ( $field['image_options'] ?? '0' );
$missing_image                        = false;

foreach ( $field['options'] as $opt_key => $opt ) {
	if ( isset( $shortcode_atts ) && isset( $shortcode_atts['opt'] ) && $shortcode_atts['opt'] !== $opt_key ) {
		continue;
	}

	if ( FrmProFieldsController::should_hide_field_choice( false, $opt_key, $field ) ) {
		continue;
	}

	$return    = array( 'label' );
	$image     = FrmProImages::single_option_details( compact( 'opt', 'opt_key', 'field', 'return' ) );
	$field_val = FrmFieldsHelper::get_value_from_array( $opt, $opt_key, $field );
	$opt       = FrmFieldsHelper::get_label_from_array( $opt, $opt_key, $field );
	$checked   = FrmAppHelper::check_selected( $field['value'], $field_val ) ? ' checked="checked" ' : ' ';

	if ( $frontend_includes_a_placeholder_icon && empty( $image['url'] ) ) {
		$missing_image = true;
	}

	?>
	<div class="<?php echo esc_attr( apply_filters( 'frm_' . $field['type'] . '_class', 'frm_' . $field['type'], $field, $field_val ) ); ?> <?php echo esc_attr( $image_class ); ?>" id="<?php echo esc_attr( FrmFieldsHelper::get_checkbox_id( $field, $opt_key, $field['type'] ) ); ?>"><?php

	$include_label = ! isset( $shortcode_atts ) || ! isset( $shortcode_atts['label'] ) || $shortcode_atts['label'];

	if ( $include_label ) {
		?><label for="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>"><?php
	}
	?>
	<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $field_name . ( $field['type'] === 'checkbox' ? '[]' : '' ) ); ?>" id="<?php echo esc_attr( $html_id . '-' . $opt_key ); ?>" value="<?php echo esc_attr( $field_val ); ?>"
	<?php
	do_action( 'frm_field_input_html', $field );
	echo $checked . ' '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	if ( FrmProFieldsController::should_disable_choice( false, $opt_key, trim( $checked ) !== '', $field ) ) {
		echo 'disabled="disabled" ';
	}

	?>
	/>
	<?php

	if ( $include_label ) {
		echo ' ';
		FrmAppHelper::kses_echo( $image['label'], 'all' );
		do_action( 'frm_after_choice_input', $field, $opt_key );
		echo '</label>';
	}

	unset( $checked );

	?>
	</div>
<?php
}

if ( $missing_image ) {
	FrmAppHelper::include_svg();
}

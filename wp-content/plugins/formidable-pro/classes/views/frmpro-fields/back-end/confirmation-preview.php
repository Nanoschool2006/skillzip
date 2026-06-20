<?php
/**
 * Confirmation field preview shown in the form builder.
 *
 * @package Formidable Pro
 *
 * @var array $field   Field data.
 * @var array $display Field display settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$input_html = sprintf(
	'<input type="text" id="conf_field_%1$s" name="field_options[conf_input_%2$s]" placeholder="%3$s" class="dyn_default_value" />',
	esc_attr( $field['field_key'] ),
	esc_attr( $field['id'] ),
	esc_attr( $field['conf_input'] )
);

// Show the confirmation label in the preview when "Show confirmation field label" is on.
// Treat a missing option as on so existing fields show labels by default.
$show_conf_label = ! isset( $field['conf_label'] ) || ! empty( $field['conf_label'] );
$conf_label_text = FrmProFieldsHelper::get_confirmation_field_label( $field );
?>
<div id="frm_conf_field_<?php echo esc_attr( $field['id'] ); ?>_container" class="frm_conf_field_container frm_form_fields frm_conf_details<?php echo esc_attr( $field['id'] . ( $field['conf_field'] ? '' : ' frm_hidden' ) ); ?>">
	<div id="frm_conf_field_<?php echo esc_attr( $field['id'] ); ?>_inner_container" class="frm_inner_conf_container">
		<label class="frm_primary_label frm_conf_preview_label<?php echo esc_attr( $show_conf_label ? ' frm_conf_label_shown' : '' ); ?>" data-conf-label="<?php echo esc_attr( $conf_label_text ); ?>"><?php
		if ( $show_conf_label ) {
			FrmAppHelper::kses_echo( force_balance_tags( $conf_label_text ), 'all' );
		} else {
			echo '&nbsp;';
		}
		?></label>
		<div class="frm_form_fields">
			<?php
			/**
			 * Filters the HTML of confirmation input in the backend.
			 *
			 * @since 6.3.1
			 *
			 * @param string $input_html Input HTML.
			 * @param array  $args       Contains `field` array.
			 */
			echo apply_filters( 'frm_conf_input_backend', $input_html, compact( 'field' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
		<div id="conf_field_description_<?php echo esc_attr( $field['id'] ); ?>" class="frm_description"><?php
			FrmAppHelper::kses_echo( force_balance_tags( $field['conf_desc'] ), 'all' );
		?></div>
</div>
</div>
<div class="clear"></div>

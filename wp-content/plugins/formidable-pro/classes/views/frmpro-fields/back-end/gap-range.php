<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Outputs Min and Max Gap settings for range slider field.
 *
 * @since 6.23
 *
 * @var array $field
 */
?>
<p class="frm_form_field frm-gap-range">
	<label for="frm_format_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_help frm-font-semibold frm-text-grey-600 frm-mb-xs" title="<?php esc_attr_e( 'Set the min/max distance between selectors.', 'formidable-pro' ); ?>">
		<?php esc_html_e( 'Gap Range', 'formidable-pro' ); ?>
	</label>
	<span class="frm_grid_container">
		<span class="frm6">
			<label for="frm_min_gap_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_form_field frm-gap-min frm-block frm-mb-6 frm-text-grey-700">
				<?php esc_html_e( 'Min Gap', 'formidable-pro' ); ?>
			</label>
			<input id="frm_min_gap_<?php echo esc_attr( $field['field_key'] ); ?>" type="text" name="field_options[mingap_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['mingap'] ); ?>" data-changeme="field_<?php echo esc_attr( $field['field_key'] ); ?>" />
		</span>
		<span class="frm6">
			<label for="frm_max_gap_<?php echo esc_attr( $field['field_key'] ); ?>" class="frm_last frm_form_field frm-block frm-mb-6 frm-text-grey-700">
				<?php esc_html_e( 'Max Gap', 'formidable-pro' ); ?>
			</label>
			<input id="frm_max_gap_<?php echo esc_attr( $field['field_key'] ); ?>" type="text" name="field_options[maxgap_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field['maxgap'] ); ?>" data-changeme="field_<?php echo esc_attr( $field['field_key'] ); ?>" />
		</span>
	</span>
</p>

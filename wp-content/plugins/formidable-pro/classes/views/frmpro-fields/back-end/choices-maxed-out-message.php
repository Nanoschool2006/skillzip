<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * @since 6.28
 *
 * @var array $field Field array.
 */
?>
<p class="frm_choice_limit_details<?php echo esc_attr( $field['id'] ); ?>">
	<label for="field_options_choice_limit_msg_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'All choices limit reached', 'formidable-pro' ); ?>
	</label>
	<input type="text" name="field_options[choice_limit_msg_<?php echo esc_attr( $field['id'] ); ?>]" value="<?php echo esc_attr( FrmFieldsHelper::get_error_msg( $field, 'choice_limit_msg' ) ); ?>" id="field_options_choice_limit_msg_<?php echo esc_attr( $field['id'] ); ?>" />
</p>
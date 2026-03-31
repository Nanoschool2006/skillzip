<?php
/**
 * Show the settings in the form builder.
 *
 * @package FrmAI
 *
 * @var array  $field The current field.
 * @var string $ai_question
 * @var string $ai_model
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field">
	<label class="frm-h-stack-xs">
		<input type="checkbox" name="field_options[hide_ai_<?php echo esc_attr( $field['id'] ); ?>]" id="frm_hide_ai_<?php echo esc_attr( $field['id'] ); ?>" value="1" <?php checked( $field['hide_ai'], 1 ); ?> />
		<span><?php esc_html_e( 'Hide Response', 'formidable-ai' ); ?></span>
		<?php FrmAIAppController::show_svg_tooltip( __( 'Check this box if you do not want to see the AI response immediately in the form.', 'formidable-ai' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
</p>

<p class="frm_form_field frm_has_shortcodes">
	<label class="frm-h-stack-xs" for="system_<?php echo esc_attr( $field['id'] ); ?>">
		<span><?php esc_html_e( 'Guide Prompt', 'formidable-ai' ); ?></span>
		<?php FrmAIAppController::show_svg_tooltip( __( 'Give Open AI more context for the type of response you would like to receive. You can include shortcodes.', 'formidable-ai' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
	<textarea name="field_options[system_<?php echo esc_attr( $field['id'] ); ?>]"
		id="system_<?php echo esc_attr( $field['id'] ); ?>"
		placeholder="<?php esc_attr_e( 'You are a helpful assistant', 'formidable-ai' ); ?>"
		rows="2"
		><?php echo FrmAppHelper::esc_textarea( $field['system'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></textarea>
</p>

<p class="frm_form_field frm_has_shortcodes">
	<label class="frm-h-stack-xs" for="frm_ai_question_<?php echo esc_attr( $field['id'] ); ?>">
		<span><?php esc_html_e( 'AI Question', 'formidable-ai' ); ?></span>
		<?php FrmAIAppController::show_svg_tooltip( __( 'Compose the question. This can be made up of multiple shortcodes and text.', 'formidable-ai' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
	<textarea name="field_options[ai_question_<?php echo esc_attr( $field['id'] ); ?>]"
		id="frm_ai_question_<?php echo esc_attr( $field['id'] ); ?>"
		rows="2"
		><?php // phpcs:ignore
		echo FrmAppHelper::esc_textarea( $ai_question ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// phpcs:ignore ?></textarea>
</p>

<p class="frm_form_field">
	<label class="frm-h-stack-xs" for="frm_ai_model_<?php echo esc_attr( $field['id'] ); ?>">
		<span><?php esc_html_e( 'AI Model', 'formidable-ai' ); ?></span>
		<?php FrmAIAppController::show_svg_tooltip( __( '4o mini requests are likely to be higher quality than 3.5 turbo, but are likely to take a few seconds longer to complete.', 'formidable-ai' ), array( 'class' => 'frm-flex' ) ); ?>
	</label>
	<select name="field_options[ai_model_<?php echo esc_attr( $field['id'] ); ?>]" id="frm_ai_model_<?php echo esc_attr( $field['id'] ); ?>">
		<option value="gpt-3.5-turbo" <?php selected( $ai_model, 'gpt-3.5-turbo' ); ?>><?php esc_html_e( 'GPT 3.5 Turbo', 'formidable-ai' ); ?></option>
		<option value="gpt-4o-mini" <?php selected( $ai_model, 'gpt-4o-mini' ); ?>><?php esc_html_e( 'GPT 4o mini', 'formidable-ai' ); ?></option>
		<option value="gpt-4.1-nano" <?php selected( $ai_model, 'gpt-4.1-nano' ); ?>><?php esc_html_e( 'GPT 4.1 nano', 'formidable-ai' ); ?></option>
		<?php /* <option value="claude-3-5-haiku" <?php selected( $ai_model, 'claude-3-5-haiku' ); ?>><?php esc_html_e( 'Claude 3.5 Haiku', 'formidable-ai' ); ?></option> */ ?>
		<option value="gemini-2-flash-lite" <?php selected( $ai_model, 'gemini-2-flash-lite' ); ?>><?php esc_html_e( 'Gemini 2 Flash Lite', 'formidable-ai' ); ?></option>
	</select>
</p>

<p class="howto">
	<?php esc_html_e( 'By using this field, you agree to the ChatGPT terms of service.', 'formidable-ai' ); ?>
</p>

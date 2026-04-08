<?php
/**
 * Ranking Field extra options
 *
 * @package FrmSurveys
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_form_field frm_full!">
	<label class="frm-h-stack-xs" for="frm_limit_ranking_selections_<?php echo absint( $field['id'] ); ?>">
		<input data-frmshow="#frm-ranking-field-limit-selection-<?php echo absint( $field['id'] ); ?>" type="checkbox" name="field_options[limit_selections_<?php echo absint( $field['id'] ); ?>]" id="frm_limit_ranking_selections_<?php echo absint( $field['id'] ); ?>" value="1" <?php isset( $field['limit_selections'] ) ? checked( $field['limit_selections'], 1 ) : 0; ?> />
		<span><?php esc_html_e( 'Limit selections', 'formidable-surveys' ); ?></span>
		<?php
		FrmSurveys\helpers\FieldsHelper::show_svg_tooltip(
			__( 'Specifies the maximum number of items you can choose', 'formidable-surveys' ),
			array(
				'data-placement' => 'right',
				'data-container' => 'body',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
</p>
<p class="frm_form_field">
	<label for="frm_randomize_ranking_options_<?php echo absint( $field['id'] ); ?>">
		<input type="checkbox" name="field_options[randomize_options_<?php echo absint( $field['id'] ); ?>]" id="frm_randomize_ranking_options_<?php echo absint( $field['id'] ); ?>" value="1" <?php isset( $field['randomize_options'] ) ? checked( $field['randomize_options'], 1 ) : 0; ?> />
		<?php esc_html_e( 'Randomize options', 'formidable-surveys' ); ?>
	</label>
</p>
<p class="<?php echo ( ! isset( $field['limit_selections'] ) || (string) $field['limit_selections'] !== '1' ? 'frm_hidden' : '' ); ?>" id="frm-ranking-field-limit-selection-<?php echo absint( $field['id'] ); ?>">
	<label class="frm_primary_label">
		<?php esc_html_e( 'Limit', 'formidable-surveys' ); ?>
		<input type="number" value="<?php echo isset( $field['answers_limit'] ) ? esc_attr( $field['answers_limit'] ) : ''; ?>" name="field_options[answers_limit_<?php echo esc_attr( $field['id'] ); ?>]" />
	</label>
</p>

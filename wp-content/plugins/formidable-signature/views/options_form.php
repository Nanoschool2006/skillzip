<?php
/**
 * Restrict direct access.
 *
 * @package frmsig
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<tr>
	<td>
		<span class="frm-font-semibold frm-text-grey-600"><?php esc_html_e( 'Field Size', 'frmsig' ); ?></span>
	</td>
	<td class="frm-p-0 frm-m-0">
		<div class="frm_grid_container">
			<p class="frm_form_field frm6">
				<label for="signature_size_<?php echo (int) $field['id']; ?>"><?php esc_html_e( 'Width (px)', 'frmsig' ); ?></label>
				<input class="frm-w-full" type="text" id="signature_size_<?php echo (int) $field['id']; ?>" name="field_options[size_<?php echo (int) $field['id']; ?>]" value="<?php echo esc_attr( $field['size'] ); ?>" />
			</p>
			<p class="frm_form_field frm6">
				<label for="signature_max_<?php echo (int) $field['id']; ?>"><?php esc_html_e( 'Height (px)', 'frmsig' ); ?></label>
				<input class="frm-w-full" type="text" id="signature_max_<?php echo (int) $field['id']; ?>" name="field_options[max_<?php echo (int) $field['id']; ?>]" value="<?php echo esc_attr( $field['max'] ); ?>" />
			</p>
		</div>
	</td>
</tr>
<tr>
	<td>
		<span class="frm-font-semibold frm-text-grey-600"><?php esc_html_e( 'Signature Options', 'frmsig' ); ?></span>
	</td>
	<td class="frm-p-0 frm-m-0">
		<div class="frm_grid_container">
			<p class="frm_form_field frm6">
				<label for="label1_<?php echo (int) $field['id']; ?>"><?php esc_html_e( 'Draw It Label', 'frmsig' ); ?></label>
				<input type="text" class="frm-w-full" name="field_options[label1_<?php echo (int) $field['id']; ?>]" value="<?php echo esc_attr( $field['label1'] ); ?>" id="label1_<?php echo (int) $field['id']; ?>"  />
			</p>
			<p class="frm_form_field frm6">
				<label for="label2_<?php echo (int) $field['id']; ?>" class="howto"><?php esc_html_e( 'Type It Label', 'frmsig' ); ?></label>
				<input type="text" class="frm-w-full" name="field_options[label2_<?php echo (int) $field['id']; ?>]" value="<?php echo esc_attr( $field['label2'] ); ?>" id="label2_<?php echo (int) $field['id']; ?>" />
			</p>
			<p class="frm_form_field frm6">
				<label for="label3_<?php echo (int) $field['id']; ?>" class="howto"><?php esc_html_e( 'Clear Label', 'frmsig' ); ?></label>
				<input type="text" class="frm-w-full" name="field_options[label3_<?php echo (int) $field['id']; ?>]" value="<?php echo esc_attr( $field['label3'] ); ?>" id="label3_<?php echo (int) $field['id']; ?>" />
			</p>
			<p class="frm_form_field">
				<input type="checkbox" name="field_options[restrict_<?php echo (int) $field['id']; ?>]" id="restrict_<?php echo (int) $field['id']; ?>" value="1" <?php FrmAppHelper::checked( $field['restrict'], 1 ); ?> />
				<label for="restrict_<?php echo (int) $field['id']; ?>">
					<?php esc_html_e( 'Hide Draw It and Type It tabs', 'frmsig' ); ?>
				</label>
			</p>
			<p class="frm_form_field">
				<input type="checkbox" name="field_options[type_it_<?php echo (int) $field['id']; ?>]" id="type_it_<?php echo (int) $field['id']; ?>" value="1" <?php FrmAppHelper::checked( $field['type_it'], 1 ); ?> />
				<label for="type_it_<?php echo (int) $field['id']; ?>">
					<?php esc_html_e( 'Set Type It as default', 'frmsig' ); ?>
				</label>
			</p>
			<p class="frm_form_field">
				<input type="checkbox" name="field_options[allow_edit_<?php echo (int) $field['id']; ?>]" id="allow_edit_<?php echo (int) $field['id']; ?>" value="1" <?php FrmAppHelper::checked( $field['allow_edit'], 1 ); ?> />
				<label for="allow_edit_<?php echo (int) $field['id']; ?>">
					<?php esc_html_e( 'Allow editing', 'frmsig' ); ?>
				</label>
			</p>
		</div>
	</td>
</tr>

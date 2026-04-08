<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
$field                              = $args['field'];
$should_hide_remaining_qty_settings = ! FrmField::get_option( $field, 'set_choices_limit' );
?>
<p class="frm12 frm_form_field frm_set_choices_limit">
	<label class="frm-h-stack-xs">
		<input type="checkbox" name="field_options[set_choices_limit_<?php echo absint( $field['id'] ); ?>]" value="1" <?php checked( $field['set_choices_limit'], 1 ); ?> class="frm_toggle_set_choices_limit" />
		<span><?php esc_html_e( 'Set option limits', 'formidable-pro' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			sprintf( __( 'Set the maximum number of times an option can be selected. Leave blank for unlimited.', 'formidable-pro' ), $field['id'] . ' show=value' ),
			array(
				'data-placement' => 'right',
				'data-container' => 'body',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
</p>
<p class="frm12 frm_form_field frm_show_remaining_quantity <?php echo $should_hide_remaining_qty_settings ? 'frm_hidden' : ''; ?>">
	<label class="frm-h-stack-xs">
		<input type="checkbox" name="field_options[show_remaining_quantity_<?php echo absint( $field['id'] ); ?>]" value="1" <?php checked( $field['show_remaining_quantity'], 1 ); ?> class="frm_toggle_show_remaining_quantity" />
		<span><?php esc_html_e( 'Show remaining quantity', 'formidable-pro' ); ?></span>
		<?php
		FrmAppHelper::tooltip_icon(
			sprintf( __( 'Show remaining entries allowed fore each choice before their limits are reached.', 'formidable-pro' ), $field['id'] . ' show=value' ),
			array(
				'data-placement' => 'right',
				'data-container' => 'body',
				'class'          => 'frm-flex',
			)
		);
		?>
	</label>
</p>
<span
	<?php
	FrmAppHelper::array_to_html_params(
		array(
			'class'                    => 'frm-remaining-qty-link ' . ( $should_hide_remaining_qty_settings ? 'frm_hidden' : '' ),
			'data-remaining-qty-label' => FrmProFieldsHelper::get_remaining_qty_label( $field ),
			'data-exhausted-message'   => FrmProFieldsHelper::get_exhausted_message( $field ),
		),
		true
	);
	?>
>
	<a href="#" class="frm-h-stack frm-justify-end frm-remaining-qty-link">
		<span><?php esc_html_e( 'Settings', 'formidable' ); ?></span>
	</a>
</span>


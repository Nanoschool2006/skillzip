<li class="frmdates_date_list_item frm-p-0 frm-m-0 <?php echo esc_attr( $css_classes ); ?>" data-date="<?php echo esc_attr( $date ); ?>">
	<?php
	$remove_date_aria_label = sprintf(
		/* translators: %s: Date */
		__( 'Remove %s', 'frmdates' ),
		$formatted_date
	);
	?>
	<a href="#" class="frmdates_remove_item frm-token" aria-label="<?php echo esc_attr( $remove_date_aria_label ); ?>">
		<span class="frmdates_date_with_format frm-token-value"><?php echo esc_html( $formatted_date ); ?></span>
		<span class="frm-token-remove"><?php FrmAppHelper::icon_by_class( 'frm_icon_font frm_close_icon' ); ?></span>
	</a>
	<?php if ( ! empty( $input_name ) ) : ?>
		<input type="hidden" name="field_options[<?php echo esc_attr( $input_name ); ?>][]" value="<?php echo esc_attr( $date ); ?>" />
	<?php endif; ?>
</li>

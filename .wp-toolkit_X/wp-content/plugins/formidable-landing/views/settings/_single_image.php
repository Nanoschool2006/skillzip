<div class="frm_radio frm_image_option frm3">
	<label>
		<input type="radio" name="options[landing_layout]" value="<?php echo esc_attr( $layout['type'] ); ?>" <?php checked( $values['landing_layout'], $layout['type'] ); ?> data-toggleclass="hide_landing_layout" />
		<div class="frm_image_option_container frm_label_with_image">
			<div class="frm_empty_url">
				<?php echo $layout['svg']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<span class="frm_text_label_for_image">
				<?php echo esc_html( $layout['label'] ); ?>
			</span>
		</div>
	</label>
</div>

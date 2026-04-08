<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="frm_grid_container">
	<label for="frm_date_format" class="frm4 frm_form_field">
		<?php esc_html_e( 'Date Format', 'formidable' ); ?>
		<?php FrmAppHelper::tooltip_icon( __( 'Change the format of the date used in the date field.', 'formidable-pro' ) ); ?>
	</label>
	<?php $formats = array_keys( FrmProAppHelper::display_to_datepicker_format() ); ?>
	<select id="frm_date_format" name="frm_date_format" class="frm8 frm_form_field">
		<?php foreach ( $formats as $f ) { ?>
			<option value="<?php echo esc_attr( $f ); ?>" <?php selected( $frmpro_settings->date_format, $f ); ?>>
				<?php echo esc_html( $f . ' &nbsp; &nbsp; ' . gmdate( $f ) ); ?>
			</option>
		<?php } ?>
	</select>
</p>

<p class="frm_grid_container">
	<label for="frm_datepicker_library" class="frm4 frm_form_field">
		<?php esc_html_e( 'Date Picker Library', 'formidable-pro' ); ?>
	</label>
	<span class="frm8 frm_grid_container">
		<select id="frm_datepicker_library" name="frm_datepicker_library" class="frm_form_field frm-12">
			<?php
				foreach ( $datepicker_libraries as $key => $name ) {
				FrmProHtmlHelper::echo_dropdown_option( $name, $frmpro_settings->datepicker_library === $key, array( 'value' => $key ) );
				}
			?>
		</select>
		<span id="frm_datepicker_jquery_range_support_note" class="frm_note_style frm-12 <?php echo $frmpro_settings->datepicker_library === 'jquery' ? '' : 'frm_hidden'; ?>">
			<?php esc_html_e( 'Switching to jQuery disables date range selection', 'formidable-pro' ); ?>
		</span>
	</span>
</p>

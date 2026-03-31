<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p>
	<?php
	if ( ! empty( $should_show_address_type_warning ) ) {
		?>
		<div class="frm_warning_style" style="padding-right: var(--gap-md);">
			<?php esc_html_e( 'Square requires the country code in order to validate the address. Select another address type to prevent checkout errors.', 'formidable-pro' ); // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong ?>
		</div>
		<?php
	}
	?>

	<label for="address_type_<?php echo esc_attr( $field['id'] ); ?>">
		<?php esc_html_e( 'Address Type', 'formidable-pro' ); ?>
	</label>

	<select name="field_options[address_type_<?php echo esc_attr( $field['id'] ); ?>]" id="address_type_<?php echo esc_attr( $field['id'] ); ?>">
		<option value="international" <?php selected( $field['address_type'], 'international' ); ?>>
			<?php esc_html_e( 'International', 'formidable' ); ?>
		</option>
		<option value="us" <?php selected( $field['address_type'], 'us' ); ?>>
			<?php esc_html_e( 'United States', 'formidable' ); ?>
		</option>
		<option value="europe" <?php selected( $field['address_type'], 'europe' ); ?>>
			<?php esc_html_e( 'Europe', 'formidable-pro' ); ?>
		</option>
		<option value="generic" <?php selected( $field['address_type'], 'generic' ); ?>>
			<?php esc_html_e( 'Other - exclude country field', 'formidable-pro' ); ?>
		</option>
	</select>
</p>
<?php
if ( ! is_callable( 'FrmGeoAppController::path' ) ) {
	require FrmProAppHelper::plugin_path() . '/classes/views/combo-fields/addresses/geo-upsell.php';
}


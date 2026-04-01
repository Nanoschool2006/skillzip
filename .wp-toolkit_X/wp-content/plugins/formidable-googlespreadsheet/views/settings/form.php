<?php
/**
 * Google Sheets Setting form.
 *
 * @since 1.0
 *
 * @package formidable-google-sheets
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<h3 class="frm-no-border frm_no_top_margin">
	<?php esc_html_e( 'Google Authentication', 'formidable-google-sheets' ); ?>
</h3>

<?php if ( ! $has_access_token ) : ?>
	<ol class="howto" style="font-size:14px">
		<li>
			<?php
			printf(
				/* translators: %1$s: Start link HTML %2$s: end link HTML */
				esc_html__( 'Create a Google API project in %1$sGoogle console%2$s.', 'formidable-google-sheets' ),
				'<a href="https://console.cloud.google.com/apis" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
		<li>
			<?php
			printf(
				/* translators: %1$s: Start link HTML %2$s: end link HTML */
				esc_html__( 'Copy the %1$sproject keys%2$s and include them below.', 'formidable-google-sheets' ),
				'<a href="' . esc_url( $doc_url ) . '" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</li>
		<li><?php esc_html_e( 'Click Authorize to complete the connection.', 'formidable-google-sheets' ); ?></li>
	</ol>
	<br/>
<?php endif; ?>

<p class="frm_grid_container">
	<label class="frm4 frm_form_field" for="frm_googlespreadsheet_client_id">
		<?php esc_html_e( 'Client ID', 'formidable-google-sheets' ); ?>
		<?php
		if ( $has_access_token && is_callable( 'FrmAppHelper::tooltip_icon' ) ) :
			FrmAppHelper::tooltip_icon( __( 'To change the client ID and client secret, please deauthorize first using the button below.', 'formidable-google-sheets' ) );
		endif;
		?>
	</label>
	<input type="text" name="frm_googlespreadsheet_client_id" id="frm_googlespreadsheet_client_id" class="frm8 frm_form_field" value="<?php echo esc_attr( $client_id ); ?>" <?php echo $has_access_token ? esc_attr( 'disabled' ) : ''; ?> />
</p>

<p class="frm_grid_container">
	<label class="frm4 frm_form_field" for="frm_googlespreadsheet_client_secret">
		<?php esc_html_e( 'Client Secret', 'formidable-google-sheets' ); ?>
	</label>
	<input type="text" name="frm_googlespreadsheet_client_secret" id="frm_googlespreadsheet_client_secret" class="frm8 frm_form_field" value="<?php echo esc_attr( $client_secret ); ?>" <?php echo $has_access_token ? esc_attr( 'disabled' ) : ''; ?> />
</p>

<p id="frm-google-sheets-connect-btns" class="frm-show-unauthorized">
	<a class="<?php echo esc_attr( $auth_button['class'] ); ?>" href="#">
		<?php echo esc_html( $auth_button['btn_text'] ); ?>
	</a>
</p>

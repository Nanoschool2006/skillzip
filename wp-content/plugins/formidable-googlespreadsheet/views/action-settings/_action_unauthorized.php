<div class="frm_error_style">
	<strong>
		<?php esc_html_e( 'Oops!', 'formidable-google-sheets' ); ?>
	</strong>
	<?php
	/* translators: %1$s: setting URL, %2$s: end link */
	printf( esc_html__( 'The Google Sheets API is not authorized. Please set it up %1$shere%2$s.', 'formidable-google-sheets' ), '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-settings&t=googlespreadsheet_settings' ) ) . '" target="_blank">', '</a>' );
	?>
</div>

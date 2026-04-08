<div id="form_settings_page" class="wrap">
	<h2><?php esc_html_e( 'Translate Form', 'formidable-polylang' ); ?></h2>

	<p>
		<?php
		printf(
			/* translators: %1$s: Start link HTML, %2$s: end link HTML */
			esc_html__( 'Oops! You do not have %1$sPolylang%2$s installed. Once you have installed and configured it, come back here to translate your forms.', 'formidable-polylang' ),
			'<a href="https://wordpress.org/plugins/polylang/">',
			'</a>'
		);
		?>
	</p>
</div>

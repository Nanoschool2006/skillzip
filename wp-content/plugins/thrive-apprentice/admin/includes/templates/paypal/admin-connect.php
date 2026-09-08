<div class="tva-paypal-connect-wrapper tva-flex">
	<div class="tva-paypal-connect tva-flex">
		<div class="tva-paypal-connect-icons">
			<?php tva_get_svg_icon( 'circle-paypal' ); ?>
			<?php tva_get_svg_icon( 'arrows-right-left' ); ?>
			<?php tva_get_svg_icon( 'circle-apprentice' ); ?>
		</div>
		<h3><?php esc_html_e( 'PayPal is not connected', 'thrive-apprentice' ); ?></h3>
		<p><?php esc_html_e( 'Connect your PayPal account with Thrive to manage payments. You will be guided through PayPal\'s onboarding; the connection mode (Live or Sandbox) is detected automatically.', 'thrive-apprentice' ); ?></p>
		<button class="tva-btn tva-btn-blue click mt-20" data-fn="createAccount" data-type="live"><?php esc_html_e( 'Connect PayPal', 'thrive-apprentice' ); ?></button>
	</div>
</div>

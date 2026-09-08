<div class="tva-paypal-connect-wrapper tva-flex">
	<div class="tva-paypal-connect tva-flex tva-paypal-finalizing">
		<div class="tva-paypal-connect-icons">
			<?php tva_get_svg_icon( 'circle-paypal' ); ?>
			<?php tva_get_svg_icon( 'arrows-right-left' ); ?>
			<?php tva_get_svg_icon( 'circle-apprentice' ); ?>
		</div>
		<div class="tva-paypal-finalizing-active">
			<h3><?php esc_html_e( 'Finalizing your PayPal connection…', 'thrive-apprentice' ); ?></h3>
			<p><?php esc_html_e( 'PayPal is still setting up your account. This usually takes less than a minute — keep this page open.', 'thrive-apprentice' ); ?></p>
		</div>
		<div class="tva-paypal-finalizing-timeout" style="display:none;">
			<h3><?php esc_html_e( 'This is taking longer than expected', 'thrive-apprentice' ); ?></h3>
			<p><?php esc_html_e( 'Your connection is still finalizing. You can keep waiting or check again now.', 'thrive-apprentice' ); ?></p>
			<button class="tva-btn tva-btn-blue click mt-20" data-fn="retryFinalize"><?php esc_html_e( 'Check again', 'thrive-apprentice' ); ?></button>
		</div>
	</div>
</div>

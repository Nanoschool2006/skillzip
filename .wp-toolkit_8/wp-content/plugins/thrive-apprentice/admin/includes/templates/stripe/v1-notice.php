<div class="tva-container-notice tva-container-notice-orange tva-stripe-v1-notice mt-10 mb-10">
	<?php tva_get_svg_icon( 'exclamation-triangle' ); ?>
	<div class="tva-stripe-notice-text">
		<h4 class="m-0 mt-10"><?php echo __( 'Please reconnect Thrive Apprentice with your Stripe account', 'thrive-apprentice' ); ?></h4>
		<p class="m-0 mt-10"><?php echo __( 'We have upgraded our connection with Stripe to provide you with an improved experience and enhanced features. In order to make this happen you will need to reconnect your Stripe account.', 'thrive-apprentice' ) ?> </p>
		<p class="m-0 mt-10"><?php echo __( 'Do not worry, your Stripe payments are still working but you will need to reconnect to access the enhanced features as soon as possible.', 'thrive-apprentice' ) ?> <a href="https://thrivethemes.com/docs/upgrading-from-stripe-v1-2-to-v1-3/#reconnecting-stripe-with-thrive-apprentice" target="_blank"><?php echo __( 'Learn More', 'thrive-apprentice' ); ?></a></p>
		<button class="tva-btn tva-btn-blue click mt-20" id="tva-stripe-reconnect" data-fn="createAccount"><?php echo __( 'Reconnect Stripe', 'thrive-apprentice' ); ?></button>
	</div>
</div>

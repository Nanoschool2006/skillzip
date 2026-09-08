<div class="tva-container-notice tva-container-notice-orange tva-square-v1-notice mt-10 mb-10">
	<?php tva_get_svg_icon( 'exclamation-triangle' ); ?>
	<div class="tva-square-notice-text">
		<h4 class="m-0 mt-10"><?php echo __( 'Please reconnect Thrive Apprentice with your Square account', 'thrive-apprentice' ); ?></h4>
		<p class="m-0 mt-10"><?php echo __( 'We have upgraded our connection with Square to provide you with an improved experience and enhanced features. In order to make this happen you will need to reconnect your Square account.', 'thrive-apprentice' ) ?> </p>
		<p class="m-0 mt-10"><?php echo __( 'Do not worry, your Square payments are still working but you will need to reconnect to access the enhanced features as soon as possible.', 'thrive-apprentice' ) ?> <a href="https://thrivethemes.com/docs/upgrading-from-square-v1-2-to-v1-3/#reconnecting-square-with-thrive-apprentice" target="_blank"><?php echo __( 'Learn More', 'thrive-apprentice' ); ?></a></p>
		<button class="tva-btn tva-btn-blue click mt-20" id="tva-square-reconnect" data-fn="createAccount"><?php echo __( 'Reconnect Square', 'thrive-apprentice' ); ?></button>
	</div>
</div>

<div class="tva-container-notice tva-container-notice-orange tva-square-v2-notice tva-hide mt-10 mb-10">
	<?php tva_get_svg_icon( 'exclamation-triangle' ); ?>
	<div class="tva-square-notice-text">
		<div class="tva-flex tva-square-notice-title mt-5">
			<h4 class="m-0"><?php echo __( 'Switch to Thrive Apprentice Managed Product', 'thrive-apprentice' ); ?></h4>
			<span class="tva-square-notice-recommended"><?php echo __( 'Recommended', 'thrive-apprentice' ) ?></span>
		</div>
		<p class="m-0 mt-10"><?php echo __( 'We have upgraded our integration with Square to provide a more streamlined experience for managing your products. To take advantage of these improvements you will need to confirm that you would like Thrive Apprentice to manage your square products on your behalf. ', 'thrive-apprentice' ) ?>
			<a href="https://thrivethemes.com/docs/upgrading-from-square-v1-2-to-v1-3/#adding-products-to-your-square-account" target="_blank"><?php echo __( 'Learn More', 'thrive-apprentice' ); ?></a>
		</p>
		<button class="tva-btn tva-btn-blue click mt-20" id="tva-square-action" data-fn="openProductMigrationModal"><?php echo __( 'Let Thrive Apprentice Manage Products', 'thrive-apprentice' ); ?></button>
	</div>
</div>
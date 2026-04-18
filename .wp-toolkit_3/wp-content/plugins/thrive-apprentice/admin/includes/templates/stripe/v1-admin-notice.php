<div class="notice notice-warning tva-stripe-notice">
	<h4 class="tva-stripe-notice-heading"><?php echo __( 'Please reconnect Thrive Apprentice with your Stripe account', 'thrive-apprentice' ); ?></h4>
	<p class="tva-stripe-notice-p"><?php echo __( 'We have upgraded our connection with Stripe to provide you with an improved experience and enhanced features. In order to make this happen you will need to reconnect your Stripe account.', 'thrive-apprentice' ) ?> </p>
	<p class="tva-stripe-notice-p"><?php echo __( 'Do not worry, your Stripe payments are still working but you will need to reconnect to access the enhanced features as soon as possible.', 'thrive-apprentice' ) ?> <a href="https://thrivethemes.com/docs/upgrading-from-stripe-v1-2-to-v1-3/#reconnecting-stripe-with-thrive-apprentice" target="_blank"><?php echo __( 'Learn More', 'thrive-apprentice' ); ?></a></p>
	<button id="tva-stripe-reconnect"><?php echo __( 'Reconnect Stripe', 'thrive-apprentice' ); ?></button>
</div>
<style>
    .tva-stripe-notice {
        border-left-color: #FF7100 !important;
    }
    .tva-stripe-notice-heading {
        margin: 5px 0 0;
        font-weight: 700;
        line-height: 30px;
        font-size: 13px;
    }

    .tva-stripe-notice-p {
        margin: 0 0 5px 0 !important;
        line-height: 21px;
    }

    #tva-stripe-reconnect {
        cursor: pointer;
        color: #fff !important;
        border-radius: 3px;
        background: #3858E9 !important;
        margin: 10px 0 15px 0 !important;
        padding: 10px;
        outline: none !important;
        border: none !important;
        font-weight: 400;
    }

    #tva-stripe-reconnect:hover {
        opacity: 0.8 !important;
    }
</style>
<script>
	const button = document.getElementById( 'tva-stripe-reconnect' );

	button.addEventListener( 'click', function ( e ) {
		wp.apiRequest( {
			url: `<?php echo tva_get_route_url( 'stripe' );?>/connect_account`,
			method: 'POST'
		} ).then( response => {
			if ( response.success ) {
				window.location = response.url;
			}

		} )
	} );
</script>

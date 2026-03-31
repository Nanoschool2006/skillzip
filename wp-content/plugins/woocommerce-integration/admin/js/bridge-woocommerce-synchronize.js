(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-specific JavaScript source
	 * should reside in this file.
	 *
	 * Note that this assume you're going to use jQuery, so it prepares
	 * the $ function reference to be used within the scope of this
	 * function.
	 *
	 * From here, you're able to define handlers for when the DOM is
	 * ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * Or when the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and so on.
	 *
	 * Remember that ideally, we should not attach any more than a single DOM-ready or window-load handler
	 * for any particular page. Though other scripts in WordPress core, other plugins, and other themes may
	 * be doing this, we should try to minimize doing that in our own work.
	 */

	$(function() {

	     /**
	     * creates ajax request to initiate product synchronization
	     * display a response to user on process completion
	     */

	    $( '#bridge_woo_synchronize_product_button' ).click( function( ){

	    	$( '.response-box' ).empty(); // empty the response

	    	var sync_options = {};
	    	var $this = $( this );

	    	// prepare sync options array
	    	$( 'input:checkbox' ).each(function () {
			    var cb_key = $( this ).attr( 'id' );
			    var cb_value = (this.checked ? $( this ).val() : 0);
				sync_options[cb_key] = cb_value;
			});
			if (jQuery( '#bridge_woo_synchronize_product_create' ).is( ':checked' ) === false && jQuery( '#bridge_woo_synchronize_product_update' ).is( ':checked' ) === false && jQuery( '#bridge_woo_synchronize_product_categories' ).is( ':checked' ) === false ) {

				jQuery( '.response-box' ).append( '<div class="alert alert-error">' + bridge_woo_product_obj.select_least_option_message + '</div>' );
				return;
			}
			else
			{
				//display loading animation
				$( '.load-response' ).show();

				$.ajax({
					method: "post",
					url: bridge_woo_product_obj.admin_ajax_path,
					dataType: "json",
					data: {
						'action':'handle_product_synchronization',
						'sync_options': JSON.stringify( sync_options ),
						'_wpnonce_field': bridge_woo_product_obj.product_sync_nonce,
					},
					success:function( data ) {

						$( '.load-response' ).hide();
						jQuery( '.response-box' ).append( data.respone_message );
					}
				});
			}

	    });

	});

})( jQuery );

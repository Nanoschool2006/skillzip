(function( $ ) {
	'use strict';
	
	$( '#woocommerce-order-items' ).on( 'change', 'input#wi_unenrol', function(){
		// var r;
		// if ($(this).is(':checked')) {
		// 	r = confirm('Are you sure?');
		// }
		$('.refund-actions button').prop('disabled', true);
		$.post(
		    ajaxurl, 
		    {
		        'action': 	'unenrol_check_status',
		        'unenrol':  $('#wi_unenrol:checked').length ? 'checked' : '',
		        'security': $('#wi_refund_unenrol').val(),
		        'order_id': $('#wi_order_id').val(),
		    }, 
		    function(response){
		        $('.refund-actions button').prop('disabled', false);
		    }
		);
	} );

	$( '#woocommerce-order-items' ).on( 'click', 'button.refund-items', function(){
		if ($('.wi-refund-wrapper').length === 0) {
			$('.refund-actions').before(wiRefund.html);
		}
		$.post(
		    ajaxurl, 
		    {
		        'action': 	'unenrol_update_html',
		        'unenrol':  $('#wi_unenrol:checked').length ? 'checked' : '',
		        'security': $('#wi_refund_unenrol').val(),
		        'order_id': $('#wi_order_id').val(),
		    }, 
		    function(response){
		        if (response.data.display === 'true') {
		        	$('.wi-refund-wrapper').show();
		        } else {
		        	$('.wi-refund-wrapper').hide();
		        }
		    }
		);
	} );

})( jQuery );

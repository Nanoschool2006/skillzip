jQuery(document).ready(function($){
	if (jQuery('#wi-thanq-wrapper').length) {
		wiThankYouRedirection();
	}

	if (jQuery('.wi-scc-wrapper').length) {
		if (jQuery('#wi-scc-url').length) {
			$('input[name="redirect"]').val(jQuery('#wi-scc-url').val());
			$('input[name="_wp_http_referer"]').val(jQuery('#wi-scc-url').val());
		}
	}

	$('#purchase-for-someone-else-checkbox').on('change', function(){
		if ($(this).is(':checked')) {
			$('.eb-purchase-for-someone-else').show();
		} else {
			$('.eb-purchase-for-someone-else').hide();
		}
	});
});

function wiThankYouRedirection()
{
	// Countdown.
	var countDownElement = document.getElementById("wi-countdown");
	var counter = 10;
	var id = wiCountDown(countDownElement, counter);

	// Cancel redirect.
	jQuery(document).on('click', '#wi-cancel-redirect', function(){
		if (jQuery(this).attr('data-wi-auto-redirect') === 'on') {
        	jQuery(this).attr('data-wi-auto-redirect', 'off');
        	jQuery(this).text(wiPublic.resume);
        	clearInterval(id);
        } else {
        	jQuery(this).attr('data-wi-auto-redirect', 'on');
        	jQuery(this).text(wiPublic.cancel);
        	id = wiCountDown(countDownElement, jQuery(countDownElement).text());
        }
	});
}

function wiCountDown(countDownElement, counter)
{
	var id = setInterval(function() {
	    counter--;
	    if(counter < 0) {
	        clearInterval(id);

	        // Redirect
	        if (jQuery('#wi-cancel-redirect').attr('data-wi-auto-redirect') === 'on') {
	        	window.location.href = wiPublic.myCoursesUrl;
	        }
	    } else {
	    	jQuery(countDownElement).text(counter.toString());
	    }
	}, 1000);

	return id;
}


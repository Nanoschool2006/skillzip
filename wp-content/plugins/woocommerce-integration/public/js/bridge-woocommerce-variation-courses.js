/**
 * All of the code for your public-facing JavaScript source
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

// $(function() {

// 	console.log(bridge_woo_courses);

// });

; (function ($, window, document, undefined) {

	var bridge_woo_obj = bridge_woo_courses;

	$(".single_variation_wrap").on("show_variation", function (event, variation) {
		// Fired when the user selects all the required dropdowns / attributes
		// and a final variation is selected / shown

		$('.bridge-woo-courses').hide();
		$('.bridge-woo-available-courses').remove();

		if (bridge_woo_obj[variation.variation_id].length) {
			$('.bridge-woo-courses').append(bridge_woo_obj[variation.variation_id]);

			$('.bridge-woo-courses').show();
		}

	});

})(jQuery, window, document);

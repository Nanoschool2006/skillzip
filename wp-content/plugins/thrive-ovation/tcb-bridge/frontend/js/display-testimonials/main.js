( function ( $ ) {
	if ( window.TCB_Front ) {
		/**
		 * The DisplayTestimonials class extends the post list class from TCB, so we do this check before initializing the course list functionality
		 * TCB_Front.PostList is undefined only if JS optimizations are enabled and there are no display testimonials on the page
		 */
		function tvoDisplayTestimonials () {
			if ( typeof TCB_Front.PostList !== 'undefined' ) {
				TCB_Front.displayTestimonials = require( './elements/display-testimonials' )( jQuery, TCB_Front );

				TCB_Front.displayTestimonials.initDisplayTestimonials();
			}
		}

		// Check if Divi is the theme.
		if ( 0 !== $( '#divi-style-css' ).length ) {
			$( window ).on( 'load', tvoDisplayTestimonials );
		} else {
			tvoDisplayTestimonials();
		}
	}
}( ThriveGlobal.$j ) );

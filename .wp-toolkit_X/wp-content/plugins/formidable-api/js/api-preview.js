( function() {
	var previousHeight = 0;

	function handleDocumentReady() {
		postMessageOnHeightUpdate();
		setInterval( postMessageOnHeightUpdate, 100 );
	};

	function postMessageOnHeightUpdate() {
		var isLandingPage, useHeight, message;

		isLandingPage = -1 !== document.body.className.indexOf( 'frm_landing_page-template-default' );

		if ( isLandingPage ) {
			useHeight = document.querySelector( '.container' ).scrollHeight + 200;
		} else {
			useHeight = document.body.scrollHeight;
		}

		if ( previousHeight === useHeight ) {
			return;
		}

		message = {
			type: 'frm_api_iframe_loaded',
			height: useHeight
		};
		window.parent.postMessage( message, '*' );
		previousHeight = useHeight;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', handleDocumentReady );
	} else {
		handleDocumentReady();
	}
}() );

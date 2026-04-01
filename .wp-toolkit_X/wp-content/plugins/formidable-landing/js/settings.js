( function() {
	var activeValidationXHR, debouncedValidate;

	function setUpLandingPageUrlValidation() {
		var landingPageSettings;

		landingPageSettings = document.getElementById( 'landing_settings' );
		if ( null === landingPageSettings ) {
			return;
		}

		fixPageUrlIndentation();
		debouncedValidate = debounce( validatePageName, 500 );
		getPageNameInput().addEventListener( 'input', handlePageUrlInput, false );
		setUpIndentFixOnTabClick();
	}

	function fixPageUrlIndentation() {
		getPageNameInput().style.textIndent = ( document.getElementById( 'frm_landing_page_url_input_wrapper' ).firstElementChild.offsetWidth ) + 'px';
	}

	function handlePageUrlInput() {
		debouncedValidate();
		setPageValidationResult( '' );
	}

	function setPageValidationResult( html ) {
		document.getElementById( 'frm_landing_page_url_validation' ).innerHTML = html;
	}

	function setUpAutoFocusPageUrlOnToggle() {
		var checkbox = document.getElementById( 'frm_landing_toggle' );
		checkbox.addEventListener(
			'change',
			function() {
				var urlInput, url;
				if ( this.checked ) {
					setTimeout( fixPageUrlIndentation, 0 );
					urlInput = document.querySelector( 'input[name="frm_landing_page_url"]' );
					url = urlInput.value;
					urlInput.focus();
					urlInput.value = '';
					urlInput.value = url;

					validatePageName();
				}
			}
		);
	}

	function getPageNameInput() {
		return document.querySelector( 'input[name="frm_landing_page_url"]' );
	}

	function validatePageName() {
		var input, pageName;

		input = getPageNameInput();
		pageName = input.value.trim();

		if ( activeValidationXHR && 'function' === typeof activeValidationXHR.abort ) {
			activeValidationXHR.abort();
		}

		if ( ! pageName.length ) {
			return;
		}

		if ( pageName === input.getAttribute( 'original-page-url' ) ) {
			return;
		}

		activeValidationXHR = post(
			{
				action: 'frm_validate_landing_page_url',
				pageName: pageName,
				nonce: frmGlobal.nonce,
				formId: document.getElementById( 'form_id' ).value
			},
			function( response ) {
				if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.html ) {
					setPageValidationResult( response.data.html );
				}
			}
		);
	}

	function setUpIndentFixOnTabClick() {
		var anchor = document.querySelector( 'a[href="#landing_settings"]' );
		if ( null === anchor ) {
			return;
		}
		anchor.addEventListener(
			'click',
			function() {
				setTimeout( fixPageUrlIndentation, 0 );
			},
			false
		);
	}

	/**
	 * Show the container when the button inside is shown.
	 */
	function setUpRemoveImage() {
		var removeLink = document.querySelector( '.frm_remove_image_option' );
		if ( null === removeLink ) {
			return;
		}
		removeLink.addEventListener(
			'click',
			function() {
				// Delay so other actions are done.
				setTimeout( showUploadImage, 0 );
			},
			false
		);
	}

	function showUploadImage() {
		document.getElementById( 'frm_choose_image_box_cont' ).classList.remove( 'frm_hidden' );
	}

	function addListeners() {
		const toggle = document.getElementById( 'frm_landing_toggle' );
		if ( toggle ) {
			toggle.addEventListener(
				'change',
				function( event ) {
					const target = document.getElementById( 'hide_landing_page' );
					if ( target ) {
						target.classList.toggle( 'frm_hidden', ! event.target.checked );
						const deleteLandingPostLink = document.getElementById( 'delete_landing_page_post' );
						if ( ! deleteLandingPostLink ) {
							return;
						}
						const hasLandingPost = deleteLandingPostLink.dataset.hasLandingPost;
						deleteLandingPostLink.classList.toggle( 'frm_hidden', event.target.checked || ! hasLandingPost );
					}
				}
			);
		}
	}

	function post( data, success ) {
		var xmlHttp, params, response;

		xmlHttp = new XMLHttpRequest();

		params = typeof data === 'string' ? data : Object.keys( data ).map(
			function( k ) {
				return encodeURIComponent( k ) + '=' + encodeURIComponent( data[k]);
			}
		).join( '&' );

		xmlHttp.open( 'post', ajaxurl, true );
		xmlHttp.onreadystatechange = function() {
			if ( xmlHttp.readyState > 3 && xmlHttp.status == 200 ) {
				response = xmlHttp.responseText;
				try {
					response = JSON.parse( response );
				} catch ( e ) {
					// The response may not be JSON, so just return it.
				}
				success( response );
			}
		};
		xmlHttp.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
		xmlHttp.setRequestHeader( 'Content-type', 'application/x-www-form-urlencoded' );
		xmlHttp.send( params );
		return xmlHttp;
	}

	function debounce( func, wait = 100 ) {
		let timeout;
		return function( ...args ) {
			clearTimeout( timeout );
			timeout = setTimeout(
				() => func.apply( this, args ),
				wait
			);
		};
	}

	setUpLandingPageUrlValidation();
	setUpAutoFocusPageUrlOnToggle();
	setUpRemoveImage();
	addListeners();
}() );

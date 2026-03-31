( function ( jQuery ) {
	function addEventListeners() {
		document.addEventListener( 'change', handleChangeEvent );
		document.addEventListener( 'keydown', handleKeyDownEvent );
		document.addEventListener( 'click', handleClickEvent );

		if ( document.getElementById( 'frm_single_entry_type' ) ) {
			// This script will also load in the form builder if a toggle field is added.
			// So avoid this listener if we're on another page.
			jQuery( document ).on( 'frm-multiselect-changed', function ( _, option ) {
				toggleSingleEntrySettings( option.value );
			} );
		}
	}

	function handleDisabledRolesClick() {
		document
			.querySelectorAll( '.frm_permissions_settings_settings .multiselect-container' )
			.forEach( ( msContainer ) => {
				const msOptions = Array.from( msContainer.children );
				msContainer.querySelectorAll( '.frm_disabled_option' ).forEach( ( disabledOption ) => {
					disabledOption.querySelector( 'input[type="checkbox"]' ).setAttribute( 'disabled', true );
					disabledOption.addEventListener( 'click', ( e ) => {
						e.stopPropagation();
						const index = msOptions.indexOf( disabledOption );
						// Click on the respective option element from the original 'select' element.
						msContainer.parentElement.parentElement
							.querySelector( `select option:nth-child(${ index + 1 })` )
							.click();
					} );
				} );
			} );
	}

	function handleDomReady() {
		// Remove the event listener in Lite that toggles the Cookie expiration JS.
		setTimeout( function () {
			jQuery( document.getElementById( 'single_entry' ) ).off( 'change' );
			jQuery( document.getElementById( 'frm_single_entry_type' ) ).off( 'change' );
			handleDisabledRolesClick();
			initGDPRSettingsHandlers();
		}, 0 );

		// Init color pickers.
		if ( 'function' === typeof jQuery.fn.wpColorPicker ) {
			jQuery( '.frm_settings_form input.hex' ).wpColorPicker( {
				change( event, ui ) {
					let color = jQuery( this ).wpColorPicker( 'color' );
					if ( ui.color._alpha < 1 ) {
						// If there's transparency, use RGBA
						color = ui.color.toCSS( 'rgba' );
					}

					const wrapper = event.target.closest( '.frm-colorpicker' );
					if ( wrapper ) {
						wrapper.querySelector( '.wp-color-result-text' ).innerText = color.replaceAll( ' ', '' );
					}

					jQuery( event.target ).val( color ).trigger( 'change' );
				},
			} );
		}

		jQuery( '.frm_settings_form .wp-color-result-text' ).text( function ( _, oldText ) {
			const container = jQuery( this ).closest( '.wp-picker-container' );
			if (
				'undefined' !== typeof container &&
				container[ 0 ].parentElement.classList.contains( 'frm-colorpicker' )
			) {
				return container[ 0 ].querySelector( '.wp-color-picker' ).value;
			}
			return oldText === 'Select Color' ? 'Select' : oldText;
		} );
	}

	/**
	 * @param {Event} event
	 * @return {void}
	 */
	function handleChangeEvent( event ) {
		if (
			'INPUT' === event.target.nodeName &&
			'checkbox' === event.target.type &&
			event.target.parentNode.classList.contains( 'frm_switch_block' )
		) {
			handleToggleChangeEvent( event );
			return;
		}

		switch ( event.target.id ) {
			case 'single_entry':
				handleSingleEntry( event );
				break;
			case 'frm_single_entry_type':
				handleSingleEntryType();
				break;
		}
	}

	/**
	 * @param {Event} e
	 * @return {void}
	 */
	function handleKeyDownEvent( e ) {
		switch ( e.key ) {
			case ' ':
				handleSpaceDownEvent( e );
				break;
		}
	}

	/**
	 * @param {Event} e
	 * @return {void}
	 */
	function handleToggleChangeEvent( e ) {
		e.target.nextElementSibling.setAttribute( 'aria-checked', e.target.checked ? 'true' : 'false' );
	}

	/**
	 * @param {Event} e
	 * @return {void}
	 */
	function handleSpaceDownEvent( e ) {
		if ( e.target.classList.contains( 'frm_switch' ) ) {
			e.target.click();
		}
	}

	/**
	 * @param {Event} e
	 * @return {void}
	 */
	function handleSingleEntry( e ) {
		if ( e.target.checked ) {
			showElementsWithClassName( 'frm-single-entry-setting', 'frm_invisible' );
			handleSingleEntryType();
			return;
		}

		hideElementsWithClassName( 'frm-single-entry-setting', 'frm_invisible' );
		hideElementsWithClassName( 'frm-single-entry-type-email-setting' );
		hideElementsWithClassName( 'frm-single-entry-type-cookie-setting' );
	}

	/**
	 * @return {void}
	 */
	function handleSingleEntryType() {
		toggleSingleEntrySettings( 'email' );
		toggleSingleEntrySettings( 'cookie' );
	}

	/**
	 * @param {string} type
	 * @return {void}
	 */
	function toggleSingleEntrySettings( type ) {
		const className = 'frm-single-entry-type-' + type + '-setting';
		if ( singleEntryTypeSettingIsSelected( type ) ) {
			showElementsWithClassName( className );
		} else {
			hideElementsWithClassName( className );
		}
	}

	/**
	 * @param {string} setting
	 * @return {boolean} True if setting is selected, false otherwise.
	 */
	function singleEntryTypeSettingIsSelected( setting ) {
		const input = document.getElementById( 'frm_single_entry_type' );
		const types = jQuery( input ).val();
		return -1 !== types.indexOf( setting );
	}

	/**
	 * @param {string} className
	 * @return {Array} Array of elements with the specified class name.
	 */
	function getArrayWithClassName( className ) {
		return Array.from( document.getElementsByClassName( className ) );
	}

	/**
	 * @param {string} className
	 * @param {string} classNameToRemove
	 * @return {void}
	 */
	function showElementsWithClassName( className, classNameToRemove = 'frm_hidden' ) {
		getArrayWithClassName( className ).forEach( ( element ) => element.classList.remove( classNameToRemove ) );
	}

	/**
	 * @param {string} className
	 * @param {string} classNameToAdd
	 * @return {void}
	 */
	function hideElementsWithClassName( className, classNameToAdd = 'frm_hidden' ) {
		getArrayWithClassName( className ).forEach( ( element ) => element.classList.add( classNameToAdd ) );
	}

	/**
	 * @return {void}
	 */
	function initGDPRSettingsHandlers() {
		const gdprSettingsWrapper = document.querySelector( '.frm-gdpr-related-setting' );
		if ( ! gdprSettingsWrapper ) {
			return;
		}

		const gdprRelatedSettings = gdprSettingsWrapper.querySelectorAll( 'button.multiselect-option' );
		const gdprDisabledMessage = gdprSettingsWrapper.getAttribute( 'data-frm-gdpr-disabled-message' );
		if ( ! gdprRelatedSettings.length || ! gdprDisabledMessage ) {
			return;
		}

		const handleDisabledSetting = ( event ) => {
			const setting = event.currentTarget;
			if ( ! setting.classList.contains( 'disabled' ) ) {
				return;
			}

			const settingsUrl = frmGlobal.applicationsUrl.replace(
				'?page=formidable-applications',
				'?page=formidable-settings'
			);
			const message = gdprDisabledMessage
				.replace( '%1$s', '<a href="' + settingsUrl + '">' )
				.replace( '%2$s', '</a>' );

			frmAdminBuild.infoModal( message );
			event.preventDefault();
			event.stopPropagation();
		};

		gdprRelatedSettings.forEach( ( setting ) => {
			setting.addEventListener( 'click', handleDisabledSetting );
		} );
	}

	function imageUploadSetting() {
		let mediaUploader;

		const handleUpload = ( e ) => {
			// If the uploader object has already been created, reopen the dialog.
			if ( mediaUploader ) {
				mediaUploader.open();
				return;
			}

			// Create the media uploader.
			mediaUploader = wp.media( {
				title: wp.i18n.__( 'Select Image', 'formidable-pro' ),
				button: {
					text: wp.i18n.__( 'Use this image', 'formidable-pro' ),
				},
				multiple: false,
			} );

			// When an image is selected, run a callback.
			mediaUploader.on( 'select', function () {
				const wrapper = e.target.closest( '.frm-image-upload-setting' );
				const previewWrapper = wrapper.querySelector( '.frm-image-upload-preview' );
				const idInput = wrapper.querySelector( '.frm-image-id-input' );
				let previewImg = wrapper.querySelector( '.frm-image-upload-preview-img' );

				const attachment = mediaUploader.state().get( 'selection' ).first().toJSON();

				idInput.value = attachment.id;

				if ( previewImg ) {
					previewImg.src = attachment.sizes.thumbnail.url;
				} else {
					previewImg = frmDom.img( {
						src: attachment.sizes.thumbnail.url,
						alt: attachment.alt,
						className: 'frm-image-upload-preview-img',
					} );

					previewWrapper.prepend( previewImg );
				}

				wrapper.classList.add( 'frm-has-uploaded-image' );
			} );

			// Open the uploader dialog.
			mediaUploader.open();
		};

		const handleRemove = ( e ) => {
			const wrapper = e.target.closest( '.frm-image-upload-setting' );
			const idInput = wrapper.querySelector( '.frm-image-id-input' );
			const previewImg = wrapper.querySelector( '.frm-image-upload-preview-img' );

			idInput.value = '';

			if ( previewImg ) {
				previewImg.remove();
			}

			wrapper.classList.remove( 'frm-has-uploaded-image' );
		};

		document.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'frm-upload-image-btn' ) ) {
				handleUpload( e );
				return;
			}

			if ( e.target.classList.contains( 'frm-remove-image-btn' ) ) {
				handleRemove( e );
			}
		} );
	}

	function handleClickResetColor( event ) {
		const values = JSON.parse( event.target.dataset.values );
		const wrapper = event.target.parentElement;

		Object.keys( values ).forEach( ( key ) => {
			const input = wrapper.querySelector( '[name="frm_' + key + '"]' );
			if ( ! input ) {
				return;
			}

			input.value = values[ key ];
			input.closest( '.frm-colorpicker' ).querySelector( '.wp-color-result-text' ).innerText = values[ key ];
			input.dispatchEvent( new Event( 'change' ) );
		} );
	}

	/**
	 * @param {Event} event
	 * @return {void}
	 */
	function handleClickEvent( event ) {
		if ( event.target.classList.contains( 'frm-reset-colors-btn' ) ) {
			handleClickResetColor( event );
		}
	}

	addEventListeners();
	imageUploadSetting();

	document.addEventListener( 'DOMContentLoaded', handleDomReady );
} )( jQuery );

/* global jQuery, frmDom, ajaxurl, frmgooglespreadsheetGlobal */
// Var frmGoogleSheetHelper, frmGoogleSheetAuthorizationHandler, frmGoogleSheetActionHandler

( function( $ ) {
	const { __ } = wp.i18n;

	const frmGoogleSheetHelper = {

		/**
		 * AJAX call.
		 *
		 * @param {Object} d
		 * @param {Object} success
		 * @since 1.0.0
		 */
		ajax( d, success ) {
			const data = {
				security: frmgooglespreadsheetGlobal.nonce,
			};

			$.extend( data, d );
			$.post( ajaxurl, data, function( result ) {
				success( result );
			} ).fail( function() {
			} );
		},

		/**
		 * Create a modal.
		 *
		 * @param {string}  title
		 * @param {string}  messages
		 * @param {Element} element
		 * @since 1.0.0
		 */
		createModal( title, messages, element = null ) {
			frmDom.modal.maybeCreateModal(
				'frm_authorization_modal',
				{
					title,
					content: this.getModalContent( messages ),
					footer: this.getModalFooter( element ),
				}
			);
		},
		/**
		 * Modal content.
		 *
		 * @param {string|Array} messages
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getModalContent( messages ) {
			var outputMessages = messages;

			if ( Array.isArray( messages ) ) {
				Array.prototype.forEach.call( messages, function( message ) {
					outputMessages = frmDom.div( message );
				} );
			} else {
				outputMessages = frmDom.div( messages );
			}

			return frmDom.div( {
				className: 'inside',
				children: [
					outputMessages,
				],
			} );
		},
		/**
		 * Get modal footer.
		 *
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getModalFooter() {
			return frmDom.div( {
				children: [
					frmDom.modal.footerButton( {
						text: __( 'Dismiss', 'formidable-google-sheets' ),
						buttonType: 'primary',
					} ),
				],
			} );
		},
	};

	var frmGoogleSheetAuthorizationHandler = {
		/**
		 * Dom ready in setting page.
		 *
		 * @since 1.0.0
		 */
		domReady() {
			// Bail if this is settings page.
			if ( ! document.getElementById( 'googlespreadsheet_settings' ) ) {
				return;
			}

			$( 'body' ).on( 'click', '#frm_top_bar input', function( e ) {
				const authButton = document.querySelector( '.formidable_googlespreadsheet_authorization' );
				if ( ! document.querySelector( '.frm-tabs.active a[href="#googlespreadsheet_settings"]' ) || ! authButton || ! frmGoogleSheetAuthorizationHandler.fieldChanges() ) {
					return;
				}
				frmGoogleSheetAuthorizationHandler.authorization( authButton, e );
			} );

			$( 'body' ).on( 'click', '.formidable_googlespreadsheet_authorization', function( e ) {
				frmGoogleSheetAuthorizationHandler.authorization( this, e );
			} );

			$( 'body' ).on( 'click', '.formidable_googlespreadsheet_deauthorize', function( e ) {
				frmGoogleSheetAuthorizationHandler.revoke( this, e );
			} );

			document.getElementById( 'frm_googlespreadsheet_client_id' ).addEventListener( 'input', function( e ) {
				frmGoogleSheetAuthorizationHandler.fieldChanges( this, e );
			} );

			document.getElementById( 'frm_googlespreadsheet_client_secret' ).addEventListener( 'input', function( e ) {
				frmGoogleSheetAuthorizationHandler.fieldChanges( this, e );
			} );

			this.fieldChanges();
		},
		/**
		 * Get client id field element.
		 *
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getClientID() {
			return document.getElementById( 'frm_googlespreadsheet_client_id' );
		},
		/**
		 * Get secret field element.
		 *
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getClientSecret() {
			return document.getElementById( 'frm_googlespreadsheet_client_secret' );
		},
		/**
		 * Get authorize buttons.
		 *
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getButton() {
			return document.querySelector( '#frm-google-sheets-connect-btns a' );
		},
		/**
		 * Toggle fields on type.
		 *
		 * @since 1.0.0
		 * @return {boolean} Whether fields are empty.
		 */
		fieldChanges() {
			if ( '' !== this.getClientID().value && '' !== this.getClientSecret().value ) {
				this.toggleVisibilityAuthorizeButton( 'show' );
				return true;
			}

			this.toggleVisibilityAuthorizeButton( 'hide' );
			return false;
		},
		/**
		 * Toggle button on type.
		 *
		 * @param {string} toggle
		 * @since 1.0.0
		 */
		toggleVisibilityAuthorizeButton( toggle ) {
			if ( 'show' === toggle ) {
				document.getElementById( 'frm-google-sheets-connect-btns' ).classList.remove( 'frm_hidden' );
				return;
			}

			document.getElementById( 'frm-google-sheets-connect-btns' ).classList.add( 'frm_hidden' );
		},
		/**
		 * Change button to authorize and unauthorize.
		 *
		 * @param {Object} args
		 * @since 1.0.0
		 */
		toggleBehaviorButton( args ) {
			const btn = this.getButton();
			btn.text = args.btn_text;
			btn.href = args.auth_url;
			btn.setAttribute( 'class', args.class );
		},
		/**
		 * Enable or disable the fields based on button.
		 *
		 * @param {string} clientID
		 * @param {string} clientSecret
		 * @param {string} mode
		 * @since 1.0.0
		 */
		toggleAuthorizeFields( clientID, clientSecret, mode = 'enable' ) {
			this.getClientID().value = clientID;
			this.getClientSecret().value = clientSecret;

			if ( 'enable' === mode ) {
				this.getClientID().removeAttribute( 'disabled' );
				this.getClientSecret().removeAttribute( 'disabled' );
			} else {
				this.getClientID().setAttribute( 'disabled', '' );
				this.getClientSecret().setAttribute( 'disabled', '' );
			}
		},
		/**
		 * Try to authorize the Google Sheets based on provided keys.
		 *
		 * @param {*} element
		 * @param {*} e
		 * @since 1.0.0
		 */
		authorization( element, e ) {
			e.preventDefault();
			const $this = $( element );

			const data = {
				action: 'formidable_googlespreadsheet_authorization',
				task: 'get_auth_url',
				client_id: this.getClientID().value.trim(),
				client_secret: this.getClientSecret().value.trim(),
			};

			frmGoogleSheetHelper.ajax( data, function( result ) {
				if ( false === result.error ) {
					const authUrl = result.response.authorization_data.auth_url;
					$this.attr( 'href', result.response.authorization_data.auth_url );
					frmGoogleSheetAuthorizationHandler._googleCloudConsoleModal.call( $this, authUrl );
				}
			} );
		},
		/**
		 * Deliver the code from Google API on success or throws the error.
		 *
		 * @since 1.0.0
		 * @param {string} authUrl
		 */
		_googleCloudConsoleModal( authUrl ) {
			const win = window.open( authUrl, 'formidablegooglespreadsheetauthwindow', 'width=1000, height=600' );

			window.addEventListener( 'message', eventListener, false );

			function eventListener( event ) {
				if ( ! authSetupIsComplete( event ) ) {
					return;
				}

				window.removeEventListener( 'message', eventListener );
				win.close();

				// We don't have an access token yet, have to go to the server for it.
				frmGoogleSheetHelper.ajax(
					{
						action: 'formidable_googlespreadsheet_authorization',
						auth_code: event.data.code,
						task: 'code_exchange',
						client_id: frmGoogleSheetAuthorizationHandler.getClientID().value,
						client_secret: frmGoogleSheetAuthorizationHandler.getClientSecret().value,
					},
					( result ) => {
						// We need to disable the fields and put asterisks instead of client ID and secret.
						if ( false === result.error ) {
							frmGoogleSheetHelper.createModal( __( 'Success!', 'formidable-google-sheets' ), result.response.message );
							frmGoogleSheetAuthorizationHandler.toggleAuthorizeFields( result.response.client_id, result.response.client_secret, 'disable' );
							frmGoogleSheetAuthorizationHandler.toggleBehaviorButton( result.response.toggle_button_deauthorize );
						} else {
							frmGoogleSheetHelper.createModal( __( 'Authorization failed', 'formidable-google-sheets' ), result.response.message );
						}
					}
				);
			}

			/**
			 * Confirm that a message event is the event we're expecting.
			 *
			 * @param {Event} event
			 * @return {boolean} True if the event is coming from this site and has the expected data.
			 */
			function authSetupIsComplete( event ) {
				if ( 0 !== event.origin.indexOf( frmgooglespreadsheetGlobal.homeURL ) ) {
					return false;
				}

				if ( 'object' !== typeof event.data || 'Google Sheets Connected' !== event.data.message || ! event.data.code ) {
					return false;
				}

				return true;
			}
		},
		/**
		 * Terminate the connection between Google API.
		 *
		 * @since 1.0.0
		 * @param {*} element
		 * @param {*} e
		 */
		revoke( element, e ) {
			e.preventDefault();
			const data = {
				action: 'formidable_googlespreadsheet_revoke',
			};
			frmGoogleSheetHelper.ajax( data, function( result ) {
				// We need to disable the fields and put asterisks instead of client ID and secret.
				if ( false === result.error ) {
					frmGoogleSheetHelper.createModal( __( 'Success!', 'formidable-google-sheets' ), result.response.message );
				} else {
					frmGoogleSheetHelper.createModal( __( 'Deauthorization failed', 'formidable-google-sheets' ), result.response.message );
				}
				frmGoogleSheetAuthorizationHandler.toggleAuthorizeFields( result.response.client_id, result.response.client_secret );
				frmGoogleSheetAuthorizationHandler.toggleBehaviorButton( result.response.toggle_button_authorize );
			} );
		},
	};

	var frmGoogleSheetActionHandler = {
		/**
		 * Dom ready for forms action.
		 *
		 * @since 1.0.0
		 */
		domReady() {
			// Form action dom events
			document.addEventListener( 'change', ( event ) => {
				if ( event.target === document.querySelector( '.frm_single_googlespreadsheet_settings [name$="[spreadsheet_id]"]' ) ) {
					frmGoogleSheetActionHandler.getFiles( event.target );
				}
				if ( event.target === document.querySelector( '.frm_single_googlespreadsheet_settings select[name$="[sheet_id]"]' ) ) {
					frmGoogleSheetActionHandler.getSheets( event.target );
				}
			} );

			wp.hooks.addAction( 'frm_autocomplete_select', 'frmGoogleSpreadsheet', function( e, ui, element ) {
				if ( element.nextElementSibling && element.nextElementSibling.name.match( /frm_googlespreadsheet_action\[\d+]\[post_content]\[spreadsheet_id]/ ) ) {
					frmGoogleSheetActionHandler.getFiles( element.nextElementSibling );
				}
			} );

			if ( parseInt( frmgooglespreadsheetGlobal.initAutocompleteOnNewActionHook ) ) {
				wp.hooks.addAction( 'frm_added_form_action', 'frmGoogleSpreadsheet', function( newAction ) {
					if ( newAction.classList.contains( 'frm_single_googlespreadsheet_settings' ) ) {
						frmDom.autocomplete.initAutocomplete( 'custom', newAction );
					}
				} );
			}

			document.addEventListener( 'click', ( event ) => {
				if ( event.target === document.getElementById( 'clrcache-googlespreadsheet' ) ) {
					frmGoogleSheetActionHandler.clearCache( event.target, event );
				}
				if ( event.target === document.querySelector( '.sync-google-spreadsheet' ) ) {
					frmGoogleSheetActionHandler.handleSendEntries( event.target );
				}
			} );
		},
		/**
		 * Get spread sheet element.
		 *
		 * @param {Element} element
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getSpreadSheetID( element ) {
			return element.closest( '.form-table' ).querySelector( '[name$="[spreadsheet_id]"]' ).value;
		},
		/**
		 * Get sheet element.
		 *
		 * @param {Element} element
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getSheetID( element ) {
			return element.closest( '.form-table' ).querySelector( 'select[name$="[sheet_id]"]' ).value;
		},
		/**
		 * Get mapped field element.
		 *
		 * @param {Element} element
		 * @since 1.0.0
		 * @return {Element} element.
		 */
		getMappedFields( element ) {
			const mappedFields = element.closest( '.form-table' ).querySelectorAll( '.frm_googlespreadsheet_fields input' );
			const mappedValues = [];
			mappedFields.forEach( function( item ) {
				mappedValues.push( item.value );
			} );
			return mappedValues;
		},
		/**
		 * Get files from Google API.
		 *
		 * @since 1.0.0
		 * @param {Element} element
		 */
		getFiles( element ) {
			const id = element.value;
			// check if there is no option field.
			if ( id === '' ) {
				return;
			}

			const fileElement = element.closest( '.form-table' ).querySelector( 'select[name$="[sheet_id]"]' );
			const spinner = element.closest( '.form-table' ).querySelector( '.clrcache-googlespreadsheet-spinner' );

			spinner.style.visibility = 'visible';

			const data = {
				action: 'frm_googlespreadsheet_get_sheets',
				spreadsheet_id: id,
			};

			frmGoogleSheetHelper.ajax( data, function( result ) {
				const sheets = jQuery.parseJSON( result );
				if ( sheets !== '' ) {
					fileElement.innerHTML = ''; // remove old options
					let length = 0;
					jQuery.each( sheets, function( key, obj ) {
						length++;
						fileElement.innerHTML += '<option value="' + obj.id + '">' + obj.id + '</option>';
					} );

					if ( length > 1 ) {
						fileElement.parentNode.classList.remove( 'frm_hidden' );
					} else {
						fileElement.parentNode.classList.add( 'frm_hidden' );
					}

					frmGoogleSheetActionHandler.getSheets( fileElement, null );
				}
				spinner.style.visibility = 'hidden';
			} );
		},
		/**
		 * Get sheets of the selected file.
		 *
		 * @since 1.0.0
		 * @param {Element} element
		 */
		getSheets( element ) {
			const formID = document.querySelector( 'input[name="id"]' ).value;
			const sheetID = this.getSpreadSheetID( element );
			const key = element.closest( '.frm_single_googlespreadsheet_settings' ).getAttribute( 'data-actionkey' );
			const div = document.querySelector( '.frm_googlespreadsheet_fields_' + key );
			div.innerHTML = '<span class="spinner" style="float: none; visibility: visible;"></span>';

			const data = {
				action: 'frm_googlespreadsheet_match_fields',
				form_id: formID,
				sheet_id: element.value,
				spreadsheet_id: sheetID,
				action_key: key,
			};

			frmGoogleSheetHelper.ajax( data, function( html ) {
				div.innerHTML = html;
				jQuery( document ).trigger( 'frmElementAdded', [ '.frm_googlespreadsheet_fields_' + key ] );
			} );
		},
		/**
		 * Confirmation modal for send entries.
		 *
		 * @param {Element} element
		 * @since 1.0.0
		 */
		handleSendEntries( element ) {
			document.getElementById( 'spreadsheet_sync_spinner-' + element.getAttribute( 'data-actionid' ) ).style.visibility = 'visible';
			frmGoogleSheetHelper.createModal.call( this, __( 'Heads up!', 'formidable-google-sheets' ), __( 'This will export all existing entries to the selected Google sheet and may cause duplication. Do you want to proceed?', 'formidable-google-sheets' ), element );
		},
		/**
		 * Send entries to Google API.
		 *
		 * @param {Element} element
		 * @since 1.0.0
		 */
		sendEntries( element ) {
			const formId = document.querySelector( 'input[name="id"]' ).value;
			const actionId = element.closest( '.frm_single_googlespreadsheet_settings' ).getAttribute( 'data-actionkey' );
			const spinner = document.getElementById( 'spreadsheet_sync_spinner-' + actionId );

			const data = {
				action: 'sync_entries_google_spreadsheet',
				formid: formId,
				actionid: actionId,
				sheet_id: this.getSheetID( element ),
				spreadsheet_id: this.getSpreadSheetID( element ),
				mapped_fields: this.getMappedFields( element ),
			};
			// Start the process
			frmGoogleSheetActionHandler.process_step( 1, data, self, element, spinner );
		},
		/**
		 * Process the steps of sending.
		 *
		 * @since 1.0.0
		 * @param {string}  step
		 * @param {Object}  data
		 * @param {Object}  self
		 * @param {Element} element
		 * @param {Element} spinner
		 */
		process_step( step, data, self, element, spinner ) {
			data.step = step;

			frmGoogleSheetHelper.ajax( data, function( result ) {
				if ( 'success' === result.response ) {
					if ( 'complete' !== result.step ) {
						frmGoogleSheetActionHandler.process_step( parseInt( result.step ), data, self, element, spinner );
					}
					$( '.spreadsheet_sync_result' ).text( result.processed ).show();
				} else if ( result.error_detail instanceof Object ) {
					$( '.spreadsheet_sync_result' ).text( JSON.stringify( result.error_detail ) ).show();
				} else {
					$( '.spreadsheet_sync_result' ).text( result.error_detail ).show();
				}
			} );
		},
		/**
		 * Clear the form cache.
		 *
		 * @since 1.0.0
		 * @param {Element} element
		 * @param {event}   e
		 */
		clearCache( element, e ) {
			e.preventDefault();
			const getFileSelector = element.closest( '.frm_single_googlespreadsheet_settings' ).querySelector( '[name$="[spreadsheet_id]"]' );
			document.querySelector( '.clrcache-googlespreadsheet-spinner' ).style.visibility = 'visible';

			const data = {
				action: 'clear_googlespreadsheet_files_cache',
			};

			frmGoogleSheetHelper.ajax( data, function() {
				document.querySelector( '.clrcache-googlespreadsheet-spinner' ).style.visibility = 'hidden';
				frmGoogleSheetActionHandler.getFiles( getFileSelector );
			} );
		},
		/**
		 * Action modal content.
		 *
		 * @since 1.0.0
		 * @param {string} message
		 * @return {Element} html of modal content.
		 */
		getModalContent( message ) {
			return frmDom.div( {
				className: 'inside',
				children: [
					frmDom.div( message ),
				],
			} );
		},
		/**
		 * Get action modal footer.
		 *
		 * @since 1.0.0
		 * @param {Element} element
		 * @return {Element} html of modal.
		 */
		getModalFooter( element ) {
			const cancelButton = frmDom.modal.footerButton( {
				text: __( 'Cancel', 'formidable-google-sheets' ),
				buttonType: 'cancel',
			} );

			cancelButton.classList.add( 'dismiss' );

			const startButton = frmDom.modal.footerButton( {
				text: __( 'Export Now', 'formidable-google-sheets' ),
				buttonType: 'primary',
			} );

			startButton.addEventListener(
				'click',
				function( event ) {
					event.preventDefault();
					frmGoogleSheetActionHandler.sendEntries( element );
				}
			);

			return frmDom.div( {
				children: [ startButton, cancelButton ],
			} );
		},
	};

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', frmGoogleSheetAuthorizationHandler.domReady() );
		document.addEventListener( 'DOMContentLoaded', frmGoogleSheetActionHandler.domReady() );
	} else {
		frmGoogleSheetAuthorizationHandler.domReady();
		frmGoogleSheetActionHandler.domReady();
	}
}( jQuery ) );

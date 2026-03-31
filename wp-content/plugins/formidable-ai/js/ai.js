( function() {
	/* globals __FRMAI, frmFrontForm, jQuery */

	let aiSubmitting = false;
	let lastRequest = [];
	let aiSettings = [];
	const xhrRequests = {};
	const fieldCache = new Map();

	if ( typeof jQuery !== 'undefined' ) {
		// Catch the Ajax submit if possible.
		jQuery( document ).on( 'frmPageChanged', function( e, form, response ) {
			if ( typeof __FRMAI  === 'undefined' || ! response.content.includes( 'frm_ai_response' ) ) {
				return;
			}
			maybeTriggerFirst();
		});
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		init();
	});

	document.addEventListener( 'frm_after_start_over', function( event ) {
		const aiResponseContainers = document.querySelectorAll( `#frm_form_${event.frmData.formId}_container form .frm_ai_response` );
		aiResponseContainers.forEach( el => {
			el.innerHTML = '';
			el.closest( '.frm_ai_field_container' ).classList.add( 'frm_none_container' );
		});
	});

	function init() {
		if ( typeof __FRMAI  === 'undefined' ) {
			return;
		}
		for ( let i = 0; i < __FRMAI.length; i++ ) {
			let watchedFields = __FRMAI[i].watch;
			const fieldId = __FRMAI[i].field;
			aiSettings[fieldId] = __FRMAI[i];
			lastRequest[fieldId] = [];

			for ( let i = 0; i < watchedFields.length; i++ ) {
				// Standardize the field name.
				let selector = watchedFields[i].replace( '#', '' );
				selector = selector.replace( '[id^="', '' ).replace( '-"]', '' ).replace( '_"]', '' );
				lastRequest[fieldId][selector] = '';
			}
			addListeners( fieldId );
		}
	}

	/**
	 * Trigger a check on the first watched field when the page is changed.
	 */
	function maybeTriggerFirst() {
		for ( let i = 0; i < __FRMAI.length; i++ ) {
			if ( __FRMAI[i].trigger ) {
				document.querySelectorAll( __FRMAI[i].watch ).forEach( function( el, index ) {
					if ( index === 0 ) {
						init();
						el.dispatchEvent( new Event( 'blur' ) );
					}
				});
			}
		}
	}

	function addListeners( fieldId ) {
		const settings = aiSettings[fieldId];
		const selectors = settings.watch.map( ( selector ) => {
			let id = selector[0] === '#' ? selector.slice( 1 ) : selector; // maybe remove # in front of the actual field id
			if ( ! id.startsWith( '[id^=' ) ) {
				id = `[id^="${id}"]`;
			}
			return `input${id}, select${id}, textarea${id}`;
		})
		.join( ',' );

		if ( ! selectors ) {
			return;
		}

		const watchedElements = document.querySelectorAll( selectors );

		// Watch for inline datepickers
		jQuery( document ).on( 'frmdates_date_changed', function( e, args ) {
			const dateIsWatched = triggerID =>  Array.from( watchedElements ).includes( document.querySelector( triggerID ) );
			const target = document.querySelector( args.datepickerOptions.altField );
			if ( target && dateIsWatched( args.triggerID ) ) {
				aiGetAnswer({ target }, fieldId );
			}
		});

		watchedElements.forEach( function( el, index ) {
			let allowed = [ 'INPUT', 'TEXTAREA', 'SELECT' ];
			if ( ! allowed.includes( el.tagName ) ) {
				return;
			}

			const isDateField = el.classList.contains( 'frm_date' );
			const event       = isDateField ? 'change' : 'blur';

			if ( isDateField ) {
				jQuery( el ).on( event, function( e ) {
					aiGetAnswer( e, fieldId );
				});
			} else {
				el.addEventListener( event, function( e ) {
					aiGetAnswer( e, fieldId );
				});
			}

			document.addEventListener( 'frmShowField', function() {
				// We don't know which field this is from, so check.
				el.dispatchEvent( new Event( event ) );
			});

			if ( settings.trigger ) {
				// Trigger a check on each watched field when the form is loaded.
				el.dispatchEvent( new Event( event ) );
			}

			if ( index === 0 ) {
				// Catch the submit event and trigger an API check if possible.
				el.closest( 'form' ).addEventListener( 'submit', function( e ) {
					const answerField = document.getElementById( settings.id );
					jQuery( document ).off( 'submit.formidable', '.frm-show-form', frmFrontForm.submitForm );
					if ( aiHasAnswer( answerField ) ) {
						jQuery( document ).on( 'submit.formidable', '.frm-show-form', frmFrontForm.submitForm );
					} else {
						e.preventDefault();

						aiSubmitting = e;
						el.dispatchEvent ( new Event( event ) );
						const hasEmptyValue = Object.values( lastRequest[fieldId]).some( function( v ) {
							if ( v.trim() === '' )  {
								return true;
							}
						});
						if ( hasEmptyValue ) {
							jQuery( document ).on( 'submit.formidable', '.frm-show-form', frmFrontForm.submitForm );
						}
					}
				});
			}
		});
	}

	function aiHasAnswer( answerField ) {
		if ( ! answerField || ! answerField.value ) {
			return false;
		}
		const defaultVal = answerField.getAttribute( 'data-frmval' );
		return defaultVal !== answerField.value;
	}

	function aiGetAnswer( e, fieldId ) {
		const target = e.target || e.srcElement;
		const fieldValue = getFieldVal( target );
		const fieldCall  = getStandardName( target.id, fieldId );

		if ( lastRequest[fieldId][ fieldCall ] === fieldValue || fieldValue === '' ) {
			// Don't check if the value hasn't changed.
			return;
		}

		const settings = aiSettings[fieldId];
		if ( isFieldConditionallyHidden( settings.field ) || typeof lastRequest[fieldId][fieldCall] === 'undefined' ) {
			// Don't check if the field is hidden or if it's not in the lastRequest array.
			return;
		}

		lastRequest[fieldId][ fieldCall ] = fieldValue;
		const hasEmptyValue = Object.values( lastRequest[fieldId]).some( function( v ) {
			return v.trim() === '';
		});

		const aiForm = target.closest( 'form' );

		if ( hasEmptyValue || aiIsSpam( aiForm ) ) {
			return;
		}

		const showAnswer = document.getElementById( 'frm_ai_response_' + fieldId );
		const answerField = document.getElementById( settings.id );

		// Show loading indicator.
		aiForm.classList.add( 'frm_loading_form' );
		aiShowLoader( showAnswer );

		const xhr = new XMLHttpRequest();
		if ( 'object' === typeof xhrRequests[ fieldId ] && xhrRequests[ fieldId ] instanceof XMLHttpRequest && 'function' === typeof xhrRequests[ fieldId ].abort ) {
			xhrRequests[ fieldId ].abort();
		}

		xhrRequests[ fieldId ] = xhr;

		const url = settings.ajax + ( settings.ajax.includes( '?' ) ? '&' : '?' ) + 'action=frm_ai_get_answer';

		xhr.open( 'post', url );
		xhr.onreadystatechange = function() {
			if ( xhr.readyState > 3 && xhr.status == 200 ) {
				let response = xhr.responseText;
				handleResponse( response, answerField, showAnswer, aiForm );
			}
		};

		xhr.setRequestHeader( 'X-Requested-With', 'XMLHttpRequest' );
		xhr.setRequestHeader( 'Content-Type', 'application/json' );

		let question = answerField.getAttribute( 'data-ai-question' );
		if ( ! question.trim() ) {
			question = Object.values( lastRequest[fieldId]).join( ' ' ).replace( /\n/g, ' ' );
		}

		xhr.send( JSON.stringify({
			question: replaceFieldShortcodes( question, answerField ),
			prompt: replaceFieldShortcodes( answerField.getAttribute( 'data-ai-prompt' ), answerField ),
			token: aiToken( aiForm ),
			id: answerField.getAttribute( 'data-form-id' ),
			field_id: answerField.name.replace( 'item_meta[', '', ).replace( ']', '' )
		}) );
	}

	/**
	 * Escape square brackets in a string.
	 *
	 * @param {string} inputString
	 * @returns {string}
	 */
	const escapeSquareBrackets = ( inputString ) => inputString.replace( /\[/g, '\\[' ).replace( /\]/g, '\\]' );

	/**
	 * If the AI field is in an embedded form, this function adds DOM selector to include such field.
	 *
	 * @param {string} selector
	 * @param {string} fieldKey
	 * @param {HTMLElement} answerField
	 * @return {string}
	 */
	const maybeAddSelectorForEmbeddedFields = ( selector, fieldKey, answerField ) => {
		const embeddedContainer = answerField.closest( '.frm_embed_form_container' );

		if ( ! embeddedContainer ) {
			return selector;
		}
		const getFieldIdFromContainerId = ( contId ) => {
			contId = contId.replace( 'frm_field_', '' );
			contId = contId.replace( '_container', '' );
			return contId;
		};

		const embeddedFieldID = getFieldIdFromContainerId( embeddedContainer.id );

		if ( embeddedFieldID ) {
			selector += `,[name="item_meta${escapeSquareBrackets( '[' + embeddedFieldID + '][0][' + fieldKey + ']' )}"]`;
		}

		return selector;
	};

	/**
	 * Fetch the field from the DOM based on the field key.
	 *
	 * @param {string} fieldKey
	 * @param {HTMLElement} answerField
	 * @returns {HTMLElement|null}
	 */
	const getFieldElement = ( fieldKey, answerField )  => {
		// Check the cache first
		if ( fieldCache.has( fieldKey ) ) {
			return fieldCache.get( fieldKey );
		}

		let selector = `
		[id=field_${fieldKey}],
		[id^=field_${fieldKey}-],
		[name=item_meta${escapeSquareBrackets( '[' + fieldKey + ']' )}], 
		[name=item_meta${escapeSquareBrackets( '[' + fieldKey + '][]' )}],
		[name=item_meta${escapeSquareBrackets( '[' + fieldKey + '][first]' )}],
		[name=item_meta${escapeSquareBrackets( '[' + fieldKey + '][middle]' )}],
		[name=item_meta${escapeSquareBrackets( '[' + fieldKey + '][last]' )}]
		`;

		selector = maybeAddSelectorForEmbeddedFields( selector, fieldKey, answerField );

		const fieldElement = document.querySelector( selector );

		// Cache the result
		fieldCache.set( fieldKey, fieldElement );

		return fieldElement;
	};

	/**
	 * Replace any field id/key shortcodes in a text.
	 *
	 * @param {string} text
	 * @param {HTMLElement} answerField
	 * @returns {string}
	 */
	const replaceFieldShortcodes = ( text, answerField ) => {
		const shortcodes = text.match( /\[[A-Za-z0-9]{1,5}\]/g  ) || [];
		let newText = text;

		for ( const shortcode of shortcodes ) {
			const fieldKey = shortcode.slice( 1, -1 );
			const fieldElement = getFieldElement( fieldKey, answerField );

			if ( fieldElement ) {
				const fieldValue = getFieldVal( fieldElement ).replace( /\n/g, ',' );
				newText = newText.replace( shortcode, fieldValue );
			}
		}

		return newText;
	};

	/**
	 * Get values for dropdowns, checkboxes, and radio buttons.
	 *
	 * @param {object} target
	 * @returns string
	 */
	function getFieldVal( target ) {
		let value = target.value;
		let checked = null;
		let comboContainer = target.parentElement.parentElement;

		if ( comboContainer.classList.contains( 'frm_combo_inputs_container' ) ) {
			value = '';
			comboContainer.querySelectorAll( 'input, select, textarea' ).forEach( function( el ) {
				value += ' ' + el.value;
			});
		} else if ( target.type === 'checkbox' || target.type === 'radio' ) {
			checked = document.querySelectorAll( '[name="' + target.name + '"]:checked' );
		} else if ( target.type === 'select' ) {
			checked = target.querySelectorAll( 'option:checked' );
		}

		if ( checked ) {
			value = '';
			checked.forEach( function( el ) {
				value += el.value + '\n';
			});
		}

		return value;
	}

	/**
	 * Get the standard field name from the field id.
	 *
	 * @param {string} fieldCall
	 * @param {number} fieldId
	 * @returns string
	 */
	function getStandardName( fieldCall, fieldId ) {
		if ( lastRequest[fieldId][fieldCall] === undefined ) {
			// Loop through existing request and find the key that matches fieldCall.
			for ( let key in lastRequest[fieldId]) {
				if ( fieldCall.startsWith( key ) ) {
					fieldCall = key;
					break;
				}
			}
		}
		return fieldCall;
	}

	function handleResponse( response, answerField, showAnswer, aiForm ) {
		if ( response !== '' ) {
			response = JSON.parse( response );
		}

		if ( typeof response.data === 'object' ) {
			answerField.value = Object.values( response.data ).join( ' \r\n' );
		} else if ( typeof response.data === 'array' ) {
			answerField.value = response.data.join( ' \r\n' );
		} else if ( response.data ) {
			answerField.value = response.data;
		}

		if ( showAnswer !== null ) {
			showAnswer.textContent = ''; // Remove loading indicator.
		}

		if ( response.success ) {
			answerField.dispatchEvent( new Event( 'change', {bubbles: true}) );
			if ( showAnswer === null && aiSubmitting ) {
				frmFrontForm.submitFormManual( aiSubmitting, aiForm );
			}
		}

		if ( showAnswer !== null ) {
			showAnswer.closest( '.frm_form_field' ).classList.remove( 'frm_none_container' );
			showAnswer.classList.add( 'frm_ai_answer' );

			if ( response.success ) {
				for ( let i = 0; i < response.data.length; i++ ) {
					let p = document.createElement( 'p' );

					const text  = document.createTextNode( response.data[i]);
					let content = text.textContent;

					// Convert markdown to HTML.
					content = content.replace( /\*\*(.*?)\*\*/g, '<strong>$1</strong>' );

					// A line that starts with * should be converted to <li>content</li>
					content = content.replace( /^\s*\*\s*(.*?)\s*$/gm, '<li>$1</li>' );

					// A line that starts with - should be converted to <li>content</li>
					content = content.replace( /^\s*-\s*(.*?)\s*$/gm, '<li>$1</li>' );

					// Convert lines that start with ### to <h3>content</h3>
					content = content.replace( /^\s*###\s*(.*?)\s*$/gm, '<h5>$1</h5>' );

					if ( content.includes( '<li>' ) ) {
						p = document.createElement( 'ul' );
					}

					p.innerHTML = content;

					showAnswer.appendChild( p );
				}

				// Merge any ul tags with a adjacent ul siblings.
				showAnswer.querySelectorAll( 'ul' ).forEach( function( ul ) {
					const prevSibling = ul.previousElementSibling;
					if ( prevSibling && prevSibling.tagName === 'UL' ) {
						prevSibling.append( ...ul.children );
						ul.remove();
					}
				});
			} else {
				const errorNote = document.createElement( 'div' );
				errorNote.classList.add( 'frm_error_style' );
				errorNote.innerHTML = response.data;
				showAnswer.appendChild( errorNote );
			}
		}

		aiForm.classList.remove( 'frm_loading_form' );
	}

	function aiShowLoader( showAnswer ) {
		if ( showAnswer !== null ) {
			const loader = showAnswer.querySelector( '.frm_ai_loading' );
			if ( loader !== null ) {
				loader.style.display = 'block';
			}
			const frmDefault = showAnswer.querySelector( '.frm_ai_default' );
			if ( frmDefault !== null ) {
				frmDefault.style.display = 'none';
			}
		}
	}

	function aiIsSpam( form ) {
		if ( aiIsHeadless() ) {
			return true;
		}
		let formID = form.parentElement.id.replace( 'frm_form_', '' ).replace( '_container', '' );
		let check = document.getElementById( 'frm_email_' + formID );
		if ( check === null ) {
			check = document.getElementById( 'frm_verify_' + formID );
		}
		return check !== null && check.value !== '';
	}

	function aiIsHeadless() {
		return (
			window._phantom || window.callPhantom || window.__phantomas ||
			window.Buffer || window.emit || window.spawn
		);
	}

	function aiToken( form ) {
		return form.getAttribute( 'data-token' );
	}

	function isFieldConditionallyHidden( fieldId ) {
		let container = document.getElementById( 'frm_field_' + fieldId + '_container' );
		return container && container.style.display === 'none';
	}
}() );

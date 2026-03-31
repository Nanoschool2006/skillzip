jQuery( document ).ready(
	function() {
		const $formActions = jQuery( document.getElementById( 'frm_notification_settings' ) );
		$formActions.on( 'click', '.frmapi_test_connection', frmapi_test_connection );
		$formActions.on( 'click', '.frmapi_insert_default_json', frmapi_insert_json );
		$formActions.on( 'click', '.frmapi_add_data_row', frmapi_add_data_row );
		$formActions.on( 'change', '.frmapi_data_format', frmapi_toggle_options );
	}
);

function frmapi_test_connection() {
	const settings     = this.closest( '.frm_single_api_settings' );
	const baseName     = 'frm_api_action[' +  settings.dataset.actionkey + '][post_content]';
	const url          = settings.querySelector( 'input[name="' + baseName + '[url]"]' ).value;
	const key          = settings.querySelector( 'input[name="' + baseName + '[api_key]"]' ).value;
	const format       = settings.querySelector( 'select[name="' + baseName + '[format]"]' ).value;
	const charset      = settings.querySelector( 'select[name="' + baseName + '[charset]"]' ).value;
	const testResponse = settings.querySelector( '.frmapi_test_resp' );
	const button       = this;

	if ( url === '' ) {
		settings.querySelector( '.frmapi_test_connection' ).textContent = 'Please enter a URL';
		return;
	}

	if ( url.indexOf( '[' ) !== -1 ) {
		testResponse.textContent = 'Sorry, Dynamic URLs cannot be tested';
		return;
	}

	button.classList.add( 'frm_loading_button' );
	testResponse.innerHTML = '';
	testResponse.classList.remove( 'frm_message', 'frm_error_style' );

	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: {
			action: 'frmapi_test_connection',
			url,
			key,
			format,
			charset,
			nonce: frmGlobal.nonce
		},
		success: function( response ) {
			if ( response.success ) {
				testResponse.classList.add( 'frm_message' );
			} else {
				testResponse.classList.add( 'frm_error_style' );
			}
			testResponse.innerHTML = response.data;
			button.classList.remove( 'frm_loading_button' );
		}
	});
}

function frmapi_insert_json() {
	const form_id  = jQuery( 'input[name="id"]' ).val();
	const settings = jQuery( this ).closest( '.frm_single_api_settings' );
	const key      = settings.data( 'actionkey' );
	const baseName = 'frm_api_action[' + key + '][post_content]';

	if ( form_id == '' ) {
		jQuery('textarea[name="'+baseName+'[data_format]"]').val('');
		return;
	}

	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: 'action=frmapi_insert_json&form_id=' + form_id,
		success: function( html ) {
			jQuery( 'textarea[name="' + baseName + '[data_format]"]' ).val( html );
		}
	});
}

function frmapi_add_data_row() {
	const table      = jQuery(this).closest('.frmapi_data_rows');
	const actionId   = jQuery(this).closest('.frm_form_action_settings').data('actionkey');
	const rowNum     = table.find('.frm_postmeta_row:last').attr('id').replace('frm_api_data_', '').replace( '_' + actionId, '' );
	const nextRowNum = parseInt( rowNum ) + 1;
	const newRow     = frmapiRowMarkup({
		id: actionId,
		row: nextRowNum
	});

	table.append( newRow );
	const addedRow = '#frm_api_data_' + nextRowNum + '_' + actionId;
	jQuery( document ).trigger( 'frmElementAdded', [ addedRow ] );
}

function frmapiRowMarkup( action ) {
	const removeIcon = frmDom.svg({ href: '#frm_minus1_icon' }).outerHTML;
	const addIcon    = frmDom.svg({ href: '#frm_plus1_icon' }).outerHTML;
	return `
	<div id="frm_api_data_${action.row}_${action.id}" class="frm_postmeta_row frm_grid_container">
		<div class="frm4 frm_form_field">
			<label class="screen-reader-text" for="frm_api_data_key_${action.row}_${action.id}">
				Name
			</label>
			<input type="text" value="" name="frm_api_action[${action.id}][post_content][data_fields][${action.row}][key]" id="frm_api_data_key_${action.row}_${action.id}" class="frm_not_email_message" />
		</div>
		<div class="frm7 frm_form_field">
			<label class="screen-reader-text" for="frm_api_data_value_${action.row}_${action.id}">
				Value
			</label>
			<input type="text" name="frm_api_action[${action.id}][post_content][data_fields][${action.row}][value]" value="" id="frm_api_data_value_${action.row}_${action.id}" class="frm_not_email_message" />
		</div>
		<div class="frm1 frm_form_field frm-inline-select">
			<a href="#" class="frm_remove_tag" data-removeid="frm_api_data_${action.row}_${action.id}">${removeIcon}</a>
			<a href="#" class="frm_add_tag frmapi_add_data_row">${addIcon}</a>
		</div>
	</div>`;
}

function frmapi_toggle_options() {
	const val      = this.value;
	const settings = jQuery(this).closest( '.frm_single_api_settings' );
	if ( val === 'raw' ) {
		settings.find( '.frm_data_raw' ).show();
		settings.find( '.frm_data_json' ).hide();
	} else {
		settings.find( '.frm_data_raw' ).hide();
		settings.find( '.frm_data_json' ).show();
	}
}

( function () {
	const { __ } = wp.i18n;
	const { maybeCreateModal, footerButton } = frmDom.modal;

	document.addEventListener('DOMContentLoaded', function() {
		document.querySelector( '.frm-refresh-api-key' )?.addEventListener( 'click', apiOnClickRefreshApiKey );
	});

	/**
	 * Generates footer for the modal that is shown on refresh api key action.
	 *
	 * @param {HTMLElement} refreshButton
	 * @returns {Void}
	 */
	function getModalFooter() {
		const continueButton = footerButton({
			text: __( 'Refresh Key', 'frmapi' ),
			buttonType: 'primary'
		});
		continueButton.classList.add( 'frm-button-red' );

		frmDom.util.onClickPreventDefault( continueButton, () => refreshAPIKey() );

		const cancelButton = footerButton({
			text: __( 'Cancel', 'frmapi' ),
			buttonType: 'cancel'
		});
		cancelButton.classList.add( 'dismiss' );

		return frmDom.div({
			children: [ continueButton, cancelButton ]
		});
	}

	/**
	 * Generates content for the modal that is shown on refresh api key action.
	 *
	 * @param {HTMLElement} refreshButton
	 * @returns {Void}
	 */
	function getModalContent( refreshButton ) {
		const modalContent = frmDom.div();
		modalContent.innerHTML = refreshButton.dataset.refreshMsg;
		modalContent.style.padding = 'var(--gap-md)';
		return modalContent;
	}

	/**
	 * Handler for click event on refresh api key button.
	 *
	 * @since 1.17
	 */
	function apiOnClickRefreshApiKey( event ) {
		event.preventDefault();

		maybeCreateModal(
			'frmRefreshAPIKey',
			{
				title: __( 'Refresh API Key', 'frmapi' ),
				content: getModalContent( event.currentTarget ),
				footer: getModalFooter()
			}
		);
	}

	/**
	 * Ajax Call to Manage the "Refresh API Key" Process.
	 * Triggered by the click event ".frm-refresh-api-key" in Global Settings/API
	 *
	 * @since 1.17
	 */
	function refreshAPIKey() {
		const { doJsonPost } = frmDom.ajax;
		const formData       = new FormData();

		formData.append( 'settings_action', 'refresh-api-key' );
		doJsonPost( 'api_settings_ajax', formData )
		.then( function( results ) {
			if ( results.api_key ) {
				const apiKeyInput = document.getElementById( 'frm-refresh-api-key-input' );
				if ( apiKeyInput ) {
					apiKeyInput.value = results.api_key;
				}
			}
		})
		.catch( error => console.error( error ) );
	}
}() );

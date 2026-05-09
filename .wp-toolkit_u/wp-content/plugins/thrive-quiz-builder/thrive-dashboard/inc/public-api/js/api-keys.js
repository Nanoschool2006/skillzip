/**
 * Thrive Dashboard - API Keys tab
 *
 * @package thrive-dashboard
 */
( function( $ ) {
	'use strict';

	var restUrl = TD_API_Keys.rest_url,
		nonce   = TD_API_Keys.nonce,
		i18n    = TD_API_Keys.i18n;

	/* ========== Tab Switching ========== */

	function switchTab( tab ) {
		$( '.td-tab-btn' ).removeClass( 'active' );
		$( '.td-tab-panel' ).removeClass( 'active' );

		$( '.td-tab-btn[data-tab="' + tab + '"]' ).addClass( 'active' );
		$( '#td-panel-' + tab ).addClass( 'active' );

		/* Remove max-width constraint on API Keys tab for full-width layout */
		if ( tab === 'api-keys' ) {
			$( '.td-tab-panel' ).closest( '.tvd-container' ).css( { 'max-width': 'none', 'width': 'calc(100% - 20px)' } );
		} else {
			$( '.td-tab-panel' ).closest( '.tvd-container' ).css( { 'max-width': '', 'width': '' } );
		}
	}

	$( document ).on( 'click', '.td-tab-btn', function() {
		switchTab( $( this ).data( 'tab' ) );
	} );

	/* Deep-link: if ?tab=api-keys is in URL, switch on load */
	if ( TD_API_Keys.tab === 'api-keys' ) {
		switchTab( 'api-keys' );
	}

	/* ========== Key Rendering ========== */

	function maskKey( key ) {
		if ( ! key || key.length <= 8 ) {
			return key || '';
		}
		return key.substring( 0, 4 ) + '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022' + key.substring( key.length - 4 );
	}

	function formatDate( dateStr ) {
		if ( ! dateStr ) {
			return '';
		}
		var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ],
			d      = new Date( dateStr );

		if ( isNaN( d.getTime() ) ) {
			return dateStr;
		}

		return months[ d.getMonth() ] + ' ' + d.getDate() + ', ' + d.getFullYear();
	}

	function buildKeyItem( token ) {
		var isActive    = !! token.status,
			dotClass    = isActive ? 'td-active' : 'td-inactive',
			statusLabel = isActive ? i18n.active : i18n.inactive,
			name        = $( '<span>' ).text( token.name || i18n.unnamed_key ).html(),
			masked      = $( '<span>' ).text( maskKey( token.key ) ).html(),
			fullKey     = $( '<span>' ).text( token.key || '' ).html(),
			created     = formatDate( token.created_at ),
			createdLabel = i18n.created,
			copyKeyLabel = i18n.copy_key,
			enableLabel  = i18n.enable,
			disableLabel = i18n.disable,
			deleteLabel  = i18n.delete;

		return '<div class="td-key-item' + ( isActive ? '' : ' td-key-inactive' ) + '" data-id="' + token.id + '" data-key="' + fullKey + '">' +
			'<div class="td-key-status-dot ' + dotClass + '"></div>' +
			'<div class="td-key-info">' +
				'<div class="td-key-name">' + name + '</div>' +
				'<div class="td-key-meta">' +
					( created ? '<span>' + createdLabel + ' ' + created + '</span>' : '' ) +
					'<span>' + statusLabel + '</span>' +
				'</div>' +
			'</div>' +
			'<div class="td-key-value-display">' +
				masked +
				' <button type="button" class="td-copy-btn td-copy-key" title="' + copyKeyLabel + '"><i class="tvd-icon-copy"></i></button>' +
			'</div>' +
			'<div class="td-key-actions">' +
				'<button type="button" class="td-toggle-btn">' + ( isActive ? disableLabel : enableLabel ) + '</button>' +
				'<button type="button" class="td-delete-btn">' + deleteLabel + '</button>' +
			'</div>' +
		'</div>';
	}

	function renderKeys( tokens ) {
		var $list    = $( '#td-keys-list' ),
			$empty   = $( '#td-empty-state' ),
			$intro   = $( '#td-keys-intro' ),
			$info    = $( '#td-info-notice' ),
			$loading = $( '#td-keys-loading' );

		$loading.hide();
		$list.empty();

		if ( ! tokens || tokens.length === 0 ) {
			$list.hide();
			$intro.hide();
			$info.hide();
			$empty.show();
			return;
		}

		$empty.hide();
		$intro.show();
		$info.show();
		$list.show();

		tokens.forEach( function( token ) {
			$list.append( buildKeyItem( token ) );
		} );
	}

	/* ========== REST API Operations ========== */

	function apiRequest( method, url, data ) {
		var opts = {
			url: url,
			method: method,
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', nonce );
			}
		};

		if ( data ) {
			opts.data = JSON.stringify( data );
			opts.contentType = 'application/json';
		}

		return $.ajax( opts );
	}

	function loadTokens() {
		$( '#td-keys-loading' ).show();
		$( '#td-keys-list' ).hide();
		$( '#td-empty-state' ).hide();

		apiRequest( 'GET', restUrl ).done( renderKeys ).fail( function() {
			$( '#td-keys-loading' ).html( '<p>' + i18n.fail_load + '</p>' );
		} );
	}

	/* ========== Modal ========== */

	var $modal = $( '#td-create-key-modal' ),
		$overlay = null;

	function openModal() {
		/* Reset form state */
		$( '#td-new-key-name' ).val( '' );
		$( '#td-save-new-key' ).prop( 'disabled', false ).text( i18n.generate );

		/* Create overlay if not exists */
		if ( ! $overlay ) {
			$overlay = $( '<div class="tvd-lean-overlay"></div>' );
			$overlay.on( 'click', closeModal );
		}

		$( 'body' ).append( $overlay );
		$overlay.css( { display: 'block', opacity: 0 } ).animate( { opacity: 0.5 }, 200 );

		$modal.css( {
			display: 'block',
			opacity: 0,
			top: '10%'
		} ).animate( { opacity: 1, top: '15%' }, 200 );

		/* Focus the name input */
		setTimeout( function() {
			$( '#td-new-key-name' ).focus();
		}, 250 );
	}

	function closeModal() {
		$modal.animate( { opacity: 0, top: '10%' }, 150, function() {
			$modal.css( 'display', 'none' );
		} );

		if ( $overlay ) {
			$overlay.animate( { opacity: 0 }, 150, function() {
				$overlay.detach();
			} );
		}
	}

	$( document ).on( 'click', '#td-create-key-btn, #td-create-first-key', openModal );
	$( document ).on( 'click', '.td-modal-close', closeModal );

	/* Close modal on Escape */
	$( document ).on( 'keydown', function( e ) {
		if ( e.key === 'Escape' && $modal.is( ':visible' ) ) {
			closeModal();
		}
	} );

	/* ========== Create Key ========== */

	$( document ).on( 'click', '#td-save-new-key', function() {
		var name = $.trim( $( '#td-new-key-name' ).val() );

		if ( ! name ) {
			alert( i18n.name_required );
			return;
		}

		var $btn = $( this ).prop( 'disabled', true );

		apiRequest( 'POST', restUrl, { name: name } )
			.done( function( token ) {
				closeModal();

				/* Show list UI if currently in empty state */
				$( '#td-empty-state' ).hide();
				$( '#td-keys-intro' ).show();
				$( '#td-info-notice' ).show();
				$( '#td-keys-list' ).show();

				/* Prepend new key to the list */
				$( '#td-keys-list' ).append( buildKeyItem( token ) );
			} )
			.fail( function() {
				if ( typeof TVE_Dash !== 'undefined' && TVE_Dash.err ) {
					TVE_Dash.err( i18n.fail_create );
				}
				$btn.prop( 'disabled', false );
			} );
	} );

	/* ========== Copy Key ========== */

	$( document ).on( 'click', '.td-copy-key', function() {
		var text = $( this ).closest( '.td-key-item' ).data( 'key' );

		if ( navigator.clipboard && text ) {
			navigator.clipboard.writeText( text ).then( function() {
				if ( typeof TVE_Dash !== 'undefined' && TVE_Dash.success ) {
					TVE_Dash.success( i18n.api_key_copied );
				}
			} );
		}
	} );

	/* ========== Toggle Status ========== */

	$( document ).on( 'click', '.td-toggle-btn', function() {
		var $btn   = $( this ).prop( 'disabled', true ),
			$item  = $btn.closest( '.td-key-item' ),
			$dot   = $item.find( '.td-key-status-dot' ),
			$meta  = $item.find( '.td-key-meta span:last' ),
			id     = $item.data( 'id' ),
			isActive = $dot.hasClass( 'td-active' ),
			newStatus = isActive ? 0 : 1;

		apiRequest( 'PATCH', restUrl + '/' + id, { id: id, status: newStatus } )
			.done( function() {
				if ( newStatus ) {
					$dot.removeClass( 'td-inactive' ).addClass( 'td-active' );
					$item.removeClass( 'td-key-inactive' );
					$meta.text( i18n.active );
					$btn.text( i18n.disable );
				} else {
					$dot.removeClass( 'td-active' ).addClass( 'td-inactive' );
					$item.addClass( 'td-key-inactive' );
					$meta.text( i18n.inactive );
					$btn.text( i18n.enable );
				}
				$btn.prop( 'disabled', false );
			} )
			.fail( function() {
				if ( typeof TVE_Dash !== 'undefined' && TVE_Dash.err ) {
					TVE_Dash.err( i18n.fail_update );
				}
				$btn.prop( 'disabled', false );
			} );
	} );

	/* ========== Delete ========== */

	$( document ).on( 'click', '.td-delete-btn', function() {
		if ( ! confirm( i18n.confirm_delete ) ) {
			return;
		}

		var $item = $( this ).closest( '.td-key-item' ),
			id    = $item.data( 'id' );

		apiRequest( 'DELETE', restUrl + '/' + id )
			.done( function() {
				$item.remove();

				/* Show empty state if no keys left */
				if ( $( '#td-keys-list' ).children().length === 0 ) {
					$( '#td-keys-list' ).hide();
					$( '#td-keys-intro' ).hide();
					$( '#td-info-notice' ).hide();
					$( '#td-empty-state' ).show();
				}
			} )
			.fail( function() {
				if ( typeof TVE_Dash !== 'undefined' && TVE_Dash.err ) {
					TVE_Dash.err( i18n.fail_delete );
				}
			} );
	} );

	/* ========== Init ========== */

	loadTokens();

} )( jQuery );

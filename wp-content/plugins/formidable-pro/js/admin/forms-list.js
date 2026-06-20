( function() {
	'use strict';

	const { documentOn } = frmDom.util;
	const { __ }         = wp.i18n;

	// Change the style dropdown.
	document.addEventListener( 'frm-dropdown-change', event => {
		const data = new FormData();
		const wrapper = event.frmData.container.closest( '.frm-form-select-wrapper' );

		data.append( 'form_id', wrapper.dataset.formId );

		// If the selected value is 1 (the hardcoded default), use the actual default style ID.
		const styleId = '1' === event.frmData.value ? wrapper.dataset.defaultStyleId : event.frmData.value;
		data.append( 'style_id', styleId );

		// Update the Edit style URL.
		const editStyleLink = wrapper.querySelector( '.frm-form-style-select .frm-custom-dropdown-link' );
		if ( editStyleLink ) {
			const url = new URL( editStyleLink.href );
			url.searchParams.set( 'id', styleId );
			editStyleLink.href = url.toString();
		}

		frmDom.ajax.doJsonPost( 'update_form_style', data )
			.catch( error => {
				console.error( error );
			} );
	} );

	/**
	 * Dropdown Class to handle individual dropdown functionality.
	 */
	class FrmCustomDropdown {
		/**
		 * Initialize a new dropdown instance.
		 *
		 * @param {HTMLElement} container - The dropdown container element.
		 */
		constructor( container ) {
			// Store DOM elements
			this.container = container;
			this.button = container.querySelector( '.frm-custom-dropdown-toggle' );
			this.menu = container.querySelector( '.frm-custom-dropdown-menu' );
			this.options = container.querySelectorAll( '.frm-custom-dropdown-item' );
			this.valueInput = container.querySelectorAll( '.frm-custom-dropdown-value' );

			// Bind event handlers
			this.toggleDropdown = this.toggleDropdown.bind( this );
			this.handleOptionSelect = this.handleOptionSelect.bind( this );
			this.handleClickOutside = this.handleClickOutside.bind( this );

			// Initialize
			this.init();
		}

		/**
		 * Initialize dropdown event listeners.
		 */
		init() {
			this.button.addEventListener( 'click', this.toggleDropdown );

			this.options.forEach( option => {
				option.addEventListener( 'click', this.handleOptionSelect );
			} );

			document.addEventListener( 'click', this.handleClickOutside );
		}

		/**
		 * Check and adjust dropdown menu position.
		 */
		checkDropdownPosition() {
			const buttonRect = this.button.getBoundingClientRect();
			const menuHeight = this.menu.offsetHeight;
			const spaceBelow = window.innerHeight - buttonRect.bottom;

			this.menu.classList.remove( 'frm-custom-dropdown-menu-up' );

			if ( spaceBelow < menuHeight && buttonRect.top > menuHeight ) {
				this.menu.classList.add( 'frm-custom-dropdown-menu-up' );
			}
		}

		/**
		 * Toggle dropdown menu visibility.
		 *
		 * @param {Event} event - The click event.
		 */
		toggleDropdown( event ) {
			event.stopPropagation();

			// Close all other dropdowns first
			document.querySelectorAll( '.frm-custom-dropdown' ).forEach( container => {
				if ( container !== this.container ) {
					container.closest( '.frm-custom-dropdown' ).classList.remove( 'frm-custom-dropdown-opened' );
				}
			} );

			const isOpen = this.container.classList.contains( 'frm-custom-dropdown-opened' );

			if ( isOpen ) {
				this.closeMenu();
			} else {
				this.openMenu();
			}
		}

		/**
		 * Handle option selection.
		 *
		 * @param {Event} event - The click event.
		 */
		handleOptionSelect( event ) {
			event.stopPropagation();

			const item = event.target.closest( '.frm-custom-dropdown-item' );
			if ( ! item ) {
				return;
			}

			const value = item.dataset.value || '';
			this.button.querySelector( 'span' ).textContent = item.querySelector( 'span' ).textContent;

			// Update selected state.
			this.container.querySelectorAll( '.frm-custom-dropdown-item--selected' ).forEach( el => {
				el.classList.remove( 'frm-custom-dropdown-item--selected' );
			} );
			item.classList.add( 'frm-custom-dropdown-item--selected' );

			this.closeMenu();
			this.valueInput.value = value;
			triggerCustomEvent( document, 'frm-dropdown-change', {
				value,
				option: item,
				container: this.container,
			} );
		}

		/**
		 * Handle clicks outside the dropdown.
		 *
		 * @param {Event} event - The click event.
		 */
		handleClickOutside( event ) {
			if ( ! this.container.contains( event.target ) ) {
				this.closeMenu();
			}
		}

		closeMenu() {
			this.container.classList.remove( 'frm-custom-dropdown-opened' );
		}

		openMenu() {
			this.container.classList.add( 'frm-custom-dropdown-opened' );
			this.checkDropdownPosition();
		}
	}

	/**
	 * Triggers custom JS event.
	 *
	 * @since 6.32
	 *
	 * @param {HTMLElement} el        The HTML element.
	 * @param {string}      eventName Event name.
	 * @param {*}           data      The passed data.
	 */
	function triggerCustomEvent( el, eventName, data ) {
		const event = new CustomEvent( eventName );
		event.frmData = data;

		el.dispatchEvent( event );
	}

	/**
	 * Initialize all dropdowns when DOM is ready.
	 */
	document.addEventListener( 'DOMContentLoaded', function() {
		// Initialize all dropdowns on the page
		document.querySelectorAll( '.frm-custom-dropdown' ).forEach( dropdown => {
			new FrmCustomDropdown( dropdown );
		} );
	} );
}() );

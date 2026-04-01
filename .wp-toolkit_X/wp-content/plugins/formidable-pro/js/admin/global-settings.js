( function() {
	document.addEventListener( 'change', handleChangeEvent );

	function handleChangeEvent( e ) {
		if ( 'frm_datepicker_library' === e.target.id ) {
			toggleDatepickerJqueryRangeSupportNoteOnChange( e );
		}
	}

	/**
	 * Toggle the jQuery range support note based on the datepicker library selection.
	 * @param {Event} event
	 * @return {void}
	 */
	function toggleDatepickerJqueryRangeSupportNoteOnChange( event ) {
		const datepickerLibrary      = event.target.value;
		const jqueryRangeSupportNote = document.getElementById( 'frm_datepicker_jquery_range_support_note' );

		if ( 'jquery' === datepickerLibrary || 'default' === datepickerLibrary ) {
			jqueryRangeSupportNote.classList.remove( 'frm_hidden' );
			return;
		}

		jqueryRangeSupportNote.classList.add( 'frm_hidden' );
	}
}() );

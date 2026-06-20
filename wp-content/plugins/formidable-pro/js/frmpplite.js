( function() {
	if ( 'undefined' === typeof window.frmProForm ) {
		return;
	}

	/**
	 * When going back or saving a draft, avoid processing the payment.
	 *
	 * @param {String} action
	 * @param {Object} args
	 * @returns {Boolean}
	 */
	window.frmProForm.currentActionTypeShouldBeProcessed = function( action, args ) {
		var thisForm, saveDraft, isDraft;

		thisForm = args.thisForm;

		if ( frmProForm.goingToPreviousPage( thisForm ) ) {
			return false;
		}

		saveDraft = frmProForm.savingDraft( thisForm );
		isDraft   = 'update' === action && saveDraft === '';
		if ( isDraft ) {
			return true;
		}

		return 'create' === action && saveDraft !== 1;
	};
}() );

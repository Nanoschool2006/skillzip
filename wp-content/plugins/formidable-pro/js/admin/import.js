( function() {
	function addImportTypeListeners() {
		const formTypeCheckbox = document.querySelector( 'input[name="type[]"][value="forms"]' );

		if ( ! formTypeCheckbox ) {
			return;
		}

		const viewTypeCheckbox  = document.querySelector( 'input[name="type[]"][value="posts"]' );
		const entryTypeCheckbox = document.querySelector( 'input[name="type[]"][value="items"]' );

		const maybeRequireFormCheckbox = () => {
			const shouldRequire = viewTypeCheckbox?.checked || entryTypeCheckbox?.checked;

			if ( shouldRequire ) {
				formTypeCheckbox.checked  = true;
				formTypeCheckbox.disabled = true;
			} else {
				formTypeCheckbox.disabled = false;
			}
		};

		viewTypeCheckbox?.addEventListener( 'change', maybeRequireFormCheckbox );
		entryTypeCheckbox?.addEventListener( 'change', maybeRequireFormCheckbox );
	}

	addImportTypeListeners();
}() );

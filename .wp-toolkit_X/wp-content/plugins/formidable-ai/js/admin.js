( function() {

	wp.hooks.addFilter( 'frm_fields_with_shortcode_popup', 'formidable-ai', function( fieldsWithShortcodesBox ) {
		fieldsWithShortcodesBox.push( 'ai' );
		return fieldsWithShortcodesBox;
	});

}() );

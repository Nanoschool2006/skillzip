( function() {
	/* globals wp, frmApiEmbedJs */

	const __ = wp.i18n.__;

	document.addEventListener( 'DOMContentLoaded', onContentLoaded );

	function onContentLoaded() {
		if ( 'undefined' === typeof frmApiEmbedJs || 'string' !== typeof frmApiEmbedJs.protocol ) {
			return;
		}

		wp.hooks.addFilter( 'frmEmbedFormExamples', 'formidable-api', addApiFormEmbedExamples );
		wp.hooks.addFilter( 'frm_embed_examples', 'formidable-api', addApiViewEmbedExamples );
	};

	function addApiFormEmbedExamples( examples, { formId, formKey }) {
		const baseUrl = getBaseUrl();
		examples.push(
			{
				label: __( 'API Form script', 'formidable' ),
				example: '<script src="' + baseUrl + frmApiEmbedJs.protocol + formKey + '"></script>'
			},
			{
				label: __( 'API Form shortcode', 'formidable' ),
				example: '[frm-api type="form" id=' + formId + ' url="' + baseUrl + '"]'
			}
		);
		return examples;
	}

	function addApiViewEmbedExamples( examples, { type, objectId }) {
		if ( 'view' !== type ) {
			return examples;
		}

		const baseUrl = getBaseUrl();
		examples.push(
			{
				label: __( 'API View shortcode', 'frmapi' ),
				example: '[frm-api type="view" id=' + objectId + ' url="' + baseUrl + '"]'
			}
		);
		return examples;
	}

	function getBaseUrl() {
		return frmGlobal.url.split( '/wp-content/' )[0];
	}
}() );

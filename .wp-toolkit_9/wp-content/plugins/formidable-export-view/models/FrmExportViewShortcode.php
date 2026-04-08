<?php


class FrmExportViewShortcode {

	/**
	 * The frm-export-view shortcode function
	 *
	 * @param array $atts Attributes for the shortcode.
	 *
	 * @return string Shortcode html.
	 *
	 * @throws Exception May throw an error.
	 */
	public static function shortcode( $atts ) {
		$defaults = array(
			'view' => '',
		);

		$sc_atts = shortcode_atts( $defaults, $atts );
		$atts    = array_merge( (array) $atts, (array) $sc_atts );

		if ( empty( $atts['view'] ) ) {
			return self::create_error_message( 'view-not-set' );
		}

		$view = FrmViewsDisplay::getOne( $atts['view'], false, true );

		if ( empty( $view ) ) {
			return self::create_error_message( 'incorrect-view' );
		}

		$frm_options = get_post_meta( $view->ID, 'frm_options', true );

		if ( ! self::view_can_be_exported( $frm_options ) ) {
			return self::create_error_message( 'not-table-view' );
		}

		if ( ! self::view_has_entries( $view ) ) {
			return '';
		}

		if ( ! empty( $atts['label'] ) ) {
			// Allow the link label to be set from the shortcode.
			$frm_options['export_link_text'] = $atts['label'];
		}

		return FrmExportViewLink::get_export_view_link_html( $view->ID, $frm_options );
	}

	/**
	 * Returns error message if the current user has permission to edit Views.
	 *
	 * @param string $error_type Type of error.
	 *
	 * @return string Error message if user has permission to edit Views and empty string if not.
	 */
	private static function create_error_message( $error_type ) {
		return current_user_can( 'frm_edit_displays' ) ? self::get_error_message_content( $error_type ) : '';
	}

	/**
	 * If there is an error, get the message to show.
	 *
	 * @param string $error_type The name of the error.
	 */
	private static function get_error_message_content( $error_type ) {
		$errors = array(
			'view-not-set'   => __( 'The [frm-export-view] shortcode needs to have a view param, which should be set to the id or key of the View you would like to export.', 'formidable-export-view' ),
			'incorrect-view' => __( 'Please set the view param of the [frm-export-view] shortcode to the id or key of a View on this site.', 'formidable-export-view' ),
			'not-table-view' => __( 'Please set the view param of the [frm-export-view] shortcode to an All Entries or Dynamic View that is set up as a table.', 'formidable-export-view' ),
		);

		return isset( $errors[ $error_type ] ) ? esc_html( $errors[ $error_type ] ) : '';
	}

	/**
	 * Returns true if the View is a table View, as determined when View was last saved.
	 *
	 * @param array|false $frm_options View options.
	 *
	 * @return bool Whether or not View can be exported.
	 */
	private static function view_can_be_exported( $frm_options ) {
		return $frm_options && ! empty( $frm_options['view_export_possible'] );
	}

	/**
	 * Returns true if the View has entries.
	 *
	 * @param object $view View object.
	 *
	 * @return bool Whether or not the View has entries.
	 */
	private static function view_has_entries( $view ) {
		self::simplify_view_for_entry_test( $view );
		$entry_ids_and_where = FrmExportViewCSVController::get_entry_ids_and_where( $view );
		if ( empty( $entry_ids_and_where ) || empty( $entry_ids_and_where['entry_ids'] ) || ! is_array( $entry_ids_and_where['entry_ids'] ) ) {
			return false;
		}

		return count( $entry_ids_and_where['entry_ids'] ) > 0;
	}

	/**
	 * Simplifies View object to make the test if it has entries more efficient.
	 *
	 * @param object $view View object.
	 */
	private static function simplify_view_for_entry_test( $view ) {
		$view->post_content       = '';
		$view->frm_before_content = '';
		$view->frm_content        = '';
		$view->frm_after_content  = '';
		$view->frm_limit          = '1';
		$view->frm_page_size      = '1';
		$view->frm_empty_msg      = '';
	}
}

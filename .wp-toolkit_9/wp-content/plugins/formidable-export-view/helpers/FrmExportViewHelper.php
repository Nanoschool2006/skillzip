<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmExportViewHelper {

	/**
	 * Returns a list of Views that can be exported as CSVs
	 *
	 * @return array
	 */
	public static function get_views() {
		$views = FrmViewsDisplay::getAll( array(), 'post_title' );
		if ( empty( $views ) ) {
			return array();
		}
		$views = array_reverse( $views );

		$view_options = FrmViewsDisplaysHelper::get_frm_options_for_views( 'view_export_possible' );
		if ( empty( $view_options ) ) {
			return array();
		}

		$select_options = array();

		foreach ( $views as $view ) {
			$id = $view->ID;
			if ( isset( $view_options[ $id ] ) && isset( $view_options[ $id ]->meta_value ) && ! empty( $view_options[ $id ]->meta_value['view_export_possible'] ) ) {
				$select_options[ $id ] = empty( $view->post_title ) ? __( '(no title)', 'formidable-pro' ) : $view->post_title;
			}
		}

		return $select_options;
	}
}

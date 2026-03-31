<?php


class FrmExportViewSettingsController {

	/**
	 * Adds export View settings metabox to View builder
	 *
	 * @param string $post_type Type of post on which this metabox should appear.
	 */
	public static function add_meta_box( $post_type ) {
		if ( FrmViewsDisplaysController::$post_type != $post_type ) {
			return;
		}
		add_meta_box( 'frm_export_view', __( 'Export View Settings', 'formidable-export-view' ), 'FrmExportViewSettingsController::add_export_view_metabox', FrmViewsDisplaysController::$post_type, 'advanced', 'high' );
		add_filter( 'postbox_classes_frm_display_frm_export_view', 'FrmExportViewSettingsController::add_meta_box_classes' );
	}

	/**
	 * Assembles content of the export View metabox
	 *
	 * @param mixed $post Screen.
	 */
	public static function add_export_view_metabox( $post ) {
		FrmViewsDisplaysHelper::prepare_duplicate_view( $post );
		$vars = get_post_meta( $post->ID, 'frm_options', true );
		include_once( FrmExportViewAppController::plugin_path() . '/views/export-view-metabox.php' );
	}

	/**
	 * Adds frm_hidden class to metabox so it won't flash on screen before it's hidden, when appropriate.
	 *
	 * @param array $classes Classes on Export Views metabox.
	 *
	 * @return array Classes on metabox, now including frm_hidden.
	 */
	public static function add_meta_box_classes( $classes ) {
		$classes[] = 'frm_hidden';

		return $classes;
	}
}

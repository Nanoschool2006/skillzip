<?php
/**
 * Helper functions used by views and controllers.
 *
 * @since 1.06
 */

class FrmPllAppHelper {

	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	public static function plugin_path() {
		return dirname( dirname( __FILE__ ) );
	}
}

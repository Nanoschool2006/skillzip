<?php

class FrmExportViewAppController {

	/**
	 * Load the pot file so translations can work.
	 *
	 * @return void
	 */
	public static function load_lang() {
		load_plugin_textdomain( 'formidable-export-view', false, basename( self::plugin_path() ) . '/languages/' );
	}

	/**
	 * Displays notice in admin if Formidable Pro isn't installed at all or at a high enough version.
	 *
	 * @return void
	 */
	public static function pro_not_installed_notice() {
		?>
		<div class="error">
			<p>
				<?php
				$min_version = FrmExportViewHooksController::$min_formidable_version;
				// Translators: %s: Formidable Pro minimum version number.
				printf( esc_html__( 'Formidable Export View requires Formidable Forms Pro version %s or higher to be installed.', 'formidable-export-view' ), esc_html( $min_version ) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays notice in admin if Formidable Views isn't installed at all.
	 *
	 * @return void
	 */
	public static function views_not_installed_notice() {
		?>
		<div class="error">
			<p>
				<?php printf( esc_html__( 'Formidable Export View requires Formidable Forms Views to be installed.', 'formidable-export-view' ) ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Includes update file, if possible.
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include self::plugin_path() . '/models/FrmExportViewUpdate.php';
			FrmExportViewUpdate::load_hooks();
		}
	}

	/**
	 * Returns URL of the plugin
	 *
	 * @return string The URL of the plugin.
	 */
	public static function plugin_url() {
		return plugins_url() . '/' . basename( self::plugin_path() );
	}

	/**
	 * Returns the path the plugin.
	 *
	 * @return string The path to the plugin.
	 */
	public static function plugin_path() {
		return dirname( dirname( __FILE__ ) );
	}
}

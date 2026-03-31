<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmExportViewUpdate extends FrmAddon {

	/**
	 * The file path.
	 *
	 * @var string $plugin_file
	 */
	public $plugin_file;

	/**
	 * The name of this plugin (must match the name of the product).
	 *
	 * @var string $plugin_name
	 */
	public $plugin_name = 'Export View to CSV';

	/**
	 * Product id in Formidable system
	 *
	 * @since 1.0.0
	 * @var int $download_id
	 */
	public $download_id = 20897348;

	/**
	 * The number of the current version.
	 *
	 * @var string $version
	 */
	public $version = '1.10';

	/**
	 * The static version for easier access.
	 *
	 * @var string $version_copy
	 */
	public static $version_copy = '1.01';

	/**
	 * FrmExportViewUpdate constructor.
	 */
	public function __construct() {
		$this->plugin_file = FrmExportViewAppController::plugin_path() . '/formidable-export-view.php';
		parent::__construct();
	}

	/**
	 * Include add-on page and instantiate this class.
	 */
	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmExportViewUpdate();
	}

	/**
	 * Returns a copy of the version of this plugin.
	 *
	 * @return string A copy of the version of this plugin.
	 */
	public static function get_export_view_version() {
		return self::$version_copy;
	}
}

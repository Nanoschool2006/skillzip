<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmGoogleSpreadsheetUpdate class.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetUpdate extends FrmAddon {

	/**
	 * Plugin file variable.
	 *
	 * @since 1.0
	 *
	 * @var mixed
	 */
	public $plugin_file;

	/**
	 * Plugin name variable.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $plugin_name = 'Google Sheets';

	/**
	 * Download_id variable
	 *
	 * @since 1.0
	 *
	 * @var integer
	 */
	public $download_id = 28149579;

	/**
	 * Version variable.
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Construct method.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->plugin_file = FrmGoogleSpreadsheetAppHelper::path() . '/formidable-google-sheets.php';
		$this->version     = FrmGoogleSpreadsheetAppHelper::plugin_version();
		parent::__construct();
	}
}

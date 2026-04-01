<?php
/**
 * Addon update class
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmAIUpdate
 */
class FrmAIUpdate extends FrmAddon {

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = 'AI';

	/**
	 * Download ID.
	 *
	 * @var int
	 */
	public $download_id = 28189169;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_file = FrmAIAppHelper::plugin_file();
		$this->version     = FrmAIAppHelper::$plug_version;
		parent::__construct();
	}
}

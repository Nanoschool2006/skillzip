<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmLandingUpdate extends FrmAddon {

	/**
	 * @var string $plugin_file
	 */
	public $plugin_file;

	/**
	 * @var string $plugin_name
	 */
	public $plugin_name = 'Landing Pages';

	/**
	 * @var int $download_id
	 */
	public $download_id = 28074303;

	/**
	 * @var string $version
	 */
	public $version;

	public function __construct() {
		$this->plugin_file = FrmLandingAppHelper::path() . '/formidable-landing.php';
		$this->version     = FrmLandingAppHelper::plugin_version();
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmLandingUpdate();
	}
}

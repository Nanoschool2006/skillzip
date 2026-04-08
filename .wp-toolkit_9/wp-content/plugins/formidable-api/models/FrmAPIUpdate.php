<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmAPIUpdate extends FrmAddon {

	public $plugin_file;
	public $plugin_name = 'Formidable API';
	public $download_id = 168072;
	public $version;

	public function __construct() {
		$this->plugin_file = FrmAPIAppHelper::path() . '/formidable-api.php';
		$this->version     = FrmAPIAppHelper::plugin_version();
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmAPIUpdate();
	}
}

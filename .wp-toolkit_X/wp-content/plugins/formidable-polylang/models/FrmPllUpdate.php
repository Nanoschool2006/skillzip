<?php

class FrmPllUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'Formidable Polylang';
	public $version = '1.14';
	public $download_id = 209561;

	public function __construct() {
		$this->plugin_file = dirname( dirname( __FILE__ ) ) . '/frm-poly.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmPllUpdate();
	}
}

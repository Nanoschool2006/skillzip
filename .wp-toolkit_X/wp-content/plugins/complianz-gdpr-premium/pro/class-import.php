<?php

defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_import_settings")) {
    class cmplz_import_settings
    {
        private static $_this;

        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

            self::$_this = $this;
	        add_filter("admin_init", array($this, 'process_import_action'), 100, 3);
        }

        static function this()
        {
            return self::$_this;
        }

	    /**
	     * Process the import action
	     *
	     * @return array
	     */
	    public function process_import_action() {
			if (!isset($_GET['cmplz_upload_file'])) {
				return [];
			}

			if (!isset($_GET['action']) || $_GET['action'] !== 'import_settings') {
				return [];
			}

		    if (!isset($_FILES['data'])) {
			    return [];
		    }
		    if ( !cmplz_user_can_manage() ) {
			    return [];
		    }

			$file = $_FILES['data'];
		    $error = false;
		    $accepted_pages = array(
			    'banners', 'settings'
		    );

		    if ($file['type'] !== 'application/json') {
			    $error = __('This file does not have the correct format', 'complianz-gdpr');
		    }

		    $data = file_get_contents($file['tmp_name']);
		    if (file_exists($file['tmp_name'])) unlink($file['tmp_name']);

		    if (!$error) {
			    $arr = explode('#--COMPLIANZ--#', $data);

			    if (!isset($arr[0]) ){
				    $error = __('Data integrity check failed', 'complianz-gdpr');
			    }

			    $data = $arr[0];

			    $data = json_decode($data, true);
		    }

		    if ( empty($data) ){
			    $error = __('Empty dataset','complianz-gdpr');
		    }

		    if ( !$error && !empty($data) ) {
			    foreach ($data as $page => $settings) {
				    if (!in_array($page, $accepted_pages)) {
					    continue;
				    }

				    if ($page === 'settings') {
					    update_option("cmplz_options", $settings);
				    } else if ($page === 'banners') {
					    //these are exported banners.
					    foreach ($settings as $banner) {
						    unset($banner['ID']);
						    $cookiebanner = new CMPLZ_COOKIEBANNER();
						    foreach($banner as $property => $value) {
								//check if property exists
                                if (!property_exists($cookiebanner, $property)) continue;
							    $cookiebanner->{$property} = $value;
						    }
						    $cookiebanner->save();
					    }
				    }
			    }
		    }
		    $response = [
				'success' => !$error,
				"error_message"  => $error,
			];
		    header( "Content-Type: application/json" );
		    echo json_encode($response);
		    exit;
	    }
    }
}

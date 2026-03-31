<?php
defined('ABSPATH') or die("you do not have access to this page!");
//https://dev.maxmind.com/geoip/geoip2/geolite2/
if ( !defined('GEOIP_DETECT_VERSION') ) require CMPLZ_PATH . 'pro/assets/vendor/autoload.php';
use GeoIp2\Database\Reader;
/*
 * http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz
 * */

if (!class_exists("cmplz_geoip")) {
    class cmplz_geoip
    {
        private static $_this;
        public $reader;
        private $db_url = 'https://cookiedatabase.org/maxmind/GeoLite2-Country.tar.gz';
//        private $db_url = 'https://cookiedatabase.org/maxmind/GeoLite2-Country.mmdb';
        public $initialized = false;

        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

            self::$_this = $this;

            add_action('cmplz_before_save_options', array($this, 'before_save_general_settings_option'), 10, 5);
            add_filter('cmplz_geoip_enabled', array($this, 'geoip_enabled'));
            add_filter('shutdown', array($this, 'close'));
        }

        static function this()
        {
            return self::$_this;
        }

		public function close(){
			if ( $this->initialized ) {
				$this->reader->close();
			}
		}

        /**
         * Runs on saving of a field, to check if geoip was enabled. If so, import the library
         *
         * @hooked complianz_before_save_settings_option
         *
         * @param array  $options
         * @param string $fieldname
         * @param $fieldvalue
         * @param $prev_value
         * @param $type
         * @return array
         */

        public function before_save_general_settings_option(array $options, string $field_id, $field_value, $prev_value, $type ): array {
            if ( !cmplz_user_can_manage() ) {
				return $options;
            }

			if ($field_value === $prev_value ) {
				return $options;
			}

	        /**
	         * For records of consent, geo ip always needs to be enabled
	         */

	        if ( $field_id === 'records_of_consent' && $field_value === 'yes' ) {
				$options['use_country'] = true;
		        $options = $this->convert_regions(true, $options);
	        } else if ($field_id==='use_country') {
		        $options = $this->convert_regions($field_value, $options);

				/**
		         * on change of the use_country variable, make sure all user's cache is cleared.
		         */
		        if ( get_option('cmplz_cbdb_version') ) {
			        //only if table created.
			        cmplz_update_all_banners();
		        }

		        //disable region redirect if geo ip is disabled
		        if ( !$field_value ){
			        $options['region_redirect'] = 'no';
				}
	        }
			return $options;
        }

		public function convert_regions( $enabled, $options ){
			//if it's just enabled, run import
			if ($enabled ) {
				update_option('cmplz_import_geoip_on_activation', true, false );
			}

			//if geo ip is disabled or enabled, convert regions to array or vice versa
			$regions = $options['regions'] ?? false;
			if ( ! empty( $regions ) ) {
				//just enabled: convert to array
				if ( $enabled && !empty($regions) && !is_array( $regions ) ) {
					$regions = array(0 => $regions);
					$value = cmplz_sanitize_field( $regions, 'multicheckbox', 'regions' );
					$options['regions'] = $value;
				} elseif (!$enabled && is_array( $regions ) ) {
					$regions = array_filter($regions);
					$regions = reset($regions);
					$options['regions'] = $regions;
				}
			}

			return $options;
		}

        /**
         *
         * Check if there is an issue with the geo ip library
         * @since 2.0.3
         * @return bool
         */

        public function geoip_library_error(): bool {

            if ($this->geoip_enabled() && (!get_option("cmplz_geo_ip_file") || !file_exists(get_option("cmplz_geo_ip_file")))){
	            update_option('cmplz_import_geoip_on_activation', true, false );
	            return true;
            }

            return false;
        }

        /**
         * initialize the geo ip library
         * @since 1.2
         */

        public function initialize()
        {
			if ( $this->initialized ) {
				return true;
			}

            if ( !$this->geoip_enabled() ) {
				return false;
            }

	        if ( cmplz_is_logged_in_rest() ) {
                if ( get_option('cmplz_import_geoip_on_activation') ) {
                    $this->get_geo_ip_database_file();
                    update_option('cmplz_import_geoip_on_activation', false, false );
                }
            }

            //check if file exists in cmplz folder
            $file_name = get_option("cmplz_geo_ip_file");
            if ( file_exists($file_name) || cmplz_remote_file_exists($file_name) ) {
	            try {
		            $this->reader = new Reader($file_name);
					if ( $this->reader ) {
						$this->initialized = true;
						//if manually uploaded after an error was detected, the error can be removed now.
						if ( is_admin() && get_option('cmplz_geoip_import_error') ) {
							delete_option('cmplz_geoip_import_error');
						}
					}
	            } catch (Exception $e) {
		            update_option('cmplz_import_geoip_on_activation', true, false );
		            delete_option("cmplz_geo_ip_file");
		            delete_option('cmplz_last_update_geoip');
	            }
            } else {
	            update_option('cmplz_import_geoip_on_activation', true, false );
	            delete_option("cmplz_geo_ip_file");
	            delete_option('cmplz_last_update_geoip');
            }
			return $this->initialized;
        }

        /**
         * Get the region belonging to the currently visiting IP address. Must be one of the supported regions, i.e. us, eu.
         * @since 2.0.0
         *
         * @return string
         */

        public function region()
        {
            $country_code = $this->get_country_code();
            return cmplz_get_region_for_country($country_code);
        }

        /**
         * Get the region belonging to the currently visiting IP address. Must be one of the supported regions, i.e. us, eu.
         * @since 4.0.0
         *
         * @return string
         */

        public function consenttype()
        {
            $country_code = $this->get_country_code();
	        return cmplz_get_consenttype_for_country($country_code);
        }

        /**
         * Get the country code for the current visiting ip address returns false on failure
         * @since 1.2
         *
         * @return bool|string
         */

        public function get_country_code()
        {
            //if we don't have the geo ip database yet, we return default.
            if ( !$this->initialize() ) {
				if (defined('WP_DEBUG') && WP_DEBUG) error_log("geo ip not initialized");
				return cmplz_get_option('country_company');
            }

            $ip = $this->get_current_ip();
            if (!$ip) return false;

            $country_code = false;

            try {
                $record = $this->reader->country($ip);
                $country_code = $record->country->isoCode;
            } catch (Exception $e) {
                if ( defined('WP_DEBUG') && WP_DEBUG ) {
	                error_log("failed retrieving country");
	                error_log(print_r($e, true));
                }
            }
            return $country_code;
        }

        /**
         * Get the ip for the current visitor. False on failure
         * @since 1.2
         *
         * @return bool|string
         */

        public function get_current_ip()
        {
            if (!$this->initialize()) {
				return false;
            }

            //localhost testing
            if (strpos(home_url(), apply_filters("cmplz_debug_domain","localhost")) !== false) {
                $company_region = COMPLIANZ::$company->get_company_region_code();
                if ($company_region === 'us') {
                    $current_ip = "128.101.101.101";//US ip
                } elseif ($company_region === 'eu') {
                    $current_ip = "94.214.200.105"; //EU ip
                } elseif($company_region==='uk') {
                    $current_ip = '185.86.151.11';
                } elseif($company_region==='ca') {
                    $current_ip = '45.44.129.152';
                }else{
                    $current_ip = "189.189.111.174";     //Mexico
                }

//                $current_ip = "128.101.101.101";//us
//                $current_ip = "94.214.200.105"; //EU ip
                //$current_ip = "185.69.233.170";
                //$current_ip = '2a02:1812:1717:4a00:919f:4a7a:33be:3c54, 2a02:1812:1717:4a00:919f:4a7a:33be:3c54';
            } else{
                $current_ip = apply_filters('cmplz_client_ip', $this->clientIP() );
            }

            //sanitize
            if (filter_var($current_ip, FILTER_VALIDATE_IP)) {
                return apply_filters('cmplz_detected_ip', $current_ip);
            }

            return apply_filters('cmplz_detected_ip', false);
        }

        /**
         * Get the ip of visiting user
         * https://stackoverflow.com/questions/11452938/how-to-use-http-x-forwarded-for-properly
         *
         * @return string ip number
         */

        public function clientIP(){
            //least common types first
	        $variables = array(
		        'HTTP_CF_CONNECTING_IP',
		        'CF-IPCountry',
		        'HTTP_TRUE_CLIENT_IP',
		        'HTTP_X_CLUSTER_CLIENT_IP',
		        'HTTP_CLIENT_IP',
		        'HTTP_X_FORWARDED_FOR',
		        'HTTP_X_FORWARDED',
		        'HTTP_X_REAL_IP',
		        'HTTP_FORWARDED_FOR',
		        'HTTP_FORWARDED',
		        'REMOTE_ADDR'
	        );

			foreach($variables as $variable ) {
				$current_ip = $this->is_real_ip($variable);
				if ( $current_ip ) {
					break;
				}
			}

            //in some cases, multiple ip's get passed. split it to get just one.
            if (strpos($current_ip, ',') !== false) {
                $ips = explode(',', $current_ip);
                $current_ip = $ips[0];
            }

            return apply_filters("cmplz_visitor_ip", $current_ip);
        }

	    /**
	     * Get ip from var, and check if the found ip is a valid one
	     * @param string $var
	     *
	     * @return false|string
	     */

		private function is_real_ip($var) {
			$ip = getenv($var);
			if (!$ip || trim($ip)==='127.0.0.1') {
				return false;
			}

			return $ip;
		}


        /**
         *
         * Check if geo ip is enabled on this site
         * @since 1.2
         *
         * @return bool
         */

        public function geoip_enabled($enabled=false)
        {
            return cmplz_get_option('use_country');
        }

        /**
         * Retrieve the MaxMind geo ip database file. Pass retrieve to force renewal of the file.
         * @since 2.0.3
         * @param bool $renew
         */

        public function get_geo_ip_database_file($renew=false)
        {
            if ( !wp_doing_cron() && !cmplz_user_can_manage() ) {
				return;
            }
	        if (defined('CMPLZ_DO_NOT_UPDATE_GEOIP') && CMPLZ_DO_NOT_UPDATE_GEOIP) {
				return;
	        }

            //only run if it doesn't exist yet, or if it should renew
	        if ( $renew
	             || !get_option("cmplz_geo_ip_file")
	             || (!file_exists(get_option("cmplz_geo_ip_file")) && !cmplz_remote_file_exists(get_option("cmplz_geo_ip_file")))) {
				require_once(ABSPATH . 'wp-admin/includes/file.php');
                //set geo ip to not available
                $this->initialized = false;
                update_option("cmplz_geo_ip_file", false);

                $upload_dir = cmplz_upload_dir("maxmind");

	            $name = 'GeoLite2-Country.tar.gz';
		        $zip_file_name = apply_filters('cmplz_zip_file_path', $upload_dir . $name);

	            $tar_file_name = str_replace('.gz', '', $zip_file_name);
	            $result_file_name = str_replace('.tar.gz', '.mmdb', $name);
	            $unzipped = $upload_dir . $result_file_name;

                //download file from maxmind
                $tmpfile = download_url($this->db_url, $timeout = 25);
                //check for errors
                if ( !is_dir($upload_dir) ) {
	                //store the error for use in the callback notice for geo ip
	                update_option( 'cmplz_geoip_import_error', __( "Required directory does not exist:", "complianz-gdpr" ) . ' ' . $upload_dir );
                } else if ( cmplz_has_open_basedir_restriction($zip_file_name) ){
	                update_option('cmplz_geoip_import_error', "Open Base dir restriction detected. Please upload manually");
                } else if (is_wp_error($tmpfile) ){
	                //store the error for use in the callback notice for geo ip
	                update_option('cmplz_geoip_import_error', $tmpfile->get_error_message() );
                } else {

					// Extract tar.gz
					update_option("cmplz_geo_ip_file", $unzipped);

					// Remove existing .mmdb
	                if (file_exists($unzipped) || cmplz_remote_file_exists($unzipped)) {
		                unlink($unzipped);
	                }

					// Copy the tar.gz if it does not exist yet.
					if (!file_exists($zip_file_name)) copy($tmpfile, $zip_file_name);

	                try {
		                //unzip the file
		                $p = new PharData($zip_file_name);
		                if ( file_exists( $tar_file_name ) ) {
			                unlink( $tar_file_name );
		                }
		                $p->decompress(); // creates tar file
		                // unarchive from the tar
		                $phar = new PharData($tar_file_name);
		                $phar->extractTo($upload_dir , null, true );
	                } catch (Exception $e) {
		                // handle exception
		                update_option('cmplz_geoip_import_error', $e->getMessage() );
	                }

					//now look up the uncompressed folder
					foreach ( glob( $upload_dir . "*" ) as $file ) {
						if ( is_dir( $file ) ) {
							//copy our file to the maxmind folder
							copy(trailingslashit($file).$result_file_name, $upload_dir . $result_file_name);
							//delete this one
							unlink(trailingslashit($file).$result_file_name);
							//clean up txt files
							foreach ( glob( $file.'/*' ) as $txt_file ) {
								unlink($txt_file);
							}
							//remove the directory
							rmdir($file);
						}
					}

					//clean up zip file
					if ( file_exists( $zip_file_name ) ) {
						unlink( $zip_file_name );
					}

					//clean up tar file
					if ( file_exists( $tar_file_name ) ) {
						unlink( $tar_file_name );
					}

                    //if there was an error saved previously, remove it
                    delete_option('cmplz_geoip_import_error');
                }

	            // Delete temp file
				if ( is_string($tmpfile) && file_exists( $tmpfile ) ) {
					unlink($tmpfile);
				}

                update_option('cmplz_last_update_geoip', time(), false );
            }
        }

        /**
         * Check if the geo ip database should be updated
         * @hooked cmplz_every_day_hook
         */

        public function cron_check_geo_ip_db(){

            if (!$this->geoip_enabled()) return;

            $now = time();
            $last_update = get_option('cmplz_last_update_geoip');
            $time_passed = $now - $last_update;

            //if file was never downloaded, or more than two months ago, redownload.
            if (!$last_update || $time_passed > 2 * MONTH_IN_SECONDS){
                $this->get_geo_ip_database_file(true);
            }
        }


    }

}

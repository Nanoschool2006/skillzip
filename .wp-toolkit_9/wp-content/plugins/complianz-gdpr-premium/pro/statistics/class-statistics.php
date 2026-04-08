<?php defined('ABSPATH') or die("you do not have access to this page!");
if (!class_exists('cmplz_statistics')) {
    class cmplz_statistics
    {
        private static $_this;
        public $prefix;
	    public $visitor_id;
        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));
            self::$_this = $this;
            add_filter('cmplz_user_banner_id', array($this, 'get_user_banner_id'));
            add_filter('cmplz_user_data', array($this, 'get_user_data'));
            add_action('cmplz_store_consent', array($this, 'store_consent'), 10, 3);
            add_filter('cmplz_ab_testing_enabled', array($this, 'ab_testing_enabled'));
		}

        static function this()
        {
            return self::$_this;
        }

        /**
         * Override free function to enable a/b testing when active
         * @param $enabled
         * @return bool $enabled
         *
         */

        public function ab_testing_enabled($enabled)
        {
            return cmplz_get_option('a_b_testing_buttons');
        }

        /**
         * In case of ab testing, the user data called through ajax overrides the default cookie setting data.
         * This way, even with caching, the data can be loaded dynamically.
         *
         * */

        public function get_user_data()
        {
            if ( !cmplz_ab_testing_enabled() ) {
				return array();
            }

	        return cmplz_get_cookiebanner( $this->get_user_banner_id() )->get_front_end_settings();
        }

        /**
         *
         * For a/b testing, get a random banner id. If the user visited before, get that same banner id.
         * @return int banner_id
         */

	    public function get_user_banner_id()
	    {
		    if ( !cmplz_ab_testing_enabled() ) {
			    return cmplz_get_default_banner_id();
		    }
		    $banners = wp_list_pluck(cmplz_get_cookiebanners(), 'ID');
		    $random_key = array_rand($banners);
		    $random = $banners[$random_key];
		    $user_banner_id = 0;
		    global $wpdb;
		    if ( !$this->visitor_id && (isset($_COOKIE['cmplz_id']) && $_COOKIE['cmplz_id']>0) ) {
			    $this->visitor_id = (int) $_COOKIE['cmplz_id'];
		    }

		    if ( $this->visitor_id ) {
			    $user_banner_id = wp_cache_get('cmplz_user_banner_id_'.$this->visitor_id, 'complianz');
			    if ( !$user_banner_id ) {
				    $user_banner_id = $wpdb->get_var($wpdb->prepare("SELECT cookiebanner_id from {$wpdb->prefix}cmplz_statistics WHERE ID = %s", $this->visitor_id));
				    wp_cache_set('cmplz_user_banner_id_'.$this->visitor_id, $user_banner_id, 'complianz', HOUR_IN_SECONDS);
			    }
			    //check if this variation still exists
			    if (!in_array($user_banner_id, $banners)) {
				    $user_banner_id = $random;
				    $success = $wpdb->update($wpdb->prefix . 'cmplz_statistics',
					    array('cookiebanner_id' => $user_banner_id),
					    array('ID' => $this->visitor_id)
				    );
				    //if the update failed, the user wasn't found in the database, so we insert it fresh
				    if ($success === 0) {
					    $user_banner_id = 0;
				    }
			    }
		    }

		    // Insert $user_banner_id once. Function is called twice, so we store it in the visitor_id variable in the class.
		    if ( $user_banner_id === 0 ) {
			    $user_banner_id = $random;
			    $wpdb->insert($wpdb->prefix . 'cmplz_statistics',
				    array('cookiebanner_id' => $user_banner_id)
			    );
			    $this->visitor_id = $wpdb->insert_id;
			    $this->setcookie($this->visitor_id);

		    }
		    return $user_banner_id;
	    }

		/**
		 * Set User cookie for ab testing or records of consent
		 * @param $visitor_id
		 */
        public function setcookie($visitor_id): void {
			if ( headers_sent() ) {
				return;
			}
			$path = COMPLIANZ::$banner_loader->get_cookie_path();
			$prefix = COMPLIANZ::$banner_loader->get_cookie_prefix();
			$options = array (
				'expires' => time() + (DAY_IN_SECONDS * 365),
				'path' => $path,
				'secure' => is_ssl(),
				'samesite' => 'Lax' // None || Lax  || Strict
			);

			if (cmplz_get_option( 'set_cookies_on_root' )) {
				$options['domain'] = COMPLIANZ::$banner_loader->get_cookie_domain();
			}

			if (version_compare(PHP_VERSION, '7.3', '<')) {
				$domain = isset($options['domain']) ? $options['domain'] : '';
				setcookie(
					$prefix.'id',
					$visitor_id,
					time() + (DAY_IN_SECONDS * 365),
					$path,
					$domain,
					is_ssl(),
					false
				);
			} else {
				setcookie( $prefix.'id', $visitor_id, $options );
			}
		}

		/**
		 * Each page page_view, we check if this user was already listed
		 * By checking the cookie. No usage data is stored, so we don't need to have a cookie warning for this
		 * If user was not listed before, we add a new entry
		 *
		 * @param array  $consented_categories
		 * @param array  $consented_services
		 * @param string $consenttype
		 */

        public function store_consent( array $consented_categories, array $consented_services, string $consenttype ): void {

			$time = time() + ( 60 * 60 * get_option( 'gmt_offset' ) );
            $visitor_is_registered = true;
            $user_ip = COMPLIANZ::$geoip->get_current_ip();
            $region = COMPLIANZ::$geoip->region();
			if ( !empty( $user_ip ) ) {
				$user_ip = apply_filters( 'cmplz_records_of_consent_user_ip', substr( $user_ip, 0, -3 ).'***' , $user_ip);
			}

	        $args = array(
					'pageviews'    => 1,
					'consenttype'  => cmplz_sanitize_consenttype($consenttype),
					'region'       => $region,
					'ip'           => $user_ip,
					'time'         => $time,
					'no_warning'   => false,
					'do_not_track' => false,
					'no_choice'    => false,
					'functional'   => false,
					'preferences'  => false,
					'statistics'   => false,
					'marketing'    => false,
	        );

	        foreach ( $consented_categories as $consented_category ) {
				$consented_category = cmplz_sanitize_category($consented_category);
	        	if ( isset($args[$consented_category]) ) {
					$args[$consented_category] = true;
				}
	        }
			//only consented on true
			$consented_services = array_filter($consented_services, function($val){ return $val==1;});
			$consented_services = array_keys($consented_services);
			$args['services'] = implode(',',array_map('sanitize_title', $consented_services));

	        //if records of consent is enabled, add the last pdf as poc.
			//if a new cookie policy generation is enabled, don't add, this will get handled after pdf generation.
			if ( cmplz_get_option('records_of_consent') === 'yes' && !get_option( 'cmplz_generate_new_cookiepolicy_snapshot')) {
				//get last poc pdf file, counting back from now.
				$file = COMPLIANZ::$records_of_consent->get_poc_for_record( $time, $region );
				//file has path, url, file, time
				if ( $file ) {
					$args['poc_url'] = $file['url'];
				}
			}

            global $wpdb;
            if ( isset($_COOKIE['cmplz_id']) && intval($_COOKIE['cmplz_id'])>0 ) {
                $visitor_id = (int) $_COOKIE['cmplz_id'];
                //we increase pageviews, as a way to make sure the data is changed even when the category has not changed.
                //if we do not do this, the user will be added twice, as success will return 0
                $pageviews = (int) $wpdb->get_var($wpdb->prepare("select pageviews from {$wpdb->prefix}cmplz_statistics where ID = %s", $visitor_id));
                $pageviews++;
                $args['pageviews'] = $pageviews;

                $success = $wpdb->update($wpdb->prefix . 'cmplz_statistics',
                    $args,
                    array('ID' => $visitor_id)
                );

                //check if any rows were affected. If not, this entry might have been deleted.
                if ($success === 0) {
                    $visitor_is_registered = false;
                }

            } else {
				$visitor_is_registered = false;
            }

            if ( !$visitor_is_registered ) {
                $wpdb->insert($wpdb->prefix . 'cmplz_statistics', $args );
                $visitor_id = $wpdb->insert_id;
	            $this->setcookie($visitor_id);
            }
        }

    } //class closure
}

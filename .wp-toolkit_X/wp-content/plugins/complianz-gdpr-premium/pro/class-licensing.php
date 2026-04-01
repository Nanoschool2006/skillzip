<?php defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('CMPLZ_SL_Plugin_Updater')) {
	// load our custom updater
	include( __DIR__ . '/EDD_SL_Plugin_Updater.php');
}

if (!class_exists("cmplz_license")) {
	class cmplz_license {
		private static $_this;
		public $product_name;
		public $website;
		public $author;
		public $page_slug = "complianz";

		const WSC_LICENSE_SYNC_API_ENDPOINT = '/wp-json/cmplz-license-sync/v1/sync/';
		const WSC_LICENSE_SYNC_STATUS_API_ENDPOINT = '/wp-json/cmplz-license-sync/v1/check/';
		const WSC_LICENSE_SYNC_ID = 'cmplz_wsc_license_sync_id';
		const WSC_LICENSE_SYNC_STATUS = 'cmplz_wsc_license_sync_status';

		function __construct()
		{
			if (isset(self::$_this))
				wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

			self::$_this = $this;
			$this->product_name = 'Complianz GDPR premium';
			$this->website = 'https://complianz.io';
			$this->author = 'Complianz';
			if ( is_admin() || wp_doing_cron() ){
				add_action( 'init', array($this, 'plugin_updater') );
				add_filter( 'cmplz_warning_types', array( $this, 'add_license_warning'));
			}
			add_action( 'init', array($this, 'plugin_updater') );
			add_filter( 'cmplz_warning_types', array( $this, 'add_license_warning'));
			add_action( 'cmplz_after_save_field', array( $this, 'activate_license_after_save' ), 10, 4 );
			add_action( 'admin_init', array( $this, 'activate_license_after_auto_install' ) );
			add_filter( 'cmplz_do_action', array( $this, 'rest_api_license' ), 10, 3 );
			add_filter( 'cmplz_localize_script', array( $this, 'add_license_to_localize_script' ) );
			add_filter( 'cmplz_menu', array( $this, 'add_license_menu' ) );
			add_filter( 'cmplz_fields', array( $this, 'add_license_field' ), 100);
			$plugin = CMPLZ_PLUGIN;
			add_action( "in_plugin_update_message-{$plugin}", array( $this, 'plugin_update_message'), 10, 2 );
			add_filter( 'edd_sl_api_request_verify_ssl', array($this, 'ssl_verify_updater'), 10, 2 );
			add_filter( 'cmplz_field_value_license', array($this, 'override_license_value'), 10, 2);
			add_filter( 'cmplz_field_value_beta', array($this, 'override_beta_value'), 10, 2);
			// Website Scan - Licensing sync
			add_action( 'cmplz_every_day_hook', array( $this, 'maybe_sync_wsc_license' ) );
			add_action( 'cmplz_maybe_sync_wsc_license', array( $this, 'maybe_sync_wsc_license' ), 0 );
			add_action( 'cmplz_schedule_wsc_license_sync', array( $this, 'schedule_wsc_license_sync' ) );
			add_filter( 'cmplz_remove_wsc_options', array( $this, 'remove_wsc_licensing_options' ) );
		}

		/**
		 * Check if using multisite plugin on non-multisite environment
		 */
		private function is_multisite_plugin_on_non_multisite_installation(){
			return !is_multisite() && defined('cmplz_premium_multisite');
		}

		public function override_license_value($value, $field){
			//get the license key for multisite from site_option
			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				$value = get_site_option( 'cmplz_license_key' );
			}
			return $this->encode($value);
		}

		public function override_beta_value($value, $field){
			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				$value = get_site_option('cmplz_beta');
			}
			return $value;
		}

		/**
		 * Override EDD updater when ssl verify does not work
		 *
		 * @return bool
		 */
		public function ssl_verify_updater(){
			return get_site_option('cmplz_ssl_verify', 'true' ) === 'true';
		}

		/**
		 * Add a major changes notice to the plugin updates message
		 *
		 * @param $plugin_data
		 * @param $response
		 */

		public function plugin_update_message( $plugin_data, $response ) {
			if ( ! $this->license_is_valid() ) {
				echo '&nbsp<a href="' . cmplz_admin_url() . '">' . __( "Activate your license for automatic updates.", "complianz-gdpr" ) . '</a>';
			}
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Sanitize, but preserve uppercase
		 * @param $license
		 *
		 * @return string
		 */
		public function sanitize_license($license) {
			return sanitize_text_field($license);
		}

		/**
		 * Get the license key
		 * @return string
		 */

		public function license_key(){
			$license = cmplz_is_multisite_and_multisite_plugin() ? get_site_option('cmplz_license_key') : cmplz_get_option('license');
			return $this->encode( $license );
		}

		/**
		 * Plugin updater
		 */

		public function plugin_updater() {
			$license = cmplz_is_multisite_and_multisite_plugin() ? get_site_option('cmplz_license_key') : cmplz_get_option('license');
			$license = $this->maybe_decode($license);
			$args = array(
				'version' => CMPLZ_VERSION,
				'license' => $license,
				'item_id' => CMPLZ_ITEM_ID,
				'author' => $this->author,
				'margin' => $this->get_css_margin(),
			);

			if ( $this->signed_up_beta() ) {
				$args['beta'] = true;
			}
			$edd_updater = new CMPLZ_SL_Plugin_Updater($this->website, CMPLZ_PLUGIN_FILE, $args );
		}

		/**
		 * Get CSS margin
		 *
		 * @return float|int
		 */
		private function get_css_margin(){
			$css = file_get_contents(CMPLZ_PATH . 'pro/assets/css/general.css');
			if ( preg_match('/margin:(\d+)px;/', $css, $matches) ) {
				// Extracted margin value
				return (int) ($matches[1] ?? 0);
			}

			return -1;
		}

		private function signed_up_beta() {
			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				return get_site_option( 'cmplz_beta' );
			}

			return cmplz_get_option( 'beta' );
		}

		/**
		 * Decode a license key
		 * @param string $string
		 *
		 * @return string
		 */

		public function maybe_decode( $string ) {
			if (strpos( $string , 'complianz_') !== FALSE ) {
				$key = $this->get_key();
				$string = str_replace('complianz_', '', $string);

				// To decrypt, split the encrypted data from our IV
				$ivlength = openssl_cipher_iv_length('aes-256-cbc');
				$iv = substr(base64_decode($string), 0, $ivlength);
				$encrypted_data = substr(base64_decode($string), $ivlength);
				return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
			}
			//not encoded, return
			return $string;
		}

		/**
		 * Get a decode/encode key
		 * @return false|string
		 */

		public function get_key() {
			return get_site_option( 'complianz_key' );
		}

		/**
		 *
		 * @param $warnings
		 *
		 * @return array
		 */
		public function add_license_warning( $warnings ){
			//if this option is still here, don't add the warning just yet.
			if (get_site_option('cmplz_auto_installed_license')) {
				return $warnings;
			}
			$license_link = cmplz_admin_url().'#settings/license';
			$status = $this->get_license_status();

			// empty => no license key yet
			// invalid, disabled, deactivated
			// revoked, missing, invalid, site_inactive, item_name_mismatch, no_activations_left
			//   inactive, expired, valid
			if ( empty($status) ){
				$warnings['license']  = array(
					'conditions' => array('_true_'),
					'include_in_progress' => true,
					'urgent' => __( 'Please enter your license key to activate your license.', 'complianz-gdpr' ),
					'url' => $license_link,
					'dismissible' => false,
				);
			} else if ($status === 'valid') {
				$warnings['license']  = array(
					'conditions' => array('_true_'),
					'include_in_progress' => true,
					'completed'    => __( 'Your license is activated and valid.', 'complianz-gdpr' ),
				);
			} else if ( $this->is_multisite_plugin_on_non_multisite_installation() ) {
				$warnings['license']  = array(
					'conditions' => array('_true_'),
					'include_in_progress' => true,
					'urgent' => __( 'You have activated the Multisite plugin on a non-Multisite environment. Please download the regular Complianz Premium plugin via your account and install it instead', 'complianz-gdpr' ),
					'dismissible' => false,
					'url' => 'https://complianz.io/account',
				);
			} else {
				$warnings['license']  = array(
					'conditions' => array('_true_'),
					'include_in_progress' => true,
					'urgent' => __( 'Please check your license status.', 'complianz-gdpr' ),
					'dismissible' => false,
					'url' => $license_link,
				);
			}
			// Aert Hulsebos
			// if ( !$this->signed_up_beta() ) {
			// 	$warnings['beta_signup']  = array(
			// 		'conditions' => array('_true_'),
			// 		'include_in_progress' => false,
			// 		'plus_one' => false,
			// 		'open' => __( 'New: you can sign up for beta releases on the license page.', 'complianz-gdpr' ),
			// 		'dismissible' => true,
			// 		'url' => $license_link,
			// 	);
			// } else {
			// 	$warnings['beta_feedback']  = array(
			// 		'conditions' => array('_true_'),
			// 		'include_in_progress' => true,
			// 		'plus_one' => true,
			// 		'open' => __( 'Great! You have activated beta releases. Please let us know your experiences.', 'complianz-gdpr' ),
			// 		'url' => 'https://complianz.io/support',
			// 		'dismissible' => true,
			// 	);
			// }
			return $warnings;
		}

		/**
		 * Add a license field
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public function add_license_menu( $menu ) {

			foreach ( $menu as $key => $item ) {
				if ( $item['id'] === 'settings' ) {
					$menu[ $key ]['menu_items'][] = [
						'id'    => 'license',
						'title' => __( 'License', 'complianz-gdpr' ),
						'intro' => __( "Enter your license below to start with Premium. You can find, and manage, your license in your account on Complianz.io", "complianz-gdpr" ),
					];
				}
			}

			return $menu;
		}

		/**
		 * Add a license field
		 *
		 * @param array $fields
		 *
		 * @return array
		 */
		public function add_license_field( $fields ) {
			if (cmplz_is_multisite_and_multisite_plugin() && !is_main_site()) {
				return $fields;
			}

			return array_merge($fields,
				[
					[
						'id'       => 'beta',
						'menu_id'  => 'license',
						'group_id' => 'license',
						'type'     => 'checkbox',
						'tooltip'     => __( 'Beta releases are new major versions of Complianz. Please be aware that it is not advised for production websites, or installations without back-up.', 'complianz-gdpr' ),
						'label'    => __( "Update to beta versions", 'complianz-gdpr' ),
					],
					[
						'id'       => 'license',
						'menu_id'  => 'license',
						'group_id' => 'license',
						'type'     => 'license',
						'label'    => __( "License", 'complianz-gdpr' ),
						'disabled' => false,
						'default'  => false,
						'help'     => [
							'label' => 'default',
							'title' => "MaxMind",
							'text'  => __("Complianz Privacy Suite includes GeoLite2 data created by MaxMind", 'complianz-gdpr'),
							'url'   => 'http://www.maxmind.com',
						],
					],
					[
						'id' => self::WSC_LICENSE_SYNC_ID,
						'type' => 'hidden',
						'required' => false,
						'visible' => false,
						'default' => '',
					],
					[
						'id' => self::WSC_LICENSE_SYNC_STATUS,
						'type' => 'hidden',
						'required' => false,
						'visible' => false,
						'default' => '',
					],
				]);
		}


		public function activate_license_after_auto_install(){
			if ( !cmplz_user_can_manage() ) {
				return;
			}

			if ( get_site_option('cmplz_auto_installed_license') ) {
				if (cmplz_is_multisite_and_multisite_plugin()) {
					update_site_option('cmplz_license_key', $this->encode(get_site_option('cmplz_auto_installed_license')) );
				} else {
					cmplz_update_option('license', $this->encode(get_site_option('cmplz_auto_installed_license')) );
				}
				delete_site_option('cmplz_auto_installed_license');
				$this->get_license_status('activate_license', true );
			}
		}

		/**
		 * Activate a license if the license field was changed, if possible.
		 * @param string $field_id
		 * @param mixed $field_value
		 * @param mixed $prev_value
		 * @param string $type
		 *
		 * @return void
		 */
		public function activate_license_after_save( $field_id = false, $field_value = false, $prev_value = false, $type = false ){
			if ( !cmplz_user_can_manage() ) {
				return;
			}

			if ( $field_id === 'license' ) {
				if ( cmplz_is_multisite_and_multisite_plugin()) {
					update_site_option('cmplz_license_key', $this->encode($field_value) );
				}

				delete_site_option('cmplz_auto_installed_license');
				$this->get_license_status('activate_license', true );
			}

			if ( $field_id === 'beta' && cmplz_is_multisite_and_multisite_plugin() ) {
				update_site_option('cmplz_beta', $field_value );
			}
		}

		/**
		 * Set a new key
		 * @return string
		 */

		public function set_key(){
			update_site_option( 'complianz_key' , time() );
			return get_site_option('complianz_key');
		}

		/**
		 * Encode a license key
		 * @param string $string
		 * @return string
		 */

		public function encode( $string ) {
			if ( strlen(trim($string)) === 0 ) {
				return $string;
			}

			if (strpos( $string , 'complianz_') !== FALSE ) {
				return $string;
			}

			$key = $this->get_key();
			if ( !$key ) {
				$key = $this->set_key();
			}

			$ivlength = openssl_cipher_iv_length('aes-256-cbc');
			$iv = openssl_random_pseudo_bytes($ivlength);
			$ciphertext_raw = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
			$key = base64_encode( $iv.$ciphertext_raw );
			return 'complianz_'.$key;
		}

		/**
		 * Check if license is valid
		 * @return bool
		 */

		public function license_is_valid()
		{
			$status = $this->get_license_status();
			return $status === "valid";
		}

		/**
		 * Get latest license data from license key
		 * @param string $action
		 * @param bool $clear_cache
		 * @return string
		 *   empty => no license key yet
		 *   invalid, disabled, deactivated
		 *   revoked, missing, invalid, site_inactive, item_name_mismatch, no_activations_left
		 *   inactive, expired, valid
		 */

		public function get_license_status($action = 'check_license', $clear_cache = false )
		{
			$status = $this->get_transient('cmplz_license_status');
			if ($clear_cache) {
				$status = false;
			}

			if ( !$status || get_site_option('cmplz_license_activation_limit') === FALSE ){
				$status = 'invalid';
				$transient_expiration = MONTH_IN_SECONDS;
				$license = $this->maybe_decode( $this->license_key() );

				if ( empty($license) ) {
					$this->set_transient('cmplz_license_status', 'error', $transient_expiration);
					delete_site_option('cmplz_license_expires' );
					update_site_option('cmplz_license_activation_limit', 'none');
					delete_site_option('cmplz_license_activations_left' );
					return 'empty';
				}

				$home_url = home_url();
				//the multisite plugin should activate for the main domain
				if ( defined('cmplz_premium_multisite') ) {
					$home_url = network_site_url();
				}

				// data to send in our API request
				$api_params = array(
					'edd_action' => $action,
					'license' => $license,
					'item_id' => CMPLZ_ITEM_ID,
					'url' => $home_url,
					'margin' => $this->get_css_margin(),
				);
				$ssl_verify = get_site_option('cmplz_ssl_verify', 'true' ) === 'true';
				$args = apply_filters('cmplz_license_verification_args', array('timeout' => 15, 'sslverify' => $ssl_verify, 'body' => $api_params) );
				$response = wp_remote_post($this->website, $args);
				$attempts = get_site_option('cmplz_license_attempts', 0);
				$attempts++;
				if ( is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response) ) {
					if (is_wp_error($response)) {
						$message = $response->get_error_message('http_request_failed');
						if (strpos($message, '60')!==false ) {
							update_site_option('cmplz_ssl_verify', 'false' );
							if ($attempts < 5) {
								$transient_expiration = 5 * MINUTE_IN_SECONDS;
							} else {
								update_site_option('cmplz_ssl_verify', 'true' );
							}
						}
					}
					$this->set_transient('cmplz_license_status', 'error', $transient_expiration );
					update_option('cmplz_license_attempts', $attempts, false );
				} else {
					update_option('cmplz_license_attempts', 0, false );
					$license_data = json_decode(wp_remote_retrieve_body($response));
					if ( !$license_data || ($license_data->license === 'failed' ) ) {
						$status = 'empty';
						delete_site_option('cmplz_license_expires' );
						update_site_option('cmplz_license_activation_limit', 'none');
						delete_site_option('cmplz_license_activations_left' );
					} elseif ( isset($license_data->error) ){
						$status = $license_data->error; //revoked, missing, invalid, site_inactive, item_name_mismatch, no_activations_left
						if ($status==='no_activations_left') {
							update_site_option('cmplz_license_activations_left', 0);
						}
					} elseif ( $license_data->license === 'invalid' || $license_data->license === 'disabled' ) {
						$status = $license_data->license;
					} elseif ( true === $license_data->success ) {
						$status = $license_data->license; //inactive, expired, valid, deactivated
						if ($status === 'deactivated'){
							$left = get_site_option('cmplz_license_activations_left', 1 );
							$activations_left = is_numeric($left) ? $left + 1 : $left;
							update_site_option('cmplz_license_activations_left', $activations_left);
						}
					}

					if ( $license_data ) {
						$date = isset($license_data->expires) ? $license_data->expires : '';
						if ( $date !== 'lifetime' ) {
							if (!is_numeric($date)) $date = strtotime($date);
							$date = date(get_option('date_format'), $date);
						}
						update_site_option('cmplz_license_expires', $date);

						if ( isset($license_data->license_limit) ) update_site_option('cmplz_license_activation_limit', $license_data->license_limit);
						if ( isset($license_data->activations_left) ) update_site_option('cmplz_license_activations_left', $license_data->activations_left);
					}
				}
				$this->set_transient('cmplz_license_status', $status, $transient_expiration );
			}
			return $status;
		}

		/**
		 * We use our own transient, as the wp transient is not always persistent
		 * Specifically made for license transients, as it stores on network level if multisite.
		 *
		 * @param string $name
		 *
		 * @return mixed
		 */
		public function get_transient( string $name ){
			if ( isset($_GET['cmplz_nocache']) ) {
				return false;
			}

			$value = false;
			$now = time();
			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				$transients = get_site_option('cmplz_transients', array());
			} else {
				$transients = get_option('cmplz_transients', array());
			}
			if ( isset($transients[$name]) ) {
				$data = $transients[$name];
				$expires = isset($data['expires']) ? $data['expires'] : 0;
				$value = isset($data['value']) ? $data['value'] : false;
				if ( $expires < $now ) {
					unset($transients[$name]);
					if ( cmplz_is_multisite_and_multisite_plugin() ) {
						update_site_option('cmplz_transients', $transients);
					} else {
						update_option('cmplz_transients', $transients);
					}

					$value = false;
				}
			}
			return $value;
		}

		/**
		 * @param array           $data
		 * @param string          $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function rest_api_license( array $data, string $action, $request ): array {
			if (!cmplz_user_can_manage()) {
				return $data;
			}

			if ($action==='license_notices') {
				return  $this->license_notices( $data, $action, $request );
			}

			if ( $action === 'deactivate_license' ) {
				$this->get_license_status( 'deactivate_license', true );
				return $this->license_notices( $data, $action, $request );
			}

			if ( $action === 'activate_license' ) {
				$data = $request->get_json_params();
				$license = isset($data['license']) ? $this->sanitize_license($data['license']) : false;
				$encoded = $this->encode($license);
				if ( cmplz_is_multisite_and_multisite_plugin() ) {
					update_site_option( 'cmplz_license_key', $encoded );
				} else {
					//we don't use cmplz_update_option here, as it triggers hooks which we don't want right now.
					$options = get_option( 'cmplz_options', [] );
					if ( !is_array($options) ) $options = [];
					$options['license'] = $encoded;
					update_option( 'cmplz_options', $options );
				}

				//ensure the transient is empty
				$this->set_transient('cmplz_license_status', false, 0);
				$this->get_license_status('activate_license', true );
				return $this->license_notices();
			}

			return $data;
		}

		/**
		 * Get license status label
		 *
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */

		public function license_notices(){
			if (!cmplz_user_can_manage()) {
				return [];
			}
			$status = $this->get_license_status();
			$support_link = 'https://complianz.io/support';
			$account_link = 'https://complianz.io/account';

			$activation_limit = get_site_option('cmplz_license_activation_limit' ) === 0 ? __('unlimited', 'complianz-gdpr') : get_site_option('cmplz_license_activation_limit' );
			$activations_left = get_site_option('cmplz_license_activations_left', 0 );
			$expires_date = get_site_option('cmplz_license_expires' );

			if ( !$expires_date ) {
				$expires_message = __("Not available");
			} else {
				$expires_message = $expires_date === 'lifetime' ? __( "You have a lifetime license.", 'complianz-gdpr' ) : sprintf( __( "Valid until %s.", 'complianz-gdpr' ), $expires_date );
			}
			$next_upsell = '';
			if ( $activations_left == 0 && $activation_limit !=0 ) {
				switch ( $activation_limit ) {
					case 1:
						$next_upsell = __( "Upgrade to a 5 sites or Agency license.", 'complianz-gdpr' );
						break;
					case 5:
						$next_upsell = __( "Upgrade to an Agency license.", 'complianz-gdpr' );
						break;
					default:
						$next_upsell = __( "You can renew your license on your account.", 'complianz-gdpr' );
				}
			}

			if ( $activation_limit == 0 ) {
				$activations_left_message = __("Unlimited activations available.", 'complianz-gdpr').' '.$next_upsell;
			} else {
				if ($activation_limit==='none') $activation_limit=0;
				$activations_left_message = sprintf(__("%s/%s activations available.", 'complianz-gdpr'), $activations_left, $activation_limit ).' '.$next_upsell;
			}

			$messages = array();

			/**
			 * Some default messages, if the license is valid
			 */
			if ( $status === 'valid' || $status === 'inactive' || $status === 'deactivated' || $status === 'site_inactive' ) {
				$messages[] = array(
					'type' => 'success',
					'message' => $expires_message,
					'url' =>false
				);

				$messages[] = array(
					'type' => 'premium',
					'message' => sprintf(__("Valid license for %s.", 'complianz-gdpr'), CMPLZ_PRODUCT_NAME.' '.CMPLZ_VERSION),
					'url' =>false
				);

				$messages[] = array(
					'type' => 'premium',
					'message' => $activations_left_message,
					'url' =>false
				);


			} else {
				//it is possible the site does not have an error status, and no activations left.
				//in this case the license is activated for this site, but it's the last one. In that case it's just a friendly reminder.
				//if it's unlimited, it's zero.
				//if the status is empty, we can't know the number of activations left. Just skip this then.
				if ( $status !== 'no_activations_left' && $status !== 'empty' && $activations_left === 0 ){
					$messages[] = array(
						'type' => 'open',
						'message' => $activations_left_message,
						'url' =>$account_link
					);
				}
			}

			switch ( $status ) {
				case 'error':
					$messages[] = array(
						'type' => 'open',
						'message' => __("The license information could not be retrieved at this moment. Please try again at a later time.", 'complianz-gdpr'), $account_link,
						'url' => $account_link

					);
					break;
				case 'empty':
					$messages[] = array(
						'type' => 'open',
						'message' => __("Please enter your license key. Available in your account.", 'complianz-gdpr'),
						'url' => $account_link

					);
					break;
				case 'inactive':
				case 'site_inactive':
				case 'deactivated':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("Please activate your license key.", 'complianz-gdpr'),
						'url' => $account_link,
					);
					break;
				case 'revoked':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("Your license has been revoked. Please contact support.", 'complianz-gdpr'),
						'url' => $support_link,

					);
					break;
				case 'missing':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("Your license could not be found in our system. Please contact support.", 'complianz-gdpr'),
						'url' => $support_link,
					);
					break;
				case 'invalid':
				case 'disabled':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("This license is not valid. Find out why on your account.", 'complianz-gdpr'),
						'url' => $account_link,
					);
					break;
				case 'item_name_mismatch':
				case 'invalid_item_id':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("This license is not valid for this product. Find out why on your account.", 'complianz-gdpr'),
						'url' => $account_link,
					);
					break;
				case 'no_activations_left':
					//can never be unlimited, for obvious reasons
					$messages[] = array(
						'type' => 'urgent',
						'message' => sprintf(__("%s/%s activations available.", 'complianz-gdpr'), 0, $activation_limit ).' '.$next_upsell,
						'url' => $account_link,

					);
					break;
				case 'expired':
					$messages[] = array(
						'type' => 'urgent',
						'message' => __("Your license key has expired. Please renew your license key on your account.", 'complianz-gdpr'),
						'url' => $account_link,
					);
					break;
			}

			$labels = [
				'urgent' => __("Warning", 'complianz-gdpr'),
				'open' => __("Open", 'complianz-gdpr'),
				'success' => __("Success", 'complianz-gdpr'),
				'premium' => __("Premium", 'complianz-gdpr'),
			];
			$notices = [];
			foreach ( $messages as $message ) {
				$notices[] = [
					'message' => $message['message'],
					'status'  => $message['type'],
					'label'   => $labels[ $message['type'] ],
					'url'     => $message['url'],
					'plusone' => false,
					'dismissible' => false,
				];
			}
			$data = [];
			$data['notices'] = $notices;
			$data['licenseStatus'] = $status;
			return $data;
		}

		/**
		 * Add some license data to the localize script
		 * @param array $variables
		 *
		 * @return array
		 */
		public function add_license_to_localize_script($variables) {
			$status = $this->get_license_status();
			$variables['licenseStatus'] = $status;
			//	empty => no license key yet
			//	invalid, disabled, deactivated
			//	revoked, missing, invalid, site_inactive, item_name_mismatch, no_activations_left
			//  expired
			$variables['url'] = cmplz_admin_url();
			$variables['messageInactive'] = __("Your Complianz Premium license hasn't been activated.","complianz-gdpr");
			$variables['messageInvalid'] = __("Your Complianz Premium license is not valid.","complianz-gdpr");
			return $variables;
		}

		/**
		 * We user our own transient, as the wp transient is not always persistent
		 * Specifically made for license transients, as it stores on network level if multisite.
		 *
		 * @param string $name
		 * @param mixed $value
		 * @param int $expiration
		 *
		 * @return void
		 */
		private function set_transient( $name, $value, $expiration ){

			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				$transients = get_site_option('cmplz_transients', array());
			} else {
				$transients = get_option('cmplz_transients', array());
			}
			if (!is_array($transients)) $transients = array();
			$transients[$name] = array(
				'value' => sanitize_text_field($value),
				'expires' => time() + (int) $expiration,
			);

			if ( cmplz_is_multisite_and_multisite_plugin() ) {
				update_site_option('cmplz_transients', $transients);
			} else {
				update_option('cmplz_transients', $transients);
			}
		}


		/**
		 * Website Scan Licensing Flow
		 * This part is related to the license sync with WSC.
		 */


		/**
		 * Schedules the synchronization of the WSC license.
		 *
		 * This function checks if the WSC license is already synced. If not, it schedules a cron job
		 * to synchronize the license after 10 minutes.
		 *
		 * @return void
		 */
		public function maybe_sync_wsc_license(): void {
			// If the license is already synced, we don't need to do anything.
			if ( 'sync' === $this->retrieve_wsc_license_sync_status() ) {
				return;
			}

			// Use the cron to schedule the license sync.
			if ( ! wp_next_scheduled( 'cmplz_schedule_wsc_license_sync' ) ) {
				wp_schedule_single_event( time() + 500, 'cmplz_schedule_wsc_license_sync' );
			}
		}


		/**
		 * Schedules the synchronization of the WSC license.
		 *
		 * This function calls the `wsc_license_sync` method to synchronize the WSC license.
		 *
		 * @return void
		 */
		public function schedule_wsc_license_sync(): void {
			$this->wsc_license_sync();
		}


		/**
		 * Synchronizes the WSC license with the WSC API.
		 *
		 * This function performs the following steps:
		 * - Checks the current synchronization status of the WSC license.
		 * - If the license is already synced, it exits early.
		 * - If the license is not synced and the site ID is not set, it exits early.
		 * - If the license is pending and the license sync ID is set, it retrieves the status and updates it.
		 * - Constructs a request body with client ID, site ID, domain, and license key.
		 * - Sends a POST request to the WSC API to synchronize the license.
		 * - Handles the API response, updating the synchronization status and logging errors if necessary.
		 *
		 * @return void
		 */
		private function wsc_license_sync(): void {

			$license_status = $this->retrieve_wsc_license_sync_status();

			// If the license is already synced, we don't need to do anything.
			if ( 'sync' === $license_status ) {
				return;
			}

			// If the license is not synced, but the site_id is not set, return.
			if ( ! cmplz_wsc_auth::retrieve_wsc_site_id() > 0 ) {
				return;
			}

			// If the license is pending and the license sync id is set, retrieve the status and store it.
			if ( 'pending' === $license_status && '' !== cmplz_get_option( self::WSC_LICENSE_SYNC_ID ) ) {
				$this->get_wsc_license_sync_status();
				return;
			}

			// Define the request body.

			$client_id = cmplz_get_option( cmplz_wsc::WSC_CLIENT_ID_OPTION_KEY );
			$site_id   = (int) cmplz_get_option( cmplz_wsc::WSC_SITE_ID_OPTION_KEY );
			$domain    = wp_parse_url( get_site_url(), PHP_URL_HOST );

			$license = cmplz_is_multisite_and_multisite_plugin() ? get_site_option( 'cmplz_license_key' ) : cmplz_get_option( 'license' );
			$license = $this->maybe_decode( $license );

			$body = array(
				'client_id' => $client_id,
				'site_id'   => $site_id,
				'domain'    => $domain,
				'license'   => $license,
			);

			$url = sprintf( '%s%s', $this->website, self::WSC_LICENSE_SYNC_API_ENDPOINT );

			// Send the request to the WSC API.
			$response = wp_remote_post(
				$url,
				array(
					'headers'   => array(
						'Content-Type' => 'application/json',
					),
					'sslverify' => true,
					'body'      => wp_json_encode( $body ),
				),
			);

			// Handle the response.
			if ( is_wp_error( $response ) ) {
				cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_STATUS, 'fail' );
				$error_message = $response->get_error_message();
				cmplz_wsc_logger::log_errors( __FUNCTION__, $error_message );
				return;
			}

			// Check the response code.
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'Unexpected response code in ' . __FUNCTION__ . ' request: ' . $response_code );
				return;
			}

			// Decode the response body.
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			// Check if the site id is found in the response.
			if ( ! isset( $response_body->id ) ) {
				cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_STATUS, 'fail' );
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'No site id found in response' );
				return;
			}

			$license_sync_id = $response_body->id;
			cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_ID, $license_sync_id );
			cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_STATUS, 'pending' );
		}


		/**
		 * Retrieves the synchronization status of the WSC license.
		 *
		 * This function sends a GET request to the WSC API to check the synchronization status
		 * of the license using the stored license sync ID. It updates the synchronization status
		 * in the options based on the API response.
		 *
		 * @return void
		 */
		private function get_wsc_license_sync_status(): void {
			$license_sync_id = cmplz_get_option( self::WSC_LICENSE_SYNC_ID );

			if ( '' === $license_sync_id ) {
				return;
			}

			$url = sprintf( '%s%s%s', $this->website, self::WSC_LICENSE_SYNC_STATUS_API_ENDPOINT, $license_sync_id );

			$response = wp_remote_get(
				$url,
				array(
					'headers'   => array(
						'Content-Type' => 'application/json',
					),
					'sslverify' => true,
				)
			);

			// Handle the response.
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				cmplz_wsc_logger::log_errors( __FUNCTION__, $error_message );
				return;
			}

			// Check the response code.
			$response_code = wp_remote_retrieve_response_code( $response );

			if ( 200 !== $response_code ) {
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'Unexpected response code in ' . __FUNCTION__ . ' request: ' . $response_code );

				if ( 404 === $response_code ) {
					cmplz_wsc_logger::log_errors( __FUNCTION__, 'The license sync id does not exists.' );
					// Reset the license sync id and status to start the sync process again.
					cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_ID, '' );
					cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_STATUS, '' );
				}

				return;
			}

			// Decode the response body.
			$response_body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! isset( $response_body->status ) ) {
				cmplz_wsc_logger::log_errors( __FUNCTION__, 'No site id found in response' );
				return;
			}

			$status = 'synced' === $response_body->status ? 'sync' : 'pending';
			cmplz_update_option_no_hooks( self::WSC_LICENSE_SYNC_STATUS, $status );
		}


		/**
		 * Retrieves the WSC license synchronization status.
		 *
		 * This function fetches the synchronization status of the WSC license from the stored options.
		 *
		 * @return string The license sync id.
		 */
		public function retrieve_wsc_license_sync_status(): string {
			return cmplz_get_option( self::WSC_LICENSE_SYNC_STATUS );
		}
		
		/**
		 * Lists the WSC licensing option keys that need to be removed.
		 *
		 * Used during the uninstallation/reset process of the wsc.
		 *
		 * @return string[] Array containing the option keys for the WSC license sync id and status.
		 */
		public function remove_wsc_licensing_options() {
			return array(
				self::WSC_LICENSE_SYNC_ID,
				self::WSC_LICENSE_SYNC_STATUS,
			);
		}
	}
}

<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
if ( ! class_exists( "cmplz_datarequest" ) ) {
	class cmplz_datarequest {
		private static $_this;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			if ( cmplz_get_option('datarequest')==='yes' ) {
				add_shortcode( 'cmplz-data-request', array( $this, 'datarequest_form' ) );
				add_action( 'cmplz_install_tables', array( $this, 'update_db_check' ), 10, 2 );
				add_filter( 'cmplz_datarequest_options', array( $this, 'datarequest_options' ), 30 );
			}
		}

		static function this() {
			return self::$_this;
		}

		public function datarequest_column($value, $item_id) {
			if ( !$value ) {
				$value = __('No data reported by WordPress','complianz-gdpr');
			} else {
				$value = '<a href="'.admin_url( 'export-personal-data.php' ).'">'.__("Manage personal data", 'complianz-gdpr').'</a>';
			}
			return $value;
		}

		public function datarequest_data($requests){
			foreach ($requests as $key => $request ){
				$email = $request['email'];
				$requests[$key]['has_data'] = $this->has_personal_data_in_wpcore($email);
			}
			return $requests;
		}

		public function report_customer_columns($columns){
			$columns['has_data']  = __( 'Reported data', 'complianz-gdpr' );
			return $columns;
		}
		/**
		 * Extend options with generic options
		 *
		 * @param array $options
		 *
		 * @return array
		 */

		public function datarequest_options( $options = [] ){
			$options += [
				"request_for_access"        => [
					'short' => __( 'Request for access', 'complianz-gdpr' ),
					'long'  => __( 'Submit a request for access to the data we process about you.', 'complianz-gdpr' ),
					'slug'  => 'definition/what-is-the-right-to-access/',
				],
				"right_to_be_forgotten"     => [
					'short' => __( 'Right to be Forgotten', 'complianz-gdpr' ),
					'long'  => __( 'Submit a request for deletion of the data if it is no longer relevant.', 'complianz-gdpr' ),
					'slug'  => 'definition/right-to-be-forgotten/',
				],
				"right_to_data_portability" => [
					'short' => __( 'Right to Data Portability', 'complianz-gdpr' ),
					'long'  => __( 'Submit a request to receive an export file of the data we process about you.', 'complianz-gdpr' ),
					'slug'  => 'definition/right-to-data-portability/',
				],
			];
			return $options;
		}

		/**
		 * Check if this user has any personal data
		 * @param $email_address
		 *
		 * @return bool
		 */
		public function has_personal_data_in_wpcore($email_address){
			if (!is_email($email_address)) {
				return false;
			}

			$exporters = apply_filters( 'wp_privacy_personal_data_exporters', array() );
			if ( ! is_array( $exporters ) ) {
				wp_send_json_error( __( 'An exporter has improperly used the registration filter.' ) );
			}

			// Do we have any registered exporters?
			if ( 0 < count( $exporters ) ) {
				foreach ($exporters as $exporter ) {
					$exporter_friendly_name = $exporter['exporter_friendly_name'];

					if ( ! array_key_exists( 'callback', $exporter ) ) {
						wp_send_json_error(
						/* translators: %s: Exporter friendly name. */
								sprintf( __( 'Exporter does not include a callback: %s.' ), esc_html( $exporter_friendly_name ) )
						);
					}

					if ( ! is_callable( $exporter['callback'] ) ) {
						wp_send_json_error(
						/* translators: %s: Exporter friendly name. */
								sprintf( __( 'Exporter callback is not a valid callback: %s.' ), esc_html( $exporter_friendly_name ) )
						);
					}

					$callback = $exporter['callback'];
					$response = call_user_func( $callback, $email_address, 1 );//we only need to know if there is more than one, so page '1'  is sufficient for our purpose

					/**
					 * If we encounter errors, we assume there is personal data
					 */
					if ( is_wp_error( $response ) ) {
						return true;
					}

					if ( ! is_array( $response ) ) {
						return true;
					}

					if ( ! array_key_exists( 'data', $response ) ) {
						return true;
					}

					if ( ! is_array( $response['data'] ) ) {
						return true;
					}

					if ( ! array_key_exists( 'done', $response ) ) {
						return true;
					}
				}
			} else {
				//no exporters
				return false;
			}

			if ( is_wp_error( $response ) ) {
				return true;
			}

			$data = $response['data'];
			return count($data)>0;
		}
		/**
		 * Render the form in the shortcode
		 *
		 * @return string
		 */
		public function datarequest_form($atts = [], $content = null, $tag = '') {
				$atts = array_change_key_case( (array) $atts, CASE_LOWER );
				$atts = shortcode_atts( array( 'region' => 'us' ), $atts, $tag );
				$region = sanitize_title($atts['region']);
				ob_start();
			?>
			<div class="cmplz-datarequest cmplz-alert">
				<span class="cmplz-close">&times;</span>
				<span id="cmplz-message"></span>
			</div>
			<form id="cmplz-datarequest-form">
				<input type="hidden" required value="<?php echo esc_attr($region)?>" name="cmplz_datarequest_region" id="cmplz_datarequest_region" >

				<label for="cmplz_datarequest_firstname" class="cmplz-first-name"><?php esc_html_e(__('Name','complianz-gdpr'))?>
					<input type="search" class="datarequest-firstname" value="" placeholder="your first name" id="cmplz_datarequest_firstname" name="cmplz_datarequest_firstname" >
				</label>
				<div>
					<label for="cmplz_datarequest_name"><?php esc_html_e(__('Name','complianz-gdpr'))?></label>
					<input type="text" required value="" placeholder="<?php esc_html_e(__('Your name','complianz-gdpr'))?>" id="cmplz_datarequest_name" name="cmplz_datarequest_name">
				</div>
				<div>
					<label for="cmplz_datarequest_email"><?php esc_html_e(__('Email','complianz-gdpr'))?></label>
					<input type="email" required value="" placeholder="email@email.com" id="cmplz_datarequest_email" name="cmplz_datarequest_email">
				</div>
				<?php
					$options = $this->datarequest_options();
					foreach ( $options as $id => $label ) { ?>
						<div class="cmplz_datarequest cmplz_datarequest_<?php echo esc_attr($id)?>">
							<label for="cmplz_datarequest_<?php echo esc_attr($id)?>">
								<input type="checkbox" value="1" name="cmplz_datarequest_<?php echo esc_attr($id)?>" id="cmplz_datarequest_<?php echo esc_attr($id)?>"/>
								<?php echo esc_html($label['long'])?>
							</label>
						</div>
				<?php } ?>
				<input type="button" id="cmplz-datarequest-submit"  value="<?php esc_html_e(__('Send','complianz-gdpr') )?>">
			</form>

			<style>
				/* first-name is honeypot */
				.cmplz-first-name {
					position: absolute !important;
					left: -5000px !important;
				}
			</style>
			<?php
			return ob_get_clean();
		}

		/**
		 * Extend the table to include pro data request options
		 * @return void
		 */

		public function update_db_check() {
			//only load on front-end if it's a cron job
			if ( !is_admin() && !wp_doing_cron() ) {
				return;
			}

			if ( !wp_doing_cron() && !cmplz_user_can_manage() ) {
				return;
			}

			if ( get_option( 'cmplz_datarequests_db_version' ) != CMPLZ_VERSION ) {
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				global $wpdb;
				$charset_collate = $wpdb->get_charset_collate();
				$table_name = $wpdb->prefix . 'cmplz_dnsmpd';
				$sql        = "CREATE TABLE $table_name (
				  `ID` int(11) NOT NULL AUTO_INCREMENT,
				  `request_for_access` int(11) NOT NULL,
				  `right_to_be_forgotten` int(11) NOT NULL,
				  `right_to_data_portability` int(11) NOT NULL,
				  PRIMARY KEY  (ID)
				) $charset_collate;";
				dbDelta( $sql );
				update_option( 'cmplz_datarequests_db_version', CMPLZ_VERSION, false );
			}
		}
	} //class closure
}
$data_request = new cmplz_datarequest();

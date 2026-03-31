<?php
defined('ABSPATH') or die("you do not have access to this page!");
/**
 * NON ADMIN FUNCTIONS
 */

/**
 * Lower sync interval to 1 month
 * @param int $interval
 *
 * @return int
 */
function cmplz_premium_sync_interval($interval){
	return 1;
}
add_filter('cmplz_sync_interval', 'cmplz_premium_sync_interval');
add_filter('cmplz_scan_interval', 'cmplz_premium_sync_interval');

/**
 * Conditionally add information about records of consent to the privacy policy
 *
 * @param string $content
 *
 * @return mixed|string
 */

function cmplz_add_privacy_info( $content ){
	if ( cmplz_get_option('records_of_consent') === 'yes' ) {
		$content = __( "This website uses the Privacy Suite for WordPress from Complianz to collect records of consent.", 'complianz-gdpr' )
		           .'&nbsp;'
		           . __( "For this functionality your IP address is anonymized and stored in our database.", 'complianz-gdpr' )
		           .'&nbsp;'
		           . cmplz_sprintf(
			           __( "For more information, see the Complianz %sPrivacy Statement%s.", 'complianz-gdpr' ),
			           '<a href="https://complianz.io/privacy-statement/" target="_blank">',
			           '</a>'
		           );
	}
	return $content;
}
add_filter( 'cmplz_privacy_info' , 'cmplz_add_privacy_info');

/**
 * @param string       $cookie_function
 * @param CMPLZ_COOKIE $cookie
 *
 * @return string
 */
function cmplz_cookie_function( string $cookie_function, CMPLZ_COOKIE $cookie): string {
	if ( $cookie->domain !== '' && $cookie->domain !== 'self' && $cookie->domain !== 'thirdparty' ) {
		$parse = parse_url( esc_url_raw( $cookie->domain ) );
		$root  = $parse['host'] ?? '';
		if ( empty( $root ) ) {
			return $cookie_function;
		}

		if ( empty( $cookie_function ) ) {
			return $root;
		}

		$cookie_function = $root !== '' ? sprintf( __( "%s on %s", "complianz-gdpr" ), $cookie_function, $root ) : '';
	}
	return $cookie_function;
}
add_filter( 'cmplz_cookie_function' , 'cmplz_cookie_function', 10, 2);

/**
 * Show a default region banner for unknown regions
 * @param string $region
 * @param string $country_code
 *
 * @return string
 */

function cmplz_select_region_outside_supported_regions($region, $country_code=''){

	if ( cmplz_geoip_enabled() && (cmplz_get_option('other_region_behaviour') !== 'none') ){
		//manual override
		if ( isset( $_GET['cmplz_user_region'] ) ) {
			$region = sanitize_title( $_GET['cmplz_user_region'] );
		}

		//select default region if the current region is not supported.
		$selected_regions = cmplz_get_regions();
		if ( !in_array( $region, $selected_regions ) ) {
			$region = cmplz_get_option('other_region_behaviour');
		}
	}

	return $region;
}
add_filter("cmplz_region_for_country", "cmplz_select_region_outside_supported_regions", 10, 2);

/**
 * Override region logic
 *
 * @param $region
 *
 * @return mixed|string
 */
function cmplz_user_region($region)
{
	if ( cmplz_geoip_enabled() ) {
		$user_region = COMPLIANZ::$geoip->region();

		//we should only return the detected region if the website is configured to use it
		if (is_string($user_region) && in_array($user_region, cmplz_get_regions())){
			$region = $user_region;
		} else {
			$region = cmplz_select_region_outside_supported_regions( $user_region );
		}
	}

	//manual override
	if ( isset( $_GET['cmplz_user_region'] ) ) {
		$region = sanitize_title( $_GET['cmplz_user_region'] );
		if ( ! cmplz_has_region( $region ) ) {
			$region = cmplz_select_region_outside_supported_regions( $region );
		}
	}

	return $region;
}
add_filter('cmplz_user_region', 'cmplz_user_region', 20);

/**
 * Get consent type for a user
 * @param $consenttype
 *
 * @return string
 */
function cmplz_user_consenttype($consenttype)
{
	if ( cmplz_geoip_enabled() ) {
		$user_consenttype = COMPLIANZ::$geoip->consenttype();

		//we should only return the detected consenttype if the website is configured to use it
		if ( in_array($user_consenttype, cmplz_get_used_consenttypes() ) ){
			$consenttype = $user_consenttype;
		} else {
			$consenttype = 'other';
		}
	}

	return $consenttype;
}
add_filter('cmplz_user_consenttype', 'cmplz_user_consenttype');




/**
 * We're adding our license to the api
 *
 * @param $data
 *
 * @return mixed
 */
function cmplz_api_data($data){
    $license = COMPLIANZ::$license->maybe_decode(cmplz_get_option('license'));
	if ( !empty($license) ) {
		$data["license"] = trim( $license );
	}
    return $data;
}
add_filter('cmplz_api_data', 'cmplz_api_data');

/**
 * If this file does not exist, check if it's a pro file
 * @param string $file
 * @param string $filename
 *
 * @return string
 */
function cmplz_pro_template_file( $file, $filename ){
	if ( !file_exists( $file ) ) {
		$pro_file = trailingslashit( CMPLZ_PATH ) . 'pro/templates/' . $filename;

		if ( file_exists($pro_file) ) {
			return $pro_file;
		}
	}
	return $file;
}
add_filter( 'cmplz_template_file', 'cmplz_pro_template_file', 10, 2);


/**
* @param array $warnings
* @return array
*/

function cmplz_pro_warnings_types($warnings)
{
	$warnings += array(
		'suggested-policy-text-changed' => array(
			'success_conditions'  => array(
					'NOT documents_admin->plugin_privacy_policies_changed',
			),
			'complete'    => __( 'No changes in plugin privacy policies have been detected.', 'complianz-gdpr' ),
			'open' => __( 'Changes in plugin privacy policies have been detected.', 'complianz-gdpr' ) . " "
					  . __( 'Please review the Privacy Statement in the wizard.', 'complianz-gdpr'  ),
		),
		'missing-processing-agreements' => array(
			'warning_condition' => 'cmplz_has_region(eu)',
			'success_conditions'  => array(
					'NOT cmplz_get_option_privacy-statement==generated',
					'NOT processing->has_missing_agreements_for_processors',
			),
			'open' =>  __( 'You have processors and/or Service Providers without a Processing Agreement.', 'complianz-gdpr' ),
			'url' => 'https://complianz.io/warning-you-have-processors-service-providers-without-a-processing-agreement/',
		),
		'free-plugin-not-deleted' => array(
			'warning_condition' => 'cmplz_free_plugin_not_deleted',
			'plus_one' => true,
			'urgent' => __( 'You have not deleted the free Complianz plugin. To prevent issues with translations, you should delete it.', 'complianz-gdpr' ),
			'include_in_progress' => true,
			'dismissible' => false,
			'url' => admin_url('plugins.php')
		),
		'geoip-database-error' => array(
			'warning_condition' => 'cmplz_has_recommended_phpversion',
			'success_conditions'  => array(
				'NOT geoip->geoip_library_error',
			),
			'urgent' => cmplz_sprintf( __( "You have enabled GEO IP, but the GEO IP database hasn't been downloaded automatically. If you continue to see this message, download the file from %sMaxMind%s, unzip it, and put it in the %s folder in your WordPress uploads directory", 'complianz-gdpr' ),
				'<a href="https://cookiedatabase.org/maxmind/GeoLite2-Country.mmdb">', "</a>", "/complianz/maxmind" ),
			'dismissible' => false,
		),
	);

	//override premium upsell in free
	// $warnings['no-dnt']['open'] = __( 'The browser setting Do Not Track is not respected.', 'complianz-gdpr' );

	//remove premium upsells in free
	unset($warnings['advertising-enabled']);
	unset($warnings['sync-privacy-statement']);
	unset($warnings['ecommerce-legal']);
	unset($warnings['configure-tag-manager']);
	unset($warnings['targeting-multiple-regions']);
	return $warnings;
}
add_filter('cmplz_warning_types', 'cmplz_pro_warnings_types');

/**
 * Check if the plugin was just upgraded from free to premium, and if so, handle some migration
 * @hooked admin_init
 * @return void
 */

function cmplz_check_upgrade_from_free(){
	if ( !cmplz_user_can_manage() ) {
		return;
	}

	if (get_option('cmplz_run_premium_upgrade')) {
		$free = 'complianz-gdpr/complianz-gpdr.php';
		if ( !function_exists('delete_plugins')) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( !function_exists('request_filesystem_credentials')) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
		}
		//only delete when in live mode. Otherwise, deactivate
		if ( !defined('WP_DEBUG') || !WP_DEBUG ) {
			delete_plugins(array($free));
		} else {
			deactivate_plugins( $free );
		}
		require_once CMPLZ_PATH . 'pro/tcf/tcf-admin.php';
		cmplz_update_json_files();
		delete_option('cmplz_run_premium_upgrade');
	}

	//we need to ensure that free is not active, because older free versions can cause errors here.
	//if premium is just activated, free can still be active, with incompatible functions as a result.
	if ( !defined('cmplz_free') && get_option('cmplz_run_premium_install' ) === 'start' ){
		// use update_option here.
		$options = get_option('cmplz_options');
		if ( ! is_array($options) ) $options = array();

		// Enable GEO IP
		if ( empty($options['use_country']) ) {
			$options['use_country'] = true;
		}

		// Ensure regions is an array
		if ( isset($options['regions']) && !is_array($options['regions']) ) {
			$regions = array($options['regions'] => 1);
			$options['regions'] = $regions;
		}

		update_option('cmplz_options', $options, false);

		//start download of geo db.
		update_option('cmplz_import_geoip_on_activation', true, false );
		COMPLIANZ::$geoip->get_geo_ip_database_file();
		update_option('cmplz_run_premium_install' , 'completed' , false );
	}
}
add_action( 'cmplz_install_tables', 'cmplz_check_upgrade_from_free', 10, 2 );

function cmplz_add_pro_system_status(){
	if (!cmplz_user_can_manage()) return;
	echo "Server Headers";
	echo "---------\n";
	print_r($_SERVER);
}
add_action('cmplz_system_status',  'cmplz_add_pro_system_status', 10);

/**
 * Checks if there are free translation files
 * @since 5.3.0
 */
function cmplz_translation_upgrade_check()
{
	if ( !get_transient('cmplz_checked_free_translation_files') ) {
		//remove free language files on upgrade to premium
		if ( cmplz_has_free_translation_files() ){
			cmplz_remove_free_translation_files();
		}
		set_transient('cmplz_checked_free_translation_files', WEEK_IN_SECONDS );
	}
}
add_action( 'admin_init', 'cmplz_translation_upgrade_check' );

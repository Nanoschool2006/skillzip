<?php
defined('ABSPATH') or die("you do not have access to this page!");
/**
 * Vendorlist updates from:
 * https://github.com/InteractiveAdvertisingBureau/GDPR-Transparency-and-Consent-Framework/blob/master/TCFv2/IAB%20Tech%20Lab%20-%20Consent%20string%20and%20vendor%20list%20formats%20v2.md#the-global-vendor-list
 * https://vendor-list.consensu.org/v2/vendor-list.json
 * Translations: https://register.consensu.org/Translation
 *
 * CCPA vendorlist:
 * https://tools.iabtechlab.com/login?returnUrl=%2Flspa
 */

/**
 * Drop in for TCF integration
 */
$debug = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? time() : '';

/**
 * Conditionally initialize
 */
add_action('plugins_loaded', 'cmplz_tcf_init', 10);
add_action( 'complianz_after_save_wizard_option', 'cmplz_tcf_after_save_cookie_settings_option', 10, 4 );

function cmplz_tcf_init() {
	if ( cmplz_iab_is_enabled() ) {
		add_shortcode( 'cmplz-tcf-vendors', 'cmplz_tcf_vendors' );
		add_shortcode( 'cmplz-tcf-us-vendors', 'cmplz_tcf_us_vendors' );
		if (get_option( 'cmplz_wizard_completed_once' ) && COMPLIANZ::$banner_loader->site_needs_cookie_warning()) {
			add_action( 'wp_enqueue_scripts', 'cmplz_tcf_enqueue_stub', 0 );
			add_action( 'wp_enqueue_scripts', 'cmplz_tcf_enqueue_assets' );
		}

		add_filter( 'cmplz_tcf_active', 'cmplz_front_end_iab_is_enabled' );
		add_filter( 'cmplz_cookiebanner_settings_front_end', 'cmplz_tcf_ajax_loaded_banner_data', 10, 2);
		add_filter( 'cmplz_cookiebanner_settings_html', 'cmplz_tcf_settings_html', 10, 2);
		add_filter( 'cmplz_banner_after_categories', 'cmplz_banner_after_categories', 10, 1);
	}
}

/**
 * Add the TCF elements to the banner
 */
function cmplz_banner_after_categories( )
{
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template handles escaping internally
	echo cmplz_get_template( "tcf-categories.php", array(), trailingslashit( CMPLZ_PATH ) . 'pro/templates/');
}

/**
 * Generate default banner text
 * @return string
 */
function cmplz_get_default_banner_text(){
	$global = false;
	$str = '<p>'.__("To provide the best experiences, we and our partners use technologies like cookies to store and/or access device information. Consenting to these technologies will allow us and our partners to process personal data such as browsing behavior or unique IDs on this site and show (non-) personalized ads. Not consenting or withdrawing consent, may adversely affect certain features and functions.", "complianz-gdpr").'</p>';	$str .= '<p>'.__("Click below to consent to the above or make granular choices.", "complianz-gdpr");
	if ($global) {
		$str .= '&nbsp;'.__("Your choices will be applied globally.", "complianz-gdpr");
		$str .= '&nbsp;'.__("This means that your settings will be available on other sites that set your choices globally.", "complianz-gdpr");
	} else {
		$str .= '&nbsp;'.__("Your choices will be applied to this site only.", "complianz-gdpr");

	}
	$str .= '&nbsp;'.__("You can change your settings at any time, including withdrawing your consent, by using the toggles on the Cookie Policy, or by clicking on the manage consent button at the bottom of the screen.", "complianz-gdpr").
	        '</p>';
	return $str;
}

/**
 * pass possible tcf regions to front end
 * @param array $data
 * @param CMPLZ_COOKIEBANNER $banner
 *
 * @return array
 */

function cmplz_tcf_ajax_loaded_banner_data($data, $banner){
	$data['tcf_regions'] = cmplz_tcf_regions();
	return $data;
}

/**
 * set default banner text
 * @param array $data
 * @param CMPLZ_COOKIEBANNER $banner
 *
 * @return mixed
 */

function cmplz_tcf_settings_html($data, $banner){
	$data['message_optin'] = cmplz_get_default_banner_text();
	return $data;
}

/**
 * Get regions where the TCF applies
 * As canada may have optin, we add Canada to the gdpr regions as well in that case
 * @return array
 */
function cmplz_tcf_regions(){
	$tcf_regions = array();
	$regions = COMPLIANZ::$config->regions;
	foreach ( $regions as $region => $region_data ) {
		if ( $region_data['tcf'] ) {
			$tcf_regions[] = $region;
		}
	}
	return $tcf_regions;
}


/**
 * @param string $fieldname
 *
 * @return array
 */
function cmplz_tcf_get_selected_array_keys( $fieldname ): array {
	$values = cmplz_get_option("tcf_".$fieldname);
	if (!is_array($values)) $values = array($values);
	$values = array_map('intval', $values);
	return array_filter($values);
}
/**
 * Enqueue scripts
 * @param $hook
 */

function cmplz_tcf_enqueue_stub( $hook ) {
	$v = filemtime(CMPLZ_PATH . "pro/tcf-stub/build/index.js");
	wp_enqueue_script( 'cmplz-tcf-stub', CMPLZ_URL . "pro/tcf-stub/build/index.js", array(), $v, false );
}

/**
 * Enqueue scripts
 * @param $hook
 */

function cmplz_tcf_enqueue_assets( $hook ) {
	$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');
	wp_enqueue_script(
		'cmplz-tcf',
		CMPLZ_URL . 'pro/tcf/build/index.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		false
	);

	$purposes = cmplz_tcf_get_selected_array_keys('purposes');
	$specialPurposes = cmplz_tcf_get_selected_array_keys('specialpurposes');
	$features = cmplz_tcf_get_selected_array_keys('features');
	$specialFeatures = cmplz_tcf_get_selected_array_keys('specialfeatures');
	$cmp_url = cmplz_upload_url();

	wp_localize_script(
		'cmplz-tcf',
		'cmplz_tcf',
		array(
			'cmp_url' => $cmp_url,
			'retention_string' => __('Data Retention', 'complianz-gdpr'),
			'undeclared_string' => __('Not declared', 'complianz-gdpr'),
			'days_string' => __('days', 'complianz-gdpr'),
			'purpose_retention_string' => __('Purpose-specific retention', 'complianz-gdpr'),
			'ac_vendor_retention_string' => __('See vendor privacy policy', 'complianz-gdpr'),
			'isServiceSpecific' => true,
			'excludedVendors' => cmplz_tcf_get_excluded_vendors(),
			'purposes' => $purposes,
			'specialPurposes' => $specialPurposes,
			'features' => $features,
			'specialFeatures' => $specialFeatures,
			'publisherCountryCode' => cmplz_tcf_get_publisher_country_code(),
			'lspact' => cmplz_get_option('tcf_lspact') === 'yes' ? 'Y' : 'N',
			'ccpa_applies' => cmplz_has_state('cal'),
			'ac_mode' => cmplz_get_option('uses_ad_cookies_personalized') === 'tcf',
			'debug' => cmplz_tcf_get_effective_debug_state(),
			'prefix'=> COMPLIANZ::$banner_loader->get_cookie_prefix(),
		)
	);
}

function cmplz_tcf_get_publisher_country_code() {
	$country_code = cmplz_get_option( 'country_company' );
	$country_code = substr(strtoupper($country_code),0,2);
	if ( empty($country_code) )  $country_code = 'EN';
	return $country_code;
}

/**
 * Get the effective debug state for TCF
 * SCRIPT_DEBUG takes precedence over the admin setting
 * 
 * @return bool
 */
function cmplz_tcf_get_effective_debug_state() {
	// SCRIPT_DEBUG takes precedence
	if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
		return true;
	}
	
	// Fall back to admin setting (checkbox values are stored as 1/0)
	return cmplz_get_option('tcf_debug_mode') == 1;
}

function cmplz_tcf_us_vendors($atts = array(), $content = null, $tag = ''){
	$html = cmplz_get_template( "vendorlist-us.php", [], trailingslashit( CMPLZ_PATH ) . 'pro/tcf/templates');
	return apply_filters('cmplz_tcf_us_container', $html);
}

/**
 * Based on additional vendor information and certain wizard questions, get list of excluded vendors.
 *
 * @return array
 */
function cmplz_tcf_get_excluded_vendors(): array {
	$upload_dir = cmplz_upload_dir('cmp/vendorlist');
	$path  = $upload_dir.'additional-vendor-information-list.json';
	if ( ! file_exists( $path ) ) {
		return [];
	}

	global $wp_filesystem;
	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	$json = json_decode( $wp_filesystem->get_contents( $path ), true );
	if (!isset($json['vendors'])) {
		return [];
	}

	$vendors = $json['vendors'];
	$all_vendor_ids = array_map( static function($vendor){
		return $vendor['id'];
	}, $vendors);

	//remove all vendors where environments does not include 'Web'
	foreach ($vendors as $key => $vendor) {
		if ( isset($vendor['environments']) && !in_array( 'Web', $vendor['environments'], true ) ) {
			unset($vendors[$key]);
		}
	}

	//this question is only relevant if Google is not used, as enabling it will exclude Google.
	if ( cmplz_get_option('tcf_international_transfer') === 'no' ) {
		//remove vendors with internationalTransfers = true
		foreach ( $vendors as $key => $vendor ) {
			if ( isset( $vendor['internationalTransfers'] ) && $vendor['internationalTransfers'] === true ) {
				unset( $vendors[ $key ] );
			}
		}
	}

	if ( cmplz_get_option('tcf_unclear_services') === 'no' ) {
		//remove all vendors with only 'Other' as serviceType
		foreach ( $vendors as $key => $vendor ) {
			if ( isset( $vendor['serviceTypes'] ) && count( $vendor['serviceTypes'] ) === 1 && in_array( 'Other', $vendor['serviceTypes'], true ) ) {
				unset( $vendors[ $key ] );
			}
		}
	}

	//get all ID's of these vendors
	$ids = array_map( static function($vendor){
		return $vendor['id'];
	}, $vendors);

	//invert selection, by removing all $ids from $all_vendor_ids
	return array_diff($all_vendor_ids, $ids);
}


/**
 *
 * Shortcode to insert IAB container in the Cookie Policy
 * @param array  $atts
 * @param null   $content
 * @param string $tag
 *
 * @return false|string
 */

function cmplz_tcf_vendors( $atts = array(), $content = null, $tag = '' ) {
	$args = [
		'checkbox' => cmplz_get_template( "checkbox.php", array(), trailingslashit( CMPLZ_PATH ) . 'pro/tcf/templates'),
		'vendor_template' => cmplz_get_template( "vendor.php", array(), trailingslashit( CMPLZ_PATH ) . 'pro/tcf/templates'),
	];
	$html = cmplz_get_template( "vendorlist.php", $args, trailingslashit( CMPLZ_PATH ) . 'pro/tcf/templates');
	return apply_filters('cmplz_tcf_container', $html);
}

/**
 * Check for IAB support, including the required files
 * Separate from the iab_is_enabled function, as this is used for the json files.
 * @return bool
 */
function cmplz_front_end_iab_is_enabled(){
	if ( cmplz_tcf_cmp_files_missing() ) {
		return false;
	}
	return cmplz_iab_is_enabled();
}

/**
 * Check if there are compatibility issues
 */

function cmplz_iab_is_enabled(){

	$license_is_valid = cmplz_get_license_status() === 'valid';

	if ( ! $license_is_valid ) {
		add_filter( 'cmplz_is_tcf_active', '__return_false' );
		return false;
	}

	$value = cmplz_get_option('uses_ad_cookies_personalized');

	return $value ==='tcf' || $value === 'yes';
}

/**
 * Disable advertising integrations
 */
function cmplz_tcf_disable_integrations(){
	remove_filter( 'cmplz_known_script_tags', 'cmplz_advertising_script' );
	remove_filter( 'cmplz_known_script_tags', 'cmplz_advertising_iframetags' );
}
add_action( 'init', 'cmplz_tcf_disable_integrations' );

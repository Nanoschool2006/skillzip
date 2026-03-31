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
add_action( 'plugins_loaded', 'cmplz_tcf_admin_init', 10);
add_action( "cmplz_before_save_options", 'cmplz_tcf_change_settings', 10, 5 );
add_filter( 'cmplz_banner_fields', 'cmplz_add_tcf_fields' , 10);
add_filter( 'cmplz_fields', 'cmplz_adjust_fields_for_tcf' , 1000);
add_action( 'cmplz_every_week_hook', 'cmplz_update_json_files');
add_action( 'admin_init', 'cmplz_tcf_reset_cache');


function cmplz_tcf_reset_cache(){
	//allow cache override.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Cache override parameter, no sensitive data processed
	if ( isset($_GET['cmplz_nocache']) ) {
		delete_option( 'cmplz_vendorlist_downloaded_once' );
	}
}


function cmplz_tcf_admin_init() {

	if ( cmplz_iab_is_enabled() ) {
		add_filter( 'cmplz_cookie_policy_snapshot_html' , 'cmplz_tcf_adjust_cookie_policy_snapshot_html' );
		add_filter( 'cmplz_warning_types', 'cmplz_tcf_warnings_types' );
		add_filter( "auto_update_plugin", 'cmplz_tcf_override_auto_updates', 100, 2 );
	}
}

/**
 * If TCF is enabled, force auto updates to on
 *
 * @param $update
 * @param $item
 *
 * @return false|mixed
 */
function cmplz_tcf_override_auto_updates( $update, $item ) {
	//skip if debug enabled
	if ( defined('WP_DEBUG') && WP_DEBUG ) {
		return $update;
	}

	if ( is_multisite() ) {
		$option = get_site_option('auto_update_plugins');
	} else {
		$option = get_option('auto_update_plugins');
	}

	if ( ! $option ) $option = [];

	if ( isset( $item->slug ) && strpos($item->slug , 'complianz-gpdr-premium') !==false && ! in_array('complianz-gdpr-premium/complianz-gpdr-premium.php', $option) ) {
		$option[] = 'complianz-gdpr-premium/complianz-gpdr-premium.php';
	}

	if ( is_multisite() ) {
		update_site_option('auto_update_plugins', $option);
	} else {
		update_option('auto_update_plugins', $option);
	}
}

/**
 * Check if the cmp files are missing
 * @return bool
 */
function cmplz_tcf_cmp_files_missing(){
	$upload_dir = cmplz_upload_dir('cmp/vendorlist/');
	return ! file_exists( $upload_dir . 'vendor-list.json' );
}
/**
 * @param array $warnings
 * @return array
 */

function cmplz_tcf_warnings_types($warnings)
{
	$warnings += array(
		'cmp-file-error' => array(
			'plus_one'           => true,
			'warning_condition'  => 'NOT get_value_uses_ad_cookies_personalized==no',
			'success_conditions' => array(
				'NOT cmplz_tcf_cmp_files_missing',
			),
			'dismissible'        => false,
			'urgent'             => __( "The CMP vendorlist files for TCF are not downloaded to, or reachable in the uploads folder yet. If you continue to see this message, contact support to update the files manually.",
				'complianz-gdpr' )
		),
		'tcf-22'         => array(
			'plus_one'           => true,
			'warning_condition'  => 'cmplz_upgraded_to_current_version',
			'dismissible'        => true,
			'open'               => __( "This update includes TCF V2.2, and is certified by Google for upcoming guidelines.", 'complianz-gdpr' ),
			'url'                => 'https://complianz.io/new-iab-tcf-requirements-and-google-cmp-certification/'
		),
//		'tcf-framework-option' => array(
//			'plus_one'           => true,
//			'warning_condition' => 'get_option_cmplz_upgraded_tcf_settings',
//			'dismissible'        => true,
//			'open'               => cmplz_sprintf( __( "You have enabled personalized ads, but not enabled an advertising framework. We recommend enabling TCF to comply with %supcoming guidelines%s.", 'complianz-gdpr' ), '<a href="https://complianz.io/new-iab-tcf-requirements-and-google-cmp-certification/" target="_blank">', '</a>'),
//			'url'                => admin_url('admin.php?page=complianz#wizard/services'),
//		),
	);

	if ( is_multisite() ) {
		$auto_updates = get_site_option('auto_update_plugins');
	} else {
		$auto_updates = get_option('auto_update_plugins');
	}

	if ( !is_array($auto_updates) || !in_array( CMPLZ_PLUGIN, $auto_updates )){
		$warnings += array(
			'auto-updates-not-enabled1' => array(
				'plus_one' => true,
				'warning_condition' => '_true_',
				'urgent' => __( "Please enable auto updates for Complianz. This is mandatory when TCF is active, to be able to quickly adapt to new requirements by the IAB.", 'complianz-gdpr'  ),
				'dismissible' => false,
				'url' => 'https://complianz.io/about-auto-updates/'
			),
		);
	}

	return $warnings;
}

/**
 * On activation set some new settings in cookiebanner
 */
function cmplz_update_json_files($attempt = 1) {

	//don't try again if it was executed recently
	if ( get_option( 'cmplz_vendorlist_downloaded_once' ) > strtotime('-1 week') ) {
		return;
	}

	//don't update on crons when not enabled.
	if ( wp_doing_cron() && !cmplz_iab_is_enabled() ) {
		return;
	}

	if (get_transient("cmplz_update_jsons_active")) {
		return;
	}

	set_transient("cmplz_update_jsons_active", true, 4 * MINUTE_IN_SECONDS);
	$cmplzExistingLanguages = ['gl', 'eu', 'bs', 'ar', 'uk', 'bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'hr', 'hu', 'it', 'ja', 'lt', 'lv', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr','sv', 'tr', 'zh',];	$srcUrl = 'https://cookiedatabase.org/cmp/vendorlist/';
	cmplz_download_json_to_site($srcUrl.'additional-vendor-information-list.json');
	cmplz_download_json_to_site($srcUrl."lspa.json" );

	$srcUrl .= 'v3/';
	cmplz_download_json_to_site($srcUrl.'vendor-list.json');
	//test if file is downloaded
	if ( !cmplz_tcf_cmp_files_missing() && $attempt <=2 ) {
		$attempt++;
		delete_transient('cmplz_update_jsons_active');
		cmplz_update_json_files($attempt);
		return;
	}
	cmplz_download_json_to_site( 'https://storage.googleapis.com/tcfac/additional-consent-providers.csv' );

	foreach ($cmplzExistingLanguages as $lang ) {
		cmplz_download_json_to_site($srcUrl."purposes-$lang.json" );
	}
	update_option( 'cmplz_vendorlist_downloaded_once', time(), false);
	delete_transient('cmplz_update_jsons_active');
}

/**
 * Download a json file to this website
 *
 * @param string $src
 *
 * @return void
 *
 * @since 5.2.3
 */
function cmplz_download_json_to_site( string $src ) {
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	$upload_dir = cmplz_upload_dir('cmp/vendorlist');
	//download file
	$tmpfile  = download_url( $src, $timeout = 25 );
	$file     = $upload_dir . basename( $src );

	//check for errors
	if ( !is_wp_error( $tmpfile ) ) {
		//remove current file
		if ( file_exists( $file ) ) {
			wp_delete_file( $file );
		}

		//in case the server prevents deletion, we check it again.
		if ( ! file_exists( $file ) ) {
			copy( $tmpfile, $file );
		}
	}

	if ( is_string( $tmpfile ) && file_exists( $tmpfile ) ) {
		wp_delete_file( $tmpfile );
	}
}

function cmplz_tcf_set_default( $value, $fieldname, $field ) {

	if ($fieldname === 'tcf_purposes'){
		$value = cmplz_tcf_get('purposes', true);
	}

	if ($fieldname === 'tcf_specialpurposes'){
		$value = cmplz_tcf_get('specialpurposes', true);
	}

	if ($fieldname === 'tcf_features'){
		$value = cmplz_tcf_get('features', true);
	}

	return $value;
}

/**
 * On activation of TCF, do some initializiation
 * @return array
 */
function cmplz_tcf_change_settings( $options=[], $field_id = false, $field_value = false, $prev_value = false, $type = false) {
	if ( !cmplz_admin_logged_in() ) {
		return $options;
	}

	//should run before init vendorlist, otherwise the jsons won't download.
	if ( ($field_value !== $prev_value) &&
	    ($field_id === 'uses_ad_cookies_personalized') &&
	    ($field_value==='yes' || $field_value==='tcf')) {
		//if this setting has just been enabled, re-download the jsons.
		delete_option( 'cmplz_vendorlist_downloaded_once' );
	}

	if ( $field_id === 'uses_ad_cookies' ) {
		//always init in the back-end, even when iab not enabled
		cmplz_update_json_files();
	}

	if ($field_value === $prev_value) {
		return $options;
	}

	if ($field_id === 'uses_ad_cookies_personalized' && ($field_value==='yes' || $field_value==='tcf')){
		//set the color scheme
		$color_schemes = cmplz_banner_color_schemes();
		$color_scheme = $color_schemes['tcf'];

		cmplz_check_minimum_one_banner();
		$banners = cmplz_get_cookiebanners();
		if ( $banners ) {
			foreach ( $banners as $banner_item ) {
				$banner                         = cmplz_get_cookiebanner( $banner_item->ID );
				$banner->soft_cookiewall        = false;
				$banner->banner_width           = '600';
				$banner->use_box_shadow         = true;
				$banner->position               = 'center';
				$banner->manage_consent_options = 'show-everywhere';
				foreach ( $color_scheme as $fieldname => $value ) {
					$banner->{$fieldname} = $value;
				}
				$banner->view_preferences             = __( 'Manage options', 'complianz-gdpr' );
				$banner->header                       = array(
					'text' => __( "Manage your privacy", 'complianz-gdpr' ),
					'show' => true,
				);
				$banner->save();
			}
		}

		//deactivate a/b testing
		$options['a_b_testing_buttons'] = false;
		/**
		 * Send an email
		 * but only once
		 */

//		if ( !get_option('cmplz_tcf_mail_sent') ) {
//			$from = get_option('admin_email');
//			$site_url = site_url();
//			$subject = "TCF enabled on ".$site_url;
//			$to      = "tcf@really-simple-plugins.com";
//			$headers = array();
//			$message = "TCF was enabled on $site_url";
//			add_filter( 'wp_mail_content_type', function ( $content_type ) {return 'text/html';} );
//			$headers[] = "Reply-To: $from <$from>" . "\r\n";
//			wp_mail( $to, $subject, $message, $headers );
//			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
//			update_option( 'cmplz_tcf_mail_sent', true, false );
//		}
	}
	return $options;
}

/**
 * Get items from the most recent vendor list, and cache it for one month
 *
 * @param string $fieldname
 * @param bool   $default_on
 *
 * @return array
 */

function cmplz_tcf_get( string $fieldname, bool $default_on = false){
	if ( !cmplz_is_logged_in_rest() ) {
		return [];
	}
	//user locale
	$locale = substr(get_user_locale(), 0, 2);
	$existing_languages = array('bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'hr', 'hu', 'it', 'ja', 'lt', 'lv', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tr', 'zh',);
	if ( !in_array($locale, $existing_languages)) {
		$locale = 'en';
	}
	$items = get_transient("cmplz_tcf_".$locale."_".$fieldname);
	if ( empty($items) ) {
		$items = [];
		$cmp_path = cmplz_upload_dir('cmp/vendorlist');
		//get the purposes
		if ($locale === 'en' ) {
			$path = $cmp_path . 'vendor-list.json';
		} else {
			$path = $cmp_path . 'purposes-'.$locale.'.json';
		}
		if ( !file_exists($path) ) {
			//try downloading fresh files
			cmplz_update_json_files();
		}

		if ( !file_exists($path) ){
			set_transient("cmplz_tcf_".$locale."_".$fieldname, $items, DAY_IN_SECONDS);
			return $items;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}
		$data = json_decode($wp_filesystem->get_contents($path));
		if (!empty($data)) {
			if ($fieldname==='specialfeatures') $fieldname = 'specialFeatures';
			if ($fieldname==='specialpurposes') $fieldname = 'specialPurposes';
			$remote_items = $data->{$fieldname};
			$items = array();
			if ( is_object($remote_items) ) {
				foreach ( $remote_items as $remote_item ) {
					$items[ $remote_item->id ] = $remote_item->name;
				}
			}
		}
		delete_option('cmplz_clear_tcf_purposes_after_upgrade');
		set_transient("cmplz_tcf_".$locale."_".$fieldname, $items, MONTH_IN_SECONDS);
	}

	if ($default_on) {
		$items = array_keys($items);
		foreach ($items as $key => $item){
			$items[$key] = "$item";
		}
	}

	return $items;
}

/**
 * For fields added with filters, we need to use the direct cmplz_fields filter instead of the cmplz_banner_fields filter
 * Because the fields won't be added yet otherwise.
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_adjust_fields_for_tcf( array $fields ): array {
	if (!cmplz_admin_logged_in()){
		return $fields;
	}

	if ( cmplz_iab_is_enabled() ) {
		//set disabled
		$disable_fields = [
			'a_b_testing_buttons',
		];
		foreach ( $disable_fields as $field_id ) {
			$key = cmplz_get_field_index( $field_id, $fields );
			if ( $key !== false ) {
				$fields[ $key ]['disabled'] = 1;
				//we don't want this field to get enabled again based on conditions
				unset( $fields[ $key ]['condition_action'], $fields[ $key ]['premium'] );
			}
		}
	}
	return $fields;
}

/**
 * Add fields for TCF
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_add_tcf_fields( array $fields): array {
	if (!cmplz_admin_logged_in()){
		return $fields;
	}

	if ( cmplz_iab_is_enabled() ) {
		//set hidden
		//we prevent all editing, as these options have to be the same for all regions.
		$hide_fields = [
			'message_optin',
			'use_categories',
			'colorpalette_background',
			'border_width',
			'colorpalette_text',
			'colorpalette_toggles',
			'colorpalette_border_radius',
			'save_preferences',
			'dismiss',
			'wcag_colors_toggles',
			'wcag_colors_general',
//			'manage_consent_options',
		];
		foreach ( $hide_fields as $field_id ) {
			$keys = array_keys( array_column( $fields, 'id' ), $field_id );
			$key  = reset( $keys );
			if ( $key !== false ) {
				$fields[ $key ]['react_conditions'] = [
					'relation' => 'AND',
					[
						'hidden' => 'true', //fake condition which never applies.
					]
				];
			}
		}

		//remove bottom right and bottom left positions from options
		$keys = array_keys( array_column( $fields, 'id' ), 'position' );
		$key  = reset( $keys );
		if ( $key !== false ) {
			unset( $fields[ $key ]['options']['bottom-left'], $fields[ $key ]['options']['bottom-right'] );
		}

		//set some default values for the TCF banner
		$fields_defaults = [
			[
				'id'      => 'soft_cookiewall',
				'default' => false
			],
			[
				'id'      => 'banner_width',
				'default' => '600'
			],
			[
				'id'      => 'position',
				'default' => 'center'
			],
			[
				'id'      => 'manage_consent_options',
				'default' => 'show-everywhere'
			],
			[
				'id'      => 'view_preferences',
				'default' => __( 'Manage options', 'complianz-gdpr' ),
			],
			[
				'id'      => 'header',
				'default' => array(
					'text' => __( "Manage your privacy", 'complianz-gdpr' ),
					'show' => true,
				),
			],
			[
				'id'      => 'dismiss',
				'default' => [
					'text' => __( "Deny", 'complianz-gdpr' ),
					'show' => true,
				]
			]
		];
		$color_schemes   = cmplz_banner_color_schemes();
		$color_scheme    = $color_schemes['tcf'];
		foreach ( $color_scheme as $fieldname => $value ) {
			$fields_defaults[] = [
				'id'      => $fieldname,
				'default' => $value,
			];
		}
		foreach ( $fields_defaults as $default_field ) {
			$keys = array_keys( array_column( $fields, 'id' ), $default_field['id'] );
			$key  = reset( $keys );
			if ( $key !== false ) {
				$fields[ $key ]['default'] = $default_field['default'];
			}
		}

		#If TCF is enabled, disable some options for the cookie policy
		$keys = array_keys( array_column( $fields, 'id' ), 'cookie-statement' );
		$key  = reset( $keys );
		if ( $key !== false ) {
			$fields[ $key ]['disabled'] = [
				'custom',
				'url',
			];
		}

		//show this notice on all these fields
		$link_notice_ids = ['title', 'header', 'position', 'colorpalette_button_accept', 'use_custom_cookie_css'];
		$link_notice_keys = [];
		foreach ($link_notice_ids as $link_notice_id ) {
			$keys = array_keys( array_column( $fields, 'id' ), $link_notice_id );
			$link_notice_keys[] = reset( $keys );
		}
		foreach ($link_notice_keys as $key) {
			if ( $key !== false ) {
				$fields[ $key ]['help'] = [
					'label' => 'warning',
					'title' => __( "TCF: Guideline restrictions", 'complianz-gdpr' ),
					'text'  => __( "Configuring your TCF consent banner is limited due to IAB guidelines.", "complianz-gdpr" ),
					'url'   => 'https://complianz.io/customizing-the-tcf-banner/',
				];
			}
		}

	}
	//always add these fields, to ensure they're added on change to tcf immediately.
	//otherwise, this hook is already executed by the time the fields load, and the fields only appear on the next save.
	$load_fields = cmplz_is_logged_in_rest() || cmplz_iab_is_enabled();
	if ($load_fields) {
		$fields = array_merge( $fields, [
			[
				'id'               => 'tcf_purposes',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => cmplz_tcf_get( 'purposes', true ),
				'type'             => 'multicheckbox',
				'options'          => cmplz_tcf_get( 'purposes' ),
				'label'            => __( "Your site will show vendors with the purposes selected here", 'complianz-gdpr' ),
				'help'             => [
					'label' => 'default',
					'title' => __( "Vendors", 'complianz-gdpr' ),
					'text'  => __( "To get a better understanding of vendors, purposes and features please read this definitions guide.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/definitions/what-are-vendors/',
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
			],
			[
				'id'               => 'tcf_specialpurposes',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => cmplz_tcf_get( 'specialpurposes', true ),
				'type'             => 'multicheckbox',
				'options'          => cmplz_tcf_get( 'specialpurposes' ),
				'label'            => __( "Your site will show vendors with the special purposes selected here", 'complianz-gdpr' ),
				'help'             => [
					'label' => 'default',
					'title' => __( "Special purposes", 'complianz-gdpr' ),
					'text'  => __( "These special purposes should be enabled for best results. These purposes are set based on legitimate interest of the vendor, one of the legal bases of data processing.",
						'complianz-gdpr' ),
					'url'   => 'https://complianz.io/definition/what-is-a-lawful-basis-for-data-processing/#legitimate-interest',
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
			],
			[
				'id'               => 'tcf_features',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => cmplz_tcf_get( 'features', true ),
				'type'             => 'multicheckbox',
				'options'          => cmplz_tcf_get( 'features' ),
				'label'            => __( "Your site will show vendors with the features selected here", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
			],
			[
				'id'               => 'tcf_specialfeatures',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => array(),
				'type'             => 'multicheckbox',
				'options'          => cmplz_tcf_get( 'specialfeatures' ),
				'label'            => __( "Your site will show vendors with the special features selected here", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
			],
			[
				'id'               => 'tcf_international_transfer',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => 'yes',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Do you want to share data outside of the EU/UK?", 'complianz-gdpr' ),
				'tooltip'          => __( "Some vendors will share data outside of the EU/UK. It's disabled by default, but we recommend US based publishers to enable this option.",
					'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'uses_ad_cookies_personalized' => 'yes',
					]
				],
			],

			[
				'id'               => 'tcf_unclear_services',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => 'yes',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Do you want to share data with advertisers that don't describe their services?", 'complianz-gdpr' ),
				'tooltip'          => __( "Vendors are required to describe their services. Some vendors list services under 'other' and could be considered to negate transparency.",
					'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
			],
			[
				'id'               => 'tcf_lspact',
				'menu_id'          => 'tcf',
				'group_id'         => 'tcf',
				'default'          => 'no',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Have you signed the IAB Privacy, LLC’s Limited Service Provider Agreement (LSPA)?", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'regions'                       => [ 'us' ],
						'us_states'                     => 'cal',
						'!uses_ad_cookies_personalized' => 'no',
					]
				],
				'help'             => [
					'label' => 'default',
					'title' => __( "Limited Service Provider Agreement (LSPA)", 'complianz-gdpr' ),
					'text'  => __( "For California, please read the article about LSPA.", 'complianz-gdpr' ),
					'url'   => 'https://complianz.io/tcf-ccpa/',
				],
			]
		] );
	}
	return $fields;
}

/**
 * With TCF, we need to hardcode some categories
 * @param $settings
 *
 * @return mixed
 */
function cmplz_tcf_adjust_cookie_policy_snapshot_settings($settings){
	unset($settings['categories']);
	return $settings;
}
add_filter( 'cmplz_cookie_policy_snapshot_settings' , 'cmplz_tcf_adjust_cookie_policy_snapshot_settings' );

/**
 * Add link to vendors overview
 * @param $html
 *
 * @return mixed
 */

function cmplz_tcf_adjust_cookie_policy_snapshot_html($html){
	$purposes = cmplz_get_option('tcf_purposes');
	if (!is_array($purposes)) $purposes = array();
	$purposes = array_keys(array_filter($purposes));
	$special_purposes = cmplz_get_option('tcf_specialpurposes');
	if (!is_array($special_purposes)) $special_purposes = array();
	$special_purposes = array_keys(array_filter($special_purposes));
	$features = cmplz_get_option('tcf_features');
	if (!is_array($features)) $features = array();
	$features= array_keys(array_filter($features));
	$special_features = cmplz_get_option('tcf_specialfeatures');
	if (!is_array($special_features)) $special_features = array();
	$special_features = array_keys(array_filter($special_features));
	$marker_marketing = '<p id="cmplz-tcf-marketing-purposes-container" class="cmplz-tcf-container"></p>';
	$marker_statistics = '<p id="cmplz-tcf-statistics-purposes-container" class="cmplz-tcf-container"></p>';
	$marker_specialfeatures = '<p id="cmplz-tcf-specialfeatures-container" class="cmplz-tcf-container"></p>';
	$marker_features = '<p id="cmplz-tcf-features-container" class="cmplz-tcf-container"></p>';
	$marker_specialpurposes = '<p id="cmplz-tcf-specialpurposes-container" class="cmplz-tcf-container"></p>';

	$p_labels = cmplz_tcf_get('purposes');
	$sp_labels = cmplz_tcf_get('specialpurposes');
	$f_labels = cmplz_tcf_get('features');
	$sf_labels = cmplz_tcf_get('specialfeatures');

	foreach ($p_labels as $key => $label ) {
		if (!in_array($key, $purposes) ) unset($p_labels[$key]);
	}
	foreach ($sp_labels as $key => $label ) {
		if (!in_array($key, $special_purposes) ) unset($sp_labels[$key]);
	}
	foreach ($f_labels as $key => $label ) {
		if (!in_array($key, $features) ) unset($f_labels[$key]);
	}
	foreach ($sf_labels as $key => $label ) {
		if (!in_array($key, $special_features) ) unset($sf_labels[$key]);
	}
	$stats_purposes = cmplz_tcf_filter_by_category($p_labels, 'statistics');
	$marketing_purposes = cmplz_tcf_filter_by_category($p_labels, 'marketing');
	$stats_purposes = '<div>'.implode('<br />', $stats_purposes).'</div>';
	$marketing_purposes = '<div>'.implode('<br />', $marketing_purposes).'</div>';
	$features = '<div>'.implode('<br />', $f_labels).'</div>';
	$special_features = '<div>'.implode('<br />', $sf_labels).'</div>';
	$special_purposes = '<div>'.implode('<br />', $sp_labels).'</div>';
	$html = str_replace( $marker_statistics, $stats_purposes, $html);
	$html = str_replace( $marker_marketing, $marketing_purposes, $html);
	$html = str_replace( $marker_specialfeatures, $special_features, $html);
	$html = str_replace( $marker_features, $features, $html);
	$html = str_replace( $marker_specialpurposes, $special_purposes, $html);

	$marker = '<div id="cmplz-tcf-vendor-template"';
	$add = cmplz_sprintf(__("The vendor list can be found at %s", "complianz-gdpr"),'<a href="https://cookiedatabase.org/cmp/vendorlist/vendor-list.json">cookiedatabase.org</a><br /><br />');
	return str_replace($marker, $add . $marker, $html);
}


function cmplz_tcf_filter_by_category( $purposes, $category ) {
	$p['marketing'] = array(1, 2, 3, 4, 5, 6, 10);
	$p['statistics']  = array(1, 7, 8, 9);

	foreach ( $purposes as $key => $value ) {
		if ( !in_array( $key, $p[ $category ] )) unset($purposes[$key]);
	}

	return $purposes;
}

function cmplz_count_all_vendors(){
	$upload_dir = cmplz_upload_dir('cmp/vendorlist');
	$path  = $upload_dir.'additional-vendor-information-list.json';
	if ( ! file_exists( $path ) ) {
		return 0;
	}

	global $wp_filesystem;
	if ( ! $wp_filesystem ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
	}
	$json = json_decode( $wp_filesystem->get_contents( $path ), true );
	if (!isset($json['vendors'])) {
		return 0;
	}

	$vendors = $json['vendors'];
	if (is_array($vendors)){
		return count($vendors);
	}
	return 0;
}
/**
 * Conditional notices for fields
 *
 * @param array           $data
 * @param string          $action
 * @param WP_REST_Request $request
 *
 * @return array
 */
function cmplz_tcf_field_notices(array $notices): array {
	if ( ! cmplz_user_can_manage() ) {
		return $notices;
	}

	if (  cmplz_tcf_active() ) {
		$all_vendors = cmplz_count_all_vendors();
		$excluded_vendors = cmplz_tcf_get_excluded_vendors();

		//should exclude purposes etc as well.
		$excluded_vendors = count($excluded_vendors);
		$vendor_count = $all_vendors;
		if ($all_vendors> $excluded_vendors){
			$vendor_count = $all_vendors - $excluded_vendors;
		}
		$notices[] = [
			'field_id' => 'tcf_specialfeatures',
			'label'    => 'default',
			'title'    => __( "Vendor count", 'complianz-gdpr' ),
			'text'     =>  cmplz_sprintf(__( "With your current settings, you have selected %s vendors.", 'complianz-gdpr' ), $vendor_count),
		];
	}

	return $notices;
}
//add_filter( 'cmplz_field_notices', 'cmplz_tcf_field_notices', 10, 1 );

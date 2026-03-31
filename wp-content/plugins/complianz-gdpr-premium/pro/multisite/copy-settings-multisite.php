<?php
defined('ABSPATH') or die("you do not have acces to this page!");

if ( !defined('CMPLZ_SITES_PER_BATCH' ) ) define('CMPLZ_SITES_PER_BATCH', 50);

/**
 * Initialize copy process
 */
add_filter( 'cmplz_do_action', 'cmplz_copy_multisite', 10, 3 );
function cmplz_copy_multisite($data, $action, $request){
	if ( ! cmplz_user_can_manage() ) {
		return [];
	}
	if ( $action==='copy_multisite' ){
		$data = $request->get_params();
		$restart = $data['restart'] ?? false;
		if ($restart) {
			update_site_option( 'cmplz_copy_settings_active', true );
			update_site_option( 'cmplz_siteprocessing_progress', 0 );
		}
		cmplz_run_copy_settings();

		$progress = get_site_option('cmplz_siteprocessing_progress');
		$start = $progress - CMPLZ_SITES_PER_BATCH;
		if ($start < 0) {
			$start = 0;
		}
		if (!is_multisite() ) {
			$data = [
				'total' => 1,
				'next' => 1,
				'start' => 1,
			];
			return $data;
		}

		$total = get_blog_count();

		$next = $progress+CMPLZ_SITES_PER_BATCH;
		if ( $next>$total ) {
			$next = $total;
		}
		$data = [
			'total' => $total,
			'next' => $next,
			'start' => $start,
		];
	}
	return $data;
}

/**
 * Run chunked copy of wizard settings.
 */

function cmplz_run_copy_settings(){
	if ( !is_multisite() ) {
		return;
	}

	if ( !cmplz_admin_logged_in() ) {
		return;
	}

	if ( !is_main_site(get_current_blog_id()) ) {
		return;
	}

	if (!get_site_option('cmplz_copy_settings_active')) {
		return;
	}

	global $wpdb;
	//get table names for cookies and services in the main site
	$table_name_cookies = $wpdb->prefix . 'cmplz_cookies';
	$table_name_services = $wpdb->prefix . 'cmplz_services';
	$table_name_cookiebanners = $wpdb->prefix . 'cmplz_cookiebanners';
	$main_blog_id = get_current_blog_id();
	$options = array(
		'cmplz_wizard_completed_once' ,
		'cmplz_options',
		'cmplz_detected_social_media',
		'cmplz_detected_thirdparty_services',
		'cmplz_run_cdb_sync_once',
		'cmplz_geo_ip_file',
		'cmplz_plugin_new_features',
		'cmplz_changed_cookies',
		'cmplz_plugins_updated',
		'cmplz_plugins_changed',
		'cmplz_publish_date',
		'cmplz_documents_update_date',
	);

	$option_values = array();
	foreach ($options as $option_name) {
		$option_values[$option_name] = get_option($option_name, true);
	}

	//get custom url value

	//Save the custom URL's for not Complianz generated pages.
	$docs = COMPLIANZ::$config->generic_documents_list;
	$custom_urls = array();
	$generated_docs = array();
	$regions = cmplz_get_regions(true);
	foreach ( $docs as $document => $data ){
		if ( cmplz_get_option( $document ) === 'url' ){
			$custom_urls[$document] = get_option("cmplz_".$document."_custom_page_url" );
		}

		if ( cmplz_get_option( $document ) === 'generated' ){
			foreach ( $regions as $region => $label ) {
				if (!isset(COMPLIANZ::$config->pages[$region][$document]) ) continue;
				$generated_docs[$region][] = $document;
			}
		}
	}


	//run chunked
	$nr_of_sites = CMPLZ_SITES_PER_BATCH;
	$offset = get_site_option('cmplz_siteprocessing_progress');

	//set batch of sites
	$args = array(
		'number' => $nr_of_sites,
		'offset' => $offset,
		'public' => 1,
	);
	$sites = get_sites($args);

	//if no sites are found, we assume we're done.
	if (count($sites)===0) {
		update_site_option('cmplz_copy_settings_active', false);
	} else {
		foreach ($sites as $site) {

			if ( (int) $site->blog_id === (int) $main_blog_id) {
				continue;
			}

			switch_to_blog($site->blog_id);

			foreach ( $regions as $region => $label ) {
				//e.g. 'all' region may not have been added.
				if ( !isset($generated_docs[$region]) ) {
					continue;
				}
				foreach ( $generated_docs[$region] as $type ) {
					$current_page_id = COMPLIANZ::$document->get_shortcode_page_id($type, $region);
					if ( !$current_page_id ){
						$title = COMPLIANZ::$config->pages[ $region ][ $type ]['title'];
						COMPLIANZ::$document->create_page( $type, $region, $title  );
					}
				}
			}

			//copy options
			foreach ($options as $option_name) {
				update_option($option_name, $option_values[$option_name]);
			}

			//Save the custom URL's for not Complianz generated pages.
			foreach ($custom_urls as $document => $url){
				update_option("cmplz_".$document."_custom_page_url", $url );
			}

			//if the plugins page is reviewed, we can reset the privacy statement suggestions from WordPress.
			if ( cmplz_get_option( 'privacy-statement' ) === 'generated'
			) {
				if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
					if ( file_exists(ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php') ) {
						require_once( ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php' );
					} else {
						require_once( ABSPATH . 'wp-admin/misc.php' );
					}
				}
				$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
				WP_Privacy_Policy_Content::_policy_page_updated( $policy_page_id );
				//check again, to update the cache.
				WP_Privacy_Policy_Content::text_change_check();
			}

			//copy cookies and services
			$new_table_name_cookies = $wpdb->prefix . 'cmplz_cookies';
			$new_table_name_services = $wpdb->prefix . 'cmplz_services';
			$new_table_name_cookiebanners = $wpdb->prefix . 'cmplz_cookiebanners';

			//should not be possible, as we skip the main site already, but we've seen this on a customer site, so we check it anyway.
			if ( $new_table_name_cookies === $table_name_cookies ) continue; //skip if it's the same table (e.g. main site

			//create new, and copy
			$wpdb->query("Drop TABLE if exists $new_table_name_cookies");
			$wpdb->query("CREATE TABLE $new_table_name_cookies LIKE $table_name_cookies");
			$wpdb->query("INSERT $new_table_name_cookies SELECT * FROM $table_name_cookies;");

			$wpdb->query("Drop TABLE if exists $new_table_name_services");
			$wpdb->query("CREATE TABLE $new_table_name_services LIKE $table_name_services");
			$wpdb->query("INSERT $new_table_name_services SELECT * FROM $table_name_services;");

			$wpdb->query("Drop TABLE if exists $new_table_name_cookiebanners");
			$wpdb->query("CREATE TABLE $new_table_name_cookiebanners LIKE $table_name_cookiebanners");
			$wpdb->query("INSERT $new_table_name_cookiebanners SELECT * FROM $table_name_cookiebanners;");

			//generate css for each banner
			$banners = cmplz_get_cookiebanners();
			if ( $banners ) {
				foreach ( $banners as $banner_item ) {
					$banner = cmplz_get_cookiebanner( $banner_item->ID );
					$banner->save();
				}
			}

			cmplz_update_option('disable_notifications', true);
			COMPLIANZ::$sync->reset_cookies_changed();
			update_option( 'cmplz_wizard_completed_once', true );

			restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
			update_site_option('cmplz_siteprocessing_progress', $offset+$nr_of_sites);
		}
	}
}
add_action('admin_init', 'cmplz_run_copy_settings');

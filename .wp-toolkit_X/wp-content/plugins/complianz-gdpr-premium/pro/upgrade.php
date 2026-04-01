<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

/**
 * Hooks in on the free plugin upgrade function
 * Runs when the plugin is updated in the dashboard.
 * @param $prev_version
 * @hooked cmplz_upgrade
 * @return void
 */

function cmplz_upgrade_premium($prev_version)
{
	if ($prev_version && version_compare($prev_version, '5.0.0', '<')) {
		global $wpdb;
		//clean up a/b testing table
		$wpdb->delete(
			$wpdb->prefix."cmplz_statistics",
			array( 'time' => '')
		);
	}

	if ($prev_version && version_compare($prev_version, '5.1.0', '<')) {
		global $wpdb;
		$table_name = $wpdb->prefix.'cmplz_statistics';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name
		) {
			$wpdb->query( "UPDATE {$wpdb->prefix}cmplz_statistics SET statistics = 1 WHERE stats=1" );
			$wpdb->query( "UPDATE {$wpdb->prefix}cmplz_statistics SET preferences = 1 WHERE prefs=1" );
		}
	}

	if (  $prev_version
	      && version_compare( $prev_version, '5.3.0', '<' )
	) {
		//find impressum page id.
		$page_id = COMPLIANZ::$document->get_shortcode_page_id('impressum', 'eu', false);
		if ($page_id) {
			$page = get_post($page_id);
			if (strpos($page->post_content, 'selectedDocument":"impressum') !==false ) {
				if ( strpos($page->post_content, 'impressum-all')===false) {
					$content = str_replace('"selectedDocument":"impressum"', '"selectedDocument":"impressum-all"', $page->post_content);
					$args = array(
						'post_content' => $content,
						'ID'           => $page_id,
					);
					wp_update_post( $args );
				}
			}

			if (strpos($page->post_content, 'cmplz-document type="impressum" region') !==false ) {
				if ( strpos($page->post_content, 'region="all"')===false) {
					$content = str_replace('cmplz-document type="impressum" region="eu"', 'cmplz-document type="impressum" region="all"', $page->post_content);
					$args = array(
						'post_content' => $content,
						'ID'           => $page_id,
					);
					wp_update_post( $args );
				}
			}
		}
	}

	if (  $prev_version
	      && version_compare( $prev_version, '5.5.0', '<' )
	) {
		//upgrade cookie policy setting to new field
		$wizard_settings = get_option( 'complianz_options_wizard' );
		if ( isset( $wizard_settings["is_webshop"] ) ) {
			if ($wizard_settings["is_webshop"]) {
				$wizard_settings["is_webshop"] = 'yes';
			} else {
				$wizard_settings["is_webshop"] = 'no';
			}
			update_option( 'complianz_options_wizard', $wizard_settings );
		}
	}

	if ($prev_version && version_compare($prev_version, '6.0.2', '<')) {
		global $wpdb;
		$table_name = $wpdb->prefix.'cmplz_statistics';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name
		) {
			$wpdb->query( "DELETE from {$wpdb->prefix}cmplz_statistics WHERE time=0" );
		}
	}

	if ( $prev_version && version_compare( $prev_version, '6.5.0', '<' ) ) {
		if ( !is_multisite() ) {
			$policy_id = get_site_option( 'complianz_active_policy_id', 1 );
			update_option( 'complianz_active_policy_id', $policy_id);
		}
	}

	//for legint, we upgrade the active policy id
	if ( $prev_version && version_compare( $prev_version, '6.1.0.1', '<' ) ) {
		COMPLIANZ::$banner_loader->upgrade_active_policy_id();
	}

	if ( $prev_version && version_compare($prev_version, '7.0.0', '<') ) {
		//publish all databreach drafts
		$args = array(
			'post_type' => 'cmplz-dataleak',
			'post_status' => 'draft',
			'posts_per_page' => -1,
		);
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$post->post_status = 'publish';
			wp_update_post( $post );
		}

		if ( cmplz_tcf_active() ) {
			delete_option( 'cmplz_vendorlist_downloaded_once' );

			require_once CMPLZ_PATH . 'pro/tcf/tcf-admin.php';
			cmplz_update_json_files();

			$locale = substr(get_user_locale(), 0, 2);
			$existing_languages = array('bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'hr', 'hu', 'it', 'ja', 'lt', 'lv', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tr', 'zh',);
			if ( !in_array($locale, $existing_languages)) {
				$locale = 'en';
			}

			delete_transient("cmplz_tcf_".$locale."_purposes");
			delete_transient("cmplz_tcf_".$locale."_specialpurposes");
			delete_transient("cmplz_tcf_".$locale."_features");
			delete_transient("cmplz_tcf_".$locale."_specialfeatures");

			//check if tcf_purposes has purpose one set. If not, set it.
			$options = get_option( 'cmplz_options', [] );
			if ( ! isset( $options['tcf_purposes'] ) ) {
				$options['tcf_purposes'] = [];
			}
			if ( ! in_array( '1', $options['tcf_purposes'], true ) ) {
				$options['tcf_purposes'][] = '1';
			}
			$uses_ad_cookies_personalized = $options['uses_ad_cookies_personalized'] ?? 'no';
			if ( $uses_ad_cookies_personalized === 'yes' ) {
				$options['uses_ad_cookies_personalized'] = 'no';
				update_option( "cmplz_upgraded_tcf_settings", true, false );
			}
			update_option( 'cmplz_options', $options );
		}
	}

	//run this upgrade on each update
	if ( $prev_version ) {
		if ( cmplz_tcf_active() ) {
			delete_option( 'cmplz_vendorlist_downloaded_once' );

			require_once CMPLZ_PATH . 'pro/tcf/tcf-admin.php';
			cmplz_update_json_files();

			$locale = substr(get_user_locale(), 0, 2);
			$existing_languages = array('bg', 'ca', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'hr', 'hu', 'it', 'ja', 'lt', 'lv', 'mt', 'nl', 'no', 'pl', 'pt', 'ro', 'ru', 'sk', 'sl', 'sr', 'sv', 'tr', 'zh',);
			if ( !in_array($locale, $existing_languages)) {
				$locale = 'en';
			}

			delete_transient("cmplz_tcf_".$locale."_purposes");
			delete_transient("cmplz_tcf_".$locale."_specialpurposes");
			delete_transient("cmplz_tcf_".$locale."_features");
			delete_transient("cmplz_tcf_".$locale."_specialfeatures");
		}

	}

	// Re-save all banners to regenerate CSS and clear caches
	if ( $prev_version && version_compare( $prev_version, '7.5.7', '<' ) ) {
		cmplz_resave_all_banners();
	}

}
add_action('cmplz_upgrade',  'cmplz_upgrade_premium', 10);

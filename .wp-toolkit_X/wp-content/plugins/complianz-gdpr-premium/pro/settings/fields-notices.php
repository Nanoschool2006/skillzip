<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
/**
 * Conditional notices for fields
 *
 * @param array           $notices
 *
 * @return array
 */
function cmplz_pro_field_notices(array $notices ): array {
	if ( ! cmplz_user_can_manage() ) {
		return [];
	}

	if ( COMPLIANZ::$geoip->geoip_library_error() ) {
		$error = get_option('cmplz_geoip_import_error');
		$folder = "/complianz/maxmind";
		$notices[] = [
			'field_id' => 'use_country',
			'label'    => 'warning',
			'title'    => __( "GEO IP database error", 'complianz-gdpr' ),
			'text'     => cmplz_sprintf(__("You have enabled GEO IP, but the GEO IP database hasn't been downloaded automatically. If you continue to see this message, download the file from MaxMind and put it in the %s folder in your WordPress uploads directory", 'complianz-gdpr'), $folder).
			' '.cmplz_sprintf(__("The following error was reported: %s", 'complianz-gdpr'),$error),
			'url' => 'https://cookiedatabase.org/maxmind/GeoLite2-Country.mmdb',
		];
	}

	if ( cmplz_has_region('eu') && ! cmplz_company_located_in_region( 'eu' ) ) {
		$notices[] = [
			'field_id' => 'dpo_or_gdpr',
			'label'    => 'default',
			'title'    => __( "GDPR representative", 'complianz-gdpr' ),
			'text'     => __( "Your company is located outside the EU, so should appoint a GDPR representative in the EU.", 'complianz-gdpr' ),
		];
	}

	if ( cmplz_has_region('uk') ) {
		if ( !cmplz_company_located_in_region('uk') ){
			$text = __("Your company is located outside the United Kingdom, so you should appoint a UK-GDPR representative in the United Kingdom.", 'complianz-gdpr');
		} else {
			$text = __("Your company is located in the United Kingdom, so you do not need to appoint a UK-GDPR representative in the United Kingdom.", 'complianz-gdpr');
		}
		$notices[] = [
			'field_id' => 'dpo_or_gdpr',
			'label'    => 'default',
			'title'    => __( "UK-GDPR representative", 'complianz-gdpr' ),
			'text'     => $text,
		];
	}

	if (defined('rsssl_pro')){
		$notices[] = [
			'field_id' => 'which_personal_data_secure',
			'label'    => 'default',
			'title'    => __( "Security Headers", 'complianz-gdpr' ),
			'text'     => __("You're using Really Simple Security Pro, headers that are enabled in Really Simple Security Pro are checked already. You can manage them in the settings, you can follow the link below", 'complianz-gdpr'),
			'url'      => admin_url('options-general.php?page=really-simple-security#settings/recommended_security_headers')
		];
	}

	if ( COMPLIANZ::$banner_loader->site_shares_data()
	) {
		$notices[] = [
			'field_id' => 'share_data_other',
			'label'    => 'default',
			'title'    => __( "Sharing Data", 'complianz-gdpr' ),
			'text'     => __( "Complianz detected settings that suggest your site shares data, which means the answer should probably be Yes, or Limited", 'complianz-gdpr' )
		];
	}

	if ( cmplz_get_option('privacy-statement')==='generated' && ( cmplz_has_region('br') || cmplz_has_region('za') ) ){
		$notices[] = [
			'field_id' => 'share_data_other',
			'label'    => 'default',
			'title'    => __( "Operators and processors", 'complianz-gdpr' ),
			'text'     => __("Please note: in South Africa and Brazil, Operator will be used instead of Processor.", "complianz-gdpr"),
		];
	}

	return $notices;
}
add_filter( 'cmplz_field_notices', 'cmplz_pro_field_notices', 10, 1 );

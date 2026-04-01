<?php defined('ABSPATH') or die();
require_once( __DIR__ .'/defaults.php');
require_once( __DIR__ .'/fields/wizard.php');
require_once( __DIR__ .'/fields/security-consent.php');
require_once( __DIR__ .'/fields/purposes.php');
require_once( __DIR__ .'/fields/sharing-of-data.php');
require_once( __DIR__ .'/fields/imprint.php');
require_once( __DIR__ .'/fields/dpo.php');
require_once( __DIR__ .'/fields/disclaimer.php');
require_once( __DIR__ .'/fields/children.php');
require_once( __DIR__ .'/fields/plugins.php');
require_once( __DIR__ .'/fields/financial-incentives.php');
require_once( __DIR__ .'/fields/settings.php');
require_once( __DIR__ .'/fields/multisite.php');
require_once( __DIR__ .'/fields/translations.php');
require_once( __DIR__ .'/fields/tools.php');
/**
 * Unlock premium fields
 * @param array $field
 * @param string $field_id
 *
 * @return array
 */

function cmplz_premium_fields($field, $field_id)
{
	if ($field_id==='dpo_or_gdpr') {
		$value = cmplz_get_option('dpo_or_gdpr', false);
		if (!is_array($value)) $value = [];
		$uk = cmplz_has_region('uk');
		$eu = cmplz_has_region('eu');
		$located_in_uk = cmplz_company_located_in_region( 'uk' );
		$located_in_eu = cmplz_company_located_in_region( 'eu' );

		if ( !$uk || !$located_in_uk ){
			if (!in_array('dpo_uk', $value)) unset($field['options']['dpo_uk']);
		}

		if ( !$eu || !$located_in_eu ){
			if (!in_array('dpo', $value)) unset($field['options']['dpo']);
		}

		if ( !$eu || $located_in_eu ){
			if (!in_array('gdpr_rep', $value)) unset($field['options']['gdpr_rep']);
		}

		if ( !$uk || $located_in_uk ){
			if (!in_array('uk_gdpr_rep', $value)) unset($field['options']['uk_gdpr_rep']);
		}
	}

	if ($field_id==='use_country' && cmplz_get_option( 'records_of_consent' ) === 'yes') {
		$field['disabled'] = true;
		unset($field['premium']); //this could override the disabled state
		$field['comment']  = __( 'With records of consent enabled, GEO IP can not be turned off.', 'complianz-gdpr' );
	}

	if ($field_id==='regions' && !cmplz_get_option( 'use_country' ) ) {
		$field['help'] = [
			'label' => 'default',
			'title' => __( "Multiple Regions", 'complianz-gdpr' ),
			'text'  => __('To be able to select multiple regions, you should enable GEO IP in the general settings','complianz-gdpr'),
			'url'   => admin_url('admin.php?page=complianz#settings'),
		];
	}

	//check if we have at least one TCF region selected. Otherwise, disable it
	if ($field_id==='uses_ad_cookies_personalized') {
		$selected_tcf_regions = array_intersect(cmplz_get_regions(), cmplz_tcf_regions());
		if ( count($selected_tcf_regions)===0 ) {
			$field['disabled'] = array('tcf', 'yes');
			unset($field['premium']); //this could override the disabled state
			$field['comment'] = __("You have not selected a TCF region at the moment", 'complianz-gdpr');
		} else {
			#enable TCF option, but only when the complianz cookie policy is used.
			if ( cmplz_get_option( 'cookie-statement' ) === 'generated' ) {
				$field['disabled'] = false;
			}
		}
	}

	if ( ( $field_id === 'telephone_company' ) && cmplz_get_option( 'impressum' ) === 'generated' ) {
		$field['required'] = true;
	}

	if ( ( $field_id === 'a_b_testing_buttons' ) && cmplz_tcf_active() ) {
		$field['comment'] = __('With TCF enabled, A/B testing is not possible.', 'complianz-gdpr');
	}

	return $field;
}
add_filter('cmplz_field', 'cmplz_premium_fields', 10, 2);

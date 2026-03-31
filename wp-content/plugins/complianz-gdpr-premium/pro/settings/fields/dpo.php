<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_pro_dpo_fields', 100 );
function cmplz_pro_dpo_fields($fields){
	return array_merge($fields,  [
		[
			'id' => 'dpo_or_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'multicheckbox',
			'default' => '',
			'label' => __("Select all that applies.", 'complianz-gdpr'),
			'options' => array(
				'dpo' => __('We have registered a DPO with the Data Protection Authority in the EU.', 'complianz-gdpr'),
				'dpo_uk' => __('We have registered a DPO with the Data Protection Authority in the UK.', 'complianz-gdpr'),
				'gdpr_rep' => __('We have appointed a GDPR representative within the EU.', 'complianz-gdpr'),
				'uk_gdpr_rep' => __('We have a UK-GDPR representative within the United Kingdom', 'complianz-gdpr'),
			),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu', 'uk', 'au', 'za'],
				]
			],
			'required' => false,
		],
		[
			'id'       => 'name_dpo',
			'menu_id'  => 'dpo',
			'parent_label'  => __( "Data Protection Officer", 'complianz-gdpr' ),
			'type' => 'text',
			'required' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'dpo_or_gdpr' => 'dpo',
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
				]
			],
			'label' => __("Name Data Protection Officer", 'complianz-gdpr'),
		],
		[
			'id'       => 'email_dpo',
			'menu_id'  => 'dpo',
			'type' => 'email',
			'default' => '',
			'required' => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'dpo'
				]
			],
			'label' => __("Email", 'complianz-gdpr'),
		],
		[
			'id'       => 'phone_dpo',
			'menu_id'  => 'dpo',
			'type' => 'phone',
			'default' => '',
			'required' => false,
			'label' => __("Phone number", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'dpo'
				]
			],
		],
		[
			'id'       => 'website_dpo',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => false,
			'placeholder' => __( "Leave empty if not applicable", 'complianz-gdpr' ),
			'label' => __("Website", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'dpo'
				]
			],
		],
		[
			'parent_label'  => __( "Representative", 'complianz-gdpr' ),
			'id'       => 'name_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => true,
			'label' => __("Name GDPR Representative", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'gdpr_rep'
				]
			],
		],
		[
			'id'       => 'email_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'email',
			'default' => '',
			'required' => true,
			'label' => __("Email", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'gdpr_rep'
				]
			],
		],
		[
			'id'       => 'phone_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'phone',
			'default' => '',
			'required' => false,
			'label' => __("Phone number", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'gdpr_rep'
				]
			],
		],
		[
			'id'       => 'website_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => false,
			'placeholder' => __("Leave empty if not applicable", 'complianz-gdpr'),
			'label' => __("Website", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['eu'],
					'dpo_or_gdpr' => 'gdpr_rep'
				]
			],
		],
		[
			'parent_label'  => __( "Data Protection Officer", 'complianz-gdpr' ),
			'id'       => 'name_uk_dpo',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'required' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'dpo_uk'
				]
			],
			'label' => __("Name UK DPO", 'complianz-gdpr'),
		],
		[
			'id'       => 'email_uk_dpo',
			'menu_id'  => 'dpo',
			'type' => 'email',
			'default' => '',
			'required' => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'dpo_uk'
				]
			],
			'label' => __("Email", 'complianz-gdpr'),
		],
		[
			'id'       => 'phone_uk_dpo',
			'menu_id'  => 'dpo',
			'type' => 'phone',
			'default' => '',
			'required' => false,
			'label' => __("Phone number", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'dpo_uk'
				]
			],
		],
		[
			'id'       => 'website_uk_dpo',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => false,
			'placeholder' => __("Leave empty if not applicable", 'complianz-gdpr'),
			'label' => __("Website", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'dpo_uk',
				]
			],
		],

		[
			'parent_label'  => __( "Representative", 'complianz-gdpr' ),
			'id'       => 'name_uk_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => true,
			'label' => __("Name", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'uk_gdpr_rep'
				]
			],
		],
		[
			'id'       => 'email_uk_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'email',
			'default' => '',
			'required' => true,
			'label' => __("Email", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'uk_gdpr_rep'
				]
			],
		],
		[
			'id'       => 'phone_uk_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'phone',
			'default' => '',
			'required' => false,
			'label' => __("Phone number", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'uk_gdpr_rep'
				]
			],
		],

		[
			'id'       => 'website_uk_gdpr',
			'menu_id'  => 'dpo',
			'type' => 'text',
			'default' => '',
			'required' => false,
			'placeholder' => __("Leave empty if not applicable", 'complianz-gdpr'),
			'label' => __("Website", 'complianz-gdpr'),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions' => ['uk'],
					'dpo_or_gdpr' => 'uk_gdpr_rep'
				]
			],
		],
	]);

}

<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_pro_security_consent_fields', 100 );
function cmplz_pro_security_consent_fields($fields){
	return array_merge(
		$fields,  [
			[
				'id' => 'add_consent_to_forms',
				'menu_id'  => 'security-consent',
				'type' => 'multicheckbox',
				'required' => false,
				'default' => '',
				'label' => __("For forms detected on your site, you can choose to add a consent checkbox", 'complianz-gdpr'),
				'options' => cmplz_admin_logged_in() ? get_option('cmplz_detected_forms') : [],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'contact_processing_data_lawfull'=>'1', //when permission is required, add consent box
						'regions' => ['eu', 'uk', 'za'],
					]
				],
				'help'     => [
					'label' => 'default',
					'title' => __( 'Consent checkboxes', 'complianz-gdpr' ),
					'text'  => __( 'You have answered that you use webforms on your site. Not every form that collects personal data requires a checkbox.', "complianz-gdpr" ),
					'url'   => 'https://complianz.io/how-to-implement-a-consent-box',
				],
			],
			[
				'id' => 'secure_personal_data',
				'menu_id'  => 'security-consent',
				'type' => 'radio',
				'required' => true,
				'default' => '',
				'label' => __("Do you want to provide a detailed list of security measures in your Privacy Statement?", 'complianz-gdpr'),
				'options' => array(
					'1' => __('No, provide a general explanation', 'complianz-gdpr'),
					'2' => __('Yes, manually', 'complianz-gdpr'),
					'3' => __('Yes, based on my configuration of Really Simple Security', 'complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
					]
				],
			],
			[
				'id'       => 'install-really-simple-ssl',
				'type'     => 'install-plugin',
				'plugin_data' => [
					'title' => "Really Simple Security",
					'summary' => __("Lightweight plugin. Heavyweight security features.", 'complianz-gdpr'),
					'slug' => 'really-simple-ssl',
					'description' => __("Leverage your SSL certificate to the fullest, with health checks, security headers, hardening, vulnerability detection and more.", 'complianz-gdpr'),
					'image' => "really-simple-ssl.png"

				],
				'menu_id'  => 'security-consent',
				'label'    => '',
//				'react_conditions' => [
//					'relation' => 'AND',
//					[
//						'secure_personal_data' => '3',
//					]
//				],
			],
			[
				'id' => 'which_personal_data_secure',
				'menu_id'  => 'security-consent',
				'required' => false,
				'type' => 'multicheckbox',
				'default' => [],
				'label' => __("Check if below features are indeed enabled", 'complianz-gdpr'),


				'options' => array(

					'1' => __('Login Security', 'complianz-gdpr'),
					'2' => __('DKIM, SPF, DMARC and other specific DNS settings', 'complianz-gdpr'),
					'3' => __('(START)TLS / SSL / DANE Encryption', 'complianz-gdpr'),
					'4' => __('HTTP Strict Transport Security and related Security Headers and Browser Policies', 'complianz-gdpr'),
					'5' => __('Website Hardening/Security Features', 'complianz-gdpr'),
					'6' => __('Vulnerability Detection', 'complianz-gdpr'),
					'7' => __('Security measures of hardware that contain, or process personal data.', 'complianz-gdpr'),
					'8' => __('ISO27001/27002 Certification', 'complianz-gdpr'),
				),
				'help'     => [
					'label' => 'default',
					'title' => __( 'Easily secure your website with Really Simple Security', 'complianz-gdpr' ),
					'text'  => __( "The easiest way to implement all recommended security features is with Really Simple Security Pro", "complianz-gdpr" ),
					'url'   => 'https://really-simple-ssl.com/pro/',
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'secure_personal_data' => ['2','3']
					]
				],
			],
		]
	);
}

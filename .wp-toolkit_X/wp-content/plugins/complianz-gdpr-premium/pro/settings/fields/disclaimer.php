<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_pro_disclaimer_fields', 100 );
function cmplz_pro_disclaimer_fields($fields){
	return array_merge(
		$fields,  [
			[
				'id' => 'themes',
				'menu_id'  => 'disclaimer',
				'type' => 'multicheckbox',
				'default' => '1',
				'label' => __("Which themes would you like to include in your Disclaimer?", 'complianz-gdpr'),
				'options' => array(
					'1' => __('Liability', 'complianz-gdpr'),
					'2' => __('Reference to terms of use', 'complianz-gdpr'),
					'3' => __('How you will answer inquiries', 'complianz-gdpr'),
					'4' => __('Privacy and reference to the privacy statement', 'complianz-gdpr'),
					'5' => __('Not liable when security is breached', 'complianz-gdpr'),
					'6' => __('Not liable for third-party content', 'complianz-gdpr'),
					'7' => __('Accessibility of the website for the disabled', 'complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'disclaimer' => 'generated',
					]
				],
				'required' => true,
			],
			[
				'id' => 'terms_of_use_link',
				'menu_id'  => 'disclaimer',
				'type' => 'url',
				'default' => '',
				'label' => __("What is the URL of the Terms of Use?", 'complianz-gdpr'),
				'help'     => [
					'label' => 'default',
					'title' => __( "Our free Terms & Conditions plugin", 'complianz-gdpr' ),
					'text'  => __( "Also see our free Terms & Conditions plugin.", "complianz-gdpr" ),
					'url'   => 'https://wordpress.org/plugins/complianz-terms-conditions',
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'disclaimer' => 'generated',
						'themes' => '2',

					]
				],
				'required' => true,
			],
			[
				'id' => 'wcag',
				'menu_id'  => 'disclaimer',
				'type' => 'radio',
				'default' => 'The WCAG documents explain how to make web content more accessible to people with disabilities.',
				'label' => __("Is your website built according to WCAG 2.1 level AA guidelines?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'disclaimer' => 'generated',
						'themes' => '7'
					]
				],
				'required' => true,
				'help'     => [
					'label' => 'default',
					'title' => "WCAG",
					'text'  => __( "You can find more information about how we handle WCAG requirements on our website.", "complianz-gdpr" ),
					'url'   => 'https://complianz.io/definition/what-is-wcag/',
				],
			],

			[
				'id' => 'development',
				'menu_id'  => 'disclaimer',
				'type' => 'radio',
				'default' => '',
				'label' => __("Who made the content of the website?", 'complianz-gdpr'),
				'options' => array(
					'1' => __('The content is being developed by ourselves', 'complianz-gdpr'),
					'2' => __('The content is being developed or posted by Third Parties', 'complianz-gdpr'),
					'3' => __('The content is being developed by ourselves and other parties', 'complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'disclaimer' => 'generated',
					]
				],
				'required' => true,
			],
			[
				'id' => 'ip-claims',
				'menu_id'  => 'disclaimer',
				'type' => 'radio',
				'default' => '',
				'required' => true,
				'label' => __("What do you want to do with any intellectual property claims?", 'complianz-gdpr'),
				'options' => array(
					'1' => __('All rights reserved', 'complianz-gdpr'),
					'2' => __('No rights reserved', 'complianz-gdpr'),
					'3' => __('Creative Commons - Attribution 4.0', 'complianz-gdpr'),
					'4' => __('Creative Commons - Attribution-ShareAlike 4.0', 'complianz-gdpr'),
					'5' => __('Creative Commons - Attribution-NoDerivatives 4.0', 'complianz-gdpr'),
					'6' => __('Creative Commons - Attribution-NonCommercial 4.0', 'complianz-gdpr'),
					'7' => __('Creative Commons - Attribution-NonCommercial-ShareAlike 4.0', 'complianz-gdpr'),
					'8' => __('Creative Commons - Attribution-NonCommercial-NoDerivatives 4.0', 'complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'disclaimer' => 'generated',
					]
				],
				'help'     => [
					'label' => 'default',
					'title' => __( "Creative Commons (CC)", 'complianz-gdpr' ),
					'text'  => __( "Creative Commons (CC) is an American non-profit organization devoted to expanding the range of creative works available for others to build upon legally and to share.", "complianz-gdpr" ),
					'url'   => 'https://complianz.io/definition/what-is-creative-commons/',
				],
			],
		]
	);
}

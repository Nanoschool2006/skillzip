<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_pro_children_fields', 100 );
function cmplz_pro_children_fields($fields){
	return array_merge(
		$fields,  [
			[
				'id' => 'targets-children',
				'menu_id'  => 'children',
				'required' => true,
				'type' => 'radio',
				'default' => '',
				'label' => __("Is your website designed to attract children and/or is it your intent to collect personal data from children under the age of 13?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk', 'ca', 'au', 'za', 'br'],
					]
				],
			],

			[
				'id' => 'children-parent-consent-type',
				'menu_id'  => 'children',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("How do you obtain verifiable parental consent for the collection, use, or disclosure of personal information from children?", 'complianz-gdpr'),
				'options' => array(
					'email' => __("We seek a parent or guardian's consent by email",'complianz-gdpr'),
					'creditcard' => __('We seek a high level of consent by asking for a creditcard verification','complianz-gdpr'),
					'phone-chat' => __('We use telephone or Videochat  to talk to the parent or guardian','complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => array('us','uk','ca', 'au', 'za'),
						'targets-children' => 'yes'
					]
				],
			],
			[
				'id' => 'children-safe-harbor',
				'menu_id'  => 'children',
				'required' => true,
				'type' => 'radio',
				'default' => '',
				'label' => __("Is your website included in a COPPA Safe Harbor Certification Program?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'help' => [
					'label' => 'default',
					'title' => "COPPA Safe Harbor Certification Program",
					'text' => __("If your website is not included in a COPPA Safe Harbor Certification Program we recommend to check out PRIVO, as you target children on your website.", 'complianz-gdpr'),
					'url' => 'https://www.privo.com/',
				],
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
						'targets-children' => 'yes'
					]
				],
			],

			[
				'id' => 'children-name-safe-harbor',
				'menu_id'  => 'children',
				'required' => true,
				'type' => 'text',
				'default' => '',
				'label' => __("What is the name of the program?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
						'children-safe-harbor' => 'yes'
					]
				],
			],

			[
				'id' => 'children-url-safe-harbor',
				'menu_id'  => 'children',
				'required' => true,
				'type' => 'url',
				'default' => '',
				'label' => __("What is the URL of the program?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
						'children-safe-harbor' => 'yes'
					]
				],
			],

			[
				'id' => 'children-what-purposes',
				'menu_id'  => 'children-purposes',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("For what potential activities on your website do you collect personal information from a child?", 'complianz-gdpr'),
				'options' => array(
					'registration' => __('Registration','complianz-gdpr'),
					'content-created-by-child' => __('Content created by a child and publicly shared','complianz-gdpr'),
					'chat' => __('Chat/messageboard','complianz-gdpr'),
					'email' => __('Email contact','complianz-gdpr'),
				),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk','ca', 'au', 'za', 'br'],
						'targets-children' => 'yes',
					]
				],
			],

			[
				'id' => 'children-what-information-registration',
				'menu_id'  => 'children-purposes',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("Information collected for registration ", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->collected_info_children,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk', 'ca', 'au', 'za', 'br'],
						'targets-children' => 'yes',
						'children-what-purposes' => 'registration'
					]
				],
			],

			[
				'id' => 'children-what-information-content',
				'menu_id'  => 'children-purposes',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("Information collected for content created by a child", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->collected_info_children,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk','ca', 'au', 'za', 'br'],
						'targets-children' => 'yes',
						'children-what-purposes' => 'content-created-by-child'
					]
				],
			],

			[
				'id' => 'children-what-information-chat',
				'menu_id'  => 'children-purposes',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("Information collected for chat/messageboard", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->collected_info_children,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk','ca', 'au', 'za', 'br'],
						'targets-children' => 'yes',
						'children-what-purposes' => 'chat'
					]
				],
			],
			[
				'id' => 'children-what-information-email',
				'menu_id'  => 'children-purposes',
				'required' => true,
				'type' => 'multicheckbox',
				'default' => '',
				'label' => __("Information collected for email contact", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->collected_info_children,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us','uk','ca', 'au', 'za', 'br'],
						'targets-children' => 'yes',
						'children-what-purposes' => 'email'
					]
				],
			],


		]
	);
}

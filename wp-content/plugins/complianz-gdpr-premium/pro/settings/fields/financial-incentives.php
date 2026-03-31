<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_pro_financial_fields', 100 );
function cmplz_pro_financial_fields($fields){
	return array_merge(
		$fields,  [
			[
				'id' => 'financial-incentives',
				'menu_id'  => 'financial',
				'required' => true,
				'type' => 'radio',
				'default' => '',
				'label' => __("Do you offer financial incentives, including payments to consumers as compensation, for the collection of personal information, the sale of personal information, or the deletion of personal information?", 'complianz-gdpr'),
				'options' => COMPLIANZ::$config->yes_no,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
						'us_states' => ['cal'],
					]
				],
			],
			[
				'id' => 'financial-incentives-terms-url',
				'menu_id'  => 'financial',
				'placeholder' => __('https://your-terms-page.com','complianz-gdpr'),
				'required' => true,
				'type' => 'text', //url
				'default' => '',
				'label' => __("Enter the URL of the terms & conditions page for the incentives", 'complianz-gdpr'),
				'comment' => __('Please note that the consumer explicitly has to consent to these terms, and that the consumer must be able to revoke this consent', "complianz-gdpr"),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'privacy-statement' => 'generated',
						'regions' => ['us'],
						'us_states' => ['cal'],
						'financial-incentives' => 'yes'
					]
				],
				'help'     => [
					'label' => 'default',
					'title' => __( 'Terms & Conditions', 'complianz-gdpr' ),
					'text'  => __( 'Also see our free Terms & Conditions plugin for this purpose.', "complianz-gdpr" ),
					'url'   => 'https://wordpress.org/plugins/complianz-terms-conditions/',
				],
			],
		]
	);
}

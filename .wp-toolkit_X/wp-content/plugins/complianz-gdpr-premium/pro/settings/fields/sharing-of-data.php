<?php
defined( 'ABSPATH' ) or die();

/**
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_add_sharing_of_data_fields( array $fields ): array {
	$fields = array_merge( $fields, [
		[
			'id'               => 'share_data_bought_or_received',
			'menu_id'          => 'sharing-of-data',
			'type'             => 'radio',
			'default'          => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'au', 'za', 'br' ],
				]
			],
			'options'          => array(
				'1' => __( 'Yes', 'complianz-gdpr' ),
				'2' => __( 'No', 'complianz-gdpr' ),
			),
			'label'            => __( "Do you collect or have you collected personal information about an individual that you bought or received from a third party?", 'complianz-gdpr' ),
			'required'         => true,
		],
		[
			'id'               => 'share_data_bought_or_received_description',
			'menu_id'          => 'sharing-of-data',
			'type'             => 'textarea',
			'default'          => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'au', 'za', 'br' ],
					'share_data_bought_or_received' => '1'
				]
			],
			'label'            => __( "Please describe the circumstances under which that is being done.", 'complianz-gdpr' ),
			'required'         => true,
		],
		[
			'id'               => 'data_disclosed_us',
			'menu_id'          => 'sharing-of-data',
			'type'             => 'multicheckbox',
			'tooltip'          => __( 'Under CCPA you must show a list of the categories of personal information you have disclosed for a business purpose in the preceding 12 months.', 'complianz-gdpr' ),
			'default'          => '',
			'label'            => __( 'Select which categories of personal data you have disclosed for a business purpose in the past 12 months', 'complianz-gdpr' ),
			'loadmore'         => 13,
			'required'         => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'us' ],
					'us_states'         => [ 'cal' ],
				]
			],
			'options'          => COMPLIANZ::$config->details_per_purpose_us,
		],
		[
			'id'                 => 'data_sold_us',
			'menu_id'            => 'sharing-of-data',
			'type'               => 'multicheckbox',
			'default'            => '',
			'tooltip'            => __( 'You must Inform your visitors if you have sold any personal data in the last 12 months, and give them the possibility to opt-out of the future sale of personal information with Complianz.',
				'complianz-gdpr' ),
			'label'              => __( 'Select which categories of personal data you have sold to Third Parties in the past 12 months', 'complianz-gdpr' ),
			'loadmore'           => 13,
			'required'           => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'us', 'ca' ],
					'us_states'         => [ 'cal' ],
					'purpose_personaldata' => 'selling-data-thirdparty',
				]
			],
			'options'            => COMPLIANZ::$config->details_per_purpose_us,
		],
		[
			'id'                 => 'share_data_other',
			'menu_id'            => 'sharing-of-data',
			'type' => 'radio',
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
				]
			],
			'options' => array(
				'1' => __('Yes, both to Processors/Service Providers and other Third Parties, whereby the data subject must give permission', 'complianz-gdpr'),
				'2' => __('No', 'complianz-gdpr'),
				'3' => __('Limited: only with Processors/Service Providers that are necessary for the fulfillment of my service', 'complianz-gdpr'),
			),
			'label' => __("Do you share personal data with other parties?", 'complianz-gdpr'),
			'required' => true,
			'tooltip' => __("A Service Provider is a legal entity that processes information on behalf of a business and to which the business discloses a consumer's personal information for a business purpose pursuant to a written contract.",'complianz-gdpr')
			             .' '
			             .__("Within the GDPR a ‘Processor’ means a natural or legal person, public authority, agency or other body which processes personal data on behalf of the Controller.", 'complianz-gdpr')
			             ." "
			             .__("A Third Party is every other entity which receives personal data, but does not fall within the definition of a Processor or Service Provider", 'complianz-gdpr'),
		],
		[
			'id'                 => 'processor',
			'menu_id'            => 'sharing-of-data',
			'region' => 'eu',
			'type' => 'processors',
			'required' => false,
			'translatable' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'!share_data_other' => '2'
				]
			],
			'label' => __("Processors & Service Providers", 'complianz-gdpr'),
		],
		[
			'id' => 'thirdparty',
			'menu_id'  => 'sharing-of-data',
			'label' => __("Third Parties", 'complianz-gdpr'),
			'type' => 'thirdparties',
			'required' => false,
			'translatable' => true,
			'default' => '',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'share_data_other' => '1',
					'privacy-statement' => 'generated',
				]
			],
		],
	] );
	return $fields;
}

add_filter( 'cmplz_fields', 'cmplz_add_sharing_of_data_fields');

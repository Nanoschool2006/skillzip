<?php
defined( 'ABSPATH' ) or die();

/**
 * For saving purposes, types should be overridden at the earliest moment
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_add_details_per_purpose( array $fields ): array {
	$fields = array_merge( $fields, [
		[
			'id'               => 'automated_processes',
			'menu_id'          => 'purpose',
			'type'             => 'radio',
			'options'          => COMPLIANZ::$config->yes_no,
			'placeholder'      => __( "We use digital services to automate processes without human intervention to optimize our workflows. We make decisions based on the frequency of payments, customer contact, profile changes, and other user-related behavior to personalize the customer journey.", 'complianz-gdpr' ),
			'required'         => true,
			'tooltip'          => __( "The placeholder is a general example, please rewrite to your specific situation.", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'eu', 'uk', 'za' ],
				]
			],
			'label'            => __( "Do you make decisions based on automated processes, such as profiling, that could have significant consequences for users?", 'complianz-gdpr' ),
		],
		[
			'id'        => 'automated_processes_details',
			'menu_id'   => 'purpose',
			'type'      => 'textarea',
			'required'  => true,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'regions'           => [ 'eu', 'uk', 'za' ],
					'automated_processes' => 'yes',
				]
			],
			'label'            => __( "Specify what kind of decisions these are, what the consequences are, and what (in general terms) the logic behind these decisions is.", 'complianz-gdpr' ),
		],
		[
			'id'               => 'legal-obligations-description',
			'menu_id'          => 'purpose',
			'type'             => 'textarea',
			'default'          => '',
			'label'            => __( "The collection is required or authorized by the following law or court/tribunal order:", 'complianz-gdpr' ),
			'required'         => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
					'purpose_personaldata' => 'legal-obligations',
					'regions'           => [ 'eu', 'za' ],
				]
			],

		]
	] );
	$index  = 10;
	foreach ( COMPLIANZ::$config->purposes as $key => $label ) {
		$index    += 10;
		$fields[] = [
			'id'               => $key . '_retain_data',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 3,
			'type'             => 'radio',
			'default'          => '',
			'required'         => true,
			'help'             => [
				'id'    => 'retain_data',
				'label' => 'default',
				'title' => __( "Retaining data", 'complianz-gdpr' ),
				'text'  => __( 'How to determine the retention of specific data sets? ', 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/data-retention',
			],
			'label'            => __( "How long will you retain data for this specific purpose?", 'complianz-gdpr' ),
			'options'          => array(
				'1' => __( 'When the services are terminated or completed', 'complianz-gdpr' ),
				'2' => __( 'When the services are terminated or completed, plus the duration specified below', 'complianz-gdpr' ),
				'3' => __( 'Other period', 'complianz-gdpr' ),
				'4' => __( "I determine the retention period according to fixed objective criteria", 'complianz-gdpr' ),
			),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'    => 'generated',
					'purpose_personaldata' => $key,
				]
			],
		];
		$fields[] = [
			'id'               => $key . '_retain_wmy',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 4,
			'type'             => 'text',
			'default'          => '',
			'required'         => true,
			'label'            => __( "Retention period in weeks, months or years:", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'    => 'generated',
					'purpose_personaldata' => $key,
					$key . '_retain_data'  => '3'
				]
			],
		];
		$fields[] = [
			'id'               => $key . '_retention_period_months',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 5,
			'type'             => 'text',
			'default'          => '',
			'required'         => true,
			'placeholder'      => __( 'Retention period in months', 'complianz-gdpr' ),
			'label'            => __( "Necessary retention period in months after completion:", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'    => 'generated',
					'purpose_personaldata' => $key,
					$key . '_retain_data'  => '2',
				]
			],
		];

		$fields[] = [
			'id'               => $key . '_description_criteria_retention',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 6,
			'type'             => 'text',
			'default'          => '',
			'required'         => true,
			'label'            => __( "Describe these criteria in understandable terms:", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'    => 'generated',
					'purpose_personaldata' => $key,
					$key . '_retain_data'  => '4'
				]
			],
		];

		$fields[] = [
			'id'               => $key . '_processing_data_lawfull',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 7,
			'type'             => 'radio',
			'default'          => '',
			'required'         => true,
			'options'          => COMPLIANZ::$config->lawful_bases,
			'label'            => __( "The processing of personal data requires a lawful basis, which do you use?", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'    => 'generated',
					'purpose_personaldata' => $key,
					'regions'              => array( 'eu', 'uk', 'za', 'br' ),
				]
			],
			'help'             => [
				'id'    => 'processing_data_lawfull',
				'label' => 'default',
				'title' => __( "What are lawful bases?", 'complianz-gdpr' ),
				'text'  => __( 'Getting to know the lawful bases will be very helpful.', 'complianz-gdpr' ),
				'url'   => 'https://complianz.io/what-lawful-basis-for-data-processing',
			],
		];

		/**
		 * This is the field for the explanation about the relevant legislation for the answer: "It is necessary for credit protection".
		 */
		$fields[] = [
			'id'               => $key . '_credit_protection_relevant_legislation',
			'menu_id'          => 'details-per-purpose',
			'order'            => $index + 8,
			'type'             => 'text',
			'default'          => '',
			'required'         => true,
			'placeholder'      => __( 'Provisions of the relevant legislation', 'complianz-gdpr' ),
			'label'            => __( "Please include the provisions of the relevant legislation:", 'complianz-gdpr' ),
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement'               => 'generated',
					'purpose_personaldata'            => $key,
					$key . '_processing_data_lawfull' => '10'
				]
			],
		];
	}

	return $fields;
}

add_filter( 'cmplz_fields', 'cmplz_add_details_per_purpose', 30, 1 );

<?php
defined( 'ABSPATH' ) or die();

/**
 * For saving purposes, types should be overridden at the earliest moment
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_add_imprint_fields( array $fields ): array {
	return array_merge( $fields, [
			[
				'id'               => 'legal_form_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'required'         => false,
				'tooltip'          => __( "Leave empty if not applicable", 'complianz-gdpr' ),
				'placeholder'      => __('e.g. GMBH, Limited, SRL etc', 'complianz-gdpr' ),
				'label'            => __( "What is the legal form of your organization?", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'email_company_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'email',
				'required'         => false,
				'placeholder'      => 'hello@company.com',
				'default'          => '',
				'tooltip'          => __( "Your email address will be obfuscated on the front-end to prevent spidering.", 'complianz-gdpr' ),
				'label'            => __( "What is the email address your visitors can use to contact you?", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'vat_company',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'placeholder'      => __( "Leave empty if not applicable", 'complianz-gdpr' ),
				'tooltip'          => __( "If you do not have a VAT ID, you can leave this question unanswered", 'complianz-gdpr' ),
				'label'            => __( "VAT ID of your company", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'register',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "In which register of companies, associations, partnerships or cooperatives is your company registered?", 'complianz-gdpr' ),
				'tooltip'          => __( "Generally the Chamber of Commerce or a local Court register, but other registers may apply. Leave blank if this does not apply to you.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'business_id',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'placeholder'      => __( "Leave empty if not applicable", 'complianz-gdpr' ),
				'label'            => __( "What is the registration number corresponding with the answer to the above question?", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'representative',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "Name one or more person(s) who can legally represent the company or legal entity.", 'complianz-gdpr' ),
				'tooltip'          => __( "This is generally an owner or director of the legal entity.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'inspecting_authority',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "If the service or product displayed on this website requires some sort of official approval, state the (inspecting) authority.", 'complianz-gdpr' ),
				'tooltip'          => __( "For example, a website from a financial advisor might need permission from an inspecting authority.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'professional_association',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "Does your website display services or products that require registration with a professional association? If so, name the professional association.",
					'complianz-gdpr' ),
				'tooltip'          => __( "Registration heavily depends on specific national laws. In most countries this obligation applies to Doctors, Pharmacists, Architects, Consulting engineers, Notaries, Patent attorneys, Psychotherapists, Lawyers, Tax consultants, Veterinary surgeons, Auditors or Dentists.",
					'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'legal_job_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'default'          => 'no',
				'label'            => __( "Does your profession or the activities displayed on the website require a certain diploma?", 'complianz-gdpr' ),
				'tooltip'          => __( "Required for an activity under a professional title, in so far as the use of such a title is reserved to the holders of a diploma governed by laws, regulations or administrative provisions.",
					'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'legal_job_title',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "Name the legal job title", 'complianz-gdpr' ),
				'placeholder'      => __( "Medical Doctor", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'         => 'generated',
						'legal_job_imprint' => 'yes'
					]
				],
			],
			[
				'id'               => 'legal_job_country_imprint',
				'menu_id'          => 'impressum',
				'options'          => COMPLIANZ::$config->countries,
				'type'             => 'select',
				'label'            => __( "Name the country where the diploma was awarded", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'         => 'generated',
						'legal_job_imprint' => 'yes'
					]
				],
			],
			[
				'id'               => 'professional_regulations',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "Professional Regulations.", 'complianz-gdpr' ),
				'tooltip'          => __( "If applicable, mention the professional regulations that may apply to your activities, and the URL where to find them.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'professional_regulations_url',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'placeholder'      => __( "Leave empty if the above is not applicable", 'complianz-gdpr' ),
				'label'            => __( "The URL to the regulations so website visitors know how to access them.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'is_webshop',
				'menu_id'          => 'impressum',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Do you sell products or services through your website?", 'complianz-gdpr' ),
				'tooltip'          => __( "If this is a webshop, the Imprint should include a paragraph about dispute settlement.", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
			[
				'id'               => 'has_webshop_obligation',
				'menu_id'          => 'impressum',
				'type'             => 'radio',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Are you obliged or prepared to use Alternative Dispute Resolution?", 'complianz-gdpr' ),
				'tooltip'          => __( "Alternate Dispute Resolution means settling disputes without lawsuit.", 'complianz-gdpr' ),
				'required'         => true,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'  => 'generated',
						'is_webshop' => 'yes'
					]
				],
			],
			// If Germany, Below Questions
			[
				'id'               => 'german_imprint_appendix',
				'menu_id'          => 'impressum',
				'type'             => 'radio',
				'default'          => 'yes',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Do you target a German audience?", 'complianz-gdpr' ),
				'tooltip'          => __( "This will enable questions specific to an Impressum", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'          => 'generated',
						'eu_consent_regions' => 'yes',
					]
				],
			],
			[
				'id'               => 'offers_editorial_content_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'radio',
				'required'         => false,
				'default'          => 'no',
				'options'          => COMPLIANZ::$config->yes_no,
				'label'            => __( "Do you offer content for journalistic and editorial purposes?", 'complianz-gdpr' ),
				'tooltip'          => __( "For example websites that run a blog, publish news articles or moderate an online community.", 'complianz-gdpr' ),
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'               => 'generated',
						'german_imprint_appendix' => 'yes',
					]
				],
			],
			[
				'id'               => 'editorial_responsible_name_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "State the full name of the person responsible for the content on this website.", 'complianz-gdpr' ),
				'tooltip'          => __( "The person should be stated with first and last name.", 'complianz-gdpr' ),
				'placeholder'      => "Max Mustermann",
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'                        => 'generated',
						'offers_editorial_content_imprint' => 'yes',
						'german_imprint_appendix'          => 'yes',
					]
				],
			],
			[
				'id'               => 'editorial_responsible_residence_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'label'            => __( "What is the residence of the person responsible for the content on this website?", 'complianz-gdpr' ),
				'placeholder'      => "Berlin",
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'                        => 'generated',
						'offers_editorial_content_imprint' => 'yes',
						'german_imprint_appendix'          => 'yes',
					]
				],
			],
			[
				'id'               => 'capital_stock',
				'menu_id'          => 'impressum',
				'type'             => 'text',
				'placeholder'      => '€ 100',
				'label'            => __( "Capital Stock", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'               => 'generated',
						'german_imprint_appendix' => 'yes',
					]
				],
			],
			[
				'id'               => 'liability_insurance_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'textarea',
				'label'            => __( "What is the name, address, and geographical scope of your professional liability insurance?", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum'               => 'generated',
						'german_imprint_appendix' => 'yes',
					]
				],
			],
			[
				'id'               => 'open_field_imprint',
				'menu_id'          => 'impressum',
				'type'             => 'textarea',
				'label'            => __( "For additional information, please use this field.", 'complianz-gdpr' ),
				'required'         => false,
				'react_conditions' => [
					'relation' => 'AND',
					[
						'impressum' => 'generated',
					]
				],
			],
		]
	);
}

add_filter( 'cmplz_fields', 'cmplz_add_imprint_fields', 20, 1 );

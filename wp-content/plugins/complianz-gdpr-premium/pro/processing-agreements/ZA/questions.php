<?php
defined('ABSPATH') or die("you do not have access to this page!");
$questions = array(
	[
		'id'       => 'processor-activities-za',
		'translatable' => true,
		'type' => 'text',
		'default' => '',
		'value' => '',
		'required' => true,
		'help'     => [
			'label' => 'default',
			'title' => __( "Activities", 'complianz-gdpr' ),
			'text' => __('E.g. hosting, data storage, payment processing, sending newsletters', 'complianz-gdpr'),
		],
		'label' => __("Describe briefly what activities your operator will perform.", 'complianz-gdpr'),
	],
	[
		'id'       => 'data-from-whom-za',
		'type' => 'multicheckbox',
		'default' => '',
		'value' => '',
		'required' => true,
		'label' => __("Who's data will be processed?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('Customers', 'complianz-gdpr'),
			'2' => __('Employees', 'complianz-gdpr'),
			'3' => __('Suppliers', 'complianz-gdpr'),
			'4' => __('Account holders', 'complianz-gdpr'),
			'5' => __('Job applicants', 'complianz-gdpr'),
			'6' => __('Website visitors', 'complianz-gdpr'),
			'7' => __('Patients', 'complianz-gdpr'),
			'8' => __('Leads', 'complianz-gdpr'),
			'9' => __('Members', 'complianz-gdpr'),
			'10' => __('Tenants', 'complianz-gdpr'),
			'11' => __('Other:', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'data-from-whom-other-za',
		'type' => 'text',
		'translatable' => true,
		'default' => '',
		'value' => '',
		'required' => true,
		'label' => __("Which categories can the persons be placed in?", 'complianz-gdpr'),
		'help'     => [
			'label' => 'default',
			'title' => __( "Multiple categories", 'complianz-gdpr' ),
			'text' => __('Multiple categories should be separated with a semi-colon.' , 'complianz-gdpr'),
		],
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'data-from-whom-za' => '11',
			]
		],
	],
	[
		'id'       => 'what-kind-of-data-za',
		'type' => 'multicheckbox',
		'default' => '',
		'required' => true,
		'label' => __("What kind of data will be processed by the data processor?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('Name Address City', 'complianz-gdpr'),
			'2' => __('Phone number', 'complianz-gdpr'),
			'3' => __('Email address', 'complianz-gdpr'),
			'4' => __('Browse history', 'complianz-gdpr'),
			'5' => __('IP Address', 'complianz-gdpr'),
			'6' => __('Social Media accounts', 'complianz-gdpr'),
			'7' => __("Photo's", 'complianz-gdpr'),
			'8' => __('Curriculum Vitae', 'complianz-gdpr'),
			'9' => __('Birth Date', 'complianz-gdpr'),
			'10' => __('Marital status', 'complianz-gdpr'),
			'11' => __('Financial data', 'complianz-gdpr'),
			'12' => __('Medical data', 'complianz-gdpr'),
			'13' => __('Other:', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'what-kind-of-data-other',
		'type' => 'text',
		'translatable' => true,
		'default' => '',
		'required' => true,
		'label' => __("Which kind of personal data will be processed?", 'complianz-gdpr'),
		'help'     => [
			'label' => 'default',
			'title' => __( "Multiple categories", 'complianz-gdpr' ),
			'text' => __('Multiple categories should be separated with a semi-colon.' , 'complianz-gdpr'),
		],
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'what-kind-of-data-za' => '13',
			]
		],
	],
	[
		'id'       => 'allow-outside-eu-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Do you allow data to be processed outside South Africa?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('No, processing outside South Africa is not allowed.', 'complianz-gdpr'),
			'2' => __('Yes, but only when the countries share the same security levels concerning privacy.', 'complianz-gdpr'),
		),
	],

		// CONTACTGEGEVENS PERSOON - CONTACT DETAILS
	[
		'id'       => 'name_of_processor-za',
		'type' => 'hidden',
		'default' => '',
		'label' => __("What is the name of the processor?", 'complianz-gdpr'),
	],

		// HANDLING REQUESTS FROM THOSE involved
	[
		'id'       => 'deal_with_requests-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("How will you deal with requests from those involved?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('I will deal with requests from those involved, The operator will forward everything to me.', 'complianz-gdpr'),
			'2' => __('The operator may charge additional costs that it incurs in this context.', 'complianz-gdpr'),
		),
		'tooltip' => __('An individual can make a subject access request to you verbally or in writing. It can also be made to any part of your organisation (including by social media) and does not have to be to a specific person or contact point.','complianz-gdpr'),
	],
	[
		'id'       => 'security_measures-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Which security measures should the Operator take?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('The Operator must at least be able to meet the legal minimum.', 'complianz-gdpr'),
			'2' => __('The Operator must comply with a separate security protocol.', 'complianz-gdpr'),
			'3' => __('I want to be able to choose the required security measures myself.', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'security-protocol-where-za',
		'type' => 'radio',
		'default' => '',
		'label' => __("Where can people find this security protocol?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('The protocol is annexed to this agreement.', 'complianz-gdpr'),
			'2' => __('The protocol can be found online via an URL', 'complianz-gdpr'),
		),
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'security_measures-za' => '2',
			]
		],
	],
	[
		'id'       => 'security-protocol-where-url-za',
		'type' => 'url',
		'default' => '',
		'label' => __("What is the URL?", 'complianz-gdpr'),
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'security-protocol-where-za' => '2',
			]
		],
	],
	[
		'id'       => 'processing-security-measures-za',
		'type' => 'multicheckbox',
		'default' => '',
		'label' => __("Select the required security standard or measure.", 'complianz-gdpr'),
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'security_measures-za' => '3',
			]
		],
		'options' => array(
			'1' => __('Username and Password', 'complianz-gdpr'),
			'2' => 'DNSSEC',
			'3' => 'TLS / SSL',
			'4' => __('DKIM, SPF en DMARC', 'complianz-gdpr'),
			'5' => __('Physical security measures of systems which contain personal  data.', 'complianz-gdpr'),
			'6' => __('Security software', 'complianz-gdpr'),
			'7' => __('ISO27001/27002 certified', 'complianz-gdpr'),
			'8' => 'HTTP Strict Transport Security',
			'9' => 'X-Content-Type-Options',
			'10' => 'X-XSS-Protection',
			'11' => 'X-Frame-Options',
			'14' => 'Content Security Policy',
			'15' => __('STARTTLS and DANE','complianz-gdpr'),
			'16' => 'WPA2 Enterprise',
			'17' => __('Other:', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'processing-security-measures-other-za',
		'type' => 'textarea',
		'default' => '',
		'label' => __("Other:", 'complianz-gdpr'),
		'react_conditions'        => [
			'relation' => 'AND',
			[
				'processing-security-measures-za' => '17',
			]
		],
	],
		// RIGHT OF AUDIT
	[
		'id'       => 'when-audit-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("When can you, as the data controller, carry out audits?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('If similar reports give no or insufficient clarification.', 'complianz-gdpr'),
			'2' => __('With a reasonable suspicion of abuse,', 'complianz-gdpr'),
			'3' => __('Once every quarter and more often with reasonable suspicion of abuse.', 'complianz-gdpr'),
			'4' => __('Once every year and more often with reasonable suspicion of abuse.', 'complianz-gdpr'),
		),
	],

	[
		'id'       => 'audit-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Can the audit be carried out by an independent Third Party?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('The audit may be performed by an independent Third Party.', 'complianz-gdpr'),
			'2' => __('Only I can perform audits', 'complianz-gdpr'),
		),
	],


	[
		'id'       => 'what-do-with-findings-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("What should be done with the findings of the audit?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('The processor is obliged to implement the findings as quickly as possible.', 'complianz-gdpr'),
			'2' => __('The parties should decide together what to do with these findings.', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'audit-costs-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Who is responsible for any audit costs?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('Responsible party', 'complianz-gdpr'),
			'2' => __('Operator', 'complianz-gdpr'),
			'3' => __('The Operator, in case of non - trivial violations of the obligations from the operator agreement. Otherwise the responsible party.', 'complianz-gdpr'),
		),
	],
	[
		'id'       => 'when-informed-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("How quickly should the Data Controller be informed of a data breach?", 'complianz-gdpr'),
		'options' => array(
			'1' => __('Immediately (without unreasonable delay) after the leak has become known to the Processor.', 'complianz-gdpr'),
			'2' => __('Immediately (without unreasonable delay), within 24 hours after the leak has become known to the Processor.', 'complianz-gdpr'),
			'3' => __('Immediately (without unreasonable delay), within 36 hours after the leak has become known to the Processor.', 'complianz-gdpr'),
		),
	],
		// DATA breach
	[
		'id'       => 'maximize-liability-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Do you want to limit liability?", 'complianz-gdpr'),
		'options' => COMPLIANZ::$config->yes_no,
	],
	[
		'id' =>'amount-liable-za',
		'type' => 'text',
		'default' => '',
		'placeholder' => '0.00 $',
		'react_conditions' => [
			'relation' => 'AND',
			[
				'maximize-liability-za' =>'yes'
			],
		],
		'required' => true,
		'label' => __("What is the maximum liability for violations of the processor agreement?", 'complianz-gdpr'),
	],
	[
		'id'       => 'insurance-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'label' => __("Should the Processor take out liability insurance?", 'complianz-gdpr'),
		'options' => COMPLIANZ::$config->yes_no,
	],
	array(
		'id'       => 'max_cost_of_insurance-za',
		'type' => 'text',
		'default' => '',
		'required' => true,
		'placeholder' => '0.00 €',
		'label' => __("What is the minimum amount the insurance should cover?", 'complianz-gdpr'),
		'react_conditions' => [
			'relation' => 'AND',
			[
				'insurance-za' => 'yes'
			],
		],
	),
	array(
		'id'       => 'insurance_conditions-za',
		'type' => 'multicheckbox',
		'default' => '',
		'required' => true,
		'react_conditions' => [
			'relation' => 'AND',
			[
				'insurance-za' => 'yes'
			],
		],
		'label' => __("The insurance conditions must provide at least cover for the following claims:", 'complianz-gdpr'),
		'options' => array(
			'1' => __('Data breaches.', 'complianz-gdpr'),
			'2' => __('Not complying to the processing data contract.', 'complianz-gdpr'),
		),
	),
	array(
		'id'       => 'access-to-policy-za',
		'type' => 'radio',
		'default' => '',
		'required' => true,
		'react_conditions' => [
			'relation' => 'AND',
			[
				'insurance-za' => 'yes'
			],
		],
		'options' => COMPLIANZ::$config->yes_no,
		'label' => __("Access to the insurance policy can be demanded.", 'complianz-gdpr'),
	),
	);

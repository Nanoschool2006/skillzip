<?php
/*
 * This document is intentionally not translatable, as it is intended to be for DE/AU citizens, and should therefore always be in German
 *
 * */
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages ['all']['impressum']['document_elements'] = array(
	array(
		'content' => '<i>' . cmplz_sprintf( _x( 'This imprint was last updated on %s.', 'Legal document', 'complianz-gdpr' ), '[checked_date]' ) .'</i>',
	),

	// NO VAT
	array(
		'subtitle'   => _x( 'The owner of this website is:', 'Legal document', 'complianz-gdpr' ),
		'numbering' => false,
		'content' => '[organisation_name] [legal_form_imprint]<br>' .
					'[address_company]' .'<br>'. '[country_company]' .'<br>'.
					_x( 'Email:', 'Legal document', 'complianz-gdpr' ).'&nbsp;[email_company_imprint]' .'<br>'.
					'[telephone_company]' .'<br>',
		'condition' => array(
			'vat_company' => 'EMPTY',
		),
	),

	// VAT
	array(
		'subtitle'   => _x( 'The owner of this website is:', 'Legal document', 'complianz-gdpr' ),
		'numbering' => false,
		'content' => '[organisation_name] [legal_form_imprint]<br>' .
		             '[address_company]' .'<br>'. '[country_company]' .'<br>'.
					_x( 'Email:', 'Legal document', 'complianz-gdpr' ).'&nbsp;[email_company_imprint]' .'<br>'.
					'[telephone_company]' .'<br>'.
					__( 'VAT ID:', 'complianz-gdpr' ).'&nbsp;[vat_company]',
		'condition' => array(
			'vat_company' => 'NOT EMPTY',
		),
	),

	// Legal representative
	array(
		'subtitle'   => cmplz_sprintf(_x( 'The legal representative(s) of %s %s:', 'Legal document', 'complianz-gdpr' ),'[organisation_name]', '[legal_form_imprint]'),
		'content' 	 => '[representative]',
		'condition' => array(
			'representative' => 'NOT EMPTY',
		),
	),

	// General
	array(
		'title'   => _x( 'General', 'Legal document', 'complianz-gdpr' ),
	),

	array(
		'subtitle'   => cmplz_sprintf(_x( 'We are registered at %s under the license or registration number:', 'Legal document', 'complianz-gdpr' ),'[register]'),
		'content' 	 => '[business_id]',
		'condition' => array(
			'register' => 'NOT EMPTY',
		),
	),

	array(
		'subtitle'   => _x( 'The name of our supervisory authority is:', 'Legal document', 'complianz-gdpr' ),
		'content' 	 => '[inspecting_authority]',
		'condition' => array(
			'inspecting_authority' => 'NOT EMPTY',
		),
	),

	array(
		'subtitle'   => _x( 'We display services or products on our website, which require registration with the following professional association:', 'Legal document', 'complianz-gdpr' ),
		'content' 	 => '[professional_association]',
		'condition' => array(
			'professional_association' => 'NOT EMPTY',
		),
	),

	array(
		'subtitle'   => _x( 'The profession or the activities displayed on this website require a certain diploma, as stated here:', 'Legal document', 'complianz-gdpr' ),
		'content' => cmplz_sprintf(_x( '%s, this diploma or job title was awarded in %s.', 'Legal document', 'complianz-gdpr'), '[legal_job_title]', '[legal_job_country_imprint]'),
		'condition' => array(
			'legal_job_imprint' => 'yes',
		),
	),

	array(
		'subtitle'   => _x( 'The following Professional Rules and Regulations apply to our organization:', 'Legal document', 'complianz-gdpr' ),
		'content' => '[professional_regulations]',
		'condition' => array(
			'professional_regulations' => 'NOT EMPTY',
		),
	),

	array(
		'content' => _x( 'You can access these rules and regulations here:', 'Legal document', 'complianz-gdpr') .'<br />'.
		'[professional_regulations_url]',
		'condition' => array(
			'professional_regulations_url' => 'NOT EMPTY',
		),
	),

	// Dispute Resolution - EU only
	array(
		'content' => _x( 'We are not willing or obliged to participate in dispute resolution procedures before a consumer arbitration board.', 'Legal document', 'complianz-gdpr'),
		'condition' => array(
			'has_webshop_obligation' => 'NOT yes',
		),
	),

	array(
		'content' => _x( 'We are willing or obliged to participate in dispute resolution procedures before a consumer arbitration board.', 'Legal document', 'complianz-gdpr'),
		'condition' => array(
			'has_webshop_obligation' => 'yes',
		),
	),

	// End Dispute Resolution

	// German Only Section
	array(
		'title'   => _x('The following information is mandatory according to German law.', 'Legal document', 'complianz-gdpr'),
		'condition' => array(
			'german_imprint_appendix' => 'yes',
			'eu_consent_regions' => 'yes',
		),
	),

	array(
		'subtitle'  => 'Die Eigentumsanteile der Gesellschaft (Aktienkapital), die von ihr ausgegeben wurden:',
		'content' => '[capital_stock]',
		'condition' => array(
			'capital_stock' => 'NOT EMPTY',
			'german_imprint_appendix' => 'yes',
		),
	),

	array(
		'subtitle' => 'Wir stellen journalistische und redaktionell aufbereitete Inhalte zur Verfügung.',
		'content'  => 'Daher müssen wir den Namen und den Wohnort der Person nennen, die für den Inhalt dieser Website verantwortlich ist:' . '<br />' .
		 	'<br />' .'Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV ist: [editorial_responsible_name_imprint] aus [editorial_responsible_residence_imprint].',
		'condition' => array(
			'offers_editorial_content_imprint' => 'yes',
			'german_imprint_appendix' => 'yes',
		),
	),

	array(
		'subtitle' => 'Unsere Berufshaftpflichtversicherung lautet:',
		'content' => '[liability_insurance_imprint]',
		'condition' => array(
			'liability_insurance_imprint' => 'NOT EMPTY',
			'german_imprint_appendix' => 'yes',
		),
	),

	array(
		'title'   => _x('Additional information', 'Legal document', 'complianz-gdpr'),
		'content' => '[open_field_imprint]',
		'condition' => array(
			'open_field_imprint' => 'NOT EMPTY',
		),
	),
);

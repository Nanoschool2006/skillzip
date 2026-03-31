<?php
defined('ABSPATH') or die("you do not have access to this page!");
/*
 *
 * This document is based on the privacy statement for the UK
 *
 * */
$this->pages['za']['privacy-statement']['document_elements'] = array(
	'last-updated' => array(
		'content' => '<i>' . cmplz_sprintf(_x('This privacy statement was last updated on %s and applies to citizens and legal permanent residents of South Africa.', 'Legal document privacy statement', 'complianz-gdpr'), '[publish_date]') . '</i><br /><br />',
	),
	'inleiding' => array(
		'p' => false,
		'content' =>
			'<p>'. cmplz_sprintf( _x('In this privacy statement, we explain what we do with the data we obtain about you via %s. We recommend you carefully read this statement. In our processing we comply with the requirements of privacy legislation. That means, among other things, that:', 'Legal document privacy statement', 'complianz-gdpr'), '[domain]') .'</p>'.
			'<ul>
                <li>' . _x('we clearly state the purposes for which we process personal data. We do this by means of this privacy statement;', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('we aim to limit our collection of personal data to only the personal data required for legitimate purposes;', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('we first request your explicit consent to process your personal data in cases requiring your consent;', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('we take appropriate security measures to protect your personal data and also require this from parties that process personal data on our behalf;', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('we respect your right to access your personal data or have it corrected or deleted, at your request.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
            </ul>' .
			'<p>'._x('If you have any questions, or want to know exactly what data we keep of you, please contact us.', 'Legal document privacy statement', 'complianz-gdpr') .'</p>',
	),
	array(
		'p' => false,
		'subtitle' => _x('We also collect or may have collected personal information that we buy or receive from a third party. This is done under the following circumstances', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => '[share_data_bought_or_received_description]',
		'condition' => array( 'share_data_bought_or_received' => '1' ),
	),

	array(
		'title' => _x("What if you don't provide us with your personal information?", 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x("If you don't provide us with your personal information, we may not be able to provide you with the information, products or assistance that you are seeking.", 'Legal document privacy statement', 'complianz-gdpr'),
	),

	//In the privacy-policy page the first paragraph containing purpose and data retention period is generated in the class-config.php
	'third-party-sharing' => array(
		'title' => _x('Sharing with other parties', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We only share this data with operators and with other third parties for which consent must be obtained.', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('share_data_other' => '1'),
	),

	//this has to be above the operators, as operators are shown for both 1 and 3. It's condition is then "Not 2"
	'no-sharing-limited' => array(
		'title' => _x('Sharing with other parties', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We only share or disclose this data to operators for the following purposes:', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('share_data_other' => '3'),
	),

	'no-sharing-limited-sub' => array(
		'numbering' => false,
		'subtitle' => _x('Operators', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'condition' => array(
			'processor' => 'NOT EMPTY',
			'share_data_other' => 'NOT 2',
		),
	),

	'processor' => array(
		'p' => false,
		'numbering' => false,
		'content' =>
			"<p>
                <b>" . _x("Name:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[name]<br />
                <b>" . _x("Country:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[country]<br />
                <b>" . _x("Purpose:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[purpose]<br />
            </p>",
		'condition' => array(
			'processor' => 'loop',
			'share_data_other' => 'NOT 2',
		),
	),
	'no-sharing-limited-sub2' => array(
		'numbering' => false,
		'subtitle' => _x('Third parties', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'condition' => array(
			'thirdparty' => 'NOT EMPTY',
			'share_data_other' => '1',
		),
	),

	array(
		'p' => false,
		'numbering' => false,
		'content' =>
			"<p>
                <b>" . _x("Name:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[name]<br />
                <b>" . _x("Country:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[country]<br />
                <b>" . _x("Purpose:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[purpose]<br />
                <b>" . _x("Data:", 'Legal document privacy statement', 'complianz-gdpr') . "</b>&nbsp;[data]
            </p>",
		'condition' => array(
			'thirdparty' => 'loop',
			'share_data_other' => '1',
		),
	),

	'no-sharing' => array(
		'p' => false,
		'title' => _x('Sharing with other parties', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We do not share your data with third parties.', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('share_data_other' => '2'),
		'callback_condition' => 'NOT cmplz_site_shares_data', //even though someone may have entered he doesn't share data, it may be possible data is shared with vendors.
	),

	'privacy-policy-cookies' => array(
		'p' => false,
		'title' => _x('Cookies', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => cmplz_sprintf(_x('Our website uses cookies. For more information about cookies, please refer to our %sCookie Policy%s.', 'Legal document privacy statement', 'complianz-gdpr'), '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
		'condition' => array(
			'uses_ad_cookies_personalized' => 'NOT tcf',
		),
	),

	array(
		'p' => false,
		'title' => _x('Cookies', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => cmplz_sprintf(_x('To provide the best experiences, we and our partners use technologies like cookies to store and/or access device information. Consenting to these technologies will allow us and our partners to process personal data such as browsing behavior or unique IDs on this site. Not consenting or withdrawing consent, may adversely affect certain features and functions. For more information about these technologies and partners, please refer to our %sCookie Policy%s.', 'Legal document privacy statement', 'complianz-gdpr'), '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
		'condition' => array(
			'uses_ad_cookies_personalized' => 'tcf',
		),
	),

	array(
		'title' => _x('Disclosure practices', 'Legal document', 'complianz-gdpr'),
		'content' =>
			'<p>' . _x('We disclose personal information if we are required by law or by a court order, in response to a law enforcement agency, to the extent permitted under other provisions of law, to provide information, or for an investigation on a matter related to public safety.', 'Legal document', 'complianz-gdpr') . "</p>" .
			'<p>' . _x('If our website or organisation is taken over, sold, or involved in a merger or acquisition, your details may be disclosed to our advisers and any prospective purchasers and will be passed on to the new owners.', 'Legal document', 'complianz-gdpr') . "</p>",
	),

	array(
		'content' => cmplz_sprintf(_x('%s participates in the IAB Europe Transparency & Consent Framework and complies with its Specifications and Policies. It uses the Consent Management Platform with the identification number %s.', 'Legal document privacy statement', 'complianz-gdpr'), '[organisation_name]', '332')."&nbsp;",
		'condition' => array(
			'uses_ad_cookies_personalized' => 'tcf',
		),
	),

	'statistics-google' => array(
		'p' => false,
		'content' => _x('We have concluded a data processing agreement with Google.', 'Legal document privacy statement', 'complianz-gdpr'),
		'callback_condition' => 'cmplz_accepted_processing_agreement',
	),

	'statistics-no-sharing' => array(
		'p' => false,
		'content' => _x('Google may not use the data for any other Google services.', 'Legal document privacy statement', 'complianz-gdpr'),
		'callback_condition' => 'cmplz_statistics_no_sharing_allowed',
	),

	'statistics-no-ip' => array(
		'p' => false,
		'content' => _x('The inclusion of full IP addresses is blocked by us.', 'Legal document privacy statement', 'complianz-gdpr'),
		'callback_condition' => 'cmplz_no_ip_addresses',
	),

	'security' => array(
		'p' => false,
		'title' => _x('Security', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We are committed to the security of personal data. We take appropriate security measures to limit abuse of and unauthorised access to personal data. This ensures that only the necessary persons have access to your data, that access to the data is protected, and that our security measures are regularly reviewed.', 'Legal document privacy statement', 'complianz-gdpr'),
	),
	'security_which' => array(
		'content' => _x('The security measures we use consist of:', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('secure_personal_data' => 'NOT 1'),
	),
	'security_which_content' => array(
		'p' => false,
		'content' => '[which_personal_data_secure]',
		'condition' => array('secure_personal_data' => 'NOT 1'),
	),
	'third-party-website' => array(
		'p' => false,
		'title' => _x('Third-party websites', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('This privacy statement does not apply to third-party websites connected by links on our website. We cannot guarantee that these third parties handle your personal data in a reliable or secure manner. We recommend you read the privacy statements of these websites prior to making use of these websites.', 'Legal document privacy statement', 'complianz-gdpr'),
	),
	'changes-privacy-statement' => array(
		'p' => false,
		'title' => _x('Amendments to this privacy statement', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We reserve the right to make amendments to this privacy statement. It is recommended that you consult this privacy statement regularly in order to be aware of any changes. In addition, we will actively inform you wherever possible.', 'Legal document privacy statement', 'complianz-gdpr'),
	),
	'insight-changes-your-data' => array(
		'title' => _x('Accessing and modifying your data', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'p'=>false,
		'content' =>
			'<p>'._x('If you have any questions or want to know which personal data we have about you, please contact us. You can contact us by using the information below. You have the following rights:', 'Legal document privacy statement', 'complianz-gdpr').'</p>'.
			'<ul>
                <li>' . _x('You have the right to know why your personal data is needed, what will happen to it, and how long it will be retained for.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('Right of access: You have the right to access your personal data that is known to us.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('Right to rectification: you have the right to supplement, correct, have deleted or blocked your personal data whenever you wish.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('If you give us your consent to process your data, you have the right to revoke that consent and to have your personal data deleted.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
                <li>' . _x('Right to object: you may object to the processing of your data. We comply with this, unless there are justified grounds for processing.', 'Legal document privacy statement', 'complianz-gdpr') . '</li>
            </ul>' .
			'<p>'._x('Please make sure to always clearly state who you are, so that we can be certain that we do not modify or delete any data of the wrong person.', 'Legal document privacy statement', 'complianz-gdpr').'</p>',
	),


	'automated_processes' => array(
		'title' => _x('Automated decision-making', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('We make decisions on the basis of automated processing with respect to matters that may have (significant) consequences for individuals. These are decisions taken by computer programmes or systems without human intervention.', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('automated_processes' => 'yes'),
	),

	'automated_processes_details' => array(
		'content' => '[automated_processes_details]',
		'condition' => array('automated_processes' => 'yes'),
	),

	'complaints' => array(
		'p' => false,
		'title' => _x('Submitting a complaint', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x("If you are not satisfied with the way in which we handle (a complaint about) the processing of your personal data, you have the right to submit a complaint to the Information Regulator South Africa:", 'Legal document privacy statement', 'complianz-gdpr'),
	),

	'complaints-address' => array(
		'p' => false,
		'content' => 	'<br />P.O Box 31533,
						<br />Braamfontein,
						<br />Johannesburg,
						<br />2017
						<br />'. _x('Complaints email:', 'Legal document privacy statement', 'complianz-gdpr') . 'complaints.IR@justice.gov.za',
	),

	array(
		'title' => _x('Children', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => _x('Our website is not designed to attract children and it is not our intent to collect personal data from children under the age of consent in their country of residence. We therefore request that children under the age of consent do not submit any personal data to us.', 'Legal document privacy statement', 'complianz-gdpr'),
		'condition' => array('targets-children' => 'no'),
	),

	array(
		'title' => _x('Children', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => cmplz_sprintf(_x("For our privacy statement regarding children, please see our dedicated %sChildren's Privacy Statement%s", 'Legal document privacy statement', 'complianz-gdpr'), '<a href="[privacy-statement-children-url]">', '</a>'),
		'condition' => array('targets-children' => 'yes'),
	),

	'contact-details' => array(
		'title' => _x('Contact details', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'content' => '[organisation_name]<br />
        [address_company]<br />
        [country_company]<br />
        ' . _x('Website:', 'Legal document privacy statement', 'complianz-gdpr') . ' [domain] <br />
        ' . _x('Email:', 'Legal document privacy statement', 'complianz-gdpr') . ' [email_company] <br />
        [telephone_company]',
	),


	array(
		'title' => _x('Data Requests', 'Legal document', 'complianz-gdpr'),
		'content' => _x("For the most frequently submitted requests, we also offer you the possibility to use our data request form",'Legal document', 'complianz-gdpr').'<br />[cmplz-data-request region="za"]',
		'condition' => array('datarequest' => 'yes'),
	),

	array(
		'content' => _x('We have appointed a contact person for the organization’s policies and practices and to whom complaints or inquiries can be forwarded:', 'Legal document privacy statement', 'complianz-gdpr').
		             '<br />[ca_name_accountable_person]'.
		             '<br />[ca_address_accountable_person]',
		),

	/* Dit zijn de privacy policies die door wp worden aangeboden per plugin */
	'custom_privacy_policy_text' => array(
		'p' => false,
		'title' => _x('Annex', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
		'numbering' => false,
		'content' => '[custom_privacy_policy_text]',
		'callback_condition' => 'cmplz_has_custom_privacy_policy',
	),

// End privacy statement array
);

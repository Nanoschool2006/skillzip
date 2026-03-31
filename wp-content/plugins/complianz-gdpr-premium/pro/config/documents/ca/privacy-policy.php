<?php
/*
 * This document is intentionally not translatable, as it is intended to be for US citizens, and should therefore always be in English
 *
 * */
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['ca']['privacy-statement']['document_elements'] = array(
    'last-updated' => array(
        'content' => '<i>' . cmplz_sprintf(_x('This privacy statement was last changed on %s, last checked on %s, and applies to citizens and legal permanent residents of Canada.', 'Legal document privacy statement', 'complianz-gdpr'), '[publish_date]', '[checked_date]') . '</i><br /><br />',
    ),
    'inleiding' => array(
      'p' => false,
        'content' =>
            '<p>'. cmplz_sprintf(_x('In this privacy statement, we explain what we do with the data we obtain about you via %s. We recommend you carefully read this statement. In our processing we comply with the requirements of privacy legislation. That means, among other things, that:', 'Legal document privacy statement', 'complianz-gdpr'), '[domain]') .
            '<ul>
                <li>'._x('we clearly state the purposes for which we process personal data. We do this by means of this privacy statement;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we aim to limit our collection of personal data to only the personal data required for legitimate purposes;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we first request your explicit consent to process your personal data in cases requiring your consent;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we take appropriate security measures to protect your personal data and also require this from parties that process personal data on our behalf;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we respect your right to access your personal data or have it corrected or deleted, at your request.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
            </ul></p>' .
            _x('If you have any questions, or want to know exactly what data we keep of you, please contact us.', 'Legal document privacy statement', 'complianz-gdpr'),
    ),

    //In the privacy-policy page the first paragraph containing purpose and data retention period is generated in the dynamic documents file
    array(
        'title' => __('Sharing with other parties','complianz-gdpr'),
        'content' => _x('We only share or disclose this data to other recipients for the following purposes:','Legal document', 'complianz-gdpr'),
        'condition' => array('share_data_other' => 'NOT 2'),
    ),

    array(
        'numbering' => false,
        'content' =>
            '<b>'._x('Purpose of the data transfer:', 'Legal document privacy statement', 'complianz-gdpr').'</b>&nbsp;[purpose]<br />
             <b>'._x('Country or state in which this service provider is located:', 'Legal document privacy statement', 'complianz-gdpr').'</b>&nbsp;[country]<br />',
        'condition' => array(
            'processor' => 'loop',
            'share_data_other' => 'NOT 2',
        ),
    ),

	array(
		'numbering' => false,
		'content' =>
			"<b>" . _x('Purpose of the data transfer:', 'complianz-gdpr') . "</b>&nbsp;[purpose]<br />" .
			"<b>" . _x('Country or state in which this third party is located:', 'complianz-gdpr'). "</b>&nbsp;[country]<br />",
		'condition' => array(
			'thirdparty' => 'loop',
			'share_data_other' => '1',
		),
	),

	array(
		'title' => _x('Disclosure practices', 'Legal document', 'complianz-gdpr'),
		'content' =>
			'<p>' . _x('We disclose personal information if we are required by law or by a court order, in response to a law enforcement agency, to the extent permitted under other provisions of law, to provide information, or for an investigation on a matter related to public safety.', 'Legal document', 'complianz-gdpr') . "</p>" .
			'<p>' . _x('If our website or organisation is taken over, sold, or involved in a merger or acquisition, your details may be disclosed to our advisers and any prospective purchasers and will be passed on to the new owners.', 'Legal document', 'complianz-gdpr') . "</p>",
	),

    array(
        'title' => _x('How we respond to Do Not Track signals & Global Privacy Control ', 'privacy statement', 'complianz-gdpr'),
        'content' => _x('Our website responds to and supports the Do Not Track (DNT) header request field. If you turn DNT on in your browser, those preferences are communicated to us in the HTTP request header, and we will not track your browsing behavior.', 'Legal document privacy statement', 'complianz-gdpr'),
        'condition' => array('respect_dnt' => 'yes'),
    ),

    array(
        'title' => _x('How we respond to Do Not Track signals & Global Privacy Control ', 'privacy statement', 'complianz-gdpr'),
        'content' => _x('Our website does not respond to and does not support the Do Not Track (DNT) header request field.', 'Legal document privacy statement', 'complianz-gdpr'),
        'condition' => array('respect_dnt' => 'no'),
    ),

    array(
        'title' => _x('Cookies', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => cmplz_sprintf(_x('Our website uses cookies. For more information about cookies, please refer to our Cookie Policy on our %s[cookie-statement-title]%s webpage.', 'Legal document privacy statement', 'complianz-gdpr'), '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
    ),

    array(
        'content' => _x('We have concluded a data Processing Agreement with Google.', 'Legal document privacy statement', 'complianz-gdpr'),
        'callback_condition' => 'cmplz_accepted_processing_agreement',
    ),

    array(
        'content' => _x('Google may not use the data for any other Google services.', 'Legal document privacy statement', 'complianz-gdpr'),
        'callback_condition' => 'cmplz_statistics_no_sharing_allowed',
    ),

    array(
        'content' => _x('The inclusion of full IP addresses is blocked by us.', 'Legal document privacy statement', 'complianz-gdpr'),
        'callback_condition' => 'cmplz_no_ip_addresses',
    ),

    array(
        'title' => _x('Security', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => _x('We are committed to the security of personal data. We take appropriate security measures to limit abuse of and unauthorized access to personal data. This ensures that only the necessary persons have access to your data, that access to the data is protected, and that our security measures are regularly reviewed.', 'Legal document privacy statement', 'complianz-gdpr')
    ),
    array(
        'content' => _x('The security measures we use consist of:', 'Legal document privacy statement', 'complianz-gdpr'),
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    array(
        'content' => '[which_personal_data_secure]',
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    array(
        'title' => _x('Third party websites', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => _x('This privacy statement does not apply to third party websites connected by links on our website. We cannot guarantee that these third parties handle your personal data in a reliable or secure manner. We recommend you read the privacy statements of these websites prior to making use of these websites.', 'Legal document privacy statement', 'complianz-gdpr'),
    ),
    array(
        'title' => _x('Amendments to this privacy statement', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => _x('We reserve the right to make amendments to this privacy statement. It is recommended that you consult this privacy statement regularly in order to be aware of any changes. In addition, we will actively inform you wherever possible.', 'Legal document privacy statement', 'complianz-gdpr'),
    ),
    array(
        'title' => _x('Accessing and modifying your data', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => _x('If you have any questions or want to know which personal data we have about you, please contact us. Please make sure to always clearly state who you are, so that we can be certain that we do not modify or delete any data of the wrong person. We shall provide the requested information only upon receipt of a verifiable consumer request. You can contact us by using the information below.', 'Legal document privacy statement', 'complianz-gdpr'),
    ),

    array(
        'p' => false,
        'subtitle' => _x('You have the following rights with respect to your personal data', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => '<ol class="alphabetic">
                        <li>'._x('You may submit a request for access to the data we process about you.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('You may request an overview, in a commonly used format, of the data we process about you.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('You may request correction or deletion of the data if it is incorrect or not or no longer relevant. Where appropriate, the amended information shall be transmitted to third parties having access to the information in question.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('You have the right to withdraw consent at any time, subject to legal or contractual restrictions and reasonable notice. You will be informed of the implications of such withdrawal.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('You have the right to address a challenge concerning non-compliance with PIPEDA to our organization and, if the issue is not resolved, to the Office of the Privacy Commissioner of Canada.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('We shall give access to personal information in an alternative format to an individual with a sensory disability who has a right of access to personal information under PIPEDA and who requests that it be transmitted in the alternative format if (a) a version of the information already exists in that format; or (b) its conversion into that format is reasonable and necessary in order for the individual to be able to exercise rights.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                      </ol>',
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

    array(
        'title' => _x('Contact details', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'content' => '[organisation_name]<br />
        [address_company]<br />
        [country_company]<br />
        '._x('Website:', 'Legal document privacy statement', 'complianz-gdpr').' [domain] <br />
        '._x('Email:', 'Legal document privacy statement', 'complianz-gdpr').' [email_company] <br />
        [free_phonenr]<br />
        [telephone_company]',
    ),


	array(
		'title' => _x('Data Requests', 'Legal document', 'complianz-gdpr'),
		'content' => _x("For the most frequently submitted requests, we also offer you the possibility to use our data request form",'Legal document', 'complianz-gdpr').'<br />[cmplz-data-request region="ca"]',
		'condition' => array('datarequest' => 'yes'),
	),

    array(
	    'content' => _x('We have appointed a contact person for the organization’s policies and practices and to whom complaints or inquiries can be forwarded:', 'Legal document privacy statement', 'complianz-gdpr') .
                     '<br />[ca_name_accountable_person]'.
                     '<br />[ca_address_accountable_person]',
),

    array(
        'title' => _x('Annex', 'Legal document privacy statement:paragraph title', 'complianz-gdpr'),
        'numbering' => false,
        'content' => '[custom_privacy_policy_text]',
        'callback_condition' => 'cmplz_has_custom_privacy_policy',
    ),
);

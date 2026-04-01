<?php
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['uk']['privacy-statement']['document_elements'] = array(
    'last-updated' => array(
        'content' =>   '<i>' . cmplz_sprintf('This privacy statement was last updated on %s and applies to citizens and legal permanent residents of the United Kingdom.', '[publish_date]') . '</i><br><br>',
    ),
    'inleiding' => array(
        'p' => false,
        'content' =>
            '<p>'. cmplz_sprintf('In this privacy statement, we explain what we do with the data we obtain about you via %s. We recommend you carefully read this statement. In our processing we comply with the requirements of privacy legislation. That means, among other things, that:', '[domain]') .'</p>'.
            '<ul>
                <li>' . 'we clearly state the purposes for which we process personal data. We do this by means of this privacy statement;' . '</li>
                <li>' . 'we aim to limit our collection of personal data to only the personal data required for legitimate purposes;' . '</li>
                <li>' . 'we first request your explicit consent to process your personal data in cases requiring your consent;' . '</li>
                <li>' . 'we take appropriate security measures to protect your personal data and also require this from parties that process personal data on our behalf;' . '</li>
                <li>' . 'we respect your right to access your personal data or have it corrected or deleted, at your request.' . '</li>
            </ul>' .
            '<p>'.'If you have any questions, or want to know exactly what data we keep of you, please contact us.'.'</p>',
    ),

    //In the privacy-policy page the first paragraph containing purpose and data retention period is generated in the class-config.php
    'third-party-sharing' => array(
        'title' => 'Sharing with other parties',
        'content' => "We only share this data with processors and with other third parties for which consent must be obtained.",
        'condition' => array('share_data_other' => '1'),
    ),

    //this has to be above the processors, as processors are shown for both 1 and 3. It's condition is then "Not 2"
    'no-sharing-limited' => array(
        'title' => 'Sharing with other parties',
        'content' => 'We only share or disclose this data to processors for the following purposes:',
        'condition' => array('share_data_other' => '3'),
    ),

    'no-sharing-limited-sub' => array(
        'numbering' => false,
        'subtitle' => 'Processors',
		'condition' => array(
			'processor' => 'NOT EMPTY',
			'share_data_other' => 'NOT 2',
		),
    ),

    'processor' => array(
        'numbering' => false,
        'content' =>
            "<b>" . "Name:" . "</b>&nbsp;[name]<br>
                <b>" . "Country:" . "</b>&nbsp;[country]<br>
                <b>" . "Purpose:" . "</b>&nbsp;[purpose]<br>",
        'condition' => array(
            'processor' => 'loop',
            'share_data_other' => 'NOT 2',
        ),
    ),
    'no-sharing-limited-sub2' => array(
        'numbering' => false,
        'subtitle' => 'Third parties',
		'condition' => array(
			'thirdparty' => 'NOT EMPTY',
			'share_data_other' => '1',
		),
    ),

    array(
        'numbering' => false,
        'content' =>
            "<b>" . "Name:" . "</b>&nbsp;[name]<br>
            <b>" . "Country:" . "</b>&nbsp;[country]<br>
            <b>" . "Purpose:" . "</b>&nbsp;[purpose]<br>
            <b>" . "Data:" . "</b>&nbsp;[data]",
        'condition' => array(
            'thirdparty' => 'loop',
            'share_data_other' => '1',
        ),
    ),

    array(
        'title' => 'Sharing with other parties',
        'content' => 'We do not share your data with third parties.',
        'condition' => array('share_data_other' => '2'),
        'callback_condition' => 'NOT cmplz_site_shares_data', //even though someone may have entered he doesn't share data, it may be possible data is shared with vendors.
    ),

    array(
        'title' => 'Cookies',
        'content' => cmplz_sprintf('Our website uses cookies. For more information about cookies, please refer to our %sCookie Policy%s.', '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
        'condition' => array(
	        'uses_ad_cookies_personalized' => 'NOT tcf',
        ),
    ),

    array(
	    'title' => 'Cookies',
	    'content' => cmplz_sprintf('To provide the best experiences, we and our partners use technologies like cookies to store and/or access device information. Consenting to these technologies will allow us and our partners to process personal data such as browsing behaviour or unique IDs on this site. Not consenting or withdrawing consent, may adversely affect certain features and functions. For more information about these technologies and partners, please refer to our %sCookie Policy%s.', '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
	    'condition' => array(
		    'uses_ad_cookies_personalized' => 'tcf',
	    ),
    ),

	array(
		'title' => 'Disclosure practices',
		'content' =>
			'<p>' . 'We disclose personal information if we are required by law or by a court order, in response to a law enforcement agency, to the extent permitted under other provisions of law, to provide information, or for an investigation on a matter related to public safety.' . "</p>" .
			'<p>' . 'If our website or organisation is taken over, sold, or involved in a merger or acquisition, your details may be disclosed to our advisers and any prospective purchasers and will be passed on to the new owners.' . "</p>",
	),

	array(
		'content' => cmplz_sprintf('%s participates in the IAB Europe Transparency & Consent Framework and complies with its Specifications and Policies. It uses the Consent Management Platform with the identification number %s.', '[organisation_name]', '332')."&nbsp;",
		'condition' => array(
			'uses_ad_cookies_personalized' => 'tcf',
		),
	),

    'statistics-google' => array(
        'content' => 'We have concluded a data processing agreement with Google.',
        'callback_condition' => 'cmplz_accepted_processing_agreement',
    ),

    'statistics-no-sharing' => array(
        'content' => 'Google may not use the data for any other Google services.',
        'callback_condition' => 'cmplz_statistics_no_sharing_allowed',
    ),

    'statistics-no-ip' => array(
        'content' => 'The inclusion of full IP addresses is blocked by us.',
        'callback_condition' => 'cmplz_no_ip_addresses',
    ),

    'security' => array(
        'title' => 'Security',
        'content' => 'We are committed to the security of personal data. We take appropriate security measures to limit abuse of and unauthorised access to personal data. This ensures that only the necessary persons have access to your data, that access to the data is protected, and that our security measures are regularly reviewed.'
    ),
    'security_which' => array(
        'content' => 'The security measures we use consist of:',
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    'security_which_content' => array(
        'content' => '[which_personal_data_secure]',
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    'third-party-website' => array(
        'title' => 'Third-party websites',
        'content' => 'This privacy statement does not apply to third-party websites connected by links on our website. We cannot guarantee that these third parties handle your personal data in a reliable or secure manner. We recommend you read the privacy statements of these websites prior to making use of these websites.',
    ),
    'changes-privacy-statement' => array(
        'title' => 'Amendments to this privacy statement',
        'content' => 'We reserve the right to make amendments to this privacy statement. It is recommended that you consult this privacy statement regularly in order to be aware of any changes. In addition, we will actively inform you wherever possible.',
    ),
    'insight-changes-your-data' => array(
        'title' => 'Accessing and modifying your data',
        'p'=>false,
        'content' =>
            '<p>'.'If you have any questions or want to know which personal data we have about you, please contact us. You can contact us by using the information below. You have the following rights:' .'</p>'.
            '<ul>
                <li>' . 'You have the right to know why your personal data is needed, what will happen to it, and how long it will be retained for.' . '</li>
                <li>' . 'Right of access: You have the right to access your personal data that is known to us.' . '</li>
                <li>' . 'Right to rectification: you have the right to supplement, correct, have deleted or blocked your personal data whenever you wish.' . '</li>
                <li>' . 'If you give us your consent to process your data, you have the right to revoke that consent and to have your personal data deleted.' . '</li>
                <li>' . 'Right to transfer your data: you have the right to request all your personal data from the controller and transfer it in its entirety to another controller.' . '</li>
                <li>' . 'Right to object: you may object to the processing of your data. We comply with this, unless there are justified grounds for processing.' . '</li>
            </ul>' .
            '<p>'.'Please make sure to always clearly state who you are, so that we can be certain that we do not modify or delete any data of the wrong person.'.'</p>',
    ),

    'automated_processes' => array(
        'title' => 'Automated decision-making',
        'content' => 'We make decisions on the basis of automated processing with respect to matters that may have (significant) consequences for individuals. These are decisions taken by computer programmes or systems without human intervention.',
        'condition' => array('automated_processes' => 'yes'),
    ),

    'automated_processes_details' => array(
        'content' => '[automated_processes_details]',
        'condition' => array('automated_processes' => 'yes'),
    ),

    'complaints' => array(
        'title' => 'Submitting a complaint',
        'content' => "If you are not satisfied with the way in which we handle (a complaint about) the processing of your personal data, you have the right to submit a complaint to the Information Commissioner's Office:",
    ),

    'complaints-address' => array(
        'content' => '<br>Wycliffe House<br>
                        Water Lane<br>
                        Wilmslow<br>
                        Cheshire<br>
                        SK9 5AF',
    ),
    'complaints-address-jersey' => array(
	    'condition' => array('uk_consent_regions' => 'yes'),
	    'content' =>   '<br>Jersey Office of The Information Commissioner<br>
                        2nd Floor 5 Castle Street<br>
                        St. Helier<br>
                        Jersey<br>
                        JE2 3BT',
    ),

    'complaints-address-guernsey' => array(
	    'condition' => array('uk_consent_regions' => 'yes'),
	    'content' =>   '<br>St Martin’s House<br>
                        Le Bordage<br>
                        St. Peter Port<br>
                        Guernsey<br>
                        GY1 1BR',
    ),

    'data-protection-officer' => array(
        'title' => 'Data Protection Officer',
        'content' => cmplz_sprintf("Our Data Protection Officer has been registered with the Information Commissioner's Office. If you have any questions or requests with respect to this privacy statement or for the Data Protection Officer, you may contact %s, %s or via %s.", '[name_uk_dpo]', '[website_uk_dpo]', '[email_uk_dpo]'),
        'condition' => array('dpo_or_gdpr' => 'dpo_uk'),
    ),

    array(
            'title' => 'Children',
            'content' => 'Our website is not designed to attract children and it is not our intent to collect personal data from children under the age of consent in their country of residence. We therefore request that children under the age of consent do not submit any personal data to us.',
            'condition' => array('targets-children' => 'no'),
        ),

    array(
        'title' => 'Children',
        'content' => cmplz_sprintf("For our privacy statement regarding children, please see our dedicated %sChildren's Privacy Statement%s", '<a href="[privacy-statement-children-url]">', '</a>'),
        'condition' => array('targets-children' => 'yes'),
    ),

    'contact-details' => array(
        'title' => 'Contact details',
        'content' => '[organisation_name]<br>
        [address_company]<br>
        [country_company]<br>
        ' . 'Website: [domain] <br>
        ' . 'Email: [email_company] <br>
        [telephone_company]',
    ),

	array(
		'title' => 'Data Requests',
		'content' => 'For the most frequently submitted requests, we also offer you the possibility to use our data request form<br>[cmplz-data-request region="uk"]',
		'condition' => array('datarequest' => 'yes'),
	),

	array(
        'content' => cmplz_sprintf('We have appointed a representative within the United Kingdom. If you have any questions or requests with respect to this privacy statement or for our representative, you may contact %s, via %s, %s or by telephone on %s.', '[name_uk_gdpr]', '[email_uk_gdpr]', '[website_uk_gdpr]', '[phone_uk_gdpr]'),
        'condition' => array('dpo_or_gdpr' => 'uk_gdpr_rep'),
    ),
    /* Dit zijn de privacy policies die door wp worden aangeboden per plugin */
    'custom_privacy_policy_text' => array(
        'title' => 'Annex',
        'numbering' => false,
        'content' => '[custom_privacy_policy_text]',
        'callback_condition' => 'cmplz_has_custom_privacy_policy',
    ),

// End privacy statement array
);

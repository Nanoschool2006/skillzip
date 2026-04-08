<?php
/*
 * This document is intentionally not translatable, as it is intended to be for AU citizens, and should therefore always be in English
 *
 * This document is based on the privacy statement for the US
 *
 * */
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['au']['privacy-statement']['document_elements'] = array(
    'last-updated' => array(
        'content' => '<i>' . cmplz_sprintf('This privacy statement was last changed on %s, last checked on %s, and applies to citizens of Australia.', '[publish_date]', '[checked_date]') . '</i><br /><br />',
    ),
    'inleiding' => array(
      'p' => false,
        'content' =>
            '<p>'. cmplz_sprintf('In this privacy statement, we explain what we do with the data we obtain about you via %s. We recommend you carefully read this statement. In our processing we comply with the requirements of privacy legislation. That means, among other things, that:', '[domain]') .
            '<ul>
                <li>we clearly state the purposes for which we process personal data. We do this by means of this privacy statement;</li>
                <li>we aim to limit our collection of personal data to only the personal data required for legitimate purposes;</li>
                <li>we first request your explicit consent to process your personal data in cases requiring your consent;</li>
                <li>we take appropriate security measures to protect your personal data and also require this from parties that process personal data on our behalf;</li>
                <li>we respect your right to access your personal data or have it corrected or deleted, at your request.</li>
            </ul></p>' .
            'If you have any questions, or want to know exactly what data we keep of you, please contact us.',
    ),

    //In the privacy-policy page the first paragraph containing purpose and data retention period is generated in the dynamic documents file

    array(
        'subtitle' => 'We also collect or may have collected personal information that we buy or receive from a third party. This is done under the following circumstances',
        'content' => '[share_data_bought_or_received_description]',
        'condition' => array( 'share_data_bought_or_received' => '1' ),
    ),

    'sharing-other-parties'=>array(
        'title' => 'Sharing with other parties',
        'content' => 'We only share or disclose this data to other recipients for the following purposes:',
        'condition' => array('share_data_other' => 'NOT 2'),
    ),

    array(
        'numbering' => false,
        'content' =>
            "<b>Purpose of the data transfer:</b>&nbsp;[purpose]<br />
             <b>Country or state in which this processor is located:</b>&nbsp;[country]<br />",
        'condition' => array(
            'processor' => 'loop',
            'share_data_other' => 'NOT 2',
        ),
    ),

    array(
        'numbering' => false,
        'content' =>
            "<b>Purpose of the data transfer:</b>&nbsp;[purpose]<br />
             <b>Country or state in which this third party is located:</b>&nbsp;[country]<br />",
        'condition' => array(
            'thirdparty' => 'loop',
            'share_data_other' => '1',
        ),
    ),

    array(
        'title' => 'Sharing with other parties',
        'content' => 'We do not share data with third parties.',
        'condition' => array('share_data_other' => '2'),
    ),

	array(
		'title' => 'Disclosure practices',
		'content' =>
			'<p>' . 'We disclose personal information if we are required by law or by a court order, in response to a law enforcement agency, to the extent permitted under other provisions of law, to provide information, or for an investigation on a matter related to public safety.'. "</p>" .
			'<p>' . 'If our website or organisation is taken over, sold, or involved in a merger or acquisition, your details may be disclosed to our advisers and any prospective purchasers and will be passed on to the new owners.' . "</p>",
	),


    array(
        'title' => "What if you don't provide us with your personal information?",
        'content' => "If you don't provide us with your personal information, we may not be able to provide you with the information, products or assistance that you are seeking.",
    ),


    array(
        'title' => 'How we respond to Do Not Track signals & Global Privacy Control ',
        'content' => 'Our website responds to and supports the Do Not Track (DNT) header request field. If you turn DNT on in your browser, those preferences are communicated to us in the HTTP request header, and we will not track your browsing behavior.',
        'condition' => array('respect_dnt' => 'yes'),
    ),


    array(
        'title' => 'How we respond to Do Not Track signals & Global Privacy Control ',
        'content' => 'Our website does not respond to and does not support the Do Not Track (DNT) header request field.',
        'condition' => array('respect_dnt' => 'no'),
    ),


    array(
        'title' => 'Cookies',
        'content' => cmplz_sprintf('Our website uses cookies. For more information about cookies, please refer to our Cookie Policy on our %s[cookie-statement-title]%s webpage.', '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
    ),

    array(
        'content' => 'We have concluded a data processing agreement with Google.',
        'callback_condition' => 'cmplz_accepted_processing_agreement',
    ),

    array(
        'content' => 'Google may not use the data for any other Google services.',
        'callback_condition' => 'cmplz_statistics_no_sharing_allowed',
    ),

    array(
        'content' => 'The inclusion of full IP addresses is blocked by us.',
        'callback_condition' => 'cmplz_no_ip_addresses',
    ),

    array(
        'title' => 'Security',
        'content' => 'We are committed to the security of personal data. We take appropriate security measures to limit abuse of and unauthorized access to personal data. This ensures that only the necessary persons have access to your data, that access to the data is protected, and that our security measures are regularly reviewed.'
    ),
    array(
        'content' => 'The security measures we use consist of:',
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    array(
        'content' => '[which_personal_data_secure]',
        'condition' => array('secure_personal_data' => 'NOT 1'),
    ),
    array(
        'title' => 'Third party websites',
        'content' => 'This privacy statement does not apply to third party websites connected by links on our website. We cannot guarantee that these third parties handle your personal data in a reliable or secure manner. We recommend you read the privacy statements of these websites prior to making use of these websites.',
    ),
    array(
        'title' => 'Amendments to this privacy statement',
        'content' => 'We reserve the right to make amendments to this privacy statement. It is recommended that you consult this privacy statement regularly in order to be aware of any changes. In addition, we will actively inform you wherever possible.',
    ),
    array(
        'title' => 'Accessing and modifying your data',
        'content' => 'If you have any questions or want to know which personal data we have about you, please contact us. Please make sure to always clearly state who you are, so that we can be certain that we do not modify or delete any data of the wrong person. We shall provide the requested information only upon receipt of a verifiable consumer request. You can contact us by using the information below. You have the following rights:',
    ),
    array(
        'p' => false,
        'subtitle' => 'You have the following rights with respect to your personal data',
        'content' => '<ol class="alphabetic">
                        <li>You may submit a request for access to the data we process about you.</li>
                        <li>You may request an overview, in a commonly used format, of the data we process about you.</li>
                        <li>You may request correction or deletion of the data if it is incorrect or not or no longer relevant for any purpose under the Privacy Act.</li>
                      </ol>
                      ',
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

    array(
        'title' => 'Contact details',
        'content' => '[organisation_name]<br />
        [address_company]<br />
        [country_company]<br />
        '._x('Website:', 'Legal document privacy statement', 'complianz-gdpr').' [domain] <br />
        '._x('Email:', 'Legal document privacy statement', 'complianz-gdpr').' [email_company] <br />
        [free_phonenr]<br />
        [telephone_company]',
    ),
	array(
		'title' => 'Data Requests',
		'content' => "For the most frequently submitted requests, we also offer you the possibility to use our data request form".'<br />[cmplz-data-request region="au"]',
		'condition' => array('datarequest' => 'yes'),
	),
    array(
        'content' => 'We have appointed a contact person for the organization’s policies and practices and to whom complaints or inquiries can be forwarded:'.
                     '<br />[ca_name_accountable_person]'.
                     '<br />[ca_address_accountable_person]',
    ),

    /* Dit zijn de privacy policies die door wp worden aangeboden per plugin */
    array(
        'title' => 'Annex',
        'numbering' => false,
        'content' => '[custom_privacy_policy_text]',
        'callback_condition' => 'cmplz_has_custom_privacy_policy',
    ),

// End privacy statement array
);

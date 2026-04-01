<?php
/*
 * This document is intentionally not translatable, as it is intended to be for US citizens, and should therefore always be in English
 *
 * */
defined('ABSPATH') or die("you do not have access to this page!");

$this->pages['ca']['privacy-statement-children']['document_elements'] = array(
    'last-updated' => array(
        'content' => '<i>' . cmplz_sprintf(_x('This Privacy Statement was last changed on %s, was last checked on %s and applies to citizens and legal permanent residents of Canada.', 'Legal document privacy statement', 'complianz-gdpr'), '[publish_date]', '[checked_date]') . '</i><br /><br />',
    ),

    'inleiding' => array(
      'p' => false,
        'content' =>
            '<p>'. cmplz_sprintf(_x('In this Privacy Statement, we explain what we do with the data we obtain about children via %s. We recommend you carefully read this statement. In our processing we comply with the requirements of Canadian privacy legislation. That means, among other things, that:', 'Legal document privacy statement', 'complianz-gdpr'), '[domain]') .'</p>'.
            '<ul>
                <li>'._x('we clearly state the purposes for which we process personal data. We do this by means of this Privacy Statement;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we aim to limit our collection of personal data to only the personal data required for legitimate purposes;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we first request consent from parents to process the personal data in cases requiring parental consent;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we take appropriate security measures to protect the personal data of children and also require this from parties that process personal data on our behalf;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                <li>'._x('we respect the right to access children’s personal data or have it corrected or deleted, at the request of a parent or guardian.', 'Legal document privacy statement', 'complianz-gdpr').'</li>
            </ul>' .
            '<p>'._x('If you have any questions, or want to know exactly what data we keep of you or your child, please contact us.', 'Legal document privacy statement', 'complianz-gdpr').'</p>',
    ),



    array(
        'title' => _x('Purposes', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('We use the personal data from children for one or more of the following purposes:', 'Legal document privacy statement', 'complianz-gdpr').'[children-what-purposes]',
    ),
    array(
        'title' => _x('Registration', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('Sometimes children need to register on our website in order to play games or to view content. For this purpose we use the following data: ', 'Legal document privacy statement', 'complianz-gdpr').'[children-what-information-registration]',
        'condition' => array('children-what-purposes' => 'registration')
    ),
    array(
        'title' => _x('Content created by a child and publicly shared', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('Sometimes children are creating content on our website, and sometimes personal information is inserted by the child in the created content. Where possible we try to delete that personal information or ask verifiable consent for the parents or guardians.', 'Legal document privacy statement', 'complianz-gdpr').'<br /><br />'.
                     _x('We will also ask for consent when we plan to post content publicly. For this purpose we might use the following data: ', 'Legal document privacy statement', 'complianz-gdpr').'[children-what-information-content]',
        'condition' => array('children-what-purposes' => 'content-created-by-child')
    ),
    array(
        'title' => _x('Chat/messageboard', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('There are games or activities that allow children to communicate with each other through a chatsystem or a messageboard. To protect children we employ filters , and recommend that parents supervise their children.', 'Legal document privacy statement', 'complianz-gdpr').'<br /><br />'.
                     _x('For this purpose we use the following data: ', 'Legal document privacy statement', 'complianz-gdpr').'[children-what-information-chat]',
        'condition' => array('children-what-purposes' => 'chat')
    ),

    array(
        'title' => _x('Email contact', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('Sometimes it is necessary that we ask for an email address. We will do this in order to respond to a request or question from a child.', 'Legal document', 'complianz-gdpr').'[children-what-information-email]',
        'condition' => array('children-what-purposes' => 'email')
    ),

    array(
        'title' => _x('Verifiable Parental Consent', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('We search consent from a parent or guardian if we wish to collect personal data from a child. We use the following method(s):', 'Legal document privacy statement', 'complianz-gdpr').'[children-parent-consent-type]',
    ),

    array(
        'content' => _x('Parents and guardians can refuse their consent, and can request that we delete any personal information we might have already collected. This might also mean that an account or membership will be terminated.', 'Legal document privacy statement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('When verifiable parental consent is not required', 'Legal document privacy statement', 'complianz-gdpr'),
        'p' => false,
        'content' => '<p>'._x('Verifiable parental consent is not required in the case of:', 'Legal document privacy statement', 'complianz-gdpr').'</p>'.
                     '<ol class="alphabetic">
                        <li>'._x('online contact information collected from a child that is used only to respond directly on a one-time basis to a specific request from the child and is not used to recontact the child and is not maintained in retrievable form by the operator;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('a request for the name or online contact information of a parent or child that is used for the sole purpose of obtaining parental consent or providing notice and where such information is not maintained in retrievable form by the operator if parental consent is not obtained after a reasonable time;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                        <li>'._x('online contact information collected from a child that is used only to respond more than once directly to a specific request from the child and is not used to recontact the child beyond the scope of that request', 'Legal document privacy statement', 'complianz-gdpr').'
                            <ol>
                                <li>'._x('if, before any additional response after the initial response to the child, the operator uses reasonable efforts to provide a parent notice of the online contact information collected from the child, the purposes for which it is to be used, and an opportunity for the parent to request that the operator make no further use of the information and that it not be maintained in retrievable form;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                            </ol>
                        </li>
                        <li>'._x('the name of the child and online contact information (to the extent reasonably necessary to protect the safety of a child participant on the site)', 'Legal document privacy statement', 'complianz-gdpr').'
                            <ol>
                                <li>'._x('used only for the purpose of protecting such safety;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                                <li>'._x('not used to recontact the child or for any other purpose; and', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                                <li>'._x('not disclosed on the site,', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                             </ol>
                             '._x('if the operator uses reasonable efforts to provide a parent notice of the name and online contact information collected from the child, the purposes for which it is to be used, and an opportunity for the parent to request that the operator make no further use of the information and that it not be maintained in retrievable form; or', 'Legal document privacy statement', 'complianz-gdpr').'
                        </li>
                        <li>'._x('the collection, use, or dissemination of such information by the operator of such a website or online service necessary', 'Legal document privacy statement', 'complianz-gdpr').'
                            <ol>
                                <li>'._x('to protect the security or integrity of its website;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                                <li>'._x('to take precautions against liability;', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                                <li>'._x('to respond to judicial process; or', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                                <li>'._x('to the extent permitted under other provisions of law, to provide information to law enforcement agencies or for an investigation on a matter related to public safety', 'Legal document privacy statement', 'complianz-gdpr').'</li>
                             </ol>
                        </li>
                    </ol>'
    ),

    //In the privacy-policy page the first paragraph containing purpose and data retention period is generated in the dynamic documents file
    // Changed to Sharing with other parties by default in 5.1
    array(
        'title' => _x('Sharing with other parties', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('We only share this data with Service Providers and with the following categories of third-party persons or entities:', 'Legal document privacy statement', 'complianz-gdpr'),
        'condition' => array('share_data_other' => '1'),
    ),

    array(
        'title' => _x('Sharing with other parties', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('We only share or disclose this data to other recipients for the following purposes:', 'Legal document', 'complianz-gdpr'),
        'condition' => array('share_data_other' => '3'),
    ),

    array(
        'title' => _x('Sharing with other parties', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('We do not share data with third parties.', 'Legal document privacy statement', 'complianz-gdpr'),
        'condition' => array('share_data_other' => '2'),
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
            "<b>".__('Purpose of the data transfer:', 'complianz-gdpr')."</b>&nbsp;[purpose]<br />
             <b>".__('Country or state in which this third party is located:','complianz-gdpr')."</b>&nbsp;[country]<br />",
        'condition' => array(
            'thirdparty' => 'loop',
            'share_data_other' => '1',
        ),
    ),

    // Changed to Disclosure practices in 5.1
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
        'title' => _x('Cookies', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => cmplz_sprintf(_x('Our website uses cookies. For more information about cookies, please refer to our %sCookie Policy%s.', 'Legal document privacy statement', 'complianz-gdpr'), '<a href="[cookie-statement-url]">', '</a>')."&nbsp;",
    ),

    array(
        'title' => _x('Security', 'Legal document privacy statement', 'complianz-gdpr'),
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
        'title' => _x('Contact details', 'Legal document privacy statement', 'complianz-gdpr'),
        'content' => _x('Please contact us at the address below if you have any questions about this Children’s Privacy Statement or about our collection and use practices:', 'Legal document privacy statement', 'complianz-gdpr').
        '<br />
        [organisation_name]<br />
        [address_company]<br />
        [country_company]<br />
        '._x('Website:', 'Legal document privacy statement', 'complianz-gdpr').' [domain] <br />
        '._x('Email:', 'Legal document privacy statement', 'complianz-gdpr').' [email_company] <br />
        [free_phonenr] <br />
        [telephone_company]',
    ),



// End privacy statement array
);

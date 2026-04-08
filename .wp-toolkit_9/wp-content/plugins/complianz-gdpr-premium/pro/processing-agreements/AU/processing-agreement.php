<?php
defined('ABSPATH') or die("you do not have access to this page!");
$elements = array(
    array(
        'subtitle' => _x('The undersigned:', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('1. [organisation_name]', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'content' => _x('hereinafter referred to as: Controller', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'content' => '<b>'._x('and', 'Legal document processing agreement', 'complianz-gdpr').'</b>',
    ),
    array(
        'content' => '2. [name_of_processor-au]',
    ),
    array(
        'content' => _x('hereinafter referred to as: Processor', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'content' => _x('hereinafter jointly referred to as: Parties; ', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('Definitions', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The following terms used in this Data Processing Agreement shall have the meaning hereby assigned to them:', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Agreement', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The agreement between the Controller and the Processor.', 'Legal document processing agreement', 'complianz-gdpr')
    ),
    array(
        'subtitle' => _x('Data Processing Agreement', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('This agreement including its recitals and annexes.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Data Subject', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The person to whom Personal Data relates.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('Any personal information relating to an identified or identifiable natural person that the Processor processes on behalf of the Controller within the scope of the Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Processing', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('Any operation or any set of operations relating to Personal Data within the scope of the Agreement, carried out by means of automated processes or otherwise, such as collection, recording, organization, structuring, storage, adaptation or alteration, retrieval, consultation, use, disclosure by means of transmission, disseminating or otherwise making available, aligning or combining, restriction, erasure or destruction.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Regulation', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The major privacy protection laws at the State and federal level.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => _x('Security Breach', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('a breach of security that accidentally or unlawfully results in the destruction, loss, alteration or unauthorized disclosure or access to unencrypted personal data transmitted, stored or otherwise processed. This also includes encrypted personal information if the encryption key or security credential was, or is reasonably believed to have been, acquired by an unauthorized person and the person or business that owns or licenses the encrypted information has a reasonable belief that the encryption key or security credential could render that personal information readable or useable.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('Subject of this Data Processing Agreement ', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
    	'subtitle' => '',
        'content' => _x('This Data Processing Agreement regulates the Processing of Personal Data by the Processor within the scope of the Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The nature and the purpose of the Processing, the type of Personal Data, and the categories of Data Subjects are set out in Annex 1.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('Entry into force and duration', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('This Agreement shall enter into force on the date it is signed by the Parties.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('This Data Processing Agreement shall terminate after and insofar as the Processor has deleted or returned all Personal Data in accordance with Article 9.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Neither Party may terminate this Data Processing Agreement prematurely.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Parties may only amend this Agreement by mutual consent. Any amendment or modification of this Agreement or additional obligation assumed by either Party in connection with this Agreement will only be binding if evidenced in writing signed by each Party or an authorized representative of each Party.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(

        'title' => _x('Scope of Processing Authority of the Processor', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall process the Personal Data exclusively on the basis of written instructions from the Controller, except in the case of derogating statutory provisions applicable to the Processor.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'content' => _x('If, in the opinion of the Processor, an instruction as referred to in the first paragraph conflicts with a statutory regulation on data protection, it shall inform the Controller thereof prior to the Processing, unless a statutory regulation prohibits such notification.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('If the Processor is required to provide Personal Data on the basis of a statutory provision, it shall inform the Controller without delay and, if possible, prior to providing the data.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor is not allowed to do one of the following:', 'Legal document processing agreement', 'complianz-gdpr').'
                    <ol class="alphabetic">
                        <li>'._x('Selling the Personal Data.', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                        <li>'._x('Retaining, using, or disclosing the Personal Data for any purpose other than for the specific purpose of performing the services specified in the contract, including retaining, using, or disclosing the Personal Data for a commercial purpose other than providing the services specified in the contract.', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                        <li>'._x('Retaining, using, or disclosing the information outside of the direct business relationship between the Processor and the Controller.', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                    </ol>',
    ),
    array(
        'title' => _x('Security of the Processing', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor will endeavour to implement and maintain reasonable security procedures and practices appropriate to the nature of the information, to protect the Personal Information from unauthorized access, destruction, use, modification, or disclosure.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array(
            'security_measures-au' => 1,
        )
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor will endeavour to implement and maintain reasonable security procedures and practices appropriate to the nature of the information, to protect the Personal Information from unauthorized access, destruction, use, modification, or disclosure. To this end, the Processor will take the technical and organizational security measures as set out in a separate security protocol.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array(
            'security_measures-au' => 2,
        )
    ),
    array(
        'subtitle' => '',
        'content' => _x('The security protocol will be added to this Agreement as a separate annex and shall be available from the Processor upon request.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array(
            'security-protocol-where-au' => 1,
        )
    ),

    array(
        'subtitle' => '',
        'content' => cmplz_sprintf(_x('The security protocol can be viewed online %shere%s.', 'Legal document processing agreement', 'complianz-gdpr'), '[security-protocol-where-url-au]', '[/security-protocol-where-url-au]'),
        'condition' => array(
            'security-protocol-where-au' => 2,
        )
    ),

    array(
        'subtitle' => '',
        'content' => cmplz_sprintf(_x('The Processor will endeavour to implement and maintain reasonable security procedures and practices appropriate to the nature of the information, to protect the Personal Information from unauthorized access, destruction, use, modification, or disclosure. To this end, the Processor shall take the technical and organizational security measures as set out in %s.', 'Legal document processing agreement', 'complianz-gdpr'), '[annex-security-measures-au]'),
        'condition' => array(
            'security_measures' => 3,
        )
    ),

    array(
        'subtitle' => '',
        'content' => _x('Parties recognise that ensuring an appropriate level of security may require additional security measures to be implemented at any time. The Processor shall ensure a level of security appropriate to the risk. If and insofar as the Controller explicitly requests this in writing, the Processor shall implement additional measures with respect to the security of the Personal Data.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    'article-5-7' => array(
        'subtitle' => '',
        'content' => _x('The Processor shall not process Personal Data outside States and territories of Australia, unless explicit written consent to do so has been granted by the Controller and subject to derogating statutory obligations.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array(
            'allow-outside-au' => 1,
        )
    ),

    'article-5-8' => array(
        'content' => _x('The Processor may process the personal data in provinces and territories of States and territories of Australia. Transfer to countries outside States and territories of Australia is permitted, provided the relevant legal conditions have been met. Upon request, the Processor shall inform the Controller of the country or countries that is or are involved.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array(
            'allow-outside-au' => 2,
        )
    ),

    'article-6' => array(
        'title' => _x('Duty of Confidentiality of Personnel of the Processor', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    'article-6.1' => array(
	    'subtitle' => '',
	    'content' => _x('The Personal Data is of a confidential nature. The Processor is required to maintain the confidentiality of the information and is prohibited from disclosing or using the information other than to carry out the service that is subject of this Data Processing Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    'article-6-2' => array(
        'subtitle' => '',
        'content' => _x('At the request of the Controller, the Processor shall demonstrate that its Personnel have undertaken to observe confidentiality. The personal data will only be disclosed to those employees and/or third parties who must necessarily take cognisance of the Personal Data.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    'article-6-3' => array(
        'subtitle' => '',
        'content' => _x('This duty of confidentiality shall not apply where the Controller has given express consent to disclose the data to third parties, if disclosure of the data to third parties is logically necessary given the nature of the assignment and the performance of this Data Processing Agreement, or if there is a statutory obligation to disclose the data to a third party.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('Assistance on account of the rights of the Data Subject', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

	array(
		'subtitle' => '',
		'content' => _x('In the event a data subject submits a request to the Processor to exercise his/her legal rights, the Processor shall forward the request to the Controller, and the Controller shall further handle the request. The Processor may inform the data subject accordingly.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
    array(
	    'subtitle' => '',
	    'content' => _x("The Processor shall, to the extent within its power, provide reasonable assistance to the Controller in fulfilling the latter's obligation to respond to requests of the Data Subject to exercise its rights laid down in the Regulation.", 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
	    'subtitle' => '',
	    'content' => _x('The Processor may charge the reasonable additional costs it incurs in this respect to the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('deal_with_requests-au' => 2),
    ),

    array(
        'title' => _x('Security Breach', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Security Breach.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-informed-au' => 1),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Security Breach, but no later than within 24 hours after discovery.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-informed-au' => 2),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Security Breach, but no later than within 36 hours after discovery.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-informed-au' => 3),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Information that must at least be provided by the Processor shall include:', 'Legal document processing agreement', 'complianz-gdpr') .
            '<ul>
                        <li>'._x('The nature of the Personal Data Breach', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                        <li>'._x('The Personal Data and Data Subject', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                        <li>'._x('Likely consequences of the Security Breach', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                        <li>'._x('Measures proposed or implemented by the Processor to address the Security Breach, including, where appropriate, measures to mitigate its possible adverse effects.', 'Legal document processing agreement', 'complianz-gdpr').'</li>
                    </ul>',
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall also inform the Controller of further developments concerning the Security Breach after having reported the breach pursuant to the first paragraph.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Each party shall bear their own costs relating to the report to the Data Subject.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'title' => _x('Returning Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('After expiry of the Agreement, the Processor shall, at the discretion of the Controller, arrange for the return of all Personal Data to the Controller or for the erasure of all Personal Data. The Processor shall remove all copies, except where otherwise provided by law.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'title' => _x('Obligation to disclose information', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Processor shall provide all information necessary to demonstrate that the obligations arising from this Data Processing Agreement have been and are being fulfilled.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Controller shall have the right to conduct audits to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Processor, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('audit-au' => 2),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The Controller shall have the right to have audits carried out by an independent external expert, who is bound by confidentiality, to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Processor, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('audit-au' => 1),
    ),
    array(
        'subtitle' => '',
        'content' => _x("Such an audit is justified if the similar audit reports present at the Processor's are in-conclusive or insufficiently conclusive with respect to the Processor's compliance with this Data Processing Agreement. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks.", 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-audit-au' => 1),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Such an audit shall be justified in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-audit-au' => 2),
    ),
     array(
        'subtitle' => '',
        'content' => _x('Such an audit may be carried out once every three months, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-audit-au' => 3),
    ),
    array(
        'subtitle' => '',
        'content' => _x('Such an audit may be carried out once every calendar year, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('when-audit-au' => 4),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The findings in respect of the audit carried out shall be implemented by the Processor as soon as possible.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('what-do-with-findings-au' => 1),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The findings of the audit carried out will be assessed by the Parties in joint consultation and, depending on the assessment, implemented (or not) by either Party or jointly by both Parties.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('what-do-with-findings-au' => 2),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('audit-costs-au' => 1),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the Processor.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('audit-costs-au' => 2),
    ),
    array(
        'subtitle' => '',
        'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the Processor, in the event of non-trivial breaches of the obligations arising from the Data Processing Agreement. Otherwise, the costs shall be borne by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('audit-costs-au' => 3),
    ),


    array(
        'title' => _x('Other Terms and Conditions', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'subtitle' => '',
        'content' => _x('The Processor shall be liable towards the Controller for all consequences of the breach of this Data Processing Agreement, and shall indemnify the Controller against all claims by third parties, including any penalties, to the extent attributable to the Processor.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),
    array(
        'subtitle' => '',
        'content' => cmplz_sprintf(_x('The liability of the Processor shall never exceed %s per year. The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Processor or its management.', 'Legal document processing agreement', 'complianz-gdpr'), '[amount-liable-au]'),
        'condition' => array('maximize-liability-au' => 'yes'),
    ),
    array(
        'subtitle' => '',
        'content' => cmplz_sprintf(_x('During the Data Processing Agreement, the Processor shall have and continue to have adequate insurance cover in place for liability in accordance with this article. The insurance policy should at least cover %s', 'Legal document processing agreement', 'complianz-gdpr'), '[max_cost_of_insurance-au]'),
        'condition' => array('insurance-au' => 'yes')
    ),

    array(
        'subtitle' => '',
        'content' => cmplz_sprintf(_x('The insurance shall cover: %s', 'Legal document processing agreement', 'complianz-gdpr'), '[insurance_conditions-au]'),
        'condition' => array('insurance-au' => 'yes')

    ),
    array(
        'subtitle' => '',
        'content' => _x('The insurance conditions may be viewed upon request.', 'Legal document processing agreement', 'complianz-gdpr'),
        'condition' => array('access-to-policy-au' => 'yes')
    ),

    array(
        'content' => _x('In the event that any of the provisions of this Agreement are held to be invalid or unenforceable in whole or in part, all other provisions will nevertheless continue to be valid and enforceable with the invalid or unenforceable parts severed from the remainder of this Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    /*signature*/
    array(
        'numbering' => false,
        'content' => _x('This agreement takes effect when all parties have signed it, and its date is the date next to [or below] the signature of the last signer to sign it.', 'Legal document processing agreement', 'complianz-gdpr').'<br />
                      <br />
                      '._x('Date:', 'Legal document processing agreement', 'complianz-gdpr').' ____________<br />
                      <br />
                      <br />
                      <br />
                      '._x('Controller:', 'Legal document processing agreement', 'complianz-gdpr').'<br />
                      <br />
                      ___________________________'._x('(Signature)', 'Legal document processing agreement', 'complianz-gdpr').'<br />
                      <br />
                      <br />
                      <br />
                      '._x('Processor:', 'Legal document processing agreement', 'complianz-gdpr').'<br />
                      <br />
                      ___________________________'._x('(Signature)', 'Legal document processing agreement', 'complianz-gdpr').'<br /><br />',
    ),

    array(
        'annex' => true,
        'title' => _x('The Processing of Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    array(
        'numbering' => false,
        'subtitle' => _x('Purpose of the processing', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => '[processor-activities-au]',
    ),

    array(
        'numbering' => false,
        'subtitle' => _x('Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => cmplz_sprintf(_x('Within the scope of the Data Processing Agreement, the Processor shall process the following Personal data on the instructions of the Controller:%s%s', 'Legal document processing agreement', 'complianz-gdpr'), '<br />[what-kind-of-data-au]', '<br />[what-kind-of-data-other-au]'),
    ),

    array(
        'numbering' => false,
        'subtitle' => _x('Data subject categories', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => cmplz_sprintf(_x('Personal data of the following groups of persons shall be processed:%s%s', 'Legal document processing agreement', 'complianz-gdpr'), '<br />[data-from-whom-au]', '<br />[data-from-whom-other-au]'),
    ),

    array(
        'numbering' => false,
        'subtitle' => _x('Data subject categories', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The Controller shall ensure that the purposes, personal data, and categories of data subjects described in this Annex 1 are complete and correct, and shall indemnify the Processor against any defects and claims resulting from an incorrect representation by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
    ),

    //index used to refer to in agreement.
    'security-measures-au' => array(
        'annex' => true,
        'title' => _x('Security measures', 'Legal document processing agreement', 'complianz-gdpr'),
        'content' => _x('The purpose of this annex is to further specify the standards and measures the Processor must apply in connection with the security of the Processing. The following security measures have been taken:', 'Legal document processing agreement', 'complianz-gdpr').
            '[processing-security-measures-au]<br />[processing-security-measures-other-au]',
        'condition' => array('security_measures-au' => 3)
    ),


);

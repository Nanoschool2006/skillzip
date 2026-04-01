<?php
defined('ABSPATH') or die("you do not have access to this page!");
$elements = array(
    array(
        'subtitle' => 'The undersigned:',
        'content' => '1. [organisation_name]',
    ),
    array(
        'content' => 'hereinafter referred to as: Controller',
    ),
    array(
        'content' => '<b>' . 'and' . '</b>',
    ),
    array(
        'content' => '2. [name_of_processor-uk]',
    ),
    array(
        'content' => 'hereinafter referred to as: Processor',
    ),
    array(
        'content' => 'hereinafter jointly referred to as: Parties; ',
    ),
    array(
        'subtitle' => 'WHEREAS:',
        'content' =>
            '<ul>
                        <li>' . 'Insofar as the Contractor processes Personal Data on behalf of the Client within the scope of the Agreement, the Client qualifies as the Controller for the Processing of Personal Data and the Contractor as the Processor, pursuant to Article 32 of the Data Protection Act;' . '</li>
                        <li>' . 'The Parties to this Data Processing Agreement, within the meaning of Article 59 paragraph 5 of the Data Protection Act, wish to record their agreements on the Processing of Personal Data.' . '</li>
                    </ul>',
    ),
    'agree-that-title' => array(
        'subtitle' => 'Agree as follows:',
    ),
    'def-1' => array(
        'title' => 'Definitions',
        'content' => 'The following terms used in this Data Processing Agreement shall have the meaning hereby assigned to them:',
    ),
    'def-4' => array(
        'subtitle' => 'Agreement',
        'content' => 'The agreement between the Controller and the Processor.',
    ),
    'def-2' => array(
        'subtitle' => 'Data Subject',
        'content' => 'The person to whom Personal Data relates'
    ),
    'def-7' => array(
        'subtitle' => 'Data Processing Agreement',
        'content' => 'This agreement including its recitals and annexes.',
    ),
    'def-5' => array(
        'subtitle' => 'Personal Data',
        'content' => 'Any information relating to an identified or identifiable natural person that the Processor processes on behalf of the Controller within the scope of the Agreement.',
    ),
    'def-3' => array(
        'subtitle' => 'Personal Data Breach',
        'content' => 'A breach of security that accidentally or unlawfully results in the destruction, loss, alteration or unauthorised disclosure or access to personal data transmitted, stored or otherwise processed.',
    ),
    'def-8' => array(
        'subtitle' => 'Processing',
        'content' => 'Any operation or any set of operations relating to Personal Data within the scope of the Agreement, carried out by means of automated processes or otherwise, such as collection, recording, organisation, structuring, storage, adaptation or alteration, retrieval, consultation, use, disclosure by means of transmission, disseminating or otherwise making available, aligning or combining, restriction, erasure or destruction. ',
    ),


    'subject-processing-agreement' => array(
        'title' => 'Subject of this Data Processing Agreement ',
    ),
    'subject-processing-agreement-1' => array(
        'subtitle' => '',
        'content' => 'This Data Processing Agreement regulates the Processing of Personal Data by the Processor within the scope of the Agreement.',
    ),
    'subject-processing-agreement-2' => array(
        'subtitle' => '',
        'content' => 'The nature and the purpose of the Processing, the type of Personal Data, and the categories of Data Subjects are set out in Annex 1. ',
    ),
    'subject-processing-agreement-3' => array(
        'subtitle' => '',
        'content' => 'The Processor guarantees the implementation of appropriate technical and organisational measures, so that the Processing complies with the requirements of the Regulation and the protection of the rights of the Data Subject is guaranteed.',
    ),
    'subject-processing-agreement-4' => array(
        'subtitle' => '',
        'content' => 'The Processor guarantees compliance with the requirements of applicable legislation and regulations relating to the processing of Personal Data. ',
    ),
    'subject-processing-agreement-5' => array(
        'subtitle' => '',
        'content' => 'The personal data to be processed on the instructions of the Controller shall remain the property of the Controller.',
    ),

    'article-3' => array(
        'title' => 'Entry into force and duration',
    ),
    'article-3-1' => array(
        'subtitle' => '',
        'content' => 'This Agreement shall enter into force on the date it is signed by the Parties.',
    ),
    'article-3-2' => array(
        'subtitle' => '',
        'content' => 'This Data Processing Agreement shall terminate after and insofar as the Processor has deleted or returned all Personal Data in accordance with Article 10.',
    ),
    'article-3-3' => array(
        'subtitle' => '',
        'content' => 'Neither Party may terminate this Data Processing Agreement prematurely.',
    ),
    'article-3-4' => array(
        'subtitle' => '',
        'content' => 'Parties may only amend this Agreement by mutual consent.',
    ),

    'article-4' => array(

        'title' => 'Scope of Processing Authority of the Processor',
    ),
    'article-4.1' => array(
        'subtitle' => '',
        'content' => 'The Processor shall process the Personal Data exclusively on the basis of written instructions from the Controller, except in the case of derogating statutory provisions applicable to the Processor.',
    ),
    'article-4.2' => array(
    	'subtitle' => '',
        'content' => 'If, in the opinion of the Processor, an instruction as referred to in the first paragraph conflicts with a statutory regulation on data protection, it shall inform the Controller thereof prior to the Processing, unless a statutory regulation prohibits such notification.',
    ),
    'article-4.3' => array(
        'subtitle' => '',
        'content' => 'If the Processor is required to provide Personal Data on the basis of a statutory provision, it shall inform the Controller without delay and, if possible, prior to providing the data.',
    ),
    'article-4.4' => array(
        'subtitle' => '',
        'content' => 'The Processor has no control over the purpose and means of Processing of Personal Data.',
    ),
    'article-5' => array(
        'title' => 'Security of the Processing',
    ),
    'article-5-1' => array(
        'subtitle' => '',
        'content' => 'The Processor will endeavour to implement adequate technical and organisational measures with regard to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data).',
        'condition' => array(
            'security_measures-uk' => 1,
        )
    ),
    'article-5-2' => array(
        'subtitle' => '',
        'content' => 'The Processor shall endeavour to implement adequate technical and organisational measures with respect to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data). To this end, the Processor shall take the technical and organisational security measures as set out in a separate security protocol.',
        'condition' => array(
            'security_measures-uk' => 2,
        )
    ),
    'article-5-3' => array(
        'subtitle' => '',
        'content' => 'The security protocol shall be added to this Agreement as a separate annex and shall be available from the Processor upon request.',
        'condition' => array(
            'security-protocol-where-uk' => 1,
        )
    ),
    'article-5-4' => array(
        'subtitle' => '',
        'content' => cmplz_sprintf('The security protocol can be viewed online %shere%s.', '[security-protocol-where-url-uk]', '[/security-protocol-where-url-uk]'),
        'condition' => array(
            'security-protocol-where-uk' => 2,
        )
    ),
    'article-5-5' => array(
        'subtitle' => '',
        'content' => cmplz_sprintf('The Processor will endeavour to implement adequate technical and organisational measures with respect to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data). To this end, the Processor shall take the technical and organisational security measures as set out in %s.', '[annex-security-measures]'),
        'condition' => array(
            'security_measures-uk' => 3,
        )
    ),
    'article-5-6' => array(
        'subtitle' => '',
        'content' => 'Parties recognise that ensuring an appropriate level of security may require additional security measures to be implemented at any time. The Processor shall ensure a level of security appropriate to the risk. If and insofar as the Controller explicitly requests this in writing, the Processor shall implement additional measures with respect to the security of the Personal Data.',
    ),
    'article-5-7' => array(
        'subtitle' => '',
        'content' => 'The Processor shall not process Personal Data outside the European Union, unless explicit written consent to do so has been granted by the Controller and subject to derogating statutory obligations.',
        'condition' => array(
            'allow-outside-eu-uk' => 1,
        )
    ),

    'article-5-8' => array(
        'content' => 'The Processor may process the personal data in countries within the United Kingdom. Transfer to countries outside the UK is permitted, provided the relevant legal conditions have been met. Upon request, the Processor shall inform the Controller of the country or countries that is or are involved.',
        'condition' => array(
            'allow-outside-eu-uk' => 2,
        )
    ),
    'article-5-9' => array(
        'subtitle' => '',
        'content' => 'The Processor shall inform the Controller without unreasonable delay as soon as it has become aware of any unlawful Processing of Personal Data or any breach of security measures as referred to in the first and second paragraph.',
    ),

    'article-5-10' => array(
        'subtitle' => '',
        'content' => 'The Processor shall assist the Controller in compliance with the obligations under Articles 64 through 68 of the Data Protection Act.',
    ),

    'article-6' => array(
        'title' => 'Duty of Confidentiality of Personnel of the Processor',
        'content' => '',
    ),
    'article-6-1' => array(
        'subtitle' => '',
        'content' => 'The Personal Data is of a confidential nature. The Processor shall not use this data for any purpose other than for which it has been acquired, even if it has been converted into such a form that it cannot be traced to data subjects.',
    ),
    'article-6-2' => array(
        'subtitle' => '',
        'content' => 'At the request of the Controller, the Processor shall demonstrate that its Personnel have undertaken to observe confidentiality. The personal data will only be disclosed to those employees and/or third parties who must necessarily take cognisance of the Personal Data.',
    ),
    'article-6-3' => array(
        'subtitle' => '',
        'content' => 'This duty of confidentiality shall not apply where the Controller has given express consent to disclose the data to third parties, if disclosure of the data to third parties is logically necessary given the nature of the assignment and the performance of this Data Processing Agreement, or if there is a statutory obligation to disclose the data to a third party.',
    ),

    'article-7' => array(
        'title' => 'Sub-processor',
        'content' => '',
    ),
    'article-7-1' => array(
        'subtitle' => '',
        'content' => 'Within the scope of the Agreement, the Processor may make use of third parties on condition that the Controller is informed thereof in advance; the Controller may terminate the Agreement if it cannot accept the use of a specific third party.',
    ),
    'article-7-2' => array(
        'subtitle' => '',
        'content' => 'In any case, the Processor shall ensure that these third parties assume, in writing, at least the same obligations as those agreed between the Controller and the Processor.',
    ),
    'article-7-3' => array(
        'subtitle' => '',
        'content' => 'The Processor is responsible for correct compliance with the obligations under this Data Processing Agreement by these third parties, and in the event of errors by these third parties it shall be liable as if it were at fault.',
    ),

    'article-8' => array(
        'title' => 'Assistance on account of the rights of the Data Subject',
    ),
    'article-8-1' => array(
        'subtitle' => '',
        'content' => 'In the event a data subject submits a request to the Processor to exercise his/her legal rights, the Processor shall forward the request to the Controller, and the Controller shall further handle the request. The Processor may inform the data subject accordingly. ',
    ),
    'article-8-2' => array(
        'subtitle' => '',
        'content' => "The Processor shall, to the extent within its power, provide assistance to the Controller in fulfilling the latter's obligation to respond to requests of the Data Subject to exercise its rights laid down in Chapter III of the Regulation.",
    ),
    'article-8-3' => array(
        'subtitle' => '',
        'content' => 'The Processor may charge the additional costs it incurs in this respect to the Controller.',
        'condition' => array('deal_with_requests-uk' => 2),
    ),

    'article-9' => array(
        'title' => 'Personal Data Breach',
    ),
    'article-9-1a' => array(
        'subtitle' => '',
        'content' => 'The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach. ',
        'condition' => array('when-informed-uk' => 1),
    ),
    'article-9-1b' => array(
        'subtitle' => '',
        'content' => 'The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach, but no later than within 24 hours after discovery.',
        'condition' => array('when-informed-uk' => 2),
    ),
    'article-9-1c' => array(
        'subtitle' => '',
        'content' => 'The Processor shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach, but no later than within 36 hours after discovery.',
        'condition' => array('when-informed-uk' => 3),
    ),
    'article-9-2' => array(
        'subtitle' => '',
        'content' => 'Information that must at least be provided by the Processor shall include:' .
            '<ul>
                        <li>' . 'The nature of the Personal Data Breach' . '</li>
                          <li>' . 'The Personal Data and Data Subject' . '</li>
                        <li>' . 'Likely consequences of the Personal Data Breach' . '</li>
                        <li>' . 'Measures proposed or implemented by the Processor to address the Personal Data Breach, including, where appropriate, measures to mitigate its possible adverse effects.' . '</li>
                    </ul>',
    ),
    'article-9-3' => array(
        'subtitle' => '',
        'content' => 'The Processor shall also inform the Controller of further developments concerning the Personal Data Breach after having reported the breach pursuant to the first paragraph. ',
    ),
    'article-9-4' => array(
        'subtitle' => '',
        'content' => 'Each party shall bear their own costs relating to the report to the competent supervisory authority and the Data Subject. ',
    ),
    'article-9-5' => array(
        'subtitle' => '',
        'content' => 'In accordance with Article 67, paragraph 6 of the Data Protection Act, the Processor shall document all data breaches, including the facts relating to the Personal Data Breach, its consequences and the corrective measures taken. Upon request, the Processor shall provide the Controller with access to this information.',
    ),

    'article-10' => array(

        'title' => 'Returning Personal Data',
    ),

    'article-10-1' => array(
        'subtitle' => '',
        'content' => 'After expiry of the Agreement, the Processor shall, at the discretion of the Controller, arrange for the return of all Personal Data to the Controller or for the erasure of all Personal Data. The Processor shall remove all copies, except where otherwise provided by law.',
    ),
    'article-11' => array(
        'title' => 'Obligation to disclose information and audit',
        'condition' => array('audit-uk' => 2),
    ),
    'article-11-1' => array(
        'subtitle' => '',
        'content' => 'The Controller shall have the right to conduct audits to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Processor, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller. ',
        'condition' => array('audit-uk' => 2),
    ),
    'article-11-2' => array(
        'subtitle' => '',
        'content' => 'The Controller shall have the right to have audits carried out by an independent external expert, who is bound by confidentiality, to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Processor, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller. ',
        'condition' => array('audit-uk' => 1),
    ),
    'article-11-3' => array(
        'subtitle' => '',
        'content' => 'Such an audit is justified if the similar audit reports present at the Processor\'s are inconclusive or insufficiently conclusive with respect to the Processor\'s compliance with this Data Processing Agreement. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks. ',
        'condition' => array('when-audit-uk' => 1),
    ),
    'article-11-4' => array(
        'subtitle' => '',
        'content' => 'Such an audit shall be justified in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks. ',
        'condition' => array('when-audit-uk' => 2),
    ),
    'article-11-5' => array(
        'subtitle' => '',
        'content' => 'Such an audit may be carried out once every three months, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks',
        'condition' => array('when-audit-uk' => 3),
    ),
    'article-11-6' => array(
        'subtitle' => '',
        'content' => 'Such an audit may be carried out once every calendar year, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Processor in advance, with due observance of a minimum period of two weeks. ',
        'condition' => array('when-audit-uk' => 4),
    ),
    'article-11-7' => array(
        'subtitle' => '',
        'content' => 'The findings in respect of the audit carried out shall be implemented by the Processor as soon as possible.',
        'condition' => array('what-do-with-findings-uk' => 1),
    ),
    'article-11-8' => array(
        'subtitle' => '',
        'content' => 'The findings of the audit carried out will be assessed by the Parties in joint consultation and, depending on the assessment, implemented (or not) by either Party or jointly by both Parties.',
        'condition' => array('what-do-with-findings-uk' => 2),
    ),
    'article-11-9' => array(
        'subtitle' => '',
        'content' => 'The costs of the audit as described in paragraph 1 shall be borne by the Data Controller. ',
        'condition' => array('audit-costs-uk' => 1),
    ),
    'article-11-10' => array(
        'subtitle' => '',
        'content' => 'The costs of the audit as described in paragraph 1 shall be borne by the Processor',
        'condition' => array('audit-costs-uk' => 2),
    ),
    'article-11-11' => array(
        'subtitle' => '',
        'content' => 'The costs of the audit as described in paragraph 1 shall be borne by the Processor, in the event of non-trivial breaches of the obligations arising from the Data Processing Agreement. Otherwise, the costs shall be borne by the Controller.',
        'condition' => array('audit-costs-uk' => 3),
    ),


    'article-12' => array(
        'title' => 'Other Terms and Conditions',
    ),

    'article-12-1' => array(
        'subtitle' => '',
        'content' => 'The Processor shall be liable towards the Controller for all consequences of the breach of this Data Processing Agreement, and shall indemnify the Controller against all claims by third parties, including any penalties, to the extent attributable to the Processor.',
    ),
    'article-12-2' => array(
        'subtitle' => '',
        'content' => cmplz_sprintf('The liability of the Processor shall never exceed %s per year.', '[amount-liable-uk]').
                    '&nbsp;'.'The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Service Provider or its management.',
        'condition' => array('maximize-liability-uk' => 'yes'),
    ),

//    The liability of the Service Provider shall never exceed [line 144 input field] per year.
//The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Service Provider or its management."
    'article-12-2b' => array(
        'subtitle' => '',
        'content' => cmplz_sprintf('During the Data Processing Agreement, the Processor shall have and continue to have adequate insurance cover in place for liability in accordance with this article. The insurance policy should at least cover %s', '[max_cost_of_insurance-uk]'),
        'condition' => array('insurance-uk' => 'yes'),
    ),

    'article-12-3' => array(
        'subtitle' => '',
        'content' => 'The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Processor or its management.',
        'condition' => array('insurance-uk' => 'yes')
    ),

    'article-12-5' => array(
        'subtitle' => '',
        'content' => 'The insurance shall cover:'.
                        '[insurance_conditions-uk]',
        'condition' => array('insurance-uk' => 'yes')

    ),
    'article-12-6' => array(
        'subtitle' => '',
        'content' => 'The insurance conditions may be viewed upon request.',
        'condition' => array('access-to-policy-uk' => 'yes')
    ),
    'annex' => array(
        'annex' => true,
        'title' => 'The Processing of Personal Data',
    ),

    'annex-1' => array(
        'numbering' => false,
        'subtitle' => 'Purpose of the processing',
        'content' => '[processor-activities-uk]',
    ),

    'annex-2' => array(
        'numbering' => false,
        'subtitle' => 'Personal Data',
        'content' => cmplz_sprintf('Within the scope of the Data Processing Agreement, the Processor shall process the following (special) personal data on the instructions of the Controller:<br />%s<br />%s', '[what-kind-of-data-uk]', '[what-kind-of-data-other-uk]'),
    ),

    'annex-3' => array(
        'numbering' => false,
        'subtitle' => 'Data subject categories',
        'content' => cmplz_sprintf('Personal data of the following groups of persons shall be processed:<br />%s<br />%s', '[data-from-whom-uk]', '[data-from-whom-other-uk]'),
    ),

    'annex-4' => array(
        'numbering' => false,
        'subtitle' => 'Data subject categories',
        'content' => 'The Controller shall ensure that the purposes, personal data, and categories of data subjects described in this Annex 1 are complete and correct, and shall indemnify the Processor against any defects and claims resulting from an incorrect representation by the Controller.',
    ),

    'security-measures' => array(
        'annex' => true,
        'title' => 'Security measures',
        'content' => 'The purpose of this annex is to further specify the standards and measures the Processor must apply in connection with the security of the Processing. The following security measures have been taken:' .
            '[processing-security-measures-uk]<br />[processing-security-measures-other-uk]',
        'condition' => array('security_measures-uk' => 3)
    ),

    'annex-6-thirdparty' => array(
        'annex' => true,
        'title' => 'Engagement of third parties and/or sub-processors',
        'content' => 'The Controller has given the Processor permission to engage the following third parties and/or sub-processor(s):',
    ),

	/*signature*/
	array(
		'numbering' => false,
		'content' => 'This agreement takes effect when all parties have signed it, and its date is the date next to [or below] the signature of the last signer to sign it.<br />
                      <br />
                      ___________ day of ____________<br />
                      <br />
                      <br />
                      <br />
                      Controller:<br />
                      <br />
                      ___________________________(Signature)<br />
                      <br />
                      <br />
                      <br />
                      Service Provider:<br />
                      <br />
                      ___________________________(Signature)<br /><br />',
	),

);

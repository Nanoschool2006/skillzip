<?php
defined('ABSPATH') or die("you do not have access to this page!");
$elements = array(
	array(
		'subtitle' => _x('The undersigned:', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => '1. [organisation_name]',
	),
	array(
		'content' => _x('hereinafter referred to as: Controller', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	array(
		'content' => '<b>' . 'and' . '</b>',
	),
	array(
		'content' => '2. [name_of_processor-za]',
	),
	array(
		'content' => _x('hereinafter referred to as: Operator', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	array(
		'content' => _x('hereinafter jointly referred to as: Parties; ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	array(
		'subtitle' => _x('WHEREAS:', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' =>
			'<ul>
                        <li>' . 'Insofar as the Contractor processes Personal Data on behalf of the Client within the scope of the Agreement, the Client qualifies as the Controller for the Processing of Personal Data and the Contractor as the Operator, pursuant to Article 20 of the Protection of Personal Information Act (POPIA);' . '</li>
                        <li>' . 'The Parties to this Data Processing Agreement, within the meaning of Article 21 paragraph 1 of the Protection of Personal Information Act (POPIA), wish to record their agreements on the Processing of Personal Data.' . '</li>
                    </ul>',
	),
	'agree-that-title' => array(
		'subtitle' => _x('Agree as follows:', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-1' => array(
		'title' => _x('Definitions', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The following terms used in this Data Processing Agreement shall have the meaning hereby assigned to them:', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-4' => array(
		'subtitle' => _x('Agreement', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The agreement between the Controller and the Operator.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-2' => array(
		'subtitle' => _x('Data Subject', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The person to whom Personal Data relates', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-7' => array(
		'subtitle' => _x('Data Processing Agreement', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('This agreement including its recitals and annexes.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-5' => array(
		'subtitle' => _x('Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('Any information relating to an identified or identifiable natural person that the Operator processes on behalf of the Controller within the scope of the Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-3' => array(
		'subtitle' => _x('Personal Data Breach', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('A breach of security that accidentally or unlawfully results in the destruction, loss, alteration or unauthorised disclosure or access to personal data transmitted, stored or otherwise processed.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'def-8' => array(
		'subtitle' => _x('Processing', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('Any operation or any set of operations relating to Personal Data within the scope of the Agreement, carried out by means of automated processes or otherwise, such as collection, recording, organisation, structuring, storage, adaptation or alteration, retrieval, consultation, use, disclosure by means of transmission, disseminating or otherwise making available, aligning or combining, restriction, erasure or destruction. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),


	'subject-processing-agreement' => array(
		'title' => _x('Subject of this Data Processing Agreement ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'subject-processing-agreement-1' => array(
		'subtitle' => '',
		'content' => _x('This Data Processing Agreement regulates the Processing of Personal Data by the Operator within the scope of the Agreement.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'subject-processing-agreement-2' => array(
		'subtitle' => '',
		'content' => _x('The nature and the purpose of the Processing, the type of Personal Data, and the categories of Data Subjects are set out in Annex 1. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'subject-processing-agreement-3' => array(
		'subtitle' => '',
		'content' => _x('The Operator guarantees the implementation of appropriate technical and organisational measures, so that the Processing complies with the requirements of the Regulation and the protection of the rights of the Data Subject is guaranteed.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'subject-processing-agreement-4' => array(
		'subtitle' => '',
		'content' => _x('The Operator guarantees compliance with the requirements of applicable legislation and regulations relating to the processing of Personal Data. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'subject-processing-agreement-5' => array(
		'subtitle' => '',
		'content' => _x('The personal data to be processed on the instructions of the Controller shall remain the property of the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-3' => array(
		'title' => _x('Entry into force and duration', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-3-1' => array(
		'subtitle' => '',
		'content' => _x('This Agreement shall enter into force on the date it is signed by the Parties.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-3-2' => array(
		'subtitle' => '',
		'content' => _x('This Data Processing Agreement shall terminate after and insofar as the Operator has deleted or returned all Personal Data in accordance with Article 10.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-3-3' => array(
		'subtitle' => '',
		'content' => _x('Neither Party may terminate this Data Processing Agreement prematurely.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-3-4' => array(
		'subtitle' => '',
		'content' => _x('Parties may only amend this Agreement by mutual consent.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-4' => array(

		'title' => _x('Scope of Processing Authority of the Operator', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-4.1' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall process the Personal Data exclusively on the basis of written instructions from the Controller, except in the case of derogating statutory provisions applicable to the Operator.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-4.2' => array(
		'subtitle' => '',
		'content' => _x('If, in the opinion of the Operator, an instruction as referred to in the first paragraph conflicts with a statutory regulation on data protection, it shall inform the Controller thereof prior to the Processing, unless a statutory regulation prohibits such notification.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-4.3' => array(
		'subtitle' => '',
		'content' => _x('If the Operator is required to provide Personal Data on the basis of a statutory provision, it shall inform the Controller without delay and, if possible, prior to providing the data.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-4.4' => array(
		'subtitle' => '',
		'content' => _x('The Operator has no control over the purpose and means of Processing of Personal Data.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-5' => array(
		'title' => _x('Security of the Processing', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-5-1' => array(
		'subtitle' => '',
		'content' => _x('The Operator will endeavour to implement adequate technical and organisational measures with regard to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data).', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array(
			'security_measures-za' => 1,
		)
	),
	'article-5-2' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall endeavour to implement adequate technical and organisational measures with respect to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data). To this end, the Operator shall take the technical and organisational security measures as set out in a separate security protocol.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array(
			'security_measures-za' => 2,
		)
	),
	'article-5-3' => array(
		'subtitle' => '',
		'content' => _x('The security protocol shall be added to this Agreement as a separate annex and shall be available from the Operator upon request.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array(
			'security-protocol-where-za' => 1,
		)
	),
	'article-5-4' => array(
		'subtitle' => '',
		'content' => cmplz_sprintf(_x('The security protocol can be viewed online %shere%s.', 'Legal document processing agreement', 'complianz-gdpr'), '[security-protocol-where-url-za]', '[/security-protocol-where-url-za]'),
		'condition' => array(
			'security-protocol-where-za' => 2,
		)
	),
	'article-5-5' => array(
		'subtitle' => '',
		'content' => cmplz_sprintf(_x('The Operator will endeavour to implement adequate technical and organisational measures with respect to the processing operations of personal data to be carried out, against loss or any form of unlawful processing (such as unauthorised disclosure, deterioration, alteration or transmission of personal data). To this end, the Operator shall take the technical and organisational security measures as set out in %s.', 'Legal document processing agreement', 'complianz-gdpr'), '[annex-security-measures]'),
		'condition' => array(
			'security_measures-za' => 3,
		)
	),
	'article-5-6' => array(
		'subtitle' => '',
		'content' => _x('Parties recognise that ensuring an appropriate level of security may require additional security measures to be implemented at any time. The Operator shall ensure a level of security appropriate to the risk. If and insofar as the Controller explicitly requests this in writing, the Operator shall implement additional measures with respect to the security of the Personal Data.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-5-7' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall not process Personal Data outside South Africa, unless explicit written consent to do so has been granted by the Controller and subject to derogating statutory obligations.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array(
			'allow-outside-eu-za' => 1,
		)
	),

	'article-5-8' => array(
		'content' => _x('Operator may process the personal data within South Africa. Transfer to countries outside South Africa is permitted, provided the relevant legal conditions have been met. Upon request, the Operator shall inform the Controller of the country or countries that is or are involved.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array(
			'allow-outside-eu-za' => 2,
		)
	),
	'article-5-9' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall inform the Controller without unreasonable delay as soon as it has become aware of any unlawful Processing of Personal Data or any breach of security measures as referred to in the first and second paragraph.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-5-10' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall assist the Controller in compliance with the obligations under Articles 64 through 68 of the Data Protection Act.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-6' => array(
		'title' => _x('Duty of Confidentiality of Personnel of the Operator', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => '',
	),
	'article-6-1' => array(
		'subtitle' => '',
		'content' => _x('The Personal Data is of a confidential nature. The Operator shall not use this data for any purpose other than for which it has been acquired, even if it has been converted into such a form that it cannot be traced to data subjects.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-6-2' => array(
		'subtitle' => '',
		'content' => _x('At the request of the Controller, the Operator shall demonstrate that its Personnel have undertaken to observe confidentiality. The personal data will only be disclosed to those employees and/or third parties who must necessarily take cognisance of the Personal Data.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-6-3' => array(
		'subtitle' => '',
		'content' => _x('This duty of confidentiality shall not apply where the Controller has given express consent to disclose the data to third parties, if disclosure of the data to third parties is logically necessary given the nature of the assignment and the performance of this Data Processing Agreement, or if there is a statutory obligation to disclose the data to a third party.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-7' => array(
		'title' => _x('Sub-processor', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => '',
	),
	'article-7-1' => array(
		'subtitle' => '',
		'content' => _x('Within the scope of the Agreement, the Operator may make use of third parties on condition that the Controller is informed thereof in advance; the Controller may terminate the Agreement if it cannot accept the use of a specific third party.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-7-2' => array(
		'subtitle' => '',
		'content' => _x('In any case, the Operator shall ensure that these third parties assume, in writing, at least the same obligations as those agreed between the Controller and the Operator.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-7-3' => array(
		'subtitle' => '',
		'content' => _x('The Operator is responsible for correct compliance with the obligations under this Data Processing Agreement by these third parties, and in the event of errors by these third parties it shall be liable as if it were at fault.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-8' => array(
		'title' => _x('Assistance on account of the rights of the Data Subject', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-8-1' => array(
		'subtitle' => '',
		'content' => _x('In the event a data subject submits a request to the Operator to exercise his/her legal rights, the Operator shall forward the request to the Controller, and the Controller shall further handle the request. The Operator may inform the data subject accordingly. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-8-2' => array(
		'subtitle' => '',
		'content' => _x("The Operator shall, to the extent within its power, provide assistance to the Controller in fulfilling the latter's obligation to respond to requests of the Data Subject to exercise its rights laid down in Chapter III of the Regulation.", 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-8-3' => array(
		'subtitle' => '',
		'content' => _x('The Operator may charge the additional costs it incurs in this respect to the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('deal_with_requests-za' => 2),
	),

	'article-9' => array(
		'title' => _x('Personal Data Breach', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-9-1a' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-informed-za' => 1),
	),
	'article-9-1b' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach, but no later than within 24 hours after discovery.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-informed-za' => 2),
	),
	'article-9-1c' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall inform the Controller without unreasonable delay, as soon as it has become aware of a Personal Data Breach, but no later than within 36 hours after discovery.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-informed-za' => 3),
	),
	'article-9-2' => array(
		'subtitle' => '',
		'content' => _x('Information that must at least be provided by the Operator shall include:', 'Legal document processing agreement', 'complianz-gdpr').
			'<ul>
                        <li>' . _x('The nature of the Personal Data Breach', 'Legal document processing agreement', 'complianz-gdpr') . '</li>
                          <li>' . _x('The Personal Data and Data Subject', 'Legal document processing agreement', 'complianz-gdpr') . '</li>
                        <li>' . _x('Likely consequences of the Personal Data Breach', 'Legal document processing agreement', 'complianz-gdpr') . '</li>
                        <li>' . _x('Measures proposed or implemented by the Operator to address the Personal Data Breach, including, where appropriate, measures to mitigate its possible adverse effects.', 'Legal document processing agreement', 'complianz-gdpr') . '</li>
                    </ul>',
	),
	'article-9-3' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall also inform the Controller of further developments concerning the Personal Data Breach after having reported the breach pursuant to the first paragraph. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-9-4' => array(
		'subtitle' => '',
		'content' => _x('Each party shall bear their own costs relating to the report to the competent supervisory authority and the Data Subject. ', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-9-5' => array(
		'subtitle' => '',
		'content' => _x('In accordance with Article 67, paragraph 6 of the Data Protection Act, the Operator shall document all data breaches, including the facts relating to the Personal Data Breach, its consequences and the corrective measures taken. Upon request, the Operator shall provide the Controller with access to this information.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-10' => array(

		'title' => _x('Returning Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-10-1' => array(
		'subtitle' => '',
		'content' => _x('After expiry of the Agreement, the Operator shall, at the discretion of the Controller, arrange for the return of all Personal Data to the Controller or for the erasure of all Personal Data. The Operator shall remove all copies, except where otherwise provided by law.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-11' => array(
		'title' => _x('Obligation to disclose information and audit', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-za' => 2),
	),
	'article-11-1' => array(
		'subtitle' => '',
		'content' => _x('The Controller shall have the right to conduct audits to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Operator, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-za' => 2),
	),
	'article-11-2' => array(
		'subtitle' => '',
		'content' => _x('The Controller shall have the right to have audits carried out by an independent external expert, who is bound by confidentiality, to verify compliance with all points of the Data Processing Agreement and everything directly related to this. This audit shall only take place after the Controller has requested similar audit reports from the Operator, reviewed them, and put forward reasonable arguments to justify an audit initiated by the Controller. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-za' => 1),
	),
	'article-11-3' => array(
		'subtitle' => '',
		'content' => _x('Such an audit is justified if the similar audit reports present at the Operator\'s are inconclusive or insufficiently conclusive with respect to the Operator\'s compliance with this Data Processing Agreement. The Controller shall communicate the audit to the Operator in advance, with due observance of a minimum period of two weeks. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-audit-za' => 1),
	),
	'article-11-4' => array(
		'subtitle' => '',
		'content' => _x('Such an audit shall be justified in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Operator in advance, with due observance of a minimum period of two weeks. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-audit-za' => 2),
	),
	'article-11-5' => array(
		'subtitle' => '',
		'content' => _x('Such an audit may be carried out once every three months, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Operator in advance, with due observance of a minimum period of two weeks', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-audit-za' => 3),
	),
	'article-11-6' => array(
		'subtitle' => '',
		'content' => _x('Such an audit may be carried out once every calendar year, and more often in the event of a concrete suspicion of abuse. The Controller shall communicate the audit to the Operator in advance, with due observance of a minimum period of two weeks. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('when-audit-za' => 4),
	),
	'article-11-7' => array(
		'subtitle' => '',
		'content' => _x('The findings in respect of the audit carried out shall be implemented by the Operator as soon as possible.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('what-do-with-findings-za' => 1),
	),
	'article-11-8' => array(
		'subtitle' => '',
		'content' => _x('The findings of the audit carried out will be assessed by the Parties in joint consultation and, depending on the assessment, implemented (or not) by either Party or jointly by both Parties.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('what-do-with-findings-za' => 2),
	),
	'article-11-9' => array(
		'subtitle' => '',
		'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the responsible party. ', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-costs-za' => 1),
	),
	'article-11-10' => array(
		'subtitle' => '',
		'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the Operator', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-costs-za' => 2),
	),
	'article-11-11' => array(
		'subtitle' => '',
		'content' => _x('The costs of the audit as described in paragraph 1 shall be borne by the Operator, in the event of non-trivial breaches of the obligations arising from the Data Processing Agreement. Otherwise, the costs shall be borne by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('audit-costs-za' => 3),
	),


	'article-12' => array(
		'title' => _x('Other Terms and Conditions', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'article-12-1' => array(
		'subtitle' => '',
		'content' => _x('The Operator shall be liable towards the Controller for all consequences of the breach of this Data Processing Agreement, and shall indemnify the Controller against all claims by third parties, including any penalties, to the extent attributable to the Operator.', 'Legal document processing agreement', 'complianz-gdpr'),
	),
	'article-12-2' => array(
		'subtitle' => '',
		'content' => cmplz_sprintf(_x('The liability of the Operator shall never exceed %s per year.', 'Legal document processing agreement', 'complianz-gdpr'), '[amount-liable-za]').
			'&nbsp;'._x('The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Service Provider or its management.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('maximize-liability-za' => 'yes'),
	),

//    The liability of the Service Provider shall never exceed [line 144 input field] per year.
//The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Service Provider or its management."
	'article-12-2b' => array(
		'subtitle' => '',
		'content' => cmplz_sprintf(_x('During the Data Processing Agreement, the Operator shall have and continue to have adequate insurance cover in place for liability in accordance with this article. The insurance policy should at least cover %s', 'Legal document processing agreement', 'complianz-gdpr'), '[max_cost_of_insurance-za]'),
		'condition' => array('insurance-za' => 'yes'),
	),

	'article-12-3' => array(
		'subtitle' => '',
		'content' => _x('The limitation referred to in this Article shall not apply if and insofar as the damage is the result of intent or deliberate recklessness on the part of the Operator or its management.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('insurance-za' => 'yes')
	),

	'article-12-5' => array(
		'subtitle' => '',
		'content' => _x('The insurance shall cover:', 'Legal document processing agreement', 'complianz-gdpr'),
			'[insurance_conditions-za]',
		'condition' => array('insurance-za' => 'yes')

	),
	'article-12-6' => array(
		'subtitle' => '',
		'content' => _x('The insurance conditions may be viewed upon request.', 'Legal document processing agreement', 'complianz-gdpr'),
		'condition' => array('access-to-policy-za' => 'yes')
	),
	'annex' => array(
		'annex' => true,
		'title' => _x('The Processing of Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'annex-1' => array(
		'numbering' => false,
		'subtitle' => _x('Purpose of the processing', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => '[processor-activities-za]',
	),

	'annex-2' => array(
		'numbering' => false,
		'subtitle' => _x('Personal Data', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => cmplz_sprintf(_x('Within the scope of the Data Processing Agreement, the Operator shall process the following (special) personal data on the instructions of the Controller:', 'Legal document processing agreement', 'complianz-gdpr') . '<br />%s<br />%s', '[what-kind-of-data-za]', '[what-kind-of-data-other-za]'),
	),

	'annex-3' => array(
		'numbering' => false,
		'subtitle' => _x('Data subject categories', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => cmplz_sprintf(_x('Personal data of the following groups of persons shall be processed:', 'Legal document processing agreement', 'complianz-gdpr') . '<br />%s<br />%s', '[data-from-whom-za]', '[data-from-whom-other-za]'),
	),

	'annex-4' => array(
		'numbering' => false,
		'subtitle' => _x('Data subject categories', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The Controller shall ensure that the purposes, personal data, and categories of data subjects described in this Annex 1 are complete and correct, and shall indemnify the Operator against any defects and claims resulting from an incorrect representation by the Controller.', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	'security-measures' => array(
		'annex' => true,
		'title' => _x('Security measures', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The purpose of this annex is to further specify the standards and measures the Operator must apply in connection with the security of the Processing. The following security measures have been taken:' , 'Legal document processing agreement', 'complianz-gdpr'),
			'[processing-security-measures-za]<br />[processing-security-measures-other-za]',
		'condition' => array('security_measures-za' => 3)
	),

	'annex-6-thirdparty' => array(
		'annex' => true,
		'title' => _x('Engagement of third parties and/or sub-processors', 'Legal document processing agreement', 'complianz-gdpr'),
		'content' => _x('The Controller has given the Operator permission to engage the following third parties and/or sub-processor(s):', 'Legal document processing agreement', 'complianz-gdpr'),
	),

	/*signature*/
	array(
		'numbering' => false,
		'content' => _x('This agreement takes effect when all parties have signed it, and its date is the date next to [or below] the signature of the last signer to sign it.', 'Legal document processing agreement', 'complianz-gdpr') ."<br />
                      <br />
                      ___________ ". _x('day of', 'Legal document processing agreement', 'complianz-gdpr') ." ____________<br />
                      <br />
                      <br />
                      <br />
                      ". _x('Controller', 'Legal document processing agreement', 'complianz-gdpr') .":<br />
                      <br />
                      ___________________________(". _x('Signature', 'Legal document processing agreement', 'complianz-gdpr') .")<br />
                      <br />
                      <br />
                      <br />
                      ". _x('Service provider', 'Legal document processing agreement', 'complianz-gdpr') .":<br />
                      <br />
                      ___________________________(". _x('Signature', 'Legal document processing agreement', 'complianz-gdpr') .")<br /><br />",
	),
);

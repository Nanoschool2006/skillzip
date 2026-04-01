<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
add_filter( 'cmplz_document_elements', 'cmplz_dynamic_pro_document_elements', 10, 4 );
function cmplz_dynamic_pro_document_elements( $elements, $region, $type, $fields ) {
	//if not documents/regions are selected, the array can be empty
	if ( !is_array($elements) ) {
		$elements = array();
	}
	if ( $type === 'privacy-statement' ) {
		$purposes = cmplz_get_option('purpose_personaldata');
		if ( !is_array( $purposes ) ) {
			$purposes = [];
		}
		$purpose_elements = array(
			'purpose' => array(
				'p'         => false,
				'numbering' => true,
				'title'     => __( "Purpose, data and retention period", 'complianz-gdpr' ),
				'content'   => __( "We may collect or receive personal information for a number of purposes connected with our business operations which may include the following: (click to expand)", 'complianz-gdpr' ),
			)
		);

		foreach ( $purposes as $key ) {
			//a key might not exist if we just disabled US, and had selected an option which is not available in the EU.
			$purpose_personal_data_index = cmplz_get_field_index( 'purpose_personaldata', $fields );
			if ( $purpose_personal_data_index === false ) {
				continue;
			}
			if ( !is_string($key) || !isset( $fields[$purpose_personal_data_index]['options'][ $key ] ) ) {
				continue;
			}
			$label            = $fields[$purpose_personal_data_index]['options'][ $key ];
			$purpose_elements += array(
				$key . '_title' => array(
					'p'              => false,
					'dropdown-open'  => true,
					'dropdown-title' => $label,
					'dropdown-class' => 'dropdown-privacy-statement',
					'condition'      => array( 'purpose_personaldata' => $key ),
				),
			);
			// For the South Arican law, the user needs to describe the law and we need to add that description to purpose.
			if ( ( $key === 'legal-obligations' && $region === 'za' ) ) {
				$purpose_elements += array(
					$key . '_legal_obligation_explained' => array(
						'subtitle'  => __( 'The collection is required or authorized by the following law or court/tribunal order:', "complianz-gdpr" ),
						'content'   => '[legal-obligations-description]',
						'numbering' => false,
						'class'     => 'legal-obligations-description',
						'condition' => array( 'purpose_personaldata' => $key )
					),
				);
			}

			$purpose_elements +=
                array(
                    $key . '_gegevens' => array(
	                    'subtitle'  => __( 'For this purpose we use the following data:', 'complianz-gdpr' ),
	                    'numbering' => false,
	                    'content'   => '[' . $key . '_data_purpose_us]',
	                    'condition' => array( 'purpose_personaldata' => $key ),
                    ),
                );
			if ( $region !== 'us' && $region !== 'ca' && $region !== 'za' ) {
				$purpose_elements += array(
						$key . '_processing_data_lawfull'     => array(
							'subtitle'  => __( 'The basis on which we may process these data is:', 'complianz-gdpr' ),
							'numbering' => false,
						),
						$key . '_lawful-basis-2'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#consent">' . __( 'Upon the provision of consent.', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '1' ),
						),
						$key . '_lawful-basis-3'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#agreement">'
							               . __( 'It is necessary for the execution of a contract or preliminary procedures related to a contract to which the data subject is a party. ', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '2' ),
						),
						$key . '_lawful-basis-4'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#obligation">'
							               . __( 'For compliance with a legal or regulatory obligation.', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '3' ),
						),
						$key . '_lawful-basis-5'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#public">'
							               . __( 'It is necessary for the performance of a task carried out in the public interest or in the exercise of official authority vested in the controller',
									'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '4' ),
						),
						$key . '_lawful-basis-6'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary for the purposes of the legitimate interests pursued by the controller or by a third party, and that interest outweighs the interest of the person concerned.',
									'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '5' ),
						),
						$key . '_lawful-basis-7'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary to protect the life or physical safety of a person', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '6' ),
						),
						$key . '_lawful-basis-8'              => array(
							'p'         => false,
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary to carry out studies by a research body, ensuring, whenever possible, the anonymization of personal data.', 'complianz-gdpr' )
							               . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '7' ),
						),
						$key . '_lawful-basis-9'              => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary for the regular exercise of rights in judicial, administrative or arbitration proceedings.', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '8' ),
						),
						$key . '_lawful-basis-10'             => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary for the protection of health, exclusively, in a procedure performed by health professionals, health services or health authority.',
									'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '9' ),
						),
						$key . '_lawful-basis-11'             => array(
							'content'   => '<a target="_blank" rel="noopener noreferrer nofollow" href="https://cookiedatabase.org/legal-bases/#legitimate">'
							               . __( 'It is necessary for credit protection.', 'complianz-gdpr' ) . '</a>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '10' ),
						),
						$key . '_lawful-basis-11-explanation' => array(
							'content'   => '<p>[' . $key . '_credit_protection_relevant_legislation]</p>',
							'numbering' => false,
							'list'      => true,
							'condition' => array( $key . '_processing_data_lawfull' => '10' ),
						),
					);
			}

			$purpose_elements += array(
					$key . '_retain_data' => array(
						'subtitle'  => __( 'Retention period', 'complianz-gdpr' ),
						'list'      => true,
						'numbering' => false,
						'condition' => array(
							'purpose_personaldata' => $key
						),
					),

					$key . '_retain_until_terminated' => array(
						'numbering' => false,
						'list'      => true,
						'content'   => __( "We retain this data until the service is terminated.", 'complianz-gdpr' ),
						'condition' => array( $key . '_retain_data' => '1' ),
					),

					$key . '_retain_until_terminated_nr_months' => array(
						'numbering' => false,
						'list'      => true,
						'content'   => cmplz_sprintf( __( "We retain this data upon termination of the service for the following number of months: %s", 'complianz-gdpr' ),
							'[' . $key . '_retention_period_months]' ),
						'condition' => array( $key . '_retain_data' => '2' ),
					),

					$key . '_retain_until_terminated_period' => array(
						'numbering' => false,
						'content'   => cmplz_sprintf( __( "Upon termination of the service we retain this data for the following period: %s.", 'complianz-gdpr' ), '[' . $key . '_retain_wmy]' ),
						'condition' => array( $key . '_retain_data' => '3' ),
					),

					$key . '_description_criteria_retention' => array(
						'numbering' => false,
						'content'   => cmplz_sprintf( __( 'We determine the retention period according to fixed objective criteria: %s', 'complianz-gdpr' ),
							'[' . $key . '_description_criteria_retention]' ),
						'condition' => array( $key . '_retain_data' => '4' ),
					),
					$key . '_dropdown_close'                 => array(
						'dropdown-close' => true,
						'condition'      => array( 'purpose_personaldata' => $key ),
					),
				);
		}
		if ( $region === 'eu' ) {
			if ( count( $purpose_elements ) > 1 ) {
				$elements = array_slice( $elements, 0, 2, true ) + $purpose_elements +
				            array_slice( $elements, 2, count( $elements ) - 2, true );
			}
		}


		//UK
		if ( $region === 'uk' ) {
			if ( count( $purpose_elements ) > 1 ) {
				$elements = array_slice( $elements, 0, 2, true ) + $purpose_elements +
				            array_slice( $elements, 2, count( $elements ) - 2, true );
			}
		}

		//ZA
		if ( $region === 'za' ) {
			if ( count( $purpose_elements ) > 1 ) {
				$elements = array_slice( $elements, 0, 2, true ) + $purpose_elements +
				            array_slice( $elements, 2, count( $elements ) - 2, true );
			}
		}

		//BR
		if ( $region === 'br' ) {
			if ( count( $purpose_elements ) > 1 ) {
				$elements = array_slice( $elements, 0, 2, true ) + $purpose_elements +
				            array_slice( $elements, 2, count( $elements ) - 2, true );
			}
		}
	}

	if ( ( $region === 'us' || $region === 'ca' || $region === 'au' ) && $type === 'privacy-statement' ) {

		$purpose_elements_us = array(
			'purpose' => array(
				'numbering' => true,
				'title'     => __( "Purpose and categories of data", "complianz-gdpr" ),
				'content'   => __( 'We may collect or receive personal information for a number of purposes connected with our business operations which may include the following: (click to expand)',
						"complianz-gdpr" ) . '</b>',
			),
		);
		foreach ( $purposes as $key ) {
			//a key might not exist if we just disabled US, and had selected an option which is not available in the EU.
			if ( ! isset( $fields[$purpose_personal_data_index]['options'][ $key ] ) ) {
				continue;
			}

			$label               = $fields[$purpose_personal_data_index]['options'][ $key ];
			$purpose_elements_us += array(
				$key . '_us_title' => array(
					'dropdown-open'  => true,
					'dropdown-title' => $label,
					'dropdown-class' => 'dropdown-privacy-statement',
					'condition'      => array( 'purpose_personaldata' => $key ),
				)
			);
			// For the australian law, the user needs to describe the law and we need to add that description to purpose.
			if ( ( $key === 'legal-obligations' && $region === 'au' ) ) {
				$purpose_elements_us += array(
					$key . '_us_legal_obligation_explained' => array(
						'subtitle'  => __( 'The collection is required or authorized by the following law or court/tribunal order:', "complianz-gdpr" ),
						'content'   => '[legal-obligations-description]',
						'numbering' => false,
						'class'     => 'legal-obligations-description',
						'condition' => array( 'purpose_personaldata' => $key )
					),
				);
			}
			$purpose_elements_us += array(
				$key . '_us_categories'                     => array(
					'subtitle'  => __( 'The following categories of data are collected', "complianz-gdpr" ),
					'content'   => '[' . $key . '_data_purpose_us]',
					'numbering' => false,
					'condition' => array( 'purpose_personaldata' => $key )
				),
				$key . '_retain_data'                       => array(
					'subtitle'  => __( 'Retention period', 'complianz-gdpr' ),
					'list'      => true,
					'numbering' => false,
					'condition' => array(
						'purpose_personaldata' => $key
					),
				),
				$key . '_retain_until_terminated'           => array(
					'numbering' => false,
					'list'      => true,
					'content'   => __( "We retain this data until the service is terminated.", 'complianz-gdpr' ),
					'condition' => array( $key . '_retain_data' => '1' ),
				),
				$key . '_retain_until_terminated_nr_months' => array(
					'numbering' => false,
					'list'      => true,
					'content'   => cmplz_sprintf( __( "We retain this data upon termination of the service for the following number of months: %s", 'complianz-gdpr' ),
						'[' . $key . '_retention_period_months]' ),
					'condition' => array( $key . '_retain_data' => '2' ),
				),
				$key . '_retain_until_terminated_period'    => array(
					'numbering' => false,
					'content'   => cmplz_sprintf( __( "Upon termination of the service we retain this data for the following period: %s.", 'complianz-gdpr' ), '[' . $key . '_retain_wmy]' ),
					'condition' => array( $key . '_retain_data' => '3' ),
				),
				$key . '_description_criteria_retention'    => array(
					'numbering' => false,
					'content'   => cmplz_sprintf( __( 'We determine the retention period according to fixed objective criteria: %s', 'complianz-gdpr' ),
						'[' . $key . '_description_criteria_retention]' ),
					'condition' => array( $key . '_retain_data' => '4' ),
				),
				$key . '_dropdown_close'                    => array(
					'dropdown-close' => true,
					'condition'      => array( 'purpose_personaldata' => $key ),
				),
			);
		}

		if ( count( $purpose_elements_us ) > 1 ) {
			$elements = array_slice( $elements, 0, 2, true ) + $purpose_elements_us +
			            array_slice( $elements, 2, count( $elements ) - 2, true );
		}
	}

	return $elements;
}

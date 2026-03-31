<?php
defined('ABSPATH') or die("you do not have access to this page!");
$questions = array(

				array(
					'id' => 'type-of-dataloss-'. $region,
					'type' => 'radio',
					'default' => '',
					'required' => true,
					'label' => __("Which situation applies to the incident.", 'complianz-gdpr'),
					'options' => array(
						'1' => __('Personal data has been lost, and there is no up to date back-up', 'complianz-gdpr'),
						'2' => __('It can not be excluded that unauthorized persons have gained access to personal data', 'complianz-gdpr'),
						'3' => __('The above alternatives do not apply.', 'complianz-gdpr'),
					),
				),
				array(
					'id' =>'risk-of-data-loss-'. $regionn,
					'type' => 'radio',
					'required' => true,
					'options' => array(
						'1' => __("There is a real risk of significant harm, due to the probability that the personal information has been, is being or will be misused.", 'complianz-gdpr'),
						'2' => __("The data breach applies to (some) personal data that may be sensitive.", 'complianz-gdpr'),
						'3' => __("The data has been encrypted in such a way that it is not possible to abuse the data", 'complianz-gdpr'),
						'4' => __("The possible consequences have been minimized immediately, which effectively excludes the possibility of abuse by malicious parties", 'complianz-gdpr'),
					),
					'default' => '',
					'label' => __("What information was involved?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
						]
					],
				),
			);
		if ($region === 'ca'){
			$questions += array(
				 array(
					'id' =>'can-reduce-risk-' . $region,
					'type'               => 'radio',
					'required'           => true,
					'options'            => COMPLIANZ::$config->yes_no,
					'default'            => '',
					'label'              => __( "Do you think any other organization, a government institution or a part of a government institution may be able to reduce the risk of harm from the breach or to mitigate that harm?", 'complianz-gdpr' ),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],

				),
			);
		}
	$questions += array(
				 array(
					'id' =>'what-occurred-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("What has occurred exactly?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],

				),
				 array(
					'id' =>'consequences-'. $region,
					'type' => 'text',
					'translatable' => false,
					'required' => true,
					'default' => '',
					'label' => __("What are the possible consequences?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],
				),
				array(
					'id' =>'measures-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("What measures have been taken after the breach?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],
				),
				 array(
					'id' =>'measures_by_person_involved-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("What measures could a person involved take to minimize damage?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],
				),
				 array(
					'id' =>'date-of-breach-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("Day on which, or period during which the breach occurred, or if neither is known, the approximate period.", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],
				),
				 array(
					'id' => 'phone-url-inquiries-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("Through which email address can customers make inquiries about your system?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!risk-of-data-loss-' . $region  => ['3','4'],
						]
					],
				),
			);

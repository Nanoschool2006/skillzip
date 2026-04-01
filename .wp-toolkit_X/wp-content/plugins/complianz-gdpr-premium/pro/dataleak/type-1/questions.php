<?php
defined('ABSPATH') or die("you do not have access to this page!");
$questions = array(

				 array(
					 'id' =>'type-of-dataloss-'. $region,
					'type' => 'radio',
					'default' => '',
					'required' => true,
					'label' => __("Which situation applies to the incident.", 'complianz-gdpr'),
					'options' => array(
						'1' => __('Personal data is lost, without a recent copy or back-up.', 'complianz-gdpr'),
						'2' => __('It can not be excluded that unauthorized persons have gained access to the personal data', 'complianz-gdpr'),
						'3' => __('The above alternatives do not apply.', 'complianz-gdpr'),
					),
				),

				 array(
					'id' =>'reach-of-dataloss-'. $region,
					'default' => '',
					'type' => 'radio',
					'required' => true,
					'label' => __("Which situation applies to the incident.", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
						]
					],
					'options' => array(
						'1' => __('The data breach concerns more than 50 people.', 'complianz-gdpr'),
						'2' => __('The data breach concerns sensitive personal data.', 'complianz-gdpr'),
						'3' => __('The above alternatives do not apply.', 'complianz-gdpr'),
					),
				),

				array(
					'id' =>'risk-of-data-loss-'. $region,
					'default' => '',
					'type' => 'radio',
					'required' => true,
					'label' => __("Risk of dataloss", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
						]
					],
					'options' => array(
						'1' => __('The data is encrypted in such a way that the data cannot be used in any way', 'complianz-gdpr'),
						'2' => __('Usage of the personal data is reduced or excluded directly after the breach to minimize damage.', 'complianz-gdpr'),
						'3' => __('The breached data presents a high risk for those involved.', 'complianz-gdpr'),
					),
				),
				array(
					'id' => 'what-occurred-'. $region,
					'type' => 'text',
					'translatable' => true,
					'default' => '',
					'required' => true,
					'label' => __("What has occurred exactly?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
							'risk-of-data-loss-' . $region  => '3'
						]
					],
				),
				 array(
					'id' => 'consequences-'. $region,
					'type' => 'text',
					'translatable' => true,
					'required' => true,
					'default' => '',
					'label' => __("What are the possible consequences?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
							'risk-of-data-loss-' . $region  => '3'
						]
					],
				),
				 array(
					'id' => 'measures-'. $region,
					'type'             => 'text',
					'translatable'     => true,
					'default'          => '',
					'required'         => true,
					'label'            => __( "What measures have been taken after the breach?", 'complianz-gdpr' ),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
							'risk-of-data-loss-' . $region  => '3'
						]
					],
				),
				array(
					'id' =>'measures_by_person_involved-' . $region,
					'type'             => 'text',
					'translatable'     => true,
					'default'          => '',
					'required'         => true,
					'label'            => __( "What measures could a person involved take to minimize damage?", 'complianz-gdpr' ),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
							'risk-of-data-loss-' . $region  => '3'
						]
					],
				),

);

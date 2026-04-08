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
						'1' => __('Encrypted personal data is lost, and it cannot be excluded that unauthorized persons have access to the encryption key or password.', 'complianz-gdpr'),
						'2' => __('It can not be excluded that unauthorized persons have gained access to unencrypted personal data', 'complianz-gdpr'),
						'3' => __('The above alternatives do not apply.', 'complianz-gdpr'),
					),
				),
				array(
					'id' =>'what-information-was-involved-'. $region,
					'type' => 'radio',
					'required' => true,
					'options' => array(
						'name' => __("An individual’s first name or first initial and last name in combination with any one or more of the data elements (as shown in the next question after selecting this option), when either the name or the data elements are not encrypted", 'complianz-gdpr'),
						'username-email' => __("A user name or email address, in combination with a password or security question and answer that would permit access to an online account.", 'complianz-gdpr'),
						'none' => __("None of the above", 'complianz-gdpr'),
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
				 array(
					'id' =>'name-what-'. $region,
					'type' => 'multicheckbox',
					'required' => true,
					'options' => array(
						'social-security-number' => __("Social security number", 'complianz-gdpr'),
						'drivers-license' => __("Driver’s license number or identification card number", 'complianz-gdpr'),
						'account-number' => __("Account number or credit or debit card number, in combination with any required security code, access code, or password that would permit access to an individual’s financial account", 'complianz-gdpr'),
						'medical-info' => __("Medical information", 'complianz-gdpr'),
						'health-insurance' => __("Health insurance information", 'complianz-gdpr'),
						'data-collected' => __("Information or data collected through the use or operation of an automated license plate recognition system", 'complianz-gdpr'),
					),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'what-information-was-involved-'. $region => 'name'
						]
					],
					'default' => '',
					'label' => __("Data elements involved in the security breach:", 'complianz-gdpr'),
				),
				 array(
					'id' =>'toll-free-phone',
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("Please enter the toll-free telephone number and addresses of the major credit reporting agencies:", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => '3',
							'name-what-'. $region => 'social-security-number OR drivers-license',
						]
					],
				),
				 array(
					'id' =>'reach-of-dataloss-large-'. $region,

					'type' => 'radio',
					'default' => '',
					'required' => true,
					'label' => __("Does the security breach affect a large number (500 or more) of people?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-' . $region => 'none',
							'what-information-was-involved-'. $region => 'name OR username-email',
						]
					],
					'options' => COMPLIANZ::$config->yes_no,
				),
				 array(
					'id' =>'california-visitors',
					'type' => 'radio',
					'default' => '',
					'required' => true,
					'label' => __("Does the databreach affect California residents?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!what-information-was-involved-'. $region => 'none',
							'reach-of-dataloss-large-'. $region => 'yes'
						]
					],
					'options' => COMPLIANZ::$config->yes_no,
				),
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
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
						]
					],
				),
				 array(
					'id' => 'consequences-'. $region,
					'type' => 'text',
					'translatable' => false,
					'required' => true,
					'default' => '',
					'label' => __("What are the possible consequences?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
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
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
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
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
						]
					],
				),
				  array(
					'id' =>'date-of-breach-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("What is the date, the approximate date, or the date range within which the security breach has occurred?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
						]
					],
				),
				 array(
					'id' =>'investigation',
					'type' => 'radio',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("Was the notification delayed as a result of a law enforcement investigation?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
						]
					],
					'options' => COMPLIANZ::$config->yes_no,
				),
				 array(
					'id' =>'phone-url-inquiries-'. $region,
					'type' => 'text',
					'translatable' => false,
					'default' => '',
					'required' => true,
					'label' => __("Through which phone number, or which URL, can customers make inquiries about this security breach?", 'complianz-gdpr'),
					'react_conditions' => [
						'relation' => 'AND',
						[
							'!type-of-dataloss-' . $region  => '3',
							'!reach-of-dataloss-'. $region => '3',
							'!what-information-was-involved-'. $region => 'none',
						]
					],
				),

			);


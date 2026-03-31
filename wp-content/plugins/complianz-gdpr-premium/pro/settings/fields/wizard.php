<?php
defined('ABSPATH') or die();

/**
 * For saving purposes, types should be overridden at the earliest moment
 *
 * @param array $fields
 *
 * @return array
 */
function cmplz_add_pro_wizard_fields( array $fields): array {
	return array_merge($fields,  [
		[
			'id'       => 'free_phonenr',
			'menu_id'  => 'website-information',
			'type' => 'phone',
			'default' => '',
			'required' => false,
			'label' => __("Enter a toll free phone number for the submission of information requests", 'complianz-gdpr'),
			'document_label' => 'Toll free phone number: ',
			'react_conditions' => [
				'relation' => 'AND',
				[
					'regions' => ['us'],
				]
			],
			'help' => [
				'label' => 'default',
				'title' => __( "A toll free phone number", 'complianz-gdpr' ),
				'text'  => __('For US based companies, you can provide a toll free phone number for inquiries.','complianz-gdpr'),
				'url'   => 'https://complianz.io/toll-free-phone-number-ccpa/',
			],
		]]
	);
}
add_filter('cmplz_fields', 'cmplz_add_pro_wizard_fields', 20, 1);

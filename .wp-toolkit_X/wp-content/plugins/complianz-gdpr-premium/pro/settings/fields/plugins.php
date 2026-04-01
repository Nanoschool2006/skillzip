<?php
defined( 'ABSPATH' ) or die();

function cmplz_add_plugins_fields( array $fields ): array {
	return array_merge( $fields, [
		[
			'id'               => 'wp_privacy_policies',
			'menu_id'          => 'plugins',
			'type'          => 'plugins_privacy_statements',
			'label' => __('Annex of your Privacy Statement', "complianz-gdpr"),
			'required' => false,
			'help'     => [
				'label' => 'default',
				'title' => __( "Adjusting of texts", 'complianz-gdpr' ),
				'text'  => __( 'Please note that you should customize these texts for your website: the text should generally not be copied as is.', "complianz-gdpr" ),
			],
			'comment' =>__('Plugins and themes can add their own suggested privacy paragraphs here. You can choose to add these to the Annex of your Privacy Statement.', 'complianz-gdpr') .
			            " " . __('You can also add additional custom texts to the Annex of your Privacy Statement if you like.', 'complianz-gdpr'),
		],
		[
			'id'               => 'custom_privacy_policy_text',
			'menu_id'          => 'plugins',
			'translatable' => true,
			'type' => 'editor',
			'required' => false,
			'react_conditions' => [
				'relation' => 'AND',
				[
					'privacy-statement' => 'generated',
				]
			],
		],

	] );
}

add_filter( 'cmplz_fields', 'cmplz_add_plugins_fields', 200, 1 );

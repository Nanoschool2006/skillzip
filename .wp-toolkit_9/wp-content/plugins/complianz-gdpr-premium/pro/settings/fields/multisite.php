<?php
defined( 'ABSPATH' ) or die();

add_filter( 'cmplz_fields', 'cmplz_multisite_fields', 100 );

function cmplz_multisite_fields( $fields ) {
	if (!is_multisite()) {
		return $fields;
	}

	if (is_multisite() && !is_main_site()) {
		return $fields;
	}

	return array_merge( $fields, [
		[
			'id'          => 'copy-multisite',
			'type'        => 'copy-multisite',
			'menu_id'     => 'tools-multisite',
			'label'       => __( "Copy settings to subsites", 'complianz-gdpr' ),
			'tooltip'     => __( 'This will overwrite the Complianz settings in all your subsites with the settings of the current site.', 'complianz-gdpr' ),
		],
	] );
}

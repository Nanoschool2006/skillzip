<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-quiz-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

$image_settings    = new TIE_Image_Settings( 0 );
$settings          = $image_settings->get_data();
$settings['fonts'] = array(
	'Permanent Marker' => '//fonts.bunny.net/css?family=Permanent Marker',
	'Lato'             => '//fonts.bunny.net/css?family=Lato',
	'Roboto' => '//fonts.bunny.net/css?family=Roboto',
);

return array(
	'name'     => __( 'Dynamic', 'thrive-image-editor' ),
	'settings' => $settings
);

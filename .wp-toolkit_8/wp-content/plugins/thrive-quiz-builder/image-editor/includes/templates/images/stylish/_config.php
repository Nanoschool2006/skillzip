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
	'Cabin' => '//fonts.bunny.net/css?family=Cabin',
	'Roboto' => '//fonts.bunny.net/css?family=Roboto',
);
return array(
	'name'     => __( 'Stylish', 'thrive-image-editor' ),
	'settings' => $settings,
);

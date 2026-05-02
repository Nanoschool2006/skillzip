<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Persist the default newCourseWelcome email template to the database
 * so that check_templates_for_trigger() can find it when sending emails.
 *
 * We avoid using the TVA_Email_Templates singleton here to prevent stale
 * cached data in the same request after the option is updated.
 */

/** @var $this TD_DB_Migration */

$option_key    = 'tva_email_templates';
$template_slug = 'newCourseWelcome';
$templates     = get_option( $option_key, array() );

if ( empty( $templates[ $template_slug ] ) ) {
	ob_start();
	include TVA_Const::plugin_path( '/admin/views/template/emailTemplates/bodies/' ) . $template_slug . '.phtml';
	$body = ob_get_clean();

	$templates[ $template_slug ] = array(
		'subject'   => 'Welcome to [course_name]! Get Started Now',
		'from_name' => get_bloginfo( 'name' ),
		'body'      => $body,
		'triggers'  => array( 'new_course_welcome' ),
	);

	update_option( $option_key, $templates );
}

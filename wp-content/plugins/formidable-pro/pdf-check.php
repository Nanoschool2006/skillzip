<?php
/**
 * This script is used to test if Imagick is able to read a PDF file.
 * If this segfaults, we know to disable PDF thumbnails.
 *
 * @since 6.27
 */

if ( php_sapi_name() !== 'cli' ) {
	// This file should only be run from the command line (using exec).
	echo 'You are not allowed to call this page directly.';
	exit;
}

if ( ! class_exists( 'Imagick' ) ) {
	echo 'IMAGICK NOT FOUND';
	exit;
}

if ( empty( $argv[1] ) ) {
	echo 'NO FILE PATH PROVIDED';
	exit;
}

$file = $argv[1];

if ( ! file_exists( $file ) ) {
	echo 'FILE NOT FOUND';
	exit;
}

if ( ! function_exists( 'mime_content_type' ) ) {
	echo 'MIME CONTENT TYPE NOT FOUND';
	exit;
}

$mime_type = mime_content_type( $file );

if ( 'application/pdf' !== $mime_type ) {
	echo 'FILE IS NOT A PDF';
	exit;
}

$im = new Imagick();
$im->setResourceLimit( 6, 1 ); // Limit threads to 1
$im->readImage( $file . '[0]' );
echo 'SUCCESS';
exit;

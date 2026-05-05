<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 7/24/2017
 * Time: 9:50 AM
 */


$post_id = ! empty( $_GET['tqb_redirect_post_id'] ) ? (int) $_GET['tqb_redirect_post_id'] : 0;
$quiz_id = ! empty( $_GET['tqb_quiz_id'] ) ? (int) $_GET['tqb_quiz_id'] : 0;

if ( empty( $post_id ) || empty( $quiz_id ) ) {
	exit( 'Invalid Post' );
}

$image_url_raw   = isset( $_GET['image_url'] ) ? sanitize_text_field( wp_unslash( $_GET['image_url'] ) ) : '';
$description_raw = isset( $_GET['description'] ) ? sanitize_text_field( wp_unslash( $_GET['description'] ) ) : '';

// URL decode the parameters (they come encoded from JavaScript)
$image_url   = urldecode( $image_url_raw );
$description = urldecode( $description_raw );

// Ensure image URL is absolute (Twitter requires absolute URLs)
if ( ! empty( $image_url ) && ! preg_match( '/^https?:\/\//', $image_url ) ) {
	$image_url = site_url( $image_url );
}

// Fallback to default image if empty
if ( empty( $image_url ) ) {
	$image_url = tqb()->plugin_url( 'tcb-bridge/assets/images/share-badge-default.png' );
}

// Ensure description is not empty
if ( empty( $description ) ) {
	$description = get_the_title( $post_id );
}

$site_url        = site_url( '?post_type=' . Thrive_Quiz_Builder::SHORTCODE_NAME . '&tqb_quiz_id=' . $quiz_id . '&tqb_redirect_post_id=' . $post_id . '&image_url=' . urlencode( $image_url ) . '&description=' . urlencode( $description ) );
$facebook_app_id = get_option( 'tve_social_fb_app_id', '' );
$is_https        = is_ssl() || ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' );
?>

<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<meta name="robots" content="noindex, nofollow"/>
	<title>Quiz Page</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>

	<!-- Open Graph Meta Tags -->
	<meta property="og:url" content="<?php echo esc_url( $site_url ); ?>"/>
	<meta property="og:type" content="article"/>
	<meta property="og:title" content="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"/>
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>"/>
	<meta property="og:image" content="<?php echo esc_url( $image_url ); ?>"/>
	<?php if ( $is_https ) : ?>
	<meta property="og:image:secure_url" content="<?php echo esc_url( $image_url ); ?>"/>
	<?php endif; ?>
	<meta property="og:image:width" content="1200"/>
	<meta property="og:image:height" content="628"/>
	<meta property="og:image:type" content="image/png"/>
	<?php if ( ! empty( $facebook_app_id ) ) : ?>
	<meta property="fb:app_id" content="<?php echo esc_attr( $facebook_app_id ); ?>"/>
	<?php endif; ?>
	
	<!-- Twitter Card Meta Tags -->
	<meta name="twitter:card" content="summary_large_image"/>
	<meta name="twitter:title" content="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"/>
	<meta name="twitter:description" content="<?php echo esc_attr( $description ); ?>"/>
	<meta name="twitter:image" content="<?php echo esc_url( $image_url ); ?>"/>
	<meta name="twitter:image:alt" content="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"/>

	<?php //wp_head(); BECAUSE OF YOAST ?>
</head>
<body>
<?php do_action( 'get_footer' ); ?>
<?php wp_footer(); ?>
</body>
</html>

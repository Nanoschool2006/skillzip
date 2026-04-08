<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$settings = FrmLandingAppHelper::get_landing_page_styles( get_the_ID() );

add_filter( 'body_class', 'FrmLandingAppController::add_image_body_class' );

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<style>
		body {
			<?php if ( $settings['bg_image'] ) { ?>
			--bg-image-url: url('<?php echo esc_url( wp_get_attachment_url( $settings['bg_image'] ) ); ?>');
			--bg-image-opacity: <?php echo esc_attr( $settings['opacity'] ); ?>;
			<?php } ?>
			--fieldset-color: <?php echo esc_attr( $settings['style_settings']['fieldset_color'] ); ?>;
			--border-radius: <?php echo esc_attr( $settings['style_settings']['border_radius'] ); ?>;
			--fieldset-bg-color: <?php echo esc_attr( 'transparent' === $settings['style_settings']['fieldset_bg_color'] ? '#fff' : $settings['style_settings']['fieldset_bg_color'] ); ?>;
			--fieldset-padding: <?php echo esc_attr( $settings['style_settings']['fieldset_padding'] ); ?>;
		}
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php
	if ( is_callable( 'twentynineteen_setup' ) ) {
		?>
	<div class="site-branding frm_hidden"></div>
		<?php
	}
	?>
	<div class="frm_image_container <?php echo esc_attr( $settings['bg_image'] ? '' : 'frm_no_image' ); ?>" style="--fieldset-bg-color:<?php echo esc_attr( $settings['style_settings']['date_band_color'] ); ?>">
		<?php
		if ( ! $settings['bg_image'] ) {
			$icon = get_site_icon_url();
			if ( $icon ) {
				echo '<img src="' . esc_url( $icon ) . '" alt="' . esc_attr( get_bloginfo( 'name' ) ) . '" class="frm-site-logo" />';
			}
		}
		?>
	</div>
	<div class="container">
		<?php the_content(); ?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>

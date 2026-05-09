<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

$icon_content = ''; // Default empty icon content.

$has_icon = ! empty( $attr['icon-markup'] );

if ( $has_icon ) {
	$raw_markup = wp_unslash( $attr['icon-markup'] );

	$decoded_markup = html_entity_decode( $raw_markup, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	$icon_content = Thrive_Shortcodes::before_wrap( [
		'content' => $decoded_markup,
		'class'   => 'thrv_icon tve_no_drag tve_no_icons tcb-icon-inherit-style tcb-icon-display',
	], [
		'tcb-elem-type' => 'dynamic-list-icon',
		'selector'      => '.dynamic-list-icon .thrv_icon',
	] );
}

?>

<ul class="theme-dynamic-list">
	<?php foreach ( $attr['items'] as $item ) : ?>
		<li class="thrive-dynamic-styled-list-item<?php echo $has_icon ? ' dynamic-item-with-icon' : ''; ?> tve_no_icons" data-selector=".thrive-dynamic-styled-list-item">
			<div class="tcb-styled-list-icon">
				<?php if ( $has_icon ) : ?>
					<span class="dynamic-list-icon">
						<?php echo wp_kses_post( $icon_content ); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="thrive-dynamic-styled-list-text" data-selector=".thrive-dynamic-styled-list-text a">
				<a class="tcb-plain-text" href="<?php echo $item['url']; ?>"><?php echo $item['name']; ?></a>
			</div>
		</li>
	<?php endforeach; ?>
</ul>

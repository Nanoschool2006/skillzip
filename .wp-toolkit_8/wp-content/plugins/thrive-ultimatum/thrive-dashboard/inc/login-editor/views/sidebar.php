<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

?>

<a href="javascript:void(0)" class="sidebar-item click" data-fn="resetDesign" data-position="left" data-tooltip="<?php echo esc_attr__( 'Reset design', 'thrive-dash' ); ?>">
	<?php tcb_icon( 'template-reset', false, 'sidebar', '' ); ?>
	<span class="sidebar-label"><?php echo esc_html__( 'Reset Design', 'thrive-dash' ); ?></span>
</a>
<a href="javascript:void(0)" data-position="left" class="sidebar-item click" data-fn="loginCloudTemplates" data-tooltip="<?php echo esc_attr__( 'Cloud Templates', 'thrive-dash' ); ?>">
	<?php tcb_icon( 'cloud-download-light' ); ?>
	<span class="sidebar-label"><?php echo esc_html__( 'Cloud Templates', 'thrive-dash' ); ?></span>
</a>

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div id="tve-tab_item-component" class="tve-component" data-view="TabItem">
	<div class="dropdown-header" data-prop="docked">
		<div class="group-description">
			<?php esc_html_e( 'Main Options', 'thrive-cb' ); ?>
		</div>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="tve-control pt-12 pb-12" data-key="TabItemType" data-view="ButtonGroup"></div>
		<div class="tve-tab-icon-img-controls tcb-hidden">
			<div class="tve-tab-icon-control">
				<div class="tve-control" data-view="ModalPicker"></div>
				<div class="tve-icon-prop-control pt-10">
					<div class="tve-control no-space" data-view="ColorPicker"></div>
					<div class="tve-control pt-10 pb-10" data-view="Slider"></div>
				</div>
			</div>
			<hr />
			<div class="tve-control" data-view="HasImage"></div>
			<div class="tve-tab-icon-position tcb-hidden">
				<span class="label"><?php esc_html_e( 'Icon / Image Position', 'thrive-cb' ); ?></span>
				<div class="tve-control mt-10" data-extends="ButtonGroup" data-view="IconPosition"></div>
			</div>
		</div>
		<hr />
		<div class="tve-tab-text-controls tcb-hidden">
			<div class="tve-control" data-view="TextTypeDropdown"></div>
			<div class="tve-control tcb-tab-item-secondary-text-control mt-5" data-key="SecondaryText" data-extends="Switch"></div>
			<div class="tve-control" data-view="FontColor"></div>
			<div class="tve-control hide-states" data-view="FontSize"></div>
		</div>

	</div>
</div>

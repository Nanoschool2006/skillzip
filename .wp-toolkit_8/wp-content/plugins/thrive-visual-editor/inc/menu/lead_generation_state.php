<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div id="tve-lead_generation_state-component" class="tve-component" data-view="LeadGenerationState">
	<div class="dropdown-header" data-prop="docked">
		<?php echo esc_html__( 'Main Options', 'thrive-cb' ); ?>
		<i></i>
	</div>
	<div class="dropdown-content">
		<div class="tve-control gl-st-button-toggle-1" data-view="DropdownPalettes"></div>
		<div class="tve-control tve-style-options no-space preview" data-view="StyleChange"></div>
		<div class="tve-control" data-key="SelectStylePicker" data-initializer="selectStylePicker"></div>
		<hr>
		<div class="tve-control" data-view="ShowLabel"></div>
		<div class="tve-control" data-key="Required" data-view="Checkbox"></div>
		<div class="tve-control mt-10" data-view="RowsWhenOpen"></div>
		<div class="tve-control" data-view="Placeholder"></div>
		<div class="tve-control" data-view="PlaceholderInput"></div>
		<div class="tve-control" data-key="DropdownIcon" data-initializer="dropdownIcon"></div>
		<div class="tve-control if-not-hamburger" data-view="DropdownAnimation"></div>
		<div class="tve-control" data-view="AnswerTag"></div>
	</div>
</div>
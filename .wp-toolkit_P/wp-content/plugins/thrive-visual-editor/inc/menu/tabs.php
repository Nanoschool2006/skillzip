<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
} ?>

<div id="tve-tabs-component" class="tve-component" data-view="TabsElement">
	<div class="action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo esc_html__( 'Main Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tcb-text-center mb-10 mr-5 ml-5">
				<button class="tve-button orange click" data-fn="editElement">
					<?php echo esc_html__( 'Edit Tab Items', 'thrive-cb' ); ?>
				</button>
			</div>
			<div class="tve-control hide-states" data-view="TabPalettes"></div>
			
			<div class="tve-control hide-tablet hide-mobile" data-key="TagsPosition" data-view="ButtonGroup"></div>
			<div class="tve-control tcb-button-tags-vertical-position hide-states hide-tablet hide-mobile" data-key="TagsAlign" data-view="ButtonGroup"></div>
			<div class="tve-control mt-5" data-key="WrapToggleViewBreakpoint" data-extends="Switch"></div>
			<div class="tve-control tcb-toggle-view-breakpoint-width-control tcb-hidden" data-view="WrapToggleViewBreakpointWidth"></div>
			<hr>
			<div class="tve-control tve-tab-dynamic hide-tablet hide-mobile" data-view="TagsSize"></div>
			<div class="tve-control tcb-tags-max-width-control tve-tab-dynamic hide-tablet hide-mobile tcb-hidden" data-view="TagsMaxWidth"></div>
			<div class="tve-control tcb-tags-fixed-size-control tve-tab-dynamic hide-tablet hide-mobile tcb-hidden" data-view="TagsFixedSize"></div>
			<div class="tve-control" data-view="SpacingBetweenTagsContent"></div>
			<hr>
			<div class="tve-control btn-group-light no-space hide-tablet hide-mobile" data-view="TagsSpacing"></div>
			<div class="spacing">
				<div class="tve-control hide-tablet hide-mobile" data-view="HorizontalSpacing"></div>
				<div class="tve-control hide-tablet hide-mobile" data-view="VerticalSpacing"></div>
				<div class="tve-control pb-10 hide-tablet hide-mobile" data-view="BetweenSpacing"></div>
			</div>
			<hr>
			<div class="tve-control tve-tab-type-control hide-tablet hide-mobile" data-view="TabType"></div>
			<div class="tve-control tve-type-control tve-tab-static hide-tablet hide-mobile" data-view="DefaultTab"></div>
			<div class="tve-control tve-type-control tve-tab-dynamic hide-tablet hide-mobile" data-view="DynamicTabType"></div>
			<div class="tve-control tve-type-control tve-tab-dynamic hide-tablet hide-mobile" data-view="VariableName"></div>
			<div class="tve-type-control tve-tab-dynamic hide-tablet hide-mobile">
				<div class="control-grid tve-tab-variable-names">
					<p data-fn="toggleVariableNames" class="label click"><?php echo __( 'View variable names', 'thrive-cb' ); ?></p>			
					<div class="tve-tab-variable-model">
						<span class="variable-model-close click" data-fn="toggleVariableNames"><?php tcb_icon( 'close2' ); ?></span>
						<p class="title"><?php echo __( 'Expected variable name', 'thrive-cb' ); ?></p>
						<ul class="variable-names"></ul>
					</div>
				</div>
			</div>
			<div class="tve-control tve-type-control tve-tab-dynamic hide-tablet hide-mobile" data-view="FallbackValue"></div>
			<div class="tve-control hide-tablet hide-mobile hide-states" data-view="HoverEffect"></div>
			<div class="tve-control hide-tablet hide-mobile" data-view="ContentAnimation"></div>
			<div class="tve-control mt-5 hide-tablet hide-mobile" data-key="ProgressStyling" data-extends="Switch" data-label="<?php esc_attr_e( 'Enable Progress Styling', 'thrive-cb' ); ?>"></div>
		</div>
	</div>
</div>


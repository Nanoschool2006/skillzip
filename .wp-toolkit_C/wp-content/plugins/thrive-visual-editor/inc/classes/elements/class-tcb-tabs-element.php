<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TCB_Tabs_Element
 */
class TCB_Tabs_Element extends TCB_Cloud_Template_Element_Abstract {

	/**
	 * Name of the element
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Tabs', 'thrive-cb' );
	}

	/**
	 * Return icon class needed for display in menu
	 *
	 * @return string
	 */
	public function icon() {
		return 'tabs';
	}

	/**
	 * Tabs element identifier
	 *
	 * @return string
	 */
	public function identifier() {
		return '.thrv-tabbed-content.tve-tab-upgraded';
	}

	/**
	 * This element is not a placeholder
	 *
	 * @return bool|true
	 */
	public function is_placeholder() {
		return false;
	}
	/**
	 * Is this element hidden from the Thrive Architect UI
	 *
	 * @return boolean
	 */
	public function hide() {
		return false;
	}

	/**
	 * Component and control config
	 *
	 * @return array
	 */
	public function own_components() {
		return array_merge(
			array(
				'tabs'       => array(
					'config' => array(
						'TabPalettes'                   => array(
							'config'  => array(),
							'extends' => 'Palettes',
						),
						'ContentAnimation'              => array(
							'config'  => array(
								'name'    => __( 'Content Switch Animation', 'thrive-cb' ),
								'options' => array(
									array(
										'value' => 'appear',
										'name'  => __( 'Appear', 'thrive-cb' ),
									),
									array(
										'value' => 'slide-right',
										'name'  => __( 'From Right', 'thrive-cb' ),
									),
									array(
										'value' => 'slide-left',
										'name'  => __( 'From Left', 'thrive-cb' ),
									),
									array(
										'value' => 'slide-up',
										'name'  => __( 'From Top', 'thrive-cb' ),
									),
									array(
										'value' => 'slide-down',
										'name'  => __( 'From Bottom', 'thrive-cb' ),
									),
									array(
										'value' => 'carousel',
										'name'  => __( 'Carousel', 'thrive-cb' ),
									),
									array(
										'value' => 'smooth-resize',
										'name'  => __( 'Smooth Resize', 'thrive-cb' ),
									),
									array(
										'value' => 'swing-up',
										'name'  => __( 'Swing Up', 'thrive-cb' ),
									),
								),
							),
							'extends' => 'Select',
						),
						'HoverEffect'                   => array(
							'config'  => array(
								'name'    => __( 'Hover Effect', 'thrive-cb' ),
								'options' => array(
									''            => 'None',
									'c-underline' => 'Underline',
									'c-double'    => 'Double line',
									'c-brackets'  => 'Brackets',
									'c-thick'     => 'Thick Underline',
								),
							),
							'extends' => 'Select',
						),
						'TagsPosition'                  => array(
							'to'     => '.tve_scT',
							'config' => array(
								'name'    => __( 'Type', 'thrive-cb' ),
								'buttons' => array(
									array(
										'value'   => 'horizontal',
										'text'    => __( 'Horizontal', 'thrive-cb' ),
										'default' => true,
									),
									array(
										'value' => 'vertical',
										'text'  => __( 'Vertical', 'thrive-cb' ),
									),
								),
							),
						),
						'TagsAlign'                     => array(
							'to'     => '.tve_scT',
							'config' => array(
								'name'    => __( 'Tab Alignment', 'thrive-cb' ),
								'buttons' => array(
									array(
										'value'   => 'left',
										'text'    => __( 'Left', 'thrive-cb' ),
										'default' => true,
									),
									array(
										'value' => 'right',
										'text'  => __( 'Right', 'thrive-cb' ),
									),
								),
							),
						),
						'WrapToggleViewBreakpoint'      => array(
							'to'      => '.tve_scT',
							'config'  => array(
								'label' => __( 'Wrap to toggle view at breakpoint', 'thrive-cb' ),
							),
							'extends' => 'Switch',
						),
						'WrapToggleViewBreakpointWidth' => array(
							'config'  => array(
								'default' => '767',
								'min'     => '767',
								'max'     => '1920',
								'um'      => array( 'px' ),
							),
							'extends' => 'Slider',
						),
						'TagsSize'                      => array(
							'config'  => array(
								'name'    => __( 'Tabs size', 'thrive-cb' ),
								'default' => 'relative',
								'options' => array(
									array(
										'value'   => 'relative',
										'name'    => __( 'Relative', 'thrive-cb' ),
										'default' => true,
									),
									array(
										'value' => 'fixed',
										'name'  => __( 'Fixed', 'thrive-cb' ),
									),
								),
							),
							'extends' => 'Select',
						),
						'TagsMaxWidth'                  => array(
							'to'      => '.tve_scT',
							'config'  => array(
								'default' => 'none',
								'min'     => '100',
								'max'     => '500',
								'label'   => __( 'Maximum Width', 'thrive-cb' ),
								'um'      => array( 'px', 'em' ),
								'css'     => 'max-width',
							),
							'extends' => 'Slider',
						),
						'TagsFixedSize'                 => array(
							'to'      => '.tve_scT',
							'config'  => array(
								'default' => 'auto',
								'min'     => '100',
								'max'     => '500',
								'label'   => __( 'Fixed size', 'thrive-cb' ),
								'um'      => array( 'px', 'em' ),
								'css'     => 'width',
							),
							'extends' => 'Slider',
						),
						'SpacingBetweenTagsContent'     => array(
							'to'      => '.tve_scT',
							'config'  => array(
								'default' => '0',
								'min'     => '0',
								'max'     => '30',
								'label'   => __( 'Spacing Between Tab & Content', 'thrive-cb' ),
								'um'      => array( 'px', 'em' ),
								'css'     => 'gap',
							),
							'extends' => 'Slider',
						),
						'TagsSpacing'                   => array(
							'config'  => array(
								'name'       => __( 'Spacing', 'thrive-cb' ),
								'full-width' => true,
								'buttons'    => array(
									array(
										'value' => 'horizontal',
										'text'  => __( 'Horizontal', 'thrive-cb' ),
									),
									array(
										'value' => 'vertical',
										'text'  => __( 'Vertical', 'thrive-cb' ),
									),
									array(
										'value' => 'between',
										'text'  => __( 'Between', 'thrive-cb' ),
									),
								),
							),
							'extends' => 'Tabs',
						),
						'HorizontalSpacing'             => array(
							'to'      => 'ul',
							'config'  => array(
								'default' => '0',
								'min'     => '0',
								'max'     => '100',
								'label'   => '',
								'um'      => array( 'px' ),
							),
							'extends' => 'Slider',
						),
						'VerticalSpacing'               => array(
							'to'      => 'ul',
							'config'  => array(
								'default' => '0',
								'min'     => '0',
								'max'     => '100',
								'label'   => '',
								'um'      => array( 'px' ),
							),
							'extends' => 'Slider',
						),
						'BetweenSpacing'                => array(
							'to'      => 'ul',
							'config'  => array(
								'default' => '0',
								'min'     => '0',
								'max'     => '300',
								'label'   => '',
								'um'      => array( 'px' ),
							),
							'extends' => 'Slider',
						),
						'TabType'                       => array(
							'config'  => array(
								'css_suffix' => ' a',
								'name'       => __( 'Default Tab', 'thrive-cb' ),
								'buttons'    => array(
									array(
										'value'        => 'static',
										'icon'         => 'link',
										'default'      => true,
										'tooltip'      => __( 'Static', 'thrive-cb' ),
										'tooltip_side' => 'top',
									),
									array(
										'value'        => 'dynamic',
										'icon'         => 'database-regular',
										'tooltip'      => __( 'Dynamic', 'thrive-cb' ),
										'tooltip_side' => 'top',
									),
								),
							),
							'extends' => 'Tabs',
						),
						'DefaultTab'                    => array(
							'config'  => array(
								'name'    => '',
								'options' => array(),
							),
							'extends' => 'Select',
						),
						'DynamicTabType'                => array(
							'config'  => array(
								'name'    => '',
								'options' => array(
									array(
										'value' => 'url-query-string',
										'name'  => __( 'URL Query String', 'thrive-cb' ),
									),
									array(
										'value' => 'post-variable',
										'name'  => __( 'Post variable', 'thrive-cb' ),
									),
									array(
										'value' => 'cookie',
										'name'  => __( 'Cookie', 'thrive-cb' ),
									),
								),
							),
							'extends' => 'Select',
						),
						'VariableName'                  => array(
							'config'  => array(
								'label'       => __( 'Variable name', 'thrive-cb' ),
								'extra_attrs' => 'value="tab"',
							),
							'extends' => 'LabelInput',
						),
						'FallbackValue'                 => array(
							'config'  => array(
								'name'    => __( 'Fallback value', 'thrive-cb' ),
								'options' => array(),
							),
							'extends' => 'Select',
						),
					),
				),
				'typography' => array( 'hidden' => true ),
				'animation'  => array( 'hidden' => true ),
				'scroll'     => array(
					'hidden' => false,
				),
				'layout'     => array(
					'disabled_controls' => array(),
				),
			),
			$this->group_component()
		);
	}

	/**
	 * Element category that will be displayed in the sidebar
	 *
	 * @return string
	 */
	public function category() {
		return static::get_thrive_advanced_label();
	}

	/**
	 * Enable group editing on text elements from table cells
	 *
	 * @return array|bool
	 */
	public function has_group_editing() {
		return array(
			'select_values' => array(
				array(
					'value'    => 'all_items',
					'selector' => ' .tve_scT > ul > .tve_tab_title_item',
					'name'     => __( 'Grouped Tab Items', 'thrive-cb' ),
					/* translators: %s: number */
					'singular' => __( '-- Tab Item %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_items',
					'selector' => ' .tve_scT > .tve_tabs_toogle_view_section_tag',
					'name'     => __( 'Grouped Toggle View Tab Items', 'thrive-cb' ),
					/* translators: %s: number */
					'singular' => __( '-- Toggle View Tab Item %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_content',
					'selector' => ' .tve_scT > .tve_tab_content',
					'name'     => __( 'Grouped Tab Contents', 'thrive-cb' ),
					/* translators: %s: number */
					'singular' => __( '-- Tab Content %s', 'thrive-cb' ),
				),
			),
		);
	}

	/**
	 * Element info
	 *
	 * @return string|string[][]
	 */
	public function info() {
		return array(
			'instructions' => array(
				'type' => 'help',
				'url'  => 'tabs',
				'link' => 'https://help.thrivethemes.com/en/articles/4425806-how-to-use-the-tabs-element',
			),
		);
	}
}

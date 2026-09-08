<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class TCB_Lead_Generation_Element
 */
class TCB_Lead_Generation_Element extends TCB_Cloud_Template_Element_Abstract {

	/**
	 * @return string
	 */
	public function name() {
		return __( 'Lead Generation', 'thrive-cb' );
	}

	public function is_placeholder() {
		return false;
	}

	/**
	 * HTML layout of the element for when it's dragged in the canvas
	 *
	 * @return string
	 */
	public function html_placeholder( $title = null ) {
		if ( empty( $title ) ) {
			$title = $this->name();
		}

		return tcb_template( 'elements/element-placeholder', array(
			'icon'       => $this->icon(),
			'class'      => 'tcb-ct-placeholder',
			'title'      => $title,
			'extra_attr' => 'data-ct="' . $this->tag() . '-0" data-tcb-elem-type="' . $this->tag() . '" data-tcb-lg-type="' . $this->tag() . '" data-specific-modal="lead-generation"',
		), true );
	}

	/**
	 * Get element alternate
	 *
	 * @return string
	 */
	public function alternate() {
		return 'form';
	}

	/**
	 * @return string
	 */
	public function icon() {
		return 'lead_gen';
	}

	/**
	 * @return string
	 */
	public function identifier() {
		return '.thrv_lead_generation';
	}

	/**
	 * Get Thrive Spam Protection API keys from endpoint with transient caching
	 *
	 * @return array API keys or empty array on error
	 */
	private function get_thrive_spam_api_keys() {
		// Check transient first
		if ( false !== $keys = get_transient( 'tcb_thrive_spam_api_keys' ) ) {
			return $keys;
		}

		$endpoint = 'https://thrivethemesapi.com/api/secrets/v1/api_key_thrive_spam';
		
		$response = wp_remote_get( $endpoint, array( 
			'timeout' => 10,
			'sslverify' => true 
		) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( ! is_array( $data ) || 
			 ! isset( $data['success'] ) || 
			 ! $data['success'] || 
			 ! isset( $data['data']['value']['site_key'] ) ||
			 ! isset( $data['data']['value']['secret_key'] ) ) {
			return array();
		}
		
		$keys = array(
			'site_key'   => sanitize_text_field( $data['data']['value']['site_key'] ),
			'secret_key' => sanitize_text_field( $data['data']['value']['secret_key'] )
		);
		
		// Cache for 24 hours
		set_transient( 'tcb_thrive_spam_api_keys', $keys, 24 * HOUR_IN_SECONDS );
		
		return $keys;
	}

	/**
	 * @return array
	 */
	public function get_spam_prevention_tools() {
		$spam_prevention_tools = apply_filters( 'tcb_spam_prevention_tools', [] );
		$formatted_tools       = [];
		foreach ( $spam_prevention_tools as $tool ) {
			$credentials = Thrive_Dash_List_Manager::credentials( $tool );

			$disabled = 1;
			if ( ! empty( $credentials['site_key'] ) && ! empty( $credentials['secret_key'] ) ) {
				unset( $credentials['secret_key'] );
				$disabled = 0;
			}

			$config = [
				'value'         => $tool,
				'name'          => ucfirst( $tool ),
				'disabled'      => $disabled,
				'class'         => $disabled ? 'tve-disabled' : '',
				'tool_settings' => $credentials,
				'logo'          => $this->get_logo_url( $tool ),
			];

			$formatted_tools[ $tool ] = $config;
		}

		// Get API keys from endpoint
		$thrive_spam_keys = $this->get_thrive_spam_api_keys();
		
		$formatted_tools['thrive-sp'] = [
			'name'          => 'Thrive spam protection',
			'value'         => 'thrive-sp',
			'enabled'       => 0,
			'tool_settings' => ! empty( $thrive_spam_keys ) ? $thrive_spam_keys : array(
				'site_key'   => '',
				'secret_key' => '',
			),
			'logo'          => $this->get_logo_url( 'thrive-spam-protect' ),
		];

		$formatted_tools['disabled'] = [
			'name'          => 'Disabled',
			'value'         => 'disabled',
			'enabled'       => 0,
			'tool_settings' => [
				'site_key'   => '0i1Lk8skHd2no5d',
				'secret_key' => 'xVCSHKWlohcZbw0hutIC93Lr2',
			],
			'logo'          => $this->get_logo_url( 'disabled' ),
		];

		return $formatted_tools;
	}

	public function get_logo_url( $name ) {
		return TVE_DASH_URL . '/inc/auto-responder/views/images/' . $name . '.png';
	}

	/**
	 * @return array
	 */
	public function own_components() {
		$spam_prevention_tools = $this->get_spam_prevention_tools();

		$lead_generation = array(
			'lead_generation'  => array(
				'config' => array(
					'ModalPicker'         => array(
						'config' => array(
							'label' => __( 'Template', 'thrive-cb' ),
						),
					),
					'FormPalettes'        => [
						'config'  => [],
						'extends' => 'Palettes',
					],
					'connectionType'      => array(
						'config' => array(
							'name'    => __( 'Connection', 'thrive-cb' ),
							'buttons' => [
								[
									'text'    => 'API',
									'value'   => 'api',
									'default' => true,
								],
								[
									'text'  => 'HTML code',
									'value' => 'custom-html',
								],
								[
									'text'  => 'Webhook',
									'value' => 'webhook',
								],
							],
						),
					),
					'FieldsControl'       => [
						'config' => [
							'sortable'      => true,
							'settings_icon' => 'pen-light',
						],
					],
					'HiddenFieldsControl' => [
						'config'  => [
							'sortable'      => false,
							'settings_icon' => 'pen-light',
						],
						'extends' => 'PreviewList',
					],
					'ApiConnections'      => [
						'config' => [],
					],
					'SPTools'             => array(
						'config'  => array(
							'name'         => '',
							'options'      => $spam_prevention_tools
						),
						'extends' => 'Select',
					),
					'consent'             => array(
						'config' => array(
							'labels' => array(
								'wordpress' => __( 'Create WordPress account', 'thrive-cb' ),
								'default'   => __( '{service}', 'thrive-cb' ),
							),
						),
					),
					'FormIdentifier'      => array(
						'config'  => array(
							'label'        => __( 'Form identifier', 'thrive-cb' ),
							'full-width'   => true,
							'tooltip'      => __( 'Used in other Thrive plugins to identify this form. It should be unique.', 'thrive-cb' ),
							'tooltip_side' => 'top',
							'width'        => '100%',
						),
						'extends' => 'LabelInput',
					),
				),
			),
			'typography'       => [
				'hidden' => true,
			],
			'layout'           => [
				'disabled_controls' => [
					'.tve-advanced-controls',
				],
				'config'            => [
					'Width' => [
						'important' => true,
					],
				],
			],
			'borders'          => [
				'disabled_controls' => [],
				'config'            => [
					'Corners' => [
						'overflow' => false,
					],
				],
			],
			'animation'        => [
				'hidden' => true,
			],
			'shadow'           => [
				'config' => [
					'disabled_controls' => [ 'text' ],
				],
			],
			'styles-templates' => [
				'config' => [
					'ID' => [
						'hidden' => true,
					],
				],
			],
		);

		return array_merge( $lead_generation, $this->group_component() );
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
	 * Element info
	 *
	 * @return string|string[][]
	 */
	public function info() {
		return [
			'instructions' => [
				'type' => 'help',
				'url'  => 'lead_generation',
				'link' => 'https://help.thrivethemes.com/en/articles/4425779-how-to-use-the-lead-generation-element',
			],
		];
	}

	/**
	 * Group Edit Properties
	 *
	 * @return array|bool
	 */
	public function has_group_editing() {
		return array(
			'select_values' => array(
				array(
					'value'    => 'all_labels',
					'selector' => '.thrv_text_element[data-label-for]',
					'name'     => __( 'Grouped Lead Generation Labels', 'thrive-cb' ),
					'singular' => __( '-- Label %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_lead_gen_items',
					'selector' => '.tve_lg_input,.tve_lg_textarea',
					'name'     => __( 'Grouped Lead Generation Inputs', 'thrive-cb' ),
					'singular' => __( '-- Input %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_radio_elements',
					'selector' => '.tve_lg_radio',
					'name'     => __( 'Grouped Lead Generation Radio', 'thrive-cb' ),
					'singular' => __( '-- Radio %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_checkbox_elements',
					'selector' => '.tve_lg_checkbox:not(.tcb-lg-consent)',
					'name'     => __( 'Grouped Form Checkbox', 'thrive-cb' ),
					'singular' => __( '-- Checkbox %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'all_dropdown_elements',
					'selector' => '.tve_lg_dropdown, .tve_lg_country, .tve_lg_state',
					'name'     => __( 'Grouped Dropdown', 'thrive-cb' ),
					'singular' => __( '-- Dropdown %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'radio_options',
					'selector' => '.tve_lg_radio_wrapper',
					'name'     => __( 'Grouped Radio Options', 'thrive-cb' ),
					'singular' => __( '-- Option %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'dropdown_options',
					'selector' => '.tve-lg-dropdown-option',
					'name'     => __( 'Grouped Dropdown Options', 'thrive-cb' ),
					'singular' => __( '-- Option %s', 'thrive-cb' ),
				),
				array(
					'value'    => 'checkbox_options',
					'selector' => '.tve_lg_checkbox_wrapper:not(.tcb-lg-consent .tve_lg_checkbox_wrapper)',
					'name'     => __( 'Grouped Checkbox Options', 'thrive-cb' ),
					'singular' => __( '-- Option %s', 'thrive-cb' ),
				),
			),
		);
	}
}

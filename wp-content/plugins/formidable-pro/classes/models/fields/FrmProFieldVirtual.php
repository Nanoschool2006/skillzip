<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Processes values server-side without rendering HTML on the front-end.
 *
 * @since 6.29
 */
class FrmProFieldVirtual extends FrmFieldType {

	/**
	 * {@inheritDoc}
	 */
	protected $type = 'virtual';

	/**
	 * {@inheritDoc}
	 */
	protected $holds_email_values = true;

	/**
	 * {@inheritDoc}
	 */
	protected $array_allowed = false;

	/**
	 * {@inheritDoc}
	 */
	protected function field_settings_for_type() {
		$settings = array(
			// Disable most UI settings since Virtual field doesn't show on the form.
			'required'       => false,
			'label_position' => false,
			'css'            => false,
			'options'        => false,
			'max'            => false,
			'visibility'     => false,
			'logic'          => false,
			'unique'         => false,
			'description'    => false,

			'label'          => true, // Keep label enabled for admin display.
			'default'        => true,
			'calc'           => true,
			'autopopulate'   => true,
			'format'         => true,
		);

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function include_form_builder_file() {
		return FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/field-virtual.php';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Renders a read-only textarea for virtual fields on the admin Edit Entry page to support large text.
	 */
	public function front_field_input( $args, $shortcode_atts ) {
		$attributes = array(
			'id'       => $args['html_id'],
			'class'    => 'frm-virtual-field-input',
			'name'     => $args['field_name'],
			'readonly' => 'readonly',
			'disabled' => 'disabled',
			'rows'     => '1',
			'style'    => 'field-sizing: content; min-height: var(--field-height);',
		);

		$value = FrmProCurrencyHelper::maybe_format_currency( $this->prepare_esc_value(), $this->field, $shortcode_atts );

		return '<textarea ' . FrmAppHelper::array_to_html_params( $attributes ) . '>' . esc_textarea( $value ) . '</textarea>';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Virtual field doesn't render on the front-end but appears in admin "Edit Entry" page.
	 */
	public function show_field( $args ) {
		if ( FrmProVirtualFieldController::can_show_virtual_field() ) {
			parent::show_field( $args );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * Virtual field just prepares field HTML for admin "Edit Entry" page.
	 */
	public function prepare_field_html( $args ) {
		return FrmProVirtualFieldController::can_show_virtual_field() ? parent::prepare_field_html( $args ) : '';
	}
}

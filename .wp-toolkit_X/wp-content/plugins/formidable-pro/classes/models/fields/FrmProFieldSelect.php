<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmProFieldSelect extends FrmFieldSelect {

	/**
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings                  = parent::field_settings_for_type();
		$settings['autopopulate']  = true;
		$settings['default_value'] = true;
		$settings['read_only']     = true;
		$settings['unique']        = true;
		$settings['prefix']        = true;
		$settings['choice_limit']  = true;

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	/**
	 * @since 6.28
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		return array_merge(
			parent::extra_field_opts(),
			array(
				'set_choices_limit'       => false,
				'show_remaining_quantity' => false,
			)
		);
	}

	/**
	 * @since 6.24
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	protected function show_priority_field_choices( $args = array() ) {
		$field = $args['field'];

		if ( isset( $field['post_field'] ) && $field['post_field'] === 'post_category' ) {
			return;
		}

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/separate-values.php';
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/choices-limit.php';
	}

	/**
	 * @since 4.0
	 *
	 * @param array $args - Includes 'field', 'display', and 'values'
	 *
	 * @return void
	 */
	public function show_extra_field_choices( $args ) {
		$field      = $args['field'];
		$hide_other = $field['other'] == true;

		if ( ! isset( $field['post_field'] ) || $field['post_field'] !== 'post_category' ) {
			include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/other-option.php';
		}

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-fields/back-end/multi-select.php';

		parent::show_extra_field_choices( $args );
	}

	/**
	 * @since 6.28
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function validate( $args ) {
		$errors = parent::validate( $args );

		if ( $errors ) {
			return $errors;
		}

		return FrmProEntryValidate::validate_choice_limit( $this->field, $args );
	}

	/**
	 * @since 4.05
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	protected function builder_text_field( $name = '' ) {
		$html  = FrmProFieldsHelper::builder_page_prepend( $this->field );
		$field = parent::builder_text_field( $name );
		return str_replace( '[input]', $field, $html );
	}
}

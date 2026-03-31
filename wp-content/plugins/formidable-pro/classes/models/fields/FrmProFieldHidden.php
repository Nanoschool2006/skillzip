<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 3.0
 */
class FrmProFieldHidden extends FrmFieldHidden {

	protected function field_settings_for_type() {
		$settings = parent::field_settings_for_type();

		$settings['autopopulate'] = true;
		$settings['visibility']   = false;
		$settings['calc']         = true;
		$settings['logic']        = false;
		$settings['unique']       = true;
		$settings['format']       = true;

		FrmProFieldsHelper::fill_default_field_display( $settings );
		return $settings;
	}

	public function prepare_field_html( $args ) {
		$args = $this->fill_display_field_values( $args );

		$this->field['html_id'] = $args['html_id'];

		ob_start();
		FrmProFieldsHelper::insert_hidden_fields( $this->field, $args['field_name'], $this->field['value'] );

		return ob_get_clean();
	}

	/**
	 * @since 6.20
	 *
	 * {@inheritdoc}
	 */
	public function set_value_before_save( $value ) {
		if ( FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $this->field, 'format' ) ) ) {
			return FrmProCurrencyHelper::normalize_formatted_numbers( $this->field, $value );
		}

		return $value;
	}
}

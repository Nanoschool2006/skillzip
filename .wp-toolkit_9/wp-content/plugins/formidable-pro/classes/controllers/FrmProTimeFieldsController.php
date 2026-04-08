<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 2.03.08
 */
class FrmProTimeFieldsController {

	/**
	 * @since 6.9
	 */
	const STEP_UNIT_MINUTE = 'min';

	/**
	 * @since 6.9
	 */
	const STEP_UNIT_SECOND = 'sec';

	/**
	 * @since 6.9
	 */
	const STEP_UNIT_MILLISECOND = 'millisec';

	/**
	 * Disable used times in a single dropdown Time field when a Date is selected
	 *
	 * @since 2.03.08
	 */
	public static function ajax_time_options() {
		$values             = array(
			'time_field' => FrmAppHelper::get_post_param( 'time_field', '', 'sanitize_text_field' ),
			'date_field' => FrmAppHelper::get_post_param( 'date_field', '', 'sanitize_text_field' ),
			'date'       => FrmAppHelper::get_post_param( 'date', '', 'sanitize_text_field' ),
			'entry_id'   => FrmAppHelper::get_post_param( 'entry_id', 0, 'absint' ),
		);
		$values['time_key'] = str_replace( 'field_', '', $values['time_field'] );
		$values['date_key'] = str_replace( 'field_', '', $values['date_field'] );

		$remove = array();

		$field_obj = FrmFieldFactory::get_field_type( 'time', $values );
		$field_obj->get_disallowed_times( $values, $remove );

		foreach ( $remove as $time_to_remove ) {
			$remove[] = FrmProAppHelper::format_time( $time_to_remove, 'g:i A' );
		}

		$remove = array_values( $remove );

		echo json_encode( $remove );

		wp_die();
	}

	/**
	 * Load the unique timepicker JS
	 *
	 * @since 2.03.08
	 *
	 * @param string $datepicker
	 */
	public static function load_timepicker_js( $datepicker ) {
		global $frm_vars;

		if ( empty( $frm_vars['timepicker_loaded'] ) || ! $datepicker ) {
			return;
		}

		$unique_time_fields = array();

		foreach ( $frm_vars['timepicker_loaded'] as $time_field_id => $options ) {
			if ( ! $options ) {
				continue;
			}

			$unique_time_fields[] = array(
				'dateID' => self::get_linked_date_id( $options, $frm_vars, $datepicker ),
				'timeID' => $time_field_id,
			);
		}

		if ( $unique_time_fields ) {
			echo '__frmUniqueTimes=' . json_encode( $unique_time_fields ) . ';';
		}
	}

	/**
	 * Gets step unit from processed field values.
	 *
	 * @since 6.9
	 *
	 * @param array $values Processed time field values.
	 *
	 * @return string
	 */
	public static function get_step_unit( $values ) {
		if ( empty( $values['step_unit'] ) ) {
			return self::STEP_UNIT_MINUTE;
		}

		return $values['step_unit'];
	}

	/**
	 * Get the date key by id.
	 *
	 * @since 6.24
	 *
	 * @param array $time_options The time options array.
	 * @param array $frm_vars The frm_vars array.
	 * @param string $default_key The default key to return if the date field is not found.
	 *
	 * @return string
	 */
	private static function get_linked_date_id( $time_options, $frm_vars, $default_key = '' ) {
		if ( empty( $frm_vars['datepicker_loaded'] ) || empty( $time_options['linked_date_field'] ) ) {
			return $default_key;
		}

		$key = array_search( $time_options['linked_date_field'], array_column( $frm_vars['datepicker_loaded'], 'field_id' ), true );

		if ( $key === false ) {
			return $default_key;
		}

		return array_keys( $frm_vars['datepicker_loaded'] )[ $key ];
	}
}

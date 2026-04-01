<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 2.02.13
 */
class FrmProConditionalLogicController {

	/**
	 * Check if a given field should be present in another field's logic options
	 *
	 * @since 2.02.13
	 *
	 * @param array  $current_field The current field being displayed for editing
	 * @param object $logic_field   The logic field
	 * @param bool   $prefiltered   Pass bool to avoid some filter type checks that have already been applied.
	 *
	 * @return bool
	 */
	public static function is_field_present_in_logic_options( $current_field, $logic_field, $prefiltered = false ) {
		$present = true;

		if ( $logic_field->id == $current_field['id'] ) {
			$present = false;
		} else {
			if ( ! $prefiltered ) {
				if ( FrmField::is_no_save_field( $logic_field->type ) ) {
					$present = false;
				} elseif ( in_array( $logic_field->type, array( 'file', 'address', 'credit_card' ), true ) ) {
					$present = false;
				} elseif ( FrmProField::is_list_field( $logic_field ) ) {
					$present = false;
				}
			}

			if ( $present && 'range' === $logic_field->type && FrmField::get_option( $logic_field, 'is_range_slider' ) ) {
				$present = false;
			}

			if ( $present ) {
				$parent_form_id = $current_field['parent_form_id'] ?? '0';

				if ( $logic_field->form_id != $current_field['form_id'] && $logic_field->form_id != $parent_form_id ) {
					$present = false;
				} else {
					$in_section_id = $logic_field->field_options['in_section'] ?? '0';

					if ( $in_section_id == $current_field['id'] ) {
						$present = false;
					}
				}
			}
		}

		/**
		 * Allows excluding fields in the conditional logic options.
		 *
		 * @since 5.0
		 *
		 * @param bool  $present Is `true` if field is present in the conditional logic options.
		 * @param array $args    The arguments. Contains `$current_field` and `$logic_field`.
		 */
		return (bool) apply_filters( 'frm_is_field_present_in_logic_options', $present, compact( 'current_field', 'logic_field' ) );
	}

	/**
	 * Get show/hide options for field conditional logic.
	 *
	 * @since 6.24
	 *
	 * @param array $field The field array.
	 *
	 * @return array
	 */
	public static function get_show_hide_options( $field ) {
		if ( 'submit' === $field['type'] ) {
			return array(
				'show'    => __( 'Show this button', 'formidable-pro' ),
				'hide'    => __( 'Hide this button', 'formidable-pro' ),
				'enable'  => __( 'Enable this button', 'formidable-pro' ),
				'disable' => __( 'Disable this button', 'formidable-pro' ),
			);
		}

		return array(
			'show' => $field['type'] === 'break' ? __( 'Do not skip next page', 'formidable-pro' ) : __( 'Show', 'formidable-pro' ),
			'hide' => $field['type'] === 'break' ? __( 'Skip next page', 'formidable-pro' ) : __( 'Hide', 'formidable-pro' ),
		);
	}
}

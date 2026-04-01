<?php
/**
 * Onboarding Wizard Helper class.
 *
 * @package Formidable
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles validation related tasks for Formidable Pro entries.
 *
 * @since 6.9
 */
class FrmProEntryValidate {

	/**
	 * Applies international phone number format to the given pattern, if specified in field options.
	 *
	 * @since 6.9
	 *
	 * @param string $pattern Existing regex pattern for phone number validation.
	 * @param object $field   Form field object containing field options.
	 *
	 * @return string Modified or original regex pattern.
	 */
	public static function apply_international_phone_format( $pattern, $field ) {
		$format = FrmField::get_option( $field, 'format' );

		if ( 'international' === $format ) {
			return self::get_international_phone_regex();
		}

		return $pattern;
	}

	/**
	 * Provides a regex pattern for validating international phone numbers.
	 *
	 * @since 6.9
	 *
	 * @return string Regex pattern for international phone number validation.
	 */
	private static function get_international_phone_regex() {
		return '^\+?\d{1,4}[\s\-]?(?:\(\d{1,3}\)[\s\-]?)?\d{1,4}[\s\-]?\d{1,4}[\s\-]?\d{1,4}$';
	}

	/**
	 * @since 6.28
	 *
	 * @param stdClass $field
	 * @param array    $args
	 *
	 * @return array
	 */
	public static function validate_choice_limit( $field, $args ) {
		if ( ! FrmField::get_option( $field, 'set_choices_limit' ) || ( ! $args['value'] && '0' !== $args['value'] ) ) {
			return array();
		}

		$stored_field_value       = self::get_field_value_for_current_entry( absint( $args['id'] ) );
		$field_choices            = $field->options;
		$limit_reached_for_choice = false;

		if ( ! is_array( $args['value'] ) ) {
			$args['value'] = array( $args['value'] );
		}

		$selected_choices       = array_filter(
			$args['value'],
			function ( $value ) {
				return $value || '0' === $value;
			}
		);
		$choice_values          = self::get_normalized_choice_values( $field, $field_choices );
		$choices_that_hit_limit = array();

		foreach ( $selected_choices as $selected_choice ) {
			if ( is_array( $stored_field_value ) && in_array( $selected_choice, $stored_field_value, true ) ) {
				continue;
			}

			$index_of_selected_choice = array_search( $selected_choice, $choice_values, true );

			if ( $index_of_selected_choice === false ) {
				// Since check_single_option_choice_limit does not expect a false $opt_key, continue early
				// if we cannot properly make a match.
				// TODO: Add shortcode processing here.
				continue;
			}

			$limit_reached_for_choice = FrmProFieldsHelper::check_single_option_choice_limit( $field, $field_choices, $index_of_selected_choice );

			if ( $limit_reached_for_choice ) {
				$choices_that_hit_limit[] = $field_choices[ $index_of_selected_choice ]['label'] ?? $field_choices[ $index_of_selected_choice ];
			}
		}

		if ( ! $choices_that_hit_limit ) {
			return array();
		}

		$error_msg = sprintf(
			/* translators: %1$s: Options that are maxed out */
			esc_html__( 'The maximum number of times the following choices can be selected was reached: %s', 'formidable-pro' ),
			implode( ', ', $choices_that_hit_limit )
		);

		return array( 'field' . $args['id'] => $error_msg );
	}

	/**
	 * @since 6.28
	 *
	 * @param int $field_id
	 *
	 * @return array|null
	 */
	private static function get_field_value_for_current_entry( $field_id ) {
		$entry_id = FrmAppHelper::get_post_param( 'id', 0, 'absint' );

		if ( ! $entry_id ) {
			return null;
		}

		$entry = FrmEntry::getOne( $entry_id, true );

		if ( ! $entry || ! isset( $entry->metas[ $field_id ] ) ) {
			return null;
		}

		if ( (int) $entry->is_draft !== FrmEntriesHelper::SUBMITTED_ENTRY_STATUS ) {
			// Only ignore errors for an entry that is already published.
			return null;
		}

		return (array) $entry->metas[ $field_id ];
	}

	/**
	 * @since 6.28
	 *
	 * @param object $field
	 * @param array  $field_choices
	 *
	 * @return array
	 */
	private static function get_normalized_choice_values( $field, $field_choices ) {
		if ( count( $field_choices ) === count( $field_choices, COUNT_RECURSIVE ) ) { // If flat field choices.
			return $field_choices;
		}

		$choice_values = FrmField::get_option( $field, 'separate_value' ) ? array_column( $field_choices, 'value' ) : array_column( $field_choices, 'label' );

		if ( count( $choice_values ) !== count( $field_choices ) ) {
			$last_key                   = array_key_last( $field_choices );
			$choice_values[ $last_key ] = $field_choices[ $last_key ];
		}

		return array_combine( array_keys( $field_choices ), $choice_values );
	}
}

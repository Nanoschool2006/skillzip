<?php
/**
 * Lookup Fields Helper class.
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Helper class for lookup field processing.
 *
 * @since 6.29
 */
class FrmProLookupFieldsHelper {

	/**
	 * Force process lookup value for a field and set it to posted data.
	 *
	 * Use this when the lookup must be processed server-side (e.g., virtual fields
	 * that have no front-end DOM).
	 *
	 * @param stdClass $field The field object.
	 * @param array    $args  Context arguments for posted values.
	 *
	 * @return void
	 */
	public static function force_process_lookup_for_field( $field, $args ) {
		$watch_lookup = isset( $field->field_options['watch_lookup'] ) && is_array( $field->field_options['watch_lookup'] )
			? array_filter( $field->field_options['watch_lookup'] )
			: array();

		$values = $watch_lookup
			? self::get_dependent_lookup_values( $field, $watch_lookup, $args )
			: self::get_independent_lookup_values( $field );

		if ( $values ) {
			FrmEntriesHelper::set_posted_value( $field, self::format_values_as_text( $values ), $args );
		}
	}

	/**
	 * Check if a field has lookup/autopopulate configured.
	 *
	 * @param array|stdClass $field The field object or array.
	 *
	 * @return bool True if lookup is configured.
	 */
	public static function has_lookup_configured( $field ) {
		return ! empty( FrmField::get_option( $field, 'get_values_field' ) );
	}

	/**
	 * Format lookup values as comma-separated text.
	 *
	 * @param array|string $values Lookup values.
	 *
	 * @return string Formatted value.
	 */
	public static function format_values_as_text( $values ) {
		return is_array( $values ) ? implode( ', ', $values ) : $values;
	}

	/**
	 * Get values for an independent lookup field.
	 *
	 * @param stdClass $field The field object.
	 *
	 * @return array Lookup values.
	 */
	private static function get_independent_lookup_values( $field ) {
		return FrmProLookupFieldsController::get_independent_lookup_field_options(
			array(
				'id'                         => $field->id,
				'get_values_field'           => $field->field_options['get_values_field'],
				'data_type'                  => 'text',
				'lookup_filter_current_user' => $field->field_options['lookup_filter_current_user'] ?? false,
				'lookup_option_order'        => $field->field_options['lookup_option_order'] ?? 'ascending',
				'get_most_recent_value'      => $field->field_options['get_most_recent_value'] ?? '',
			)
		);
	}

	/**
	 * Get values for a dependent lookup field.
	 *
	 * @param stdClass $field        The field object.
	 * @param array    $watch_lookup Parent field IDs to watch.
	 * @param array    $args         Context arguments for posted values.
	 *
	 * @return array Lookup values.
	 */
	private static function get_dependent_lookup_values( $field, $watch_lookup, $args ) {
		$parent_args = self::build_parent_args_from_posted_values( $watch_lookup, $args );
		return FrmProLookupFieldsController::get_filtered_values_for_dependent_lookup_field( $parent_args, $field );
	}

	/**
	 * Build parent lookup arguments from posted field values.
	 *
	 * @param array $watch_lookup Parent field IDs to watch.
	 * @param array $args         Context arguments for posted values.
	 *
	 * @return array Parent args with 'parent_field_ids' and 'parent_vals'.
	 */
	private static function build_parent_args_from_posted_values( $watch_lookup, $args ) {
		$parent_args = array(
			'parent_field_ids' => array(),
			'parent_vals'      => array(),
		);

		foreach ( $watch_lookup as $parent_field_id ) {
			$parent_value = '';
			FrmEntriesHelper::get_posted_value( $parent_field_id, $parent_value, $args );

			$parent_args['parent_field_ids'][] = $parent_field_id;
			$parent_args['parent_vals'][]      = $parent_value;
		}

		return $parent_args;
	}
}

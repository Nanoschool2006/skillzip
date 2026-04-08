<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Date range controller
 *
 * @package formidable-dates
 * @since 3.0
 */
class FrmDatesRangeController {

	/**
	 * Initialize the date range relationship between start and end date fields.
	 *
	 * @param array $field The field array.
	 * @return void
	 */
	public static function init_date_range_relationship_between_start_end_date_fields( $field ) {
		if ( 'date' !== $field['type'] ) {
			return;
		}
		include FrmDatesAppHelper::get_path( '/views/date-range-options-before.php' );
	}
}

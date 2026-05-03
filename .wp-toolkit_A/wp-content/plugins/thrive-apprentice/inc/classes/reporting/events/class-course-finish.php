<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Reporting\Events;

use TVA\Reporting\EventFields\Course_Id;
use TVA\Reporting\EventFields\Member_Id;
use TVE\Reporting\Event;
use TVE\Reporting\Traits\Report;

class Course_Finish extends Event {
	use Report {
		Report::get_group_by as _get_group_by;
		Report::parse_query as _parse_query;
	}

	public static function get_user_id_field(): string {
		return Member_Id::class;
	}

	public static function key(): string {
		return 'tva_course_finish';
	}

	public static function label(): string {
		return esc_html__( 'Course finish', 'thrive-apprentice' );
	}

	public static function get_tooltip_text(): string {
		return '<strong>{number}</strong> ' . esc_html__( 'completed courses', 'thrive-apprentice' );
	}

	public static function get_item_id_field(): string {
		return Course_Id::class;
	}

	public static function get_group_by(): array {
		return array_filter( static::_get_group_by(), static function ( $field, $key ) {
			return strpos( $key, 'post_id' ) === false;
		}, ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * Override parse_query to use DISTINCT count for preventing duplicates.
	 *
	 * @param array $query Query array.
	 * @return array
	 */
	protected static function parse_query( $query ): array {
		// Call parent method first.
		$reports_query = static::_parse_query( $query );

		// Override count field to use DISTINCT CONCAT for deduplication.
		$reports_query['count'] = "DISTINCT CONCAT(item_id, '-', user_id, '-', post_id)";

		return $reports_query;
	}

	public static function register_action() {
		add_action( 'thrive_apprentice_course_finish', static function ( $course_details, $user_details ) {
			$event = new static( [
				'item_id' => $course_details['course_id'],
				'user_id' => empty( $user_details['user_id'] ) ? 0 : $user_details['user_id'],
				'post_id' => $course_details['course_id'],
			] );

			$event->log();
		}, 10, 2 );
	}

	/**
	 * Event description - used for user timeline
	 *
	 * @return string
	 */
	public function get_event_description(): string {
		$item = $this->get_field( 'item_id' )->get_title();

		return sprintf( esc_html__( ' finished course %s.', 'thrive-apprentice' ), $item );
	}
}

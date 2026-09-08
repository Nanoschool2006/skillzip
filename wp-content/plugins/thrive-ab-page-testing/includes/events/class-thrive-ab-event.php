<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-ab-page-testing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class Thrive_AB_Event extends Thrive_AB_Model {

	/**
	 * @inheritdoc
	 */
	protected function _table_name() {

		return thrive_ab()->table_name( 'event_log' );
	}

	/**
	 * Columns that exist in the event_log table.
	 *
	 * @see migrations/install-1.0.php  base columns
	 * @see migrations/revenue-1.1.php  revenue, goal_page
	 *
	 * @return string[]
	 */
	protected function _table_columns() {

		return array(
			'id',
			'page_id',
			'variation_id',
			'test_id',
			'date',
			'event_type',
			'revenue',
			'goal_page',
		);
	}

	/**
	 * @inheritdoc
	 */
	protected function is_valid() {

		$is_valid = true;

		if ( ! ( $this->page_id ) ) {
			$is_valid = false;
		} elseif ( ! ( $this->variation_id ) ) {
			$is_valid = false;
		} elseif ( ! ( $this->test_id ) ) {
			$is_valid = false;
		} elseif ( ! ( $this->event_type ) ) {
			$is_valid = false;
		}

		return $is_valid;
	}

	public function is_impression() {

		return 1 === $this->event_type;
	}

	public function is_conversion() {

		return 2 === $this->event_type;
	}
}

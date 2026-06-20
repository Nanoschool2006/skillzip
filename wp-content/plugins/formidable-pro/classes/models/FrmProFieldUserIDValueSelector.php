<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 2.03.05
 */
class FrmProFieldUserIDValueSelector extends FrmProFieldValueSelector {

	/**
	 * Whether the value selector should display as a dropdown.
	 *
	 * @var bool
	 *
	 * @since 6.32
	 */
	private $show_dropdown = true;

	/**
	 * @since 6.32
	 *
	 * @param int|string $field_id Field ID.
	 * @param array      $args     Arguments for configuring the value selector.
	 */
	public function __construct( $field_id, $args ) {
		parent::__construct( $field_id, $args );

		if ( isset( $args['show_dropdown'] ) ) {
			$this->show_dropdown = (bool) $args['show_dropdown'];
		}
	}

	/**
	 * Display the field value selector
	 *
	 * @since 2.03.05
	 */
	public function display() {
		if ( ! $this->show_dropdown ) {
			parent::display();
			return;
		}

		echo '<select name="' . esc_attr( $this->html_name ) . '">';
		echo '<option value=""></option>';
		echo '<option value="current_user" ' . selected( $this->value, 'current_user', false ) . '>';
		esc_html_e( 'Current User', 'formidable-pro' );
		echo '</option>';

		if ( $this->has_options() ) {
			foreach ( $this->options as $user_id => $user_login ) {
				if ( ! $user_id ) {
					continue;
				}

				echo '<option value="' . esc_attr( $user_id ) . '" ' . selected( $this->value, $user_id, false ) . '>';
				echo esc_html( $user_login );
				echo '</option>';
			}
		}

		echo '</select>';
	}
}

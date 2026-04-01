<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 6.25
 */
class FrmProSquareLiteHelper {

	/**
	 * @since 6.25
	 *
	 * @param stdClass $field
	 *
	 * @return bool
	 */
	public static function address_field_is_compatible_with_square( $field ) {
		return ! isset( $field->field_options['address_type'] ) || 'generic' !== $field->field_options['address_type'];
	}
}

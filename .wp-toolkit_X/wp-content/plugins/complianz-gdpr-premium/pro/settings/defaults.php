<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_filter( 'cmplz_default_value', 'cmplz_set_premium_default', 10, 3 );
function cmplz_set_premium_default( $value, $fieldname, $field ) {
	if ( isset($field['premium']['default']) ) {
		return $field['premium']['default'];
	}

	return $value;
}

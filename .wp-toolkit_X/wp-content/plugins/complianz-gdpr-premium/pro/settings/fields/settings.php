<?php
defined('ABSPATH') or die();

/**
 * For saving purposes, types should be overridden at the earliest moment
 * @param array $fields
 * @return array
 */
function cmplz_add_pro_settings($fields){
	/**
	 * premium option to set cookies across domains on multisite
	 */

	$fields = array_merge($fields,  [

	]);

	return $fields;
}
add_filter('cmplz_fields', 'cmplz_add_pro_settings', 10, 1);

<?php defined('ABSPATH') or die("you do not have access to this page!");
/**
 * This file contains functions for the dataleak reports, and is only including during pdf generation
 *
 * @since 1.0
 * @package cmplz-dataleak
 *
 */



/**
 * Wrapper for reporting function
 * @param $post_id
 *
 * @return bool
 */
function cmplz_dataleak_has_to_be_reported($post_id)
{
	return COMPLIANZ::$dataleak->dataleak_has_to_be_reported($post_id);
}
function cmplz_dataleak_has_to_be_reported_to_involved($post_id)
{
	return COMPLIANZ::$dataleak->dataleak_has_to_be_reported_to_involved($post_id);
}
function cmplz_get_regions_by_dataleak_type($dataleak_type){
	$regions       = COMPLIANZ::$config->regions;
	$type_regions = array();
	foreach ( $regions as $region_code => $region_data ) {
		if ($dataleak_type == $region_data['dataleak_type'] ) {
			$type_regions[] = $region_code;
		}
	}

	return $type_regions;
}

function cmplz_socialsecurity_or_driverslicense()
{
	$type = cmplz_get_option('name-what-us');

	if (isset($type['drivers-license']) && $type['drivers-license'] ==1) {
		return true;
	}
	if (isset($type['social-security-number']) && $type['social-security-number'] ==1) {
		return true;
	}

	return false;
}

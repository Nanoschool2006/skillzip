<?php
/**
 * Premium Tools fields.
 *
 * @package Complianz
 * @author Complianz
 * @since 7.5.5
 */

defined( 'ABSPATH' ) || die();

/**
 * Filter the 'cmplz_fields' array to include fields in the 'Tools' page.
 *
 * @param array $fields the fields array.
 * @return array the fields array.
 */
function cmplz_add_pro_tools( $fields ) {
	// Add the TCF debug_mode field only when IAB TCF is enabled.
	if ( cmplz_iab_is_enabled() ) {
		$fields = array_merge(
			$fields,
			array(
				array(
					'id'       => 'tcf_debug_mode',
					'menu_id'  => 'support',
					'group_id' => 'debugging',
					'type'     => 'checkbox',
					'default'  => false,
					'label'    => __( 'Enable TCF Debug mode', 'complianz-gdpr' ),
					'tooltip'  => __( 'Overrides default behavior to enable additional TCF logging and diagnostics. If SCRIPT_DEBUG is enabled in wp-config.php, Debug mode is enabled regardless of this setting.', 'complianz-gdpr' ),
				),
			)
		);
	}

	return $fields;
}
add_filter( 'cmplz_fields', 'cmplz_add_pro_tools', 100, 1 );

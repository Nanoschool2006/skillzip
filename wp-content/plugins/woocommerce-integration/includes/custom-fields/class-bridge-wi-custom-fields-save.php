<?php
/**
 * Setup plugin menus in WP admin.
 *
 * @link       https://edwiser.org
 * @since      2.1.4
 *
 * @package    Woocommerce Integration
 * @subpackage Woocommerce Integration/includes/custom-fields
 */

namespace NmBridgeWoocommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit(); // Exit if accessed directly.
}

/**
 * Eb_Admin_Menus Class
 */
class Bridge_Wi_Cf_Save_Handler {

	/**
	 * Save bridge custom fields in DB.
	 */
	public function wi_cf_save_fields( $post_fields )
	{
		$data_array = array();
		if ( isset( $post_fields['eb-wi-cf-tbl-name'] ) && count( $post_fields['eb-wi-cf-tbl-name'] ) ) {
			for ( $i = 0;  $i < count( $post_fields['eb-wi-cf-tbl-name'] );  $i++ ) { 
				if ( ! empty( $post_fields['eb-wi-cf-tbl-name'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-name'][$i] ) ) {
					$field_key = sanitize_text_field( $post_fields['eb-wi-cf-tbl-name'][$i] );

					$data_array[$field_key] = array(
						'type'               => isset( $post_fields['eb-wi-cf-tbl-type'][$i] ) ? sanitize_text_field( $post_fields['eb-wi-cf-tbl-type'][$i] ) : '',
						'class'              => isset(  $post_fields['eb-wi-cf-tbl-class'][$i] ) ? sanitize_text_field( $post_fields['eb-wi-cf-tbl-class'][$i] ) : '',
						'label'              => isset(  $post_fields['eb-wi-cf-tbl-label'][$i] ) ? sanitize_text_field( $post_fields['eb-wi-cf-tbl-label'][$i] ) : '',
						'placeholder'        => isset(  $post_fields['eb-wi-cf-tbl-placeholder'][$i] ) ? sanitize_text_field( $post_fields['eb-wi-cf-tbl-placeholder'][$i] ) : '',
						'default-val'        => isset(  $post_fields['eb-wi-cf-tbl-default-val'][$i] ) ? sanitize_text_field( $post_fields['eb-wi-cf-tbl-default-val'][$i] ) : '',
						'enabled'            => isset(  $post_fields['eb-wi-cf-tbl-enabled'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-enabled'][$i] ) ? 1 : 0,
						'required'           => isset(  $post_fields['eb-wi-cf-tbl-required'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-required'][$i] ) ? 1 : 0,
						'sync-on-moodle'     => isset(  $post_fields['eb-wi-cf-tbl-sync-on-moodle'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-sync-on-moodle'][$i] ) ? 1 : 0,
						'woo-my-accnt'       => isset(  $post_fields['eb-wi-cf-tbl-woo-my-accnt'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-woo-my-accnt'][$i] ) ? 1 : 0,
						'edwiser-user-accnt' => isset(  $post_fields['eb-wi-cf-tbl-edwiser-user-accnt'][$i] ) && sanitize_text_field( $post_fields['eb-wi-cf-tbl-edwiser-user-accnt'][$i] ) ? 1 : 0,
					);

					if ( 'select' == $post_fields['eb-wi-cf-tbl-type'][$i] && isset( $post_fields['eb-wi-cf-tbl-options'][$i] ) ) {
						// Replaced double qoutes with single to avoid browser cinflict while fetching the input value which was stored after JSON.stringify.
						// So now need to remove backslash first then again replace single quotes with double qoutes.
						$options = $post_fields['eb-wi-cf-tbl-options'][$i];
						$options = stripslashes( $options );
						$options = str_replace("'", '"', $options);
						$options = (array)json_decode( $options );

						foreach ($options as $option_value => $option_text) {
							$data_array[$post_fields['eb-wi-cf-tbl-name'][$i]]['options'][$option_value] = $option_text;
						}
					}
				}
			}
		}

		// if ( ! empty( $data_array ) ) {
		update_option('eb_wi_custom_fields', $data_array);
		// }
	}

}

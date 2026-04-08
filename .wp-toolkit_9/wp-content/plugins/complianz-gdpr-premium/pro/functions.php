<?php
defined('ABSPATH') or die("you do not have access to this page!");

/**
 * If free is active, we should deactivate it.
 *
 * */

add_action('admin_init', 'cmplz_check_for_free_version');
if ( !function_exists('cmplz_check_for_free_version') ) {
    function cmplz_check_for_free_version()
    {
		if ( !cmplz_user_can_manage() ) {
			return;
		}

		if ( defined('cmplz_plugin_free') ) {
			deactivate_plugins(cmplz_plugin_free);
			add_action('admin_notices', 'cmplz_notice_free_active');
			//older method:
		} else if (defined('cmplz_free')) {
             $free = 'complianz-gdpr/complianz-gpdr.php';
             deactivate_plugins($free);
			 add_action('admin_notices', 'cmplz_notice_free_active');
         }
    }
}

if (!function_exists('cmplz_notice_free_active')) {
    function cmplz_notice_free_active()
    { ?>
       <div id="message" class="notice notice-success is-dismissible cmplz-dismiss-notice really-simple-plugins">
           <p>
               <?php echo esc_html(__("You have installed Complianz Privacy Suite. We have deactivated and removed the free plugin.", 'complianz-gdpr')); ?>
           </p>
       </div>
       <?php
   }
}

if (!function_exists('cmplz_free_plugin_not_deleted')){
	function cmplz_free_plugin_not_deleted(){
		if ( file_exists(trailingslashit( WP_PLUGIN_DIR).'complianz-gdpr/complianz-gpdr.php' ) ){
			return true;
		}

		return false;
	}
}

/**
 * Use only multisite licensing if this is also the multisite plugin
 *
 * @return bool
 */
if ( !function_exists('cmplz_is_multisite_and_multisite_plugin') ) {
	function cmplz_is_multisite_and_multisite_plugin() {
		return defined( 'cmplz_premium_multisite' ) && is_multisite();
	}
}

if ( ! function_exists( 'cmplz_get_license_status' ) ) {
	/**
	 * Get the license status.
	 * Uses get_site_option() on multisite to read the network-level license transient.
	 *
	 * @return string
	 */
	function cmplz_get_license_status() {
		$status              = 'invalid';
		$transient_key       = 'cmplz_transients';
		$transient_license_key = 'cmplz_license_status';

		if ( cmplz_is_multisite_and_multisite_plugin() ) {
			$transients = get_site_option( $transient_key, array() );
		} else {
			$transients = get_option( $transient_key, array() );
		}

		if ( empty( $transients ) || ! isset( $transients[ $transient_license_key ] ) ) {
			return $status;
		}

		return $transients[ $transient_license_key ]['value'];
	}
}

if (!function_exists('cmplz_custom_document_data')) {
	/**
	 * Get array of fields or document elements, for either processing or dataleaks
	 *
	 * @param $post_id
	 * @param $data_type
	 * @param $region
	 *
	 * @return mixed
	 */
	function cmplz_custom_document_data( $post_id, $data_type, $region ) {
		$post_type = str_replace( 'cmplz-', '', get_post_type( $post_id ) );
		if ( $data_type === 'elements' ) {
			return COMPLIANZ::${$post_type}->get_document_elements( $region );
		}

		$fields = COMPLIANZ::${$post_type}->get_fields( $region );
		foreach ( $fields as $field ) {
			$field['value'] = get_post_meta( $post_id, $field['id'], true );
		}

		return $fields;
	}
}

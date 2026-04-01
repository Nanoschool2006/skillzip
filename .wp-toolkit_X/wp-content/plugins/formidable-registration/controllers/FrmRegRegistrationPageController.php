<?php
/**
 * @since 2.01
 */

class FrmRegRegistrationPageController {

	/**
	 * Redirect to custom registration page if selected in global settings
	 *
	 * @since 2.01
	 */
	public static function redirect_to_custom_registration_page() {
		if ( 'GET' === FrmRegAppHelper::request_method() ) {
			$redirect_url = FrmRegAppHelper::registration_page_url( 'none' );

			if ( $redirect_url ) {
				wp_redirect( esc_url_raw( $redirect_url ) );
				exit;
			}
		}
	}
}

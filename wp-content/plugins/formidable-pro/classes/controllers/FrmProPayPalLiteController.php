<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 6.31
 */
class FrmProPayPalLiteController {

	/**
	 * Register additional scripts to support Pro functionality with PayPal Lite.
	 * This includes:
	 * - Saving drafts, and going to the previous page in a multiple-page form without processing a payment.
	 *
	 * @return void
	 */
	public static function maybe_register_paypal_scripts() {
		if ( ! class_exists( 'FrmPayPalLiteHooksController', false ) ) {
			// Only register PayPal scripts if PayPal Lite is available.
			return;
		}

		if ( ! wp_script_is( 'formidable-paypal', 'enqueued' ) ) {
			// Only register scripts if PayPal Lite has registered scripts.
			return;
		}

		$dependencies = array( 'formidable' );

		if ( ! FrmAppHelper::js_suffix() || ! FrmFormsController::has_combo_js_file() ) {
			$dependencies[] = 'formidablepro';
		}

		wp_register_script(
			'formidablepro_paypal',
			FrmProAppHelper::plugin_url() . '/js/frmpplite.js',
			$dependencies,
			FrmProDb::$plug_version,
			true
		);
		wp_enqueue_script( 'formidablepro_paypal' );
	}
}

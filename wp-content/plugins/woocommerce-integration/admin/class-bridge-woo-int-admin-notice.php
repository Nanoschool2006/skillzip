<?php
/**
 * Bridge_Woo_Int_Admin_Notice_Handler
 *
 * @package BridgeWooInt
 */

namespace NmBridgeWoocommerce;

/**
 * Class Bridge_Woo_Int_Admin_Notice_Handler
 */
class Bridge_Woo_Int_Admin_Notice_Handler {

	/**
	 * Update Notice.
	 */
	public function wi_admin_update_notice() {

		$redirection = add_query_arg( 'eb_woo_int_update_notice', true );

		if ( ! get_option( 'eb_woo_int_update_notice' ) ) {

			echo '  <div class="notice eb_woo_int_update_notice_message_cont">
						<div class="eb_admin_update_notice_message">

							<div class="eb_update_notice_content">
								' . __( 'WooCommerce Integration is now compatible with the WooCommerce My-Account page. </b>', 'eb-textdomain' ) . '
								<a href="'. get_admin_url(  ) . '/admin.php?page=eb-settings&tab=woo_int_settings">' . __( ' Click here ', 'woocommerce-integration' ) . '</a>
								' . __( ' to check now.', 'woocommerce-integration' ) . ' </a>
							</div>
							
							<div class="eb_update_notice_dismiss_wrap">
								<span style="padding-left: 5px;">
									<a href="' . $redirection . '">
										' . __( ' Dismiss notice', 'woocommerce-integration' ) . '
									</a>
								</span>
							</div>

						</div>
						<div class="eb_admin_update_dismiss_notice_message">
								<span class="dashicons dashicons-dismiss eb_update_notice_hide"></span>
						</div>
					</div>';
		}
	}

	/**
	 * Update Notice Dismiss Handler.
	 */
	public function wi_admin_update_notice_dismiss_handler() {
		if ( isset( $_GET['eb_woo_int_update_notice'] ) ) { // @codingStandardsIgnoreLine
			update_option( 'eb_woo_int_update_notice', 'true', true );
		}
	}
}

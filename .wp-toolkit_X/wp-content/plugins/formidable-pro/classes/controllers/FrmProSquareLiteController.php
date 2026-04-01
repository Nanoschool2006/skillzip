<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 6.25
 */
class FrmProSquareLiteController {

	/**
	 * @param object $action
	 *
	 * @return void
	 */
	public static function maybe_show_address_type_warning( $action ) {
		if ( empty( $action->post_content['gateway'] ) ) {
			return;
		}

		$gateways = (array) $action->post_content['gateway'];

		if ( ! in_array( 'square', $gateways, true ) ) {
			return;
		}

		if ( empty( $action->post_content['billing_address'] ) ) {
			return;
		}

		$address_field = FrmField::getOne( $action->post_content['billing_address'] );

		if ( ! $address_field || FrmProSquareLiteHelper::address_field_is_compatible_with_square( $address_field ) ) {
			return;
		}
		?>
		<div class="frm_warning_style">
			<?php
			esc_html_e( 'The address field selected is not compatible with Square, because it does not include the country code. Select another address type to prevent checkout errors.', 'formidable-pro' ); // phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
			?>
		</div>
		<?php
	}
}

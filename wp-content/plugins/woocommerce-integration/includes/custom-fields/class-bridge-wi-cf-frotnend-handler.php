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
class Bridge_Wi_Cf_Frontend_Handler {

	/**
     * Show fields on Checkout page. 
	 */
	public function wi_show_fields_on_checkout_page() {
		// Hook to add fields on the checkput page woocommerce_after_order_notes
		$this->wi_parse_fields( 'checkout', 'woocommerce-input-wrapper' );
	}

	/**
     * Show fields on My Account page.
	 */
	public function wi_show_fields_on_woo_my_accnt_page() {
		// Hook woocommerce_edit_account_form.
		$this->wi_parse_fields( 'woo-my-accnt', 'woocommerce-Input' );
	}

	/**
     * Show fields on User Account page.
	 */
	public function wi_show_fields_on_edwiser_user_accnt_page() {
		// Hook eb_edit_user_profile.
		$this->wi_parse_fields( 'edwiser-user-accnt', '' );
	}


	/**
	 * Parse Fields to show on selected pages.
	 */
	public function wi_parse_fields( $page = 'checkout', $input_class = '' ) {		
		$current_user_id = get_current_user_id();
		// get fields from DB.
		$fields = get_option( 'eb_wi_custom_fields', array() );

		// foreach for all fields.
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			$display_header = 0;

			foreach ( $fields as $name => $field_details ) {
				// Check if the field is enabled for the page. If checkout page then check if the field is enabled or not.
				if ( isset( $field_details['enabled'] ) && ! empty( $field_details['enabled'] ) && ( 'checkout' == $page  ||  isset( $field_details[$page] ) && ! empty( $field_details[$page] ) ) ) {

					if ( $display_header < 1 ) {
						// Here comes the overall pagewise wrapper start for ALL fields.
						$this->wi_pagewise_all_fields_wrapper_start( $page );
					}
					$display_header ++;

					// get deault value user wise.
					$field_value = get_user_meta($current_user_id, $name, 1);
					if ( empty( $field_value ) ) {
						$field_value = 	$field_details['default-val'];
					}

					// Here comes the overall pagewise wrapper for EACH field.
					$this->wi_pagewise_per_fields_wrapper_start( $field_details['class'], $page );

					$required = '';
					$required_txt = '';

					if ( $field_details['required'] ) {
						$required = ' *';
						$required_txt = 'required ';
					}

					// Switch case for each field type to show them on fronend.
					switch ( $field_details['type'] ) {
						case 'text':
						case 'number':
							?>
							<label>
								<?php echo esc_html( $field_details['label'] ) . esc_html( $required ); ?> 
							</label>
							<span class="<?php echo esc_html( $input_class ); ?>">
								<input placeholder="<?php echo esc_html( $field_details['placeholder'] ); ?>" class="input-text" type="<?php echo esc_html( $field_details['type'] ); ?>" name="<?php echo esc_html( $name ); ?>" value="<?php echo esc_html( $field_value ); ?>" <?php echo esc_html( $required_txt ); ?>>
							</span>

							<?php
							break;

						case 'textarea':
								?>
								<label>
									<?php echo esc_html( $field_details['label'] ) . esc_html( $required ); ?>
								</label>
								<span class="<?php echo esc_html( $input_class ); ?>">
									<textarea placeholder="<?php echo esc_html( $field_details['placeholder'] ); ?>" class="input-text"  type="<?php echo esc_html( $field_details['type'] ); ?>" name="<?php echo esc_html( $name ); ?>" <?php echo esc_html( $required_txt ); ?>><?php echo esc_html( $field_value ); ?></textarea>
								</span>

							<?php
							break;

						case 'select':
								?>
								<label>
									<?php echo esc_html( $field_details['label'] ) . esc_html( $required ); ?>
								</label>
								<span class="<?php echo esc_html( $input_class ); ?>">
									<select class="input-text" name="<?php echo esc_html( $name ); ?>" <?php echo esc_html( $required_txt ); ?>>
										<?php
										foreach ( $field_details['options'] as $value => $text ) {
											$selected = '';
											if ( $value == $field_value ) {
												$selected = 'selected';
											}
										?>
										<option value="<?php echo esc_html( $value ); ?>"  <?php echo esc_html( $selected ); ?>> <?php echo esc_html( $text ); ?> </option>
										<?php
										}
										?>
									</select>
								</span>

								<?php
							break;
						
						case 'checkbox':
							$checked = '';
							if ( $field_value ) {
								$checked = 'checked';
							}

							?>
							<label>
								
							</label>
							<span class="<?php echo esc_html( $input_class ); ?>">
								<input type="checkbox" name="<?php echo esc_html( $name ); ?>" value="<?php echo esc_html( $name ); ?>" <?php echo esc_html( $required_txt ); ?> <?php echo esc_html( $checked ); ?>>
								<?php echo esc_html( $field_details['label'] ) . esc_html( $required ); ?>
							</span>
							<?php
							break;

						default:
							break;
					}
					?>
					<?php

					// Here comes the overall pagewise wrapper end for EACH field.
					$this->wi_pagewise_per_fields_wrapper_end( $page );
				}
			}

			if ( $display_header > 0 ) {
				// Here comes the overall pagewise wrapper start for ALL fields.
				$this->wi_pagewise_all_fields_wrapper_end( $page );
			}

		}

	}


	/**
     * Pagewise field wrapper.
	 */
	public function wi_pagewise_per_fields_wrapper_start( $page = 'checkout', $field_class )
	{
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
				?>
				<p class="form-row notes validate- <?php echo esc_html( $field_class ); ?>" id="order_comments_field">
				<?php
				break;

			case 'woo-my-accnt':
				?>
				<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide <?php echo esc_html( $field_class ); ?>">
				<?php
				break;

			case 'edwiser-user-accnt':
				?>
				<p class="eb-profile-txt-field <?php echo esc_html( $field_class ); ?>">
				<?php
				break;
			
			default:
				?>
				<p class="<?php echo esc_html( $field_class ); ?>">
				<?php
				break;
		}

	}

	/**
     * Pagewise per field wrapper end.
	 */
	public function wi_pagewise_per_fields_wrapper_end( $page = 'checkout' )
	{
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
			case 'woo-my-accnt':
			case 'edwiser-user-accnt':
				?>
				</p>
				<?php
				break;
			
			default:
				?>
				</p>
				<?php
				break;
		}

	}


	/**
     * All fields wrapper start.
	 */
	public function wi_pagewise_all_fields_wrapper_start( $page = 'checkout' )
	{
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
			    break;
			case 'woo-my-accnt':
			case 'edwiser-user-accnt':
				?>
				<fieldset>
					<legend><?php esc_html_e( 'Additional Fields', 'woocommerce-integration'); ?></legend>
				<?php
				break;

			default:
				?>
				<fieldset>
				<?php
				break;
		}
	}

	/**
     * All fields wrapper end.
	 */
	public function wi_pagewise_all_fields_wrapper_end( $page = 'checkout' )
	{
		// switch case for each page.
		switch ( $page ) {
			case 'checkout':
				break;
			case 'woo-my-accnt':
			case 'edwiser-user-accnt':
				?>
				</fieldset>
				<?php
				break;
			
			default:
				?>
				</fieldset>
				<?php
				break;
		}

	}


	/**
	 * Validate fields on checkout page.
	 */
	public function wi_validate_fields_on_checkout()
	{
		// Get all fields. and check if they are enabled if enabled then check if the required is checked then chek if the field is empty or not.
		$fields = get_option( 'eb_wi_custom_fields', array() );
		if ( is_array( $fields ) && ! empty( $fields ) ) {
			foreach ( $fields as $field_name => $field_details ) {
				if ( isset( $field_details['required'] ) && $field_details['required'] && ( isset( $_POST[$field_name] ) && empty( $_POST[$field_name] ) ) || ! isset( $_POST[$field_name] ) ) {
				    wc_add_notice( '<b>' . $field_details['label'] . '</b>' . esc_html__( ' is a required field.', 'woocommerce-integration' ), 'error' );
				}
			}
		}
	}
}

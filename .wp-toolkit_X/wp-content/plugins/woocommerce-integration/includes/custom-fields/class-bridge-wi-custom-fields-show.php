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
class Bridge_Wi_Custom_Field_Hanndler {

	/**
	 * Hook in tabs.
	 *
	 * @since 2.1.4
	 */
	public function __construct() {
	}

	/**
	 * Function which will output the custom fields table.
	 */
	public function wi_output_custom_fields()
	{
		// show migration notice
		$this->wi_output_migration_notice();

		ob_start();
		$table_headers = array(
			'sort'        => '',
			'name'        => __( 'Name', 'woocommerce-integration' ),
			'type'        => __( 'Type', 'woocommerce-integration' ),
			'label'       => __( 'Label', 'woocommerce-integration' ),
			'placeholder' => __( 'Placeholder', 'woocommerce-integration' ),
			'required'    => __( 'Required', 'woocommerce-integration' ),
			'enabled'     => __( 'Enabled', 'woocommerce-integration' ),
		);

		$table_headers = apply_filters( 'eb_wi_custom_field_tbl_headers', $table_headers );

		// Table data will have unique key as the field name.
		// As we also can not add 2 fields with the same name.
		$table_data = get_option( 'eb_wi_custom_fields', array() );

		?>
		<div class ='eb_wi_custom_fields_wrap' >
						<!-- Table bulk action wrapper div -->
			<form method="post"  action="">

				<!-- Table wrapper div -->
				<div>
					<table class='eb_wi_custom_field_tbl'>
						<thead>
							<tr>
								<?php
								foreach ( $table_headers as $class => $table_header ) {
									?>
									<th class = "<?php echo 'wi-cf-tbl-thead-' . esc_html( $class ); ?>"> <?php echo $table_header; ?> </th>
									<?php
								}
								?>
							</tr>
						</thead>

						<tbody>
							<?php

							if ( is_array( $table_data ) && count( $table_data ) ) {

								// Foreach for each row.
								foreach ( $table_data as $data_key => $field_data ) {
									?>
									<tr>
										<?php
										// foreach for each column.
										foreach ( $table_headers as $key => $table_header ) {
											// Adding swicth case for each column.
											switch ( $key ) {
												case 'sort':
													?>
													<td style="width: 5%; text-align:center;"> <span class="dashicons dashicons-menu"></span> </td>
													<?php
													break;

												case 'name':
													?>
													<td> 
														<span class="eb-wi-cf-tbl-name-lbl"><?php echo esc_html( $data_key ); ?></span> 
														<input type="hidden" class="eb-wi-cf-tbl-name" name="eb-wi-cf-tbl-name[]"  value="<?php echo esc_html( $data_key ); ?>">
													</td>
													<?php
													break;

												case 'type':
													?>
													<td>
														<span class="eb-wi-cf-tbl-type-lbl"><?php echo esc_html( $field_data['type'] ); ?></span>
														<input type="hidden" class="eb-wi-cf-tbl-type" name="eb-wi-cf-tbl-type[]" value="<?php echo esc_html( $field_data['type'] ); ?>">
													</td>
													<?php
													break;

												case 'label':
													?>
													<td>
														<span class="eb-wi-cf-tbl-label-lbl"><?php echo esc_html( $field_data['label'] ); ?></span>
														<input type="hidden" class="eb-wi-cf-tbl-label" name="eb-wi-cf-tbl-label[]" value="<?php echo esc_html( $field_data['label'] ); ?>">
														</td>
													<?php
													break;

												case 'placeholder':
													?>
													<td>
														<span class="eb-wi-cf-tbl-placeholder-lbl"><?php echo esc_html( $field_data['placeholder'] ); ?></span>
														<input type="hidden" class="eb-wi-cf-tbl-placeholder" name="eb-wi-cf-tbl-placeholder[]" value="<?php echo esc_html( $field_data['placeholder'] ); ?>">
													</td>
													<?php
													break;

												case 'required':
													?>
													<td>
														<?php
														if ( $field_data['required'] ) {
															?>
															<span class="eb-wi-cf-tbl-required-lbl"> <span class="dashicons dashicons-saved"></span> </span>
															<input type="hidden" class="eb-wi-cf-tbl-required" name="eb-wi-cf-tbl-required[]" value="1">
															<?php
														} else {
															?>
															<span class="eb-wi-cf-tbl-required-lbl"> - </span>
															<input type="hidden" class="eb-wi-cf-tbl-required" name="eb-wi-cf-tbl-required[]" value="0">
															<?php
														}
														?>
													</td>

													<?php
													break;

												case 'enabled':
													?>
													<td>
														<?php
														if ( $field_data['enabled'] ) {
															?>
															<span class="eb-wi-cf-tbl-enabled-lbl"> <span class="dashicons dashicons-saved"></span> </span>
															<input type="hidden" class="eb-wi-cf-tbl-enabled" name="eb-wi-cf-tbl-enabled[]" value="1">
															<?php
														} else {
															?>
															<span class="eb-wi-cf-tbl-enabled-lbl"> - </span>
															<input type="hidden" class="eb-wi-cf-tbl-enabled" name="eb-wi-cf-tbl-enabled[]" value="0">
															<?php
														}
														?>
													</td>
													<?php
													break;

												default:
													break;
											}
										} 
									?>
									</tr>
									<?php
								}
							} else {
								?>
								<tr class="wi_cf_empty_table">
									<td colspan="9" style="text-align:center;"><?php esc_html_e( 'Currently no fields available.', 'woocommerce-integration' ); ?> </td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</div>

			</form>

		</div>
		<?php
		ob_flush();
	}

	/**
	 * Migration notice for the users who are using the old version of the plugin.
	 * 
	 * @since 2.2.1
	 */
	public function wi_output_migration_notice() {
		?>
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<div class="notice notice-info">
			<h2><?php echo __( 'Edwiser Woocommerce Intergartion plugin’s  Custom User Fields features has been improved and move to a new plugin!', 'woocommerce-integration' ); ?></h2>
			<p><?php echo __( 'We have introduced a ', 'woocommerce-integration' ) . '<b>' . __( 'new plugin - Edwiser Custom Fields', 'woocommerce-integration' ) . '</b>'. __( ' to improve the functionality and make it available on different regression forms.', 'woocommerce-integration' ); ?></p>
			<p><b><?php echo __( 'Don’t worry', 'woocommerce-integration' ) . '</b>' . __( ', in Edwiser Woocoomerce Integration version 2.2.1, your old field will be available at checkout / Profile page as configured. You may just not be able to edit them from here.', 'woocommerce-integration' ); ?></p>
			<p><b><?php echo __( 'Download and install', 'woocommerce-integration' ) . '</b>' . __( ' the new plugin - ', 'woocommerce-integration' ) . '<b>' . __('Edwiser Custom Fields', 'woocommerce-integration' ) . '</b>' . __( ' From ', 'woocommerce-integration' ) . '<a href="https://edwiser.org/my-account/">' . __( 'here', 'woocommerce-integration' ); ?></a></p>
			
		</div>
		<?php
	}
}

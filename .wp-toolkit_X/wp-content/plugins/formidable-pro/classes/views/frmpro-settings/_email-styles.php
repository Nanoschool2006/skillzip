<?php
/**
 * Email styles extra settings
 *
 * @since 6.25
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$frm_settings = FrmProAppHelper::get_settings();

$image_sizes = array(
	''       => __( 'Small', 'formidable' ),
	'medium' => __( 'Medium', 'formidable' ),
	'large'  => __( 'Large', 'formidable' ),
);

$image_alignments = array(
	''      => __( 'Centered', 'formidable' ),
	'left'  => __( 'Left', 'formidable' ),
	'right' => __( 'Right', 'formidable' ),
);

$image_locations = array(
	''       => __( 'Outside container', 'formidable-pro' ),
	'inside' => __( 'Inside container', 'formidable-pro' ),
);

$fonts = array(
	''      => __( 'Sans Serif', 'formidable-pro' ),
	'serif' => __( 'Serif', 'formidable-pro' ),
);

$default_options = $frm_settings->default_options();
$default_colors  = array(
	'email_bg_color'           => $default_options['email_bg_color'],
	'email_container_bg_color' => $default_options['email_container_bg_color'],
	'email_text_color'         => $default_options['email_text_color'],
	'email_link_color'         => $default_options['email_link_color'],
	'email_divider_color'      => $default_options['email_divider_color'],
);
?>

<table class="form-table">
	<tbody>
		<tr>
			<th class="frm-pr-lg">
				<strong><?php esc_html_e( 'Header Image', 'formidable' ); ?></strong>
				<p class="description frm-mt-xs">
					<?php esc_html_e( 'Upload or choose a logo to be displayed at the top of email notifications.', 'formidable' ); ?>
				</p>
			</th>

			<td>
				<?php
				FrmProSettingsController::image_uploader(
					array(
						'name'   => 'frm_email_image_id',
						'img_id' => $frm_settings->email_image_id,
					)
				);
				?>

				<div class="frm_grid_container frm-mt-xs">
					<div class="frm6">
						<p>
							<label for="frm-email-image-size"><?php esc_html_e( 'Size', 'formidable' ); ?></label>
							<select id="frm-email-image-size" name="frm_email_image_size">
								<?php FrmProEmailStylesController::print_options( $image_sizes, $frm_settings->email_image_size ); ?>
							</select>
						</p>
					</div>

					<div class="frm6">
						<p>
							<label for="frm-email-image-align"><?php esc_html_e( 'Image Alignment', 'formidable' ); ?></label>
							<select id="frm-email-image-align" name="frm_email_image_align">
								<?php FrmProEmailStylesController::print_options( $image_alignments, $frm_settings->email_image_align ); ?>
							</select>
						</p>
					</div>

					<div class="frm6 frm-mt-sm">
						<p>
							<label for="frm-email-image-location"><?php esc_html_e( 'Image Location', 'formidable' ); ?></label>
							<select id="frm-email-image-location" name="frm_email_image_location">
								<?php FrmProEmailStylesController::print_options( $image_locations, $frm_settings->email_image_location ); ?>
							</select>
						</p>
					</div>
				</div>
			</td>
		</tr>

		<tr>
			<th class="frm-pt-xl frm-pr-lg">
				<strong><?php esc_html_e( 'Color Scheme', 'formidable' ); ?></strong>
				<p class="description frm-mt-xs"><?php esc_html_e( 'Change how your email looks by changing these values.', 'formidable' ); ?></p>
			</th>

			<td class="frm-pt-xl">
				<div class="frm_grid_container">
					<div class="frm6">
						<label for="frm-email-bg-color"><?php esc_html_e( 'Email Background', 'formidable' ); ?></label>
						<?php
						new FrmColorpickerStyleComponent(
							'frm_email_bg_color',
							$frm_settings->email_bg_color,
							array(
								'id'          => 'frm-email-bg-color',
								'action_slug' => 'email_bg_color',
							)
						);
						?>
					</div>
					<div class="frm6">
						<label for="frm-email-container-bg-color"><?php esc_html_e( 'Container Background', 'formidable' ); ?></label>
						<?php
						new FrmColorpickerStyleComponent(
							'frm_email_container_bg_color',
							$frm_settings->email_container_bg_color,
							array(
								'id'          => 'frm-email-container-bg-color',
								'action_slug' => 'email_container_bg_color',
							)
						);
						?>
					</div>
					<div class="frm6 frm-mt-sm">
						<label for="frm-email-text-color"><?php esc_html_e( 'Text', 'formidable' ); ?></label>
						<?php
						new FrmColorpickerStyleComponent(
							'frm_email_text_color',
							$frm_settings->email_text_color,
							array(
								'id'          => 'frm-email-text-color',
								'action_slug' => 'email_text_color',
							)
						);
						?>
					</div>
					<div class="frm6 frm-mt-sm">
						<label for="frm-email-link-color"><?php esc_html_e( 'Link', 'formidable' ); ?></label>
						<?php
						new FrmColorpickerStyleComponent(
							'frm_email_link_color',
							$frm_settings->email_link_color,
							array(
								'id'          => 'frm-email-link-color',
								'action_slug' => 'email_link_color',
							)
						);
						?>
					</div>
					<div class="frm6 frm-mt-sm">
						<label for="frm-email-divider-color"><?php esc_html_e( 'Field Divider', 'formidable' ); ?></label>
						<?php
						new FrmColorpickerStyleComponent(
							'frm_email_divider_color',
							$frm_settings->email_divider_color,
							array(
								'id'          => 'frm-email-divider-color',
								'action_slug' => 'email_divider_color',
							)
						);
						?>
					</div>
				</div>

				<button type="button" class="frm-reset-colors-btn frm-button-secondary frm-mt-md" data-values="<?php echo esc_attr( wp_json_encode( $default_colors ) ); ?>"><?php esc_html_e( 'Reset to default', 'formidable-pro' ); ?></button>
			</td>
		</tr>

		<tr>
			<th class="frm-pt-xl frm-pr-lg">
				<strong><?php esc_html_e( 'Typography', 'formidable' ); ?></strong>
				<p class="description frm-mt-xs"><?php esc_html_e( 'Choose the style that’s applied to all text in email notifications.', 'formidable' ); ?></p>
			</th>

			<td class="frm-pt-xl">
				<div class="frm_grid_container">
					<div class="frm6">
						<label for="frm-email-font" class="screen-reader-text"><?php esc_html_e( 'Font family', 'formidable-pro' ); ?></label>
						<select id="frm-email-font" name="frm_email_font">
							<?php FrmProEmailStylesController::print_options( $fonts, $frm_settings->email_font ); ?>
						</select>
					</div>
				</div>
			</td>
		</tr>
	</tbody>
</table>

<hr class="frm-mt-md frm-mb-md" />

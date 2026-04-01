<?php
/**
 * Radio image options
 *
 * @package FrmSurveys
 *
 * @var array $field Field array.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

// phpcs:disable WordPress.WP.I18n.TextDomainMismatch
$display_format        = FrmField::get_option( $field, 'image_options' );
$text_align            = FrmField::get_option( $field, 'text_align' );
$image_size            = FrmField::get_option( $field, 'image_size' );
$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );
$hide_option_text      = FrmField::get_option( $field, 'hide_image_text' );
$image_align           = FrmField::get_option( $field, 'image_align' );
$separate_value        = FrmField::get_option( $field, 'separate_value' );

FrmFieldsHelper::show_radio_display_format( $field );
?>
<div class="frm_grid_container frm_priority_field_choices frm_surveys_priority_field_choices frm-mt-2xs" data-fid="<?php echo intval( $field['id'] ); ?>">
	<?php
	$classes = 'frm_form_field';
	if ( 'buttons' !== $display_format || 'center' === $text_align || ! $use_images_in_buttons ) {
		$classes .= ' frm_hidden';
	}
	?>
	<p id="frm_image_align_<?php echo intval( $field['id'] ); ?>_container" class="frm6 frm6_followed <?php echo esc_attr( $classes ); ?>">
		<label for="frm_image_align_<?php echo intval( $field['id'] ); ?>">
			<?php esc_html_e( 'Image alignment', 'formidable-surveys' ); ?>
		</label>

		<select
			name="field_options[image_align_<?php echo intval( $field['id'] ); ?>]"
			id="frm_image_align_<?php echo intval( $field['id'] ); ?>"
			class="frm_surveys_image_align_option"
			data-fid="<?php echo intval( $field['id'] ); ?>"
			data-type="image"
		>
			<option value="left" <?php selected( $image_align, 'left' ); ?>><?php esc_html_e( 'Left', 'formidable-pro' ); ?></option>
			<option value="right" <?php selected( $image_align, 'right' ); ?>><?php esc_html_e( 'Right', 'formidable-pro' ); ?></option>
		</select>
	</p>

	<?php
	$classes = 'frm_form_field';
	if ( 'buttons' !== $display_format ) {
		$classes .= ' frm_hidden';
	}
	?>
	<p id="frm_text_align_<?php echo intval( $field['id'] ); ?>_container" class="<?php echo esc_attr( $classes ); ?>">
		<label for="frm_text_align_<?php echo intval( $field['id'] ); ?>">
			<?php esc_html_e( 'Text alignment', 'formidable-surveys' ); ?>
		</label>
		<select
			name="field_options[text_align_<?php echo intval( $field['id'] ); ?>]"
			id="frm_text_align_<?php echo intval( $field['id'] ); ?>"
			class="frm_surveys_text_align_option"
			data-fid="<?php echo intval( $field['id'] ); ?>"
		>
			<option value="left" <?php selected( $text_align, 'left' ); ?>><?php esc_html_e( 'Left', 'formidable-pro' ); ?></option>
			<option value="center" <?php selected( $text_align, 'center' ); ?>><?php esc_html_e( 'Center', 'formidable-pro' ); ?></option>
			<option value="right" <?php selected( $text_align, 'right' ); ?>><?php esc_html_e( 'Right', 'formidable-pro' ); ?></option>
		</select>
	</p>
	<p id="frm_use_images_in_buttons_<?php echo intval( $field['id'] ); ?>_container" class="frm-mb-0 <?php echo esc_attr( $classes ); ?>">
		<label for="frm_use_images_in_buttons_<?php echo intval( $field['id'] ); ?>">
			<input
				type="checkbox"
				name="field_options[use_images_in_buttons_<?php echo intval( $field['id'] ); ?>]"
				id="frm_use_images_in_buttons_<?php echo intval( $field['id'] ); ?>"
				value="1"
				class="frm_use_images_in_button"
				data-fid="<?php echo intval( $field['id'] ); ?>"
				<?php checked( $use_images_in_buttons, 1 ); ?>
			/>
			<?php esc_html_e( 'Use images in buttons', 'formidable-surveys' ); ?>
		</label>
	</p>

	<?php
	$classes = 'frm_form_field frm_image_size_container frm_image_size_' . intval( $field['id'] );
	$columns = array(
		'small'  => __( 'Small', 'formidable-pro' ),
		'medium' => __( 'Medium', 'formidable-pro' ),
		'large'  => __( 'Large', 'formidable-pro' ),
		'xlarge' => __( 'Extra Large', 'formidable-pro' ),
	);
	if ( 1 !== intval( $display_format ) ) {
		$classes .= ' frm_hidden';
	}
	?>
	<p id="frm_image_size_<?php echo intval( $field['id'] ); ?>_container" class="<?php echo esc_attr( $classes ); ?>">
		<label for="field_options_image_size_<?php echo intval( $field['id'] ); ?>">
			<?php esc_html_e( 'Image Size', 'formidable-pro' ); ?>
		</label>
		<select name="field_options[image_size_<?php echo intval( $field['id'] ); ?>]" id="field_options_image_size_<?php echo intval( $field['id'] ); ?>" class="frm_field_options_image_size">
			<?php foreach ( $columns as $col => $col_label ) { ?>
				<option value="<?php echo esc_attr( $col ); ?>" <?php selected( $image_size, $col ); ?>>
					<?php echo esc_html( $col_label ); ?>
				</option>
			<?php } ?>
		</select>
	</p>

	<?php
	$classes = 'frm_form_field';
	if ( 1 !== intval( $display_format ) ) {
		$classes .= ' frm_hidden';
	}
	?>
	<p id="frm_hide_option_text_<?php echo intval( $field['id'] ); ?>_container" class="frm-mb-0 <?php echo esc_attr( $classes ); ?>">
		<label for="hide_image_text_<?php echo intval( $field['id'] ); ?>">
			<input
				type="checkbox"
				name="field_options[hide_image_text_<?php echo intval( $field['id'] ); ?>]"
				id="hide_image_text_<?php echo intval( $field['id'] ); ?>"
				value="1" class="frm_hide_image_text"
				<?php checked( $hide_option_text, 1 ); ?>
			/>
			<?php esc_html_e( 'Hide option text', 'formidable-surveys' ); ?>
		</label>
	</p>
</div>

<p class="frm_form_field frm6_followed frm-my-sm frm_sep_val_<?php echo esc_attr( $field['type'] ); ?>">
	<label class="frm-h-stack-xs" for="separate_value_<?php echo intval( $field['id'] ); ?>">
		<input
			type="checkbox"
			name="field_options[separate_value_<?php echo intval( $field['id'] ); ?>]"
			id="separate_value_<?php echo intval( $field['id'] ); ?>"
			value="1"
			<?php checked( $separate_value, 1 ); ?>
			class="frm_toggle_sep_values"
		/>
		<span><?php esc_html_e( 'Use separate values', 'formidable-pro' ); ?></span>
		<?php
		FrmSurveys\helpers\FieldsHelper::show_svg_tooltip(
			// Translators: shortcode to show value.
			sprintf(
				__( 'Add a separate value to use for calculations, email routing, saving to the database, and many other uses. The option values are saved while the option labels are shown in the form. Use [%s] to show the saved value in emails or views.', 'formidable-pro' ),
				$field['id'] . ' show=value'
			),
			array(
				'data-placement' => 'right',
				'class' => 'frm-flex',
			)
		);
		?>
	</label>
</p>
<?php
// phpcs:enable

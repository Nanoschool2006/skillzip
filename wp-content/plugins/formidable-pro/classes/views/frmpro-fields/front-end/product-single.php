<?php
/**
 * View for product single in front end
 *
 * @package FormidablePro
 *
 * @var array $field
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$image_options_enabled = (bool) FrmField::get_option( $field, 'image_options' );

echo '<div class="frm_single_product_wrap">';

foreach ( $field['options'] as $opt_key => $opt ) {
	$field_val = FrmFieldsHelper::get_value_from_array( $opt, $opt_key, $field );
	$price     = FrmProFieldProduct::get_price_from_array( $opt, $opt_key, $field );
	$return    = array();
	$image     = FrmProImages::single_option_details( compact( 'opt', 'opt_key', 'field', 'return' ) );
	$opt       = FrmFieldsHelper::get_label_from_array( $opt, $opt_key, $field );

	if ( $image_options_enabled ) {
		if ( ! empty( $image['url'] ) ) {
			?>
			<img src="<?php echo esc_url( $image['url'] ); ?>" alt="<?php echo esc_attr( $opt ); ?>" class="frm_single_product_image" />
			<?php
		} else {
			echo '<div class="frm_empty_url">';
			FrmAppHelper::include_svg();
			FrmAppHelper::kses_echo( FrmProImages::get_image_icon_markup(), 'all' );
			echo '</div>';
		}
	}
	?>

	<?php
	if ( ! FrmField::get_option( $field, 'hide_image_text' ) ) {
		?>
		<p class="frm_single_product_label">
			<?php echo esc_html( FrmProFieldProduct::get_displayed_product_label( $opt, $price, $field ) ); ?></span>
		</p>
		<?php
	}
	?>

	<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" id="<?php echo esc_attr( $html_id ); ?>" value="<?php echo esc_attr( $field_val ); ?>" data-frmprice="<?php echo esc_attr( $price ); ?>" <?php do_action( 'frm_field_input_html', $field ); ?> />
	<?php
	break; // We want just the first
}//end foreach
echo '</div>';

<?php
/**
 * Date calculation settings
 *
 * @package formidable-dates
 * @since 2.0
 *
 * @var array $field               Field data.
 * @var array $default_value_types Default value types data.
 * @var array $args                See {@see FrmDatesCalculationController::add_default_value_type_box()}.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$class = 'frm-date-calc-box-' . $field['id'] . ( isset( $default_value_types['date_calc']['current'] ) ? '' : ' frm_hidden' );
?>
<div class="<?php echo esc_attr( $class ); ?>">
	<?php FrmDatesCalculationHelper::calc_settings_modal_callback( $field ); ?>
</div>
<?php
unset( $class );

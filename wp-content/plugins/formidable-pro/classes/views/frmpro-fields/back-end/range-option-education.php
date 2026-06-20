<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$upgrade_data        = FrmProFieldDate::get_dates_add_on_upgrade_link_data( true );
$noallow             = ! empty( $upgrade_data['data-oneclick'] ) ? '' : ' frm_noallow';
$label_atts          = $upgrade_data;
$label_atts['class'] = 'frm_help frm-mb-0 frm-field-date-or-time-enable-range frm_show_upgrade' . $noallow;
$label_atts['title'] = $range_option_tooltip;
?>
<div class="frm_form_field">
	<label <?php FrmAppHelper::array_to_html_params( $label_atts, true ); ?>>
		<input type="checkbox" value="1" disabled />
		<?php
		/* translators: %s: Field type */
		printf( esc_html__( '%s Range', 'formidable-pro' ), esc_html( ucfirst( $field['type'] ) ) );
		?>
	</label>
</div>

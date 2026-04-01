<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

$form_id                = FrmAppHelper::get_param( 'id', '', 'get', 'absint' );
if ( empty( $form_id ) ) {
	$form_id = FrmAppHelper::get_param( 'form', '', 'get', 'absint' );
}
$landing_page_post_name = FrmLandingSettingsController::get_landing_page_post_name( $form_id );

if ( ! empty( $landing_page_post_name ) ) {
	?>
	<a href="<?php echo esc_attr( home_url() ) . '/' . esc_attr( $landing_page_post_name ); ?>" target="_blank">
		<?php esc_html_e( 'On Landing Page', 'formidable-landing' ); ?>
	</a>
	<?php
	return;
}

$current_tab = FrmAppHelper::get_param( 't', '', 'get', 'sanitize_text_field' );
if ( 'landing_settings' === $current_tab ) {
	// Don't show link to landing page settings if already on it.
	return;
}
?>
<a href="<?php echo esc_attr( FrmLandingSettingsController::get_settings_url( $form_id ) ); ?>">
	<?php esc_html_e( 'Generate Landing Page', 'formidable' ); ?>
</a>

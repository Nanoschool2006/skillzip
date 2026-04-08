<?php
/**
 * Settings for frm logs
 *
 * @since 1.0.1
 *
 * @see FrmLogSettingsController
 * @package formidable-logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<?php if ( $frmlog_settings->auto_clear_log && wp_next_scheduled( 'frmlog_auto_clear' ) ) : ?>
	<p class="howto">
		<?php echo esc_html__( 'Next occurrence will be at', 'formidable-logs' ) . ' ' . esc_html( $data ); ?>
	</p>
<?php endif; ?>
<p>
	<label for="frm_auto_clear_log">
		<input type="checkbox" name="frm_auto_clear_log" id="frm_auto_clear_log" value="1" <?php checked( $frmlog_settings->auto_clear_log, 1 ); ?> />
		<?php
		esc_html_e( 'Automatically delete log entries every 30 days', 'formidable-logs' );
		if ( is_callable( 'FrmAppHelper::tooltip_icon' ) ) {
			FrmAppHelper::tooltip_icon( esc_html__( 'This action will keep the small portion of the latest logs at the end of the 30 days.', 'formidable-logs' ) );
		}
		?>
	</label>
</p>

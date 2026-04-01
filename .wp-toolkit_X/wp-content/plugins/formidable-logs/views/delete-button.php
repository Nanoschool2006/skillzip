<?php
/**
 * Delete button markup
 *
 * @since 1.0.1
 *
 * @package formidable-logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<span class="frm_uninstall">
	<a href="<?php echo esc_url( wp_nonce_url( 'edit.php?post_type=frm_logs&frm_logs_action=destroy_all' ) ); ?>" class="button frm-button-secondary" data-frmcaution="<?php esc_attr_e( 'Heads up', 'formidable-logs' ); ?>" data-frmverify="<?php esc_attr_e( 'ALL logs will be permanently deleted. Want to proceed?', 'formidable-logs' ); ?>">
		<?php esc_html_e( 'Delete All Logs', 'formidable-logs' ); ?>
	</a>
</span>

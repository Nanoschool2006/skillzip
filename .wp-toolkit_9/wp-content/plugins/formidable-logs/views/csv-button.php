<?php
/**
 * CSV button markup
 *
 * @since 1.0.1
 *
 * @package formidable-logs
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( $page_params, admin_url( 'admin-ajax.php' ) ) ) ); ?>" class="button frm-button-secondary">
	<?php esc_html_e( 'Download CSV', 'formidable-logs' ); ?>
</a>

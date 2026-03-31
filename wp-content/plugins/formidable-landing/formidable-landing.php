<?php
/*
Plugin Name: Formidable Landing Pages
Description: Create full page forms without distractions.
Version: 1.0.02
Plugin URI: https://formidableforms.com/
Author URI: https://formidableforms.com/
Author: Strategy11
*/

/**
 * Register autoload for Formidable landing page.
 *
 * @param string $class_name
 * @return void
 */
function frm_forms_landing_autoloader( $class_name ) {
	// Only load Frm classes here
	if ( ! preg_match( '/^FrmLanding.+$/', $class_name ) ) {
		return;
	}

	$filepath = dirname( __FILE__ ) . '/classes/';
	if ( preg_match( '/^.+Helper$/', $class_name ) ) {
		$filepath .= '/helpers/';
	} elseif ( preg_match( '/^.+Controller$/', $class_name ) ) {
		$filepath .= '/controllers/';
	} else {
		$filepath .= '/models/';
	}

	$filepath .= $class_name . '.php';

	if ( file_exists( $filepath ) ) {
		include $filepath;
	}
}

/**
 * @since 1.0.01
 *
 * @return void
 */
function load_formidable_landing() {
	$is_free_installed = function_exists( 'load_formidable_forms' );
	$is_pro_installed  = function_exists( 'load_formidable_pro' );

	if ( ! $is_free_installed ) {
		add_action( 'admin_notices', 'frm_landing_free_not_installed_notice' );
	} elseif ( ! $is_pro_installed ) {
		add_action( 'admin_notices', 'frm_landing_pro_not_installed_notice' );
		$page = FrmAppHelper::get_param( 'page', '', 'get', 'sanitize_text_field' );
		if ( 'formidable' === $page ) {
			add_filter( 'frm_message_list', 'frm_landing_pro_missing_add_message' );
		}
	} else {
		// Add the autoloader
		spl_autoload_register( 'frm_forms_landing_autoloader' );

		FrmLandingAppController::load_hooks();
		FrmLandingSettingsController::load_hooks();
	}
}

/**
 * @since 1.0.01
 *
 * @return void
 */
function frm_landing_free_not_installed_notice() {
	?>
	<div class="error">
		<p>
			<?php esc_html_e( 'Formidable Landing Pages requires Formidable Forms to be installed.', 'formidable-landing' ); ?>
			<a href="<?php echo esc_url( admin_url( 'plugin-install.php?s=formidable+forms&tab=search&type=term' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Install Formidable Forms', 'formidable-landing' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * @since 1.0.01
 *
 * @return void
 */
function frm_landing_pro_not_installed_notice() {
	?>
	<div class="error">
		<p><?php esc_html_e( 'Formidable Landing Pages requires Formidable Forms Pro to be installed.', 'formidable-landing' ); ?></p>
	</div>
	<?php
}

/**
 * @since 1.0.01
 *
 * @param array $messages
 * @return array
 */
function frm_landing_pro_missing_add_message( $messages ) {
	$messages['landing_pro_missing'] = 'Formidable Landing Pages requires Formidable Forms Pro to be installed.';
	return $messages;
}

add_action( 'plugins_loaded', 'load_formidable_landing', 1 );

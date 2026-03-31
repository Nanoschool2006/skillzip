<?php
/**
 * Handle error from SESSION
 *
 * @package FrmReg
 * @since 2.05
 */

/**
 * Class FrmRegSessionErrorController
 */
class FrmRegSessionErrorController {

	/**
	 * Store the error message.
	 *
	 * @var array
	 */
	protected static $errors = array();

	/**
	 * Session key.
	 *
	 * @var string
	 */
	protected static $key = 'frm_reg_errors';

	/**
	 * Adds error to Session.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 */
	public static function add_error_to_session( $code, $message ) {
		if ( ! isset( $_SESSION[ self::$key ] ) ) {
			$_SESSION[ self::$key ] = array();
		}
		$_SESSION[ self::$key ][ $code ] = $message;
	}

	/**
	 * Gets error message from Session.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public static function get_error_from_session( $code ) {
		if ( isset( self::$errors[ $code ] ) ) {
			return self::$errors[ $code ];
		}

		if ( ! isset( $_SESSION[ self::$key ] ) || ! isset( $_SESSION[ self::$key ][ $code ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$message = wp_unslash( $_SESSION[ self::$key ][ $code ] );
		self::$errors[ $code ] = $message;
		unset( $_SESSION[ self::$key ][ $code ] ); // Unset the error from Session right after getting it.
		return $message;
	}

	/**
	 * Maybe starts Session on specific pages.
	 */
	public static function maybe_start_session() {
		if ( ! FrmRegLoginController::is_login_limit_feature_activated() ) {
			return;
		}

		$login_page_id = FrmRegLoginController::is_global_login_page_set();
		if ( ! $login_page_id || ! is_page( $login_page_id ) ) {
			return;
		}

		self::start_session();
	}

	/**
	 * Starts session.
	 */
	public static function start_session() {
		if ( ! session_id() ) {
			session_start();
		}
	}
}

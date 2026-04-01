<?php

class FrmExportViewLink {
	/**
	 * Maybe add export View link after View on the front-end.
	 *
	 * @param string $after_content The View's after content.
	 * @param object $view          View object.
	 * @param string $view_page     Type of View.
	 *
	 * @return string The View's after content, which may have an export link at the end of it.
	 *
	 * @throws Exception May throw an exception.
	 */
	public static function maybe_add_export_view_link( $after_content, $view, $view_page ) {
		$frm_options = get_post_meta( $view->ID, 'frm_options', true );

		if ( ! empty( $frm_options['show_export_view'] ) && 'all' === $view_page && ! empty( $frm_options['view_export_possible'] ) ) {
			$after_content .= self::get_export_view_link_html( $view->ID, $frm_options );
		}

		return $after_content;
	}

	/**
	 * Retrieves HTML with export view link
	 *
	 * @param int   $view_id     Id of the View.
	 * @param array $frm_options The View's options.
	 *
	 * @return false|string Export View link HTML.
	 *
	 * @throws Exception May throw an exception.
	 */
	public static function get_export_view_link_html( $view_id, $frm_options ) {
		$link = self::get_link( $view_id, $frm_options );

		// Get export view link HTML.
		ob_start();
		include FrmExportViewAppController::plugin_path() . '/views/export-view-link.php';
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Returns a link to export the View, with params, when available and appropriate.
	 *
	 * @param int   $view_id     Id of the View.
	 * @param array $frm_options The View's options.
	 *
	 * @return string The full URL of the export View download link, with params, when available and appropriate.
	 *
	 * @throws Exception May throw an exception.
	 */
	private static function get_link( $view_id, $frm_options ) {
		$export_with_params  = isset( $frm_options['export_with_params'] ) ? $frm_options['export_with_params'] : false;
		$query_args_for_link = self::get_query_args_for_link( $view_id, $export_with_params );
		$query_args_for_link = apply_filters( 'frm_export_view_query_args', $query_args_for_link, $view_id );

		// Deprecated hook.
		$query_args_for_link = apply_filters( 'frm-export-view-query-args', $query_args_for_link, $view_id );

		return home_url() . '?' . http_build_query( $query_args_for_link );
	}

	/**
	 * Returns the query args for the export View link, including secure data and params, where available and appropriate.
	 *
	 * @param int  $view_id            Id of View.
	 * @param bool $export_with_params Whether or not params should be included in the export View link.
	 *
	 * @return array Query args associative array.
	 *
	 * @throws Exception May throw an exception.
	 */
	public static function get_query_args_for_link( $view_id, $export_with_params ) {
		$params_to_exclude = array( 'preview', 'preview_id', 'preview_nonce' );

		$query_args_for_link = self::get_encrypted_param( $view_id );

		if ( ! is_array( $_GET ) || ! $export_with_params ) {
			return $query_args_for_link;
		}

		foreach ( $_GET as $key => $value ) {
			if ( in_array( $key, $params_to_exclude ) || '' == $value ) {
				continue;
			}
			$query_args_for_link[ $key ] = $value;
		}

		return $query_args_for_link;
	}

	/**
	 * Returns an array with key "frmdata" and value, args with the View id and nonce(logged-in users) or date (logged-out visitors)
	 *
	 * @param int $view_id Id of View.
	 *
	 * @return array Array of the View id and nonce/date.
	 *
	 * @throws Exception May throw an exception.
	 */
	private static function get_encrypted_param( $view_id ) {
		$secure_args = self::build_secure_args( $view_id );

		return array( 'frmdata' => self::encrypt( $secure_args ) );
	}

	/**
	 * Builds the secure args string with the View ID and nonce (logged-in useres) or date (logged-out visitors)
	 *
	 * @param int $view_id Id of the View.
	 *
	 * @return string Secure args string.
	 */
	private static function build_secure_args( $view_id ) {
		$query_args = array(
			'view'         => $view_id,
			'export_nonce' => is_user_logged_in() ? wp_create_nonce( 'exporting_view' ) : gmdate( 'U' ),
		);

		return http_build_query( $query_args );
	}

	/**
	 * Encrypts a string using secret keys
	 *
	 * @param string $string String to be encrypted.
	 *
	 * @return string Encrypted string.
	 *
	 * @throws Exception May throw an exception.
	 */
	private static function encrypt( $string ) {
		$secret_keys = self::get_secret_keys( true );

		$encryption_params = self::get_encryption_params( $secret_keys );

		$output = base64_encode( openssl_encrypt( $string, $encryption_params['encrypt_method'], $encryption_params['key'], 0, $encryption_params['iv'] ) );
		$output = urlencode( $output );

		return $output;
	}

	/**
	 * Retrieves secret keys: a random number saved in options and a salt from wp-config
	 *
	 * @param bool $create_new_key Whether a new option key should be created if one doesn't exist.
	 *
	 * @return array An array of the random number saved in options and the AUTH_KEY salt from wp-config.
	 *
	 * @throws Exception May throw an exception.
	 */
	private static function get_secret_keys( $create_new_key = false ) {
		$secret_key = get_option( 'frm_export_view_key' );
		if ( ! $secret_key && $create_new_key ) {
			$secret_key = self::create_secret_key();
		}

		return array(
			'key' => defined( 'AUTH_KEY' ) ? AUTH_KEY : 'sd234r324ergaw432rA@Q#Q3',
			'iv'  => $secret_key,
		);
	}

	/**
	 * A random number is generated, saved as an option, and returned.
	 *
	 * @return int Random number.
	 * @throws Exception May throw an exception.
	 */
	private static function create_secret_key() {
		$key = random_int( 1, 99999999 );
		update_option( 'frm_export_view_key', $key );

		return $key;
	}

	/**
	 * Params used in encryption and decryption.
	 *
	 * @param array $secret_keys Array containing key from options and salt from wp-config.
	 *
	 * @return array Params used in encryption and decryption.
	 */
	private static function get_encryption_params( $secret_keys ) {
		return array(
			'encrypt_method' => 'AES-256-CBC',
			'key'            => hash( 'sha256', $secret_keys['key'] ),
			'iv'             => substr( hash( 'sha256', $secret_keys['iv'] ), 0, 16 ),
		);
	}

	/**
	 * Retrieves secure data param from $_GET, decrypts it, and saves the decrypted view id and nonce to the $_GET array.
	 */
	public static function decrypt_secure_data() {
		$secure_param = FrmAppHelper::simple_get( 'frmdata' );
		if ( '' === $secure_param ) {
			return;
		}
		$decrypted_param = self::decrypt( $secure_param );
		parse_str( $decrypted_param, $secure_array );
		if ( isset( $secure_array['view'] ) ) {
			$_GET['view'] = $secure_array['view'];
		}
		if ( isset( $secure_array['export_nonce'] ) ) {
			$_GET['export_nonce'] = $secure_array['export_nonce'];
		}
	}

	/**
	 * Decrypts string using secret keys.
	 *
	 * @param string $string String to be decrypted.
	 *
	 * @return string Decrypted string.
	 *
	 * @throws Exception May throw an exception.
	 */
	private static function decrypt( $string ) {
		if ( '' === $string ) {
			return '';
		}

		$secret_keys = self::get_secret_keys();
		if ( ! is_array( $secret_keys ) || ! isset( $secret_keys['key'] ) || ! isset( $secret_keys['iv'] ) ) {
			return '';
		}

		$encryption_params = self::get_encryption_params( $secret_keys );

		$string = urldecode( $string );
		$output = openssl_decrypt( base64_decode( $string ), $encryption_params['encrypt_method'], $encryption_params['key'], 0, $encryption_params['iv'] );

		return $output;
	}
}

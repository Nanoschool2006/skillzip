<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class FrmGoogleSpreadsheetAppHelper.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetAppHelper {

	/**
	 * Plugin version.
	 *
	 * @since 1.0
	 * @var string plug_version.
	 */
	public static $plug_version = '1.0.5';

	/**
	 * Settings holder.
	 *
	 * @since 1.0
	 *
	 * @var FrmGoogleSpreadsheetSettings|null $settings
	 */
	private static $settings;

	/**
	 * Get plugin version.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	/**
	 * Gets plugin folder name.
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::path() );
	}

	/**
	 * Plugin URL.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url() . '/' . self::plugin_folder();
	}

	/**
	 * Get the plugin path.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function path() {
		return dirname( dirname( __FILE__ ) );
	}

	/**
	 * Get the sheet settings.
	 *
	 * @since 1.0
	 *
	 * @return FrmGoogleSpreadsheetSettings
	 */
	public static function get_settings() {
		if ( ! isset( self::$settings ) ) {
			self::$settings = new FrmGoogleSpreadsheetSettings();
		}
		return self::$settings;
	}

	/**
	 * Change any string to asterisks except the first and last letter.
	 *
	 * @since 1.0
	 *
	 * @param string $string String to convert.
	 * @return string
	 */
	public static function change_string_to_asterisks( $string ) {
		return substr( $string, 0, 1 ) . str_repeat( '*', strlen( $string ) - 2 ) . substr( $string, strlen( $string ) - 1, 1 );
	}

	/**
	 * Get admin JS url.
	 *
	 * @since 1.0
	 *
	 * @return bool|string File path.
	 */
	public static function use_minified_js_file() {
		return self::debug_scripts_are_on() && self::has_unminified_js_url() ? self::has_unminified_js_url() : self::get_minified_js_url();
	}

	/**
	 * Weather admin script debug is enabled.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public static function debug_scripts_are_on() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
	}

	/**
	 * Get the authorization button in setting form.
	 *
	 * @since 1.0
	 *
	 * @param string $client_id Google client ID.
	 * @return array<string>
	 */
	public static function get_setting_form_authorization_data( $client_id ) {
		$auth_settings = get_option( 'formidable_googlespreadsheet_auth' );

		if ( ! empty( $client_id ) && empty( $auth_settings['access_token'] ) ) {
			$auth_url           = self::base_auth_url() . 'auth';
			$redirect_uri       = trailingslashit( home_url() );
			$button['auth_url'] = esc_url_raw( $auth_url . '?response_type=code&prompt=consent&access_type=offline&client_id=' . $client_id . '&redirect_uri=' . $redirect_uri . '&scope=https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive' );
			$button['btn_text'] = esc_html__( 'Authorize', 'formidable-google-sheets' );
			$button['class']    = 'button-secondary frm-button-secondary formidable_googlespreadsheet_authorization';
		} elseif ( ! empty( $client_id ) && ! empty( $auth_settings['access_token'] ) ) {
			$revoke_url         = self::base_auth_url() . 'revoke';
			$button['auth_url'] = esc_url( $revoke_url . '/services/oauth2/revoke' );
			$button['btn_text'] = esc_html__( 'Deauthorize', 'formidable-google-sheets' );
			$button['class']    = 'button-secondary frm-button-secondary formidable_googlespreadsheet_deauthorize';
		} else {
			$button['auth_url'] = esc_url( admin_url( 'admin.php?page=formidable-settings&t=googlespreadsheet_settings' ) );
			$button['btn_text'] = esc_html__( 'Authorize', 'formidable-google-sheets' );
			$button['class']    = 'button-secondary frm-button-secondary formidable_googlespreadsheet_authorization';
		}

		return $button;
	}

	/**
	 * Get the Google URL to use for API requests.
	 *
	 * @since 1.0
	 * @return string
	 */
	public static function base_auth_url() {
		return 'https://accounts.google.com/o/oauth2/';
	}

	/**
	 * Escape a " in a csv with another ".
	 *
	 * @since 1.0
	 *
	 * @param string|int $value value.
	 * @param array      $context context.
	 * @return string
	 */
	public static function escape_csv( $value, $context = array() ) {
		if ( ! is_string( $value ) ) {
			return (string) $value;
		}

		if ( ! empty( $context['field_id'] ) && '=' === $context['field_id'][0] ) {
			// If the setting begins with =, do not escape it by default.
			$escape_value = false;
		} else {
			$escape_value = true;
		}

		/**
		 * Allow someone to opt out of escaping the value.
		 *
		 * @since 1.0.5
		 *
		 * @param string $escape_value
		 * @param array  $context
		 */
		$escape_value = apply_filters( 'frm_googlespreadsheet_escape_value', $escape_value, array_merge( compact( 'value' ), $context ) );
		if ( ! $escape_value ) {
			return $value;
		}

		// No to complicated condition and loops to have a better benchmark here.
		if ( '=' === $value[0] ) {
			// escape the = to prevent vulnerability.
			$value = "'" . $value;
		}

		if ( '+' === $value[0] ) {
			// escape the + to prevent vulnerability.
			$value = "'" . $value;
		}

		if ( '@' === $value[0] ) {
			// escape the @ to prevent vulnerability.
			$value = "'" . $value;
		}

		return $value;
	}

	/**
	 * Get unminified JS url.
	 *
	 * @since 1.0
	 *
	 * @return bool|string File path.
	 */
	private static function has_unminified_js_url() {
		return is_readable( self::path() . '/js/admin/admin.js' ) ? self::plugin_url() . '/js/admin/admin.js' : false;
	}

	/**
	 * Get minified admin JS url.
	 *
	 * @since 1.0
	 *
	 * @return string File path.
	 */
	private static function get_minified_js_url() {
		return self::plugin_url() . '/js/admin/admin.min.js';
	}

	/**
	 * Shows spreadsheet files dropdown.
	 *
	 * @since 1.0.5
	 *
	 * @param array $args Contains the following keys: files, selected, name, id.
	 * @return void
	 */
	public static function show_files_dropdown( $args ) {
		if ( is_array( $args['files'] ) ) {
			$args['files'] = self::sort_files( $args['files'] );
		}

		if ( method_exists( 'FrmAppHelper', 'maybe_autocomplete_options' ) ) {
			FrmAppHelper::maybe_autocomplete_options(
				array(
					'placeholder' => __( '&mdash; Select &mdash;', 'formidable-google-sheets' ),
					'source'      => $args['files'],
					'selected'    => $args['selected'],
					'name'        => $args['name'],
					'id'          => $args['id'],
					'value_key'   => 'id',
				)
			);
			return;
		}
		?>
		<select name="<?php echo esc_attr( $args['name'] ); ?>">
			<option value=""><?php esc_html_e( '&mdash; Select &mdash;', 'formidable-google-sheets' ); ?></option>
			<?php if ( ! is_wp_error( $args['files'] ) ) : ?>
				<?php foreach ( $args['files'] as $spreadsheet ) : ?>
					<option value="<?php echo esc_attr( $spreadsheet['id'] ); ?>" <?php selected( $args['selected'], $spreadsheet['id'] ); ?>>
						<?php echo esc_html( $spreadsheet['label'] ); ?>
					</option>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<?php
	}

	/**
	 * Sort files.
	 *
	 * @param array $files Files.
	 * @return array
	 */
	private static function sort_files( $files ) {
		usort(
			$files,
			function ( $a, $b ) {
				return strcasecmp( self::prepare_label_for_sorting( $a['label'] ), self::prepare_label_for_sorting( $b['label'] ) );
			}
		);
		return $files;
	}

	/**
	 * @since 1.0.5
	 *
	 * @param string $label Label.
	 * @return string
	 */
	private static function prepare_label_for_sorting( $label ) {
		$stripped = preg_replace( '/[^\p{L}\p{N}\p{P}\s]+/u', '', $label );
		if ( ! is_string( $stripped ) ) {
			return $label;
		}
		return trim( $stripped );
	}
}

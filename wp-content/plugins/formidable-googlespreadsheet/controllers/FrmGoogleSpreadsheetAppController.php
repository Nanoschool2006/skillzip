<?php
/**
 * FrmGoogleSpreadsheetAppController class.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetAppController {

	/**
	 * Register action in formidable actions.
	 *
	 * @since 1.0
	 *
	 * @param array<string> $actions actions.
	 * @return array<string>
	 */
	public static function register_actions( $actions ) {
		$actions['googlespreadsheet'] = 'FrmGoogleSpreadsheetAction';

		include_once FrmGoogleSpreadsheetAppHelper::path() . '/models/FrmGoogleSpreadsheetAction.php';

		return $actions;
	}

	/**
	 * Load the basic admin hooks to allow updating and display notices.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmGoogleSpreadsheetUpdate::load_hooks();
		}
	}

	/**
	 * Enqueue js for setting form and action.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function enqueue_admin_js() {
		if ( ! self::is_form_settings_page() ) {
			return;
		}

		$init_autocomplete_on_new_action_hook = version_compare( FrmAppHelper::plugin_version(), '6.22', '<=' );

		wp_register_script( 'frmgooglespreadsheet_admin', FrmGoogleSpreadsheetAppHelper::use_minified_js_file(), array( 'jquery', 'formidable_dom', 'wp-i18n' ), FrmGoogleSpreadsheetAppHelper::plugin_version(), true );
		wp_localize_script(
			'frmgooglespreadsheet_admin',
			'frmgooglespreadsheetGlobal',
			array(
				'nonce'                           => wp_create_nonce( 'frmgooglespreadsheet_ajax' ),
				'homeURL'                         => esc_url( home_url() ),
				'initAutocompleteOnNewActionHook' => $init_autocomplete_on_new_action_hook,
			)
		);

		wp_enqueue_script( 'frmgooglespreadsheet_admin' );
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'formidable-google-sheets', false, FrmGoogleSpreadsheetAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Trigger action.
	 *
	 * @since 1.0
	 *
	 * @param stdClass     $action    Referenced WP_Post object.
	 * @param stdClass     $entry entry.
	 * @param array<mixed> $form form.
	 * @return void
	 */
	public static function trigger_googlespreadsheet( $action, $entry, $form ) {
		add_filter( 'frmpro_fields_replace_shortcodes', __CLASS__ . '::prepare_entry_output', 10, 4 );
		add_filter( 'frm_display_value', __CLASS__ . '::prepare_display_value', 11, 2 );

		$settings       = $action->post_content;
		$entry_id       = $entry->id;
		$spreadsheet_id = $settings['spreadsheet_id'];
		$sheet_id       = $settings['sheet_id'];

		$vars = self::prepare_mapped_values( $action->post_content['fields'], $entry );

		if ( ! empty( $spreadsheet_id ) && ! empty( $sheet_id ) ) {
			$row             = array();
			$manager         = new FrmGoogleSpreadsheetManager();
			$default_headers = $manager->get_googlespreadsheet_headers( $spreadsheet_id, $sheet_id );
			if ( ! empty( $default_headers ) ) {
				foreach ( $default_headers as $key => $name ) {
					$row[ $key ] = isset( $vars[ $key ] ) ? $vars[ $key ] : '';
				}

				$final_row = array( $row );
				$manager->add_new_row( $spreadsheet_id, $sheet_id, $final_row, $action, $entry );
			}
		}

		remove_filter( 'frmpro_fields_replace_shortcodes', __CLASS__ . '::prepare_entry_output' );
		remove_filter( 'frm_display_value', __CLASS__ . '::prepare_display_value', 11 );
	}

	/**
	 * Match fields of spreadsheet cols and the form in action setting.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function match_fields() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );

		$form_id = FrmAppHelper::get_post_param( 'form_id', '', 'absint' );
		// Spreadsheet Id can be an alphanumeric with a mix of other strings so it's best to sanitize with sanitize_text_default here.
		$spreadsheet_id = FrmAppHelper::get_post_param( 'spreadsheet_id' );
		$sheet_id       = FrmAppHelper::get_post_param( 'sheet_id' );

		if ( ! $form_id ) {
			wp_die();
		}

		$action_key = FrmAppHelper::get_post_param( 'action_key' );
		if ( ! $action_key ) {
			wp_die();
		}

		$action_control = FrmFormActionsController::get_form_actions( 'googlespreadsheet' );
		$action_control->_set( $action_key );

		$manager = new FrmGoogleSpreadsheetManager();
		$headers = $manager->get_googlespreadsheet_headers( $spreadsheet_id, $sheet_id );

		if ( ! empty( $headers ) ) {
			include FrmGoogleSpreadsheetAppHelper::path() . '/views/action-settings/_match_fields.php';
		} else {
			include FrmGoogleSpreadsheetAppHelper::path() . '/views/action-settings/_match_fields_error.php';
		}

		wp_die();
	}

	/**
	 * Clear cache in action setting for selected sheet.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public static function clear_cache() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frmgooglespreadsheet_ajax', 'security' );

		delete_transient( 'frm_googlespreadsheet_files' );
		delete_transient( 'frm_googlespreadsheet_sheets' );
		delete_transient( 'frm_googlespreadsheet_headers' );
		wp_die();
	}

	/**
	 * Get raw image url of signature instead of html tag for export.
	 *
	 * @since 1.0
	 *
	 * @param array<mixed> $replace_with Field content.
	 * @param int          $tag Field id.
	 * @param array<mixed> $atts Shortcode attributes.
	 * @param stdClass     $field Field.
	 *
	 * @return array<mixed>
	 */
	public static function prepare_entry_output( $replace_with, $tag, $atts, $field ) {
		if ( ! class_exists( 'FrmSigAppController' ) ) {
			return $replace_with;
		}

		if ( 'signature' === $field->type ) {
			$replace_with = FrmSigAppController::get_final_signature_value( $replace_with, array( 'use_html' => false ) );
		}

		return $replace_with;
	}

	/**
	 * Decode HTML entities in total field display values.
	 * This way a euro symbol doesn't appear in the sheet as "&#8364;".
	 *
	 * @since 1.0.2
	 *
	 * @param array|string $value Current display value.
	 * @param object|array $field Target field.
	 * @return array|string
	 */
	public static function prepare_display_value( $value, $field ) {
		$field_type = FrmField::get_field_type( $field );
		if ( 'total' === $field_type && is_string( $value ) ) {
			$value = html_entity_decode( $value );
		}
		return $value;
	}

	/**
	 * Prepare action mapped value and entry to send.
	 *
	 * @since 1.0
	 *
	 * @param array    $fields Action fields.
	 * @param stdClass $entry entry.
	 * @return array
	 */
	public static function prepare_mapped_values( $fields, $entry ) {
		$vars = array();

		foreach ( $fields as $field_tag => $field_id ) {
			$vars[ $field_tag ] = self::process_shortcodes(
				array(
					'entry' => $entry,
					'value' => $field_id,
				)
			);

			if ( empty( $vars[ $field_tag ] ) ) {
				continue;
			}

			$vars[ $field_tag ] = wp_strip_all_tags( FrmGoogleSpreadsheetAppHelper::escape_csv( $vars[ $field_tag ], compact( 'field_id', 'entry' ) ) );
		}

		return $vars;
	}

	/**
	 * Allow entry values, default values, and other shortcodes.
	 *
	 * @param array $atts - Includes value (required), form, entry.
	 * @return string|int
	 */
	private static function process_shortcodes( $atts ) {
		$value = $atts['value'];
		if ( strpos( $value, '[' ) === false ) {
			return $value;
		}

		if ( is_callable( 'FrmProFieldsHelper::replace_non_standard_formidable_shortcodes' ) ) {
			FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $value );
		}

		if ( isset( $atts['entry'] ) && ! empty( $atts['entry'] ) ) {
			if ( ! isset( $atts['form'] ) ) {
				$atts['form'] = FrmForm::getOne( $atts['entry']->form_id );
			}
			$value = apply_filters( 'frm_content', $value, $atts['form'], $atts['entry'] );
		}

		$value = do_shortcode( $value );
		return $value;
	}

	/**
	 * Check if the current page is the form settings page
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	private static function is_form_settings_page() {
		$page   = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		$action = FrmAppHelper::simple_get( 'frm_action', 'sanitize_title' );
		return ( 'formidable-settings' === $page || 'formidable' === $page && 'settings' === $action );
	}

	/**
	 * Allow Google Spreadsheet to be triggered by the automation.
	 *
	 * @since 1.0.5
	 *
	 * @param array $actions Actions supporting automation.
	 * @return array
	 */
	public static function add_google_spreadsheet_to_automation( $actions ) {
		$actions[] = 'googlespreadsheet';
		return $actions;
	}
}

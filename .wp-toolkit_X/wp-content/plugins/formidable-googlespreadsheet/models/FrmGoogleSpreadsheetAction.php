<?php

/**
 * FrmGoogleSpreadsheetAction Class.
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetAction extends FrmFormAction {

	/**
	 * Create form action.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_googlesheets_icon frm_icon_font',
			'limit'    => 99,
			'active'   => true,
			'priority' => 25,
			'event'    => array_keys( array_diff_key( $this->trigger_labels(), array_flip( array( 'delete', 'update', 'draft' ) ) ) ),
		);

		$this->FrmFormAction( 'googlespreadsheet', __( 'Google Sheets', 'formidable-google-sheets' ), $action_ops );
	}

	/**
	 * Echo the settings update form.
	 *
	 * @since 1.0
	 *
	 * @param object $form_action form action instance.
	 * @param array  $args args.
	 * @todo Possibility of authorizing oath2 in action.
	 * @return void
	 */
	public function form( $form_action, $args = array() ) {
		$form = $args['form'];

		$authsettings = get_option( 'formidable_googlespreadsheet_auth' );
		$settings     = FrmGoogleSpreadsheetAppHelper::get_settings();

		// Check when it's unauthorized return.
		if ( '' === $settings->frm_googlespreadsheet_client_id || '' === $settings->frm_googlespreadsheet_client_secret || empty( $authsettings['access_token'] ) ) {
			include FrmGoogleSpreadsheetAppHelper::path() . '/views/action-settings/_action_unauthorized.php';
			return;
		}

		$list_options   = empty( $form_action->post_content ) ? array() : $form_action->post_content;
		$spreadsheet_id = $list_options['spreadsheet_id'];
		$sheet_id       = $list_options['sheet_id'];
		// Files.
		$manager = new FrmGoogleSpreadsheetManager();
		$files   = $manager->get_googlespreadsheet_files( $form_action );
		$sheets  = array();

		if ( $spreadsheet_id ) {
			// Sheets in File.
			$sheets = $manager->get_googlespreadsheet_sheets( $spreadsheet_id, $form_action );
			if ( is_string( $sheets ) ) {
				$sheets = json_decode( $sheets );
			}
			// Maybe sheets are manipulated by customer, so we need to ensure that the saved sheet exists or not.
			$sheets_exist = false;
			$first_id     = 0;
			if ( ! empty( $sheets ) ) {
				foreach ( $sheets as $sid => $sheet ) {
					if ( ! is_object( $sheet ) || empty( $sheet->id ) ) {
						continue;
					}
					$first_id = $first_id ? $first_id : $sheet->id;
					if ( $sheet->id === $sheet_id ) {
						$sheets_exist = true;
						break;
					}
				}
				// If the sheet didn't exist, pick first available one for display..
				if ( false === $sheets_exist ) {
					$sheet_id = $first_id;
				}
			}

			if ( $sheet_id ) {
				$headers = $manager->get_googlespreadsheet_headers( $spreadsheet_id, $sheet_id );
			}
		}

		$action_control = $this;

		include FrmGoogleSpreadsheetAppHelper::path() . '/views/action-settings/options.php';
	}

	/**
	 * Get default values for action.
	 *
	 * @since 1.0
	 *
	 * @return array<mixed>
	 */
	public function get_defaults() {
		return array(
			'spreadsheet_id' => '',
			'sheet_id'       => '',
			'fields'         => array(),
		);
	}

	/**
	 * Get switch fields.
	 *
	 * @since 1.0
	 *
	 * @return array<mixed>
	 */
	public function get_switch_fields() {
		return array(
			'fields' => array(),
			'groups' => array( array( 'id' ) ),
		);
	}
}

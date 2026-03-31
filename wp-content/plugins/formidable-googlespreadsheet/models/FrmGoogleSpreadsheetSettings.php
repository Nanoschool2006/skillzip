<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmGoogleSpreadsheetSettings class
 *
 * @since 1.0
 */
class FrmGoogleSpreadsheetSettings extends FrmSettings {

	/**
	 * Option name.
	 *
	 * @since 1.0
	 *
	 * @var string $option_name
	 */
	public $option_name = 'frm_googlespreadsheet_options';

	/**
	 * Setting holder.
	 *
	 * @since 1.0
	 *
	 * @var string $frm_googlespreadsheet_client_id
	 */
	public $frm_googlespreadsheet_client_id;

	/**
	 * Setting holder.
	 *
	 * @since 1.0
	 *
	 * @var string $frm_googlespreadsheet_client_secret
	 */
	public $frm_googlespreadsheet_client_secret;

	/**
	 * Update values based on changes or initial result.
	 *
	 * @since 1.0
	 *
	 * @param array<string|int> $params post value.
	 * @return void
	 */
	public function update( $params ) {
		$this->frm_googlespreadsheet_client_id     = sanitize_text_field( (string) $params['frm_googlespreadsheet_client_id'] );
		$this->frm_googlespreadsheet_client_secret = sanitize_text_field( (string) $params['frm_googlespreadsheet_client_secret'] );
	}

	/**
	 * Store options to db.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function store() {
		update_option( $this->option_name, $this, 'no' );

		set_transient( $this->option_name, $this );
	}

}

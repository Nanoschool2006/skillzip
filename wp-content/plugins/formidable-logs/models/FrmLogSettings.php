<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
/**
 * Class FrmLogSettings.
 *
 * @since 1.0.1
 */
class FrmLogSettings extends FrmSettings {

	/**
	 * Option name.
	 *
	 * @since 1.0.1
	 *
	 * @var string $option_name
	 */
	public $option_name = 'frmlog_options';

	/**
	 * Setting holder.
	 *
	 * @since 1.0.1
	 *
	 * @var string|int $auto_clear_log
	 */
	public $auto_clear_log;

	public function __construct( $args = array() ) {
		$settings = get_option( $this->option_name );

		if ( ! is_object( $settings ) ) {
			$settings = array(
				'auto_clear_log' => '',
			);
		}

		foreach ( $settings as $setting_name => $setting ) {
			$this->{$setting_name} = $setting;
			unset( $setting_name, $setting );
		}
	}

	/**
	 * Update values based on changes or initial result.
	 *
	 * @since 1.0.1
	 *
	 * @param array<string|int> $params post value.
	 * @return void
	 */
	public function update( $params ) {
		$this->auto_clear_log = isset( $params['frm_auto_clear_log'] ) && 1 === (int) $params['frm_auto_clear_log'] ? 1 : '';
	}

	/**
	 * Store frmlogs options to db.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function store() {
		update_option( $this->option_name, $this, 'no' );

		set_transient( $this->option_name, $this );
	}

}

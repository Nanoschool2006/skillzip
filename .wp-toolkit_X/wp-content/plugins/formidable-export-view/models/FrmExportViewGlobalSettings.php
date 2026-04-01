<?php

class FrmExportViewGlobalSettings {
	/**
	 * All the saved values from the settings.
	 *
	 * @var object $settings
	 */
	public $settings;

	/**
	 * FrmExportViewGlobalSettings constructor.
	 */
	public function __construct() {
		$this->set_options();
	}

	/**
	 * Returns the default options
	 *
	 * @return array Array of default options
	 */
	public function default_options() {
		return array(
			'csv_format'       => '',
			'csv_col_sep'      => ',',
			'export_view_id'   => array(),
			'frequency'        => '',
			'frequency_period' => '',
			'upload_dir'       => '',
		);
	}

	/**
	 * Sets settings property with saved and default options, if not already set.
	 */
	public function set_options() {
		if ( is_object( $this->settings ) ) {
			return;
		}

		$this->settings = new stdClass();

		$settings         = $this->get_saved_settings();
		$default_settings = $this->default_options();

		$this->populate_settings_with_saved_value_or_default( $default_settings, $settings );
	}

	/**
	 * Retrieves settings from the db or returns an empty object.
	 *
	 * @return mixed|stdClass Settings object, which may be empty if no options saved in the db.
	 */
	private function get_saved_settings() {
		$settings = get_option( 'frm_export_view_options' );

		if ( ! $settings ) {
			return new stdClass();
		}

		if ( ! is_object( $settings ) ) { // Workaround for W3 total cache conflict.
			$settings = unserialize( serialize( $settings ) );
		}

		if ( is_object( $settings ) ) {
			return $settings;
		}

		return new stdClass();
	}

	/**
	 * Populates the settings property with saved value, if it exists, or the default for each setting.
	 *
	 * @param array  $default_settings Array of default settings.
	 * @param object $settings         Object with saved settings.
	 */
	public function populate_settings_with_saved_value_or_default( $default_settings, $settings ) {
		foreach ( $default_settings as $setting => $default ) {
			if ( is_object( $settings ) && isset( $settings->{$setting} ) ) {
				$this->settings->{$setting} = $settings->{$setting};
			}

			if ( ! isset( $this->settings->{$setting} ) ) {
				$this->settings->{$setting} = $default;
			}
		}
		unset( $setting, $default );

	}

	/**
	 * Returns the settings property
	 *
	 * @return object The settings property
	 */
	public function get_options() {
		return $this->settings;
	}

	/**
	 * Updates the settings property with the $params values
	 *
	 * @param array $params Array of new settings values.
	 */
	public function update( $params ) {
		$settings = $this->default_options();
		foreach ( $settings as $setting => $default ) {
			if ( isset( $params[ 'frm_export_view_' . $setting ] ) ) {
				$this->settings->{$setting} = $params[ 'frm_export_view_' . $setting ];
			} else {
				$this->settings->{$setting} = $default;
			}
		}
	}

	/**
	 * Saves the settings property in the db.
	 */
	public function store() {
		update_option( 'frm_export_view_options', $this->settings );
		do_action( 'frm_export_view_options_saved', $this->settings );
	}
}

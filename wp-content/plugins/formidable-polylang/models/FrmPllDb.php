<?php

/**
 * Class FrmPllDb
 *
 * @since 1.06
 */

class FrmPllDb {

	/**
	 * The database version before upgrading.
	 *
	 * @var int
	 */
	private $current_db_version = 0;

	/**
	 * The database version we're migrating to.
	 *
	 * @var int
	 */
	private $new_db_version = 2;

	/**
	 * The name of the option we're storing the database version in.
	 *
	 * @var string
	 */
	private $option_name = 'frm_pll_db';

	public function __construct() {
		$this->set_db_version();
	}

	/**
	 * Check if Polylang settings need migrating
	 *
	 * @since 1.06
	 *
	 * @return bool
	 */
	public function need_to_migrate_settings() {
		return $this->current_db_version < $this->new_db_version;
	}

	/**
	 * Migrate data to current version, if needed
	 *
	 * @since 1.06
	 *
	 * @return void
	 */
	public function migrate() {
		if ( $this->need_to_migrate_settings() ) {
			$this->migrate_to_2();
			update_option( $this->option_name, $this->new_db_version );
		}
	}

	/**
	 * Set the current db version property
	 * If it does not yet exist in the database, this will return 0
	 *
	 * @since 1.06
	 *
	 * @return void
	 */
	private function set_db_version() {
		$this->current_db_version = (int) get_option( $this->option_name );
	}

	/**
	 * Convert single row of polylang form translations to multiple rows (one for each form)
	 *
	 * @since 1.06
	 *
	 * @return void
	 */
	private function migrate_to_2() {
		$option_name = 'frm_polylang_strings';
		$all_strings = get_option( $option_name );

		if ( ! $all_strings || ! is_array( $all_strings ) ) {
			return;
		}

		foreach ( $all_strings as $form_id => $form_strings ) {
			update_option( $option_name . '_' . $form_id, $form_strings );
		}

		delete_option( $option_name );
	}
}

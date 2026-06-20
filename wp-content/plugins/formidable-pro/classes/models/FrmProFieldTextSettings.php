<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 2.03.05
 */
class FrmProFieldTextSettings extends FrmProFieldSettings {

	/**
	 * Set the use_key property for a hidden field
	 *
	 * @since 2.03.05
	 */
	protected function set_use_key() {
		$this->use_key = false;
	}
}

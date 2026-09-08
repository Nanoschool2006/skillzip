<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 *
 * Adds created_at column to API tokens table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/** @var $this TD_DB_Migration */
$this->add_or_modify_column( 'tva_tokens', 'created_at', 'DATETIME NULL DEFAULT NULL AFTER `status`' );

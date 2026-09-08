<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

/**
 * @var $this TD_DB_Migration
 */
$this->add_or_modify_column( 'transactions', 'debug_id', 'VARCHAR(64) NULL DEFAULT NULL' );

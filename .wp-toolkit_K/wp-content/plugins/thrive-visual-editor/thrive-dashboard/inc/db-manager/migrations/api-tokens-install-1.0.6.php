<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 *
 * Creates the API tokens table for Dashboard-level token authentication.
 * Uses CREATE TABLE IF NOT EXISTS, so safe for sites that already have
 * the table from Thrive Apprentice.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/** @var $this TD_DB_Migration */
$this->create_table(
	'tva_tokens',
	'
	`id` INT NOT NULL AUTO_INCREMENT,
	`key` VARCHAR(255) NOT NULL,
	`name` VARCHAR(255) NOT NULL,
	`status` INT NOT NULL,
	PRIMARY KEY (`id`)
	'
);

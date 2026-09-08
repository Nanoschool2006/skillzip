<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

/**
 * @var $this TD_DB_Migration
 */
$this->create_table(
	'paypal_seen_webhooks',
	'
	`id` BIGINT(20) NOT NULL AUTO_INCREMENT,
	`event_id` VARCHAR(64) NOT NULL,
	`created_at` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00",
	PRIMARY KEY (`id`),
	UNIQUE KEY `event_id` (`event_id`)
	', true
);

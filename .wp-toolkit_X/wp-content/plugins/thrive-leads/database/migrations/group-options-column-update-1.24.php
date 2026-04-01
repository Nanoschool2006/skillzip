<?php
/**
 * Update Group options table field group
 *
 * @package thrive-leads
 */

defined( 'TVE_LEADS_DB_UPGRADE' ) || exit();

$test_item_table = tve_leads_table_name( 'group_options' );

global $wpdb;

$wpdb->query( "ALTER TABLE {$test_item_table} MODIFY COLUMN `group` bigint(20) unsigned NOT NULL;" );

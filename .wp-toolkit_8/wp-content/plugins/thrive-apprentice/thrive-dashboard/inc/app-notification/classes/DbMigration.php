<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class TD_DbMigration {
	static $tableName = 'td_app_notifications';

	public static function migrate() {
		global $wpdb;

		// Check completion flag to prevent running on every page load
		$migration_status = get_option( 'thrive_mail_notifications_migration_status' );
		if ( $migration_status === 'completed' ) {
			return;
		}

		$charsetCollate = $wpdb->get_charset_collate();
		$table = $wpdb->prefix . static::$tableName;
		$has_error = false;

		if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ) {
			$sql = "CREATE TABLE $table (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`slug` varchar(255) NOT NULL,
				`title` text NOT NULL,
				`content` longtext NOT NULL,
				`type` varchar(64) NOT NULL,
				`level` text NOT NULL,
				`notification_id` bigint(20) unsigned DEFAULT NULL,
				`notification_name` varchar(255) DEFAULT NULL,
				`start` datetime DEFAULT NULL,
				`end` datetime DEFAULT NULL,
				`button1_label` varchar(255) DEFAULT NULL,
				`button1_action` varchar(255) DEFAULT NULL,
				`button2_label` varchar(255) DEFAULT NULL,
				`button2_action` varchar(255) DEFAULT NULL,
				`dismissed` tinyint(1) NOT NULL DEFAULT 0,
				`created` datetime NOT NULL,
				`updated` datetime NOT NULL,
				PRIMARY KEY (id),
				KEY ian_dates (start, end),
				KEY ian_type (type),
				KEY ian_dismissed (dismissed)
			) $charsetCollate;";
			dbDelta($sql);
			// Check if table was created successfully
			if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table ) {
				$has_error = true;
			}
		}

		// Only drop index if it exists AND migration hasn't completed yet
		if ( ! $has_error ) {
			$index_exists = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = 'ian_slug'");
			if ( $index_exists ) {
				$result = $wpdb->query("ALTER TABLE $table DROP INDEX ian_slug");
				if ( $result === false ) {
					$has_error = true;
				}
			}
		}

		// Only modify level column if it's not already nullable
		if ( ! $has_error ) {
			$level_column = $wpdb->get_row("SHOW COLUMNS FROM $table WHERE Field = 'level'");
			if ( $level_column && $level_column->Null === 'NO' ) {
				$result = $wpdb->query("ALTER TABLE $table MODIFY COLUMN `level` text DEFAULT NULL");
				if ( $result === false ) {
					$has_error = true;
				}
			}
		}

		// Set completion flag only after successful execution
		if ( ! $has_error ) {
			update_option( 'thrive_mail_notifications_migration_status', 'completed' );
		}
	}
}

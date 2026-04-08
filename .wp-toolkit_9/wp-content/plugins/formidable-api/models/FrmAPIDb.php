<?php

class FrmAPIDb {
	public static $db_version = 2;
	public static $option_name = 'frmapi_db_version';

	/**
	 * @return void
	 */
	public function upgrade( $old_db_version = false ) {
		global $wpdb;

		// $frm_db_version is the version of the database we're moving to
		$frm_db_version = self::$db_version;
		$old_db_version = (int) $old_db_version;
		if ( ! $old_db_version ) {
			$old_db_version = get_option( self::$option_name );
		}

		if ( $frm_db_version != $old_db_version ) {
			$this->migrate_data( $frm_db_version, $old_db_version );
			update_option( self::$option_name, $frm_db_version );
		}
	}

	/**
	 * @param integer $frm_db_version
	 *
	 * @return void
	 */
	private function migrate_data( $frm_db_version, $old_db_version ) {
		$migrations = array( 2 );
		foreach ( $migrations as $migration ) {
			if ( $frm_db_version >= $migration && $old_db_version < $migration ) {
				$function_name = 'migrate_to_' . $migration;
				$this->$function_name();
			}
		}
	}

	/**
	 * Move all the existing frm_api post types to the form actions
	 *
	 * @return void
	 */
	private function migrate_to_2() {
		$hooks = get_posts(
			array(
				'post_type'     => 'frm_api',
				'post_status'   => 'publish',
			)
		);

		$events = array(
			'frm_after_create_entry'   => 'create',
			'frm_after_update_entry'   => 'update',
			'frm_before_destroy_entry' => 'delete',
		);

		foreach ( $hooks as $hook ) {
			$form_id = get_post_meta( $hook->ID, 'frm_form_id', true );
			$api_key = get_post_meta( $hook->ID, 'frm_api_key', true );
			$event = $hook->post_title;
			if ( ! isset( $events[ $event ] ) ) {
				continue;
			}

			$new_action = array(
				'post_type'    => 'frm_form_actions',
				'post_excerpt' => 'api',
				'post_title'   => __( 'Send API data', 'frmapi' ),
				'menu_order'   => $form_id,
				'post_status'  => 'publish',
				'post_content' => array(
					'url'         => $hook->post_excerpt,
					'api_key'     => $api_key,
					'data_format' => $hook->post_content,
					'event'       => $events[ $event ],
				),
				'post_name'    => $form_id . '_api_' . $hook->ID,
			);

			$new_action['post_content'] = FrmAppHelper::prepare_and_encode( $new_action['post_content'] );

			$exists = get_posts(
				array(
					'name'          => $new_action['post_name'],
					'post_type'     => $new_action['post_type'],
					'post_status'   => $new_action['post_status'],
					'numberposts'   => 1,
				)
			);

			if ( empty( $exists ) ) {
				FrmDb::save_json_post( $new_action );
			}
			unset( $new_action );
		}
	}
}

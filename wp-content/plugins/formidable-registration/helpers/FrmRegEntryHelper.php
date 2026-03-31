<?php

class FrmRegEntryHelper {

	/**
	 * Check if a profile is being updated
	 *
	 * @since 2.0
	 * @param int $form_id
	 * @return bool
	 */
	public static function is_profile_update( $form_id ) {
		return self::is_user_id_posted( $form_id );
	}

	/**
	 * Get the posted userID value
	 *
	 * @since 2.0
	 * @param int    $form_id            Form ID.
	 * @param int    $repeater_id        Repeater field ID.
	 * @param string $repeater_entry_key Repeater entry key in the item meta.
	 *
	 * @return int
	 */
	public static function get_posted_user_id( $form_id, $repeater_id = 0, $repeater_entry_key = '' ) {
		$user_id_field = self::get_user_id_field_for_form( $form_id );
		if ( ! $user_id_field ) {
			return 0;
		}

		$user_id   = 0;
		$item_meta = FrmAppHelper::get_post_param( 'item_meta' );
		if ( isset( $item_meta[ $user_id_field ] ) ) {
			$user_id = (int) $item_meta[ $user_id_field ];
		} elseif ( $repeater_id && $repeater_entry_key && isset( $item_meta[ $repeater_id ][ $repeater_entry_key ][ $user_id_field ] ) ) {
			$user_id = (int) $item_meta[ $repeater_id ][ $repeater_entry_key ][ $user_id_field ];
		}

		return $user_id;
	}

	/**
	 * Check if field is selected and posted in a registration setting
	 *
	 * @since 2.0
	 *
	 * @param stdClass $entry
	 * @param string $opt
	 *
	 * @return bool
	 */
	public static function is_field_selected_and_value_saved( $entry, $opt ) {
		return is_numeric( $opt ) && isset( $entry->metas[ $opt ] ) && ! empty( $entry->metas[ $opt ] );
	}

	/**
	 * Check if a non-zero userID is posted. It should only be posted when a user is updating their profile.
	 *
	 * @since 2.0
	 * @param int $form_id
	 * @return boolean
	 */
	private static function is_user_id_posted( $form_id ) {
		return self::get_posted_user_id( $form_id ) !== 0;
	}

	/**
	 * Get the userID field from a form
	 * This will not get repeating or embedded userID fields
	 *
	 * @since 2.0
	 * @param int $form_id
	 * @return int
	 */
	public static function get_user_id_field_for_form( $form_id ) {
		$where = array(
			'type' => 'user_id',
			'form_id' => $form_id,
		);
		$user_id_field = FrmDb::get_var( 'frm_fields', $where, 'id' );

		return (int) $user_id_field;
	}
}

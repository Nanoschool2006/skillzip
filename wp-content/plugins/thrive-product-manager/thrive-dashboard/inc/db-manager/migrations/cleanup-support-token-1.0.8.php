<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Clean up leftovers from the removed support-login token feature (#3766).
 *
 * Runs once on sites that previously generated a support token: removes the
 * support user (we can no longer use that access), the options the feature
 * stored, and the now handler-less daily cron event.
 *
 * The server-side token is intentionally NOT revoked here; that data is dropped
 * on the Thrive Themes side.
 */

/* 1. Remove the support user(s) created when a token was activated.
 *
 * Identify them ONLY by the _thrive_support_user meta flag - the support-login
 * feature always set it when it created the user. We deliberately do NOT match
 * on the support@thrivethemes.com email: that would delete any account that
 * happens to use that address (e.g. a real staff admin), on every site, even
 * ones that never used support-login. The meta flag is the authoritative signal.
 *
 * Reassign any content the support user authored to a surviving administrator -
 * do NOT let wp_delete_user() destroy it. Support staff sometimes built pages or
 * Theme Builder templates while logged in via the token, so those posts are
 * owned by the support user, and wp_delete_user( $id ) with no second argument
 * DELETES every post owned by the user. Passing a reassign target keeps that
 * content on the site while still removing the user.
 */
if ( ! function_exists( 'wp_delete_user' ) ) {
	require_once ABSPATH . 'wp-admin/includes/user.php';
}

$support_users = get_users( array( 'meta_key' => '_thrive_support_user', 'meta_value' => 1 ) );

if ( $support_users ) {
	$support_ids = array_map( static function ( $user ) {
		return (int) $user->ID;
	}, $support_users );

	/* Pick the lowest-ID administrator that is NOT a support user to inherit the
	 * content. 0 means "no surviving admin found". */
	$reassign_to = 0;
	foreach ( get_users( array(
		'role'    => 'administrator',
		'orderby' => 'ID',
		'order'   => 'ASC',
		'fields'  => 'ID',
	) ) as $admin_id ) {
		if ( ! in_array( (int) $admin_id, $support_ids, true ) ) {
			$reassign_to = (int) $admin_id;
			break;
		}
	}

	foreach ( $support_ids as $support_id ) {
		if ( $support_id === $reassign_to ) {
			continue;
		}
		if ( $reassign_to ) {
			wp_delete_user( $support_id, $reassign_to );
		}
		/* No surviving admin to inherit the content: leave the user in place
		 * rather than delete their posts. The cleanup below still runs. */
	}
}

/* 2. Remove the options the feature stored. */
delete_option( 'thrive_token_support' );
delete_option( 'tve_dash_generated_token' );
delete_option( 'ttw_temp_key' );

/* 3. Clear the orphaned daily cron event. */
wp_clear_scheduled_hook( 'thrive_token_cron' );

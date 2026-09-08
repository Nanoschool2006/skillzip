<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-ultimatum
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

if ( ! class_exists( 'Thrive_Ultimatum_API' ) ) {

	/**
	 * Public API for Thrive Ultimatum.
	 *
	 * Provides validated, documented methods for inter-plugin communication.
	 * All methods are static and can be called without instantiation.
	 *
	 * Usage:
	 *     $result = Thrive_Ultimatum_API::start_campaign( $user_id, $campaign_id );
	 *     if ( is_wp_error( $result ) ) {
	 *         // Handle error: $result->get_error_code(), $result->get_error_message()
	 *     }
	 */
	class Thrive_Ultimatum_API {

		/**
		 * Source identifier for the current API call.
		 *
		 * Set before calling tu_start_campaign() so that the hook in
		 * tve_ult_save_email_log() can include the correct source.
		 *
		 * @var string|null
		 */
		public static $current_source = null;

		/**
		 * User ID for the current API call.
		 *
		 * tu_start_campaign() MD5-hashes the email before passing it to
		 * tve_ult_save_email_log(), making it impossible to resolve the
		 * user from email at that point. This carries the validated user ID.
		 *
		 * @var int|null
		 */
		public static $current_user_id = null;

		/**
		 * Start an evergreen countdown campaign for a user.
		 *
		 * Validates that the user and campaign exist, that the campaign is
		 * an evergreen type and currently running, and that it has not already
		 * been triggered or ended for the user. Delegates to tu_start_campaign()
		 * on success.
		 *
		 * Fires `thrivethemes_ultimatum_campaign_triggered` on success.
		 *
		 * Error codes:
		 * - `invalid_user`      (404) User does not exist.
		 * - `invalid_campaign`  (404) Campaign does not exist or is not evergreen.
		 * - `campaign_inactive` (400) Campaign is not currently running.
		 * - `already_triggered` (409) Campaign already active for this user.
		 * - `campaign_ended`    (409) Campaign already ended for this user.
		 * - `start_failed`      (500) Failed to start campaign (internal error).
		 *
		 * @param int $user_id     WordPress user ID.
		 * @param int $campaign_id Ultimatum campaign ID.
		 *
		 * @return true|WP_Error True on success, WP_Error on failure.
		 */
		public static function start_campaign( int $user_id, int $campaign_id ) {
			tve_debug_log( "Ultimatum_API::start_campaign called: user_id={$user_id}, campaign_id={$campaign_id}" );

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				tve_debug_log( "Ultimatum_API::start_campaign validation failed: invalid_user (user_id={$user_id})" );

				return new WP_Error(
					'invalid_user',
					__( 'User does not exist.', 'thrive-ult' ),
					array( 'status' => 404 )
				);
			}

			$campaign = tve_ult_get_campaign( $campaign_id, array( 'get_settings' => true ) );
			if ( empty( $campaign ) ) {
				tve_debug_log( "Ultimatum_API::start_campaign validation failed: invalid_campaign (campaign_id={$campaign_id})" );

				return new WP_Error(
					'invalid_campaign',
					__( 'Campaign does not exist.', 'thrive-ult' ),
					array( 'status' => 404 )
				);
			}

			if ( $campaign->type !== TVE_Ult_Const::CAMPAIGN_TYPE_EVERGREEN ) {
				tve_debug_log( "Ultimatum_API::start_campaign validation failed: not evergreen (type={$campaign->type})" );

				return new WP_Error(
					'invalid_campaign',
					__( 'Campaign is not an evergreen campaign.', 'thrive-ult' ),
					array( 'status' => 404 )
				);
			}

			if ( $campaign->status !== TVE_Ult_Const::CAMPAIGN_STATUS_RUNNING ) {
				tve_debug_log( "Ultimatum_API::start_campaign validation failed: campaign_inactive (status={$campaign->status})" );

				return new WP_Error(
					'campaign_inactive',
					__( 'Campaign is not currently running.', 'thrive-ult' ),
					array( 'status' => 400 )
				);
			}

			$email           = $user->user_email;
			$encrypted_email = md5( $email );
			$log             = tve_ult_get_email_log( $campaign_id, $encrypted_email );

			if ( ! empty( $log ) ) {
				if ( ! empty( $log['end'] ) && (int) $log['end'] === 1 ) {
					tve_debug_log( "Ultimatum_API::start_campaign skipped: campaign_ended (user_id={$user_id}, campaign_id={$campaign_id})" );

					return new WP_Error(
						'campaign_ended',
						__( 'Campaign has already ended for this user.', 'thrive-ult' ),
						array( 'status' => 409 )
					);
				}

				tve_debug_log( "Ultimatum_API::start_campaign skipped: already_triggered (user_id={$user_id}, campaign_id={$campaign_id})" );

				return new WP_Error(
					'already_triggered',
					__( 'Campaign is already active for this user.', 'thrive-ult' ),
					array( 'status' => 409 )
				);
			}

			self::$current_source  = 'api';
			self::$current_user_id = $user_id;
			$started               = tu_start_campaign( $campaign_id, $email );
			self::$current_source  = null;
			self::$current_user_id = null;

			if ( ! $started ) {
				tve_debug_log( "Ultimatum_API::start_campaign failed: start_failed (user_id={$user_id}, campaign_id={$campaign_id})" );

				return new WP_Error(
					'start_failed',
					__( 'Failed to start campaign.', 'thrive-ult' ),
					array( 'status' => 500 )
				);
			}

			tve_debug_log( "Ultimatum_API::start_campaign success: user_id={$user_id}, campaign_id={$campaign_id}" );

			return true;
		}
	}
}

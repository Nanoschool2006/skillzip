<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

if ( ! class_exists( 'Thrive_Apprentice_API' ) ) {

	/**
	 * Public API for Thrive Apprentice.
	 *
	 * Provides validated, documented methods for inter-plugin communication.
	 * All methods are static and can be called without instantiation.
	 *
	 * Usage:
	 *     $result = Thrive_Apprentice_API::grant_access( $user_id, $product_id );
	 *     if ( is_wp_error( $result ) ) {
	 *         // Handle error: $result->get_error_code(), $result->get_error_message()
	 *     }
	 */
	class Thrive_Apprentice_API {

		/**
		 * Source identifier set during API calls.
		 *
		 * Used by internal hooks to determine call origin.
		 *
		 * @var string|null
		 */
		public static $current_source = null;

		/**
		 * User ID set during API calls.
		 *
		 * Used by internal hooks when the current user context differs.
		 *
		 * @var int|null
		 */
		public static $current_user_id = null;

		/**
		 * Grant a user access to a product.
		 *
		 * Validates that the user and product exist and that the user does not
		 * already have access before delegating to the internal enrolment method.
		 * Fires `thrivethemes_apprentice_access_granted` on success.
		 *
		 * @param int    $user_id    WordPress user ID.
		 * @param int    $product_id Apprentice product ID.
		 * @param string $source     Source identifier for tracking. Default 'api'.
		 *
		 * @return true|WP_Error True on success, WP_Error on failure.
		 */
		public static function grant_access( int $user_id, int $product_id, string $source = 'api' ) {
			tve_debug_log( "Apprentice_API::grant_access called: user_id={$user_id}, product_id={$product_id}, source={$source}" );

			if ( $product_id <= 0 ) {
				tve_debug_log( "Apprentice_API::grant_access validation failed: invalid_product (product_id={$product_id})" );

				return new WP_Error(
					'invalid_product',
					__( 'Product does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				tve_debug_log( "Apprentice_API::grant_access validation failed: invalid_user (user_id={$user_id})" );

				return new WP_Error(
					'invalid_user',
					__( 'User does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			$product = new \TVA\Product( $product_id );
			if ( ! $product->get_id() ) {
				tve_debug_log( "Apprentice_API::grant_access validation failed: invalid_product (product_id={$product_id})" );

				return new WP_Error(
					'invalid_product',
					__( 'Product does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			/* Check if user already has access */
			$original_user_id = get_current_user_id();
			tva_access_manager()->set_tva_user( $user );
			wp_set_current_user( $user_id );
			$already_has_access = tva_access_manager()->set_product( $product )->check_rules();
			wp_set_current_user( $original_user_id );

			if ( $already_has_access ) {
				tve_debug_log( "Apprentice_API::grant_access skipped: access_exists (user_id={$user_id}, product_id={$product_id})" );

				return new WP_Error(
					'access_exists',
					__( 'User already has access to this product.', 'thrive-apprentice' ),
					array( 'status' => 409 )
				);
			}

			/* Signal source to internal hooks */
			static::$current_source  = $source;
			static::$current_user_id = $user_id;

			TVA_Customer::enrol_user_to_product( $user_id, $product_id );

			static::$current_source  = null;
			static::$current_user_id = null;

			/* Re-verify access was actually granted */
			tva_access_manager()->set_tva_user( $user );
			wp_set_current_user( $user_id );
			$access_granted = tva_access_manager()->set_product( $product )->check_rules();
			wp_set_current_user( $original_user_id );

			if ( ! $access_granted ) {
				tve_debug_log( "Apprentice_API::grant_access failed: grant_failed (user_id={$user_id}, product_id={$product_id})" );

				return new WP_Error(
					'grant_failed',
					__( 'Failed to grant access.', 'thrive-apprentice' ),
					array( 'status' => 500 )
				);
			}

			tve_debug_log( "Apprentice_API::grant_access success: user_id={$user_id}, product_id={$product_id}" );

			return true;
		}

		/**
		 * Revoke a user's access to a product.
		 *
		 * Validates that the user and product exist and that the user currently
		 * has access before delegating to the internal removal method.
		 * Fires `thrivethemes_apprentice_access_revoked` on success.
		 *
		 * Note: Access granted via WP role integration cannot be revoked
		 * programmatically. The internal method will silently skip those.
		 *
		 * @param int    $user_id    WordPress user ID.
		 * @param int    $product_id Apprentice product ID.
		 * @param string $source     Source identifier for tracking. Default 'api'.
		 *
		 * @return true|WP_Error True on success, WP_Error on failure.
		 */
		public static function revoke_access( int $user_id, int $product_id, string $source = 'api' ) {
			tve_debug_log( "Apprentice_API::revoke_access called: user_id={$user_id}, product_id={$product_id}, source={$source}" );

			if ( $product_id <= 0 ) {
				tve_debug_log( "Apprentice_API::revoke_access validation failed: invalid_product (product_id={$product_id})" );

				return new WP_Error(
					'invalid_product',
					__( 'Product does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				tve_debug_log( "Apprentice_API::revoke_access validation failed: invalid_user (user_id={$user_id})" );

				return new WP_Error(
					'invalid_user',
					__( 'User does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			$product = new \TVA\Product( $product_id );
			if ( ! $product->get_id() ) {
				tve_debug_log( "Apprentice_API::revoke_access validation failed: invalid_product (product_id={$product_id})" );

				return new WP_Error(
					'invalid_product',
					__( 'Product does not exist.', 'thrive-apprentice' ),
					array( 'status' => 404 )
				);
			}

			/* Check if user currently has access */
			$original_user_id = get_current_user_id();
			tva_access_manager()->set_tva_user( $user );
			wp_set_current_user( $user_id );
			$has_access = tva_access_manager()->set_product( $product )->check_rules();
			wp_set_current_user( $original_user_id );

			if ( ! $has_access ) {
				tve_debug_log( "Apprentice_API::revoke_access skipped: no_access (user_id={$user_id}, product_id={$product_id})" );

				return new WP_Error(
					'no_access',
					__( 'User does not have access to this product.', 'thrive-apprentice' ),
					array( 'status' => 409 )
				);
			}

			/* Signal source to internal hooks */
			static::$current_source  = $source;
			static::$current_user_id = $user_id;

			TVA_Customer::remove_user_from_product( $user, $product );

			static::$current_source  = null;
			static::$current_user_id = null;

			/* Re-verify access was actually revoked */
			tva_access_manager()->set_tva_user( $user );
			wp_set_current_user( $user_id );
			$still_has_access = tva_access_manager()->set_product( $product )->check_rules();
			wp_set_current_user( $original_user_id );

			if ( $still_has_access ) {
				tve_debug_log( "Apprentice_API::revoke_access failed: revoke_failed (user_id={$user_id}, product_id={$product_id})" );

				return new WP_Error(
					'revoke_failed',
					__( 'Access could not be fully revoked. This can happen when access is granted via WordPress role integration or another non-revocable source.', 'thrive-apprentice' ),
					array( 'status' => 403 )
				);
			}

			tve_debug_log( "Apprentice_API::revoke_access success: user_id={$user_id}, product_id={$product_id}" );

			return true;
		}

		/**
		 * Check if a user has access to a product.
		 *
		 * Returns false for invalid parameters rather than throwing errors.
		 * Temporarily switches the current user context to evaluate access rules.
		 *
		 * @param int $user_id    WordPress user ID.
		 * @param int $product_id Apprentice product ID.
		 *
		 * @return bool True if user has active access, false otherwise.
		 */
		public static function user_has_access( int $user_id, int $product_id ): bool {
			tve_debug_log( "Apprentice_API::user_has_access called: user_id={$user_id}, product_id={$product_id}" );

			if ( $product_id <= 0 ) {
				tve_debug_log( "Apprentice_API::user_has_access result: false (invalid product_id)" );

				return false;
			}

			$user = get_userdata( $user_id );
			if ( ! $user ) {
				tve_debug_log( "Apprentice_API::user_has_access result: false (invalid user_id)" );

				return false;
			}

			$product = new \TVA\Product( $product_id );
			if ( ! $product->get_id() ) {
				tve_debug_log( "Apprentice_API::user_has_access result: false (product not found)" );

				return false;
			}

			$original_user_id = get_current_user_id();
			tva_access_manager()->set_tva_user( $user );
			wp_set_current_user( $user_id );
			$has_access = tva_access_manager()->set_product( $product )->check_rules();
			wp_set_current_user( $original_user_id );

			$result = (bool) $has_access;
			tve_debug_log( "Apprentice_API::user_has_access result: " . ( $result ? 'true' : 'false' ) );

			return $result;
		}

		/**
		 * Check if a user has completed a course.
		 *
		 * Uses a fast user meta check first, falling back to full progress
		 * calculation if the meta key is not set.
		 *
		 * @param int $user_id   WordPress user ID.
		 * @param int $course_id Apprentice course ID.
		 *
		 * @return bool True if user completed the course, false otherwise.
		 */
		public static function user_completed_course( int $user_id, int $course_id ): bool {
			tve_debug_log( "Apprentice_API::user_completed_course called: user_id={$user_id}, course_id={$course_id}" );

			if ( $course_id <= 0 ) {
				tve_debug_log( "Apprentice_API::user_completed_course result: false (invalid course_id)" );

				return false;
			}

			if ( ! get_userdata( $user_id ) ) {
				tve_debug_log( "Apprentice_API::user_completed_course result: false (invalid user_id)" );

				return false;
			}

			/* Fast path: check completion meta directly */
			if ( TVA_Customer::get_user_completed_meta( $user_id, $course_id ) ) {
				tve_debug_log( "Apprentice_API::user_completed_course result: true (fast path via meta)" );

				return true;
			}

			/* Full progress calculation (handles edge cases where meta is missing) */
			$original_user_id = get_current_user_id();
			wp_set_current_user( $user_id );

			$customer  = new TVA_Customer( $user_id );
			$completed = $customer->has_completed_course( $course_id );

			wp_set_current_user( $original_user_id );

			tve_debug_log( "Apprentice_API::user_completed_course result: " . ( $completed ? 'true' : 'false' ) . " (full calculation)" );

			return $completed;
		}
	}
}

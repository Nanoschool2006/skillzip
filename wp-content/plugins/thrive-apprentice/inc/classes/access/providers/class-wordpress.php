<?php

namespace TVA\Access\Providers;

use TVA\Product;

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Wordpress extends Base {

	/**
	 * @var string
	 */
	const KEY = 'wordpress';

	/**
	 * Tracks user+product combos already triggered in the current request.
	 * Prevents duplicate tva_user_receives_product_access fires when a role change
	 * occurs in the same request as a purchase flow (WooCommerce, Stripe, etc.)
	 * that already calls trigger_product_received_access.
	 *
	 * @var array<string, true>
	 */
	private static $_triggered_access = [];

	/**
	 * Constructor function
	 */
	public function __construct() {
		/**
		 * Activate general hooks
		 */
		parent::__construct();

		/**
		 * WordPress role actions
		 */
		add_action( 'add_user_role', [ $this, 'add_user_role' ], 10, 2 );
		add_action( 'remove_user_role', [ $this, 'remove_user_role' ], 10, 2 );
		add_action( 'set_user_role', [ $this, 'set_user_role_handler' ], 10, 3 );

		/**
		 * Track product access triggers from any source (purchases, manual enrollment, etc.)
		 * to prevent this class from re-triggering for the same user+product in the same request.
		 */
		add_action( 'tva_user_receives_product_access', [ $this, 'track_triggered_access' ], 1, 2 );
	}

	/**
	 * Called from "product_added_access" and "product_removed_access" functions from Base
	 * Returns a list of users IDs with access of the corresponding levels
	 *
	 * @param array $levels
	 *
	 * @return array
	 */
	public function get_users_with_access( $levels = [] ) {
		return get_users( [ 'role__in' => $levels, 'fields' => 'ID' ] );
	}

	/**
	 * Callback for when a role is added to a user via WP_User::add_role().
	 *
	 * This hook does NOT fire during user registration (which uses set_role),
	 * so there is no risk of double-triggering with on_user_register.
	 *
	 * @param int    $user_id
	 * @param string $new_role
	 *
	 * @return void
	 */
	public function add_user_role( $user_id, $new_role ) {
		Product::flush_global_cache( [ 'get_protected_products_by_integration', static::KEY ] );

		if ( ! is_super_admin( $user_id ) ) {
			$this->check_product_and_log_changes( $new_role, $user_id, static::STATUS_ACCESS_ADDED );
			$this->maybe_trigger_product_access( $user_id, $new_role );
		}
	}

	/**
	 * Callback for when a user's role is replaced via WP_User::set_role().
	 *
	 * Guards:
	 * 1. Skips new user registrations (empty $old_roles) - on_user_register handles those.
	 * 2. Skips if the role hasn't actually changed (same role re-set).
	 * 3. Skips super admins.
	 *
	 * @param int      $user_id   The user ID.
	 * @param string   $role      The new role.
	 * @param string[] $old_roles The user's previous roles before the change.
	 *
	 * @return void
	 */
	public function set_user_role_handler( $user_id, $role, $old_roles ) {
		if ( empty( $old_roles ) ) {
			return;
		}

		if ( in_array( $role, $old_roles, true ) ) {
			return;
		}

		if ( is_super_admin( $user_id ) ) {
			return;
		}

		Product::flush_global_cache( [ 'get_protected_products_by_integration', static::KEY ] );

		$this->maybe_trigger_product_access( $user_id, $role );
	}

	/**
	 *  Callback for when a role is changed from wordpress for a user
	 *
	 * @param int    $user_id
	 * @param string $old_role
	 *
	 * @return void
	 */
	public function remove_user_role( $user_id, $old_role ) {
		Product::flush_global_cache( [ 'get_protected_products_by_integration', static::KEY ] );

		if ( ! is_super_admin( $user_id ) ) {
			$this->check_product_and_log_changes( $old_role, $user_id, static::STATUS_ACCESS_REVOKED );
		}
	}

	/**
	 * Track when tva_user_receives_product_access fires from any source.
	 *
	 * Records user+product combos so maybe_trigger_product_access() can skip
	 * products already triggered by other flows (e.g., WooCommerce purchase,
	 * Stripe payment, manual enrollment) in the same request.
	 *
	 * @param \WP_User $user       The user object.
	 * @param int      $product_id The product ID.
	 *
	 * @return void
	 */
	public function track_triggered_access( $user, $product_id ) {
		if ( $user instanceof \WP_User ) {
			self::$_triggered_access[ $user->ID . '_' . $product_id ] = true;
		}
	}

	/**
	 * Trigger product access events when a user gains a role matching product access rules.
	 *
	 * Finds products protected by the given WordPress role and fires the
	 * tva_user_receives_product_access action, which enables features like
	 * the course welcome email.
	 *
	 * Safeguards:
	 * 1. Filterable via 'tva_should_trigger_access_on_role_change' - allows external code to disable.
	 * 2. Validates user exists before triggering.
	 * 3. Skips products already triggered in this request (prevents duplicates with purchase flows).
	 *
	 * @param int    $user_id The user ID.
	 * @param string $role    The WordPress role to check against product rules.
	 *
	 * @return void
	 */
	private function maybe_trigger_product_access( $user_id, $role ) {
		/**
		 * Filter: Allow disabling product access triggering on role change.
		 *
		 * @param bool   $should_trigger Whether to trigger product access. Default true.
		 * @param int    $user_id        The user ID receiving the role.
		 * @param string $role           The WordPress role being added.
		 */
		if ( ! apply_filters( 'tva_should_trigger_access_on_role_change', true, $user_id, $role ) ) {
			return;
		}

		$user = get_user_by( 'ID', (int) $user_id );
		if ( ! $user instanceof \WP_User ) {
			return;
		}

		$protected_products = Product::get_protected_products_by_integration( static::KEY );

		if ( empty( $protected_products ) ) {
			return;
		}

		$matched_product_ids = [];

		foreach ( $protected_products as $product ) {
			$product_id = $this->get_matching_product_id( $product, $role, $user_id );

			if ( $product_id ) {
				$matched_product_ids[] = $product_id;
			}
		}

		if ( ! empty( $matched_product_ids ) ) {
			$customer = new \TVA_Customer( $user_id );
			$customer->trigger_product_received_access( $matched_product_ids );
		}
	}

	/**
	 * Check if a product's access rules match the given role and hasn't been triggered yet.
	 *
	 * @param Product $product The product to check.
	 * @param string  $role    The WordPress role to match against.
	 * @param int     $user_id The user ID for dedup tracking.
	 *
	 * @return int|false The product ID if matched and not yet triggered, false otherwise.
	 */
	private function get_matching_product_id( $product, $role, $user_id ) {
		$rules = $product->get_rules_by_integration( static::KEY );
		$rule  = array_pop( $rules );

		if ( empty( $rule['items'] ) ) {
			return false;
		}

		foreach ( $rule['items'] as $rule_item ) {
			if ( empty( $rule_item['id'] ) || $rule_item['id'] !== $role ) {
				continue;
			}

			$product_id = $product->get_id();

			// Skip if already triggered by another flow in this request
			if ( isset( self::$_triggered_access[ $user_id . '_' . $product_id ] ) ) {
				return false;
			}

			return $product_id;
		}

		return false;
	}

	/**
	 * Returns the level change date
	 * USED IN THE MIGRATION PROCESS
	 *
	 * @param int            $user_id
	 * @param int[]|string[] $access_levels
	 *
	 * @return string
	 */
	public function get_level_change_date( $user_id, $access_levels ) {
		global $wpdb;

		return $wpdb->prepare( "SELECT user_registered from $wpdb->users WHERE ID = %d", [ $user_id ] );
	}


	/**
	 * @return bool
	 */
	public static function is_active() {
		return true;
	}
}

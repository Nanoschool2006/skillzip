<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Architect\Cancel_Subscription;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * "Cancel subscription" dynamic link.
 *
 * Registers a Thrive Architect dynamic link (like the Stripe "Customer portal"
 * link) that resolves, for the current course + logged-in user, to the PayPal
 * buyer-cancel REST endpoint URL — but only when the user has an active PayPal
 * vault subscription for that course. PayPal has no hosted billing portal, so the
 * rendered <a> is intercepted by js/frontend.js, which shows a confirmation modal
 * and sends the DELETE request in place (it does not navigate).
 */
class Main {

	/**
	 * @var Main
	 */
	private static $instance;

	/**
	 * Shortcode tag => handler method.
	 *
	 * @var array
	 */
	private $shortcodes = [
		'tva_paypal_cancel_url' => 'cancel_url',
	];

	/**
	 * @var bool
	 */
	public static $is_editor_page = false;

	/**
	 * Per-request cache of a user's orders, keyed by user id. get_current_course_order_id()
	 * can call get_active_subscription_order_id() once per course product, so without this
	 * the same TVA_User order query/objects would be rebuilt repeatedly in one request.
	 *
	 * @var array<int, \TVA_Order[]>
	 */
	protected static $user_orders_cache = [];

	public function __construct() {
		$this->hooks();

		foreach ( $this->shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, [ $this, $function ] );
		}

		static::$is_editor_page = is_editor_page_raw( true );
	}

	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Main();
		}
	}

	public function hooks() {
		add_filter( 'tcb_dynamiclink_data', [ $this, 'tcb_dynamic_link_data' ] );
		add_filter( 'tcb_content_allowed_shortcodes', [ $this, 'content_allowed_shortcodes_filter' ] );
	}

	/**
	 * Expose the "Cancel subscription" option in the dynamic-link picker.
	 */
	public function tcb_dynamic_link_data( $data ) {
		$data['PayPal'] = [
			'links'     => [
				[
					[
						'name'  => __( 'Cancel subscription', 'thrive-apprentice' ),
						'label' => __( 'Cancel subscription', 'thrive-apprentice' ),
						'url'   => '',
						'show'  => true,
						'id'    => 'cancel_subscription',
					],
				],
			],
			'shortcode' => 'tva_paypal_cancel_url',
		];

		return $data;
	}

	/**
	 * Whitelist the shortcode inside the editor so the link resolves there too.
	 */
	public function content_allowed_shortcodes_filter( $shortcodes = [] ) {
		if ( static::$is_editor_page ) {
			$shortcodes = array_merge( $shortcodes, array_keys( $this->shortcodes ) );
		}

		return $shortcodes;
	}

	/**
	 * Resolve the dynamic link to the PayPal buyer-cancel endpoint URL for the
	 * current course, or an empty string when the user has no active subscription.
	 *
	 * The endpoint is DELETE-only; js/frontend.js intercepts the click, confirms,
	 * and sends the DELETE. The empty-string fallback (and the JS hiding empty
	 * links) mean ineligible visitors get no actionable link.
	 *
	 * @param array $attr
	 *
	 * @return string
	 */
	public function cancel_url( $attr ) {
		if ( empty( $attr['id'] ) || $attr['id'] !== 'cancel_subscription' || ! is_user_logged_in() ) {
			return '';
		}

		$order_id = static::get_current_course_order_id();
		if ( $order_id <= 0 ) {
			return '';
		}

		// Bail if the REST namespace isn't resolvable yet — a blank base would yield a
		// broken relative URL that esc_url() wouldn't reject.
		$base = tva_get_route_url( 'paypal' );
		if ( empty( $base ) ) {
			return '';
		}

		return esc_url( $base . '/subscriptions/' . $order_id . '/cancel' );
	}

	/**
	 * Resolve the eligible cancel order id for the course currently being viewed,
	 * checking every product the course is associated with.
	 *
	 * @return int order id, or 0 when the user has no active subscription / no course context.
	 */
	public static function get_current_course_order_id() {
		$course = function_exists( 'tva_course' ) ? tva_course() : null;
		if ( ! $course || ! $course->get_id() ) {
			return 0;
		}

		foreach ( static::get_course_product_ids( $course ) as $product_id ) {
			$order_id = static::get_active_subscription_order_id( $product_id );
			if ( $order_id > 0 ) {
				return $order_id;
			}
		}

		return 0;
	}

	/**
	 * Collect the product term ids a course is associated with.
	 *
	 * Resolves from two sources for robustness: the side-effect-free `return_all`
	 * lookup (the single-product accessor swaps the access-manager product and can
	 * return empty on repeated/re-entrant calls during page render) and the cached
	 * get_product_term(). Results are de-duplicated.
	 *
	 * @param \TVA_Course_V2|object $course
	 *
	 * @return int[]
	 */
	protected static function get_course_product_ids( $course ) {
		$ids = [];

		if ( method_exists( $course, 'get_product' ) ) {
			$all = $course->get_product( true );
			if ( is_array( $all ) ) {
				foreach ( $all as $product ) {
					if ( ! is_object( $product ) || empty( $product->id ) ) {
						continue;
					}
					$ids[] = (int) $product->id;
				}
			}
		}

		if ( method_exists( $course, 'get_product_term' ) ) {
			$term = $course->get_product_term();
			if ( $term instanceof \WP_Term ) {
				$ids[] = (int) $term->term_id;
			}
		}

		return array_values( array_unique( array_filter( $ids ) ) );
	}

	/**
	 * Find the current user's active PayPal vault-subscription order for a product.
	 *
	 * Targets the subscription *anchor* order — the original purchase, whose vault
	 * record holds the subscription_id and has no 'original_order_id' back-reference.
	 * Renewal orders are also COMPLETED and copy the subscription_id, but they carry
	 * an 'original_order_id' (see Payment_Capture_Completed) and are intentionally
	 * skipped so the link maps to one deterministic order regardless of renewals.
	 *
	 * @param int $product_id
	 *
	 * @return int anchor order id, or 0 when the user has no active subscription.
	 */
	public static function get_active_subscription_order_id( $product_id ) {
		$product_id = (int) $product_id;
		$user_id    = get_current_user_id();

		if ( $product_id <= 0 || $user_id <= 0 ) {
			return 0;
		}

		$statuses = [ (int) \TVA_Const::STATUS_COMPLETED, (int) \TVA_Const::STATUS_GRACE_PERIOD ];

		foreach ( static::get_user_orders( $user_id ) as $order ) {
			/** @var \TVA_Order $order */
			if ( ! static::order_is_eligible( $order, $product_id, $statuses ) ) {
				continue;
			}

			$vault = get_option( 'tva_paypal_vault_' . $order->get_id(), [] );
			if ( ! empty( $vault['subscription_id'] ) && empty( $vault['original_order_id'] ) ) {
				return (int) $order->get_id();
			}
		}

		return 0;
	}

	/**
	 * Whether an order is an active PayPal order for the given product (gateway +
	 * status + product match). Extracted so get_active_subscription_order_id() keeps
	 * a single, flat order loop.
	 *
	 * @param \TVA_Order $order
	 * @param int        $product_id
	 * @param int[]      $statuses
	 *
	 * @return bool
	 */
	protected static function order_is_eligible( $order, $product_id, $statuses ) {
		if ( $order->get_gateway() !== \TVA_Const::PAYPAL_GATEWAY ) {
			return false;
		}

		if ( ! in_array( (int) $order->get_status(), $statuses, true ) ) {
			return false;
		}

		foreach ( $order->get_order_items() as $item ) {
			/** @var \TVA_Order_Item $item */
			if ( (int) $item->get_product_id() === $product_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return (and cache for this request) a user's orders. Avoids rebuilding the
	 * TVA_User order query/objects once per course product within a single render.
	 *
	 * @param int $user_id
	 *
	 * @return \TVA_Order[]
	 */
	protected static function get_user_orders( $user_id ) {
		$user_id = (int) $user_id;

		if ( ! isset( static::$user_orders_cache[ $user_id ] ) ) {
			$user                                  = new \TVA_User( $user_id );
			static::$user_orders_cache[ $user_id ] = $user->get_orders();
		}

		return static::$user_orders_cache[ $user_id ];
	}
}

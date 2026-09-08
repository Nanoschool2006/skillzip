<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\PayPal\Events;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Generic
 *
 * Abstract base for all PayPal webhook event handlers.
 * Provides the raw event array, resource accessor, and DB lookup helpers.
 */
abstract class Generic {

	/** @var array Full decoded webhook event payload */
	protected $event;

	/** @var string 'live' or 'test' */
	protected $mode;

	/**
	 * @param array  $event Decoded webhook payload.
	 * @param string $mode  'live' or 'test'.
	 */
	public function __construct( array $event, string $mode = 'live' ) {
		$this->event = $event;
		$this->mode  = $mode;
	}

	/**
	 * Execute the event-specific business logic.
	 */
	abstract public function do_action(): void;

	// -------------------------------------------------------------------------
	// Accessors
	// -------------------------------------------------------------------------

	/**
	 * @return string
	 */
	public function get_event_id(): string {
		return (string) ( $this->event['id'] ?? '' );
	}

	/**
	 * @return string
	 */
	public function get_event_type(): string {
		return (string) ( $this->event['event_type'] ?? '' );
	}

	/**
	 * @return array
	 */
	public function get_resource(): array {
		return is_array( $this->event['resource'] ?? null ) ? $this->event['resource'] : [];
	}

	// -------------------------------------------------------------------------
	// Order lookup helpers
	// -------------------------------------------------------------------------

	/**
	 * Find a TVA_Order by the PayPal Capture ID stored in payment_id.
	 * Used as a fallback lookup by event type e.g. Checkout_Payment_Approval_Reversed.
	 *
	 * NOTE: gateway_order_id is an int column and cannot store alphanumeric PayPal
	 * Order IDs. Phase 2 does not persist the PayPal Order ID to the DB. If a
	 * pre-capture reversal arrives before the order is created, this returns null
	 * (which is the expected, graceful path).
	 *
	 * @param string $paypal_order_id  Treated as a capture ID for the DB lookup.
	 * @return \TVA_Order|null
	 */
	protected function find_order_by_paypal_order_id( string $paypal_order_id ): ?\TVA_Order {
		return $this->find_order_by_capture_id( $paypal_order_id );
	}

	/**
	 * Find a TVA_Order by the PayPal Capture ID stored in payment_id.
	 *
	 * @param string $capture_id
	 * @return \TVA_Order|null
	 */
	protected function find_order_by_capture_id( string $capture_id ): ?\TVA_Order {
		global $wpdb;
		$table = $wpdb->prefix . \TVA_Const::DB_PREFIX . \TVA_Const::ORDERS_TABLE_NAME;
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM `' . $table . '` WHERE `payment_id` = %s AND `gateway` = %s LIMIT 1',
				$capture_id,
				\TVA_Const::PAYPAL_GATEWAY
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		// set_data() calls set_ID() internally when 'ID' key is present — no need to call set_id() separately.
		$order = new \TVA_Order();
		$order->set_data( $row );

		return $order;
	}

	// -------------------------------------------------------------------------
	// Class name resolver
	// -------------------------------------------------------------------------

	/**
	 * Convert a PayPal event_type string to the matching handler class name.
	 *
	 * "PAYMENT.CAPTURE.COMPLETED" → "TVA\PayPal\Events\Payment_Capture_Completed"
	 *
	 * @param string $event_type
	 * @return string Fully-qualified class name.
	 */
	public static function get_class_name( string $event_type ): string {
		$parts = explode( '.', strtolower( $event_type ) );
		$parts = array_map( static function( $part ) {
			return str_replace( ' ', '_', ucwords( str_replace( '-', ' ', $part ) ) );
		}, $parts );

		return 'TVA\\PayPal\\Events\\' . implode( '_', $parts );
	}
}

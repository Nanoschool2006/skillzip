<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

/**
 * Class TVA_Transaction
 */
class TVA_Transaction {

	/**
	 * @var null
	 */
	protected $ID = null;
	/**
	 * @var int
	 */
	protected $order_id = 0;
	/**
	 * @var string
	 */
	protected $transaction_id = '';
	/**
	 * @var string
	 */
	protected $currency = '';
	/**
	 * @var int
	 */
	protected $price = 0;
	/**
	 * @var int
	 */
	protected $price_gross = 0;
	/**
	 * @var int
	 */
	protected $gateway_fee = 0;
	/**
	 * @var int
	 */
	protected $transaction_type = 0;
	/**
	 * @var string
	 */
	protected $gateway = '';
	/**
	 * @var string
	 */
	protected $card_last_4_digits = '';
	/**
	 * @var string
	 */
	protected $card_expires_at = '0000-00-00';
	/**
	 * @var string
	 */
	protected $created_at = '0000-00-00 00:00:00';

	/**
	 * PayPal-Debug-Id for the most recent transaction-affecting call on this order.
	 * Empty for non-PayPal gateways and for webhook-only completions.
	 *
	 * @var string
	 */
	protected $debug_id = '';

	/**
	 * Database object
	 *
	 * @var WP_Query|wpdb
	 */
	protected $wpdb;


	/**
	 * TVA_Transaction constructor.
	 *
	 * @param null $ID
	 */
	public function __construct( $ID = null ) {
		global $wpdb;

		$this->wpdb = $wpdb;
		/**
		 * Skip everything else if we don't have any order id
		 */
		if ( ! $ID ) {
			$this->set_created_at( gmdate( 'Y-m-d H:i:s' ) );

			return;
		}

		$this->set_ID( $ID );
		$this->get_data();
	}

	/**
	 * Get the data from the DB
	 */
	protected function get_data() {
		$sql        = 'SELECT * FROM ' . $this->wpdb->prefix . TVA_Const::DB_PREFIX . TVA_Const::TRANSACTIONS_TABLE_NAME . ' WHERE ID = %d';
		$order_data = $this->wpdb->get_row( $this->wpdb->prepare( $sql, array( $this->ID ) ), ARRAY_A );

		if ( ! empty( $order_data ) ) {
			$this->set_data( $order_data );
		}
	}

	/**
	 * Set the data
	 *
	 * @param $data
	 */
	public function set_data( $data ) {
		if ( is_object( $data ) ) {
			$data = (array) $data;
		}

		/**
		 * We don't need to map the data here because both the IPN data and DB data either come
		 * with the correct fields or need to be constructed beforehand because it's set in
		 * multiple arrays of data
		 */
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$fn = 'set_' . $key;
				$this->$fn( $value );
			}
		}
	}

	/**
	 * Save the data
	 *
	 * @return bool
	 */
	public function save() {
		$transaction_id = $this->get_transaction_id();

		if ( empty( $transaction_id ) ) {
			return false;
		}

		$data = get_object_vars( $this );
		unset( $data['wpdb'] );
		unset( $data['ID'] );

		$types = array(
			'%d', // order_id
			'%s', // transaction_id
			'%s', // currency
			'%s', // price
			'%s', // price_gross
			'%s', // gateway_fee
			'%d', // transaction_type
			'%s', // gateway
			'%s', // card_last_4_digits
			'%s', // card_expires_at
			'%s', // created_at
			'%s', // debug_id
		);

		if ( ! $this->get_id() ) {

			do_action( 'tva_before_sendowl_insert_order_item', $data, $types, $this );

			$result = $this->wpdb->insert(
				$this->wpdb->prefix . TVA_Const::DB_PREFIX . TVA_Const::TRANSACTIONS_TABLE_NAME,
				$data,
				$types
			);

			if ( $result ) {
				// insert() returns rows-affected (1), not the new row id — read insert_id so a
				// later save() on this object updates the right row instead of ID 1.
				$this->set_id( $this->wpdb->insert_id );
			}


		} else {

			do_action( 'tva_before_sendowl_update_order_item', $data, $types, $this );

			$result = $this->wpdb->update(
				$this->wpdb->prefix . TVA_Const::DB_PREFIX . TVA_Const::TRANSACTIONS_TABLE_NAME,
				$data,
				array( 'ID' => $this->get_id() ),
				$types,
				array( '%d' )
			);
		}

		do_action( 'tva_after_sendowl_order_item_db', $data, $types, $this );

		return $result;
	}

	/**
	 * @return null
	 */
	public function get_ID() {
		return $this->ID;
	}

	/**
	 * @param null $ID
	 */
	public function set_ID( $ID ) {
		$this->ID = $ID;
	}

	/**
	 * @return int
	 */
	public function get_order_id() {
		return $this->order_id;
	}

	/**
	 * @param int $order_id
	 */
	public function set_order_id( $order_id ) {
		$this->order_id = $order_id;
	}

	/**
	 * @return string
	 */
	public function get_transaction_id() {
		return $this->transaction_id;
	}

	/**
	 * @param string $transaction_id
	 */
	public function set_transaction_id( $transaction_id ) {
		$this->transaction_id = $transaction_id;
	}

	/**
	 * @return string
	 */
	public function get_currency() {
		return $this->currency;
	}

	/**
	 * @param string $currency
	 */
	public function set_currency( $currency ) {
		$this->currency = $currency;
	}

	/**
	 * @return int
	 */
	public function get_price() {
		return $this->price;
	}

	/**
	 * @param int $price
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * @return int
	 */
	public function get_price_gross() {
		return $this->price_gross;
	}

	/**
	 * @param int $price_gross
	 */
	public function set_price_gross( $price_gross ) {
		$this->price_gross = $price_gross;
	}

	/**
	 * @return int
	 */
	public function get_gateway_fee() {
		return $this->gateway_fee;
	}

	/**
	 * @param int $gateway_fee
	 */
	public function set_gateway_fee( $gateway_fee ) {
		$this->gateway_fee = $gateway_fee;
	}

	/**
	 * @return int
	 */
	public function get_transaction_type() {
		return $this->transaction_type;
	}

	/**
	 * @param int $transaction_type
	 */
	public function set_transaction_type( $transaction_type ) {
		$this->transaction_type = $transaction_type;
	}

	/**
	 * @return string
	 */
	public function get_gateway() {
		return $this->gateway;
	}

	/**
	 * @param string $gateway
	 */
	public function set_gateway( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * @return string
	 */
	public function get_card_last_4_digits() {
		return $this->card_last_4_digits;
	}

	/**
	 * @param string $card_last_4_digits
	 */
	public function set_card_last_4_digits( $card_last_4_digits ) {
		$this->card_last_4_digits = $card_last_4_digits;
	}

	/**
	 * @return string
	 */
	public function get_card_expires_at() {
		return $this->card_expires_at;
	}

	/**
	 * @param string $card_expires_at
	 */
	public function set_card_expires_at( $card_expires_at ) {
		$this->card_expires_at = $card_expires_at;
	}

	/**
	 * @return string
	 */
	public function get_created_at() {
		return $this->created_at;
	}

	/**
	 * @param string $created_at
	 */
	public function set_created_at( $created_at ) {
		$this->created_at = $created_at;
	}

	/**
	 * @return string
	 */
	public function get_debug_id() {
		return $this->debug_id;
	}

	/**
	 * @param string $debug_id
	 */
	public function set_debug_id( $debug_id ) {
		$this->debug_id = (string) $debug_id;
	}

	/**
	 * Find the most recent transaction row for a given transaction id + gateway.
	 *
	 * @param string $transaction_id Gateway transaction id (capture/refund id).
	 * @param string $gateway        Gateway label (e.g. TVA_Const::PAYPAL_GATEWAY).
	 *
	 * @return TVA_Transaction|null
	 */
	public static function find_by_transaction_id( $transaction_id, $gateway ) {
		if ( empty( $transaction_id ) ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . TVA_Const::DB_PREFIX . TVA_Const::TRANSACTIONS_TABLE_NAME;
		$id    = $wpdb->get_var( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE transaction_id = %s AND gateway = %s ORDER BY ID DESC LIMIT 1',
			$transaction_id,
			$gateway
		) );

		return $id ? new self( (int) $id ) : null;
	}

	/**
	 * Idempotently upsert a PayPal transaction row keyed on (transaction_id, 'PayPal').
	 *
	 * Insert when no row exists. When a row exists, only fill debug_id if the incoming
	 * value is non-empty and the stored one is empty — so a later webhook never clobbers
	 * a debug id the synchronous path already captured, and an empty value never overwrites
	 * a real one.
	 *
	 * @param array $args order_id, transaction_id, currency, price, price_gross,
	 *                     gateway_fee, transaction_type, debug_id, created_at.
	 *
	 * @return void
	 */
	public static function record_paypal( array $args ) {
		$transaction_id = (string) ( $args['transaction_id'] ?? '' );
		if ( '' === $transaction_id ) {
			return;
		}

		$existing = self::find_by_transaction_id( $transaction_id, TVA_Const::PAYPAL_GATEWAY );

		if ( $existing instanceof self ) {
			$incoming_debug = (string) ( $args['debug_id'] ?? '' );
			if ( '' !== $incoming_debug && '' === $existing->get_debug_id() ) {
				$existing->set_debug_id( $incoming_debug );
				if ( ! $existing->save() ) {
					TVA_Logger::set_type( 'PayPal' );
					TVA_Logger::log( 'transaction_debug_id_save_failed', array( 'transaction_id' => $transaction_id ), true );
				}
			}
			return;
		}

		$transaction = new self();
		$transaction->set_data( array(
			'order_id'         => (int) ( $args['order_id'] ?? 0 ),
			'transaction_id'   => $transaction_id,
			'currency'         => (string) ( $args['currency'] ?? '' ),
			'price'            => (string) ( $args['price'] ?? 0 ),
			'price_gross'      => (string) ( $args['price_gross'] ?? 0 ),
			'gateway_fee'      => (string) ( $args['gateway_fee'] ?? 0 ),
			'transaction_type' => (int) ( $args['transaction_type'] ?? TVA_Const::STATUS_COMPLETED ),
			'gateway'          => TVA_Const::PAYPAL_GATEWAY,
			'debug_id'         => (string) ( $args['debug_id'] ?? '' ),
		) );

		if ( ! empty( $args['created_at'] ) ) {
			$transaction->set_created_at( (string) $args['created_at'] );
		}

		if ( ! $transaction->save() ) {
			TVA_Logger::set_type( 'PayPal' );
			TVA_Logger::log( 'transaction_save_failed', array( 'transaction_id' => $transaction_id ), true );
		}
	}

	/**
	 * Set/refresh the debug id on an existing PayPal transaction row by transaction id.
	 * No-op when the row is missing or already has a debug id.
	 *
	 * @param string $transaction_id Capture/refund id.
	 * @param string $debug_id       PayPal-Debug-Id.
	 *
	 * @return void
	 */
	public static function set_paypal_debug_id( $transaction_id, $debug_id ) {
		if ( '' === (string) $debug_id ) {
			return;
		}

		$existing = self::find_by_transaction_id( $transaction_id, TVA_Const::PAYPAL_GATEWAY );
		if ( $existing instanceof self && '' === $existing->get_debug_id() ) {
			$existing->set_debug_id( (string) $debug_id );
			if ( ! $existing->save() ) {
				TVA_Logger::set_type( 'PayPal' );
				TVA_Logger::log( 'debug_id_save_failed', array( 'transaction_id' => (string) $transaction_id ), true );
			}
		}
	}

	/**
	 * Most recent COMPLETED PayPal transaction row for an order (the capture), for admin display.
	 *
	 * @param int $order_id
	 *
	 * @return TVA_Transaction|null
	 */
	public static function get_completed_for_order( $order_id ) {
		if ( $order_id <= 0 ) {
			return null;
		}

		global $wpdb;
		$table = $wpdb->prefix . TVA_Const::DB_PREFIX . TVA_Const::TRANSACTIONS_TABLE_NAME;
		$id    = $wpdb->get_var( $wpdb->prepare(
			'SELECT ID FROM `' . $table . '` WHERE order_id = %d AND gateway = %s AND transaction_type = %d ORDER BY ID DESC LIMIT 1',
			(int) $order_id,
			TVA_Const::PAYPAL_GATEWAY,
			TVA_Const::STATUS_COMPLETED
		) );

		return $id ? new self( (int) $id ) : null;
	}

}

<?php

/**
 * Backward-compatibility wrapper for TVA_Token.
 *
 * The token system has been moved to Thrive Dashboard (TD_API_Token).
 * This class delegates all operations to the Dashboard implementation
 * to preserve compatibility with existing code that references TVA_Token.
 *
 * @see \TVE\Dashboard\Public_API\TD_API_Token
 */
class TVA_Token {

	/**
	 * @var \TVE\Dashboard\Public_API\TD_API_Token
	 */
	private $delegate;

	/**
	 * @param int|string|array $data
	 */
	public function __construct( $data ) {
		$this->delegate = new \TVE\Dashboard\Public_API\TD_API_Token( $data );
	}

	/**
	 * Proxy property reads to the delegate.
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->delegate->$name;
	}

	/**
	 * Proxy property writes to the delegate.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function __set( $name, $value ) {
		$this->delegate->$name = $value;
	}

	/**
	 * @return int|true|WP_Error
	 */
	public function save() {
		return $this->delegate->save();
	}

	/**
	 * @return true|WP_Error
	 */
	public function delete() {
		return $this->delegate->delete();
	}

	/**
	 * @return int|true|WP_Error
	 */
	public function enable() {
		return $this->delegate->enable();
	}

	/**
	 * @return int|true|WP_Error
	 */
	public function disable() {
		return $this->delegate->disable();
	}

	/**
	 * @return array
	 */
	public function get_data() {
		return $this->delegate->get_data();
	}

	/**
	 * @return int
	 */
	public function get_id() {
		return $this->delegate->get_id();
	}

	/**
	 * @return string
	 */
	public function get_key() {
		return $this->delegate->get_key();
	}

	/**
	 * @return bool
	 */
	public function is_enabled() {
		return $this->delegate->is_enabled();
	}

	/**
	 * @param string $type ARRAY_A|OBJECT
	 *
	 * @return array
	 */
	public static function get_items( $type = ARRAY_A ) {
		return \TVE\Dashboard\Public_API\TD_API_Token::get_items( $type );
	}

	/**
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 */
	public static function auth( $username, $password ) {
		return \TVE\Dashboard\Public_API\TD_API_Token::auth( $username, $password );
	}

	/**
	 * Used by Apprentice DB migration (tokentable-1.0.0.php).
	 *
	 * @return string
	 */
	public static function get_table_name() {
		return 'tokens';
	}
}

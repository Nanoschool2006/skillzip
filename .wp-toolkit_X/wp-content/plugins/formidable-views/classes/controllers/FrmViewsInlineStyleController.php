<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * FrmViewsInlineStyleController class
 */
class FrmViewsInlineStyleController {

	/**
	 * @var FrmViewsInlineStyleController
	 */
	private static $instance;

	/**
	 * @var array
	 */
	private $styles = array();

	/**
	 * Singleton instance. Make it private to prevent direct instantiation.
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Get the instance
	 *
	 * @return FrmViewsInlineStyleController
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Set the dynamic style.
	 *
	 * @param string $handle The handle of the style.
	 * @param string $css The CSS to add.
	 * @return void
	 */
	public function set_style( $handle, $css ) {
		if ( ! $css ) {
			return;
		}
		$this->styles[ $handle ] = $css;
	}

	/**
	 * Output the styles
	 *
	 * @return void
	 */
	public function output_styles() {
		foreach ( $this->styles as $handle => $css ) {
			echo '<style type="text/css">' . esc_html( $css ) . '</style>';
		}
	}
}

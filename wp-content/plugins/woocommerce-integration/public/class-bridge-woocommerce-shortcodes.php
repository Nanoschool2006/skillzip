<?php
/**
 * The file that defines the user account shortcode.
 *
 * @link       http://wisdmlabs.com
 * @since      1.0.0
 * @package    BridgeWoocommerce
 */

/**
 * The file that defines the shortcodes.
 *
 * @author     WisdmLabs <support@wisdmlabs.com>
 */
namespace NmBridgeWoocommerce{

	class BridgeWoocommerceShortcodes {
		/**
		 * Init shortcodes.
		 */
		public static function init() {
			include_once BRIDGE_WOOCOMMERCE_PLUGIN_DIR . '/includes/class-bridge-woo-get-plugin-data.php';
			global $wooint_plugin_data;
			$get_data_from_db = BridgeWooGetPluginData::getDataFromDb( $wooint_plugin_data );

			if ( 'available' === $get_data_from_db ) {
				// Define shortcodes.
				$shortcodes = array(
					'bridge_woo_display_associated_courses' => __CLASS__ . '::displayAssociatedCourse',
					'bridge_woo_single_cart_checkout' => __CLASS__ . '::singleCartCheckout',
				);

				foreach ( $shortcodes as $shortcode => $function ) {
					add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
				}
			}
		}

		/**
		 * Shortcode Wrapper.
		 *
		 * @since  1.0.0
		 *
		 * @param mixed $function function name.
		 * @param array $atts     (default: array()).
		 * @param array $wrapper  (default: array('class' => 'bridge-woo-associated-courses', 'before' => null, 'after' => null)).
		 */
		public static function shortcodeWrapper(
			$function,
			$atts = array(),
			$wrapper = array(
				'class'  => 'bridge-woo-associated-courses',
				'before' => null,
				'after'  => null,
			)
		) {
			ob_start();
			$before = empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
			$after  = empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];

			echo $before; // @codingStandardsIgnoreLine
			call_user_func( $function, $atts );
			echo $after; // @codingStandardsIgnoreLine
			echo ob_get_clean(); // @codingStandardsIgnoreLine
		}

		/**
		 * User account shortcode.
		 *
		 * @since  1.0.0
		 *
		 * @param mixed $atts shortcode attributes.
		 *
		 * @return string
		 */
		public static function displayAssociatedCourse( $atts ) {
			return self::shortcodeWrapper( array( 'NmBridgeWoocommerce\BridgeWooShortcodeAssociatedCourses', 'output' ), $atts );
		}

		/**
		 * SingleCartCheckout Page shortcode
		 *
		 * @since  1.1.3
		 *
		 * @param mixed $atts shortcode attributes.
		 *
		 * @return string
		 */
		public static function singleCartCheckout( $atts ) {
			ob_start();

			self::shortcodeWrapper(
				array( 'NmBridgeWoocommerce\SingleCartCheckout', 'output' ),
				$atts
			);
			return ob_get_clean();
		}
	}
}

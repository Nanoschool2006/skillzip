<?php
/**
 * Pro Email Styles controller
 *
 * @since 6.25
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Class FrmProEmailStylesController
 */
class FrmProEmailStylesController {

	/**
	 * Track the current table generator object.
	 *
	 * @var FrmTableHTMLGenerator
	 */
	private static $current_table_generator;

	/**
	 * Shows email style extra settings.
	 */
	public static function show_extra_settings() {
		add_action( 'frm_style_settings_input_atts', 'FrmProStylesController::echo_style_settings_input_atts' );
		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-settings/_email-styles.php';
		remove_action( 'frm_style_settings_input_atts', 'FrmProStylesController::echo_style_settings_input_atts' );
	}

	/**
	 * Prints <option> tags.
	 *
	 * @param array  $options  Assoc array of value and label.
	 * @param string $selected Selected value.
	 */
	public static function print_options( $options, $selected = '' ) {
		foreach ( $options as $value => $label ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $value, $selected, false ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Filter the email styles.
	 *
	 * @param array[] $styles Email styles.
	 *
	 * @return array[]
	 */
	public static function email_styles( $styles ) {
		$styles['modern']['selectable']  = true;
		$styles['compact']['selectable'] = true;
		$styles['sleek']['selectable']   = true;
		return $styles;
	}

	/**
	 * Filter the email style settings value.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public static function email_style_settings( $settings ) {
		$frm_settings = FrmProAppHelper::get_settings();

		return wp_parse_args(
			array(
				'img'                => $frm_settings->email_image_id,
				'img_size'           => $frm_settings->email_image_size,
				'img_align'          => $frm_settings->email_image_align,
				'img_location'       => $frm_settings->email_image_location,
				'bg_color'           => $frm_settings->email_bg_color,
				'container_bg_color' => $frm_settings->email_container_bg_color,
				'text_color'         => $frm_settings->email_text_color,
				'link_color'         => $frm_settings->email_link_color,
				'border_color'       => $frm_settings->email_divider_color,
				'font'               => $frm_settings->email_font,
			),
			$settings
		);
	}

	/**
	 * Wraps email message.
	 * This uses the same method in Lite and add some more features.
	 *
	 * @param string $message Email message.
	 * @param array  $atts    See `frm_email_message` filter. Default is empty.
	 */
	public static function wrap_email_message( $message, $atts = array() ) {
		if ( ! method_exists( 'FrmEmailStylesController', 'wrap_email_message' ) ) {
			return $message;
		}

		if ( ! empty( $atts['plain_text'] ) ) {
			return $message;
		}

		$email_style = $atts['email_style'];
		$message     = self::process_table_style_shortcodes( $message, $email_style );

		if ( $email_style && 'classic' !== $email_style ) {
			return FrmEmailStylesController::wrap_email_message( $message );
		}

		return $message;
	}

	/**
	 * Processes table style shortcodes.
	 *
	 * @param string $message     Email message.
	 * @param string $email_style Email style.
	 *
	 * @return string
	 */
	private static function process_table_style_shortcodes( $message, $email_style ) {
		self::$current_table_generator = FrmEmailStylesController::get_table_generator( $email_style );

		add_shortcode( 'frm_table', array( self::class, 'shortcode_table' ) );
		add_shortcode( 'frm_tr', array( self::class, 'shortcode_tr' ) );
		add_shortcode( 'frm_td', array( self::class, 'shortcode_td' ) );
		add_shortcode( 'frm_th', array( self::class, 'shortcode_th' ) );
		$message = do_shortcode( $message );
		remove_shortcode( 'frm_table' );
		remove_shortcode( 'frm_tr' );
		remove_shortcode( 'frm_td' );
		remove_shortcode( 'frm_th' );

		self::$current_table_generator = null;

		return $message;
	}

	/**
	 * Shortcode [frm_table].
	 *
	 * @param array  $atts    Shortcode atts.
	 * @param string $content Inner content.
	 *
	 * @return string
	 */
	public static function shortcode_table( $atts, $content = '' ) {
		if ( ! self::$current_table_generator ) {
			// Only process this shortcode if the current table generator is set.
			return $content;
		}

		$table_style = self::$current_table_generator->get_table_style() . ' cellspacing="0"';

		if ( $atts ) {
			if ( ! empty( $atts['no_border_bottom'] ) ) {
				$table_style = self::$current_table_generator->remove_border( $table_style, 'bottom' );
			}

			unset( $atts['no_border_bottom'] );

			$table_atts  = shortcode_parse_atts( $table_style );
			$table_atts  = self::merge_atts( $table_atts, $atts );
			$table_style = FrmAppHelper::array_to_html_params( $table_atts );
		}

		$table_html = '<table ' . $table_style . '>' . do_shortcode( $content ) . '</table>';

		return self::remove_line_breaks_inside_table( $table_html );
	}

	/**
	 * Removes the line breaks inside table that break the table styling.
	 *
	 * @param string $html The HTML.
	 *
	 * @return string
	 */
	private static function remove_line_breaks_inside_table( $html ) {
		$new_html = preg_replace(
			array(
				'/<p><table/',
				'/<\/table><\/p>/',
				'/(<table .*?>)(<br \/>)/',
				'/(<tr .*?>)(<br \/>)/',
				'/(<th .*?>)(<br \/>)/',
				'/(<td .*?>)(<br \/>)/',
				'/(<thead .*?>)(<br \/>)/',
				'/(<tbody .*?>)(<br \/>)/',
				'/(<tfoot .*?>)(<br \/>)/',
				'/<\/tr><br \/>/',
				'/<\/th><br \/>/',
				'/<\/td><br \/>/',
				'/<\/thead><br \/>/',
				'/<\/tbody><br \/>/',
				'/<\/tfoot><br \/>/',
			),
			array(
				'<table',
				'</table>',
				'$1',
				'$1',
				'$1',
				'$1',
				'$1',
				'$1',
				'$1',
				'</tr>',
				'</th>',
				'</td>',
				'</thead>',
				'</tbody>',
				'</tfoot>',
			),
			$html
		);

		if ( ! $new_html ) {
			// The result of preg_replace() may be null if errors occur.
			return $html;
		}

		return $new_html;
	}

	/**
	 * Shortcode [frm_tr].
	 *
	 * @param array  $atts    Shortcode atts.
	 * @param string $content Inner content.
	 *
	 * @return string
	 */
	public static function shortcode_tr( $atts, $content = '' ) {
		if ( ! self::$current_table_generator ) {
			return $content;
		}

		$tr_style = self::$current_table_generator->tr_style();

		if ( $atts ) {
			$tr_atts  = self::merge_atts( shortcode_parse_atts( $tr_style ), $atts );
			$tr_style = FrmAppHelper::array_to_html_params( $tr_atts );
		}

		return '<tr ' . $tr_style . '>' . do_shortcode( $content ) . '</tr>';
	}

	/**
	 * Processes table cell shortcode.
	 *
	 * @param array  $atts    Shortcode atts.
	 * @param string $content Inner content.
	 * @param string $tag     Table cell tag.
	 *
	 * @return string
	 */
	private static function shortcode_table_cell( $atts, $content = '', $tag = 'td' ) {
		if ( ! self::$current_table_generator ) {
			return $content;
		}

		$td_style = self::$current_table_generator->get_td_style();

		if ( $atts ) {
			if ( ! empty( $atts['no_border_top'] ) ) {
				$td_style = self::$current_table_generator->remove_border( $td_style );
			}
			unset( $atts['no_border_top'] );

			$td_atts  = self::merge_atts( shortcode_parse_atts( $td_style ), $atts );
			$td_style = FrmAppHelper::array_to_html_params( $td_atts );
		}

		return '<' . $tag . ' ' . $td_style . '>' . do_shortcode( $content ) . '</' . $tag . '>';
	}

	/**
	 * Shortcode [frm_td].
	 *
	 * @param array  $atts    Shortcode atts.
	 * @param string $content Inner content.
	 * @param string $tag
	 *
	 * @return string
	 */
	public static function shortcode_td( $atts, $content = '', $tag = 'td' ) {
		return self::shortcode_table_cell( $atts, $content );
	}

	/**
	 * Shortcode [frm_th].
	 *
	 * @param array  $atts    Shortcode atts.
	 * @param string $content Inner content.
	 *
	 * @return string
	 */
	public static function shortcode_th( $atts, $content = '' ) {
		return self::shortcode_table_cell( $atts, $content, 'th' );
	}

	/**
	 * Merges table atts and shortcode atts.
	 *
	 * @param array $table_atts     Table atts.
	 * @param array $shortcode_atts Shortcode atts.
	 *
	 * @return array
	 */
	private static function merge_atts( $table_atts, $shortcode_atts ) {
		if ( ! empty( $shortcode_atts['style'] ) ) {
			// Append shortcode style to the table style.
			$table_style  = rtrim( $table_atts['style'], '"' );
			$table_style .= $shortcode_atts['style'];
			$table_style .= '"';

			$table_atts['style'] = $table_style;

			unset( $shortcode_atts['style'] );
		}

		return $shortcode_atts + $table_atts;
	}
}

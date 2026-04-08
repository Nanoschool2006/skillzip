<?php
/**
 * App helper
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FrmAIAppHelper
 */
class FrmAIAppHelper {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static $plug_version = '2.1';

	/**
	 * Gets plugin folder name.
	 *
	 * @return string
	 */
	public static function plugin_folder() {
		return basename( self::plugin_path() );
	}

	/**
	 * Gets plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file() {
		return self::plugin_path() . '/formidable-ai.php';
	}

	/**
	 * Gets plugin path.
	 *
	 * @return string
	 */
	public static function plugin_path() {
		return dirname( dirname( __DIR__ ) );
	}

	/**
	 * Gets plugin URL.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::plugin_path() . '/formidable-ai.php' );
	}

	/**
	 * Get the URL to the ajax endpoint.
	 *
	 * @return string
	 */
	public static function ajax_url() {
		$ajax_url = admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' );
		return (string) apply_filters( 'frm_ajax_url', $ajax_url );
	}

	/**
	 * Checks if this plugin is safe to run.
	 *
	 * @return bool
	 */
	public static function is_compatible() {
		return function_exists( 'load_formidable_pro' );
	}

	/**
	 * Get the SVG loading animation ready for css.
	 *
	 * @return string
	 */
	public static function loading_svg() {
		$heights   = '120;110;100;90;80;70;60;50;40;50;60;70;80;90;100;110;120;140;120';
		$positions = '10;15;20;25;30;35;40;45;50;45;40;35;30;25;20;15;10;0;10';

		$sm_height   = self::start_svg( 40, $heights );
		$md_height   = self::start_svg( 80, $heights );
		$sm_position = self::start_svg( 50, $positions );
		$md_position = self::start_svg( 30, $positions );

		$svg = '<svg width="58" height="60" viewBox="0 0 135 140" xmlns="http://www.w3.org/2000/svg">
<rect y="50" width="15" height="40" rx="6"><animate attributeName="height" begin="0s" dur="1s" values="' . esc_attr( $sm_height ) . '" calcMode="linear" repeatCount="indefinite"/><animate attributeName="y" begin="0s" dur="1s" values="' . esc_attr( $sm_position ) . '" calcMode="linear" repeatCount="indefinite"/></rect>
<rect x="30" y="30" width="15" height="80" rx="6"><animate attributeName="height" begin="0s" dur="1s" values="' . esc_attr( $md_height ) . '" calcMode="linear" repeatCount="indefinite"/><animate attributeName="y" begin="0s" dur="1s" values="' . esc_attr( $md_position ) . '" calcMode="linear" repeatCount="indefinite"/></rect>
<rect x="60" width="15" height="140" rx="6"><animate attributeName="height" begin="0s" dur="1s" values="' . esc_attr( $heights ) . '" calcMode="linear" repeatCount="indefinite"/><animate attributeName="y" begin="0s" dur="1s" values="' . esc_attr( $positions ) . '" calcMode="linear" repeatCount="indefinite"/></rect>
<rect x="90" y="30" width="15" height="80" rx="6"><animate attributeName="height" begin="0s" dur="1s" values="' . esc_attr( $md_height ) . '" calcMode="linear" repeatCount="indefinite"/><animate attributeName="y" begin="0s" dur="1s" values="' . esc_attr( $md_position ) . '" calcMode="linear" repeatCount="indefinite"/></rect>
<rect x="120" y="50" width="15" height="40" rx="6"><animate attributeName="height" begin="0s" dur="1s" values="' . esc_attr( $sm_height ) . '" calcMode="linear" repeatCount="indefinite"/><animate attributeName="y" begin="0s" dur="1s" values="' . esc_attr( $sm_position ) . '" calcMode="linear" repeatCount="indefinite"/></rect>
</svg>';
		$replace = array(
			'"' => "'",
			'<' => '%3C',
			'>' => '%3E',
			'#' => '%23',
			"\n" => '',
		);
		$svg     = strtr( trim( $svg ), $replace );
		return '"data:image/svg+xml,' . $svg . '"';
	}

	/**
	 * Split the position and height values to start each one at a different point.
	 *
	 * @param int    $spot The spot to start at.
	 * @param string $spots The string of all the spots.
	 * @return string
	 */
	private static function start_svg( $spot, $spots ) {
		$parts = explode( ';' . $spot . ';', $spots, 2 );
		return $spot . ';' . $parts[1] . ';' . $parts[0];
	}
}

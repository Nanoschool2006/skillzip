<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-quiz-builder
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class TQB_Shortcodes {

	protected static $quizzes = array();

	/**
	 * Per-request cache for category data to avoid repeated DB queries.
	 * Keyed by quiz_id, stores: enriched_categories, max_possible_points, computed_categories
	 *
	 * @var array
	 */
	private static $category_cache = array();

	public static function init() {
		add_shortcode( 'tqb_quiz', array( 'TQB_Shortcodes', 'render_quiz_shortcode' ) );
		add_shortcode( 'tqb_quiz_options', array( 'TQB_Shortcodes', 'tqb_quiz_options' ) );
		add_shortcode( 'tqb_quiz_result', array( 'TQB_Shortcodes', 'render_quiz_result' ) );
	}

	/**
	 * Render quiz result content
	 *
	 * @since 3.x.x Updated with category breakdown parameters
	 *
	 * @param array $attributes Shortcode attributes.
	 *
	 * @return string Rendered output.
	 */
	public static function render_quiz_result( $attributes ) {

		// Ensure attributes is an array
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// Early return for editor mode - validate request parameter exists and is string
		if ( isset( $_REQUEST[ Thrive_Quiz_Builder::VARIATION_QUERY_KEY_NAME ] ) &&
		     is_string( $_REQUEST[ Thrive_Quiz_Builder::VARIATION_QUERY_KEY_NAME ] ) &&
		     '' !== $_REQUEST[ Thrive_Quiz_Builder::VARIATION_QUERY_KEY_NAME ] ) {
			return '';
		}

		// Validate required data attribute
		if ( ! isset( $attributes['data'] ) ) {
			return 0;
		}

		// Try base64 decode first, then regular JSON
		$raw_data = $attributes['data'];
		$decoded  = base64_decode( $raw_data, true );
		if ( false !== $decoded ) {
			$data = json_decode( $decoded, true );
		}
		if ( empty( $data ) || ! is_array( $data ) ) {
			// Fallback to regular JSON (backward compatibility)
			$data = json_decode( $raw_data, true );
		}

		if ( empty( $data ) || ! is_array( $data ) ) {
			return '';
		}

		// Check for show_all_categories (accept truthy values)
		$show_all = isset( $attributes['show_all_categories'] ) &&
		            filter_var( $attributes['show_all_categories'], FILTER_VALIDATE_BOOLEAN );

		if ( $show_all ) {
			return self::render_all_categories_result( $attributes, $data );
		}

		// Check for single category request
		if ( ! empty( $attributes['category'] ) ) {
			return self::render_single_category_result( $attributes, $data );
		}

		// Default: backward compatible behavior
		return self::render_legacy_result( $attributes, $data );
	}

	/**
	 * Render legacy result (backward compatible)
	 *
	 * @since 3.x.x
	 *
	 * @param array $attributes Shortcode attributes.
	 * @param array $data       Quiz result data.
	 *
	 * @return string Rendered output.
	 */
	protected static function render_legacy_result( $attributes, $data ) {

		// When no result_type is specified, return the basic result
		if ( ! isset( $attributes['result_type'] ) ) {
			return self::get_untyped_result( $data );
		}

		global $tqbdb;

		// Validate global exists
		if ( ! isset( $tqbdb ) || ! is_object( $tqbdb ) ) {
			return '';
		}

		$result = '';

		if ( ! empty( $data['points']['explicit'] ) ) {
			$category = str_replace( Thrive_Quiz_Builder::COMMA_PLACEHOLDER, "'", $data['points']['explicit'] );

			$data['points']['explicit'] = addslashes( $category );
		}

		$score = $tqbdb->get_explicit_result( $data['points'] );

		// Validate return value before using
		if ( ! is_string( $score ) && ! is_numeric( $score ) ) {
			return '';
		}

		$quiz_type = isset( $data['quiz_type'] ) ? $data['quiz_type'] : '';

		if ( 'personality' === $quiz_type || 'right_wrong' === $quiz_type ) {
			return (string) $score;
		}

		$score = str_replace( '%', '', (string) $score );

		switch ( $attributes['result_type'] ) {

			case 'one_decimal':
				$result = round( $score, 1 );

				break;

			case 'two_decimal':
				$result = round( $score, 2 );

				break;

			case 'whole_number':
			case 'default':
				$result = round( $score );

				break;
		}

		if ( 'percentage' === $quiz_type ) {
			$result = $result . '%';
		}

		return $result;
	}

	/**
	 * Render single category result
	 *
	 * @since 3.x.x
	 *
	 * @param array $attributes Shortcode attributes.
	 * @param array $data       Quiz result data.
	 *
	 * @return string Rendered output.
	 */
	protected static function render_single_category_result( $attributes, $data ) {

		$quiz_id     = isset( $data['quiz_id'] ) ? (int) $data['quiz_id'] : 0;
		$category    = sanitize_text_field( $attributes['category'] );
		$result_type = isset( $attributes['result_type'] ) ? sanitize_key( trim( $attributes['result_type'] ) ) : 'name';

		if ( $quiz_id <= 0 || empty( $category ) ) {
			return '';
		}

		$categories = self::get_computed_categories( $data );

		if ( empty( $categories ) ) {
			return '';
		}

		// Find the requested category (by ID or name, case-insensitive)
		$found = null;
		foreach ( $categories as $cat ) {
			if ( ! isset( $cat['id'], $cat['name'] ) ) {
				continue;
			}
			if ( (string) $cat['id'] === $category || strtolower( $cat['name'] ) === strtolower( $category ) ) {
				$found = $cat;
				break;
			}
		}

		if ( ! $found ) {
			return '';
		}

		return self::format_category_value( $found, $result_type );
	}

	/**
	 * Render all categories result
	 *
	 * @since 3.x.x
	 *
	 * @param array $attributes Shortcode attributes.
	 * @param array $data       Quiz result data.
	 *
	 * @return string Rendered output.
	 */
	protected static function render_all_categories_result( $attributes, $data ) {

		$quiz_id      = isset( $data['quiz_id'] ) ? (int) $data['quiz_id'] : 0;
		$result_type  = isset( $attributes['result_type'] ) ? sanitize_key( trim( $attributes['result_type'] ) ) : 'share';
		$sort_by      = isset( $attributes['sort_by'] ) ? sanitize_key( trim( $attributes['sort_by'] ) ) : 'highest';
		$include_zero = isset( $attributes['include_zero'] ) &&
		                filter_var( $attributes['include_zero'], FILTER_VALIDATE_BOOLEAN );

		if ( $quiz_id <= 0 ) {
			return '';
		}

		$categories = self::get_computed_categories( $data );

		if ( empty( $categories ) ) {
			return '';
		}

		// Filter zero-scored categories unless include_zero is true
		if ( ! $include_zero ) {
			$filtered = array_filter( $categories, function( $cat ) {
				return isset( $cat['points'] ) && $cat['points'] > 0;
			} );

			// Fall back to all categories if filtering removes everything
			if ( ! empty( $filtered ) ) {
				$categories = $filtered;
			}
		}

		// Sort categories
		usort( $categories, function( $a, $b ) use ( $sort_by ) {
			$a_points = isset( $a['points'] ) ? $a['points'] : 0;
			$b_points = isset( $b['points'] ) ? $b['points'] : 0;
			$a_name   = isset( $a['name'] ) ? $a['name'] : '';
			$b_name   = isset( $b['name'] ) ? $b['name'] : '';

			$diff = ( 'lowest' === $sort_by )
				? $a_points - $b_points
				: $b_points - $a_points;

			// Tie-breaker: alphabetical
			return ( 0 === $diff ) ? strcmp( $a_name, $b_name ) : $diff;
		} );

		// Build output
		$output = array();
		foreach ( $categories as $cat ) {
			if ( ! isset( $cat['name'] ) ) {
				continue;
			}
			$value    = self::format_category_value( $cat, $result_type );
			$output[] = esc_html( $cat['name'] ) . ': ' . esc_html( $value );
		}

		// Use wp_kses to allow only <br> tags
		return wp_kses( implode( '<br>', $output ), array( 'br' => array() ) );
	}

	/**
	 * Format category value based on result type
	 *
	 * @since 3.x.x
	 *
	 * @param array  $category    Category data with 'points', 'share', and 'percent' keys.
	 * @param string $result_type Result type: 'points', 'share', 'percent'.
	 *
	 * @return string Formatted value.
	 */
	protected static function format_category_value( $category, $result_type ) {

		// Normalize result_type
		$result_type = strtolower( trim( (string) $result_type ) );

		if ( 'points' === $result_type ) {
			return isset( $category['points'] ) ? (string) $category['points'] : '0';
		}

		if ( 'share' === $result_type ) {
			return isset( $category['share'] ) ? $category['share'] . '%' : '0%';
		}

		if ( 'percent' === $result_type || 'percent_of_max' === $result_type ) {
			return isset( $category['percent'] ) ? $category['percent'] . '%' : '0%';
		}

		// Default to name for backward compatibility
		return isset( $category['name'] ) ? $category['name'] : '';
	}

	/**
	 * Get result for shortcodes without a result_type attribute
	 *
	 * @since 3.x.x
	 *
	 * @param array $data Quiz result data.
	 *
	 * @return string
	 */
	private static function get_untyped_result( $data ) {

		$fallback = isset( $data['result'] ) ? (string) $data['result'] : '';

		$quiz_type = isset( $data['quiz_type'] ) ? $data['quiz_type'] : '';

		if ( 'personality' !== $quiz_type && 'right_wrong' !== $quiz_type ) {
			return $fallback;
		}

		global $tqbdb;

		if ( ! isset( $tqbdb ) || ! is_object( $tqbdb ) ) {
			return $fallback;
		}

		if ( ! empty( $data['points']['explicit'] ) ) {
			$category                   = str_replace( Thrive_Quiz_Builder::COMMA_PLACEHOLDER, "'", $data['points']['explicit'] );
			$data['points']['explicit'] = addslashes( $category );
		}

		$score = $tqbdb->get_explicit_result( $data['points'] );

		if ( is_string( $score ) || is_numeric( $score ) ) {
			return (string) $score;
		}

		return $fallback;
	}

	/**
	 * Get computed categories for a quiz, with per-request caching
	 *
	 * @since 3.x.x
	 *
	 * @param array $data Quiz result data containing quiz_id and points.category_breakdown.
	 *
	 * @return array Computed categories with share/percent data, or empty array.
	 */
	private static function get_computed_categories( $data ) {

		$quiz_id = isset( $data['quiz_id'] ) ? (int) $data['quiz_id'] : 0;

		if ( $quiz_id <= 0 || empty( $data['points']['category_breakdown'] ) ) {
			return array();
		}

		if ( isset( self::$category_cache[ $quiz_id ] ) ) {
			$cached = self::$category_cache[ $quiz_id ]['computed_categories'];

			return is_array( $cached ) ? $cached : array();
		}

		global $tqbdb;

		if ( ! isset( $tqbdb ) || ! is_object( $tqbdb ) ) {
			return array();
		}

		$categories = $tqbdb->get_category_breakdown_with_names( $data['points']['category_breakdown'], $quiz_id );

		if ( empty( $categories ) ) {
			return array();
		}

		$max_possible = $tqbdb->get_category_max_possible_points( $quiz_id );

		if ( ! is_array( $max_possible ) ) {
			$max_possible = array();
		}

		$categories = $tqbdb->calculate_category_shares( $categories, $max_possible );

		if ( empty( $categories ) ) {
			return array();
		}

		self::$category_cache[ $quiz_id ] = array(
			'enriched_categories' => $categories,
			'max_possible_points' => $max_possible,
			'computed_categories' => $categories,
		);

		return $categories;
	}

	public static function render_quiz_shortcode( $attributes = array() ) {
		$quiz_id = ! empty( $attributes['id'] ) ? $attributes['id'] : $attributes['quiz_id'];
		/**
		 * Make sure we enqueue only once the frontend scripts
		 * - in this way we dont overwrite the TQB_Front localization
		 */
		if ( ! defined( 'TQB_IN_SHORTCODE' ) ) {
			Thrive_Quiz_Builder::enqueue_frontend_scripts( $quiz_id );

			add_filter( 'tcb_overwrite_scripts_enqueue', '__return_true' );
			$quiz = new TQB_Quiz( (int) $quiz_id );
			if ( $quiz->optin_gate_is_enabled() ) {
				tve_frontend_enqueue_scripts( $quiz->get_optin_gate_id() );
			}
		}

		defined( 'TQB_IN_SHORTCODE' ) || define( 'TQB_IN_SHORTCODE', true );

		if ( ! empty( $attributes['save_user_progress'] ) && (int) $attributes['save_user_progress'] === 1 && is_user_logged_in() ) {
			$unique_id = tqb_customer()->get_user_unique_id( $quiz_id, get_the_ID() );
		}

		$unique_id = empty( $unique_id ) ? ( 'tqb-' . uniqid() ) : $unique_id;

		$placeholder_style = TQB_Lightspeed::get_quiz_placeholder_style( $quiz_id );

		$style = TQB_Post_meta::get_quiz_style_meta( $quiz_id );
		$html  = '<div class="tve_flt" id="tve_editor">
			<div class="tqb-shortcode-wrapper" id="tqb-shortcode-wrapper-' . $quiz_id . '-' . $unique_id . '" ' . $placeholder_style . static::compute_shortcode_attributes( $attributes ) . ' data-unique="' . $unique_id . '" >
				<div class="tqb-loading-overlay tqb-template-overlay-style-' . $style . '">
					<div class="tqb-loading-bullets"></div>
				</div>
				<div class="tqb-frontend-error-message"></div>
				<div class="tqb-shortcode-old-content"></div>
				<div class="tqb-shortcode-new-content tqb-template-style-' . $style . '"></div>
			</div></div>';

		TQB_Quiz_Manager::run_shortcodes_on_quiz_content( $quiz_id );

		if ( is_editor_page() || ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_REQUEST['tqb_in_tcb_editor'] ) ) ) {
			$html = str_replace( array( 'id="tve_editor"' ), '', $html );
			$html = '<div class="thrive-shortcode-html"><div>' . $html . '</div><style>.tqb-shortcode-wrapper{pointer-events: none;}</style></div>';
		}

		return $html;
	}

	public static function tqb_quiz_options( $args ) {
		return '#';
	}

	/**
	 * Render backbone templates
	 */
	public static function render_backbone_templates() {
		$templates = tve_dash_get_backbone_templates( tqb()->plugin_path( 'includes/frontend/views/templates' ), 'templates' );

		$templates = apply_filters( 'tqb_backbone_frontend_templates', $templates );

		tve_dash_output_backbone_templates( $templates );
	}

	/**
	 * Computes shortcode attributes
	 *
	 * @param array $attributes
	 *
	 * @return string
	 */
	public static function compute_shortcode_attributes( $attributes ) {
		$attributes_string = '';
		if ( is_array( $attributes ) ) {

			if ( ! empty( $attributes['id'] ) ) {
				$attributes['quiz_id'] = $attributes['id'];
				unset( $attributes['id'] );
			}

			$attributes_string = implode( ' ', array_map( static function ( $key ) use ( $attributes ) {
				$value = $attributes[ $key ];
				if ( is_array( $value ) ) {
					$value = implode( ',', $value );
				}

				return 'data-' . str_replace( '_', '-', $key ) . '="' . esc_attr( $value ) . '"';
			}, array_keys( $attributes ) ) );
		}

		return $attributes_string;
	}
}

TQB_Shortcodes::init();


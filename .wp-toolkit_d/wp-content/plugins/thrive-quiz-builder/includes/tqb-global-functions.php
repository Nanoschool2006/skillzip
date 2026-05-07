<?php
/**
 * Global functions file.
 *
 * @package Thrive Quiz Builder
 */

/**
 * Wrapper over the wp_enqueue_style function.
 * it will add the plugin version to the style link if no version is specified
 *
 * @param string      $handle Name of the stylesheet. Should be unique.
 * @param string|bool $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
 * @param array       $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
 * @param bool|string $ver    Optional. String specifying stylesheet version number.
 * @param string      $media  Optional. The media for which this stylesheet has been defined.
 */
function tqb_enqueue_style( $handle, $src = false, $deps = array(), $ver = false, $media = 'all' ) {
	if ( false === $ver ) {
		$ver = Thrive_Quiz_Builder::V;
	}
	wp_enqueue_style( $handle, $src, $deps, $ver, $media );
}

/**
 * Wrapper over the wp_enqueue_script function.
 * It will add the plugin version to the script source if no version is specified.
 *
 * @param string $handle    Name of the script. Should be unique.
 * @param string $src       Full URL of the script, or path of the script relative to the WordPress root directory.
 * @param array  $deps      Optional. An array of registered script handles this script depends on. Default empty array.
 * @param bool   $ver       Optional. String specifying script version number, if it has one, which is added to the URL.
 * @param bool   $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
 */
function tqb_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {

	if ( false === $ver ) {
		$ver = Thrive_Quiz_Builder::V;
	}

	if ( defined( 'TVE_DEBUG' ) && TVE_DEBUG ) {
		$src = preg_replace( '#\.min\.js$#', '.js', $src );
	}

	wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
}

/**
 * TODO: NOT USED SO FAR!!!!!!!
 *
 * Run a MySQL transaction query, if supported
 *
 * @param string $type start (default), commit, rollback.
 *
 * @since 2.5.0
 */
function tqb_transaction_query( $type = 'start' ) {
	global $wpdb;

	$wpdb->hide_errors();

	if ( ! defined( 'TQB_USE_TRANSACTIONS' ) ) {
		define( 'TQB_USE_TRANSACTIONS', true );
	}

	if ( TQB_USE_TRANSACTIONS ) {
		switch ( $type ) {
			case 'commit' :
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback' :
				$wpdb->query( 'ROLLBACK' );
				break;
			default :
				$wpdb->query( 'START TRANSACTION' );
				break;
		}
	}
}

/**
 * Appends the WordPress tables prefix and the default tqb_ prefix to the table name
 *
 * @param string $table name of the table.
 *
 * @return string the modified table name
 */
function tqb_table_name( $table ) {
	global $wpdb;

	return $wpdb->prefix . Thrive_Quiz_Builder::DB_PREFIX . $table;
}

/**
 * Get the timezone difference between WordPress current time and server time
 *
 * This helper function centralizes the commonly used timezone offset calculation
 * that's needed to adjust timestamps between server time and WordPress configured time.
 *
 * @return int The timezone difference in seconds
 */
function tqb_get_timezone_offset() {
	return current_time( 'timestamp' ) - time();
}

/**
 * Checks if we are editing a design
 */
function tqb_is_editor_page() {
	global $variation;

	return isset( $_GET[ TVE_EDITOR_FLAG ] ) && ! empty( $variation ) && TCB_Hooks::is_editable( get_the_ID() );
}

/**
 * Enqueues scripts and styles for a specific variation
 *
 * @param array $for_variation
 *
 * @return array
 */
function tqb_enqueue_variation_scripts( $for_variation = null ) {

	if ( empty( $for_variation ) ) {
		global $variation;
		$for_variation = $variation;
	}
	if ( empty( $for_variation ) || empty( $for_variation[ Thrive_Quiz_Builder::FIELD_TEMPLATE ] ) ) {
		return array(
			'fonts' => array(),
			'css'   => array(),
			'js'    => array(),
		);
	}

	/** enqueue Custom Fonts, if any */
	$fonts = TCB_Hooks::tqb_editor_enqueue_custom_fonts( $for_variation );

	$config = TCB_Hooks::tqb_editor_get_template_config( $for_variation[ Thrive_Quiz_Builder::FIELD_TEMPLATE ] );

	/** custom fonts for the form */
	if ( ! empty( $config['fonts'] ) ) {
		foreach ( $config['fonts'] as $font ) {
			$fonts[ 'tqb-font-' . md5( $font ) ] = $font;
			wp_enqueue_style( 'tqb-font-' . md5( $font ), $font );
		}
	}

	$css = array();

	$quiz_style_meta   = TQB_Post_meta::get_quiz_style_meta( $for_variation['quiz_id'] );
	$template_css_file = tqb()->get_style_css( $quiz_style_meta );

	/* include also the CSS for each variation template */
	if ( ! empty( $template_css_file ) ) {
		$template_css_file_path = tqb()->plugin_url( 'tcb-bridge/editor-templates/css/' . TQB_Template_Manager::type( $for_variation['post_type'] ) . '/' . $template_css_file );
		$css_handle             = 'tqb-' . TQB_Template_Manager::type( $for_variation[ Thrive_Quiz_Builder::FIELD_TEMPLATE ] ) . '-' . str_replace( '.css', '', $template_css_file );

		tqb_enqueue_style( $css_handle, $template_css_file_path );
		$css = array(
			$css_handle => $template_css_file_path,
		);
	}


	$js = array();

	if ( ! empty( $for_variation[ Thrive_Quiz_Builder::FIELD_ICON_PACK ] ) ) {
		TCB_Icon_Manager::enqueue_icon_pack();
	}

	if ( ! empty( $for_variation[ Thrive_Quiz_Builder::FIELD_MASONRY ] ) ) {
		wp_enqueue_script( 'jquery-masonry' );
		$js['jquery-masonry'] = includes_url( 'js/jquery/jquery.masonry.min.js' );
	}

	if ( ! empty( $for_variation[ Thrive_Quiz_Builder::FIELD_TYPEFOCUS ] ) ) {
		tqb_enqueue_script( 'tve_typed', tve_editor_js() . '/typed.min.js', array(), false, true );
		$js['tve_typed'] = tve_editor_js() . '/typed.min.js';
	}

	return array(
		'fonts' => $fonts,
		'js'    => $js,
		'css'   => $css,
	);
}

/**
 * Enqueue the default styles when they are needed
 */
function tqb_enqueue_default_scripts() {
	if ( ! wp_script_is( 'tve_frontend' ) ) {
		if ( TQB_Lightspeed::has_lightspeed() ) {
			\TCB\Lightspeed\JS::get_instance( get_the_ID() )->enqueue_scripts();
		}

		$frontend_options = array(
			'is_editor_page'   => is_editor_page(),
			'page_events'      => array(),
			'is_single'        => 1,
			'ajaxurl'          => admin_url( 'admin-ajax.php' ),
			'social_fb_app_id' => function_exists( 'tve_get_social_fb_app_id' ) ? tve_get_social_fb_app_id() : '',
		);

		/**
		 * Allows adding frontend options from different plugins
		 *
		 * @param $frontend_options
		 */
		$frontend_options = apply_filters( 'tve_frontend_options_data', $frontend_options );
		wp_localize_script( 'tve_frontend', 'tve_frontend_options', $frontend_options );
	}

	/** Basic Standard Shortcode Style */
	wp_enqueue_style( 'tqb-shortcode', tqb()->plugin_url( 'assets/css/frontend/tqb-shortcode.css' ) );
}

/**
 * Generate an array of dates between $start_date and $end_date depending on the $interval
 *
 * @param        $start_date
 * @param        $end_date
 * @param string $interval - can be 'day', 'week', 'month'
 *
 * @return array $dates
 */
function tqb_generate_dates_interval( $start_date, $end_date, $interval = 'day' ) {
	switch ( $interval ) {
		case 'day':
			$date_format = 'd M, Y';
			break;
		case 'week':
			$date_format = '\W\e\e\k W, o';
			break;
		case 'month':
			$date_format = 'F Y';
			break;
		default:
			return array();
	}

	$dates = array();
	$seen_dates = array(); // Associative array for O(1) duplicate checking
	$timezone_offset = tqb_get_timezone_offset(); // Calculate once and reuse

	for ( $i = 0; strtotime( $start_date . ' + ' . $i . 'day' ) <= strtotime( $end_date ); $i ++ ) {
		$timestamp = strtotime( $start_date . ' + ' . $i . 'day' );
		$date      = date( $date_format, $timestamp + $timezone_offset );

		// Remove the 0 from the week number
		if ( $interval == 'week' ) {
			$date = str_replace( 'Week 0', 'Week ', $date );
		}

		// Use isset() for O(1) duplicate checking instead of in_array() O(n)
		if ( ! isset( $seen_dates[ $date ] ) ) {
			$seen_dates[ $date ] = true;
			$dates[] = $date;
		}
	}

	return $dates;
}

/**
 * return a formatted conversion rate based on $impressions and $conversions
 *
 * @param int    $impressions
 * @param int    $conversions
 * @param string $suffix
 * @param string $decimals
 *
 * @return string|float $rate the calculated conversion rate
 */
function tqb_conversion_rate( $impressions, $conversions, $suffix = '%', $decimals = '2' ) {
	if ( $conversions == 0 || $impressions == 0 ) {
		// For chart data, return 0 instead of 'N/A' when suffix is empty
		if ( empty( $suffix ) ) {
			return 0;
		}
		return 'N/A';
	}

	$rate = round( 100 * ( $conversions / $impressions ), $decimals );

	// Return numeric value when no suffix is requested (for charts)
	if ( empty( $suffix ) ) {
		return (float) $rate;
	}

	return $rate . $suffix;
}

/**
 * generate a random number between 0 and $total-1
 *
 * @param int $total
 * @param int $multiplier for smaller values, it's better to extend the interval by a number of times,
 *                        example: to choose between 0 and 1 -> we think it's better to have a random number between 0 and 10000 and split that into halves
 *
 * @return int
 */
function tqb_get_random_index( $total, $multiplier = 1000 ) {
	$_rand = function_exists( 'mt_rand' ) ? mt_rand( 0, $total * $multiplier - 1 ) : rand( 0, $total * $multiplier - 1 );

	return intval( floor( $_rand / $multiplier ) );
}


/**
 * Return data for the test chart
 *
 * @param $filter
 *
 * @return array
 */
function tqb_get_conversion_rate_test_data( $filter ) {
	global $tqbdb;

	// Validate required filter parameters
	if ( empty( $filter ) || ! is_array( $filter ) ) {
		return array(
			'error' => array(
				'error_type' => 'invalid_parameters',
				'message' => esc_html__( 'Invalid filter parameters', 'thrive-quiz-builder' )
			)
		);
	}

	$required_params = array( 'start_date', 'end_date', 'interval', 'group_names' );
	foreach ( $required_params as $param ) {
		if ( empty( $filter[ $param ] ) ) {
			return array(
				'error' => array(
					'error_type' => 'missing_parameter',
					'message' => sprintf(
						/* translators: %s: Parameter name */
						esc_html__( 'Missing required parameter: %s', 'thrive-quiz-builder' ),
						esc_html( $param )
					)
				)
			);
		}
	}

	// Validate date format and range
	if ( ! strtotime( $filter['start_date'] ) || ! strtotime( $filter['end_date'] ) ) {
		return array(
			'error' => array(
				'error_type' => 'invalid_date_format',
				'message' => esc_html__( 'Invalid date format', 'thrive-quiz-builder' )
			)
		);
	}

	if ( strtotime( $filter['start_date'] ) > strtotime( $filter['end_date'] ) ) {
		return array(
			'error' => array(
				'error_type' => 'invalid_date_range',
				'message' => esc_html__( 'Start date cannot be after end date', 'thrive-quiz-builder' )
			)
		);
	}

	// Validate interval
	$valid_intervals = array( 'day', 'week', 'month' );
	if ( ! in_array( $filter['interval'], $valid_intervals ) ) {
		return array(
			'error' => array(
				'error_type' => 'invalid_interval',
				'message' => esc_html__( 'Invalid interval type', 'thrive-quiz-builder' )
			)
		);
	}

	$report_data = $tqbdb->get_report_data_count_event_type( $filter );
	if ( $report_data === false ) {
		return array(
			'error' => array(
				'error_type' => 'fetch_failed',
				'message' => esc_html__( 'Failed to fetch report data', 'thrive-quiz-builder' )
			)
		);
	}

	$group_names = $filter['group_names'];

	//generate interval to fill empty dates.
	$dates = tqb_generate_dates_interval( $filter['start_date'], $filter['end_date'], $filter['interval'] );
	if ( empty( $dates ) ) {
		return array(
			'error' => array(
				'error_type' => 'failed_intervals',
				'message' => esc_html__( 'Failed to generate date intervals', 'thrive-quiz-builder' )
			)
		);
	}

	$chart_data_temp = array();
	foreach ( $report_data as $interval ) {
		// Validate interval data
		if ( ! isset( $interval->data_group, $interval->event_type, $interval->date_interval, $interval->log_count ) ) {
			continue; // Skip invalid data
		}

		//Group all report data by main_group_id
		if ( ! isset( $chart_data_temp[ $interval->data_group ] ) ) {
			$chart_data_temp[ $interval->data_group ] = array(
				'id'   => intval( $interval->data_group ),
				'name' => isset( $group_names[ intval( $interval->data_group ) ] ) ? esc_html( $group_names[ intval( $interval->data_group ) ] ) : '',
				'data' => array(
					Thrive_Quiz_Builder::TQB_CONVERSION => array(),
					Thrive_Quiz_Builder::TQB_IMPRESSION => array(),
				),
			);
		}

		//store the date interval so we can add it as X Axis in the chart
		if ( $filter['interval'] == 'day' ) {
			$timezone_diff = tqb_get_timezone_offset();
			$interval->date_interval = date( 'd M, Y', strtotime( $interval->date_interval ) + $timezone_diff );
		}

		$chart_data_temp[ $interval->data_group ]['data'][ intval( $interval->event_type ) ][ $interval->date_interval ] = intval( $interval->log_count );
	}

	$chart_data = array();
	foreach ( $group_names as $key => $name ) {
		$chart_data[ $key ] = array(
			'id'   => intval( $key ),
			'name' => esc_html( $name ),
			'data' => array(),
		);

		// Complete missing data with zero and calculate rates based on interval
		foreach ( $dates as $date ) {
			// Get impressions and conversions for this specific date/interval
			$impressions = isset( $chart_data_temp[ $key ]['data'][ Thrive_Quiz_Builder::TQB_IMPRESSION ][ $date ] ) ?
				max( 0, intval( $chart_data_temp[ $key ]['data'][ Thrive_Quiz_Builder::TQB_IMPRESSION ][ $date ] ) ) : 0;
			$conversions = isset( $chart_data_temp[ $key ]['data'][ Thrive_Quiz_Builder::TQB_CONVERSION ][ $date ] ) ?
				max( 0, intval( $chart_data_temp[ $key ]['data'][ Thrive_Quiz_Builder::TQB_CONVERSION ][ $date ] ) ) : 0;

			// Calculate conversion rate using only this interval's data (non-cumulative for all intervals: day, week, month)
			// If no impressions or conversions, rate will be 0
			$rate = tqb_conversion_rate( $impressions, $conversions, '', 2 );

			$chart_data[ $key ]['data'][] = (float) $rate;
		}
	}

	return array(
		'chart_title'  => esc_html__( 'Conversion rate over time', 'thrive-quiz-builder' ),
		'chart_data'   => $chart_data,
		'chart_x_axis' => $dates,
		'chart_y_axis' => esc_html__( 'Conversion Rate', 'thrive-quiz-builder' ) . ' (%)',
	);
}

/**
 * Function that will generate a cumulative normal distribution and return the confidence level as a number between 0 and 1
 *
 * @param $x
 *
 * @return float
 */
function tqb_norm_dist( $x ) {
	$b1 = 0.319381530;
	$b2 = - 0.356563782;
	$b3 = 1.781477937;
	$b4 = - 1.821255978;
	$b5 = 1.330274429;
	$p  = 0.2316419;
	$c  = 0.39894228;

	if ( $x >= 0.0 ) {
		if ( ( 1.0 + $p * $x ) == 0 ) {
			return 'N/A';
		}
		$t = 1.0 / ( 1.0 + $p * $x );

		return ( 1.0 - $c * exp( - $x * $x / 2.0 ) * $t * ( $t * ( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ) );
	} else {
		if ( ( 1.0 - $p * $x ) == 0 ) {
			return 'N/A';
		}
		$t = 1.0 / ( 1.0 - $p * $x );

		return ( $c * exp( - $x * $x / 2.0 ) * $t * ( $t * ( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ) );
	}
}

/**
 * Function that will create frontend error message
 *
 * @param $text
 *
 * @return string
 */
function tqb_create_frontend_error_message( $text ) {
	$html = '';
	foreach ( $text as $error ) {
		$html .= '<div class="tqb-frontend-error-message-individual"><p class="tqb-error-message"><span>' . __( 'Error:', 'thrive-quiz-builder' ) . ' </span> ' . $error . '</p></div>';
	}

	return $html;
}

/**
 * Add 'move forward' event on visual editor
 */
function tqb_event_actions( $actions, $scope, $post_id ) {

	$post       = get_post( $post_id );
	$post_types = array(
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_SPLASH_PAGE,
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_OPTIN,
	);
	if ( ! empty( $post ) && in_array( $post->post_type, $post_types ) ) {
		require_once dirname( dirname( __FILE__ ) ) . '/tcb-bridge/class-tqb-next-step-event.php';
		$actions['thrive_quiz_next_step'] = array(
			'class' => 'TQB_Thrive_Next_Step',
			'order' => 90,
		);
	}

	return $actions;
}

/**
 * Gets default values
 *
 * @param string $option
 *
 * @return array
 */
function tqb_get_default_values( $option = '' ) {

	$has_quizzes = get_posts( array(
		'post_type' => Thrive_Quiz_Builder::SHORTCODE_NAME,
	) );

	switch ( $option ) {
		case Thrive_Quiz_Builder::PLUGIN_SETTINGS:
			$default_values = array(
				'tqb_promotion_badge' => empty( $has_quizzes[0] ) ? 1 : 0,
				'tqb_answer_submission_mode' => 0,
				'tqb_auto_clear_stats' => 0,
				'tqb_stats_duration' => 90,
				'tqb_stats_duration_unit' => 'days',
			);
			break;
		default:
			$default_values = array();
	}

	return $default_values;
}

function tqb_get_svg_icon( $icon, $class = '', $return = false ) {
	if ( ! $class ) {
		$class = 'tqb-' . $icon;
	}

	$html = '<svg class="' . $class . '"><use xlink:href="#tqb-' . $icon . '"></use></svg>';

	if ( false !== $return ) {
		return $html;
	}
	echo $html; // phpcs:ignore
}

function tqb_add_frontend_svg_file() {
	include tqb()->plugin_path( 'assets/images/tqb-svg-icons.svg' );
}

/**
 * @param       $option_name
 * @param array $default_values
 *
 * @return array|mixed
 */
function tqb_get_option( $option_name, $default_values = array() ) {

	$option = get_option( $option_name );

	// On-demand repair: Handle double-serialized data (fix for PHP 8.3 compatibility)
	// This only runs if data is corrupted, not on every site.
	// WordPress's get_option() auto-unserializes once. If it's still a serialized string,
	// it means the data was double-serialized and needs repair.
	if ( is_string( $option ) && $option !== '' ) {
		$option = tqb_repair_double_serialized_data( $option_name, $option );
	}

	// WordPress should have already unserialized, but use maybe_unserialize as a safety net
	$option = maybe_unserialize( $option );

	// Early exit: If we have valid option data, return it
	if ( ! empty( $option ) ) {
		return $option;
	}

	// No option exists, add default values and return them
	add_option( $option_name, $default_values );

	return $default_values;
}

/**
 * Repair double-serialized option data
 * Extracts nested logic from tqb_get_option for better maintainability
 *
 * @param string $option_name The name of the option being repaired
 * @param string $option      The potentially double-serialized option value
 *
 * @return mixed The repaired option value or original if no repair needed
 */
function tqb_repair_double_serialized_data( $option_name, $option ) {
	// Early exit: If the string is not serialized, no repair needed
	if ( ! is_serialized( $option ) ) {
		return $option;
	}

	// Unserialize to check if it contains an array or object (indicates double-serialization)
	$unserialized = unserialize( $option );

	// Early exit: If result is not an array or object, no repair needed
	if ( ! is_array( $unserialized ) && ! is_object( $unserialized ) ) {
		return $option;
	}

	// Fix the corrupted data by saving it properly (only happens once per corrupted option)
	update_option( $option_name, $unserialized );

	return $unserialized;
}

/**
 * Check if stats tracking is enabled
 *
 * @return bool True if stats tracking is enabled, false otherwise
 */
function tqb_is_stats_tracking_enabled() {
	$settings = tqb_get_option( Thrive_Quiz_Builder::PLUGIN_SETTINGS, tqb_get_default_values( Thrive_Quiz_Builder::PLUGIN_SETTINGS ) );

	// If setting is not set or is not 0, tracking is enabled (default is ON)
	return ! isset( $settings['tqb_enable_stats_tracking'] ) || $settings['tqb_enable_stats_tracking'] !== 0;
}

/**
 * Wrapper over the update option
 *
 * @param              $option_name
 * @param array|object $value
 * @param boolean      $serialize
 *
 * @return array|mixed
 */
function tqb_update_option( $option_name, $value, $serialize = false ) {

	if ( empty( $option_name ) || empty( $value ) ) {
		return false;
	}

	$old_value = tqb_get_option( $option_name );

	/* Merge new values with old values to preserve keys not present in the new value */
	if ( is_array( $old_value ) && is_array( $value ) ) {
		$value = array_merge( $old_value, $value );
		// Compare merged value with old value to find differences (including new keys)
		$diff = array_diff_assoc( $value, $old_value );
	} else if ( is_object( $old_value ) && is_object( $value ) ) {
		$diff = array_diff_assoc( get_object_vars( $value ), get_object_vars( $old_value ) );
	} else {
		$diff = ! ( $old_value == $value );
	}

	/* If the new value is the same with the old one, return true and don't update */
	if ( empty( $diff ) ) {
		return true;
	}

	// WordPress's update_option() automatically serializes arrays and objects,
	// so we don't need to manually serialize to avoid double serialization.
	// The $serialize parameter is kept for backward compatibility but ignored.
	return update_option( $option_name, $value );
}

/**
 * An array with all TQB post types
 *
 * @return array
 */
function tqb_get_all_post_types() {
	return array(
		Thrive_Quiz_Builder::SHORTCODE_NAME,
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_SPLASH_PAGE,
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_QNA,
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_OPTIN,
		Thrive_Quiz_Builder::QUIZ_STRUCTURE_ITEM_RESULTS,
	);
}

/**
 * Merge css in case it was saved as array of media queries
 *
 * @param array|string $styles
 *
 * @return string
 */
function tqb_merge_media_query_styles( $styles ) {
	$css = '';

	if ( is_array( $styles ) ) {
		foreach ( $styles as $media => $style ) {
			$css .= '@media ' . $media . '{' . $style . '}';
		}
	} else if ( is_string( $styles ) ) {
		$css = $styles;
	}

	return $css;
}

/**
 * @param string $path
 *
 * @return bool
 */
function tqb_empty_folder( $path ) {

	if ( empty( $path ) || false === current_user_can( 'manage_options' ) || true !== WP_Filesystem() ) {
		return false;
	}

	/** @var WP_Filesystem_Direct $wp_filesystem */
	global $wp_filesystem;

	$wp_filesystem->delete( $path, true );

	return true;
}

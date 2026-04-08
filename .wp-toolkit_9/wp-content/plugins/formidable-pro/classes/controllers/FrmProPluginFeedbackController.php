<?php
/**
 * Plugin Feedback Controller class.
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles the plugin feedback process for Formidable.
 *
 * @since 6.26.1
 */
class FrmProPluginFeedbackController {

	/**
	 * The meta key for storing the feedback.
	 *
	 * @var string
	 */
	const PLUGIN_FEEDBACK_META_KEY = 'frm-plugin-feedback';

	/**
	 * The cache key for storing the eligibility check result.
	 *
	 * @var string
	 */
	const ELIGIBILITY_CACHE_KEY = 'frm-plugin-feedback-eligibility';

	/**
	 * The user ID for the current user.
	 *
	 * @var int
	 */
	private static $user_id;

	/**
	 * The plugin feedback data for the current user.
	 *
	 * @var array
	 */
	private static $plugin_feedback;

	/**
	 * The current year.
	 *
	 * @var int
	 */
	private static $current_year;

	/**
	 * Loads admin hooks for the feedback controller.
	 *
	 * @return void
	 */
	public static function load_admin_hooks() {
		self::$user_id = get_current_user_id();

		if ( ! self::$user_id || ! self::should_show_plugin_feedback() ) {
			return;
		}

		add_filter( 'frm_should_show_floating_links', '__return_false' );
		add_action( 'admin_enqueue_scripts', self::class . '::enqueue_assets' );
		add_action( 'admin_footer', self::class . '::show_plugin_feedback', 1 );
	}

	/**
	 * Checks if the plugin feedback should be shown.
	 *
	 * @return bool
	 */
	private static function should_show_plugin_feedback() {
		if ( self::is_local_environment() || ! FrmAppHelper::is_formidable_admin() || ! self::has_reached_january_25th() || self::get_current_year_feedback()['submitted'] ) {
			return false;
		}

		return self::check_feedback_eligibility();
	}

	/**
	 * Checks if the site is running in a local development environment.
	 *
	 * @return bool True if local environment, false otherwise.
	 */
	private static function is_local_environment() {
		return in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}

	/**
	 * Checks if today is on or after January 25th using WordPress timezone.
	 *
	 * @return bool
	 */
	private static function has_reached_january_25th() {
		return wp_date( 'm-d' ) >= '01-25';
	}

	/**
	 * Checks feedback eligibility via formidableforms.com API.
	 *
	 * @return bool
	 */
	private static function check_feedback_eligibility() {
		$license_key = FrmAddonsController::get_pro_license();

		if ( ! $license_key ) {
			return false;
		}

		$cached = self::get_cached_eligibility();

		if ( null !== $cached ) {
			return (bool) $cached['eligible'];
		}

		$response = wp_remote_get(
			'https://formidableforms.com/wp-json/s11edd/v1/plugin-feedback?l=' . urlencode( base64_encode( $license_key ) ),
			array(
				'timeout'    => 30,
				'user-agent' => 'formidable-pro/' . FrmProDb::$plug_version . '; ' . get_bloginfo( 'url' ),
			)
		);

		if ( is_wp_error( $response ) || WP_Http::OK !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data        = json_decode( wp_remote_retrieve_body( $response ), true );
		$is_eligible = false;

		if ( is_array( $data ) && isset( $data['eligible'] ) ) {
			$is_eligible = (bool) $data['eligible'];
		}

		self::cache_eligibility( $is_eligible );

		return $is_eligible;
	}

	/**
	 * Caches the eligibility result.
	 *
	 * @param bool $is_eligible Whether the user is eligible for feedback.
	 *
	 * @return void
	 */
	private static function cache_eligibility( $is_eligible ) {
		$data = array(
			'eligible' => $is_eligible,
			'timeout'  => time() + ( $is_eligible ? DAY_IN_SECONDS : MONTH_IN_SECONDS ),
		);

		update_option( self::ELIGIBILITY_CACHE_KEY, $data, false );
	}

	/**
	 * Caches eligibility as false until January 24th of next year after user submits feedback.
	 *
	 * @return void
	 */
	private static function cache_eligibility_after_submission() {
		$next_year = self::get_current_year() + 1;
		$datetime  = new DateTime( $next_year . '-01-24 23:59:59', wp_timezone() );
		$data      = array(
			'eligible'    => false,
			'timeout'     => $datetime->getTimestamp(),
			'skip_compat' => true,
		);

		update_option( self::ELIGIBILITY_CACHE_KEY, $data, false );
	}

	/**
	 * Gets cached eligibility result.
	 *
	 * @return array|null Cached data if valid, null if no cache or expired.
	 */
	private static function get_cached_eligibility() {
		$cache = get_option( self::ELIGIBILITY_CACHE_KEY, array() );

		if ( ! isset( $cache['timeout'], $cache['eligible'] ) || time() > $cache['timeout'] ) {
			return null;
		}

		// Skip backward compatibility if cache was set after submission, those keep their 1-year timeout.
		if ( ! empty( $cache['skip_compat'] ) ) {
			return $cache;
		}

		// Backward compatibility: re-validate old cache timeouts to current eligibility-based limits.
		$max_timeout = time() + ( $cache['eligible'] ? DAY_IN_SECONDS : MONTH_IN_SECONDS );

		if ( $cache['timeout'] > $max_timeout ) {
			$cache['timeout'] = $max_timeout;
			update_option( self::ELIGIBILITY_CACHE_KEY, $cache, false );
		}

		return $cache;
	}

	/**
	 * Enqueues the necessary assets for the plugin feedback.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$version = FrmProDb::$plug_version;

		wp_enqueue_script( 'formidable-pro-plugin-feedback', FrmProAppHelper::plugin_url() . '/js/plugin-feedback.js', array( 'formidable_dom' ), $version, true );
		wp_enqueue_style( 'formidable-pro-plugin-feedback', FrmProAppHelper::plugin_url() . '/css/components/plugin-feedback.css', array(), $version );
	}

	/**
	 * Shows the plugin feedback.
	 *
	 * @return void
	 */
	public static function show_plugin_feedback() {
		$step    = isset( self::get_current_year_feedback()['nps-score'] ) ? 'reasons' : 'nps';
		$reasons = self::get_reasons();

		include FrmProAppHelper::plugin_path() . '/classes/views/global/plugin-feedback.php';
	}

	/**
	 * AJAX callback for submitting the plugin feedback.
	 *
	 * @return void
	 */
	public static function ajax_submit_plugin_feedback() {
		check_ajax_referer( 'frm_ajax', 'nonce' );
		FrmAppHelper::permission_check( 'frm_change_settings' );

		self::maybe_save_nps_and_send_response();
		self::submit_feedback_to_remote();
	}

	/**
	 * AJAX callback for dismissing the plugin feedback.
	 *
	 * @return void
	 */
	public static function ajax_dismiss_plugin_feedback() {
		check_ajax_referer( 'frm_ajax', 'nonce' );
		FrmAppHelper::permission_check( 'frm_change_settings' );

		self::submit_feedback_to_remote();
	}

	/**
	 * Saves NPS score locally and sends early response for the first step.
	 *
	 * Instead of sending the NPS score to the server immediately, we store it locally.
	 * This reduces server requests and avoids sending separate data for each step.
	 * The score is sent along with feedback data in submit_feedback_to_remote(),
	 * whether the user submits or dismisses.
	 *
	 * @return void
	 */
	private static function maybe_save_nps_and_send_response() {
		// No nps-score means we're past the first step.
		// Return silently to let submit_feedback_to_remote() handle the request.
		if ( ! isset( $_POST['nps-score'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		$nps_score = (int) FrmAppHelper::get_post_param( 'nps-score', 10, 'absint' );

		if ( $nps_score < 0 || $nps_score > 10 ) {
			wp_send_json_error( array( 'type' => 'invalid-nps' ) );
		}

		self::set_current_year_feedback( 'nps-score', $nps_score );
		wp_send_json_success( array( 'message' => __( 'Feedback score saved successfully.', 'formidable-pro' ) ) );
	}

	/**
	 * Submits feedback data to remote service.
	 *
	 * @return void
	 */
	private static function submit_feedback_to_remote() {
		// No local nps-score means user dismissed at the first step.
		// Mark current year feedback as submitted without sending data to the server.
		if ( ! isset( self::get_current_year_feedback()['nps-score'] ) ) {
			self::set_current_year_feedback( 'submitted', true );
			self::cache_eligibility_after_submission();
			wp_send_json_success( array( 'message' => __( 'Feedback dismissed successfully.', 'formidable-pro' ) ) );
		}

		$remote_response = wp_remote_post(
			'https://formidableforms.com/wp-admin/admin-ajax.php?action=frm_forms_preview&form=plugin-feedback',
			array(
				'timeout' => 30,
				'body'    => http_build_query(
					array(
						'l'               => base64_encode( FrmAddonsController::get_pro_license() ),
						'form_key'        => 'plugin-feedback',
						'frm_action'      => 'create',
						'form_id'         => 289,
						'item_key'        => '',
						'item_meta[0]'    => '',
						'item_meta[4517]' => self::get_current_year_feedback()['nps-score'],
						'item_meta[4518]' => self::format_reasons_list( self::get_posted_reasons() ),
						'item_meta[4519]' => FrmAppHelper::get_post_param( 'details', '' ),
						'item_meta[4520]' => site_url(),
						'item_meta[4521]' => 0,
					)
				),
			)
		);

		if ( is_wp_error( $remote_response ) ) {
			wp_send_json_error(
				array(
					'type'    => 'server-error',
					'message' => __( 'Failed to submit feedback to remote service.', 'formidable-pro' ),
				)
			);
		}

		$response_code = wp_remote_retrieve_response_code( $remote_response );

		if ( WP_Http::OK !== $response_code ) {
			wp_send_json_error(
				array(
					'type'    => 'server-error',
					'message' => __( 'Remote service returned an error.', 'formidable-pro' ),
				)
			);
		}

		self::set_current_year_feedback( 'submitted', true );
		self::cache_eligibility_after_submission();
		wp_send_json_success( array( 'message' => __( 'Feedback submitted successfully.', 'formidable-pro' ) ) );
	}

	/**
	 * Validates and sanitizes feedback reasons from POST request.
	 *
	 * @return array Validated and sanitized feedback reasons array.
	 */
	private static function get_posted_reasons() {
		$reasons = json_decode( FrmAppHelper::get_post_param( 'reasons', '[]' ), true );
		$reasons = rest_sanitize_value_from_schema(
			$reasons,
			array(
				'type'  => 'array',
				'items' => array(
					'enum' => array_keys( self::get_reasons() ),
					'type' => 'string',
				),
			)
		);

		if ( ! $reasons && ! FrmAppHelper::get_post_param( 'dismissed', false, 'rest_sanitize_boolean' ) ) {
			wp_send_json_error( array( 'type' => 'invalid-reasons' ) );
		}

		return $reasons;
	}

	/**
	 * Formats validated feedback reason keys as a bulleted text list.
	 *
	 * @param array $reason_keys Array of validated reason keys.
	 *
	 * @return string Formatted list with each reason on a new line prefixed with "- ".
	 */
	private static function format_reasons_list( $reason_keys ) {
		if ( ! $reason_keys ) {
			return '';
		}

		$reasons           = self::get_reasons();
		$formatted_reasons = array_map(
			static function ( $key ) use ( $reasons ) {
				return '- ' . $reasons[ $key ];
			},
			$reason_keys
		);

		return implode( "\n", $formatted_reasons );
	}

	/**
	 * Returns the plugin feedback.
	 *
	 * @return array
	 */
	private static function get_plugin_feedback() {
		if ( self::$plugin_feedback ) {
			return self::$plugin_feedback;
		}

		self::$plugin_feedback = get_user_meta( self::$user_id, self::PLUGIN_FEEDBACK_META_KEY, true );

		// No feedback history exists yet, initialize empty structure.
		if ( ! is_array( self::$plugin_feedback ) ) {
			self::$plugin_feedback = array( self::get_current_year() => array( 'submitted' => false ) );
		}

		return self::$plugin_feedback;
	}

	/**
	 * Gets the plugin feedback data for the current year.
	 *
	 * @return array
	 */
	private static function get_current_year_feedback() {
		return self::get_plugin_feedback()[ self::get_current_year() ];
	}

	/**
	 * Sets a key-value pair in the current year's feedback data and updates user meta.
	 *
	 * @param string $key   The feedback data key.
	 * @param mixed  $value The value to set.
	 *
	 * @return void
	 */
	private static function set_current_year_feedback( $key, $value ) {
		self::get_plugin_feedback(); // Ensure initialized.
		self::$plugin_feedback[ self::get_current_year() ][ $key ] = $value;
		update_user_meta( self::$user_id, self::PLUGIN_FEEDBACK_META_KEY, self::$plugin_feedback );
	}

	/**
	 * Gets the current year.
	 *
	 * @return int
	 */
	private static function get_current_year() {
		if ( self::$current_year ) {
			return self::$current_year;
		}

		self::$current_year = (int) wp_date( 'Y' );
		return self::$current_year;
	}

	/**
	 * Returns the feedback reasons.
	 *
	 * @return array
	 */
	private static function get_reasons() {
		// Note: Do not make the text translatable, as it will be sent to a remote service.
		return array(
			'pricing'          => 'Pricing and plans',
			'form-builder'     => 'Form builder flexibility',
			'customization'    => 'Customization options',
			'integrations'     => 'Integrations',
			'advanced-fields'  => 'Advanced fields',
			'customer-support' => 'Customer support',
			'templates'        => 'Template selection',
			'performance'      => 'Performance/Speed',
			'calculations'     => 'Calculations & formulas',
			'documentation'    => 'Documentation / tutorials',
		);
	}
}

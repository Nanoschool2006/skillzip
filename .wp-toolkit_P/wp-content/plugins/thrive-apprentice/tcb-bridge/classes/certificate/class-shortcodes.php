<?php

namespace TVA\Architect\Certificate;

use TVA\Reporting\Events\Course_Finish;
use TVE\Reporting\Logs;
use TVA\Architect\Visual_Builder\Shortcodes as Visual_Builder_Shortcodes;

class Shortcodes {
	/**
	 * @var string[]
	 */
	private static $_shortcodes = [
		'tva_certificate_title'          => 'certificate_title',
		'tva_certificate_course_name'    => 'course_name',
		'tva_certificate_course_summary' => 'course_summary',
		'tva_certificate_course_author'  => 'course_author',
		'tva_certificate_inline_link'    => 'inline_link',
		'tva_certificate_number'         => 'number',
		'tva_certificate_recipient'      => 'recipient',
		'tva_certificate_date1'          => 'date',
		'tva_certificate_date2'          => 'date',
		'tva_certificate_date3'          => 'date',
		'tva_qr_source'                  => 'qr_source',
		'tva_certificate_course_grade'   => 'course_grade',
		'tva_certificate_course_result'  => 'course_result',
	];

	/**
	 * Shortcodes constructor.
	 */
	public static function init() {
		foreach ( static::$_shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, array( __CLASS__, $function ) );
		}
	}

	/**
	 * Renders the Certificate Date ShortCode
	 *
	 * @param array  $attr      of the shortcode
	 * @param string $content   of the shortcode to be displayed
	 * @param string $shortcode string
	 *
	 * @return string $content
	 */
	public static function date( $attr, $content, $shortcode ) {
		global $certificate;
		$formats = array(
			'1' => 'd M Y',
			'2' => 'd/m/Y',
			'3' => 'm/d/Y',
		);

		$format_index = str_replace( 'tva_certificate_date', '', $shortcode );
		$date         = 'certificate date';

		if ( empty( $certificate ) ) {
			$name        = static::number();
			$certificate = static::get_certificate_course()->get_certificate()->search_by_number( $name );
		}

		$user_id = null;
		$post_id = null;

		if ( ! empty( $certificate ) ) {
			$user_id = ! empty( $certificate['user_id'] ) ? $certificate['user_id'] : null;
			$post_id = ! empty( $certificate['course_id'] ) ? $certificate['course_id'] : null;
		}

		if ( empty( $user_id ) && empty( $post_id ) && get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			$user_data = tve_current_user_data();
			$post_id   = static::get_certificate_course()->get_id();
			$user_id   = ! empty( $user_data['id'] ) ? (int) $user_data['id'] : null;
		}

		if ( ! empty( $user_id ) && ! empty( $post_id ) ) {
			$logs = Logs::get_instance();
			$args = [
				'fields'             => 'created',
				'event_type'         => Course_Finish::key(),
				'filters'            => [
					'user_id' => $user_id,
					'post_id' => $post_id,
				],
				'page'               => '1',
				'items_per_page'     => '1',
				'order_by'           => 'created',
				'order_by_direction' => 'DESC',
			];

			$results = $logs->set_query( $args )->get_results();
			$result  = reset( $results );

			if ( ! empty( $result['created'] ) ) {
				$date = wp_date( $formats[ $format_index ], strtotime( $result['created'] ) );

				return $content . $date;
			}
		}

		if ( ! empty( $certificate['timestamp'] ) ) {
			$date = wp_date( $formats[ $format_index ], $certificate['timestamp'] );
		}

		return $content . $date;
	}

	/**
	 * Renders the Certificate Recipient ShortCode
	 *
	 * @return string
	 */
	public static function recipient() {
		$recipient = 'certificate recipient';

		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			return tve_current_user_data()['display_name'];
		}

		global $certificate;

		if ( ! empty( $certificate ) ) {
			$recipient = $certificate['recipient']->display_name;
		}

		return $recipient;
	}

	/**
	 * Renders the Certificate Number ShortCode
	 *
	 * @return string $content
	 */
	public static function number() {
		$number = 'certificate number';

		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			//In certificate context - Edit certificate with TAR & Certificate PDF
			$custom_code = static::get_certificate_course()->get_certificate()->get_number();

			if ( ! empty( $custom_code ) ) {
				$number = $custom_code;
			}

			return $number;
		}

		global $certificate;

		if ( ! empty( $certificate ) ) {
			$number = $certificate['number'];
		}

		return $number;
	}

	/**
	 * Inline link used into the Certificate Verification Element to get back to previous state
	 * - handled by JS
	 *
	 * @return string
	 */
	public static function inline_link() {
		return '#';
	}

	/**
	 * Certificate title shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public static function certificate_title( $attr = array(), $content = '' ) {
		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			return get_the_title();
		}

		return '';
	}

	private static function get_certificate_course() {
		return Main::get_certificate_course( get_the_ID() );
	}

	/**
	 * Course name shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public static function course_name( $attr = array(), $content = '' ) {

		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			$course = static::get_certificate_course();

			return $course->name;
		}

		global $certificate;

		$name = 'certificate course name';

		if ( ! empty( $certificate ) ) {
			$name = $certificate['course']->name;
		}

		return $content . $name;
	}

	/**
	 * Course summary shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public static function course_summary( $attr = array(), $content = '' ) {
		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			$course = static::get_certificate_course();

			return $course->excerpt;
		}

		return '';
	}

	/**
	 * Course author shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public static function course_author( $attr = array(), $content = '' ) {

		if ( get_post_type() === \TVA_Course_Certificate::POST_TYPE ) {
			$course = static::get_certificate_course();

			return $course->get_author()->get_user()->display_name;
		}

		return '';
	}

	/**
	 * QR code source callback
	 *
	 * @param $attr
	 * @param $content
	 *
	 * @return string|null
	 */
	public static function qr_source( $attr = array() ) {
		$type   = $attr['type'];
		$source = '';
		if ( $type === 'verification' ) {
			$source = add_query_arg( [
				'u'                                                   => static::number(),
				\TVA_Course_Certificate::VERIFICATION_PAGE_QUERY_NAME => \TVA_Course_Certificate::VERIFICATION_PAGE_QUERY_VAL,
			], home_url() );
		} else if ( $type === 'course' ) {
			if ( is_int( get_the_ID() ) ) {
				$source = static::get_certificate_course()->get_link( false );
			}
		} else if ( $type === 'site' ) {
			$source = site_url();
		}

		return $source;
	}

	/**
	 * Displays the grade of a course
	 *
	 * This function will only work if it is called from within a course certificate.
	 *
	 * @return string the grade of the course
	 */
	public static function course_grade(): string {
		return static::get_course_grade( 'grade' );
	}

	/**
	 * Displays the result of a course in a certificate context.
	 *
	 * @return string The result of the course or an error message if the context is invalid.
	 */
	public static function course_result(): string {
		return static::get_course_grade( 'result' );
	}

	/**
	 * Retrieves the grade for a course based on specified variant.
	 *
	 * @param string $variant The display variant for the grade (e.g., 'result', 'grade').
	 *
	 * @return string The calculated course grade or an error message if the context is invalid.
	 */
	private static function get_course_grade( string $variant ): string {
		if ( get_post_type() !== \TVA_Course_Certificate::POST_TYPE ) {
			return __( 'Current post type is not a course certificate', 'thrive-apprentice' );
		}

		$course = static::get_certificate_course();

		$vb_sc = new Visual_Builder_Shortcodes();

		$grade_settings = $vb_sc->get_grade_settings($course->get_id());
		
		$weight_data = $grade_settings['weight_data'];
		if ( empty( $weight_data ) ) {
			return 'Please select proper assessment weighting under the course grade settings';
		}

		if ( $grade_settings['grade_weight_type'] === 'weight-hardcode' ) {
			return $grade_settings['display_as_custom'];
		}

		$temp_data = $vb_sc->generate_temp_data( $course->get_all_items() );
		$weight_total = $vb_sc->get_weight_total( $temp_data, $weight_data );

		return $vb_sc->get_weight_display( $grade_settings['display_as'], get_term_meta( $course->get_id(), 'tva_grade', true ), $weight_total, $variant );
	}
}

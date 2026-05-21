<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Architect\Visual_Builder;

use TCB_Utils;
use TVA\Access\Expiry\Base;
use TVD_Global_Shortcodes;
use TVA\Architect\Assessment\Result;
use TVA\Assessments\TVA_User_Assessment;
use TVA_Assessment;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Shortcodes
 *
 * @project: thrive-apprentice
 */
class Shortcodes {

	/**
	 * Contains the List of Shortcodes
	 *
	 * @var array
	 */
	private $shortcodes = [
		'tva_content_post_title'         => 'post_title',
		'tva_content_course_title'       => 'course_title',
		'tva_content_post_summary'       => 'post_summary',
		'tva_content_difficulty_name'    => 'difficulty',
		'tva_content_course_type'        => 'course_type',
		'tva_content_course_type_icon'   => 'course_type_icon',
		'tva_content_course_progress'    => 'course_progress',
		'tva_content_course_topic_title' => 'course_topic_title',
		'tva_content_course_topic_icon'  => 'course_topic_icon',
		'tva_content_course_label_title' => 'course_label_title',
		'tva_course_grade'               => 'course_grade',
		'tva_course_result'              => 'course_result',
	];

	/**
	 * Strings that is shown if the shortcode is placed in a non course context
	 * Ex: save a shortcode from a lesson page and place it into a page
	 *
	 * @var string
	 */
	private $could_not_determine_course = 'Could not determine course';

	/**
	 * Shortcodes constructor.
	 */
	public function __construct() {
		foreach ( $this->shortcodes as $shortcode => $function ) {
			add_shortcode( $shortcode, [ $this, $function ] );
		}
	}

	/**
	 * Apprentice Content Post Title Shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function post_title( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return TVD_Global_Shortcodes::maybe_link_wrap( tcb_tva_visual_builder()->get_title(), $attr );
	}

	/**
	 * Apprentice Content Course Title Shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_title( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		$course_title = tcb_tva_visual_builder()->get_active_course()->name;

		if ( ! empty( $attr['link'] ) ) {

			$attributes = array_filter( [
				'href'     => tcb_tva_visual_builder()->get_active_course()->get_link(),
				'target'   => ! empty( $attr['target'] ) ? '_blank' : '',
				'rel'      => ! empty( $attr['rel'] ) ? 'nofollow' : '',
				'data-css' => ! empty( $attr['link-css-attr'] ) ? 'link-css-attr' : '',
			], 'strlen' );

			$course_title = TCB_Utils::wrap_content( $course_title, 'a', '', array(), $attributes );
		} else {
			$course_title = TVD_Global_Shortcodes::maybe_link_wrap( $course_title, $attr );
		}

		return $course_title;
	}

	/**
	 * Apprentice Content Post Summary Shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function post_summary( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		$summary = nl2br( tcb_tva_visual_builder()->get_summary() );

		return TVD_Global_Shortcodes::maybe_link_wrap( $summary, $attr );
	}

	/**
	 * Apprentice Content Difficulty Shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function difficulty( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return TVD_Global_Shortcodes::maybe_link_wrap( tcb_tva_visual_builder()->get_difficulty_name(), $attr );
	}

	/**
	 * Apprentice Course Type Shortcode Callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_type( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return TVD_Global_Shortcodes::maybe_link_wrap( tcb_tva_visual_builder()->get_course_type(), $attr );
	}

	/**
	 * Apprentice Course Type Icon Shortcode Callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_type_icon( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return tcb_tva_visual_builder()->get_course_type_icon();
	}

	/**
	 * Apprentice Course Progress Callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_progress( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return TVD_Global_Shortcodes::maybe_link_wrap( tcb_tva_visual_builder()->get_course_progress(), $attr );
	}

	/**
	 * Apprentice Course Topic title callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_topic_title( $attr = [], $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		return TVD_Global_Shortcodes::maybe_link_wrap( tcb_tva_visual_builder()->get_course_topic_title(), $attr );
	}

	/**
	 * Course Topic Icon Element shortcode callback
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_topic_icon( $attr = array(), $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		$icon = tcb_tva_visual_builder()->get_course_topic_icon();

		if ( strpos( $icon, '<svg' ) !== false ) {
			$color = tcb_tva_visual_builder()->get_active_course_topic()->overview_icon_color;
			$html  = str_replace( '<svg', '<svg style="fill:' . $color . ';color:' . $color . ';"', $icon );
		} elseif ( wp_http_validate_url( $icon ) ) { //Returns false if not a valid URL or true if is valid URL
			$html = '<div class="tva-course-topic-icon-bg" style="background-image: url(' . $icon . ');"></div>';
		} else {
			$html = $icon;
		}

		return $html;
	}

	/**
	 * Course Label Title shortcode implementation
	 *
	 * @param array  $attr
	 * @param string $content
	 *
	 * @return string
	 */
	public function course_label_title( $attr = array(), $content = '' ) {

		if ( empty( tcb_tva_visual_builder()->get_active_course() ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}

		$label_data = tcb_tva_visual_builder()->get_course_label();

		if ( ( ! empty( $label_data['default_label'] ) || $label_data['ID'] < 0 ) && ! Main::$is_editor_page ) {
			return '';
		}

		if ( isset( $label_data['ID'] ) && $label_data['ID'] === 'access_about_to_expire' ) {
			$label_data['title'] = str_replace( '[days]', Base::get_days_until_expiration( get_current_user_id(), tcb_tva_visual_builder()->get_active_course()->get_product() ), $label_data['title'] );
		}

		$is_tar_element = ! empty( $attr['tar-element'] );

		if ( $is_tar_element ) {
			$shortcodeContentHTML = '<span class="thrive-shortcode-content" data-shortcode="tva_content_course_label_title" data-shortcode-name="Course Label" contenteditable="false" data-extra_key="">' . $label_data['title'] . '</span>';
			$content              = '<div class="thrv_wrapper thrv_text_element"><p><span class="thrive-inline-shortcode" contenteditable="false">' . $shortcodeContentHTML . '</span></p></div>';

			$attributes = array(
				'data-css' => $attr['css'],
			);

			$return = TCB_Utils::wrap_content( $content, 'div', '', [ 'thrv_wrapper', 'tva-course-label-title' ], $attributes );
		} else {
			$return = TVD_Global_Shortcodes::maybe_link_wrap( $label_data['title'], $attr );
		}

		return $return;
	}

	/**
	 * Return the shortcode keys
	 *
	 * @return array
	 */
	public function get_shortcodes() {
		return array_keys( $this->shortcodes );
	}

	/**
	 * Apprentice Course Grade
	 *
	 * @return string
	 */
	public function course_grade(): string {
		return $this->get_course_grade( 'grade' );
	}

	/**
	 * Apprentice Course Result
	 *
	 * @return string
	 */
	public function course_result(): string {
		return $this->get_course_grade( 'result' );
	}

	/**
	 * Latest assessment grade - shortcode callback
	 *
	 * @param TVA_Assessment $assessment
	 *
	 * @return string
	 */
	public static function grade_latest( TVA_Assessment $assessment = null): string {
		$grade = Result::get_not_graded_text();

		if ( ! $assessment instanceof TVA_Assessment ) {
			return $grade;
		}

		$latest_user_assessment = static::get_latest_user_assessment( $assessment );

		if ( ! empty( $latest_user_assessment ) ) {
			$grade = $latest_user_assessment->get_grade( true );

			if ( empty( $grade ) ) {
				$grade = Result::get_not_graded_text();
			}

		}

		return $grade;
	}


	/**
	 * Get the latest user assessment
	 *
	 * @param TVA_Assessment $assessment
	 *
	 * @return TVA_User_Assessment|null
	 */
	private static function get_latest_user_assessment( TVA_Assessment $assessment ): ?TVA_User_Assessment {
		$current = current( TVA_User_Assessment::get_user_submission( [
			'post_parent'    => $assessment->ID,
			'posts_per_page' => 1,
		] ) );

		if ( empty( $current ) ) {
			return null;
		}

		return $current;
	}

	/**
	 * Generates temporary data for comparing and calculating grades for each assessment.
	 *
	 * @param array $assesstments Array of assessments
	 * @return array
	 */
	public function generate_temp_data( array $assesstments ): array {
		// storing temp data for comparation and calculation for each assessment
		$temp_data = [
			'grading_method' => [],
			'grades' => [],
			'assessment_ids' => [],
		];

		foreach ( $assesstments as $assessment ) {
			if ( get_post_type( $assessment->ID ) !== 'tva_assessment' ) {
				continue;
			}

			$current_grade = static::grade_latest($assessment);
			$grading_method = get_post_meta( $assessment->ID, 'tva_grading_method', true );
			if ( $grading_method === 'percentage' ) {
				$temp_data['grades'][] = str_replace('%', '', $current_grade);
			}

			if ( $grading_method === 'score' ) {
				$max_score = get_post_meta( $assessment->ID, 'tva_max_score', true );

				if ( ! empty( $max_score ) ) {
					$temp_data['grades'][] = ( (float) $current_grade * 100 ) / (float) $max_score;
				}
			}

			if ( $grading_method === 'pass_fail' ) {
				$grade_val = 0;
				if ( $current_grade === 'Passed' ) {
					$grade_val = 100;
				}
				$temp_data['grades'][] = $grade_val;
			}

			if ( $grading_method === 'category' ) {
				$children = get_children(
					[
						'post_parent' => $assessment->ID,
						'post_type'   => 'tva_assessment_gr_c',
						'numberposts' => -1,
						'fields'       => 'ids',
						'orderby'     => 'id',		
					]
				);

				// reverse order
				$children = array_reverse( $children );

				$passed = [];
				$pass_counter = 0;
				$fail_counter = 0;
				$failed = [];
				foreach ( $children as $child ) {
					$child_title = get_the_title( $child );
					$child_type = get_post_meta( $child, 'tva_type', true ); // pass/fail
					if ( $child_type === 'pass' ) {
						$passed[$pass_counter]['title'] = $child_title;
						$passed[$pass_counter]['id'] = $child;
						$passed[$pass_counter]['value'] = 50;
						$pass_counter ++;
					} else {
						$failed[$fail_counter]['title'] = $child_title;
						$failed[$fail_counter]['id'] = $child;
						$failed[$fail_counter]['value'] = 0;
						$fail_counter ++;
					}
				}

				// Each passing grade step is calculated based on -> 50 + (( 50 / { Number of passing grades }) * { step })
				// Similar for failing -> 50 - (( 50 / { Number of failing grades }) * { step })
				$passed_divider = 50 / (float) $pass_counter;
				$failed_divider = 50 / (float) $fail_counter;

				$pass_val = 50;
				foreach ( $passed as &$pass ) {
					$pass_val += (float)$passed_divider;						
					$pass['value'] = $pass_val;
				}

				// update last element of passed array to 100 to avoid rounding issues
				$last_key = array_key_last($passed);
				$passed[$last_key]['value'] = 100;

				$fail_val = 0;
				foreach ( $failed as &$fail ) {
					$fail_val += (float)$failed_divider;
					$fail['value'] = $fail_val - 1;
				}

				// update last element of failed array to 49 to avoid rounding issues
				$last_key = array_key_last($failed);
				$failed[$last_key]['value'] = 49;

				// merge arrays
				$final = array_merge( $passed, $failed );

				foreach ( $final as $key => $value ) {
					if ( $value['title'] === $current_grade  ) {
						$temp_data['grades'][] = $value['value'];
						break;
					}
				}
			}
			
			$temp_data['assessment_ids'][] = $assessment->ID;
			$temp_data['grading_method'][] = get_post_meta( $assessment->ID, 'tva_grading_method', true );
		}
		return $temp_data;
	}

	/**
	 * Get the total weight for a course, given the grades and assessment weights.
	 * 
	 * @param array $temp_data Grades and assessment IDs for the course.
	 * @param array $weight_data Assessment weights for the course.
	 * 
	 * @return string The total weight for the course, rounded to 2 decimal places.
	 */
	public function get_weight_total( array $temp_data, array $weight_data ): string {
		$weight_total = 0;
		
		foreach ( $temp_data['assessment_ids'] as $key => $id ) {	
			$k = array_search( $id, $weight_data['assessment_ids'] );
			if ( $k === false ) {
				continue;
			}

			$grade = $temp_data['grades'][$key]; // percentage completed in assessment logic
			$weight = $weight_data['assessment_weights'][$k]; // assessment weight in course
			//grade:100 = real_weight:weight
			$real_weight = ( (float)$grade * (float)$weight ) / 100;
			
			$weight_total += (float)$real_weight;
		}

		return number_format((float)$weight_total, 2, '.', '');
	}

	/**
	 * Return the weight display for a course, given the grading method and weight total.
	 *
	 * @param string $display_as The grading method to use.
	 * @param int    $post_id    The ID of the course.
	 * @param string $weight_total The total weight for the course.
	 * @param string $variant    The course variant (grade, assessment, etc.).
	 *
	 * @return string The weight display for the course.
	 */
	public function get_weight_display( string $display_as, int $post_id, string $weight_total, string $variant ): string {

		if ( $variant === 'result' ) {
			return $weight_total;
		}

		if ( $display_as === 'percentage' ) {
			return $weight_total . '%';
		}

		if ( $display_as === 'score' ) {
			return $weight_total;
		}

		if ( $display_as === 'category' ) {
			$category_data = get_post_meta( $post_id, 'tva_category_data', true );
			if ( empty( $category_data ) ) {
				return $weight_total;
			}
			$category_data = json_decode( $category_data, true );
			foreach ( $category_data['category_labels'] as $key => $label ) {
				if ( (float)$weight_total >= (float)$category_data['cateory_ranges_from'][$key] 
					&& (float)$weight_total <= (float)$category_data['cateory_ranges_to'][$key] ) {
					return $label;
				}
			}
		}

		return $weight_total;
	}

	/**
	 * Retrieves the grade settings for a given course.
	 *
	 * @param int $course_id The ID of the course.
	 *
	 * @return array An associative array containing the grade settings.
	 */
	public function get_grade_settings($course_id) {
		$post_id = get_term_meta($course_id, 'tva_grade', true);
		return [
		  'grade_weight_type' => get_post_meta($post_id, 'tva_grade_type', true),
		  'display_as' => get_post_meta($post_id, 'tva_display_as', true),
		  'weight_data' => json_decode(get_post_meta($post_id, 'tva_weight_data', true), true),
		  'display_as_custom' => get_post_meta($post_id, 'tva_display_as_custom', true),
		];
	}

	/**
	 * Retrieves the grade for a course based on specified variant.
	 *
	 *
	 * @param string $variant The display variant for the grade (e.g., 'result', 'grade').
	 *
	 * @return string The calculated course grade or an error message if the context is invalid.
	 */
	public function get_course_grade( string $variant ): string {
	
		$course = tcb_tva_visual_builder()->get_active_course();

		if ( empty( $course ) ) {
			/**
			 * In case this shortcode ends up to be on a non course context content, we output an error string
			 * we need to be inside a course context content so the shortcode can render properly
			 */
			return $this->could_not_determine_course;
		}
		
		$grade_settings = $this->get_grade_settings($course->get_id());
		
		$weight_data = $grade_settings['weight_data'];
		if ( empty( $weight_data ) ) {
			return 'Please select proper assessment weighting under the course grade settings';
		}

		if ( $grade_settings['grade_weight_type'] === 'weight-hardcode' ) {
			return $grade_settings['display_as_custom'];
		}

		$temp_data = $this->generate_temp_data( $course->get_all_items() );
		$weight_total = $this->get_weight_total( $temp_data, $weight_data );

		return $this->get_weight_display( $grade_settings['display_as'], get_term_meta( $course->get_id(), 'tva_grade', true ), $weight_total, $variant );
	}
}

<?php

namespace TVA\Assessments\Grading;
use TVA_Course_V2;
use TVA_Customer;
use TVA_Assessment;
use function TVA\Architect\Dynamic_Actions\tcb_tva_dynamic_actions;

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package ${NAMESPACE}
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Base {
	const PASS_FAIL_METHOD  = 'pass_fail';
	const PERCENTAGE_METHOD = 'percentage';
	const SCORE_METHOD      = 'score';
	const CATEGORY_METHOD   = 'category';
	const CREDIT_ON_PASS   = 'upon_passing';
	const CREDIT_ON_GRADE  = 'upon_grading';
	const CREDIT_ON_SUBMIT = 'upon_submission';

	/** @var string $grading_method */
	protected $grading_method;

	/** @var bool $grading_manually_mark */
	protected $grading_manually_mark = 1;

	/** @var int|string $grading_passing_value */
	protected $grading_passing_value;

	/** @var int $assessment_id */
	protected $assessment_id;

	/** @var string $grading_completion_credit */
	protected $grading_completion_credit = '';

	public static $grading_methods = [
		self::PASS_FAIL_METHOD  => 'Pass/Fail',
		self::PERCENTAGE_METHOD => 'Percentage',
		self::SCORE_METHOD      => 'Score',
		self::CATEGORY_METHOD   => 'Category',
	];

	private $meta_keys = [
		'grading_method',
		'grading_passing_value',
		'grading_manually_mark',
		'grading_completion_credit',
	];

	public function __construct( $data ) {
		if ( ! empty( $data['grading_method'] ) && in_array( $data['grading_method'], array_keys( static::$grading_methods ) ) ) {
			$this->grading_method = $data['grading_method'];
		}

		if ( ! empty( $data['grading_passing_value'] ) ) {
			$this->grading_passing_value = $data['grading_passing_value'];
		}

		if ( isset( $data['grading_manually_mark'] ) ) {
			$this->grading_manually_mark = (int) $data['grading_manually_mark'];
		}

		if ( isset( $data['grading_completion_credit'] ) && $this->grading_manually_mark ) {
			$this->grading_completion_credit = $data['grading_completion_credit'];
		} else {
			$this->grading_completion_credit = static::CREDIT_ON_SUBMIT;
		}
	}

	/**
	 * Factory method
	 *
	 * @param array|string $data
	 *
	 * @return static
	 */
	public static function factory( $data ) {
		$method = '';
		if ( is_array( $data ) && ! empty( $data['grading_method'] ) ) {
			$method = $data['grading_method'];
		} elseif ( is_string( $data ) ) {
			$method = $data;
		}

		if ( ! in_array( $method, array_keys( static::$grading_methods ) ) ) {
			return new static( [] );
		}

		$grading_class = static::get_grading_class( $method );

		return new $grading_class( $data );
	}

	private static function get_grading_class( $grading_method ) {
		$grading_method = explode( '_', $grading_method );
		$grading_method = array_map( 'ucfirst', $grading_method );

		return __NAMESPACE__ . '\\' . join( '', $grading_method );
	}

	/**
	 * @param int $id assessment id
	 *
	 * @return $this
	 */
	public function set_assessment_id( $id ) {

		$id = (int) $id;

		if ( $id > 0 ) {
			$this->assessment_id = (int) $id;
		}

		return $this;
	}

	/**
	 * To be overwritten by the child classes
	 * - which might have additional data
	 *
	 * @return array
	 */
	public function get_additional_meta_keys() {
		return [];
	}

	private function get_meta_keys() {
		return array_merge( $this->meta_keys, $this->get_additional_meta_keys() );
	}

	/**
	 * @return bool
	 */
	public function save() {

		if ( empty( $this->assessment_id ) ) {
			return false;
		}

		// Add course completion meta before saving the new completion credit.
		$this->maybe_add_course_completed_meta();

		foreach ( $this->get_meta_keys() as $key ) {
			if ( isset( $this->{$key} ) ) {
				update_post_meta( $this->assessment_id, 'tva_' . $key, $this->{$key} );
			}
		}

		return true;
	}

	/**
	 * Add course completed meta to users that have completed the course and this assessment.
	 *
	 * @return void
	 */
	protected function maybe_add_course_completed_meta() {
		$assessment = new TVA_Assessment( $this->assessment_id );
		$course = $assessment->get_course_v2();

		if ( ! $course instanceof TVA_Course_V2 ) {
			return;
		}

		$course_id = $course->get_id();
		$processed_ids = array();
		$user_assessments = get_posts([
			'post_type'      => 'tva_user_assessment',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent__in' => [ $this->assessment_id ],
		]);

		foreach ( $user_assessments as $user_assessment ) {
			$user_id = $user_assessment->post_author;
			if ( in_array( $user_id, $processed_ids ) ) {
				continue;
			}

			$processed_ids[] = $user_id;
			
			$customer = new TVA_Customer( $user_id );
			if ( $customer::get_user_completed_meta( $user_id, $course_id ) ) {
				continue;
			}

			$progress = $customer->calculate_progress( $course );
			if ( (int) $progress['progress'] >= 100 ) {
				$customer::add_user_completed_meta( $user_id, $course_id );
			}
		}
	}

	public function get_grading_details() {
		$metas = [];
		if ( ! empty( $this->assessment_id ) ) {
			foreach ( $this->get_meta_keys() as $key ) {
				$metas[ $key ] = get_post_meta( $this->assessment_id, 'tva_' . $key, true );
			}
		}

		return $metas;
	}


	/**
	 * Get the grading details for the assessment
	 *
	 * @param int $assessment_id
	 *
	 * @return mixed
	 */
	public static function get_assessment_grading_details( $assessment_id ) {
		$grading_instance = static::factory( get_post_meta( $assessment_id, 'tva_grading_method', true ) );

		return $grading_instance->set_assessment_id( $assessment_id )->get_grading_details();
	}

	/**
	 * Get the grading completion credit type for the assessment.
	 * upon_submission, upon_grading, upon_passing
	 *
	 * @param int $assessment_id
	 *
	 * @return mixed
	 */
	public static function get_assessment_completion_credit( $assessment_id ) {
		// If grading_manually_mark is false, default to "upon_submission".
		return (bool) get_post_meta( $assessment_id, 'tva_grading_manually_mark', true ) ? 
			get_post_meta( $assessment_id, 'tva_grading_completion_credit', true ) :
			self::CREDIT_ON_SUBMIT;
	}

	/**
	 * Checks if the $value is passing the grading
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function passed( $value ) {
		return false;
	}

	/**
	 * Returns the grade value
	 *
	 * @param string $grade
	 *
	 * @return string
	 */
	public function get_value( $grade ) {

		if ( in_array( $grade, [ PassFail::PASSING_GRADE, PassFail::FAILING_GRADE ] ) ) {
			$name = $grade === PassFail::PASSING_GRADE ? 'assessments_pass' : 'assessments_fail';
			$grade = tcb_tva_dynamic_actions()->get_course_structure_label( $name, 'singular' );
		}

		return $grade;
	}

	/**
	 * Returns the set passing grade
	 *
	 * @return int|mixed|string
	 */
	public function get_passing_grade() {
		return $this->grading_passing_value;
	}

	/**
	 * @param $original_id
	 * @param $clone_id
	 *
	 * @return void
	 */
	public static function handle_assessment_clone( $original_id, $clone_id ) {
		$grading_instance = static::factory( get_post_meta( $original_id, 'tva_grading_method', true ) );
		$grading_instance->set_assessment_id( $original_id );
		$clone_instance = static::factory( get_post_meta( $clone_id, 'tva_grading_method', true ) );
		$clone_instance->set_assessment_id( $clone_id );

		$grading_instance->after_clone( $clone_instance );
	}

	/**
	 * Clone additional data
	 *
	 * @param $clone_instance
	 *
	 * @return void
	 */
	public function after_clone( $clone_instance ) {

	}

	/**
	 * Get credit schemes with translation.
	 *
	 * @return array
	 */
	public static function get_credit_schemes() {
		return array(
			self::CREDIT_ON_PASS   => __( 'Credit on passing grade', 'thrive-apprentice' ),
			self::CREDIT_ON_GRADE  => __( 'Credit on grading (pass or fail)', 'thrive-apprentice' ),
			self::CREDIT_ON_SUBMIT => __( 'Credit on submission', 'thrive-apprentice' ),
		);
	}
}

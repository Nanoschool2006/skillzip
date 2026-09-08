<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TVA_Course_Post
 * - post which helps edit the course certificate
 *
 * @property int        ID
 * @property string     post_title
 * @property string     post_name
 * @property string     type
 * @property string     editor_url
 * @property string     url
 * @property string     weight_data
 * @property string 	display_as
 * @property string 	display_as_custom
 * @property string 	equal_ranges
 * @property string 	category_data
 * @property string 	certificate_score
 * @property string     certificate_category
 * 
 */
final class TVA_Course_Grade implements JsonSerializable {

	use TVA_Course_Post;

	/**
	 * Post type used to store course certificate
	 */
	const POST_TYPE = 'tva_grade';

	/**
	 * Post type used to save certificate as a user template
	 */
	const USER_TEMPLATE_POST_TYPE = 'tva_user_grade_tpl';

	/**
	 * Verification page query string name
	 *
	 * Used in template redirect to redirect the user to the verification page
	 */
	const VERIFICATION_PAGE_QUERY_NAME = 'tva_r_c_v';

	/**
	 * Verification page query string value
	 *
	 * Used in template redirect to redirect the user to verification page
	 */
	const VERIFICATION_PAGE_QUERY_VAL = 'Thr!v3';

	/**
	 * @var TVA_Course_V2
	 */
	protected $_course;

	/**
	 * @var WP_Post which holds the content for grade page and
	 *              - is editable with TAr
	 */
	protected $_post;

	/**
	 * @var TVA_Course_Grade
	 */
	protected static $_instance;

	/**
	 * Holds data for default template cache
	 *
	 * @var array|null
	 */
	public static $default_template_cache;

	/**
	 * @return TVA_Course_Grade
	 */
	public static function instance() {
		if ( empty( static::$_instance ) ) {
			static::$_instance = new static();
		}

		return static::$_instance;
	}

	/**
	 * Registers filters and actions
	 */
	private function __construct() {
		add_filter( 'thrive_theme_allow_body_class', [ $this, 'theme_body_class' ], 99, 1 );

		add_filter( 'tve_dash_exclude_post_types_from_index', [ $this, 'add_post_type_to_list' ] );

		add_filter( 'thrive_theme_ignore_post_types', [ $this, 'add_post_type_to_list' ] );

		//add_action( 'init', [ $this, 'register_post_type' ] );
	}

	/**
	 * Magic get
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {

		$value = null;

		if ( $this->_post instanceof WP_Post ) {
			$value = $this->_post->$key;
		} elseif ( method_exists( $this, 'get_' . $key ) ) {
			$method_name = 'get_' . $key;
			$value       = $this->$method_name();
		}

		return $value;
	}

	/**
	 * @param TVA_Course_V2 $course
	 *
	 * @return TVA_Course_Grade
	 */
	public function set_course( $course ) {

		if ( true === $course instanceof TVA_Course_V2 ) {
			$this->_course = $course;
		}

		return $this;
	}


	/**
	 * Returns the URL of the grade post
	 *
	 * @return string
	 */
	private function get_url() {
		return get_permalink( $this->ID );
	}

	/**
	 * @return string
	 */
	public function get_editor_url() {
		return tcb_get_editor_url( $this->_post->ID, false );
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->_post->post_title;
	}

	/**
	 * Ensure there is a post for current course
	 *
	 * @return false|WP_Post
	 */
	public function ensure_post( $force = false ) {
		if ( false === $this->_course instanceof TVA_Course_V2 ) {
			return false;
		}

		/**
		 * @var $_post WP_Post
		 */
		$_post = $this->_course->has_grade();

		if ( $force && ! ( $_post instanceof WP_Post ) ) {

			$id = wp_insert_post(
				[
					'post_type'   => static::POST_TYPE,
					'post_title'  => $this->_course->name . ' grade',
					'post_name'   => $this->_course->name . '_grade',
					'post_status' => 'publish',
				]
			);

			if ( false === is_wp_error( $id ) ) {
				update_post_meta( $id, 'tcb2_ready', 1 );
				update_post_meta( $id, 'tcb_editor_enabled', 1 );

				update_post_meta( $id, 'tva_grade_type', 'weight-equally' );
				update_post_meta( $id, 'tva_weight_data', '' );
				update_post_meta( $id, 'tva_display_as', 'percentage' );
				update_post_meta( $id, 'tva_equal_ranges', '' );
				update_post_meta( $id, 'tva_category_data', '' );
				update_post_meta( $id, 'tva_certificate_score', '' );
				update_post_meta( $id, 'tva_certificate_category', '' );
				update_post_meta( $id, 'tva_display_as_custom', '' );
				update_term_meta( $this->_course->term_id, 'tva_grade', $id );
				wp_set_object_terms( $id, $this->_course->term_id, TVA_Const::COURSE_TAXONOMY );

				$_post = get_post( $id );
				update_post_meta( $_post->ID, 'tva_post_name_set', 1 );
			}

		} /**
		 * If post name doesn't end with _grade, update it
		 */
		else if ( $_post instanceof WP_Post &&
				$_post->post_type === static::POST_TYPE &&
				! get_post_meta( $_post->ID, 'tva_post_name_set' ) &&
				strpos( $_post->post_name, '_grade' ) === false ) {
			$_post->post_name = $this->_course->name . '_grade';
			wp_update_post( $_post );
			update_post_meta( $_post->ID, 'tva_post_name_set', 1 );
		}

		$this->_post = $_post;

		return $this->_post;
	}

	/**
	 * Used on localization
	 *
	 * @return array
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		$data = [
			'course_id' => $this->_course->term_id,
		];

		if ( $this->_post ) {
			$data = array_merge( [
				'title'          => $this->post_title,
				'ID'             => $this->_post->ID,
				'post_name'      => $this->post_name,
				'edit_url'       => $this->get_editor_url(),
				'preview_url'    => $this->get_url(),
				'type'           => $this->get_type(),
				'weight_data'    => $this->get_weight_data(),
				'display_as'     => $this->get_display_as(),
				'display_as_custom' => $this->get_display_as_custom(),
				'equal_ranges'   => $this->get_equal_ranges(),
				'category_data'  => $this->get_category_data(),
				'certificate_score' => $this->get_certificate_score(),
				'certificate_category' => $this->get_certificate_category(),
			], $data );
		}

		return $data;
	}

	/**
	 * @param $allow_theme_classes
	 *
	 * @return boolean
	 */
	public function theme_body_class( $allow_theme_classes ) {
		$post_type = get_post_type();

		if ( static::POST_TYPE === $post_type ) {
			$allow_theme_classes = false;
		}

		return $allow_theme_classes;
	}

	/**
	 * Adds the certificate post type to a list of post types
	 * Used in various filters that are implemented in TAR/Theme
	 *
	 * @param array $post_types
	 *
	 * @return array
	 */
	public function add_post_type_to_list( $post_types = [] ) {
		$post_types[] = static::POST_TYPE;

		return $post_types;
	}

	/**
	 * Register the certificate post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		if ( ! TVA_Const::$tva_during_activation ) {
			register_post_type(
				static::POST_TYPE,
				array(
					'labels'              => array(
						'name' => 'Course Grade',
					),
					'publicly_queryable' => true,
					'public'             => true,
					'has_archive'        => false,
					'show_ui'            => false,
					'rewrite'            => [ 'slug' => TVA_Routes::get_route( static::POST_TYPE ) ],
					'hierarchical'       => false,
					'show_in_nav_menus'  => true,
					'taxonomies'         => [ TVA_Const::COURSE_TAXONOMY ],
					'show_in_rest'       => true,
					'_edit_link'         => 'post.php?post=%d',
				)
			);
		}
	}

	/**
	 * Generates code for the current certificate instance
	 *
	 * @return null|string certificate code for the user
	 */
	public function generate_code( $user_id = null, $segments = 4, $segment_length = 4 ) {

		if ( empty( $user_id ) || ! ( $this->_course instanceof TVA_Course_V2 ) || ! ( $this->_post instanceof WP_Post ) ) {
			return null;
		}

		$site_url  = get_site_url();
		$course_id = $this->_course->term_id;
		$post_id   = $this->_post->ID;

		$raw    = strtoupper( md5( $site_url . $course_id . $user_id . $post_id . time() ) );
		$length = $segments * $segment_length;
		$code   = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$code .= $raw[ $i ];
			if ( ( $i + 1 ) % $segment_length === 0 && $i !== $length - 1 ) {
				$code .= '-';
			}
		}

		return $code;
	}

	/**
	 * Data to be saved when the grade is generated
	 *
	 * @param int $user_id
	 *
	 * @return array with prepared data to be saved in DB
	 */
	public function get_data( $user_id ) {
		return array(
			'post_id'   => $this->_post->ID,
			'course_id' => $this->_course->term_id,
			'user_id'   => $user_id,
			'url'       => home_url(),
			'timestamp' => time(),
		);
	}

	/**
	 * Duplicates the completed post on the new course
	 *
	 * @param TVA_Course_V2 $new_course
	 *
	 * @return TVA_Course_Grade
	 */
	public function duplicate( $new_course ): TVA_Course_Grade {
		$old_grade = $this->_post;
		$new_grade = $new_course->grade;
		$new_grade->ensure_post( true );
		wp_update_post(
			array(
				'ID'             => $new_grade->ID,
				'comment_status' => $old_grade->comment_status,
			)
		);

		$this->duplicate_post_meta( $old_grade, $new_grade );

		return $new_grade;
	}

	/**
	 * Returns the grade type
	 *
	 * @return string
	 */
	public function get_type() {
		return get_post_meta( $this->_post->ID, 'tva_grade_type', true );
	}

	/**
	 * Returns the grade weight data
	 *
	 * @return string
	 */
	public function get_weight_data() {
		return get_post_meta( $this->_post->ID, 'tva_weight_data', true );
	}

	/**
	 * Returns the grade display type
	 *
	 * @return string
	 */
	public function get_display_as() {
		return get_post_meta( $this->_post->ID, 'tva_display_as', true );
	}

	public function get_equal_ranges() {
		return get_post_meta( $this->_post->ID, 'tva_equal_ranges', true );
	}

	public function get_category_data() {
		return get_post_meta( $this->_post->ID, 'tva_category_data', true );
	}

	public function get_certificate_score() {
		return get_post_meta( $this->_post->ID, 'tva_certificate_score', true );
	}

	public function get_certificate_category() {
		return get_post_meta( $this->_post->ID, 'tva_certificate_category', true );
	}

	public function get_display_as_custom( ) {
		return get_post_meta( $this->_post->ID, 'tva_display_as_custom', true );
	}
}

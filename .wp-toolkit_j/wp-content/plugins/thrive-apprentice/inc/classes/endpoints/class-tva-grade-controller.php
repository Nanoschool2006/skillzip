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
 * Grade controller
 */
class TVA_Grade_Controller extends TVA_REST_Controller {
	public $base = 'grade';

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route( static::$namespace . static::$version, '/' . $this->base, [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'args' => [
						'id' => [
							'required' => true,
							'type'     => 'int',
						],
					],
				],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_type', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_type' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'type'      => [
						'required' => true,
						'type'     => 'string',
						'enum'     => [ 'weight-equally', 'weight-manually', 'weight-hardcode' ],
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_weight', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_weight' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'weight_data'      => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_display_as', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_display_as' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'display_as'      => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_display_as_custom', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_display_as_custom' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'display_as_custom'      => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_equal_ranges', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_equal_ranges' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'equal_ranges'      => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_category_data', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_category_data' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'category_data'   => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_certificate_score', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_certificate_score' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'certificate_score'   => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/(?P<id>[\d]+)/change_certificate_category', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'update_certificate_category' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'certificate_category'   => [
						'required' => true,
						'type'     => 'string',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/get_course_grade', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_course_grade' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
				],

			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/post_course_grade', [
			[

				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'post_course_grade' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
					'assessment_ids'   => [
						'required' => true,
						'type'     => 'array',
					],
				],
			],
		] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$course_id = (int) $request->get_param( 'course_id' );
		$course    = new TVA_Course_V2( $course_id );
		return new WP_REST_Response( $course->get_grade( true ) );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id      = (int) $request->get_param( 'id' );
		wp_delete_post( $id, true );

		return new WP_REST_Response( $id );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		$title          = sanitize_text_field( $request->get_param( 'title' ) );
		$post_name      = sanitize_text_field( $request->get_param( 'post_name' ) );
		$allow_comments = (int) $request->get_param( 'allow_comments' );

		wp_update_post( [
			'ID'             => $grade->ID,
			'post_title'     => $title,
			'post_name'      => $post_name,
			'comment_status' => $allow_comments === 1 ? 'open' : 'closed',
		] );
		$grade->title     = $title;
		$grade->comments  = $allow_comments;
		$grade->post_name = $post_name;


		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_type( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_grade_type', (string) $request->get_param( 'type' ) );

		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_weight( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_weight_data', (string) $request->get_param( 'weight_data' ) );

		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_display_as( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_display_as', (string) $request->get_param( 'display_as' ) );

		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_display_as_custom( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_display_as_custom', (string) $request->get_param( 'display_as_custom' ) );

		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_equal_ranges( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_equal_ranges', (bool) $request->get_param( 'equal_ranges' ) );

		return new WP_REST_Response( $grade );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_category_data( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_category_data', (string) $request->get_param( 'category_data' ) );

		return new WP_REST_Response( $grade );
	}

	public function update_certificate_score( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_certificate_score', (string) $request->get_param( 'certificate_score' ) );

		return new WP_REST_Response( $grade );
	}

	public function update_certificate_category( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		update_post_meta( $grade->ID, 'tva_certificate_category', (string) $request->get_param( 'certificate_category' ) );

		return new WP_REST_Response( $grade );
	}

	public function get_course_grade( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		$tva_grade_type = get_post_meta( $grade->ID, 'tva_grade_type', true );

		$tva_grade_weight_data = get_post_meta( $grade->ID, 'tva_weight_data', true );

		$tva_excluded = [];	
		if ( ! empty( $tva_grade_weight_data ) ) {
			$tva_grade_weight_data = json_decode( $tva_grade_weight_data, true );
			
			foreach ( $tva_grade_weight_data['assessment_ids'] as $key => $value ) {
				if ( $tva_grade_weight_data['assessment_weights'][ $key ] === '0' ) {
					array_push( $tva_excluded, $value );
				}
			}
		}

		$data = [
			'course_id' => $request->get_param( 'course_id' ),
			'grade_id'  => $grade->ID,
			'tva_grade_type' => $tva_grade_type,
			'tva_excluded' => $tva_excluded,
			'tva_grade_weight_data' => $tva_grade_weight_data,
		];

		return new WP_REST_Response( $data );
	}

	public function post_course_grade( $request ) {
		$course = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );

		$grade = $course->get_grade();

		$assessment_ids = $request->get_param( 'assessment_ids' );

		$assessment_weights = [];
		$assessment_weight_types = [];

		foreach ( $assessment_ids as $key => $value ) {
			array_push( $assessment_weights, '0' );
			array_push( $assessment_weight_types, 'automatic' );
		}

		$data = [
			'course_id' => $request->get_param( 'course_id' ),
			'assessment_ids'  => $assessment_ids,
			'assessment_weights'  => $assessment_weights,
			'assessment_weight_types' => $assessment_weight_types,
			'grade_id'  => $grade->ID,
		];

		update_post_meta( $grade->ID, 'tva_weight_data', json_encode( $data ) );

		return new WP_REST_Response( $data );
	}
}

<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

use TVA\Architect\Visual_Builder\Shortcodes as Visual_Builder_Shortcodes;

/**
 * Certificate controller
 */
class TVA_Certificate_Controller extends TVA_REST_Controller {
	public $base = 'certificate';

	/**
	 * Registers REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		parent::register_routes();

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/download', [
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'download' ],
				'permission_callback' => 'is_user_logged_in', //Only logged in users have access to download functionality
				'args'                => [
					'course_id' => [
						'required' => true,
						'type'     => 'int',
					],
				],
			],
		] );

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

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/clear-cache', [
			[
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => [ $this, 'clear_cache' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			],
		] );

		register_rest_route( static::$namespace . static::$version, '/' . $this->base . '/search', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array(
					$this,
					'search_certificate',
				),
				'permission_callback' => '__return_true',
				'args'                => array(
					'number' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			),
		) );
	}

	/**
	 * Deletes all the certificate PDF files from the /uploads
	 *
	 * @return void
	 */
	public function clear_cache() {
		\TVD_PDF_From_URL::delete_by_prefix( \TVA_Course_Certificate::FILE_NAME_PREFIX );
	}

	/**
	 * Search for a TVA Certificate and applies the shortcodes
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function search_certificate( $request ) {
		$code          = '';
		$message       = '';
		$number        = sanitize_text_field( $request->get_param( 'number' ) );
		$skin_template = \TVA\TTB\Main::skin()->get_default_template( \TVA_Const::CERTIFICATE_VALIDATION_POST );

		if ( ! ( $skin_template instanceof \TVA\TTB\Skin_Template ) ) {
			return rest_ensure_response( new WP_Error( 'missing_validation_page', esc_html__( 'Certificate validation page not set', 'thrive-apprentice' ) ) );
		}

		global $certificate;
		$certificate = tva_course_certificate()->search_by_number( $number );

		if ( empty( $certificate ) ) {
			$code    = 'no_certificate_found';
			$message = esc_html__( 'Certificate not found', 'thrive-apprentice' );
		} elseif ( ! $certificate['course']->get_wp_term() instanceof WP_Term ) {
			$code    = 'attached_course_deleted';
			$message = esc_html__( 'The certificate is not available anymore as the course was removed', 'thrive-apprentice' );
		}

		if ( ! empty( $code ) && ! empty ( $message ) ) {
			return new WP_REST_Response( array(
				'code'    => $code,
				'message' => $message,
				'data'    => null,
			), 400 );
		}

		$html = '';

		foreach ( $skin_template->meta( 'sections' ) as $section ) {
			if ( strpos( $section['content'], 'tva-certificate-verification-element' ) !== false ) {
				$html = do_shortcode( $section['content'] );
				break;
			}
		}

		$data = array(
			'certificate' => $certificate,
			'html'        => $html,
		);

		$certificate_data = [
			'certificate_number' => $number,
		];
		$user_data        = [
			'user_id'    => $certificate['recipient']->ID,
			'user_email' => $certificate['recipient']->user_email,
		];
		$course_data      = [
			'course_id'   => $certificate['course']->term_id,
			'course_name' => $certificate['course']->name,
		];

		/**
		 * This hook is triggered when a certificate has been verified
		 *
		 * @param array $certificate_data
		 * @param array $user_data
		 * @param array $course_data
		 */
		do_action( 'tva_certificate_verified', $certificate_data, $user_data, $course_data );

		return rest_ensure_response( $data, is_wp_error( $data ) ? 400 : 200 );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return void|WP_REST_Response
	 */
	public function download( $request ) {

		$course   = new TVA_Course_V2( (int) $request->get_param( 'course_id' ) );
		$user_id  = (int) $request->get_param( 'user_id' );
		$customer = tva_customer();
		
		if ( ! empty( $user_id ) ) {
			$customer = new TVA_Customer( $user_id );
		}
		$user = $customer->get_user();
		$current_user = wp_get_current_user();

 		if ( empty( $course->get_id() ) ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'Invalid parameters!', 'thrive-apprentice' ),
				),
				404
			);
		}

		if ( ! $course->has_certificate() ) {
			return new WP_REST_Response(
				array(
					'message' => __( 'No certificate found!', 'thrive-apprentice' ),
				),
				404
			);
		}

		$course_result = $this->course_result( $course );
		$min_pass_val = $this->get_min_pass_val( $course );

		if ( ! in_array('administrator', $user->roles) 
			&& ! in_array('administrator', $current_user->roles) 
			&& $course->get_grade() && (float)$course_result < (float)$min_pass_val ) {
			$grade_failed_msg = __( 'Current course result is not enough to generate certificate.', 'thrive-apprentice' );
			return new WP_REST_Response(
				array(
					'message' => $grade_failed_msg,
				),
				404
			);
		}

		$certificate = $course->get_certificate();
		$response    = $certificate->download( $customer );

		if ( ! empty( $response['error'] ) ) {
			return new WP_REST_Response( $response, 404 );
		}

		$certificate_data = [
			'certificate_number' => $certificate->number,
		];
		$user_data        = [
			'user_id'    => $customer->get_id(),
			'user_email' => $customer->get_user()->user_email,
		];
		$course_data      = [
			'course_id'   => $course->get_id(),
			'course_name' => $course->name,
		];

		/**
		 * This hook is triggered when a certificate is downloaded by a user
		 *
		 * @param array $certificate_data
		 * @param array $user_data
		 * @param array $course_data
		 */
		$is_admin_action = (bool) $request->get_param( 'tva_admin_download' );
		if ( ! $is_admin_action ) {
			do_action( 'tva_certificate_downloaded', $certificate_data, $user_data, $course_data );
		}

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . filesize( $response['file'] ) );
		if ( ob_get_contents() ) {
			ob_end_clean();
		}

		readfile( $response['file'] );
		exit();
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		$course_id = (int) $request->get_param( 'course_id' );
		$course    = new TVA_Course_V2( $course_id );

		if ( ! TD_TTW_Connection::get_instance()->is_connected() ) {
			/**
			 * If TPM is not connected. disable certificate functionality
			 */
			return new WP_REST_Response( [ 'tpm_not_connected' => 1 ] );
		}

		return new WP_REST_Response( $course->get_certificate()->set_status_publish() );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		$id      = (int) $request->get_param( 'id' );
		$success = wp_update_post( [
			'ID'          => $id,
			'post_status' => 'draft',
		] );

		return new WP_REST_Response( $success );
	}

	public function course_grade( $course ): string {
		return static::get_course_grade( 'grade', $course  );
	}

	/**
	 * Displays the result of a course in a certificate context.
	 *
	 * @return string The result of the course or an error message if the context is invalid.
	 */
	public function course_result( $course ): string {
		return static::get_course_grade( 'result', $course  );
	}

	/**
	 * Retrieves the grade for a course based on specified variant.
	 *
	 * @param string $variant The display variant for the grade (e.g., 'result', 'grade').
	 *
	 * @return string The calculated course grade or an error message if the context is invalid.
	 */
	private static function get_course_grade( string $variant, $course ): string {
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

	/**
	 * Retrieves the minimum passing value for a course.
	 *
	 * This function checks the grade of the provided course and determines the
	 * minimum passing value based on the grading display type. If the display
	 * type is set to 'category', it retrieves the corresponding category range.
	 * Otherwise, it returns the certificate score.
	 *
	 * @param mixed $course The course object to retrieve the grade from.
	 *
	 * @return string The minimum passing value for the course, or "0" if the course or grade is invalid.
	 */
	public function get_min_pass_val( $course ): string {

		if ( empty( $course ) ) {
			return "0";
		}

		$grade = $course->get_grade();

		if ( empty( $grade ) ) {
			return "0";
		}

		$display_as = get_post_meta( $grade->ID, 'tva_display_as', true );

		if ( empty( $display_as ) || $display_as === '' ) {
			return "0";
		}

		if ( $display_as === 'category' ) {
			$category_name = get_post_meta( $grade->ID, 'tva_certificate_category', true );
			$category_data = get_post_meta( $grade->ID, 'tva_category_data', true );
			if ( !empty( $category_data ) ) {
				$category_data = json_decode( $category_data, true );
				foreach ( $category_data['category_labels'] as $key => $value ) {
					if ( $value === $category_name ) {
						return $category_data['cateory_ranges_from'][$key];
					}
				}
			}
		} else {
			return get_post_meta( $grade->ID, 'tva_certificate_score', true );
		}

		return "0";
	}
}

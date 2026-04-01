<?php

use TVA\Assessments\TVA_User_Assessment;
use TVA\Product;

/**
 * Class TVA_Email_Templates
 * - localizes required data
 * - saves an template item into DB
 * - handles the email templates sent to users
 */
class TVA_Email_Templates {

	const NEW_ACCOUNT_TEMPLATE_SLUG        = 'newAccount';
	const CERTIFICATE_ISSUED_TEMPLATE_SLUG = 'certificateIssued';
	const ASSESSMENT_MARKED_TEMPLATE_SLUG  = 'assessmentMarked';
	const PRODUCT_ACCESS_EXPIRE            = 'productAccessExpire';
	const NEW_COURSE_WELCOME_TEMPLATE_SLUG = 'newCourseWelcome';
	const CONTENT_TYPE                     = 'text/html';
	const TRIGGERS                         = array(
		'thrivecart',
		'sendowl',
		'wordpress',
		'certificate_issued',
		'assessment_passed',
		'assessment_failed',
		'new_course_welcome',
	);
	/**
	 * @var self
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected $_templates = array();

	/**
	 * @var WP_User newly created user or student the certificate is sent to; kept on this instance to be available for rendering the shortcodes
	 */
	protected $_user;

	/**
	 * @var Product
	 */
	protected $_product;

	/**
	 * @var string course name for the certificate template; kept on this instance to be available for rendering the shortcodes
	 */
	protected $_course;

	/**
	 * @var string certificate download link for the certificate template; kept on this instance to be available for rendering the shortcodes
	 */
	protected $_certificate_download;

	/**
	 * @var string assessment for the assessment template; kept on this instance to be available for rendering the shortcodes
	 */
	protected $_user_assessment;

	/**
	 * TVA_Email_Templates constructor.
	 */
	private function __construct() {

		$this->_templates = $this->_get_option();
		/*
		 * Backwards compatibility: [user_pass] shortcode should always have a `if_user_provided` parameter
		 */
		if ( ! empty( $this->_templates['newAccount'] ) && ! empty( $this->_templates['newAccount']['body'] ) ) {
			$this->_templates['newAccount']['body'] = str_replace( '[user_pass]', '[user_pass if_user_provided="The password you chose during registration"]', $this->_templates['newAccount']['body'] );
		}

		$this->_init();
	}

	/**
	 * @return array
	 */
	protected function _get_option() {
		return get_option( 'tva_email_templates', array() );
	}

	/**
	 * @return bool
	 */
	protected function _save_option() {
		return update_option( 'tva_email_templates', $this->_templates );
	}

	/**
	 * Handles wp hooks
	 */
	protected function _init() {
		add_filter( 'tva_admin_localize', array( $this, 'get_connected_email_apis' ) );
		add_filter( 'tva_admin_localize', array( $this, 'get_admin_data_localization' ) );
		
		// Automator email coordination
		add_action( 'user_register', array( $this, 'track_automator_user_creation' ), 10, 1 );
		add_action( 'tva_user_receives_product_access', array( $this, 'send_automator_welcome_email' ), 10, 2 );
		add_filter( 'tva_admin_localize', array( $this, 'get_shortcodes' ) );
		add_filter( 'tva_admin_localize', array( $this, 'get_triggers' ) );
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_action( 'tva_prepare_new_user_email_template', array( $this, 'prepare_new_user_email_template' ) );
		add_action( 'tva_prepare_certificate_email_template', array( $this, 'prepare_certificate_email_template' ) );
		add_action( 'tva_prepare_square_email_template', array( $this, 'prepare_square_email_template' ) );
		add_action( 'tva_prepare_product_expiry_email_template', array( $this, 'prepare_product_expiry_email_template' ) );
		add_action( 'tva_prepare_assessment_marked_email_template', array( $this, 'prepare_assessment_email_template' ) );
		add_action( 'tvd_after_create_wordpress_account', array( $this, 'after_create_wordpress_account' ), 10, 2 );
		add_filter( 'pre_wp_mail', array( $this, 'maybe_send_mail_via_api' ), 10, 2 );

		// New Course Welcome Email
		add_action( 'tva_user_receives_product_access', array( $this, 'send_course_welcome_email' ), 20, 2 );
		add_action( 'tva_course_published', array( $this, 'send_welcome_emails_for_published_course' ), 10, 1 );
		add_action( 'tva_prepare_new_course_welcome_email_template', array( $this, 'prepare_new_course_welcome_email_template' ) );

		// Batch processing cron hook for course welcome emails
		add_action( 'tva_process_course_welcome_batch', array( $this, 'process_course_welcome_batch' ) );

		add_shortcode( 'first_name', function () {

			if ( $this->_user ) {
				$first_name = $this->_user->first_name;

				if ( empty( $first_name ) ) {

					if ( ! empty( $_POST['name'] ) ) {
						/**
						 * This is a bit ugly
						 * Creating a user can be from apprentice or from a lead generation with WP connection form (from TAR)
						 *
						 * When created via lead generation form from TAR (with function register_new_user) the first name of the user is updated after the user has been created with function wp_update_user()
						 * The send user email trigger is fired on user creation.
						 */
						$first_name = sanitize_text_field( $_POST['name'] );
					} else {
						$first_name = $this->_user->user_email;
					}
				}

				return $first_name;
			}

			return null;
		} );

		add_shortcode( 'user_name', function () {
			return $this->_user ? $this->_user->user_login : '';
		} );

		add_shortcode( 'user_pass', function ( $attributes ) {
			/* if the password has been generated, include it in the email message */
			if ( ! empty( $GLOBALS['tva_user_pass_generated'] ) ) {
				// generate link for password reset
				return $this->generate_password_set_link_for_user( $this->_user );
			}

			/* if not, the password must have been chosen by the user, return the `if_user_provided` message */
			if ( empty( $attributes['if_user_provided'] ) ) {
				$attributes['if_user_provided'] = 'The password you chose during registration';
			}

			return $attributes['if_user_provided'];
		} );

		add_shortcode( 'login_button', function ( $attributes, $content ) {
			return '<a target="_blank" href="' . $this->_get_login_url() . '" style="color: #ffffff; border-radius: 4px; background-color: #236085; display: inline-block; padding: 5px 40px;">' . $content . '</a>';
		} );

		add_shortcode( 'set_password', function ( $attributes, $content ) {
			return $this->_user ? $this->generate_password_set_link_for_user_v2( $this->_user, $content ) : '';
		} );

		add_shortcode( 'login_link', function ( $attributes, $content ) {
			return '<a target="_blank" href="' . $this->_get_login_url() . '">' . $content . '</a>';
		} );

		add_shortcode( 'site_name', function () {
			return get_bloginfo( 'name' );
		} );

		add_shortcode(
			'course_name',
			function () {
				// First, check if course is already set on the instance
				// If $_course is not empty, return its value.
				// The condition prioritizes checking if $_course is a string; if true, it returns the string directly.
				// If $_course is an object, read the public/name property (handled via __get on TVA_Course_V2) to avoid calling a missing method.
				if ( ! empty( $this->_course ) ) {
					return is_string( $this->_course ) ? $this->_course : ( $this->_course->name ?? '' );
				}

				// Second, check if we have a user assessment with course ID
				if ( $this->_user_assessment instanceof TVA_User_Assessment ) {
					return $this->_user_assessment->get_course_name();
				}

				// Third, check if we're in the middle of processing an enrollment and course_id is in POST data
				if ( ! empty( $_POST['course_ids'] ) && is_array( $_POST['course_ids'] ) ) {
					$course_ids = array_map( 'intval', $_POST['course_ids'] );
					if ( ! empty( $course_ids[0] ) ) {
						$course = TVA_Course_V2::get_instance( $course_ids[0] );
						if ( $course ) {
							return $course->name;
						}
					}
				}

				// Fourth, check if we have a course ID directly in POST
				if ( ! empty( $_POST['course_id'] ) ) {
					$course = TVA_Course_V2::get_instance( (int) $_POST['course_id'] );
					if ( $course ) {
						return $course->name;
					}
				}

				// Fifth, if we have a user, try to find their most recent course access
				if ( isset( $this->_user ) && $this->_user instanceof WP_User ) {
					$user_id = $this->_user->ID;

					// Create TVA_User instance to get orders
					$tva_user = new TVA_User( $user_id );
					$orders   = $tva_user->get_orders();

					if ( ! empty( $orders ) ) {
						foreach ( $orders as $order ) {
							$order_items = $order->get_order_items();
							if ( ! empty( $order_items ) && isset( $order_items[0] ) ) {
								$product_id = $order_items[0]->get_product_id();

								// Get products based on order type (Stripe/Square use different identifiers)
								if ( $order->is_stripe() ) {
									$products = TVA_Stripe_Integration::get_all_products_for_identifier( $product_id );
								} elseif ( $order->is_square() ) {
									$products = TVA_Square_Integration::get_all_products_for_identifier( $product_id );
								} else {
									$product  = new \TVA\Product( (int) $product_id );
									$products = $product->get_id() ? [ $product ] : [];
								}

								foreach ( $products as $product ) {
									$name = $product->get_name();
									if ( ! empty( $name ) ) {
										return $name;
									}
								}
							}
						}
					}
				}

				// If all else fails, return an empty string
				return '';
			}
		);

		add_shortcode(
			'product',
			function ( $atts = array() ) {
				// Just call the expiring_product shortcode handler
				return do_shortcode( '[expiring_product]' );
			}
		);

		add_shortcode(
			'expiring_product',
			function () {
				// First, check if product is already set on the instance
				if ( ! empty( $this->_product ) && $this->_product instanceof Product ) {
					return $this->_product->get_name();
				}

				// Check if we have a product name in globals (set during email preparation)
				if ( ! empty( $GLOBALS['tva_current_product_name'] ) ) {
					return $GLOBALS['tva_current_product_name'];
				}

				// Check if product_id is in POST data
				if ( ! empty( $_POST['product_id'] ) ) {
					$product_id = (int) wp_unslash( $_POST['product_id'] );
					$product = new \TVA\Product( $product_id );
					if ( $product instanceof \TVA\Product ) {
						$name = $product->get_name();
						if ( ! empty( $name ) ) {
							return $name;
						}
					}
				}

				// If we have a user, try to find their most recent product
				if ( isset( $this->_user ) && $this->_user instanceof WP_User ) {
					$user_id = $this->_user->ID;
					$tva_user = new TVA_User( $user_id );
					$orders = $tva_user->get_orders();

					if ( ! empty( $orders ) ) {
						foreach ( $orders as $order ) {
							$order_items = $order->get_order_items();
							if ( ! empty( $order_items ) && isset( $order_items[0] ) ) {
								$product_id = $order_items[0]->get_product_id();
								$product = new \TVA\Product( $product_id );
								if ( $product instanceof \TVA\Product ) {
									$name = $product->get_name();
									if ( ! empty( $name ) ) {
										return $name;
									}
								}
							}
						}
					}
				}

				return '';
			}
		);

		add_shortcode(
			'course_url',
			function () {
				return $this->get_course_url_for_shortcode();
			}
		);

		add_shortcode( 'download_certificate_button', function ( $attributes, $content ) {
			return '<a target="_blank" href="' . $this->_certificate_download . '" style="color: #ffffff; border-radius: 4px; background-color: #236085; display: inline-block; padding: 5px 40px;">' . $content . '</a>';
		} );

		add_shortcode( 'assessment_button', function ( $attributes, $content ) {
			$url = $this->_get_login_url();
			if ( $this->_user_assessment instanceof TVA_User_Assessment ) {
				$assessment = new TVA_Assessment( $this->_user_assessment->post_parent );
				$url        = $assessment->get_url();
			}

			return '<a target="_blank" href="' . $url . '" style="color: #ffffff; border-radius: 4px; background-color: #236085; display: inline-block; padding: 5px 40px;">' . $content . '</a>';
		} );

		add_shortcode( 'assessment_type', function () {
			if ( $this->_user_assessment instanceof TVA_User_Assessment ) {
				$assessment = new TVA_Assessment( $this->_user_assessment->post_parent );

				return TVA_Assessment::$types[ $assessment->get_type() ];
			}
		} );

		add_shortcode( 'assessment_status', function () {
			if ( $this->_user_assessment instanceof TVA_User_Assessment ) {
				$assessment_status = '';
				$status            = $this->_user_assessment->status;
				if ( $status === TVA_Const::ASSESSMENT_STATUS_COMPLETED_PASSED ) {
					$assessment_status = TVA_Const::ASSESSMENTS_PASSED_TEXT;
				} else {
					$assessment_status = TVA_Const::ASSESSMENTS_FAILED_TEXT;
				}

				return $assessment_status;
			}
		} );

		add_filter( 'tcb_api_subscribe_data_instance', array( $this, 'trigger_wp_new_registration' ), 10, 2 );

		/**
		 * When sending emails from automator or from the default register page via WordPress,
		 * the email templates needs to be changed in case the the backend option is on
		 *
		 * 0 priority is set to hook before the WP hook
		 */
		add_action( 'register_new_user', static function () {
			$email_template = tva_email_templates()->check_templates_for_trigger( 'wordpress' );

			if ( false !== $email_template ) {
				tva_email_templates()->trigger_process( $email_template );
			}
		}, 0 );
	}

	/**
	 * Checks if there is set a Login Page and returns its URL
	 * - otherwise returns wp login url
	 *
	 * @return string
	 */
	protected function _get_login_url() {

		$login_url  = wp_login_url();
		$login_page = tva_get_settings_manager()->get_setting( 'login_page' );

		if ( $login_page ) {
			$login_url = get_permalink( $login_page );
		}

		return $login_url;
	}

	/**
	 * Hooks into `wp_new_user_notification_email` with specified template
	 *
	 * @param array $email_template
	 */
	public function prepare_new_user_email_template( $email_template ) {

		add_filter( 'wp_mail_content_type', function () {
			return self::CONTENT_TYPE;
		} );

		add_filter( 'wp_mail_from_name', static function ( $from_name ) use ( $email_template ) {

			if ( ! empty( $email_template['from_name'] ) ) {
				$from_name = $email_template['from_name'];
			}

			return $from_name;
		}, PHP_INT_MAX );

		add_filter( 'wp_new_user_notification_email', function ( $email_data, $user ) use ( $email_template ) {
			/** @var WP_User $user */
			$this->_user = $user;

			if ( empty( $email_template['user_pass'] ) ) {
				$GLOBALS['tva_user_pass_generated'] = true;
				$new_pass                           = wp_generate_password( 12, false );
				wp_set_password( $new_pass, $user->ID );
			} else {
				$new_pass = $email_template['user_pass'];
			}

			$this->_user->user_pass = $new_pass; //used on generating email body

			$email_data['subject'] = do_shortcode( $email_template['subject'] );
			$email_data['message'] = do_shortcode( nl2br( $email_template['body'] ) );

			return $email_data;
		}, 10, 3 );
	}

	/**
	 * @param array $email_template
	 *
	 * @return void
	 */
	public function prepare_product_expiry_email_template( $email_template ) {
		add_filter( 'wp_mail_content_type', function () {
			return self::CONTENT_TYPE;
		} );

		add_filter( 'wp_mail_from_name', static function ( $from_name ) use ( $email_template ) {

			if ( ! empty( $email_template['from_name'] ) ) {
				$from_name = $email_template['from_name'];
			}

			return $from_name;
		} );

		$this->_user    = $email_template['user'];
		$this->_product = $email_template['product'];
	}

	public function prepare_certificate_email_template( $email_template ) {
		add_filter( 'wp_mail_content_type', function () {
			return self::CONTENT_TYPE;
		} );

		add_filter( 'wp_mail_from_name', static function ( $from_name ) use ( $email_template ) {

			if ( ! empty( $email_template['from_name'] ) ) {
				$from_name = $email_template['from_name'];
			}

			return $from_name;
		} );

		$this->_user                 = $email_template['user'];
		$this->_course               = $email_template['course_name'];
		$this->_certificate_download = $email_template['certificate_download'];
	}

	public function prepare_square_email_template( $email_template ) {
		$this->_user = $email_template['user'];
		$GLOBALS['tva_user_pass_generated'] = true;
	}

	public function generate_password_set_link_for_user( $user ) {	
		if ( ! empty( get_transient( 'generate_password_set_link_for_user_transition' . $user->ID ) ) ) {
			$reset_link = get_transient( 'generate_password_set_link_for_user_transition' . $user->ID );
			return esc_html__( 'Reset your password: ', 'thrive-apprentice' ) . '<a href="' . $reset_link . '" target="_blank">' . $reset_link . '</a>';
		}

		if ( ! $user ) {
			error_log( 'generate_password_set_link_for_user: User not found with ID ' . $user->ID );
			return new WP_Error( 'user_not_found', 'User not found.' );
		}		
	
		// Generate the reset key. This also stores it in user meta with an expiration.
		$reset_key = get_password_reset_key( $user );
	
		if ( is_wp_error( $reset_key ) ) {
			error_log( 'generate_password_set_link_for_user: Error generating reset key for user ' . $user->ID . ': ' . $reset_key->get_error_message() );
			return $reset_key; // Return the WP_Error object
		}
	
		// Return the reset URL	
		$reset_link = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $user->user_login ), 'login' );
		set_transient( 'generate_password_set_link_for_user_transition' . $user->ID, $reset_link, 10 );
		return esc_html__( 'Reset your password: ', 'thrive-apprentice' ) . '<a href="' . $reset_link . '" target="_blank">' . $reset_link . '</a>';
	}

	public function generate_password_set_link_for_user_v2( $user, $content ) {	
		$sc_text = ! empty( $content ) ? $content : esc_html__( 'Set your password', 'thrive-apprentice' );
		if ( ! empty( get_transient( 'generate_password_set_link_for_user_transition' . $user->ID ) ) ) {
			$reset_link = get_transient( 'generate_password_set_link_for_user_transition' . $user->ID );
			return  '<a href="' . $reset_link . '" target="_blank">' . $sc_text . '</a>';
		}

		if ( ! $user ) {
			error_log( 'generate_password_set_link_for_user: User not found with ID ' . $user->ID );
			return new WP_Error( 'user_not_found', 'User not found.' );
		}		
	
		// Generate the reset key. This also stores it in user meta with an expiration.
		$reset_key = get_password_reset_key( $user );
	
		if ( is_wp_error( $reset_key ) ) {
			error_log( 'generate_password_set_link_for_user: Error generating reset key for user ' . $user->ID . ': ' . $reset_key->get_error_message() );
			return $reset_key; // Return the WP_Error object
		}
	
		// Return the reset URL	
		$reset_link = network_site_url( "wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode( $user->user_login ), 'login' );
		set_transient( 'generate_password_set_link_for_user_transition' . $user->ID, $reset_link, 10 );
		return '<a href="' . $reset_link . '" target="_blank">' .  $sc_text . '</a>';
	}

	public function prepare_assessment_email_template( $email_template ) {
		add_filter( 'wp_mail_content_type', function () {
			return self::CONTENT_TYPE;
		} );

		add_filter( 'wp_mail_from_name', static function ( $from_name ) use ( $email_template ) {

			if ( ! empty( $email_template['from_name'] ) ) {
				$from_name = $email_template['from_name'];
			}

			return $from_name;
		} );

		$this->_user            = $email_template['user'];
		$this->_user_assessment = $email_template['user_assessment'];
	}

	/**
	 * Registers required rest API endpoints
	 */
	public function rest_api_init() {
		register_rest_route( 'tva/v1', '/emailTemplate', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'save_template' ),
			'permission_callback' => array( $this, 'permissions_check' ),
		) );
	}


	/**
	 * Checks if there are templates saved for a specified trigger slug
	 *
	 * @param string $trigger_slug
	 *
	 * @return bool|array of template
	 */
	public function check_templates_for_trigger( $trigger_slug ) {

		foreach ( $this->_templates as $template ) {
			if ( ! empty( $template['triggers'] ) && in_array( $trigger_slug, $template['triggers'] ) ) {
				return $template;
			}
		}

		return false;
	}

	/**
	 * @param $template_slug
	 *
	 * @return array
	 */
	public function get_template_details_by_slug( $template_slug ) {
		return [
			'subject'   => $this->_get_template_subject( $template_slug ),
			'from_name' => $this->_get_template_from_name( $template_slug ),
			'body'      => $this->_get_template_body( $template_slug ),
			'triggers'  => $this->_get_template_triggers( $template_slug ),
		];
	}

	/**
	 * Loops through all triggers and if there is a template set for it then return the template
	 * otherwise return false
	 *
	 * @return array|bool
	 */
	public function check_template_for_any_trigger() {

		foreach ( self::TRIGGERS as $trigger ) {
			$template = $this->check_templates_for_trigger( $trigger );
			if ( false !== $template ) {
				return $template;
			}
		}

		return false;
	}

	/**
	 * Callback for saving a template API endpoint
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return true
	 */
	public function save_template( $request ) {

		$this->_templates[ $request->get_param( 'slug' ) ] = array(
			'subject'   => $request->get_param( 'subject' ),
			'from_name' => (string) $request->get_param( 'from_name' ),
			'body'      => $request->get_param( 'body' ),
			'triggers'  => $request->get_param( 'triggers' ),
		);

		$this->_save_option();

		return true;
	}


	/**
	 * Check if a given request has access to the product
	 *
	 * @return WP_Error|bool
	 */
	public function permissions_check() {
		return TVA_Product::has_access();
	}

	/**
	 * Localizes triggers
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function get_triggers( $data ) {

		$data['emailTriggers'] = array();

		$data['emailTriggers']['stripe'] = array(
			'slug'        => 'stripe',
			'description' => esc_html__( 'Stripe - new account created after purchase', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['square'] = array(
			'slug'        => 'square',
			'description' => esc_html__( 'Square - new account created after purchase', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['sendowl'] = array(
			'slug'        => 'sendowl',
			'description' => esc_html__( 'SendOwl - new account created on registration page (during purchase flow)', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['thrivecart'] = array(
			'slug'        => 'thrivecart',
			'description' => esc_html__( 'ThriveCart - new account created after purchase', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['wordpress'] = array(
			'slug'        => 'wordpress',
			'description' => esc_html__( 'When a user registers to create a new free account', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['certificate_issued'] = array(
			'slug' => 'certificate_issued',
		);

		$data['emailTriggers']['assessment_passed'] = array(
			'slug'        => 'assessment_passed',
			'description' => esc_html__( 'Assessment Passed', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['assessment_failed'] = array(
			'slug'        => 'assessment_failed',
			'description' => esc_html__( 'Assessment Failed', 'thrive-apprentice' ),
		);

		$data['emailTriggers']['new_course_welcome'] = array(
			'slug'        => 'new_course_welcome',
			'description' => esc_html__( 'Course Welcome - when a user gains access to a course', 'thrive-apprentice' ),
		);

		return $data;
	}

	/**
	 * Localizes shortcodes
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function get_shortcodes( $data ) {

		$data['emailShortcodes'] = array();

		$data['emailShortcodes']['firstName'] = array(
			'slug'  => 'firstName',
			'label' => esc_html__( 'First name' ),
			'text'  => '[first_name]',
		);

		$data['emailShortcodes']['username'] = array(
			'slug'  => 'username',
			'label' => esc_html__( 'Username' ),
			'text'  => '[user_name]',
		);

		$data['emailShortcodes']['password'] = array(
			'slug'  => 'password',
			'label' => esc_html__( 'Password' ),
			'text'  => '[user_pass if_user_provided="The password you chose during registration"]',
		);

		$data['emailShortcodes']['loginButton'] = array(
			'slug'  => 'loginButton',
			'label' => esc_html__( 'Login button' ),
			'text'  => '[login_button]' . esc_html__( 'Log into your account', 'thrive-apprentice' ) . '[/login_button]',
		);

		$data['emailShortcodes']['setPassword'] = array(
			'slug'  => 'setPassword',
			'label' => esc_html__( 'Set password' ),
			'text'  => '[set_password]' . esc_html__( 'Set your password', 'thrive-apprentice' ) . '[/set_password]',
		);

		$data['emailShortcodes']['loginLink'] = array(
			'slug'  => 'loginLink',
			'label' => esc_html__( 'Login link' ),
			'text'  => '[login_link]' . esc_html__( 'Log into your account', 'thrive-apprentice' ) . '[/login_link]',
		);

		$data['emailShortcodes']['siteName'] = array(
			'slug'  => 'siteName',
			'label' => esc_html__( 'Site name' ),
			'text'  => '[site_name]',
		);

		$data['emailShortcodes']['assessmentButton'] = array(
			'slug'  => 'assessmentButton',
			'label' => esc_html__( 'Assessment button' ),
			'text'  => '[assessment_button]' . esc_html__( 'Access your assessment', 'thrive-apprentice' ) . '[/assessment_button]',

		);

		$data['emailShortcodes']['assessmentType'] = array(
			'slug'  => 'assessmentType',
			'label' => esc_html__( 'Assessment Type' ),
			'text'  => '[assessment_type]',
		);

		$data['emailShortcodes']['courseName'] = array(
			'slug'  => 'courseName',
			'label' => esc_html__( 'Course Name' ),
			'text'  => '[course_name]',
		);

		$data['emailShortcodes']['assessmentStatus'] = array(
			'slug'  => 'assessmentStatus',
			'label' => esc_html__( 'Assessment Status' ),
			'text'  => '[assessment_status]',
		);

		$data['emailShortcodes']['product'] = array(
			'slug'  => 'product',
			'label' => esc_html__( 'Product', 'thrive-apprentice' ),
			'text'  => '[expiring_product]',
		);

		$data['emailShortcodes']['courseUrl'] = array(
			'slug'  => 'courseUrl',
			'label' => esc_html__( 'Course URL', 'thrive-apprentice' ),
			'text'  => '[course_url]',
		);

		return $data;
	}

	/**
	 * Gets a template's body by template's name
	 * - from DB if exists or from file as default
	 *
	 * @param string $tpl_slug
	 *
	 * @return string
	 */
	private function _get_template_body( $tpl_slug ) {

		/**
		 * default body
		 */
		ob_start();
		include TVA_Const::plugin_path( '/admin/views/template/emailTemplates/bodies/' ) . $tpl_slug . '.phtml';
		$body = ob_get_contents();
		ob_end_clean();

		/**
		 * DB saved body
		 */
		if ( ! empty( $this->_templates[ $tpl_slug ]['body'] ) ) {
			$body = $this->_templates[ $tpl_slug ]['body'];
		}

		return $body;
	}

	/**
	 * Based on template's name returns a string as email subject
	 *
	 * @param string $tpl_slug
	 *
	 * @return string
	 */
	private function _get_template_subject( $tpl_slug ) {
		switch ( $tpl_slug ) {
			case self::CERTIFICATE_ISSUED_TEMPLATE_SLUG:
				$subject = 'Download your course certificate here!';
				break;
			case self::ASSESSMENT_MARKED_TEMPLATE_SLUG:
				$subject = 'Your assessment has been marked!';
				break;
			case self::NEW_ACCOUNT_TEMPLATE_SLUG:
				$subject = 'Your account has been created';
				break;
			case static::PRODUCT_ACCESS_EXPIRE:
				$subject = 'Your access is about to expire';
				break;
			case self::NEW_COURSE_WELCOME_TEMPLATE_SLUG:
				$subject = esc_html__( 'Welcome to [course_name]! Get Started Now', 'thrive-apprentice' );
				break;
			default:
				$subject = 'No Template Selected';
				break;
		}

		if ( ! empty( $this->_templates[ $tpl_slug ]['subject'] ) ) {
			$subject = $this->_templates[ $tpl_slug ]['subject'];
		}

		return $subject;
	}

	private function _get_template_from_name( $tpl_slug ) {
		$from_name = get_bloginfo( 'name' );

		if ( ! empty( $this->_templates[ $tpl_slug ]['from_name'] ) ) {
			$from_name = $this->_templates[ $tpl_slug ]['from_name'];
		}

		return $from_name;
	}

	/**
	 * Gets a list of trigger slugs for which a template is activated
	 *
	 * @param string $tpl_slug
	 *
	 * @return array
	 */
	private function _get_template_triggers( $tpl_slug ) {

		/**
		 * by default thrivecart, square and stripe trigger has to be selected for new account template
		 */
		if ( $tpl_slug === self::NEW_ACCOUNT_TEMPLATE_SLUG && empty( $this->_templates[ $tpl_slug ]['triggers'] ) ) {
			$this->_templates[ $tpl_slug ]['triggers'] = array(
				'thrivecart',
				'square',
				'stripe',
				'wordpress',
			);
		} elseif ( $tpl_slug === self::CERTIFICATE_ISSUED_TEMPLATE_SLUG ) {
			$this->_templates[ $tpl_slug ]['triggers'] = array(
				'certificate_issued',
			);
		} elseif ( $tpl_slug === static::PRODUCT_ACCESS_EXPIRE ) {
			$this->_templates[ $tpl_slug ]['triggers'] = [
				'product_access_expire',
			];
		} elseif ( $tpl_slug === self::ASSESSMENT_MARKED_TEMPLATE_SLUG && empty( $this->_templates[ $tpl_slug ]['triggers'] ) ) {
			$this->_templates[ $tpl_slug ]['triggers'] = array(
				'assessment_passed',
				'assessment_failed',
			);
		} elseif ( $tpl_slug === self::NEW_COURSE_WELCOME_TEMPLATE_SLUG && empty( $this->_templates[ $tpl_slug ]['triggers'] ) ) {
			$this->_templates[ $tpl_slug ]['triggers'] = array(
				'new_course_welcome',
			);
		}

		return $this->_templates[ $tpl_slug ]['triggers'];
	}

	/**
	 * Localization of available email services
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function get_connected_email_apis( $data ) {

		$email_connection_instance = Thrive_Dash_List_Manager::connection_instance( 'email' );

		if ( method_exists( $email_connection_instance, 'get_connected_email_providers' ) ) {
			$data['connected_email_providers'] = $email_connection_instance->get_connected_email_providers();
		}

		return $data;
	}

	/**
	 * Checks if the system should send emails through Emails APIs.
	 *
	 * The system will send emails through APIs if the option is selected in the database and the email contains no attachments.
	 * If the API doesn't return a successful response, it sends emails through WordPress
	 *
	 * @param null|mixed $return
	 * @param array      $attrs
	 *
	 * @return mixed
	 */
	public function maybe_send_mail_via_api( $return, $attrs = array() ) {
		$email_service = tva_get_setting( 'email_service' );

		$send_via_api = false;
		$from_name    = get_bloginfo( 'name' );
		if ( did_action( 'tva_prepare_new_user_email_template' ) > 0 ) {
			$send_via_api = true;
			if ( ! empty( $this->_templates['newAccount'] ) && ! empty( $this->_templates['newAccount']['from_name'] ) ) {
				$from_name = $this->_templates['newAccount']['from_name'];
			}
		} elseif ( did_action( 'tva_prepare_certificate_email_template' ) > 0 ) {
			$send_via_api = true;
			if ( ! empty( $this->_templates['certificateIssued'] ) && ! empty( $this->_templates['certificateIssued']['from_name'] ) ) {
				$from_name = $this->_templates['certificateIssued']['from_name'];
			}
		} elseif ( did_action( 'tva_prepare_assessment_marked_email_template' ) > 0 ) {
			$send_via_api = true;
			if ( ! empty( $this->_templates[ self::ASSESSMENT_MARKED_TEMPLATE_SLUG ] ) && ! empty( $this->_templates[ self::ASSESSMENT_MARKED_TEMPLATE_SLUG ]['from_name'] ) ) {
				$from_name = $this->_templates[ self::ASSESSMENT_MARKED_TEMPLATE_SLUG ]['from_name'];
			}
		} elseif ( did_action( 'tva_prepare_new_course_welcome_email_template' ) > 0 ) {
			$send_via_api = true;
			if ( ! empty( $this->_templates[ self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ] ) && ! empty( $this->_templates[ self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ]['from_name'] ) ) {
				$from_name = $this->_templates[ self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ]['from_name'];
			}
		}

		if ( $send_via_api && empty( $attrs['attachments'] ) && ! empty( $email_service ) && $email_service !== 'own_site' ) {
			$api  = Thrive_List_Manager::connection_instance( $email_service );
			$data = array(
				'html_content' => $attrs['message'],
				'text_content' => strip_tags( $attrs['message'] ),
				'subject'      => $attrs['subject'],
				'from_name'    => $from_name,
				'from_email'   => get_option( 'admin_email' ),
				'bcc'          => '',
				'cc'           => '',
				'emails'       => is_string( $attrs['to'] ) ? array( $attrs['to'] ) : $attrs['to'],
				'email_tags'   => $this->get_tags()
			);

			$sent = false;

			if ( method_exists( $api, 'sendMultipleEmails' ) ) {
				$sent = $api->sendMultipleEmails( $data );
			}

			if ( $sent === true ) {
				/**
				 * If the email API returns success -> we stop emails sending through WordPress
				 */
				$return = false;
			}
		}

		return $return;
	}

	/**
	 * Localizes required data for admin
	 *
	 * @param array $data
	 *
	 * @return array mixed
	 */
	public function get_admin_data_localization( $data ) {

		$data['emailTemplates'] = array(
			self::NEW_ACCOUNT_TEMPLATE_SLUG        => array(
				'slug'      => self::NEW_ACCOUNT_TEMPLATE_SLUG,
				'name'      => esc_html__( 'New Account Created', 'thrive-apprentice' ),
				'subject'   => $this->_get_template_subject( self::NEW_ACCOUNT_TEMPLATE_SLUG ),
				'from_name' => $this->_get_template_from_name( self::NEW_ACCOUNT_TEMPLATE_SLUG ),
				'body'      => $this->_get_template_body( self::NEW_ACCOUNT_TEMPLATE_SLUG ),
				'triggers'  => $this->_get_template_triggers( self::NEW_ACCOUNT_TEMPLATE_SLUG ),
			),
			self::CERTIFICATE_ISSUED_TEMPLATE_SLUG => array(
				'slug'      => self::CERTIFICATE_ISSUED_TEMPLATE_SLUG,
				'name'      => esc_html__( 'Certificate manually issued', 'thrive-apprentice' ),
				'subject'   => $this->_get_template_subject( self::CERTIFICATE_ISSUED_TEMPLATE_SLUG ),
				'from_name' => $this->_get_template_from_name( self::CERTIFICATE_ISSUED_TEMPLATE_SLUG ),
				'body'      => $this->_get_template_body( self::CERTIFICATE_ISSUED_TEMPLATE_SLUG ),
				'triggers'  => $this->_get_template_triggers( self::CERTIFICATE_ISSUED_TEMPLATE_SLUG ),
			),
			self::ASSESSMENT_MARKED_TEMPLATE_SLUG  => array(
				'slug'      => self::ASSESSMENT_MARKED_TEMPLATE_SLUG,
				'name'      => esc_html__( 'Assessment Marked', 'thrive-apprentice' ),
				'subject'   => $this->_get_template_subject( self::ASSESSMENT_MARKED_TEMPLATE_SLUG ),
				'from_name' => $this->_get_template_from_name( self::ASSESSMENT_MARKED_TEMPLATE_SLUG ),
				'body'      => $this->_get_template_body( self::ASSESSMENT_MARKED_TEMPLATE_SLUG ),
				'triggers'  => $this->_get_template_triggers( self::ASSESSMENT_MARKED_TEMPLATE_SLUG ),
			),
			static::PRODUCT_ACCESS_EXPIRE          => [
				'slug'      => static::PRODUCT_ACCESS_EXPIRE,
				'name'      => esc_html__( 'Product access is expiring', 'thrive-apprentice' ),
				'subject'   => $this->_get_template_subject( static::PRODUCT_ACCESS_EXPIRE ),
				'from_name' => $this->_get_template_from_name( static::PRODUCT_ACCESS_EXPIRE ),
				'body'      => $this->_get_template_body( static::PRODUCT_ACCESS_EXPIRE ),
				'triggers'  => $this->_get_template_triggers( static::PRODUCT_ACCESS_EXPIRE ),
			],
			self::NEW_COURSE_WELCOME_TEMPLATE_SLUG => array(
				'slug'      => self::NEW_COURSE_WELCOME_TEMPLATE_SLUG,
				'name'      => esc_html__( 'New Course Welcome', 'thrive-apprentice' ),
				'subject'   => $this->_get_template_subject( self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ),
				'from_name' => $this->_get_template_from_name( self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ),
				'body'      => $this->_get_template_body( self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ),
				'triggers'  => $this->_get_template_triggers( self::NEW_COURSE_WELCOME_TEMPLATE_SLUG ),
			),
		);

		return $data;
	}

	/**
	 * Singleton instance
	 *
	 * @return TVA_Email_Templates
	 */
	public static function get_instance() {

		if ( ! isset( static::$instance ) ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Tear down function that destroy the instance
	 *
	 * @return void
	 */
	public static function tear_down() {
		static::$instance = null;
	}

	/**
	 * Call this when necessary:
	 * - new Thrive Cart Orders takes place
	 * - new account was created on registration page
	 * - new user is registered over WP Connection on LG Element
	 * - executes do_action() with a specified template for later process
	 *
	 * @param array $email_template
	 *
	 * @see prepare_new_user_email_template()
	 *
	 */
	public function trigger_process( $email_template ) {
		do_action( 'tva_prepare_new_user_email_template', $email_template );
	}

	/**
	 * On LG Submit if the connection is WordPress checks if there is an email template
	 * triggered for WordPress connection, if yes then execute trigger_process()
	 * and after the user is saved hooks onto `tvd_after_create_wordpress_account` action to send new registration email
	 *
	 * @param array                                 $data from LG Element
	 * @param Thrive_Dash_List_Connection_Wordpress $connection_instance
	 *
	 * @return mixed
	 */
	public function trigger_wp_new_registration( $data, $connection_instance ) {

		if ( false === $connection_instance instanceof Thrive_Dash_List_Connection_Wordpress ) {
			return $data;
		}

		//if there is any email template set for new wp user registration
		$email_template = tva_email_templates()->check_templates_for_trigger( 'wordpress' );
		if ( false !== $email_template ) {
			if ( ! empty( $data['password'] ) ) {
				$email_template['user_pass'] = $data['password'];
			}
			tva_email_templates()->trigger_process( $email_template );
		}

		return $data;
	}

	/**
	 * When password field is sent from LG notification process is not triggered so we have to do it here
	 *
	 * @param WP_User $user
	 * @param array   $arguments
	 */
	public function after_create_wordpress_account( $user, $arguments ) {

		$email_template = tva_email_templates()->check_templates_for_trigger( 'wordpress' );

		if ( false !== $email_template && isset( $arguments['password'] ) ) {
			wp_send_new_user_notifications( $user->ID );
		}
	}

	public function get_tags() {
		$tags = array();
		if ( ! empty( $this->_course ) ) {
			$tags[] = $this->_course;
		}

		if ( ! empty( $this->_product ) ) {
			$tags[] = $this->_product;
		}

		return $tags;
	}

	/**
	 * Track user creation from automation tools
	 *
	 * @param int $user_id The user ID.
	 */
	public function track_automator_user_creation( $user_id ) {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( $_SERVER['REQUEST_URI'] ) : '';
		
		// Check if this is coming from automation based on REQUEST_URI
		$is_automation = false;
		
		// Uncanny Automator webhook pattern (from logs: /wp-json/uap/v2/uap-1584-1587)
		if ( strpos( $request_uri, '/wp-json/uap/v2/' ) !== false ) {
			$is_automation = true;
		}
		
		// Thrive Automator webhook pattern (may need adjustment)
		if ( strpos( $request_uri, '/wp-json/tap/' ) !== false ) {
			$is_automation = true;
		}
		
		if ( $is_automation ) {
			set_transient( 'automator_pending_course_' . $user_id, true, 10 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * Send welcome email when course is assigned to automator-created user
	 *
	 * @param WP_User $user       The user object.
	 * @param int     $product_id The product ID.
	 */
	public function send_automator_welcome_email( $user, $product_id ) {
		$user_id = $user->ID;
		$transient_key = 'automator_pending_course_' . $user_id;
		
		// Check if transient exists (user created by automation)
		if ( ! get_transient( $transient_key ) ) {
			return;
		}
		
		// Clean up transient
		delete_transient( $transient_key );
		
		// Check if email template exists
		$email_template = $this->check_templates_for_trigger( 'wordpress' );
		if ( ! $email_template ) {
			return;
		}
		
		// Check if Product class exists to prevent fatal errors
		if ( ! class_exists( 'TVA\Product' ) ) {
			return;
		}
		
		// Create product instance and validate
		$product = new \TVA\Product( $product_id );
		if ( ! $product || ! method_exists( $product, 'get_name' ) ) {
			return;
		}
		
		// Set product name for email template
		$GLOBALS['tva_current_course_name'] = $product->get_name();
		
		// Send the welcome email
		$this->trigger_process( $email_template );
		wp_send_new_user_notifications( $user_id, 'user' );
	}

	/**
	 * Prepare the new course welcome email template.
	 *
	 * Sets up the content type and from name filters, and stores user/course data
	 * for shortcode processing.
	 *
	 * @param array $email_template The email template data containing 'user', 'course', and optionally 'from_name'.
	 *
	 * @return void
	 */
	public function prepare_new_course_welcome_email_template( $email_template ) {
		if ( ! is_array( $email_template ) ) {
			return;
		}

		if ( empty( $email_template['user'] ) || ! $email_template['user'] instanceof WP_User ) {
			return;
		}

		if ( empty( $email_template['course'] ) ) {
			return;
		}

		add_filter( 'wp_mail_content_type', static function () {
			return self::CONTENT_TYPE;
		} );

		add_filter( 'wp_mail_from_name', static function ( $from_name ) use ( $email_template ) {
			if ( ! empty( $email_template['from_name'] ) ) {
				return sanitize_text_field( $email_template['from_name'] );
			}

			return $from_name;
		} );

		$this->_user   = $email_template['user'];
		$this->_course = $email_template['course'];
	}

	/**
	 * Send course welcome email when user gains access to a course.
	 *
	 * @param WP_User $user       The user object.
	 * @param int     $product_id The product ID.
	 *
	 * @return void
	 */
	public function send_course_welcome_email( $user, $product_id ) {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return;
		}

		if ( ! class_exists( 'TVA\Product' ) ) {
			return;
		}

		$product = new \TVA\Product( $product_id );
		if ( ! $product->get_id() ) {
			return;
		}

		$courses = $product->get_courses();
		if ( empty( $courses ) || ! is_array( $courses ) ) {
			return;
		}

		foreach ( $courses as $course ) {
			$this->maybe_send_course_welcome( $user, $course );
		}
	}

	/**
	 * Send welcome emails to existing product users when a course is published.
	 *
	 * Collects all user IDs from all products associated with the course,
	 * deduplicates them, and decides whether to send synchronously (small batches)
	 * or queue for background processing via WP Cron (large batches).
	 *
	 * Threshold: If total users < 2x batch_size, send immediately (synchronous).
	 * Otherwise, queue for batch processing with wp_schedule_single_event().
	 *
	 * @param TVA_Course_V2 $course The published course.
	 *
	 * @return void
	 */
	public function send_welcome_emails_for_published_course( $course ) {
		if ( ! $course instanceof TVA_Course_V2 ) {
			return;
		}

		$course_id = absint( $course->get_id() );
		if ( ! $course_id ) {
			return;
		}

		if ( ! $course->get_send_welcome_email() ) {
			return;
		}

		$products = $course->get_product( true );
		if ( empty( $products ) || ! is_array( $products ) ) {
			return;
		}

		// Collect all user IDs from all products and deduplicate
		$all_user_ids = array();
		foreach ( $products as $product ) {
			$user_ids = $product->get_users_with_access();

			if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
				continue;
			}

			foreach ( $user_ids as $user_id ) {
				$user_id = absint( $user_id );
				if ( $user_id ) {
					$all_user_ids[] = $user_id;
				}
			}
		}

		$all_user_ids = array_unique( $all_user_ids );
		$all_user_ids = array_values( $all_user_ids );

		if ( empty( $all_user_ids ) ) {
			return;
		}

		$batch_size = $this->calculate_optimal_batch_size();

		// If total users < 2x batch_size, send synchronously
		if ( count( $all_user_ids ) < ( 2 * $batch_size ) ) {
			$this->send_welcome_emails_sync( $all_user_ids, $course );
		} else {
			// Large user count: send first batch immediately, queue the rest
			// This provides instant feedback and starts processing right away
			$first_batch   = array_slice( $all_user_ids, 0, $batch_size );
			$remaining_ids = array_slice( $all_user_ids, $batch_size );

			// Send first batch immediately (synchronous)
			$this->send_welcome_emails_sync( $first_batch, $course );

			// Queue remaining batches for background processing
			if ( ! empty( $remaining_ids ) ) {
				$this->queue_course_welcome_emails( $remaining_ids, $course_id, $batch_size );
			}
		}
	}

	/**
	 * Send welcome emails synchronously for a small set of users.
	 *
	 * Used when the total number of users is below the batch threshold (< 2x batch_size).
	 *
	 * @param array         $user_ids Array of user IDs to process.
	 * @param TVA_Course_V2 $course   The course object.
	 *
	 * @return void
	 */
	private function send_welcome_emails_sync( $user_ids, $course ) {
		foreach ( $user_ids as $user_id ) {
			$user_id = absint( $user_id );
			if ( ! $user_id ) {
				continue;
			}

			$user = get_user_by( 'ID', $user_id );
			if ( ! $user instanceof WP_User ) {
				continue;
			}

			$this->maybe_send_course_welcome( $user, $course );
		}
	}

	/**
	 * Calculate the optimal batch size based on server environment.
	 *
	 * Takes into account:
	 * - PHP max_execution_time (more time = larger batches)
	 * - SMTP type: premium services can handle higher throughput
	 * - Minimum: 10 emails/batch, Maximum: 250 emails/batch
	 *
	 * @return int The optimal batch size.
	 */
	public function calculate_optimal_batch_size() {
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		// Default to 30 seconds if unlimited (0) or unreadable
		if ( $max_execution_time <= 0 ) {
			$max_execution_time = 30;
		}

		$smtp_type = $this->get_smtp_type();

		switch ( $smtp_type ) {
			case 'premium':
				// Premium SMTP services (SendGrid, Mailgun, SES, etc.): 100-200 emails/batch
				// Base: 100, scale up with execution time
				$batch_size = 100 + (int) ( ( $max_execution_time - 30 ) * 1.5 );
				$batch_size = max( 100, min( 200, $batch_size ) );
				break;

			case 'basic':
				// Basic external SMTP (generic SMTP, Post SMTP, etc.): 10-50 emails/batch
				// Base: 10, scale up with execution time (very conservative for stability)
				$batch_size = 10 + (int) ( ( $max_execution_time - 30 ) * 0.5 );
				$batch_size = max( 10, min( 50, $batch_size ) );
				break;

			case 'local':
			default:
				// Local mail (PHP mail(), wp_mail default): 100-200 emails/batch
				// Local sending is fast but we cap to avoid timeouts
				$batch_size = 100 + (int) ( ( $max_execution_time - 30 ) * 1.5 );
				$batch_size = max( 100, min( 200, $batch_size ) );
				break;
		}

		// Apply global min/max bounds
		$batch_size = max( 10, min( 250, $batch_size ) );

		/**
		 * Filter the batch size for course welcome emails.
		 *
		 * @param int    $batch_size The calculated batch size.
		 * @param string $smtp_type  The detected SMTP type ('premium', 'basic', or 'local').
		 * @param int    $max_execution_time The PHP max_execution_time in seconds.
		 */
		return (int) apply_filters( 'tva_course_welcome_batch_size', $batch_size, $smtp_type, $max_execution_time );
	}

	/**
	 * Detect the SMTP type being used by the site.
	 *
	 * @return string One of 'premium', 'basic', or 'local'.
	 */
	public function get_smtp_type() {
		if ( $this->is_using_premium_smtp() ) {
			return 'premium';
		}

		if ( $this->is_using_external_smtp() ) {
			return 'basic';
		}

		return 'local';
	}

	/**
	 * Detect if the site is using a premium SMTP service.
	 *
	 * Checks for known premium email delivery services:
	 * - WP Mail SMTP with premium mailers (SendGrid, Mailgun, Amazon SES, etc.)
	 * - Standalone SendGrid plugin
	 * - Standalone Mailgun plugin
	 * - Amazon SES plugin
	 * - SparkPost plugin
	 * - Postmark plugin
	 *
	 * @return bool True if a premium SMTP service is detected.
	 */
	public function is_using_premium_smtp() {
		// Check WP Mail SMTP with premium mailers
		if ( function_exists( 'wp_mail_smtp' ) ) {
			$wp_mail_smtp_options = get_option( 'wp_mail_smtp', array() );
			$mailer               = isset( $wp_mail_smtp_options['mail']['mailer'] ) ? sanitize_key( $wp_mail_smtp_options['mail']['mailer'] ) : '';

			$premium_mailers = array(
				'sendgrid',
				'mailgun',
				'amazonses',
				'sendinblue',
				'sparkpost',
				'postmark',
				'sendlayer',
				'smtpcom',
			);

			if ( in_array( $mailer, $premium_mailers, true ) ) {
				return true;
			}
		}

		// Check FluentSMTP with premium connections
		if ( defined( 'FLUENTMAIL' ) ) {
			$fluentsmtp_settings = get_option( 'fluentmail-settings', array() );
			if ( ! empty( $fluentsmtp_settings['connections'] ) && is_array( $fluentsmtp_settings['connections'] ) ) {
				$premium_providers = array( 'ses', 'sendgrid', 'mailgun', 'sparkpost', 'postmark', 'sendinblue' );
				foreach ( $fluentsmtp_settings['connections'] as $connection ) {
					$provider = isset( $connection['provider_settings']['provider'] ) ? sanitize_key( $connection['provider_settings']['provider'] ) : '';
					if ( in_array( $provider, $premium_providers, true ) ) {
						return true;
					}
				}
			}
		}

		// Check standalone premium plugins
		if ( class_exists( 'SendGrid_Tools' ) || defined( 'SENDGRID_CATEGORY' ) ) {
			return true;
		}

		if ( class_exists( 'Mailgun' ) && defined( 'MAILGUN_PLUGIN_FILE' ) ) {
			return true;
		}

		if ( class_exists( 'AWS_SES_WP_Mail' ) || defined( 'WPSES_PLUGIN_VER' ) ) {
			return true;
		}

		if ( defined( 'SPARKPOST_PLUGIN_DIR' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect if the site is using any external SMTP service (non-premium).
	 *
	 * Checks for:
	 * - WP Mail SMTP with basic SMTP or other non-premium mailer
	 * - Post SMTP plugin
	 * - FluentSMTP with generic SMTP
	 * - Custom SMTP configuration via phpmailer_init
	 * - SMTP constants defined in wp-config
	 *
	 * @return bool True if an external SMTP service is detected.
	 */
	public function is_using_external_smtp() {
		// Check WP Mail SMTP (any non-default mailer = external SMTP)
		if ( function_exists( 'wp_mail_smtp' ) ) {
			$wp_mail_smtp_options = get_option( 'wp_mail_smtp', array() );
			$mailer               = isset( $wp_mail_smtp_options['mail']['mailer'] ) ? sanitize_key( $wp_mail_smtp_options['mail']['mailer'] ) : '';

			if ( ! empty( $mailer ) && $mailer !== 'mail' ) {
				return true;
			}
		}

		// Check Post SMTP
		if ( class_exists( 'PostmanOptions' ) || defined( 'POST_SMTP_VER' ) ) {
			return true;
		}

		// Check FluentSMTP
		if ( defined( 'FLUENTMAIL' ) ) {
			return true;
		}

		// Check Easy WP SMTP
		if ( class_exists( 'EasyWPSMTP' ) || defined( 'EasyWPSMTP_PLUGIN_VER' ) ) {
			return true;
		}

		// Check for custom SMTP constants commonly set in wp-config.php
		if ( defined( 'SMTP_HOST' ) || defined( 'WPMS_SMTP_HOST' ) ) {
			return true;
		}

		// Check if phpmailer_init has custom SMTP hooks (beyond WordPress defaults)
		if ( has_filter( 'phpmailer_init' ) ) {
			// This filter is commonly used by SMTP plugins to configure PHPMailer
			// If it has hooks beyond WP defaults, external SMTP is likely configured
			global $wp_filter;
			if ( isset( $wp_filter['phpmailer_init'] ) && count( $wp_filter['phpmailer_init']->callbacks ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Queue course welcome emails for background batch processing.
	 *
	 * Stores the queue data in wp_options and schedules the first batch
	 * via wp_schedule_single_event with a 30-second delay.
	 *
	 * @param array $user_ids   Array of user IDs to process.
	 * @param int   $course_id  The course ID.
	 * @param int   $batch_size The number of emails to send per batch.
	 *
	 * @return void
	 */
	private function queue_course_welcome_emails( $user_ids, $course_id, $batch_size ) {
		$course_id  = absint( $course_id );
		$batch_size = absint( $batch_size );

		if ( ! $course_id || ! $batch_size || empty( $user_ids ) ) {
			return;
		}

		$queue_key = 'tva_course_welcome_queue_' . $course_id;

		$queue_data = array(
			'course_id'  => $course_id,
			'user_ids'   => array_map( 'absint', $user_ids ),
			'batch_size' => $batch_size,
			'offset'     => 0,
			'total'      => count( $user_ids ),
			'started_at' => time(),
		);

		update_option( $queue_key, $queue_data, false );

		// Schedule the next batch with a 30-second delay
		// Note: First batch is already sent synchronously before queueing
		$scheduled = wp_schedule_single_event(
			time() + 30,
			'tva_process_course_welcome_batch',
			array( $course_id )
		);

		if ( false === $scheduled ) {
			// Check if a batch event is already scheduled (e.g. rapid re-publish within 10 min)
			// If so, the existing event will pick up the updated queue data — no cleanup needed
			if ( ! wp_next_scheduled( 'tva_process_course_welcome_batch', array( $course_id ) ) ) {
				error_log( 'TVA: Failed to schedule course welcome batch for course ' . $course_id );
				delete_option( $queue_key );
			}
		}
	}

	/**
	 * Process a batch of course welcome emails (WP Cron callback).
	 *
	 * Reads the queue from wp_options, processes batch_size users starting
	 * from the current offset, and schedules the next batch if users remain.
	 * Cleans up the option when all users have been processed.
	 *
	 * @param int $course_id The course ID to process.
	 *
	 * @return void
	 */
	public function process_course_welcome_batch( $course_id ) {
		$course_id = absint( $course_id );
		if ( ! $course_id ) {
			return;
		}

		$queue_key  = 'tva_course_welcome_queue_' . $course_id;
		$queue_data = get_option( $queue_key );

		if ( empty( $queue_data ) || ! is_array( $queue_data ) ) {
			return;
		}

		// Validate queue data structure
		if ( empty( $queue_data['user_ids'] ) || ! is_array( $queue_data['user_ids'] ) ) {
			delete_option( $queue_key );

			return;
		}

		$course = TVA_Course_V2::get_instance( $course_id );
		if ( ! $course instanceof TVA_Course_V2 || ! $course->get_id() ) {
			delete_option( $queue_key );

			return;
		}

		// Verify the course still has welcome email enabled
		if ( ! $course->get_send_welcome_email() ) {
			delete_option( $queue_key );

			return;
		}

		$user_ids   = $queue_data['user_ids'];
		$batch_size = isset( $queue_data['batch_size'] ) ? absint( $queue_data['batch_size'] ) : $this->calculate_optimal_batch_size();
		$offset     = isset( $queue_data['offset'] ) ? absint( $queue_data['offset'] ) : 0;
		$total      = count( $user_ids );

		// Extract the current batch
		$batch = array_slice( $user_ids, $offset, $batch_size );

		if ( empty( $batch ) ) {
			// No more users to process, clean up
			delete_option( $queue_key );

			return;
		}

		// Process this batch
		$consecutive_failures     = 0;
		$max_consecutive_failures = 5;
		$processed_count          = 0;
		$circuit_breaker_tripped  = false;

		foreach ( $batch as $user_id ) {
			$user_id = absint( $user_id );
			if ( ! $user_id ) {
				$processed_count ++;
				continue;
			}

			$user = get_user_by( 'ID', $user_id );
			if ( ! $user instanceof WP_User ) {
				$processed_count ++;
				continue;
			}

			try {
				$this->maybe_send_course_welcome( $user, $course );
				$consecutive_failures = 0;
			} catch ( \Exception $e ) {
				$consecutive_failures ++;
				error_log( 'TVA: Error sending course welcome email to user ' . $user_id . ' for course ' . $course_id . ': ' . $e->getMessage() );

				if ( $consecutive_failures >= $max_consecutive_failures ) {
					error_log( 'TVA: Pausing course welcome batch for course ' . $course_id . ' - ' . $max_consecutive_failures . ' consecutive failures' );
					$circuit_breaker_tripped = true;
					break;
				}
			}

			$processed_count ++;
		}

		// When circuit breaker trips, rewind offset to retry from the first failure in the streak
		$new_offset = $offset + $processed_count - ( $circuit_breaker_tripped ? $consecutive_failures - 1 : 0 );

		if ( $new_offset >= $total ) {
			// All users processed, clean up
			delete_option( $queue_key );
		} else {
			// Update the offset and schedule the next batch
			$queue_data['offset'] = $new_offset;
			update_option( $queue_key, $queue_data, false );

			// Schedule next batch with 30-second delay
			$scheduled = wp_schedule_single_event(
				time() + 30,
				'tva_process_course_welcome_batch',
				array( $course_id )
			);

			if ( false === $scheduled ) {
				// Check if a batch event is already scheduled (e.g. rapid re-publish within 10 min)
				// If so, the existing event will pick up the updated queue data — no cleanup needed
				if ( ! wp_next_scheduled( 'tva_process_course_welcome_batch', array( $course_id ) ) ) {
					error_log( 'TVA: Failed to schedule next course welcome batch for course ' . $course_id . ' at offset ' . $new_offset );
					delete_option( $queue_key );
				}
			}
		}
	}

	/**
	 * Attempt to send a welcome email for a specific course if conditions are met.
	 *
	 * Validates the course and user, checks deduplication, prepares the template,
	 * and sends via wp_mail(). Marks the email as sent on success.
	 *
	 * @param WP_User             $user   The user object.
	 * @param TVA_Course_V2|mixed $course The course object or term ID.
	 *
	 * @return void
	 */
	private function maybe_send_course_welcome( $user, $course ) {
		if ( ! $user instanceof WP_User || ! $user->ID ) {
			return;
		}

		if ( ! $course instanceof TVA_Course_V2 ) {
			$course_id = absint( $course );
			if ( ! $course_id ) {
				return;
			}
			$course = new TVA_Course_V2( $course_id );
		}

		$course_id = absint( $course->get_id() );
		if ( ! $course_id ) {
			return;
		}

		if ( ! $course->is_published() ) {
			return;
		}

		if ( ! $course->get_send_welcome_email() ) {
			return;
		}

		$user_id = absint( $user->ID );
		if ( $this->has_user_received_course_welcome( $user_id, $course_id ) ) {
			return;
		}

		$email_template = $this->check_templates_for_trigger( 'new_course_welcome' );
		if ( ! $email_template ) {
			return;
		}

		if ( empty( $email_template['subject'] ) || empty( $email_template['body'] ) ) {
			return;
		}

		$email_template['user']   = $user;
		$email_template['course'] = $course;

		do_action( 'tva_prepare_new_course_welcome_email_template', $email_template );

		$subject = do_shortcode( sanitize_text_field( $email_template['subject'] ) );
		$message = do_shortcode( nl2br( wp_kses_post( $email_template['body'] ) ) );

		$to = sanitize_email( $user->user_email );
		if ( empty( $to ) ) {
			return;
		}

		try {
			$sent = wp_mail( $to, $subject, $message );
		} catch ( \Exception $e ) {
			error_log( 'TVA: wp_mail failed for user ' . $user_id . ' course ' . $course_id . ': ' . $e->getMessage() );
			$sent = false;
		}

		if ( $sent ) {
			$this->mark_course_welcome_email_sent( $user_id, $course_id );
		}
	}

	/**
	 * Check if user has already received course welcome email.
	 *
	 * @param int $user_id   The user ID.
	 * @param int $course_id The course ID.
	 *
	 * @return bool
	 */
	public function has_user_received_course_welcome( $user_id, $course_id ) {
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );

		$meta_key = $this->get_course_welcome_meta_key( $course_id );

		return (bool) get_user_meta( $user_id, $meta_key, true );
	}

	/**
	 * Mark that user has received course welcome email.
	 *
	 * @param int $user_id   The user ID.
	 * @param int $course_id The course ID.
	 *
	 * @return bool|int
	 */
	public function mark_course_welcome_email_sent( $user_id, $course_id ) {
		$user_id   = absint( $user_id );
		$course_id = absint( $course_id );

		$meta_key = $this->get_course_welcome_meta_key( $course_id );

		return update_user_meta( $user_id, $meta_key, time() );
	}

	/**
	 * Get the meta key for tracking course welcome email sent status.
	 *
	 * @param int $course_id The course ID.
	 *
	 * @return string
	 */
	private function get_course_welcome_meta_key( $course_id ) {
		return 'tva_course_welcome_sent_' . absint( $course_id );
	}

	/**
	 * Get the course URL for use in email shortcodes.
	 *
	 * Attempts to find the course URL from various sources:
	 * 1. Instance $_course property (if TVA_Course_V2 object)
	 * 2. User assessment context
	 * 3. POST data (course_ids or course_id)
	 * 4. User's recent order history
	 *
	 * Note: When $_course is a string (e.g., in certificate email context where only
	 * course_name is passed), we cannot derive the URL and must fall back to other sources.
	 *
	 * @return string The escaped course URL, or empty string if unavailable.
	 */
	private function get_course_url_for_shortcode() {
		// First, check if course object is available on the instance
		if ( $this->_course instanceof TVA_Course_V2 ) {
			return esc_url( $this->_course->get_link( false ) );
		}

		// Check if we have a user assessment with course info
		$url = $this->get_course_url_from_assessment();
		if ( $url ) {
			return $url;
		}

		// Check POST data for course_ids array (enrollment context)
		$url = $this->get_course_url_from_post_course_ids();
		if ( $url ) {
			return $url;
		}

		// Check POST data for single course_id
		$url = $this->get_course_url_from_post_course_id();
		if ( $url ) {
			return $url;
		}

		// Fall back to user's recent order if available
		return $this->get_course_url_from_user_orders();
	}

	/**
	 * Get course URL from user assessment context.
	 *
	 * @return string The escaped course URL, or empty string if unavailable.
	 */
	private function get_course_url_from_assessment() {
		if ( ! $this->_user_assessment instanceof TVA_User_Assessment ) {
			return '';
		}

		$course_id = $this->_user_assessment->get_course_id();
		if ( ! $course_id ) {
			return '';
		}

		$course = new TVA_Course_V2( $course_id );
		if ( ! $course || ! $course->get_id() ) {
			return '';
		}

		return esc_url( $course->get_link( false ) );
	}

	/**
	 * Get course URL from POST course_ids array.
	 *
	 * @return string The escaped course URL, or empty string if unavailable.
	 */
	private function get_course_url_from_post_course_ids() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled at API level
		if ( empty( $_POST['course_ids'] ) || ! is_array( $_POST['course_ids'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$course_ids = array_map( 'absint', wp_unslash( $_POST['course_ids'] ) );
		if ( empty( $course_ids[0] ) ) {
			return '';
		}

		$course = new TVA_Course_V2( $course_ids[0] );
		if ( ! $course || ! $course->get_id() ) {
			return '';
		}

		return esc_url( $course->get_link( false ) );
	}

	/**
	 * Get course URL from POST course_id.
	 *
	 * @return string The escaped course URL, or empty string if unavailable.
	 */
	private function get_course_url_from_post_course_id() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification handled at API level
		if ( empty( $_POST['course_id'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$course_id = absint( wp_unslash( $_POST['course_id'] ) );
		if ( ! $course_id ) {
			return '';
		}

		$course = new TVA_Course_V2( $course_id );
		if ( ! $course || ! $course->get_id() ) {
			return '';
		}

		return esc_url( $course->get_link( false ) );
	}

	/**
	 * Get course URL from user's order history.
	 *
	 * @return string The escaped course URL, or empty string if unavailable.
	 */
	private function get_course_url_from_user_orders() {
		if ( ! isset( $this->_user ) || ! $this->_user instanceof WP_User ) {
			return '';
		}

		$tva_user = new TVA_User( $this->_user->ID );
		$orders   = $tva_user->get_orders();

		if ( empty( $orders ) ) {
			return '';
		}

		foreach ( $orders as $order ) {
			$order_items = $order->get_order_items();

			if ( empty( $order_items ) || ! isset( $order_items[0] ) ) {
				continue;
			}

			$product_id = $order_items[0]->get_product_id();

			if ( $order->is_stripe() ) {
				$products = TVA_Stripe_Integration::get_all_products_for_identifier( $product_id );
			} elseif ( $order->is_square() ) {
				$products = TVA_Square_Integration::get_all_products_for_identifier( $product_id );
			} else {
				$product  = new Product( (int) $product_id );
				$products = $product->get_id() ? [ $product ] : [];
			}

			foreach ( $products as $product ) {
				$courses = $product->get_courses();

				if ( ! empty( $courses ) && $courses[0] instanceof TVA_Course_V2 && $courses[0]->get_id() ) {
					return esc_url( $courses[0]->get_link( false ) );
				}
			}
		}

		return '';
	}
}
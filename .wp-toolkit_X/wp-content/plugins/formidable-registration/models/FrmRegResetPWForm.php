<?php

class FrmRegResetPWForm extends FrmRegForm {

	protected $path = '/views/reset_password_form.php';
	private $login = '';
	private $key = '';

	/**
	 * @since 2.13
	 * @var array
	 */
	protected $shortcode_atts = array();

	public function __construct( $atts ) {
		parent::__construct( $atts );

		$this->init_description();
		$this->init_login();
		$this->init_key();
		$this->init_submit_text( $atts );
		$this->init_shortcode_atts( $atts );
	}

	/**
	 * @since 2.13
	 * @param array $atts
	 * @return void
	 */
	private function init_shortcode_atts( $atts ) {
		$this->shortcode_atts = $atts;
	}

	/**
	 * Initialize the form description
	 *
	 * @since 2.0
	 */
	protected function init_description() {
		$global_settings = new FrmRegGlobalSettings();
		$this->description = $global_settings->get_global_message( 'reset_password' );
	}

	/**
	 * Initialize the login property
	 *
	 * @since 2.0
	 */
	private function init_login() {
		if ( isset( $_REQUEST['login'] ) ) {
			$this->login = sanitize_text_field( $_REQUEST['login'] );
		}
	}

	/**
	 * Initialize the key property
	 *
	 * @since 2.0
	 */
	private function init_key() {
		if ( isset( $_REQUEST['key'] ) ) {
			$this->key = sanitize_text_field( $_REQUEST['key'] );
		}
	}

	/**
	 * Initialize the submit button text
	 *
	 * @since 2.0
	 * @param $atts
	 */
	protected function init_submit_text( $atts ) {
		if ( isset( $atts['resetpass_button'] ) && $atts['resetpass_button'] ) {
			$this->submit_text = $atts['resetpass_button'];
		} else {
			$this->submit_text = __( 'Reset Password', 'frmreg' );
		}
	}

	/**
	 * Get the login property
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_login() {
		return $this->login;
	}

	/**
	 * Get the key property
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the HTML ID for the first password field
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_first_field_id() {
		return 'pass1_' . $this->form_number;
	}

	/**
	 * Get the HTML ID for the second password field
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_second_field_id() {
		return 'pass2_' . $this->form_number;
	}

	/**
	 * Get the HTML ID for the hidden user field
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_user_field_id() {
		return 'user_login_' . $this->form_number;
	}

	/**
	 * Get the error message from a code
	 *
	 * @since 2.0
	 *
	 * @param string $error_code
	 *
	 * @return string
	 */
	protected function get_error_message( $error_code ) {
		if ( 0 === strpos( $error_code, 'weak_password_' ) ) {
			return $this->get_weak_password_error_message( $error_code );
		}

		switch ( $error_code ) {
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'frmreg' );

			case 'password_reset_mismatch':
				return __( 'The two passwords you entered do not match.', 'frmreg' );

			case 'password_reset_empty':
				return __( 'Sorry, we do not accept empty passwords.', 'frmreg' );

			default:
				break;
		}

		return $error_code;
	}

	/**
	 * Gets weak password error message from the error type.
	 *
	 * @since 2.05
	 *
	 * @param string $error_type Weak password type.
	 * @return string
	 */
	protected function get_weak_password_error_message( $error_type ) {
		$field_obj = FrmRegResetPasswordController::get_fake_password_field_obj();
		if ( ! is_callable( array( $field_obj, 'password_checks' ) ) ) {
			return $error_type;
		}

		$checks     = $field_obj->password_checks();
		$error_type = str_replace( 'weak_password_', '', $error_type );
		if ( isset( $checks[ $error_type ]['message'] ) ) {
			return $checks[ $error_type ]['message'];
		}

		return $error_type;
	}

	/**
	 * Get the HTML for a form
	 *
	 * @since 2.13
	 *
	 * @return string
	 */
	public function get_html() {
		$show_label = __( 'Show password', 'frmreg' );

		$button_attrs = array(
			'type'                     => 'button',
			'class'                    => 'frm_show_password_btn',
			'title'                    => $show_label,
			'aria-label'               => $show_label,
			'data-hide-password-label' => __( 'Hide password', 'frmreg' ),
		);
		$password_visibility_toggle = isset( $this->shortcode_atts['password_visibility_toggle'] ) && FrmAppHelper::is_true( $this->shortcode_atts['password_visibility_toggle'] );
		$password_strength          = isset( $this->shortcode_atts['password_strength'] ) && FrmAppHelper::is_true( $this->shortcode_atts['password_strength'] );

		$this->enqueue_pro_js();

		return $this->get_view_content(
			array(
				'password_visibility_toggle' => $password_visibility_toggle,
				'password_strength'          => $password_strength,
			)
		);
	}

	/**
	 * @since 2.13
	 * @return void
	 */
	private function enqueue_pro_js() {
		FrmProAppController::register_scripts();
		wp_enqueue_script( 'formidable' );
		wp_enqueue_script( 'formidablepro' );
	}

	/**
	 * Makes password field html pass through kses function.
	 *
	 * @since 2.13
	 *
	 * @param array $allowed_html
	 * @return array
	 */
	public static function allow_password_field_html( $allowed_html ) {
		$allowed_tags = array(
			'class'        => true,
			'type'         => true,
			'name'         => true,
			'id'           => true,
			'value'        => true,
			'autocomplete' => true,
		);

		$allowed_html['input'] = empty( $allowed_html['input'] ) ? $allowed_tags : $allowed_html['input'] + $allowed_tags;

		$allowed_tags = array(
			'title'                    => true,
			'aria-label'               => true,
			'data-hide-password-label' => true,
		);

		$allowed_html['button'] = empty( $allowed_html['button'] ) ? $allowed_tags : $allowed_html['button'] + $allowed_tags;

		return $allowed_html;
	}
	/**
	 * @since 2.13
	 *
	 * @param bool $password_visibility_toggle
	 * @param bool $password_strength
	 * @param bool $is_confirmation
	 *
	 * @return void
	 */
	protected function maybe_show_password_widgets( $password_visibility_toggle, $password_strength, $is_confirmation = false ) {
		$classes = $password_strength ? 'frm_strength_meter' : '';
		if ( $is_confirmation ) {
			$field_id   = $this->get_second_field_id();
			$field_name = 'pass2';
		} else {
			$field_id   = $this->get_first_field_id();
			$field_name = 'pass1';
		}

		$input_html = '<input type="password" id="' . esc_attr( $field_id ) . '" name="' . esc_attr( $field_name ) . '" value="" autocomplete="off" class="' . esc_attr( $classes ) . '"/>';

		add_filter( 'frm_striphtml_allowed_tags', array( __CLASS__, 'allow_password_field_html' ) );

		if ( $password_visibility_toggle ) {
			$input_html = FrmProFieldsHelper::add_show_password_html( $input_html );
		}

		echo FrmAppHelper::kses( $input_html, 'all' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		remove_filter( 'frm_striphtml_allowed_tags', array( __CLASS__, 'allow_password_field_html' ) );

		if ( ! $password_strength || $is_confirmation ) {
			return;
		}

		$password_field = FrmRegResetPasswordController::get_fake_password_field_obj();
		if ( is_callable( array( $password_field, 'get_password_stength_html' ) ) ) {
			echo "\r\n";
			$password_field->get_password_stength_html( 'pass1', true );
		}
	}
}

<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Thrive_Dash_List_Connection_Turnstile extends Thrive_Dash_List_Connection_Abstract {
	public function __construct( $key ) {
		parent::__construct( $key );

		add_filter( 'tcb_spam_prevention_tools', [ $this, 'add_spam_prevention_tool' ] );
		add_action( 'tve_load_turnstile', [ $this, 'enqueue_turnstile_scripts' ] );
	}

	/**
	 * Return the connection type
	 *
	 * @return String
	 */
	public static function get_type() {
		return 'recaptcha';
	}

	/**
	 * @return string the API connection title
	 */
	public function get_title() {
		return 'Turnstile';
	}

	/**
	 * output the setup form html
	 *
	 * @return void
	 */
	public function output_setup_form() {
		do_action( 'tve_load_turnstile' );
		$this->output_controls_html( 'turnstile' );
	}

	public function enqueue_turnstile_scripts() {
		tve_dash_enqueue_script( 'tve-dash-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js' );
	}

	/**
	 * should handle: read data from post / get, test connection and save the details
	 *
	 * on error, it should register an error message (and redirect?)
	 *
	 * @return mixed
	 */
	public function read_credentials() {
		$site   = ! empty( $_POST['site_key'] ) ? sanitize_text_field( $_POST['site_key'] ) : '';
		$secret = ! empty( $_POST['secret_key'] ) ? sanitize_text_field( $_POST['secret_key'] ) : '';
		$token  = ! empty( $_POST['api_token'] ) ? sanitize_text_field( $_POST['api_token'] ) : '';

		$theme      = ! empty( $_POST['theme'] ) ? sanitize_text_field( $_POST['theme'] ) : '';
		$language   = ! empty( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : '';
		$appearance = ! empty( $_POST['appearance'] ) ? sanitize_text_field( $_POST['appearance'] ) : '';
		$size       = ! empty( $_POST['size'] ) ? sanitize_text_field( $_POST['size'] ) : '';

		//extra i believe we don't need following fields for api call
		if ( empty( $site ) || empty( $secret ) ) {
			return $this->error( __( 'Both Site Key and Secret Key fields are required', 'thrive-dash' ) );
		}

		//recreate credential object
		$credentials = array(
			'connection' => $this->post( 'connection' ),
			'site_key'   => $site,
			'secret_key' => $secret,
			'api_token'  => $token,
			'theme'      => $theme,
			'language'   => $language,
			'appearance' => $appearance,
			'size'       => $size,
		);

		$old_token = $this->param( 'api_token' );

		$this->set_credentials( $credentials );

		if ( strcmp( $old_token, $token ) !== 0 ) {
			if ( empty( $token ) ) {
				return $this->error( __( 'Token is missing!', 'thrive-dash' ) );
			}

			$result = $this->test_connection();

			if ( $result !== true ) {
				$errorMessage = ! empty( $result['message'] ) ? $result['message'] : __( 'Incorrect Secret Key.', 'thrive-dash' );

				return $this->error( sprintf( $errorMessage, $result ) );
			}
		}

		/**
		 * finally, save the connection details
		 */
		$this->save();

		return $this->success( __( 'Turnstile connected successfully!', 'thrive-dash' ) );
	}

	/**
	 * test if the secret key is correct and it exists.
	 *
	 * @return bool|array true for success or error details array for failure
	 */
	public function test_connection() {
		$CAPTCHA_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

		$_capthca_params = array(
			'response' => $this->param( 'api_token' ),
			'secret'   => $this->param( 'secret_key' ),
		);

		$request  = tve_dash_api_remote_post( $CAPTCHA_URL, array( 'body' => $_capthca_params ) );
		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		if ( ! empty( $response['error-codes'] ) ) {
			$errorLists = $this->getErrorLists();
			foreach ( $response['error-codes'] as $errorCode ) {
				if ( 'timeout-or-duplicate' === $errorCode ) {
					break;
				}

				if ( ! empty( $errorLists[ $errorCode ] ) ) {
					return array(
						'success' => false,
						'message' => $errorLists[ $errorCode ],
						'code'    => $errorCode,
					);
				}
			}
		}

		return true;
	}

	public function getErrorLists() {
		$errorLists = array(
			'missing-input-secret'   => __( 'The secret parameter was not passed', 'thrive-dash' ),
			'invalid-input-secret'   => __( 'The secret parameter was invalid or did not exist', 'thrive-dash' ),
			'missing-input-response' => __( 'The response parameter was not passed', 'thrive-dash' ),
			'invalid-input-response' => __( 'The response parameter is invalid or has expired', 'thrive-dash' ),
			'invalid-widget-id'      => __( 'The widget ID extracted from the parsed site secret key was invalid or did not exist', 'thrive-dash' ),
			'invalid-parsed-secret'  => __( 'The secret extracted from the parsed site secret key was invalid', 'thrive-dash' ),
			'bad-request'            => __( 'The request was rejected because it was malformed', 'thrive-dash' ),
			'internal-error'         => __( 'An internal error happened while validating the response. The request can be retried', 'thrive-dash' ),
		);

		return $errorLists;
	}

	public function getSiteKey() {
		$this->get_credentials();

		return $this->param( 'site_key' );
	}

	public function add_spam_prevention_tool( $sp_tools ) {
		array_push( $sp_tools, $this->_key );

		return $sp_tools;
	}

	public function supportedLanguages() {
		$languages = array(
			'auto'  => __( 'Auto', 'thrive-dash' ),
			'en'    => __( 'English (United States)', 'thrive-dash' ),
			'ar'    => __( 'Arabic (Egypt)', 'thrive-dash' ),
			'bg'    => __( 'Bulgarian (Bulgaria)', 'thrive-dash' ),
			'cs'    => __( 'Czech (Czech Republic)', 'thrive-dash' ),
			'da'    => __( 'Danish (Denmark)', 'thrive-dash' ),
			'de'    => __( 'German (Germany)', 'thrive-dash' ),
			'el'    => __( 'Greek (Greece)', 'thrive-dash' ),
			'es'    => __( 'Spanish (Spain)', 'thrive-dash' ),
			'fa'    => __( 'Farsi (Iran)', 'thrive-dash' ),
			'fi'    => __( 'Finnish (Finland)', 'thrive-dash' ),
			'fr'    => __( 'French (France)', 'thrive-dash' ),
			'he'    => __( 'Hebrew (Israel)', 'thrive-dash' ),
			'hi'    => __( 'Hindi (India)', 'thrive-dash' ),
			'hr'    => __( 'Croatian (Croatia)', 'thrive-dash' ),
			'hu'    => __( 'Hungarian (Hungary)', 'thrive-dash' ),
			'id'    => __( 'Indonesian (Indonesia)', 'thrive-dash' ),
			'it'    => __( 'Italian (Italy)', 'thrive-dash' ),
			'ja'    => __( 'Japanese (Japan)', 'thrive-dash' ),
			'ko'    => __( 'Korean (Korea)', 'thrive-dash' ),
			'lt'    => __( 'Lithuanian (Lithuania)', 'thrive-dash' ),
			'ms'    => __( 'Malay (Malaysia)', 'thrive-dash' ),
			'nb'    => __( 'Norwegian Bokmål (Norway)', 'thrive-dash' ),
			'nl'    => __( 'Dutch (Netherlands)', 'thrive-dash' ),
			'pl'    => __( 'Polish (Poland)', 'thrive-dash' ),
			'pt'    => __( 'Portuguese (Brazil)', 'thrive-dash' ),
			'ro'    => __( 'Romanian (Romania)', 'thrive-dash' ),
			'ru'    => __( 'Russian (Russia)', 'thrive-dash' ),
			'sk'    => __( 'Slovak (Slovakia)', 'thrive-dash' ),
			'sl'    => __( 'Slovenian (Slovenia)', 'thrive-dash' ),
			'sr'    => __( 'Serbian (Bosnia and Herzegovina)', 'thrive-dash' ),
			'sv'    => __( 'Swedish (Sweden)', 'thrive-dash' ),
			'th'    => __( 'Thai (Thailand)', 'thrive-dash' ),
			'tlh'   => __( 'Klingon (Qo’noS)', 'thrive-dash' ),
			'tr'    => __( 'Turkish (Turkey)', 'thrive-dash' ),
			'uk'    => __( 'Ukrainian (Ukraine)', 'thrive-dash' ),
			'vi'    => __( 'Vietnamese (Vietnam)', 'thrive-dash' ),
			'zh-cn' => __( 'Chinese (Simplified, China)', 'thrive-dash' ),
			'zh-tw' => __( 'Chinese (Traditional, Taiwan)', 'thrive-dash' ),
		);

		$result = array();
		foreach ( $languages as $code => $name ) {
			$result[ $code ] = array(
				"value" => $code,
				"name"  => $name,
				"html"  => sprintf( '<option value="%s" <#- (item && item.language === "%s") ? selected="selected" : "" #> >%s</option>', $code, $code, $name ),
			);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public function custom_success_message() {
		return ' ';
	}

	/*
	 * Those functions do not apply
	 */
	protected function get_api_instance() {
	}

	protected function _get_lists() {
	}

	public function add_subscriber( $list_identifier, $arguments ) {
	}
}

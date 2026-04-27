<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

namespace TCB\Tools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Spam_Prevention {
	private $tool;

	public function __construct( $tool ) {
		$this->tool = $tool;
	}

	public function execute( $data ) {
		$fn_name = "run_{$this->tool}";
		$fn_name = str_replace( '-', '_', $fn_name );

		if ( method_exists( $this, "{$fn_name}" ) ) {
			return $this->{$fn_name}( $data );
		}
	}

	function run_recaptcha( $data ) {
		$captcha_api = \Thrive_Dash_List_Manager::credentials( 'recaptcha' );
		$captcha_url = 'https://www.google.com/recaptcha/api/siteverify';

		$_capthca_params = array(
			'response' => empty( $data['g-recaptcha-response'] ) ? '' : $data['g-recaptcha-response'],
			'secret'   => empty( $captcha_api['secret_key'] ) ? '' : $captcha_api['secret_key'],
			'remoteip' => ! empty( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '',
		);

		$request  = tve_dash_api_remote_post( $captcha_url, [ 'body' => $_capthca_params ] );
		$response = json_decode( wp_remote_retrieve_body( $request ) );

		if ( empty( $response ) || $response->success === false || ( ! empty( $captcha_api['connection'] ) && $captcha_api['connection']['version'] === 'v3' && $response->score <= $captcha_api['connection']['threshold'] ) ) {
			return false;
		}

		return true;
	}

	function run_turnstile( $data ) {
		$captcha_api = \Thrive_Dash_List_Manager::credentials( 'turnstile' );

		$CAPTCHA_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

		$_capthca_params = array(
			'response' => empty( $data['cf-turnstile-response'] ) ? '' : $data['cf-turnstile-response'],
			'secret'   => empty( $captcha_api['secret_key'] ) ? '' : $captcha_api['secret_key'],
		);

		$request  = tve_dash_api_remote_post( $CAPTCHA_URL, array( 'body' => $_capthca_params ) );
		$response = json_decode( wp_remote_retrieve_body( $request ), true );

		$errorCodes = array( 'missing-input-secret', 'invalid-input-secret', 'missing-input-response', 'invalid-input-response', 'invalid-widget-id', 'invalid-parsed-secret', 'bad-request', 'timeout-or-duplicate', 'internal-error' );
		if ( ! empty( $response['error-codes'] ) ) {
			foreach ( $response['error-codes'] as $code ) {
				if ( in_array( $code, $errorCodes ) ) {
					return false;
				}
			}
		}

		return ( ! empty( $response['success'] ) && $response['success'] === true );
	}

	public function run_thrive_sp( $data ) {
		$spField = ! empty( $data['sp_field'] ) ? $data['sp_field'] : '';
		if ( ! empty( $spField ) && ! empty( $data[ $spField ] ) ) {
			return false;
		}

		return true;
	}
}

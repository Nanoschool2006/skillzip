<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_support")) {
	class cmplz_support
	{
		private static $_this;
		const CMPLZ_SUPPORT_MAIL = 'support@complianz.io';

		function __construct()
		{
			if (isset(self::$_this))
				wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

			self::$_this = $this;
			add_filter('cmplz_do_action', array($this, 'support_data'), 10, 3);
			add_filter( 'allowed_redirect_hosts' , array($this, 'allow_complianz_redirect') , 10 );
		}

		static function this()
		{
			return self::$_this;
		}

		/**
		 * @param array           $data
		 * @param string          $action
		 * @param WP_REST_Request $request
		 *
		 * @return array
		 */
		public function support_data( array $data, string $action, WP_REST_Request $request): array {
			if ( $action !== 'supportdata' ) {
				return $data;
			}

			if ( !cmplz_user_can_manage() ) {
				return $data;
			}

			$user_info = get_userdata( get_current_user_id() );
			$email = $user_info->user_email;
			$name = $user_info->display_name;
			$domain = site_url();
			$license_key = COMPLIANZ::$license->license_key();
			$license_key = COMPLIANZ::$license->maybe_decode( $license_key );
			$license_key = $license_key ?: '';
			$_GET['support_form'] = true;
			require_once(trailingslashit(CMPLZ_PATH).'system-status.php');
			$system_status = cmplz_get_system_status();
			$request = $request->get_json_params();

			$output = array(
				'message'       => isset( $request['message'] ) ? sanitize_text_field( $request['message'] ) : '',
				'customer_name' => $name,
				'email'         => $email,
				'domain'        => $domain,
				'license_key'   => $license_key,
				'system_status' => $system_status,
			);

			$body = '';
			foreach ( $output as $key => $value ) {
				$body .= "$key: $value\n";
			}

			$headers  = sprintf( 'From: %1$s <%2$s>', $name, $email ) . "\r\n";
			$headers .= "Content-Type: text/plain; charset=UTF-8";

			$mailer          = new cmplz_mailer();
			$mailer->to      = self::CMPLZ_SUPPORT_MAIL;
			$mailer->subject = 'Support | Request from ' . $name . ' for ' . $domain;
			$mailer->body    = $body;
			$mailer->headers = $headers;

			$send_request = $mailer->send_basic_mail();

			return array(
				'success' => $send_request,
				'message' => $send_request ?
					__( 'Your request has been sent.', 'complianz-gdpr' ) :
					sprintf(
						__( 'An error occurred. Please try again later or reach out to our support team at <a target="_blank" href="mailto:%s">%s</a>.', 'complianz-gdpr' ),
						self::CMPLZ_SUPPORT_MAIL,
						self::CMPLZ_SUPPORT_MAIL
					)
			);
		}


		/**
		 * @param array $allowed_hosts
		 *
		 * @return mixed
		 */
		public function allow_complianz_redirect($allowed_hosts){
			$allowed_hosts[] = 'complianz.io';
			return $allowed_hosts;
		}
	}
} //class closure

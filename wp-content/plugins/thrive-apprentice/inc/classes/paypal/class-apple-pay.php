<?php
/**
 * Apple Pay domain registration + domain-association file serving.
 *
 * Apple Pay requires two independent things before the button renders:
 *   1. PayPal-side registration of the checkout domain (POST /merchant/domains).
 *   2. Apple-side runtime verification: Apple fetches
 *      /.well-known/apple-developer-merchantid-domain-association over HTTPS and
 *      refuses to render unless it byte-matches PayPal's published file.
 * This class drives both, automatically and per-seller.
 *
 * @package TVA\PayPal
 */

namespace TVA\PayPal;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Apple_Pay {

	/** Option storing the last successful registration ( domain + mode + time ). */
	const REGISTERED_DOMAIN_OPTION = 'tva_paypal_apple_pay_domain';

	/** Throttle transient so an unregistered site retries at most hourly via admin. */
	const REGISTER_THROTTLE_TRANSIENT = 'tva_paypal_apple_pay_reg_attempt';

	/** Option storing the most recent install/registration error message, if any. */
	const ERROR_OPTION = 'tva_paypal_apple_pay_domain_error';

	/** Exact path Apple fetches. */
	const WELL_KNOWN_PATH = '/.well-known/apple-developer-merchantid-domain-association';

	/** PayPal's published association files (sandbox + live differ; live lives under /well-known/). */
	const FILE_URL = [
		'live' => 'https://www.paypalobjects.com/devdoc/apple-pay/well-known/apple-developer-merchantid-domain-association',
		'test' => 'https://www.paypalobjects.com/devdoc/apple-pay/sandbox/apple-developer-merchantid-domain-association',
	];

	/**
	 * Wire hooks. Registered from inc/functions.php alongside Hooks::init().
	 * The serve callback uses default priority (10) so it still fires when this
	 * runs during the init:9 PayPal bootstrap.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'maybe_serve_domain_file' ] );
		add_action( 'tva_paypal_capability_updated', [ __CLASS__, 'maybe_install' ] );
		add_action( 'admin_init', [ __CLASS__, 'maybe_install_throttled' ] );
		add_action( 'admin_notices', [ __CLASS__, 'render_error_notice' ] );
	}

	/** Record an install/registration error for the admin notice. */
	public static function record_error( $message ) {
		update_option( self::ERROR_OPTION, (string) $message, false );
	}

	/** Last recorded install/registration error, or '' when none. */
	public static function get_error() {
		return (string) get_option( self::ERROR_OPTION, '' );
	}

	/**
	 * Resolve the active mode. Uses the same predicate as Credentials::get_capabilities_summary()
	 * and the admin status template (is_mode_enabled = merchant id + valid site secret), so the
	 * file served, the domain registered, and the admin "Domain registered" label all agree on mode.
	 *
	 * @return string 'live' | 'test'
	 */
	public static function current_mode() {
		return Credentials::is_mode_enabled( 'live' ) ? 'live' : 'test';
	}

	/**
	 * Whether the static file is in place AND our local record marks the domain+mode as
	 * registered (see is_domain_registered() — the option written on a prior 201/422, not a
	 * live PayPal lookup). When false, maybe_install() re-runs the lifecycle.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 * @return bool
	 */
	public static function is_valid( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}
		return self::file_exists_in_docroot() && self::is_domain_registered( $mode );
	}

	/**
	 * Serve the domain-association file when the request path matches. Outputs raw
	 * bytes as application/octet-stream and exits. Never emits a wrong/empty body
	 * (a bad body fails Apple's byte-match worse than a 503).
	 */
	public static function maybe_serve_domain_file() {
		$path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
		if ( $path !== self::WELL_KNOWN_PATH ) {
			return;
		}

		// Reaching here means the static docroot file was NOT served by the web server —
		// either it doesn't exist, or the host routes /.well-known through PHP even when the
		// file is present. Either way we should serve via PHP: there is no double-serve risk
		// (the web server would have short-circuited the request before WP booted otherwise),
		// and bailing would 404 the file Apple needs.

		$body = self::get_association_file( self::current_mode() );
		if ( '' === $body ) {
			status_header( 503 );
			exit;
		}

		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Length: ' . strlen( $body ) );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw binary association file, must be byte-identical.
		exit;
	}

	/**
	 * Absolute path Apple expects the file at, under the server document root.
	 *
	 * @return string Path, or '' when the document root cannot be determined.
	 */
	public static function get_docroot_file_path() {
		$docroot = isset( $_SERVER['DOCUMENT_ROOT'] ) ? untrailingslashit( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '';
		if ( '' === $docroot ) {
			return '';
		}
		return $docroot . self::WELL_KNOWN_PATH;
	}

	/**
	 * Whether the static association file exists in the document root.
	 *
	 * @return bool
	 */
	public static function file_exists_in_docroot() {
		$path = self::get_docroot_file_path();
		return '' !== $path && file_exists( $path );
	}

	/**
	 * Write the association bytes to {docroot}/.well-known/… via WP_Filesystem.
	 * Throws on any failure so install() records it as the admin-facing error.
	 *
	 * @param string $content Raw association file bytes.
	 * @throws \RuntimeException When docroot is unknown, the dir can't be made, or the write fails.
	 */
	public static function write_to_docroot( $content ) {
		$path = self::get_docroot_file_path();
		if ( '' === $path ) {
			throw new \RuntimeException( 'Document root could not be determined for the Apple Pay domain file.' );
		}

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		global $wp_filesystem;
		if ( ! WP_Filesystem() || ! $wp_filesystem ) {
			throw new \RuntimeException( 'Filesystem is not writable for the Apple Pay domain file.' );
		}

		$dir = dirname( $path );
		if ( ! $wp_filesystem->is_dir( $dir ) && ! $wp_filesystem->mkdir( $dir, FS_CHMOD_DIR ) ) {
			throw new \RuntimeException( 'Unable to create the .well-known directory in the server root.' );
		}

		if ( ! $wp_filesystem->put_contents( $path, $content, FS_CHMOD_FILE ) ) {
			throw new \RuntimeException( 'Unable to write the Apple Pay domain file to the server root.' );
		}
	}

	/**
	 * Fetch + cache PayPal's published association file for a mode.
	 *
	 * @param string $mode 'live' | 'test'.
	 * @return string File bytes, or '' on failure.
	 */
	private static function get_association_file( $mode ) {
		$cache_key = 'tva_paypal_ap_domain_file_' . $mode;
		$cached    = get_transient( $cache_key );
		if ( is_string( $cached ) && '' !== $cached ) {
			return $cached;
		}

		// Rate-limit upstream retries: Apple (and buyers) hit this endpoint repeatedly, so a
		// failing fetch must not re-hit PayPal's CDN on every request. Back off briefly after a
		// failure; the success path below caches for ~2 days.
		$fail_key = 'tva_paypal_ap_domain_fail_' . $mode;
		if ( get_transient( $fail_key ) ) {
			return '';
		}

		$url = isset( self::FILE_URL[ $mode ] ) ? self::FILE_URL[ $mode ] : self::FILE_URL['live'];
		// PayPal's CDN (Akamai) intermittently 403s the default "WordPress/x" user agent from
		// datacenter IPs; a browser UA fetches reliably. One success warms the transient cache
		// below; the TTL is kept short-ish (2 days) since PayPal has changed this file before.
		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 15,
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
			]
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $fail_key, 1, 5 * MINUTE_IN_SECONDS );
			return '';
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			set_transient( $fail_key, 1, 5 * MINUTE_IN_SECONDS );
			return '';
		}

		set_transient( $cache_key, $body, 2 * DAY_IN_SECONDS );
		return $body;
	}

	/**
	 * Run the full Apple Pay domain lifecycle for a mode: fetch bytes, write the
	 * static docroot file, register the domain with PayPal. Any step failing throws,
	 * and the caller records it for the admin notice. On success clears the error.
	 *
	 * Order: write the static file first (so a docroot-permission problem surfaces as
	 * the recorded error), then register with PayPal.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 * @throws \RuntimeException When the fetch, write, or registration fails.
	 */
	public static function install( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}

		$content = self::get_association_file( $mode );
		if ( '' === $content ) {
			throw new \RuntimeException( 'Could not fetch the Apple Pay domain-association file from PayPal.' );
		}

		self::write_to_docroot( $content );
		self::register_domain_or_throw( $mode );

		delete_option( self::ERROR_OPTION );
	}

	/**
	 * Register the current domain with PayPal, throwing on a genuine failure.
	 * DOMAIN_ALREADY_REGISTERED is idempotent success. Delegates the option write
	 * to maybe_register_domain() so the REGISTERED_DOMAIN_OPTION record stays in one place.
	 *
	 * @param string $mode 'live' | 'test'.
	 * @throws \RuntimeException When PayPal returns a non-idempotent error.
	 */
	private static function register_domain_or_throw( $mode ) {
		self::maybe_register_domain( $mode );
		if ( ! self::is_domain_registered( $mode ) ) {
			throw new \RuntimeException( 'PayPal did not confirm the Apple Pay domain registration.' );
		}
	}

	/**
	 * Throttled admin entry point — bounds install attempts for an unverified site
	 * to once per hour, independent of which admin screen loaded.
	 */
	public static function maybe_install_throttled() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$mode = self::current_mode();
		if ( ! self::needs_domain_registration( $mode ) || self::is_valid( $mode ) ) {
			return;
		}

		// A stale-mode static file (e.g. after a test→live switch — the live and test
		// association files differ) is corrected by a local rewrite, not gated on a
		// PayPal call, so bypass the hourly throttle for it. A MISSING or unreadable file
		// is left throttled, so a non-writable docroot doesn't retry on every page load.
		// The throttle still bounds genuine registration retries (file current, PayPal
		// not yet confirmed).
		$expected = self::get_association_file( $mode );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.PHP.NoSilencedErrors.Discouraged -- local read of our own .well-known file.
		$on_disk = self::file_exists_in_docroot() ? @file_get_contents( self::get_docroot_file_path() ) : false;
		// Only a successful read that differs from the expected bytes is "stale". A read
		// failure (false) is treated as not-stale so it can't bypass the throttle.
		$stale = false !== $on_disk && '' !== $expected && $on_disk !== $expected;

		if ( ! $stale && get_transient( self::REGISTER_THROTTLE_TRANSIENT ) ) {
			return;
		}

		// Arm the throttle BEFORE attempting the install (intentional): if the install fails
		// — e.g. a non-writable docroot — the hour gate still holds so we don't retry on every
		// admin page load. The manual Re-verify button bypasses this for an immediate retry.
		set_transient( self::REGISTER_THROTTLE_TRANSIENT, 1, HOUR_IN_SECONDS );
		self::maybe_install( $mode );
	}

	/**
	 * Run the lifecycle when Apple Pay needs it and the site isn't already valid.
	 * Catches install failures and records them for the admin notice.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 */
	public static function maybe_install( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}
		if ( ! self::needs_domain_registration( $mode ) || self::is_valid( $mode ) ) {
			return;
		}
		try {
			self::install( $mode );
		} catch ( \Throwable $e ) {
			self::record_error( $e->getMessage() );
		}
	}

	/**
	 * Register the current checkout domain for Apple Pay if the capability requires
	 * it and we have not already registered this exact domain+mode.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 */
	public static function maybe_register_domain( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}

		if ( ! self::needs_domain_registration( $mode ) ) {
			return;
		}

		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $domain ) ) {
			return;
		}

		$record = get_option( self::REGISTERED_DOMAIN_OPTION, [] );
		if ( is_array( $record ) && ( $record['domain'] ?? '' ) === $domain && ( $record['mode'] ?? '' ) === $mode ) {
			return; // Already registered for this domain + mode.
		}

		$response = Request::register_domain( $domain, $mode );

		$ok = ! is_wp_error( $response );
		if ( is_wp_error( $response ) ) {
			// Only DOMAIN_ALREADY_REGISTERED is idempotent success — other 422s are genuine
			// validation failures and must NOT be recorded as registered (so we keep retrying).
			// Match the explicit issue marker in the message or the structured error body rather
			// than treating any 422 as success.
			$data     = $response->get_error_data();
			$haystack = $response->get_error_message();
			if ( is_array( $data ) && isset( $data['body'] ) ) {
				$haystack .= ' ' . wp_json_encode( $data['body'] );
			}
			$ok = false !== stripos( $haystack, 'ALREADY_REGISTERED' );
		}

		if ( $ok ) {
			// autoload=false: this option is only read in admin/webhook paths, never on the
			// frontend checkout, so keep it out of the autoloaded options set.
			update_option(
				self::REGISTERED_DOMAIN_OPTION,
				[
					'domain' => $domain,
					'mode'   => $mode,
					'time'   => time(),
				],
				false
			);
		}
		// On 503 (upstream PayPal 5xx) we intentionally leave the record unset so a
		// later trigger retries. See docs/product-api-doc.md "Troubleshooting: 500s
		// on /wallet-domains" — the fix is re-onboarding the merchant.
	}

	/**
	 * Whether the current checkout domain is registered for Apple Pay in the given mode,
	 * per our local record (written by maybe_register_domain() on a confirmed 201/422).
	 *
	 * PayPal keeps reporting capabilities_summary apple_pay extra.domain_registration_required
	 * as true even after a domain IS registered (it's a capability attribute, not a live
	 * "still outstanding" status), so the admin UI must read this record — not that flag — to
	 * tell the merchant their domain is handled.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 * @return bool
	 */
	public static function is_domain_registered( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}

		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		$record = get_option( self::REGISTERED_DOMAIN_OPTION, [] );

		return ! empty( $domain )
			&& is_array( $record )
			&& ( $record['domain'] ?? '' ) === $domain
			&& ( $record['mode'] ?? '' ) === $mode;
	}

	/**
	 * Does the stored capabilities_summary mark apple_pay as needing domain
	 * registration?
	 *
	 * @param string $mode 'live' | 'test'.
	 * @return bool
	 */
	public static function needs_domain_registration( $mode ) {
		foreach ( Credentials::get_capabilities_summary( $mode )['groups'] as $group ) {
			foreach ( (array) ( $group['items'] ?? [] ) as $item ) {
				if ( 'apple_pay' === ( $item['key'] ?? '' ) ) {
					return ! empty( $item['extra']['domain_registration_required'] );
				}
			}
		}
		return false;
	}

	/**
	 * Tear down all local Apple Pay domain state — the registration record, any
	 * recorded error, and the static docroot file. Called on PayPal disconnect so a
	 * future merchant doesn't inherit a stale registration or a file they can't vouch for.
	 */
	public static function uninstall() {
		delete_option( self::REGISTERED_DOMAIN_OPTION );
		delete_option( self::ERROR_OPTION );

		$path = self::get_docroot_file_path();
		if ( '' !== $path && file_exists( $path ) ) {
			// Silenced: a leftover file is less harmful than a fatal, and the next
			// install() rewrites it if the merchant reconnects.
			@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Force PayPal to re-run Apple's domain validation: deregister, tear down local
	 * state, then install from scratch. PayPal otherwise short-circuits with
	 * DOMAIN_ALREADY_REGISTERED and never retries a failed Apple-side check.
	 * Admin-only; never invoked from a customer-facing path.
	 *
	 * @param string|null $mode 'live' | 'test'. Null resolves via current_mode().
	 * @throws \RuntimeException When the re-install fails.
	 */
	public static function reverify( $mode = null ) {
		if ( null === $mode ) {
			$mode = self::current_mode();
		}
		$domain = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( ! empty( $domain ) ) {
			// Best-effort: a "not registered" result is fine — the post-condition holds.
			Request::deregister_domain( $domain, $mode );
		}
		self::uninstall();
		self::install( $mode );
	}

	/**
	 * Surface the last install/registration failure as a dismissible admin notice on
	 * the Apprentice settings + dashboard screens, so the merchant can retry or fix hosting.
	 */
	public static function render_error_notice() {
		$error = self::get_error();
		if ( '' === $error || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Limit to Thrive Apprentice screens — don't shout the Apple Pay failure at every
		// admin on the generic WP dashboard or unrelated pages.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, 'thrive-apprentice' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e( 'PayPal Apple Pay:', 'thrive-apprentice' ); ?></strong>
				<?php esc_html_e( 'Apple Pay domain registration did not complete, so the Apple Pay button will not render at checkout until this is resolved.', 'thrive-apprentice' ); ?>
			</p>
			<p><code><?php echo esc_html( $error ); ?></code></p>
			<p><?php esc_html_e( 'Retry from the Apprentice PayPal settings, or contact your host if the server root is not writable.', 'thrive-apprentice' ); ?></p>
		</div>
		<?php
	}
}

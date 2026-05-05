<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class TCB_Content_REST
 *
 * REST controller for the canonical Thrive Architect save pipeline. Exposes
 * POST /wp-json/tcb/v1/posts/<id>/content as a parity-equivalent alternative to
 * the editor's admin-ajax `tcb_save_post` flow, so external clients (App
 * Password authenticated automation, the CRO Agent, etc.) can persist content
 * without going through the editor UI.
 *
 * The controller does authentication + authorization, normalises the request
 * body into the `$_POST`-shaped payload that {@see TCB_Editor_Ajax::save_post_content()}
 * expects, then delegates the actual save.
 */
class TCB_Content_REST {

	public static $namespace = 'tcb/v1';
	public static $route     = '/posts/(?P<id>\d+)/content';

	/**
	 * Register the REST routes for this controller. Called from the global
	 * `rest_api_init` action via {@see tcb_rest_api_init()} in plugin-core.php.
	 */
	public static function register_routes() {
		/* `args` is intentionally omitted from the route registration. WP REST runs `sanitize_params()` against the registered arg schema before the callback executes — and although `type:string` only casts via `(string) $value` today, any future schema-driven sanitization (e.g. text-field coercion via formats) would silently strip HTML/CSS from `tve_content`/`inline_rules`/`tve_custom_css`/`tve_stripped_content` and break the byte-parity guarantee. The save pipeline does its own normalisation in `TCB_Editor_Ajax::save_post_content()`. The schema is preserved as `get_endpoint_args()` for documentation. */
		register_rest_route(
			self::$namespace,
			self::$route,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ __CLASS__, 'save_content' ],
					'permission_callback' => [ __CLASS__, 'permission_check' ],
				],
			]
		);
	}

	/**
	 * Permission gate for the save endpoint. Returns true / WP_Error.
	 *
	 * Checks run authentication and capability first so unauthenticated callers
	 * cannot probe post existence or post-type configuration. Mirrors the AJAX
	 * handler's required caps: caller must be authenticated, hold a Thrive
	 * Architect product cap (`tcb_has_external_cap()`), edit a TCB-editable
	 * post type, and have `edit_post` on the target.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return true|WP_Error
	 */
	public static function permission_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be authenticated to perform this action.', 'thrive-cb' ),
				[ 'status' => 401 ]
			);
		}

		if ( ! tcb_has_external_cap() ) {
			return new WP_Error(
				'tcb_rest_no_architect_cap',
				__( 'Your account does not have access to Thrive Architect.', 'thrive-cb' ),
				[ 'status' => 403 ]
			);
		}

		$post_id = (int) $request['id'];

		if ( $post_id <= 0 ) {
			return new WP_Error(
				'tcb_rest_invalid_post',
				__( 'Invalid post id.', 'thrive-cb' ),
				[ 'status' => 400 ]
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'tcb_rest_post_not_found',
				__( 'Post not found.', 'thrive-cb' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! function_exists( 'tve_is_post_type_editable' ) || ! tve_is_post_type_editable( $post->post_type, $post_id ) ) {
			return new WP_Error(
				'tcb_rest_post_type_not_editable',
				__( 'This post type is not editable with Thrive Architect.', 'thrive-cb' ),
				[ 'status' => 400 ]
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'You are not allowed to edit this post.', 'thrive-cb' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * POST /tcb/v1/posts/<id>/content — save Architect content for the post.
	 *
	 * Body is JSON (or form-encoded). Field shape mirrors the AJAX endpoint;
	 * see {@see TCB_Editor_Ajax::save_post_content()} for the contract.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function save_content( $request ) {
		@ini_set( 'memory_limit', TVE_EXTENDED_MEMORY_LIMIT ); //phpcs:ignore

		$post_id = (int) $request['id'];

		/* When the client sends a JSON content type, parse failure must surface as a 400 — silently falling through to an empty payload would let a malformed body be persisted as a blank post. */
		$content_type = $request->get_content_type();
		$is_json      = is_array( $content_type ) && isset( $content_type['value'] ) && false !== stripos( (string) $content_type['value'], 'json' );

		if ( $is_json ) {
			$payload = $request->get_json_params();

			if ( null === $payload && '' !== trim( (string) $request->get_body() ) ) {
				return new WP_Error(
					'tcb_rest_invalid_json',
					__( 'Request body is not valid JSON.', 'thrive-cb' ),
					[ 'status' => 400 ]
				);
			}
		} else {
			$payload = $request->get_body_params();
		}

		if ( ! is_array( $payload ) ) {
			$payload = [];
		}

		/* The AJAX endpoint requires `update='true'` (string) to persist the rich content; the REST endpoint is exclusively the rich-content save path, so always normalize regardless of what the client sent (true, 1, "1", missing, etc.). */
		$payload['update'] = 'true';

		/* save_post_content() expects $_POST-shaped data — slashed strings, recursively. wp_slash() walks the array tree and addslashes() string values, matching what core does to $_POST. */
		$payload = wp_slash( $payload );

		/* Some `tcb_ajax_save_post` listeners (video reporting, conditional display) and one in-pipeline `apply_filters( 'tcb_ajax_*' )` call still read directly from $_REQUEST instead of the hook's payload arg. Overlay the slashed payload onto $_REQUEST for the duration of the save so REST callers get the same side-effects (video entries, conditional-display rules, etc.) as the editor flow. Restored in finally so failures don't leak request state. */
		$original_request = $_REQUEST;
		$_REQUEST         = $payload + $_REQUEST; //phpcs:ignore -- intentional overlay for legacy listener parity; reverted below.

		try {
			$result = TCB_Editor_Ajax::save_post_content( $post_id, $payload );
		} finally {
			$_REQUEST = $original_request;
		}

		$status = ! empty( $result['success'] ) ? 200 : 500;

		return new WP_REST_Response( $result, $status );
	}

	/**
	 * Argument schema surfaced via OPTIONS so REST clients (and JSONSchema
	 * tooling) can introspect the endpoint contract.
	 *
	 * @return array
	 */
	public static function get_endpoint_args() {
		return [
			'id'                     => [
				'description' => __( 'Target post ID.', 'thrive-cb' ),
				'type'        => 'integer',
				'required'    => true,
			],
			'tve_content'            => [
				'description' => __( 'Full Thrive Architect HTML for the post.', 'thrive-cb' ),
				'type'        => 'string',
				'required'    => false,
			],
			'inline_rules'           => [
				'description' => __( 'Generated inline CSS rules (the contents of `tve_custom_css`).', 'thrive-cb' ),
				'type'        => 'string',
				'required'    => false,
			],
			'tve_custom_css'         => [
				'description' => __( 'User-defined custom CSS for this post.', 'thrive-cb' ),
				'type'        => 'string',
				'required'    => false,
			],
			'tve_globals'            => [
				'description' => __( 'Per-post global options (font_cls, etc.).', 'thrive-cb' ),
				'type'        => 'object',
				'required'    => false,
			],
			'tve_landing_page'       => [
				'description' => __( 'Landing page template key, or empty/0 for a regular post.', 'thrive-cb' ),
				'type'        => [ 'string', 'integer' ],
				'required'    => false,
			],
			'page_events'            => [
				'description' => __( 'Page-level event triggers configured in the editor.', 'thrive-cb' ),
				'type'        => 'array',
				'required'    => false,
			],
			'has_icons'              => [
				'description' => __( 'Whether the content references the Thrive icon pack.', 'thrive-cb' ),
				'type'        => [ 'integer', 'boolean' ],
				'required'    => false,
			],
			'tve_has_masonry'        => [
				'description' => __( 'Whether the content uses the masonry layout helper.', 'thrive-cb' ),
				'type'        => [ 'integer', 'boolean' ],
				'required'    => false,
			],
			'tve_has_typefocus'      => [
				'description' => __( 'Whether the content uses the typefocus helper.', 'thrive-cb' ),
				'type'        => [ 'integer', 'boolean' ],
				'required'    => false,
			],
			'tve_has_wistia_popover' => [
				'description' => __( 'Whether the content uses the Wistia popover helper.', 'thrive-cb' ),
				'type'        => [ 'integer', 'boolean' ],
				'required'    => false,
			],
			'header'                 => [
				'description' => __( 'Header section post ID. Coerced via (int); empty string and null are treated as no override (0).', 'thrive-cb' ),
				'required'    => false,
			],
			'footer'                 => [
				'description' => __( 'Footer section post ID. Coerced via (int); empty string and null are treated as no override (0).', 'thrive-cb' ),
				'required'    => false,
			],
			'tve_stripped_content'   => [
				'description' => __( 'Stripped plain-text fallback rendered into post_content.', 'thrive-cb' ),
				'type'        => 'string',
				'required'    => false,
			],
			'custom_font_classes'    => [
				'description' => __( 'List of custom font classes to register for this post.', 'thrive-cb' ),
				'type'        => 'array',
				'required'    => false,
			],
		];
	}

	/**
	 * Hook target — wired from {@see tcb_rest_api_init()} on `rest_api_init`.
	 */
	public static function rest_api_init() {
		self::register_routes();
	}
}

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Webhooks;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden!
}

/**
 * REST controller for Webhooks management and testing.
 *
 * Routes under namespace td/v1:
 * - GET    /webhooks               → list
 * - POST   /webhooks               → create
 * - GET    /webhooks/{id}          → read
 * - PUT    /webhooks/{id}          → update (also PATCH)
 * - DELETE /webhooks/{id}          → delete
 * - GET    /webhooks/{id}/logs     → retrieve logs
 * - POST   /webhooks/{id}/test     → test send with synthetic context
 * - GET    /webhooks/settings      → get settings
 * - PUT    /webhooks/settings      → save settings (also PATCH)
 */
class TD_Webhooks_Rest_Controller {
    const REST_NAMESPACE = 'td/v1';
    const ROUTE_BASE     = '/webhooks';

    /**
     * Register all REST routes for Webhooks.
     *
     * @return void
     */
    public function register_routes() {
        // Collection routes: list and create
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE, [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'list_webhooks' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_webhook' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );

        // Single resource routes: read, update, delete
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_webhook' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
            [
                'methods'             => 'PUT,PATCH',
                'callback'            => [ $this, 'update_webhook' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_webhook' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );

        // Logs for a single webhook
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\\d+)/logs', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_logs' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );

        // Test send for a persisted webhook
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE . '/(?P<id>\\d+)/test', [
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'test_webhook' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );

        // Inline test without saving a CPT
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE . '/test', [
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'test_webhook_inline' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );

        // Global module settings get/update
        register_rest_route( self::REST_NAMESPACE, self::ROUTE_BASE . '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'can_manage' ],
            ],
        ] );
    }

    /**
     * Capability check for managing Webhooks endpoints.
     */
    public function can_manage(): bool {
        return current_user_can( \TVE_DASH_CAPABILITY );
    }

    /**
     * List all webhooks.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function list_webhooks( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( TD_Webhooks_Repository::list(), 200 );
    }

    /**
     * Create a webhook from request payload.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function create_webhook( WP_REST_Request $request ) {
        // Sanitize incoming payload to expected format
        $data = $this->sanitize_webhook_input( $request->get_json_params() ?: $request->get_params() );

        // Validate payload; bail early on failure
        if ( ! TD_Webhooks_Validator::validate_webhook( $data ) ) {
            return new WP_Error( 'td_invalid', __( 'Invalid webhook data', 'thrive-dash' ), [ 'status' => 400 ] );
        }

        // Persist webhook and return the created resource
        $id = TD_Webhooks_Repository::create( $data );

        if ( ! $id ) {
            return new WP_Error( 'td_create_failed', __( 'Failed to create webhook', 'thrive-dash' ), [ 'status' => 500 ] );
        }

        return new WP_REST_Response( TD_Webhooks_Repository::read( $id ), 201 );
    }

    /**
     * Retrieve a webhook by id.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function get_webhook( WP_REST_Request $request ) {
        // Load webhook by id
        $id   = (int) $request['id'];
        $data = TD_Webhooks_Repository::read( $id );

        // 404 if not found
        if ( empty( $data ) ) {
            return new WP_Error( 'td_not_found', __( 'Webhook not found', 'thrive-dash' ), [ 'status' => 404 ] );
        }

        // Return resource
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Update a webhook by id.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function update_webhook( WP_REST_Request $request ) {
        // Identify resource and sanitize payload
        $id   = (int) $request['id'];
        $data = $this->sanitize_webhook_input( $request->get_json_params() ?: $request->get_params() );

        // Ensure resource exists
        if ( empty( TD_Webhooks_Repository::read( $id ) ) ) {
            return new WP_Error( 'td_not_found', __( 'Webhook not found', 'thrive-dash' ), [ 'status' => 404 ] );
        }

        // Validate incoming changes
        if ( ! TD_Webhooks_Validator::validate_webhook( $data ) ) {
            return new WP_Error( 'td_invalid', __( 'Invalid webhook data', 'thrive-dash' ), [ 'status' => 400 ] );
        }

        // Persist update and return fresh representation
        TD_Webhooks_Repository::update( $id, $data );

        return new WP_REST_Response( TD_Webhooks_Repository::read( $id ), 200 );
    }

    /**
     * Delete a webhook by id.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function delete_webhook( WP_REST_Request $request ) {
        // Identify resource
        $id = (int) $request['id'];

        // Ensure resource exists
        if ( empty( TD_Webhooks_Repository::read( $id ) ) ) {
            return new WP_Error( 'td_not_found', __( 'Webhook not found', 'thrive-dash' ), [ 'status' => 404 ] );
        }

        // Delete and acknowledge
        TD_Webhooks_Repository::delete( $id );

        return new WP_REST_Response( [ 'deleted' => true ], 200 );
    }

    /**
     * Return execution logs for a webhook.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_logs( WP_REST_Request $request ) {
        // Load logs grouped by webhook id
        $id   = (int) $request['id'];
        $all  = get_option( 'td_webhooks_logs', [] );

        // Extract logs for the specific webhook (falls back to empty array)
        $logs = (array) ( $all[ $id ] ?? [] );

        return new WP_REST_Response( $logs, 200 );
    }

    /**
     * Trigger a test send for a webhook id.
     * The payload context can be provided in JSON body.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function test_webhook( WP_REST_Request $request ) {
        // Load webhook configuration by id
        $id = (int) $request['id'];
        $wh = TD_Webhooks_Repository::read( $id );

        // 404 if not found
        if ( empty( $wh ) ) {
            return new WP_Error( 'td_not_found', __( 'Webhook not found', 'thrive-dash' ), [ 'status' => 404 ] );
        }

        // Build a minimal test context using provided JSON body or defaults
        $payload_context = $request->get_json_params() ?: [];
        $context = [
            'trigger_when' => 'on_submit',
            'form_id'      => $payload_context['form_id'] ?? '',
            'post_id'      => (int) ( $payload_context['post_id'] ?? 0 ),
            'slug'         => sanitize_title( $payload_context['slug'] ?? '' ),
            'user_consent' => true,
            'data'         => (array) ( $payload_context['data'] ?? [] ),
            'user'         => wp_get_current_user(),
        ];

        // Send immediately (no queue) and report success
        TD_Webhooks_Sender::send( $wh, $context );

        return new WP_REST_Response( [ 'queued' => false, 'sent' => true ], 200 );
    }

    public function test_webhook_inline( WP_REST_Request $request ) {
        // Accept either {config, context} or a flat webhook config
        $raw = $request->get_json_params() ?: [];
        $config = (array) ( $raw['config'] ?? $raw );

        // Fill a default name if missing
        if ( empty( $config['name'] ) ) {
            $config['name'] = 'Inline Test';
        }

        // Normalize and sanitize the provided webhook config
        $wh = $this->sanitize_webhook_input( $config );

        // Build a minimal test context
        $ctx_in = (array) ( $raw['context'] ?? [] );
        $context = [
            'trigger_when' => 'on_submit',
            'form_id'      => $ctx_in['form_id'] ?? '',
            'post_id'      => (int) ( $ctx_in['post_id'] ?? 0 ),
            'slug'         => sanitize_title( $ctx_in['slug'] ?? '' ),
            'user_consent' => true,
            'data'         => (array) ( $ctx_in['data'] ?? [] ),
            'user'         => wp_get_current_user(),
        ];

        // Prepare HTTP client and request components
        $http = _wp_http_get_object();
        $url     = trim( (string) ( $wh['url'] ?? '' ) );
        $method  = strtoupper( (string) ( $wh['method'] ?? 'POST' ) );
        $format  = strtolower( (string) ( $wh['request_format'] ?? 'form' ) );
        $headers = (array) ( $wh['headers'] ?? [] );
        $mapping = (array) ( $wh['body_mapping'] ?? [] );

        // Build request payload and resolve header placeholders
        $payload = TD_Webhooks_Templating::build_payload( $mapping, $context );
        $header_map = TD_Webhooks_Sender::flat_key_value_pairs( $headers, $context );

        // Encode body according to the selected request format
        $body = $payload;
        if ( $method !== 'GET' ) {
            switch ( $format ) {
                case 'json':
                    $body = wp_json_encode( $payload );
                    $header_map['content-type'] = 'application/json';
                    break;
                case 'xml':
                    $body = TD_Webhooks_Sender::xml_encode( $payload );
                    break;
                case 'form':
                default:
                    // leave as array
                    break;
            }
        }

        // Remove hop-by-hop headers that WP HTTP will set
        unset( $header_map['host'], $header_map['content-length'] );

        // Finalize request args
        $timeout = (int) TD_Webhooks_Settings::get( 'timeout', 8 );
        $args = [ 'method' => $method, 'body' => $body, 'headers' => $header_map, 'timeout' => $timeout ];

        // Perform request and capture timing
        $start = microtime( true );
        $response = $http->request( $url, $args );
        $duration = (int) round( ( microtime( true ) - $start ) * 1000 );

        // Transport-level failure (DNS, timeout, SSL, etc.)
        if ( is_wp_error( $response ) ) {
            $code = $response->get_error_code();
            $message = $response->get_error_message();
            $diagnostics = [
                'http_status' => 0,
                'code'        => $code,
                'message'     => $message,
                'meaning'     => \TVE\Dashboard\Utils\TT_HTTP_Error_Map::meaning( [ 'code' => $code, 'http_status' => 0, 'message' => $message ] ),
                'duration_ms' => $duration,
                'request'     => [
                    'method'           => $method,
                    'url'              => $url,
                    'headers_included' => ! empty( $header_map ),
                    'body_bytes'       => is_scalar( $body ) ? strlen( (string) $body ) : strlen( wp_json_encode( $body ) ),
                ],
                'response'    => [
                    'status_text'      => '',
                    'headers'          => new \stdClass(),
                    'body'             => $message,
                    'body_size_bytes'  => strlen( (string) $message ),
                ],
            ];
            return new WP_REST_Response( [ 'ok' => false, 'error' => $diagnostics ], 400 );
        }

        // Extract response details
        $status    = (int) wp_remote_retrieve_response_code( $response );
        $status_t  = (string) wp_remote_retrieve_response_message( $response );
        $headers   = (array) wp_remote_retrieve_headers( $response );
        $body_str  = (string) wp_remote_retrieve_body( $response );

        // Non-2xx HTTP status: return diagnostics
        if ( $status < 200 || $status >= 300 ) {
            $diagnostics = [
                'http_status' => $status,
                'code'        => '',
                'message'     => $status_t,
                'meaning'     => \TVE\Dashboard\Utils\TT_HTTP_Error_Map::meaning( [ 'http_status' => $status, 'message' => $status_t ] ),
                'duration_ms' => $duration,
                'request'     => [
                    'method'           => $method,
                    'url'              => $url,
                    'headers_included' => ! empty( $header_map ),
                    'body_bytes'       => is_scalar( $body ) ? strlen( (string) $body ) : strlen( wp_json_encode( $body ) ),
                ],
                'response'    => [
                    'status_text'      => $status_t,
                    'headers'          => (object) $headers,
                    'body'             => $body_str,
                    'body_size_bytes'  => strlen( $body_str ),
                ],
            ];
            return new WP_REST_Response( [ 'ok' => false, 'error' => $diagnostics ], $status ?: 400 );
        }

        // Success
        return new WP_REST_Response( [ 'ok' => true, 'sent' => true, 'duration_ms' => $duration ], 200 );
    }

    /**
     * Get global Webhooks settings.
     *
     * @return WP_REST_Response
     */
    public function get_settings(): WP_REST_Response {
        return new WP_REST_Response( TD_Webhooks_Settings::get(), 200 );
    }

    // Save settings endpoint removed: settings are configured via filters

    /**
     * Sanitize webhook input payload to stored shape.
     *
     * @param array $in
     * @return array
     */
    private function sanitize_webhook_input( array $in ): array {
        // Produce a normalized, stored shape for a webhook configuration
        return [
            'name'             => sanitize_text_field( $in['name'] ?? '' ),
            'enabled'          => ! empty( $in['enabled'] ),
            'url'              => esc_url_raw( $in['url'] ?? '' ),
            'method'           => sanitize_key( $in['method'] ?? 'post' ),
            'request_format'   => sanitize_key( $in['request_format'] ?? 'form' ),
            'trigger_when'     => sanitize_key( $in['trigger_when'] ?? 'on_submit' ),
            'consent_required' => ! empty( $in['consent_required'] ),
            'headers'          => $this->normalize_pairs( $in['headers'] ?? [] ),
            'body_mapping'     => $this->normalize_pairs( $in['body_mapping'] ?? [] ),
            'targeting'        => $this->normalize_targeting( $in['targeting'] ?? [] ),
        ];
    }

    /**
     * Normalize key/value array into the expected list structure.
     *
     * @param mixed $pairs
     * @return array
     */
    private function normalize_pairs( $pairs ): array {
        $out = [];

        if ( is_array( $pairs ) ) {
            foreach ( $pairs as $row ) {
                // Skip entries without a key; sanitize values
                if ( empty( $row['key'] ) ) {
                    continue;
                }

                $out[] = [ 'key' => sanitize_text_field( $row['key'] ), 'value' => is_scalar( $row['value'] ?? '' ) ? wp_unslash( (string) $row['value'] ) : '' ];
            }
        }

        return $out;
    }

    /**
     * Normalize targeting structure from request payload.
     *
     * @param mixed $t
     * @return array
     */
    private function normalize_targeting( $t ): array {
        // Normalize base structure and scope
        $t = is_array( $t ) ? $t : [];
        $scope = isset( $t['scope'] ) ? sanitize_key( $t['scope'] ) : '';
        $scope = in_array( $scope, [ 'all', 'include', 'exclude' ], true ) ? $scope : '';

        // Collect targeted form IDs (strings)
        $form_ids = [];
        if ( isset( $t['form_ids'] ) ) {
            $form_ids = is_array( $t['form_ids'] ) ? $t['form_ids'] : $this->csv_or_array( $t['form_ids'] );
        }

        // Collect targeted post IDs (ints)
        $post_ids = [];
        if ( isset( $t['post_ids'] ) ) {
            $post_ids = is_array( $t['post_ids'] ) ? $t['post_ids'] : $this->csv_or_array( $t['post_ids'] );
            $post_ids = array_map( 'intval', $post_ids );
        }

        // Collect targeted slugs
        $slugs = [];
        if ( isset( $t['slugs'] ) ) {
            $slugs = is_array( $t['slugs'] ) ? $t['slugs'] : $this->csv_or_array( $t['slugs'] );
        }

        return [ 'scope' => $scope, 'form_ids' => array_values( $form_ids ), 'post_ids' => array_values( $post_ids ), 'slugs' => array_values( $slugs ) ];
    }

    /**
     * Convert CSV string or array input into normalized array of strings.
     *
     * @param mixed $val
     * @return array
     */
    private function csv_or_array( $val ): array {
        if ( is_array( $val ) ) {
            return array_values( array_filter( array_map( 'trim', $val ), static function( $s ) { return $s !== ''; } ) );
        }

        $val = (string) $val;

        if ( $val === '' ) {
            return [];
        }

        $parts = array_map( 'trim', explode( ',', $val ) );

        return array_values( array_filter( $parts, static function( $s ) { return $s !== ''; } ) );
    }
}

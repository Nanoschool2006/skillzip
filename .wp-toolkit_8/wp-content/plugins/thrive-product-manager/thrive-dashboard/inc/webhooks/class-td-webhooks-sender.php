<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Webhooks;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden!
}

/**
 * Prepare and send outbound HTTP requests for webhooks, with logging.
 */
class TD_Webhooks_Sender {
    /**
     * Build payload, headers and perform HTTP request. Logs the outcome.
     *
     * @param array $webhook Webhook configuration (id, url, method, request_format, headers, body_mapping, ...)
     * @param array $context Event context (trigger_when, form_id, post_id, slug, data, user, user_consent)
     * @return void
     */
    public static function send( array $webhook, array $context ): void {
        $url     = trim( (string) ( $webhook['url'] ?? '' ) );
        $method  = strtolower( (string) ( $webhook['method'] ?? 'post' ) );
        $format  = strtolower( (string) ( $webhook['request_format'] ?? 'form' ) );
        $headers = (array) ( $webhook['headers'] ?? [] );
        $mapping = (array) ( $webhook['body_mapping'] ?? [] );

        // If the URL is empty or not allowed, return.
        if ( empty( $url ) || ! self::is_url_allowed( $url ) ) {
            return;
        }

        // Build payload and resolve header placeholders
        $payload = TD_Webhooks_Templating::build_payload( $mapping, $context );
        $header_map = self::flat_key_value_pairs( $headers, $context );

        // Encode request body depending on selected format
        $body = $payload;

        if ( $method !== 'get' ) {
            switch ( $format ) {
                case 'json':
                    $body = TD_Webhooks_Templating::json_encode( $payload );
                    $header_map['content-type'] = 'application/json';
                    break;
                case 'xml':
                    $body = self::xml_encode( $payload );
                    break;
                case 'form':
                default:
                    // leave as array
                    break;
            }
        }

        // Strip hop-by-hop headers
        unset( $header_map['host'], $header_map['content-length'] );

        // Build request args
        $timeout = (int) TD_Webhooks_Settings::get( 'timeout', 8 );

        $args = [
            'method'  => strtoupper( $method ),
            'body'    => $body,
            'headers' => $header_map,
            'timeout' => $timeout,
        ];

        // Execute HTTP request and capture timing
        $http = _wp_http_get_object();
        $start = microtime( true );
        $response = $http->request( $url, $args );

        // Calculate the duration of the request.
        $duration = (int) round( ( microtime( true ) - $start ) * 1000 );

        // Extract response status/body for logging purposes
        $status = is_wp_error( $response ) ? $response->get_error_code() : wp_remote_retrieve_response_code( $response );
        $body_str = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_body( $response );

        // Record execution outcome
        TD_Webhooks_Logger::log( (int) ( $webhook['id'] ?? 0 ), [
            'status_code' => $status,
            'duration_ms' => $duration,
            'request'     => self::snapshot_request( $url, $args ),
            'response'    => self::snapshot_response( $response, $body_str ),
            'trigger_when'=> $context['trigger_when'] ?? '',
        ] );
    }

    /**
     * Create a request snapshot safe for logging (masked + truncated).
     *
     * @param string $url
     * @param array  $args
     * @return array
     */
    private static function snapshot_request( string $url, array $args ): array {
        return [
            'url'     => $url,
            'method'  => $args['method'] ?? 'GET',
            'headers' => self::mask_array( (array) ( $args['headers'] ?? [] ) ),
            'body'    => self::truncate( is_scalar( $args['body'] ?? '' ) ? (string) $args['body'] : TD_Webhooks_Templating::json_encode( $args['body'] ) ),
        ];
    }

    /**
     * Create a response snapshot safe for logging (masked + truncated).
     *
     * @param mixed  $response
     * @param string $body
     * @return array
     */
    private static function snapshot_response( $response, string $body ): array {
        $headers = is_wp_error( $response ) ? [] : wp_remote_retrieve_headers( $response );
        return [
            'headers' => self::mask_array( (array) $headers ),
            'body'    => self::truncate( $body ),
        ];
    }

    /**
     * Mask sensitive keys from an associative array.
     *
     * @param array $input
     * @return array
     */
    private static function mask_array( array $input ): array {
        $sensitive = [ 'authorization', 'api_key', 'api-key', 'token', 'x-api-key', 'x-auth-token' ];
        $out = [];
        foreach ( $input as $k => $v ) {
            $lk = strtolower( (string) $k );
            $out[ $k ] = in_array( $lk, $sensitive, true ) ? '***' : $v;
        }
        return $out;
    }

    /**
     * Truncate long strings for log safety.
     *
     * @param string $s
     * @return string
     */
    private static function truncate( string $s ): string {
        if ( strlen( $s ) > 2000 ) {
            return substr( $s, 0, 2000 ) . '…';
        }
        return $s;
    }

    /**
     * Convert [ ['key' => 'A', 'value' => 'B'], ... ] to [ 'a' => 'B' ] with lowercase keys.
     *
     * @param array $pairs
     * @return array
     */
    public static function flat_key_value_pairs( array $pairs, array $context = [] ): array {
        $out = [];

        foreach ( $pairs as $row ) {
            // If the key is not set or is empty, skip.
            if ( ! isset( $row['key'] ) || $row['key'] === '' ) {
                continue;
            }

            $key = strtolower( (string) $row['key'] );
            $val = $row['value'] ?? '';

            if ( is_string( $val ) ) {
                // Resolve {{placeholders}} inside header values using same rules as payload
                $val = TD_Webhooks_Templating::resolve_placeholders( $val, $context );
            }

            $out[ $key ] = is_scalar( $val ) ? (string) $val : TD_Webhooks_Templating::json_encode( $val );
        }

        return $out;
    }

    /**
     * Enforce scheme/host and optional allow/deny lists from settings.
     *
     * @param string $url
     * @return bool
     */
    private static function is_url_allowed( string $url ): bool {
        $parts = wp_parse_url( $url );

        // If the scheme is not http or https, return false.
        if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
            return false;
        }

        $host = $parts['host'];

        // If the host is empty or localhost, return false.
        if ( empty( $host ) || in_array( strtolower( $host ), [ 'localhost', '127.0.0.1' ], true ) ) {
            return false;
        }

        // Optionally enforce allow/deny lists
        $allow = TD_Webhooks_Settings::get( 'allowlist', [] );
        $deny  = TD_Webhooks_Settings::get( 'denylist', [] );

        if ( ! empty( $deny ) ) {
            foreach ( $deny as $pattern ) {
                if ( $pattern && fnmatch( $pattern, $host ) ) {
                    return false;
                }
            }
        }

        if ( ! empty( $allow ) ) {
            foreach ( $allow as $pattern ) {
                if ( $pattern && fnmatch( $pattern, $host ) ) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Encode an array as XML suitable for POSTing.
     *
     * @param mixed                    $data
     * @param \SimpleXMLElement|null $xml
     * @return string
     */
    public static function xml_encode( $data, \SimpleXMLElement $xml = null ) {
        // If the XML is null, create a new SimpleXMLElement.
        if ( $xml === null ) {
            $xml = new \SimpleXMLElement( '<root/>' );
        }

        // Loop through the data and add the key and value to the XML.
        foreach ( (array) $data as $key => $value ) {

            // If the key is numeric, set it to 'item'.
            $key = is_numeric( $key ) ? 'item' : $key;

            // If the value is an array, add the key and value to the XML.
            if ( is_array( $value ) ) {
                $child = $xml->addChild( $key );

                // Recursively add the key and value to the XML.
                self::xml_encode( $value, $child );
            } else {
                // Add the key and value to the XML.
                $xml->addChild( $key, htmlspecialchars( (string) $value ) );
            }
        }

        // Return the XML as a string.
        return $xml->asXML();
    }
}


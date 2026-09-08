<?php
/**
 * Thrive Themes - Shared HTTP error mapping
 */

namespace TVE\Dashboard\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden!
}

class TT_HTTP_Error_Map {
    /**
     * Return standard HTTP status text for a code, or empty string.
     */
    public static function http_status_text( int $status ): string {
        static $map = [
            0   => '',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            413 => 'Payload Too Large',
            415 => 'Unsupported Media Type',
            418 => "I'm a teapot",
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];
        return $map[ $status ] ?? '';
    }

    /**
     * Human-friendly meaning for common transport/HTTP errors.
     * Context may include: code (string), http_status (int), message (string).
     */
    public static function meaning( array $context ): string {
        $code = isset( $context['code'] ) ? strtolower( (string) $context['code'] ) : '';
        $status = isset( $context['http_status'] ) ? (int) $context['http_status'] : 0;
        $message = (string) ( $context['message'] ?? '' );

        // If HTTP status is present and a standard text exists, use that.
        if ( $status > 0 ) {
            $text = self::http_status_text( $status );
            if ( $text !== '' ) {
                return $text;
            }
        }

        // Common WP/cURL/transport codes
        $map = [
            'http_request_failed' => 'The HTTP request failed to complete.',
            'connect_timeout'     => 'Connection timed out while reaching the server.',
            'operation_timedout'  => 'The server did not respond in time.',
            'couldnt_connect'     => 'Could not connect to the host.',
            'couldnt_resolve_host'=> 'Could not resolve the host name.',
            'ssl_connect_error'   => 'SSL/TLS handshake failed.',
            'ssl_cacert_badfile'  => 'SSL certificate problem.',
            'too_many_redirects'  => 'Too many redirects were encountered.',
            'timeout'             => 'The operation timed out.',
            'dns_lookup_failed'   => 'DNS lookup failed.',
            'disallowed_host'     => 'This host is not allowed by site settings.',
        ];

        if ( isset( $map[ $code ] ) ) {
            return $map[ $code ];
        }

        // Fallback to message if available
        if ( $message !== '' ) {
            return $message;
        }

        return 'The request failed.';
    }
}



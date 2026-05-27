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
 * Validation helpers for webhook configuration integrity.
 */
class TD_Webhooks_Validator {
    /**
     * Validate entire webhook structure.
     *
     * @param array $webhook
     * @return bool
     */
    public static function validate_webhook( array $webhook ): bool {
        // Validate URL, HTTP method, and mapping arrays
        return self::validate_url( (string) ( $webhook['url'] ?? '' ) )
            && self::validate_method( (string) ( $webhook['method'] ?? 'post' ) )
            && self::validate_mapping( (array) ( $webhook['headers'] ?? [] ) )
            && self::validate_mapping( (array) ( $webhook['body_mapping'] ?? [] ) );
    }

    /**
     * Validate URL to be http(s) and have a host.
     *
     * @param string $url
     * @return bool
     */
    public static function validate_url( string $url ): bool {
        if ( empty( $url ) ) {
            return false;
        }

        $parts = wp_parse_url( $url );

        if ( empty( $parts['scheme'] ) || ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
            return false;
        }

        if ( empty( $parts['host'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Validate supported HTTP method.
     *
     * @param string $method
     * @return bool
     */
    public static function validate_method( string $method ): bool {
        return in_array( strtolower( $method ), [ 'get', 'post', 'put', 'patch', 'delete' ], true );
    }

    /**
     * Validate mapping rows contain non-empty keys.
     *
     * @param array $mapping
     * @return bool
     */
    public static function validate_mapping( array $mapping ): bool {
        foreach ( $mapping as $row ) {
            if ( empty( $row['key'] ) ) {
                return false;
            }
        }

        return true;
    }
}


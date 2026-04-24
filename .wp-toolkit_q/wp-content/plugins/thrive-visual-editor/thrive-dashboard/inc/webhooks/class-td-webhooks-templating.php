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
 * Simple templating and payload shaping for webhook requests.
 */
class TD_Webhooks_Templating {
    /**
     * JSON encode options: no escaped slashes or unicode.
     *
     * @var int
     */
    const JSON_OPTIONS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Build request payload from mapping and context
     *
     * @param array $mapping [ [ 'key' => 'user[name]', 'value' => '{{data.name}}' ], ... ]
     * @param array $context Context containing 'data', 'user', etc.
     *
     * @return array
     */
    public static function build_payload( array $mapping, array $context ): array {
        $fields = [];

        foreach ( $mapping as $field ) {
            // If the key is not set or is empty, skip.
            if ( empty( $field['key'] ) ) {
                continue;
            }

            $original_key = str_replace( ']', '', (string) $field['key'] );
            $reference    = &$fields;

            // Navigate through the fields using the original key.
            foreach ( explode( '[', $original_key ) as $key ) {

                // If the key is empty, skip.
                if ( $key === '' ) {
                    continue;
                }

                // If the key does not exist, create an empty array.
                if ( ! array_key_exists( $key, $reference ) ) {
                    $reference[ $key ] = [];
                }

                $reference = &$reference[ $key ];
            }

            $value = $field['value'] ?? '';

            // If value starts and ends with %, set it to empty.
            // %FIELD% is used by automator, we are not using it.
            if ( is_string( $value ) && preg_match( '/^%.*%$/', $value ) ) {
                $value = '';
            }

            // If value is a single placeholder, resolve raw so arrays remain arrays (for checkboxes / multi-select)
            if ( is_string( $value ) && preg_match( '/^\s*\{\{\s*([^}]+)\s*\}\}\s*$/', $value, $matches ) ) {
                $value = self::get_by_path( $context, trim( $matches[1] ) ) ?? '';
            } else {
                // Resolve the placeholders.
                $value = self::resolve_placeholders( $value, $context );
            }

            $reference = $value;
            unset( $reference );
        }

        return $fields;
    }

    /**
     * Resolve {{path}} placeholders using dot-notation into the provided context.
     * If value is not a string, return as-is.
     *
     * @param mixed $value
     * @param array $context
     *
     * @return mixed
     */
    public static function resolve_placeholders( $value, array $context ) {
        if ( ! is_string( $value ) ) {
            return $value;
        }

        // Replace {{ path }} placeholders using a callback
        $callback = function( $matches ) use ( &$context ) {
            return self::placeholder_replace_callback( $matches, $context );
        };

        return preg_replace_callback( '/\{\{\s*([^}]+)\s*\}\}/', $callback, $value );
    }

    /**
     * Callback used by resolve_placeholders to replace a single {{ path }} match.
     *
     * @param array $matches
     * @param array $context
     * @return string
     */
    private static function placeholder_replace_callback( array $matches, array $context ): string {
        $path  = trim( $matches[1] ?? '' );
        $found = self::get_by_path( $context, $path );

        if ( $found === null ) {
            return '';
        }

        if ( is_scalar( $found ) ) {
            return (string) $found;
        }

        return self::json_encode( $found );
    }

    /**
     * Get value from nested array/object using dot notation (e.g., data.email, user.email)
     *
     * @param array $source
     * @param string $path
     *
     * @return mixed|null
     */
    public static function get_by_path( array $source, string $path ) {
        // Split the dot-notation path into sanitized, non-empty segments
        // a.b.c -> [ 'a', 'b', 'c' ]
        $segments = array_filter( array_map( 'trim', explode( '.', $path ) ), static function( $s ) { return $s !== ''; } );
        $cursor = $source;

        foreach ( $segments as $segment ) {
            if ( is_array( $cursor ) ) {
                $resolved = self::resolve_array_segment( $cursor, $segment );

                if ( $resolved === null ) {
                    return null;
                }

                $cursor = $resolved;
            } elseif ( is_object( $cursor ) && isset( $cursor->$segment ) ) {
                $cursor = $cursor->$segment;
            } else {
                return null;
            }
        }

        // Return the resolved value after traversing the full path
        return $cursor;
    }

    /**
     * Attempt to resolve an array segment, accounting for PHP checkbox name conventions (foo vs foo[]).
     *
     * @param array  $cursor Current array level being traversed
     * @param string $seg    Segment name
     *
     * @return mixed|null Returns the resolved value or null if not found
     */
    private static function resolve_array_segment( array $cursor, string $seg ) {
        if ( array_key_exists( $seg, $cursor ) ) {
            return $cursor[ $seg ];
        }

        // Normalize checkbox-style names: allow foo[] vs foo
        $alt = ( substr( $seg, -2 ) === '[]' ) ? substr( $seg, 0, -2 ) : ( $seg . '[]' );
        // If the alternate checkbox-style key exists, prefer it
        if ( array_key_exists( $alt, $cursor ) ) {
            return $cursor[ $alt ];
        }

        return null;
    }

    /**
     * JSON encode data with webhook-friendly options.
     *
     * Encodes without escaping slashes or unicode characters,
     * making URLs and international text more readable.
     *
     * @param mixed $data Data to encode
     * @return string JSON string
     */
    public static function json_encode( $data ): string {
        $json = wp_json_encode( $data, self::JSON_OPTIONS );
        return ($json === false) ? '' : $json;
    }
}

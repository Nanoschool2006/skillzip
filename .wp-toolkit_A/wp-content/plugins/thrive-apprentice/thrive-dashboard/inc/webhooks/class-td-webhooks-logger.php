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
 * Lightweight logger for per-webhook execution entries stored in options.
 *
 * Option shape (td_webhooks_logs):
 * - [ webhook_id => [ entry, entry, ... ] ] with newest entries first
 * - entry: { timestamp, webhook_id, status_code, duration_ms, request, response, trigger_when, ... }
 */
class TD_Webhooks_Logger {
    /**
     * Append a log entry for a webhook id with retention and TTL enforcement.
     *
     * @param int   $webhook_id
     * @param array $entry { status_code, duration_ms, request, response, trigger_when, ... }
     * @return void
     */
    public static function log( int $webhook_id, array $entry ): void {
        // Load bucket storage
        $all = get_option( 'td_webhooks_logs', [] );
        if ( ! is_array( $all ) ) {
            $all = [];
        }

        // Normalize entry structure
        $entry['timestamp']  = current_time( 'mysql' );
        $entry['webhook_id'] = $webhook_id;
        $entry['request']    = isset( $entry['request'] ) && is_array( $entry['request'] ) ? $entry['request'] : [];
        $entry['response']   = isset( $entry['response'] ) && is_array( $entry['response'] ) ? $entry['response'] : [];

        // Ensure bucket for this webhook
        if ( empty( $all[ $webhook_id ] ) || ! is_array( $all[ $webhook_id ] ) ) {
            $all[ $webhook_id ] = [];
        }

        // Prepend new entry (newest first)
        array_unshift( $all[ $webhook_id ], $entry );

        // Retention: keep only the newest N
        $limit = (int) TD_Webhooks_Settings::get( 'retention_per_id', 100 );
        if ( $limit > 0 && count( $all[ $webhook_id ] ) > $limit ) {
            $all[ $webhook_id ] = array_slice( $all[ $webhook_id ], 0, $limit );
        }

        // TTL: drop entries older than N days
        $ttl_days = (int) TD_Webhooks_Settings::get( 'ttl_days', 0 );
        if ( $ttl_days > 0 ) {
            $cutoff = strtotime( '-' . $ttl_days . ' days' );
            $all[ $webhook_id ] = self::filter_recent_by_cutoff( $all[ $webhook_id ], $cutoff );
        }

        // Persist storage
        update_option( 'td_webhooks_logs', $all, 'no' );
    }

    /**
     * Return only rows newer than the provided cutoff timestamp.
     *
     * @param array $bucket
     * @param int   $cutoff Unix timestamp cutoff
     * @return array
     */
    private static function filter_recent_by_cutoff( array $bucket, int $cutoff ): array {
        $kept = [];

        foreach ( $bucket as $row ) {
            $timestamp = isset( $row['timestamp'] ) ? strtotime( (string) $row['timestamp'] ) : time();

            // If the timestamp is newer than the cutoff, add the row to the kept array.
            if ( $timestamp >= $cutoff ) {
                $kept[] = $row;
            }
        }

        // Reset the array keys.
        return array_values( $kept );
    }
}

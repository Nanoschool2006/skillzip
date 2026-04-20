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
 * Settings helper for Webhooks module.
 *
 * - Returns effective settings from a single method
 * - Allows overrides via a single filter
 */
class TD_Webhooks_Settings {
    /**
     * Get effective settings (defaults + filter only).
     *
     * @return array
     */
    public static function get( $key = null, $fallback = null ) {
        // Base values; callers can override via the filter below
        $settings = [
            'timeout'          => 8,
            'retention_per_id' => 100,
            'ttl_days'         => 0,
            'allowlist'        => [],
            'denylist'         => [],
        ];

        /**
         * Filter effective Webhooks settings derived from base values.
         *
         * @param array $settings
         */
        $settings = (array) apply_filters( 'td_webhooks_settings', $settings );

        if ( $key === null ) {
            return $settings;
        }

        return array_key_exists( $key, $settings ) ? $settings[ $key ] : $fallback;
    }
}



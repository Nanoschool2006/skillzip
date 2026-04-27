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
 * Storage layer for Webhooks based on a private CPT.
 *
 * Persists webhook configuration in post meta using the `td_webhook` post type.
 */
class TD_Webhooks_Repository {
    const POST_TYPE = 'td_webhook';

    /**
     * Meta key prefix used for all webhook meta fields.
     */
    private const META_PREFIX = 'td_webhook_';

    /**
     * List of meta field names (without prefix).
     * Used for both reading and writing post meta.
     *
     * @var string[]
     */
    private static $META_FIELDS = [
        'enabled',
        'url',
        'method',
        'request_format',
        'headers',
        'body_mapping',
        'trigger_when',
        'consent_required',
        'targeting',
        'advanced',
    ];

    /**
     * Build a full meta key from a field name.
     */
    private static function meta_key( string $field ): string {
        return self::META_PREFIX . $field;
    }

    /**
     * Create a webhook and return its post ID.
     *
     * @param array $data
     * @return int 0 on failure
     */
    public static function create( array $data ): int {
        // Prepare post array for the private CPT
        $postarr = [
            'post_type'   => self::POST_TYPE,
            'post_status' => 'publish',
            'post_title'  => sanitize_text_field( $data['name'] ?? '' ),
        ];

        $post_id = wp_insert_post( $postarr );

        // Bail out on error
        if ( is_wp_error( $post_id ) || empty( $post_id ) ) {
            return 0;
        }

        // Persist meta fields
        self::save_meta( $post_id, $data );

        return (int) $post_id;
    }

    /**
     * Read a webhook by post ID.
     *
     * @param int $post_id
     * @return array Empty array if not found
     */
    public static function read( int $post_id ): array {
        $post = get_post( $post_id );

        // Return empty if not a webhook CPT
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return [];
        }

        // Merge basic fields with meta
        return array_merge(
            [
                'id'   => $post->ID,
                'name' => $post->post_title,
            ],
            self::get_meta( $post->ID )
        );
    }

    /**
     * Update a webhook by post ID.
     *
     * @param int   $post_id
     * @param array $data
     * @return bool False if not a webhook
     */
    public static function update( int $post_id, array $data ): bool {
        $post = get_post( $post_id );

        // Ensure the post is a webhook CPT
        if ( ! $post || $post->post_type !== self::POST_TYPE ) {
            return false;
        }

        // Only the name is stored in the post title. Other fields are stored in post meta.
        if ( isset( $data['name'] ) ) {
            wp_update_post( [ 'ID' => $post_id, 'post_title' => sanitize_text_field( $data['name'] ) ] );
        }

        // Persist meta fields
        self::save_meta( $post_id, $data );

        return true;
    }

    /**
     * Delete a webhook by post ID.
     *
     * @param int $post_id
     * @return bool
     */
    public static function delete( int $post_id ): bool {
        // Validate CPT type before delete
        if ( get_post_type( $post_id ) !== self::POST_TYPE ) {
            return false;
        }

        wp_delete_post( $post_id, true );

        return true;
    }

    /**
     * List webhooks filtered by optional WP_Query args.
     *
     * @param array $args
     * @return array[]
     */
    public static function list( array $args = [] ): array {
        // Compose query args
        $parsed_args = array_merge(
            [
                'post_type'      => self::POST_TYPE,
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'fields'         => 'ids',
            ],
            $args
        );

        $query = new \WP_Query( $parsed_args );

        $items = [];

        // Loop through results and map to stored structure
        foreach ( $query->posts as $post_id ) {
            $items[] = self::read( (int) $post_id );
        }

        return $items;
    }

    /**
     * Persist webhook meta fields.
     *
     * @param int   $post_id
     * @param array $data
     * @return void
     */
    private static function save_meta( int $post_id, array $data ) {
        // Update only provided fields; keep others untouched
        foreach ( self::$META_FIELDS as $field ) {
            if ( array_key_exists( $field, $data ) ) {
                update_post_meta( $post_id, self::meta_key( $field ), $data[ $field ] );
            }
        }
    }

    /**
     * Retrieve all webhook meta as a normalized array keyed by field name.
     *
     * @param int $post_id
     * @return array
     */
    private static function get_meta( int $post_id ): array {
        $out = [];
        foreach ( self::$META_FIELDS as $field ) {
            $out[ $field ] = get_post_meta( $post_id, self::meta_key( $field ), true );
        }
        return $out;
    }
}


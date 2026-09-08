<?php
/**
 * Thrive Themes - https://thrivethemes.com
 * 
 * @package thrive-dashboard
 */

namespace Thrive_Dashboard\Font_Library;

use WP_REST_Response;

/**
 * Class Endpoint
 * 
 * This class handles the REST API endpoints for the font library.
 */
class Endpoint {
    private $strategy;

    /**
     * Font_Library_Endpoint constructor.
     *
     * @param object $strategy The font strategy to use.
     */
    public function __construct( $strategy ) {
        $this->strategy = $strategy;
    }

    /**
     * Initializes the REST API endpoints.
     */
    public function init() {
        add_action('rest_api_init', function () {
            register_rest_route('theme/v1', '/fonts', array(
                'methods' => 'GET',
                'callback' => [ $this, 'get_fonts' ],
                'permission_callback' => function () {
                    return current_user_can( 'edit_posts' );
                },
            ));
        
            register_rest_route('theme/v1', '/fonts', array(
                'methods' => 'POST',
                'callback' => [ $this, 'set_fonts' ],
                'permission_callback' => function () {
                    return current_user_can('edit_posts');
                },
            ));
        
        });

        // Since init hook is already called, we can directly call the function to set font capabilities.
        $this->set_font_capabilities();
    }

    /**
     * Sets the capabilities for the font post type.
     * Unregisters the 'wp_font_family' post type and re-registers it with modified capabilities.
     * This is necessary to ensure that anyone who can edit posts can read the installed fonts.
     */
    private function set_font_capabilities() {
        global $wp_post_types;

        // Ensure the post type is registered before modifying
        if ( ! isset( $wp_post_types['wp_font_family'] ) ) {
            return;
        }
        // Get the existing args of 'post'
        $post_args = (array) $wp_post_types['wp_font_family'];

        // Unregister 'post' before re-registering
        unregister_post_type('wp_font_family');

        // Modify capabilities
        $post_args['cap']->read = 'edit_posts';

        // Re-register the post with modified args
        register_post_type('wp_font_family', $post_args);
    }

    /**
     * Retrieves the fonts using the current strategy.
     *
     * @return WP_REST_Response The response containing the fonts.
     */
    public function get_fonts() {
        $fonts = $this->strategy->get_fonts();
        return new WP_REST_Response( array_values( $fonts ), 200 );
    }

    /**
     * Sets the fonts using the current strategy.
     *
     * @param WP_REST_Request $request The request containing the fonts to set.
     * @return WP_REST_Response The response after setting the fonts.
     */
    public function set_fonts( $request ) {
        $fonts = $request['fonts'];

        if ( ! is_array( $fonts ) ) {
            return new WP_REST_Response( 'Invalid request', 400 );
        }
        
        // Merge the existing fonts with the new fonts. 
        // Make sure they're unique by the slug key and 
        // the current fonts will override the existing ones.
        $existing_fonts = $this->strategy->get_fonts();
        $fonts          = array_merge( $existing_fonts, $fonts );

        // Remove duplicate fonts, empty fonts and uninstalled fonts.
        $fonts  = $this->filter_duplicate_fonts( $fonts );
        $fonts  = $this->filter_empty_fonts( $fonts );
        $fonts  = $this->filter_uninstalled_fonts( $fonts );

        $result = $this->strategy->set_fonts( $fonts );
        return new WP_REST_Response( $result, 200 );
    }

    /**
     * Filters out fonts that are not installed on the server.
     * This could happen when a font is deleted when the theme is not active.
     * 
     * @param array $fonts The fonts to filter.
     * 
     * @return array The filtered fonts.
     */
    private function filter_uninstalled_fonts( $fonts ) {
        $installed_slugs = $this->get_installed_fonts();
    
        $fonts = array_filter( $fonts, function( $font ) use ( $installed_slugs ) {
            return in_array( $font['slug'], $installed_slugs );
        } );

        return array_values( $fonts );
    }

    /**
     * Retrieves the installed fonts.
     * 
     * @return array The installed font slugs.
     */
    private function get_installed_fonts() {
        $page = 1;
        $per_page = 100;
        $slugs = [];

        do {
            $installed_fonts = tve_send_wp_rest_request( '/wp/v2/font-families', [ 'per_page' => $per_page, 'page' => $page ] );
    
            $slugs = array_merge(
                $slugs,
                array_map( function( $font ) {
                    return $font['font_family_settings']['slug'] ?? '';
                }, $installed_fonts )
            );

            $page++;

            // Just a safety measure to avoid infinite loops. This should never happen.
            if ( $page > 10 ) {
                break;
            }
        } while ( count( $installed_fonts ) === $per_page ); // If the count is less than the per_page, we've reached the end.

        return $slugs;
    }

    /**
     * Filters out fonts with empty fontFaces.
     * 
     * @param array $fonts The fonts to filter.
     * 
     * @return array The filtered fonts.
     */
    private function filter_empty_fonts( $fonts ) {
        $fonts = array_filter( $fonts, function( $font ) {
            return ! empty( $font[ 'fontFace' ] );
        } );

        return array_values( $fonts );
    }

    /**
     * Filters out duplicate fonts.
     * 
     * @param array $fonts The fonts to filter.
     * 
     * @return array The filtered fonts.
     */
    private function filter_duplicate_fonts( $fonts ) {
        $fonts = array_reduce( $fonts, function( $acc, $font ) {
            $acc[ $font['slug'] ] = $font;
            return $acc;
        }, [] );

        return array_values( $fonts );
    } 
}

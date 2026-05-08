<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace Thrive_Dashboard\Font_Library;

/**
 * Class Main
 *
 * This class handles the font library functionality.
 * It initializes the appropriate strategy based on the theme's support for theme.json
 * and sets up REST API routes and admin scripts.
 */
class Main {
    /**
     * The admin class.
     * 
     * @var Admin
     */
    private $admin;
    
    /**
     * The public endpoint class.
     * 
     * @var Endpoint
     */
    public $endpoint;

    /**
     * Initializes the font library with a given strategy.
     *
     * @param object $strategy The font strategy to use.
     */
    public function init() {

        // Modules.
        $this->admin = new Admin();
        $this->admin->init();

        if ( ! self::is_wordpress_version_supported() ) {
            return;
        }

        $this->endpoint = new Endpoint( $this->get_font_storage_strategy() );
        $this->endpoint->init();

        // Actions.
        add_action( 'wp_head', [ $this, 'define_font_sources' ] );
    }

    /**
     * Retrieves the font storage strategy based on the theme's support for theme.json.
     * If the theme supports theme.json, the REST API strategy is used, otherwise the theme mod strategy is used.
     * 
     * @return object The font storage strategy.
     */
    public function get_font_storage_strategy() {
        static $strategy = null;

        if ( null !== $strategy ) {
            return $strategy;
        }

        if ( function_exists( 'wp_theme_has_theme_json' ) && wp_theme_has_theme_json() ) {
            $strategy = new Rest_Api_Font_Strategy();
        } else {
            $strategy = new Theme_Mod_Font_Strategy();
        }

        return $strategy;
    }

    /**
     * Defines the Font library font sources in the head of the document.
     * 
     * @return void
     */
    public function define_font_sources() {
        $fonts = $this->get_font_storage_strategy()->get_fonts();
        
        if ( empty( $fonts ) ) {
            return;
        }

        // Generate font face rules for all the fonts into a single string.
        $font_faces = array_reduce( $fonts, function( $styles, $font ) {
            return $styles . $this->generate_font_face_rules( $font['fontFace'] );
        }, '' );

        // Enqueue the generated CSS as inline styles.
        wp_register_style('thrive-font-library-fonts', false);
        wp_enqueue_style('thrive-font-library-fonts');
        wp_add_inline_style('thrive-font-library-fonts', $font_faces);
    }

    /**
     * Generates CSS font-face rules for given font faces
     *
     * @param array $font_faces Array of font face definitions
     * @return string Generated CSS rules
     */
    private function generate_font_face_rules( $font_faces ) {
        return array_reduce( 
            $font_faces, 
            function( $rules, $face ) {
                return $rules . sprintf(
                    '@font-face{font-family:%s;font-style:%s;font-weight:%s;src:url("%s");}',
                    $face['fontFamily'],
                    $face['fontStyle'],
                    $face['fontWeight'],
                    $face['src']
                );
            },
            ''
        );
    }

    /**
     * Checks if the current WordPress version is supported.
     * The WP Core feature was introduced in 6.5.
     *
     * @see https://make.wordpress.org/core/2024/03/14/new-feature-font-library/
     *
     * @return bool True if the version is supported, false otherwise.
     */
    public static function is_wordpress_version_supported() {
        global $wp_version;
        return version_compare( $wp_version, '6.5', '>=' );
    }
}

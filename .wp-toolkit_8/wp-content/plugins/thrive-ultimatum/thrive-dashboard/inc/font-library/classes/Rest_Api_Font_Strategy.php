<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace Thrive_Dashboard\Font_Library;

use Thrive_Dashboard\Font_Library\Interfaces\Font_Strategy;

class Rest_Api_Font_Strategy implements Font_Strategy {

    /**
     * The theme mod font strategy.
     *
     * This will be used to get the fonts on the frontend
     * when we don't have access to the REST API. This also doesn't
     * provide access to any details other than the fonts list.
     * 
     * @var Theme_Mod_Font_Strategy
     */
    private $theme_mod_cache;

    public function __construct() {
        $this->theme_mod_cache = new Theme_Mod_Font_Strategy();
    }

    /**
     * Retrieves the fonts using the REST API.
     *
     * @return array The fonts.
     */
    public function get_fonts() {
        $global_styles = $this->get_global_styles();

        if ( is_wp_error( $global_styles ) || empty( $global_styles['settings'] ) || ! is_array( $global_styles['settings'] ) ) {
            return $this->theme_mod_cache->get_fonts();
        }

        $fonts = $global_styles['settings']['typography']['fontFamilies']['custom'] ?? [];

        return $fonts;
    }

    /**
     * Sets the fonts using the REST API.
     *
     * @param array $fonts The fonts to set.
     *
     * @return array The updated global styles.
     */
    public function set_fonts( $fonts ) {
        $global_styles = $this->get_global_styles();
        
        if ( ! is_array( $global_styles['settings'] ) || empty( $global_styles['settings'] ) ) {
            $global_styles['settings'] = [];
        }

        $global_styles['settings']['typography']['fontFamilies']['custom'] = $fonts;

        $this->set_global_styles( $global_styles );
        $this->theme_mod_cache->set_fonts( $fonts );

        return $global_styles;
    }

    /**
     * Retrieves the global styles endpoint.
     * 
     * @return string|null The global styles endpoint.
     */
    private function get_global_styles_endpoint() {
        $themes = tve_send_wp_rest_request( '/wp/v2/themes' );

        if ( is_wp_error( $themes ) ) {
            return null;
        }

        $active_theme = array_filter($themes, function($theme) {
            return $theme['status'] === 'active';
        });

        $active_theme = reset($active_theme);
        $global_styles_link = isset($active_theme['_links']['wp:user-global-styles'][0]['href']) ? $active_theme['_links']['wp:user-global-styles'][0]['href'] : null;

        return $global_styles_link;
    }

    /**
     * Retrieves the global styles.
     * 
     * @return array The global styles.
     */
    private function get_global_styles() {
        $global_styles_link = $this->get_global_styles_endpoint();

        if ( empty( $global_styles_link ) ) {
            return [];
        }

        return tve_send_wp_rest_request( $global_styles_link );
    }

    /**
     * Sets the global styles.
     * 
     * @param array $global_styles The global styles to set.
     * 
     * @return array The updated global styles.
     */
    private function set_global_styles( $global_styles ) {
        $global_styles_link = $this->get_global_styles_endpoint();

        if ( empty( $global_styles_link ) ) {
            return false;
        }

        return tve_send_wp_rest_request( $global_styles_link, $global_styles, 'POST' );
    }
}
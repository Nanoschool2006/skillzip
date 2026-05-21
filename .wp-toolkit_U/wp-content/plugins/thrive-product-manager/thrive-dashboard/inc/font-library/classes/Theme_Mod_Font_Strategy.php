<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace Thrive_Dashboard\Font_Library;

use Thrive_Dashboard\Font_Library\Interfaces\Font_Strategy;

class Theme_Mod_Font_Strategy implements Font_Strategy {

    const THEME_MOD_KEY = 'thrive_active_fonts';

    /**
     * Retrieves the fonts using the theme mod.
     * 
     * @return array The fonts.
     */
    public function get_fonts() {
        $fonts = get_theme_mod( self::THEME_MOD_KEY, [] );
        return $fonts;
    }

    /**
     * Sets the fonts using the theme mod.
     * 
     * @param array $fonts The fonts to set.
     * 
     * @return array The updated fonts.
     */
    public function set_fonts( $fonts ) {
        return set_theme_mod( self::THEME_MOD_KEY, $fonts );
    }
}
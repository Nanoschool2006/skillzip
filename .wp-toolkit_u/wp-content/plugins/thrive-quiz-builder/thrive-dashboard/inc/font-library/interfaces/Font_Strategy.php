<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace Thrive_Dashboard\Font_Library\Interfaces;

interface Font_Strategy {

    /**
     * Retrieves the fonts.
     *
     * @return array The fonts.
     */
    public function get_fonts();
    
    /**
     * Sets the fonts.
     *
     * @param array $fonts The fonts to set.
     *
     * @return array The updated fonts.
     */
    public function set_fonts( $fonts );
}
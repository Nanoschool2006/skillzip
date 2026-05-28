<?php
/**
 * Thrive Themes - https://thrivethemes.com
 * 
 * @package thrive-dashboard
 */


namespace Thrive_Dashboard\Font_Library;

/**
 * Class Admin
 * 
 * This class handles the admin functionality for the font library.
 */
class Admin {
    const SLUG = 'tve_dash_font_library';
    const SCREEN = 'admin_page_' . self::SLUG;

    /**
     * Initializes the admin functionality.
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
    }

    /**
     * Returns the URL for the font library admin page.
     *
     * @return string The URL for the font library admin page.
     */
    public static function get_url () {
        return admin_url( 'admin.php?page=' . self::SLUG );
    }

    /**
     * Enqueues admin scripts and styles for the font library.
     *
     * @param string $screen The current admin screen.
     */
    public function admin_enqueue_scripts( $screen ) {
        if ( self::SCREEN !== $screen ) {
            return;
        }

        if ( ! Main::is_wordpress_version_supported() ) {
            return;
        }

        tve_dash_enqueue_script( 'font-library-app-script', TVE_DASH_URL . '/assets/dist/js/font-library.js', [], TVE_DASH_VERSION, true );

        $this->enqueue_with_increased_specificity( TVE_DASH_PATH . '/assets/dist/css/font-library.css' );
    }

    /**
     * Processes the CSS file for the font library. This increases specificity of all rules. 
     * This is necessary because we don't want to add unnecessary !important declarations
     * or chain multiple classes within the CSS file. The conflicting styles are 
     * from thrive-dashboard/css/sass/materialize/_typography.scss. This logic 
     * can be removed once the Materialize CSS is removed from the plugin.
     * This can be extracted to a global method if needed in the future.
     *
     * @param string $css_path The path to the CSS file.
     * @return string The processed CSS.
     */
    private function enqueue_with_increased_specificity( $css_path ) {
        $css = file_get_contents( $css_path );

        // Extract :root{} rule
        preg_match('/:root\s*{[^}]*}/', $css, $root_matches);
        $root_css = isset($root_matches[0]) ? $root_matches[0] : '';

        // Remove :root{} rule from the original CSS
        $css = preg_replace('/:root\s*{[^}]*}/', '', $css);

        // Wrap the remaining CSS in #wpbody {}
        // We want the increased specificity, while keeping the CSS code clean.
        $css = $root_css . "\n#wpbody {\n" . $css . "\n}";

        wp_register_style( 'font-library-app-css', false );
        wp_enqueue_style( 'font-library-app-css' );

        wp_add_inline_style( 'font-library-app-css', $css );
    }

    /**
     * Includes the font library template.
     */
    public static function get_template() {
        require_once( TVE_DASH_PATH . '/inc/font-library/templates/font-library.php' );
    }
}
<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

require_once 'Tve_Dash_Growth_Tools_Config.php';

if ( ! class_exists( 'Tve_Dash_Growth_Tools' ) ) {
    /**
     * Class Tve_Dash_Growth_Tools
     */
    class Tve_Dash_Growth_Tools extends Tve_Dash_Growth_Tools_Config {
        protected $namespace = 'tve-dash/v1';

        /**
         * Instance of the class.
         *
         * @var null
         */
        protected static $instance = null;

        /**
         * Categories.
         *
         * @var array
         */
        protected $categories = [];

        /**
         * Tools.
         *
         * @var array
         */
        protected $tools = [];

        /**
         * Data.
         *
         * @var array
         */
        protected $data = [];

        /**
         * View path.
         *
         * @var string
         */
        protected $view_path = '';

        /**
         * Get an instance of the class.
         *
         * @return Tve_Dash_Growth_Tools|null The instance of the class, or null if not instantiated yet.
         */
        public static function instance() {
            if ( static::$instance === null ) {
                static::$instance = new Tve_Dash_Growth_Tools();
            }

            return static::$instance;
        }

        /**
         * Constructor.
         */
        public function __construct() {
            if ( is_TPM_installed() ) {
                require_once 'TPM_License_Checker_And_Activation_Helper.php';
            }

            $this->view_path = dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
            $this->categories = $this->categories();
            $this->tools = $this->get_tools_order_by_category();
            $this->load_dependencies();
            $this->get_tools_states( $this->tools );
        }

        /**
         * Dashboard.
         */
        public function dashboard() {
            $this->enqueue();
            $this->render( 'dashboard' );
        }

        /**
         * Get tools states.
         *
         * @param array $tools_list Tools list.
         */
        private function get_tools_states( $tools_list ) {
            foreach ( $this->categories as $category ) {
                $tools = $tools_list[ $category ];

                foreach ( $tools as $key => $tool ) {
                    $tools[ $key ]['status'] = $this->get_state( $tool['path'] );

                    // Maybe pro or thrive product
                    if ( $this->maybe_pro_or_thrive_product( $tools[ $key ] ) ) {
                        if ( is_TPM_installed() ) {
                            // Product is purchased user can install
                            if ( TPM_License_Checker_And_Activation_Helper::is_product_purchased( $tool['plugin_slug'] ) ) {
                                $tools[ $key ]['status'] = 'Not installed';
                            } else {
                                $tools[ $key ]['status'] = 'Learn More';
                            }
                        } else {
                            $tools[ $key ]['status'] = 'Learn More';
                        }
                    }
                }
                $tools_list[ $category ] = $tools;
            }

            $this->tools = $tools_list;
        }

        /**
         * Check if the given tool is a Pro or Thrive Product.
         *
         * This function determines whether the provided tool is a Pro or Thrive Product based on its status and availability.
         *
         * @param array $tool The tool data containing information about the tool.
         *                    Should include at least 'path', 'status', and 'available_at_org' keys.
         * @return bool True if the tool is a Pro or Thrive Product, false otherwise.
         */
        private function maybe_pro_or_thrive_product( $tool ) {
            // If the tool path is empty, it cannot be a Pro or Thrive Product.
            if ( empty( $tool['path'] ) ) {
                return false;
            }

            // If the tool status is neither 'Installed' nor 'Activated' and it's not available at the organization, it may be a Pro or Thrive Product.
            if ( ! in_array( $tool['status'], [ 'Installed', 'Activated' ], true ) && ! $tool['available_at_org'] ) {
                return true;
            }

            // If the above conditions are not met, the tool is not considered a Pro or Thrive Product.
            return false;
        }

        /**
         * Get the state of a plugin.
         *
         * @param string $plugin_file The path to the main plugin file.
         * @return string The state of the plugin.
         */
        private function get_state( $plugin_file ) {
            // If the plugin file path is empty, return 'Learn More'.
            if ( empty( $plugin_file ) ) {
                return 'Learn More';
            }

            // Check if the plugin is active.
            if ( is_plugin_active( $plugin_file ) ) {
                return 'Activated';
            }

            // Check if the plugin file exists.
            if ( file_exists( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin_file ) ) {
                return 'Installed';
            }

            // If the plugin is neither active nor inactive, return 'Not installed'.
            return 'Not installed';
        }

        /**
         * Filter tools by category.
         *
         * @param string $category   The category name to filter.
         * @param array  $categories The array of categories with tools.
         * @return array The filtered array of categories with tools.
         */
        protected function filter_tools_by_category( $category, $tools_by_categories ) {
            // If no specific category is provided, return all categories
            if ( empty( $category ) || 'All Tools' === $category ) {
                return $tools_by_categories;
            }

            // Filter categories based on the provided category name
            foreach ( $tools_by_categories as $cat => $tools ) {
                if ( $category === $cat ) {
                    return array(
                        $category => $tools,
                    );
                }
            }

            // Return an empty array if no matching category is found
            return [];
        }

        public function filter_tools_by_category_query( $filter_category, $search_query ) {
            $tools = $this->filter_tools_by_category( $filter_category, $this->tools );
            $this->filter_tools_by_query($tools, $search_query );

            return $tools;
        }

        /**
         * Filter tools by search query.
         *
         * @param array  $tools      The array of tools.
         * @param string $searchText The search text.
         */
        protected function filter_tools_by_query( &$tools, $searchText ) {
            // Convert search text to lowercase for case-insensitive search
            $searchTextLower = strtolower( $searchText );

            if ( empty( $searchTextLower ) ) {
                return ;
            }

            // Filter array tools
            foreach ( $tools as $cat_key => &$category ) {
                // Ensure category is represented as an array
                $category = is_array( $category ) ? $category : [ $category ];

                // Filter category's tools
                $category = array_filter( $category, function ( $tool ) use ( $searchTextLower ) {
                    // Convert tool values to lowercase for case-insensitive search
                    $nameLower = strtolower( $tool['name'] );
                    $summaryLower = strtolower( $tool['summary'] );

                    // Check if any of the tool's values contain the search text
                    return strpos( $nameLower, $searchTextLower ) !== false || strpos( $summaryLower, $searchTextLower ) !== false;
                } );
                // Re-index the array to start from zero
                $category = array_values( $category );
                // Remove category if it has no matching tools
                if ( empty( $category ) ) {
                    unset( $tools[ $cat_key ] );
                }
            }
        }
    }
}

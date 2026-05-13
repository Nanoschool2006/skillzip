<?php
/**
 * Controller for managing growth tools via REST API.
 */

if ( ! class_exists( 'Growth_Tools_Controller' ) ) {
    require_once 'Tve_Dash_Growth_Tools.php';
    require_once 'Plugin_Installation_Activation_Handler.php';

    class Growth_Tools_Controller extends Tve_Dash_Growth_Tools {
        /**
         * Registers REST routes for growth tools.
         */
        public function register_routes() {
            register_rest_route( static::REST_NAMESPACE, static::REST_ROUTE, array(
                array(
                    'methods'  => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_tools_list' ),
                    'permission_callback' => '__return_true',
                    'args'     => array(
                        'category' => array(
                            'type'     => 'string',
                            'required' => false,
                        ),
                        'query'    => array(
                            'type'     => 'string',
                            'required' => false,
                        ),
                    ),
                ),
                array(
                    'methods'  => WP_REST_Server::CREATABLE,
                    'callback' => array( $this, 'maybe_install_activate_plugin' ),
                    'permission_callback' => '__return_true',
                    'args'     => array(
                        'plugin_slug' => array(
                            'type'     => 'string',
                            'required' => true,
                        ),
                        'action'      => array(
                            'type'     => 'string',
                            'required' => true,
                        ),
                    ),
                ),
            ) );
        }

        /**
         * Retrieves a list of growth tools.
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response Response object containing the list of growth tools.
         */
        public function get_tools_list( $request ) {
            $filter_category = sanitize_text_field( $request->get_param( 'category' ) );
            $search_query    = sanitize_text_field( $request->get_param( 'query' ) );
            $tools = [];
            if ( ! class_exists('Tve_Dash_Growth_Tools') ) {
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "Tve_Dash_Growth_Tools.php";
            }

            $tools_class = Tve_Dash_Growth_Tools::instance();
            $tools = $tools_class->filter_tools_by_category_query( $filter_category, $search_query );

            return new WP_REST_Response( $tools );
        }

        /**
         * Installs or activates a plugin based on the request.
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return WP_REST_Response Response object containing the installation or activation status.
         */
        public function maybe_install_activate_plugin( $request ) {
            $plugin_slug = sanitize_text_field( $request->get_param( 'plugin_slug' ) );
            $action      = sanitize_text_field( $request->get_param( 'action' ) );

            $installer = new Plugin_Installation_Activation_Handler( $this->tools, $plugin_slug, $action );

            return $installer->handle();
        }
    }
}

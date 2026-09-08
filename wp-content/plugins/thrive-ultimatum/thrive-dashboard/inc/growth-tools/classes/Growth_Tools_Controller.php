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
                    'permission_callback' => array( $this, 'check_permissions' ),
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
                    'permission_callback' => array( $this, 'check_manage_permissions' ),
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
         * Permission check for reading the growth tools list (GET route).
         *
         * Listing the curated catalog is not a privileged action, so this only
         * requires the Thrive Dashboard capability (TVE_DASH_CAPABILITY, i.e.
         * 'tve-use-td', held by administrators and editors). The constant is
         * always defined by the dashboard bootstrap, so the 'edit_theme_options'
         * fallback is a defensive default that is not expected to be reached.
         * Gating this behind install_plugins would lock out dashboard users who
         * are not allowed to install plugins (e.g. non-super-admins on Multisite).
         *
         * The authorization boundary is current_user_can() alone: an
         * unauthenticated request has no user and fails the check. The X-WP-Nonce
         * the dashboard UI sends is CSRF protection for cookie-authenticated
         * callers, not the access gate.
         *
         * @return bool|WP_Error True if the current user can view growth tools, error otherwise.
         */
        public function check_permissions() {
            $capability = defined( 'TVE_DASH_CAPABILITY' ) ? TVE_DASH_CAPABILITY : 'edit_theme_options';
            if ( current_user_can( $capability ) ) {
                return true;
            }

            return new WP_Error(
                'rest_forbidden',
                __( 'You are not allowed to view growth tools.', 'thrive-dash' ),
                array( 'status' => rest_authorization_required_code() )
            );
        }

        /**
         * Permission check for installing/activating growth tools (POST route).
         *
         * This endpoint installs and activates plugins, so it must only ever be
         * reachable by a user who is allowed to manage plugins (install_plugins).
         *
         * The authorization boundary is current_user_can() alone: an
         * unauthenticated request has no user and fails the check. The X-WP-Nonce
         * the dashboard UI sends is CSRF protection for cookie-authenticated
         * callers, not the access gate.
         *
         * @return bool|WP_Error True if the current user can install plugins, error otherwise.
         */
        public function check_manage_permissions() {
            if ( current_user_can( 'install_plugins' ) ) {
                return true;
            }

            return new WP_Error(
                'rest_forbidden',
                __( 'You are not allowed to manage growth tools.', 'thrive-dash' ),
                array( 'status' => rest_authorization_required_code() )
            );
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

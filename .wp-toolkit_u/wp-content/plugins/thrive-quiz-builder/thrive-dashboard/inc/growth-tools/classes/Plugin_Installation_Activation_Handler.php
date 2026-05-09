<?php
/**
 * Plugin Installation and Activation Handler
 *
 * Handles the installation and activation of plugins based on different actions.
 *
 * @package Your_Plugin
 * @subpackage Installation_Activation
 */

if (!defined('ABSPATH')) {
    exit; // Silence is golden!
}

if (!function_exists('wp_install_plugin')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

if (!function_exists('WP_Filesystem')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'TD_Plugin_Installer_Skin.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Thrive_Product_Tags_Helper.php';
class Plugin_Installation_Activation_Handler extends Thrive_Product_Tags_Helper
{
    /**
     * @var array $tools An array of available tools.
     */
    protected $tools;

    /**
     * @var string $plugin_slug The slug of the plugin.
     */
    protected $plugin_slug;

    /**
     * @var string $action The action to be performed.
     */
    protected $action;

    /**
     * @var object|null $plugin_info Information about the plugin.
     */
    protected $plugin_info;

    /**
     * Constructor.
     *
     * @param array  $tools       An array of available tools.
     * @param string $slug        The slug of the plugin.
     * @param string $action      The action to be performed.
     */
    public function __construct( $tools, $slug, $action ) {
        $this->tools = $tools;
        $this->plugin_slug = $slug;
        $this->action = $action;
        $this->plugin_info = $this->get_tool_info_by_slug();
    }

    /**
     * Handles the plugin installation and activation based on the provided action.
     *
     * @return WP_REST_Response The response object.
     */
    public function handle() {
        $url = get_option( 'install_remote_url' . $this->plugin_slug, '' );

        if ( 'install' === $this->action && ! empty( $url ) ) {
            return $this->install_from_url( $url );
        }

        if ( $this->plugin_info->available_at_org ) {
            return $this->handle_product_from_org();
        } else {
            return $this->handle_thrive_product();
        }
    }

    /**
     * Handles the product installation and activation from the WP Org.
     *
     * @return WP_REST_Response The response object.
     */
    private function handle_product_from_org() {
        if ( 'install' === $this->action ) {
            return $this->install_tools_from_org();
        } else {
            return $this->activate_tool_using_path();
        }
    }

    /**
     * Handles the product installation and activation from Thrive Website.
     *
     * @return WP_REST_Response The response object.
     */
    private function handle_thrive_product()
    {
        $tag = $this->get_tag_by_slug($this->plugin_slug);

        if (empty($tag))
        {
            return new \WP_REST_Response(
                array(
                    'message' => 'Invalid plugin submitted to install or activate',
                ),
                500
            );
        }

        if ( !TPM_License_Checker_And_Activation_Helper::is_product_purchased( $tag ) ) {
            return new \WP_REST_Response(
                array(
                    'message' => 'You need to purchase first to install or activate this plugin',
                ),
                500
            );
        }

        if ( 'install' === $this->action ) {
            return $this->install_thrive_product();
        } else {
            return $this->activate_tool_using_path();
        }
    }

    /**
     * Installs plugin from the organization.
     *
     * @return WP_REST_Response The response object.
     */
    public function install_tools_from_org() {
        $plugin_slug = $this->plugin_slug;

        // Check if the plugin is already installed
        if ( is_plugin_inactive( $plugin_slug ) ) {
            // Get plugin information from WordPress.org
            $api = plugins_api(
                'plugin_information',
                array(
                    'slug'   => $plugin_slug,
                    'fields' => array(
                        'sections' => false,
                    ),
                )
            );

            if ( is_wp_error( $api ) ) {
                return $api->get_error_message();
            }

            $url = get_option( 'install_remote_url' . $this->plugin_slug, '' );

            if ( ! empty( $url ) ) {
                update_option( 'install_remote_url' . $this->plugin_slug, $api->download_link );
            } else {
                add_option( 'install_remote_url' . $this->plugin_slug, $api->download_link );
            }

            return new \WP_REST_Response(
                array(
                    'dl'      => true,
                    'success' => 'Plugin download link restored successfully',
                ),
                200
            );
        } else {
            return new \WP_REST_Response(
                array(
                    'message' => 'Plugin is already installed.',
                ),
                500
            );
        }
    }

    /**
     * Installs a plugin from a provided URL.
     *
     * @param string $url The URL of the plugin.
     * @return WP_REST_Response The response object.
     */
    public function install_from_url( $url ) {
        // Install the plugin
        $upgrader = new \Plugin_Upgrader( new TD_Plugin_Installer_Skin() );
        $install_result = $upgrader->install( $url );
        delete_option( 'install_remote_url' . $this->plugin_slug );

        if ( $install_result === true || is_wp_error( $install_result ) ) {
            return new \WP_REST_Response(
                array(
                    'success' => 'Plugin installed successfully',
                ),
                200
            );
        } else {

            return new \WP_REST_Response(
                array(
                    'message' => 'Something went wrong during installation, please try later!',
                ),
                500
            );
        }
    }

    /**
     * Activates a plugin using its path.
     *
     * @return WP_REST_Response The response object.
     */
    public function activate_tool_using_path()
    {
        if ( is_plugin_active( $this->plugin_slug ) ) {
            // Plugin is already activated, return a 400 Bad Request response
            return new \WP_REST_Response(
                array(
                    'message' => 'Plugin is already activated',
                ),
                400
            );
        }

        // Attempt to activate the plugin
        activate_plugin( $this->plugin_info->path );

        // Check if the plugin is now active
        if ( is_plugin_active( $this->plugin_info->path ) ) {
            //If TPM is installed, check and activate license
            if( is_TPM_installed() ) {
                require_once 'TPM_License_Checker_And_Activation_Helper.php';
                $this->activate_thrive_product_license();
            }

            // Plugin activated successfully, return a 200 OK response
            return new \WP_REST_Response(
                array(
                    'message' => 'Plugin activated successfully',
                ),
                200
            );
        } else {
            // Activation failed, return a 500 Internal Server Error response
            return new \WP_REST_Response(
                array(
                    'message' => 'Something went wrong during activation, please try later!',
                ),
                500
            );
        }
    }

    /**
     * Activates the Thrive product license if available.
     *
     * @return void
     */
    private function activate_thrive_product_license() {
        if( $tag =  $this->get_tag_by_slug( $this->plugin_slug ) ) {
            TPM_License_Checker_And_Activation_Helper::activate_license_using_slug( $tag );
        }
    }


    /**
     * Get the installation url for a Thrive product.
     *
     * @return WP_REST_Response The response object.
     */
    public function install_thrive_product() {
        $download_link = $this->_get_download_url();

        if ( is_wp_error( $download_link ) ) {
            return $download_link;
        }

        $url = get_option( 'install_remote_url' . $this->plugin_slug, '' );

        if ( ! empty( $url ) ) {
            update_option( 'install_remote_url' . $this->plugin_slug, $download_link );
        } else {
            add_option( 'install_remote_url' . $this->plugin_slug, $download_link );
        }

        return new \WP_REST_Response(
            array(
                'dl'      => true,
                'success' => 'Plugin download link restored successfully',
            ),
            200
        );
    }

    /**
     * Retrieves tool information by slug.
     *
     * @return object|null The tool information or null if not found.
     */
    protected function get_tool_info_by_slug() {
        foreach ( $this->tools as $plugins ) {
            foreach ( $plugins as $plugin ) {
                if ( isset( $plugin['plugin_slug'] ) && $plugin['plugin_slug'] === $this->plugin_slug ) {
                    return (object) $plugin;
                }
            }
        }
        return null;
    }

    /**
     * Retrieves the download URL for the thrive plugin.
     *
     * @return mixed The download URL or WP_Error object.
     */
    protected function _get_download_url() {
        $options = array(
            'timeout'   => 20, // seconds
            'sslverify' => false,
            'headers'   => array(
                'Accept' => 'application/json',
            ),
        );
        /**
         * Prepare the POST parameters
         */
        $options['body'] = array(
            'api_slug' => $this->plugin_slug,
        );

        $service_api = defined( 'TD_SERVICE_API_URL' ) ? TD_SERVICE_API_URL : 'https://service-api.thrivethemes.com';
        $endpoint = rtrim($service_api, '/') . '/plugin/update';
        $url    = add_query_arg( array( 'p' => $this->_get_hash( $options['body'] ) ), $endpoint );
        $result = wp_remote_post( $url, $options );

        if ( ! is_wp_error( $result ) ) {
            $info = json_decode( wp_remote_retrieve_body( $result ), true );
            if ( ! empty( $info ) ) {
                return $info['download_url'];
            }
        }

        return new \WP_Error( '400', wp_remote_retrieve_body( $result ) );
    }

    /**
     * Generates a hash based on the provided data.
     *
     * @param array $data The data to be hashed.
     * @return string The generated hash.
     */
    protected function _get_hash( $data ) {
        $key = '@#$()%*%$^&*(#@$%@#$%93827456MASDFJIK3245';

        return md5( $key . serialize( $data ) . $key );
    }
}

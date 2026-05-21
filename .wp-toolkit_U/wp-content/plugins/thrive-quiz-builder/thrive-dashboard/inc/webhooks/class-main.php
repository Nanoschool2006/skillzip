<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Webhooks;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden!
}

/**
 * Main bootstrap for the Webhooks module.
 *
 * Responsibilities:
 * - Load all Webhooks classes in this directory
 * - Register the internal CPT used to persist webhooks
 * - Wire admin menu and options
 * - Initialize REST routes and runtime services (dispatcher, admin UI)
 */
class Main {
	/**
	 * Entry point for the Webhooks module.
	 *
	 * - Includes all classes in this directory
	 * - Registers CPT and admin menu
	 * - Initializes options, dispatcher and admin UI
	 * - Registers REST routes
	 *
	 * @return void
	 */
    public static function init() {
        static::includes();

        // Register storage (CPT) and menu
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'init', [ __CLASS__, 'init_options' ] );

        // Wire pre-save handler so drafts get persisted during Save Work
        add_action( 'init', [ __CLASS__, 'hook_content_pre_save' ] );

        // Runtime services
        TD_Webhooks_Dispatcher::init();
        TD_Webhooks_Admin::init();

        // REST API endpoints
        add_action( 'rest_api_init', [ __CLASS__, 'rest_api_init' ] );
    }

    /**
     * Require all PHP files next to this one, except this class file itself.
     *
     * This allows the module to self-bootstrap without hardcoding class includes.
     *
     * @return void
     */
    public static function includes() {
        // Ensure shared utils are loaded (HTTP error map)
        $utils_file = dirname( __DIR__ ) . '/utils/class-tt-http-error-map.php';

        if ( file_exists( $utils_file ) ) {
            require_once $utils_file;
        }

        // Require all PHP files next to this one, except this class file itself.
        foreach ( glob( __DIR__ . '/*.php' ) as $file ) {

            if ( strpos( $file, 'class-main.php' ) !== false ) {
                continue;
            }

            require_once $file;
        }
    }

    /**
     * Register all REST API routes for the Webhooks module.
     *
     * @return void
     */
    public static function rest_api_init() {
        $rest = new TD_Webhooks_Rest_Controller();
        $rest->register_routes();
    }

    /**
     * Register the internal `td_webhook` custom post type.
     *
     * This CPT is not exposed in the UI; it persists webhook configurations.
     *
     * @return void
     */
    public static function register_cpt() {
        $labels = [
            'name'          => __( 'Webhooks', 'thrive-dash' ),
            'singular_name' => __( 'Webhook', 'thrive-dash' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'exclude_from_search' => true,
            'show_in_nav_menus'   => false,
            'supports'            => [ 'title' ],
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'rewrite'             => false,
        ];

        register_post_type( 'td_webhook', $args );

        // Exclude from Thrive index
        add_filter( 'tve_dash_exclude_post_types_from_index', static function( $post_types ) {
            $post_types[] = 'td_webhook';
            return $post_types;
        } );
    }

    /**
     * Add the Webhooks submenu under the Thrive Dashboard section.
     *
     * @return void
     */
    public static function register_menu() {
        // Only expose the Webhooks admin UI when TVE_DEBUG is enabled
        if ( defined( 'TVE_DEBUG' ) && TVE_DEBUG ) {
            add_submenu_page(
                'tve_dash_section',
                __( 'Webhooks', 'thrive-dash' ),
                __( 'Webhooks', 'thrive-dash' ),
                TVE_DASH_CAPABILITY,
                'td_webhooks',
                [ __CLASS__, 'render_admin_page' ]
            );
        }
    }

    /**
     * Render the admin page wrapper. The concrete UI is delegated to TD_Webhooks_Admin.
     *
     * @return void
     */
    public static function render_admin_page() {
        TD_Webhooks_Admin::render();
    }

    /**
     * Initialize default options for Webhooks settings and logs storage.
     *
     * - td_webhooks_settings: module settings (timeout, retention, TTL, allow/deny lists)
     * - td_webhooks_logs: per-webhook execution logs
     *
     * @return void
     */
    public static function init_options() {
        // Do not create settings option unconditionally; defaults are provided via TD_Webhooks_Settings.
        if ( get_option( 'td_webhooks_logs', null ) === null ) {
            add_option( 'td_webhooks_logs', [], '', 'no' );
        }
    }

    /**
     * Hook into the editor content pre-save pipeline to persist any webhook drafts coming from TCB.
     */
    public static function hook_content_pre_save() {
        add_filter( 'tcb.content_pre_save', static function ( $response, $post_data ) {
            // Expect an array of { form_identifier, webhook_id, draft } under 'webhooks'
            if ( empty( $post_data['webhooks'] ) || ! is_array( $post_data['webhooks'] ) ) {
                return $response;
            }

            $result = [];

            foreach ( $post_data['webhooks'] as $item ) {


                $draft           = isset( $item['draft'] ) && is_array( $item['draft'] ) ? $item['draft'] : [];
                $form_identifier = isset( $item['form_identifier'] ) ? sanitize_text_field( $item['form_identifier'] ) : '';
                $form_id         = isset( $item['form_id'] ) ? sanitize_text_field( $item['form_id'] ) : '';
                $existing_id     = isset( $item['webhook_id'] ) ? (int) $item['webhook_id'] : 0;
                $delete_id       = isset( $item['delete'] ) ? (int) $item['delete'] : 0;

                // If deletion was requested, delete and return mapping only
                if ( $delete_id > 0 && get_post_type( $delete_id ) === TD_Webhooks_Repository::POST_TYPE ) {
                    TD_Webhooks_Repository::delete( $delete_id );
                    $result[] = [
                        'id'              => 0,
                        'name'            => __( 'Deleted', 'thrive-dash' ),
                        'form_identifier' => $form_identifier,
                        'form_id'         => $form_id,
                        'deleted'         => $delete_id,
                    ];
                    continue;
                }

                // Otherwise create or update based on presence of existing_id
                if ( $existing_id > 0 && get_post_type( $existing_id ) === TD_Webhooks_Repository::POST_TYPE ) {

                    // ID Exists, Update the webhook.
                    TD_Webhooks_Repository::update( $existing_id, $draft );
                    $saved_id = $existing_id;
                } else {

                    // ID Does Not Exist, Create the webhook.
                    $saved_id = TD_Webhooks_Repository::create( $draft );
                }

                if ( $saved_id ) {
                    $saved = TD_Webhooks_Repository::read( (int) $saved_id );
                    $result[] = [
                        'id'              => (int) $saved_id,
                        'name'            => isset( $saved['name'] ) ? $saved['name'] : '',
                        'form_identifier' => $form_identifier,
                        'form_id'         => $form_id,
                        'deleted'         => 0,
                    ];
                }
            }

            if ( ! empty( $result ) ) {
                $response['webhooks'] = $result;
            }

            return $response;
        }, 10, 2 );
    }
}


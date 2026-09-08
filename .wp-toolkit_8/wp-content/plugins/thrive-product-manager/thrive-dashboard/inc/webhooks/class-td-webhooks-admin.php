<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 *
 * Admin UI for managing Webhooks.
 * Follows WordPress coding standards, uses proper escaping and translations.
 */

namespace TVE\Dashboard\Webhooks;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Silence is golden!
}

/**
 * Admin UI for managing Webhooks.
 *
 * Provides CRUD over webhooks, settings management, and log viewing.
 */
class TD_Webhooks_Admin {
	/**
	 * Hook admin_post actions for CRUD operations and settings save.
	 *
	 * @return void
	 */
    public static function init() {
        // Register handlers for create/update and delete actions
        add_action( 'admin_post_td_webhooks_save', [ __CLASS__, 'handle_save' ] );

        add_action( 'admin_post_td_webhooks_delete', [ __CLASS__, 'handle_delete' ] );
    }

    /**
     * Render the Webhooks admin page with tabs.
     *
     * @return void
     */
    public static function render() {
        if ( ! current_user_can( TVE_DASH_CAPABILITY ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'thrive-dash' ) );
        }

        // Visible only when TVE_DEBUG is true
        if ( ! defined( 'TVE_DEBUG' ) || ! TVE_DEBUG ) {
            wp_die( esc_html__( 'This page is available only in debug mode.', 'thrive-dash' ) );
        }

        // Determine active tab
        $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'list';

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Webhooks', 'thrive-dash' ) . '</h1>';

        // Render tab links
        echo '<h2 class="nav-tab-wrapper">';
        self::tab_link( 'list', __( 'All Webhooks', 'thrive-dash' ), $tab );
        self::tab_link( 'edit', __( 'Add New', 'thrive-dash' ), $tab );
        echo '</h2>';

        // Switch by selected tab and render corresponding view
        switch ( $tab ) {
            case 'edit':
                self::render_edit();
                break;
            case 'logs':
                self::render_logs();
                break;
            case 'list':
            default:
                self::render_list();
        }
        echo '</div>';
    }

    /**
     * Output a tab link.
     *
     * @param string $slug   Tab slug
     * @param string $label  Tab label
     * @param string $active Currently active tab
     *
     * @return void
     */
    private static function tab_link( $slug, $label, $active ) {
        // Build link URL and CSS class
        $url = add_query_arg( [ 'page' => 'td_webhooks', 'tab' => $slug ], admin_url( 'admin.php' ) );
        $class = 'nav-tab' . ( $active === $slug ? ' nav-tab-active' : '' );

        // Output tab anchor
        echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
    }

    /**
     * List existing webhooks in a table.
     *
     * @return void
     */
    private static function render_list() {
        // Fetch all webhooks and pass to list view
        $items = TD_Webhooks_Repository::list();

        self::render_view( 'list', [ 'items' => $items ] );
    }

    /**
     * Render the create/update form for a webhook.
     *
     * @return void
     */
    private static function render_edit() {
        // Read resource and related data for the edit form
        $id   = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $item = $id ? TD_Webhooks_Repository::read( $id ) : [];

        // Nonces for save/delete actions
        $nonce = wp_create_nonce( 'td_webhooks_save' );
        $delete_nonce = $id ? wp_create_nonce( 'td_webhooks_delete' ) : '';

        // Normalize complex fields for the form
        $targeting = (array) ( $item['targeting'] ?? [] );
        $headers = is_array( $item['headers'] ?? null ) ? $item['headers'] : [];
        $mapping = is_array( $item['body_mapping'] ?? null ) ? $item['body_mapping'] : [];

        // Render edit form
        self::render_view(
            'edit', 
            [
                'id'           => $id,
                'item'         => $item,
                'nonce'        => $nonce,
                'delete_nonce' => $delete_nonce,
                'targeting'    => $targeting,
                'headers'      => $headers,
                'mapping'      => $mapping,
            ]
        );
    }

    /**
     * Render logs table for a specific webhook.
     *
     * @return void
     */
    private static function render_logs() {
        // Identify webhook and load its logs from stored option
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $all = get_option( 'td_webhooks_logs', [] );
        $rows = (array) ( $all[ $id ] ?? [] );

        // Render logs table
        self::render_view( 'logs', [ 'rows' => $rows ] );
    }

    

    /**
     * Include a view template with scoped variables.
     *
     * @param string $name
     * @param array  $vars
     * @return void
     */
    private static function render_view( $name, array $vars = [] ) {
        // Resolve template file path for the requested view
        $file = __DIR__ . '/views/' . $name . '.phtml';

        if ( ! file_exists( $file ) ) {
            return;
        }

        // Expose variables to the template in a safe manner
        extract( $vars, EXTR_SKIP );

        include $file;
    }

    /**
     * Handle webhook create/update submission.
     *
     * @return void
     */
    public static function handle_save() {
        // Security checks
        check_admin_referer( 'td_webhooks_save' );
        if ( ! current_user_can( TVE_DASH_CAPABILITY ) ) {
            wp_die( esc_html__( 'Permission denied', 'thrive-dash' ) );
        }

        // Identify resource and collect sanitized form data
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $data = [
            'name'             => sanitize_text_field( $_POST['name'] ?? '' ),
            'enabled'          => ! empty( $_POST['enabled'] ),
            'url'              => esc_url_raw( $_POST['url'] ?? '' ),
            'method'           => sanitize_key( $_POST['method'] ?? 'post' ),
            'request_format'   => sanitize_key( $_POST['request_format'] ?? 'form' ),
            'trigger_when'     => sanitize_key( $_POST['trigger_when'] ?? 'on_submit' ),
            'consent_required' => ! empty( $_POST['consent_required'] ),
            'headers'          => self::parse_pairs( $_POST['headers'] ?? [] ),
            'body_mapping'     => self::parse_pairs( $_POST['body_mapping'] ?? [] ),
            'targeting'        => self::parse_targeting( $_POST['targeting'] ?? [] ),
        ];

        // Persist changes (update or create)
        if ( $id ) {
            TD_Webhooks_Repository::update( $id, $data );
        } else {
            $id = TD_Webhooks_Repository::create( $data );
        }

        // Redirect back to edit screen with success flag
        wp_safe_redirect( add_query_arg( [ 'page' => 'td_webhooks', 'tab' => 'edit', 'id' => $id, 'updated' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /**
     * Handle webhook deletion.
     *
     * @return void
     */
    public static function handle_delete() {
        // Security checks
        check_admin_referer( 'td_webhooks_delete' );
        if ( ! current_user_can( TVE_DASH_CAPABILITY ) ) {
            wp_die( esc_html__( 'Permission denied', 'thrive-dash' ) );
        }

        // Identify resource and delete if present
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        if ( $id ) {
            TD_Webhooks_Repository::delete( $id );
        }

        // Redirect back to list with confirmation
        wp_safe_redirect( add_query_arg( [ 'page' => 'td_webhooks', 'tab' => 'list', 'deleted' => 1 ], admin_url( 'admin.php' ) ) );
        exit;
    }

    

    // View helper methods removed; templates are self-contained.

    /**
     * Normalize key/value arrays from the repeater POST data.
     *
     * @param array $pairs [ 'key' => string[], 'value' => string[] ]
     *
     * @return array[] Each row in shape [ 'key' => string, 'value' => string ]
     */
    private static function parse_pairs( array $pairs ): array {
        // Align keys and values arrays
        $keys = (array) ( $pairs['key'] ?? [] );
        $vals = (array) ( $pairs['value'] ?? [] );

        $out = [];
        $len = max( count( $keys ), count( $vals ) );

        for ( $i = 0; $i < $len; $i++ ) {
            $k = isset( $keys[$i] ) ? trim( (string) $keys[$i] ) : '';
            $v = isset( $vals[$i] ) ? (string) $vals[$i] : '';

            // Skip rows without a key
            if ( $k === '' ) {
                continue;
            }

            // Sanitize and keep raw-ish value (unslashed) as stored
            $out[] = [ 'key' => sanitize_text_field( $k ), 'value' => wp_unslash( $v ) ];
        }

        return $out;
    }

    /**
     * Normalize targeting POST payload as stored structure.
     *
     * @param array $t
     *
     * @return array { scope, form_ids[], post_ids[], slugs[] }
     */
    private static function parse_targeting( array $t ): array {
        // Normalize scope and targeted IDs
        $scope = isset( $t['scope'] ) ? sanitize_key( $t['scope'] ) : '';
        return [
            'scope'    => in_array( $scope, [ 'all', 'include', 'exclude' ], true ) ? $scope : '',
            'form_ids' => self::array_from_csv( $t['form_ids'] ?? '' ),
            'post_ids' => array_map( 'intval', self::array_from_csv( $t['post_ids'] ?? '' ) ),
            'slugs'    => self::array_from_csv( $t['slugs'] ?? '' ),
        ];
    }

    /**
     * Convert CSV string into trimmed array of strings, ignoring empties.
     *
     * @param string $csv
     *
     * @return string[]
     */
    private static function array_from_csv( $csv ): array {
        // Convert (possibly empty) CSV to an array of trimmed strings
        $csv = (string) $csv;
        if ( $csv === '' ) {
            return [];
        }
        $parts = array_map( 'trim', explode( ',', $csv ) );
        $parts = array_filter( $parts, static function( $s ) { return $s !== '' ; } );
        return array_values( $parts );
    }
}

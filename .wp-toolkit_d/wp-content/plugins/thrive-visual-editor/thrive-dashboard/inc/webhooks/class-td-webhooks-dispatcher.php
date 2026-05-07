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
 * Orchestrates webhook triggering based on product events and targeting rules.
 */
class TD_Webhooks_Dispatcher {
	/**
	 * Subscribe to relevant product events.
	 *
	 * @return void
	 */
    public static function init() {
        // Subscribe to TCB form submissions
        add_action( 'tcb_api_form_submit', [ __CLASS__, 'on_submit' ], 10, 1 );
    }

    /**
     * Handle immediate form submit event coming from TCB.
     *
     * @param array $post Raw submission data
     * @return void
     */
    public static function on_submit( array $post ) {
        // Build event context from raw submission
        $context = [
            'trigger_when' => 'on_submit',
            'form_id'      => isset( $post['_tcb_id'] ) ? sanitize_text_field( $post['_tcb_id'] ) : '',
            'post_id'      => isset( $post['post_id'] ) ? intval( $post['post_id'] ) : 0,
            'slug'         => isset( $post['page_slug'] ) ? sanitize_title( $post['page_slug'] ) : '',
            'user_consent' => ! empty( $post['user_consent'] ) || ! empty( $post['gdpr'] ),
            'data'         => $post,
            'user'         => self::build_user_context(),
        ];

        // If a specific webhook is bound to the form, prioritize sending that one
        if ( ! empty( $post['_td_webhook_id'] ) ) {
            $id = intval( $post['_td_webhook_id'] );
            $wh = TD_Webhooks_Repository::read( $id );

            if ( ! empty( $wh ) && ! empty( $wh['enabled'] ) ) {
                if ( self::is_consent_ok( $wh, $context ) ) {
                    // Direct send and short-circuit any global dispatch
                    TD_Webhooks_Sender::send( $wh, $context );
                    return; // Do not fall back to global dispatch
                }
            }
        }

    }

    /**
     * Verify consent, when required, for on_submit events.
     *
     * @param array $webhook
     * @param array $context
     * @return bool
     */
    private static function is_consent_ok( array $webhook, array $context ): bool {
        if ( ! empty( $webhook['consent_required'] ) && ( $context['trigger_when'] === 'on_submit' ) ) {
            return ! empty( $context['user_consent'] );
        }

        return true;
    }

    /**
     * Build a normalized current user context for templating.
     * Includes user meta fields such as first/last name and last_login.
     *
     * @return array|null
     */
    private static function build_user_context() {
        if ( ! is_user_logged_in() ) {
            return null;
        }

        $user = wp_get_current_user();

        if ( empty( $user ) || empty( $user->ID ) ) {
            return null;
        }

        $meta       = get_user_meta( $user->ID );
        $first_name = isset( $meta['first_name'][0] ) ? (string) $meta['first_name'][0] : '';
        $last_name  = isset( $meta['last_name'][0] ) ? (string) $meta['last_name'][0] : '';
        $last_login = '';

        // Format the last_login date.
        if ( isset( $meta['tve_last_login'][0] ) && $meta['tve_last_login'][0] !== '' ) {
            $last_login = wp_date( 'Y-m-d H:i:s', (int) $meta['tve_last_login'][0] );
        }

        // Return the user context.
        return [
            'ID'              => (int) $user->ID,
            'user_login'      => (string) $user->user_login,
            'user_email'      => (string) $user->user_email,
            'user_registered' => (string) $user->user_registered,
            'first_name'      => $first_name,
            'last_name'       => $last_name,
            'last_login'      => $last_login,
        ];
    }
}


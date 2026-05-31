<?php
/**
 * Migration script to convert Twitter social share elements to X
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden..
}

defined( 'THRIVE_SOCIAL_OPTION_NAME' ) || define( 'THRIVE_SOCIAL_OPTION_NAME', 'thrive_social_urls' );

class TCB_Social_Migration {
    /**
     * Initialize the migration
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_run_migration' ) );
    }

    /**
     * Check if migration needs to be run
     */
    public static function maybe_run_migration() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $migration_completed = get_option( 'tcb_twitter_to_x_migration_completed' );
        
        if ( ! $migration_completed ) {
            self::run_migration();
        }
    }

    /**
     * Migrate user social media meta data from 't' to 'x'
     */
    public static function migrate_user_social_meta() {
        global $wpdb;
        
        $batch_size = 50; // Process 50 users at a time
        $offset = 0;
        
        // Get total count of users with posts
        $total_users = count_users();
        $total_users = isset( $total_users['total_users'] ) ? $total_users['total_users'] : 0;
        
        while ( $offset < $total_users ) {
            // Get users in batches
            $args = array(
                'has_published_posts' => true,
                'fields' => 'ID', // Only get IDs to reduce memory usage
                'number' => $batch_size,
                'offset' => $offset
            );
            
            $users = get_users( $args );
            
            if ( empty( $users ) ) {
                break;
            }
            
            foreach ( $users as $user ) {
                try {
                    // Get user meta
                    $user_meta = get_user_meta( $user, THRIVE_SOCIAL_OPTION_NAME, true );
                    
                    // Skip if no meta data
                    if ( empty( $user_meta ) ) {
                        continue;
                    }
                    
                    // Skip if no 't' data to migrate
                    if ( empty( $user_meta['t'] ) ) {
                        continue;
                    }
                    
                    // Migrate the value from t to x
                    $user_meta['x'] = $user_meta['t'];
                    unset( $user_meta['t'] );
                    
                    // Update the user meta
                    update_user_meta( $user, THRIVE_SOCIAL_OPTION_NAME, $user_meta );
                    
                } catch ( Exception $e ) {
                    error_log( sprintf(
                        'Error processing user ID %d: %s',
                        $user,
                        $e->getMessage()
                    ) );
                }
            }
            
            $offset += $batch_size;
            
            // Give the server a small break between batches
            if ( function_exists( 'usleep' ) ) {
                usleep( 100000 ); // 100ms delay
            }
        }
    }

    /**
     * Run the migration
     */
    public static function run_migration() {
        global $wpdb;

        // Get all posts that might contain Twitter social share elements
        $posts = $wpdb->get_results( "
            SELECT ID, post_content 
            FROM {$wpdb->posts} 
            WHERE post_content LIKE '%tve_s_t_share%'
            AND post_status != 'trash'
        " );

        $updated = 0;

        foreach ( $posts as $post ) {
            $content = $post->post_content;
            
            // Replace Twitter elements with X elements
            $new_content = self::replace_twitter_elements( $content );
            
            if ( $content !== $new_content ) {
                wp_update_post( array(
                    'ID' => $post->ID,
                    'post_content' => $new_content
                ) );
                $updated++;
            }
        }

        // Update quiz variations
        self::update_quiz_variations();

        // Migrate user social meta
        self::migrate_user_social_meta();

        // Mark migration as completed
        update_option( 'tcb_twitter_to_x_migration_completed', true );
    }

    /**
     * Update quiz variations that contain Twitter elements
     */
    private static function update_quiz_variations() {
        global $wpdb;
        $updated = 0;

        // Get all quiz variations that contain Twitter elements
        $variations = $wpdb->get_results( $wpdb->prepare( "
            SELECT id, content 
            FROM {$wpdb->prefix}tqb_variations 
            WHERE content LIKE %s
        ", '%tve_s_t_share%' ) );

        foreach ( $variations as $variation ) {
            $content = $variation->content;
            
            // Replace Twitter elements with X elements
            $new_content = self::replace_twitter_elements( $content );
            
            if ( $content !== $new_content ) {
                $wpdb->update(
                    $wpdb->prefix . 'tqb_variations',
                    array( 'content' => $new_content ),
                    array( 'id' => $variation->id ),
                    array( '%s' ),
                    array( '%d' )
                );
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Replace Twitter elements with X elements in content
     */
    private static function replace_twitter_elements( $content ) {
        // Pattern to match Twitter social share elements
        $pattern = '/<div[^>]*class="[^"]*tve_s_item[^"]*tve_s_t_share[^"]*"[^>]*data-s="t_share"[^>]*>.*?<\/div>/s';
        
        // Replacement function
        $replacement = function( $matches ) {
            $element = $matches[0];
            
            // Extract data attributes
            preg_match( '/data-href="([^"]*)"/', $element, $href_matches );
            $href = isset( $href_matches[1] ) ? $href_matches[1] : '{tcb_post_url}';
            
            preg_match( '/data-via="([^"]*)"/', $element, $via_matches );
            $via = isset( $via_matches[1] ) ? $via_matches[1] : '';
            
            preg_match( '/<span class="tve_s_text">([^<]*)<\/span>/', $element, $label_matches );
            $label = isset( $label_matches[1] ) ? $label_matches[1] : esc_html__( 'Post', 'thrive-cb' );
            
            // Create new X element
            return sprintf(
                '<div class="tve_s_item tve_s_x_share" data-s="x_share" data-href="%s" data-post="%s" data-via="%s">
                    <a href="javascript:void(0)" class="tve_s_link">
                        <span class="tve_s_icon thrv-svg-icon">
                            <svg class="tcb-x" viewBox="0 0 512 512">
                                <path d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"></path>
                            </svg>
                        </span>
                        <span class="tve_s_text">%s</span>
                        <span class="tve_s_count">0</span>
                    </a>
                </div>',
                esc_attr( $href ),
                esc_attr__( 'I got: %result%', 'thrive-cb' ), // translators: %result% is a placeholder for quiz result
                esc_attr( $via ),
                esc_html( $label )
            );
        };
        
        return preg_replace_callback( $pattern, $replacement, $content );
    }

}

// Initialize the migration
TCB_Social_Migration::init(); 
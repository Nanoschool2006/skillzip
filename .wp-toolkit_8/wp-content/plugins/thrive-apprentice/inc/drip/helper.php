<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

namespace TVA\Drip;

use TVA\Drip\Trigger\Specific_Date_Time_Interval;
use TVA\Drip\Trigger\Time_After_First_Lesson;
use TVA\Drip\Trigger\Time_After_Purchase;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Callback for datetime schedule CRON that is triggered when the time passes for a datetime drip trigger
 *
 * @param int $product_id
 * @param int $campaign_id
 * @param int $post_id
 */
function datetime_schedule_callback( $product_id, $campaign_id, $post_id ) {
	$campaign = new Campaign( (int) $campaign_id );

	if ( $campaign->cron_allow_execute( (int) $product_id, $post_id ) ) {

		$campaign->set_customer( false );

		$trigger = $campaign->get_trigger_for_post( $post_id, Specific_Date_Time_Interval::NAME );

		if ( $trigger ) {
			$campaign->cron_check_post_unlocked( $product_id, $post_id );
		}
	}
}

/**
 * Callback for start course schedule user-based CRON
 *
 * @param int $product_id
 * @param int $campaign_id
 * @param int $post_id
 * @param int $customer_id
 */
function start_course_schedule_callback( $product_id, $campaign_id, $post_id, $customer_id ) {
	$campaign = new Campaign( (int) $campaign_id );

	if ( $campaign->cron_allow_execute( (int) $product_id, $post_id ) && get_userdata( $customer_id ) !== false ) {

		$campaign->set_customer( new \TVA_Customer( $customer_id ) );

		$trigger = $campaign->get_trigger_for_post( $post_id, Time_After_First_Lesson::NAME );

		if ( $trigger && ! $campaign->is_user_drip_complete( $post_id ) ) {
			$trigger->mark_user_completed( $post_id );

			$campaign->cron_check_post_unlocked( $product_id, $post_id, $customer_id );
		}
	}
}

/**
 * Callback for purchase schedule user-based CRON
 *
 * @param int $product_id
 * @param int $campaign_id
 * @param int $post_id
 * @param int $customer_id
 */
function purchase_schedule_callback( $product_id, $campaign_id, $post_id, $customer_id ) {
	$campaign = new Campaign( (int) $campaign_id );

	if ( $campaign->cron_allow_execute( (int) $product_id, $post_id ) && get_userdata( $customer_id ) !== false ) {

		$campaign->set_customer( new \TVA_Customer( $customer_id ) );

		$trigger = $campaign->get_trigger_for_post( $post_id, Time_After_Purchase::NAME );

		if ( $trigger && ! $campaign->is_user_drip_complete( $post_id ) ) {
			$trigger->mark_user_completed( $post_id );

			$campaign->cron_check_post_unlocked( $product_id, $post_id, $customer_id );
		}
	}
}

/**
 * Callback from external plugin (Automator)
 * Sets a particular content as unlocked for a specific user
 *
 * @param int $post_id
 * @param int $user_id
 */
function unlock_content_for_specific_user( $post_id, $user_id ) {
	$post = get_post( $post_id );
	/* make sure that this does not get called with invalid parameters */
	if ( $post ) {
		$customer = new \TVA_Customer( $user_id );
		$customer->set_drip_content_unlocked( $post_id );
		$product = \TVA\Product::get_from_set( \TVD\Content_Sets\Set::get_for_object( $post, $post_id ), array(), $post ) ;

		/**
		 * Triggered when content is unlocked for a specific user
		 *
		 * @param \WP_User $user    User object for which content is unlocked
		 * @param \WP_Post $post    The post object that is unlocked
		 * @param \WP_Term $product The product term that the campaign belongs to
		 */
		do_action( 'tva_drip_content_unlocked_for_specific_user', $customer->get_user(), $post, $product );
	}
}

/**
 * Callback from external plugin (Automator)
 * Sets a particular content as unlocked for everyone
 *
 * @param int $post_id
 */
function unlock_content_for_everyone( $post_id ) {
	update_post_meta( $post_id, 'tva_drip_content_unlocked_for_everyone', 1 );
}

/**
 * Resolves the future unlock date for a post if it is locked by a drip campaign
 * and a date can be computed. Returns null otherwise.
 *
 * Result is cached per request, keyed by post id.
 *
 * @param int $post_id
 *
 * @return \DateTimeInterface|null
 */
function get_unlock_date_for_post( $post_id ) {
	static $cache = [];

	$key = (int) $post_id;
	if ( array_key_exists( $key, $cache ) ) {
		return $cache[ $key ];
	}

	$post = get_post( $key );
	if ( ! $post ) {
		return $cache[ $key ] = null;
	}

	$sets     = \TVD\Content_Sets\Set::get_for_object( $post, $key );
	$product  = \TVA\Product::get_from_set( $sets, [], $post );
	$course   = $product ? \TVA_Post::factory( $post )->get_course_v2() : null;
	$campaign = ( $course && $course->get_id() ) ? $product->get_drip_campaign_for_course( $course->get_id() ) : null;
	if ( ! $campaign instanceof Campaign ) {
		return $cache[ $key ] = null;
	}

	$triggers = $campaign->get_all_triggers_for_post( $key );
	if ( $triggers ) {
		$dates = array_map( static fn( $t ) => $t->get_unlock_timestamp(), $triggers );
		return $cache[ $key ] = ( in_array( null, $dates, true ) ? null : max( $dates ) );
	}

	if ( $campaign->trigger === 'datetime' && $campaign->unlock_date ) {
		$dt = Trigger\Base::get_datetime( $campaign->unlock_date );
		return $cache[ $key ] = ( $dt > current_datetime() ? $dt : null );
	}

	return $cache[ $key ] = null;
}

/**
 * Renders the unlock-date meta block (calendar icon + "Unlock date:" label + formatted date)
 * for a given post. Returns empty string when the post has no resolvable unlock date.
 *
 * Design follows the v3 lesson-meta pattern: small inline-flex line beneath the lesson
 * description, tertiary text color, calendar glyph, sentence-case label.
 *
 * @param int $post_id
 *
 * @return string
 */
function render_unlock_date_html( $post_id ) {
	$dt = get_unlock_date_for_post( (int) $post_id );
	if ( ! $dt ) {
		return '';
	}

	$date  = esc_html( wp_date( get_option( 'date_format' ), $dt->getTimestamp() ) );
	$label = esc_html__( 'Unlock date', 'thrive-apprentice' );
	$icon  = '<svg class="tva-drip-unlock-date-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="flex:0 0 auto;"><rect x="3.5" y="5.5" width="17" height="15" rx="2.5" stroke="currentColor" stroke-width="1.8"/><path d="M3.5 10h17" stroke="currentColor" stroke-width="1.8"/><path d="M8 3.5v4M16 3.5v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';

	return '<span class="tva-drip-unlock-date-meta" style="display:inline-flex;align-items:center;gap:6px;font-size:12px;line-height:1.3;color:rgba(106,107,108,0.85);white-space:nowrap;">'
		. $icon
		. '<span class="tva-drip-unlock-date-label" style="font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;">' . $label . '</span>'
		. '<span class="tva-drip-unlock-date-sep" style="color:rgba(106,107,108,0.5);">·</span>'
		. '<span class="tva-drip-unlock-date-value" style="font-weight:500;font-variant-numeric:tabular-nums;color:rgba(80,82,84,1);">' . $date . '</span>'
		. '</span>';
}

add_shortcode(
	'tva_drip_unlock_date',
	static function () {
		return render_unlock_date_html( (int) get_queried_object_id() );
	}
);

add_action(
	'init',
	static function () {
		if ( get_option( 'tva_drip_unlock_date_migrated_v1' ) ) {
			return;
		}
		// Short-lived lock prevents concurrent requests on a high-traffic site from
		// running the full-table scan in parallel before the option is set. The TTL
		// auto-recovers if a request fatals mid-loop. The shortcode-presence guard
		// inside the loop keeps overlapping runs idempotent.
		if ( get_transient( 'tva_drip_unlock_date_migration_lock' ) ) {
			return;
		}
		// Bail after 5 unsuccessful attempts so a persistent write failure (read-only
		// filesystem, broken object cache, etc.) doesn't run the full-table scan
		// indefinitely on every page load. Increment is inside the transient lock so
		// concurrent requests can't burn the budget in parallel.
		$tries = (int) get_option( 'tva_drip_unlock_date_migration_tries', 0 );
		if ( $tries >= 5 ) {
			return;
		}
		set_transient( 'tva_drip_unlock_date_migration_lock', 1, MINUTE_IN_SECONDS );
		update_option( 'tva_drip_unlock_date_migration_tries', $tries + 1 );

		$tar_ids = get_posts( [
			'post_type'      => \TVA_Access_Restriction::POST_TYPE,
			'post_status'    => [ 'draft', 'publish' ],
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'tva_content_for',
					'value'   => 'locked',
					'compare' => 'LIKE',
				],
			],
		] );

		// The marker `tve-u-1768408dffc` is the data-css ID of the stock locked-content TAR
		// text-element wrapping `<h4>` (heading) and `<p>` (explanation). The pattern requires
		// exactly that structure — h4 followed by p, no other element types in between — so
		// light text/styling edits to either the h4 or the p still match and migrate, while
		// deeper edits (added/removed/restructured elements inside the wrapping div) fail to
		// match and are skipped. Lookaheads forbid nested h4/p/div, so a customer-added third
		// element pushes the closing </div> out of regex range and aborts injection.
		$marker_pattern = '#(<div\s+class="thrv_wrapper thrv_text_element"[^>]*data-css="tve-u-1768408dffc"[^>]*>\s*<h4\b[^>]*>(?:(?!</?h4|</?p|</?div).)*?</h4>\s*<p\b[^>]*>(?:(?!</?h4|</?p|</?div).)*?</p>\s*)(</div>)#s';
		// Inject as a bare <p> (no thrv_wrapper div) BEFORE the marker text-element's closing </div>,
		// so our line becomes a third sibling of the existing h4 + p inside the same text-element.
		// This shares the natural block flow with the surrounding text and avoids the inter-wrapper
		// vertical spacing that .tve-cb adds between sibling thrv_wrapper elements.
		$injected = '<p class="tva-drip-unlock-date" style="margin:0;padding:0;text-align:center;">[tva_drip_unlock_date]</p>';

		foreach ( $tar_ids as $tar_id ) {
			$content = (string) get_post_meta( $tar_id, 'tve_updated_post', true );
			if ( $content === '' || strpos( $content, 'tva_drip_unlock_date' ) !== false ) {
				continue;
			}
			$new = preg_replace( $marker_pattern, '$1' . $injected . '$2', $content, 1, $count );
			if ( $new === null || $count === 0 ) {
				continue;
			}
			update_post_meta( $tar_id, 'tve_updated_post', $new );
		}

		// Set the success flag only after the loop completes. The shortcode-presence check above
		// keeps the loop idempotent, so an interrupted run safely retries on the next request
		// (subject to the 5-try ceiling). Clear the tries counter on success so a future schema
		// migration (v2) starts fresh.
		update_option( 'tva_drip_unlock_date_migrated_v1', 1 );
		delete_option( 'tva_drip_unlock_date_migration_tries' );
		delete_transient( 'tva_drip_unlock_date_migration_lock' );
	}
);

add_filter(
	'tva_drip_locked_tile_html',
	static function ( $html, $post_id ) {
		$html_str = (string) $html;
		if ( strpos( $html_str, 'tva_drip_unlock_date' ) !== false || strpos( $html_str, 'tva-drip-unlock-date' ) !== false ) {
			return $html;
		}
		$rendered = render_unlock_date_html( (int) $post_id );
		if ( $rendered === '' ) {
			return $html;
		}
		// Inject the date as a bare <p> INSIDE the description's wrapping `thrv_text_element` div,
		// as a sibling of the description's <p>. This shares the same block-flow container so we
		// don't fight TCB's 15px margin-bottom on `thrv_text_element` (which would otherwise sit
		// between two sibling wrappers). Explicit font-size/line-height on the <p> collapse the
		// inherited 17px/28.9px line-box down to the 12px/15.6px the inline-flex meta needs.
		// Anchor on the lesson description shortcode marker — drip campaigns track only lessons
		// (TVA_Const::LESSON_POST_TYPE in inc/drip/campaign.php), so module/chapter/assessment
		// tiles never resolve to a drip date and don't need anchors here.
		$unlock_p = '<p class="tva-drip-unlock-date" style="margin:6px 0 0 0;padding:0;font-size:12px;line-height:1.3;text-align:left;">' . $rendered . '</p>';
		$fallback = '<div class="thrv_wrapper thrv_text_element" style="margin:0;padding:0;">' . $unlock_p . '</div>';

		$injected = preg_replace_callback(
			'#(<div[^>]*class="[^"]*thrv_text_element[^"]*"[^>]*>(?:(?!</?div).)*?data-shortcode="tva_course_lesson_description"(?:(?!</?div).)*?)(</div>)#s',
			static fn( $m ) => $m[1] . $unlock_p . $m[2],
			$html_str,
			1,
			$count
		);
		return ( $injected !== null && $count > 0 ) ? $injected : ( $html_str . $fallback );
	},
	10,
	2
);

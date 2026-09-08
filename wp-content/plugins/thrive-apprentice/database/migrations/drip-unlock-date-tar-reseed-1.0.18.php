<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * #3616: inject the [tva_drip_unlock_date] shortcode into locked
 * restricted-content (TAR) posts that are missing it.
 *
 * Covers two populations:
 * - TARs created from the pre-10.9.3.3 seed template, which was missing the
 *   shortcode (#3541 only migrated TARs that existed at 10.9 upgrade time).
 * - All locked TARs on sites where the 10.9 init-hook migration (v1) never
 *   completed (or never ran - e.g. direct upgrade from pre-10.9).
 *
 * Data-only migration (no schema changes), same pattern as
 * email-welcome-template-1.0.16.php. Running here instead of the previous
 * init-hook implementation gives us: one run per site on the DB version bump,
 * no hand-rolled completion flag / retry counter / transient lock, and
 * failures surfacing through TD_DB_Manager's admin-notice path.
 *
 * IMPORTANT (#4431 review): $injected below must stay byte-identical to the
 * shortcode line in templates/access-restriction/locked/template.php - the
 * two files must change together so newly seeded and migrated TARs converge
 * on identical markup (the strpos guard below depends on it).
 */

/** @var $this TD_DB_Migration */

$ran_v1 = (bool) get_option( 'tva_drip_unlock_date_migrated_v1' );

$query_args = [
	'post_type'      => 'tva-acc-restriction', /* TVA_Access_Restriction::POST_TYPE - literal to avoid a load-order dependency during migration */
	'post_status'    => [ 'draft', 'publish' ],
	'posts_per_page' => - 1,
	'fields'         => 'ids',
	'meta_query'     => [
		[
			'key'     => 'tva_content_for',
			'value'   => 'locked',
			'compare' => 'LIKE',
		],
	],
];

if ( $ran_v1 ) {
	/**
	 * #4431 (review): the site already completed the 10.9 injection, so only
	 * TARs created after the 10.9 release can have been seeded without the
	 * shortcode. Scoping the rescan to that window means a post whose owner
	 * deliberately deleted the injected line (making it stock-shaped and
	 * matchable again) is left alone.
	 */
	$query_args['date_query'] = [
		[
			'after'     => '2026-04-30',
			'inclusive' => true,
		],
	];
}

$tar_ids = get_posts( $query_args );

/*
 * Marker regex: matches the stock locked-TAR text element (an h4 heading
 * followed by exactly one description p, no other elements). Deliberately
 * conservative - customized structures fail the lookaheads and are skipped;
 * the documented manual-add path applies to those. Light text/styling edits
 * to the h4 or p still match. See #3541 for the original rationale.
 */
$marker_pattern = '#(<div\s+class="thrv_wrapper thrv_text_element"[^>]*data-css="tve-u-1768408dffc"[^>]*>\s*<h4\b[^>]*>(?:(?!</?h4|</?p|</?div).)*?</h4>\s*<p\b[^>]*>(?:(?!</?h4|</?p|</?div).)*?</p>\s*)(</div>)#s';
$injected       = '<p class="tva-drip-unlock-date" style="margin:0;padding:0;text-align:center;">[tva_drip_unlock_date]</p>';

foreach ( $tar_ids as $tar_id ) {
	$content = (string) get_post_meta( $tar_id, 'tve_updated_post', true );

	if ( $content === '' || strpos( $content, 'tva_drip_unlock_date' ) !== false ) {
		continue;
	}

	$new = preg_replace( $marker_pattern, '$1' . $injected . '$2', $content, 1, $count );

	if ( $new === null ) {
		/* #4431 (review): PCRE error (e.g. backtrack limit on a very large content blob) - a real failure, not an intentional skip */
		if ( class_exists( 'TVA_Logger' ) ) {
			TVA_Logger::log(
				'drip_unlock_date_reseed_failed',
				[
					'tar_id'          => (int) $tar_id,
					'reason'          => 'preg_replace_failed',
					'preg_last_error' => preg_last_error(),
				],
				true,
				null,
				'Migration'
			);
		}
		continue;
	}

	if ( 0 === $count ) {
		/* customized structure - intentional skip */
		continue;
	}

	if ( update_post_meta( $tar_id, 'tve_updated_post', $new ) === false ) {
		/* #4431 (review): meta write failed - log instead of silently skipping. */
		if ( class_exists( 'TVA_Logger' ) ) {
			TVA_Logger::log(
				'drip_unlock_date_reseed_failed',
				[
					'tar_id' => (int) $tar_id,
					'reason' => 'meta_update_failed',
				],
				true,
				null,
				'Migration'
			);
		}
	}
}

/*
 * #4431 (review): clean up every artifact of the retired init-hook
 * implementation. The v1 completion flag in particular was written with
 * default autoload and is referenced nowhere anymore - leaving it would cost
 * a per-request autoload forever. ($ran_v1 was read above, before this.)
 */
delete_option( 'tva_drip_unlock_date_migrated_v1' );
delete_option( 'tva_drip_unlock_date_migrated_v2' );
delete_option( 'tva_drip_unlock_date_migration_tries' );
delete_option( 'tva_drip_unlock_date_migration_tries_v2' );
delete_transient( 'tva_drip_unlock_date_migration_lock' );

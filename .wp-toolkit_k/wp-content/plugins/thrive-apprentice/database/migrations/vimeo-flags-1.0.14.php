<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

/** @var $this TD_DB_Migration */

// Update Vimeo flags in Architect content saved in postmeta (tve_updated_post*)
// Targets: title, byline, portrait flags and data-* attributes in Vimeo embeds

global $wpdb;
$postmeta = $wpdb->prefix . 'postmeta';
$posts    = $wpdb->prefix . 'posts';

// Only affect Architect content for Vimeo embeds
// 1) data-showinfo="1" -> data-showinfo="0"
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-showinfo=\"1\"','data-showinfo=\"0\"')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-showinfo=\"1\"%'"
);
$this->add_query('COMMIT');

// 1b) single quotes variant
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-showinfo=\\'1\\'','data-showinfo=\\'0\\'')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-showinfo=\\'1\\'%'"
);
$this->add_query('COMMIT');

// 2) data-byline="1" -> data-byline="0"
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-byline=\"1\"','data-byline=\"0\"')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-byline=\"1\"%'"
);
$this->add_query('COMMIT');

// 2b) single quotes variant
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-byline=\\'1\\'','data-byline=\\'0\\'')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-byline=\\'1\\'%'"
);
$this->add_query('COMMIT');

// 3) data-modestbranding="1" -> data-modestbranding="0" (defensive: some content may have this)
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-modestbranding=\"1\"','data-modestbranding=\"0\"')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-modestbranding=\"1\"%'"
);
$this->add_query('COMMIT');

// 3b) single quotes variant
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'data-modestbranding=\\'1\\'','data-modestbranding=\\'0\\'')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND (pm.meta_value LIKE '%data-type=\"vimeo\"%' OR pm.meta_value LIKE '%data-type=\\'vimeo\\'%')
	   AND pm.meta_value LIKE '%data-modestbranding=\\'1\\'%'"
);
$this->add_query('COMMIT');

// 4) iframe src params: title=1 -> title=0 for Vimeo players
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'title=1','title=0')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND pm.meta_value LIKE '%player.vimeo.com%'
	   AND pm.meta_value LIKE '%title=1%'"
);
$this->add_query('COMMIT');

// 5) iframe src params: byline=1 -> byline=0
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'byline=1','byline=0')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND pm.meta_value LIKE '%player.vimeo.com%'
	   AND pm.meta_value LIKE '%byline=1%'"
);
$this->add_query('COMMIT');

// 6) iframe src params: portrait=1 -> portrait=0
$this->add_query('START TRANSACTION');
$this->add_query(
	"UPDATE `{$postmeta}` pm
	  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
	 SET pm.meta_value = REPLACE(pm.meta_value,'portrait=1','portrait=0')
	 WHERE pm.meta_key LIKE 'tve_updated_post%'
	   AND pm.meta_value LIKE '%player.vimeo.com%'
	   AND pm.meta_value LIKE '%portrait=1%'"
);
$this->add_query('COMMIT');

// 7) Update tva_video meta: ensure Vimeo options hide title/byline/portrait by default
// We update only if type is Vimeo (or source contains vimeo) and the flag is not already set
try {
	$processed = 0;
	$updated   = 0;
	$post_ids  = $wpdb->get_col(
		"SELECT pm.post_id FROM `{$postmeta}` pm
		  INNER JOIN `{$posts}` p ON p.ID = pm.post_id
		 WHERE pm.meta_key = 'tva_video'"
	);

	if ( is_array( $post_ids ) && ! empty( $post_ids ) ) {
		foreach ( $post_ids as $pid ) {
			$processed++;
			$video = get_post_meta( (int) $pid, 'tva_video', true );
			if ( empty( $video ) ) {
				continue;
			}
			if ( ! is_array( $video ) ) {
				$maybe = maybe_unserialize( $video );
				$video = is_array( $maybe ) ? $maybe : array();
			}
			$type   = isset( $video['type'] ) ? $video['type'] : '';
			$source = isset( $video['source'] ) ? (string) $video['source'] : '';
			if ( $type !== 'vimeo' && strpos( $source, 'vimeo' ) === false ) {
				continue;
			}
			$options = ! empty( $video['options'] ) && is_array( $video['options'] ) ? $video['options'] : array();
			$changed = false;
			if ( empty( $options['hide-title'] ) ) {
				$options['hide-title'] = 1;
				$changed               = true;
			}
			if ( empty( $options['hide-byline'] ) ) {
				$options['hide-byline'] = 1;
				$changed                 = true;
			}
			if ( empty( $options['hide-portrait'] ) ) {
				$options['hide-portrait'] = 1;
				$changed                   = true;
			}

			if ( $changed ) {
				$video['options'] = $options;
				update_post_meta( (int) $pid, 'tva_video', $video );
				$updated++;
			}
		}
	}
} catch ( Exception $e ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'TVA Vimeo migration tva_video error: ' . $e->getMessage() );
	}
}

return $this;

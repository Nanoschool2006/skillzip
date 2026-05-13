<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-apprentice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/** @var $this TD_DB_Migration */

// Execute the video duration update using the TVA_Video helper class
return TVA_Video::update_all_video_durations();
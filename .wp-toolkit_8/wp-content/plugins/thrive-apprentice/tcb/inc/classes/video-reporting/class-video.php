<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

namespace TCB\VideoReporting;

use TCB\Traits\Is_Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

class Video {
	use Is_Singleton;
	use Has_Post_Type;

	/**
	 * @var int
	 */
	private $ID;

	/**
	 * @param $id
	 */
	public function __construct( $id = null ) {
		$this->ID = $id;
	}

	public function on_video_start( $user_id, $post_id ) {
		do_action( 'thrive_video_start', [
			'item_id' => $this->ID,
			'user_id' => $user_id,
			'post_id' => $post_id,
		] );
	}

	public function save_range( $user_id, $post_id, $range_start, $range_end ) {
		do_action( 'thrive_video_update_watch_data', [
			'item_id'     => $this->ID,
			'user_id'     => $user_id,
			'post_id'     => $post_id,
			'range_start' => $range_start,
			'range_end'   => $range_end,
		] );
	}

	public function is_completed( $current_duration ) {
		$percentage_to_complete = $this->get_percentage_to_complete();
		if ( ! $percentage_to_complete ) {
			$percentage_to_complete = 100;
		}
		
		$duration_to_complete = (int) $this->get_full_duration() * (int) $percentage_to_complete / 100;
		/**
		 * If current duration is less than 10 seconds of the duration_to_complete, 
		 * consider it as completed for long videos.
		 * For less than 60 seconds, consider it as completed if 
		 * the current duration is less than 10% of the duration_to_complete.
		 **/
		$end_margin = $duration_to_complete < 60 ? $duration_to_complete * 0.10 : 10;

		return $current_duration >= $duration_to_complete - $end_margin;
	}

	/**
	 * Handles duration storage for lesson posts that store video data in tva_video meta
	 *
	 * @param integer $post_id The lesson post ID
	 * @return bool True if duration was updated, false otherwise
	 */
	public function ensure_lesson_video_duration_stored( $post_id ) {
		// Validate input
		if ( empty( $post_id ) || ! is_numeric( $post_id ) ) {
			return false;
		}

		$video_data = get_post_meta( $post_id, 'tva_video', true );
		
		if ( empty( $video_data ) || ! is_array( $video_data ) ) {
			return false;
		}

		if ( empty( $video_data['source'] ) || empty( $video_data['type'] ) ) {
			return false;
		}

		// Fetch duration from video provider
		$new_duration = $this->get_video_duration( $video_data['source'], $video_data['type'] );
		
		if ( $new_duration <= 0 ) {
			return false;
		}

		$current_duration = ! empty( $video_data['duration'] ) ? (int) $video_data['duration'] : 0;
		
		// Update if duration is missing or significantly different (allow 1 second tolerance)
		if ( $this->should_update_duration( $current_duration, $new_duration ) ) {
			$video_data['duration'] = $new_duration;
			return update_post_meta( $post_id, 'tva_video', $video_data );
		}
		
		// Duration is already correct, no update needed
		return false;
	}

	/**
	 * Determines if video duration should be updated
	 *
	 * @param int $current_duration Current stored duration
	 * @param int $new_duration New fetched duration
	 * @return bool True if duration should be updated
	 */
	private function should_update_duration( $current_duration, $new_duration ) {
		// Always update if no current duration exists
		if ( empty( $current_duration ) ) {
			return true;
		}

		// Allow 1 second tolerance for minor variations
		return abs( $current_duration - $new_duration ) > 1;
	}

	/**
	 * Gets video duration with error handling
	 *
	 * @param string $video_url Video URL
	 * @param string $provider Video provider
	 * @return int Duration in seconds, 0 if failed
	 */
	private function get_video_duration( $video_url, $provider ) {
		try {
			$duration = (int) $this->fetch_video_duration_php( $video_url, $provider );
			return $duration > 0 ? $duration : 0;
		} catch ( \Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Failed to fetch video duration: " . $e->getMessage() );
			}
			return 0;
		}
	}

	/**
	 * Fetches video duration from provider APIs
	 *
	 * @param string $video_url The video URL
	 * @param string $provider The video provider (youtube, vimeo, wistia)
	 * @return int Duration in seconds, 0 if failed
	 */
	private function fetch_video_duration_php( $video_url, $provider ) {
		switch ( strtolower( $provider ) ) {
			case 'youtube':
				return $this->get_youtube_duration_php( $video_url );
			case 'vimeo':
				return $this->get_vimeo_duration_php( $video_url );
			case 'wistia':
				return $this->get_wistia_duration_php( $video_url );
			default:
				return 0;
		}
	}

	/**
	 * Fetches YouTube video duration using YouTube Data API v3
	 *
	 * @param string $video_url YouTube video URL
	 * @return int Duration in seconds, 0 if failed
	 */
	private function get_youtube_duration_php( $video_url ) {
		$video_id = $this->extract_youtube_id( $video_url );
		if ( ! $video_id ) {
			return 0;
		}

		$api_key = $this->get_youtube_api_key();
		if ( ! $api_key ) {
			return 0;
		}

		$api_url = "https://www.googleapis.com/youtube/v3/videos?id={$video_id}&part=contentDetails&key={$api_key}";
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'headers' => [
				'User-Agent' => 'WordPress/ThriveThemes'
			]
		] );

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! empty( $data['items'][0]['contentDetails']['duration'] ) ) {
			return $this->iso8601_to_seconds( $data['items'][0]['contentDetails']['duration'] );
		}

		return 0;
	}

	/**
	 * Fetches Vimeo video duration using oEmbed API
	 *
	 * @param string $video_url Vimeo video URL
	 * @return int Duration in seconds, 0 if failed
	 */
	private function get_vimeo_duration_php( $video_url ) {
		$api_url = "https://vimeo.com/api/oembed.json?url=" . urlencode( $video_url );
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'headers' => [
				'User-Agent' => 'WordPress/ThriveThemes'
			]
		] );

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return ! empty( $data['duration'] ) ? (int) $data['duration'] : 0;
	}

	/**
	 * Fetches Wistia video duration using oEmbed API
	 *
	 * @param string $video_url Wistia video URL
	 * @return int Duration in seconds, 0 if failed
	 */
	private function get_wistia_duration_php( $video_url ) {
		$api_url = "https://fast.wistia.com/oembed?url=" . urlencode( $video_url );
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 10,
			'headers' => [
				'User-Agent' => 'WordPress/ThriveThemes'
			]
		] );

		if ( is_wp_error( $response ) ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return ! empty( $data['duration'] ) ? (int) $data['duration'] : 0;
	}

	/**
	 * Extracts YouTube video ID from various URL formats
	 *
	 * @param string $url YouTube URL
	 * @return string|null Video ID or null if not found
	 */
	private function extract_youtube_id( $url ) {
		preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches );
		return ! empty( $matches[1] ) ? $matches[1] : null;
	}

	/**
	 * Converts ISO 8601 duration to seconds
	 *
	 * @param string $duration ISO 8601 duration (e.g., PT4M13S)
	 * @return int Duration in seconds
	 */
	private function iso8601_to_seconds( $duration ) {
		try {
			$interval = new \DateInterval( $duration );
			return ( $interval->h * 3600 ) + ( $interval->i * 60 ) + $interval->s;
		} catch ( \Exception $e ) {
			return 0;
		}
	}

	/**
	 * Gets YouTube API key from WordPress options, constants, or existing keys
	 *
	 * @return string|null API key or null if not found
	 */
	private function get_youtube_api_key() {
		// Check for constant first
		if ( defined( 'YOUTUBE_API_KEY' ) ) {
			return YOUTUBE_API_KEY;
		}

		// Check WordPress options
		$api_key = get_option( 'tve_youtube_api_key' );
		if ( ! empty( $api_key ) ) {
			return $api_key;
		}

		// Use existing API keys from the codebase as fallbacks
		// These are the same keys used in the frontend JavaScript
		$existing_keys = [
			'AIzaSyDpi07JtIdicPoLfgp1qbEfRM6kYFsseb4', // Primary key from video-popup.js
			'AIzaSyD9QcdlSlJ2yEg2DmE_ULM2hZCxChaYMD8', // Fallback key from video-popup.js
		];

		// Try each key to see if it works
		foreach ( $existing_keys as $key ) {
			if ( $this->test_youtube_api_key( $key ) ) {
				return $key;
			}
		}

		return null;
	}

	/**
	 * Tests if a YouTube API key is valid by making a simple API request
	 *
	 * @param string $api_key The API key to test
	 * @return bool True if key is valid, false otherwise
	 */
	private function test_youtube_api_key( $api_key ) {
		// Use a simple test video ID that should always exist
		$test_video_id = 'dQw4w9WgXcQ'; // Rick Roll - reliable test video
		$api_url = "https://www.googleapis.com/youtube/v3/videos?id={$test_video_id}&part=id&key={$api_key}";
		
		$response = wp_remote_get( $api_url, [
			'timeout' => 5, // Shorter timeout for testing
			'headers' => [
				'User-Agent' => 'WordPress/ThriveThemes'
			]
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Check if API responded successfully and found the video
		return ! empty( $data['items'] ) && ! isset( $data['error'] );
	}


}

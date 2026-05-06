<?php

/**
 * Class TVA_Video
 * - handles embed code for data provided
 */
class TVA_Video extends TVA_Media {

	/**
	 * Ready made embed code for Custom
	 *
	 * @return string
	 */
	protected function _custom_embed_code() {

		$data = $this->_data;

		/**
		 * If by any change someone puts a wistia url here we try to generate the html based on that url
		 *
		 * @see tva_get_custom_embed_code() wtf is this ?
		 */
		if ( preg_match( '/wistia/', $data['source'] ) && ! preg_match( '/(script)|(iframe)/', $data['source'] ) ) {
			$this->_data['type'] = 'wistia';

			return $this->_wistia_embed_code();
		}

		return html_entity_decode( $data['source'] );
	}

	/**
	 * Ready made embed code for Wistia
	 *
	 * @return string
	 */
	protected function _wistia_embed_code() {

		$url     = ! empty( $this->_data['source'] ) ? $this->_data['source'] : '';
		$url     = preg_replace( '/\?.*/', '', $url );
		$options = empty( $this->_data['options'] ) ? array() : $this->_data['options'];

		$split = parse_url( $url );
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) || strpos( $split['host'], 'wistia' ) === false ) {
			return '';
		}

		$exploded = explode( '/', $split['path'] );
		$video_id = end( $exploded );

		$embed_options = array( 'autoplay' => 'false', 'controls' => 'true', 'fullscreen' => 'true' );
		if ( isset( $options['autoplay'] ) ) {
			$embed_options['autoplay'] = 'true';
		}
		if ( isset( $options['hide-controls'] ) ) {
			$embed_options['controls'] = 'false';
		}
		if ( isset( $options['hide-full-screen'] ) ) {
			$embed_options['fullscreen'] = 'false';
		}

		$embed_code = "
		<script>
			window._wq = window._wq || [];
			_wq.push( {
				id: '" . $video_id . "',
				options: {
					autoPlay: " . $embed_options['autoplay'] . ",
					controlsVisibleOnLoad: " . $embed_options['controls'] . ",
					fullscreenButton: " . $embed_options['fullscreen'] . ",
					playerColor: '#000000',
				},
			} );
		</script>";
		$embed_code .= '<div class="wistia_embed wistia_async_' . $video_id . '" style="height:360px;width:640px">&nbsp;</div>';
		$embed_code .= '<script src="https://fast.wistia.com/assets/external/E-v1.js" async></script>';

		return $embed_code;
	}

	/**
	 * Ready made embed code for Vimeo
	 *
	 * @return string
	 */
	protected function _vimeo_embed_code() {

		$width   = '100%';
		$source  = ! empty( $this->_data['source'] ) ? $this->_data['source'] : '';
		$options = empty( $this->_data['options'] ) ? array() : $this->_data['options'];

		/**
		 * Vimeo videos can be of 2 forms:
		 * 1. https://vimeo.com/604925161
		 * 2. https://vimeo.com/614046843/a485d4710e
		 */
		if ( ! preg_match( '/(http|https)?:\/\/(www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|video\/|)(\d+)(?:|\/\?)(\/(.+))?/', $source, $m ) ) {
			return '';
		}

		$video_id = $m[4];
		$rand_id  = 'player' . rand( 1, 1000 );

		$src_url       = '//player.vimeo.com/video/' . $video_id;
		$embed_options = array( 'autoplay' => 'autoplay=false', 'title' => 'title=true', 'byline' => 'byline=true', 'portrait' => 'portrait=true', 'fullscreen' => 'webkitallowfullscreen mozallowfullscreen allowfullscreen' );
		if ( isset( $options['autoplay'] ) ) {
			$embed_options['autoplay'] = 'autoplay=true&muted=true';
		}
		if ( isset( $options['hide-title'] ) ) {
			$embed_options['title'] = 'title=false';
		}
		if ( isset( $options['hide-byline'] ) ) {
			$embed_options['byline'] = 'byline=false';
		}
		if ( isset( $options['hide-portrait'] ) ) {
			$embed_options['portrait'] = 'portrait=false';
		}
		if ( isset( $options['hide-full-screen'] ) ) {
			$embed_options['fullscreen'] = '';
		}

		if ( ! empty( $m[6] ) ) {
			$src_url .= '?h=' . $m[6];
		}

		$src_url .= strpos( $src_url, '?' ) === false ? '?' : '&';

		$video_height = '400';

		return "<iframe id='" . $rand_id . "' src='" . $src_url . $embed_options['autoplay'] . '&' . $embed_options['title'] . '&' . $embed_options['byline'] . '&' . $embed_options['portrait'] . "' height='" . $video_height . "' width='" . $width . "' frameborder='0' " . $embed_options['fullscreen'] . "></iframe>";
	}

	/**
	 * Ready made embed code for YouTube
	 *
	 * @return string
	 */
	protected function _youtube_embed_code() {

		$url_params = array();
		$rand_id    = 'player' . rand( 1, 1000 );
		$video_url  = empty( $this->_data['source'] ) ? '' : $this->_data['source'];
		$options    = empty( $this->_data['options'] ) ? array() : $this->_data['options'];

		if ( empty( $video_url ) ) {
			return '';
		}

		parse_str( parse_url( $video_url, PHP_URL_QUERY ), $url_params );

		$video_id = ( isset( $url_params['v'] ) ) ? trim( $url_params['v'] ) : 0;

		if ( strpos( $video_url, 'youtu.be' ) !== false ) {
			$chunks   = array_filter( explode( '/', $video_url ) );
			$video_id = array_pop( $chunks );
		}

		/**
		 * Check if the url is a shorts url
		 */
		if ( str_contains( $video_url, 'shorts' ) ) {
			$chunks   = array_filter( explode( '/', $video_url ) );
			$video_id = array_pop( $chunks );
		}

		$src_url = '//www.youtube.com/embed/' . $video_id . '?not_used=1';

		/**
		 * Check if the url is a playlist url
		 */
		$matches = array();

		preg_match( '/^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:embed\/|list\/|playlist\?list=|playlist\?.+&list=))((\w|-){34})(?:\S+)?$/', $video_url, $matches );

		if ( isset( $matches[1] ) ) {
			$src_url = '//www.youtube.com/embed?listType=playlist&list=' . $matches[1];
		}
		if ( ! isset( $options['show-related'] ) || ( isset( $options['show-related'] ) && ( $options['show-related'] == 0 || $options['show-related'] === 'false' ) ) ) {
			$src_url .= '&rel=0';
		}
		if ( isset( $options['hide-logo'] ) ) {
			$src_url .= '&modestbranding=1';
		}
		if ( isset( $options['hide-controls'] ) ) {
			$src_url .= '&controls=0';
		}
		if ( isset( $options['hide-title'] ) ) {
			$src_url .= '&showinfo=0';
		}
		$hide_fullscreen = 'allowfullscreen';
		if ( isset( $options['hide-full-screen'] ) ) {
			$src_url .= '&fs=0';
		}
		if ( isset( $options['autoplay'] ) ) {
			$src_url .= '&autoplay=1&mute=1';
		}
		if ( ! isset( $options['video_width'] ) ) {
			$options['video_width']  = '100%';
			$options['video_height'] = 400;
		} else {
			if ( $options['video_width'] > 1080 ) {
				$options['video_width'] = 1080;
			}
			$options['video_height'] = ( $options['video_width'] * 9 ) / 16;
		}

		return '<iframe id="' . $rand_id . '" src="' . $src_url . '" height="' . $options['video_height'] . '" width="' . $options['video_width'] . '" frameborder="0" ' . $hide_fullscreen . ' ></iframe>';
	}

	/**
	 * Ready made embed code for Bunny.net Stream
	 *
	 * @return string
	 */
	protected function _bunnynet_embed_code() {
		$video_url = empty( $this->_data['source'] ) ? '' : $this->_data['source'];
		$options   = empty( $this->_data['options'] ) ? array() : $this->_data['options'];
		$rand_id   = 'player' . wp_rand( 1, 1000 );

		if ( empty( $video_url ) ) {
			return '';
		}

		$video_library = '0';
		$video_id      = '0';
		$url_path      = parse_url( $video_url, PHP_URL_PATH );

		if ( ! empty( $url_path ) ) {
			$segments = array_values( array_filter( explode( '/', ltrim( $url_path, '/' ) ) ) );

			if ( count( $segments ) === 3 ) {
				$video_library = sanitize_text_field( $segments[1] );
				$video_id      = sanitize_text_field( $segments[2] );
			}
		}

		$src_url = sprintf(
			'https://iframe.mediadelivery.net/embed/%1$s/%2$s',
			rawurlencode( $video_library ),
			rawurlencode( $video_id )
		);

		$params     = array();
		$allow_attr = 'accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;';

		/* Add autoplay parameter */
		if ( ! empty( $options['autoplay'] ) ) {
			$params[] = 'autoplay=1';
		} else {
			$params[] = 'autoplay=0';
		}

		/* Add preload parameter */
		if ( empty( $options['preload'] ) && empty( $options['autoplay'] ) ) {
			$params[] = 'preload=false';
		}

		/* Add muted parameter */
		if ( ! empty( $options['muted'] ) || ! empty( $options['autoplay'] ) ) {
			$params[] = 'muted=true';
		}

		/* Add loop parameter */
		if ( ! empty( $options['loop'] ) ) {
			$params[] = 'loop=true';
		}

		/* Build the final URL with parameters */
		if ( ! empty( $params ) ) {
			$src_url .= '?' . implode( '&', $params );
		}

		$iframe = sprintf(
			'<iframe id="%1$s" src="%2$s" frameborder="0" allow="%3$s" loading="lazy" allowfullscreen ',
			esc_attr( $rand_id ),
			esc_url( $src_url ),
			esc_attr( $allow_attr )
		);

		if ( ! empty( $options['responsive'] ) ) {
			$iframe .= 'style="border: none; position: absolute; top: 0; height: 100%; width: 100%"></iframe>';

			return sprintf(
				'<div style="position: relative; padding-top: 56.25%%">%s</div>',
				$iframe
			);
		}

		$iframe .= 'style="border: none" width="1280" height="720"></iframe>';

		return $iframe;
	}

	/**
	 * Updates video durations for all video lessons with reporting enabled
	 *
	 * @return array Migration results with counts for processed, updated, and error items
	 */
	public static function update_all_video_durations() {
		$results = [
			'processed'      => 0,
			'updated'        => 0,
			'errors'         => 0,
			'total_lessons'  => 0,
			'timestamp'      => current_time( 'mysql' ),
		];

		try {
			$video_lessons = self::get_video_lessons_for_update();
			$results['total_lessons'] = count( $video_lessons );

			if ( empty( $video_lessons ) ) {
				return $results;
			}

			foreach ( $video_lessons as $lesson_id ) {
				$lesson_result = self::process_video_lesson( $lesson_id );
				
				$results['processed']++;
				
				if ( $lesson_result['updated'] ) {
					$results['updated']++;
				}
				
				if ( $lesson_result['error'] ) {
					$results['errors']++;
				}
			}

		} catch ( \Exception $e ) {
			$results['critical_error'] = $e->getMessage();
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'TVA Video Duration Update Critical Error: ' . $e->getMessage() );
			}
		}

		return $results;
	}

	/**
	 * Gets all video lessons that need duration updates
	 *
	 * @return array Array of lesson IDs
	 */
	private static function get_video_lessons_for_update() {
		return get_posts( [
			'post_type'      => TVA_Const::LESSON_POST_TYPE,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => 'tva_lesson_type',
					'value' => 'video',
				],
			],
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
	}

	/**
	 * Processes a single video lesson for duration update
	 *
	 * @param int $lesson_id Lesson post ID
	 * @return array Processing result with updated and error flags
	 */
	private static function process_video_lesson( $lesson_id ) {
		$result = [
			'updated' => false,
			'error'   => false,
		];

		try {
			$video_meta = get_post_meta( $lesson_id, 'tva_video', true );

			// Only process lessons with reporting enabled and valid video data
			if ( empty( $video_meta['progress_enabled'] ) || empty( $video_meta['source'] ) || empty( $video_meta['type'] ) ) {
				return $result;
			}

			$video = self::get_or_create_tcb_video( $lesson_id, $video_meta );
			
			if ( ! $video ) {
				$result['error'] = true;
				return $result;
			}

			$result['updated'] = $video->ensure_lesson_video_duration_stored( $lesson_id );

		} catch ( \Exception $e ) {
			$result['error'] = true;
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "TVA Video Duration Update Error for lesson {$lesson_id}: " . $e->getMessage() );
			}
		}

		return $result;
	}

	/**
	 * Gets existing or creates new TCB video record
	 *
	 * @param int   $lesson_id  Lesson post ID
	 * @param array $video_meta Video metadata
	 * @return \TCB\VideoReporting\Video|null Video instance or null on failure
	 */
	private static function get_or_create_tcb_video( $lesson_id, $video_meta ) {
		// Try to find existing TCB video record
		$video_id = \TCB\VideoReporting\Video::get_post_id_by_video_url( $video_meta['source'] );

		if ( $video_id ) {
			return new \TCB\VideoReporting\Video( $video_id );
		}

		// Create new TCB video record
		$video_data = [
			'url'                    => $video_meta['source'],
			'provider'               => $video_meta['type'],
			'title'                  => get_the_title( $lesson_id ),
			'percentage_to_complete' => ! empty( $video_meta['progress_percentage'] ) ? $video_meta['progress_percentage'] : 90,
			'duration'               => '',
		];

		$new_video_id = \TCB\VideoReporting\Video::insert_post( $video_data );

		if ( is_wp_error( $new_video_id ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "Failed to create TCB video record for lesson {$lesson_id}: " . $new_video_id->get_error_message() );
			}
			return null;
		}

		return new \TCB\VideoReporting\Video( $new_video_id );
	}
}

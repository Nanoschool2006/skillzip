<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thrive-wide user tag storage system.
 *
 * Tags are stored in user meta grouped by source (e.g. thrive-quiz-builder, thrive-leads).
 * All Thrive plugins use this class directly for tag CRUD operations.
 */
class Thrive_User_Tags {

	const META_KEY = 'thrive_user_tags';

	const TAG_MAX_LENGTH = 64;

	/**
	 * Allowed source identifiers — one per Thrive plugin that assigns user tags.
	 */
	const VALID_SOURCES = [
		'thrive-leads',
		'thrive-quiz-builder',
		'thrive-apprentice',
		'thrive-architect',
		'thrive-theme-builder',
		'thrive-ultimatum',
		'thrive-ovation',
		'thrive-comments',
		'thrive-optimize',
		'thrive-automator',
	];

	/**
	 * Add a tag for a user under a specific source.
	 *
	 * @param int    $user_id  WordPress user ID.
	 * @param string $tag_name Tag name to add.
	 * @param string $source   Source identifier (e.g. 'thrive-leads', 'thrive-quiz-builder').
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function add_tag( int $user_id, string $tag_name, string $source ) {
		tve_debug_log( "User_Tags::add_tag called: user_id={$user_id}, tag={$tag_name}, source={$source}" );

		$error = static::validate( $user_id, $tag_name, $source );
		if ( is_wp_error( $error ) ) {
			tve_debug_log( "User_Tags::add_tag validation failed: " . $error->get_error_code() );

			return $error;
		}

		$tags = static::get_stored_tags( $user_id );

		if ( isset( $tags[ $source ] ) && in_array( $tag_name, $tags[ $source ], true ) ) {
			tve_debug_log( "User_Tags::add_tag skipped: tag_exists (tag={$tag_name}, source={$source})" );

			return new WP_Error(
				'tag_exists',
				sprintf(
					/* translators: %1$s: tag name, %2$s: source name */
					__( 'Tag "%1$s" already exists for source "%2$s".', 'thrive-dash' ),
					esc_html( $tag_name ),
					esc_html( $source )
				),
				[ 'status' => 409 ]
			);
		}

		$tags[ $source ]   = $tags[ $source ] ?? [];
		$tags[ $source ][] = $tag_name;

		update_user_meta( $user_id, static::META_KEY, $tags );

		tve_debug_log( "User_Tags::add_tag success: user_id={$user_id}, tag={$tag_name}, source={$source}" );

		/**
		 * Fires after a tag is added to a user.
		 *
		 * @param array $payload {
		 *     @type int    $user_id   The user ID.
		 *     @type string $tag_name  The tag that was added.
		 *     @type string $source    The source that added the tag.
		 *     @type int    $timestamp Unix timestamp of when the tag was added.
		 * }
		 */
		do_action( 'thrivethemes_user_tag_added', [
			'user_id'   => $user_id,
			'tag_name'  => $tag_name,
			'source'    => $source,
			'timestamp' => time(),
		] );

		return true;
	}

	/**
	 * Remove a tag from a user.
	 *
	 * When $source is provided, removes only from that source.
	 * When $source is empty, removes the tag from ALL sources.
	 *
	 * @param int    $user_id  WordPress user ID.
	 * @param string $tag_name Tag name to remove.
	 * @param string $source   Optional. Source to remove from. Empty = all sources.
	 *
	 * @return true|WP_Error True on success, WP_Error on failure.
	 */
	public static function remove_tag( int $user_id, string $tag_name, string $source = '' ) {
		$scope = $source !== '' ? "source={$source}" : 'all sources';
		tve_debug_log( "User_Tags::remove_tag called: user_id={$user_id}, tag={$tag_name}, scope={$scope}" );

		$tag_name = static::sanitize_tag( $tag_name );
		$source   = trim( $source );

		$error = static::validate_user( $user_id );
		if ( is_wp_error( $error ) ) {
			tve_debug_log( "User_Tags::remove_tag validation failed: " . $error->get_error_code() );

			return $error;
		}

		$tag_error = static::validate_tag( $tag_name );
		if ( is_wp_error( $tag_error ) ) {
			tve_debug_log( "User_Tags::remove_tag validation failed: " . $tag_error->get_error_code() );

			return $tag_error;
		}

		if ( $source !== '' && ! in_array( $source, static::VALID_SOURCES, true ) ) {
			tve_debug_log( "User_Tags::remove_tag validation failed: invalid_source ({$source})" );

			return new WP_Error(
				'invalid_source',
				sprintf(
					/* translators: %1$s: source name, %2$s: list of allowed sources */
					__( 'Invalid source "%1$s". Allowed: %2$s.', 'thrive-dash' ),
					esc_html( $source ),
					implode( ', ', static::VALID_SOURCES )
				),
				[ 'status' => 400 ]
			);
		}

		$tags  = static::get_stored_tags( $user_id );
		$found = false;

		if ( $source !== '' ) {
			/* Remove from specific source */
			if ( isset( $tags[ $source ] ) ) {
				$key = array_search( $tag_name, $tags[ $source ], true );
				if ( $key !== false ) {
					array_splice( $tags[ $source ], $key, 1 );
					$found = true;

					if ( empty( $tags[ $source ] ) ) {
						unset( $tags[ $source ] );
					}
				}
			}
		} else {
			/* Remove from ALL sources */
			foreach ( $tags as $src => &$src_tags ) {
				$key = array_search( $tag_name, $src_tags, true );
				if ( $key !== false ) {
					array_splice( $src_tags, $key, 1 );
					$found = true;

					if ( empty( $src_tags ) ) {
						unset( $tags[ $src ] );
					}
				}
			}
			unset( $src_tags );
		}

		if ( ! $found ) {
			$context = $source !== '' ? " under source '{$source}'" : '';
			tve_debug_log( "User_Tags::remove_tag not found: tag={$tag_name}{$context}" );

			return new WP_Error(
				'tag_not_found',
				sprintf(
					/* translators: %1$s: tag name, %2$s: optional source context */
					__( 'Tag "%1$s" not found%2$s.', 'thrive-dash' ),
					esc_html( $tag_name ),
					esc_html( $context )
				),
				[ 'status' => 404 ]
			);
		}

		if ( empty( $tags ) ) {
			delete_user_meta( $user_id, static::META_KEY );
		} else {
			update_user_meta( $user_id, static::META_KEY, $tags );
		}

		tve_debug_log( "User_Tags::remove_tag success: user_id={$user_id}, tag={$tag_name}" );

		/**
		 * Fires after a tag is removed from a user.
		 *
		 * @param array $payload {
		 *     @type int    $user_id   The user ID.
		 *     @type string $tag_name  The tag that was removed.
		 *     @type string $source    The source it was removed from (empty if removed from all).
		 *     @type int    $timestamp Unix timestamp of when the tag was removed.
		 * }
		 */
		do_action( 'thrivethemes_user_tag_removed', [
			'user_id'   => $user_id,
			'tag_name'  => $tag_name,
			'source'    => $source,
			'timestamp' => time(),
		] );

		return true;
	}

	/**
	 * Check if a user has a specific tag.
	 *
	 * @param int    $user_id  WordPress user ID.
	 * @param string $tag_name Tag name to check.
	 * @param string $source   Optional. Check only within this source. Empty = any source.
	 *
	 * @return bool True if the user has the tag.
	 */
	public static function has_tag( int $user_id, string $tag_name, string $source = '' ): bool {
		tve_debug_log( "User_Tags::has_tag called: user_id={$user_id}, tag={$tag_name}" . ( $source !== '' ? ", source={$source}" : '' ) );

		$tag_name = static::sanitize_tag( $tag_name );
		$source   = trim( $source );

		if ( $user_id <= 0 || empty( $tag_name ) ) {
			tve_debug_log( 'User_Tags::has_tag result: false (invalid params)' );

			return false;
		}

		$tags = static::get_stored_tags( $user_id );

		if ( $source !== '' ) {
			$result = isset( $tags[ $source ] ) && in_array( $tag_name, $tags[ $source ], true );
			tve_debug_log( 'User_Tags::has_tag result: ' . ( $result ? 'true' : 'false' ) );

			return $result;
		}

		foreach ( $tags as $src_tags ) {
			if ( in_array( $tag_name, $src_tags, true ) ) {
				tve_debug_log( 'User_Tags::has_tag result: true' );

				return true;
			}
		}

		tve_debug_log( 'User_Tags::has_tag result: false' );

		return false;
	}

	/**
	 * Get tags for a user.
	 *
	 * Without $source: returns the full grouped structure ['source' => ['tag1', 'tag2']].
	 * With $source: returns a flat array of tags for that source ['tag1', 'tag2'].
	 *
	 * @param int    $user_id WordPress user ID.
	 * @param string $source  Optional. Specific source to retrieve tags for.
	 *
	 * @return array Tags grouped by source, or flat array for a specific source.
	 */
	public static function get_tags( int $user_id, string $source = '' ): array {
		tve_debug_log( "User_Tags::get_tags called: user_id={$user_id}" . ( $source !== '' ? ", source={$source}" : '' ) );

		$source = trim( $source );

		if ( $user_id <= 0 ) {
			tve_debug_log( 'User_Tags::get_tags result: 0 tags (invalid user_id)' );

			return [];
		}

		$tags = static::get_stored_tags( $user_id );

		if ( $source !== '' ) {
			$result = $tags[ $source ] ?? [];
			tve_debug_log( 'User_Tags::get_tags result: ' . count( $result ) . ' tags' );

			return $result;
		}

		$count = array_sum( array_map( 'count', $tags ) );
		tve_debug_log( "User_Tags::get_tags result: {$count} tags across " . count( $tags ) . ' sources' );

		return $tags;
	}

	/**
	 * Validate user ID, tag name, and source.
	 *
	 * @param int    $user_id  User ID.
	 * @param string $tag_name Tag name (will be trimmed).
	 * @param string $source   Source identifier (will be trimmed).
	 *
	 * @return true|WP_Error
	 */
	private static function validate( int $user_id, string &$tag_name, string &$source ) {
		$tag_name = static::sanitize_tag( $tag_name );
		$source   = trim( $source );

		$error = static::validate_user( $user_id );
		if ( is_wp_error( $error ) ) {
			return $error;
		}

		$tag_error = static::validate_tag( $tag_name );
		if ( is_wp_error( $tag_error ) ) {
			return $tag_error;
		}

		if ( empty( $source ) ) {
			return new WP_Error( 'invalid_source', __( 'Source must not be empty.', 'thrive-dash' ), [ 'status' => 400 ] );
		}

		if ( ! in_array( $source, static::VALID_SOURCES, true ) ) {
			return new WP_Error(
				'invalid_source',
				sprintf(
					/* translators: %1$s: source name, %2$s: list of allowed sources */
					__( 'Invalid source "%1$s". Allowed: %2$s.', 'thrive-dash' ),
					esc_html( $source ),
					implode( ', ', static::VALID_SOURCES )
				),
				[ 'status' => 400 ]
			);
		}

		return true;
	}

	/**
	 * Normalize a tag name: trim and lowercase.
	 *
	 * @param string $tag_name Raw tag name.
	 *
	 * @return string Normalized tag name.
	 */
	private static function sanitize_tag( string $tag_name ): string {
		return strtolower( trim( $tag_name ) );
	}

	/**
	 * Validate a normalized tag name.
	 *
	 * Allowed characters: a-z, 0-9, hyphens, underscores, spaces.
	 *
	 * @param string $tag_name Normalized tag name.
	 *
	 * @return true|WP_Error
	 */
	private static function validate_tag( string $tag_name ) {
		if ( empty( $tag_name ) ) {
			return new WP_Error( 'invalid_tag', __( 'Tag name must not be empty.', 'thrive-dash' ), [ 'status' => 400 ] );
		}

		if ( strlen( $tag_name ) > static::TAG_MAX_LENGTH ) {
			return new WP_Error(
				'invalid_tag',
				sprintf(
					/* translators: %d: maximum character count */
					__( 'Tag name must not exceed %d characters.', 'thrive-dash' ),
					static::TAG_MAX_LENGTH
				),
				[ 'status' => 400 ]
			);
		}

		if ( preg_match( '/[^a-z0-9\-_ ]/', $tag_name ) ) {
			return new WP_Error( 'invalid_tag', __( 'Tag name contains invalid characters. Allowed: a-z, 0-9, hyphens, underscores, spaces.', 'thrive-dash' ), [ 'status' => 400 ] );
		}

		return true;
	}

	/**
	 * Validate that a user ID is valid and the user exists.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return true|WP_Error
	 */
	private static function validate_user( int $user_id ) {
		if ( $user_id <= 0 ) {
			return new WP_Error( 'invalid_user', __( 'User ID must be greater than zero.', 'thrive-dash' ), [ 'status' => 400 ] );
		}

		if ( ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'invalid_user',
				sprintf(
					/* translators: %d: user ID */
					__( 'User with ID %d does not exist.', 'thrive-dash' ),
					$user_id
				),
				[ 'status' => 404 ]
			);
		}

		return true;
	}

	/**
	 * Get stored tags from user meta.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return array Tags grouped by source.
	 */
	private static function get_stored_tags( int $user_id ): array {
		$tags = get_user_meta( $user_id, static::META_KEY, true );

		return is_array( $tags ) ? $tags : [];
	}
}

<?php
/**
 * Thrive Themes - Name parsing utility
 * 
 * Provides robust name parsing functionality that handles compound surnames
 * and international naming conventions.
 *
 * @package thrive-dashboard
 */

namespace TVE\Dashboard\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Name_Parser
 * 
 * Utility class for parsing full names into first and last name parts,
 * handling compound surnames from various cultures.
 */
class Name_Parser {
	/**
	 * Get surname prepositions/particles from different cultures
	 * 
	 * This method provides a comprehensive list of surname prepositions used in different cultures
	 * to properly split compound surnames like "Van Wielemaker", "de la Cruz", etc.
	 * 
	 * Developers can extend this list using the 'thrive_surname_prepositions' filter:
	 * 
	 * Example:
	 * add_filter( 'thrive_surname_prepositions', function( $prepositions ) {
	 *     $prepositions[] = 'custom_preposition';
	 *     return $prepositions;
	 * } );
	 * 
	 * @return array List of surname prepositions (lowercase)
	 */
	public static function get_surname_prepositions() {
		$prepositions = array(
			// Multiple cultures
			'de', 'del',
			// Dutch/Flemish
			'van', 'van der', 'van den', 'van de', 'den', 'der',
			// German/Austrian  
			'von', 'zu', 'zur', 'zum',
			// Spanish/Portuguese
			'de la', 'de las', 'de los', 'da', 'do', 'dos', 'das',
			// French
			'du', 'des', 'le', 'la', 'les',
			// Italian
			'di', 'da', 'della', 'delle', 'dei', 'degli',
			// Irish/Scottish
			'mac', 'mc', 'o\'', 'o',
			// Arabic
			'al', 'el', 'ibn', 'bin',
			// Other
			'dela', 'dela cruz', 'st', 'saint'
		);

		/**
		 * Filter surname prepositions to allow customization for different cultures
		 * 
		 * @param array $prepositions List of surname prepositions
		 */
		return apply_filters( 'thrive_surname_prepositions', $prepositions );
	}

	/**
	 * Find the position where the surname starts based on prepositions
	 * 
	 * Searches for surname prepositions using regex with word boundaries to prevent false matches.
	 * Uses PHP_INT_MAX as sentinel value for clean comparison logic.
	 * 
	 * @param string $full_name The full name string
	 * @return int|false Position where surname starts, or false if no preposition found
	 */
	protected static function find_surname_start_position( $full_name ) {
		$prepositions = self::get_surname_prepositions();
		
		// Use PHP_INT_MAX as sentinel - any valid position will be smaller
		$best_position = PHP_INT_MAX;
		
		foreach ( $prepositions as $preposition ) {
			// Use word boundaries to ensure we match complete words only.
			// Replace spaces with \s+ to match multiple spaces.
			$pattern = '/\b' . str_replace(' ', '\s+', preg_quote($preposition, '/')) . '\b/i';

			// If no match, continue to the next preposition.
			if ( ! preg_match( $pattern, $full_name, $matches, PREG_OFFSET_CAPTURE ) ) {
				continue;
			}

			$position = $matches[0][1];

			// Accept position if it's not at the beginning and is earlier than current best
			if ( $position > 0 && $position < $best_position ) {
				$best_position = $position;
			}
		}
		
		// Return the earliest position found, or false if no valid preposition was found
		return $best_position === PHP_INT_MAX ? false : $best_position;
	}

	/**
	 * Parse a full name into first and last name parts, handling compound surnames
	 * 
	 * Uses an optimized string-based approach with regex and word boundaries to efficiently
	 * detect surname prepositions. The algorithm finds the earliest preposition position
	 * using PHP_INT_MAX as a sentinel value for clean comparison logic.
	 * 
	 * Works for a wide range of test cases including:
	 * - Common name formats and patterns
	 * - International naming conventions
	 * - Edge cases and special formatting
	 * - Validation against false matches
	 * 
	 * @param string $full_name The full name to parse
	 * @return array Array with first name and last name [first_name, last_name]
	 */
	public static function parse( $full_name ) {
		if ( empty( $full_name ) ) {
			return array( '', '' );
		}

		// Clean the name
		$full_name = trim( preg_replace( '/\s+/', ' ', $full_name ) );
		
		// Handle single word names
		if ( strpos( $full_name, ' ' ) === false ) {
			return array(
				sanitize_text_field( $full_name ),
				'',
			);
		}

		// Try to find a preposition-based split
		$surname_start_pos = self::find_surname_start_position( $full_name );
		
		if ( $surname_start_pos === false ) {
			// Fallback to traditional approach (last word as surname)
			$surname_start_pos = strrpos( $full_name, ' ' );
		}

		// Split based on preposition
		$first_name = trim( substr( $full_name, 0, $surname_start_pos ) );
		$last_name = trim( substr( $full_name, $surname_start_pos ) );

		return array(
			sanitize_text_field( $first_name ),
			sanitize_text_field( $last_name ),
		);
	}
}


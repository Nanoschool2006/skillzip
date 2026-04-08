<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProMathController {

	/**
	 * Process content of frm-math shortcode and evaluate as a math expression.
	 *
	 * @param array  $atts
	 * @param string $content
	 *
	 * @return string
	 */
	public static function math_shortcode( $atts, $content = '' ) {
		if ( '0' === $content ) {
			return '0';
		}

		if ( ! $content ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'decimal'       => 0,
				'dec_point'     => '.',
				'thousands_sep' => ',',
				'error'         => '',
				'clean'         => 0,
			),
			$atts,
			'frm-math'
		);

		$expression = self::get_math_expression_from_shortcode_content( $content, $atts );

		if ( $expression === '' ) {
			return $expression;
		}

		if ( self::expression_contains_non_math_characters( $expression ) ) {
			return self::get_error_content( $atts, $expression );
		}

		$result = self::calculate_math_in_string( $expression );

		if ( ! is_numeric( $result ) ) {
			return self::get_error_content( $atts, $expression );
		}

		return number_format( $result, $atts['decimal'], $atts['dec_point'], $atts['thousands_sep'] );
	}

	/**
	 * Process frm-math shortcode content into string with appropriate content for math expression.
	 *
	 * @param string $content
	 * @param array  $atts
	 *
	 * @return string
	 */
	private static function get_math_expression_from_shortcode_content( $content, $atts ) {
		$expression = do_shortcode( $content );
		$expression = preg_replace( '/&#8211;/', '-', $expression );

		return self::clear_expression_of_extra_characters( $expression, $atts );
	}

	/**
	 * Remove non-math characters or extra spaces, depending on the value of the clean attribute
	 *
	 * @param string $expression
	 * @param array  $atts
	 *
	 * @return string
	 */
	private static function clear_expression_of_extra_characters( $expression, $atts ) {
		if ( ! empty( $atts['clean'] ) ) {
			// Strip tags first.
			// The other regex replaces do not strip the numbers or hyphens used in HTML open tags.
			// Without this the calculations include unexpected invisible numbers and too many minus symbols.
			$expression = strip_tags( $expression );

			// Remove /> and </ so HTML tags can be fully removed
			$expression = preg_replace( '/\/>|<\/|&lt;\/|\/&gt;/', '', $expression );
			return preg_replace( '/[^\+\-\/\*0-9\.\(\)\%]/', '', $expression );
		}

		return preg_replace( '/[\s\,]/', '', $expression );
	}

	/**
	 * Tests if an expression contains characters that don't belong in a math expression, e.g. letters
	 *
	 * @param string $expression
	 *
	 * @return bool
	 */
	private static function expression_contains_non_math_characters( $expression ) {
		$result = preg_match( '/[^\+\-\/\*0-9\.\s\(\)\%]/', $expression );
		return $result === 1;
	}

	/**
	 * Returns appropriate error content.
	 *
	 * @param array  $atts
	 * @param string $expression
	 *
	 * @return string
	 */
	private static function get_error_content( $atts, $expression ) {
		if ( ! isset( $atts['error'] ) ) {
			return '';
		}

		return $atts['error'] === 'debug' ? $expression : $atts['error'];
	}

	/**
	 * Calculate value of string math expression.
	 *
	 * @param string $math_string
	 *
	 * @return mixed
	 */
	private static function calculate_math_in_string( $math_string ) {
		$math_array = self::parse_math_string_into_array( $math_string );

		if ( ! is_array( $math_array ) ) {
			return $math_array;
		}

		$post_fix = self::convert_to_postfix( $math_array );

		if ( ! is_array( $post_fix ) ) {
			return $post_fix;
		}

		return self::evaluate_postfix_expression( $post_fix );
	}

	/**
	 * Convert math expression string into array.
	 *
	 * @param $math_string
	 *
	 * @return array
	 */
	private static function parse_math_string_into_array( $math_string ) {
		$math_array = preg_split( '/([\+\-\*\/\(\)\%])/', $math_string, - 1, PREG_SPLIT_DELIM_CAPTURE );
		$math_array = array_filter(
			$math_array,
			/**
			 * @param string $string
			 *
			 * @return bool
			 */
			function ( $string ) {
				return $string !== '';
			}
		);
		$math_array = array_values( $math_array );

		return self::set_negative_numbers( $math_array );
	}

	/**
	 * Convert numbers in math array to negative numbers, where appropriate.
	 *
	 * @param string[] $math_array
	 *
	 * @return array
	 */
	private static function set_negative_numbers( $math_array ) {
		$negatives = preg_grep( '/\-/', $math_array );

		if ( $negatives === array() ) {
			return $math_array;
		}

		foreach ( $negatives as $key => $negative ) {
			if ( $key === 0 || ( preg_match( '/[\-\+\*\/\(\%]/', $math_array[ $key - 1 ] ) > 0 ) ) {
				$next = $key + 1;

				if ( isset( $math_array[ $next ] ) && is_numeric( $math_array[ $next ] ) ) {
					$math_array[ $next ] = - 1 * $math_array[ $next ];
					unset( $math_array[ $key ] );
				}
			}
		}

		return array_values( $math_array );
	}

	/**
	 * Convert infix (normal) math expression to postfix.
	 *
	 * @param array $math_array
	 *
	 * @return array|string
	 */
	private static function convert_to_postfix( $math_array ) {
		$output    = array();
		$operators = array();

		foreach ( $math_array as $element ) {
			if ( is_numeric( $element ) ) {
				array_push( $output, $element );
			} elseif ( self::is_operator( $element ) ) {
				self::process_next_operator_in_postfix_conversion( $output, $operators, $element );
			} elseif ( '(' === $element ) {
				array_push( $operators, $element );
			} elseif ( ')' === $element ) {
				$status = self::process_right_paren_in_postfix_conversion( $output, $operators );

				if ( false === $status ) {
					return 'error';
				}
			}
		}

		$status = self::move_remaining_operators_to_stack( $output, $operators );

		return false === $status ? 'error' : $output;
	}

	/**
	 * Determine if a given element is an operator.
	 *
	 * @param $element
	 *
	 * @return bool
	 */
	private static function is_operator( $element ) {
		return self::operator_precedence( $element ) > 0;
	}

	/**
	 * Return precedence of operator.  Higher is greater precedence.
	 *
	 * @param string $operator
	 *
	 * @return int
	 */
	private static function operator_precedence( $operator ) {
		switch ( $operator ) {
			case '-':
			case '+':
				return 5;
			case '*':
			case '/':
			case '%':
				return 7;
			default:
				return 0;
		}
	}

	/**
	 * Process operators in conversion to postfix expression.
	 * Postfix rules: http://csis.pace.edu/~wolf/CS122/infix-postfix.htm
	 *
	 * @param array  $output
	 * @param array  $operators
	 * @param string $operator
	 */
	private static function process_next_operator_in_postfix_conversion( &$output, &$operators, $operator ) {
		if ( count( $operators ) > 0 ) {
			$current_element_precedence = self::operator_precedence( $operator );
			do {
				$top_operator            = array_pop( $operators );
				$top_operator_precedence = self::operator_precedence( $top_operator );

				if ( $current_element_precedence <= $top_operator_precedence ) {
					array_push( $output, $top_operator );
				} else {
					// Replace last top operator in operator stack; it was only removed for testing
					array_push( $operators, $top_operator );
				}

				$operators_count = count( $operators );
			} while ( $operators_count > 0 && $current_element_precedence <= $top_operator_precedence );
		}

		array_push( $operators, $operator );
	}

	/**
	 * Process right parenthesis in conversion to postfix expression.
	 *
	 * @param array $output
	 * @param array $operators
	 *
	 * @return bool
	 */
	private static function process_right_paren_in_postfix_conversion( &$output, &$operators ) {
		if ( $operators === array() ) {
			return false;
		}
		do {
			$next_operator = array_pop( $operators );

			if ( $next_operator === '(' ) {
				continue;
			}

			array_push( $output, $next_operator );

			if ( $operators === array() ) {
				return false;
			}
		} while ( $next_operator !== '(' );

		return true;
	}

	/**
	 * Move remaining operators to output stack in conversion to postfix expression.
	 *
	 * @param $output
	 * @param $operators
	 *
	 * @return bool
	 */
	private static function move_remaining_operators_to_stack( &$output, &$operators ) {
		$operators_count = count( $operators );

		while ( $operators_count > 0 ) {
			$next_operator = array_pop( $operators );

			if ( $next_operator === '(' ) {
				return false;
			}

			array_push( $output, $next_operator );

			$operators_count = count( $operators );
		}

		return true;
	}

	/**
	 * Evaluate postfix expression mathematically.
	 *
	 * @param array $postfix_array
	 *
	 * @return mixed
	 */
	private static function evaluate_postfix_expression( $postfix_array ) {
		$stack = array();

		foreach ( $postfix_array as $element ) {
			if ( is_numeric( $element ) ) {
				array_push( $stack, $element );
			} elseif ( count( $stack ) >= 2 ) {
					$operand2 = array_pop( $stack );
					$operand1 = array_pop( $stack );
					$result   = self::evaluate_simple_math_expression( $operand1, $operand2, $element );

				if ( ! is_numeric( $result ) ) {
					return 'error';
				}

					array_push( $stack, $result );
			} else {
				return 'error';
			}
		}

		if ( count( $stack ) === 1 ) {
			$answer = array_pop( $stack );

			if ( is_numeric( $answer ) ) {
				return $answer;
			}
		}

		return 'error';
	}

	/**
	 * Perform simple arithmetic on two operands.
	 *
	 * @param float|int|string $operand1
	 * @param float|int|string $operand2
	 * @param string           $operator
	 *
	 * @return float|int
	 */
	private static function evaluate_simple_math_expression( $operand1, $operand2, $operator ) {
		$result = 'error';

		switch ( $operator ) {
			case '-':
				$result = $operand1 - $operand2;
				break;
			case '+':
				$result = $operand1 + $operand2;
				break;
			case '*':
				$result = $operand1 * $operand2;
				break;
			case '%':
				if ( $operand2 != 0 ) {
					$result = $operand1 % $operand2;
				}
				break;
			case '/':
				if ( $operand2 != 0 ) {
					$result = $operand1 / $operand2;
				}
				break;
		}

		return $result;
	}

	/**
	 * Maybe force calculation during form validation.
	 *
	 * @since 6.21
	 *
	 * @param array    $errors
	 * @param stdClass $posted_field
	 * @param mixed    $value The current posted value for the field being validated.
	 * @param array    $args  Validation args including parent_field_id and key_pointer.
	 *
	 * @return array
	 */
	public static function maybe_force_calculation( $errors, $posted_field, $value = '', $args = array() ) {
		if ( empty( $posted_field->field_options['calc'] ) ) {
			return $errors;
		}

		/**
		 * This is still experimental and disabled by default.
		 * It's built in to help with testing, and offers our customers an option.
		 *
		 * @since 6.21
		 *
		 * @param bool     $should_force_calculation
		 * @param stdClass $posted_field
		 */
		$should_force_calculation = apply_filters( 'frm_force_calculation_on_validate', false, $posted_field );

		if ( ! $should_force_calculation ) {
			return $errors;
		}

		if ( FrmProEntryMeta::is_field_conditionally_hidden( $posted_field ) || FrmProEntryMeta::field_is_hidden_by_form_state( $posted_field ) ) {
			return $errors;
		}

		$calc_type = $posted_field->field_options['calc_type'];

		if ( $calc_type && 'text' !== $calc_type ) {
			// Skip date calculations.
			return $errors;
		}

		$calculation = $posted_field->field_options['calc'];
		$shortcodes  = self::get_shortcodes_from_string( $calculation );

		foreach ( $shortcodes as $shortcode ) {
			$field = FrmField::getOne( $shortcode );

			if ( ! $field ) {
				continue;
			}

			$field_id           = $field->id;
			$field_value        = self::get_calc_field_value( $field_id, $args, $calc_type, $field->type );
			$has_repeater_value = false;

			// When calc field is outside repeater, aggregate values from repeater fields across all rows.
			if ( '' === $field_value && empty( $args['parent_field_id'] ) ) {
				$field_value        = self::collect_repeater_field_values( $field_id, $field, $calc_type );
				$has_repeater_value = '' !== $field_value;
			}

			$replace_value = FrmAppHelper::strip_most_html( $field_value );

			if ( ! $has_repeater_value ) {
				if ( FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $field, 'format' ) ) ) {
					$replace_value = FrmProCurrencyHelper::normalize_formatted_numbers( $field, $replace_value );
				}

				if ( ! $calc_type ) {
					$replace_value = floatval( $replace_value );
				}
			}

			$calculation = str_replace( '[' . $shortcode . ']', $replace_value, $calculation );
		}

		if ( ! $calc_type ) {
			$trim           = false;
			$shortcode_atts = array(
				'thousands_sep' => '',
			);

			if ( FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $posted_field, 'format' ) ) ) {
				$currency                  = FrmProCurrencyHelper::get_custom_currency( $posted_field->field_options );
				$shortcode_atts['decimal'] = $currency['decimals'];
			} else {
				$shortcode_atts['decimal'] = 10;
				$trim                      = true;
			}

			$calculation = self::math_shortcode( $shortcode_atts, $calculation );

			if ( $trim ) {
				$calculation = rtrim( rtrim( $calculation, '0' ), '.' );
			}
		}

		FrmEntriesHelper::set_posted_value( $posted_field, $calculation, $args );

		return $errors;
	}

	/**
	 * Collect and aggregate all values of a field from all repeater rows in the posted data.
	 *
	 * @since 6.29
	 *
	 * @param int|string $field_id  The field ID to look up in repeater rows.
	 * @param object     $field     The field object, used for currency normalization.
	 * @param string     $calc_type The calculation type: '' for math, 'text' for text.
	 *
	 * @return string
	 */
	private static function collect_repeater_field_values( $field_id, $field, $calc_type ) {
		$section_id = FrmField::get_option( $field, 'in_section' );

		if ( ! $section_id ) {
			return '';
		}

		$values       = array();
		$is_currency  = FrmProCurrencyHelper::is_currency_format( FrmField::get_option( $field, 'format' ) );
		$section_meta = FrmAppHelper::get_post_param( 'item_meta', array() )[ $section_id ] ?? array();

		foreach ( $section_meta as $row_key => $row_data ) {
			if ( $row_key && in_array( $row_key, array( 'form', 'row_ids' ), true ) ) {
				continue;
			}

			if ( ! is_array( $row_data ) || ! isset( $row_data[ $field_id ] ) ) {
				continue;
			}

			$row_value = FrmProEntriesHelper::get_posted_meta(
				$field_id,
				array(
					'parent_field_id' => $section_id,
					'key_pointer'     => $row_key,
				)
			);

			if ( '' === $row_value ) {
				continue;
			}

			if ( is_array( $row_value ) ) {
				$row_value = self::convert_array_value_for_calc( $row_value, $calc_type, FrmField::get_field_type( $field ) );
			}

			if ( $is_currency ) {
				$row_value = FrmProCurrencyHelper::normalize_formatted_numbers( $field, $row_value );
			}

			$values[] = $row_value;
		}

		if ( ! $values ) {
			return '';
		}

		if ( ! $calc_type ) {
			/** @var string[] $values */
			return (string) array_sum( array_map( 'floatval', $values ) );
		}

		return implode( ', ', $values );
	}

	/**
	 * Get a field value by reading from the correct nested path and converts array values to strings.
	 *
	 * @since 6.29
	 *
	 * @param int|string $field_id   The field ID.
	 * @param array      $args       Validation args with parent_field_id and key_pointer.
	 * @param string     $calc_type  The calculation type: '' for math, 'text' for text.
	 * @param string     $field_type The field type (e.g. 'name', 'address', 'checkbox').
	 *
	 * @return string
	 */
	private static function get_calc_field_value( $field_id, $args, $calc_type = '', $field_type = '' ) {
		$value = FrmProEntriesHelper::get_posted_meta( $field_id, $args );

		if ( ! is_array( $value ) ) {
			return $value;
		}

		return self::convert_array_value_for_calc( $value, $calc_type, $field_type );
	}

	/**
	 * Convert an array field value to a string for a calculation.
	 *
	 * @since 6.29
	 *
	 * @param array  $value      The array value to convert.
	 * @param string $calc_type  The calculation type: '' for math, 'text' for text.
	 * @param string $field_type The field type (e.g. 'name', 'address', 'checkbox').
	 *
	 * @return string
	 */
	private static function convert_array_value_for_calc( $value, $calc_type, $field_type = '' ) {
		$flat = (array) FrmAppHelper::array_flatten( $value );

		if ( ! $calc_type ) {
			return (string) array_sum( array_map( 'floatval', $flat ) );
		}

		return implode(
			'name' === $field_type ? ' ' : ', ',
			array_filter(
				$flat,
				static function ( $item_value ) {
					return '' !== $item_value;
				}
			)
		);
	}

	/**
	 * Get shortcodes from string.
	 *
	 * @since 6.21
	 *
	 * @param string $string
	 *
	 * @return array
	 */
	private static function get_shortcodes_from_string( $string ) {
		preg_match_all( '/\[(\d+|[\w\s]+)\]/', $string, $matches );
		return ! empty( $matches[1] ) ? array_unique( $matches[1] ) : array();
	}
}

<?php
/**
 * Custom radio field controller
 *
 * @package FrmSurveys
 */

namespace FrmSurveys\controllers;

use FrmField;
use FrmFieldsHelper;
use FrmProImages;
use FrmSurveys\helpers\AppHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Radio/Checkbox buttons routing.
 *
 * @since 1.0
 */
class RadioController {

	/**
	 * Keeps record of choices of fields.
	 *
	 * @since 1.1.6
	 *
	 * @var array|null
	 */
	private static $field_choices;

	/**
	 * Extend the Radio/Checkbox field code.
	 *
	 * @param string $class The Class name.
	 * @param string $field_type The name of the field type.
	 *
	 * @since 1.0
	 */
	public static function change_radio_field_type_class( $class, $field_type ) {
		if ( 'radio' === $field_type ) {
			return '\\FrmSurveys\\models\\fields\\Radio';
		}
		if ( 'checkbox' === $field_type ) {
			return '\\FrmSurveys\\models\\fields\\Checkbox';
		}
		return $class;
	}

	/**
	 * Checks if image is enabled in the field.
	 *
	 * @param array $field Field data.
	 * @return bool
	 */
	public static function is_image_enabled( $field ) {
		$field = (array) $field;
		if ( ! isset( $field['type'] ) || ( 'radio' !== $field['type'] && 'checkbox' !== $field['type'] ) ) {
			return false;
		}

		$display_format        = FrmField::get_option( $field, 'image_options' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );
		return 1 === intval( $display_format ) || 'buttons' === $display_format && $use_images_in_buttons;
	}

	/**
	 * This is hooked to `frm_pro_field_should_show_images`.
	 *
	 * @param bool  $show Set to `true` to show.
	 * @param array $args The arguments.
	 * @return bool
	 */
	public static function field_should_show_images( $show, $args ) {
		$field = (array) $args['field'];
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $show;
		}

		return self::is_image_enabled( $field );
	}

	/**
	 * This is hooked to `frm_pro_field_should_show_label`.
	 *
	 * @param bool  $show Set to `true` to show.
	 * @param array $args The arguments.
	 * @return bool
	 */
	public static function field_should_show_label( $show, $args ) {
		$field = (array) $args['field'];
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $show;
		}

		if ( 'buttons' === FrmField::get_option( $field, 'image_options' ) ) {
			// Always show label in buttons format.
			return true;
		}

		return $show;
	}

	/**
	 * Adds custom CSS classes to field element.
	 *
	 * @param string $classes CSS classes.
	 * @param array  $field   Field data.
	 * @return string
	 */
	public static function add_field_classes( $classes, $field ) {
		$field = (array) $field;
		if ( ! in_array( $field['type'], array( 'radio', 'checkbox' ), true ) ) {
			return $classes;
		}

		if ( 'buttons' !== FrmField::get_option( $field, 'image_options' ) ) {
			return $classes;
		}

		$classes .= ' frm_display_format_buttons';

		$text_align            = FrmField::get_option( $field, 'text_align' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );

		$classes .= ' frm_text_align_' . $text_align;

		if ( $use_images_in_buttons && 'center' !== $text_align ) {
			$image_align = FrmField::get_option( $field, 'image_align' );
			$classes    .= ' frm_image_align_' . $image_align;
		}

		return $classes;
	}

	/**
	 * Changes the HTML of option label.
	 *
	 * @param string $label Label HTML.
	 * @param array  $args  The arguments. Contains `field`.
	 * @return string
	 */
	public static function choice_field_option_label( $label, $args ) {
		$field = $args['field'];

		$display_format        = FrmField::get_option( $field, 'image_options' );
		$use_images_in_buttons = FrmField::get_option( $field, 'use_images_in_buttons' );

		if ( 'buttons' === $display_format && ! $use_images_in_buttons ) {
			$remaining_choices_text = self::get_remaining_choices_text( $field, $args['field_val'] ?? '' );
			if ( ! $remaining_choices_text ) {
				return '<div class="frm_label_button_container">' . $label . '</div>';
			}
			// Add a wrapper div to change styling of the label.
			return sprintf(
				'<div class="frm_label_button_container">%1$s %2$s</div>',
				$label,
				$remaining_choices_text
			);
		}

		return $label;
	}

	/**
	 * Checks if remaining choices text should be shown.
	 *
	 * @since 1.1.6
	 *
	 * @param array  $field Field data.
	 * @param string $opt Option value.
	 *
	 * @return bool
	 */
	private static function should_get_remaining_choices_text( $field, $opt ) {
		if ( ! is_callable( array( 'FrmProFieldsHelper', 'should_show_remaining_choices' ) ) ) {
			return false;
		}
		if ( ! $opt && '0' !== $opt ) {
			return false;
		}
		if ( \FrmAppHelper::is_form_builder_page() || ! FrmField::get_option( $field, 'set_choices_limit' ) || ! \FrmProFieldsHelper::should_show_remaining_choices( $field ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Return remaining choices text for button formatted choices.
	 *
	 * @since 1.1.6
	 *
	 * @param array|object $field Field data.
	 * @param string       $opt Option value.
	 *
	 * @return string
	 */
	private static function get_remaining_choices_text( $field, $opt ) {
		if ( ! self::should_get_remaining_choices_text( $field, $opt ) ) {
			return '';
		}

		$field_id = absint( is_array( $field ) ? $field['id'] : $field->id );
		$choices  = self::get_field_choices( $field_id, $field );

		if ( count( $choices ) === count( $choices, COUNT_RECURSIVE ) ) {
			// If flat field choices, return empty string as that means choices limits are not set.
			return '';
		}

		$choice_values = FrmField::get_option( $field, 'separate_value' ) ? array_column( $choices, 'value' ) : array_column( $choices, 'label' );

		$choice_index = array_search( $opt, $choice_values, true );
		if ( false === $choice_index ) {
			return '';
		}

		$choice = $choices[ $choice_index ];
		if ( empty( $choice['limit'] ) ) {
			return '';
		}

		$opt_key = FrmField::get_option( $field, 'separate_value' ) ? $choice['value'] : $choice['label'];

		$use_contains = FrmField::get_field_type( $field ) === 'checkbox' || ( FrmField::get_field_type( $field ) === 'select' && FrmField::get_option( $field, 'multiple' ) );

		$shortcode_atts = array(
			'id'   => $field_id,
			'type' => 'count',
		);

		if ( $use_contains ) {
			$shortcode_atts[ $field_id . '_contains' ] = $opt_key;
		} else {
			$shortcode_atts[ $field_id ] = $opt_key;
		}

		$shortcode = '[frm-stats ' . \FrmAppHelper::array_to_html_params( $shortcode_atts ) . ']';

		$choice_entries_count = do_shortcode( $shortcode );
		$choices_left         = absint( $choice['limit'] ) - absint( $choice_entries_count );

		return \FrmAppHelper::kses( \FrmProFieldsHelper::get_remaining_qty_message( $choices_left, $field ), 'all' );
	}

	/**
	 * Returns field choices.
	 *
	 * @since 1.1.6
	 *
	 * @param int $field_id The field ID.
	 *
	 * @return array
	 */
	private static function get_field_choices( $field_id ) {
		if ( isset( self::$field_choices[ $field_id ] ) ) {
			return self::$field_choices[ $field_id ];
		}

		$choices = \FrmDb::get_var( 'frm_fields', array( 'id' => $field_id ), 'options' );
		\FrmAppHelper::unserialize_or_decode( $choices );

		self::$field_choices[ $field_id ] = $choices;

		return $choices;
	}

	/**
	 * Changes options of Display format setting of Radio field.
	 *
	 * @param array $options The options array.
	 * @return array
	 */
	public static function change_radio_display_format_options( $options ) {
		_deprecated_function( __METHOD__, '1.0.09', 'AppController::change_field_display_format_options' );
		return AppController::change_field_display_format_options( $options );
	}

	/**
	 * Changes args of Display format setting of Radio field.
	 *
	 * @param array $args        The arguments.
	 * @param array $method_args The arguments from the method. Contains `field`, `options`.
	 * @return array
	 */
	public static function change_radio_display_format_args( $args, $method_args ) {
		_deprecated_function( __METHOD__, '1.0.09', 'AppController::change_field_display_format_args' );
		return AppController::change_field_display_format_args( $args, $method_args );
	}
}

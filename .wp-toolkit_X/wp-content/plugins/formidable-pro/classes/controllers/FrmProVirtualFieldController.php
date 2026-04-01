<?php
/**
 * Virtual Field Controller class.
 *
 * @package FormidablePro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Handles virtual field hooks and processing.
 *
 * @since 6.29
 */
class FrmProVirtualFieldController {

	/**
	 * Loads hooks for virtual fields (frontend + admin).
	 *
	 * @return void
	 */
	public static function load_hooks() {
		add_filter( 'frm_force_calculation_on_validate', self::class . '::force_calculation_on_validate', 10, 2 );
		add_filter( 'frm_modify_posted_field_value', self::class . '::inject_value', 1, 4 );
		add_filter( 'frm_fields_in_form', self::class . '::exclude_virtual_from_form_html', 10, 2 );
	}

	/**
	 * Loads admin-only hooks for virtual fields.
	 *
	 * @return void
	 */
	public static function load_admin_hooks() {
		add_filter( 'frm_is_field_present_in_logic_options', self::class . '::exclude_virtual_from_logic', 10, 2 );
	}

	/**
	 * Force calculation on validate for virtual fields.
	 *
	 * @param bool $force Whether to force calculation on validate.
	 * @param object $field The field object.
	 *
	 * @return bool
	 */
	public static function force_calculation_on_validate( $force, $field ) {
		return $force || 'virtual' === FrmField::get_field_type( $field );
	}

	/**
	 * Injects default value for virtual fields.
	 *
	 * @param array|string $value  The value to inject.
	 * @param array $errors        The array of validation errors.
	 * @param object $posted_field The field object being processed.
	 * @param array $args          Additional arguments for context.
	 *
	 * @return mixed
	 */
	public static function inject_value( $value, $errors, $posted_field, $args ) {
		if ( 'virtual' !== FrmField::get_field_type( $posted_field ) ) {
			return $value;
		}

		if ( FrmProLookupFieldsHelper::has_lookup_configured( $posted_field ) ) {
			FrmProLookupFieldsHelper::force_process_lookup_for_field( $posted_field, $args );
			FrmEntriesHelper::get_posted_value( $posted_field, $value, $args );
			return $value;
		}

		$default_value = $posted_field->default_value ?? '';
		$value         = FrmProFieldsHelper::get_default_value( $default_value, $posted_field );
		FrmEntriesHelper::set_posted_value( $posted_field, $value, $args );

		return $value;
	}

	/**
	 * Exclude virtual fields from conditional logic options.
	 *
	 * Virtual fields don't support front-end conditional logic because they have no DOM element to show/hide.
	 *
	 * @param bool  $present Whether the field is present in logic options.
	 * @param array $args    Contains 'logic_field'.
	 *
	 * @return bool False if logic_field is a virtual field, original value otherwise.
	 */
	public static function exclude_virtual_from_logic( $present, $args ) {
		if ( ! $present ) {
			return $present;
		}

		$logic_field = $args['logic_field'] ?? null;

		if ( $logic_field && 'virtual' === $logic_field->type ) {
			return false;
		}

		return $present;
	}

	/**
	 * Exclude virtual fields from form HTML rendering.
	 *
	 * @param array $fields Array of fields to display in the form.
	 * @param array $args   Contains 'form'.
	 *
	 * @return array Filtered fields array without virtual fields.
	 */
	public static function exclude_virtual_from_form_html( $fields, $args ) {
		if ( self::can_show_virtual_field() ) {
			// Keep virtual fields visible on admin Edit Entry page.
			return $fields;
		}

		return array_filter(
			$fields,
			function ( $field ) {
				return 'virtual' !== FrmField::get_field_type( $field );
			}
		);
	}

	/**
	 * Whether the current context allows virtual fields to be visible.
	 *
	 * @since 6.29
	 *
	 * @return bool
	 */
	public static function can_show_virtual_field() {
		return FrmAppHelper::is_admin() && current_user_can( 'frm_edit_entries' );
	}
}

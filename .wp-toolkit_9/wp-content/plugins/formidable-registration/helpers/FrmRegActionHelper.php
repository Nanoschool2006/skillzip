<?php

/**
 * @since 2.0
 */
class FrmRegActionHelper {

	/**
	 * Get the default options for a new Register User action
	 *
	 * @since 2.0
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			'registration'     => 0,
			'login'            => 0,
			'reg_avatar'       => '',
			'reg_username'     => '',
			'reg_email'        => '',
			'reg_password'     => '',
			'reg_last_name'    => '',
			'reg_first_name'   => '',
			'reg_display_name' => '',
			'reg_user_url'     => '',
			'reg_role'         => 'subscriber',
			'reg_usermeta'     => array(),
			'reg_moderate'     => array(),
			'reg_redirect'     => '',
			'reg_redirect_url' => '',
			'reg_create_users' => '',
			'reg_create_role'  => array(),
			'event'            => array( 'create', 'update' ),
			'create_subsite'   => 0,
			'subsite_title'    => 'username',
			'subsite_domain'   => 'blog_title',
			'on_email_confirmation' => 'page',
			'open_in_new_tab'  => '',
		);
	}

	/**
	 * Check if any registration actions exist in database
	 *
	 * @since 2.0
	 *
	 * @return bool
	 */
	public static function registration_action_exists_in_db() {
		$where = array(
			'post_type'    => FrmFormActionsController::$action_post_type,
			'post_excerpt' => 'register',
			'post_status' => 'publish',
		);

		$action_id = FrmDb::get_var( 'posts', $where, 'ID', array(), 1 );

		return $action_id !== null;
	}

	/**
	 * Get the registration settings for a given form
	 *
	 * @since 2.0
	 * @since 3.0 Added the second parameter.
	 *
	 * @param object|int|boolean $form   Form ID or object.
	 * @param object|null        $action Form action.
	 * @return array
	 */
	public static function get_registration_settings_for_form( $form, $action = null ) {
		if ( is_numeric( $form ) ) {
			$form_id = $form;
		} else if ( is_object( $form ) ) {
			$form_id = $form->id;
		} else {
			return array();
		}

		global $frm_vars;
		if ( ! isset( $frm_vars['reg_settings'] ) ) {
			$frm_vars['reg_settings'] = array();
		}

		if ( isset( $frm_vars['reg_settings'][ $form_id ] ) ) {
			return $frm_vars['reg_settings'][ $form_id ];
		}

		// check for registration action
		if ( ! $action ) {
			$action = self::get_action_for_form( $form_id );
		}

		if ( $action && 'publish' == $action->post_status ) {
			$frm_vars['reg_settings'][ $form_id ] = $settings = $action->post_content;
		} else {
			$frm_vars['reg_settings'][ $form_id ] = $settings = array();
		}

		return $settings;
	}

	/**
	 * Gets registration action for a form (with repeater actions check).
	 *
	 * @since 3.0
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return object|false
	 */
	public static function get_action_for_form( $form_id ) {
		$actions          = FrmFormAction::get_action_for_form( $form_id, 'register' );
		$repeater_form_id = 0;

		// If no actions found, this might be a repeater form, we need to get actions from parent form then get the one with this repeater form.
		if ( ! $actions ) {
			$parent_form_id = FrmDb::get_var( 'frm_forms', array( 'id' => $form_id ), 'parent_form_id' );
			if ( $parent_form_id ) {
				$actions = FrmFormAction::get_action_for_form( $parent_form_id, 'register' );
				$repeater_form_id = intval( $form_id );
			}
		}

		foreach ( $actions as $action ) {
			$child_form = isset( $action->post_content['child_form'] ) ? intval( $action->post_content['child_form'] ) : 0;
			if ( $child_form === $repeater_form_id ) {
				return $action;
			}
		}
		return false;
	}

	/**
	 * Determines if a field should be included in the user meta select in the Register User action.
	 *
	 * User ID fields shouldn't be included in new user meta rows. Including the User ID can cause bugs.  It can clear out the User Id field on edit.
	 * User ID fields should be included if already selected. This is intentional for someone who isn't editing, and they need the user ID field option to stay.
	 *
	 * @since 2.03
	 *
	 * @param object $field Field object.
	 * @param int $field_id Id of the selected field.
	 *
	 * @return bool Whether the field should be included in the user meta select.
	 */
	public static function include_in_user_meta( $field, $field_id ) {
		return ( 'user_id' !== $field->type || (int) $field->id === (int) $field_id );
	}


	/**
	 * Shows redirect settings.
	 *
	 * @since 2.13
	 *
	 * @param array $args {
	 *     The args.
	 *
	 *     @type object        $form_action    Form action post data.
	 *     @type FrmFormAction $action_control Form action object.
	 *     @type object        $form           Form data.
	 *     @type string        $action_key     Action key.
	 *     @type array         $values         Contains `fields` (form fields) and `id` (form ID).
	 * }
	 *
	 * @return void
	 */
	public static function show_redirect_settings( $args ) {
		$id = $args['action_control']->get_field_id( 'reg_redirect_url' );
		?>
		<div class="frm_form_field frm_has_shortcodes">
			<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Redirect URL', 'frmreg' ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( $args['action_control']->get_field_name( 'reg_redirect_url' ) ); ?>"
				value="<?php echo esc_attr( $args['form_action']->post_content['reg_redirect_url'] ); ?>"
			/>
		</div>

		<?php
		$id   = $args['action_control']->get_field_id( 'open_in_new_tab' );
		$name = $args['action_control']->get_field_name( 'open_in_new_tab' );
		if ( ! is_callable( 'FrmHtmlHelper::toggle' ) ) {
			return;
		}
		?>
		<div class="frm_form_field">
			<?php
			FrmHtmlHelper::toggle(
				$id,
				$name,
				array(
					'div_class' => 'with_frm_style frm_toggle',
					'checked'   => ! empty( $args['form_action']->post_content['open_in_new_tab'] ),
					'echo'      => true,
				)
			);
			?>
			<label for="<?php echo esc_attr( $id ); ?>" <?php FrmAppHelper::maybe_add_tooltip( 'new_tab' ); ?>>
				<?php esc_html_e( 'Open in new tab', 'frmreg' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Shows page settings.
	 *
	 * @since 2.13
	 *
	 * @param array $args {
	 *     The args.
	 *
	 *     @type object        $form_action    Form action post data.
	 *     @type FrmFormAction $action_control Form action object.
	 *     @type object        $form           Form data.
	 *     @type string        $action_key     Action key.
	 *     @type array         $values         Contains `fields` (form fields) and `id` (form ID).
	 * }
	 */
	public static function show_page_settings( $args ) {
		$name_attr = $args['action_control']->get_field_name( 'reg_redirect' );
		?>
		<div class="frm_form_field">
			<label for="<?php echo esc_attr( $name_attr ); ?>" class="screen-reader-text">
				<?php esc_html_e( 'Select a page', 'frmreg' ); ?>
			</label>
			<?php
			FrmAppHelper::maybe_autocomplete_pages_options(
				array(
					'field_name'  => $name_attr,
					'page_id'     => $args['form_action']->post_content['reg_redirect'],
					'placeholder' => __( 'Select a Page', 'frmreg' ),
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Check if auto login option should be visible or not
	 *
	 * @since 2.0
	 *
	 * @param object $register_action
	 *
	 * @return bool
	 */
	public static function is_auto_login_visible( $register_action ) {
		$settings = $register_action->post_content;
		$password_option = isset( $settings['reg_password'] ) ? $settings['reg_password'] : 'nothing';

		if ( $password_option === '' || $password_option === 'nothing' || ! empty( $settings['child_form'] ) ) {
			$show_auto_login = false;
		} else {
			$show_auto_login = true;
		}

		return $show_auto_login;
	}

	/**
	 * Determines if $user_meta has user meta content.
	 *
	 * @since 2.03
	 *
	 * @param array $user_meta An array containing user meta or an empty array.
	 *
	 * @return bool Whether $user_meta has user meta content.
	 */
	public static function has_user_meta( $user_meta ) {
		if ( ! is_array( $user_meta ) ) {
			return false;
		}

		foreach ( $user_meta as $row ) {
			if ( ! empty( $row['meta_name'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Removes empty rows from user meta in user reg post content.
	 *
	 * @since 2.03
	 *
	 * @param array $post_content An array with the post content values of a register user action.
	 *
	 * @return array $post_content The post content array, with filtered user meta array
	 */
	public static function filter_user_meta( $post_content ) {
		if ( empty( $post_content ) || empty( $post_content['reg_usermeta'] ) ) {
			return $post_content;
		}

		$post_content['reg_usermeta'] = self::remove_empty_user_meta_elements( $post_content['reg_usermeta'] );

		return $post_content;
	}

	/**
	 * Removes empty user meta rows.
	 *
	 * @since 2.03
	 *
	 * @param array $user_meta An array of user meta arrays.
	 *
	 * @return array User meta filtered to remove empty rows.
	 */
	private static function remove_empty_user_meta_elements( $user_meta ) {
		return array_filter( $user_meta, self::class . '::user_meta_has_value' );
	}

	/**
	 * Returns true if the meta_name element of a user meta array has a value.
	 *
	 * @since 2.03
	 *
	 * @param array $meta A user meta array with meta_name and field_id keys.
	 *
	 * @return bool Returns true if the meta_name has a value.
	 */
	private static function user_meta_has_value( $meta ) {
		return ! empty( $meta['meta_name'] );
	}
}

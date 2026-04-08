<?php

class FrmRegAction extends FrmFormAction {

	private $switch_ids = false;

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frm_register_icon frm_icon_font',
			'color'    => 'rgb(226,42,110)',
			'limit'    => 1,
			'active'   => true,
			'priority' => 9,
			'event'    => array( 'create', 'update', 'import' ),
		);

		$this->FrmFormAction( 'register', __( 'Register User', 'frmreg' ), $action_ops );
	}

	public function form( $form_action, $args = array() ) {
		$form = isset( $args['form'] ) ? $args['form'] : false;

		if ( ! $form ) {
			return;
		}

		if ( method_exists( $this, 'get_form_fields' ) ) {
			$fields = $this->get_form_fields( $form->id );
		} else {
			$fields = FrmRegActionController::get_user_meta_fields( $form->id );
		}

		$show_auto_login = FrmRegActionHelper::is_auto_login_visible( $form_action );

		$on_email_confirmation = $form_action->post_content['on_email_confirmation'];

		$redirect_types = array(
			'page' => array(
				'label'        => __( 'Redirect to Page', 'frmreg' ),
				'icon'         => 'frm_icon_font frm_file_text_icon frm_svg20',
				'sub_settings' => array( 'FrmRegActionHelper', 'show_page_settings' ),
			),
			'url' => array(
				'label'        => __( 'Redirect to URL', 'frmreg' ),
				'icon'         => 'frm_icon_font frm_globe_icon frm_svg20',
				'sub_settings' => array( 'FrmRegActionHelper', 'show_redirect_settings' ),
			),
		);

		$redirect_type_args                   = $args;
		$redirect_type_args['form_action']    = $form_action;
		$redirect_type_args['action_control'] = $this;

		include FrmRegAppHelper::path() . '/views/_register_settings.php';
	}

	public function get_defaults() {
		return FrmRegActionHelper::get_default_options();
	}

	public function get_switch_fields() {
		return array(
			'reg_usermeta' => array( array( 'field_id' ) ),
		);
	}

	/**
	 * Reformat options when migrating old settings to new action
	 *
	 * @since 2.0
	 *
	 * @param object $action
	 * @param object $form
	 *
	 * @return object
	 */
	public function migrate_values( $action, $form ) {
		if ( ! empty( $action->post_content['reg_usermeta'] ) ) {
			$new_usermeta = array();
			foreach ( $action->post_content['reg_usermeta'] as $meta_name => $field_id ) {
				$new_usermeta[] = array( 'meta_name' => $meta_name, 'field_id' => $field_id );
				unset( $meta_name, $field_id );
			}
			$action->post_content['reg_usermeta'] = $new_usermeta;
		}

		$action->post_content['event'] = array( 'create', 'update' );

		if ( $this->switch_ids ) {
			$action->post_content = $this->switch_action_field_ids( $action->post_content );
		}

		return $action;
	}

	/**
	 * Determine whether the current action needs field IDs switched out
	 *
	 * @since 2.0
	 *
	 * @param boolean $switch
	 */
	public function set_switch( $switch ) {
		$this->switch_ids = $switch;
	}

	/**
	 * Switch field IDs in an action
	 *
	 * @since 2.0
	 *
	 * @param array $post_content
	 *
	 * @return array
	 */
	private function switch_action_field_ids( $post_content ) {
		global $frm_duplicate_ids;

		// If there aren't IDs that were switched, end now
		if ( ! $frm_duplicate_ids ) {
			return $post_content;
		}

		// Get old IDs
		$old = array_keys( $frm_duplicate_ids );

		// Get new IDs
		$new = array_values( $frm_duplicate_ids );

		$post_content = $this->replace_field_ids( $new, $old, $post_content );

		return $post_content;
	}

	/**
	 * Replace old field IDs with new field IDs in the post content
	 *
	 * @since 2.0
	 *
	 * @param array $new
	 * @param array $old
	 * @param array $post_content
	 *
	 * @return array
	 */
	private function replace_field_ids( $new, $old, $post_content ) {
		foreach ( $post_content as $key => $setting ) {
			if ( is_numeric( $setting ) && $setting ) {
				$post_content[ $key ] = str_replace( $old, $new, $setting );
			} else if ( is_array( $setting ) ) {
				$post_content[ $key ] = $this->replace_field_ids( $new, $old, $setting );
			}
		}

		return $post_content;
	}
}

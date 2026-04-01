<?php

class FrmAPIAction extends FrmFormAction {

	function __construct() {
		$action_ops = array(
			'classes'   => 'frm_feed_icon frm_icon_font',
			'limit'     => 99,
			'active'    => true,
			'priority'  => 25,
			'event'     => array( 'create', 'draft', 'update', 'delete', 'import' ),
		);

		$this->FrmFormAction( 'api', __( 'Send API data', 'frmapi' ), $action_ops );
	}

	function form( $form_action, $args = array() ) {
		$action_control = $this;
		$api_key = FrmAPIAppController::prepare_basic_auth_key( $form_action->post_content['api_key'] );
		$data_fields = $form_action->post_content['data_fields'];
		if ( empty( $data_fields ) ) {
			$data_fields[] = array( 'key' => '', 'value' => '' );
		}

		if ( empty( $form_action->post_content['format'] ) && ! empty( $form_action->post_content['data_format'] ) ) {
			// set the format for reverse compatibility
			$form_action->post_content['format'] = 'raw';
		}

		include( FrmAPIAppHelper::path() . '/views/action-settings/options.php' );
	}

	function get_defaults() {
		return array(
			'url'         => '',
			'api_key'     => '',
			'data_format' => '',
			'data_fields' => array(),
			'format'      => 'json',
			'method'      => 'POST',
		);
	}

	public function get_switch_fields() {
		return array(
			'data_fields' => array( array( 'key', 'value' ) ),
		);
	}
}

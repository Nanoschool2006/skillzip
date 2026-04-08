<?php

class FrmZapAction extends FrmFormAction {
	public $zap_url;

	public function __construct() {
		$action_ops = array(
			'classes'  => 'frmfont frm_zapier_icon',
			'color'    => '#ff4a00',
			'limit'    => 99,
			'active'   => true,
			'priority' => 41,
			'event'    => array( 'create', 'delete' ),
			'tooltip'  => __( 'Send to Zapier', 'frmzap' ),
		);

		$this->FrmFormAction( 'zapier', __( 'Zapier', 'frmzap' ), $action_ops );
	}

	/**
	 * @param WP_Post $form_action
	 * @param array   $args
	 * @return void
	 */
	public function form( $form_action, $args = array() ) {
		$form           = $args['form'];
		$action_control = $this;
		$this->zap_url  = $form_action->post_content['zap_url'];
		include FrmZapAppController::path() . '/views/action-settings/zapier_options.php';
	}

	/**
	 * Create a new Zapier form action for a specific form with a URL.
	 *
	 * @since 2.0
	 *
	 * @param int    $form_id the ID of the form to add an action for.
	 * @param string $new_url the URL of the Zapier WebHook.
	 * @param string $event the action trigger(s) that this action should trigger on.
	 * @return FrmZapAction
	 */
	public function create_new( $form_id, $new_url, $event ) {
		$this->form_id = $form_id;

		$action = $this->prepare_new();
		$action->post_content['zap_url'] = $new_url;
		$action->post_content['event'] = $event;

		return $this->save_settings( $action );
	}

	/**
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'zap_url'  => '',
			'event'    => array( 'create' ),
		);
	}

}

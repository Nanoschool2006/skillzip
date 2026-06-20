<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

// Backwards compatibility "@since 6.31".
if ( ! FrmProAppHelper::lite_supports_form_actions_refresh() ) {
	include __DIR__ . '/backwards-compatibility/_form_action.php';
	return;
}

$logic_rows_class = 'frm_logic_rows_' . $action_key;

if ( ! empty( $form_fields ) ) { ?>
	<div class="frm-h-stack-xs frm-bt-200 frm-py-md">
		<?php
		FrmHtmlHelper::toggle(
			'frm_logic_' . $action_key,
			'frm_logic_' . $action_key,
			array(
				'div_class'  => 'with_frm_style frm_toggle',
				'checked'    => $show_logic,
				'echo'       => true,
				'input_html' => array(
					'data-toggleclass' => $logic_rows_class,
					'data-emailkey'    => $action_key,
				),
			)
		);
		?>
		<label for="frm_logic_<?php echo esc_attr( $action_key ); ?>">
			<?php esc_html_e( 'Add Conditional Logic', 'formidable-pro' ); ?>
		</label>
	</div>
<?php } ?>

<div class="frm_logic_rows frm-logic-rows--horizontal frm-form-actions-refresh frm_add_remove -frm-mt-md frm-mb-md <?php echo esc_attr( $logic_rows_class . ( $show_logic ? '' : ' frm_hidden' ) ); ?>" id="frm_logic_rows_<?php echo esc_attr( $action_key ); ?>">
	<div id="frm_logic_row_<?php echo esc_attr( $action_key ); ?>" class="frm-mb-sm">
		<div class="frm-flex frm-flex-wrap frm-gap-xs frm-items-center frm-mt-md frm-mb-sm">
			<select name="<?php echo esc_attr( $action_control->get_field_name( 'conditions' ) ); ?>[send_stop]" class="frm-w-fit frm-m-0">
				<?php
				$send_stop = $form_action->post_content['conditions']['send_stop'];
				FrmProHtmlHelper::echo_dropdown_option( $send, $send_stop === 'send', array( 'value' => 'send' ) );
				FrmProHtmlHelper::echo_dropdown_option( $stop, $send_stop === 'stop', array( 'value' => 'stop' ) );
				?>
			</select>

			<span class="frm-white-space-nowrap frm-text-grey-700">
				<?php echo esc_html( $this_action_if ); ?> <?php esc_html_e( 'the following match:', 'formidable-pro' ); ?>
			</span>
		</div>

		<?php
		FrmProHtmlHelper::echo_radio_group(
			$action_control->get_field_name( 'conditions' ) . '[any_all]',
			array(
				'any' => esc_html__( 'Any', 'formidable-pro' ),
				'all' => esc_html__( 'All', 'formidable' ),
			),
			! empty( $form_action->post_content['conditions']['any_all'] ) ? $form_action->post_content['conditions']['any_all'] : 'any'
		);

		foreach ( $form_action->post_content['conditions'] as $meta_name => $condition ) {
			if ( ! is_numeric( $meta_name ) || ! is_array( $condition ) || empty( $condition['hide_field'] ) ) {
				continue;
			}

			FrmProFormActionsController::include_action_logic_row( $values['id'], $meta_name, $action_key, $action_control->id_base, $condition );
			unset( $meta_name, $condition );
		}
		?>
	</div>

	<a href="javascript:void(0)" class="frm_add_form_logic frm-flex-center frm-gap-2xs button frm-button-secondary" data-emailkey="<?php echo esc_attr( $action_key ); ?>">
		<?php FrmAppHelper::icon_by_class( 'frmfont frm_plus1_icon frm_svg12' ); ?>
		<span><?php esc_html_e( 'Add Condition', 'formidable-pro' ); ?></span>
	</a>
</div>

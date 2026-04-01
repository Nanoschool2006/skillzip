<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 6.28
 *
 * @var array  $field Field array.
 * @var string $opt_key Option key.
 * @var string $choice_limit Choice limit.
 * @var bool   $set_choices_limit Set choices limit.
 */

$container_html_atts = array(
	'style' => 'display: ' . ( $set_choices_limit ? 'flex' : 'none' ),
	'class' => 'frm-flex-justify frm-choice-entry-limit',
);

$input_html_atts = array(
	'type'  => 'text', // Do not use type="number" because we want to support shortcodes.
	'name'  => 'field_options[options_' . $field['id'] . '][' . $opt_key . '][limit]',
	'value' => $choice_limit,
);

?>
<div <?php FrmAppHelper::array_to_html_params( $container_html_atts, true ); ?>>
	<span><?php esc_html_e( 'Limit', 'formidable-pro' ); ?></span>
	<input <?php FrmAppHelper::array_to_html_params( $input_html_atts, true ); ?> />
	&nbsp;
	<?php
	FrmAppHelper::tooltip_icon(
		__( 'Set a max number of times a specific option can be chosen', 'formidable-pro' ),
		array(
			'data-placement' => 'right',
			'class'          => 'frm-flex',
		)
	);
	?>
</div>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}
?>
<p class="howto frm-my-sm">
	<?php
		$docs_link = FrmAppHelper::admin_upgrade_link(
			array(
				'medium'  => 'summary-field-settings',
				'content' => 'summary-field-exclude-fields-docs',
				'anchor'  => 'kb-exclude-fields-from-summary',
			),
			'knowledgebase/summary-review-before-submit/'
		);

		printf(
			/* translators: %1$s: HTML open link, %2$s: HTML close link */
			esc_html__( 'Select a field from the list below to exclude it from the summary OR %1$sLearn which fields are automatically included%2$s.', 'formidable-pro' ),
			'<a href="' . esc_url( $docs_link ) . '" target="_blank" class="frm-summary-learn-more frm-force-inline">',
			'</a>'
		);
	?>
</p>

<?php
FrmAppHelper::show_search_box(
	array(
		'input_id'    => 'frm_calc_' . $field['id'],
		'placeholder' => __( 'Search Fields', 'formidable' ),
		'tosearch'    => 'frm-field-list-' . $field['id'],
	)
);
?>

<ul class="frm_code_list frm-full-hover frm-short-list frm_js_summary_list" data-exclude="<?php echo esc_attr( json_encode( FrmProFieldSummary::remove_from_exclude_field_settings() ) ); ?>" id="frm-calc-list-<?php echo esc_attr( $field['id'] ); ?>" data-shortcode="no"></ul>

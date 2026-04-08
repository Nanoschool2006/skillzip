<?php //phpcs:ignore Squiz.Commenting.FileComment.MissingPackageTag
/**
 * Translations settings.
 */

defined( 'ABSPATH' ) || die();

/**
 * Add filters.
 */
add_filter( 'cmplz_fields', 'cmplz_tools_translations_fields', 100 );
add_filter( 'cmplz_warning_types', 'cmplz_tools_translations_warnings' );

/**
 * Add translations fields to the cmplz_fields filter.
 * If translations are disabled, hide the translation fields.
 *
 * @param array $fields The fields array.
 * @return array The fields array.
 */
function cmplz_tools_translations_fields( $fields ) {

	$is_disabled = defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS;

	// If translations are disabled, hide the translation fields.
	if ( ! $is_disabled ) {
		$translation_fields = array(
			array(
				'id'       => 'translation-management',
				'type'     => 'translation_management',
				'menu_id'  => 'translations',
				'group_id' => 'translation-management',
				'label'    => '',
			),
			array(
				'id'       => 'translation_cron_interval',
				'menu_id'  => 'translations',
				'group_id' => 'translation-management',
				'type'     => 'select',
				'label'    => __( 'Automatic Update Interval', 'complianz-gdpr' ),
				'tooltip'  => __( 'Choose how often to automatically fetch translation updates.', 'complianz-gdpr' ),
				'default'  => 'cmplz_weekly',
				'options'  => array(
					'disabled'      => __( 'Disabled', 'complianz-gdpr' ),
					'cmplz_daily'   => __( 'Daily', 'complianz-gdpr' ),
					'cmplz_weekly'  => __( 'Weekly', 'complianz-gdpr' ),
					'cmplz_monthly' => __( 'Monthly', 'complianz-gdpr' ),
				),
				'action'   => 'update_cron_interval',
			),
		);

		$fields = array_merge( $fields, $translation_fields );
	}

	return $fields;
}

/**
 * Add translations warning to the cmplz_warning_types filter.
 * If translations are disabled, show a warning into the dashboard.
 *
 * @param array $warnings The warnings array.
 * @return array The warnings array.
 */
function cmplz_tools_translations_warnings( $warnings ) {

	$is_disabled = defined( 'CMPLZ_DISABLE_TRANSLATIONS' ) && CMPLZ_DISABLE_TRANSLATIONS;

	if ( ! $is_disabled ) {
		return $warnings;
	}

	$warnings['translations-disabled'] = array(
		'plus_one'          => false,
		'dismissible'       => true,
		'warning_condition' => '_true_',
		'open'              => __( 'Dynamic Translations are disabled. To enable translations, remove the CMPLZ_DISABLE_TRANSLATIONS constant from your wp-config.php file.', 'complianz-gdpr' ),
	);

	return $warnings;
}

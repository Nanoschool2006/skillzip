<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProSettingsController {

	/**
	 * Print out the license form for users with Pro installed.
	 * This includes the license type information, and actions to disconnect and clear the API cache.
	 *
	 * @return void
	 */
	public static function license_box() {
		$edd_update      = FrmProAppHelper::get_updater();
		$show_creds_form = self::show_license_form( $edd_update );
		$errors          = array();

		if ( ! empty( $edd_update->license ) ) {
			if ( class_exists( 'FrmFormApi' ) ) {
				$api    = new FrmFormApi( $edd_update->license );
				$errors = $api->error_for_license();
			} elseif ( is_callable( 'FrmProAddonsController::error_for_license' ) ) {
				$errors = FrmProAddonsController::error_for_license( $edd_update->license );
			}
		}

		self::display_errors( $errors );

		if ( $show_creds_form ) {
			$edd_update->pro_cred_form();
		}
	}

	/**
	 * Adds the Global Settings currency settings.
	 *
	 * @since 6.18
	 *
	 * @return void
	 */
	public static function add_currency_settings() {
		$settings   = FrmProAppHelper::get_settings();
		$currencies = FrmProCurrencyHelper::get_currencies();

		include FrmProAppHelper::plugin_path() . '/classes/views/frmpro-settings/_currency.php';
	}

	/**
	 * Display license errors, but without messages from the frm_message_list filter.
	 *
	 * @since 6.0
	 *
	 * @param array $errors
	 *
	 * @return void
	 */
	private static function display_errors( $errors ) {
		add_filter( 'frm_message_list', '__return_empty_array', 99 );
		include FrmAppHelper::plugin_path() . '/classes/views/shared/errors.php';
		remove_filter( 'frm_message_list', '__return_empty_array', 99 );
	}

	public static function standalone_license_box() {
		$edd_update = FrmProAppHelper::get_updater();

		if ( self::show_license_form( $edd_update ) ) {
			include FrmProAppHelper::plugin_path() . '/classes/views/settings/standalone_license_box.php';
		}
	}

	/**
	 * @param FrmProEddController $edd_update
	 *
	 * @return bool
	 */
	private static function show_license_form( $edd_update ) {
		return ! is_multisite() || current_user_can( 'setup_network' ) || ! get_site_option( $edd_update->pro_wpmu_store );
	}

	/**
	 * @since 4.0
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public static function add_settings_section( $sections ) {
		// TODO: Backward compatibility, remove in a future safe version.
		add_action(
			is_callable( 'FrmCurrencyHelper::is_currency_format' ) ? 'frm_other_settings_form' : 'frm_settings_form',
			'FrmProSettingsController::general_style_settings'
		);

		add_action( 'frm_messages_settings_form', 'FrmProSettingsController::message_settings' );
		add_action( 'frm_settings_form', 'FrmProSettingsController::more_settings', 1 );

		if ( FrmProAddonsController::is_expired_outside_grace_period() ) {
			return $sections;
		}

		$sections['white_label'] = array(
			'class'    => self::class,
			'function' => 'white_label_settings',
			'name'     => isset( $sections['white_label'] ) ? $sections['white_label']['name'] : __( 'White Labeling', 'formidable' ),
			'icon'     => isset( $sections['white_label'] ) ? $sections['white_label']['icon'] : 'frmfont frm_ghost_icon',
		);

		$sections['inbox'] = array(
			'class'    => self::class,
			'function' => 'inbox_settings',
			'name'     => isset( $sections['inbox'] ) ? $sections['inbox']['name'] : __( 'Inbox', 'formidable' ),
			'icon'     => isset( $sections['inbox'] ) ? $sections['inbox']['icon'] : 'frmfont frm_email_icon',
		);

		return $sections;
	}

	public static function general_style_settings( $frm_settings ) {
		include FrmProAppHelper::plugin_path() . '/classes/views/settings/general_style.php';
	}

	/**
	 * @since 4.0
	 *
	 * @param object $frm_settings
	 */
	public static function message_settings( $frm_settings ) {
		$frmpro_settings = FrmProAppHelper::get_settings();
		require FrmProAppHelper::plugin_path() . '/classes/views/settings/messages.php';
	}

	/**
	 * Display additional Global settings in the "Other" section at the bottom of "General Settings".
	 * This includes Date Format and Currency settings.
	 *
	 * @param FrmSettings $frm_settings
	 *
	 * @return void
	 */
	public static function more_settings( $frm_settings ) {
		$frmpro_settings      = FrmProAppHelper::get_settings();
		$datepicker_libraries = array(
			'default'   => __( 'Default', 'formidable' ),
			'jquery'    => 'jQuery',
			'flatpickr' => 'Flatpickr (Beta)',
		);
		require FrmProAppHelper::plugin_path() . '/classes/views/settings/form.php';
	}

	/**
	 * @since 4.0
	 */
	public static function white_label_settings() {
		$frm_settings    = FrmAppHelper::get_settings();
		$frmpro_settings = FrmProAppHelper::get_settings();
		include FrmProAppHelper::plugin_path() . '/classes/views/settings/white-label.php';
	}

	/**
	 * @since 4.06.01
	 */
	public static function inbox_settings() {
		$settings      = FrmProAppHelper::get_settings();
		$message_types = $settings->inbox_types();
		$has_access    = self::has_current_access();
		include FrmProAppHelper::plugin_path() . '/classes/views/settings/inbox.php';
	}

	/**
	 * @since 4.06.01
	 */
	private static function has_current_access() {
		$user_type = FrmProAddonsController::license_type();
		return in_array( $user_type, array( 'elite', 'business', 'personal', 'grandfathered' ), true );
	}

	/**
	 * @since 4.06.01
	 *
	 * @param array $messages
	 */
	public static function filter_inbox( $messages ) {
		if ( ! $messages ) {
			return $messages;
		}

		$excluded = self::excluded_messages();

		if ( ! $excluded ) {
			return $messages;
		}

		foreach ( $messages as $k => $message ) {
			if ( isset( $message['type'] ) && in_array( $message['type'], $excluded ) ) {
				unset( $messages[ $k ] );
			}
		}

		return $messages;
	}

	/**
	 * @since 4.06.01
	 */
	private static function excluded_messages() {
		$excluded = array();

		if ( ! self::has_current_access() ) {
			return $excluded;
		}

		$settings = FrmProAppHelper::get_settings();
		$types    = $settings->inbox_types();

		foreach ( $types as $type => $label ) {
			if ( ! empty( $settings->inbox ) && ! isset( $settings->inbox[ $type ] ) ) {
				$excluded[] = $type;
			}
		}

		return $excluded;
	}

	/**
	 * @since 4.06.01
	 *
	 * @param string $count
	 *
	 * @return string
	 */
	public static function inbox_badge( $count ) {
		$settings = FrmProAppHelper::get_settings();
		$off      = ! empty( $settings->inbox ) && ! isset( $settings->inbox['badge'] );

		if ( $off && self::has_current_access() ) {
			return '';
		}

		return $count;
	}

	public static function update( $params ) {
		global $frmpro_settings;
		$frmpro_settings = new FrmProSettings();
		$frmpro_settings->update( $params );
	}

	public static function store() {
		$frmpro_settings = FrmProAppHelper::get_settings();
		$frmpro_settings->store();
	}

	/**
	 * Add values to the advanced helpers on the settings/views pages
	 *
	 * @since 3.04.01
	 *
	 * @param array $helpers
	 * @param array $atts
	 *
	 * @return array
	 */
	public static function advanced_helpers( $helpers, $atts ) {
		$repeat_field  = 0;
		$dynamic_field = 0;
		$linked_field  = 0;
		$file_field    = 0;

		foreach ( $atts['fields'] as $field ) {
			if ( ! $repeat_field && FrmField::is_repeating_field( $field ) ) {
				$repeat_field = $field->id;
			} elseif ( ! $dynamic_field && $field->type === 'data' && isset( $field->field_options['form_select'] ) && is_numeric( $field->field_options['form_select'] ) ) {
				add_action( 'frm_field_code_tab', 'FrmProSettingsController::field_sidebar' );
				$dynamic_field = $field->id;
				$linked_field  = $field->field_options['form_select'];
			} elseif ( ! $file_field && $field->type === 'file' ) {
				$file_field = $field;
			}
			unset( $field );
		}

		if ( $repeat_field ) {
			$helpers['repeat'] = array(
				'heading' => '',
				'codes'   => array(
					'foreach ' . $repeat_field . '][/foreach' => __( 'For Each', 'formidable-pro' ),
				),
			);
		}

		if ( $dynamic_field ) {
			$helpers['dynamic'] = array(
				'heading' => '',
				'codes'   => array(
					$dynamic_field . ' show="created-at"' => __( 'Creation Date', 'formidable-pro' ),
					$dynamic_field . ' show="' . $linked_field . '"' => __( 'Field From Entry', 'formidable-pro' ),
				),
			);
		}

		if ( ! $file_field ) {
			return $helpers;
		}

		$helpers['default']['codes'][ $file_field->id . ' show_image=1' ]    = __( 'Show image', 'formidable-pro' );
		$helpers['default']['codes'][ $file_field->id . ' show=id' ]         = __( 'Image ID', 'formidable-pro' );
		$helpers['default']['codes'][ $file_field->id . ' show_filename=1' ] = __( 'Image Name', 'formidable-pro' );

		return $helpers;
	}

	/**
	 * Add extra field shortcodes in the shortcode lists
	 *
	 * @since 3.04.01
	 *
	 * @param array $atts
	 *
	 * @return void
	 */
	public static function field_sidebar( $atts ) {
		$field = $atts['field'];

		if ( $field->type !== 'data' || ! isset( $field->field_options['form_select'] ) || ! is_numeric( $field->field_options['form_select'] ) ) {
			return;
		}

		// Get all fields from linked form
		$linked_form = FrmDb::get_var( 'frm_fields', array( 'id' => $field->field_options['form_select'] ), 'form_id' );

		$linked_fields = FrmField::getAll(
			array(
				'fi.type not' => FrmField::no_save_fields(),
				'fi.form_id'  => $linked_form,
			)
		);

		if ( ! $linked_fields ) {
			return;
		}

		foreach ( $linked_fields as $linked_field ) {
			FrmFormsHelper::insert_opt_html(
				array(
					'id'    => $field->id . ' show=' . $linked_field->id,
					'key'   => $field->field_key . ' show=' . $linked_field->field_key,
					'name'  => $linked_field->name,
					'type'  => $linked_field->type,
					'class' => 'frm-customize-list',
				)
			);
		}
	}

	/**
	 * Enqueues scripts for global settings page.
	 *
	 * @since 6.25
	 */
	public static function enqueue_scripts() {
		wp_enqueue_media();

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'formidable_pro_settings', FrmProAppHelper::plugin_url() . '/css/settings/global-settings.css', array(), FrmProDb::$plug_version );

		wp_enqueue_script( 'wp-color-picker-alpha', FrmProAppHelper::plugin_url() . '/js/admin/settings/wp-color-picker-alpha.js', array( 'wp-color-picker' ), '3.0.2', true );
		wp_enqueue_script( 'formidable_pro_settings', FrmProAppHelper::plugin_url() . '/js/admin/settings.js', array( 'wp-color-picker' ), FrmProDb::$plug_version, true );
	}

	/**
	 * Shows image uploader.
	 *
	 * @since 6.25
	 *
	 * @param array $args {
	 *     Args
	 *
	 *     @type string $name Input name.
	 *     @type string $img_id Image ID.
	 * }
	 *
	 * @return void
	 */
	public static function image_uploader( $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'name'   => '',
				'img_id' => '',
			)
		);

		include FrmProAppHelper::plugin_path() . '/classes/views/shared/image-uploader.php';
	}
}

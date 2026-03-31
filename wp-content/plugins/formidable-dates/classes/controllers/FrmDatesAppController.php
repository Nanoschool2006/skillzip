<?php
class FrmDatesAppController {

	public static function pro_not_installed_notice() {
		?>
	<div class="error">
		<p><?php esc_html_e( 'Formidable Dates requires Formidable Forms Pro to be installed.', 'frmdates' ); ?></p>
	</div>
		<?php
	}

	public static function enqueue_scripts() {
		$suffix = FrmAppHelper::js_suffix();
		wp_enqueue_script( 'frmdates', FrmDatesAppHelper::get_url( '/js/frmdates' . $suffix . '.js' ), array( 'jquery' ), FrmDatesAppHelper::plugin_version(), true );
	}

	public static function load_date_js( $args ) {
		if ( 'date' === $args['field']->type ) {
			global $frm_vars;

			if ( isset( $frm_vars['datepicker_loaded'] ) && ! empty( $frm_vars['datepicker_loaded'] ) ) {
				self::enqueue_scripts();
			}
		}
	}

	/**
	 * Maybe enqueue date JS when rendering form to fix the date calculation error in a multi-pages form.
	 *
	 * @since 2.0.4
	 *
	 * @param array $params See `frm_enqueue_form_scripts` hook.
	 */
	public static function maybe_enqueue_date_js_earlier( $params ) {
		global $frm_vars;
		$is_in_place_edit = ! empty( $frm_vars['inplace_edit'] );

		if ( ! $is_in_place_edit && ! FrmProFormsHelper::has_field( 'break', $params['form_id'] ) ) {
			// We only require this on a multi-page form.
			// Or when editing in-place.
			return;
		}

		// Get all dates field, enqueue if one of them has date calculation.
		$date_fields = FrmField::get_all_types_in_form( $params['form_id'], 'date' );
		if ( ! $date_fields ) {
			return;
		}

		foreach ( $date_fields as $date_field ) {
			if ( ! empty( $date_field->field_options['date_calc'] ) ) {
				self::enqueue_scripts();
				break;
			}
		}
	}

	/**
	 * Maybe enqueue frontend scripts on backend entry pages. This should run before FrmAppController::admin_js() to
	 * enqueue date JS before frm.min.js.
	 *
	 * @since 2.0.4
	 */
	public static function maybe_enqueue_frontend_scripts() {
		if ( ! FrmAppHelper::js_suffix() || ! FrmProAppController::has_combo_js_file() ) {
			// This issue only happen with frm.min.js.
			return;
		}

		$page = FrmAppHelper::simple_get( 'page', 'sanitize_title' );
		if ( 'formidable-entries' === $page ) {
			self::enqueue_scripts();
		}
	}

	public static function enqueue_admin_assets() {
		if ( ! FrmAppHelper::is_admin_page( 'formidable' ) ) {
			return;
		}

		wp_enqueue_style( 'jquery-theme', FrmProStylesController::jquery_css_url( '' ), array(), FrmAppHelper::plugin_version() );
		wp_enqueue_style( 'formidable-pro-fields', admin_url( 'admin-ajax.php?action=pro_fields_css' ), array(), FrmAppHelper::plugin_version() );
		wp_enqueue_style( 'frmdates_admin', FrmDatesAppHelper::get_url( '/css/admin.css' ), array(), FrmDatesAppHelper::plugin_version() );

		FrmDatesDatepickerLibraryHelper::load_admin_style_and_scripts();
		wp_register_script( 'frmdates_admin', FrmDatesAppHelper::get_url( '/js/admin.js' ), FrmDatesDatepickerLibraryHelper::get_admin_js_dependencies(), FrmDatesAppHelper::plugin_version() );

		$frmpro_settings = FrmProAppHelper::get_settings();
		$script_strings  = array(
			'datepickerLibrary' => FrmDatesDatepickerLibraryHelper::use_jquery_datepicker() ? 'jquery' : 'flatpickr',
			'itemTemplate'      => FrmDatesTemplatesHelper::settings_render_dates_list_item(
				array(
					'date'           => '%DATE%',
					'formatted_date' => '%DATE_WITH_FORMAT%',
					'input_name'     => '%DATE_TYPE%_%FIELD_ID%',
				)
			),
			'dateFormat'        => $frmpro_settings->cal_date_format,
		);
		wp_localize_script( 'frmdates_admin', 'frmdates_admin_js', $script_strings );

		wp_enqueue_script( 'frmdates_admin' );

		// Backwards compatibility "@since 3.0".
		if ( FrmAppHelper::is_form_builder_page() && ! class_exists( 'FrmTextToggleStyleComponent' ) ) {
			wp_add_inline_style( 'frmdates_admin', self::get_builder_backward_compatibility_css() );
		}
	}

	/**
	 * Get backward compatibility CSS styles for form builder page.
	 *
	 * @since 3.0
	 *
	 * @return string CSS styles.
	 */
	private static function get_builder_backward_compatibility_css() {
		return '
			.frmdates_add_blackout_date_link,
			.frmdates_add_exception_link {
				outline: 0;
				box-shadow: var(--box-shadow-xs);
				border-radius: var(--small-radius);
				padding: 5px 14px;
				border-color: var(--grey-300);
				color: var(--grey-800);
				font-size: var(--text-md);
				margin: 0;
				background-color: #fff;
				line-height: var(--leading);
			}

			.frmdates_add_blackout_date_link .frm_calendar_icon,
			.frmdates_add_exception_link .frm_calendar_icon {
				width: 14px;
				height: 14px;
			}

			.frm-h-stack {
				display: flex !important;
				align-items: center;
				gap: var(--gap-2xs);
			}

			.frm-token-container {
				position: relative;
				display: block;
				direction: ltr;
			}

			.frm-token-container .frm-tokens {
				position: absolute;
				top: 0;
				left: 0;
				max-width: calc(100% - var(--gap-xl));
				display: flex;
				align-items: center;
				flex-wrap: wrap;
				gap: var(--gap-2xs);
				padding: 6px var(--gap-xs);
				margin: 0;
			}

			.frm-token-container .frm-token {
				position: relative;
				display: flex;
				align-items: center;
				gap: var(--gap-xs);
				height: 24px;
				color: var(--grey-900);
				background: var(--grey-100);
				border-radius: 4px;
				padding: 0 var(--gap-xs);
				font-size: var(--text-md);
				line-height: 1;
				white-space: nowrap;
				margin: 0;
				z-index: 2;
			}

			.frm-token-container .frm-token .frm-token-remove {
				display: flex;
				cursor: pointer;
			}

			.frm-token-container .frm-token .frm-token-remove .frmsvg {
				color: var(--grey-900);
				position: static;
				width: 12px;
				height: 12px;
				padding: 0;
			}

			.frm-token-container .frm-token .frm-token-remove:hover .frmsvg {
				color: var(--error-500);
			}

			.frm-token-container .frm-show-inline-modal {
				z-index: 3 !important;
			}

			.frm-token-container .frm-token-proxy-input {
				position: relative;
				z-index: 1;
				padding-right: var(--gap-xl) !important;
			}';
	}

	public static function add_settings_to_form( $field, $display, $values ) {
		$field_id = absint( $field['id'] );
		$form_id  = absint( $field['form_id'] );

		$date_fields = array();
		foreach ( FrmField::get_all_types_in_form( $form_id, 'date', '', 'include' ) as $date_field ) {
			if ( $date_field->id == $field_id ) {
				continue;
			}

			$date_fields[ $date_field->field_key ] = $date_field->name;
		}

		$min_max_dates_labels = array(
			'minimum_date' => __( 'Minimum', 'frmdates' ),
			'maximum_date' => __( 'Maximum', 'frmdates' ),
		);

		$hide_min_max         = empty( $field['minimum_date_cond'] ) && empty( $field['maximum_date_cond'] );
		$hide_blackout_dates  = empty( $field['blackout_dates'] );
		$all_days_of_the_week = empty( $field['days_of_the_week'] ) || 7 === count( $field['days_of_the_week'] );

		if ( ! empty( $field['locale'] ) ) {
			FrmDatesDatepickerLibraryHelper::load_localization_file( $field['locale'] );
		}

		include FrmDatesAppHelper::get_path( '/views/date-field-settings.php' );
	}

	public static function date_field_options_js( $js_options, $extra ) {
		$field_key = str_replace( array( 'field_', '^' ), '', $extra['field_id'] );
		$field     = FrmField::getOne( $field_key );

		if ( ! $field ) {
			return $js_options;
		}

		$js_options['fieldId'] = $field->id;

		if ( ! FrmDatesField::field_has_custom_opts( $field ) && empty( $field->field_options['unique'] ) ) {
			return $js_options;
		}

		$inline = FrmDatesAppHelper::date_field_display_inline( $field );
		if ( $inline ) {
			// TODO: Adjust for repeating sections.
			$js_options['options']['altField'] = '#field_' . $field->field_key . '_alt';
		}

		$constraints                    = self::get_constraints_for_field( $field );
		$js_options['formidable_dates'] = array(
			'inline'                   => (bool) $inline,
			'daysEnabled'              => $constraints['days'],
			'datesEnabled'             => $constraints['exceptions'],
			'datesDisabled'            => apply_filters( 'frm_dates_disabled', $constraints['blackout_dates'], $field ),
			'minimum_date_cond'        => FrmField::get_option( $field, 'minimum_date_cond' ),
			'minimum_date_val'         => FrmField::get_option( $field, 'minimum_date_val' ),
			'maximum_date_cond'        => FrmField::get_option( $field, 'maximum_date_cond' ),
			'maximum_date_val'         => FrmField::get_option( $field, 'maximum_date_val' ),
			'selectableResponse'       => apply_filters( 'frm_dates_selectable_response', true, $field ),
			'isRangeEnabled'           => FrmField::get_option( $field, 'range_field' ),
			'isRangeEndField'          => FrmField::get_option( $field, 'is_range_end_field' ),
			'rangeStartFieldId'        => FrmField::get_option( $field, 'range_start_field' ),

			/**
			 * Skips blocked dates from date calculation.
			 *
			 * @since 2.0.4
			 *
			 * @param bool   $skip  Set to `true` to skip.
			 * @param object $field Field object.
			 */
			'skipBlockedDatesFromCalc' => apply_filters( 'frm_dates_skip_blocked_dates_from_calc', true, $field ),
		);

		return $js_options;
	}

	private static function get_constraints_for_field( $field ) {
		$days           = FrmField::get_option( $field, 'days_of_the_week' );
		$blackout_dates = FrmField::get_option( $field, 'blackout_dates' );
		$exceptions     = FrmField::get_option( $field, 'excepted_dates' );

		if ( empty( $days ) ) {
			$days = range( 0, 6 );
		} else {
			$days = array_map( 'absint', $days );
		}

		return compact( 'days', 'blackout_dates', 'exceptions' );
	}

	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include FrmDatesAppHelper::get_path( '/classes/models/FrmDatesUpdate.php' );
			FrmDatesUpdate::load_hooks();
		}
	}

	/**
	 * Show a tooltip icon with the message passed.
	 *
	 * @since 2.1
	 *
	 * @param string $message The message to be displayed in the tooltip.
	 * @param array  $atts    The attributes to be added to the tooltip.
	 *
	 * @return void
	 */
	public static function show_svg_tooltip( $message, $atts = array() ) {
		if ( ! is_callable( 'FrmAppHelper::tooltip_icon' ) ) {
			return;
		}
		FrmAppHelper::tooltip_icon( $message, $atts );
	}

	/**
	 * Add extra attributes to the form field container.
	 *
	 * @since 3.0
	 *
	 * @param array  $attributes The active attributes of the form field container.
	 * @param object $field      The field object.
	 * @param array  $display    The display settings.
	 *
	 * @return array
	 */
	public static function add_extra_atts_to_form_field_container( $attributes, $field, $display ) {
		if ( ! empty( $field['range_end_field'] ) ) {
			$attributes['data-date-range-end-field-id'] = $field['range_end_field'];
		}

		if ( ! empty( $field['range_start_field'] ) ) {
			$attributes['data-range-start-field-id'] = $field['range_start_field'];
		}

		return $attributes;
	}

	/**
	 * Add extra field options to the start date field when the range field is enabled when the end date field is created.
	 * It's used by Date Ranges options.
	 *
	 * @since 3.0
	 *
	 * @param array $field    The end date field object.
	 * @param int   $form_id  The form ID.
	 */
	public static function init_start_date_field_extra_options_on_end_date_field_creation( $field, $form_id ) {

		if ( empty( $field['id'] ) ) {
			return;
		}

		$field_options = FrmDb::get_var( 'frm_fields', array( 'id' => $field['id'] ), 'field_options' );
		FrmProAppHelper::unserialize_or_decode( $field_options );

		if ( empty( $field_options['is_range_end_field'] ) || empty( $field_options['range_start_field'] ) ) {
			return;
		}

		$start_date_field_options = FrmDb::get_var( 'frm_fields', array( 'id' => $field_options['range_start_field'] ), 'field_options' );
		FrmProAppHelper::unserialize_or_decode( $start_date_field_options );

		$start_date_field_options['is_range_start_field'] = 1;
		$start_date_field_options['range_end_field']      = $field['id'];
		$start_date_field_options['range_field']          = 1;
		$start_date_field_options['classes']              = 'frm6 frm_first';

		$update_values = array(
			'field_options' => $start_date_field_options,
			'name'          => __( 'Start Date', 'frmdates' ),
		);

		// Update end date field.
		FrmField::update( (int) $field['id'], array( 'name' => __( 'End Date', 'frmdates' ) ) );
		// Update start date field.
		FrmField::update( (int) $field_options['range_start_field'], $update_values );
	}
}

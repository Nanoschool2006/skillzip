<?php
$calendar_icon = class_exists( 'FrmTextToggleStyleComponent' ) ? 'frm_calendar2_icon' : 'frm_calendar_icon';
?>
<tr>
	<td class="frm-p-0"></td>
	<td class="frm-p-0">
		<p class="<?php echo esc_attr( $hide_min_max ? '' : 'frm_hidden' ); ?>">
			<label class="frm-h-stack-xs">
				<input type="checkbox" value="1" class="frm_date_show" data-show="frm_min_max_date" data-value="1" />
				<span><?php esc_html_e( 'Set specific minimum and maximum dates', 'frmdates' ); ?></span>
			</label>
		</p>
		<div class="frm_min_max_date <?php echo esc_attr( $hide_min_max ? 'frm_hidden' : '' ); ?>">
			<div class="frm_grid_container">
				<?php foreach ( $min_max_dates_labels as $opt_name => $opt_label ) : ?>
					<label class="frm_primary_label" for="<?php echo esc_attr( $opt_name ); ?>_cond_<?php echo absint( $field['id'] ); ?>">
						<span><?php echo esc_html( $opt_label ); ?></span>
					</label>
					<select name="field_options[<?php echo esc_attr( $opt_name ); ?>_cond_<?php echo absint( $field['id'] ); ?>]" id="<?php echo esc_attr( $opt_name ); ?>_cond_<?php echo absint( $field['id'] ); ?>" class="frm6 frm_date_show frm-mb-2xs" data-hide="frm_set_<?php echo esc_attr( $opt_name ); ?>" data-value="" data-default="date:<?php echo esc_attr( 'i.e. ' . gmdate( 'Y-m-d' ) ); ?>|:i.e. +0, +7 days">
						<option value=""><?php esc_html_e( 'Year set above', 'frmdates' ); ?></option>
						<option value="date" <?php selected( $field[ $opt_name . '_cond' ], 'date' ); ?>><?php esc_html_e( 'Specific Date', 'frmdates' ); ?></option>
						<option value="today" <?php selected( $field[ $opt_name . '_cond' ], 'today' ); ?>><?php esc_html_e( 'Current Date', 'frmdates' ); ?></option>
						<?php if ( ! empty( $date_fields ) ) : ?>
							<optgroup label="<?php esc_attr_e( 'Date Fields', 'frmdates' ); ?>" class="frmdates_date_fields_opts">
								<?php foreach ( $date_fields as $date_field_key => $date_field_name ) : ?>
									<option value="field_<?php echo esc_attr( $date_field_key ); ?>" <?php selected( $field[ $opt_name . '_cond' ], 'field_' . $date_field_key ); ?>><?php echo esc_attr( $date_field_name ); ?></option>
								<?php endforeach; ?>
							</optgroup>
						<?php endif; ?>
					</select>

					<input type="text" name="field_options[<?php echo esc_attr( $opt_name ); ?>_val_<?php echo absint( $field['id'] ); ?>]" value="<?php echo esc_attr( $field[ $opt_name . '_val' ] ); ?>" class="frm6 frm-mb-2xs frm_set_<?php echo esc_attr( $opt_name ); ?> <?php echo esc_attr( '' === $field[ $opt_name . '_cond' ] ? 'frm_hidden' : '' ); ?>" placeholder="<?php echo esc_attr( 'i.e. ' . gmdate( 'Y-m-d' ) ); ?>" />
				<?php endforeach; ?>
			</div>
		</div>
	</td>
</tr>
<tr>
	<td>
		<label class="frm-h-stack-xs frm-m-0" id="<?php echo esc_attr( 'days_of_the_week_' . absint( $field['id'] ) . '_heading' ); ?>">
			<span><?php esc_html_e( 'Days of the week', 'frmdates' ); ?></span>
			<?php
			FrmDatesAppController::show_svg_tooltip(
				__( 'Check the boxes for the days of the week that should be selectable in the datepicker. Deselecting a week day will disable it in the datepicker.', 'frmdates' ),
				array(
					'data-placement' => 'right',
					'class'          => 'frm-flex',
				)
			);
			?>
		</label>
	</td>
	<td>
		<div class="frmdates_days_of_the_week_toggle frm-flex frm-items-center frm-gap-xs <?php echo esc_attr( $all_days_of_the_week ? '' : 'frm_hidden' ); ?>">
			<input class="frm-m-0" type="checkbox" id="<?php echo esc_attr( 'frmdates_days_of_the_week_toggle_' . absint( $field['id'] ) ); ?>" <?php checked( $all_days_of_the_week ); ?> />
			<label class="frm-m-0" for="<?php echo esc_attr( 'frmdates_days_of_the_week_toggle_' . absint( $field['id'] ) ); ?>">
				<?php esc_html_e( 'All Days', 'frmdates' ); ?>
			</label>
		</div>

		<div role="group" aria-labelledby="<?php echo esc_attr( 'days_of_the_week_' . absint( $field['id'] ) . '_heading' ); ?>" class="frmdates_days_of_the_week <?php echo $all_days_of_the_week ? 'frm_hidden' : ''; ?>" id="<?php echo esc_attr( 'frmdates_days_of_the_week_' . absint( $field['id'] ) ); ?>">
			<?php
			foreach ( FrmDatesAppHelper::get_days_of_the_week() as $day_number => $day_name ) {
				?>
				<label class="frm_inline">
					<input type="checkbox" name="field_options[days_of_the_week_<?php echo absint( $field['id'] ); ?>][]" value="<?php echo absint( $day_number ); ?>" <?php checked( in_array( $day_number, $field['days_of_the_week'] ) ); ?>/>
					<?php echo esc_html( $day_name ); ?>
				</label>
				&nbsp;
			<?php } ?>
		</div>
	</td>
</tr>
<tr id="frmdates_excepted_dates_row_<?php echo absint( $field['id'] ); ?>" class="<?php echo count( $field['days_of_the_week'] ) == 7 ? 'frm_hidden' : ''; ?>">
	<td colspan="2" class="frm-p-0">
		<p>
			<label class="frm-h-stack-xs" for="frmdates_excepted_dates_<?php echo absint( $field['id'] ); ?>">
				<span><?php esc_html_e( 'Exceptions', 'frmdates' ); ?></span>
				<?php
				FrmDatesAppController::show_svg_tooltip(
					__( 'When a weekday is disabled in the datepicker, you may select specific dates to enable.', 'frmdates' ),
					array(
						'data-placement' => 'right',
						'class'          => 'frm-flex',
					)
				);
				?>
			</label>
		</p>

		<div class="frm-token-container" id="frmdates_excepted_dates_<?php echo absint( $field['id'] ); ?>">
			<input type="hidden" value="" class="frmdates_datepicker" />
			<a href="#" class="frmdates_add_exception_link frm-h-stack" data-field-id="<?php echo absint( $field['id'] ); ?>">
				<?php FrmAppHelper::icon_by_class( 'frm_icon_font ' . $calendar_icon . ' frm_add_tag frm_svg20 -frm-ml-2xs' ); ?>
				<span><?php esc_html_e( 'Add Exceptions', 'frmdates' ); ?></span>
			</a>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo FrmDatesTemplatesHelper::settings_render_dates_list(
				array(
					'items'     => $field['excepted_dates'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'field_id'  => $field['id'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'date_type' => 'excepted_dates',
				)
			);
			?>
		</div>
	</td>
</tr>
<tr>
	<td colspan="2" class="frm-p-0">
		<p class="<?php echo esc_attr( $hide_blackout_dates ? 'frm-mb-12' : '' ); ?>">
			<label class="frm-h-stack-xs">
				<input type="checkbox" value="1" class="<?php echo esc_attr( $hide_blackout_dates ? 'frm_date_show' : 'frm_hidden' ); ?>" data-show="frmdates_blackout_dates" data-value="1" />
				<span><?php esc_html_e( 'Blackout Dates', 'frmdates' ); ?></span>
				<?php
				FrmDatesAppController::show_svg_tooltip(
					__( 'Disable specific dates in the datepicker. These dates are disabled in addition to any weekdays you uncheck.', 'frmdates' ),
					array(
						'data-placement' => 'right',
						'class'          => 'frm-flex',
					)
				);
				?>
			</label>
		</p>

		<div class="frm-token-container frmdates_blackout_dates" id="frmdates_blackout_dates_<?php echo absint( $field['id'] ); ?>" style="<?php echo esc_attr( $hide_blackout_dates ? 'display: none;' : '' ); ?>">
			<input type="hidden" value="" class="frmdates_datepicker" />
			<a href="#" class="frmdates_add_blackout_date_link frm-h-stack" data-locale="<?php echo esc_attr( $field['locale'] ); ?>" data-field-id="<?php echo absint( $field['id'] ); ?>">
				<?php FrmAppHelper::icon_by_class( 'frm_icon_font ' . $calendar_icon . ' frm_add_tag frm_svg20 -frm-ml-2xs' ); ?>
				<span><?php esc_html_e( 'Add dates', 'frmdates' ); ?></span>
			</a>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo FrmDatesTemplatesHelper::settings_render_dates_list(
				array(
					'items'     => $field['blackout_dates'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'field_id'  => $field['id'], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'date_type' => 'blackout_dates',
				)
			);
			?>
		</div>
	</td>
</tr>
<tr>
	<td colspan="2">
		<label class="frm-h-stack-xs frm-m-0" for="frmdates_display_inline_<?php echo absint( $field['id'] ); ?>">
			<input class="frm-admin-datepicker-display-inline" type="checkbox" value="1" name="field_options[display_inline_<?php echo absint( $field['id'] ); ?>]" id="frmdates_display_inline_<?php echo absint( $field['id'] ); ?>" <?php checked( $field['display_inline'] ); ?>/>
			<span><?php esc_html_e( 'Display Inline Date Picker', 'frmdates' ); ?></span>
		</label>
	</td>
</tr>

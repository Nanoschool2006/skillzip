<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmProHtmlHelper {

	/**
	 * @since 5.0.17
	 *
	 * @param string $id
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string|void
	 */
	public static function toggle( $id, $name, $args ) {
		if ( FrmAppHelper::is_admin_page() ) {
			// Load keyboard shortcuts. If this is a setting, use admin_toggle() instead.
			wp_enqueue_script( 'formidable_pro_settings', FrmProAppHelper::plugin_url() . '/js/admin/settings.js', array(), FrmProDb::$plug_version, true );
		}

		return FrmAppHelper::clip(
			function () use ( $id, $name, $args ) {
				require FrmProAppHelper::plugin_path() . '/classes/views/shared/toggle.php';
			},
			$args['echo'] ?? false
		);
	}

	/**
	 * @since 6.0
	 *
	 * @param string $id
	 * @param string $name
	 * @param array  $args
	 *
	 * @return string|void
	 */
	public static function admin_toggle( $id, $name, $args ) {
		return FrmHtmlHelper::toggle( $id, $name, $args );
	}

	/**
	 * Echo a dropdown option.
	 * This is useful to avoid closing and opening PHP to echo <option> tags which leads to extra whitespace.
	 * Avoiding whitespace saves 5KB of HTML for an international address field with a country dropdown with 252 options.
	 *
	 * @since 6.3
	 *
	 * @param string $option   The string used as the option label.
	 * @param bool   $selected True if the option should be selected.
	 * @param array  $params   Other HTML params for the option.
	 *
	 * @return void
	 */
	public static function echo_dropdown_option( $option, $selected, $params = array() ) {
		echo '<option ';
		FrmAppHelper::array_to_html_params( $params, true );
		selected( $selected );
		echo '>';
		echo esc_html( $option === '' ? ' ' : $option );
		echo '</option>';
	}

	/**
	 * Output a simple radio button group
	 *
	 * @since 6.24
	 *
	 * @param string          $name       The name attribute for the radio group
	 * @param array           $options    Array of options with value => label pairs
	 * @param int|string|null $selected   The selected value
	 * @param bool            $horizontal Whether to display horizontally (default: true = horizontal)
	 *
	 * @return void
	 */
	public static function echo_radio_group( $name, $options, $selected, $horizontal = true ) {
		?>
		<div class="frm-gap-xs <?php echo $horizontal ? 'frm-flex' : 'frm-flex-col'; ?>">
			<?php foreach ( $options as $value => $label ) { ?>
				<label class="frm-h-stack frm-text-grey-700 frm-leading-none">
					<input
						class="frm-m-2xs"
						type="radio"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						<?php echo checked( $selected, $value, false ); ?>
					/>
					<span><?php echo esc_html( $label ); ?></span>
				</label>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Echo the NPS score to gather more feedback on the plugin.
	 *
	 * @since 6.26.1
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	public static function echo_nps( $args = array() ) {
		wp_enqueue_style( 'formidable-pro-nps', FrmProAppHelper::plugin_url() . '/css/components/nps.css', array(), FrmProDb::$plug_version );

		$defaults = array(
			'id'                 => '',
			'class'              => '',
			'name'               => 'nps_score',
			'value'              => '0',
			'negative_statement' => __( 'Not satisfied', 'formidable-pro' ),
			'positive_statement' => __( 'Very satisfied', 'formidable-pro' ),
		);
		$args     = wp_parse_args( $args, $defaults );

		include FrmProAppHelper::plugin_path() . '/classes/views/shared/nps.php';
	}
}

<?php

class FrmPllSettingsController {

	public static function load_hooks() {
		add_action( 'frm_form_action_translate', 'FrmPllSettingsController::translate' );
		add_action( 'frm_settings_buttons', 'FrmPllSettingsController::add_translate_button' );
		add_action( 'frm_form_action_update_translations', 'FrmPllSettingsController::update_translations' );
		add_filter( 'frm_form_stop_action_translate', '__return_true' );
		add_filter( 'frm_form_stop_action_update_translations', '__return_true' );
	}

	public static function add_translate_button( $values ) {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=formidable' ) . '&frm_action=translate&id=' . $values['id'] ); ?>" class="button-secondary"><?php esc_html_e( 'Translate Form', 'formidable-polylang' ); ?></a>
		<?php
	}

	public static function translate( $message = '' ) {
		if ( ! function_exists( 'pll_register_string' ) ) {
			include dirname( dirname( __FILE__ ) ) . '/views/settings/install_polylang.php';
			return;
		}

		$id   = FrmAppHelper::get_param( 'id', false, 'get', 'absint' );
		$form = FrmForm::getOne( $id );

		self::prepare_strings_for_form( $id );

		// load translations
		$listlanguages = pll_languages_list( array( 'fields' => '' ) );

		if ( ! empty( $listlanguages ) ) {
			$string_table = new PLL_Table_String( $listlanguages );
			$string_table->prepare_items();
		}

		if ( ! defined( 'PLL_ADMIN_INC' ) ) {
			define( 'PLL_ADMIN_INC', POLYLANG_DIR . '/admin' );
		}

		if ( ! defined( 'PLL_SETTINGS_INC' ) ) {
			define( 'PLL_SETTINGS_INC', POLYLANG_DIR . '/settings' );
		}

		include dirname( dirname( __FILE__ ) ) . '/views/settings/translate.php';
	}

	public static function update_translations() {
		$message = '';
		if ( isset( $_POST['translation'] ) && is_array( $_POST['translation'] ) ) {
			check_admin_referer( 'string-translation', '_wpnonce_string-translation' );
			PLL_Admin_Strings::init();
			$strings = self::get_strings();

			foreach ( pll_languages_list( array( 'fields' => '' ) ) as $language ) {
				if ( empty( $_POST['translation'][ $language->slug ] ) ) {
					continue;
				}

				$mo = new PLL_MO();
				$mo->import_from_db( $language );

				//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$translations = array_map( 'trim', wp_unslash( $_POST['translation'][ $language->slug ] ) );
				FrmAppHelper::sanitize_value( 'wp_kses_post', $translations );

				foreach ( $translations as $key => $translation ) {
					$translation = apply_filters( 'pll_sanitize_string_translation', $translation, $strings[ $key ]['name'], $strings[ $key ]['context'], $strings[ $key ]['string'] );
					$mo->add_entry( $mo->make_entry( $strings[ $key ]['string'], $translation ) );
				}

				$mo->export_to_db( $language );
			}
			$message = __( 'Settings Successfully Updated', 'formidable-polylang' );
		}

		self::translate( $message );
	}

	/**
	 * Prepare and register the translatable strings for a given form
	 *
	 * @since 1.05
	 * @param int $id
	 */
	private static function prepare_strings_for_form( $id ) {
		if ( ! $id ) {
			return;
		}

		$form = FrmForm::getOne( $id );

		$translate = new FrmPllAppController();
		$strings = $translate->get_form_strings( $form );
		$translate->register_strings( $form, $strings );

		add_filter( 'pll_get_strings', 'FrmPllSettingsController::remove_default_strings' );
	}

	private static function get_strings() {
		$id = FrmAppHelper::get_param( 'id', false, 'get', 'absint' );
		self::prepare_strings_for_form( $id );

		$data = PLL_Admin_Strings::get_strings();
		return $data;
	}

	public static function remove_default_strings( $strings ) {
		$all_strings = $strings;
		foreach ( $all_strings as $key => $string ) {
			if ( $string['context'] != 'Formidable' ) {
				unset( $strings[ $key ] );
			}
		}
		return $strings;
	}
}

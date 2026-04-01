<?php

class FrmLandingAppHelper {

	/**
	 * @since 1.0.01
	 *
	 * @var string $plug_version
	 */
	public static $plug_version = '1.0.02';

	/**
	 * @return string
	 */
	public static function path() {
		return dirname( dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * @return string
	 */
	public static function plugin_url() {
		return plugins_url( '', self::path() . '/formidable-landing.php' );
	}

	/**
	 * @return string
	 */
	public static function plugin_version() {
		return self::$plug_version;
	}

	/**
	 * @param int $form_id
	 * @return mixed
	 */
	public static function get_landing_page_post_id( $form_id ) {
		return FrmDb::get_var(
			'postmeta',
			array(
				'meta_key'   => 'frm_landing_form_id',
				'meta_value' => $form_id,
			),
			'post_id'
		);
	}

	/**
	 * @param int $post_id
	 * @return mixed
	 */
	public static function get_landing_page_form_id( $post_id ) {
		return FrmDb::get_var(
			'postmeta',
			array(
				'meta_key' => 'frm_landing_form_id',
				'post_id'  => $post_id,
			),
			'meta_value'
		);
	}

	/**
	 * @param int $form_id
	 *
	 * @return string
	 */
	public static function get_landing_page_layout( $form_id ) {
		if ( ! $form_id ) {
			return 'default';
		}

		return self::get_form_option(
			array(
				'form'    => $form_id,
				'option'  => 'landing_layout',
				'default' => 'default',
			)
		);
	}

	/**
	 * @param array $atts
	 *
	 * @return mixed
	 */
	public static function get_form_option( $atts ) {
		if ( is_numeric( $atts['form'] ) ) {
			$atts['form'] = FrmForm::getOne( $atts['form'] );
		}
		return FrmForm::get_option( $atts );
	}

	/**
	 * Set landing page settings from the form or fallback to the style settings.
	 *
	 * @param int $post_id The id of the landing page.
	 *
	 * @return array
	 */
	public static function get_landing_page_styles( $post_id ) {
		$form_id        = self::get_landing_page_form_id( $post_id );
		$form           = FrmForm::getOne( $form_id );
		$style_settings = self::get_form_style_settings( $form );

		$bg_image = self::get_bg_id( $form, $style_settings );
		$opacity  = self::get_opacity( $form, $style_settings );

		return compact( 'style_settings', 'bg_image', 'opacity' );
	}

	/**
	 * @param int|object $form
	 *
	 * @return array
	 */
	public static function get_form_style_settings( $form ) {
		$style = FrmStylesController::get_form_style( $form );
		if ( ! $style ) {
			$frm_style = new FrmStyle( 'default' );
			$style     = $frm_style->get_one();
		}

		$style_settings = $style->post_content;
		FrmStylesHelper::prepare_color_output( $style_settings );
		return $style_settings;
	}

	/**
	 * @param object $form
	 * @param array  $style_settings
	 *
	 * @return int
	 */
	private static function get_bg_id( $form, $style_settings ) {
		$bg_image = self::get_form_option(
			array(
				'form'   => $form,
				'option' => 'landing_bg_image_id',
			)
		);
		if ( ! $bg_image ) {
			$bg_image = $style_settings['bg_image_id'];
		}
		return $bg_image;
	}

	/**
	 * @param object $form
	 * @param array  $style_settings
	 *
	 * @return string
	 */
	private static function get_opacity( $form, $style_settings ) {
		$opacity = self::get_form_option(
			array(
				'form'    => $form,
				'option'  => 'landing_opacity',
			)
		);

		if ( '' === $opacity ) {
			$opacity = $style_settings['bg_image_opacity'];
		}

		if ( empty( $opacity ) ) {
			$opacity = '100%';
		}

		// Allow values like '20'.
		if ( is_numeric( $opacity ) && $opacity > 1 ) {
			$opacity .= '%';
		}
		return $opacity;
	}

	/**
	 * @param int   $form_id
	 * @param array $values
	 */
	public static function sync_landing_page( $form_id, $values ) {
		$action = FrmAppHelper::get_param( 'frm_action', '', 'post', 'sanitize_text_field' );
		if ( ! isset( $values['options'] ) || 'update_settings' !== $action ) {
			return;
		}

		$form_name = FrmDb::get_var( 'frm_forms', array( 'id' => $form_id ), 'name' );
		if ( ! $form_name ) {
			$form_name = '';
		}

		$options = $values['options'];

		$landing_page_active  = ! empty( $options['landing_page_id'] );
		$landing_page_post_id = self::get_landing_page_post_id( $form_id );
		if ( $landing_page_active ) {
			$page_name    = FrmAppHelper::get_post_param( 'frm_landing_page_url', '', 'sanitize_title' );
			$post_content = self::get_post_content( $landing_page_post_id, $form_id );

			if ( $landing_page_post_id ) {
				wp_update_post(
					array(
						'ID'           => $landing_page_post_id,
						'post_status'  => 'publish',
						'post_title'   => $form_name,
						'post_content' => $post_content,
						'post_name'    => $page_name,
					)
				);
			} else {
				$landing_page_post_id = wp_insert_post(
					array(
						'post_type'    => FrmLandingAppController::get_landing_page_post_type(),
						'post_title'   => $form_name,
						'post_content' => $post_content,
						'post_status'  => 'publish',
						'post_name'    => $page_name,
					)
				);
				update_post_meta( $landing_page_post_id, 'frm_landing_form_id', $form_id );
				FrmDb::cache_delete_group( 'postmeta' );
			}
		} elseif ( $landing_page_post_id ) {
			wp_update_post(
				array(
					'ID'          => $landing_page_post_id,
					'post_status' => 'private',
				)
			);
		}
	}

	/**
	 * Get the existing content to prevent it from getting overwritten on update.
	 *
	 * @param int $post_id
	 * @param int $form_id
	 *
	 * @return string
	 */
	private static function get_post_content( $post_id, $form_id ) {
		$shortcode    = '[formidable id="' . $form_id . '" title="1" description="1"]';
		$post_content = self::wrap_form_shortcode( $shortcode, $form_id );
		if ( empty( $post_id ) ) {
			// It's a new page, so set the content.
			return $post_content;
		}

		$current_page = get_post( $post_id );
		$new_content  = $current_page->post_content;

		// The existing page doesn't have a form.
		if ( false === strpos( $new_content, '[formidable' ) ) {
			$new_content = $new_content . $post_content;
		}

		return $new_content;
	}

	/**
	 * @param string $shortcode
	 * @param int    $form_id
	 * @return string
	 */
	private static function wrap_form_shortcode( $shortcode, $form_id ) {
		return '<!-- wp:formidable/simple-form {"formId":"' . $form_id . '","title":"1","description":"1"} -->' .
			'<div>' . $shortcode . '</div>' .
			'<!-- /wp:formidable/simple-form -->';
	}
}

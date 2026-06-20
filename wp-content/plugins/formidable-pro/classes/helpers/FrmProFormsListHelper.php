<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * @since 5.3.1
 */
class FrmProFormsListHelper extends FrmFormsListHelper {

	/**
	 * All application IDs are queried once and stored for later use.
	 *
	 * @since 6.16
	 *
	 * @var array|null
	 */
	private $application_ids_by_form_id;

	/**
	 * Style options are queried once and stored for later use.
	 *
	 * @since 6.32
	 *
	 * @var array|null
	 */
	private $style_opts;

	/**
	 * @param array $args
	 */
	public function __construct( $args ) {
		parent::__construct( $args );
		wp_enqueue_style( 'frm_applications_common' );
	}

	/**
	 * @param stdClass $form
	 *
	 * @return string
	 */
	public function column_application( $form ) {
		$application_ids = $this->get_application_ids_for_form_id( $form->id );
		return FrmProApplicationsHelper::get_application_tags_html( array_unique( $application_ids ) );
	}

	/**
	 * @since 6.16
	 *
	 * @param int|string $form_id
	 *
	 * @return array
	 */
	private function get_application_ids_for_form_id( $form_id ) {
		if ( ! isset( $this->application_ids_by_form_id ) ) {
			$this->init_application_ids_by_form_id();
		}

		if ( ! is_array( $this->application_ids_by_form_id ) || ! array_key_exists( $form_id, $this->application_ids_by_form_id ) ) {
			return array();
		}

		$application_ids = $this->application_ids_by_form_id[ $form_id ];
		return is_array( $application_ids ) ? $application_ids : array();
	}

	/**
	 * Query for application ID data for all forms instead of querying for every form in the list.
	 *
	 * @since 6.16
	 *
	 * @return void
	 */
	private function init_application_ids_by_form_id() {
		global $wpdb;
		$this->application_ids_by_form_id = array();

		if ( empty( $this->items ) || ! is_array( $this->items ) ) {
			return;
		}

		$form_ids = array_unique( wp_list_pluck( $this->items, 'id' ) );

		if ( ! $form_ids ) {
			return;
		}

		$where               = array(
			'meta_key'   => '_frm_form_id',
			'meta_value' => $form_ids,
		);
		$application_id_data = FrmDb::get_results( $wpdb->termmeta, $where, 'term_id, meta_value' );

		foreach ( $application_id_data as $row ) {
			$application_id = $row->term_id;
			$form_id        = $row->meta_value;

			if ( ! array_key_exists( $form_id, $this->application_ids_by_form_id ) ) {
				$this->application_ids_by_form_id[ $form_id ] = array();
			}

			$this->application_ids_by_form_id[ $form_id ][] = $application_id;
		}
	}

	/**
	 * @since 6.32
	 *
	 * @param stdClass $form
	 *
	 * @return string
	 */
	public function column_style2( $form ) {
		FrmProAppHelper::include_svg();

		if ( null === $this->style_opts ) {
			$this->style_opts = FrmStylesController::get_style_opts();
		}

		$selected = intval( $form->options['custom_style'] ?? 1 );

		// Get the actual default style ID for the JavaScript to use.
		$default_style    = ( new FrmStyle() )->get_default_style( $this->style_opts );
		$default_style_id = $default_style ? $default_style->ID : 1;

		return FrmAppHelper::clip(
			function () use ( $form, $selected, $default_style_id ) {
				?>
				<div class="frm-form-select-wrapper" data-form-id="<?php echo intval( $form->id ); ?>" data-default-style-id="<?php echo intval( $default_style_id ); ?>">
					<?php
					FrmProAppHelper::custom_dropdown(
						$this->get_form_style_opts( $this->style_opts, $form ),
						array(
							'class'       => 'frm-form-style-select',
							'selected'    => $selected,
							'empty_label' => __( 'Default', 'formidable' ),
						)
					);
					?>
				</div>
				<?php
			}
		);
	}

	/**
	 * @since 6.32
	 *
	 * @param array    $styles
	 * @param stdClass $form
	 *
	 * @return array
	 */
	private function get_form_style_opts( $styles, $form ) {
		$selected = intval( $form->options['custom_style'] ?? 1 );

		// Get the actual default style ID for the initial Edit link.
		$default_style    = ( new FrmStyle() )->get_default_style( $styles );
		$default_style_id = $default_style ? $default_style->ID : 1;
		$edit_style_id    = 1 === $selected ? $default_style_id : $selected;

		$options = array(
			array(
				'is_group' => true,
				'options'  => array(
					1 => __( 'Default', 'formidable' ),
				),
			),
			array(
				'is_divider' => true,
			),
			array(
				'url'  => admin_url( 'admin.php?page=formidable-styles&form=' . $form->id . '&id=' . $edit_style_id ),
				'text' => FrmAppHelper::icon_by_class( 'frmfont frm_pencil_with_underscore_icon', array( 'echo' => false ) ) . __( 'Edit Style', 'formidable' ),
			),
		);

		foreach ( $styles as $style ) {
			$options[0]['options'][ $style->ID ] = $style->post_title . ' ';

			// translators: %d: style ID
			$options[0]['options'][ $style->ID ] .= sprintf( esc_html__( '(#%d)', 'formidable-pro' ), $style->ID );
		}

		return $options;
	}

	/**
	 * Override the Lite version to support nested forms.
	 *
	 * @since 6.32
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return string[]
	 */
	protected function get_search_strings_for_form( $form_id ) {
		$query_strings      = $this->get_base_search_strings_for_form( $form_id );
		$forms_contain_this = FrmProNestedFormsController::get_forms_contain_embedded_form( $form_id );

		if ( ! $forms_contain_this ) {
			return $query_strings;
		}

		foreach ( $forms_contain_this as $form_contain_this ) {
			$query_strings = array_merge( $query_strings, $this->get_base_search_strings_for_form( $form_contain_this ) );
		}

		return $query_strings;
	}
}

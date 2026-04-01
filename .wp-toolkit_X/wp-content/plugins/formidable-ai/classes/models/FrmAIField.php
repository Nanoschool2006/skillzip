<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FrmAIField extends FrmFieldHidden {

	/**
	 * The type of field being displayed.
	 *
	 * @var string
	 */
	protected $type = 'ai';

	/**
	 * Is there an input field on the page?
	 *
	 * @var bool
	 */
	protected $has_input = true;

	/**
	 * Should the HTML be customizable?
	 *
	 * @var bool
	 */
	protected $has_html = true;

	/**
	 * The current field.
	 *
	 * @var object|array|int
	 */
	protected $field;

	/**
	 * Which Formidable settings should be hidden or displayed?
	 *
	 * @return array
	 */
	protected function field_settings_for_type() {
		$settings             = parent::field_settings_for_type();
		$settings['default']  = true;
		$settings['logic']    = true;
		$settings['required'] = false;
		$settings['css']      = true;

		return $settings;
	}

	/**
	 * Need custom options too? Add them here or remove this function.
	 *
	 * @return array
	 */
	protected function extra_field_opts() {
		return array(
			'system'      => __( 'You are a helpful assistant', 'formidable-ai' ),
			'hide_ai'     => false,
			'watch_ai'    => array(),
			'ai_question' => '',
			'ai_model'    => '',
		);
	}

	/**
	 * Set the file shown in the builder.
	 *
	 * @return string
	 */
	protected function include_form_builder_file() {
		return FrmAIAppHelper::plugin_path() . '/classes/views/builder-field.php';
	}

	/**
	 * Get the type of field being displayed. This is required to add a settings
	 * section just for this field. show_extra_field_choices will not be triggered
	 * without it.
	 *
	 * @param array $field The field being displayed.
	 * @return array
	 */
	public function displayed_field_type( $field ) {
		return array(
			$this->type => true,
		);
	}

	/**
	 * Add settings in the builder here.
	 *
	 * @param array $args Arguments for the field.
	 * @return void
	 */
	public function show_extra_field_choices( $args ) {
		$field = $args['field'];

		$ai_question = '';
		if ( ! empty( $field['ai_question'] ) ) {
			$ai_question = $field['ai_question'];
		} elseif ( ! empty( $field['watch_ai'] ) && is_array( $field['watch_ai'] ) ) {
			foreach ( $field['watch_ai'] as $watch_field_id ) {
				$ai_question .= '[' . $watch_field_id . '] ';
			}
		}
		$ai_question = trim( $ai_question );

		$ai_model = isset( $field['ai_model'] ) ? $field['ai_model'] : 'gpt-3.5-turbo';

		include FrmAIAppHelper::plugin_path() . '/classes/views/builder-settings.php';
	}

	/**
	 * If the field won't be shown, only show the hidden field.
	 *
	 * @param array  $args Arguments for the field.
	 * @param string $html HTML for the field.
	 *
	 * @return string
	 */
	protected function before_replace_html_shortcodes( $args, $html ) {
		$hide = FrmField::get_option( $this->field, 'hide_ai' );
		if ( $hide ) {
			// Don't add extra HTML if the field is hidden.
			$html = $this->input_html();
		}
		return $html;
	}

	/**
	 * Add custom classes on the field HTML container.
	 *
	 * @return string
	 */
	public function get_container_class() {
		$class  = parent::get_container_class();
		$class .= ' frm_ai_field_container';
		if ( empty( $this->get_field_column( 'value' ) ) ) {
			$class .= ' frm_none_container';
		}
		return $class;
	}

	/**
	 * Add extra HTML to the default.
	 *
	 * @return string
	 */
	public function default_html() {
		$html = parent::default_html();
		$div  = $this->ai_container();
		return str_replace( '[input]', '[input]' . $div, $html );
	}

	/**
	 * Get the HTML for the AI container.
	 *
	 * @return string
	 */
	private function ai_container() {
		return <<<DEFAULT_HTML

    <div id="frm_ai_response_[id]" class="frm_ai_response frm_description">
        [if loading]<div class="frm_ai_loading"></div>[/if loading]
    </div>
DEFAULT_HTML;
	}

	/**
	 * Replace the loading indicator with the actual value.
	 *
	 * @param array  $args Arguments for the field.
	 * @param string $html HTML for the field.
	 * @return string
	 */
	protected function after_replace_html_shortcodes( $args, $html ) {
		$hide = FrmField::get_option( $this->field, 'hide_ai' );
		if ( $hide ) {
			return $html;
		}

		$show_value = $this->get_field_column( 'value' );
		$value      = $this->get_display_value( $show_value );
		$is_default = $value && ( $show_value === $this->get_field_column( 'default_value' ) );
		if ( $is_default ) {
			// Add default value to [if loading] section.
			$value = '<div class="frm_ai_default">' . $value . '</div>';
			$html  = str_replace( '[if loading]', '[if loading]' . $value, $html );
		} elseif ( $value ) {
			// Replace [if loading] section with value.
			$html = preg_replace( '/(\[if\s+loading\])(.*?)(\[\/if\s+loading\])/mis', $value, $html );
			if ( ! $html ) {
				// If preg_replace fails, just return the value.
				$html = $value;
			}
		}

		// Remove [if loading] tags.
		$html = str_replace( array( '[if loading]', '[/if loading]' ), '', $html );

		return $html;
	}

	/**
	 * Show the field in the front end.
	 *
	 * @param array $args The arguments passed to FrmFieldsHelper::get_front_field_input().
	 * @param array $shortcode_atts The attributes passed to the [formidable] shortcode.
	 * @return string Whatever shows in the front end goes here.
	 */
	public function front_field_input( $args, $shortcode_atts ) {
		$input_html = parent::front_field_input( $args, $shortcode_atts );

		$settings = array(
			'field'   => $args['field_id'],
			'watch'   => $this->setup_watched_fields(),
			'ajax'    => FrmAIAppHelper::ajax_url(),
			'trigger' => $this->should_setup_ai_value(),
			'id'      => $args['html_id'],
		);

		ob_start();
		include FrmAIAppHelper::plugin_path() . '/classes/views/front-end-field.php';
		$script = ob_get_contents();
		ob_end_clean();

		return $input_html . $script;
	}

	/**
	 * Get the field keys for the watched fields in a format that will cover
	 * different field types, repeaters, and embedded forms.
	 *
	 * @since 2.0
	 * @return array
	 */
	private function setup_watched_fields() {
		$watch_ids      = array();
		$watched_fields = FrmField::get_option( $this->field, 'watch_ai' );

		foreach ( $watched_fields as $watched_field_id ) {
			$watched_field = FrmField::getOne( $watched_field_id );
			if ( ! $watched_field ) {
				continue;
			}

			$prefix = '#';
			$id     = 'field_' . $watched_field->field_key;

			if ( $this->use_flexible_id( $watched_field ) ) {
				// Checkbox, Radio: field_cg9jf-0.
				// Embedded, repeater: field_cg9jf-0.
				// Checkbox in embedded: field_cg9jf-0-0.
				// Name fields: field_1r3w5_first and field_1r3w5_last.
				$prefix = '[id^="';
				if ( in_array( $watched_field->type, array( 'address', 'name' ), true ) ) {
					$id .= '_';
				} else {
					$id .= '-';
				}
				$id .= '"]';
			}

			$watch_ids[] = $prefix . $id;
		}

		return $watch_ids;
	}

	/**
	 * Which fields should use the flexible ids?
	 *
	 * @param object{form_id:int, field_key:string, type:string, field_options:array} $watched_field The field to check.
	 * @return bool
	 */
	private function use_flexible_id( $watched_field ) {
		$form_id  = $this->get_field_column( 'form_id' );
		$embedded = $form_id !== $watched_field->form_id;
		$embedded = $embedded || ! empty( $watched_field->field_options['in_embed_form'] );

		if ( $embedded ) {
			return true;
		}

		if ( FrmField::is_checkbox( $watched_field ) || FrmField::is_radio( $watched_field ) ) {
			return true;
		}

		if ( FrmField::is_combo_field( $watched_field ) || $watched_field->type === 'address' ) {
			return true;
		}

		return false;
	}

	/**
	 * Pass the prompt and form id in the field HTML.
	 *
	 * @param array  $args Arguments for the field.
	 * @param string $input_html HTML for the field.
	 * @return void
	 */
	protected function add_extra_html_atts( $args, &$input_html ) {
		$input_html .= ' data-ai-prompt="' . esc_attr( $this->get_system_prompt() ) . '"' .
			' data-ai-question="' . esc_attr( $this->get_ai_question() ) . '"' .
			' data-form-id="' . esc_attr( $this->get_field_column( 'form_id' ) ) . '" ';
	}

	/**
	 * Get the system prompt from the settings.
	 *
	 * @return string
	 */
	private function get_system_prompt() {
		return str_replace( array( "\r", "\n" ), ' ', FrmField::get_option( $this->field, 'system' ) );
	}

	/**
	 * Get the ai question from the settings.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	private function get_ai_question() {
		return str_replace( array( "\r", "\n" ), ' ', FrmField::get_option( $this->field, 'ai_question' ) );
	}

	/**
	 * Load the js for the field.
	 *
	 * @param array $args Arguments for the field.
	 * @return void
	 */
	protected function load_field_scripts( $args ) {
		$version = FrmAIAppHelper::$plug_version;
		wp_enqueue_script( 'frmai', FrmAIAppHelper::plugin_url() . '/js/ai.js', array(), $version, true );
	}

	/**
	 * If the watched fields have a value and the AI field doesn't, this will trigger the js
	 * to run an API check. This can happen in multi-page forms or if the form is
	 * submitted before the API call is finished.
	 *
	 * @return bool
	 */
	private function should_setup_ai_value() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $this->get_field_column( 'value' ) ) || empty( $_POST ) ) {
			return false;
		}

		$watching   = FrmField::get_option( $this->field, 'watch_ai' );
		$should_set = false;
		foreach ( $watching as $watch_field_id ) {
			$other_field_value = '';
			FrmEntriesHelper::get_posted_value( $watch_field_id, $other_field_value, array() );
			if ( empty( $other_field_value ) ) {
				// Skip the check if the watched field is empty.
				return false;
			}
			$should_set = true;
		}

		return $should_set;
	}

	/**
	 * Sanitize the value without any HTML.
	 *
	 * @since 1.0.1
	 * @param string $value The value to sanitize.
	 * @return void
	 */
	public function sanitize_value( &$value ) {
		sanitize_textarea_field( $value );
	}

	/**
	 * Prepare the value for display.
	 *
	 * @param string $value The value to display.
	 * @param array  $atts The attributes passed.
	 * @return string
	 */
	protected function prepare_display_value( $value, $atts ) {
		return wpautop( $value );
	}
}

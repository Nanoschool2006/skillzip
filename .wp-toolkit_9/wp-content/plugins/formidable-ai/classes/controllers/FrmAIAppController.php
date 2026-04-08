<?php
/**
 * App controller
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FrmAIAppController
 */
class FrmAIAppController {

	/**
	 * Shows the incompatible notice.
	 *
	 * @return void
	 */
	public static function show_incompatible_notice() {
		if ( FrmAIAppHelper::is_compatible() ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'You are running an outdated version of Formidable Forms.', 'formidable-ai' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Initializes plugin translation.
	 *
	 * @return void
	 */
	public static function init_translation() {
		load_plugin_textdomain( 'formidable-ai', false, FrmAIAppHelper::plugin_folder() . '/languages/' );
	}

	/**
	 * Includes addon updater.
	 *
	 * @return void
	 */
	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmAIUpdate::load_hooks();
		}
	}

	/**
	 * Trigger a CSS update when the plugin is activated.
	 *
	 * @return void
	 */
	public static function update_stylesheet_on_activation() {
		if ( ! function_exists( 'load_formidable_forms' ) || ! function_exists( 'get_filesystem_method' ) ) {
			return;
		}

		// This is run before other hooks, so we need to manually load the CSS.
		add_action( 'frm_include_front_css', array( __CLASS__, 'load_css' ) );

		$frm_style = new FrmStyle();
		$frm_style->update( 'default' );
	}

	/**
	 * Tell Formidable where to find the new field type.
	 *
	 * @param string $class The name of the class that extends FrmFieldType.
	 * @param string $field_type The type of field.
	 * @return string The name of the new class that extends FrmFieldType.
	 */
	public static function get_field_type_class( $class, $field_type ) {
		if ( $field_type === 'ai' ) {
			$class = 'FrmAIField';
		}
		return $class;
	}

	/**
	 * Add the AI field to the list of available fields.
	 *
	 * @param array $fields The list of available fields.
	 * @return array
	 */
	public static function add_new_field( $fields ) {
		$icon         = class_exists( 'FrmTextToggleStyleComponent' ) ? 'frm-ai-icon' : 'frm_eye_icon';
		$fields['ai'] = array(
			'name' => 'AI',
			'icon' => 'frm_icon_font ' . $icon,
		);
		return $fields;
	}

	/**
	 * Switch the watch_ai IDs when a field is imported.
	 * TODO: Handle this in the core plugin like the form actions.
	 *
	 * @param array $values The field values to save.
	 * @return array
	 */
	public static function switch_ids_after_import( $values ) {
		global $frm_duplicate_ids;

		$setting      = 'watch_ai';
		$old_field_id = isset( $values['field_options'][ $setting ] ) ? $values['field_options'][ $setting ] : 0;
		if ( ! $old_field_id || ! is_array( $old_field_id ) ) {
			return $values;
		}

		$values['field_options'][ $setting ] = array();
		foreach ( $old_field_id as $old_id ) {
			$values['field_options'][ $setting ][] = isset( $frm_duplicate_ids[ $old_id ] ) ? $frm_duplicate_ids[ $old_id ] : $old_id;
		}

		return $values;
	}

	/**
	 * Include the AI field js in form builder.
	 *
	 * @return void
	 */
	public static function enqueue_builder_scripts() {
		if ( ! FrmAppHelper::is_form_builder_page() ) {
			return;
		}

		$version = FrmAIAppHelper::$plug_version;
		$plugin_url = FrmAIAppHelper::plugin_url();

		wp_enqueue_script( 'formidable_ai_admin', $plugin_url . '/js/admin.js', array( 'formidable_dom', 'wp-hooks' ), $version, true );
		wp_add_inline_style(
			'formidable-admin',
			'.frm-type-ai .frmsvg.frm-show-box { bottom: var(--gap-sm); }'
		);

		if ( class_exists( 'FrmTextToggleStyleComponent' ) ) { // Backwards compatibility condition "@since 2.1".
			wp_enqueue_script( 'formidable-generate-options-with-ai', $plugin_url . '/js/generate-options-with-ai.js', array( 'formidable_pro_builder' ), $version, true );
			wp_enqueue_style( 'formidable-create-ai-form-button', $plugin_url . '/css/create-ai-form-button.css', array(), $version );
		} else {
			// Backwards compatibility "@since 2.1".
			wp_add_inline_style(
				'formidable-admin',
				'.frm-h-stack-xs { display: flex !important; align-items: center; gap: var(--gap-xs); } .frm-h-stack-xs .frm_help .frmsvg { position: static; }'
			);
		}
	}

	/**
	 * Include the AI field js for ajax forms.
	 *
	 * @param array $scripts The list of scripts to allow.
	 * @return array
	 */
	public static function ajax_load_script( $scripts ) {
		$scripts[] = 'frmai';
		return $scripts;
	}

	/**
	 * The ajax endpoint for the AI requests.
	 *
	 * @return void
	 */
	public static function get_ai_response() {
		$data = file_get_contents( 'php://input' );
		if ( $data ) {
			$data = (array) json_decode( $data, true );
		}

		if ( empty( $data ) || empty( $data['id'] ) ) {
			wp_send_json_error( 'No form ID provided' );
			return; // Let PHPStan exclude a few parameter types.
		}

		// Set the token as a POST variable so that the anti-spam check will work.
		$_POST['antispam_token'] = $data['token'];
		if ( self::is_spam( absint( $data['id'] ) ) ) {
			wp_send_json_error( 'Spam detected' );
		}

		$field_id = isset( $data['field_id'] ) ? absint( $data['field_id'] ) : 0;
		if ( $field_id ) {
			$field = FrmField::getOne( $field_id );

			if ( ! $field || 'ai' !== $field->type ) {
				wp_send_json_error( 'Invalid AI request.' );
			}

			if ( ! empty( $field->field_options['ai_model'] ) ) {
				$data['gpt_version'] = $field->field_options['ai_model'];
			}
		}

		FrmAIChatGPT::get_json_response( $data );
	}

	/**
	 * Check if the form submission is spam using the js token.
	 *
	 * @param int $form_id The ID of the current form.
	 * @return bool
	 */
	private static function is_spam( $form_id ) {
		$aspm = new FrmAntiSpam( $form_id );
		return is_string( $aspm->validate() );
	}

	/**
	 * Add the CSS into the main FF CSS file.
	 *
	 * @param array $args The arguments for the CSS.
	 * @return void
	 */
	public static function load_css( $args ) {
		$bg_color = FrmStylesHelper::adjust_brightness( $args['defaults']['border_color'], 45 );
		include FrmAIAppHelper::plugin_path() . '/css/front-end.css.php';
	}

	/**
	 * Extracts shortcodes from $prompt and $question and save them in watch_ai field prop.
	 *
	 * @since 2.0
	 *
	 * @param array  $field_options The field options.
	 * @param object $field         The field object.
	 * @return array
	 */
	public static function populate_watched_fields( $field_options, $field ) {
		$prompt   = empty( $field_options['system'] ) ? '' : $field_options['system'];
		$question = empty( $field_options['ai_question'] ) ? '' : $field_options['ai_question'];

		if ( ( property_exists( $field, 'type' ) && $field->type !== 'ai' ) || ( ! $prompt && ! $question ) ) {
			return $field_options;
		}
		$combined_text = $prompt . $question;
		preg_match_all( '/\[(\w+)]/', $combined_text, $matches );
		if ( empty( $matches[1] ) ) {
			return $field_options;
		}
		$field_options['watch_ai'] = $matches[1];

		return $field_options;
	}

	/**
	 * Show the button to create a form with AI beside the Create a blank form button on the form templates page.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function after_create_blank_form_button() {
		?>
		<button id="frm-form-templates-create-ai-form" class="frm-flex-box frm-items-center frm-form-templates-create-button">
			<?php FrmAppHelper::icon_by_class( 'frmfont frm-ai-form-icon', array( 'aria-label' => _x( 'Create', 'form templates: create an AI generated form', 'formidable-ai' ) ) ); ?>
			<span><?php esc_html_e( 'Create with AI', 'formidable-ai' ); ?></span>
			<?php FrmAppHelper::show_pill_text( __( 'BETA', 'formidable-ai' ) ); ?>
		</button>
		<?php
	}

	/**
	 * Enqueue assets on the form templates page to enable the Create with AI button / modal functionality.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function form_templates_enqueue_assets() {
		$plugin_url      = FrmAIAppHelper::plugin_url();
		$js_dependencies = array( 'wp-i18n', 'formidable_dom' );
		$version         = FrmAIAppHelper::$plug_version;
		wp_register_script( 'formidable-create-ai-form-button', $plugin_url . '/js/create-ai-form-button.js', $js_dependencies, $version, true );
		wp_localize_script(
			'formidable-create-ai-form-button',
			'frmAiFormGeneratorVars',
			array(
				'tryExampleSvgUrl' => FrmAIAppHelper::plugin_url() . '/images/try-example.svg',
			)
		);

		wp_enqueue_script( 'formidable-create-ai-form-button' );

		wp_register_style( 'formidable-create-ai-form-button', $plugin_url . '/css/create-ai-form-button.css', array(), $version );
		wp_enqueue_style( 'formidable-create-ai-form-button' );
	}

	/**
	 * Handle AJAX request to get JSON field data based on a prompt input.
	 * Used for the Create with AI modal on the form templates page.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function get_ai_generated_form_summary() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$prompt = FrmAppHelper::get_post_param( 'prompt', '', 'sanitize_textarea_field' );
		if ( ! $prompt ) {
			wp_send_json_error( 'Request is missing prompt data.' );
		}

		$response = FrmAIFormGenerator::get_response(
			array(
				'question'    => $prompt,
				'gpt_version' => FrmAppHelper::get_post_param( 'model', '', 'sanitize_text_field' ),
			)
		);
		if ( isset( $response['error'] ) ) {
			wp_send_json_error( $response['error'] );
		}

		$json = json_encode( $response['success'] );
		if ( false === $json ) {
			wp_send_json_error( 'API response was invalid' );
		}

		$decoded_prompt = json_decode( $json, true );
		if ( ! is_array( $decoded_prompt ) ) {
			wp_send_json_error( 'API response was invalid' );
		}

		if ( empty( $decoded_prompt['fields'] ) ) {
			wp_send_json_error( 'The AI failed to generate a form.' );
		}

		$unique_token = uniqid();
		set_transient( 'frm_ai_form_data_' . $unique_token, $json, HOUR_IN_SECONDS );

		$data                = $decoded_prompt;
		$data['uniqueToken'] = $unique_token;

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to get JSON field data based on a prompt input.
	 * Used for the Generate Options with AI modal.
	 *
	 * @since 2.1
	 *
	 * @return void
	 */
	public static function get_ai_generated_options_summary() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$prompt = FrmAppHelper::get_post_param( 'prompt', '', 'sanitize_textarea_field' );
		if ( ! $prompt ) {
			wp_send_json_error( 'Request is missing prompt data.' );
		}

		$response = FrmAIChatGPT::get_response(
			array(
				'prompt'      => implode(
					"\n",
					array(
						'1. Put your ENTIRE response on a SINGLE LINE with NO line breaks',
						'1a. Return only a JSON object containing exactly one key "options".',
						'1b. The JSON must exactly match this schema: {"options":["Option","Other"]}.',
						'2. The value of "options" must be a JSON array of strings, each a plain option text with no extra text, markdown, code fences or characters.',
						'3. Generate those options based solely on the following question: ',
					)
				),
				'question'    => $prompt,
				'gpt_version' => FrmAppHelper::get_post_param( 'model', '', 'sanitize_text_field' ),
			)
		);

		if ( isset( $response['error'] ) ) {
			wp_send_json_error( $response['error'] );
		}

		$json = $response['success'][0]; // Since FrmAIChatGPT::sanitize_answer uses array_values, the options data is inside the first element.
		if ( false === $json ) {
			wp_send_json_error( 'API response was invalid' );
		}

		$decoded_prompt = json_decode( $json, true );
		if ( ! is_array( $decoded_prompt ) ) {
			wp_send_json_error( 'API response was invalid' );
		}

		if ( empty( $decoded_prompt['options'] ) ) {
			wp_send_json_error( 'The AI failed to generate options.' );
		}

		$unique_token = uniqid();
		set_transient( 'frm_ai_options_data_' . $unique_token, $json, HOUR_IN_SECONDS );

		$data                = $decoded_prompt;
		$data['uniqueToken'] = $unique_token;

		wp_send_json_success( $data );
	}

	/**
	 * Handle AJAX request to confirm AI generated form summary.
	 * Use the previous AI form summary response to generate a form.
	 *
	 * @since 2.0
	 *
	 * @return void
	 */
	public static function create_ai_generated_form() {
		FrmAppHelper::permission_check( 'frm_edit_forms' );
		check_ajax_referer( 'frm_ajax', 'nonce' );

		$unique_token = FrmAppHelper::get_post_param( 'token', '', 'sanitize_text_field' );
		if ( ! $unique_token ) {
			wp_send_json_error( __( 'Request is missing token.', 'formidable-ai' ) );
		}

		$json = get_transient( 'frm_ai_form_data_' . $unique_token );
		if ( ! is_string( $json ) ) {
			wp_send_json_error( __( 'Generate form data no longer exists.', 'formidable-ai' ) );
		}

		if ( ! function_exists( 'simplexml_import_dom' ) ) {
			wp_send_json_error( __( 'Your server is missing the simplexml_import_dom function', 'formidable-ai' ) );
		}

		$fixer      = new FrmAIFormXMLBuilder( $json );
		$xml_string = $fixer->get_fixed_output();

		if ( ! class_exists( 'DOMDocument' ) ) {
			wp_send_json_error( __( 'In order to install XML, your server must have DOMDocument installed.', 'formidable-ai' ) );
		}

		$dom     = new DOMDocument();
		$success = $dom->loadXML( $xml_string, LIBXML_COMPACT | LIBXML_PARSEHUGE );

		if ( ! $success ) {
			wp_send_json_error( __( 'Failed trying to load XML data.', 'formidable-ai' ) );
		}

		$xml = simplexml_import_dom( $dom );
		unset( $dom );

		if ( ! $xml ) {
			wp_send_json_error( __( 'There was an error when reading this XML file', 'formidable-ai' ) );
		}

		$imported = FrmXMLHelper::import_xml_now( $xml );

		if ( ! empty( $imported['error'] ) ) {
			wp_send_json_error( __( 'Importing XML failed: ', 'formidable-ai' ) . ' ' . $imported['error'] );
		}

		if ( empty( $imported['forms'] ) ) {
			wp_send_json_error( __( 'No forms were actually generated. Please reach out to support.', 'formidable-ai' ) );
		}

		$form_id = reset( $imported['forms'] );

		self::track_ai_form();

		wp_send_json_success(
			array(
				'redirect' => admin_url( 'admin.php?page=formidable&frm_action=edit&id=' . absint( $form_id ) ),
			)
		);
	}

	/**
	 * Track the number of AI-generated forms.
	 *
	 * @since 2.0.1
	 *
	 * @return void
	 */
	private static function track_ai_form() {
		if ( method_exists( 'FrmUsageController', 'update_flows_data' ) ) {
			FrmUsageController::update_flows_data( 'form_templates', 'ai' );
		}
	}

	/**
	 * Show a tooltip icon with the message passed.
	 *
	 * @since 2.0.1
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
	 * Add a Watch fields row in the field options (when the + or Watch Fields link is clicked)
	 *
	 * @deprecated 2.0
	 *
	 * @return void
	 */
	public static function add_watch_ai_row() {
		_deprecated_function( __METHOD__, '2.0' );
	}
}

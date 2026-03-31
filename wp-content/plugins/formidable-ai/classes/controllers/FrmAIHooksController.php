<?php
/**
 * Hooks controller
 *
 * @package FrmAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class FrmAIHooksController
 */
class FrmAIHooksController {

	/**
	 * Adds this class to hook controllers list.
	 *
	 * @param array $controllers Hooks controllers.
	 * @return array
	 */
	public static function add_hooks_controller( $controllers ) {
		if ( ! FrmAIAppHelper::is_compatible() ) {
			self::load_incompatible_hooks();
			return $controllers;
		}

		$controllers[] = __CLASS__;
		return $controllers;
	}

	/**
	 * Loads hooks when this plugin isn't safe to run.
	 *
	 * @return void
	 */
	private static function load_incompatible_hooks() {
		self::load_translation();

		add_action( 'admin_notices', array( 'FrmAIAppController', 'show_incompatible_notice' ) );
	}

	/**
	 * Loads translation.
	 *
	 * @return void
	 */
	private static function load_translation() {
		add_action( 'init', array( 'FrmAIAppController', 'init_translation' ) );
	}

	/**
	 * Loads plugin hooks.
	 *
	 * @return void
	 */
	public static function load_hooks() {
		self::load_translation();
		add_filter( 'frm_get_field_type_class', array( 'FrmAIAppController', 'get_field_type_class' ), 10, 2 );
		add_filter( 'frm_pro_available_fields', array( 'FrmAIAppController', 'add_new_field' ) );
		add_action( 'frm_include_front_css', array( 'FrmAIAppController', 'load_css' ) );
	}

	/**
	 * These hooks only load during ajax request.
	 *
	 * @return void
	 */
	public static function load_ajax_hooks() {
		add_action( 'wp_ajax_frm_ai_get_answer', array( 'FrmAIAppController', 'get_ai_response' ) );
		add_action( 'wp_ajax_nopriv_frm_ai_get_answer', array( 'FrmAIAppController', 'get_ai_response' ) );

		add_filter( 'frm_ajax_load_scripts', array( 'FrmAIAppController', 'ajax_load_script' ) );

		add_action( 'wp_ajax_frm_get_ai_generated_form_summary', array( 'FrmAIAppController', 'get_ai_generated_form_summary' ) );
		add_action( 'wp_ajax_frm_create_ai_generated_form', array( 'FrmAIAppController', 'create_ai_generated_form' ) );

		add_action( 'wp_ajax_frm_get_ai_generated_options_summary', array( 'FrmAIAppController', 'get_ai_generated_options_summary' ) );
	}

	/**
	 * These hooks only load in the admin area.
	 *
	 * @return void
	 */
	public static function load_admin_hooks() {
		add_action( 'admin_init', array( 'FrmAIAppController', 'include_updater' ) );
		add_action( 'frm_enqueue_builder_scripts', array( 'FrmAIAppController', 'enqueue_builder_scripts' ) );
		add_filter( 'frm_duplicated_field', array( 'FrmAIAppController', 'switch_ids_after_import' ), 10, 2 );
		add_filter( 'frm_update_field_options', array( 'FrmAIAppController', 'populate_watched_fields' ), 10, 2 );
		add_action( 'frm_after_create_blank_form_button', array( 'FrmAIAppController', 'after_create_blank_form_button' ) );
		add_action( 'frm_form_templates_enqueue_assets', array( 'FrmAIAppController', 'form_templates_enqueue_assets' ) );
	}
}

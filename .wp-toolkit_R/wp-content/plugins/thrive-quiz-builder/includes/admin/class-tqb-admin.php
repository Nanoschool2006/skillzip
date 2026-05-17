<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 8/30/2016
 * Time: 3:53 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden.
}

/**
 * Thrive_Quiz_Builder_Admin class.
 */
class Thrive_Quiz_Builder_Admin {
	
	const NONCE_KEY_AJAX = 'tqb_admin_ajax_request';

	/**
	 * Constructor for Thrive_Quiz_Builder_Admin
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'includes' ) );

		if ( ! empty( $_GET['action'] ) && 'tqb_question_cvs' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'download_question_answers' ) );
		}

		if ( ! empty( $_GET['action'] ) && 'tqb_answers_csv' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'download_quiz_answers' ) );
		}

		if ( ! empty( $_GET['action'] ) && 'tqb_export_results' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'download_user_results' ) );
		}

		if ( ! empty( $_GET['action'] ) && 'tqb_download_export' === $_GET['action'] ) {
			add_action( 'admin_init', array( $this, 'download_export_file' ) );
		}

		/**
		 * Add Thrive Quiz Builder To Dashboard
		 */
		add_filter( 'tve_dash_admin_product_menu', array( $this, 'add_to_dashboard_menu' ) );

		/**
		 * Add admin scripts and styles
		 */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			add_action( 'tcb_ajax_load', array( $this, 'tqb_tcb_ajax_load' ) ); //applied from TCB plugin
		}

		add_filter( 'tie_load_admin_scripts', array( $this, 'allow_tie_scripts' ) );
	}

	/**
	 * Includes required files
	 */
	public function includes() {
		require_once __DIR__ . '/classes/class-tqb-export-manager.php';
		require_once __DIR__ . '/classes/class-tqb-import-manager.php';
		require_once 'tqb-admin-functions.php';
	}

	/**
	 * Push the Thrive Quiz Builder to Thrive Dashboard menu
	 *
	 * @param array $menus items already in Thrive Dashboard.
	 *
	 * @return array
	 */
	public function add_to_dashboard_menu( $menus = array() ) {

		$menus['tqb'] = array(
			'parent_slug' => 'tve_dash_section',
			'page_title'  => __( 'Thrive Quiz Builder', 'thrive-quiz-builder' ),
			'menu_title'  => __( 'Thrive Quiz Builder', 'thrive-quiz-builder' ),
			'capability'  => TQB_Product::cap(),
			'menu_slug'   => 'tqb_admin_dashboard',
			'function'    => array( $this, 'dashboard' ),
		);


		return $menus;
	}

	/**
	 * Enqueue all required scripts and styles
	 *
	 * @param string $hook page hook.
	 */
	public function enqueue_scripts( $hook ) {

		$accepted_hooks = apply_filters( 'tqb_accepted_admin_pages', array(
			'thrive-dashboard_page_tqb_admin_dashboard',
		) );

		if ( ! in_array( $hook, $accepted_hooks, true ) ) {
			return;
		}

		/* first, the license check */
		if ( ! tqb()->license_activated() ) {
			return;
		}

		/* second, the minimum required TCB version */
		if ( ! tqb()->check_tcb_version() ) {
			return;
		}

		/**
		 * Enqueue dash scripts
		 */
		tve_dash_enqueue();

		/**
		 * Specific admin styles
		 */
		tqb_enqueue_style( 'tqb-admin-style', tqb()->plugin_url( 'assets/css/admin/tqb-styles.css' ) );

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'backbone' );
		wp_enqueue_script( 'jquery-ui-sortable', false, array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-autocomplete', false, array( 'jquery' ) );

		/**
		 * Highcharts
		 */
		tve_dash_enqueue_script( 'tve-dash-highcharts', TVE_DASH_URL . '/js/util/highcharts/highcharts.js', array(
			'jquery',
		), false, false );
		tve_dash_enqueue_script( 'tve-dash-highcharts-more', TVE_DASH_URL . '/js/util/highcharts/highcharts-more.js', array(
			'jquery',
			'tve-dash-highcharts',
		), false, false );
		tve_dash_enqueue_script( 'tve-dash-highcharts-3d', TVE_DASH_URL . '/js/util/highcharts/highcharts-3d.js', array(
			'jquery',
			'tve-dash-highcharts',
		), false, false );

		tqb_enqueue_script( 'tqb-admin-js', tqb()->plugin_url( 'assets/js/dist/tqb-admin.min.js' ), array(
			'jquery',
			'backbone',
			'tve-dash-highcharts',
			'tve-dash-highcharts-more',
		), false, true );

		/**
		 * Enqueue Wystia script for popover videos
		 */

		wp_localize_script( 'tqb-admin-js', 'ThriveQuizB', tqb_get_localization() );

		/**
		 * Output the main templates for backbone views used in dashboard.
		 */
		add_action( 'admin_print_footer_scripts', array( $this, 'render_backbone_templates' ) );
	}

	/**
	 * Output Thrive Quiz Builder dashboard - the main plugin admin page
	 */
	public function dashboard() {

		if ( ! tqb()->license_activated() ) {
			return include tqb()->plugin_path( '/includes/admin/views/license-inactive.phtml' );
		}

		if ( ! tqb()->check_tcb_version() ) {
			return include tqb()->plugin_path( 'includes/admin/views/tcb_version_incompatible.phtml' );
		}

		include tqb()->plugin_path( '/includes/admin/views/dashboard.phtml' );
		include tqb()->plugin_path( 'assets/images/tqb-admin-svg-icons.svg' );

	}

	/**
	 * Render backbone templates
	 */
	public function render_backbone_templates() {
		$templates = tve_dash_get_backbone_templates( tqb()->plugin_path( 'includes/admin/views/templates' ), 'templates' );
		tve_dash_output_backbone_templates( $templates );
	}

	public function allow_tie_scripts( $screens ) {

		$screens[] = 'thrive-dashboard_page_tqb_admin_dashboard';

		return $screens;
	}

	/**
	 * Hook applied from TCB
	 * Used for loading a file through ajax call
	 * Used for displaying lightbox for choosing a template
	 *
	 * @param $file string
	 */
	public function tqb_tcb_ajax_load( $file ) {
		switch ( $file ) {
			case 'tqb_compute_result_page_states':
				include tqb()->plugin_path( 'tcb-bridge/editor-lightbox/result-intervals.php' );
				exit();
				break;
			case 'tqb_import_state_content':
				include tqb()->plugin_path( 'tcb-bridge/editor-lightbox/import-content.php' );
				exit();
				break;
			case 'tqb_social_share_badge_template':
				include tqb()->plugin_path( 'tcb-bridge/editor-lightbox/social-share-badge-template.php' );
				exit();
				break;
		}
	}

	/**
	 * Download quiz questions and answers as csv [Export results]
	 *
	 * @return bool
	 */
	public function download_quiz_answers() {

		$quiz_id = ! empty( $_GET['quiz_id'] ) ? sanitize_text_field( $_GET['quiz_id'] ) : null;

		if ( false === current_user_can( 'manage_options' ) || false === is_admin() || empty( $quiz_id ) ) {
			return false;
		}

		$nonce = ! empty( $_GET['tqb_answ_csv_nonce'] ) ? sanitize_text_field( $_GET['tqb_answ_csv_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tqb_answers_csv' ) ) {
			die( 'Security check error' );
		}
		ob_start();

		$filename = 'quiz_' . $quiz_id . '_questionsAndAnswers.csv';

		$reporting_manager = new TQB_Reporting_Manager( $quiz_id, 'questions' );
		$prepared_data     = $reporting_manager->get_full_csv_questions_report();

		self::download_csv( $filename, $prepared_data['body'], $prepared_data['headers'], true );
		ob_end_flush();
	}

	/**
	 * Download user results as CSV (synchronous direct download).
	 *
	 * This method handles the direct CSV download for quiz user results when the submission count
	 * is below the threshold for async processing (typically ≤5,000 submissions).
	 *
	 * Security: Verifies user capabilities, admin context, nonce, and quiz existence.
	 * Performance: Sets unlimited execution time to handle large exports.
	 *
	 * @since 3.x
	 *
	 * @return bool False if security checks fail or quiz doesn't exist.
	 */
	public function download_user_results() {

		if ( ! current_user_can( 'manage_options' ) || ! is_admin() ) {
			return false;
		}

		$quiz_id = ! empty( $_GET['quiz_id'] ) ? absint( $_GET['quiz_id'] ) : 0;

		if ( empty( $quiz_id ) ) {
			return false;
		}

		$nonce = ! empty( $_GET['tqb_export_nonce'] ) ? sanitize_text_field( $_GET['tqb_export_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tqb_export_results_' . $quiz_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'thrive-quiz-builder' ), '', array( 'response' => 403 ) );
		}

		// Validate quiz exists and is a valid quiz post
		$quiz_post = get_post( $quiz_id );
		if ( ! $quiz_post || TQB_Post_types::QUIZ_POST_TYPE !== $quiz_post->post_type ) {
			return false;
		}

		// Prevent timeout on large exports
		set_time_limit( 0 );

		// Build filename: quiz-{id}-results-{date}.csv
		$filename = 'quiz-' . $quiz_id . '-results-' . gmdate( 'Y-m-d' ) . '.csv';

		// Set CSV headers
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		// Open output stream
		$fh = @fopen( 'php://output', 'w' );

		if ( ! $fh ) {
			wp_die( esc_html__( 'Could not open output stream.', 'thrive-quiz-builder' ), '', array( 'response' => 500 ) );
		}

		// Generate CSV content (BOM is handled by export_user_results_csv)
		$reporting_manager = new TQB_Reporting_Manager( $quiz_id, 'results' );
		$row_count         = $reporting_manager->export_user_results_csv( $fh );

		// Close file handle
		fclose( $fh );

		// Fire audit hook for logging
		do_action( 'tqb_results_exported', array(
			'quiz_id'   => $quiz_id,
			'quiz_name' => $quiz_post->post_title,
			'user_id'   => get_current_user_id(),
			'row_count' => $row_count,
			'mode'      => 'sync',
		) );

		die();
	}

	public function download_question_answers() {

		if ( false === current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( false === is_admin() ) {
			return false;
		}

		$nonce = ! empty( $_GET['tqb_csv_nonce'] ) ? sanitize_text_field( $_GET['tqb_csv_nonce'] ) : '';

		if ( ! wp_verify_nonce( $nonce, 'tqb_question_cvs' ) ) {
			die( 'Security check error' );
		}

		$question_id = sanitize_text_field( ! empty( $_GET['question_id'] ) ? $_GET['question_id'] : null );

		if ( true === empty( $question_id ) ) {
			return false;
		}

		ob_start();

		$filename = 'question' . $question_id . '_answers.csv';

		$header_row = array(
			'Number',
			'Answer',
		);

		$data_rows = array();

		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT answer_text FROM ' . tqb_table_name( 'user_answers' ) . ' WHERE question_id = %d',
			$question_id
		);

		$answers = $wpdb->get_results( $sql, 'ARRAY_A' );

		foreach ( $answers as $key => $answer ) {
			$row         = array(
				$key + 1,
				$answer['answer_text'],
			);
			$data_rows[] = $row;
		}

		self::download_csv( $filename, $data_rows, $header_row );
		ob_end_flush();

		die();
	}

	/**
	 * @param       $filename
	 * @param       $data_rows
	 * @param array $header_row
	 * @param bool  $multidimensional   meaning that csv will be built from $data_rows[ 'col1' ] => array(
	 *                                  'col1_row1'
	 *                                  'col1_row2'
	 *                                  'col1_row3'
	 *                                  )
	 *
	 * @return bool
	 */
	public static function download_csv( $filename, $data_rows, $header_row = array(), $multidimensional = false ) {

		if ( empty( $filename ) || empty( $data_rows ) || ! is_array( $data_rows ) ) {

			return false;
		}

		$fh = @fopen( 'php://output', 'w' );

		fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Content-Description: File Transfer' );
		header( 'Content-type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Expires: 0' );
		header( 'Pragma: public' );

		if ( $multidimensional ) {

			// Grab cols(headers) from main array keys
			$heads = array_keys( $data_rows );
			if ( empty( $header_row ) ) {
				$header_row = $heads;
			}

			$maxs = array();

			foreach ( $heads as $head ) {
				$maxs[] = count( $data_rows[ $head ] );
			}

			fputcsv( $fh, $header_row );
			for ( $i = 0; $i < max( $maxs ); $i ++ ) {
				$row = array();
				foreach ( $heads as $head ) {
					$row[] = isset( $data_rows[ $head ][ $i ] ) ? $data_rows[ $head ][ $i ] : '';
				}
				fputcsv( $fh, $row );
			}
			fclose( $fh );
			die();
		}

		if ( ! empty( $header_row ) && is_array( $header_row ) ) {

			fputcsv( $fh, $header_row );
		}

		foreach ( $data_rows as $data_row ) {

			fputcsv( $fh, $data_row );
		}

		fclose( $fh );
		die();
	}

	/**
	 * Download async export file.
	 * Delegates to TQB_Results_Export_Manager.
	 */
	public function download_export_file() {
		$job_id = isset( $_GET['job_id'] ) ? sanitize_text_field( $_GET['job_id'] ) : '';
		$nonce  = isset( $_GET['nonce'] ) ? sanitize_text_field( $_GET['nonce'] ) : '';

		TQB_Results_Export_Manager::download_export_file( $job_id, $nonce );
	}
}

return new Thrive_Quiz_Builder_Admin();

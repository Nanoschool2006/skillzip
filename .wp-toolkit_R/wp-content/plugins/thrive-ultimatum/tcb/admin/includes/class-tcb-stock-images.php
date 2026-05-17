<?php

/**
 * Class TCB_Stock_Library
 *
 * Handles the stock image library functionality.
 */
class TCB_Stock_Library {

	/**
	 * TCB_Stock_Library constructor.
	 * Sets up action hooks.
	 */
	public function __construct() {
		// Use higher priority (5) to ensure Thrive loads before Optimole (default 10)
		add_action( 'wp_enqueue_media', array( $this, 'enqueue_scripts' ), 5 );
		add_action( 'wp_ajax_unsplash_list', array( $this, 'unsplash_list_callback' ) );
		add_action( 'wp_ajax_unsplash_download_image', array( $this, 'unsplash_download_image_callback' ) );
		add_action( 'wp_ajax_nopriv_unsplash_download_image', array( $this, 'unsplash_download_image_callback' ) );
	}

	/**
	 * Enqueue necessary scripts and styles for the stock image library.
	 */
	public function enqueue_scripts() {
		// Only block Optimole when we're in the TAR editor context
		if ( $this->is_tar_editor_context() ) {
			$this->block_optimole_media_script();
		}

		$js_suffix = TCB_Utils::get_js_suffix();
		tve_enqueue_script( 'unsplash_media_tab_js', tcb_admin()->admin_url( 'assets/js/stock-library' . $js_suffix), array( 'jquery' ), TVE_VERSION, false );
		tve_enqueue_style( 'tcb-admin-stock-images', tcb_admin()->admin_url( 'assets/css/tcb-admin-stock-images.css' ), array(), TVE_VERSION, false );

		$nonce = wp_create_nonce( 'unsplash_api_nonce' );
		
		wp_localize_script(
			'unsplash_media_tab_js',
			'unsplashApi',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => $nonce,
			)
		);
	}

	/**
	 * Check if we're in the TAR (Thrive Architect) editor context.
	 * 
	 * @return bool True if we're in the TAR editor context, false otherwise.
	 */
	private function is_tar_editor_context() {
		// Check for the main TAR editor flag (covers most cases)
		if ( defined( 'TVE_EDITOR_FLAG' ) && ! empty( $_GET[ TVE_EDITOR_FLAG ] ) ) {
			return true;
		}
		
		// Check for TAR editor page parameter (covers AJAX requests)
		if ( ! empty( $_REQUEST['tar_editor_page'] ) ) {
			return true;
		}
		
		return false;
	}

	/**
	 * Block Optimole's media modal script to prevent conflicts with Thrive's stock library.
	 */
	private function block_optimole_media_script() {
		// Check if Optimole is active and has the DAM class
		if ( ! class_exists( 'Optml_Dam' ) ) {
			return;
		}

		// Get the Optimole DAM instance
		$optml_dam_instance = null;
		if ( class_exists( 'Optml_Main' ) && method_exists( 'Optml_Main', 'instance' ) ) {
			$main_instance = Optml_Main::instance();
			if ( isset( $main_instance->dam ) ) {
				$optml_dam_instance = $main_instance->dam;
			}
		}

		// Remove Optimole's media script hooks
		if ( $optml_dam_instance ) {
			// Remove the enqueue_media_scripts action
			remove_action( 'wp_enqueue_media', array( $optml_dam_instance, 'enqueue_media_scripts' ), 10 );
			
			// Remove print_media_templates action as well
			remove_action( 'print_media_templates', array( $optml_dam_instance, 'print_media_template' ), 10 );
		}

		// Also check for any already enqueued scripts and remove them
		static $action_registered = false;
		if ( ! $action_registered ) {
			add_action( 'wp_print_scripts', array( $this, 'dequeue_optimole_scripts' ), 999 );
			$action_registered = true;
		}
	}

	/**
	 * Dequeue Optimole media scripts if they were already enqueued.
	 */
	public function dequeue_optimole_scripts() {
		wp_dequeue_script( 'optml-media-modal' );
		wp_deregister_script( 'optml-media-modal' );
		wp_dequeue_style( 'optml-media-modal' );
		wp_deregister_style( 'optml-media-modal' );
	}

	/**
	 * Handle AJAX request to fetch a list of images from Unsplash.
	 */
	public function unsplash_list_callback() {
		check_ajax_referer( 'unsplash_api_nonce', 'nonce' );

		$count               = min( isset( $_POST['count'] ) ? intval( $_POST['count'] ) : 10, 30 );
		$page                = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
		$search              = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$allowed_order_by    = array( 'relevant', 'latest', 'oldest' );
		$allowed_orientation = array( 'landscape', 'portrait', 'squarish' );
		$order_by            = isset( $_POST['order_by'] ) && in_array( wp_unslash( $_POST['order_by'] ), $allowed_order_by ) ? sanitize_text_field( wp_unslash( $_POST['order_by'] ) ) : 'relevant';
		$orientation         = isset( $_POST['orientation'] ) && in_array( wp_unslash( $_POST['orientation'] ), $allowed_orientation ) ? sanitize_text_field( wp_unslash( $_POST['orientation'] ) ) : '';

		$api_url = 'https://service-api.thrivethemes.com/api/unsplash/unsplash_service.php?action=list&page=' . $page . '&count=' . $count . '&order_by=' . rawurlencode( $order_by );
		if ( ! empty( $search ) ) {
			$api_url .= '&keyword=' . rawurlencode( $search ) . '&orientation=' . rawurlencode( $orientation );
		}

		$response = wp_remote_get( $api_url );
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error connecting to the Unsplash API' );
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( is_null( $data ) || ! isset( $data['results'] ) ) {
			wp_send_json_error( 'Invalid JSON response from Unsplash service' );
			return;
		}

		wp_send_json_success(
			array(
				'total'       => $data['total'],
				'total_pages' => $data['total_pages'],
				'results'     => $data['results'],
			)
		);
	}

	/**
	 * Handle AJAX request to download an image from Unsplash.
	 */
	public function unsplash_download_image_callback() {
		check_ajax_referer( 'unsplash_api_nonce', 'nonce' );

		$photo_id = isset( $_POST['photo_id'] ) ? sanitize_text_field( wp_unslash( $_POST['photo_id'] ) ) : '';
		if ( empty( $photo_id ) ) {
			wp_send_json_error( 'No photo ID provided' );
			return;
		}

		$photo_size       = 'full';
		$download_success = false;

		$unsplash_api_url = 'https://service-api.thrivethemes.com/api/unsplash/unsplash_service.php?action=download&photo_id=' . urlencode( $photo_id ) . '&photo_size=' . $photo_size;
		$response         = wp_remote_get( $unsplash_api_url );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'Error retrieving image information from Unsplash service.' );
			return;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $data['download_url'] ) ) {
			wp_send_json_error( 'Missing download URL in Unsplash service response.' );
			return;
		}

		$download_url = esc_url_raw( $data['download_url'] );

		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$alt_text    = isset( $_POST['alt_text'] ) ? sanitize_text_field( wp_unslash( $_POST['alt_text'] ) ) : ( isset( $data['alt_description'] ) ? sanitize_text_field( $data['alt_description'] ) : '' );
		$caption     = isset( $_POST['caption'] ) ? sanitize_text_field( wp_unslash( $_POST['caption'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
		$filename    = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : ( isset( $photo_id ) ? sanitize_text_field( $photo_id ) : '' );

		$image_response = wp_remote_get( $download_url );
		if ( is_wp_error( $image_response ) ) {
			wp_send_json_error( 'Error downloading image from Unsplash.' );
			return;
		}

		$image_body = wp_remote_retrieve_body( $image_response );
		$mime_type  = wp_remote_retrieve_header( $image_response, 'content-type' );
		if ( ! $image_body ) {
			wp_send_json_error( 'Error reading image content from Unsplash.' );
			return;
		}

		$file_extension_map = array(
			'image/jpeg' => '.jpg',
			'image/png'  => '.png',
			'image/gif'  => '.gif',
		);

		if ( ! isset( $file_extension_map[ $mime_type ] ) ) {
			wp_send_json_error( 'Unsupported image type: ' . $mime_type );
			return;
		}

		$file_extension = $file_extension_map[ $mime_type ];
		$filename       = sanitize_file_name( $filename . $file_extension );
		$upload_dir     = wp_upload_dir();
		$file_path      = $upload_dir['path'] . '/' . $filename;

		if ( file_put_contents( $file_path, $image_body ) !== false ) {
			$download_success = true;
		}

		if ( ! $download_success ) {
			wp_send_json_error( 'Error saving image to the uploads directory.' );
			return;
		}

		$attachment = array(
			'guid'           => $upload_dir['url'] . '/' . basename( $file_path ),
			'post_mime_type' => $mime_type,
			'post_title'     => $title,
			'post_content'   => $description,
			'post_status'    => 'inherit',
			'post_excerpt'   => $caption,
		);

		$attach_id = wp_insert_attachment( $attachment, $file_path );
		if ( file_exists( ABSPATH . 'wp-admin/includes/image.php' ) ) {
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			if ( ! $attach_data ) {
				wp_send_json_error( 'Error generating attachment metadata.' );
				return;
			}
		} else {
			if ( ! wp_update_attachment_metadata( $attach_id, $attach_data ) ) {
				wp_send_json_error( 'Error updating attachment metadata.' );
				return;
			}
			return;
		}
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		update_post_meta( $attach_id, '_wp_attachment_image_alt', $alt_text );

		wp_send_json_success(
			array(
				'message'       => 'Image downloaded, added to the media library, and added to Thrive Architect',
				'attachment_id' => $attach_id,
			)
		);
	}
}

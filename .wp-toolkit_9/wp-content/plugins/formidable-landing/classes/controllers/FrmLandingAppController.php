<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class FrmLandingAppController {

	private static $post_type = 'frm_landing_page';

	/**
	 * @return string
	 */
	public static function get_landing_page_post_type() {
		return self::$post_type;
	}

	public static function load_hooks() {
		// The load_hooks function is called on plugins_loaded so call load_lang without a hook.
		self::load_lang();

		// actions
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 0 );
		add_action( 'admin_init', array( __CLASS__, 'include_updater' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'parse_request' ), 99 );
		add_action( 'frm_update_form', 'FrmLandingAppHelper::sync_landing_page', 11, 2 );
		add_action( 'wp_ajax_frm_validate_landing_page_url', 'FrmLandingSettingsController::validate_landing_page_url' );

		// filters
		add_filter( 'template_include', array( __CLASS__, 'maybe_template_include' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'remove_slug' ), 10, 2 );
		add_filter( 'frm_form_list_actions', array( __CLASS__, 'maybe_add_landing_page_action' ), 10, 2 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'get_canonical_url' ), 10, 2 );
		add_filter( 'frm_abandonment_edit_entry_url', array( __CLASS__, 'update_edit_entry_url' ), 10, 2 );

		if ( is_admin() ) {
			self::load_admin_hooks();
		}
	}

	/**
	 * Loads admin hooks.
	 *
	 * @since 1.0.02
	 *
	 * @return void
	 */
	private static function load_admin_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * Functions for admin_init hook.
	 *
	 * @since 1.0.02
	 *
	 * @return void
	 */
	public static function admin_init() {
		self::maybe_delete_landing_page_post();
	}

	/**
	 * Returns true if the delete landing page button was clicked.
	 *
	 * @since 1.0.02
	 *
	 * @return bool
	 */
	private static function should_delete_landing_page() {
		if ( ! FrmAppHelper::is_admin_page( 'formidable' ) ) {
			return false;
		}
		if ( FrmAppHelper::get_param( 'frm_action', '', 'get', 'sanitize_title' ) !== 'settings' ) {
			return false;
		}
		return FrmAppHelper::get_param( 'delete_landing_page', 0, 'get', 'absint' ) === 1;
	}

	/**
	 * Deletes landing page post when the button for that is clicked.
	 *
	 * @since 1.0.02
	 *
	 * @return void
	 */
	private static function maybe_delete_landing_page_post() {
		if ( ! self::should_delete_landing_page() ) {
			return;
		}

		if ( ! current_user_can( 'delete_posts' ) ) {
			$frm_settings = FrmAppHelper::get_settings();
			wp_die( esc_html( $frm_settings->admin_permission ) );
		}

		FrmAppHelper::permission_check( 'frm_change_settings' );
		$nonce = FrmAppHelper::simple_get( '_wpnonce' );

		if ( ! wp_verify_nonce( $nonce, 'frm_delete_landing_nonce' ) ) {
			$frm_settings = FrmAppHelper::get_settings();
			wp_die( esc_html( $frm_settings->admin_permission ) );
		}

		$form_id = FrmAppHelper::get_param( 'id', 0, 'absint' );
		if ( ! $form_id ) {
			return;
		}

		$landing_page_post_id = FrmLandingAppHelper::get_landing_page_post_id( $form_id );
		if ( ! $landing_page_post_id ) {
			return;
		}
		wp_delete_post( $landing_page_post_id );

		$url = admin_url( 'admin.php?page=formidable&frm_action=settings&id=' . $form_id );

		wp_safe_redirect( $url );
	}

	/**
	 * Updates the url used for entry recovery link.
	 *
	 * @since 1.0.02
	 *
	 * @param string $base_url
	 * @param array  $atts
	 *
	 * @return string
	 */
	public static function update_edit_entry_url( $base_url, $atts ) {
		if ( empty( $atts['id'] ) ) {
			return $base_url;
		}
		$entry_id = (int) $atts['id'];

		$form_id           = FrmProEntriesHelper::get_field( 'form_id', $entry_id );
		$landing_post_name = FrmLandingSettingsController::get_landing_page_post_name( $form_id );
		if ( $landing_post_name ) {
			return home_url() . '/' . $landing_post_name;
		}
		return $base_url;
	}

	/**
	 * @since 1.0.02
	 *
	 * @return void
	 */
	private static function load_lang() {
		load_plugin_textdomain( 'formidable-landing', false, basename( FrmLandingAppHelper::path() ) . '/languages/' );
	}

	public static function maybe_template_include( $template ) {
		$post = get_post();
		if ( ! $post ) {
			return $template;
		}

		$loading_frm_landing_page = $post instanceof WP_Post && self::$post_type === $post->post_type;

		if ( ! $loading_frm_landing_page ) {
			return $template;
		}

		return self::template_include();
	}

	private static function template_include() {
		add_action( 'wp_enqueue_scripts', 'FrmLandingAppController::enqueue_assets' );

		return FrmLandingAppHelper::path() . '/views/template.php';
	}

	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			include FrmLandingAppHelper::path() . '/classes/models/FrmLandingUpdate.php';
			FrmLandingUpdate::load_hooks();
		}
	}

	public static function register_post_types() {
		register_post_type(
			self::$post_type,
			array(
				'label'               => __( 'Landing Pages', 'formidable-landing' ),
				'description'         => '',
				'public'              => true,
				'publicly_queryable'  => false,
				'show_ui'             => true,
				'exclude_from_search' => true,
				'show_in_nav_menus'   => false,
				'show_in_menu'        => false,
				'hierarchical'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'capability_type'     => 'page',
				'capabilities'        => array(
					'edit_post'         => 'frm_change_settings',
					'edit_posts'        => 'frm_change_settings',
					'edit_others_posts' => 'frm_change_settings',
					'publish_posts'     => 'frm_change_settings',
					'delete_post'       => 'frm_change_settings',
					'delete_posts'      => 'frm_change_settings',
					'read_post'         => 'frm_change_settings',
				),
				'show_in_rest'       => true,
				'supports'           => array( 'editor' ),
				'has_archive'        => false,
				'labels'             => array(
					'name'          => __( 'Landing Pages', 'formidable-landing' ),
					'singular_name' => __( 'Landing Page', 'formidable-landing' ),
					'menu_name'     => __( 'Landing Page', 'formidable-landing' ),
					'edit'          => __( 'Edit', 'formidable-landing' ),
					'search_items'  => __( 'Search Landing Pages', 'formidable-landing' ),
					'not_found'     => __( 'No Landing Pages Found.', 'formidable-landing' ),
					'add_new_item'  => __( 'Add New Landing Page', 'formidable-landing' ),
					'edit_item'     => __( 'Edit Landing Page', 'formidable-landing' ),
				),
			)
		);
	}

	/**
	 * Getting a custom post type to behave like a page is a pain in the butt and requires the two functions underneath to work at all.
	 *
	 * See the conversation on stack exchange for more information.
	 * https://wordpress.stackexchange.com/questions/291735/remove-slug-from-custom-post-type-results-in-404/292379#292379
	 *
	 * @param string  $post_link
	 * @param WP_Post $post
	 * @return string
	 */
	public static function remove_slug( $post_link, $post ) {
		if ( self::$post_type !== $post->post_type || 'publish' !== $post->post_status ) {
			return $post_link;
		}
		return str_replace( '/' . self::$post_type . '/', '/', $post_link );
	}

	/**
	 * @param WP_Query $query
	 * @return void
	 */
	public static function parse_request( $query ) {
		if ( ! $query->is_main_query() || ! isset( $query->query['page'] ) || is_home() ) {
			return;
		}

		$has_name      = ! empty( $query->query['name'] );
		$has_page_name = ! empty( $query->query['pagename'] ) && false === strpos( $query->query['pagename'], '/' );

		if ( ! $has_name && ! $has_page_name ) {
			return;
		}

		$query_post_type = $query->get( 'post_type' );
		if ( $query_post_type && is_string( $query_post_type ) && ! in_array( $query_post_type, array( 'post', 'page', self::$post_type ), true ) ) {
			$object = get_post_type_object( $query_post_type );
			if ( $object instanceof WP_Post_Type && is_array( $object->rewrite ) && ! empty( $object->rewrite['slug'] ) ) {
				// Ignore post types with a slug set on post type registration.
				// This fixes a conflict with LearnDash (issue #12).
				return;
			}
		}

		$post_types = self::no_slug_types( $query );
		$query->set( 'post_type', $post_types );

		if ( ! $has_name && $has_page_name ) {
			// We also need to set the name query var since redirect_guess_404_permalink() relies on it.
			$query->set( 'name', $query->query['pagename'] );
		}
	}

	/**
	 * Check if another plugin is adding post types to the base urls so we don't
	 * override it.
	 *
	 * @param object $query The WP Query class.
	 *
	 * @return array
	 */
	private static function no_slug_types( $query ) {
		$post_types = $query->get( 'post_type' );
		if ( empty( $post_types ) ) {
			$post_types = array( 'post', 'page' );
		} else {
			$post_types = (array) $post_types;
		}
		$post_types[] = self::$post_type;
		return $post_types;
	}

	public static function enqueue_assets() {
		$version = FrmLandingAppHelper::plugin_version();

		wp_enqueue_style( 'frm-landing', FrmLandingAppHelper::plugin_url() . '/css/landing.css', array(), $version );
	}

	/**
	 * Loaded from the template file when the style includes an image.
	 * This is used to move the image from the form to the body.
	 *
	 * @param array $classes The classes for the body tag.
	 *
	 * @return array
	 */
	public static function add_image_body_class( $classes ) {
		$form_id   = FrmLandingAppHelper::get_landing_page_form_id( get_the_ID() );
		$layout    = FrmLandingAppHelper::get_landing_page_layout( $form_id );
		$classes[] = 'frm_image_' . esc_attr( $layout );
		if ( in_array( $layout, array( 'block', 'left', 'right' ) ) ) {
			$classes[] = 'frm_with_color_block';
		}
		$classes   = apply_filters( 'frm_landing_body_class', $classes, compact( 'form_id' ) );

		return $classes;
	}

	/**
	 * @since 1.0.01
	 *
	 * @param string $actions
	 * @param array  $args inclues key 'form' with stdClass value.
	 * @return string
	 */
	public static function maybe_add_landing_page_action( $actions, $args ) {
		$form = $args['form'];
		if ( ! empty( $form->options['landing_page_id'] ) ) {
			$actions .= self::get_landing_page_icon( $form );
		}
		return $actions;
	}

	/**
	 * @since 1.0.01
	 *
	 * @param stdClass $form
	 * @return string
	 */
	private static function get_landing_page_icon( $form ) {
		$landing_page_post_name = FrmLandingSettingsController::get_landing_page_post_name( $form->id );

		if ( ! $landing_page_post_name ) {
			return '';
		}

		$url  = home_url() . '/' . $landing_page_post_name;
		$icon = FrmAppHelper::icon_by_class( 'frmfont frm_file_text_icon', array( 'echo' => false ) );

		return '&nbsp;&nbsp;<a role="button" aria-label="' . esc_attr__( 'Open Landing Page', 'formidable-landing' ) . '" href="' . esc_url_raw( $url ) . '">' . $icon . '</a>';
	}

	/**
	 * Force the canonical URL for landing pages so that landing pages can be properly scraped by Facebook.
	 *
	 * @param string  $canonical_url The post's canonical URL.
	 * @param WP_Post $post          Post object.
	 * @return string
	 */
	public static function get_canonical_url( $canonical_url, $post ) {
		if ( self::$post_type === $post->post_type && 'publish' === $post->post_status ) {
			return home_url() . '/' . $post->post_name;
		}
		return $canonical_url;
	}
}

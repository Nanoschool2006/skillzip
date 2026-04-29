<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * This contains various functionality that addresses conflicts or incompatibilities with 3rd party products
 *
 * @package thrive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Filters to keep theme scripts and styles when in a CartFlow page
 */
add_filter( 'cartflows_remove_theme_scripts', 'thrive_theme_cartflows_keep_assets' );
add_filter( 'cartflows_remove_theme_styles', 'thrive_theme_cartflows_keep_assets' );

/**
 * CartFlows plugin removes ALL styles and scripts from the theme,
 * unless the current theme is not one of Divi or Flatsome ( it seems they hardcoded these )
 *
 * Fortunately, this can be controlled with a filter
 *
 * @param boolean $to_remove
 *
 * @return false
 */
function thrive_theme_cartflows_keep_assets( $to_remove ) {
	/* keep theme styles or scripts on editor page */
	if ( is_editor_page_raw() ) {
		$to_remove = false;
	}

	return $to_remove;
}

/**
 * Integrate CartFlows pages with landing pages and top / bottom sections
 */
add_filter( 'thrive_body_class', static function ( $class, $post ) {
	if ( $post->get( 'post_type' ) === 'cartflows_step' ) {
		$class = '.postid-' . $post->ID;
	}

	return $class;
}, 10, 2 );


/**
 * In some cases we're rendering the template earlier and the Smart Slider 3 Plugin disables his slider shortcode.
 * We overwrite the shortcode and set it to normal mode so it can be rendered.
 */
if ( class_exists( N2SS3Shortcode::class, false ) ) {
	add_action( 'before_theme_builder_template_render', [ N2SS3Shortcode::class, 'shortcodeModeToNormal' ] );
}

/**
 * Compatibility with eLearnCommerce plugin - remove their template_include hooks so we can display our own templates.
 */
if ( class_exists( WPEP\Controller::class, false ) ) {
	add_action( 'wp', static function () {
		if ( has_action( 'template_include', [ WPEP\Controller::instance()->template, 'load' ] ) ) {
			if ( get_post_type() === 'courses' ) {
				add_action( 'wpep_before_main_content', static function () {
					echo '<div id="wrapper">' . thrive_template()->render_theme_hf_section( THRIVE_HEADER_SECTION ) . '<div id="content"><div class="main-container thrv_wrapper">';
				}, 1 );

				add_action( 'wpep_after_main_content', static function () {
					echo '</div></div>' . thrive_template()->render_theme_hf_section( THRIVE_FOOTER_SECTION ) . '</div>';
				}, PHP_INT_MAX );

				add_filter( 'thrive_theme_do_the_post', '__return_false' );
			} else {
				remove_action( 'template_include', [ WPEP\Controller::instance()->template, 'load' ], 50 );
			}
		}
	} );
}

/**
 * Compatibility with MEC - Modern Events Calendar plugin - add programmatically the header and footer based on their actions
 */

if ( class_exists( MEC::class, false ) ) {
	add_action( 'get_header', static function ( $name ) {
		if ( $name === 'mec' ) {
			add_action( 'mec_after_main_content', static function () {
				echo '</div></div>' . thrive_template()->render_theme_hf_section( THRIVE_FOOTER_SECTION ) . '</div>';
			}, PHP_INT_MAX );

			echo '<div id="wrapper">' . thrive_template()->render_theme_hf_section( THRIVE_HEADER_SECTION ) . '<div id="content"><div class="main-container">';
		}
	} );
}

/**
 * Compatibility with relevanssi - when we render the blog list we need to let the plugin to do his search
 */
if ( function_exists( 'relevanssi_query' ) ) {
	add_action( 'theme_before_render_blog_list', static function () {
		global $relevanssi_active;
		$relevanssi_active = false;
	} );
}

/**
 * Compatibility with Optimole WP - image optimization plugin.
 * This makes sure the CSS style file is being generated with all the CSS background image URLs replaced with their optimole CDN equivalents
 */
if ( class_exists( 'Optml_Main', false ) ) {
	add_filter( 'thrive_css_file_content', static function ( $style ) {
		/* only replace if current request is a rest api ajax request ... */
		$should_replace_urls = ! empty( $_REQUEST['tar_editor_page'] ) && defined( 'REST_REQUEST' ) && REST_REQUEST;
		/* ... and it's the one that saves a Theme template  */
		$should_replace_urls = $should_replace_urls && ! empty( $_REQUEST['action'] ) && $_REQUEST['action'] === 'update_template';

		/* first, check to see if optimole is configured / connected */
		if ( $should_replace_urls && thrive_optimole_wp()->is_registered() ) {
			/* setup everything necessary. optimole does not allow force-registering their hooks on a custom request because of an incorrectly coded filter */
			do_action( 'optml_replacer_setup' );

			$style = Optml_Main::instance()->manager->process_urls_from_content( $style );
		}

		return $style;
	} );
}

/**
 * Compatibility with MemberPress
 * Their login page redirects to a list page, so we don't want that to be loaded inside our templates
 */
if ( class_exists( 'MeprOptions', false ) && method_exists( 'MeprOptions', 'fetch' ) ) {
	$mepr_options = MeprOptions::fetch();
	if ( ! empty( $mepr_options->login_page_id ) ) {
		add_filter( 'thrive_theme_get_posts_args', static function ( $args ) use ( $mepr_options ) {
			$args['exclude'][] = $mepr_options->login_page_id;

			return $args;
		} );
	}
}
add_filter( 'pre_site_option_loginpress_review_dismiss', static function ( $value ) {
	/* If we are in the theme dashbaord dismiss the loginpress review */
	if ( Thrive_Utils::in_theme_dashboard() ) {
		$value = true;
	}

	return $value;
} );

/**
 * Compatibility with MyBookTable - don't enter 'pre_get_posts' when MBT archives are detected.
 */
add_filter( 'thrive_theme_should_filter_pre_get_posts', static function ( $should_filter, $query ) {
	if (
		$query->is_post_type_archive( 'mbt_book' ) ||
		(
			! empty( $query->queried_object ) &&
			property_exists( $query->queried_object, 'taxonomy' ) &&
			in_array( $query->queried_object->taxonomy, [ 'mbt_author', 'mbt_genre', 'mbt_tag' ], true )
		)
	) {
		$should_filter = false;
	}

	return $should_filter;
}, 10, 2 );

add_filter( 'thrive_theme_ignore_post_types', static function ( $post_types ) {

	/* Remove some memberpress custom post types for which there is no use case to create theme templates */
	$post_types[] = 'memberpressproduct';
	$post_types[] = 'memberpressgroup';

	return $post_types;
} );

/**
 * Compatibility with WishList Member plugin
 * They have a page which should not be used in the iframe from the page template
 */
global $WishListMemberInstance;
if ( ! empty( $WishListMemberInstance ) && is_object( $WishListMemberInstance ) && method_exists( $WishListMemberInstance, 'MagicPage' ) ) {
	$page_id = $WishListMemberInstance->MagicPage( false );

	if ( ! empty( $page_id ) ) {
		add_filter( 'thrive_theme_get_posts_args', static function ( $args ) use ( $page_id ) {
			$args['exclude'][] = $page_id;

			return $args;
		} );
	}
}

/**
 * Compatibility with Google SiteKit - Google Tag Manager
 *
 * Ensures GTM scripts are properly output on Thrive landing pages.
 * SiteKit hooks into wp_head/wp_body_open which work fine on regular pages,
 * but landing pages use custom hooks that SiteKit doesn't know about.
 *
 * This fix ONLY outputs GTM on landing pages where SiteKit's hooks don't fire.
 * Regular pages are handled by SiteKit natively.
 */
if ( defined( 'GOOGLESITEKIT_PLUGIN_MAIN_FILE' ) ) {

	/**
	 * Get Google Tag Manager container ID from SiteKit settings
	 *
	 * @return string|false Container ID or false if not configured/disabled
	 */
	function thrive_get_sitekit_gtm_container_id() {
		$settings = get_option( 'googlesitekit_tagmanager_settings' );

		if ( empty( $settings ) || ! is_array( $settings ) ) {
			return false;
		}

		// Respect user's choice to disable SiteKit snippet output
		if ( isset( $settings['useSnippet'] ) && $settings['useSnippet'] === false ) {
			return false;
		}

		return ! empty( $settings['containerID'] ) ? $settings['containerID'] : false;
	}

	/**
	 * Output GTM head script
	 *
	 * @param string $container_id GTM container ID
	 */
	function thrive_output_gtm_head_script( $container_id ) {
		if ( empty( $container_id ) ) {
			return;
		}

		// Validate container ID format (GTM-XXXXXXX) - allows uppercase/lowercase letters and numbers
		if ( ! preg_match( '/^GTM-[A-Za-z0-9]+$/', $container_id ) ) {
			return;
		}

		?>
<!-- Google Tag Manager (Thrive Compatibility) -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $container_id ); ?>');</script>
<!-- End Google Tag Manager -->
		<?php
	}

	/**
	 * Output GTM noscript tag
	 *
	 * @param string $container_id GTM container ID
	 */
	function thrive_output_gtm_noscript( $container_id ) {
		if ( empty( $container_id ) ) {
			return;
		}

		// Validate container ID format - allows uppercase/lowercase letters and numbers
		if ( ! preg_match( '/^GTM-[A-Za-z0-9]+$/', $container_id ) ) {
			return;
		}

		?>
<!-- Google Tag Manager (noscript) (Thrive Compatibility) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $container_id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
		<?php
	}

	/**
	 * Ensure GTM head script is output on landing pages (with duplicate prevention)
	 */
	function thrive_ensure_sitekit_gtm_head() {
		static $already_output = false;

		if ( $already_output ) {
			return;
		}

		// Skip in editor context
		if ( function_exists( 'is_editor_page_raw' ) && is_editor_page_raw() ) {
			return;
		}

		$container_id = thrive_get_sitekit_gtm_container_id();

		if ( empty( $container_id ) ) {
			return;
		}

		thrive_output_gtm_head_script( $container_id );
		$already_output = true;
	}

	/**
	 * Ensure GTM noscript is output on landing pages (with duplicate prevention)
	 */
	function thrive_ensure_sitekit_gtm_body_open() {
		static $already_output = false;

		if ( $already_output ) {
			return;
		}

		// Skip in editor context
		if ( function_exists( 'is_editor_page_raw' ) && is_editor_page_raw() ) {
			return;
		}

		$container_id = thrive_get_sitekit_gtm_container_id();

		if ( empty( $container_id ) ) {
			return;
		}

		thrive_output_gtm_noscript( $container_id );
		$already_output = true;
	}

	// Landing pages only - output GTM script in landing page head
	// Regular pages are handled by SiteKit's native wp_head hook
	add_action( 'tcb_landing_head_frontend', static function () {
		thrive_ensure_sitekit_gtm_head();
	}, 1 );

	// Landing pages only - output noscript after landing page body opens
	// Regular pages are handled by SiteKit's native wp_body_open hook
	add_action( 'tcb_landing_body_open_frontend', static function () {
		thrive_ensure_sitekit_gtm_body_open();
	}, 1 );
}

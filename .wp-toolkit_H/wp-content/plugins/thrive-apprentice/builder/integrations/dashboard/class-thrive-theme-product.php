<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * Class Thrive_Theme_Product
 */
class Thrive_Theme_Product extends TVE_Dash_Product_Abstract {

	const TAG = 'ttb';
	
	/**
	 * WordPress capability needed to edit theme options
	 */
	const THEME_OPTIONS_CAP = 'edit_theme_options';

	protected $tag = 'ttb';

	protected $version = THEME_VERSION;

	protected $slug = 'thrive-theme';

	protected $title = 'Thrive Theme';

	protected $productIds = [];

	protected $type = 'theme';

	protected $needs_architect = true;

	/**
	 * Checking if the default capabilities were set for the theme
	 */
	public function check_default_cap() {
		$admin  = get_role( 'administrator' );
		$option = $this->tag . '_def_caps_set';

		if ( ! $admin ) {
			return;
		}

		if ( ! get_option( $option ) && $admin->has_cap( $this->get_cap() ) ) {
			update_option( $option, true );

			return;
		}

		/**
		 * In some weird instances, either the update_option call from above fails, or the add_cap() fails the first time it's called.
		 * With these 2 if()s we are ensuring that the cap is set correctly each time, even if one of the two function calls fails once
		 * For now, admin will have the TTB cap no matter what.
		 */
		if ( ! get_option( $option ) || ! $admin->has_cap( $this->get_cap() ) ) {
			$admin->add_cap( $this->get_cap() );
		}
		
		/**
		 * Sync the edit_theme_options capability with tve-use-ttb capability for all roles
		 * This ensures users with Thrive Theme access can also access theme options,
		 * but we track which roles we've modified to avoid interfering with other plugins.
		 */
		global $wp_roles;
		
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
		
		// Get our tracking option - which roles received edit_theme_options from us
		$ttb_theme_options_roles = get_option( 'ttb_added_theme_options_to_roles', array() );
		
		// Initialize as array if it's not already
		if ( ! is_array( $ttb_theme_options_roles ) ) {
			$ttb_theme_options_roles = array();
		}
		
		// Loop through all roles using the role slugs
		foreach ( array_keys( $wp_roles->roles ) as $role_slug ) {
			// Skip administrators as they already have all capabilities
			if ( $role_slug === 'administrator' ) {
				continue;
			}
			
			$role = $wp_roles->get_role( $role_slug );
			
			if ( $role && $role->has_cap( $this->get_cap() ) ) {
				// Add the capability if the role has Thrive Theme access
				$role->add_cap( self::THEME_OPTIONS_CAP );
				
				// Track that we added this capability to this role
				if ( ! in_array( $role_slug, $ttb_theme_options_roles ) ) {
					$ttb_theme_options_roles[] = $role_slug;
				}
			} else if ( $role ) {
				// Only remove the capability if we previously added it
				if ( in_array( $role_slug, $ttb_theme_options_roles ) ) {
					$role->remove_cap( self::THEME_OPTIONS_CAP );
					
					// Remove from our tracking array
					$key = array_search( $role_slug, $ttb_theme_options_roles );
					if ( false !== $key ) {
						unset( $ttb_theme_options_roles[ $key ] );
					}
				}
			}
		}
		
		// Update our tracking option
		update_option( 'ttb_added_theme_options_to_roles', $ttb_theme_options_roles );
	}

	public function __construct( $data = [] ) {
		parent::__construct( $data );

		$this->logoUrl      = THEME_URL . '/inc/assets/images/theme-logo.png';
		$this->logoUrlWhite = THEME_URL . '/inc/assets/images/theme-logo-white.png';

		$this->description = __( 'Fully customizable, front end theme and template editing for WordPress has arrived!', 'thrive-theme' );

		$this->button = [
			'label'  => __( 'Theme Options', 'thrive' ),
			'url'    => admin_url( 'admin.php?page=' . THRIVE_MENU_SLUG . '&tab=w#wizard' ),
			'active' => true,
		];

		$this->moreLinks = [
			'tutorials' => [
				'class'      => 'tve-theme-tutorials',
				'icon_class' => 'tvd-icon-graduation-cap',
				'href'       => 'https://thrivethemes.com/thrive-theme-builder-tutorials-2/',
				'target'     => '_blank',
				'text'       => __( 'Tutorials', 'thrive' ),
			],
			'support'   => [
				'class'      => 'tve-theme-tutorials',
				'icon_class' => 'tvd-icon-life-bouy',
				'href'       => 'https://thrivethemes.com/support/',
				'target'     => '_blank',
				'text'       => __( 'Support', 'thrive' ),
			],
		];
	}
}

<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if (! defined('ABSPATH') ) {
    exit; // Silence is golden!
}

/**
 * Load the header template. This is the simplest way to include the header template
 * as the dashboard is a part of both themes and plugins.
 */
require_once TVE_DASH_PATH . '/templates/header.phtml';

$dashboard_link = admin_url('admin.php?page=tve_dash_section');
?>

<div class="tvd-content flow">
    <!-- Breadcrumbs -->
    <div class="tvd-breadcrumbs">
        <a class="tvd-breadcrumb" href="<?php echo esc_url( $dashboard_link ); ?>">
            <?php echo esc_html__('Thrive Dashboard', 'thrive-dash'); ?>
        </a>

        <span class="tvd-breadcrumb"><?php echo esc_html__('Thrive Font Library', 'thrive-dash'); ?></span>
    </div>
    <?php if ( tve_font_library()::is_wordpress_version_supported() ) { ?>
        <!-- The Application -->
        <div id="font-library-app"></div>
    <?php } else { ?>
        <div class="tvd-font-library-version-notice">
            <h1>Font Library</h1>
            <p class="text--secondary">
                <?php echo esc_html__('The Font Library requires WordPress version 6.5 or higher. Please upgrade your WordPress version to continue.', 'thrive-dash'); ?>
                <a href="https://www.wpbeginner.com/beginners-guide/ultimate-guide-to-upgrade-wordpress-for-beginners-infograph/" target="_blank"><?php echo esc_html__('Learn more', 'thrive-dash'); ?></a>
            </p>
        </div>
    <?php } ?>

    <!-- Bottom Navigation -->
    <div class="tvd-bottom-navigation">
        <a class="tvd-btn button--back" href="<?php echo esc_url( $dashboard_link ); ?>">
            <?php echo esc_html__('Back to Dashboard', 'thrive-dash'); ?>
        </a>
    </div>
</div>

<style>
    /**
    * NOTE:
    * 1. Currently, there is only support for one :root block in the CSS.
    * This is a limitation of the current implementation of the CSS parser.
    * See - thrive-dashboard/inc/font-library/classes/Admin::enqueue_with_increased_specificity()
    * This means that if you have multiple :root blocks in your CSS, only the first one will be used.
    *
    * 2. The following CSS variables are used in the FontLibrary component.
    * Though these colors are supposed to be defined in a global CSS file, they are defined here momentarily.
    * Creating a global CSS palette will be beyond the scope of this feature.
    * This will be addressed in a future iteration, probably as part of the REVAMP.
    */
    :root {
        /* Green - Primary */
        --color--primary: #4BB35E;

        /* Red - Error */
        --color--red: #fb5c55;

        /* Grey - Neutral */
        --color--neutral-white: #ffffff;
        --color--neutral-lightest: #f3f6f6;
        --color--neutral-lighter: #eaefef;
        --color--neutral-light: #cdd3d8;
        --color--neutral: #a9a9a9;
        --color--neutral-dark: #898989;
        --color--neutral-darker: #808080;
        --color--neutral-black: #000000;

        --tab-active-indicator-color: var(--color--primary);
        --separator-color: var(--color--neutral-lighter);

        --modal-layer: 100;
    }

    /* 
     * Page level styles - header, spacing, etc.
     * These styles are added here to avoid adding a new file for a few lines of CSS.
     * The few lines constraint must be maintained. If the styles grow, they should be moved to a separate file.
     * Do not add any styles irrelevant to this current template.
     */
    #wpcontent {
        padding-inline-start: 0;
        --breadcrumb-color: var(--color--neutral-darker);
        --breadcrumb-color-active: var(--color--neutral-black);

        /* 
         * Since #wpcontent padding is set to 0, we can remove the negative margin from the header.
         */
        .tvd-header {
            margin-inline-start: 0;
        }
    }

    .tvd-btn {
        min-height: unset;
        line-height: 30px;
        min-width: 130px;
    }

    .tvd-content {
        --flow-space: 20px;
    }

    /* Both the sections have the same padding. */
    #font-library-app,
    .tvd-bottom-navigation {
        padding-inline: 24px;
    }

    .button--back {
        background-color: var(--color--neutral);
    }

    .tvd-font-library-version-notice {
        margin: 20px;
        padding: 20px;
        background-color: #fff;
        color: var(--color--neutral-black);
    }
</style>

<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('Tve_Dash_Growth_Tools_Config')) {
    class Tve_Dash_Growth_Tools_Config
    {
        const REST_NAMESPACE = 'tve-dash/v1';
        const REST_ROUTE = 'growth-tools';

        public static function init()
        {
            static::hooks();
        }

        public static function hooks()
        {
            add_filter( 'tve_dash_admin_product_menu', array( __CLASS__, 'add_to_dashboard_menu' ) );
            add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
            add_filter('tve_dash_features', [__CLASS__, 'tve_dash_features']);
        }

        public static function add_to_dashboard_menu( $menus = array() ) {
            $menus['growth_tools'] = array(
                'parent_slug' => 'tve_dash_section',
                'page_title'  => __( 'About Us', 'thrive-dash' ),
                'menu_title'  => __( 'About Us', 'thrive-dash' ),
                'capability'  => TVE_DASH_CAPABILITY,
                'menu_slug'   => 'about_tve_theme_team',
                'function'    => 'tve_dash_growth_tools_dashboard',
            );

            return $menus;
        }


        public static function tve_dash_features( $enabled_features )
        {
            $enabled_features['growth_tools'] = true;
            return $enabled_features;
        }

        public static function register_rest_routes()
        {
            if ( ! class_exists( 'Growth_Tools_Controller' ) ) {
                require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "Growth_Tools_Controller.php";
            }

            $growthToolsController = new Growth_Tools_Controller();
            $growthToolsController->register_routes();
        }

        /**
         * Load dependencies.
         */
        protected function load_dependencies()
        {
            if ( ! function_exists( 'is_plugin_active' ) ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
        }

        /**
         * Retrieves a list of growth tools.
         *
         * @return array An array containing growth tools categorized by type.
         */
        public static function growth_tools_list()
        {
            return apply_filters(
                'tve_dash_growth_tools',
                array(
                    'SEO and Analytics' => array(
                        array(
                            'plugin_slug' => 'all-in-one-seo-pack',
                            'name' => 'All in One SEO',
                            'path' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
                            'icon' => 'icon-all-in-one-seo',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'The original WordPress SEO plugin and toolkit that improves your website\'s search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.',
                            'landing_url' => 'https://aioseo.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/all-in-one-seo-pack/',
                            'dashboard_uri' => admin_url( 'admin.php?page=aioseo' )
                        ),
                        array(
                            'plugin_slug' => 'google-analytics-for-wordpress',
                            'name' => 'MonsterInsights',
                            'path' => 'google-analytics-for-wordpress/googleanalytics.php',
                            'icon' => 'icon-monster-insights',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.',
                            'landing_url' => 'https://www.monsterinsights.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/google-analytics-for-wordpress/',
                            'dashboard_uri' => admin_url( 'admin.php?page=monsterinsights_settings' )
                        ),
                    ),
                    'Engagement and Social' => array(
                        array(
                            'plugin_slug' => 'pushengage',
                            'name' => 'PushEngage',
                            'path' => 'pushengage/main.php',
                            'icon' => 'icon-push-engage',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Turn website visitors and app users into loyal fans and repeat customers with the best push notification software. 10,000+ businesses worldwide use PushEngage to grow repeat traffic, user engagement, and sales.',
                            'landing_url' => 'https://www.pushengage.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/pushengage/',
                            'dashboard_uri' => admin_url( 'admin.php?page=pushengage' )
                        ),
                        array(
                            'plugin_slug' => 'instagram-feed',
                            'name' => 'Instagram Feeds',
                            'path' => 'instagram-feed/instagram-feed.php',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.',
                            'landing_url' => 'https://smashballoon.com/instagram-feed?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/instagram-feed/',
                            'dashboard_uri' => admin_url( 'admin.php?page=sbi-feed-builder' )
                        ),
                        array(
                            'plugin_slug' => 'custom-facebook-feed',
                            'name' => 'Facebook Feeds',
                            'path' => 'custom-facebook-feed/custom-facebook-feed.php',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, reviews, live videos, comments, and reactions.',
                            'landing_url' => 'https://smashballoon.com/custom-facebook-feed?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/custom-facebook-feed/',
                            'dashboard_uri' => admin_url( 'admin.php?page=cff-setup' )
                        ),
                        array(
                            'plugin_slug' => 'custom-twitter-feeds',
                            'name' => 'Twitter Feeds',
                            'path' => 'custom-twitter-feeds/custom-twitter-feed.php',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.',
                            'landing_url' => 'https://smashballoon.com/custom-twitter-feeds?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/custom-twitter-feeds/',
                            'dashboard_uri' => admin_url( 'admin.php?page=ctf-feed-builder' )
                        ),
                        array(
                            'plugin_slug' => 'feeds-for-youtube',
                            'name' => 'YouTube Feeds',
                            'path' => 'feeds-for-youtube/youtube-feed.php',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.',
                            'landing_url' => 'https://smashballoon.com/youtube-feed?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/feeds-for-youtube/',
                            'dashboard_uri' => admin_url( 'admin.php?page=sby-feed-builder' )
                        ),
                        array(
                            'plugin_slug' => 'reviews-feed',
                            'name' => 'Review Feeds',
                            'path' => 'reviews-feed/sb-reviews.php',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Easily display Google reviews, Yelp reviews, and more in a clean customizable feed. Comes with multiple layouts, customizable review content, leave-a-review link, review moderation, and more.',
                            'landing_url' => 'https://smashballoon.com/reviews-feed?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/reviews-feed/',
                            'dashboard_uri' => admin_url( 'admin.php?page=sbr' )
                        ),
                        array(
                            'plugin_slug' => 'tiktok-feeds',
                            'name' => 'TikTok Feeds',
                            'path' => '',
                            'icon' => 'icon-smash-balloon',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Embed feeds of your TikTok videos on your website -- from different accounts and in multiple layouts in just a few clicks!',
                            'landing_url' => 'https://smashballoon.com/tiktok-feeds?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => ''
                        ),
                        array(
                            'plugin_slug' => 'thrive_quiz_builder',
                            'name' => 'Thrive Quiz Builder',
                            'path' => 'thrive-quiz-builder/thrive-quiz-builder.php',
                            'icon' => 'icon-thrive-quiz-builder',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Easily build advanced online quizzes to better understand your audience, offer personalized product recommendations, segment your email list, and much more.',
                            'landing_url' => 'https://thrivethemes.com/quizbuilder?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=tqb_admin_dashboard' )
                        ),
                        array(
                            'plugin_slug' => 'thrive_comments',
                            'name' => 'Thrive Comments',
                            'path' => '',
                            'icon' => 'icon-thrive-comments',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Turn your blog comments section into an interactive experience with upvotes, social shares, gamified incentives, and more.',
                            'landing_url' => 'https://thrivethemes.com/comments?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=tcm_admin_dashboard' )
                        ),
                        array(
                            'plugin_slug' => 'thrive_ovation',
                            'name' => 'Thrive Ovation',
                            'path' => 'thrive-ovation/thrive-ovation.php',
                            'icon' => 'icon-thrive-ovation',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'A powerful testimonial management system for WordPress. Effortlessly save and display testimonials to maximize social proof.',
                            'landing_url' => 'https://thrivethemes.com/ovation/?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=tvo_admin_dashboard' )
                        ),
                        array(
                            'plugin_slug' => 'thrive_ultimatum',
                            'name' => 'Thrive Ultimatum',
                            'path' => 'thrive-ultimatum/thrive-ultimatum.php',
                            'icon' => 'icon-thrive-ultimatum',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'The #1 WordPress scarcity marketing tool to build advanced countdown timer campaigns.',
                            'landing_url' => 'https://thrivethemes.com/ultimatum/?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=tve_ult_dashboard' )
                        ),
                    ),
                    'Conversion Rate Optimization' => array(
                        array(
                            'plugin_slug' => 'trustpulse-api',
                            'name' => 'TrustPulse',
                            'path' => 'trustpulse-api/trustpulse.php',
                            'icon' => 'icon-trust-pulse',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'TrustPulse is the honest marketing platform that leverages and automates the real power of social proof to instantly increase trust, conversions and sales.',
                            'landing_url' => 'https://wordpress.org/plugins/trustpulse-api?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/trustpulse-api/',
                            'dashboard_uri' => admin_url( 'admin.php?page=trustpulse' )
                        ),
                        array(
                            'plugin_slug' => 'affiliate-wp',
                            'name' => 'AffiliateWP',
                            'path' => '',
                            'icon' => 'icon-affiliate-wp',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'The #1 affiliate management plugin for WordPress. Easily create an affiliate program for your eCommerce store or membership site within minutes and start growing your sales with the power of referral marketing.',
                            'landing_url' => 'https://affiliatewp.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => ''
                        ),
                    ),
                    'Lead Generation' => array(
                        array(
                            'plugin_slug' => 'wpforms-lite',
                            'name' => 'WPForms',
                            'path' => 'wpforms-lite/wpforms.php',
                            'icon' => 'icon-am-wp-forms',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'The world’s most popular WordPress form builder, trusted by over 6 million websites. Get hundreds of pre-made templates so you can easily build contact forms, payment forms, and more.',
                            'landing_url' => 'https://wpforms.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/wpforms-lite/',
                            'dashboard_uri' => admin_url( 'admin.php?page=wpforms-overview' )
                        ),
                        array(
                            'plugin_slug' => 'optinmonster',
                            'name' => 'OptinMonster',
                            'path' => 'optinmonster/optin-monster-wp-api.php',
                            'icon' => 'icon-am-optin-monster',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.',
                            'landing_url' => 'https://wordpress.org/plugins/optinmonster?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/optinmonster/',
                            'dashboard_uri' => admin_url( 'admin.php?page=optin-monster-dashboard' )
                        ),
                        array(
                            'plugin_slug' => 'rafflepress',
                            'name' => 'RafflePress',
                            'path' => 'rafflepress/rafflepress.php',
                            'icon' => 'icon-raffle-press',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.',
                            'landing_url' => 'https://rafflepress.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/rafflepress/',
                            'dashboard_uri' => admin_url( 'admin.php?page=rafflepress_lite' )
                        ),
                        array(
                            'plugin_slug' => 'thrive_leads',
                            'name' => 'Thrive Leads',
                            'path' => '',
                            'icon' => 'icon-am-thrive-leads',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Turn site visitors into engaged leads and build a more valuable mailing list faster.',
                            'landing_url' => 'https://thrivethemes.com/leads?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=thrive_leads_dashboard' )
                        ),
                    ),
                    'Email Marketing and Deliverability' => array(
                        array(
                            'plugin_slug' => 'wp-mail-smtp',
                            'name' => 'WP Mail SMTP',
                            'path' => 'wp-mail-smtp/wp_mail_smtp.php',
                            'icon' => 'icon-am-wp-mail-smtp',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Make sure your WordPress emails always reach the recipient\'s inbox with the world\'s #1 SMTP plugin. Over 3,000,000 websites use WP Mail SMTP to fix their email deliverability issues.',
                            'landing_url' => 'https://wpmailsmtp.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/wp-mail-smtp/',
                            'dashboard_uri' => admin_url( 'admin.php?page=wp-mail-smtp' )
                        ),
                        array(
                            'plugin_slug' => 'send-layer',
                            'name' => 'SendLayer',
                            'path' => '',
                            'icon' => 'icon-send-layer',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Reliable email delivery made easy. Send your website emails with our API and SMTP relay for maximum deliverability, reliability, and scalability.',
                            'landing_url' => 'https://sendlayer.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => ''
                        ),
                    ),
                    'Essential WordPress Tools' => array(
                        array(
                            'plugin_slug' => 'insert-headers-and-footers',
                            'name' => 'WPCode',
                            'path' => 'insert-headers-and-footers/ihaf.php',
                            'icon' => 'icon-am-wp-code',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.',
                            'landing_url' => 'https://wpcode.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/insert-headers-and-footers/',
                            'dashboard_uri' => admin_url( 'admin.php?page=wpcode' )
                        ),
                        array(
                            'plugin_slug' => 'duplicator',
                            'name' => 'Duplicator',
                            'path' => 'duplicator/duplicator.php',
                            'icon' => 'icon-am-duplicator',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make reliable and secure WordPress backups to protect their websites. It also makes website migration really easy.',
                            'landing_url' => 'https://duplicator.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/duplicator/',
                            'dashboard_uri' => admin_url( 'admin.php?page=duplicator' )
                        ),

                    ),
                    'Ecommerce and Payments' => array(
                        array(
                            'plugin_slug' => 'charitable',
                            'name' => 'WP Charitable',
                            'path' => 'charitable/charitable.php',
                            'icon' => 'icon-wp-charitable',
                            'available_at_org' => true,
                            'status' => '',
                            'summary' => 'Top-rated WordPress donation and fundraising plugin. Over 10,000+ non-profit organizations and website owners use Charitable to create fundraising campaigns and raise more money online.',
                            'landing_url' => 'https://www.wpcharitable.com?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => 'https://wordpress.org/plugins/charitable/',
                            'dashboard_uri' => admin_url( 'admin.php?page=charitable' )
                        ),
                    ),
                    'Course Management & Membership' => array(
                        array(
                            'plugin_slug' => 'thrive_apprentice',
                            'name' => 'Thrive Apprentice',
                            'path' => 'thrive-apprentice/thrive-apprentice.php',
                            'icon' => 'icon-thrive-apprentice',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'The #1 Membership and Course Builder for WordPress. Start creating attractive online courses and membership sites that sell with the most customizable LMS and Membership plugin available.',
                            'landing_url' => 'https://thrivethemes.com/apprentice?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=thrive_apprentice' )
                        ),
                    ),
                    'Website Builders' => array(
                        array(
                            'plugin_slug' => 'thrive-architect',
                            'name' => 'Thrive Architect',
                            'path' => '',
                            'icon' => 'icon-am-thrive-architect',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'The #1 WordPress page builder for deliberate marketers who have a vision of a conversion-focused website building.',
                            'landing_url' => 'https://thrivethemes.com/architect?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'edit.php?post_type=page' )
                        ),
                        array(
                            'plugin_slug' => 'thrive-theme-builder',
                            'name' => 'Thrive Theme Builder',
                            'path' => '',
                            'icon' => 'icon-am-thrive-theme-builder',
                            'available_at_org' => false,
                            'status' => '',
                            'summary' => 'Visually build the conversion focused site of your dreams no matter your experience level with the only marketing centered WordPress Theme Builder',
                            'landing_url' => 'https://thrivethemes.com/themebuilder?utm_source=thrivethemes&utm_medium=link&utm_campaign=TTGrowthTools&utm_content='. TVE_DASH_VERSION,
                            'install_uri' => '',
                            'dashboard_uri' => admin_url( 'admin.php?page=thrive-theme-dashboard' )
                        ),

                    )
                )
            );
        }

        /**
         * Categories.
         *
         * @return array
         */
        public static function categories()
        {
            $items = apply_filters(
                'tve_dash_growth_tools_category',
                array(
                    10 => 'SEO and Analytics',
                    20 => 'Engagement and Social',
                    30 => 'Conversion Rate Optimization',
                    40 => 'Lead Generation',
                    50 => 'Email Marketing and Deliverability',
                    60 => 'Essential WordPress Tools',
                    70 => 'Ecommerce and Payments',
                    80 => 'Course Management & Membership',
                    90 => 'Website Builders',
                )
            );

            ksort( $items );
            return $items;
        }

        /**
         * Retrieves tools ordered by category.
         *
         * @return array Tools ordered by category.
         */
        protected function get_tools_order_by_category() {
            $categories = $this->categories();
            $tools_list = self::growth_tools_list();
            $tools = [];

            foreach ( $categories as $category ) {
                $tools[ $category ] = $tools_list[ $category ];
            }

            return apply_filters( 'tve_dash_growth_tools_by_category', $tools );
        }

        /**
         * Get the URL for a REST API route.
         *
         * @param string $endpoint The REST API endpoint.
         * @param int    $id       Optional. The ID for the endpoint.
         * @param array  $args     Optional. Additional arguments to append to the URL as query parameters.
         * @return string The URL for the REST API route.
         */
        public function tva_get_route_url( $endpoint, $id = 0, $args = array() ) {
            $url = get_rest_url() . $this->namespace . '/' . $endpoint;

            if ( ! empty( $id ) && is_numeric( $id ) ) {
                $url .= '/' . $id;
            }

            if ( ! empty( $args ) ) {
                $url = add_query_arg( $args, $url );
            }

            return $url;
        }


        /**
         * Enqueue scripts and styles for the growth tools feature.
         */
        protected function enqueue()
        {
            tve_dash_enqueue();
            include TVE_DASH_PATH . '/inc/growth-tools/assets/css/growth-tools-icons.svg';
            tve_dash_enqueue_style( 'tve-dash-growth-tools-css', TVE_DASH_URL . '/inc/growth-tools/assets/css/style.css' );

            tve_dash_enqueue_script( 'tve-dash-growth-tools', TVE_DASH_URL . '/inc/growth-tools/assets/dist/growth-tools.min.js', array(
                'tve-dash-main-js',
                'jquery',
                'backbone',
            ), false, true );

            wp_localize_script( 'tve-dash-growth-tools', 'TVD_AM_CONST', array(
                'baseUrl'          => $this->tva_get_route_url( 'growth-tools' ),
                'tools_category'   => json_encode( $this->categories() ),
                'is_TPM_installed' => is_TPM_installed(),
            ) );
        }

        /**
         * Render a template.
         *
         * @param string $file Template file.
         * @param array  $data Template data.
         */
        protected function render( $file, $data = array() ) {
            if ( strpos( $file, '.php' ) === false ) {
                $file .= '.php';
            }
            $file_path = $this->view_path . $file;

            if ( ! is_file( $file_path ) ) {
                echo 'No template found for ' . esc_html( $file );
                return;
            }

            if ( ! empty( $data ) ) {
                $this->data = array_merge_recursive( $this->data, $data );
            }

            include $file_path;
        }
    }
}

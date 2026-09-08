<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}
$product_key  = TD_Ian_Helper::get_product_name_by_page( $this->current_page );
$dom_class = TD_Ian_Helper::class_by_product( $product_key );

$class = "td-app-notification-counter";
if ( $this->current_page === 'tcm_admin_dashboard' ) {
    $class .= " tvd-right";
}
?>
<li class="<?php echo $class?>">
    <span id="tvd-notifications-btn ian-notification" class="tvd-notifications-btn ian-icon-<?php echo $dom_class?>">
        <span class="td-app-notification-counter-holder" style="display: none;"></span>

        <svg width="20" height="20" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="1" y="0.793457" width="20" height="20" rx="1" stroke="#12394A" stroke-width="1.5"/>
            <path d="M21 15.0793H14.5135C14.5135 15.0793 14.5135 17.9365 11.2703 17.9365C8.02703 17.9365 8.02703 15.0793 8.02703 15.0793H1" stroke="#12394A" stroke-width="1.5"/>
        </svg>

    </span>
</li>

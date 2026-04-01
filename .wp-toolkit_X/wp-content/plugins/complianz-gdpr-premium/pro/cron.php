<?php
defined('ABSPATH') or die("you do not have access to this page!");

add_action('plugins_loaded','cmplz_pro_schedule_cron');
function cmplz_pro_schedule_cron() {
    //link function to this custom cron hook
    add_action( 'cmplz_every_week_hook', array(COMPLIANZ::$sync, 'maybe_sync_cookies'), 100);
    add_action( 'cmplz_every_week_hook', array(COMPLIANZ::$sync, 'maybe_sync_services'), 110);

    add_action( 'cmplz_every_day_hook', array(COMPLIANZ::$admin_statistics, 'cron_maybe_enable_best_performer'));
    add_action( 'cmplz_every_day_hook', array(COMPLIANZ::$geoip, 'cron_check_geo_ip_db'));


    //testing
    //        add_action( 'init', array(COMPLIANZ::$sync, 'maybe_sync_cookies'), 100);
//        add_action( 'init', array(COMPLIANZ::$sync, 'maybe_sync_services'), 110);

//    if (defined('cmplz_premium')) add_action( 'init', array(COMPLIANZ::$statistics, 'cron_maybe_enable_best_performer'));
//    if (defined('cmplz_premium')) add_action( 'init', array(COMPLIANZ::$geoip, 'cron_check_geo_ip_db'));
}

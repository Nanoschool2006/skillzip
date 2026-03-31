<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
require_once(CMPLZ_PATH . 'pro/tcf/tcf.php');
require_once(CMPLZ_PATH . 'pro/class-geoip.php' );
require_once(CMPLZ_PATH . 'pro/statistics/class-statistics.php');
require_once(CMPLZ_PATH . 'pro/functions.php');
require_once(CMPLZ_PATH . 'pro/filters-actions.php');
require_once(CMPLZ_PATH . 'pro/upgrade.php');

if ( cmplz_admin_logged_in() ) {
	require_once( CMPLZ_PATH . 'pro/cron.php');
	require_once( CMPLZ_PATH . 'pro/tcf/tcf-admin.php' );
	require_once( CMPLZ_PATH . 'pro/statistics/class-admin-statistics.php' );
	require_once( CMPLZ_PATH . 'pro/settings/fields-notices.php' );
	require_once( CMPLZ_PATH . 'pro/settings/fields/translations.php' );
	require_once( CMPLZ_PATH . 'pro/class-comments.php' );
	require_once( CMPLZ_PATH . 'pro/class-import.php' );
	require_once( CMPLZ_PATH . 'pro/class-support.php' );
	require_once( CMPLZ_PATH . 'pro/processing-agreements/class-processing.php' );
	require_once( CMPLZ_PATH . 'pro/dataleak/class-dataleak.php' );
	if ( is_multisite() ){
		require_once(CMPLZ_PATH . 'pro/multisite/copy-settings-multisite.php');
	}
}
require_once( CMPLZ_PATH . 'pro/datarequests/class-datarequests.php' );
require_once( CMPLZ_PATH . 'pro/translations/class-translations.php' );



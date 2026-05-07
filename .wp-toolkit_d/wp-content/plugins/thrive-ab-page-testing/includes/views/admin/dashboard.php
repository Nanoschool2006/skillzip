<?php
/**
 * Created by PhpStorm.
 * User: Ovidiu
 * Date: 1/16/2018
 * Time: 9:23 AM
 */
?>
<div id="thrive-ab-top-bar" class="thrive-ab-logo-holder" style="margin-left: -20px">
	<div class="thrive-ab-logo">
		<span></span>
	</div>
	<?php do_action( 'tvd_notification_inbox' ); ?>
</div>
<div class="td-app-notification-overlay overlay close "></div>
<div class="td-app-notification-drawer">
    <div class="td-app-notification-holder">
        <div class="td-app-notification-header notification-header-notify-t-optimize"></div>
        <div class="td-app-notification-wrapper notification-wrapper-notify-t-optimize"></div>
        <div class="notification-footer notification-footer-notify-t-optimize"></div>
    </div>
</div>
<div id="tab-breadcrumbs-wrapper"></div>
<?php echo tvd_get_individual_plugin_license_message( new Thrive_AB_Product(), true ); ?>
<div id="tab-admin-dashboard-wrapper"></div>

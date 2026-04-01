<?php global $OUTPUT; ?>

<?php
$sharedsecret = (isset($config->sharedsecret) && !empty($config->sharedsecret)) ? $config->sharedsecret : '';
$logoutredirecturl = (isset($config->logoutredirecturl) && !empty($config->logoutredirecturl)) ? $config->logoutredirecturl : '';
$wpsiteurl = (isset($config->wpsiteurl) && !empty($config->wpsiteurl)) ? $config->wpsiteurl : '';
?>

<table cellspacing="0" cellpadding="5" border="0">

<tr valign="top" class="required">
    <td align="right"><label for="sharedsecret"><?php print_string('auth_wdmwpmoodle_secretkey', 'auth_wdmwpmoodle') ?></label></td>
    <td>
        <input id="sharedsecret" name="sharedsecret" type="text" size="30" value="<?php echo $sharedsecret?>" />
    </td>
    <td><i><?php print_string('auth_wdmwpmoodle_secretkey_desc', 'auth_wdmwpmoodle') ?></i></td>
</tr>

<tr valign="top" class="required">
    <td align="right"><label for="wpsiteurl"><?php print_string('auth_wdmwpmoodle_wpsiteurl_lbl', 'auth_wdmwpmoodle') ?></label></td>
    <td>
        <input id="wpsiteurl" name="wpsiteurl" type="url" size="30" placeholder="e.g. http://mywpsite.com" value="<?php echo $wpsiteurl ?>" />
    </td>
</tr>

<tr valign="top" class="required">
    <td align="right"><label for="logoutredirecturl"><?php print_string('auth_wdmwpmoodle_logoutredirecturl_lbl', 'auth_wdmwpmoodle') ?></label></td>
    <td>
        <input id="logoutredirecturl" name="logoutredirecturl" type="url" size="30" placeholder="e.g. http://redirecttothis.com" value="<?php echo $logoutredirecturl ?>" />
    </td>
    <td><i><?php print_string('auth_wdmwpmoodle_logoutredirecturl_desc', 'auth_wdmwpmoodle') ?></i></td>
</tr>

</table>

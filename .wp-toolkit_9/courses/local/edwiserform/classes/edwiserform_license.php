<?php
// This file is part of Edwiserform Moodle Local Plugin.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Edwiser Forms license page.
 * @package   local_edwiserform
 * @copyright (c) 2020 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB, $CFG;
require_once('license_controller.php');


// License controller.
$plugin_slug = 'edwiser-form-pro';

// Get License key.
$license_key = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $plugin_slug .'_license_key'), IGNORE_MISSING);


// Get License Status.
$status = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'edd_' . $plugin_slug . '_license_status'), IGNORE_MISSING);


// Get renew link.
$renew_link = $DB->get_field_select('config_plugins', 'value', 'name = :name', array('name' => 'wdm_'.$plugin_slug.'_product_site'), IGNORE_MISSING);
$licensekey = get_config('local_edwiserform', 'licensekey');
$licensekeyactivate = get_config('local_edwiserform', 'licensekeyactivate');
$licensekeydeactivate = get_config('local_edwiserform', 'licensekeydeactivate');

// Show proper reponse to user on license activation/deactivation.
if ($licensekey == 'empty') {
    // If empty, show error message.
    echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Error</h4>'.get_string("enterlicensekey", "local_edwiserform").'
    </div>';
} elseif (!empty($licensekey) && $licensekey != 'empty') {
    if ($status !== false && $status == 'valid' && !empty($licensekeyactivate)) {
        // Valid license key.
        echo '<div class="alert alert-success">
           <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
           <h4><i class="icon fa fa-check"></i> Success</h4>'.get_string("licensekeyactivated", "local_edwiserform").'
        </div>';
    } elseif ($status !== false && $status == 'expired') { // Expired license key.
        echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeyhasexpired", "local_edwiserform").'
    </div>';
    } elseif ($status !== false && $status == 'disabled') { // Disabled license key.
            echo '<div class="alert alert-danger">
               <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
               <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeyisdisabled", "local_edwiserform").'
            </div>';
    } elseif ($status == 'invalid') { // Invalid license key.
        echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("entervalidlicensekey", "local_edwiserform").'
    </div>';
    } elseif ($status == 'site_inactive') { // Site is inactive.
        echo '<div class="alert alert-danger">
              <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
              <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("siteinactive", "local_edwiserform").'
            </div>';
    } elseif ($status == 'deactivated') { // Site is inactive.
            echo '<div class="alert alert-danger">
       <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
       <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("licensekeydeactivated", "local_edwiserform").'
       </div>';
    } elseif ($status == 'no_response' || (!empty($licensekeydeactivate) && $status == 'valid')) { // Site is inactive.
        echo '<div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h4><i class="icon fa fa-ban"></i> Alert!</h4>'.get_string("noresponsereceived", "local_edwiserform").'
            </div>';
    }
}

// Remove config vars.
unset_config('licensekey', 'local_edwiserform');
unset_config('licensekeyactivate', 'local_edwiserform');
unset_config('licensekeydeactivate', 'local_edwiserform');
?>
        <div class="license-box box box-warning">
            <div class="box-header with-border">
              <h3 class="box-title"><?php echo get_string('edwiserformlicenseactivation', 'local_edwiserform') ?></h3>
            </div>

            <!-- /.box-header -->
            <div class="panel-body">

              <input type="hidden" name="activetab" value="local_edwiserform_license_status">

                <?php

                if ($status=="valid") {
                    ?>

                    <div class="form-group has-success">

                      <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'local_edwiserform') ?>:</label>

                      <div class="col-sm-9">
                        <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control' name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' readonly/>"; ?>
                      </div>
                    </div>

                <?php
                } elseif ($status=="expired") {
                    ?>

                    <div class="form-group has-error">

                        <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'local_edwiserform') ?>:</label>

                        <div class="col-sm-9">
                        <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control' name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' readonly/>"; ?>
                        </div>
                    </div>

                <?php
                } else {
                    ?>

                    <div class="form-group has-error">

                        <label class="control-label text-black col-sm-3"><?php echo get_string('licensekey', 'local_edwiserform') ?>:</label>

                        <div class="col-sm-9">
                        <?php echo "<input id='edd_{$plugin_slug}_license_key' class='form-control'  name='edd_{$plugin_slug}_license_key' type='text' class='regular-text' value='{$license_key}' placeholder='Enter license key...' />"; ?>
                        </div>
                    </div>

                <?php
                } ?>

                <div class="form-group">
                    <?php
                        echo '<label class="control-label col-sm-3">'.get_string('licensestatus', 'local_edwiserform').':</label>';

                        echo '<div class="col-sm-9">';

                        $status_text_active = get_string('active', 'local_edwiserform');
                        $status_text_active_text = "<p style='color:green;'>{$status_text_active}</p>";
                        $status_text_inactive = get_string('notactive', 'local_edwiserform');
                        $status_text_inactive_text = "<p style='color:red;'>{$status_text_inactive}</p>";
                        $status_text_expired = get_string('expired', 'local_edwiserform');
                        $status_text_expired_text = "<p style='color:red;'>{$status_text_expired}</p>";

                    if ($status !== false && $status == 'valid') {
                        echo $status_text_active_text;
                    } elseif ($status == 'site_inactive') {
                        echo $status_text_inactive_text;
                    } elseif ($status == 'expired') {
                        echo $status_text_expired_text;
                    } elseif ($status == 'invalid') {
                        echo $status_text_inactive_text;
                    } else {
                        echo $status_text_inactive_text;
                    }

                        echo '</div>';
                    ?>
                </div>

                <div class="form-group">
                    <?php

                        $activate_license_text = get_string('activatelicense', 'local_edwiserform');
                        $deactivate_license_text = get_string('deactivatelicense', 'local_edwiserform');
                        $renew_license_text = get_string('renewlicense', 'local_edwiserform');

                        echo '<div class="col-sm-9">';

                        // Hidden field to cehck if on license tab.
                        echo "<input type='hidden' id='onEdwiserFormLicensePage' name='onEdwiserFormLicensePage' value='1'/>";

                    if ($status !== false && $status == 'valid') {
                        echo "<input type='submit' class='btn btn-primary text-white'  style='color:white;' name='edd_{$plugin_slug}_license_deactivate' value='{$deactivate_license_text}'/>";
                    } elseif ($status == 'expired') {
                        echo "<input type='submit' class='btn btn-primary' style='color:white;' name='edd_{$plugin_slug}_license_deactivate' value='{$deactivate_license_text}'/>&nbsp&nbsp";

                        echo '<input type="button" class="btn btn-primary" style="color:white;" name="edd_'.$plugin_slug.'_license_renew" value="'.$renew_license_text.'" onclick="window.open(\''.$renew_link.'\');">';
                    } else {
                        echo "<input type='submit' class='btn btn-primary' style='color:white;' name='edd_{$plugin_slug}_license_activate' value='{$activate_license_text}'/>";
                    }

                        echo '</div>';
                    ?>
                </div>

            </div>
            <!-- /.box-body -->
          </div>

<?php

namespace NmBridgeWoocommerce;

class BridgeWooGetPluginData
{

    public static $responseData;

    /**
     * Retrieves licensing information from database. If valid information is not found, sends request to server to get info
     * @param  array  $pluginData Plugin data
     * @param  boolean $cache      When cache is true, it returns the value stored in static variable $responseData. When set to false, it forcefully retrieves value from database. Example: If you want to show plugin's settings page after activating license, then pass false, so that it will forcefully get the data from database
     * @return string              returns 'available' if license is valid or expired else returns 'unavailable'
     */
    public static function getDataFromDb($pluginData, $cache = true)
    {
        if (null !== self::$responseData && $cache === true) {
            return self::$responseData;
        }
        $pluginName = isset( $pluginData[ 'pluginName' ] ) ? $pluginData[ 'pluginName' ] : 'WooCommerce Integration';
        $pluginSlug = isset( $pluginData[ 'pluginSlug' ] ) ? $pluginData[ 'pluginSlug' ] : 'woocommerce_integration';
        $storeUrl   = isset( $pluginData[ 'storeUrl' ] ) ? $pluginData[ 'storeUrl' ] : 'https://edwiser.org/check-update';

        $licenseTransient = get_transient('wdm_' . $pluginSlug . '_license_trans');

        if (! $licenseTransient) {
            $licenseKey = trim(get_option('edd_' . $pluginSlug . '_license_key'));

            if ($licenseKey) {
                $apiParams = array(
                    'edd_action'         => 'check_license',
                    'license'            => $licenseKey,
                    'item_name'          => urlencode($pluginName),
                    'current_version'    => isset( $pluginData[ 'pluginVersion' ] ) ? $pluginData[ 'pluginVersion' ] : '2.2.0', 
                );

                $response = wp_remote_get(
                    add_query_arg($apiParams, $storeUrl),
                    array( 
						'timeout' => 15,
						'sslverify' => false,
						'blocking' => true,
						'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) . 'Edwiser Bridge',
					)
                );

                if (is_wp_error($response)) {
                    return false;
                }

                $licenseData = json_decode(wp_remote_retrieve_body($response));

                $validResponseCode = array( '200', '301' );

                $currentResponseCode = wp_remote_retrieve_response_code($response);

                if ($licenseData == null || ! in_array($currentResponseCode, $validResponseCode)) {
                    //if server does not respond, read current license information
                    $licenseStatus = get_option('edd_' . $pluginSlug . '_license_status', '');
                    if (empty($licenseData)) {
                        set_transient('wdm_' . $pluginSlug . '_license_trans', 'server_did_not_respond', 60 * 60 * 24);
                    }
                } else {
                    include_once(plugin_dir_path(__FILE__) . 'class-bridge-woo-add-plugin-data-in-db.php');
                    $licenseStatus = BridgeWooAddPluginDataInDB::updateStatus($licenseData, $pluginSlug);
                }

                $activeSite = self::getSiteList($pluginSlug);

                self::setResponseData($licenseStatus, $activeSite, $pluginSlug, true);

                return self::$responseData;
            }
        } else {
            $licenseStatus  = get_option('edd_' . $pluginSlug . '_license_status');
            $activeSite     = self::getSiteList($pluginSlug);

            self::setResponseData($licenseStatus, $activeSite, $pluginSlug);
            return self::$responseData;
        }
    }

    public static function setResponseData($licenseStatus, $activeSite, $pluginSlug, $setTransient = false)
    {

        if ($licenseStatus == 'valid') {
            self::$responseData = 'available';
        } elseif ($licenseStatus == 'expired' && ( ! empty($activeSite) || $activeSite != "")) {
            self::$responseData = 'unavailable';
        } elseif ($licenseStatus == 'expired') {
            self::$responseData = 'available';
        } else {
            self::$responseData  = 'unavailable';
        }

        if ($setTransient) {
            switch ($licenseStatus) {
                case 'invalid':
                case 'no_activations_left':
                    $time = 0; // Do not repeat.
                    break;
                case 'failed':
                    $time = 86400; // Repeat everyday.
                    break;
                case 'expired':
                    $time = 86400 * 2; // Repeat every 2 days.
                    break;
                case 'disabled':
                    $time = 86400 * 4; // Repeat every 4 days.
                    break;
                case 'valid':
                    $time = 86400 * 7; // Repeat every 7 days.
                    break;
                default:
                    $time = 86400 * 7; // Fallback. Repeat every 7 days.
                    break;
            }
            set_transient( 'wdm_' . $pluginSlug . '_license_trans', $licenseStatus, $time );
        }
    }

    /**
     * This function is used to get list of sites where license key is already acvtivated.
     *
     * @param type $pluginSlug current plugin's slug
     * @return string  list of site
     *
     * @author Foram Rambhiya
     *
     */
    public static function getSiteList($pluginSlug)
    {
        $sites       = get_option('wdm_' . $pluginSlug . '_license_key_sites');
        $max         = get_option('wdm_' . $pluginSlug . '_license_max_site');
        $currentSite    = get_site_url();
        //EDD treats site with www as a different site. Solving this issue.
        $currentSite    = str_ireplace('www.', '', $currentSite);

        /****** cahange new line found  line no 128    ***/
        $currentSite    = preg_replace('#^https?://#', '', $currentSite);

        $siteCount  = 0;
        $activeSite = "";

        if (! empty($sites) || $sites != "") {
            foreach ($sites as $key) {
                foreach ($key as $value) {
                    $value = rtrim($value, "/");

                    if (strcasecmp($value, $currentSite) != 0) {
                        $activeSite.= "<li>" . $value . "</li>";
                        $siteCount ++;
                    }
                }
            }
        }

        //echo $activeSite; exit;
        if ($siteCount >= $max) {
            return $activeSite;
        } else {
            return "";
        }
    }
}

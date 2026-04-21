<?php

/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-dashboard
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}

/**
 * ConvertKit Exception Class
 * Used for API error handling
 */
if ( ! class_exists( 'Thrive_Dash_Api_ConvertKit_Exception' ) ) {
	class Thrive_Dash_Api_ConvertKit_Exception extends Exception {
	}
}

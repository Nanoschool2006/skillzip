<?php

class TD_Ian_Helper {
	public static function product_list() {
		return [
			'tve_dash',// Thrive Dashboard
			'thrive_automator',//Thrive Automator
			'thrive_apprentice', //Thrive Apprentice
			'tcm', // Thrive Comments
			'tvo',// Thrive Ovation
			'tab_admin_dashboard', // Thrive Optimize
			'thrive_leads', // Thrive Leads
			'tqb', // Thrive Quiz Builder
			'tve_ult', //Thrive Ultimatum
			'architect', //Thrive Architect
			'thrive-theme-dashboard' //Thrive Theme Builder
		];
	}

	public static function class_by_product( $product ) {
		$classList = [
			'tve_dash'               => 'notify-td',
			'thrive_automator'       => 'notify-t-automator',
			'thrive_apprentice'      => 'notify-ta',
			'tcm'                    => 'notify-tcm',
			'tvo'                    => 'notify-tvo',
			'tab_admin_dashboard'    => 'notify-t-optimize',
			'thrive_leads'           => 'notify-thrive-leads',
			'tqb'                    => 'notify-tqb',
			'tve_ult'                => 'notify-tve-ult',
			'architect'              => 'notify-tar',
			'thrive-theme-dashboard' => 'notify-ttb'
		];

		return isset( $classList[ $product ] ) ? $classList[ $product ] : '';
	}

	public static function get_product_name_by_page( $page ) {
		$products = self::product_list();
		$product  = "";
		foreach ( $products as $p ) {
			if ( strpos( $page, $p ) !== false ) {
				$product = $p;
				break;
			}
		}

		return $product;
	}

	public static function get_user_product_ids() {
		$user_license_details = thrive_get_transient( 'td_ttw_licenses_details' );
		$user_products        = [];

		foreach ( $user_license_details as $license ) {
			$user_products[] = $license['product_id'];
		}

		return $user_products;
	}
}

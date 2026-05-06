<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class TCB_Lead_Generation_Recaptcha_Element extends TCB_Element_Abstract {

	public function name() {
		return __( 'Form Spam Prevention Tool', 'thrive-cb' );
	}

	public function identifier() {
		return '.tve-sp-tool-container';
	}

	public function hide() {
		return true;
	}

	public function own_components() {

		$components = array(
			'lead_generation_recaptcha' => array(
				'config' => array(
					'CaptchaTheme'      => array(
						'config'  => array(
							'name'    => __( 'Theme', 'thrive-cb' ),
							'options' => array(
								array(
									'value' => 'light',
									'name'  => __( 'Light', 'thrive-cb' ),
								),
								array(
									'value' => 'dark',
									'name'  => __( 'Dark', 'thrive-cb' ),
								),
							),
						),
						'extends' => 'Select',
					),
					'CaptchaLanguage'   => array(
						'config'  => array(
							'name'    => __( 'Language', 'thrive-cb' ),
							'options' => $this->supportedLanguages(),
						),
						'extends' => 'Select',
					),
					'CaptchaAppearance' => array(
						'config'  => array(
							'name'    => __( 'Appearance mode', 'thrive-cb' ),
							'options' => array(
								'always'           => 'Always',
								'interaction-only' => 'Interaction Only',
							),
						),
						'extends' => 'Select',
					),
					'CaptchaType'       => array(
						'config'  => array(
							'name'    => __( 'Type', 'thrive-cb' ),
							'options' => array(
								array(
									'value' => 'image',
									'name'  => __( 'Image', 'thrive-cb' ),
								),
								array(
									'value' => 'audio',
									'name'  => __( 'Audio', 'thrive-cb' ),
								),
							),
						),
						'extends' => 'Select',
					),
					'CaptchaSize'       => array(
						'config'  => array(
							'name'    => __( 'Size', 'thrive-cb' ),
							'options' => array(
								array(
									'value' => 'normal',
									'name'  => __( 'Normal', 'thrive-cb' ),
								),
								array(
									'value' => 'compact',
									'name'  => __( 'Compact', 'thrive-cb' ),
								),
							),
						),
						'extends' => 'Select',
					),
				),
			),
			'layout'                    => [
				'disabled_controls' => [
					'Width',
					'Height',
					'padding',
					'Display',
					'.tve-advanced-controls',
				],
				'config'            => [
					'Alignment' => [
						'important' => true,
					],
				],
			],
			'animation'                 => [
				'hidden' => true,
			],
			'background'                => [
				'hidden' => true,
			],
			'responsive'                => [
				'hidden' => true,
			],
			'styles-templates'          => [ 'hidden' => true ],
			'typography'                => [ 'hidden' => true ],
		);

		return $components;
	}

	public function supportedLanguages() {
		$languages = array(
			"auto"  => __( "Auto", 'thrive-cb' ),
			"en"    => __( "English (United States)", 'thrive-cb' ),
			"ar"    => __( "Arabic (Egypt)", 'thrive-cb' ),
			"bg"    => __( "Bulgarian (Bulgaria)", 'thrive-cb' ),
			"cs"    => __( "Czech (Czech Republic)", 'thrive-cb' ),
			"da"    => __( "Danish (Denmark)", 'thrive-cb' ),
			"de"    => __( "German (Germany)", 'thrive-cb' ),
			"el"    => __( "Greek (Greece)", 'thrive-cb' ),
			"es"    => __( "Spanish (Spain)", 'thrive-cb' ),
			"fa"    => __( "Farsi (Iran)", 'thrive-cb' ),
			"fi"    => __( "Finnish (Finland)", 'thrive-cb' ),
			"fr"    => __( "French (France)", 'thrive-cb' ),
			"he"    => __( "Hebrew (Israel)", 'thrive-cb' ),
			"hi"    => __( "Hindi (India)", 'thrive-cb' ),
			"hr"    => __( "Croatian (Croatia)", 'thrive-cb' ),
			"hu"    => __( "Hungarian (Hungary)", 'thrive-cb' ),
			"id"    => __( "Indonesian (Indonesia)", 'thrive-cb' ),
			"it"    => __( "Italian (Italy)", 'thrive-cb' ),
			"ja"    => __( "Japanese (Japan)", 'thrive-cb' ),
			"ko"    => __( "Korean (Korea)", 'thrive-cb' ),
			"lt"    => __( "Lithuanian (Lithuania)", 'thrive-cb' ),
			"ms"    => __( "Malay (Malaysia)", 'thrive-cb' ),
			"nb"    => __( "Norwegian Bokmål (Norway)", 'thrive-cb' ),
			"nl"    => __( "Dutch (Netherlands)", 'thrive-cb' ),
			"pl"    => __( "Polish (Poland)", 'thrive-cb' ),
			"pt"    => __( "Portuguese (Brazil)", 'thrive-cb' ),
			"ro"    => __( "Romanian (Romania)", 'thrive-cb' ),
			"ru"    => __( "Russian (Russia)", 'thrive-cb' ),
			"sk"    => __( "Slovak (Slovakia)", 'thrive-cb' ),
			"sl"    => __( "Slovenian (Slovenia)", 'thrive-cb' ),
			"sr"    => __( "Serbian (Serbia)", 'thrive-cb' ),
			"sv"    => __( "Swedish (Sweden)", 'thrive-cb' ),
			"th"    => __( "Thai (Thailand)", 'thrive-cb' ),
			"tlh"   => __( "Klingon (Qo’noS)", 'thrive-cb' ),
			"tr"    => __( "Turkish (Turkey)", 'thrive-cb' ),
			"uk"    => __( "Ukrainian (Ukraine)", 'thrive-cb' ),
			"vi"    => __( "Vietnamese (Vietnam)", 'thrive-cb' ),
			"zh-cn" => __( "Chinese (Simplified, China)", 'thrive-cb' ),
			"zh-tw" => __( "Chinese (Traditional, Taiwan)", 'thrive-cb' ),
		);

		$result = array();
		foreach ( $languages as $code => $name ) {
			$result[ $code ] = array(
				"value" => $code,
				"name"  => $name,
			);
		}

		return $result;
	}
}

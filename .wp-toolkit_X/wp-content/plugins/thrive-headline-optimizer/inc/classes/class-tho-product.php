<?php

class Tho_Product extends TVE_Dash_Product_Abstract {
	protected $tag = 'tho';

	protected $title = 'Thrive Headline Optimizer';

	protected $productIds = array();

	protected $type = 'plugin';

	public function __construct( $data = array() ) {
		parent::__construct( $data );

		$this->logoUrl      = THO_ADMIN_URL . '/img/tho-logo-icon.png';
		$this->logoUrlWhite = THO_ADMIN_URL . '/img/tho-logo-icon-white.png';

		$this->description = __( 'Generate reports to find out how well your site is performing.', THO_TRANSLATE_DOMAIN );

		$this->button = array(
			'active' => true,
			'url'    => admin_url( 'admin.php?page=tho_admin_dashboard' ),
			'label'  => __( 'Thrive Headline Optimizer', THO_TRANSLATE_DOMAIN )
		);

		$this->moreLinks = array(
			'tutorials' => array(
				'class'      => '',
				'icon_class' => 'tvd-icon-graduation-cap',
				'href'       => 'https://help.thrivethemes.com/en/collections/2561613-thrive-headline-optimizer',
				'target'     => '_blank',
				'text'       => __( 'Tutorials', THO_TRANSLATE_DOMAIN ),
			),
			'support'   => array(
				'class'      => '',
				'icon_class' => 'tvd-icon-life-bouy',
				'href'       => 'https://thrivethemes.com/support/',
				'target'     => '_blank',
				'text'       => __( 'Support', THO_TRANSLATE_DOMAIN ),
			),
		);
	}

	public static function reset_plugin() {
		global $wpdb;



		$tables = array(
			'event_log',
			'tests',
			'test_items',
		);
		foreach ( $tables as $table ) {
			$table_name = tho_table_name( $table );
			$sql        = "TRUNCATE TABLE $table_name";
			$wpdb->query( $sql );
		}

		$wpdb->query(
			"DELETE FROM $wpdb->options WHERE 
						`option_name` LIKE '%tho_%' or '%thrive_headline%';"
		);

		$wpdb->query(
			"DELETE FROM $wpdb->postmeta WHERE 
						`meta_key` LIKE '%tho_%';"
		);
	}

}

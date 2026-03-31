<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists("cmplz_dataleak")) {
    class cmplz_dataleak
    {
        private static $_this;
        public $position;
        public $cookies = array();
        public $total_steps;
        public $total_sections;
        public $page_url;

        function __construct()
        {
            if (isset(self::$_this))
	            wp_die(sprintf('%s is a singleton class and you cannot create a second instance.', get_class($this)));

            self::$_this = $this;
			add_filter( 'cmplz_do_action', array( $this, 'get_databreach_data' ), 10, 3 );
	        add_filter("cmplz_tools_databreaches", array($this, 'tools_databreaches') );

	        add_action('init', array($this, 'register_post_type' ), 99, 1);
			add_action( 'init', array($this, 'register_regions') );
		}

        static function this()
        {
            return self::$_this;
        }

		public function tools_databreaches($dataBreachDocuments) {
			$docs = get_posts( array(
					'post_type' => 'cmplz-dataleak',
					'post_status' => 'publish',
				)
			);

			foreach ( $docs as $doc ) {
				if ( !COMPLIANZ::$dataleak->dataleak_has_to_be_reported_to_involved($doc->ID) ) {
					continue;
				}
				$dataBreachDocuments[] = [
					'label' => $doc->post_title,
					'value' => COMPLIANZ::$dataleak->download_url($doc->ID),
				];
			}
			return $dataBreachDocuments;
		}

		/**
		 * Get the fields for the data breaches
		 * @return array
		 */

		public function get_fields( $region ): array {

			$type = $this->get_dataleak_type($region);
			$questions = array();
			require_once( __DIR__ . "/type-$type/questions.php" );
			//add a value property for each field
			foreach ($questions as $key => $question) {
				$questions[$key]['value'] = '';
			}
			return $questions;
		}

		/**
		 * Get a list of processors
		 * @param array $data
		 * @param string $action
		 * @param WP_REST_Request $request
		 *
		 * @return []
		 */

		public function get_databreach_data($data, $action, $request): array {
			if ( $action==='get_databreach_reports' ){
				$regions = cmplz_get_regions(false, 'full');
				$data = [
						'documents' => $this->databreach_reports(),
						'regions' => $regions,
				];
			} else if ($action === 'get_databreach_report_fields'){
				$region = $request->get_param('region');
				$data = ['fields' => $this->get_fields($region)];
			} else if ($action === 'save_databreach_report' ) {
				$fields = $request->get_param('fields');
				$post_id = (int) $request->get_param('post_id');
				$region = (string) $request->get_param('region');
				$post_id = $this->save($fields, $region, $post_id);

				$data = [
					'success' => true,
					'post_id' => $post_id,
					'conclusions' => $this->get_conclusions($post_id),
				];
			} else if ($action === 'delete_databreach_report' ) {
				$documents = $request->get_param('documents');
				foreach ($documents as $document) {
					$this->delete( $document['id'] );
				}
				$data = ['success' => true];
			} else if ($action === 'load_databreach_report') {
				$post_id = (int) $request->get_param('id');
				$region = $this->get_region($post_id);
				$field_region = $region === 'eu' ? '' : "-$region";
				$fields = $this->get_fields($region);
				foreach ($fields as $key => $field) {
					$fields[$key]['value'] = get_post_meta($post_id, $field['id'], true);
				}
				$serviceName = get_post_meta($post_id, 'name_of_processor'.$field_region, true);
				$data = [
						'fields' => $fields,
						'serviceName' => $serviceName,
						'region' => $region,
						'file_name' =>  get_the_title($post_id),
				];
			}
			return $data;
		}

	    public function get_document_elements($region){
		    $type = $this->get_dataleak_type($region);
		    $elements = array();
		    require_once( __DIR__ . "/type-$type/report.php" );
			return $elements[$region];
	    }

		/**
		 * Delete a post by post id
		 *
		 * @param int $post_id
		 *
		 * @r
		 * eturn void
		 */
		private function delete($post_id): void {
			if (!cmplz_user_can_manage()) {
				return;
			}
			$post = get_post($post_id);
			if ($post->post_type !== 'cmplz-dataleak') {
				return;
			}
			wp_delete_post($post_id, true);
		}



		public function download_url($post_id): string {
			if (!cmplz_user_can_manage()) {
				return '';
			}
			return CMPLZ_URL . 'pro/pdf.php?nonce=' . wp_create_nonce("cmplz_pdf_nonce") .'&region=' . $this->get_region($post_id). '&post_id=' . $post_id . '&token=' . time();
		}

		/**
		 * Get the region for a post id, based on the post type.
		 *
		 * @param int $post_id
		 *
		 * @return string|bool $region
		 * */

		public function get_region( int $post_id ) {
			$term = wp_get_post_terms( $post_id, 'cmplz-region' );
			if ( is_wp_error( $term ) ) {
				return false;
			}

			if ( isset( $term[0] ) ) {
				return $term[0]->slug;
			}

			return false;
		}
		/**
		 * Get list of processing agreements
		 * @return array
		 */
		public function databreach_reports(): array {
			if (!cmplz_user_can_manage()) {
				return [];
			}
			$args = array(
					'post_type' => 'cmplz-dataleak',
					'numberposts' => -1,
			);
			$posts =  get_posts($args);
			$output = [];
			foreach ($posts as $post ) {
				$region = $this->get_region( $post->ID );
				$download_url = $this->dataleak_has_to_be_reported_to_involved($post->ID) ? $this->download_url($post->ID) : '';
				$output[] = 					[
						'id' => $post->ID,
						'title' => $post->post_title,
						'region' =>  $region,
						'date' => date( get_option( 'date_format' ), strtotime( $post->post_date ) ),
						'edit_url' => get_edit_post_link($post->ID),
						'download_url' => $download_url,
						'has_to_be_reported' => $this->dataleak_has_to_be_reported_to_involved($post->ID),
				];
			}
			return $output;
		}

		/**
		 * Get the type of dataleak
		 * @param string $region
		 *
		 * @return false|mixed
		 */
		public function get_dataleak_type( $region ){
			return COMPLIANZ::$config->regions[strtolower($region)]['dataleak_type'];
		}

		/**
		 * Generate a conclusion
		 *
		 * @param int $post_id
		 * @return array
		 */
		public function get_conclusions(int $post_id): array {
			if (!cmplz_user_can_manage()) {
				return [];
			}
			$region = $this->get_region($post_id);
			$dataleak_type = $this->get_dataleak_type($region);
			$dpo = array(
					'eu' => array(
							'label'     => __( 'data protection authority', 'complianz-gdpr' ),
					),
					'uk' => array(
							'label'     => __( "Information Commissioner's Office", 'complianz-gdpr' ),
							'url' 		=> 'https://ico.org.uk/for-organisations/report-a-breach/',
					),
					'us' => array(
							'label'     => __( 'Attorney General', 'complianz-gdpr' ),
					),
					'ca' => array(
							'label'     => __( 'data protection authority', 'complianz-gdpr' ),
							'url' 		=> 'https://www.priv.gc.ca/en/report-a-concern/report-a-privacy-breach-at-your-organization/report-a-privacy-breach-at-your-business/',
					),
					'au' => array(
							'label'     => __( 'Australian Information Commissioner', 'complianz-gdpr' ),
							'url' 		=> 'https://forms.business.gov.au/smartforms/servlet/SmartForm.html?formCode=OAIC-NDB&tmFormVersion=10.0',
							'time'		=> __( '72 hours', 'complianz-gdpr' ),
					),
					'za' => array(
							'label'     => __( 'Information Regulator', 'complianz-gdpr' ),
							'url' 		=> 'https://www.justice.gov.za/inforeg/',
					),
					'br' => array(
							'label'		=> __( 'National Data Protection Authority', 'complianz-gdpr' ),
							'url'		=> 'https://www.gov.br/secretariageral/pt-br/sei-peticionamento-eletronico',
							'time'		=> __( '48 hours', 'complianz-gdpr' ),
					),
			);
			$report_dpo = '';
			if (isset($dpo[$region]['label'])){
				$dpo_text = isset($dpo[$region]['url']) ? "<a target='_blank' href='". $dpo[$region]['url'] ."'>". $dpo[$region]['label'] ."</a>" : $dpo[$region]['label'];
				$report_dpo = __( "Please report this incident to the", 'complianz-gdpr' ) . ' ' . $dpo_text;
				$report_dpo .= isset($dpo[$region]['time']) ? ' ' . cmplz_sprintf(__("within %s after the incident occurred", 'complianz-gdpr'), $dpo[$region]['time']) . '.' : '.';
			}

			// Defaults for the dataleak conclusions
			$conclusions = array(
					'report' => array(
							'check_text' 	=> cmplz_sprintf( __( 'Checking if you should report to the %s.', 'complianz-gdpr' ), $dpo[$region]['label']),
							'report_text' 	=>  cmplz_sprintf(__( 'The security incident does not have to be reported to the %s.', 'complianz-gdpr' ), $dpo[$region]['label']),
							'report_status' => 'success',
					),
					'report_to_involved' => array(
							'check_text' 	=> __( 'Checking if you should report to those involved.', 'complianz-gdpr' ),
							'report_text' 	=> __( 'It is not necessary to inform those involved.', 'complianz-gdpr' ),
							'report_status' => 'success',
					),
			);

			// Dataleak type specific URLs and text to help report the incident
			if ($this->dataleak_has_to_be_reported($post_id)) {
				$conclusions['report']['report_text'] = $report_dpo;
				$conclusions['report']['report_status'] = 'error';

				if ($this->dataleak_has_to_be_reported_to_involved($post_id)) {
					$conclusions['report_to_involved']['report_status'] = 'error';
					$conclusions['report_to_involved']['report_text'] = __("You should report this incident to those involved.", 'complianz-gdpr');
					$conclusions['report_to_involved']['report_text'] .= ' ' . __("You can use the generated report to inform those involved.", 'complianz-gdpr');
					if (!$post_id) $conclusions['report_to_involved']['report_text'] .= ' ' . __("Click view document to save and view this report.", 'complianz-gdpr');
				}

				// Can reduse risk for CA
				if (get_post_meta($post_id, 'can-reduce-risk-'. $region, true )==='yes' && $region === 'ca'){
					$conclusions['can_reduce_risk']['report_text'] = __("You should make a notice to the organizations that may be able to reduce the risk of harm from the breach or to mitigate that harm.", 'complianz-gdpr');
					$conclusions['can_reduce_risk']['report_status'] = 'warning';
				}

				if ( $dataleak_type == '2' ) {
					$reach_large = get_post_meta($post_id, 'reach-of-dataloss-large-'. $region, true) === 'yes';
					$california_visitors = get_post_meta($post_id, 'california-visitors', true )  === 'yes';
					$login_credentials = get_post_meta( $post_id, 'what-information-was-involved-'. $region, true) === 'username-email';
					if ( $login_credentials ) {
						$conclusions['login_credentials']['report_text'] = __("In this particular case where login credentials of an email account are involved, it is not allowed to send the security breach notification to that email address.", 'complianz-gdpr');
						$conclusions['login_credentials']['report_status'] = 'error';
					}
					if ( $california_visitors ) {
						$conclusions['california']['report_text'] = __("The databreach concerns California residents, which means the databreach has to be reported to the Attorney General.", 'complianz-gdpr');
						$conclusions['california']['report_status'] = 'error';
					}
					if ( $reach_large ) {
						$conclusions['reach_of_data']['report_text'] = __("Considering the scale of the databreach, it is recommended to get legal counsel regarding this databreach.", 'complianz-gdpr');
						$conclusions['reach_of_data']['report_status'] = 'warning';
					}
				}
			}
			// add check texts if they are empty
			foreach($conclusions as $key => $conclusion){
				if (!isset($conclusion['check_text'])){
					$conclusions[$key]['check_text'] = __("Checking databreach laws matching your setup.", 'complianz-gdpr');
				}
			}
			//strip array keys
			$conclusions = empty($conclusions) ? []: array_values($conclusions);

			return $conclusions;
		}

		/**
		 * Check if dataleak has to be reported to those involved.
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 */
        public function dataleak_has_to_be_reported_to_involved( int $post_id): bool {
			$region        = $this->get_region($post_id);
			$dataleak_type = (int) $this->get_dataleak_type($region);
			$risk_of_dataloss = (int) get_post_meta( $post_id, 'risk-of-data-loss-' . $region, true );
			$type_of_dataloss = (int) get_post_meta( $post_id, 'type-of-dataloss-' . $region, true );
			$what_information_was_involved = get_post_meta($post_id, 'what-information-was-involved-'. $region, true);

			if ( $dataleak_type === 1 ) {
				return $risk_of_dataloss  === 3;
            }

			if ( $dataleak_type == 2 ) {
				if ($type_of_dataloss === 3 ) {
					return false;
				}
				if ( $what_information_was_involved === 'none' ) return false;
			}

			if ( $dataleak_type == 3 ) {
				if ( $type_of_dataloss === 3 ) {
					return false;
				}
				return $risk_of_dataloss !== 3 && $risk_of_dataloss !== 4;
			}

            return true;
        }

		/**
		 * Check if a dataleak has to be reported
		 *
		 * @param int $post_id
		 *
		 * @return bool
		 *
		 * Databreach type 1: EU, UK
		 * Databreach type 2: US
		 * Databreach type 3: CA, AU, ZA
		 *
		 */
        public function dataleak_has_to_be_reported( int $post_id): bool {
			$region = $this->get_region($post_id);
			$dataleak_type = (int) $this->get_dataleak_type($region);
			$type_of_dataloss = (int) get_post_meta( $post_id, 'type-of-dataloss-' . $region, true );
			$what_information_was_involved = get_post_meta( $post_id, 'what-information-was-involved-' . $region, true );
			$riskofabuse = (int) get_post_meta( $post_id, 'risk-of-data-loss-' . $region, true );
			$sensitive = (int) get_post_meta( $post_id, 'risk-of-data-loss-' . $region, true );
			$type_of_dataloss_not_serious = (int) get_post_meta( $post_id, 'type-of-dataloss-' . $region, true ) === 3;
			$reach_of_dataloss_minor = (int) get_post_meta( $post_id, 'reach-of-dataloss-' . $region, true ) === 3;

			if ( $dataleak_type === 1 ) {
                if ( $type_of_dataloss_not_serious ) {
                	return false;
				}
                if ( $reach_of_dataloss_minor ){
					return false;
				}
            }

            if ( $dataleak_type === 2){
				if ($type_of_dataloss === 3 ) {
					return false;
				}
                return $what_information_was_involved !=='none';
            }

	        if ( $dataleak_type === 3 ) {
	        	if  ($type_of_dataloss === 3 ) {
					return false;
				}

				if ( $riskofabuse!==1 && $sensitive!==2 ) {
					return false;
				}
	        }

			return true;
        }


		/**
		 * Save a databreach report
		 * @return int
		 */
        public function save( $fields, string $region, int $post_id)
        {
            if (!cmplz_user_can_manage()) {
				return 0;
			}

			$date = cmplz_localize_date( time() );
			$args = array(
				'post_status' => 'publish',
				'post_title' => cmplz_sprintf(__("Data breach report %s", 'complianz-gdpr'), $date),
				'post_type' => 'cmplz-dataleak',
			);
			//create new post type, and add all wizard data as meta fields.
			if ($post_id===0) {
				//create new post type processing, and add all wizard data as meta fields.
				$post_id = wp_insert_post($args);
			} else {
				$args['ID'] = $post_id;
				wp_update_post($args);
			}
			$this->set_region($post_id, $region);
			//get all fields for this page
			foreach ($fields as $field) {
				$id    = sanitize_title( $field['id'] );
				$value = cmplz_sanitize_field( $field['value'], $field['type'], $id );
				update_post_meta( $post_id, $field['id'], $value );
			}
			return $post_id;
        }

		/**
		 * Set the region in a post
		 *
		 * @param int    $post_id
		 * @param string $region
		 */

		public function set_region( int $post_id, string $region ): void {
			$region = sanitize_title( $region );
			$term = get_term_by( 'slug', $region, 'cmplz-region' );
			if ( ! $term ) {
				wp_insert_term( COMPLIANZ::$config->regions[ $region ]['label'],
						'cmplz-region', array(
								'slug' => $region,
						) );
				$term = get_term_by( 'slug', $region, 'cmplz-region' );
			}

			if ( empty( $term ) ) {
				return;
			}

			$term_id = $term->term_id;
			wp_set_object_terms( $post_id, array( $term_id ), 'cmplz-region' );
		}

		/**
		 * add custom post type
		 */
		public function register_post_type()
		{
			register_post_type(
					"cmplz-dataleak", //post name to use in code
					array(
							'labels' => array(
									'name' => __('Dataleaks', 'complianz-gdpr'),
									'add_new' => __('Add new', 'complianz-gdpr'),
									'add_new_item' => __('Add new', 'complianz-gdpr'),
									'parent_item_colon' => __('Dataleak', 'complianz-gdpr'),
									'parent' => 'Dataleak parent item',
							),

							'rewrite' => array(
									'slug' => "dataleak",
									'pages' => true
							),
							'exclude_from_search' => true,
							'supports' => array(
									'title',
									'author',
								//'page-attributes'
							),
							'publicly_queryable' => false,
							'query_var' => false,
							'public' => true,
							'has_archive' => false,
							'taxonomies' => array('region'),
							'hierarchical' => false,
							'map_meta_cap' => true, //enable capability handling
							'capabilities' => array(
									'create_posts' => 'do_not_allow',
									'delete_post' => true,
							),
							'show_in_menu' => false

					)
			);
		}

		/**
		 * Register region taxonomy
		 */
		function register_regions() {
			register_taxonomy(
					'cmplz-region',
					array('cmplz-dataleak'),
					array(
							'label' => __( 'Region', 'complianz-gdpr'),
							'publicly_queryable' => false,
							'hierarchical' => true,
							'show_ui' => false,
							'capabilities'      => array(
									'assign_terms' => apply_filters('cmplz_capability','manage_privacy'),
									'edit_terms'   => 'NOT_EXISTING_CAPABILITY',
									'manage_terms' => 'NOT_EXISTING_CAPABILITY',
							),
							'show_in_nav_menus' => false,
							'show_in_rest' => false,
							'rewrite' => array( 'slug' => 'region' ),
					)
			);
		}

	}
} //class closure

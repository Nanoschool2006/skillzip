<?php

class FrmAPIEntriesController extends WP_REST_Controller {

	protected $rest_base = 'entries';

	/**
	 * @return void
	 */
	public function register_routes() {

		$posts_args = $this->get_item_args();

		$entry_routes = array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'            => $posts_args,
			),
			array(
				'methods'         => WP_REST_Server::CREATABLE,
				'callback'        => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'            => $posts_args,
			),
			array(
				'methods'         => WP_REST_Server::EDITABLE,
				'callback'        => array( $this, 'update_item_wo_id' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'            => $posts_args,
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		);

		register_rest_route( FrmAPIAppController::$v2_base, '/' . $this->rest_base, $entry_routes );

		// /form/#/entries route works the same as /entries?form_id=#
		register_rest_route( FrmAPIAppController::$v2_base, '/forms/(?P<form_id>[\w-]+)/' . $this->rest_base, $entry_routes );

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				array(
					'methods'         => WP_REST_Server::READABLE,
					'callback'        => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'            => $posts_args,
				),
				array(
					'methods'         => WP_REST_Server::EDITABLE,
					'callback'        => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'edit_item_permissions_check' ),
					'args'            => $posts_args,
				),
				array(
					'methods'         => WP_REST_Server::DELETABLE,
					'callback'        => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'            => $posts_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * @return array
	 */
	protected function get_item_args() {
		$posts_args = array(
			'form_id'               => array(
				'default'           => 0,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'page'                  => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'page_size'             => array(
				'default'           => 25,
				'sanitize_callback' => 'absint',
			),
			'order'                 => array(
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_text_field',
				'enum'              => array( 'asc', 'desc' ),
			),
			'order_by'              => array(
				'default'           => 'id',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'                => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'start_date'            => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'end_date'              => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		return $posts_args;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function prepare_items_query( $request ) {

		$prepared_args = array(
			'is_draft' => 0,
		);

		if ( ! empty( $request['form_id'] ) ) {
			if ( ! is_numeric( $request['form_id'] ) ) {
				$request['form_id'] = FrmForm::get_id_by_key( $request['form_id'] );
			}
			$prepared_args['form_id'] = $request['form_id'];

			if ( ! empty( $request['search'] ) && class_exists( 'FrmProEntriesHelper' ) ) {
				$new_ids = FrmProEntriesHelper::get_search_ids(
					$request['search'],
					$request['form_id'],
					array(
						'is_draft' => $prepared_args['is_draft'],
					)
				);
				$prepared_args['it.id'] = $new_ids;
			}
		}

		if ( ! empty( $request['search'] ) ) {
			$_GET['frm_search'] = $request['search'];
		}

		if ( ! empty( $request['start_date'] ) ) {
			$prepared_args['it.created_at >'] = date( 'Y-m-d H:i:s', strtotime( $request['start_date'] ) );
		}

		if ( ! empty( $request['end_date'] ) ) {
			$prepared_args['it.created_at <'] = date( 'Y-m-d H:i:s', strtotime( $request['end_date'] ) );
		}

		return $prepared_args;
	}

	/**
	 * @return array
	 */
	public function get_items( $request ) {
		$prepared_args = $this->prepare_items_query( $request );
		if ( isset( $prepared_args['it.id'] ) && empty( $prepared_args['it.id'] ) ) {
			$entries = array();
		} else {
			list( $order, $limit ) = FrmAPIAppHelper::prepare_order_and_limit( $request );
			$entries = FrmEntry::getAll( $prepared_args, $order, $limit, false, false );
		}

		$item_form_id = 0;
		$fields = array();
		$data = array();
		foreach ( $entries as $obj ) {

			if ( $item_form_id != $obj->form_id ) {
				$fields = FrmField::get_all_for_form( $obj->form_id, '', 'include' );
				$item_form_id = $obj->form_id;
			}

			$meta = FrmEntriesController::show_entry_shortcode(
				array(
					'format' => 'array',
					'include_blank' => true,
					'id' => $obj->id,
					'user_info' => false,
					'fields' => $fields,
				)
			);
			$obj->meta = $meta;

			$status = $this->prepare_item_for_response( $obj, $request );
			$data[ $obj->item_key ] = $this->prepare_response_for_collection( $status );
		}
		unset( $fields );

		return $data;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response|array
	 */
	public function get_item( $request ) {
		if ( ! method_exists( 'FrmEntriesController', 'show_entry_shortcode' ) ) {
			return array();
		}

		$entry = $this->get_item_object( $request['id'] );

		if ( is_wp_error( $entry ) ) {
			$data = $entry;
		} else {
			$data = $this->prepare_item_for_response( $entry, $request );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * @param string[] $atts
	 */
	private function get_item_object( $id, $atts = array() ) {
		$entry = FrmEntry::getOne( $id );

		if ( empty( $entry ) ) {
			return new WP_Error( 'frmapi_not_found', __( 'Nothing was found with that id', 'frmapi' ), array( 'status' => 409 ) );
		}

		$shortcode_atts = array(
			'format'        => 'array',
			'include_blank' => true,
			'id'            => $id,
			'user_info'     => false,
			'child_array'   => true,
			'date_format'   => 'Y-m-d',
		);

		$meta = FrmEntriesController::show_entry_shortcode( array_merge( $shortcode_atts, $atts ) );
		$entry->meta = $meta;

		return $entry;
	}

	/**
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function create_item( $request ) {
		add_filter( 'frm_create_cookies', '__return_false' );
		add_filter( 'frm_stop_file_switching', '__return_true' );

		$response = $this->validate_create_entry( $request );
		if ( ! empty( $response ) ) {
			return $response;
		}

		if ( $this->is_test( $request ) ) {
			return array( 'success' => 1, 'entry_id' => 'test' );
		}

		$response = $this->create_validated_entry( $request );

		if ( $response instanceof WP_REST_Response ) {
			$data = $response->get_data();
			FrmEntriesController::delete_entry_after_save( array( 'entry_id' => $data['id'], 'form' => FrmForm::getOne( $data['form_id'] ) ) );
		}

		return rest_ensure_response( $response );
	}

	/**
	 * @since 1.03
	 *
	 * @param WP_REST_Request $request
	 */
	protected function validate_create_entry( $request ) {
		$data = $request;

		if ( empty( $data['form_id'] ) ) {
			if ( $this->is_test( $data ) ) {
				return array( 'success' => 1 );
			}
			return new WP_Error( 'frmapi_no_form_id', __( 'Missing form id', 'frmapi' ), array( 'status' => 409 ) );
		}

		global $wpdb;
		$form_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_forms WHERE id=%d OR form_key=%s", $data['form_id'], $data['form_id'] ) );
		if ( ! $form_id ) {
			return new WP_Error( 'frmapi_invalid_form_id', sprintf( __( 'Invalid form id %s', 'frmapi' ), $data['form_id'] ), array( 'status' => 409 ) );
		}

		if ( isset( $data['entry_id'] ) && is_numeric( $data['entry_id'] ) ) {
			// if entry_id is included, then we are editing
			return $this->update_item_wo_id( $data );
		}

		$new_entry = $data->get_params();
		$new_entry['form_id'] = $form_id;

		if ( ! isset( $new_entry['item_meta'] ) && isset( $new_entry['meta'] ) ) {
			$new_entry['item_meta'] = $new_entry['meta'];
			unset( $new_entry['meta'] );
		}

		$new_entry = $this->format_field_value( $form_id, $new_entry );

		// allow nonce since we've already validated
		$new_entry[ 'frm_submit_entry_' . $form_id ] = wp_create_nonce( 'frm_submit_entry_nonce' );
		$this->add_skip_recaptcha_filter();
		add_filter( 'frm_run_antispam', '__return_false' );
		$_POST = $new_entry;

		$this->load_registration_hooks();

		$errors = FrmEntryValidate::validate( $new_entry, false );
		if ( ! empty( $errors ) ) {
			return new WP_Error( 'frmapi_validate_entry', $errors, array( 'status' => 409 ) );
		}

		return array();
	}

	/**
	 * Prevent an error in a form that includes a recaptcha
	 */
	public function skip_recaptcha( $hidden, $field ) {
		$field_type = FrmField::get_field_type( $field );
		if ( 'captcha' === $field_type ) {
			$hidden = true;
		}
		return $hidden;
	}

	/**
	 * Prevent an error in a form that includes a password field.
	 *
	 * @since 1.09
	 *
	 * @param bool  $hidden This value if `true` if this field is treated as hidden field.
	 * @param array $field  Field data.
	 *
	 * @return bool
	 */
	public function skip_password( $hidden, $field ) {
		$field_type = FrmField::get_field_type( $field );
		if ( 'password' === $field_type ) {
			$hidden = true;
		}
		return $hidden;
	}

	/**
	 * @since 1.03
	 *
	 * @param WP_REST_Request $request
	 * @return mixed
	 */
	private function create_validated_entry( $request ) {
		$_POST['frm_skip_cookie'] = true;
		$id = FrmEntry::create( $_POST );

		if ( $id ) {
			$request['id']      = $id;
			$request['context'] = 'edit';
			$response           = $this->get_item( $request );
		} else {
			if ( is_callable( 'FrmAppHelper::get_settings' ) ) {
				// 2.0 compatibility
				$frm_settings = FrmAppHelper::get_settings();
			} else {
				global $frm_settings;
			}
			$response = new WP_Error( 'frmapi_create_entry', $frm_settings->failed_msg, array( 'status' => 409 ) );
		}

		return $response;
	}

	/**
	 * @param WP_REST_Request $data
	 *
	 * @return bool
	 */
	private function is_test( $data ) {
		$headers = $data->get_headers();
		return ( isset( $headers['x_hook_test'] ) && $headers['x_hook_test'][0] );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item_wo_id( $request ) {
		if ( ! is_numeric( $request['entry_id'] ) ) {
			return $this->create_item( $request );
		}

		$request->set_param( 'id', $request['entry_id'] );
		return $this->update_item( $request );
	}

	/**
	 * @return WP_Error|array
	 *
	 * @psalm-return WP_Error|array{success: mixed, entry_id?: mixed}
	 */
	public function update_item( $request ) {
		add_filter( 'frm_stop_file_switching', '__return_true' );

		$entry = $this->get_item_for_update( $request['id'] );
		$_POST['id'] = $entry['id']; // this is used during validation

		$data = $request->get_params();
		$this->combine_entry_and_data( $entry, $data );

		$data['form_id'] = ! empty( $data['form_id'] ) ? $data['form_id'] : $entry['form_id'];
		$data = array_merge( $entry, $data );
		unset( $data['meta'] );

		$data = $this->format_field_value( $data['form_id'], $data );

		$data[ 'frm_submit_entry_' . $data['form_id'] ] = wp_create_nonce( 'frm_submit_entry_nonce' );
		$this->add_skip_recaptcha_filter();
		add_filter( 'frm_run_antispam', '__return_false' );
		$_POST = $data;

		$this->load_registration_hooks( 'update' );

		$errors = FrmEntryValidate::validate( $data, false );
		if ( ! empty( $errors ) ) {
			return new WP_Error( 'frmapi_validate_entry', $errors, array( 'status' => 409 ) );
		}

		$response = array();
		$response['success'] = FrmEntry::update( $entry['id'], $data );
		if ( $response['success'] ) {
			$response['entry_id'] = $entry['id'];
		}

		return $response;
	}

	/**
	 * Applies the correct format to each field value
	 * so that it can be stored correctly in the database.
	 *
	 * @param  array $data
	 *
	 * @return array
	 */
	private function format_field_value( $form_id, $data ) {
		$fields = FrmField::get_all_for_form( $form_id );
		return self::prepare_data( $data, $fields );
	}

	/**
	 * @return array
	 */
	private function get_item_for_update( $id ) {
		$entry = $this->get_item_object( $id, array( 'array_key' => 'id' ) );
		$entry = (array) $entry;
		return $entry;
	}

	/**
	 * @param string $action
	 *
	 * @psalm-param 'create'|'update' $action
	 *
	 * @return void
	 */
	private function load_registration_hooks( $action = 'create' ) {
		if ( class_exists( 'FrmRegEntryController' ) ) {
			$_POST['frm_action'] = $action;
			new FrmRegEntryController();
		}
	}

	/**
	 * @param array $data
	 *
	 * @return void
	 */
	private function combine_entry_and_data( $entry, &$data ) {
		if ( isset( $data[1] ) ) {
			unset( $data[1] );
		}

		if ( ! isset( $data['item_meta'] ) && isset( $data['meta'] ) ) {
			$data['item_meta'] = $data['meta'];
			unset( $data['meta'] );
		}

		$this->switch_displayed_value_to_saved( $entry );

		if ( empty( $data['item_meta'] ) ) {
			$skip = array( 'page', 'page_size', 'order', 'order_by', 'search', 'item_meta' );
			$added = array();
			$data['item_meta'] = $entry['meta'];

			foreach ( $data as $k => $v ) {
				if ( in_array( $k, $skip, true ) ) {
					continue;
				}

				$field_id = 0;

				if ( is_numeric( $k ) ) {
					$field_id = $k;
					unset( $data[ $k ] );
				} elseif ( ! isset( $entry[ $k ] ) ) {
					$field = FrmField::getOne( $k );
					if ( $field ) {
						$field_id = $field->id;
					}
					unset( $field );
				}

				if ( ! empty( $field_id ) ) {
					$added[] = $field_id;
					$data['item_meta'][ $field_id ] = $v;
					$this->prepare_child_data( $field_id, $data['item_meta'] );
				}

				unset( $k, $v );
			}

			$this->clear_extra_child_values( $added, $data['item_meta'] );
		} else {
			// fill in missing values with existing values
			$data['item_meta'] += $entry['meta'];
		}
	}

	/**
	 * We want to use the real data for an entry on update, not a display value.
	 * The meta array may have both a key with "$field_id" and one with "$field-value".
	 * When the "$field-value" key exists, the "$field_id" value is replaced with the "$field-value" value.
	 *
	 * @param array $entry
	 * @return void
	 */
	private function switch_displayed_value_to_saved( &$entry ) {
		$new_meta = $entry['meta'];
		foreach ( $new_meta as $key => $value ) {
			if ( strpos( $key, '-value' ) ) {
				$field_id = str_replace( '-value', '', $key );
				if ( is_numeric( $field_id ) ) {
					$entry['meta'][ $field_id ] = $value;
					unset( $entry['meta'][ $key ] );
				}
			}

			self::switch_display_values_inside_repeaters( $entry, $key, $value );
		}
	}

	/**
	 * @since 1.17
	 *
	 * @param array  $entry
	 * @param string $key   This is used when mapping updated to $entry.
	 * @param mixed  $value This data is checked in a loop.
	 * @return void
	 */
	private static function switch_display_values_inside_repeaters( &$entry, $key, $value ) {
		if ( ! is_array( $value ) || empty( $value['form'] ) ) {
			// Not a repeater value so exit early.
			return;
		}

		foreach ( $value as $repeater_entry_index => $repeater_entry_data ) {
			// Skip keys like 'form' that are not switched.
			if ( 0 !== strpos( $repeater_entry_index, 'i' ) || ! is_array( $repeater_entry_data ) ) {
				continue;
			}

			foreach ( $repeater_entry_data as $repeater_key => $repeater_value ) {
				if ( ! strpos( $repeater_key, '-value' ) ) {
					// Skip regular field ID keys. We are only interested in the "$field_id-value" keys.
					continue;
				}

				$field_id = str_replace( '-value', '', $repeater_key );
				if ( ! is_numeric( $field_id ) ) {
					continue;
				}

				if ( ! isset( $entry['meta'][ $key ][ $repeater_entry_index ] ) ) {
					$entry['meta'][ $key ][ $repeater_entry_index ] = array();
				}

				$entry['meta'][ $key ][ $repeater_entry_index ][ $field_id ] = $repeater_value;
				unset( $entry['meta'][ $key ][ $repeater_entry_index ][ $repeater_key ] );
			}
		}
	}

	/**
	 * @return void
	 */
	private function prepare_child_data( $field_id, &$meta ) {
		$parent_field = $this->parent_field_id( $field_id, $meta );
		if ( empty( $parent_field ) ) {
			return;
		}

		foreach ( $meta[ $parent_field ] as $entry_id => $entry ) {
			$field_is_nested = is_array( $entry ) && isset( $entry[ $field_id ] ) && strpos( $entry_id, 'i' ) === 0;
			if ( $field_is_nested ) {
				$meta[ $parent_field ][ $entry_id ][ $field_id ] = $meta[ $field_id ];
				unset( $meta[ $field_id ] );
				break;
			}
		}
	}

	/**
	 * When a field is embedded, clear the top-level values
	 *
	 * @param array $added
	 *
	 * @return void
	 */
	private function clear_extra_child_values( $added, &$meta ) {
		foreach ( $meta as $field_id => $value ) {
			if ( in_array( $field_id, $added ) ) {
				continue;
			}

			$parent_field = $this->parent_field_id( $field_id, $meta );
			if ( $parent_field ) {
				unset( $meta[ $field_id ] );
			}
		}
	}

	/**
	 * @return int - the parent field id or 0
	 */
	private function parent_field_id( $field_id, $meta ) {
		foreach ( $meta as $field => $value ) {
			if ( ! is_array( $value ) || ! isset( $value['form'] ) ) {
				continue;
			}

			foreach ( $value as $entry_id => $entry ) {
				if ( is_array( $entry ) && isset( $entry[ $field_id ] ) && strpos( $entry_id, 'i' ) === 0 ) {
					return $field;
				}
			}
		}

		return 0;
	}

	public function delete_item( $request ) {
		$id = sanitize_text_field( $request['id'] );

		$get_request = new WP_REST_Request( 'GET', rest_url( '/' . FrmAPIAppController::$v2_base . '/' . $this->rest_base . '/' . $id ) );
		$get_request->set_param( 'context', 'edit' );
		$entry = FrmEntry::getOne( $id );
		if ( empty( $entry ) ) {
			return new WP_Error( 'rest_entry_invalid_id', __( 'Invalid entry ID.', 'frmapi' ), array( 'status' => 404 ) );
		}

		$entry->meta = array();
		$response = $this->prepare_item_for_response( $entry, $get_request );

		$results = FrmEntry::destroy( $entry->id );

		if ( ! $results ) {
			$response = new WP_Error( 'rest_entry_invalid_id', __( 'Invalid entry ID.', 'frmapi' ), array( 'status' => 404 ) );
		}

		return $response;
	}

	public function prepare_item_for_response( $item, $request ) {

		$data = array(
			'id'           => $item->id,
			'item_key'     => $item->item_key,
			'name'         => $item->name,
			'ip'           => $item->ip,
			'meta'         => $item->meta,
			'form_id'      => $item->form_id,
			'post_id'      => $item->post_id,
			'user_id'      => $item->user_id,
			'parent_item_id' => $item->parent_item_id,
			'is_draft'     => $item->is_draft,
			'updated_by'   => $item->updated_by,
			'created_at'   => $item->created_at,
			'updated_at'   => $item->updated_at,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->filter_response_by_context( $data, $context );

		$data = $this->add_additional_fields_to_object( $data, $request );

		// Wrap the data in a response object
		$data = rest_ensure_response( $data );

		return apply_filters( 'rest_prepare_frm_' . $this->rest_base, $data, $item, $request );
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->rest_base,
			'type'       => 'object',
			'properties' => array(
				'id'              => array(
					'description' => 'Unique identifier for the object.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'item_key'        => array(
					'description' => 'An alphanumeric identifier for the object unique to its type.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'            => array(
					'description' => 'The title of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'ip'              => array(
					'description' => 'The IP of the user who created the entry.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_draft'        => array(
					'description' => 'If the entry is a draft or not.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'user_id'         => array(
					'description' => 'The id of the user who created the entry.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'form_id'         => array(
					'description' => 'The id of the form this entry belongs to.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_item_id'  => array(
					'description' => 'The id of the parent entry if this is a repeating or embeded entry.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'post_id'         => array(
					'description' => 'The id of the post this entry created.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'      => array(
					'description' => 'The date the object was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'updated_at'      => array(
					'description' => 'The date the object was updated.',
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'updated_by'      => array(
					'description' => 'The id of the user who last updated the entry.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'meta'            => array(
					'description' => 'The field values for this entry.',
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $schema;
	}

	public function get_item_permissions_check( $request ) {
		// TODO: check if user can edit this entry
		if ( 'edit' === $request['context'] && ! current_user_can( 'frm_edit_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		if ( ! current_user_can( 'frm_view_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function get_items_permissions_check( $request ) {

		if ( 'edit' === $request['context'] && ! current_user_can( 'frm_edit_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		if ( ! current_user_can( 'frm_view_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'frm_create_entries' ) && ! current_user_can( 'administrator' ) ) {
			// TODO: check if anyone can create entries in this form
			$allowed = apply_filters( 'frm_api_allow_create_entry', false, $request );
			if ( ! $allowed ) {
				return new WP_Error(
					'rest_forbidden_context',
					__( 'Sorry, you are not allowed to create entries', 'frmapi' ),
					array( 'status' => 403 )
				);
			}
		}

		return true;
	}

	/**
	 * @return WP_Error|true
	 */
	public function edit_item_permissions_check( $request ) {
		// TODO: check if user can edit this entry
		if ( ! current_user_can( 'frm_edit_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function delete_item_permissions_check( $request ) {
		// TODO: check if user can edit this entry

		if ( ! current_user_can( 'frm_delete_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to delete entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * @param array $entry
	 */
	public static function prepare_data( $entry, $fields ) {
		$set_meta = isset( $entry['item_meta'] ) ? false : true;

		$data = array();
		$possible_data = array( 'id', 'item_key', 'name', 'description', 'ip', 'form_id', 'post_id', 'user_id', 'parent_item_id', 'is_draft', 'updated_by', 'created_at', 'updated_at' );
		foreach ( $possible_data as $possible ) {
			if ( isset( $entry[ $possible ] ) ) {
				$data[ $possible ] = $entry[ $possible ];
			}
		}
		$data['item_meta'] = ( $set_meta ) ? array() : $entry['item_meta'];

		$include = class_exists( 'FrmProAppHelper' );

		foreach ( $fields as $k => $field ) {
			if ( $set_meta ) {
				if ( isset( $entry[ $field->id ] ) ) {
					$data['item_meta'][ $field->id ] = $entry[ $field->id ];
				} else if ( isset( $entry[ $field->field_key ] ) ) {
					$data['item_meta'][ $field->id ] = $entry[ $field->field_key ];
				}
			}

			if ( 'divider' == $field->type ) {
				if ( FrmField::is_option_true( $field, 'repeat' ) && ! isset( $data['item_meta'][ $field->id ]['form'] ) ) {
					$data['item_meta'][ $field->id ]['form'] = $field->field_options['form_select'];
				}
			}

			if ( ! $include || ! isset( $data['item_meta'][ $field->id ] ) ) {
				continue;
			}

			switch ( $field->type ) {
				case 'user_id':
					$data['item_meta'][ $field->id ] = FrmAppHelper::get_user_id_param( trim( $data['item_meta'][ $field->id ] ) );
					$data['frm_user_id'] = $data['item_meta'][ $field->id ];
					break;
				case 'checkbox':
				case 'select':
					if ( ! is_array( $data['item_meta'][ $field->id ] ) ) {
						FrmAPIAppHelper::format_field_value( $field, $data['item_meta'][ $field->id ] );
					}
					break;
				case 'file':
					FrmAPIAppHelper::format_file_id( $data['item_meta'][ $field->id ], $field );
					break;
				case 'data':
				case 'date':
					FrmAPIAppHelper::format_field_value( $field, $data['item_meta'][ $field->id ] );
			}

			unset( $k, $field );
		}

		$data = apply_filters( 'frm_api_prepare_data', $data, $fields );
		return $data;
	}

	/**
	 * Adds skip recaptcha filter.
	 *
	 * @since 1.09
	 *
	 * @return void
	 */
	private function add_skip_recaptcha_filter() {
		add_filter( 'frm_is_field_hidden', array( $this, 'skip_recaptcha' ), 10, 2 );
	}
}

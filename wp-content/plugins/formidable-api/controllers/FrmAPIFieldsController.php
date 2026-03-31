<?php

class FrmAPIFieldsController extends WP_REST_Controller {

	protected $rest_base   = 'fields';
	protected $parent_base = 'forms';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		$posts_args = $this->get_item_args();

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->parent_base . '/(?P<parent_id>[\w-]+)/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $posts_args,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_items' ),
					'permission_callback' => array( $this, 'edit_item_permissions_check' ),
					'args'                => $posts_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'edit_item_permissions_check' ),
					'args'                => $posts_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->parent_base . '/(?P<parent_id>[\w-]+)/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $posts_args,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'edit_item_permissions_check' ),
					'args'                => $posts_args,
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => $posts_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * @return array
	 */
	protected function get_item_args() {
		$posts_args = array();
		return $posts_args;
	}

	/**
	 * @return array
	 */
	public function get_items( $request ) {
		$prepared_args = $this->prepare_items_query( $request );

		if ( is_numeric( $request['parent_id'] ) ) {
			$form_id = $request['parent_id'];
		} else {
			$form_id = FrmForm::get_id_by_key( $request['parent_id'] );
		}
		$fields = FrmField::get_all_for_form( $form_id, '', 'include' );

		$data = array();
		foreach ( $fields as $obj ) {
			$status                  = $this->prepare_item_for_response( $obj, $request );
			$data[ $obj->field_key ] = $this->prepare_response_for_collection( $status );
		}
		return $data;
	}

	/**
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_items( $request ) {
		$form_id = $request['parent_id'];

		foreach ( $request['fields'] as $field ) {
			$f = apply_filters( 'frm_before_field_created', FrmFieldsHelper::setup_new_vars( $field['type'], $form_id ) );
			foreach ( $field as $opt => $val ) {
				$f[ $opt ] = $val;
			}

			$f['form_id'] = $form_id;

			FrmField::create( $f );
		}

		$request['parent_id'] = $form_id;
		$request['context']   = 'edit';
		$response             = $this->get_items( $request );

		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Updates multiple fields at once. All fields data should be placed in 'fields' key.
	 *
	 * @since 1.15
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|array
	 */
	public function update_items( $request ) {
		$parsed_body = $this->maybe_get_parsed_request_body( $request->get_body(), $request->get_content_type() );

		if ( empty( $parsed_body['fields'] ) ) {
			return new WP_Error( 'rest_field_no_fields_data', __( 'No "fields" data found in the request body', 'frmapi' ), array( 'status' => 400 ) );
		}

		$response = array();
		foreach ( $parsed_body['fields'] as $field ) {
			if ( empty( $field['id'] ) ) {
				continue;
			}
			$field_id = $field['id'];
			unset( $field['id'] );

			$success = FrmField::update( $field_id, $field );
			if ( $success ) {
				$response['field_id'][] = $field_id;
			}
		}

		return $response;
	}

	/**
	 * Tries to parse the request body.
	 *
	 * @since 1.15
	 *
	 * @param string $request_body
	 * @param string $content_type
	 *
	 * @return array
	 */
	private function get_parsed_request_body( $request_body, $content_type ) {
		if ( 'application/x-www-form-urlencoded' === $content_type ) {
			parse_str( $request_body, $parsed_body );
		} elseif ( 'text/plain' === $content_type || 'application/json' === $content_type ) {
			$parsed_body = json_decode( $request_body, true );
			if ( $parsed_body ) {
				foreach ( array( 'field_options', 'options' ) as $option ) {
					if ( ! empty( $parsed_body[ $option ] ) ) {
						$parsed_body[ $option ] = (array) $parsed_body[ $option ];
					}
				}
			}
		}

		return ! empty( $parsed_body ) ? $parsed_body : array();
	}

	/**
	 * @since 1.15
	 *
	 * @param WP_REST_Request $request
	 * @return WP_Error|array
	 */
	public function update_item( $request ) {
		$field_data = $this->prepare_field_data( $request );

		if ( empty( $request['id'] ) && empty( $field_data['id'] ) ) {
			return new WP_Error( 'rest_field_no_id', __( 'Field id not provided.', 'frmapi' ), array( 'status' => 400 ) );
		}
		$field_id = ! empty( $request['id'] ) ? $request['id'] : $field_data['id'];

		$field = FrmField::getOne( $field_id );
		if ( empty( $field ) ) {
			return $this->get_invalid_field_error();
		}

		$response = array();

		$response['success'] = FrmField::update( $field->id, $field_data );
		if ( $response['success'] ) {
			$response['field_id'] = $field->id;
		}

		return $response;
	}

	/**
	 * @since 1.15
	 *
	 * @param string     $request_body
	 * @param array|null $content_type
	 * @return array
	 */
	private function maybe_get_parsed_request_body( $request_body, $content_type ) {
		if ( ! empty( $content_type['value'] ) ) {
			return $this->get_parsed_request_body( $request_body, $content_type['value'] );
		}

		return array();
	}

	/**
	 * Prepares field data from the request params and request body.
	 *
	 * @since 1.15
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	private function prepare_field_data( $request ) {
		$request_params = $request->get_params();
		$parsed_body    = $this->maybe_get_parsed_request_body( $request->get_body(), $request->get_content_type() );
		$field_data     = array_merge( $request_params, $parsed_body );

		unset( $field_data['parent_id'], $field_data['id'] );

		return $field_data;
	}

	public function get_item( $request ) {
		$obj      = FrmField::getOne( $request['id'] );
		$data     = $this->prepare_item_for_response( $obj, $request );
		$response = rest_ensure_response( $data );
		return $response;
	}

	/**
	 * Returns true if a field is directly the form's child or inside a repeater field in that form.
	 *
	 * @since 1.14
	 *
	 * @param object     $field   The field object.
	 * @param string|int $form_id The form id.
	 *
	 * @return bool
	 */
	private static function field_belongs_to_form( $field, $form_id ) {
		if ( (int) $field->form_id === (int) $form_id ) {
			return true;
		}

		$field_form = FrmForm::getOne( $field->form_id );

		// returns true for fields in a repeater.
		return (int) $field_form->parent_form_id === (int) $form_id;
	}

	public function delete_item( $request ) {
		$id      = sanitize_text_field( $request['id'] );
		$form_id = sanitize_text_field( $request['parent_id'] );

		if ( ! FrmForm::getOne( $form_id ) ) {
			return new WP_Error( 'rest_form_invalid_id', __( 'Invalid form ID.', 'frmapi' ), array( 'status' => 404 ) );
		}
		$field = FrmField::getOne( $id );

		if ( ! $field ) {
			return self::get_invalid_field_error();
		}

		if ( ! self::field_belongs_to_form( $field, $form_id ) ) {
			return new WP_Error( 'rest_form_invalid_url', __( 'Invalid URL.', 'frmapi' ), array( 'status' => 404 ) );
		}

		$get_request = new WP_REST_Request( 'GET', rest_url( '/' . FrmAPIAppController::$v2_base . '/' . $this->parent_base . '/' . $form_id . '/' . $this->rest_base . '/' . $id ) );
		$get_request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $field, $get_request );

		if ( FrmField::is_repeating_field( $field ) && ! empty( $field->field_options['form_select'] ) ) {
			self::destroy_fields_in_repeater( $field );
		}
		$results = FrmField::destroy( $id );

		if ( ! $results ) {
			return self::get_invalid_field_error();
		}

		return $response;
	}

	/**
	 * @since 1.14
	 *
	 * @param $field Repeater field.
	 *
	 * @return void
	 */
	private static function destroy_fields_in_repeater( $field ) {
		$repeater_form = FrmForm::getOne( $field->field_options['form_select'] );
		$field_ids     = FrmDb::get_col( 'frm_fields', array( 'form_id' => $repeater_form->id ) );
		foreach ( $field_ids as $field_id ) {
			FrmField::destroy( $field_id );
		}
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function prepare_items_query( $request ) {
		$form_id = $request['parent_id'];
		if ( ! is_numeric( $form_id ) ) {
			$form_id = FrmForm::get_id_by_key( $form_id );
		}

		$prepared_args = array(
			'form_id' => $form_id,
		);

		if ( ! empty( $request['search'] ) ) {
			$prepared_args[] = array(
				'name like'        => $request['search'],
				'description like' => $request['search'],
				'or'               => 1,
			);
		}

		return $prepared_args;
	}

	/**
	 * Returns an error object if invalid field id is used.
	 *
	 * @return WP_Error
	 */
	private static function get_invalid_field_error() {
		return new WP_Error( 'rest_field_invalid_id', __( 'Invalid field ID.', 'frmapi' ), array( 'status' => 404 ) );
	}

	public function prepare_item_for_response( $field, $request ) {
		if ( ! $field ) {
			return self::get_invalid_field_error();
		}

		// Base fields for every post
		$data = array(
			'id'            => $field->id,
			'field_key'     => $field->field_key,
			'name'          => $field->name,
			'description'   => $field->description,
			'type'          => $field->type,
			'default_value' => $field->default_value,
			'options'       => $field->options,
			'field_order'   => $field->field_order,
			'required'      => $field->required,
			'field_options' => $field->field_options,
			'form_id'       => $field->form_id,
			'created_at'    => $field->created_at,
		);

		$schema = $this->get_item_schema();

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->filter_response_by_context( $data, $context );

		$data = $this->add_additional_fields_to_object( $data, $request );

		// Wrap the data in a response object
		$data = rest_ensure_response( $data );

		return apply_filters( 'rest_prepare_frm_' . $this->rest_base, $data, $field, $request );
	}

	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->rest_base,
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => 'Unique identifier for the object.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'field_key'     => array(
					'description' => 'An alphanumeric identifier for the object unique to its type.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'          => array(
					'description' => 'The title of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'description'   => array(
					'description' => 'The description of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'type'          => array(
					'description' => 'The field type of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'default_value' => array(
					'description' => 'The default value for this field.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'options'       => array(
					'description' => 'An array of options for the object.',
					'type'        => 'array',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'field_options' => array(
					'description' => 'An array of options to show in the field.',
					'type'        => 'array',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'field_order'   => array(
					'description' => 'The order of the fields.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'required'      => array(
					'description' => 'If this object is a required field.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'form_id'       => array(
					'description' => 'The id of the form this entry belongs to.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'    => array(
					'description' => 'The date the object was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
			),
		);

		return $schema;
	}

	public function get_item_permissions_check( $request ) {
		if ( current_user_can( 'administrator' ) ) {
			return true;
		}

		if ( 'edit' === $request['context'] && ! current_user_can( 'frm_edit_forms' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit form fields', 'frmapi' ), array( 'status' => 403 ) );
		} elseif ( 'view' === $request['context'] && ! current_user_can( 'frm_view_forms' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to view form fields', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * @return WP_Error|true
	 */
	public function edit_item_permissions_check( $request ) {

		if ( ! current_user_can( 'frm_edit_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to create or edit forms', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public function delete_item_permissions_check( $request ) {

		if ( ! current_user_can( 'frm_delete_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed delete forms', 'frmapi' ), array( 'status' => 403 ) );
		}

		return true;
	}
}

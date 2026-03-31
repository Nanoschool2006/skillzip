<?php

class FrmAPIFormsController extends WP_REST_Controller {

	protected $rest_base = 'forms';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		$posts_args = $this->get_item_args();

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $posts_args,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'edit_item_permissions_check' ),
					'args'                => $posts_args,
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			FrmAPIAppController::$v2_base,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
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
		$posts_args = array(
			'page'           => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'page_size'      => array(
				'default'           => 20,
				'sanitize_callback' => 'absint',
			),
			'order'          => array(
				'default'           => 'ASC',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order_by'       => array(
				'default'           => 'created_at',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'         => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'return'         => array(
				'default'           => 'array',
				'sanitize_callback' => 'sanitize_title',
			),
			'exclude_script' => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'exclude_style'  => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		return $posts_args;
	}

	/**
	 * @return array
	 */
	public function get_items( $request ) {
		$prepared_args = $this->prepare_items_query( $request );

		list( $order, $limit ) = FrmAPIAppHelper::prepare_order_and_limit( $request );
		$forms                 = FrmForm::getAll( $prepared_args, $order, $limit );
		if ( is_object( $forms ) ) {
			$forms = array( $forms );
		}

		$data = array();
		foreach ( $forms as $obj ) {
			if ( $obj->logged_in && ! is_user_logged_in() ) {
				// check if logged out users can view the forms
				continue;
			}

			$status                 = $this->prepare_item_for_response( $obj, $request );
			$data[ $obj->form_key ] = $this->prepare_response_for_collection( $status );
		}
		return $data;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$_POST = $request->get_params();
		$form  = FrmFormsHelper::setup_new_vars( array() );

		$form_id = FrmForm::create( $form );
		if ( ! $form_id ) {
			return new WP_Error( 'frm_create_form', __( 'Form creation failed', 'frmapi' ), array( 'status' => 409 ) );
		}

		foreach ( (array) $request['fields'] as $field ) {
			$f = apply_filters( 'frm_before_field_created', FrmFieldsHelper::setup_new_vars( $field['type'], $form_id ) );
			foreach ( $field as $opt => $val ) {
				$f[ $opt ] = $val;
			}

			$f['form_id'] = $form_id;

			FrmField::create( $f );
			unset( $f, $field );
		}

		$request['id']      = $form_id;
		$request['context'] = 'edit';
		$response           = $this->get_item( $request );

		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * @param array $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		if ( isset( $request['return'] ) && $request['return'] === 'html' ) {
			add_action( 'frm_enqueue_form_scripts', array( 'FrmAPIAppController', 'maybe_initialize_scripts' ), 5 );
			add_filter( 'frm_pre_display_form', array( 'FrmAPIAppController', 'force_ajax_submit' ) );
			add_filter( 'frm_form_object', array( 'FrmAPIAppController', 'force_ajax_submit' ) );

			if ( class_exists( 'FrmProFormState' ) ) {
				FrmProFormState::set_initial_value( 'a', 1 );
			}

			FrmAPIAppController::include_scripts( $request );

			$form     = FrmFormsController::get_form_shortcode( $request->get_params() );
			$form_key = is_numeric( $request['id'] ) ? FrmForm::get_key_by_id( $request['id'] ) : sanitize_text_field( $request['id'] );
			$action   = FrmFormsHelper::get_direct_link( $form_key );
			$form     = str_replace( ' class="frm-show-form ', ' action="' . esc_url( $action ) . '" class="frm-show-form ', $form );
			$data     = array( 'renderedHtml' => $form );
		} else {
			$obj = FrmForm::getOne( $request['id'] );

			if ( ! $obj ) {
				return new WP_Error( 'rest_form_invalid_id', __( 'Invalid form ID.', 'frmapi' ), array( 'status' => 404 ) );
			}

			$data = $this->prepare_item_for_response( $obj, $request );
		}

		$response = rest_ensure_response( $data );
		return $response;
	}

	public function delete_item( $request ) {
		$id = sanitize_text_field( $request['id'] );

		$get_request = new WP_REST_Request( 'GET', rest_url( '/' . FrmAPIAppController::$v2_base . '/' . $this->rest_base . '/' . $id ) );
		$get_request->set_param( 'context', 'edit' );

		$form     = FrmForm::getOne( $id );
		$response = $this->prepare_item_for_response( $form, $get_request );

		$results = FrmForm::destroy( $id );

		if ( ! $results ) {
			return new WP_Error( 'rest_form_invalid_id', __( 'Invalid form ID.', 'frmapi' ), array( 'status' => 404 ) );
		}

		return $response;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	protected function prepare_items_query( $request ) {

		$prepared_args = array(
			'is_template' => 0,
			'status'      => 'published',
		);

		if ( isset( $request['parent_form_id'] ) ) {
			$prepared_args['parent_form_id'] = $request['parent_form_id'];
		}

		if ( ! empty( $request['search'] ) ) {
			$prepared_args[] = array(
				'name like'        => $request['search'],
				'description like' => $request['search'],
				'or'               => 1,
			);
		}

		return $prepared_args;
	}

	public function prepare_item_for_response( $form, $request ) {

		// Base fields for every post
		$data = array(
			'id'             => $form->id,
			'form_key'       => $form->form_key,
			'name'           => $form->name,
			'description'    => $form->description,
			'status'         => $form->status,
			'parent_form_id' => $form->parent_form_id,
			'logged_in'      => $form->logged_in,
			'is_template'    => $form->is_template,
			'options'        => $form->options,
			'editable'       => $form->editable,
			'created_at'     => $form->created_at,
			'link'           => FrmFormsHelper::get_direct_link( $form->form_key, $form ),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->filter_response_by_context( $data, $context );

		$data = $this->add_additional_fields_to_object( $data, $request );

		// Wrap the data in a response object
		$data = rest_ensure_response( $data );
		$data->add_links( $this->prepare_links( $form ) );

		return apply_filters( 'rest_prepare_frm_' . $this->rest_base, $data, $form, $request );
	}

	/**
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->rest_base,
			'type'       => 'object',
			'properties' => array(
				'id'             => array(
					'description' => 'Unique identifier for the object.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'form_key'       => array(
					'description' => 'An alphanumeric identifier for the object unique to its type.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'           => array(
					'description' => 'The title of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description'    => array(
					'description' => 'The description of this object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status'         => array(
					'description' => 'A named status for the object.',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_form_id' => array(
					'description' => 'The parent id for the object for repeating sections.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'logged_in'      => array(
					'description' => 'If login is required to see this form.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'is_template'    => array(
					'description' => 'If this object is a template.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'options'        => array(
					'description' => 'An array of options for the object.',
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'editable'       => array(
					'description' => 'If the entries in the form can be edited.',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'created_at'     => array(
					'description' => 'The date the object was created.',
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'link'           => array(
					'description' => 'URL to the object.',
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}

	/**
	 * @return array
	 */
	public function prepare_links( $form ) {
		$base = '/' . FrmAPIAppController::$v2_base . '/' . $this->rest_base;

		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $form->id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		$links['fields'] = array(
			'href'       => rest_url( trailingslashit( $base ) . $form->id . '/fields' ),
			'embeddable' => true,
		);

		return $links;
	}

	/**
	 * @return WP_Error|true
	 */
	public function get_item_permissions_check( $request ) {

		if ( 'edit' === $request['context'] && ! current_user_can( 'frm_edit_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to edit forms', 'frmapi' ), array( 'status' => 403 ) );
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

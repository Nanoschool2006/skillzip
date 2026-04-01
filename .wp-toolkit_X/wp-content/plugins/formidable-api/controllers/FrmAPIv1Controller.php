<?php
if ( ! defined( 'ABSPATH' )) die( 'You are not allowed to call this page directly.' );

class FrmAPIv1Controller {
	public static $timeout = 15;

	/**
	 * @return void
	 */
	public static function load_hooks() {
		add_action( 'wp_json_server_before_serve', array( __CLASS__, 'default_filters' ), 10, 1 );
	}

	/**
	 * @return void
	 */
	public static function default_filters() {
		add_filter( 'json_endpoints', array( __CLASS__, 'register_routes' ), 0 );
		add_filter( 'json_authentication_errors', array( __CLASS__, 'check_authentication' ), 50 );
		self::force_reauthentication();
	}

	/**
	 * @return array
	 */
	public static function register_routes( $routes ) {
		// posts?filter[posts_per_page]=8&filter[order]=ASC

		$frm_routes = array(
			// Forms
			'/frm/forms' => array(
				array( array( __CLASS__, 'get_forms' ), WP_JSON_Server::READABLE ),
				array( array( __CLASS__, 'create_form' ), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
			),

			'/frm/forms/(?P<id>\d+)' => array(
				array( array( __CLASS__, 'get_form' ), WP_JSON_Server::READABLE ),
				// array( array( __CLASS__, 'update_form' ), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
				array( array( __CLASS__, 'delete_form' ), WP_JSON_Server::DELETABLE ),
			),

			'/frm/forms/(?P<id>\d+)/html' => array(
				array( array( __CLASS__, 'get_form_html' ), WP_JSON_Server::READABLE ),
			),

			'/frm/forms/(?P<id>\d+)/fields' => array(
				array( array( __CLASS__, 'get_fields' ), WP_JSON_Server::READABLE ),
			),

			// Entries
			'/frm/forms/(?P<id>\d+)/entries' => array(
				array( array( __CLASS__, 'get_entries' ), WP_JSON_Server::READABLE ),
				array( array( __CLASS__, 'delete_entries' ), WP_JSON_Server::DELETABLE ),
			),

			'/frm/entries' => array(
				array( array( __CLASS__, 'create_entry' ), WP_JSON_Server::CREATABLE | WP_JSON_Server::ACCEPT_JSON ),
				array( array( __CLASS__, 'update_entry_wo_id' ), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
			),

			'/frm/entries/(?P<id>\d+)' => array(
				array( array( __CLASS__, 'get_entry' ), WP_JSON_Server::READABLE ),
				array( array( __CLASS__, 'update_entry' ), WP_JSON_Server::EDITABLE | WP_JSON_Server::ACCEPT_JSON ),
				array( array( __CLASS__, 'delete_entry' ), WP_JSON_Server::DELETABLE ),
			),

		);

		return array_merge( $routes, $frm_routes );
	}

	/**
	 * Force reauthentication after we've registered our handler
	 *
	 * @return void
	 */
	public static function force_reauthentication() {
		if ( is_user_logged_in() ) {
			// Another handler has already worked successfully, no need to reauthenticate.
			return;
		}

		// Force reauthentication
		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			$user_id = apply_filters( 'determine_current_user', false );
			if ( $user_id ) {
				wp_set_current_user( $user_id );
			}
		}
	}

	public static function check_authentication( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		// only return error if this is an frm route
		if ( ! FrmAPIAppHelper::is_frm_route() ) {
			return $result;
		}

		global $frm_api_error;
		if ( $frm_api_error && is_array( $frm_api_error ) ) {
			return new WP_Error( $frm_api_error['code'], $frm_api_error['message'], array( 'status' => 403 ) );
		}

		if ( 'success' == $frm_api_error || is_user_logged_in() ) {
			return true;
		}

		return $result;
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_forms() {
		if ( ! current_user_can( 'frm_view_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_forms_permission', 'You do not have permission to view forms', array( 'status' => 403 ) );
		}

		// TODO: allow templates too
		$forms = FrmForm::getAll( array('is_template' => 0, 'status' => 'published') );
		return array( 'forms' => $forms );
	}

	/**
	 * @return WP_Error|array
	 */
	public static function create_form( $data ) {
		if ( ! current_user_can( 'frm_edit_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_edit_forms_permission', 'You do not have permission to create forms', array( 'status' => 403 ) );
		}

		$form = FrmFormsHelper::setup_new_vars( array() );
		// $form['options'] = FrmAppHelper::maybe_json_decode($form['options']);

		$form_id = FrmForm::create( $form );
		if ( ! $form_id ) {
			return new WP_Error( 'frm_create_form', 'Form creation failed', array( 'status' => 409 ) );
		}

		foreach ( $data['fields'] as $field ) {
			$f = apply_filters( 'frm_before_field_created', FrmFieldsHelper::setup_new_vars( $field['type'], $form_id ) );
			foreach ( $field as $opt => $val ) {
				$f[ $opt ] = $val;
			}

			$f['form_id'] = $form_id;

			FrmField::create( $f );
			unset( $f, $field );
		}

		unset( $form );

		return array( 'id' => $form_id );
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_form_html( $id ) {
		if ( ! current_user_can( 'frm_view_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_forms_permission', 'You do not have permission to view forms', array( 'status' => 403 ) );
		}

		if ( is_numeric( $id ) ) {
			$shortcode_atts = array( 'id' => $id );
		} else {
			$shortcode_atts = array( 'key' => $id );
		}
		$form = FrmFormsController::get_form_shortcode( $shortcode_atts );
		return array( 'forms' => $form );
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_form( $id ) {
		if ( ! current_user_can( 'frm_view_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_forms_permission', 'You do not have permission to view forms', array( 'status' => 403 ) );
		}

		$form = FrmForm::getOne( $id );
		return array( 'forms' => $form );
	}

	/**
	 * @return WP_Error|string[]
	 */
	public static function delete_form( $id ) {
		if ( ! current_user_can( 'frm_delete_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_delete_forms_permission', 'You do not have permission to delete forms', array( 'status' => 403 ) );
		}

		$results = FrmForm::destroy( $id );
		$response = array();

		if ( $results ) {
			$response['success'] = 'Form successfully deleted';
		} else {
			$response['error'] = 'There is no form with that ID';
		}

		return $response;
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_fields( $id ) {
		if ( ! current_user_can( 'frm_view_forms' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_forms_permission', 'You do not have permission to view forms', array( 'status' => 403 ) );
		}

		if ( is_numeric( $id ) ) {
			$form_id = $id;
		} else {
			$form_id = FrmForm::get_id_by_key( $id );
		}
		$fields = FrmField::get_all_for_form( $form_id, '', 'include' );

		return (array) $fields;
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_entries( $id ) {
		if ( ! current_user_can( 'frm_view_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_entries_permission', 'You do not have permission to view entries', array( 'status' => 403 ) );
		}

		$defaults = array(
			'orderby' => 'id',
			'order' => 'ASC', // DESC|ASC
			'page_start' => 0,
			'page_size' => 25,
		);

		$filters = array();
		foreach ( $defaults as $opt => $val ) {
			$filters[ $opt ] = isset( $_GET[ $opt ] ) ? sanitize_key( $_GET[ $opt ] ) : $val;
			unset( $opt, $val );
		}

		if ( ! in_array( strtoupper( $filters['order'] ), array( 'DESC', 'ASC' ) ) ) {
			$filters['order'] = $defaults['order'];
		}

		// ?Filter1=EntryId+Is_equal_to+1
		// ?Filter1=EntryId+Is_after+1&Filter2=EntryId+Is_before+200&match=AND

		global $wpdb;
		$entry_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_items WHERE (form_id=%d OR item_key=%s) ORDER BY " . $filters['orderby'] . " " . $filters['order'] . " LIMIT %d, %d", $id, $id, $filters['page_start'], $filters['page_size'] ) );
		if ( empty( $entry_ids ) ) {
			return array();
		}

		$entries = self::get_entries_array( $entry_ids );

		return array( 'entries' => $entries );
	}

	/**
	 * Delete all entries in a form.
	 *
	 * @return WP_Error|string[]
	 */
	public static function delete_entries( $id ) {
		if ( ! current_user_can( 'frm_delete_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_delete_entries_permission', 'You do not have permission to delete entries', array( 'status' => 403 ) );
		}

		$form = FrmForm::getOne( $id );

		if ( ! $form ) {
			return new WP_Error( 'frm_invalid_id', 'That form ID is invalid', array( 'status' => 409 ) );
		}

		global $wpdb;
		$entry_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_items WHERE form_id=%d", $form->id ) );
		if ( empty( $entry_ids ) ) {
			return new WP_Error( 'frm_empty', 'There are no entries to delete', array( 'status' => 409 ) );
		}

		foreach ( $entry_ids as $entry_id ) {
			$entry = FrmEntry::getOne( $entry_id, true );
			do_action( 'frm_before_destroy_entry', $entry_id, $entry );
			unset( $entry_id, $entry );
		}

		$wpdb->query( "DELETE em.* FROM {$wpdb->prefix}frm_item_metas as em INNER JOIN {$wpdb->prefix}frm_items as e on (em.item_id=e.id) and form_id=" . (int) $form->id );
		$results = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}frm_items WHERE form_id=%d", $form->id ) );

		if ( ! $results ) {
			return new WP_Error( 'frm_not_deleted', 'The entries were not deleted', array( 'status' => 409 ) );
		}

		return array( 'success' => __( 'Entries were Successfully Destroyed', 'frmapi' ) );
	}

	/**
	 * @return WP_Error|array
	 */
	public static function get_entry( $id ) {
		if ( ! current_user_can( 'frm_view_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_view_entries_permission', 'You do not have permission to view entries', array( 'status' => 403 ) );
		}

		if ( ! method_exists( 'FrmEntriesController', 'show_entry_shortcode' ) ) {
			return array();
		}

		$entry = self::get_entries_array( array( $id ) );

		return array( 'entries' => $entry );
	}

	public static function create_entry( $data ) {
		if ( ! current_user_can( 'frm_create_entries' ) && ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'frm_create_entries_permission', 'You do not have permission to create entries', array( 'status' => 403 ) );
		}

		$response = array();

		if ( ! isset( $data['form_id'] ) ) {
			if ( isset( $data['test'] ) ) {
				return array( 'success' => 1 );
			}
			return new WP_Error( 'frmapi_no_form_id', __( 'Missing form id', 'frmapi' ), array( 'status' => 409 ) );
		}

		global $wpdb;
		$form_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_forms WHERE id=%d OR form_key=%s", $data['form_id'], $data['form_id'] ) );
		if ( ! $form_id ) {
			return new WP_Error( 'frmapi_invalid_form_id', __( 'Invalid form id', 'frmapi' ) . ' ' . $data['form_id'], array( 'status' => 409 ) );
		}

		if ( isset( $data['entry_id'] ) && is_numeric( $data['entry_id'] ) ) {
			// if entry_id is included, then we are editing
			return self::update_entry( $data['entry_id'], $data );
		}

		$data['form_id'] = $form_id;

		if ( ! isset( $data['item_meta'] ) && isset( $data['meta'] ) ) {
			$data['item_meta'] = $data['meta'];
			unset( $data['meta'] );
		}

		$fields = FrmField::getAll( array( 'form_id' => $form_id ) );

		$data = self::prepare_data( $data, $fields );
		unset( $fields );

		// allow nonce since we've already validated
		$data['frm_submit_entry'] = wp_create_nonce( 'frm_submit_entry_nonce' );
		$_POST = $data;

		$errors = FrmEntryValidate::validate( $data, false );
		if ( ! empty( $errors ) ) {
			return new WP_Error( 'frmapi_validate_entry', $errors, array( 'status' => 409 ) );
		}

		if ( isset( $_SERVER['X-Hook-Test'] ) ) {
			return array( 'success' => 1, 'entry_id' => 'test' );
		}

		if ( $id = FrmEntry::create( $_POST ) ) {
			$response['success'] = 1;
			$response['entry_id'] = $id;
			// $response['entry_link'] = FrmAPIAppController::api_base_url() .'/v'. $version .'/entry/'. $id;
			// $response['redirect_url']
		} else {
			if ( is_callable( 'FrmAppHelper::get_settings' ) ) {
				// 2.0 compatability
				$frm_settings = FrmAppHelper::get_settings();
			} else {
				global $frm_settings;
			}
			return new WP_Error( 'frmapi_create_entry', $frm_settings->failed_msg, array( 'status' => 409 ) );
		}
		return $response;
	}

	public static function update_entry_wo_id( $data ) {
		if ( ! isset( $data['entry_id'] ) || ! is_numeric( $data['entry_id'] ) ) {
			return self::create_entry( $data );
		}

		return self::update_entry( $data['entry_id'], $data );
	}

	/**
	 * @param numeric $id
	 *
	 * @return array|WP_Error
	 */
	public static function update_entry( $id, $data ) {
		if ( ! current_user_can( 'frm_edit_entries' ) ) {
			return new WP_Error( 'frm_edit_entries_permission', __( 'You do not have permission to edit entries', 'frmapi' ), array( 'status' => 403 ) );
		}

		$entry = FrmEntry::getOne( $id, true );
		$response = array();

		if ( ! $entry ) {
			$response['error'] = __( 'That entry does not exist' );
			return $response;
		}

		if ( ! isset( $data['item_meta'] ) && isset( $data['meta'] ) ) {
			$data['item_meta'] = $data['meta'];
			unset( $data['meta'] );
		}

		if ( ! isset( $data['item_meta'] ) || empty( $data['item_meta'] ) ) {
			foreach ( $data as $k => $v ) {
				if ( is_numeric( $k ) ) {
					$data['item_meta'][ $k ] = $v;
					unset( $data[ $k ] );
				}
				unset( $k, $v );
			}
		}

		// fill in missing values with existing values
		$data['item_meta'] = $data['item_meta'] + $entry->metas;
		$data = array_merge( (array) $entry, $data );

		$response['success'] = FrmEntry::update( $id, $data );
		if ( $response['success'] ) {
			$response['entry_id'] = $id;
		}

		return $response;
	}

	/**
	 * @return array|WP_Error
	 */
	public static function delete_entry( $id ) {
		if ( ! current_user_can( 'frm_delete_entries' ) ) {
			return new WP_Error( 'frm_delete_entries_permission', 'You do not have permission to delete entries', array( 'status' => 403 ) );
		}

		if ( ! is_numeric( $id ) ) {
			global $wpdb;
			$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}frm_items WHERE item_key=%s", $id ) );
		}

		$response = array();
		if ( ! $id ) {
			$response['error'] = 'That entry does not exist';
		} else {
			$response['success'] = FrmEntry::destroy( $id );
		}

		return $response;
	}

	/**
	 * @return void
	 */
	public static function send_webhooks( $entry, $hook, $type = 'live' ) {
		FrmAPIAppController::send_webhooks( $entry, $hook, $type );
	}

	public static function prepare_data( $data, $fields ) {
		$set_meta = isset( $data['item_meta'] ) ? false : true;

		if ( $set_meta ) {
			$data['item_meta'] = array();
		}

		$include = false;
		if ( class_exists( 'FrmAppHelper' ) ) {
			$file = FrmAppHelper::plugin_path() . '/pro/classes/helpers/FrmProXMLHelper.php';
			if ( file_exists( $file ) ) {
				$include = true;
				include_once( $file );
			}
		}

		foreach ( $fields as $k => $field ) {
			if ( $set_meta ) {
				if ( isset( $data[ $field->id ] ) ) {
					$data['item_meta'][ $field->id ] = $data[ $field->id ];
					unset( $data[ $field->id ] );
				} else if ( isset( $data[ $field->field_key ] ) ) {
					$data['item_meta'][ $field->id ] = $data[ $field->field_key ];
					unset( $data[ $field->field_key ] );
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
						$field_obj = FrmFieldFactory::get_field_object( $field );
						$data['item_meta'][ $field->id ] = $field_obj->get_import_value( $data['item_meta'][ $field->id ] );
					}
				break;
				case 'data':
					$field_obj = FrmFieldFactory::get_field_object( $field );
					$data['item_meta'][ $field->id ] = $field_obj->get_import_value( $data['item_meta'][ $field->id ] );
				break;
				case 'file':
					$field_obj = FrmFieldFactory::get_field_type( 'file' );
					$data['item_meta'][ $field->id ] = $field_obj->get_file_id( $data['item_meta'][ $field->id ] );
				break;
				case 'date':
					FrmAPIAppHelper::format_field_value( $field, $data['item_meta'][ $field->id ] );
				break;
			}

			unset( $k, $field );
		}

		$data = apply_filters( 'frm_api_prepare_data', $data, $fields );
		return $data;
	}

	// PRIVATE

	/**
	 * @param array $ids
	 */
	private static function get_entries_array( $ids ) {
		return FrmAPIAppController::get_entries_array( $ids );
	}

}

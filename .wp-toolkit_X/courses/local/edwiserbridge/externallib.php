<?php

/**
* This class will add the web services for edwiser bridge.
*/

require_once($CFG->libdir . "/externallib.php");
require_once(dirname(__FILE__).'/lib.php');

use core_completion\progress as progress;

class local_edwiserbridge_external extends external_api
{
	public function __construct()
	{
	}


	/**
	 * request to test connection
	 * @param  [type] $wp_url   [description]
	 * @param  [type] $wp_token [description]
	 * @return [type]           [description]
	 */
	public static function eb_test_connection($wp_url, $wp_token)
	{
		$params = self::validate_parameters(
			self::eb_test_connection_parameters(),
			array('wp_url' => $wp_url, "wp_token" => $wp_token)
		);

		$request_data = array(
			'action'     => "test_connection",
			'secret_key' => $params["wp_token"]
		);


		$api_handler = api_handler_instance();
		$response = $api_handler->connect_to_wp_with_args($params["wp_url"], $request_data);
		if (!$response["error"]) {

			$status = $response["data"]->status;
			$msg = $response["data"]->msg;
			if (!$status) {
				$msg = $response["data"]->msg . get_string('wp_test_connection_failed', 'local_edwiserbridge');
			}

		} else {
			$status = 0;
			$msg = $response["msg"];
		}


		return array("status"=> $status, "msg" => $msg);
	}

	public static function eb_test_connection_parameters()
	{
		return new external_function_parameters(
			array(
				'wp_url' => new external_value(PARAM_TEXT, get_string('web_service_wp_url', 'local_edwiserbridge')),
				'wp_token' => new external_value(PARAM_TEXT, get_string('web_service_wp_token', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_test_connection_returns()
	{
		return new external_single_structure(
			array(
				'status'  => new external_value(PARAM_TEXT, get_string('web_service_test_conn_status', 'local_edwiserbridge')),
				'msg'  => new external_value(PARAM_RAW, get_string('web_service_test_conn_msg', 'local_edwiserbridge'))
			)
		);
	}


	/**
	 * functionality to get all site related data.
	 * @param  [type] $site_index [description]
	 * @return [type]             [description]
	 */
	public static function eb_get_site_data($site_index)
	{
		$params = self::validate_parameters(
			self::eb_get_site_data_parameters(),
			array('site_index' => $site_index)
		);
		return get_synch_settings($params['site_index']);
	}

	public static function eb_get_site_data_parameters()
	{
		return new external_function_parameters(
			array(
				'site_index' => new external_value(PARAM_TEXT, get_string('web_service_site_index', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_get_site_data_returns()
	{
		return new external_single_structure(
			array(
				'course_enrollment'  => new external_value(PARAM_INT, get_string('web_service_course_enrollment', 'local_edwiserbridge')),
				'course_un_enrollment'  => new external_value(PARAM_INT,  get_string('web_service_course_un_enrollment', 'local_edwiserbridge')),
				'user_creation'  => new external_value(PARAM_INT,  get_string('web_service_user_creation', 'local_edwiserbridge')),
				'user_deletion'  => new external_value(PARAM_INT, get_string('web_service_user_deletion', 'local_edwiserbridge'))
			)
		);
	}

	/**
	 * Functionality to get course progress.
	 *
	 * @param  string $user_id the user id.
	 * @return array of the course progress.
	 */
	public static function eb_get_course_progress( $user_id ) {
		global $DB, $CFG;

		$params = self::validate_parameters(
			self::eb_get_course_progress_parameters(),
			array( 'user_id' => $user_id )
		);

		$result = $DB->get_records_sql( 'SELECT ctx.instanceid course, count(cmc.completionstate) as completed, count(cm.id) as  outoff
			FROM {user} u
			LEFT JOIN {role_assignments} ra ON u.id = ra.userid and u.id = ?
			JOIN {context} ctx ON ra.contextid = ctx.id
			JOIN {course_modules} cm ON ctx.instanceid = cm.course AND cm.completion > 0
			LEFT JOIN {course_modules_completion} cmc ON cm.id = cmc.coursemoduleid AND u.id = cmc.userid AND cmc.completionstate > 0
			GROUP BY ctx.instanceid, u.id
			ORDER BY u.id',
			array( $params['user_id'] )
		);

		$enrolled_courses  = get_array_of_enrolled_courses( $params['user_id'], 1 );
		$processed_courses = $enrolled_courses;

		$response = array();

		if ( $result && ! empty( $result ) ) {
			foreach ( $result as $key => $value ) {
				$course     = get_course( $value->course );
				$cinfo      = new completion_info( $course );
				$iscomplete = $cinfo->is_course_complete( $params['user_id'] );
				$progress   = $iscomplete ? 100 : ( $value->completed / $value->outoff )*100;
				$response[] = array(
					'course_id'  => $value->course,
					'completion' => $progress,
				);

				$processed_courses = remove_processed_coures( $value->course, $processed_courses );
			}
		}

		if ( ! empty( $processed_courses ) ) {
			foreach ( $processed_courses as $value ) {
				$course     = get_course( $value );
				$cinfo      = new completion_info( $course );
				$iscomplete = $cinfo->is_course_complete( $params['user_id'] );
				$progress   = $iscomplete ? 100 : 0;
				$response[] = array(
					'course_id'  => $value,
					'completion' => $progress,
				);

				$processed_courses = remove_processed_coures( $value, $processed_courses );
			}
		}
		return $response;
	}

	public static function eb_get_course_progress_parameters()
	{
		return new external_function_parameters(
			array(
				'user_id' => new external_value(PARAM_TEXT, '')
			)
		);
	}

	public static function eb_get_course_progress_returns()
	{
		return new external_multiple_structure(
			new external_single_structure(
				array(
					'course_id'   => new external_value(PARAM_TEXT, ''),
					'completion'  => new external_value(PARAM_NUMBER, '')
				)
			)
		);
	}




	/**
	 * functionality to get users in chunk.
	 * @param  [type] $user_id
	 * @return [type]          [description]
	 */
	public static function eb_get_users($offset, $limit, $search_string, $total_users)
	{
		global $DB;

		$params = self::validate_parameters(
			self::eb_get_users_parameters(),
			array('offset' => $offset, "limit" => $limit, "search_string" => $search_string, "total_users" => $total_users)
		);

		$query = "SELECT id, username, firstname, lastname, email FROM {user} WHERE  deleted = 0 AND confirmed = 1 AND username != 'guest' ";

		if (!empty($params['search_string'])) {
			$search_string = "%" . $params['search_string'] . "%";
			$query .= " AND (firstname LIKE '$search_string' OR lastname LIKE '$search_string' OR username LIKE '$search_string')";
		}

		$users = $DB->get_records_sql($query, null, $offset, $limit);
		$user_count = 0;
		if (!empty($params['total_users'])) {
			$user_count = $DB->get_record_sql("SELECT count(*) total_count FROM {user} WHERE  deleted = 0 AND confirmed = 1 AND username != 'guest' ");
			$user_count = $user_count->total_count;
		}

		return array("total_users" => $user_count, "users" => $users);
	}

	public static function eb_get_users_parameters()
	{
		return new external_function_parameters(
			array(
				'offset' => new external_value(PARAM_INT, get_string('web_service_offset', 'local_edwiserbridge')),
				'limit' => new external_value(PARAM_INT, get_string('web_service_limit', 'local_edwiserbridge')),
				'search_string' => new external_value(PARAM_TEXT, get_string('web_service_search_string', 'local_edwiserbridge')),
				'total_users' => new external_value(PARAM_INT, get_string('web_service_total_users', 'local_edwiserbridge')),
			)
		);
	}

	public static function eb_get_users_returns()
	{
		return new external_function_parameters(
			array(
				'total_users' => new external_value(PARAM_INT, ''),
				'users' => new external_multiple_structure(
					new external_single_structure(
						array(
							'id'        => new external_value(PARAM_INT, get_string('web_service_id', 'local_edwiserbridge')),
							'username'  => new external_value(PARAM_TEXT, get_string('web_service_username', 'local_edwiserbridge')),
							'firstname' => new external_value(PARAM_TEXT, get_string('web_service_firstname', 'local_edwiserbridge')),
							'lastname'  => new external_value(PARAM_TEXT, get_string('web_service_lastname', 'local_edwiserbridge')),
							'email'     => new external_value(PARAM_TEXT, get_string('web_service_email', 'local_edwiserbridge'))
						)
					)
				)
			)
		);
	}



	/**
	 * functionality to create new external service 
	 * @param  [type] $user_id
	 * @return [type]          
	 */
	public static function eb_create_service($web_service_name, $user_id)
	{
		$settings_handler = new eb_settings_handler();
		$response = $settings_handler->eb_create_externle_service($web_service_name, $user_id);
		return $response;
	}

	public static function eb_create_service_parameters()
	{
		return new external_function_parameters(
			array(
				'web_service_name' => new external_value(PARAM_TEXT, get_string('web_service_name', 'local_edwiserbridge')),
				'user_id'          => new external_value(PARAM_TEXT, get_string('web_service_auth_user', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_create_service_returns()
	{
		return new external_single_structure(
			array(
				'token'     => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge')),
				'site_url'  => new external_value(PARAM_TEXT, get_string('moodle_url', 'local_edwiserbridge')),
				'service_id'  => new external_value(PARAM_INT, get_string('web_service_id', 'local_edwiserbridge')),
				'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
				'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
			)
		);
	}



	/**
	 * functionality to link existing services.
	 * @param  [type] $user_id
	 * @return [type]          
	 */
	public static function eb_link_service($service_id, $token)
	{
		$response['status'] = 0;
		$response['msg'] = get_string('eb_link_err', 'local_edwiserbridge');

		$settings_handler = new eb_settings_handler();
		$result = $settings_handler->eb_link_exitsing_service($service_id, $token);
		if ($result) {
			$response['status'] = 1;
			$response['msg'] = get_string('eb_link_success', 'local_edwiserbridge');
			return $response;
		}
		return $response;
	}

	public static function eb_link_service_parameters()
	{
		return new external_function_parameters(
			array(
				'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'local_edwiserbridge')),
				'token'      => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_link_service_returns()
	{
		return new external_single_structure(
			array(
				'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
				'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
			)
		);
	}


	/**
	 * functionality to link existing services.
	 * @param  [type] $user_id
	 * @return [type]          
	 */
	public static function eb_get_service_info($service_id)
	{
		$response['status'] = 1;
		$response['msg'] = '';

		$count = eb_get_service_info($service_id);
		if ($count) {
			$response['status'] = 0;
			$response['msg'] = $count . get_string('eb_service_info_error', 'local_edwiserbridge');
			return $response;
		}
		return $response;
	}

	public static function eb_get_service_info_parameters()
	{
		return new external_function_parameters(
			array(
				'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'local_edwiserbridge')),
				// 'token'      => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_get_service_info_returns()
	{
		return new external_single_structure(
			array(
				'status'  => new external_value(PARAM_INT, get_string('web_service_creation_status', 'local_edwiserbridge')),
				'msg'  => new external_value(PARAM_TEXT, get_string('web_service_creation_msg', 'local_edwiserbridge'))
			)
		);
	}



	/**
	 * functionality to link existing services.
	 * @param  [type] $user_id
	 * @return [type]          
	 */
	public static function eb_get_edwiser_plugins_info()
	{
		$response['plugin_name'] = 'edwiser_bridge';
		$response['version']     = '';

		$response['plugin_name'] = 'edwiser_bridge';
		$response['version']     = '2.0.7';

		return $response;
	}

	public static function eb_get_edwiser_plugins_info_parameters()
	{
		return new external_function_parameters(
			array(
				// 'service_id' => new external_value(PARAM_TEXT, get_string('web_service_id', 'local_edwiserbridge')),
				// 'token'      => new external_value(PARAM_TEXT, get_string('web_service_token', 'local_edwiserbridge'))
			)
		);
	}

	public static function eb_get_edwiser_plugins_info_returns()
	{
		return new external_single_structure(
			array(
				'plugin_name'  => new external_value(PARAM_TEXT, get_string('eb_plugin_name', 'local_edwiserbridge')),
				'version'  => new external_value(PARAM_TEXT, get_string('eb_plugin_version', 'local_edwiserbridge'))
			)
		);
	}

}

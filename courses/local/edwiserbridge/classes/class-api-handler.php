<?php

/**
*
*/
class api_handler
{
    //Returns instance of the class if already created
    protected static $instance = null;

    //creates insce of the class
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * functionality which creates request to connect to the wordpress
     * @return [type]               [description]
     */
    public function connect_to_wp_with_args($request_url, $request_data)
    {
        global $CFG;
        $success          = 1;
        $response_message = 'success';
        $request_url .= '/wp-json/edwiser-bridge/wisdmlabs/';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $request_url,
            CURLOPT_TIMEOUT => 100
        ));


        // curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request_data);
        $response = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);


// var_dump('response ::: '.print_r($response, 1));


        if (curl_error($curl)) {
            $error_msg = curl_error($curl);
            curl_close($curl);
            return array("error"=> 1, "msg" => $error_msg );
        } else {
            curl_close($curl);

            if ("200" == $status_code) {
                return array("error"=> 0, "data" => json_decode($response));
            } else {
                $msg = get_string("default_error", "local_edwiserbridge");
                return array("error"=> 1, "msg" => $msg);
            }
        }
    }
}
